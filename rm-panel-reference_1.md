# RM Panel Extensions - Project Reference Document

## 📋 Project Overview
**Plugin Name:** RM Panel Extensions  
**Version:** 1.0.3  
**Last Updated:** October 29, 2025  
**Purpose:** Comprehensive WordPress plugin with survey management, Elementor widgets, user tracking, Fluent Forms integration with real-time validation, country auto-detection, country mismatch prevention, and profile picture management

---

## 📁 File Structure

### Core Files
```
rm-panel-extensions.php (Main plugin file)
├── modules/
│   ├── survey/
│   │   ├── class-survey-module.php (CPT registration)
│   │   ├── class-survey-tracking.php (Response tracking)
│   │   ├── class-survey-callbacks.php (External survey returns)
│   │   ├── class-survey-approval-admin.php (Admin approval UI)
│   │   ├── class-survey-database-upgrade.php (DB version management)
│   │   ├── class-survey-tabs-shortcode.php (Available/Completed tabs)
│   │   └── class-survey-thank-you.php (Thank you pages)
│   ├── elementor/
│   │   ├── class-elementor-module.php (Main Elementor integration)
│   │   ├── widgets/
│   │   │   ├── login-widget.php (Login form)
│   │   │   ├── survey-listing-widget.php (Survey grid/list)
│   │   │   ├── survey-accordion-widget.php (Expandable survey list)
│   │   │   ├── survey-accordion-tabs-widget.php (Tabs + Accordion)
│   │   │   └── profile-picture-widget.php (Profile picture with upload)
│   │   └── templates/
│   │       └── login-form.php (Login form HTML)
│   ├── referral/
│   │   └── class-referral-system.php (Referral tracking)
│   ├── profile-picture/
│   │   └── class-profile-picture-handler.php (Profile picture AJAX handler)
│   └── fluent-forms/
│       └── class-fluent-forms-module.php (Fluent Forms integration, validation & country detection)
└── assets/
    ├── css/
    │   ├── All stylesheets
    │   ├── fluent-forms-validation.css (Real-time validation styles + country mismatch)
    │   └── profile-picture-widget.css (Profile picture widget styles)
    └── js/
        ├── All JavaScript files
        ├── fluent-forms-validation.js (Real-time validation, country detection & mismatch prevention)
        └── profile-picture-widget.js (Profile picture upload & interactions)
```

---

## 🔑 Key Classes & Methods

### 1. **RM_Panel_Survey_Module** (class-survey-module.php)
**Purpose:** Registers the Survey custom post type and taxonomy

**Key Methods:**
- `register_post_type()` - Registers 'rm_survey' CPT
- `register_taxonomy()` - Registers 'survey_category' taxonomy
- `add_meta_boxes()` - Adds survey meta boxes
- `save_survey_meta()` - Saves survey meta data

---

### 2. **RM_Panel_Survey_Tracking** (class-survey-tracking.php)
**Purpose:** Tracks user survey responses and completions

**Database Table:** `wp_rm_survey_responses`

**Key Methods:**
- `record_survey_start()` - Records when user starts a survey
- `record_survey_completion()` - Records survey completion with status
- `get_user_survey_history()` - Gets user's survey history
- `has_user_completed_survey()` - Checks if user completed specific survey

---

### 3. **RM_Survey_Callbacks** (class-survey-callbacks.php)
**Purpose:** Handles callbacks from external survey platforms

**Key Methods:**
- `handle_survey_callback()` - Processes return URLs with tokens
- `validate_token()` - Validates survey completion tokens
- `update_user_balance()` - Updates user earnings

---

### 4. **RM_Panel_Elementor_Module** (class-elementor-module.php)
**Purpose:** Integrates custom Elementor widgets

**Key Methods:**
- `register_widgets()` - Registers all custom widgets
- `add_widget_categories()` - Creates custom widget category
- `handle_login()` - AJAX login handler
- `load_more_surveys()` - AJAX survey pagination

---

### 5. **RM_Panel_Fluent_Forms_Module** (class-fluent-forms-module.php) - v1.0.2
**Purpose:** Integrates Fluent Forms with real-time validation for username, email, password fields, auto-detects country from IP, and prevents country mismatch

**Important:** Uses **Singleton Pattern** to prevent double initialization

**Singleton Implementation:**
```php
class RM_Panel_Fluent_Forms_Module {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize hooks
    }
}

// Usage in main plugin file (rm-panel-extensions.php):
RM_Panel_Fluent_Forms_Module::get_instance(); // ✅ CORRECT
// new RM_Panel_Fluent_Forms_Module(); // ❌ WRONG - Private constructor
```

**Key Methods:**
- `get_instance()` - **Singleton access method**
- `validate_password_confirmation($errors, $formData, $form, $fields)` - Main server-side validation (includes country validation)
- `validate_password_strength($password)` - Checks password complexity
- `check_username_availability()` - AJAX handler for real-time username validation
- `check_email_availability()` - AJAX handler for real-time email validation
- `check_password_strength()` - AJAX handler for real-time password strength checking
- `ajax_get_country_from_ip()` - AJAX handler for country detection (stores in session)
- `get_user_country_from_ip()` - Gets country from IPStack API
- `get_user_ip()` - Gets user's IP address
- `compare_countries($submitted, $detected)` - Compares submitted vs detected country with aliases
- `get_detected_country_from_session()` - Retrieves detected country from PHP session
- `enqueue_validation_scripts()` - Loads real-time validation JS/CSS (includes country_mismatch message)
- `add_settings_submenu()` - Adds admin settings page under Fluent Forms menu
- `render_settings_page()` - Renders per-form validation settings
- `save_form_settings()` - Saves per-form validation preferences
- `before_submission($insertData, $formData, $form)` - Pre-submission processing
- `create_wordpress_user($formData)` - Creates WordPress user from form data
- `custom_password_messages($message, $formData, $form)` - Custom error messages

---

### 6. **Profile_Picture_Widget** (profile-picture-widget.php) - ✨ NEW v1.0.3
**Purpose:** Elementor widget that displays user profile picture with upload/edit functionality

**Namespace:** `RMPanelExtensions\Modules\Elementor\Widgets\Profile_Picture_Widget`

