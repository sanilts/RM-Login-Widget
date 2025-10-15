/**
 * Real-time Validation for Fluent Forms
 * WITH COUNTRY MISMATCH VALIDATION
 */
(function($) {
    'use strict';

    let usernameCheckTimeout, emailCheckTimeout, passwordCheckTimeout;
    let detectedCountry = null; // Store the detected country
    let detectedCountryValue = null; // Store the detected country value

    $(document).ready(function() {
        console.log('RM Panel: Initializing validation and country detection...');
        initializeValidation();
        initializeCountryDetection();
        initializeCountryValidation(); // NEW: Initialize country change validation
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
        if (!$field.next('.rm-validation-feedback').length) {
            $field.after('<div class="rm-validation-feedback"></div>');
        }
        const $feedback = $field.next('.rm-validation-feedback');

        $field.on('input', function() {
            const username = $(this).val().trim();
            
            clearTimeout(usernameCheckTimeout);

            if (username.length === 0) {
                $feedback.removeClass('checking success error').text('');
                return;
            }

            if (username.length < 5) {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Username must be at least 5 characters');
                return;
            }

            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Username can only contain letters, numbers, and underscores');
                return;
            }

            $feedback.removeClass('success error')
                .addClass('checking')
                .html('<span class="spinner"></span>' + rmFluentFormsValidation.messages.username_checking);

            usernameCheckTimeout = setTimeout(function() {
                checkUsernameAvailability(username, $feedback);
            }, 500);
        });
    }

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
        if (!$field.next('.rm-validation-feedback').length) {
            $field.after('<div class="rm-validation-feedback"></div>');
        }
        const $feedback = $field.next('.rm-validation-feedback');

        $field.on('input', function() {
            const email = $(this).val().trim();
            
            clearTimeout(emailCheckTimeout);

            if (email.length === 0) {
                $feedback.removeClass('checking success error').text('');
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                $feedback.removeClass('checking success')
                    .addClass('error')
                    .text('Please enter a valid email address');
                return;
            }

            $feedback.removeClass('success error')
                .addClass('checking')
                .html('<span class="spinner"></span>' + rmFluentFormsValidation.messages.email_checking);

            emailCheckTimeout = setTimeout(function() {
                checkEmailAvailability(email, $feedback);
            }, 500);
        });
    }

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
        if (!$passwordField.next('.rm-validation-feedback').length) {
            $passwordField.after('<div class="rm-validation-feedback"></div>');
        }
        const $passwordFeedback = $passwordField.next('.rm-validation-feedback');

        if ($confirmPasswordField.length && !$confirmPasswordField.next('.rm-validation-feedback').length) {
            $confirmPasswordField.after('<div class="rm-validation-feedback"></div>');
        }
        const $confirmFeedback = $confirmPasswordField.next('.rm-validation-feedback');

        $passwordField.on('input', function() {
            const password = $(this).val();
            const confirmPassword = $confirmPasswordField.val();
            
            clearTimeout(passwordCheckTimeout);

            if (password.length === 0) {
                $passwordFeedback.removeClass('checking success error warning').text('');
                return;
            }

            $passwordFeedback.removeClass('success error warning')
                .addClass('checking')
                .html('<span class="spinner"></span>Checking password strength...');

            passwordCheckTimeout = setTimeout(function() {
                checkPasswordStrength(password, confirmPassword, $passwordFeedback);
            }, 300);
        });

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

            $passwordField.on('input', function() {
                const confirmPassword = $confirmPasswordField.val();
                if (confirmPassword.length > 0) {
                    $confirmPasswordField.trigger('input');
                }
            });
        }
    }

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
        
        autoFillCountry();
        
        setTimeout(function() {
            console.log('RM Panel: Delayed country detection attempt (1s)...');
            autoFillCountry();
        }, 1000);
        
        setTimeout(function() {
            console.log('RM Panel: Final country detection attempt (2s)...');
            autoFillCountry();
        }, 2000);
        
        $(document).on('fluentform_init', function() {
            console.log('RM Panel: Fluent Forms initialized, detecting country...');
            setTimeout(autoFillCountry, 500);
        });
    }

    /**
     * Auto-detect and fill country field
     */
    function autoFillCountry() {
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
        
        const currentValue = $countryField.val();
        if (currentValue && currentValue !== '' && currentValue !== 'Select Country') {
            console.log('RM Panel: Country field already has value:', currentValue);
            return;
        }
        
        console.log('RM Panel: Starting country auto-detection...');
        
        let $feedback = $countryField.next('.rm-validation-feedback');
        if ($feedback.length === 0) {
            $countryField.after('<div class="rm-validation-feedback"></div>');
            $feedback = $countryField.next('.rm-validation-feedback');
        }
        
        $feedback.removeClass('success error')
            .addClass('checking')
            .html('<span class="spinner"></span> ' + (rmFluentFormsValidation.messages.country_detecting || 'Detecting country...'))
            .show();
        
        $.ajax({
            url: rmFluentFormsValidation.ajax_url,
            type: 'POST',
            data: {
                action: 'get_country_from_ip',
                nonce: rmFluentFormsValidation.country_nonce
            },
            timeout: 10000,
            success: function(response) {
                console.log('RM Panel: Country detection response:', response);
                
                if (response.success && response.data.country) {
                    const country = response.data.country;
                    console.log('RM Panel: Detected country:', country);
                    
                    // Store detected country for validation
                    detectedCountry = country;
                    
                    if ($countryField.is('select')) {
                        console.log('RM Panel: Country field is a dropdown');
                        
                        const options = [];
                        $countryField.find('option').each(function() {
                            options.push({
                                value: $(this).val(),
                                text: $(this).text().trim()
                            });
                        });
                        console.log('RM Panel: Available country options:', options);
                        
                        let $option = null;
                        const countryLower = country.toLowerCase();
                        
                        // Exact text match
                        $option = $countryField.find('option').filter(function() {
                            return $(this).text().trim().toLowerCase() === countryLower;
                        });
                        
                        // Exact value match
                        if ($option.length === 0) {
                            $option = $countryField.find('option').filter(function() {
                                return $(this).val().toLowerCase() === countryLower;
                            });
                        }
                        
                        // Common aliases
                        if ($option.length === 0) {
                            const aliases = {
                                'india': ['in'],
                                'united states': ['usa', 'us', 'united states of america'],
                                'united kingdom': ['uk', 'great britain', 'gb'],
                                'china': ['cn', 'people\'s republic of china'],
                                'south korea': ['korea, republic of', 'republic of korea'],
                                'north korea': ['korea, democratic people\'s republic of']
                            };
                            
                            if (aliases[countryLower]) {
                                $option = $countryField.find('option').filter(function() {
                                    const optionText = $(this).text().trim().toLowerCase();
                                    const optionValue = $(this).val().toLowerCase();
                                    return aliases[countryLower].includes(optionText) || 
                                           aliases[countryLower].includes(optionValue);
                                });
                            }
                        }
                        
                        if ($option.length > 0) {
                            const selectedValue = $option.first().val();
                            detectedCountryValue = selectedValue; // Store detected value
                            console.log('RM Panel: Matching option found, value:', selectedValue);
                            $countryField.val(selectedValue).trigger('change');
                            
                            // Mark field as auto-detected
                            $countryField.attr('data-country-detected', country);
                            $countryField.attr('data-detected-value', selectedValue);
                            
                            $feedback.removeClass('checking error')
                                .addClass('success')
                                .html('<span class="icon">✓</span> ' + (rmFluentFormsValidation.messages.country_detected || 'Country detected!'));
                            
                            setTimeout(function() {
                                $feedback.fadeOut();
                            }, 3000);
                        } else {
                            console.warn('RM Panel: Country "' + country + '" not found in dropdown options');
                            $feedback.removeClass('checking success error').fadeOut();
                        }
                    } else {
                        console.log('RM Panel: Country field is a text input');
                        detectedCountryValue = country;
                        $countryField.val(country).trigger('change');
                        $countryField.attr('data-country-detected', country);
                        
                        $feedback.removeClass('checking error')
                            .addClass('success')
                            .html('<span class="icon">✓</span> ' + (rmFluentFormsValidation.messages.country_detected || 'Country detected!'));
                        
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
                
                setTimeout(function() {
                    $feedback.fadeOut();
                }, 3000);
            }
        });
    }

    /**
     * NEW: Initialize Country Change Validation
     */
    function initializeCountryValidation() {
        console.log('RM Panel: Initializing country change validation...');
        
        // Wait a bit for country detection to complete
        setTimeout(function() {
            const $countryField = $(
                'select[name="country"], ' +
                'input[name="country"], ' +
                'select[data-name="country"], ' +
                'input[data-name="country"], ' +
                '.ff-el-form-control[name="country"]'
            );
            
            if ($countryField.length === 0) {
                return;
            }
            
            // Monitor country field changes
            $countryField.on('change', function() {
                validateCountrySelection($(this));
            });
            
            // Add form submit validation
            $countryField.closest('form').on('submit', function(e) {
                if (!validateCountrySelection($countryField)) {
                    e.preventDefault();
                    console.log('RM Panel: Form submission blocked due to country mismatch');
                    
                    // Scroll to country field
                    $('html, body').animate({
                        scrollTop: $countryField.offset().top - 100
                    }, 500);
                    
                    return false;
                }
            });
        }, 3000); // Wait 3 seconds for country detection
    }

    /**
     * NEW: Validate Country Selection
     */
    function validateCountrySelection($countryField) {
        const detectedValue = $countryField.attr('data-detected-value');
        const selectedValue = $countryField.val();
        
        // If no country was detected, allow any selection
        if (!detectedValue || detectedValue === '') {
            console.log('RM Panel: No detected country, allowing selection');
            return true;
        }
        
        // Check if user changed the country
        if (selectedValue !== detectedValue) {
            console.log('RM Panel: Country mismatch detected!', {
                detected: detectedValue,
                selected: selectedValue
            });
            
            let $feedback = $countryField.next('.rm-validation-feedback');
            if ($feedback.length === 0) {
                $countryField.after('<div class="rm-validation-feedback"></div>');
                $feedback = $countryField.next('.rm-validation-feedback');
            }
            
            const detectedCountryName = $countryField.attr('data-country-detected') || 'detected country';
            
            $feedback.removeClass('checking success')
                .addClass('error')
                .html('<span class="icon">✗</span> ' + 
                      (rmFluentFormsValidation.messages.country_mismatch || 
                       'Please select your actual country: ' + detectedCountryName))
                .show();
            
            // Add error class to field
            $countryField.addClass('rm-country-mismatch');
            
            return false;
        } else {
            // Clear any existing error
            let $feedback = $countryField.next('.rm-validation-feedback');
            if ($feedback.length > 0) {
                $feedback.removeClass('error').fadeOut();
            }
            $countryField.removeClass('rm-country-mismatch');
            
            return true;
        }
    }

})(jQuery);