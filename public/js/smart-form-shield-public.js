/**
 * Public JavaScript for Smart Form Shield
 *
 * @since      1.0.0
 * @package    Smart_Form_Shield
 * @subpackage Smart_Form_Shield/public/js
 */

(function($) {
    'use strict';

    var SmartFormShield = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.setupHoneypot();
            this.setupRateLimiting();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Gravity Forms
            $(document).on('gform_post_render', function(event, form_id) {
                SmartFormShield.setupGravityForm(form_id);
            });

            // Elementor Forms
            $(document).on('elementor/frontend/init', function() {
                elementorFrontend.hooks.addAction('frontend/element_ready/form.default', SmartFormShield.setupElementorForm);
            });

            // Generic form submission handler
            $('form').on('submit', function(e) {
                return SmartFormShield.validateForm($(this));
            });
        },

        /**
         * Setup honeypot field protection
         */
        setupHoneypot: function() {
            // Add autocomplete="off" to honeypot fields
            $('[name="sfs_website"]').attr('autocomplete', 'off');
            
            // Ensure honeypot stays empty
            $('[name="sfs_website"]').val('');
        },

        /**
         * Setup rate limiting
         */
        setupRateLimiting: function() {
            // Check if rate limit message should be shown
            var rateLimitCookie = this.getCookie('sfs_rate_limit');
            if (rateLimitCookie) {
                this.showRateLimitMessage();
            }
        },

        /**
         * Setup Gravity Form
         */
        setupGravityForm: function(form_id) {
            var $form = $('#gform_' + form_id);
            
            // Add validation before submission
            $form.on('submit', function(e) {
                if (!$form.hasClass('sfs-validated')) {
                    e.preventDefault();
                    SmartFormShield.validateFormAjax($form);
                    return false;
                }
            });
        },

        /**
         * Setup Elementor Form
         */
        setupElementorForm: function($scope) {
            var $form = $scope.find('form.elementor-form');
            
            $form.on('submit', function(e) {
                if (!$form.hasClass('sfs-validated')) {
                    e.preventDefault();
                    SmartFormShield.validateFormAjax($form);
                    return false;
                }
            });
        },

        /**
         * Validate form
         */
        validateForm: function($form) {
            // Check honeypot
            if (!this.checkHoneypot($form)) {
                console.log('Smart Form Shield: Honeypot field detected');
                return false;
            }

            // Check rate limiting
            if (!this.checkRateLimit()) {
                this.showRateLimitMessage();
                return false;
            }

            return true;
        },

        /**
         * Validate form via AJAX
         */
        validateFormAjax: function($form) {
            var formData = this.getFormData($form);
            
            // Show loading state
            $form.addClass('sfs-validating');
            this.showMessage($form, sfs_public.strings.validating, 'info');

            $.ajax({
                url: sfs_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'sfs_validate_form',
                    form_data: formData,
                    nonce: sfs_public.nonce
                },
                success: function(response) {
                    if (response.success && !response.data.is_spam) {
                        // Valid submission
                        $form.addClass('sfs-validated');
                        $form.submit();
                    } else {
                        // Spam detected
                        SmartFormShield.handleSpamDetection($form, response.data);
                    }
                },
                error: function() {
                    // On error, allow submission (fail open)
                    console.error('Smart Form Shield: Validation failed, allowing submission');
                    $form.addClass('sfs-validated');
                    $form.submit();
                },
                complete: function() {
                    $form.removeClass('sfs-validating');
                }
            });
        },

        /**
         * Get form data
         */
        getFormData: function($form) {
            var data = {};
            
            $form.find('input, textarea, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var type = $field.attr('type');
                
                if (name && name !== 'sfs_website') {
                    if (type === 'checkbox' || type === 'radio') {
                        if ($field.is(':checked')) {
                            data[name] = $field.val();
                        }
                    } else {
                        data[name] = $field.val();
                    }
                }
            });

            return data;
        },

        /**
         * Check honeypot field
         */
        checkHoneypot: function($form) {
            var $honeypot = $form.find('[name="sfs_website"]');
            return $honeypot.length === 0 || $honeypot.val() === '';
        },

        /**
         * Check rate limit
         */
        checkRateLimit: function() {
            var submissions = parseInt(this.getCookie('sfs_submissions') || 0);
            var maxSubmissions = 5; // Default, should match server setting

            if (submissions >= maxSubmissions) {
                return false;
            }

            // Increment counter
            this.setCookie('sfs_submissions', submissions + 1, 1/24/60); // 1 minute
            return true;
        },

        /**
         * Handle spam detection
         */
        handleSpamDetection: function($form, data) {
            var message = data.message || 'Your submission has been blocked as potential spam.';
            this.showMessage($form, message, 'error');

            // Log to console in development
            if (window.location.hostname === 'localhost') {
                console.log('Smart Form Shield: Spam detected', data);
            }

            // Trigger custom event
            $form.trigger('sfs:spam-detected', [data]);
        },

        /**
         * Show rate limit message
         */
        showRateLimitMessage: function() {
            var message = 'You have exceeded the maximum number of submissions. Please try again later.';
            $('.sfs-rate-limit-message').remove();
            
            $('form').each(function() {
                $(this).before('<div class="sfs-rate-limit-message">' + message + '</div>');
            });
        },

        /**
         * Show message
         */
        showMessage: function($form, message, type) {
            var $message = $form.find('.sfs-validation-message');
            
            if ($message.length === 0) {
                $message = $('<div class="sfs-validation-message"></div>');
                $form.append($message);
            }

            $message
                .removeClass('sfs-error sfs-success sfs-info')
                .addClass('sfs-' + type)
                .text(message)
                .show();

            if (type !== 'info') {
                setTimeout(function() {
                    $message.fadeOut();
                }, 5000);
            }
        },

        /**
         * Get cookie value
         */
        getCookie: function(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            
            return null;
        },

        /**
         * Set cookie
         */
        setCookie: function(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + value + expires + "; path=/";
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SmartFormShield.init();
    });

    // Expose to global scope for debugging
    window.SmartFormShield = SmartFormShield;

})(jQuery);