**Key Features:**
- Display user profile picture, name, email, and country
- Click-to-upload modal interface
- Drag-and-drop file upload
- Real-time image preview
- AJAX-powered file upload
- Automatic image resizing (medium size)
- Integration with FluentCRM for country display
- Fallback to WordPress Gravatar

**Widget Controls:**
```php
// Content Section
'show_name'           // Toggle to show/hide full name
'show_email'          // Toggle to show/hide email
'show_country'        // Toggle to show/hide country
'default_avatar'      // Default avatar image (placeholder)
'upload_button_text'  // Text shown on upload overlay

// Style Section - Profile Picture
'picture_size'        // Profile picture dimensions (50-300px)
'picture_border'      // Border styling
'picture_border_radius' // Border radius (default 50% for circle)
'picture_box_shadow'  // Box shadow effect

// Style Section - Text
'name_typography'     // Typography for name
'name_color'          // Name text color
'email_typography'    // Typography for email
'email_color'         // Email text color
'country_typography'  // Typography for country
'country_color'       // Country text color

// Style Section - Container
'container_alignment' // Text alignment (left/center/right)
'container_padding'   // Container padding
'container_background' // Container background color
```

**User Data Retrieval:**
```php
// Get current user data
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$full_name = $current_user->display_name;
$email = $current_user->user_email;

// Get country from FluentCRM (priority) or user meta
if (class_exists('RM_Panel_FluentCRM_Helper')) {
    $country = RM_Panel_FluentCRM_Helper::get_contact_country($user_id);
}
if (empty($country)) {
    $country = get_user_meta($user_id, 'country', true);
}

// Get profile picture
$profile_picture_id = get_user_meta($user_id, 'rm_profile_picture', true);
if ($profile_picture_id) {
    $profile_picture_url = wp_get_attachment_image_url($profile_picture_id, 'medium');
} else {
    $profile_picture_url = get_avatar_url($user_id, ['size' => 150]);
}
```

**HTML Structure:**
```html
<div class="rm-profile-picture-container">
    <div class="rm-profile-picture-wrapper">
        <!-- Profile Picture with Overlay -->
        <div class="rm-profile-picture-image-wrapper">
            <img class="rm-profile-picture-image" data-user-id="{user_id}">
            <div class="rm-profile-picture-overlay">
                <span class="rm-profile-picture-icon">📷</span>
                <span class="rm-profile-picture-text">Click to Upload Photo</span>
            </div>
        </div>
        
        <!-- User Information -->
        <div class="rm-profile-info">
            <div class="rm-profile-name">{Full Name}</div>
            <div class="rm-profile-email">{email@example.com}</div>
            <div class="rm-profile-country">🌍 {Country}</div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="rm-profile-picture-modal" id="rm-profile-picture-modal">
    <div class="rm-modal-content">
        <div class="rm-modal-header">
            <h3>Update Profile Picture</h3>
            <span class="rm-modal-close">&times;</span>
        </div>
        <div class="rm-modal-body">
            <!-- Upload Area -->
            <div class="rm-upload-area" id="rm-upload-area">
                <div class="rm-upload-icon">📤</div>
                <p>Click to upload or drag and drop</p>
                <p class="rm-upload-hint">PNG, JPG, GIF up to 5MB</p>
                <input type="file" id="rm-profile-picture-input" accept="image/*">
            </div>
            <!-- Preview Area -->
            <div class="rm-preview-area" id="rm-preview-area" style="display: none;">
                <img src="" alt="Preview" id="rm-preview-image">
                <button id="rm-change-image">Change Image</button>
            </div>
        </div>
        <div class="rm-modal-footer">
            <button class="rm-modal-cancel">Cancel</button>
            <button id="rm-save-profile-picture">Save Changes</button>
        </div>
    </div>
</div>
```

**Login Check:**
```php
protected function render() {
    if (!is_user_logged_in()) {
        echo '<p>' . __('Please log in to view your profile.', 'rm-panel-extensions') . '</p>';
        return;
    }
    // ... render widget
}
```

---

### 7. **RM_Profile_Picture_Handler** (class-profile-picture-handler.php) - ✨ NEW v1.0.3
**Purpose:** Handles AJAX profile picture uploads, validation, and management

**Pattern:** Singleton

**Singleton Implementation:**
```php
class RM_Profile_Picture_Handler {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }
}

// Initialize
RM_Profile_Picture_Handler::get_instance();
```

**AJAX Endpoints:**
```php
// Upload profile picture
add_action('wp_ajax_rm_upload_profile_picture', 'upload_profile_picture');

// Get current profile picture
add_action('wp_ajax_rm_get_profile_picture', 'get_profile_picture');

// Delete profile picture
add_action('wp_ajax_rm_delete_profile_picture', 'delete_profile_picture');
```

**Upload Validation:**
```php
// File Type Validation
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

// File Size Validation
$max_size = 5 * 1024 * 1024; // 5MB

// Security Checks
- Nonce verification: 'rm_profile_picture_nonce'
- User login check: is_user_logged_in()
- User ID match verification
- MIME type validation
- File size validation
```

**Upload Process:**
```php
1. Verify nonce and user authentication
2. Validate file type and size
3. Upload to WordPress media library using media_handle_upload()
4. Store attachment ID in user meta: 'rm_profile_picture'
5. Delete old profile picture (if not used by other users)
6. Log action in user history
7. Return success response with image URL
```

**User Meta Storage:**
```php
// Current profile picture
update_user_meta($user_id, 'rm_profile_picture', $attachment_id);

// History tracking (last 5 entries)
update_user_meta($user_id, 'rm_profile_picture_history', [
    [
        'user_id' => $user_id,
        'attachment_id' => $attachment_id,
        'timestamp' => current_time('mysql'),
        'ip_address' => $ip_address
    ]
]);
```

**Helper Method:**
```php
/**
 * Get profile picture URL for a user
 * 
 * @param int $user_id User ID
 * @param string $size Image size (thumbnail, medium, large, full)
 * @return string Image URL
 */
public static function get_user_profile_picture($user_id, $size = 'medium') {
    $attachment_id = get_user_meta($user_id, 'rm_profile_picture', true);
    
    if ($attachment_id) {
        $image_url = wp_get_attachment_image_url($attachment_id, $size);
        if ($image_url) {
            return $image_url;
        }
    }
    
    // Fallback to WordPress avatar
    return get_avatar_url($user_id, ['size' => 150]);
}
```

