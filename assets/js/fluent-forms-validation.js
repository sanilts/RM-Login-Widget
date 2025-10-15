/**
 * Real-time Validation for Fluent Forms
 * Handles username, email, password validation, and country auto-detection
 */
(function($) {
    'use strict';

    let usernameCheckTimeout, emailCheckTimeout, passwordCheckTimeout;

    $(document).ready(function() {
        console.log('RM Panel: Initializing validation and country detection...');
        initializeValidation();
        initializeCountryDetection();
    });

    function initializeValidation() {
        // Username validation
        const $usernameField = $('input[name="username"]');
        if ($usernameField.length) {
            setupUsernameValidation($usernameField);
        }

        // Email validation
        const $emailField = $('input[name="email"]');
        if ($emailField.length) {
            setupEmailValidation($emailField);
        }

        // Password validation
        const $passwordField = $('input[name="password"]');
        const $confirmPasswordField = $('input[name="confirm_password"]');
        if ($passwordField.length) {
            setupPasswordValidation($passwordField, $confirmPasswordField);
        }
    }

    /**
     * Setup Username Validation
     */
    function setupUsernameValidation($field) {
        // Create feedback element
        if (!$field.next('.rm-validation-feedback').length) {
            $field.after('<div class="rm-validation-feedback"></div>');
        }
        const $feedback = $field.next('.rm-validation-feedback');

        $field.on('input', function() {
            const username = $(this).val().trim();
            
            // Clear existing timeout
            clearTimeout(usernameCheckTimeout);

            // Clear feedback if empty
            if (username.length === 0) {
                $feedback.removeClass('checking success error').text('');
                return;
            }

            // Check minimum length
            if (username.length < 5) {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Username must be at least 5 characters');
                return;
            }

            // Check format
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Username can only contain letters, numbers, and underscores');
                return;
            }

            // Show checking state
            $feedback.removeClass('success error')
                .addClass('checking')
                .html('<span class="spinner"></span>' + rmFluentFormsValidation.messages.username_checking);

            // Debounce AJAX call
            usernameCheckTimeout = setTimeout(function() {
                checkUsernameAvailability(username, $feedback);
            }, 500);
        });
    }

    /**
     * Check username availability via AJAX
     */
    function checkUsernameAvailability(username, $feedback) {
        $.ajax({
            url: rmFluentFormsValidation.ajax_url,
            type: 'POST',
            data: {
                action: 'check_username_availability',
                username: username,
                nonce: rmFluentFormsValidation.username_nonce
            },
            success: function(response) {
                if (response.success) {
                    $feedback.removeClass('checking error')
                        .addClass('success')
                        .html('<span class="icon">✓</span>' + response.data.message);
                } else {
                    $feedback.removeClass('checking success')
                        .addClass('error')
                        .html('<span class="icon">✗</span>' + response.data.message);
                }
            },
            error: function() {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Error checking username availability');
            }
        });
    }

    /**
     * Setup Email Validation
     */
    function setupEmailValidation($field) {
        // Create feedback element
        if (!$field.next('.rm-validation-feedback').length) {
            $field.after('<div class="rm-validation-feedback"></div>');
        }
        const $feedback = $field.next('.rm-validation-feedback');

        $field.on('input', function() {
            const email = $(this).val().trim();
            
            // Clear existing timeout
            clearTimeout(emailCheckTimeout);

            // Clear feedback if empty
            if (email.length === 0) {
                $feedback.removeClass('checking success error').text('');
                return;
            }

            // Basic email format check
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Please enter a valid email address');
                return;
            }

            // Show checking state
            $feedback.removeClass('success error')
                .addClass('checking')
                .html('<span class="spinner"></span>' + rmFluentFormsValidation.messages.email_checking);

            // Debounce AJAX call
            emailCheckTimeout = setTimeout(function() {
                checkEmailAvailability(email, $feedback);
            }, 500);
        });
    }

    /**
     * Check email availability via AJAX
     */
    function checkEmailAvailability(email, $feedback) {
        $.ajax({
            url: rmFluentFormsValidation.ajax_url,
            type: 'POST',
            data: {
                action: 'check_email_availability',
                email: email,
                nonce: rmFluentFormsValidation.email_nonce
            },
            success: function(response) {
                if (response.success) {
                    $feedback.removeClass('checking error')
                        .addClass('success')
                        .html('<span class="icon">✓</span>' + response.data.message);
                } else {
                    $feedback.removeClass('checking success')
                        .addClass('error')
                        .html('<span class="icon">✗</span>' + response.data.message);
                }
            },
            error: function() {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Error checking email availability');
            }
        });
    }

    /**
     * Setup Password Validation
     */
    function setupPasswordValidation($passwordField, $confirmPasswordField) {
        // Create feedback element for password
        if (!$passwordField.next('.rm-validation-feedback').length) {
            $passwordField.after('<div class="rm-validation-feedback"></div>');
        }
        const $passwordFeedback = $passwordField.next('.rm-validation-feedback');

        // Create feedback element for confirm password
        if ($confirmPasswordField.length && !$confirmPasswordField.next('.rm-validation-feedback').length) {
            $confirmPasswordField.after('<div class="rm-validation-feedback"></div>');
        }
        const $confirmFeedback = $confirmPasswordField.next('.rm-validation-feedback');

        // Password strength indicator
        $passwordField.on('input', function() {
            const password = $(this).val();
            const confirmPassword = $confirmPasswordField.val();
            
            clearTimeout(passwordCheckTimeout);

            if (password.length === 0) {
                $passwordFeedback.removeClass('checking success error warning').text('');
                return;
            }

            // Show checking state
            $passwordFeedback.removeClass('success error warning')
                .addClass('checking')
                .html('<span class="spinner"></span>Checking password strength...');

            passwordCheckTimeout = setTimeout(function() {
                checkPasswordStrength(password, confirmPassword, $passwordFeedback);
            }, 300);
        });

        // Confirm password match check
        if ($confirmPasswordField.length) {
            $confirmPasswordField.on('input', function() {
                const password = $passwordField.val();
                const confirmPassword = $(this).val();

                if (confirmPassword.length === 0) {
                    $confirmFeedback.removeClass('checking success error').text('');
                    return;
                }

                if (password === confirmPassword) {
                    $confirmFeedback.removeClass('checking error')
                        .addClass('success')
                        .html('<span class="icon">✓</span>' + rmFluentFormsValidation.messages.passwords_match);
                } else {
                    $confirmFeedback.removeClass('checking success')
                        .addClass('error')
                        .html('<span class="icon">✗</span>' + rmFluentFormsValidation.messages.passwords_no_match);
                }
            });

            // Also check when main password changes
            $passwordField.on('input', function() {
                const confirmPassword = $confirmPasswordField.val();
                if (confirmPassword.length > 0) {
                    $confirmPasswordField.trigger('input');
                }
            });
        }
    }

    /**
     * Check password strength via AJAX
     */
    function checkPasswordStrength(password, confirmPassword, $feedback) {
        $.ajax({
            url: rmFluentFormsValidation.ajax_url,
            type: 'POST',
            data: {
                action: 'check_password_strength',
                password: password,
                confirm_password: confirmPassword,
                nonce: rmFluentFormsValidation.password_nonce
            },
            success: function(response) {
                const strength = response.data.strength;
                let icon = '';
                let strengthClass = '';

                if (strength === 'strong') {
                    icon = '✓';
                    strengthClass = 'success';
                } else if (strength === 'medium') {
                    icon = '⚠';
                    strengthClass = 'warning';
                } else {
                    icon = '✗';
                    strengthClass = 'error';
                }

                $feedback.removeClass('checking success error warning')
                    .addClass(strengthClass)
                    .html('<span class="icon">' + icon + '</span>' + response.data.message);
            },
            error: function() {
                $feedback.removeClass('checking success warning')
                    .addClass('error')
                    .text('Error checking password strength');
            }
        });
    }

    /**
     * Initialize Country Detection
     */
    function initializeCountryDetection() {
        console.log('RM Panel: Initializing country detection...');
        
        // Try immediately
        autoFillCountry();
        
        // Try again after 1 second (in case Fluent Forms hasn't loaded yet)
        setTimeout(function() {
            console.log('RM Panel: Delayed country detection attempt (1s)...');
            autoFillCountry();
        }, 1000);
        
        // Try again after 2 seconds (backup)
        setTimeout(function() {
            console.log('RM Panel: Final country detection attempt (2s)...');
            autoFillCountry();
        }, 2000);
        
        // Listen for Fluent Forms initialization
        $(document).on('fluentform_init', function() {
            console.log('RM Panel: Fluent Forms initialized, detecting country...');
            setTimeout(autoFillCountry, 500);
        });
    }

    /**
     * Auto-detect and fill country field
     */
    function autoFillCountry() {
        // Look for country field - try multiple selectors
        const $countryField = $(
            'select[name="country"], ' +
            'input[name="country"], ' +
            'select[data-name="country"], ' +
            'input[data-name="country"], ' +
            '.ff-el-form-control[name="country"]'
        );
        
        if ($countryField.length === 0) {
            console.log('RM Panel: No country field found');
            return;
        }
        
        console.log('RM Panel: Country field found:', $countryField);
        
        // Check if field already has a value
        const currentValue = $countryField.val();
        if (currentValue && currentValue !== '' && currentValue !== 'Select Country') {
            console.log('RM Panel: Country field already has value:', currentValue);
            return;
        }
        
        console.log('RM Panel: Starting country auto-detection...');
        
        // Add/show detecting message
        let $feedback = $countryField.next('.rm-validation-feedback');
        if ($feedback.length === 0) {
            $countryField.after('<div class="rm-validation-feedback"></div>');
            $feedback = $countryField.next('.rm-validation-feedback');
        }
        
        $feedback.removeClass('success error')
            .addClass('checking')
            .html('<span class="spinner"></span> ' + (rmFluentFormsValidation.messages.country_detecting || 'Detecting country...'))
            .show();
        
        // Make AJAX call to detect country
        $.ajax({
            url: rmFluentFormsValidation.ajax_url,
            type: 'POST',
            data: {
                action: 'get_country_from_ip',
                nonce: rmFluentFormsValidation.country_nonce
            },
            timeout: 10000, // 10 second timeout
            success: function(response) {
                console.log('RM Panel: Country detection response:', response);
                
                if (response.success && response.data.country) {
                    const country = response.data.country;
                    console.log('RM Panel: Detected country:', country);
                    
                    // For select fields, try to find matching option
                    if ($countryField.is('select')) {
                        console.log('RM Panel: Country field is a dropdown');
                        
                        // Log all available options
                        const options = [];
                        $countryField.find('option').each(function() {
                            options.push({
                                value: $(this).val(),
                                text: $(this).text().trim()
                            });
                        });
                        console.log('RM Panel: Available country options:', options);
                        
                        // Try to find matching option (case-insensitive)
                        let $option = $countryField.find('option').filter(function() {
                            const optionText = $(this).text().trim().toLowerCase();
                            const optionValue = $(this).val().toLowerCase();
                            const countryLower = country.toLowerCase();
                            
                            return optionText === countryLower || 
                                   optionValue === countryLower ||
                                   optionText.indexOf(countryLower) !== -1;
                        });
                        
                        if ($option.length > 0) {
                            const selectedValue = $option.first().val();
                            console.log('RM Panel: Matching option found, value:', selectedValue);
                            $countryField.val(selectedValue).trigger('change');
                            
                            // Show success feedback
                            $feedback.removeClass('checking error')
                                .addClass('success')
                                .html('<span class="icon">✓</span> ' + (rmFluentFormsValidation.messages.country_detected || 'Country detected!'));
                            
                            // Hide feedback after 3 seconds
                            setTimeout(function() {
                                $feedback.fadeOut();
                            }, 3000);
                        } else {
                            console.warn('RM Panel: Country "' + country + '" not found in dropdown options');
                            $feedback.removeClass('checking success error').fadeOut();
                        }
                    } else {
                        // For text input, set directly
                        console.log('RM Panel: Country field is a text input');
                        $countryField.val(country).trigger('change');
                        
                        // Show success feedback
                        $feedback.removeClass('checking error')
                            .addClass('success')
                            .html('<span class="icon">✓</span> ' + (rmFluentFormsValidation.messages.country_detected || 'Country detected!'));
                        
                        // Hide feedback after 3 seconds
                        setTimeout(function() {
                            $feedback.fadeOut();
                        }, 3000);
                    }
                } else {
                    console.log('RM Panel: Failed to detect country:', response);
                    $feedback.fadeOut();
                }
            },
            error: function(xhr, status, error) {
                console.error('RM Panel: AJAX error detecting country:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Could not detect country')
                    .show();
                
                // Hide error after 3 seconds
                setTimeout(function() {
                    $feedback.fadeOut();
                }, 3000);
            }
        });
    }

})(jQuery);