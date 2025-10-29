/**
 * Profile Picture Widget JavaScript - COMPLETE REWRITE
 * Fixed infinite loop by eliminating problematic event patterns
 * 
 * Changes:
 * - Direct event binding instead of delegation for file input
 * - Manual element removal before creating new one
 * - Flag to prevent concurrent operations
 * - Simplified modal lifecycle
 */

(function ($) {
    'use strict';

    // Global flags for state management
    var modalIsOpen = false;
    var uploadInProgress = false;
    var fileInputExists = false;

    // Wait for DOM to be ready
    $(document).ready(function () {
        initProfilePictureWidget();
    });

    /**
     * Initialize Profile Picture Widget
     */
    function initProfilePictureWidget() {
        // Open modal when clicking on profile picture
        $(document).on('click', '.rm-profile-picture-image-wrapper', function (e) {
            e.preventDefault();
            if (modalIsOpen)
                return;
            openModal();
        });

        // Close modal buttons
        $(document).on('click', '.rm-modal-close, .rm-modal-cancel', function () {
            if (!uploadInProgress) {
                closeModal();
            }
        });

        // Close modal when clicking outside
        $(document).on('click', '.rm-profile-picture-modal', function (e) {
            if ($(e.target).is('.rm-profile-picture-modal') && !uploadInProgress) {
                closeModal();
            }
        });

        // Close modal on ESC key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && modalIsOpen && !uploadInProgress) {
                closeModal();
            }
        });

        // Upload area click
        $(document).on('click', '#rm-upload-area', function () {
            if (!uploadInProgress) {
                $('#rm-profile-picture-input').trigger('click');
            }
        });

        // Change image button
        $(document).on('click', '#rm-change-image', function () {
            if (!uploadInProgress) {
                $('#rm-profile-picture-input').trigger('click');
            }
        });

        // Save button
        $(document).on('click', '#rm-save-profile-picture', function () {
            if (!uploadInProgress) {
                saveProfilePicture();
            }
        });

        // Initialize drag and drop
        setupDragAndDrop();
    }

    /**
     * Open modal and initialize file input
     */
    function openModal() {
        modalIsOpen = true;
        $('#rm-profile-picture-modal').addClass('active');
        $('body').css('overflow', 'hidden');

        // Initialize file input if it doesn't exist
        if (!fileInputExists) {
            createFileInput();
        }

        // Reset modal state
        resetModalState();
    }

    /**
     * Create file input element with direct event binding
     */
    function createFileInput() {
        // Remove any existing input first
        $('#rm-profile-picture-input').remove();

        // Create new input using vanilla JS
        var input = document.createElement('input');
        input.type = 'file';
        input.id = 'rm-profile-picture-input';
        input.accept = 'image/*';
        input.style.display = 'none';

        // Append to modal body
        $('.rm-modal-body').append(input);

        // Bind change event DIRECTLY to the element (not delegated)
        $(input).on('change', function (e) {
            if (!uploadInProgress) {
                var files = e.target.files;
                if (files && files.length > 0) {
                    handleFileSelect(files);
                }
            }
        });

        fileInputExists = true;

        console.log('RM Panel: File input created');
    }

    /**
     * Reset modal state
     */
    function resetModalState() {
        $('#rm-upload-area').show();
        $('#rm-preview-area').hide();
        $('#rm-preview-image').attr('src', '');
        hideMessage();
    }

    /**
     * Close modal
     */
    function closeModal() {
        modalIsOpen = false;
        $('#rm-profile-picture-modal').removeClass('active');
        $('body').css('overflow', '');

        // Wait for animation
        setTimeout(function () {
            resetModalState();
            destroyFileInput();
        }, 300);
    }

    /**
     * Safely destroy file input
     */
    function destroyFileInput() {
        if (fileInputExists) {
            var $input = $('#rm-profile-picture-input');

            // Unbind all events
            $input.off('change');

            // Remove from DOM
            $input.remove();

            fileInputExists = false;

            console.log('RM Panel: File input destroyed');
        }
    }

    /**
     * Setup drag and drop
     */
    function setupDragAndDrop() {
        var uploadArea = document.getElementById('rm-upload-area');
        if (!uploadArea)
            return;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, function () {
                uploadArea.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, function () {
                uploadArea.classList.remove('dragover');
            }, false);
        });

        // Handle dropped files
        uploadArea.addEventListener('drop', function (e) {
            if (!uploadInProgress) {
                var files = e.dataTransfer.files;
                handleFileSelect(files);
            }
        }, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
    }

    /**
     * Handle file selection
     */
    function handleFileSelect(files) {
        if (!files || files.length === 0) {
            console.log('RM Panel: No files selected');
            return;
        }

        var file = files[0];
        console.log('RM Panel: Processing file:', file.name);

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
        reader.onload = function (e) {
            $('#rm-preview-image').attr('src', e.target.result);
            $('#rm-upload-area').hide();
            $('#rm-preview-area').show();
        };
        reader.onerror = function () {
            showMessage('error', 'Failed to read file');
        };
        reader.readAsDataURL(file);
    }

    /**
     * Save profile picture via AJAX
     */
    function saveProfilePicture() {
        var fileInput = document.getElementById('rm-profile-picture-input');

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showMessage('error', 'Please select an image first');
            return;
        }

        var $saveButton = $('#rm-save-profile-picture');
        var userId = $('.rm-profile-picture-image').data('user-id');

        // Prevent concurrent uploads
        if (uploadInProgress) {
            return;
        }

        uploadInProgress = true;

        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'rm_upload_profile_picture');
        formData.append('user_id', userId);
        formData.append('profile_picture', fileInput.files[0]);
        formData.append('nonce', rmProfilePicture.nonce);

        // Show loading state
        $saveButton.addClass('loading').prop('disabled', true);
        $('.rm-modal-cancel').prop('disabled', true);
        hideMessage();

        console.log('RM Panel: Starting upload...');

        // AJAX request
        $.ajax({
            url: rmProfilePicture.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // 30 second timeout
            success: function (response) {
                console.log('RM Panel: Upload response:', response);

                uploadInProgress = false;
                $saveButton.removeClass('loading').prop('disabled', false);
                $('.rm-modal-cancel').prop('disabled', false);

                if (response.success) {
                    // Update profile picture on page
                    $('.rm-profile-picture-image').attr('src', response.data.url);

                    // Show success message with FluentCRM sync status
                    var message = response.data.message || 'Profile picture updated successfully!';

                    // ✨ NEW: Add FluentCRM sync indicator
                    if (response.data.fluentcrm_synced) {
                        message += ' (Synced to FluentCRM)';
                    }

                    showMessage('success', message);

                    // Close modal after delay
                    setTimeout(function () {
                        closeModal();
                    }, 1500);
                } else {
                    showMessage('error', response.data.message || 'Failed to upload profile picture');
                }
            },
            error: function (xhr, status, error) {
                console.error('RM Panel: Upload error:', status, error);

                uploadInProgress = false;
                $saveButton.removeClass('loading').prop('disabled', false);
                $('.rm-modal-cancel').prop('disabled', false);

                showMessage('error', 'An error occurred while uploading. Please try again.');
            }
        });


    }

    /**
     * Show message in modal
     */
    function showMessage(type, message) {
        var $message = $('.rm-message');

        if ($message.length === 0) {
            $message = $('<div class="rm-message"></div>');
            $('.rm-modal-body').prepend($message);
        }

        $message
                .removeClass('success error')
                .addClass(type + ' show')
                .text(message);

        console.log('RM Panel: Message -', type, ':', message);
    }

    /**
     * Hide message
     */
    function hideMessage() {
        $('.rm-message').removeClass('show');
    }



})(jQuery);