**AJAX Response Format:**
```javascript
// Success Response
{
    success: true,
    data: {
        message: "Profile picture updated successfully!",
        url: "https://site.com/wp-content/uploads/2025/10/image-150x150.jpg",
        full_url: "https://site.com/wp-content/uploads/2025/10/image.jpg",
        attachment_id: 123
    }
}

// Error Response
{
    success: false,
    data: {
        message: "Invalid file type. Only JPG, PNG, and GIF are allowed"
    }
}
```

**Smart Cleanup:**
```php
/**
 * Only delete old profile picture if not used by other users
 */
private function maybe_delete_old_picture($attachment_id) {
    global $wpdb;
    
    $usage_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->usermeta} 
        WHERE meta_key = 'rm_profile_picture' 
        AND meta_value = %d",
        $attachment_id
    ));
    
    // Only delete if not used by any other user
    if ($usage_count == 0) {
        wp_delete_attachment($attachment_id, true);
    }
}
```

---

### 8. **Profile Picture JavaScript** (profile-picture-widget.js) - ✨ NEW v1.0.3
**Purpose:** Handles modal interactions, file upload, drag-and-drop, and AJAX submission

**Dependencies:** jQuery

**Initialization:**
```javascript
(function($) {
    'use strict';
    
    $(document).ready(function() {
        initProfilePictureWidget();
    });
})(jQuery);
```

**Event Handlers:**
```javascript
// Modal Control
$(document).on('click', '.rm-profile-picture-image-wrapper', openModal);
$(document).on('click', '.rm-modal-close, .rm-modal-cancel', closeModal);
$(document).on('click', '.rm-profile-picture-modal', clickOutsideModal);
$(document).on('keydown', escapeKeyHandler); // ESC key

// File Upload
$(document).on('click', '#rm-upload-area', triggerFileInput);
$(document).on('change', '#rm-profile-picture-input', handleFileSelect);
$(document).on('click', '#rm-change-image', triggerFileInput);

// Save Button
$(document).on('click', '#rm-save-profile-picture', saveProfilePicture);
```

**Drag & Drop:**
```javascript
function setupDragAndDrop() {
    var uploadArea = document.getElementById('rm-upload-area');
    
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
}

function highlight(e) {
    uploadArea.classList.add('dragover');
}

function unhighlight(e) {
    uploadArea.classList.remove('dragover');
}

function handleDrop(e) {
    var files = e.dataTransfer.files;
    handleFileSelect(files);
}
```

**File Validation:**
```javascript
function handleFileSelect(files) {
    // Safety check
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
```

**AJAX Upload:**
```javascript
function saveProfilePicture() {
    var fileInput = $('#rm-profile-picture-input')[0];
    var userId = $('.rm-profile-picture-image').data('user-id');
    
    // Prepare FormData
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
        processData: false,  // Don't process data
        contentType: false,  // Don't set content type
        success: function(response) {
            $saveButton.removeClass('loading').prop('disabled', false);
            
            if (response.success) {
                // Update profile picture on page
                $('.rm-profile-picture-image').attr('src', response.data.url);
                
                // Show success message
                showMessage('success', response.data.message || 'Profile picture updated successfully!');
                
                // Close modal after delay
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
```

**Modal Management:**
```javascript
function closeModal() {
    $('#rm-profile-picture-modal').removeClass('active');
    $('body').css('overflow', ''); // Restore scrolling
    
    // Reset modal state
    setTimeout(function() {
        $('#rm-upload-area').show();
        $('#rm-preview-area').hide();
        
        // Reset file input (prevents infinite loop)
        var $fileInput = $('#rm-profile-picture-input');
        var $newInput = $fileInput.clone();
        $fileInput.replaceWith($newInput);
        
        $('#rm-preview-image').attr('src', '');
        hideMessage();
    }, 300); // Wait for fade out animation
}
```

**Message System:**
```javascript
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

function hideMessage() {
    $('.rm-message').removeClass('show');
}
```

**Localized Script Variables:**
```javascript
rmProfilePicture = {
    ajax_url: 'https://site.com/wp-admin/admin-ajax.php',
    nonce: 'abc123def456...'
}
```

---

### 9. **Profile Picture CSS** (profile-picture-widget.css) - ✨ NEW v1.0.3
**Purpose:** Comprehensive styling for profile picture display, modal, and interactions

**Key CSS Classes:**
```css
/* Profile Picture Display */
.rm-profile-picture-container        // Main container
.rm-profile-picture-image-wrapper    // Image wrapper with hover effect
.rm-profile-picture-image            // Actual image (150x150, circular)
.rm-profile-picture-overlay          // Hover overlay with upload icon

/* Profile Information */
.rm-profile-info                     // Info container
.rm-profile-name                     // User's full name
.rm-profile-email                    // User's email
.rm-profile-country                  // User's country with icon

/* Modal */
.rm-profile-picture-modal            // Modal backdrop
.rm-modal-content                    // Modal container
.rm-modal-header                     // Modal header with close button
.rm-modal-body                       // Modal body content
.rm-modal-footer                     // Modal footer with buttons

/* Upload Interface */
.rm-upload-area                      // Drag-drop upload area
.rm-upload-area.dragover             // Highlighted state when dragging
.rm-preview-area                     // Image preview area
.rm-preview-image                    // Preview image

/* Buttons */
.rm-btn                              // Base button style
.rm-btn-primary                      // Primary action button
.rm-btn-secondary                    // Secondary action button
.rm-btn.loading                      // Loading state

/* Messages */
.rm-message                          // Message container
.rm-message.success                  // Success message (green)
.rm-message.error                    // Error message (red)
```

**Hover Effect:**
```css
.rm-profile-picture-image-wrapper {
    position: relative;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.rm-profile-picture-image-wrapper:hover {
    transform: scale(1.05);
}

.rm-profile-picture-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.rm-profile-picture-image-wrapper:hover .rm-profile-picture-overlay {
    opacity: 1;
}
```

**Animations:**
```css
/* Fade In (Modal) */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Slide Up (Modal Content) */
@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Slide Down (Messages) */
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Spin (Loading) */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
```

**Responsive Design:**
```css
@media (max-width: 768px) {
    .rm-modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .rm-profile-picture-image {
        width: 120px;
        height: 120px;
    }
    
    .rm-modal-header h3 {
        font-size: 18px;
    }
    
    .rm-modal-footer {
        flex-direction: column;
    }
    
    .rm-btn {
        width: 100%;
    }
}
```

