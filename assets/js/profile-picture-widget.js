/**
 * Profile Picture Widget JavaScript
 * Handles modal popup, file upload, drag-and-drop, and AJAX submission
 */

(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        initProfilePictureWidget();
    });

    /**
     * Initialize Profile Picture Widget
     */
    function initProfilePictureWidget() {
        // Open modal when clicking on profile picture
        $(document).on('click', '.rm-profile-picture-image-wrapper', function(e) {
            e.preventDefault();
            $('#rm-profile-picture-modal').addClass('active');
            $('body').css('overflow', 'hidden'); // Prevent background scrolling
        });

        // Close modal
        $(document).on('click', '.rm-modal-close, .rm-modal-cancel', function() {
            closeModal();
        });

        // Close modal when clicking outside
        $(document).on('click', '.rm-profile-picture-modal', function(e) {
            if ($(e.target).is('.rm-profile-picture-modal')) {
                closeModal();
            }
        });

        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#rm-profile-picture-modal').hasClass('active')) {
                closeModal();
            }
        });

        // Upload area click
        $(document).on('click', '#rm-upload-area', function() {
            $('#rm-profile-picture-input').click();
        });

        // File input change
        $(document).on('change', '#rm-profile-picture-input', function(e) {
            handleFileSelect(e.target.files);
        });

        // Change image button
        $(document).on('click', '#rm-change-image', function() {
            $('#rm-profile-picture-input').click();
        });

        // Save button
        $(document).on('click', '#rm-save-profile-picture', function() {
            saveProfilePicture();
        });

        // Drag and drop functionality
        setupDragAndDrop();
    }

    /**
     * Setup drag and drop
     */
    function setupDragAndDrop() {
        var uploadArea = document.getElementById('rm-upload-area');
        if (!uploadArea) return;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        uploadArea.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            uploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }

        function handleDrop(e) {
            var dt = e.dataTransfer;
            var files = dt.files;
            handleFileSelect(files);
        }
    }

    /**
     * Handle file selection
     */
    function handleFileSelect(files) {
        // Safety check: ensure files exist and length is greater than 0
        if (!files || files.length === 0) {
            return;
        }

        var file = files[0];

        // Validate file type
        if (!file.type.match('image.*')) {
            showMessage('error', 'Please select an image file (PNG, JPG, GIF)');
            return;
        }

        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            showMessage('error', 'File size must be less than 5MB');
            return;
        }

        // Show preview
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#rm-preview-image').attr('src', e.target.result);
            $('#rm-upload-area').hide();
            $('#rm-preview-area').show();
        };
        reader.readAsDataURL(file);
    }

    /**
     * Save profile picture via AJAX
     */
    function saveProfilePicture() {
        var fileInput = $('#rm-profile-picture-input')[0];
        if (!fileInput.files || fileInput.files.length === 0) {
            showMessage('error', 'Please select an image first');
            return;
        }

        var $saveButton = $('#rm-save-profile-picture');
        var userId = $('.rm-profile-picture-image').data('user-id');

        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'rm_upload_profile_picture');
        formData.append('user_id', userId);
        formData.append('profile_picture', fileInput.files[0]);
        formData.append('nonce', rmProfilePicture.nonce);

        // Show loading state
        $saveButton.addClass('loading').prop('disabled', true);
        hideMessage();

        // AJAX request
        $.ajax({
            url: rmProfilePicture.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $saveButton.removeClass('loading').prop('disabled', false);

                if (response.success) {
                    // Update profile picture on page
                    $('.rm-profile-picture-image').attr('src', response.data.url);
                    
                    // Show success message
                    showMessage('success', response.data.message || 'Profile picture updated successfully!');

                    // Close modal after a short delay
                    setTimeout(function() {
                        closeModal();
                    }, 1500);
                } else {
                    showMessage('error', response.data.message || 'Failed to upload profile picture');
                }
            },
            error: function(xhr, status, error) {
                $saveButton.removeClass('loading').prop('disabled', false);
                console.error('Upload error:', error);
                showMessage('error', 'An error occurred while uploading. Please try again.');
            }
        });
    }

    /**
     * Close modal
     */
    function closeModal() {
        $('#rm-profile-picture-modal').removeClass('active');
        $('body').css('overflow', ''); // Restore scrolling
        
        // Reset modal state
        setTimeout(function() {
            $('#rm-upload-area').show();
            $('#rm-preview-area').hide();
            
            // Reset file input by replacing it (prevents infinite loop from .val('') triggering change event)
            var $fileInput = $('#rm-profile-picture-input');
            var $newInput = $fileInput.clone();
            $fileInput.replaceWith($newInput);
            
            $('#rm-preview-image').attr('src', '');
            hideMessage();
        }, 300); // Wait for fade out animation
    }

    /**
     * Show message in modal
     */
    function showMessage(type, message) {
        var $message = $('.rm-message');
        
        // Create message element if it doesn't exist
        if ($message.length === 0) {
            $message = $('<div class="rm-message"></div>');
            $('.rm-modal-body').prepend($message);
        }

        $message
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .addClass('show');
    }

    /**
     * Hide message
     */
    function hideMessage() {
        $('.rm-message').removeClass('show');
    }

})(jQuery);