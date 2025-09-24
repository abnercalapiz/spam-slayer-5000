# GitHub Updater Guide for WordPress Plugins

This guide explains how to implement automatic updates from GitHub releases for your WordPress plugins. The implementation allows your plugins to check for updates directly from GitHub and update through the WordPress admin dashboard.

## Features

- ✅ Automatic update checks from GitHub releases
- ✅ Update notifications in WordPress admin
- ✅ One-click updates through WordPress dashboard
- ✅ Support for public and private repositories
- ✅ Version comparison and changelog display
- ✅ Cache to minimize API calls (12-hour cache)
- ✅ No configuration required - just uses plugin header

## Quick Setup (3 Steps)

### 1. Add Update URI to Plugin Header

In your main plugin file, add the `Update URI` header pointing to your GitHub repository:

```php
/**
 * Plugin Name:       Your Plugin Name
 * Plugin URI:        https://yourwebsite.com/
 * Description:       Your plugin description
 * Version:           1.0.0
 * Author:            Your Name
 * Update URI:        https://github.com/yourusername/your-plugin-repo
 */
```

### 2. Copy the GitHub Updater Class

Copy the `class-github-updater.php` file to your plugin's includes directory and initialize it:

```php
// In your plugin's main class or bootstrap file
require_once plugin_dir_path( __FILE__ ) . 'includes/class-github-updater.php';

// Initialize the updater (use plugin_basename(__FILE__) if calling from main plugin file)
new Your_Plugin_GitHub_Updater( plugin_basename( __FILE__ ) );
```

### 3. Create GitHub Releases

In your GitHub repository:
1. Go to Releases → Create a new release
2. Tag version: `v1.0.1` (or your version number)
3. Release title: Version 1.0.1
4. Describe changes in the release body
5. Attach a `.zip` file of your plugin (optional, will use source code if not provided)
6. Publish release

That's it! Your plugin will now check for updates from GitHub.

## Full Implementation Guide

### Step 1: Create the GitHub Updater Class

Create `includes/class-github-updater.php` in your plugin with the following content:

```php
<?php
/**
 * GitHub Updater Class
 *
 * Replace "Your_Plugin" with your actual plugin prefix
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Your_Plugin_GitHub_Updater {
    
    private $slug;
    private $plugin_data;
    private $plugin_file;
    private $username;
    private $repo;
    private $github_api_result;
    private $access_token;
    
    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
        $this->slug        = plugin_basename( dirname( $plugin_file ) );
        
        add_action( 'init', array( $this, 'set_plugin_properties' ) );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
        add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip' ), 10, 3 );
        add_action( 'upgrader_process_complete', array( $this, 'purge_transients' ), 10, 2 );
    }
    
    public function set_plugin_properties() {
        $this->plugin_data = get_plugin_data( $this->plugin_file );
        $this->set_github_properties();
    }
    
    private function set_github_properties() {
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
    }
    
    private function get_github_release_info() {
        if ( empty( $this->username ) || empty( $this->repo ) ) {
            return false;
        }
        
        if ( ! empty( $this->github_api_result ) ) {
            return $this->github_api_result;
        }
        
        // Check transient first
        $transient_key = 'your_plugin_github_' . md5( $this->username . '/' . $this->repo );
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
            return false;
        }
        
        $this->github_api_result = $github_data;
        set_transient( $transient_key, $github_data, HOUR_IN_SECONDS * 12 );
        
        return $github_data;
    }
    
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
    
    private function parse_changelog( $body ) {
        $changelog = '<h4>' . __( 'Changelog', 'your-plugin-textdomain' ) . '</h4>';
        $changelog .= '<pre>' . esc_html( $body ) . '</pre>';
        return $changelog;
    }
    
    public function rename_github_zip( $source, $remote_source, $upgrader ) {
        global $wp_filesystem;
        
        if ( strpos( $source, $this->repo ) === false ) {
            return $source;
        }
        
        $corrected_source = trailingslashit( $remote_source ) . $this->slug . '/';
        
        if ( $wp_filesystem->move( $source, $corrected_source ) ) {
            return $corrected_source;
        }
        
        return $source;
    }
    
    public function purge_transients( $upgrader, $options ) {
        if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
            $transient_key = 'your_plugin_github_' . md5( $this->username . '/' . $this->repo );
            delete_transient( $transient_key );
        }
    }
}
```

### Step 2: Add Plugin Headers

Update your main plugin file with the required headers:

```php
<?php
/**
 * Plugin Name:       Your Plugin Name
 * Plugin URI:        https://yourwebsite.com/
 * Description:       Your plugin description
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       your-plugin-textdomain
 * Update URI:        https://github.com/yourusername/your-plugin-repo
 */

// Plugin code...
```

**Important**: The `Update URI` must point to your GitHub repository URL.

### Step 3: Initialize the Updater

In your main plugin file or initialization code:

```php
// Basic initialization (if in main plugin file)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-github-updater.php';
new Your_Plugin_GitHub_Updater( __FILE__ );

// Or in a class context
public function __construct() {
    $this->init_updater();
}

private function init_updater() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-github-updater.php';
    new Your_Plugin_GitHub_Updater( plugin_basename( __FILE__ ) );
}
```

### Step 4: Create GitHub Releases

The updater will use the Update URI from your plugin header to check for updates. Make sure it points to your GitHub repository.

## Creating GitHub Releases

### Release Format

1. **Tag Version**: Use semantic versioning with 'v' prefix (e.g., `v1.2.3`)
2. **Release Title**: Clear description (e.g., "Version 1.2.3 - Bug Fixes")
3. **Release Body**: Changelog in markdown format
4. **Assets**: Optionally attach a `.zip` file of your plugin