**Elementor Editor Fixes:**
```css
/* Disable pointer events in editor to prevent modal opening */
.elementor-editor-active .rm-profile-picture-image-wrapper {
    pointer-events: none;
}

/* Show overlay partially in editor for preview */
.elementor-editor-active .rm-profile-picture-overlay {
    opacity: 0.5;
}
```

**Loading State:**
```css
.rm-btn.loading .rm-btn-text {
    display: none;
}

.rm-btn.loading .rm-btn-loader {
    display: inline-block;
}

.eicon-loading.eicon-animation-spin {
    animation: spin 1s linear infinite;
}
```

**Drag & Drop Visual Feedback:**
```css
.rm-upload-area {
    border: 2px dashed #d0d0d0;
    background-color: #fafafa;
    transition: all 0.3s ease;
}

.rm-upload-area:hover {
    border-color: #0073aa;
    background-color: #f0f7ff;
}

.rm-upload-area.dragover {
    border-color: #0073aa;
    background-color: #e6f3ff;
}
```

---

## 🔧 Important Settings

### Profile Picture Widget Setting - ✨ NEW v1.0.3

**Location:** RM Panel Ext → Settings

**Field Name:** `enable_profile_picture_widget`

**Default:** Enabled (1)

**Setting Structure:**
```php
$sanitized['enable_profile_picture_widget'] = isset($settings['enable_profile_picture_widget']) ? 1 : 0;
```

**Script Enqueuing:**
```php
// In rm-panel-extensions.php enqueue_frontend_scripts() method
if (is_user_logged_in()) {
    // Enqueue Profile Picture CSS
    wp_enqueue_style(
        'rm-profile-picture-widget',
        RM_PANEL_EXT_PLUGIN_URL . 'assets/css/profile-picture-widget.css',
        [],
        RM_PANEL_EXT_VERSION
    );

    // Enqueue Profile Picture JavaScript
    wp_enqueue_script(
        'rm-profile-picture-widget',
        RM_PANEL_EXT_PLUGIN_URL . 'assets/js/profile-picture-widget.js',
        ['jquery'],
        RM_PANEL_EXT_VERSION,
        true
    );

    // Localize script with AJAX configuration
    wp_localize_script('rm-profile-picture-widget', 'rmProfilePicture', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rm_profile_picture_nonce')
    ]);
}
```

**⚠️ CRITICAL:** Scripts only load for logged-in users to prevent unnecessary loading on public pages.

---

### IPStack API Key Setting

**Location:** RM Panel Ext → Settings

**Field Name:** `rm_panel_ipstack_api_key`

**Saving Logic:**
```php
// In rm-panel-extensions.php save_settings() method:
if (isset($settings['ipstack_api_key'])) {
    $api_key = sanitize_text_field($settings['ipstack_api_key']);
    update_option('rm_panel_ipstack_api_key', $api_key);
}
```

**Getting Free API Key:**
1. Visit: https://ipstack.com
2. Sign up for free account (no credit card required)
3. Free tier: 100 requests/month
4. Copy your API key
5. Paste in RM Panel Ext → Settings
6. Click "Save Settings"

**Testing API Key:**
```php
// Quick test
$api_key = get_option('rm_panel_ipstack_api_key', '');
$ip = '8.8.8.8'; // Test with Google's IP
$url = "http://api.ipstack.com/{$ip}?access_key={$api_key}";
$response = wp_remote_get($url);
$data = json_decode(wp_remote_retrieve_body($response), true);
print_r($data);
```

---

## 🔄 Module Loading Order

The plugin loads modules in this order:
1. Survey Module (independent)
2. Survey Tracking (depends on Survey Module)
3. Survey Callbacks (depends on Survey Module)
4. Elementor Module (if Elementor active)
5. **Profile Picture Handler** - ✨ NEW
6. **Fluent Forms Module (if Fluent Forms active) - Uses Singleton Pattern**
7. Referral System (depends on Survey Module)

**Integration Code in `rm-panel-extensions.php`:**

```php
/**
 * Load modules
 */
private function load_modules() {
    // ... other module loading code ...
    
    // Load Profile Picture Handler - NEW
    $profile_picture_handler_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/profile-picture/class-profile-picture-handler.php';
    if (file_exists($profile_picture_handler_file)) {
        require_once $profile_picture_handler_file;
    }
    
    // ... rest of code ...
}
```

**Elementor Widget Registration:**
```php
// In class-elementor-module.php register_widgets() method
public function register_widgets($widgets_manager) {
    // ... other widgets ...
    
    // Register Profile Picture Widget
    require_once RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/profile-picture-widget.php';
    $widgets_manager->register(new \RMPanelExtensions\Modules\Elementor\Widgets\Profile_Picture_Widget());
}
```

---

## 🐛 Common Issues & Solutions - UPDATED v1.0.3

### Issue 20: Profile Picture Not Uploading - ✨ NEW
**Problem:** File selected but upload fails  
**Possible Causes:**
1. JavaScript not loaded properly
2. Nonce verification failing
3. File permissions issue
4. Max upload size exceeded
5. AJAX URL incorrect

**Solutions:**

**A. Check JavaScript Console:**
```javascript
// F12 → Console tab
// Should NOT see these errors:
// - "rmProfilePicture is not defined"
// - "Uncaught ReferenceError"
// - "Failed to load resource"
```

**B. Verify Script Loaded:**
```javascript
// Browser console
console.log(rmProfilePicture);
// Should output: {ajax_url: "...", nonce: "..."}
```

**C. Check Nonce:**
```php
// Temporary debug in upload_profile_picture() method
error_log('Nonce received: ' . $_POST['nonce']);
error_log('Nonce valid: ' . wp_verify_nonce($_POST['nonce'], 'rm_profile_picture_nonce'));
```

**D. Check File Permissions:**
```bash
# uploads folder should be writable
chmod 755 wp-content/uploads
```

**E. Check PHP Upload Limits:**
```php
// In wp-config.php or php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

**F. Enable Debug Logging:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check debug.log for errors
```

---

