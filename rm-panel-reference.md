# RM Panel Extensions - Complete Technical Reference

**Plugin Name:** RM Panel Extensions  
**Version:** 1.0.4.1  
**Last Updated:** October 31, 2025  
**Purpose:** Comprehensive WordPress plugin for Research and Metric panel management with survey tracking, user registration, Elementor widgets, and admin customization

---

## 📑 Table of Contents

1. [Overview](#overview)
2. [File Structure](#file-structure)
3. [Key Classes & Methods](#key-classes--methods)
4. [Module System](#module-system)
5. [Admin Bar Manager](#admin-bar-manager)
6. [Profile Picture Widget](#profile-picture-widget)
7. [CSS Classes & Styling](#css-classes--styling)
8. [JavaScript Functions](#javascript-functions)
9. [AJAX Endpoints](#ajax-endpoints)
10. [Database Schema](#database-schema)
11. [Configuration](#configuration)
12. [Testing Checklist](#testing-checklist)
13. [Common Issues & Solutions](#common-issues--solutions)
14. [Security Notes](#security-notes)
15. [Performance Optimization](#performance-optimization)
16. [Version History](#version-history)

---

## 📖 Overview

RM Panel Extensions is a comprehensive WordPress plugin designed for Research and Metric's panel management system. It provides:

- **Survey Management:** Track external survey completions via callback URLs
- **User Registration:** Custom registration with country auto-detection
- **Elementor Widgets:** Login, Survey Listing, and Profile Picture widgets
- **Admin Bar Control:** Role-based admin bar visibility management
- **FluentCRM Integration:** Automatic contact creation and tagging
- **Profile Management:** User profile picture upload with AJAX
- **Security:** Nonce verification, IP logging, and parameter validation

---

## 📁 File Structure

```
rm-panel-extensions/
├── rm-panel-extensions.php (Main plugin file)
├── includes/
│   ├── class-rm-panel-core.php (Core plugin class)
│   ├── class-rm-panel-settings.php (Settings page manager)
│   └── class-rm-panel-constants.php (Plugin constants)
├── modules/
│   ├── survey-tracking/
│   │   ├── class-survey-tracker.php (Survey completion tracking)
│   │   └── class-callback-handler.php (External callback handler)
│   ├── registration/
│   │   ├── class-registration-handler.php (User registration logic)
│   │   └── class-country-detector.php (IPStack API integration)
│   ├── elementor/
│   │   ├── class-elementor-integration.php (Elementor module loader)
│   │   ├── widgets/
│   │   │   ├── class-login-widget.php (Login form widget)
│   │   │   ├── class-survey-listing-widget.php (Survey list widget)
│   │   │   └── class-profile-picture-widget.php (Profile picture widget)
│   ├── fluent-forms/
│   │   └── class-fluent-forms-module.php (FluentCRM integration)
│   ├── profile-picture/
│   │   └── class-profile-picture-handler.php (Profile picture upload handler)
│   └── admin-bar/
│       └── class-admin-bar-manager.php (Admin bar visibility manager)
├── templates/
│   ├── login-form.php (Login form template)
│   └── survey-list.php (Survey listing template)
├── assets/
│   ├── css/
│   │   ├── login-widget.css (Login widget styles)
│   │   ├── survey-listing-widget.css (Survey listing styles)
│   │   ├── profile-picture-widget.css (Profile picture widget styles)
│   │   └── admin-bar-settings.css (Admin bar settings page styles)
│   └── js/
│       ├── login-widget.js (Login form validation)
│       ├── survey-listing-widget.js (Survey list interactions)
│       ├── profile-picture-widget.js (Profile picture upload & modal)
│       └── admin-bar-settings.js (Admin bar settings page interactions)
└── languages/
    └── rm-panel-extensions.pot (Translation template)
```

---

## 🔑 Key Classes & Methods

### 1. RM_Panel_Core

**Location:** `includes/class-rm-panel-core.php`

**Purpose:** Central plugin initialization and module management

**Singleton Implementation:**
```php
private static $instance = null;
public static function get_instance()
```

**Key Methods:**
- `init()` - Initialize all modules
- `load_modules()` - Load active modules based on settings
- `register_elementor_widgets()` - Register custom Elementor widgets
- `enqueue_assets()` - Load CSS/JS files

**Module Loading:**
```php
$this->modules = [
    'survey_tracking' => new RM_Panel_Survey_Tracker(),
    'registration' => new RM_Panel_Registration_Handler(),
    'elementor' => new RM_Panel_Elementor_Integration(),
    'admin_bar' => RM_Panel_Admin_Bar_Manager::get_instance()
];
```

---

### 2. RM_Panel_Survey_Tracker

**Location:** `modules/survey-tracking/class-survey-tracker.php`

**Purpose:** Track survey completions via callback URLs with external survey parameters

**Database Table:** `{prefix}_rm_survey_completions`

**Key Methods:**

1. **process_callback($survey_id, $user_id, $params)**
   - Validates callback authenticity
   - Logs completion to database
   - Returns JSON response
   - Stores external survey parameters

2. **get_user_surveys($user_id)**
   - Returns array of completed surveys
   - Includes completion dates and status

3. **validate_callback_security($params)**
   - Verifies nonce or signature
   - Checks IP whitelist
   - Validates timestamp

**Callback URL Format:**
```
https://yoursite.com/wp-json/rm-panel/v1/survey-complete?
    survey_id={SURVEY_ID}&
    user_id={USER_ID}&
    nonce={NONCE}&
    external_params={JSON_ENCODED_PARAMS}
```

**External Parameters Storage:**
- Stored as JSON in `external_params` column
- Can include: completion_time, survey_platform, response_id, etc.
- Retrieved with `get_external_params($completion_id)`

---

### 3. RM_Panel_Registration_Handler

**Location:** `modules/registration/class-registration-handler.php`

**Purpose:** Handle user registration with country auto-detection and FluentCRM integration

**Key Methods:**

1. **process_registration($data)**
   - Validates user input (email, username, password)
   - Detects country via IPStack API
   - Creates WordPress user
   - Triggers FluentCRM contact creation
   - Sends welcome email
   - Returns user_id or WP_Error

2. **detect_country($ip_address)**
   - Calls IPStack API
   - Returns country code (ISO 3166-1 alpha-2)
   - Caches results for 24 hours

3. **validate_email($email)**
   - Checks format
   - Verifies domain
   - Checks against blacklist

**Country Detection:**
```php
// Automatic via IP
$country = $this->detect_country($_SERVER['REMOTE_ADDR']);

// Manual override
$country = isset($_POST['country']) ? $_POST['country'] : $detected_country;

// Store in user meta
update_user_meta($user_id, 'billing_country', $country);
```

**FluentCRM Integration:**
```php
// Triggered automatically on registration
do_action('rm_panel_user_registered', $user_id, $data);

// Creates contact with tags
- Tag: "Panel Member"
- Tag: "Country: {COUNTRY_CODE}"
- List: "Main Panel"
```

---

### 4. RM_Panel_Elementor_Integration

**Location:** `modules/elementor/class-elementor-integration.php`

**Purpose:** Register and manage custom Elementor widgets

**Registered Widgets:**
1. **Login Widget** - Custom login form with AJAX
2. **Survey Listing Widget** - Display user's available/completed surveys
3. **Profile Picture Widget** - User profile picture upload

**Widget Registration:**
```php
add_action('elementor/widgets/register', function($widgets_manager) {
    $widgets_manager->register(new RM_Panel_Login_Widget());
    $widgets_manager->register(new RM_Panel_Survey_Widget());
    $widgets_manager->register(new RM_Panel_Profile_Picture_Widget());
});
```

**Widget Categories:**
- Category: "rm-panel"
- Display Name: "RM Panel"

---

### 5. RM_Panel_Fluent_Forms_Module

**Location:** `modules/fluent-forms/class-fluent-forms-module.php`

**Purpose:** Integrate form submissions with FluentCRM

**Key Methods:**

1. **handle_submission($entry_id, $form_data, $form_id)**
   - Extracts user data from form
   - Creates/updates FluentCRM contact
   - Applies tags based on form fields
   - Adds to email lists

2. **create_crm_contact($data)**
   - Maps form fields to CRM fields
   - Handles custom fields
   - Returns contact_id

**Form Field Mapping:**
```php
$field_map = [
    'email' => 'email',
    'first_name' => 'first_name',
    'last_name' => 'last_name',
    'country' => 'country',
    'phone' => 'phone',
    'age' => 'custom_field_age',
    'gender' => 'custom_field_gender'
];
```

**Automatic Tags:**
- Source: "Registration Form"
- Country: From IP or form field
- Status: "Active Panel Member"

---

### 6. RM_Profile_Picture_Handler

**Location:** `modules/profile-picture/class-profile-picture-handler.php`

**Purpose:** Handle user profile picture uploads via AJAX with automatic cleanup

**Singleton Implementation:**
```php
private static $instance = null;
public static function get_instance()
```

**Key Methods:**

**AJAX Handlers:**

1. **ajax_upload_profile_picture()**
   - Validates nonce and user permissions
   - Validates file type (jpg, jpeg, png, gif)
   - Validates file size (max 2MB)
   - Uploads to WordPress media library
   - Deletes old profile picture
   - Updates user meta with new attachment ID
   - Returns JSON response with image URL

2. **ajax_get_profile_picture()**
   - Retrieves current user's profile picture
   - Returns attachment ID and image URLs
   - Fallback to default avatar if no picture

3. **ajax_delete_profile_picture()**
   - Deletes attachment from media library
   - Removes user meta
   - Returns success response

**Helper Methods:**

4. **delete_old_profile_picture($user_id)**
   - Gets old attachment ID from user meta
   - Deletes attachment file
   - Removes from media library

5. **validate_image_file($file)**
   - Checks file type whitelist
   - Validates MIME type
   - Checks file size limits
   - Returns true/false

6. **log_upload_attempt($user_id, $success, $details)**
   - Logs upload history to custom table
   - Stores IP address
   - Records file details
   - Tracks success/failure

7. **get_user_profile_picture_url($user_id, $size)**
   - Gets image URL for specific size
   - Returns full, medium, or thumbnail
   - Fallback to avatar if no picture

**Static Methods:**

8. **get_default_avatar_url()**
   - Returns plugin default avatar
   - Used when user has no profile picture

**User Meta Storage:**
```php
// Attachment ID stored in user meta
update_user_meta($user_id, 'rm_profile_picture_id', $attachment_id);

// Retrieve
$attachment_id = get_user_meta($user_id, 'rm_profile_picture_id', true);
```

**File Validation Rules:**
- **Allowed types:** image/jpeg, image/jpg, image/png, image/gif
- **Max size:** 2MB (2097152 bytes)
- **Extensions:** .jpg, .jpeg, .png, .gif
- **MIME validation:** Uses WordPress wp_check_filetype()

**AJAX Response Format:**
```php
// Success
{
    "success": true,
    "data": {
        "attachment_id": 123,
        "url": "https://site.com/uploads/profile-pic.jpg",
        "thumbnail": "https://site.com/uploads/profile-pic-150x150.jpg"
    }
}

// Error
{
    "success": false,
    "data": "Error message here"
}
```

---

### 7. RM_Panel_Admin_Bar_Manager

**Location:** `modules/admin-bar/class-admin-bar-manager.php`

**Version:** 1.0.4.1 (FIXED - Corrected inverted logic)

**Purpose:** Manage WordPress admin bar visibility based on user roles with explicit enable/disable logic

**Singleton Implementation:**
```php
private static $instance = null;
public static function get_instance()
```

**Key Methods:**

**Core Management:**

1. **manage_admin_bar()**
   - Gets admin bar settings from database
   - Checks if current user should see admin bar
   - **EXPLICITLY ENABLES** with `show_admin_bar(true)` + filter
   - **EXPLICITLY DISABLES** with `show_admin_bar(false)` + filter
   - Triggered on `after_setup_theme` hook

2. **should_show_admin_bar($settings)**
   - Returns: `bool` (true = show, false = hide)
   - Checks if user is logged in
   - Gets user roles
   - Compares user roles against allowed roles in settings
   - Returns true if ANY user role is allowed
   - Default: false (hide if no match)

3. **hide_admin_bar_css()**
   - Adds CSS to completely hide admin bar
   - Removes top margin artifacts
   - Fixes Elementor editor spacing
   - Triggered on `wp_head` and `admin_head` (priority 999)

**Settings Management:**

4. **get_admin_bar_settings()**
   - Retrieves settings from `rm_panel_admin_bar_settings` option
   - Returns array with role => enabled pairs
   - Falls back to defaults if empty

5. **save_settings($settings)**
   - **Static method**
   - Validates settings against available roles
   - Saves validated settings to database
   - Returns: `bool` (success/failure)

6. **get_all_roles()**
   - **Static method**
   - Returns all WordPress roles
   - Format: `['role_key' => ['name' => 'role_key', 'display_name' => 'Role Name']]`

7. **get_default_settings()**
   - **Static method**
   - Returns default admin bar visibility settings
   - Default: Only administrators can see admin bar

8. **reset_to_defaults()**
   - **Static method**
   - Resets settings to default configuration
   - Returns: `bool` (success/failure)

**Settings Structure:**
```php
// Database option: rm_panel_admin_bar_settings
[
    'administrator' => '1',  // Show admin bar
    'editor' => '0',         // Hide admin bar
    'author' => '0',         // Hide admin bar
    'contributor' => '0',    // Hide admin bar
    'subscriber' => '0'      // Hide admin bar
]

// '1' = enabled (show admin bar)
// '0' = disabled (hide admin bar)
```

**Default Settings:**
```php
[
    'administrator' => '1', // Admins can see
    'editor' => '0',        // Editors cannot see
    'author' => '0',        // Authors cannot see
    'contributor' => '0',   // Contributors cannot see
    'subscriber' => '0'     // Subscribers cannot see
]
```

**CSS Injection (for hidden admin bar):**
```css
/* Hide admin bar completely */
#wpadminbar {
    display: none !important;
}

/* Remove top margin added by admin bar */
html {
    margin-top: 0 !important;
}

body.admin-bar {
    margin-top: 0 !important;
}

/* Fix for Elementor editor */
body.elementor-editor-active {
    margin-top: 0 !important;
}
```

**Hook Implementation:**
```php
// Initialize hooks
add_action('after_setup_theme', [$this, 'manage_admin_bar']);
add_action('wp_head', [$this, 'hide_admin_bar_css'], 999);
add_action('admin_head', [$this, 'hide_admin_bar_css'], 999);
```

**Usage Examples:**

```php
// Get instance
$admin_bar_manager = RM_Panel_Admin_Bar_Manager::get_instance();

// Save settings (from admin page)
$new_settings = [
    'administrator' => '1',
    'editor' => '1',
    'subscriber' => '0'
];
RM_Panel_Admin_Bar_Manager::save_settings($new_settings);

// Reset to defaults
RM_Panel_Admin_Bar_Manager::reset_to_defaults();

// Get all available roles
$roles = RM_Panel_Admin_Bar_Manager::get_all_roles();
```

**Logic Flow:**

1. User visits site (frontend or admin)
2. `after_setup_theme` hook triggers `manage_admin_bar()`
3. Settings loaded from database
4. User roles checked against settings
5. If ANY role matches enabled setting → Show admin bar (explicit enable)
6. If NO roles match → Hide admin bar (explicit disable)
7. CSS injected if hidden to remove visual artifacts

**Version History:**

- **v1.0.4:** Initial admin bar manager implementation
- **v1.0.4.1:** Fixed inverted logic bug - now explicitly enables/disables admin bar

---

## 🧩 Module System

### Module Loading Order

1. **Core** (always loaded first) - Uses Singleton Pattern
2. **Settings** (loads after core) - Uses Singleton Pattern
3. **Survey Tracking** (conditional) - Active if surveys enabled
4. **Registration** (conditional) - Active if registration enabled
5. **Elementor Integration** (conditional) - Active if Elementor installed
6. **Profile Picture Handler** (always loaded) - Uses Singleton Pattern
7. **Admin Bar Manager** (always loaded) - Uses Singleton Pattern
8. **FluentCRM** (conditional) - Active if FluentCRM installed

### Module Initialization

```php
// In main plugin file
function rm_panel_extensions_init() {
    // Core initialization
    $core = RM_Panel_Core::get_instance();
    
    // Load modules
    if (rm_panel_is_module_active('survey_tracking')) {
        require_once RM_PANEL_PLUGIN_DIR . 'modules/survey-tracking/class-survey-tracker.php';
    }
    
    if (rm_panel_is_module_active('registration')) {
        require_once RM_PANEL_PLUGIN_DIR . 'modules/registration/class-registration-handler.php';
    }
    
    // Elementor (check if Elementor is active)
    if (did_action('elementor/loaded')) {
        require_once RM_PANEL_PLUGIN_DIR . 'modules/elementor/class-elementor-integration.php';
    }
    
    // Profile Picture Handler (always load)
    require_once RM_PANEL_PLUGIN_DIR . 'modules/profile-picture/class-profile-picture-handler.php';
    RM_Profile_Picture_Handler::get_instance();
    
    // Admin Bar Manager (always load)
    require_once RM_PANEL_PLUGIN_DIR . 'modules/admin-bar/class-admin-bar-manager.php';
    RM_Panel_Admin_Bar_Manager::get_instance();
    
    // FluentCRM (check if FluentCRM is active)
    if (function_exists('FluentCrm')) {
        require_once RM_PANEL_PLUGIN_DIR . 'modules/fluent-forms/class-fluent-forms-module.php';
    }
}
add_action('plugins_loaded', 'rm_panel_extensions_init');
```

---

## 🎛️ Admin Bar Manager

### Settings Page

**Location:** WordPress Admin → Settings → RM Panel → Admin Bar

**Purpose:** Control which user roles can see the WordPress admin bar

### Settings Page Structure

```php
<div class="wrap rm-panel-admin-bar-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rm_panel_admin_bar_settings_save'); ?>
        
        <table class="form-table">
            <tbody>
                <?php foreach ($roles as $role_key => $role_data): ?>
                <tr>
                    <th scope="row">
                        <label for="role_<?php echo esc_attr($role_key); ?>">
                            <?php echo esc_html($role_data['display_name']); ?>
                        </label>
                    </th>
                    <td>
                        <label class="switch">
                            <input type="checkbox" 
                                   id="role_<?php echo esc_attr($role_key); ?>"
                                   name="admin_bar_roles[<?php echo esc_attr($role_key); ?>]"
                                   value="1"
                                   <?php checked($settings[$role_key], '1'); ?>>
                            <span class="slider"></span>
                        </label>
                        <p class="description">
                            <?php echo $settings[$role_key] === '1' ? 'Can see admin bar' : 'Cannot see admin bar'; ?>
                        </p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary" name="rm_panel_save_admin_bar">
                Save Settings
            </button>
            <button type="submit" class="button" name="rm_panel_reset_admin_bar">
                Reset to Defaults
            </button>
        </p>
    </form>
</div>
```

### Settings Page Handler

```php
// Handle form submission
if (isset($_POST['rm_panel_save_admin_bar'])) {
    check_admin_referer('rm_panel_admin_bar_settings_save');
    
    $settings = isset($_POST['admin_bar_roles']) ? $_POST['admin_bar_roles'] : [];
    
    if (RM_Panel_Admin_Bar_Manager::save_settings($settings)) {
        add_settings_error(
            'rm_panel_admin_bar',
            'settings_saved',
            'Admin bar settings saved successfully.',
            'success'
        );
    }
}

// Handle reset
if (isset($_POST['rm_panel_reset_admin_bar'])) {
    check_admin_referer('rm_panel_admin_bar_settings_save');
    
    if (RM_Panel_Admin_Bar_Manager::reset_to_defaults()) {
        add_settings_error(
            'rm_panel_admin_bar',
            'settings_reset',
            'Admin bar settings reset to defaults.',
            'success'
        );
    }
}
```

### Settings Page CSS

**File:** `assets/css/admin-bar-settings.css`

```css
.rm-panel-admin-bar-settings .form-table {
    margin-top: 20px;
}

.rm-panel-admin-bar-settings .switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.rm-panel-admin-bar-settings .switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.rm-panel-admin-bar-settings .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.rm-panel-admin-bar-settings .slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.rm-panel-admin-bar-settings input:checked + .slider {
    background-color: #2271b1;
}

.rm-panel-admin-bar-settings input:checked + .slider:before {
    transform: translateX(26px);
}

.rm-panel-admin-bar-settings .description {
    margin-top: 5px;
    font-style: italic;
}
```

---

## 🎨 Profile Picture Widget - Elementor Integration

### Widget Location

**File:** `modules/elementor/widgets/class-profile-picture-widget.php`

**Category:** RM Panel

**Name:** Profile Picture Upload

**Icon:** eicon-user-circle-o

### Widget Features

1. **User Info Display**
   - Shows logged-in user's name
   - Displays current profile picture or default avatar
   - Hover effect reveals "Change Picture" overlay

2. **Modal Upload Interface**
   - Animated slide-down modal
   - Drag & drop support
   - File selection button
   - Real-time preview
   - Upload progress indication

3. **AJAX Upload**
   - No page reload required
   - Instant feedback
   - Error handling
   - Success messages

### Widget Settings (Elementor Editor)

```php
protected function register_controls() {
    // Style Section
    $this->start_controls_section(
        'style_section',
        [
            'label' => 'Style',
            'tab' => Controls_Manager::TAB_STYLE,
        ]
    );
    
    // Avatar size
    $this->add_responsive_control(
        'avatar_size',
        [
            'label' => 'Avatar Size',
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [
                'px' => [
                    'min' => 50,
                    'max' => 300,
                    'step' => 10,
                ],
            ],
            'default' => [
                'unit' => 'px',
                'size' => 120,
            ],
        ]
    );
    
    // Border radius
    $this->add_control(
        'avatar_border_radius',
        [
            'label' => 'Border Radius',
            'type' => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors' => [
                '{{WRAPPER}} .profile-picture-avatar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
    );
    
    $this->end_controls_section();
}
```

---

## 💅 CSS Classes & Styling

### Profile Picture Widget CSS

**File:** `assets/css/profile-picture-widget.css`

### Main Container Classes

```css
.rm-profile-picture-widget {
    max-width: 400px;
    margin: 0 auto;
    padding: 20px;
    text-align: center;
}

.profile-picture-container {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}
```

### Overlay Effect

```css
.profile-picture-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
    border-radius: 50%;
}

.profile-picture-container:hover .profile-picture-overlay {
    opacity: 1;
}

.profile-picture-overlay-text {
    color: white;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
}
```

### User Information

```css
.profile-picture-user-info {
    margin-top: 15px;
}

.profile-picture-user-name {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 5px 0;
}

.profile-picture-user-email {
    font-size: 14px;
    color: #666;
    margin: 0;
}
```

### Modal Styles

```css
.profile-picture-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 999999;
    animation: fadeIn 0.3s ease;
}

.profile-picture-modal-content {
    position: relative;
    background: white;
    max-width: 500px;
    margin: 50px auto;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.4s ease;
}

.profile-picture-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.profile-picture-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    line-height: 1;
    padding: 0;
    width: 30px;
    height: 30px;
}
```

### Upload Area

```css
.profile-picture-upload-area {
    padding: 40px 20px;
    text-align: center;
    border: 2px dashed #ccc;
    margin: 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.profile-picture-upload-area:hover,
.profile-picture-upload-area.drag-over {
    border-color: #2271b1;
    background: #f0f7ff;
}

.upload-icon {
    font-size: 48px;
    color: #2271b1;
    margin-bottom: 15px;
}
```

### Preview Area

```css
.profile-picture-preview-area {
    display: none;
    padding: 20px;
    text-align: center;
}

.profile-picture-preview-area.active {
    display: block;
}

.preview-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 50%;
    margin-bottom: 15px;
    border: 3px solid #e0e0e0;
}
```

### Button Styles

```css
.profile-picture-button {
    display: inline-block;
    padding: 12px 30px;
    background: #2271b1;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: background 0.3s ease;
}

.profile-picture-button:hover {
    background: #135e96;
}

.profile-picture-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.profile-picture-button.secondary {
    background: #f0f0f0;
    color: #333;
}

.profile-picture-button.secondary:hover {
    background: #e0e0e0;
}
```

### Message Styles

```css
.profile-picture-message {
    display: none;
    padding: 12px 20px;
    margin: 20px;
    border-radius: 4px;
    font-size: 14px;
}

.profile-picture-message.success {
    display: block;
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.profile-picture-message.error {
    display: block;
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
```

### Admin Bar Settings CSS

**File:** `assets/css/admin-bar-settings.css`

```css
/* Settings page container */
.rm-panel-admin-bar-settings {
    max-width: 800px;
}

.rm-panel-admin-bar-settings h1 {
    margin-bottom: 20px;
}

/* Form table styling */
.rm-panel-admin-bar-settings .form-table {
    margin-top: 20px;
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.rm-panel-admin-bar-settings .form-table th {
    width: 200px;
    padding: 20px 10px;
    font-weight: 600;
}

.rm-panel-admin-bar-settings .form-table td {
    padding: 20px 10px;
}

/* Toggle switch */
.rm-panel-admin-bar-settings .switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.rm-panel-admin-bar-settings .switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.rm-panel-admin-bar-settings .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.rm-panel-admin-bar-settings .slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.rm-panel-admin-bar-settings input:checked + .slider {
    background-color: #2271b1;
}

.rm-panel-admin-bar-settings input:focus + .slider {
    box-shadow: 0 0 1px #2271b1;
}

.rm-panel-admin-bar-settings input:checked + .slider:before {
    transform: translateX(26px);
}

/* Description text */
.rm-panel-admin-bar-settings .description {
    margin-top: 8px;
    font-style: italic;
    color: #646970;
}

/* Submit buttons */
.rm-panel-admin-bar-settings .submit {
    padding: 20px 0;
}

.rm-panel-admin-bar-settings .button {
    margin-right: 10px;
}

/* Info box */
.rm-panel-admin-bar-info {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 15px;
    margin: 20px 0;
}

.rm-panel-admin-bar-info p {
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
}

/* Role status indicator */
.role-status {
    display: inline-block;
    margin-left: 10px;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.role-status.enabled {
    background: #d4edda;
    color: #155724;
}

.role-status.disabled {
    background: #f8d7da;
    color: #721c24;
}
```

### Animation Keyframes

```css
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
```

### Responsive Breakpoints

```css
@media (max-width: 768px) {
    .profile-picture-modal-content {
        margin: 20px;
        max-width: calc(100% - 40px);
    }
    
    .profile-picture-avatar {
        width: 100px !important;
        height: 100px !important;
    }
    
    .profile-picture-upload-area {
        padding: 30px 15px;
    }
    
    .rm-panel-admin-bar-settings {
        margin: 10px;
    }
    
    .rm-panel-admin-bar-settings .form-table th {
        width: auto;
        display: block;
        padding: 15px 10px 5px;
    }
    
    .rm-panel-admin-bar-settings .form-table td {
        display: block;
        padding: 5px 10px 15px;
    }
}

@media (max-width: 480px) {
    .profile-picture-button {
        display: block;
        width: 100%;
        margin-bottom: 10px;
    }
}
```

---

## 🎯 JavaScript Functions

### Profile Picture Widget JS

**File:** `assets/js/profile-picture-widget.js`

### Main Functions

```javascript
(function($) {
    'use strict';
    
    // Main initialization
    $(document).ready(function() {
        initProfilePictureWidget();
    });
    
    function initProfilePictureWidget() {
        // Bind events
        $('.profile-picture-container').on('click', openModal);
        $('.profile-picture-modal-close').on('click', closeModal);
        $('.profile-picture-modal').on('click', function(e) {
            if ($(e.target).hasClass('profile-picture-modal')) {
                closeModal();
            }
        });
        
        // File input
        $('#profile-picture-file-input').on('change', handleFileSelect);
        
        // Drag and drop
        const uploadArea = $('.profile-picture-upload-area');
        uploadArea.on('dragover', handleDragOver);
        uploadArea.on('dragleave', handleDragLeave);
        uploadArea.on('drop', handleDrop);
        
        // Upload button
        $('#upload-profile-picture-btn').on('click', uploadProfilePicture);
        
        // Cancel button
        $('#cancel-upload-btn').on('click', resetModal);
    }
})(jQuery);
```

### Event Handlers

```javascript
function openModal() {
    $('.profile-picture-modal').fadeIn(300);
    resetModal();
}

function closeModal() {
    $('.profile-picture-modal').fadeOut(300);
    resetModal();
}

function handleFileSelect(e) {
    const file = e.target.files[0];
    if (file && validateFile(file)) {
        previewFile(file);
    }
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('drag-over');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('drag-over');
    
    const file = e.originalEvent.dataTransfer.files[0];
    if (file && validateFile(file)) {
        previewFile(file);
        $('#profile-picture-file-input')[0].files = e.originalEvent.dataTransfer.files;
    }
}
```

### File Validation

```javascript
function validateFile(file) {
    // Check file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        showMessage('Please select a valid image file (JPG, PNG, or GIF)', 'error');
        return false;
    }
    
    // Check file size (2MB max)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        showMessage('File size must be less than 2MB', 'error');
        return false;
    }
    
    return true;
}

function previewFile(file) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        $('.preview-image').attr('src', e.target.result);
        $('.profile-picture-upload-area').hide();
        $('.profile-picture-preview-area').addClass('active');
        $('#upload-profile-picture-btn').prop('disabled', false);
    };
    
    reader.readAsDataURL(file);
}
```

### AJAX Upload

```javascript
function uploadProfilePicture() {
    const fileInput = $('#profile-picture-file-input')[0];
    const file = fileInput.files[0];
    
    if (!file) {
        showMessage('Please select a file', 'error');
        return;
    }
    
    // Disable upload button
    $('#upload-profile-picture-btn').prop('disabled', true).text('Uploading...');
    
    // Create FormData
    const formData = new FormData();
    formData.append('action', 'rm_upload_profile_picture');
    formData.append('nonce', rmProfilePicture.nonce);
    formData.append('profile_picture', file);
    
    // AJAX request
    $.ajax({
        url: rmProfilePicture.ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Update profile picture
                $('.profile-picture-avatar').attr('src', response.data.url);
                showMessage('Profile picture updated successfully!', 'success');
                
                // Close modal after 2 seconds
                setTimeout(function() {
                    closeModal();
                }, 2000);
            } else {
                showMessage(response.data, 'error');
            }
        },
        error: function() {
            showMessage('An error occurred. Please try again.', 'error');
        },
        complete: function() {
            $('#upload-profile-picture-btn').prop('disabled', false).text('Upload Picture');
        }
    });
}
```

### Helper Functions

```javascript
function showMessage(message, type) {
    const messageEl = $('.profile-picture-message');
    messageEl.removeClass('success error').addClass(type).text(message).show();
    
    setTimeout(function() {
        messageEl.fadeOut();
    }, 5000);
}

function resetModal() {
    $('#profile-picture-file-input').val('');
    $('.preview-image').attr('src', '');
    $('.profile-picture-upload-area').show();
    $('.profile-picture-preview-area').removeClass('active');
    $('.profile-picture-message').hide();
    $('#upload-profile-picture-btn').prop('disabled', true);
}
```

### Localized Variables

```javascript
// Localized from PHP
const rmProfilePicture = {
    ajaxurl: '/wp-admin/admin-ajax.php',
    nonce: 'abc123xyz789',
    maxFileSize: 2097152, // 2MB in bytes
    allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
};
```

---

## 🔧 Frontend Script Enqueue Configuration

### Critical Enqueue Code for Profile Picture Widget

**Location:** Add this to your main plugin file or in the Profile Picture Handler class

```php
/**
 * Enqueue profile picture widget assets
 * 
 * IMPORTANT: This must be called on 'wp_enqueue_scripts' hook
 */
public function enqueue_profile_picture_assets() {
    // Only load on pages with the widget
    if (!$this->has_profile_picture_widget()) {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'rm-profile-picture-widget',
        RM_PANEL_PLUGIN_URL . 'assets/css/profile-picture-widget.css',
        [],
        RM_PANEL_VERSION
    );
    
    // Enqueue JavaScript
    wp_enqueue_script(
        'rm-profile-picture-widget',
        RM_PANEL_PLUGIN_URL . 'assets/js/profile-picture-widget.js',
        ['jquery'], // jQuery dependency is REQUIRED
        RM_PANEL_VERSION,
        true // Load in footer
    );
    
    // Localize script with AJAX data
    wp_localize_script(
        'rm-profile-picture-widget',
        'rmProfilePicture',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rm_profile_picture_nonce'),
            'maxFileSize' => 2097152, // 2MB
            'allowedTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
            'messages' => [
                'invalidType' => __('Please select a valid image file (JPG, PNG, or GIF)', 'rm-panel-extensions'),
                'tooLarge' => __('File size must be less than 2MB', 'rm-panel-extensions'),
                'uploadError' => __('An error occurred during upload. Please try again.', 'rm-panel-extensions'),
                'uploadSuccess' => __('Profile picture updated successfully!', 'rm-panel-extensions'),
            ]
        ]
    );
    
    // Debug log (remove in production)
    if (WP_DEBUG) {
        error_log('RM Panel: Profile picture assets enqueued');
    }
}

/**
 * Check if current page has profile picture widget
 * 
 * @return bool
 */
private function has_profile_picture_widget() {
    // Method 1: Check if Elementor page with widget
    if (class_exists('\Elementor\Plugin')) {
        $document = \Elementor\Plugin::$instance->documents->get(get_the_ID());
        if ($document) {
            $data = $document->get_elements_data();
            return $this->search_widget_in_elements($data, 'rm-profile-picture-widget');
        }
    }
    
    // Method 2: Always load if user is logged in (simpler approach)
    return is_user_logged_in();
}

/**
 * Recursively search for widget in Elementor elements
 * 
 * @param array $elements
 * @param string $widget_name
 * @return bool
 */
private function search_widget_in_elements($elements, $widget_name) {
    foreach ($elements as $element) {
        if (isset($element['widgetType']) && $element['widgetType'] === $widget_name) {
            return true;
        }
        
        if (!empty($element['elements'])) {
            if ($this->search_widget_in_elements($element['elements'], $widget_name)) {
                return true;
            }
        }
    }
    
    return false;
}
```

### Hook Registration

```php
// In the class constructor or init method
add_action('wp_enqueue_scripts', [$this, 'enqueue_profile_picture_assets']);
```

### Complete Example in Profile Picture Handler

```php
class RM_Profile_Picture_Handler {
    
    private function __construct() {
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_profile_picture_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_rm_upload_profile_picture', [$this, 'ajax_upload_profile_picture']);
        add_action('wp_ajax_rm_get_profile_picture', [$this, 'ajax_get_profile_picture']);
        add_action('wp_ajax_rm_delete_profile_picture', [$this, 'ajax_delete_profile_picture']);
    }
    
    public function enqueue_profile_picture_assets() {
        if (!is_user_logged_in()) {
            return;
        }
        
        wp_enqueue_style(
            'rm-profile-picture-widget',
            RM_PANEL_PLUGIN_URL . 'assets/css/profile-picture-widget.css',
            [],
            RM_PANEL_VERSION
        );
        
        wp_enqueue_script(
            'rm-profile-picture-widget',
            RM_PANEL_PLUGIN_URL . 'assets/js/profile-picture-widget.js',
            ['jquery'],
            RM_PANEL_VERSION,
            true
        );
        
        wp_localize_script(
            'rm-profile-picture-widget',
            'rmProfilePicture',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rm_profile_picture_nonce'),
                'maxFileSize' => 2097152,
                'allowedTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
            ]
        );
    }
}
```

### Admin Bar Settings Enqueue

```php
/**
 * Enqueue admin bar settings page assets
 */
public function enqueue_admin_bar_settings_assets($hook) {
    // Only load on our settings page
    if ($hook !== 'settings_page_rm-panel-admin-bar') {
        return;
    }
    
    wp_enqueue_style(
        'rm-admin-bar-settings',
        RM_PANEL_PLUGIN_URL . 'assets/css/admin-bar-settings.css',
        [],
        RM_PANEL_VERSION
    );
    
    wp_enqueue_script(
        'rm-admin-bar-settings',
        RM_PANEL_PLUGIN_URL . 'assets/js/admin-bar-settings.js',
        ['jquery'],
        RM_PANEL_VERSION,
        true
    );
}

// Register hook
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_bar_settings_assets']);
```

### Common Mistakes to Avoid

**❌ WRONG - Missing jQuery dependency:**
```php
wp_enqueue_script(
    'rm-profile-picture-widget',
    RM_PANEL_PLUGIN_URL . 'assets/js/profile-picture-widget.js',
    [], // WRONG: jQuery dependency missing
    RM_PANEL_VERSION,
    true
);
```

**✅ CORRECT - With jQuery dependency:**
```php
wp_enqueue_script(
    'rm-profile-picture-widget',
    RM_PANEL_PLUGIN_URL . 'assets/js/profile-picture-widget.js',
    ['jquery'], // CORRECT: jQuery dependency included
    RM_PANEL_VERSION,
    true
);
```

**❌ WRONG - Missing localization:**
```php
wp_enqueue_script('rm-profile-picture-widget', ...);
// Script won't have access to AJAX URL and nonce
```

**✅ CORRECT - With localization:**
```php
wp_enqueue_script('rm-profile-picture-widget', ...);
wp_localize_script('rm-profile-picture-widget', 'rmProfilePicture', [...]);
```

---

## 🔐 AJAX Endpoints

### 1. Survey Completion Callback

**Endpoint:** `/wp-json/rm-panel/v1/survey-complete`

**Method:** POST or GET

**Parameters:**
- `survey_id` (required) - Survey identifier
- `user_id` (required) - WordPress user ID
- `nonce` (required) - Security nonce
- `external_params` (optional) - JSON encoded external survey data

**Response:**
```json
{
    "success": true,
    "message": "Survey completion recorded",
    "completion_id": 123
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Invalid user ID"
}
```

---

### 2. User Registration

**Endpoint:** `/wp-json/rm-panel/v1/register`

**Method:** POST

**Parameters:**
- `email` (required) - User email address
- `username` (required) - Desired username
- `password` (required) - User password
- `first_name` (optional) - First name
- `last_name` (optional) - Last name
- `country` (optional) - Country code (auto-detected if not provided)

**Response:**
```json
{
    "success": true,
    "user_id": 456,
    "message": "Registration successful"
}
```

---

### 3. Profile Picture Upload

**Action:** `rm_upload_profile_picture`

**Method:** POST (AJAX)

**Required:** User must be logged in

**Parameters:**
- `action` = 'rm_upload_profile_picture'
- `nonce` - wp_nonce
- `profile_picture` - File upload (image)

**Response (Success):**
```json
{
    "success": true,
    "data": {
        "attachment_id": 789,
        "url": "https://site.com/wp-content/uploads/2025/10/profile-pic.jpg",
        "thumbnail": "https://site.com/wp-content/uploads/2025/10/profile-pic-150x150.jpg"
    }
}
```

**Response (Error):**
```json
{
    "success": false,
    "data": "Invalid file type. Only JPG, PNG, and GIF are allowed."
}
```

---

### 4. Get Profile Picture

**Action:** `rm_get_profile_picture`

**Method:** POST (AJAX)

**Required:** User must be logged in

**Parameters:**
- `action` = 'rm_get_profile_picture'
- `nonce` - wp_nonce

**Response:**
```json
{
    "success": true,
    "data": {
        "attachment_id": 789,
        "url": "https://site.com/wp-content/uploads/2025/10/profile-pic.jpg",
        "thumbnail": "https://site.com/wp-content/uploads/2025/10/profile-pic-150x150.jpg",
        "medium": "https://site.com/wp-content/uploads/2025/10/profile-pic-300x300.jpg"
    }
}
```

---

### 5. Delete Profile Picture

**Action:** `rm_delete_profile_picture`

**Method:** POST (AJAX)

**Required:** User must be logged in

**Parameters:**
- `action` = 'rm_delete_profile_picture'
- `nonce` - wp_nonce

**Response:**
```json
{
    "success": true,
    "data": "Profile picture deleted successfully"
}
```

---

## 💾 Database Schema

### Survey Completions Table

**Table Name:** `{prefix}_rm_survey_completions`

```sql
CREATE TABLE {prefix}_rm_survey_completions (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    survey_id varchar(100) NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    completed_at datetime DEFAULT CURRENT_TIMESTAMP,
    ip_address varchar(45) DEFAULT NULL,
    external_params longtext DEFAULT NULL,
    status varchar(20) DEFAULT 'completed',
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY survey_id (survey_id),
    KEY completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns:**
- `id` - Auto-increment primary key
- `survey_id` - External survey identifier
- `user_id` - WordPress user ID (foreign key to wp_users)
- `completed_at` - Timestamp of completion
- `ip_address` - User's IP address at time of completion
- `external_params` - JSON encoded parameters from external survey platform
- `status` - Completion status (completed, pending, failed)

---

### Profile Picture Upload History Table

**Table Name:** `{prefix}_rm_profile_picture_history`

```sql
CREATE TABLE {prefix}_rm_profile_picture_history (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    attachment_id bigint(20) UNSIGNED DEFAULT NULL,
    action varchar(20) NOT NULL,
    uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
    ip_address varchar(45) DEFAULT NULL,
    file_name varchar(255) DEFAULT NULL,
    file_size int(11) DEFAULT NULL,
    file_type varchar(50) DEFAULT NULL,
    success tinyint(1) DEFAULT 1,
    error_message text DEFAULT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns:**
- `id` - Auto-increment primary key
- `user_id` - WordPress user ID
- `attachment_id` - WordPress attachment ID (if successful)
- `action` - Action type (upload, delete, replace)
- `uploaded_at` - Timestamp
- `ip_address` - User's IP
- `file_name` - Original filename
- `file_size` - File size in bytes
- `file_type` - MIME type
- `success` - 1 for success, 0 for failure
- `error_message` - Error details if failed

---

### User Meta Keys

**Profile Picture:**
- `rm_profile_picture_id` - Stores attachment ID of current profile picture

**Registration:**
- `rm_registration_date` - Date of registration via RM Panel
- `rm_registration_country` - Country detected during registration
- `rm_registration_ip` - IP address at registration

**Survey Tracking:**
- `rm_survey_count` - Total number of surveys completed
- `rm_last_survey_date` - Date of most recent survey completion

---

## ⚙️ Configuration

### Plugin Constants

**File:** `includes/class-rm-panel-constants.php`

```php
// Plugin version
define('RM_PANEL_VERSION', '1.0.4.1');

// Plugin paths
define('RM_PANEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RM_PANEL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Database table names
define('RM_PANEL_TABLE_SURVEYS', $wpdb->prefix . 'rm_survey_completions');
define('RM_PANEL_TABLE_PROFILE_HISTORY', $wpdb->prefix . 'rm_profile_picture_history');

// API settings
define('RM_PANEL_IPSTACK_API_KEY', get_option('rm_panel_ipstack_key', ''));

// File upload limits
define('RM_PANEL_MAX_UPLOAD_SIZE', 2097152); // 2MB
define('RM_PANEL_ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
```

### Settings Options

**Option Name:** `rm_panel_settings`

**Structure:**
```php
[
    'ipstack_api_key' => 'your_api_key_here',
    'enable_survey_tracking' => true,
    'enable_registration' => true,
    'enable_fluentcrm' => true,
    'default_user_role' => 'subscriber',
    'welcome_email' => true,
    'admin_notifications' => true,
    'max_upload_size' => 2097152,
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif']
]
```

### Admin Bar Settings Option

**Option Name:** `rm_panel_admin_bar_settings`

**Structure:**
```php
[
    'administrator' => '1',  // Show admin bar
    'editor' => '0',         // Hide admin bar
    'author' => '0',         // Hide admin bar
    'contributor' => '0',    // Hide admin bar
    'subscriber' => '0'      // Hide admin bar
]
```

**Defaults:**
- Administrators: Enabled (1)
- All other roles: Disabled (0)

---

## 🧪 Testing Checklist

### Survey Tracking

- [ ] Callback URL receives parameters correctly
- [ ] User ID validation works
- [ ] Survey ID validation works
- [ ] Nonce verification prevents unauthorized access
- [ ] IP address is logged correctly
- [ ] External parameters are stored as JSON
- [ ] Duplicate completions are handled
- [ ] Database entry is created successfully
- [ ] Response JSON format is correct
- [ ] Error handling works for invalid data

### Registration

- [ ] Email validation works
- [ ] Username validation works
- [ ] Password strength requirements are enforced
- [ ] Country auto-detection via IPStack works
- [ ] Manual country selection overrides auto-detection
- [ ] WordPress user is created successfully
- [ ] User meta is stored correctly
- [ ] FluentCRM contact is created
- [ ] Appropriate tags are applied
- [ ] Welcome email is sent
- [ ] Error messages are clear and helpful

### Elementor Widgets

#### Login Widget
- [ ] Widget appears in Elementor editor
- [ ] Form displays correctly on frontend
- [ ] AJAX login works without page reload
- [ ] Error messages display properly
- [ ] Success redirect works
- [ ] Remember me checkbox functions
- [ ] Forgot password link works
- [ ] CSS styling is correct
- [ ] Mobile responsive design works

#### Survey Listing Widget
- [ ] Widget appears in Elementor editor
- [ ] Surveys load for logged-in user
- [ ] Completed surveys show correct status
- [ ] Available surveys are highlighted
- [ ] Survey links work correctly
- [ ] Accordion functionality works
- [ ] Empty state displays when no surveys
- [ ] Loading state displays during fetch
- [ ] Mobile responsive design works

#### Profile Picture Widget
- [ ] Widget appears in Elementor editor
- [ ] Widget displays on frontend
- [ ] Current profile picture shows correctly
- [ ] Default avatar shows if no picture
- [ ] User name displays correctly
- [ ] User email displays correctly
- [ ] Hover effect works on avatar

### Modal Functionality
- [ ] Modal opens on avatar click
- [ ] Modal closes on X button click
- [ ] Modal closes on outside click
- [ ] Modal close on ESC key works
- [ ] Modal animations smooth
- [ ] Modal is centered on screen
- [ ] Modal scrollable on small screens

### File Selection
- [ ] File input button works
- [ ] File type validation works
- [ ] File size validation works (2MB max)
- [ ] Error messages display for invalid files
- [ ] Only image files accepted

### Drag & Drop
- [ ] Drag over effect shows
- [ ] Drop zone highlights
- [ ] File drop works
- [ ] Multiple files rejected (only one)
- [ ] Drag leave removes highlight

### Upload Process
- [ ] Preview shows before upload
- [ ] Upload button enables after selection
- [ ] Upload button disables during upload
- [ ] Upload progress indication works
- [ ] Success message shows on completion
- [ ] Avatar updates after successful upload
- [ ] Modal closes after success (2s delay)
- [ ] Error messages display on failure

### Image Storage
- [ ] Image uploaded to media library
- [ ] Attachment ID stored in user meta
- [ ] Old image deleted on new upload
- [ ] Image accessible via direct URL
- [ ] Multiple sizes generated (thumbnail, medium)

### AJAX & Security
- [ ] Nonce verification works
- [ ] User authentication required
- [ ] Non-logged-in users get error
- [ ] Invalid nonce rejected
- [ ] AJAX endpoint responds correctly
- [ ] JSON response format correct

### Responsive Design
- [ ] Widget responsive on mobile
- [ ] Modal responsive on mobile
- [ ] Buttons stack on small screens
- [ ] Text readable on all sizes
- [ ] Touch events work on mobile

### Browser Compatibility
- [ ] Works in Chrome
- [ ] Works in Firefox
- [ ] Works in Safari
- [ ] Works in Edge
- [ ] Works in mobile browsers

### Integration Testing
- [ ] Works with Elementor Pro
- [ ] Works with WooCommerce (if applicable)
- [ ] Works with membership plugins
- [ ] No conflicts with other plugins
- [ ] Works in Elementor preview mode

### Error Handling
- [ ] Network errors handled gracefully
- [ ] Server errors show message
- [ ] File validation errors clear
- [ ] Upload failures don't break widget
- [ ] Console errors logged (debug mode)

### Performance
- [ ] Page load time acceptable
- [ ] AJAX requests fast (<2s)
- [ ] No memory leaks
- [ ] Images optimized on upload
- [ ] CSS/JS minified (production)

### Admin Bar Manager

#### Basic Functionality
- [ ] Admin bar shows for enabled roles
- [ ] Admin bar hides for disabled roles
- [ ] Settings save correctly
- [ ] Settings persist after page reload
- [ ] Default settings work correctly
- [ ] Reset to defaults works

#### Role Testing
- [ ] Administrator role can see/hide based on settings
- [ ] Editor role can see/hide based on settings
- [ ] Author role can see/hide based on settings
- [ ] Contributor role can see/hide based on settings
- [ ] Subscriber role can see/hide based on settings
- [ ] Custom roles respect settings

#### Settings Page
- [ ] Settings page accessible
- [ ] Toggle switches work
- [ ] Save button works
- [ ] Reset button works
- [ ] Success messages display
- [ ] Changes reflect immediately
- [ ] Nonce verification works

#### CSS Injection
- [ ] Admin bar CSS hides when disabled
- [ ] Top margin removed when hidden
- [ ] No visual artifacts remain
- [ ] Elementor editor not affected
- [ ] Frontend and admin both work

#### Edge Cases
- [ ] Works with multiple user roles
- [ ] Works when user has no roles
- [ ] Works for non-logged-in users
- [ ] Works with custom role plugins
- [ ] Settings page permissions correct

---

## 🐛 Common Issues & Solutions

### Issue 1: Callback URL Not Working

**Symptoms:** Survey completions not being recorded

**Solutions:**
1. Check permalink structure (must not be "Plain")
2. Verify REST API is enabled
3. Check nonce is being passed correctly
4. Verify user_id exists in WordPress
5. Check IP whitelist if configured
6. Review error logs in wp-content/debug.log

**Debug:**
```php
// Enable debug logging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Check REST API
curl https://yoursite.com/wp-json/rm-panel/v1/survey-complete
```

---

### Issue 2: Country Detection Not Working

**Symptoms:** Users registered with wrong or no country

**Solutions:**
1. Verify IPStack API key is configured
2. Check API key has sufficient credits
3. Verify IPStack API endpoint is accessible
4. Check for firewall blocking outgoing requests
5. Review IPStack response in logs

**Debug:**
```php
// Test IPStack directly
$ip = '8.8.8.8';
$api_key = get_option('rm_panel_ipstack_key');
$url = "http://api.ipstack.com/{$ip}?access_key={$api_key}";
$response = wp_remote_get($url);
error_log(print_r($response, true));
```

---

### Issue 3: Elementor Widgets Not Appearing

**Symptoms:** Custom widgets don't show in Elementor editor

**Solutions:**
1. Verify Elementor is installed and active
2. Clear Elementor cache (Tools → Regenerate CSS)
3. Check widget class extends \Elementor\Widget_Base
4. Verify widget is registered on correct hook
5. Check for PHP errors in debug log

**Debug:**
```php
// Verify Elementor is loaded
if (!did_action('elementor/loaded')) {
    error_log('Elementor not loaded');
}

// Check if widgets registered
add_action('elementor/widgets/register', function($widgets_manager) {
    error_log('Elementor widgets register hook fired');
    error_log(print_r($widgets_manager->get_widget_types(), true));
});
```

---

### Issue 4: AJAX Requests Failing

**Symptoms:** Form submissions or AJAX actions return errors

**Solutions:**
1. Verify nonce is created and passed correctly
2. Check AJAX URL is correct (admin-ajax.php)
3. Verify user has required permissions
4. Check for JavaScript errors in browser console
5. Review server error logs

**Debug:**
```javascript
// Console log AJAX request
console.log('AJAX URL:', rmProfilePicture.ajaxurl);
console.log('Nonce:', rmProfilePicture.nonce);

// Check response
$.ajax({
    url: rmProfilePicture.ajaxurl,
    type: 'POST',
    data: {...},
    success: function(response) {
        console.log('Success:', response);
    },
    error: function(xhr, status, error) {
        console.log('Error:', xhr.responseText);
    }
});
```

---

### Issue 5: FluentCRM Integration Not Working

**Symptoms:** Contacts not created or tags not applied

**Solutions:**
1. Verify FluentCRM is installed and active
2. Check API settings in FluentCRM
3. Verify contact email is unique
4. Check for required fields in form
5. Review FluentCRM logs

**Debug:**
```php
// Check if FluentCRM active
if (!function_exists('FluentCrm')) {
    error_log('FluentCRM not active');
}

// Test contact creation
$contact_data = [
    'email' => 'test@example.com',
    'first_name' => 'Test',
    'last_name' => 'User'
];

$contact = FluentCrm('contacts')->createOrUpdate($contact_data);
error_log('Contact created: ' . $contact->id);
```

---

### Issue 6: CSS Not Loading

**Symptoms:** Widgets appear unstyled

**Solutions:**
1. Check file paths are correct
2. Verify CSS file exists
3. Clear browser cache
4. Check for CSS enqueue hook
5. Verify file permissions

**Debug:**
```php
// Verify CSS enqueued
add_action('wp_enqueue_scripts', function() {
    global $wp_styles;
    error_log('Enqueued styles: ' . print_r($wp_styles->queue, true));
});

// Check file exists
$css_file = RM_PANEL_PLUGIN_DIR . 'assets/css/profile-picture-widget.css';
if (!file_exists($css_file)) {
    error_log('CSS file not found: ' . $css_file);
}
```

---

### Issue 7: JavaScript Errors

**Symptoms:** Interactive features not working

**Solutions:**
1. Check browser console for errors
2. Verify jQuery is loaded
3. Check script dependencies
4. Verify localized variables exist
5. Check for conflicting scripts

**Debug:**
```javascript
// Check if jQuery loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery not loaded');
}

// Check localized variables
if (typeof rmProfilePicture === 'undefined') {
    console.error('rmProfilePicture not defined');
} else {
    console.log('rmProfilePicture:', rmProfilePicture);
}
```

---

### Issue 8: Database Tables Not Created

**Symptoms:** Plugin activation errors or missing tables

**Solutions:**
1. Run activation hook manually
2. Check database user permissions
3. Verify table prefix is correct
4. Check for MySQL errors
5. Review WordPress debug log

**Debug:**
```php
// Check if table exists
global $wpdb;
$table_name = $wpdb->prefix . 'rm_survey_completions';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists !== $table_name) {
    error_log('Table does not exist: ' . $table_name);
    // Run activation function
    rm_panel_activation();
}
```

---

### Issue 9: Nonce Verification Failing

**Symptoms:** AJAX requests return "Nonce verification failed"

**Solutions:**
1. Check nonce is created with correct action
2. Verify nonce is passed in request
3. Check for caching issues
4. Verify user is logged in
5. Check nonce lifetime (default 24 hours)

**Debug:**
```php
// Verify nonce
$nonce = $_POST['nonce'] ?? '';
$action = 'rm_profile_picture_nonce';

if (!wp_verify_nonce($nonce, $action)) {
    error_log('Nonce verification failed');
    error_log('Received nonce: ' . $nonce);
    error_log('Expected action: ' . $action);
}

// Check nonce creation
$created_nonce = wp_create_nonce($action);
error_log('Created nonce: ' . $created_nonce);
```

---

### Issue 10: User Meta Not Saving

**Symptoms:** User data not persisting

**Solutions:**
1. Verify user ID is correct
2. Check for database errors
3. Verify user exists
4. Check meta key naming
5. Review WordPress debug log

**Debug:**
```php
// Test meta save
$user_id = get_current_user_id();
$result = update_user_meta($user_id, 'rm_profile_picture_id', 123);

if ($result === false) {
    error_log('Failed to update user meta for user: ' . $user_id);
} else {
    error_log('User meta updated successfully');
}

// Verify saved
$saved_value = get_user_meta($user_id, 'rm_profile_picture_id', true);
error_log('Saved value: ' . $saved_value);
```

---

### Issue 11: REST API 404 Errors

**Symptoms:** REST API endpoints return 404

**Solutions:**
1. Flush permalinks (Settings → Permalinks → Save)
2. Check .htaccess file is writable
3. Verify REST API is not disabled
4. Check for conflicting plugins
5. Review server configuration

**Debug:**
```php
// Check REST API status
$rest_url = rest_url('rm-panel/v1/survey-complete');
error_log('REST URL: ' . $rest_url);

// Test REST API
$response = wp_remote_get($rest_url);
if (is_wp_error($response)) {
    error_log('REST API error: ' . $response->get_error_message());
}
```

---

### Issue 12: Permission Errors

**Symptoms:** Users get "You don't have permission" errors

**Solutions:**
1. Check user capabilities
2. Verify role has required permissions
3. Check for capability filters
4. Verify user is logged in
5. Review permission checks in code

**Debug:**
```php
// Check user capabilities
$user = wp_get_current_user();
error_log('User roles: ' . print_r($user->roles, true));
error_log('User capabilities: ' . print_r($user->allcaps, true));

// Check specific capability
if (!current_user_can('upload_files')) {
    error_log('User cannot upload files');
}
```

---

### Issue 13: File Upload Errors

**Symptoms:** Profile picture upload fails

**Solutions:**
1. Check file size (must be under 2MB)
2. Verify file type is allowed
3. Check server upload limits (php.ini)
4. Verify uploads directory is writable
5. Check for PHP errors

**Debug:**
```php
// Check upload limits
error_log('Max upload size: ' . ini_get('upload_max_filesize'));
error_log('Post max size: ' . ini_get('post_max_size'));

// Check uploads directory
$upload_dir = wp_upload_dir();
error_log('Upload path: ' . $upload_dir['path']);
error_log('Upload URL: ' . $upload_dir['url']);
error_log('Is writable: ' . (is_writable($upload_dir['path']) ? 'Yes' : 'No'));
```

---

### Issue 14: Widget Not Updating After Changes

**Symptoms:** Changes to widget don't appear

**Solutions:**
1. Clear Elementor cache
2. Clear browser cache
3. Regenerate CSS files
4. Check for caching plugins
5. Verify file changes were saved

**Debug:**
```php
// Clear Elementor cache programmatically
if (class_exists('\Elementor\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
    error_log('Elementor cache cleared');
}
```

---

### Issue 15: Login Widget Not Redirecting

**Symptoms:** Login successful but no redirect

**Solutions:**
1. Check redirect URL in widget settings
2. Verify URL is absolute (includes http://)
3. Check for JavaScript errors
4. Verify AJAX response includes redirect URL
5. Check for conflicting redirects

**Debug:**
```javascript
// Log AJAX response
$.ajax({
    // ... login AJAX request
    success: function(response) {
        console.log('Login response:', response);
        if (response.data.redirect_url) {
            console.log('Redirecting to:', response.data.redirect_url);
            window.location.href = response.data.redirect_url;
        }
    }
});
```

---

### Issue 16: Survey Completions Not Showing

**Symptoms:** Completed surveys don't display in widget

**Solutions:**
1. Verify database entries exist
2. Check user ID matches
3. Review SQL query
4. Check for query filters
5. Verify survey_id format

**Debug:**
```php
// Check database entries
global $wpdb;
$table = $wpdb->prefix . 'rm_survey_completions';
$user_id = get_current_user_id();

$results = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id)
);

error_log('Survey completions: ' . print_r($results, true));
```

---

### Issue 17: IPStack API Errors

**Symptoms:** Country detection returns errors

**Solutions:**
1. Verify API key is valid
2. Check API credits remaining
3. Verify IP address format
4. Check for rate limiting
5. Review IPStack response

**Debug:**
```php
// Detailed IPStack debugging
$ip = $_SERVER['REMOTE_ADDR'];
$api_key = get_option('rm_panel_ipstack_key');
$url = "http://api.ipstack.com/{$ip}?access_key={$api_key}";

$response = wp_remote_get($url, ['timeout' => 10]);

if (is_wp_error($response)) {
    error_log('IPStack request error: ' . $response->get_error_message());
} else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    error_log('IPStack response: ' . print_r($data, true));
    
    if (isset($data['error'])) {
        error_log('IPStack API error: ' . $data['error']['info']);
    }
}
```

---

### Issue 18: Memory Limit Errors

**Symptoms:** Plugin causes memory exhaustion

**Solutions:**
1. Increase PHP memory limit
2. Optimize database queries
3. Reduce image upload size limits
4. Check for memory leaks
5. Profile code execution

**Debug:**
```php
// Check memory usage
error_log('Memory limit: ' . ini_get('memory_limit'));
error_log('Current usage: ' . memory_get_usage(true) / 1024 / 1024 . ' MB');
error_log('Peak usage: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB');
```

---

### Issue 19: Conflicting Plugins

**Symptoms:** Plugin doesn't work with certain other plugins

**Solutions:**
1. Deactivate other plugins one by one
2. Check for JavaScript conflicts
3. Review action/filter priorities
4. Check for function name conflicts
5. Use plugin namespace

**Debug:**
```php
// Check for function conflicts
if (function_exists('rm_panel_init')) {
    error_log('Function rm_panel_init already exists');
}

// Check for action conflicts
global $wp_filter;
if (isset($wp_filter['wp_enqueue_scripts'])) {
    error_log('wp_enqueue_scripts hooks: ' . print_r($wp_filter['wp_enqueue_scripts'], true));
}
```

---

### Issue 20: Profile Picture Widget Not Appearing

**Symptoms:** Widget missing from Elementor editor or frontend

**Solutions:**
1. Verify widget file exists in correct location
2. Check widget class extends \Elementor\Widget_Base
3. Verify widget registered on elementor/widgets/register hook
4. Clear Elementor cache (Tools → Regenerate CSS)
5. Check for PHP errors during widget registration
6. Verify Elementor is installed and active

**Debug:**
```php
// Check if widget class exists
if (!class_exists('RM_Panel_Profile_Picture_Widget')) {
    error_log('Profile Picture Widget class not found');
}

// Verify widget registration
add_action('elementor/widgets/register', function($widgets_manager) {
    error_log('Registering Profile Picture Widget');
    try {
        $widgets_manager->register(new RM_Panel_Profile_Picture_Widget());
        error_log('Widget registered successfully');
    } catch (Exception $e) {
        error_log('Widget registration failed: ' . $e->getMessage());
    }
}, 10, 1);
```

---

### Issue 21: Modal Not Opening

**Symptoms:** Clicking avatar doesn't open upload modal

**Solutions:**
1. Check JavaScript file is loaded (check browser Network tab)
2. Verify jQuery is loaded before widget script
3. Check for JavaScript errors in console
4. Verify click event is bound correctly
5. Check CSS z-index conflicts
6. Verify modal HTML exists in DOM

**Debug:**
```javascript
// Check if click handler is bound
jQuery(document).ready(function($) {
    console.log('Profile picture container:', $('.profile-picture-container').length);
    
    $('.profile-picture-container').on('click', function() {
        console.log('Avatar clicked');
        $('.profile-picture-modal').fadeIn(300);
    });
});

// Check modal exists
console.log('Modal exists:', $('.profile-picture-modal').length);
```

---

### Issue 22: File Upload Fails

**Symptoms:** File selected but upload doesn't complete

**Solutions:**
1. Check file size is under 2MB
2. Verify file type is allowed (JPG, PNG, GIF)
3. Check server PHP upload limits
4. Verify AJAX endpoint is correct
5. Check nonce verification
6. Review server error logs
7. Check uploads directory permissions

**Debug:**
```javascript
// Log file details before upload
$('#profile-picture-file-input').on('change', function(e) {
    const file = e.target.files[0];
    console.log('File name:', file.name);
    console.log('File size:', file.size, 'bytes');
    console.log('File type:', file.type);
    console.log('Max allowed:', rmProfilePicture.maxFileSize);
});
```

**Server-side debug:**
```php
// In AJAX handler
error_log('File upload data: ' . print_r($_FILES, true));
error_log('POST data: ' . print_r($_POST, true));

// Check for upload errors
if (isset($_FILES['profile_picture']['error'])) {
    error_log('Upload error code: ' . $_FILES['profile_picture']['error']);
}
```

---

### Issue 23: Image Not Updating After Upload

**Symptoms:** Upload succeeds but avatar doesn't change

**Solutions:**
1. Check AJAX success callback
2. Verify response contains image URL
3. Check for browser caching (add cache-busting query parameter)
4. Verify jQuery selector is correct
5. Check for JavaScript errors

**Debug:**
```javascript
// In upload success handler
success: function(response) {
    console.log('Upload response:', response);
    
    if (response.success) {
        const newImageUrl = response.data.url;
        console.log('New image URL:', newImageUrl);
        
        // Update avatar with cache-busting parameter
        const timestamp = new Date().getTime();
        $('.profile-picture-avatar').attr('src', newImageUrl + '?t=' + timestamp);
        
        console.log('Avatar updated');
    } else {
        console.error('Upload failed:', response.data);
    }
}
```

---

### Issue 24: Drag & Drop Not Working

**Symptoms:** Files can't be dragged onto upload area

**Solutions:**
1. Check dragover event is preventing default
2. Verify drop event handler exists
3. Check for CSS pointer-events issues
4. Verify file from drop event
5. Check browser compatibility

**Debug:**
```javascript
// Enhanced drag & drop debugging
$('.profile-picture-upload-area')
    .on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Drag over');
        $(this).addClass('drag-over');
    })
    .on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Drag leave');
        $(this).removeClass('drag-over');
    })
    .on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Drop event');
        
        const files = e.originalEvent.dataTransfer.files;
        console.log('Dropped files:', files);
        
        if (files.length > 0) {
            console.log('First file:', files[0]);
            // Process file...
        }
    });
```

---

### Issue 25: Old Profile Picture Not Deleted

**Symptoms:** Multiple profile pictures remain in media library

**Solutions:**
1. Verify delete_old_profile_picture() is called
2. Check attachment ID is retrieved correctly
3. Verify wp_delete_attachment() executes
4. Check file permissions
5. Review error logs

**Debug:**
```php
// In upload handler, before saving new image
$old_attachment_id = get_user_meta($user_id, 'rm_profile_picture_id', true);
error_log('Old attachment ID: ' . $old_attachment_id);

if ($old_attachment_id) {
    $deleted = wp_delete_attachment($old_attachment_id, true);
    
    if ($deleted) {
        error_log('Old attachment deleted successfully');
    } else {
        error_log('Failed to delete old attachment');
    }
}

// Verify user meta is updated
$saved = update_user_meta($user_id, 'rm_profile_picture_id', $new_attachment_id);
error_log('User meta updated: ' . ($saved ? 'Yes' : 'No'));
```

---

### Issue 26: Profile Picture Not Showing in Elementor Editor

**Symptoms:** Widget shows in editor but profile picture missing

**Solutions:**
1. Elementor editor uses preview mode
2. Use placeholder image in editor
3. Check if user is logged in
4. Verify get_user_meta() works in preview
5. Add editor-specific handling

**Debug:**
```php
// In widget render method
protected function render() {
    // Check if in editor mode
    if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
        error_log('Rendering in Elementor editor mode');
        // Show placeholder or sample data
        $avatar_url = plugins_url('assets/images/default-avatar.png', __FILE__);
    } else {
        // Normal frontend rendering
        $user_id = get_current_user_id();
        $attachment_id = get_user_meta($user_id, 'rm_profile_picture_id', true);
        
        if ($attachment_id) {
            $avatar_url = wp_get_attachment_url($attachment_id);
        } else {
            $avatar_url = get_avatar_url($user_id);
        }
    }
    
    error_log('Avatar URL: ' . $avatar_url);
    
    // Render widget with avatar_url...
}
```

---

### Issue 27: Admin Bar Shows for Disabled Roles

**Symptoms:** Admin bar appears even when role is set to hide

**Solutions:**
1. Clear site cache (if using caching plugin)
2. Verify settings saved correctly
3. Check for conflicting admin bar plugins
4. Verify user role is correct
5. Check for theme admin bar modifications

**Debug:**
```php
// Add to admin bar manager
public function manage_admin_bar() {
    $settings = $this->get_admin_bar_settings();
    $user = wp_get_current_user();
    
    error_log('Current user ID: ' . $user->ID);
    error_log('User roles: ' . print_r($user->roles, true));
    error_log('Admin bar settings: ' . print_r($settings, true));
    
    $should_show = $this->should_show_admin_bar($settings);
    error_log('Should show admin bar: ' . ($should_show ? 'Yes' : 'No'));
    
    if ($should_show) {
        show_admin_bar(true);
    } else {
        show_admin_bar(false);
    }
}
```

---

### Issue 28: Admin Bar Settings Not Saving

**Symptoms:** Changes to admin bar settings don't persist

**Solutions:**
1. Verify nonce is valid
2. Check form submission method
3. Verify user has admin capabilities
4. Check for database errors
5. Review form processing code

**Debug:**
```php
// In settings save handler
if (isset($_POST['rm_panel_save_admin_bar'])) {
    error_log('Save admin bar settings triggered');
    
    // Check nonce
    if (!check_admin_referer('rm_panel_admin_bar_settings_save')) {
        error_log('Nonce verification failed');
        return;
    }
    
    // Get submitted settings
    $submitted = isset($_POST['admin_bar_roles']) ? $_POST['admin_bar_roles'] : [];
    error_log('Submitted settings: ' . print_r($submitted, true));
    
    // Save
    $result = RM_Panel_Admin_Bar_Manager::save_settings($submitted);
    error_log('Save result: ' . ($result ? 'Success' : 'Failed'));
    
    // Verify saved
    $saved = get_option('rm_panel_admin_bar_settings');
    error_log('Saved settings: ' . print_r($saved, true));
}
```

---

## 🔐 Important Security Notes

### 1. Nonce Verification
**All AJAX requests MUST verify nonces:**
```php
if (!wp_verify_nonce($_POST['nonce'], 'action_name')) {
    wp_send_json_error('Security check failed');
}
```

### 2. User Authentication
**Always check if user is logged in:**
```php
if (!is_user_logged_in()) {
    wp_send_json_error('User not authenticated');
}
```

### 3. Capability Checks
**Verify user permissions:**
```php
if (!current_user_can('upload_files')) {
    wp_send_json_error('Insufficient permissions');
}
```

### 4. Input Sanitization
**Sanitize all user inputs:**
```php
$survey_id = sanitize_text_field($_POST['survey_id']);
$email = sanitize_email($_POST['email']);
$url = esc_url_raw($_POST['url']);
```

### 5. SQL Injection Prevention
**Always use prepared statements:**
```php
$wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id);
```

### 6. XSS Prevention
**Escape output:**
```php
echo esc_html($user_name);
echo esc_attr($field_value);
echo esc_url($link);
```

### 7. IP Logging
**Log IP addresses for audit trail:**
```php
$ip_address = $_SERVER['REMOTE_ADDR'];
// Store in database for security auditing
```

### 8. File Upload Validation
**Strictly validate uploaded files:**
```php
// Check file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    wp_die('Invalid file type');
}

// Check file size
if ($file['size'] > 2097152) { // 2MB
    wp_die('File too large');
}
```

### 9. Database Escaping
**Escape table names:**
```php
$table = esc_sql($wpdb->prefix . 'rm_survey_completions');
```

### 10. Rate Limiting
**Consider implementing rate limits:**
```php
// Check how many uploads in last hour
$recent_uploads = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table 
     WHERE user_id = %d 
     AND uploaded_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
    $user_id
));

if ($recent_uploads > 10) {
    wp_send_json_error('Too many uploads. Please try again later.');
}
```

### 11. External API Security
**Secure external API calls:**
```php
// Use HTTPS for API calls
$url = str_replace('http://', 'https://', $api_url);

// Validate API responses
$response = wp_remote_get($url, [
    'timeout' => 10,
    'sslverify' => true
]);

if (is_wp_error($response)) {
    error_log('API error: ' . $response->get_error_message());
}
```

### 12. Password Handling
**Never log or expose passwords:**
```php
// Good
error_log('User registration attempt: ' . $email);

// Bad - NEVER DO THIS
error_log('User password: ' . $password);
```

### 13. Session Security
**Use WordPress nonces instead of sessions:**
```php
// Create nonce
$nonce = wp_create_nonce('rm_panel_action');

// Verify nonce (valid for 24 hours by default)
wp_verify_nonce($_POST['nonce'], 'rm_panel_action');
```

### 14. Error Messages
**Don't expose sensitive information in error messages:**
```php
// Good
wp_send_json_error('Login failed');

// Bad
wp_send_json_error('User with email test@example.com does not exist');
```

### 15. Database Table Prefixes
**Always use WordPress table prefix:**
```php
global $wpdb;
$table = $wpdb->prefix . 'rm_survey_completions';
```

### 16. File Upload Validation (Profile Picture)
**Critical security checks for image uploads:**
```php
// Validate file type using WordPress function
$file_type = wp_check_filetype_and_ext($_FILES['profile_picture']['tmp_name'], $_FILES['profile_picture']['name']);

if (!$file_type['ext'] || !$file_type['type']) {
    wp_send_json_error('Invalid file type');
}

// Whitelist allowed MIME types
$allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file_type['type'], $allowed_mimes)) {
    wp_send_json_error('Only JPG, PNG, and GIF files allowed');
}

// Check file size (2MB max)
if ($_FILES['profile_picture']['size'] > 2097152) {
    wp_send_json_error('File size exceeds 2MB limit');
}
```

### 17. Attachment Management (Profile Picture)
**Secure attachment handling:**
```php
// Only allow user to delete their own attachments
$attachment_user_id = get_post_field('post_author', $attachment_id);
if ($attachment_user_id != $user_id && !current_user_can('delete_others_posts')) {
    wp_send_json_error('Permission denied');
}

// Force delete (not just trash)
wp_delete_attachment($attachment_id, true);
```

### 18. User Meta Security (Profile Picture)
**Protect user meta from unauthorized access:**
```php
// Only allow users to update their own profile picture
if ($requesting_user_id != $target_user_id && !current_user_can('edit_users')) {
    wp_send_json_error('Cannot update other user profiles');
}

// Validate attachment belongs to user
$attachment_author = get_post_field('post_author', $attachment_id);
if ($attachment_author != $user_id) {
    wp_send_json_error('Invalid attachment');
}
```

### 19. AJAX Nonce (Profile Picture)
**Implement proper nonce verification:**
```php
// In AJAX handler
public function ajax_upload_profile_picture() {
    // Verify nonce
    if (!check_ajax_referer('rm_profile_picture_nonce', 'nonce', false)) {
        wp_send_json_error('Security verification failed');
        return;
    }
    
    // Verify user logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not authenticated');
        return;
    }
    
    // Get current user ID (don't trust user input)
    $user_id = get_current_user_id();
    
    // Continue with upload...
}
```

### 20. User ID Verification (Profile Picture)
**Never trust user-provided user IDs:**
```php
// WRONG - Don't do this
$user_id = $_POST['user_id']; // Attacker could change this

// CORRECT - Always get from session
$user_id = get_current_user_id();
```

### 21. Media Library Security (Profile Picture)
**Control who can access uploaded images:**
```php
// Set proper attachment data
$attachment = [
    'post_mime_type' => $file_type['type'],
    'post_title' => sanitize_file_name($file['name']),
    'post_content' => '',
    'post_status' => 'inherit',
    'post_author' => $user_id // Set current user as author
];

// This prevents other users from accessing/modifying the image
```

### 22. Image Size Limits (Profile Picture)
**Implement multiple validation layers:**
```php
// Server-side PHP limit
if ($_FILES['profile_picture']['size'] > 2097152) {
    wp_send_json_error('File too large');
}

// Client-side JavaScript validation (first line of defense)
if (file.size > rmProfilePicture.maxFileSize) {
    showMessage('File size must be less than 2MB', 'error');
    return false;
}

// WordPress upload limit check
$upload_max = wp_max_upload_size();
if ($file['size'] > $upload_max) {
    wp_send_json_error('File exceeds server upload limit');
}
```

### 23. File Type Whitelist (Profile Picture)
**Explicitly whitelist allowed types:**
```php
// Define whitelist
const ALLOWED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

// Check MIME type
if (!in_array($file_type['type'], self::ALLOWED_TYPES)) {
    wp_send_json_error('Invalid file type');
}

// Check extension
if (!in_array($file_type['ext'], self::ALLOWED_EXTENSIONS)) {
    wp_send_json_error('Invalid file extension');
}

// Reject if either check fails
```

### 24. IP Logging (Profile Picture)
**Log upload attempts for security auditing:**
```php
// Log successful uploads
$this->log_upload_attempt($user_id, true, [
    'attachment_id' => $attachment_id,
    'file_name' => $file['name'],
    'file_size' => $file['size'],
    'ip_address' => $_SERVER['REMOTE_ADDR']
]);

// Log failed attempts
$this->log_upload_attempt($user_id, false, [
    'error' => $error_message,
    'ip_address' => $_SERVER['REMOTE_ADDR']
]);
```

### 25. Upload History (Profile Picture)
**Track all profile picture changes:**
```php
// Store in database table
global $wpdb;
$table = $wpdb->prefix . 'rm_profile_picture_history';

$wpdb->insert($table, [
    'user_id' => $user_id,
    'attachment_id' => $attachment_id,
    'action' => 'upload',
    'uploaded_at' => current_time('mysql'),
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'file_name' => $file['name'],
    'file_size' => $file['size'],
    'success' => 1
]);
```

### 26. Admin Bar Settings Access Control
**Restrict admin bar settings to administrators:**
```php
// In settings page registration
add_options_page(
    'Admin Bar Settings',
    'Admin Bar',
    'manage_options', // Only administrators
    'rm-panel-admin-bar',
    [$this, 'render_settings_page']
);

// In settings save handler
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}
```

### 27. Settings Validation (Admin Bar)
**Validate admin bar settings before saving:**
```php
public static function save_settings($settings) {
    // Only accept valid roles
    $valid_roles = array_keys(self::get_all_roles());
    $validated = [];
    
    foreach ($settings as $role => $value) {
        // Only process valid roles
        if (in_array($role, $valid_roles)) {
            // Only accept '1' or '0'
            $validated[$role] = ($value === '1' || $value === 1) ? '1' : '0';
        }
    }
    
    return update_option('rm_panel_admin_bar_settings', $validated);
}
```

### 28. Cross-Site Request Forgery (Admin Bar)
**Protect settings form with nonce:**
```php
// In form
wp_nonce_field('rm_panel_admin_bar_settings_save');

// In handler
if (!check_admin_referer('rm_panel_admin_bar_settings_save')) {
    wp_die('Security check failed');
}
```

---

## 📊 Performance Optimization

### Database Query Optimization

1. **Use Indexes:**
```sql
-- Add indexes to frequently queried columns
CREATE INDEX idx_user_id ON wp_rm_survey_completions(user_id);
CREATE INDEX idx_survey_id ON wp_rm_survey_completions(survey_id);
CREATE INDEX idx_completed_at ON wp_rm_survey_completions(completed_at);
```

2. **Limit Query Results:**
```php
// Instead of getting all surveys
$surveys = $wpdb->get_results("SELECT * FROM $table WHERE user_id = $user_id");

// Limit to recent surveys
$surveys = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table 
     WHERE user_id = %d 
     ORDER BY completed_at DESC 
     LIMIT 20",
    $user_id
));
```

3. **Use Transients for Caching:**
```php
// Cache country detection results
$transient_key = 'rm_country_' . md5($ip_address);
$country = get_transient($transient_key);

if ($country === false) {
    $country = $this->detect_country_from_api($ip_address);
    set_transient($transient_key, $country, DAY_IN_SECONDS);
}
```

### Asset Loading Optimization

1. **Conditional Loading:**
```php
// Only load CSS/JS on pages that need them
if (is_page('panel-dashboard')) {
    wp_enqueue_style('rm-panel-dashboard');
    wp_enqueue_script('rm-panel-dashboard');
}
```

2. **Minify Assets:**
```bash
# Use minified versions in production
rm-panel-extensions.min.css
rm-panel-extensions.min.js
```

3. **Defer Non-Critical JavaScript:**
```php
wp_enqueue_script(
    'rm-panel-widget',
    RM_PANEL_PLUGIN_URL . 'assets/js/widget.js',
    ['jquery'],
    RM_PANEL_VERSION,
    true // Load in footer
);
```

### Image Optimization

1. **Generate Multiple Sizes:**
```php
// WordPress automatically generates thumbnails
add_image_size('rm_profile_thumbnail', 150, 150, true);
add_image_size('rm_profile_medium', 300, 300, true);
```

2. **Lazy Load Images:**
```html
<img src="placeholder.jpg" 
     data-src="profile-picture.jpg" 
     loading="lazy" 
     alt="Profile Picture">
```

### Profile Picture Upload Optimization

**✅ Checklist for optimal performance:**

1. **Image Compression:**
```php
// Use WordPress image compression
add_filter('jpeg_quality', function() {
    return 85; // Reduce from default 90
});
```

2. **Limit Image Dimensions:**
```php
// Resize large images on upload
function rm_resize_profile_picture($file) {
    $max_width = 800;
    $max_height = 800;
    
    $image = wp_get_image_editor($file['tmp_name']);
    
    if (!is_wp_error($image)) {
        $size = $image->get_size();
        
        if ($size['width'] > $max_width || $size['height'] > $max_height) {
            $image->resize($max_width, $max_height, false);
            $image->save($file['tmp_name']);
        }
    }
    
    return $file;
}
add_filter('wp_handle_upload_prefilter', 'rm_resize_profile_picture');
```

3. **Delete Old Attachments:**
```php
// Always cleanup old profile pictures
private function delete_old_profile_picture($user_id) {
    $old_attachment_id = get_user_meta($user_id, 'rm_profile_picture_id', true);
    
    if ($old_attachment_id) {
        wp_delete_attachment($old_attachment_id, true);
    }
}
```

4. **AJAX Response Optimization:**
```php
// Only return necessary data
wp_send_json_success([
    'attachment_id' => $attachment_id,
    'url' => wp_get_attachment_url($attachment_id),
    'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail')
]);
```

5. **Browser Caching:**
```php
// Add cache headers for images
function rm_profile_picture_cache_headers() {
    header('Cache-Control: public, max-age=2592000'); // 30 days
}
add_action('wp_ajax_rm_get_profile_picture', 'rm_profile_picture_cache_headers', 1);
```

### Admin Bar Performance

1. **Minimal Database Queries:**
```php
// Cache admin bar settings
$settings = wp_cache_get('rm_admin_bar_settings');

if ($settings === false) {
    $settings = get_option('rm_panel_admin_bar_settings', []);
    wp_cache_set('rm_admin_bar_settings', $settings, '', HOUR_IN_SECONDS);
}
```

2. **Early Hook Priority:**
```php
// Run admin bar check early to prevent unnecessary processing
add_action('after_setup_theme', [$this, 'manage_admin_bar'], 1);
```

### General Performance Tips

1. **Use Object Caching:**
```php
// Cache expensive operations
wp_cache_set('key', $data, 'group', $expire_time);
$data = wp_cache_get('key', 'group');
```

2. **Reduce Database Calls:**
```php
// Get multiple user meta values in one call
$meta = get_user_meta($user_id);
```

3. **Profile Code:**
```php
// Measure execution time
$start = microtime(true);
// ... code ...
$end = microtime(true);
error_log('Execution time: ' . ($end - $start) . ' seconds');
```

---

## 📋 Version History

### v1.0.4.1 (October 31, 2025)

**🐛 Bug Fix - Admin Bar Manager**

**Fixed:**
- ✅ Corrected inverted admin bar visibility logic
- ✅ Now explicitly enables admin bar with `show_admin_bar(true)` + filter
- ✅ Now explicitly disables admin bar with `show_admin_bar(false)` + filter
- ✅ Fixed issue where disabled roles were seeing admin bar
- ✅ Fixed issue where enabled roles weren't seeing admin bar

**Technical Details:**
- Updated `manage_admin_bar()` method to use explicit enable/disable
- Added both function call and filter for reliable behavior
- Improved logic in `should_show_admin_bar()` method
- Added comprehensive inline documentation

---

### v1.0.4 (October 30, 2025)

**🎛️ New Feature - Admin Bar Manager**

**Added:**
- ✨ Admin bar visibility control by user role
- ✨ Settings page in WordPress Admin (Settings → RM Panel → Admin Bar)
- ✨ Toggle switches for each user role
- ✨ Default settings (administrators only)
- ✨ Reset to defaults functionality
- ✨ CSS injection to hide admin bar completely
- ✨ Elementor editor compatibility
- ✨ Singleton pattern implementation

**Files Added:**
- `modules/admin-bar/class-admin-bar-manager.php`
- `assets/css/admin-bar-settings.css`
- `assets/js/admin-bar-settings.js`

**Database:**
- New option: `rm_panel_admin_bar_settings`

---

### v1.0.3 (October 29, 2025)

**🎨 New Feature - Profile Picture Widget**

**Added:**
- ✨ Profile picture upload widget for Elementor
- ✨ Drag & drop file upload support
- ✨ AJAX-powered upload (no page reload)
- ✨ Real-time image preview
- ✨ Animated modal interface
- ✨ File type validation (JPG, PNG, GIF)
- ✨ File size validation (2MB max)
- ✨ Automatic old image cleanup
- ✨ User profile picture management
- ✨ Upload history tracking
- ✨ Security: nonce verification, user authentication
- ✨ Responsive design for mobile devices

**Files Added:**
- `modules/profile-picture/class-profile-picture-handler.php`
- `modules/elementor/widgets/class-profile-picture-widget.php`
- `assets/css/profile-picture-widget.css`
- `assets/js/profile-picture-widget.js`

**Database:**
- New table: `wp_rm_profile_picture_history`
- New user meta: `rm_profile_picture_id`

**AJAX Endpoints:**
- `rm_upload_profile_picture` - Upload profile picture
- `rm_get_profile_picture` - Retrieve current picture
- `rm_delete_profile_picture` - Delete picture

**CSS Features:**
- Modal animations (fadeIn, slideDown)
- Hover effects on avatar
- Drag & drop visual feedback
- Responsive breakpoints
- Loading states
- Success/error messages

**JavaScript Features:**
- Event-driven architecture
- File validation
- AJAX upload with progress
- Modal management
- Drag & drop handling
- Image preview

---

### v1.0.2 (October 15, 2025)

**Improvements:**
- Enhanced survey callback validation
- Added external parameter support for survey tracking
- Improved error handling in registration flow
- Optimized database queries
- Added IP logging for audit trails
- Better FluentCRM tag management

**Bug Fixes:**
- Fixed country detection cache issues
- Resolved nonce verification timing problems
- Fixed Elementor widget rendering in preview mode
- Corrected survey listing widget empty states

---

### v1.0.1 (October 1, 2025)

**Added:**
- FluentCRM automatic contact creation
- Survey Listing widget for Elementor
- Login widget with AJAX functionality
- IPStack API integration for country detection
- Survey completion tracking via REST API

---

### v1.0.0 (September 15, 2025)

**Initial Release:**
- Core plugin structure
- Basic survey tracking
- User registration system
- Database schema setup
- Settings page

---

## 🚀 Future Reference Usage

### Quick Code Snippets

**Get Current User's Surveys:**
```php
$surveys = RM_Panel_Survey_Tracker::get_user_surveys(get_current_user_id());
```

**Check Survey Completion:**
```php
$completed = RM_Panel_Survey_Tracker::is_survey_completed($survey_id, $user_id);
```

**Get User Country:**
```php
$country = get_user_meta($user_id, 'billing_country', true);
```

**Create FluentCRM Contact:**
```php
do_action('rm_panel_create_crm_contact', $user_data);
```

**Get Profile Picture:**
```php
$attachment_id = get_user_meta($user_id, 'rm_profile_picture_id', true);
$image_url = wp_get_attachment_url($attachment_id);
```

**Upload Profile Picture Programmatically:**
```php
$handler = RM_Profile_Picture_Handler::get_instance();
// Upload handled through AJAX - see AJAX endpoints section
```

**Delete Profile Picture:**
```php
$handler = RM_Profile_Picture_Handler::get_instance();
$handler->ajax_delete_profile_picture(); // Called via AJAX
```

**Check Admin Bar Visibility for Role:**
```php
$settings = get_option('rm_panel_admin_bar_settings', []);
$can_see = isset($settings['editor']) && $settings['editor'] === '1';
```

**Save Admin Bar Settings:**
```php
$new_settings = [
    'administrator' => '1',
    'editor' => '1',
    'subscriber' => '0'
];
RM_Panel_Admin_Bar_Manager::save_settings($new_settings);
```

**Reset Admin Bar to Defaults:**
```php
RM_Panel_Admin_Bar_Manager::reset_to_defaults();
```

### Reference Documents

- **Profile Picture Widget:** See sections 6, 7, 8, 9 for complete implementation details
- **Admin Bar Manager:** See section 5 and Testing section for setup and configuration
- **AJAX Endpoints:** See section 9 for all available endpoints and response formats
- **Database Schema:** See section 10 for table structures and relationships
- **Security:** See section 14 for all security best practices
- **Testing:** See section 12 for comprehensive testing checklists

---

## 📞 Support & Documentation

### Plugin Settings

Access plugin settings in WordPress Admin:
- **Main Settings:** Settings → RM Panel
- **Admin Bar Settings:** Settings → RM Panel → Admin Bar
- **Survey Settings:** RM Panel → Survey Tracking
- **Registration Settings:** RM Panel → Registration

### Debug Mode

Enable debug logging:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Log Files

Check logs in:
- `/wp-content/debug.log` - WordPress debug log
- `/wp-content/uploads/rm-panel-logs/` - Plugin-specific logs

---

## 🎯 Latest Features

- **Admin bar visibility control** ✨ NEW (v1.0.4)
- **Role-based admin bar management** ✨ NEW (v1.0.4)
- **Profile picture upload widget** (v1.0.3)
- **Drag & drop image upload** (v1.0.3)
- **AJAX-powered upload (no reload)** (v1.0.3)
- **Real-time preview** (v1.0.3)
- **Animated modal interface** (v1.0.3)
- **Upload history tracking** (v1.0.3)
- **Automatic cleanup** (v1.0.3)
- Survey tracking with external parameters
- Country auto-detection via IPStack
- FluentCRM integration
- Custom Elementor widgets
- Comprehensive security measures

---

**End of Documentation**

---

*For technical support or feature requests, contact the development team.*

**Version:** 1.0.4.1  
**Last Updated:** October 31, 2025  
**Next Planned Update:** v1.0.5 (Dashboard widget, bulk survey import)