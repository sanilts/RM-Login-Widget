/**
 * RM Panel Extensions - Elementor Widgets JavaScript
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        initLoginForms();
        initMessageClose();
    });

    /**
     * Initialize login forms
     */
    function initLoginForms() {
        $('.rm-login-form').each(function() {
            const form = $(this);
            
            // Skip if already initialized
            if (form.data('initialized')) {
                return;
            }
            
            form.data('initialized', true);
            
            // Handle form submission
            form.on('submit', function(e) {
                e.preventDefault();
                handleLoginSubmit(form);
            });
            
            // Handle enter key in inputs
            form.find('input').on('keypress', function(e) {
                if (e.which === 13 && !$(this).is('textarea')) {
                    e.preventDefault();
                    if (!form.find('button[type="submit"]').prop('disabled')) {
                        form.submit();
                    }
                }
            });
            
            // Toggle password visibility (optional feature)
            // initPasswordToggle(form);
        });
    }

    /**
     * Handle login form submission
     */
    function handleLoginSubmit(form) {
        const messages = form.find('.login-messages');
        const submitBtn = form.find('button[type="submit"]');
        const buttonText = submitBtn.find('.button-text');
        const buttonLoading = submitBtn.find('.button-loading');
        const originalButtonText = form.find('input[name="login_button_text"]').val();
        const loadingText = form.find('input[name="loading_text"]').val();
        const errorMessage = form.find('input[name="error_message"]').val();
        const successMessage = form.find('input[name="success_message"]').val();
        
        // Clear previous messages
        messages.empty().hide();
        
        // Validate form
        if (!validateLoginForm(form)) {
            return false;
        }
        
        // Disable submit button and show loading
        submitBtn.prop('disabled', true);
        buttonText.hide();
        buttonLoading.show();
        
        // Prepare data
        const formData = {
            action: 'rm_panel_login_handler',
            username: form.find('input[name="username"]').val(),
            password: form.find('input[name="password"]').val(),
            remember: form.find('input[name="remember"]').is(':checked') ? 1 : 0,
            redirect_urls: form.find('input[name="redirect_urls"]').val(),
            default_redirect: form.find('input[name="default_redirect"]').val(),
            nonce: form.find('#rm_panel_login_nonce_field').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: rm_panel_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showMessage(messages, 'success', response.data.message || successMessage);
                    
                    // Trigger custom event
                    form.trigger('rm_login_success', [response.data]);
                    
                    // Redirect after a short delay
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    // Show error message
                    showMessage(messages, 'error', response.data.message || errorMessage);
                    
                    // Re-enable submit button
                    submitBtn.prop('disabled', false);
                    buttonLoading.hide();
                    buttonText.show();
                    
                    // Focus on the first input
                    if (response.data.message.toLowerCase().includes('password')) {
                        form.find('input[name="password"]').focus().select();
                    } else {
                        form.find('input[name="username"]').focus().select();
                    }
                    
                    // Trigger custom event
                    form.trigger('rm_login_error', [response.data]);
                }
            },
            error: function(xhr, status, error) {
                // Show generic error message
                showMessage(messages, 'error', errorMessage);
                
                // Re-enable submit button
                submitBtn.prop('disabled', false);
                buttonLoading.hide();
                buttonText.show();
                
                // Log error for debugging
                if (typeof console !== 'undefined' && console.error) {
                    console.error('RM Panel Login Error:', error);
                }
                
                // Trigger custom event
                form.trigger('rm_login_ajax_error', [xhr, status, error]);
            }
        });
    }

    /**
     * Validate login form
     */
    function validateLoginForm(form) {
        const username = form.find('input[name="username"]').val().trim();
        const password = form.find('input[name="password"]').val();
        const messages = form.find('.login-messages');
        
        // Clear previous messages
        messages.empty().hide();
        
        // Validate username
        if (!username) {
            const errorMsg = rm_panel_ajax.strings.empty_username || 'Please enter your username or email address.';
            showMessage(messages, 'error', errorMsg);
            form.find('input[name="username"]').focus();
            return false;
        }
        
        // Validate email format if it looks like an email
        if (username.includes('@') && !isValidEmail(username)) {
            const errorMsg = rm_panel_ajax.strings.invalid_email || 'Please enter a valid email address.';
            showMessage(messages, 'error', errorMsg);
            form.find('input[name="username"]').focus();
            return false;
        }
        
        // Validate password
        if (!password) {
            const errorMsg = rm_panel_ajax.strings.empty_password || 'Please enter your password.';
            showMessage(messages, 'error', errorMsg);
            form.find('input[name="password"]').focus();
            return false;
        }
        
        return true;
    }

    /**
     * Check if email is valid
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Show message
     */
    function showMessage(container, type, message) {
        const messageHtml = `
            <div class="${type}">
                ${escapeHtml(message)}
                <button type="button" class="close" aria-label="Close">&times;</button>
            </div>
        `;
        
        container.html(messageHtml).fadeIn(300);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                container.find('.' + type).fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to message if needed
        if (!isInViewport(container[0])) {
            $('html, body').animate({
                scrollTop: container.offset().top - 100
            }, 300);
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Check if element is in viewport
     */
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Initialize message close buttons
     */
    function initMessageClose() {
        $(document).on('click', '.login-messages .close', function() {
            const message = $(this).parent();
            message.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }

    /**
     * Initialize password toggle (optional feature)
     */
    function initPasswordToggle(form) {
        const passwordInput = form.find('input[name="password"]');
        
        if (passwordInput.length && !passwordInput.next('.password-toggle').length) {
            // Check if dashicons are available
            if (typeof dashicons === 'undefined') {
                return;
            }
            
            // Add toggle button
            const toggleBtn = $('<button type="button" class="password-toggle" aria-label="Toggle password visibility"><span class="dashicons dashicons-visibility"></span></button>');
            
            // Wrap input and add toggle button
            if (!passwordInput.parent().hasClass('password-input-wrapper')) {
                passwordInput.wrap('<div class="password-input-wrapper"></div>');
                passwordInput.after(toggleBtn);
                
                // Handle toggle click
                toggleBtn.on('click', function() {
                    const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                    passwordInput.attr('type', type);
                    
                    // Update icon
                    const icon = $(this).find('.dashicons');
                    if (type === 'password') {
                        icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    } else {
                        icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    }
                });
            }
        }
    }

    /**
     * Public API
     */
    window.RMPanelLogin = {
        /**
         * Manually initialize a login form
         */
        init: function(selector) {
            $(selector).each(function() {
                initLoginForms();
            });
        },
        
        /**
         * Validate email
         */
        isValidEmail: isValidEmail,
        
        /**
         * Show message in a container
         */
        showMessage: showMessage,
        
        /**
         * Escape HTML
         */
        escapeHtml: escapeHtml
    };

    /**
     * Elementor frontend handlers
     */
    $(window).on('elementor/frontend/init', function() {
        // Re-initialize forms when Elementor editor reloads
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/rm_panel_login_widget.default', function($element) {
                // Remove initialized flag to force re-initialization
                $element.find('.rm-login-form').removeData('initialized');
                initLoginForms();
            });
        }
    });

})(jQuery);