### Issue 21: Modal Not Opening - ✨ NEW
**Problem:** Clicking profile picture does nothing  
**Possible Causes:**
1. jQuery not loaded
2. JavaScript conflicts
3. Elementor editor mode active
4. Event handler not attached

**Solutions:**

**A. Check jQuery:**
```javascript
// Browser console
jQuery.fn.jquery
// Should show version number like "3.6.0"
```

**B. Check Event Handler:**
```javascript
// Browser console
jQuery._data(jQuery('.rm-profile-picture-image-wrapper')[0], 'events')
// Should show "click" event
```

**C. Manual Modal Open (Test):**
```javascript
// Browser console
jQuery('#rm-profile-picture-modal').addClass('active');
jQuery('body').css('overflow', 'hidden');
```

**D. Check Elementor Editor:**
- Profile picture widget has pointer-events disabled in editor
- Must preview page or view on frontend
- Check CSS: `.elementor-editor-active .rm-profile-picture-image-wrapper { pointer-events: none; }`

**E. Check for JavaScript Errors:**
```javascript
// F12 → Console tab
// Look for errors before clicking
// Common: "$ is not defined" = jQuery issue
```

---

### Issue 22: Drag & Drop Not Working - ✨ NEW
**Problem:** Can't drag files into upload area  
**Possible Causes:**
1. Browser doesn't support drag & drop
2. Event listeners not attached
3. Z-index issue covering upload area
4. File type not supported

**Solutions:**

**A. Check Browser Support:**
```javascript
// Modern browsers support this
'draggable' in document.createElement('div')
// Should return: true
```

**B. Test Event Listeners:**
```javascript
// Browser console
document.getElementById('rm-upload-area')
// Should return the element, not null
```

**C. Test Manually:**
```javascript
// Browser console
setupDragAndDrop(); // Re-initialize
```

**D. Check Element Visibility:**
```css
/* Make sure upload area is visible */
#rm-upload-area {
    display: block !important;
    z-index: 1 !important;
}
```

**E. Use Click Upload Instead:**
- Fallback: Click "Click to upload" text
- Opens file browser
- Same validation applies

---

### Issue 23: Old Profile Picture Not Deleting - ✨ NEW
**Problem:** Multiple pictures accumulating in media library  
**Possible Causes:**
1. Picture used by multiple users
2. Smart cleanup disabled
3. Permissions issue

**Solutions:**

**A. Check Cleanup Function:**
```php
// In maybe_delete_old_picture() method
error_log('Checking usage for attachment: ' . $attachment_id);
error_log('Usage count: ' . $usage_count);
```

**B. Manual Cleanup:**
```php
// Find unused profile pictures
global $wpdb;
$all_attachments = $wpdb->get_col(
    "SELECT meta_value FROM {$wpdb->usermeta} 
    WHERE meta_key = 'rm_profile_picture'"
);

// Get all media with 'profile' in name not in use
// Delete manually from Media Library
```

**C. Disable Smart Cleanup (Keep All):**
```php
// In maybe_delete_old_picture() method
// Comment out wp_delete_attachment() call
// Pictures will never be deleted
```

---

### Issue 24: Profile Picture Not Showing After Upload - ✨ NEW
**Problem:** Upload succeeds but image doesn't update  
**Possible Causes:**
1. Cache issue
2. Wrong image URL in response
3. JavaScript not updating src
4. Browser cache

**Solutions:**

**A. Check AJAX Response:**
```javascript
// In browser console → Network tab
// Find "admin-ajax.php" request
// Check response JSON for "url" field
```

**B. Hard Refresh:**
```
- Windows: Ctrl + F5
- Mac: Cmd + Shift + R
```

**C. Clear Browser Cache:**
```
- Chrome: Settings → Privacy → Clear browsing data
- Select "Cached images and files"
```

**D. Manual Update Test:**
```javascript
// Browser console
jQuery('.rm-profile-picture-image').attr('src', 'NEW_URL_HERE');
```

**E. Check Image Size:**
```php
// Verify 'medium' size exists
$sizes = get_intermediate_image_sizes();
print_r($sizes);
// Should include 'medium'
```

---

### Issue 25: Upload Works But Country Not Showing - ✨ NEW
**Problem:** Profile picture displays but country is blank  
**Possible Causes:**
1. FluentCRM not active
2. Country not set in FluentCRM
3. User meta 'country' not set

**Solutions:**

**A. Check FluentCRM:**
```php
// Check if FluentCRM active
if (defined('FLUENTCRM')) {
    echo 'FluentCRM is active';
} else {
    echo 'FluentCRM is NOT active';
}
```

**B. Check Country Data:**
```php
// Check FluentCRM country
if (class_exists('RM_Panel_FluentCRM_Helper')) {
    $country = RM_Panel_FluentCRM_Helper::get_contact_country($user_id);
    echo 'FluentCRM Country: ' . $country;
}

// Check user meta
$country_meta = get_user_meta($user_id, 'country', true);
echo 'User Meta Country: ' . $country_meta;
```

**C. Set Country Manually:**
```php
// Set in user meta
update_user_meta($user_id, 'country', 'United States');

// Or set in FluentCRM
// Go to FluentCRM → Contacts → Edit Contact → Country
```

**D. Widget Setting:**
```
- Edit widget in Elementor
- Content → Show Country → Enable
- Update page
```

---

## 🧪 Testing Checklist - UPDATED v1.0.3

### Profile Picture Widget Testing - ✨ NEW
- [ ] Widget appears in Elementor under RM Panel Widgets category
- [ ] Widget shows message for logged-out users
- [ ] Widget displays current user's name
- [ ] Widget displays current user's email
- [ ] Widget displays current user's country (if available)
- [ ] Widget shows default avatar if no custom picture
- [ ] Widget shows custom profile picture if uploaded
- [ ] Hover effect shows upload icon and text
- [ ] Clicking picture opens modal
- [ ] Modal backdrop appears
- [ ] Modal content animates in
- [ ] Close button (X) closes modal
- [ ] Cancel button closes modal
- [ ] Clicking outside modal closes it
- [ ] ESC key closes modal
- [ ] Clicking upload area opens file browser
- [ ] File browser filters to images only
- [ ] Selecting non-image shows error
- [ ] Selecting file > 5MB shows error
- [ ] Valid image shows preview
- [ ] Preview image displays correctly
- [ ] "Change Image" button works
- [ ] Drag & drop file onto upload area works
- [ ] Dragover highlights upload area
- [ ] Dropping file shows preview
- [ ] Save button is disabled initially
- [ ] Save button enables after file selection
- [ ] Clicking Save shows loading spinner
- [ ] Save button disables during upload
- [ ] Upload success updates profile picture on page
- [ ] Success message displays
- [ ] Modal closes automatically after success
- [ ] Error message displays on failure
- [ ] Modal state resets after closing
- [ ] File input resets after closing
- [ ] Widget respects Content settings (show/hide name, email, country)
- [ ] Widget respects Style settings (size, colors, spacing)
- [ ] Responsive design works on mobile
- [ ] Widget doesn't interfere with other widgets
- [ ] Multiple instances work independently
- [ ] Works in Elementor preview mode
- [ ] Picture displays correctly after page refresh

