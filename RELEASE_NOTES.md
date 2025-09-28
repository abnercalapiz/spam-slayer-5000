# GitHub Release Creation Guide

Since GitHub CLI is not authenticated, you need to manually create releases on GitHub. Here are the steps and release notes for each version:

## Release Notes for v1.0.9 (LATEST)

**Release Title:** Spam Slayer 5000 v1.0.9

**Release Notes:**
```markdown
## Release v1.0.9

### What's New

- **Release Management**: Added automated release creation script for easier GitHub releases
- **Documentation**: Added comprehensive release notes documentation (RELEASE_NOTES.md)
- **Update System**: Improved plugin update detection with proper GitHub release support
- **Developer Tools**: Added create-release.sh script for automated release creation
- **Instructions**: Better documentation for creating future releases

### Why This Release?

This release focuses on improving the plugin's update mechanism and release process. Previous versions had tags but no GitHub releases, which prevented the automatic update system from working properly. This version ensures smooth updates going forward.

### Installation

1. Download the `spam-slayer-5000.zip` file from the Assets section below
2. In WordPress Admin, go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Configure your AI provider API keys in Spam Slayer 5000 → Settings

### Update from Previous Version

Once this release is published on GitHub, your WordPress site will automatically detect the update within 12 hours. You can also manually check by clicking "Check for updates" in the Plugins page.

### Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- At least one AI provider API key (OpenAI or Claude)

### Notes for Developers

- Use the included `create-release.sh` script for future releases (requires GitHub CLI)
- See RELEASE_NOTES.md for detailed release creation instructions
```

## How to Create a Release on GitHub

1. Go to: https://github.com/abnercalapiz/spam-slayer-5000/releases/new
2. Choose the tag (e.g., v1.0.8)
3. Set release title: "Spam Slayer 5000 v1.0.8" (adjust version)
4. Copy the release notes below
5. Create a ZIP file of the plugin:
   ```bash
   cd /path/to/parent/directory
   zip -r spam-slayer-5000.zip spam-slayer-5000/ -x "*.git*" "*.DS_Store"
   ```
6. Attach the ZIP file to the release
7. Publish the release

## Release Notes for v1.0.8

**Release Title:** Spam Slayer 5000 v1.0.8

**Release Notes:**
```markdown
## Release v1.0.8

### What's Fixed

- **Submissions Page Actions**: Fixed View and Approve buttons that were not responding to clicks
- **Whitelist Page**: Fixed Remove button functionality 
- **JavaScript Updates**: Corrected modal element IDs from sfs- to ss5k- prefix
- **AJAX Improvements**: Fixed action name for whitelist removal (ss5k_remove_from_whitelist)
- **CSS Consistency**: Updated modal styles to use consistent ss5k- prefixes

### Installation

1. Download the `spam-slayer-5000.zip` file from the Assets section below
2. In WordPress Admin, go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
4. Configure your AI provider API keys in Spam Slayer 5000 → Settings

### Update from Previous Version

The plugin will automatically notify you of this update in your WordPress admin dashboard. Click "Update Now" to get the latest fixes.

### Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- At least one AI provider API key (OpenAI or Claude)
```

## Release Notes for v1.0.7

**Release Title:** Spam Slayer 5000 v1.0.7

**Release Notes:**
```markdown
## Release v1.0.7

### What's New

- **Settings Feedback**: Added success notification "Settings saved successfully!" when settings are saved
- **Analytics Page Fix**: Resolved critical errors on the Analytics page
- **Database Improvements**: Added safe table name retrieval method to prevent undefined constant errors
- **SQL Query Updates**: Fixed spam score distribution ranges and GROUP BY clauses
- **Error Handling**: Improved error handling for missing database methods

### Technical Details

- Added `get_table_name()` static method to Database class for safe table access
- Updated all Analytics SQL queries to use safe table name method
- Added `settings_updated_notice()` method for settings save feedback

### Installation

1. Download the `spam-slayer-5000.zip` file from the Assets section below
2. In WordPress Admin, go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
```

## Release Notes for v1.0.6

**Release Title:** Spam Slayer 5000 v1.0.6

**Release Notes:**
```markdown
## Release v1.0.6

### What's Fixed

- **Analytics Page Error**: Fixed critical error when accessing Analytics page
- **Plugin Directory Naming**: Enhanced the upgrader filter to ensure consistent 'spam-slayer-5000' directory
- **Error Handling**: Added comprehensive try-catch blocks and fallback data structures
- **Database Checks**: Added verification of database tables before running queries

### Installation

1. Download the `spam-slayer-5000.zip` file from the Assets section below
2. In WordPress Admin, go to Plugins → Add New → Upload Plugin
3. Upload the ZIP file and activate the plugin
```