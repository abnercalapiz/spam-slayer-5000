# Changelog

All notable changes to Spam Slayer 5000 will be documented in this file.

## [1.1.9] - 2025-01-26

### 🐛 Bug Fixes
- **Fixed Plugin Activation Error**: Fixed missing Parsedown vendor files causing "Failed to open stream" error
- **Fixed .gitignore**: Updated to properly include plugin update checker vendor directory

### 🔧 Technical
- Added plugin update checker vendor files to repository
- Ensures all required files are included in the plugin package

## [1.1.8] - 2025-01-26

### 🚀 New Features
- **Australian Data Validation**: Added comprehensive validation for Australian businesses
  - Validates Australian addresses (street, suburb, state, postcode)
  - Validates Australian phone numbers (mobile, landline, and special numbers)
  - Validates business ABN (Australian Business Number) with live lookup
  - Validates company names against Australian Business Register
- **Company Name Verification**: Automatically verifies business names exist in ABR when no ABN is provided
- **Smart Fuzzy Matching**: Uses 85% similarity threshold for company name matching to handle variations

### 🔧 Changes
- **Removed Dashboard Widget**: The Spam Slayer 5000 overview widget has been removed from the WordPress dashboard
- **Settings Enhancement**: Added new settings in Advanced tab for Australian validation features
  - Toggle to enable/disable Australian validation
  - Field for ABN Lookup API key configuration

### 🛡️ Security Improvements
- Added validation to prevent spam submissions using fake Australian business details
- Blocks submissions with non-existent companies or inactive ABNs
- Validates postcode matches the selected Australian state

## [1.1.7] - 2025-01-26

### 🐛 Bug Fixes
- **Fixed Plugin Update Checker Error**: Fixed "Class 'Parsedown' not found" error that occurred during update checks
- **Fixed GitHub API Integration**: Properly loads Parsedown dependency for changelog parsing

### 🔧 Technical
- Added vendor dependency loading to plugin update checker
- Ensures Parsedown class is available for release note parsing

## [1.1.6] - 2025-01-26

### 🚨 Breaking Changes
- **Removed Email Notifications**: The plugin no longer sends email notifications for high spam scores
- **Removed Daily Reports**: Daily analytics email reports have been discontinued

### 🔧 Changes
- **Removed Features**:
  - Daily analytics reports (email)
  - Email notifications for spam submissions
  - Notification email and threshold settings
- **Default Settings**:
  - Daily Budget Limit now defaults to 0 (unlimited) instead of $10.00
- **Settings Page**:
  - Removed "Reports" section from Advanced settings tab
  - Removed "Notification Email" field from Advanced settings
  - Removed "Notification Threshold" field from Advanced settings

### 🧹 Code Cleanup
- Removed all notification-related functionality from codebase
- Removed daily report generation and sending methods
- Added automatic cleanup of deprecated options on plugin update

## [1.1.5] - 2025-01-24

### 🚀 New Features
- **Duplicate Detection System**: Added intelligent duplicate submission detection to catch repeated spam attempts
  - Checks for matching name, email, phone, and message content
  - Configurable time window (default: 60 seconds)
  - Uses similarity scoring for near-duplicate detection
- **Clear Cache Button**: Added cache management in admin settings
  - Shows current cache statistics (entries and size)
  - One-click cache clearing for testing
  - Visual feedback with success/error messages

### 🔧 Improvements
- **Enhanced Spam Detection Accuracy**:
  - Improved AI prompts with 17 specific spam indicators
  - Better detection of generic greetings and vague compliments
  - Added patterns for SEO/marketing offers and link building requests
  - Smarter handling of legitimate test submissions
- **Better Legitimate Email Handling**:
  - AI now explicitly avoids flagging messages with specific business context
  - Test submissions are less likely to be marked as spam
  - Reduced false positives for genuine inquiries

### 🐛 Bug Fixes
- Fixed view modal showing incorrect submission details
- Fixed modal content persistence between different submission views
- Fixed JavaScript parameter mismatch in AJAX calls
- Added support for ID filtering in database queries

### 🔄 Technical Changes
- Reduced duplicate detection sensitivity (3→5 submissions threshold)
- Decreased duplicate penalty points (40→20)
- Shortened duplicate check time window (300→60 seconds)
- Added exception for test messages in rule-based validation

## [1.1.4] - 2025-01-24

### 🐛 Bug Fixes
- Fixed SQL syntax errors in database operations
- Improved query preparation for better security

## [1.1.3] - 2025-01-24

### 🐛 Bug Fixes
- Fixed PHP fatal error in form validation

## [1.1.2] - 2025-01-24

### 🐛 Bug Fixes
- Fixed blocklist functionality issues
- Improved blocklist validation

## [1.1.1] - 2025-01-24

### 🐛 Bug Fixes
- Fixed critical error in update checker

## [1.1.0] - 2025-01-24

### 🚀 New Features
- **Blocklist Feature**: Added email and IP blocking functionality
  - Block specific email addresses or domains
  - Block IP addresses from submitting forms
  - Manage blocklist from admin interface
  - Import/export blocklist entries

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