/**
 * Real-time Validation for Fluent Forms
 * Handles username, email, and password validation
 */
(function($) {
    'use strict';

    let usernameCheckTimeout, emailCheckTimeout, passwordCheckTimeout;

    $(document).ready(function() {
        initializeValidation();
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
                    .text(rmFluentFormsValidation.messages.username_checking.replace('Checking username...', 'Username must be at least 5 characters'));
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

})(jQuery);