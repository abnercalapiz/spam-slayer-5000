# Smart Form Shield

**Intelligent AI-powered spam filtering for WordPress contact forms**

Smart Form Shield is a WordPress plugin that provides advanced spam protection for your contact forms using AI providers including OpenAI and Claude (Anthropic).

**Author:** [Jezweb](https://www.jezweb.com.au/)  
**Version:** 1.0.1  
**Requires WordPress:** 5.8 or higher  
**Requires PHP:** 7.4 or higher  
**License:** GPL-2.0+  
**GitHub:** [abnercalapiz/smart-form-field](https://github.com/abnercalapiz/smart-form-field)

## Features

### 🛡️ Advanced Spam Protection
- **AI-Powered Analysis**: Leverages cutting-edge AI models to detect spam with high accuracy
- **Multiple Provider Support**: Choose from OpenAI or Claude (Anthropic)
- **Smart Scoring System**: Configurable spam threshold with detailed scoring (0-100)
- **Real-time Detection**: Instant spam analysis during form submission

### 🤖 Multi-AI Provider Support
- **OpenAI**: Support for GPT models
- **Claude**: Claude 3.5 Haiku, Claude 3 Haiku, and other Claude models
- **Automatic Fallback**: Seamless switching between providers if one fails

### 📝 Form Integration
- **Gravity Forms**: Automatic integration with form-specific settings
- **Elementor Pro Forms**: Full support for Elementor form builder
- **Per-Form Configuration**: Customize spam thresholds for individual forms

### 📊 Analytics & Reporting
- **Comprehensive Dashboard**: View all submissions at a glance
- **Detailed Analytics**: Track spam rates, API usage, and costs
- **Visual Charts**: Daily trends and spam score distribution
- **Export Options**: Download data in CSV or JSON format

### ⚙️ Advanced Features
- **Email Whitelist**: Automatically approve trusted emails/domains
- **Response Caching**: Reduce API costs by caching similar submissions
- **Bulk Actions**: Approve, mark as spam, or whitelist multiple submissions
- **Cost Tracking**: Monitor API usage and expenses
- **Daily Reports**: Optional email summaries of spam activity
- **Automatic Updates**: Update directly from GitHub releases (v1.0.1+)

### 🔒 Security & Privacy
- **Encrypted API Keys**: All API credentials are securely encrypted
- **Data Retention Control**: Automatic cleanup of old submissions
- **IP Tracking**: Optional IP address logging for security
- **GDPR Compliant**: Configurable data handling and retention

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- At least one AI provider API key (OpenAI or Claude)
- Gravity Forms or Elementor Pro (for form integration)

## Installation

### From GitHub Release (Recommended)
1. Download the latest release from [GitHub Releases](https://github.com/abnercalapiz/smart-form-field/releases/latest)
2. Upload the ZIP file through WordPress Admin → Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Configure your AI provider API keys

### Manual Installation
1. Upload the `smart-form-shield` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Form Shield** in your WordPress admin menu
4. Configure at least one AI provider with your API key
5. Enable the form integrations you want to protect

## Configuration

### Quick Start

1. **Get API Keys**: Sign up for at least one AI provider:
   - [OpenAI API](https://platform.openai.com/api-keys) - For GPT models
   - [Claude API](https://console.anthropic.com/) - For Claude models

2. **Add API Key**: 
   - Go to **Form Shield → Settings → API Settings**
   - Enter your API key for OpenAI and/or Claude
   - Select your preferred model
   - Test the connection

3. **Configure Protection**: 
   - Set spam threshold (default: 75)
   - Choose primary and fallback providers
   - Enable form integrations
   - Configure email whitelist if needed

### Spam Threshold Guide

- **60-69**: Strict - Catches more spam but may flag some legitimate messages
- **70-75**: Balanced (Default) - Good for most websites
- **76-85**: Lenient - Only blocks obvious spam
- **86-95**: Very Lenient - Minimal false positives

## Usage

### Gravity Forms Integration

1. Protection is automatic for all forms once enabled
2. Configure per-form settings:
   - Edit any Gravity Form
   - Go to **Settings → Smart Form Shield**
   - Enable/disable protection or set custom threshold

### Elementor Forms Integration

1. Works automatically with all Elementor Pro forms
2. No additional configuration needed
3. Compatible with all Elementor form actions

### Managing Submissions

Access **Form Shield → Submissions** to:
- View all form submissions with spam scores
- Filter by status (spam, approved, whitelist)
- Search submissions by content
- View detailed AI analysis for each submission
- Take actions: Approve, Mark as Spam, Add to Whitelist
- Perform bulk operations

### Analytics

View insights at **Form Shield → Analytics**:
- Total submissions and spam rate
- API costs and usage
- Provider performance metrics
- Daily submission trends
- Spam score distribution
- Form-specific statistics

## API Providers

### OpenAI
- Models: GPT-3.5, GPT-4 (when available)
- Pricing: Based on tokens used
- Best for: General spam detection

### Claude (Anthropic)
- Models: Claude 3 Haiku ($0.25/1M input, $1.25/1M output tokens)
- Excellent accuracy with competitive pricing
- Best for: High-volume sites needing cost efficiency

## Troubleshooting

### "Invalid analysis format" Error
- Check that your API key is valid and has credits
- Ensure you're using a supported model
- Verify your server can make HTTPS requests

### High False Positive Rate
1. Increase spam threshold (e.g., 75 to 80)
2. Add legitimate email domains to whitelist
3. Review spam reasons in submission details

### No IP Address Showing
- This is normal on localhost (shows ::1)
- Production sites will show real visitor IPs

## Developer Reference

### Hooks and Filters

```php
// Modify spam threshold globally
add_filter('smart_form_shield_spam_threshold', function($threshold) {
    return 80; // Custom threshold
});

// Customize provider selection
add_filter('smart_form_shield_primary_provider', function($provider) {
    return 'claude'; // Use Claude as primary
});
```

### Database Tables

The plugin creates three tables:
- `wp_sfs_submissions` - Form submission records
- `wp_sfs_api_logs` - API usage tracking
- `wp_sfs_whitelist` - Whitelisted emails/domains

## Frequently Asked Questions

**Q: Which AI provider should I choose?**  
A: Both work well. Claude 3 Haiku offers excellent performance at low cost. OpenAI provides good accuracy but verify you're using valid model names.

**Q: Will this slow down form submissions?**  
A: The AI analysis typically takes 1-3 seconds. With caching enabled, repeated spam attempts are blocked instantly.

**Q: How can I reduce API costs?**  
A: Enable response caching, set appropriate data retention, and use Claude 3 Haiku for the best cost/performance ratio.

**Q: Is my data secure?**  
A: Yes. API keys are encrypted, data is sent over HTTPS only, and you control data retention periods.

## Support

For support, feature requests, or bug reports:
- **Website**: [Jezweb](https://www.jezweb.com.au/)
- **Email**: Contact through the website

## Automatic Updates

Starting with version 1.0.1, Smart Form Shield supports automatic updates directly from GitHub:

- Updates are checked automatically every 12 hours
- Update notifications appear in your WordPress admin dashboard
- One-click updates through the standard WordPress update system
- No configuration required - updates work automatically

## Changelog

### Version 1.0.1 - 2025-01-24
- Added GitHub updater system for automatic plugin updates
- Plugin can now check for updates directly from GitHub repository
- Fixed GitHub zip extraction issue during updates
- Added comprehensive GitHub updater guide for developers
- Enhanced error handling in updater class
- Created detailed documentation for implementation

### Version 1.0.0 - 2025-01-24
- Initial release
- Support for OpenAI and Claude AI providers
- Gravity Forms and Elementor Pro integrations
- Comprehensive analytics dashboard
- Email whitelist management
- Response caching system
- Bulk actions for submissions
- Cost tracking and reporting

## License

This plugin is licensed under the GPL v2 or later.

---

**Smart Form Shield** - Protecting your WordPress forms with intelligent AI-powered spam detection.

Developed with ❤️ by [Jezweb](https://www.jezweb.com.au/)