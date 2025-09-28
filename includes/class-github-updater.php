<?php
/**
 * GitHub Updater Class
 *
 * Handles automatic updates from GitHub releases
 *
 * @package Spam_Slayer_5000
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Spam_Slayer_5000_GitHub_Updater {
    
    /**
     * Plugin slug
     *
     * @var string
     */
    private $slug;
    
    /**
     * Plugin data from get_plugin_data()
     *
     * @var array
     */
    private $plugin_data;
    
    /**
     * Plugin file
     *
     * @var string
     */
    private $plugin_file;
    
    /**
     * GitHub username
     *
     * @var string
     */
    private $username;
    
    /**
     * GitHub repository name
     *
     * @var string
     */
    private $repo;
    
    /**
     * GitHub API result
     *
     * @var array
     */
    private $github_api_result;
    
    /**
     * Access token for private repositories
     *
     * @var string
     */
    private $access_token;
    
    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file.
     */
    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
        $this->slug        = plugin_basename( dirname( $plugin_file ) );
        
        add_action( 'init', array( $this, 'set_plugin_properties' ) );
        
        // Hook into WordPress update process
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
        add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip' ), 10, 3 );
        add_action( 'upgrader_process_complete', array( $this, 'purge_transients' ), 10, 2 );
        
        // Add manual update check
        add_action( 'admin_init', array( $this, 'maybe_force_check' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( $plugin_file ), array( $this, 'add_action_links' ) );
    }
    
    /**
     * Set plugin properties
     */
    public function set_plugin_properties() {
        $this->plugin_data = get_plugin_data( $this->plugin_file );
        $this->set_github_properties();
    }
    
    /**
     * Set GitHub properties
     */
    private function set_github_properties() {
        // Check plugin headers first
        if ( ! empty( $this->plugin_data['UpdateURI'] ) ) {
            $parsed_url = wp_parse_url( $this->plugin_data['UpdateURI'] );
            if ( 'github.com' === $parsed_url['host'] ) {
                $path_parts = explode( '/', trim( $parsed_url['path'], '/' ) );
                if ( count( $path_parts ) >= 2 ) {
                    $this->username = $path_parts[0];
                    $this->repo     = $path_parts[1];
                }
            }
        }
        
        // No fallback to options - only use Update URI from plugin header
        
        // Log for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Spam Slayer 5000 GitHub Updater: Username=' . $this->username . ', Repo=' . $this->repo );
        }
    }
    
    /**
     * Get GitHub release information
     *
     * @return array|bool
     */
    private function get_github_release_info() {
        if ( empty( $this->username ) || empty( $this->repo ) ) {
            return false;
        }
        
        if ( ! empty( $this->github_api_result ) ) {
            return $this->github_api_result;
        }
        
        // Check transient first
        $transient_key = $this->get_transient_key();
        $github_data = get_transient( $transient_key );
        
        if ( false !== $github_data ) {
            $this->github_api_result = $github_data;
            return $github_data;
        }
        
        // Make API request
        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        );
        
        if ( ! empty( $this->access_token ) ) {
            $args['headers']['Authorization'] = 'token ' . $this->access_token;
        }
        
        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
        $response = wp_remote_get( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $github_data = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( empty( $github_data['tag_name'] ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Spam Slayer 5000 GitHub Updater: No tag_name in response' );
            }
            return false;
        }
        
        $this->github_api_result = $github_data;
        set_transient( $transient_key, $github_data, HOUR_IN_SECONDS * 12 );
        
        return $github_data;
    }
    
    /**
     * Check for plugin updates
     *
     * @param object $transient WordPress update transient.
     * @return object
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }
        
        $github_data = $this->get_github_release_info();
        
        if ( false === $github_data ) {
            return $transient;
        }
        
        $latest_version = ltrim( $github_data['tag_name'], 'v' );
        $current_version = $this->plugin_data['Version'];
        
        if ( version_compare( $latest_version, $current_version, '>' ) ) {
            $download_url = '';
            
            // Look for zip asset
            if ( ! empty( $github_data['assets'] ) ) {
                foreach ( $github_data['assets'] as $asset ) {
                    if ( 'application/zip' === $asset['content_type'] || 
                         'application/x-zip-compressed' === $asset['content_type'] ||
                         preg_match( '/\.zip$/', $asset['name'] ) ) {
                        $download_url = $asset['browser_download_url'];
                        break;
                    }
                }
            }
            
            // Fallback to zipball URL
            if ( empty( $download_url ) ) {
                $download_url = $github_data['zipball_url'];
            }
            
            $plugin_data = array(
                'id'            => $this->plugin_file,
                'slug'          => $this->slug,
                'new_version'   => $latest_version,
                'url'           => $this->plugin_data['PluginURI'],
                'package'       => $download_url,
                'icons'         => array(),
                'tested'        => get_bloginfo( 'version' ),
                'requires_php'  => $this->plugin_data['RequiresPHP'],
            );
            
            $transient->response[ $this->plugin_file ] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * Provide plugin information for view details
     *
     * @param false|object|array $result The result object or array.
     * @param string             $action The type of information being requested.
     * @param object             $args   Plugin API arguments.
     * @return false|object|array
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }
        
        if ( $this->slug !== $args->slug ) {
            return $result;
        }
        
        $github_data = $this->get_github_release_info();
        
        if ( false === $github_data ) {
            return $result;
        }
        
        $plugin_info = array(
            'name'              => $this->plugin_data['Name'],
            'slug'              => $this->slug,
            'version'           => ltrim( $github_data['tag_name'], 'v' ),
            'author'            => $this->plugin_data['Author'],
            'homepage'          => $this->plugin_data['PluginURI'],
            'short_description' => $this->plugin_data['Description'],
            'sections'          => array(
                'description' => $this->plugin_data['Description'],
                'changelog'   => $this->parse_changelog( $github_data['body'] ),
            ),
            'download_link'     => $github_data['zipball_url'],
        );
        
        return (object) $plugin_info;
    }
    
    /**
     * Parse changelog from GitHub release body
     *
     * @param string $body Release body text.
     * @return string
     */
    private function parse_changelog( $body ) {
        $changelog = '<h4>' . __( 'Changelog', 'spam-slayer-5000' ) . '</h4>';
        $changelog .= '<pre>' . esc_html( $body ) . '</pre>';
        return $changelog;
    }
    
    /**
     * Rename the unzipped folder to match the plugin folder name
     *
     * @param string $source        File source location.
     * @param string $remote_source Remote file source location.
     * @param object $upgrader      WP_Upgrader instance.
     * @return string
     */
    public function rename_github_zip( $source, $remote_source, $upgrader ) {
        global $wp_filesystem;
        
        // Only process for our plugin
        if ( ! is_a( $upgrader, 'Plugin_Upgrader' ) ) {
            return $source;
        }
        
        // Get the list of directories in the source
        $dirlist = $wp_filesystem->dirlist( $remote_source );
        if ( ! $dirlist ) {
            return $source;
        }
        
        // Find the GitHub directory (usually repo-name-hash)
        $github_dir = '';
        foreach ( $dirlist as $filename => $fileinfo ) {
            if ( $fileinfo['type'] === 'd' && strpos( $filename, $this->repo ) === 0 ) {
                $github_dir = $filename;
                break;
            }
        }
        
        if ( empty( $github_dir ) ) {
            return $source;
        }
        
        // Build paths
        $from = trailingslashit( $remote_source ) . trailingslashit( $github_dir );
        $to = trailingslashit( $remote_source ) . trailingslashit( $this->slug );
        
        // Rename the folder
        if ( $wp_filesystem->move( $from, $to ) ) {
            return $to;
        }
        
        return $source;
    }
    
    /**
     * Purge transients after update
     *
     * @param object $upgrader WP_Upgrader instance.
     * @param array  $options  Array of update options.
     */
    public function purge_transients( $upgrader, $options ) {
        if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
            delete_transient( $this->get_transient_key() );
            delete_site_transient( 'update_plugins' );
        }
    }
    
    /**
     * Force check for updates when requested
     */
    public function maybe_force_check() {
        // Check if force update is requested
        if ( isset( $_GET['force-check'] ) && isset( $_GET['plugin'] ) && $_GET['plugin'] === $this->slug ) {
            if ( current_user_can( 'update_plugins' ) ) {
                delete_transient( $this->get_transient_key() );
                delete_site_transient( 'update_plugins' );
                
                // Force WordPress to check for updates
                wp_update_plugins();
                
                // Redirect to remove query args
                wp_redirect( remove_query_arg( array( 'force-check', 'plugin' ) ) );
                exit;
            }
        }
    }
    
    /**
     * Get transient key
     *
     * @return string
     */
    private function get_transient_key() {
        return 'ss5k_gh_' . md5( $this->username . '/' . $this->repo );
    }
    
    /**
     * Add plugin action links
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function add_action_links( $links ) {
        if ( current_user_can( 'update_plugins' ) ) {
            $url = add_query_arg( array(
                'force-check' => 1,
                'plugin' => $this->slug,
            ), admin_url( 'plugins.php' ) );
            
            $links[] = '<a href="' . esc_url( $url ) . '">' . __( 'Check for updates', 'spam-slayer-5000' ) . '</a>';
        }
        
        return $links;
    }
}