### Example Release Process

```bash
# 1. Update version in your plugin header
# Version: 1.2.3

# 2. Commit your changes
git add .
git commit -m "Bump version to 1.2.3"

# 3. Create and push a tag
git tag v1.2.3
git push origin v1.2.3

# 4. Create release on GitHub
# - Go to https://github.com/yourusername/your-repo/releases/new
# - Select the tag v1.2.3
# - Add release notes
# - Optionally attach plugin .zip file
# - Publish release
```

### Release Notes Template

```markdown
## What's New in Version 1.2.3

### 🚀 New Features
- Added feature X
- Improved feature Y

### 🐛 Bug Fixes
- Fixed issue with Z
- Resolved compatibility issue with WordPress 6.3

### 🔧 Improvements
- Enhanced performance of feature A
- Updated translations

### ⚠️ Breaking Changes
- Minimum PHP version is now 7.4
```

## Private Repository Support

For private repositories, you need a GitHub personal access token:

1. Go to GitHub → Settings → Developer settings → Personal access tokens
2. Generate new token with `repo` scope
3. Add token to the updater class (hardcode it or use a constant)

```php
// In the updater class
private $access_token = 'your_personal_access_token';

// Or use a constant defined in wp-config.php
private $access_token = defined( 'YOUR_PLUGIN_GITHUB_TOKEN' ) ? YOUR_PLUGIN_GITHUB_TOKEN : '';
```

## Testing Your Implementation

1. **Install Plugin**: Install your plugin with version 1.0.0
2. **Create Release**: Create a GitHub release with version 1.0.1
3. **Check Updates**: Go to WordPress Dashboard → Updates
4. **Verify**: Your plugin should appear with an available update
5. **Update**: Click update and verify it installs correctly

## Troubleshooting

### Updates Not Showing

1. **Check Update URI**: Verify it's correct in plugin header
2. **Version Format**: Ensure GitHub release tag matches format (v1.0.0)
3. **Clear Cache**: Delete transient to force recheck
   ```php
   delete_transient( 'your_plugin_github_' . md5( 'username/repo' ) );
   ```
4. **Debug Mode**: Enable WordPress debugging to see errors

### API Rate Limits

GitHub API has rate limits:
- **Unauthenticated**: 60 requests/hour
- **Authenticated**: 5,000 requests/hour

The updater caches results for 12 hours to minimize API calls.

### Version Comparison Issues

- Always use semantic versioning (1.0.0, not 1.0)
- Tag versions with 'v' prefix (v1.0.0)
- Plugin header version should match release version (without 'v')

### Common Errors

1. **"No update available"**: Check version numbers and Update URI
2. **"Download failed"**: Verify release has a zip asset or repository is public
3. **"Failed to open stream: No such file or directory"**: This happens when the GitHub zip structure doesn't match. The updater class should handle this automatically with the `rename_github_zip` method
4. **"Unpacking failed"**: Ensure plugin folder name matches your plugin's directory name

## Security Best Practices

1. **Never commit tokens**: Use environment variables or constants
2. **Validate updates**: The updater verifies source matches GitHub
3. **Use HTTPS**: Always use https:// URLs
4. **Sanitize inputs**: All inputs are sanitized by the updater

## Customization Options

### Change Cache Duration

Modify the cache duration in `get_github_release_info()`:

```php
// 24 hours instead of 12
set_transient( $transient_key, $github_data, HOUR_IN_SECONDS * 24 );
```

### Add Custom Headers

Add additional headers in `get_github_release_info()`:

```php
$args['headers']['User-Agent'] = 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url();
```

### Filter Release Assets

Only accept specific asset names:

```php
foreach ( $github_data['assets'] as $asset ) {
    if ( $asset['name'] === $this->slug . '.zip' ) {
        $download_url = $asset['browser_download_url'];
        break;
    }
}
```

### Multiple Update Channels

Support beta releases:

```php
// Check for beta releases based on an option or constant
$prerelease = defined( 'YOUR_PLUGIN_BETA' ) && YOUR_PLUGIN_BETA;
$url = $prerelease 
    ? "https://api.github.com/repos/{$this->username}/{$this->repo}/releases"
    : "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
```

## Example Plugin Structure

Here's how to structure your plugin for GitHub updates:

```
your-plugin/
├── your-plugin.php          # Main file with Update URI header
├── includes/
│   ├── class-github-updater.php
│   └── class-your-plugin.php
├── admin/
├── public/
└── README.md
```

## Minimal Implementation Checklist

For the simplest implementation, you only need:

- [ ] Add `Update URI` header to your plugin
- [ ] Copy the updater class to your plugin
- [ ] Add this line to initialize: `new Your_Plugin_GitHub_Updater( __FILE__ );`
- [ ] Create a GitHub release with a higher version number

That's it! Your plugin will now update from GitHub releases.

## Important Notes

1. **Class Naming**: Replace `Your_Plugin` with your actual plugin prefix
2. **Text Domain**: Replace `your-plugin-textdomain` with your plugin's text domain
3. **Transient Prefix**: Change the transient prefix to avoid conflicts
4. **No Settings Page**: This implementation doesn't include any settings - it only uses the Update URI from the plugin header

## Support

If you encounter issues:

1. Enable WordPress debugging: `define( 'WP_DEBUG', true );`
2. Check error logs for API failures
3. Verify GitHub API responses using: `https://api.github.com/repos/username/repo/releases/latest`
4. Test with a public repository first

This implementation is compatible with WordPress 5.8+ and PHP 7.4+.