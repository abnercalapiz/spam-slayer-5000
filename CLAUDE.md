# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Smart Form Shield is a WordPress plugin that provides AI-powered spam filtering for contact forms using multiple AI providers (OpenAI, Claude, and Gemini).

## Key Architecture

### Plugin Structure
- Main plugin file: `smart-form-shield.php` - Entry point with WordPress hooks
- Core class: `includes/class-smart-form-shield.php` - Orchestrates all functionality
- Providers: `providers/` - AI provider implementations following common interface
- Integrations: `integrations/` - Form plugin integrations (Gravity Forms, Elementor)
- Admin: `admin/` - Dashboard, settings, and analytics functionality
- Database: `database/class-database.php` - All database operations

### AI Provider System
- All providers implement `Smart_Form_Shield_Provider_Interface`
- Factory pattern used for provider instantiation: `Smart_Form_Shield_Provider_Factory`
- Automatic fallback between providers if primary fails
- Cost tracking and token counting for each provider

### Form Integration Pattern
Each form integration follows this flow:
1. Hook into form validation
2. Extract submission data
3. Check whitelist
4. Check cache
5. Validate with AI provider
6. Log submission
7. Apply validation result

### Database Schema
Three main tables:
- `wp_sfs_submissions` - Form submission records
- `wp_sfs_api_logs` - API call logs and costs
- `wp_sfs_whitelist` - Whitelisted emails/domains

## Common Development Commands

```bash
# No build process required - this is a standard WordPress plugin

# To test the plugin:
# 1. Copy to wp-content/plugins/smart-form-shield/
# 2. Activate in WordPress admin

# Run WordPress coding standards check (if PHPCS installed):
phpcs --standard=WordPress smart-form-shield.php

# Create a zip for distribution:
zip -r smart-form-shield.zip smart-form-shield/ -x "*.git*" "*.DS_Store"
```

## Adding New Features

### Adding a New AI Provider
1. Create class in `providers/class-[provider]-provider.php` implementing `Smart_Form_Shield_Provider_Interface`
2. Add to factory in `providers/class-provider-factory.php`
3. Register settings in `admin/class-admin.php`
4. Add provider models and pricing info

### Adding a New Form Integration
1. Create integration class in `integrations/[form-name]/`
2. Hook into form's validation process
3. Register hooks in `includes/class-smart-form-shield.php`
4. Follow existing pattern from Gravity Forms or Elementor integrations

### Key Functions and Hooks

WordPress hooks used:
- `register_activation_hook` - Database setup
- `gform_validation` - Gravity Forms validation
- `elementor_pro/forms/validation` - Elementor Forms validation
- `rest_api_init` - REST API endpoints
- `wp_ajax_*` - AJAX handlers

## Important Considerations

- API keys are stored encrypted in WordPress options
- All user inputs must be sanitized using WordPress functions
- Use prepared statements for all database queries
- Implement proper capability checks for admin actions
- Cache API responses to reduce costs
- Follow WordPress coding standards
- Use WordPress HTTP API for external requests
- Implement proper nonce verification for forms