### AJAX Handler Testing - ✨ NEW
- [ ] Nonce verification works
- [ ] User authentication check works
- [ ] User ID validation works
- [ ] File type validation works (JPG, PNG, GIF only)
- [ ] File size validation works (5MB max)
- [ ] Upload to media library succeeds
- [ ] User meta updates correctly
- [ ] Old picture deleted if unused
- [ ] Old picture kept if used by others
- [ ] History log updated
- [ ] IP address captured
- [ ] Success response includes URL
- [ ] Success response includes attachment ID
- [ ] Error responses have clear messages
- [ ] 'get_profile_picture' endpoint works
- [ ] 'delete_profile_picture' endpoint works

### Integration Testing - ✨ NEW
- [ ] FluentCRM country display works
- [ ] Falls back to user meta country
- [ ] Works without FluentCRM installed
- [ ] Gravatar fallback works
- [ ] Scripts load only for logged-in users
- [ ] Scripts don't load for logged-out users
- [ ] No JavaScript errors in console
- [ ] No PHP errors in debug.log
- [ ] Settings page checkbox works
- [ ] Disabling widget removes from Elementor
- [ ] Widget works with WPML (if installed)
- [ ] Widget works with different themes
- [ ] Widget works with page builders besides Elementor

### Fluent Forms - Real-time Validation
- [ ] Username: Type less than 5 characters → Error
- [ ] Username: Type invalid characters (!, @, #) → Error
- [ ] Username: Type valid username → "Checking..."
- [ ] Username: Type existing username → Error: "already taken"
- [ ] Username: Type new username → Success: "available!"
- [ ] Email: Type invalid format → Error
- [ ] Email: Type valid email → "Checking..."
- [ ] Email: Type existing email → Error: "already registered"
- [ ] Email: Type new email → Success: "available!"
- [ ] Password: Type weak password → "Checking..."
- [ ] Password: Type weak password → Warning or Error
- [ ] Password: Type strong password → Success: "Strong!"
- [ ] Confirm Password: Type mismatch → Error: "do not match"
- [ ] Confirm Password: Type match → Success: "match!"

### Country Detection Testing
- [ ] IPStack API key saved in settings
- [ ] Verify API key in database
- [ ] Form has field named exactly `country`
- [ ] Validation enabled for the specific form
- [ ] Open form in incognito/private window
- [ ] Open browser console (F12)
- [ ] See "Detecting country..." message
- [ ] AJAX call appears in Network tab
- [ ] Response contains country name
- [ ] Country field auto-fills correctly
- [ ] "Country detected!" success message appears
- [ ] Works on live server (not localhost)
- [ ] Cache working (second load is faster)
- [ ] Error logging working in debug.log
- [ ] Test with VPN (different countries)
- [ ] Test with dropdown and text input fields
- [ ] Verify exact match priority (India ≠ British Indian Ocean Territory)

### Country Mismatch Validation Testing
- [ ] Country auto-detects (e.g., "India")
- [ ] Field shows green success message
- [ ] Wait for 3 seconds (validation initializes)
- [ ] Try to change country to different value
- [ ] **Expected:** Red error message appears immediately
- [ ] **Expected:** Field border turns red
- [ ] **Expected:** Shake animation plays
- [ ] **Expected:** Error message: "Please select your actual country"
- [ ] Try to submit form with wrong country
- [ ] **Expected:** Form submission blocked
- [ ] **Expected:** Page scrolls to country field
- [ ] **Expected:** Console log: "Form submission blocked"
- [ ] Change country back to detected value
- [ ] **Expected:** Error clears, green border returns
- [ ] Submit form with correct country
- [ ] **Expected:** Form submits successfully
- [ ] Test with JavaScript disabled (server-side validation)
- [ ] **Expected:** Server returns country mismatch error
- [ ] Test with country aliases (USA vs United States)
- [ ] **Expected:** Aliases accepted (no error)
- [ ] Check `data-detected-value` attribute set correctly
- [ ] Check `data-country-detected` attribute set correctly
- [ ] Test session storage (refresh page within 30 min)
- [ ] **Expected:** Country still validated against session

---

## 📝 Quick Reference Commands - UPDATED v1.0.3

### Profile Picture - ✨ NEW

```php
// Get user's profile picture URL
$picture_url = RM_Profile_Picture_Handler::get_user_profile_picture($user_id);
$picture_url = RM_Profile_Picture_Handler::get_user_profile_picture($user_id, 'full');

// Get attachment ID
$attachment_id = get_user_meta($user_id, 'rm_profile_picture', true);

// Set profile picture manually
update_user_meta($user_id, 'rm_profile_picture', 123); // attachment ID

// Delete profile picture
delete_user_meta($user_id, 'rm_profile_picture');

// Get profile picture history
$history = get_user_meta($user_id, 'rm_profile_picture_history', true);
print_r($history);

// Clear history
delete_user_meta($user_id, 'rm_profile_picture_history');

// Check if user has custom picture
$has_picture = !empty(get_user_meta($user_id, 'rm_profile_picture', true));

// Get all users with profile pictures
global $wpdb;
$users_with_pictures = $wpdb->get_results(
    "SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
    WHERE meta_key = 'rm_profile_picture'"
);

// Count total profile pictures uploaded
$total = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} 
    WHERE meta_key = 'rm_profile_picture' AND meta_value != ''"
);
```

### JavaScript Console Commands - ✨ NEW

```javascript
// Check if script loaded
typeof initProfilePictureWidget; // Should show "function"

// Check localized variables
console.log(rmProfilePicture);

// Manually open modal
jQuery('#rm-profile-picture-modal').addClass('active');

// Manually close modal
jQuery('#rm-profile-picture-modal').removeClass('active');

// Check if file selected
jQuery('#rm-profile-picture-input')[0].files.length;

// Get current profile picture URL
jQuery('.rm-profile-picture-image').attr('src');

// Manually update profile picture
jQuery('.rm-profile-picture-image').attr('src', 'NEW_URL');

// Show success message manually
showMessage('success', 'Test message');

// Show error message manually
showMessage('error', 'Test error');

// Check drag & drop support
'draggable' in document.createElement('div');

// List all event handlers on upload area
jQuery._data(jQuery('#rm-upload-area')[0], 'events');

// Reset modal state
jQuery('#rm-upload-area').show();
jQuery('#rm-preview-area').hide();

// Check if modal is open
jQuery('#rm-profile-picture-modal').hasClass('active');
```

### Fluent Forms - Country Detection

```php
// Check if API key is set
$api_key = get_option('rm_panel_ipstack_api_key', '');
echo !empty($api_key) ? 'Set' : 'Not Set';

// Clear country cache for an IP
$ip = '8.8.8.8';
$cache_key = 'rm_country_' . md5($ip);
delete_transient($cache_key);

// Test IPStack API directly
$api_key = get_option('rm_panel_ipstack_api_key', '');
$response = wp_remote_get("http://api.ipstack.com/8.8.8.8?access_key={$api_key}");
$data = json_decode(wp_remote_retrieve_body($response), true);
print_r($data);

// Check session country
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo $_SESSION['rm_detected_country'];

// Clear session country
unset($_SESSION['rm_detected_country']);
unset($_SESSION['rm_detected_country_time']);
```

---

## 🔐 Important Security Notes - UPDATED v1.0.3

1. **Token Validation:** All callback URLs MUST include valid token
2. **Nonce Verification:** All AJAX requests use `wp_verify_nonce()`
3. **User Capabilities:** Admin functions check `manage_options`
4. **SQL Injection:** All queries use `$wpdb->prepare()`
5. **Password Security:** Fluent Forms module never stores raw passwords
6. **User Registration:** Passwords automatically hashed by `wp_create_user()`
7. **Input Sanitization:** All form inputs sanitized using WordPress functions
8. **Username Sanitization:** All usernames sanitized with `sanitize_user()`
9. **Email Sanitization:** All emails sanitized with `sanitize_email()`
10. **AJAX Security:** Real-time validation uses nonce verification for each endpoint
11. **Double Validation:** Client-side validation backed by server-side checks
12. **Rate Limiting:** Consider implementing rate limiting for AJAX validation endpoints
13. **Singleton Pattern:** Prevents double initialization and duplicate menu items
14. **API Key Storage:** IPStack API key stored securely in wp_options
15. **IP Address Validation:** User IP validated before API calls
16. **Cache Security:** Transient cache prevents excessive API calls
17. **Error Logging:** Sensitive data not logged in production
18. **Session Security:** PHP sessions used for country validation
19. **Country Validation:** Cannot be bypassed - both client and server validation
20. **Session Timeout:** 30-minute expiration for security
21. **XSS Prevention:** All country values sanitized with `sanitize_text_field()`
22. **Country Comparison:** Normalized comparison prevents case-sensitive bypasses
23. **File Upload Security - NEW:** File type validation prevents malicious uploads
24. **File Size Limits - NEW:** 5MB max prevents server overload
25. **User Authentication - NEW:** Profile picture uploads require login
26. **User ID Verification - NEW:** Prevents users from uploading as other users
27. **MIME Type Validation - NEW:** Uses `wp_check_filetype()` for real validation
28. **Media Library Integration - NEW:** Uses WordPress functions for secure uploads
29. **Smart Cleanup - NEW:** Prevents accidental deletion of shared images
30. **Upload Logging - NEW:** All uploads logged with IP and timestamp

---

## 📊 Performance Optimization - UPDATED v1.0.3

### Profile Picture Optimization - ✨ NEW
- ✅ Scripts load only for logged-in users (conditional loading)
- ✅ Images resized to 'medium' size automatically (reduces bandwidth)
- ✅ Old pictures deleted only if unused (prevents orphaned files)
- ✅ Modal content lazy-loaded (not rendered until opened)
- ✅ AJAX upload with progress indication
- ✅ Smart cleanup checks before deletion
- ✅ History limited to last 5 entries (prevents database bloat)
- ✅ Fallback to WordPress Gravatar (no custom storage needed)
- ✅ CSS animations use GPU acceleration (transform, opacity)
- ✅ JavaScript uses event delegation (better performance)
- ✅ File input reset after upload (prevents memory leaks)
- ✅ Preview uses FileReader API (no server upload until save)
- ✅ Drag & drop uses native events (no heavy libraries)

### Country Detection Optimization
- ✅ 5-minute transient cache reduces API calls by ~99%
- ✅ Cache key based on IP hash (not raw IP for privacy)
- ✅ AJAX timeout set to 10 seconds (prevents hanging)
- ✅ Graceful fallback if API fails (form still usable)
- ✅ Conditional loading (only on enabled forms)
- ✅ Multiple detection attempts (0s, 1s, 2s) improve success rate
- ✅ Session storage prevents re-detection on form reloads
- ✅ 30-minute session reduces API calls further
- ✅ Exact match priority reduces comparison operations

### API Call Optimization
```php
// Cache structure
$cache_key = 'rm_country_' . md5($ip); // Hashed for privacy
set_transient($cache_key, $country, 5 * MINUTE_IN_SECONDS);

// Session storage
$_SESSION['rm_detected_country'] = $country;
$_SESSION['rm_detected_country_time'] = time();
// 30-minute timeout reduces API calls

// Check cache first
$cached_country = get_transient($cache_key);
if ($cached_country !== false) {
    return $cached_country; // No API call needed
}
```

### Database Optimization - ✨ NEW
```php
// Profile picture history limited to 5 entries
$history = array_slice($history, -4); // Keep only last 4
$history[] = $log_entry; // Add new entry = 5 total

// Smart cleanup query
$usage_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} 
    WHERE meta_key = 'rm_profile_picture' 
    AND meta_value = %d",
    $attachment_id
));
// Indexed query, very fast
```

### Image Optimization - ✨ NEW
```php
// Automatic resize to 'medium' size (default 300x300)
$image_url = wp_get_attachment_image_url($attachment_id, 'medium');

// Benefits:
// - Reduces page load time
// - Saves bandwidth
// - Maintains quality for profile pictures
// - WordPress handles resizing automatically
```

### Monitoring - ✨ NEW
```php
// Track upload statistics
// Number of users with custom pictures
$total_users = $wpdb->get_var(
    "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
    WHERE meta_key = 'rm_profile_picture' AND meta_value != ''"
);

// Average picture size
// Check media library attachment sizes
```

---

## 🚀 Future Reference Usage - UPDATED v1.0.3

**Instead of pasting files, say:**
- "Check the Profile Picture Widget section" - ✨ NEW
- "Reference: RM_Profile_Picture_Handler::get_instance() - Singleton Pattern" - ✨ NEW
- "See 'Issue 20: Profile Picture Not Uploading' in Common Issues" - ✨ NEW
- "Check Profile Picture Widget Testing checklist" - ✨ NEW
- "Reference: RM_Profile_Picture_Handler::upload_profile_picture()" - ✨ NEW
- "Reference: Profile Picture JavaScript Event Handlers" - ✨ NEW
- "See 'Profile Picture CSS Animations' section" - ✨ NEW
- "Check 'Profile Picture Optimization' in Performance section" - ✨ NEW
- "Reference: Profile Picture AJAX Response Format" - ✨ NEW
- "See 'Smart Cleanup' in Security Notes" - ✨ NEW
- "Check the Country Detection & Validation Flow section"
- "Reference: RM_Panel_Fluent_Forms_Module::get_instance() - Singleton Pattern"
- "See 'Issue 15: Country Matching India to British Indian Ocean Territory' (FIXED in v1.0.2)"
- "See 'Issue 16: Country Mismatch Not Showing Error' in Common Issues"
- "Check Fluent Forms Country Detection & Validation Configuration"
- "Reference: RM_Panel_Fluent_Forms_Module::ajax_get_country_from_ip()"
- "Reference: RM_Panel_Fluent_Forms_Module::compare_countries()"
- "Reference: RM_Panel_Fluent_Forms_Module::get_detected_country_from_session()"
- "Reference: validateCountrySelection() in JavaScript"
- "Reference: initializeCountryValidation() in JavaScript"
- "See 'Issue 17: Form Still Submits Despite Mismatch' in Common Issues"
- "Check Country Mismatch Validation Testing checklist"
- "Reference: Country Alias Matching section"
- "See Session Storage Checklist for debugging"

---

## 📋 Version History

### v1.0.3 (October 29, 2025) - ✨ NEW
**✨ NEW: Profile Picture Management**
- Added Profile Picture Widget for Elementor
- Added profile picture upload with drag & drop
- Added real-time image preview in modal
- Added AJAX-powered file upload system
- Added RM_Profile_Picture_Handler class (Singleton)
- Added smart cleanup for unused profile pictures
- Added upload history tracking (last 5 uploads)
- Added file type validation (JPG, PNG, GIF)
- Added file size validation (5MB max)
- Added user authentication and authorization checks
- Added FluentCRM integration for country display
- Added Gravatar fallback for users without custom pictures
- Added responsive design for mobile devices
- Added Elementor editor compatibility
- Added comprehensive CSS animations
- Added loading states and error handling
- Fixed: Scripts now load only for logged-in users
- Improved: Conditional script loading for better performance
- Improved: Image resize to 'medium' for optimization
- Updated: All testing checklists and troubleshooting guides

### v1.0.2 (October 16, 2025)
**✨ NEW: Country Mismatch Prevention**
- Added client-side country validation on change event
- Added form submission blocking for country mismatch
- Added server-side country validation with session storage
- Added compare_countries() method with alias matching
- Added get_detected_country_from_session() method
- Added 30-minute session timeout for detected country
- Added red border + shake animation on mismatch
- Added scroll to field on validation error
- Added comprehensive error messages for country mismatch
- Fixed: "India" now matches exactly (not "British Indian Ocean Territory")
- Improved: Exact match priority for country selection
- Improved: Better alias handling (India/IN, USA/US, etc.)
- Updated: All testing checklists and troubleshooting guides

### v1.0.1 (January 2025)
**✨ NEW: Country Auto-Detection**
- Real-time username validation with 5 character minimum
- Real-time email validation with availability checking
- Real-time password strength indicator (weak/medium/strong)
- Auto-detect country from IP using IPStack API
- 5-minute cache for country detection
- Multiple detection attempts for reliability
- Per-form validation settings in admin
- Conditional script loading based on form settings
- Singleton pattern to prevent double initialization
- Three separate AJAX endpoints with nonce security
- Country detection AJAX endpoint
- Comprehensive visual feedback system
- IPStack API integration with error handling

### v1.0.0 (Initial Release)
- Survey custom post type
- Elementor widgets
- User tracking
- Basic Fluent Forms integration

---

**Version:** 1.0.3  
**Last Updated:** October 29, 2025  
**Latest Features:** 
- **Profile Picture Widget with upload functionality** ✨ NEW
- **Drag & drop file upload** ✨ NEW
- **Real-time image preview** ✨ NEW
- **AJAX profile picture management** ✨ NEW
- **Smart cleanup for unused images** ✨ NEW
- **Upload history tracking** ✨ NEW
- **FluentCRM integration for country display** ✨ NEW
- **Gravatar fallback** ✨ NEW
- **Comprehensive security validation** ✨ NEW
- Real-time username validation with 5 character minimum
- Real-time email validation with availability checking
- Real-time password strength indicator (weak/medium/strong)
- Auto-detect country from IP using IPStack API
- 5-minute cache for country detection
- Multiple detection attempts for reliability
- Per-form validation settings in admin
- Conditional script loading based on form settings
- Singleton pattern to prevent double initialization
- Three separate AJAX endpoints with nonce security
- Country detection AJAX endpoint
- Comprehensive visual feedback system
- IPStack API integration with error handling
- Country mismatch prevention (client + server)
- Exact match priority for country selection
- Session-based country validation
- Red border + shake animation on error
- Form submission blocking
- 30-minute session timeout
- Country alias matching system
- Comprehensive error feedback
