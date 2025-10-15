jQuery(document).ready(function($) {
    let usernameCheckTimeout;
    let lastCheckedUsername = '';
    
    // Find the username field - adjust selector if needed
    const $usernameField = $('input[name="username"]');
    
    if ($usernameField.length === 0) {
        return; // No username field found
    }
    
    // Create validation message container
    const $messageContainer = $('<div class="rm-username-validation-message"></div>');
    $usernameField.after($messageContainer);
    
    // Validate username format
    function isValidUsernameFormat(username) {
        // Only letters, numbers, and underscores
        return /^[a-zA-Z0-9_]+$/.test(username);
    }
    
    // Show message
    function showMessage(message, type) {
        $messageContainer
            .removeClass('rm-validation-error rm-validation-success rm-validation-checking')
            .addClass('rm-validation-' + type)
            .html('<span class="rm-validation-icon"></span>' + message)
            .slideDown(200);
    }
    
    // Hide message
    function hideMessage() {
        $messageContainer.slideUp(200);
    }
    
    // Check username availability
    function checkUsername(username) {
        // Validate length
        if (username.length < 5) {
            if (username.length > 0) {
                showMessage(rmFluentFormsValidation.messages.too_short, 'error');
            } else {
                hideMessage();
            }
            return;
        }
        
        // Validate format
        if (!isValidUsernameFormat(username)) {
            showMessage(rmFluentFormsValidation.messages.invalid, 'error');
            return;
        }
        
        // Don't check if same as last checked
        if (username === lastCheckedUsername) {
            return;
        }
        
        // Show checking message
        showMessage(rmFluentFormsValidation.messages.checking, 'checking');
        
        // AJAX check
        $.ajax({
            url: rmFluentFormsValidation.ajax_url,
            type: 'POST',
            data: {
                action: 'check_username_availability',
                username: username,
                nonce: rmFluentFormsValidation.nonce
            },
            success: function(response) {
                lastCheckedUsername = username;
                
                if (response.success) {
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('Error checking username. Please try again.', 'error');
            }
        });
    }
    
    // Real-time validation on input
    $usernameField.on('input', function() {
        const username = $(this).val().trim();
        
        // Clear previous timeout
        clearTimeout(usernameCheckTimeout);
        
        // Debounce: wait 500ms after user stops typing
        usernameCheckTimeout = setTimeout(function() {
            checkUsername(username);
        }, 500);
    });
    
    // Also validate on blur (when field loses focus)
    $usernameField.on('blur', function() {
        const username = $(this).val().trim();
        if (username.length > 0) {
            clearTimeout(usernameCheckTimeout);
            checkUsername(username);
        }
    });
});