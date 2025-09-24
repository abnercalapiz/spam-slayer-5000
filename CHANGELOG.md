# Changelog

All notable changes to Smart Form Shield will be documented in this file.

## [1.0.3] - 2025-01-24

### 🧪 Test Release
- Test release to verify GitHub updater functionality
- No functional changes from v1.0.2
- Used for testing automatic update detection and installation

## [1.0.2] - 2025-01-24

### 🐛 Bug Fixes
- Fixed update detection issues on some WordPress sites
- Improved transient cache handling for GitHub API responses
- Added consistent transient keys throughout the updater

### 🚀 New Features
- Added "Check for updates" link on plugins page for manual update checks
- Added debug logging when WP_DEBUG is enabled
- Force cache clearing after plugin updates

### 🔧 Improvements
- Better error handling and logging in GitHub updater
- Automatic clearing of WordPress update cache after checking
- Added troubleshooting guide for update issues

### 📝 Documentation
- Created TROUBLESHOOTING-UPDATES.md with detailed solutions
- Added debugging steps for update detection issues

## [1.0.1] - 2025-01-24

### 🚀 New Features
- Added GitHub updater system for automatic plugin updates from GitHub releases
- Plugin can now check for updates directly from GitHub repository
- One-click updates through WordPress admin dashboard

### 🐛 Bug Fixes
- Fixed GitHub zip extraction issue that caused "Failed to open stream" error
- Improved folder renaming logic for GitHub downloads

### 🔧 Improvements
- Added comprehensive GitHub updater guide for reuse in other plugins
- Removed optional settings page configuration - updater now only uses plugin header
- Enhanced error handling in updater class

### 📝 Documentation
- Created GITHUB-UPDATER-GUIDE.md with full implementation instructions
- Added troubleshooting section for common update issues

## [1.0.0] - 2025-01-24

### 🎉 Initial Release
- Intelligent AI-powered spam filtering
- Support for OpenAI, Claude, and Gemini APIs
- Gravity Forms integration
- Elementor Forms integration
- Whitelist management
- Analytics dashboard
- API usage tracking and cost monitoring
- Daily reports via email
- Caching system for API responses