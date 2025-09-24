# Troubleshooting GitHub Updates

If updates are not being detected on some WordPress sites, follow this guide to diagnose and fix the issue.

## Quick Solutions

### 1. Force Update Check
After releasing a new version on GitHub, you can force WordPress to check for updates:

- Go to **Plugins** page in WordPress admin
- Find your plugin
- Click **"Check for updates"** link (added in v1.0.2)

OR manually clear caches:

```php
// Add to functions.php temporarily
add_action('init', function() {
    if (current_user_can('update_plugins')) {
        delete_site_transient('update_plugins');
        delete_transient('sfs_gh_' . md5('abnercalapiz/smart-form-field'));
        wp_update_plugins();
    }
});
```

### 2. Verify Update URI
Check that the Update URI in your plugin header is correct:

```php
/**
 * Plugin Name: Your Plugin
 * Version: 1.0.1
 * Update URI: https://github.com/yourusername/your-repo
 */
```

Common mistakes:
- Wrong repository name
- Private repository without access token
- Trailing slash in URL
- Using .git extension

### 3. Check GitHub Release Format
Ensure your GitHub release follows the correct format:
- Tag: `v1.0.2` (with 'v' prefix)
- Tag must be higher version than installed
- Release must be published (not draft)

## Common Issues and Solutions

### Issue 1: Transient Cache
**Problem**: GitHub API results are cached for 12 hours
**Solution**: 
- Click "Check for updates" link
- Or wait 12 hours
- Or clear transients manually

### Issue 2: WordPress Update Schedule
**Problem**: WordPress only checks updates twice daily
**Solution**:
```php
// Force immediate check
wp_update_plugins();
```

### Issue 3: Version Comparison
**Problem**: Version numbers not comparing correctly
**Solution**: 
- Use semantic versioning: 1.0.0, 1.0.1, 1.0.2
- GitHub tag: v1.0.2
- Plugin header: 1.0.2 (without 'v')

### Issue 4: API Rate Limits
**Problem**: GitHub API rate limit exceeded (60/hour unauthenticated)
**Solution**: 
- Wait an hour
- Add access token for 5000/hour limit
- Check current limit: https://api.github.com/rate_limit

### Issue 5: Server Configuration
**Problem**: Server can't connect to GitHub API
**Solution**:
- Check if `wp_remote_get()` works
- Verify SSL certificates are valid
- Check firewall allows GitHub API

## Debugging Steps

### 1. Enable WordPress Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 2. Check Error Logs
Look for entries like:
- `Smart Form Shield GitHub Updater: Username=X, Repo=Y`
- `Smart Form Shield GitHub Updater: No tag_name in response`

### 3. Test GitHub API Directly
Visit in browser:
```
https://api.github.com/repos/yourusername/your-repo/releases/latest
```

Should return JSON with:
- `tag_name`: "v1.0.2"
- `zipball_url`: Download URL
- `assets`: Array of release assets

### 4. Check Transients
```php
// Check if cached data exists
$transient = get_transient('sfs_gh_' . md5('yourusername/your-repo'));
var_dump($transient);
```

### 5. Verify Plugin File Structure
After update, check folder name matches:
- Expected: `/wp-content/plugins/your-plugin/your-plugin.php`
- Not: `/wp-content/plugins/your-plugin-1/your-plugin.php`

## Manual Update Check Code

Add this to test updates:

```php
add_action('admin_init', function() {
    if (!current_user_can('update_plugins')) return;
    
    // Clear all update caches
    delete_site_transient('update_plugins');
    
    // Clear GitHub cache for specific plugin
    $github_transient = 'sfs_gh_' . md5('abnercalapiz/smart-form-field');
    delete_transient($github_transient);
    
    // Force check
    wp_update_plugins();
    
    // Check if update is available
    $updates = get_site_transient('update_plugins');
    if (isset($updates->response['smart-form-shield/smart-form-shield.php'])) {
        echo 'Update available!';
    } else {
        echo 'No update found';
    }
});
```

## Fixes Implemented in v1.0.2

1. **"Check for updates" link** on plugins page
2. **Consistent transient keys** throughout the code
3. **Better error logging** when WP_DEBUG is enabled
4. **Automatic cache clearing** after updates
5. **Improved folder renaming** for GitHub zip structure

## Testing Updates

1. Install version 1.0.1
2. Create GitHub release v1.0.2
3. Click "Check for updates" on plugins page
4. Should see update notification immediately
5. Click "Update Now"
6. Verify update completes successfully

## Still Having Issues?

If updates still don't appear:

1. Check the Update URI is exactly: `https://github.com/abnercalapiz/smart-form-field`
2. Verify the repository is public
3. Ensure tag format is `v1.0.2` (with v prefix)
4. Check server time is correct (affects transients)
5. Try on a different WordPress installation
6. Enable WP_DEBUG and check error logs

## Support

For persistent issues:
1. Check debug.log for errors
2. Test API endpoint manually
3. Verify transient storage is working
4. Check file permissions