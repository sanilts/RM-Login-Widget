# RM Panel Extensions - Project Reference Document

## 📋 Project Overview
**Plugin Name:** RM Panel Extensions  
**Version:** 1.0.0  
**Purpose:** Comprehensive WordPress plugin with survey management, Elementor widgets, user tracking, and Fluent Forms integration with real-time validation

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
│   │   │   └── survey-accordion-tabs-widget.php (Tabs + Accordion)
│   │   └── templates/
│   │       └── login-form.php (Login form HTML)
│   ├── referral/
│   │   └── class-referral-system.php (Referral tracking)
│   └── fluent-forms/
│       └── class-fluent-forms-module.php (Fluent Forms integration & real-time validation)
└── assets/
    ├── css/
    │   ├── All stylesheets
    │   └── fluent-forms-validation.css (Real-time validation styles)
    └── js/
        ├── All JavaScript files
        └── fluent-forms-validation.js (Real-time validation: username, email, password)
```

---

## 🔑 Key Classes & Methods

### 1. **RM_Panel_Survey_Module** (class-survey-module.php)
**Purpose:** Registers survey custom post type

**Key Methods:**
- `register_post_type()` - Creates 'rm_survey' CPT
- `register_taxonomies()` - Creates survey_category and survey_user_category
- `add_meta_boxes()` - Survey settings UI
- `save_meta_data()` - Saves survey configuration
- `handle_survey_redirect()` - Redirects to external survey URL with parameters

**Important Meta Keys:**
- `_rm_survey_type` - 'paid' or 'not_paid'
- `_rm_survey_amount` - Payment amount
- `_rm_survey_url` - External survey URL
- `_rm_survey_parameters` - Array of URL parameters
- `_rm_survey_status` - 'draft', 'active', 'paused', 'closed'
- `_rm_survey_duration_type` - 'never_ending' or 'date_range'
- `_rm_survey_start_date` / `_rm_survey_end_date` - Date range

**Default Parameters:**
```php
[
    ['field' => 'survey_id', 'variable' => 'sid'],  // Required
    ['field' => 'user_id', 'variable' => 'uid']     // Required
]
```

---

### 2. **RM_Panel_Survey_Tracking** (class-survey-tracking.php)
**Purpose:** Tracks user survey responses

**Database Table:** `wp_rm_survey_responses`

**Key Methods:**
- `start_survey($user_id, $survey_id)` - Creates tracking record
- `complete_survey($user_id, $survey_id, $completion_status, $response_data)` - Finalizes response
- `get_user_survey_history($user_id, $args)` - Gets user's survey history
- `get_available_surveys($user_id)` - Gets surveys user hasn't completed
- `approve_survey_response($response_id, $admin_notes)` - Approves paid survey
- `reject_survey_response($response_id, $admin_notes)` - Rejects paid survey

**Completion Statuses:**
- `success` - Completed successfully
- `quota_complete` - Survey quota reached
- `disqualified` - User didn't qualify

**Approval Statuses:**
- `pending` - Awaiting admin review (paid surveys)
- `approved` - Payment approved
- `rejected` - Payment rejected
- `auto_approved` - Not paid/auto-approved

**Database Columns:**
```sql
id, user_id, survey_id, status, completion_status, 
start_time, completion_time, response_data, 
ip_address, user_agent, referrer_url, 
approval_status, approved_by, approval_date, 
country, return_time, admin_notes
```

---

### 3. **RM_Survey_Callbacks** (class-survey-callbacks.php)
**Purpose:** Handles callback URLs from external survey platforms

**Callback URL Pattern:**
```
https://site.com/survey-callback/success/?sid=123&uid=456&token=abc123
https://site.com/survey-callback/terminate/?sid=123&uid=456&token=abc123
https://site.com/survey-callback/quotafull/?sid=123&uid=456&token=abc123
```

**Key Methods:**
- `generate_survey_token($survey_id)` - Creates stable survey token
- `verify_survey_token($survey_id, $provided_token)` - Validates token
- `generate_callback_urls($survey_id, $user_id)` - Generates all 3 callback URLs
- `handle_callback_request()` - Processes incoming callbacks

**Token Generation:**
```php
$token = hash('sha256', 'survey_' . $survey_id . '_callback_' . wp_salt('auth'));
```

**Status Mapping:**
```php
'success'    => 'success'
'terminate'  => 'disqualified' 
'quotafull'  => 'quota_complete'
```

---

### 4. **RM_Panel_Elementor_Module** (class-elementor-module.php)
**Purpose:** Integrates Elementor widgets

**Registered Widgets:**
1. `RM_Panel_Login_Widget` - Login form with role-based redirects
2. `RM_Panel_Survey_Listing_Widget` - Survey grid/list view
3. `RM_Panel_Survey_Accordion_Widget` - Expandable survey items
4. `RM_Panel_Survey_Accordion_Tabs_Widget` - Available/Completed tabs

**Key Methods:**
- `register_widgets($widgets_manager)` - Registers all widgets
- `handle_login()` - AJAX login handler
- `load_more_surveys()` - AJAX pagination handler

---

### 5. **RM_Panel_Fluent_Forms_Module** (class-fluent-forms-module.php)
**Purpose:** Integrates Fluent Forms with real-time validation for username, email, and password fields

**Key Methods:**
- `validate_password_confirmation($errors, $formData, $form, $fields)` - Main server-side validation
- `validate_password_strength($password)` - Checks password complexity
- `check_username_availability()` - AJAX handler for real-time username validation
- `check_email_availability()` - AJAX handler for real-time email validation
- `check_password_strength()` - AJAX handler for real-time password strength checking
- `enqueue_validation_scripts()` - Loads real-time validation JS/CSS (only on forms with validation enabled)
- `add_settings_submenu()` - Adds admin settings page under Fluent Forms menu
- `render_settings_page()` - Renders per-form validation settings
- `save_form_settings()` - Saves per-form validation preferences
- `before_submission($insertData, $formData, $form)` - Pre-submission processing
- `create_wordpress_user($formData)` - Creates WordPress user from form data
- `custom_password_messages($message, $formData, $form)` - Custom error messages
- `get_instance()` - Singleton pattern to prevent double initialization

**Required Field Names (Default):**
```php
'username'          // Username field (min 5 chars, alphanumeric + underscore only)
'email'             // Email field (valid format, must be unique)
'password'          // Password field
'confirm_password'  // Confirm password field
```

**Username Validation Rules:**
- ✅ Minimum 5 characters
- ✅ Only letters, numbers, and underscores allowed
- ✅ Must be unique (not already taken)
- ✅ Real-time AJAX validation as user types (500ms debounce)
- ✅ Server-side validation on form submission

**Email Validation Rules:**
- ✅ Valid email format (RFC compliant)
- ✅ Must be unique (not already registered)
- ✅ Real-time AJAX validation as user types (500ms debounce)
- ✅ Server-side validation on form submission

**Password Validation Rules:**
- ✅ Minimum 8 characters
- ✅ At least one uppercase letter
- ✅ At least one lowercase letter
- ✅ At least one number
- ✅ Passwords must match
- ✅ Real-time strength indicator (weak/medium/strong)
- ✅ Real-time password match validation

**User Registration Fields:**
```php
'username'     // WordPress username (required, min 5 chars)
'email'        // User email (required, must be unique)
'password'     // User password (required, min 8 chars with complexity)
'first_name'   // First name (optional)
'last_name'    // Last name (optional)
'gender'       // Gender (optional)
'country'      // Country (optional)
```

**AJAX Endpoints:**
```php
// Username validation
add_action('wp_ajax_check_username_availability', 'check_username_availability');
add_action('wp_ajax_nopriv_check_username_availability', 'check_username_availability');

// Email validation
add_action('wp_ajax_check_email_availability', 'check_email_availability');
add_action('wp_ajax_nopriv_check_email_availability', 'check_email_availability');

// Password strength
add_action('wp_ajax_check_password_strength', 'check_password_strength');
add_action('wp_ajax_nopriv_check_password_strength', 'check_password_strength');
```

**Real-time Validation Responses:**

Username Check:
```javascript
// Success
{
    success: true,
    data: { message: "Username is available!" }
}

// Error
{
    success: false,
    data: { message: "This username is already taken. Please choose another." }
}
```

Email Check:
```javascript
// Success
{
    success: true,
    data: { message: "Email is available!" }
}

// Error
{
    success: false,
    data: { message: "This email is already registered. Please use another email or login." }
}
```

Password Strength:
```javascript
// Strong
{
    success: true,
    data: {
        strength: "strong",
        message: "Strong password!",
        passwords_match: true
    }
}

// Weak/Medium
{
    success: false,
    data: {
        strength: "weak",
        message: "Password needs: At least 8 characters, One uppercase letter",
        requirements: ["At least 8 characters", "One uppercase letter"],
        passwords_match: false
    }
}
```

**Hooks Used:**
- `fluentform/validation_errors` - Main validation filter
- `fluentform/before_insert_submission` - Pre-submission action
- `fluentform/validation_message_password` - Custom messages
- `wp_enqueue_scripts` - Load validation scripts (conditional)
- `wp_ajax_check_username_availability` - AJAX username check
- `wp_ajax_check_email_availability` - AJAX email check
- `wp_ajax_check_password_strength` - AJAX password strength
- `admin_menu` - Add settings submenu
- `admin_init` - Register settings

**Error Messages:**
```php
// Username errors
'Username must be at least 5 characters long.'
'Username can only contain letters, numbers, and underscores.'
'This username is already taken. Please choose another.'

// Email errors
'Please enter a valid email address.'
'This email is already registered. Please use another email or login.'

// Password errors
'Passwords do not match. Please ensure both password fields are identical.'
'Password must be at least 8 characters long.'
'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'
'Password needs: At least 8 characters, One uppercase letter' (dynamic)

// Registration errors
'Username or email already exists.'
```

**Integration Check:**
```php
// Check if Fluent Forms is active
defined('FLUENTFORM') || function_exists('wpFluentForm')
```

**Admin Settings Page:**
- **Location:** Fluent Forms → RM Validation
- **Features:**
  - Enable/disable real-time validation per form
  - List all Fluent Forms
  - Show form IDs
  - Display validation features
  - Field name requirements guide

**Per-Form Settings Storage:**
```php
// Each form has its own option
get_option('rm_fluent_form_validation_' . $form_id, []);

// Structure:
[
    'enable_realtime_validation' => 1  // 1 = enabled, 0 = disabled
]
```

**Frontend Validation Features:**
- 🔄 Real-time username checking (500ms debounce)
- 🔄 Real-time email checking (500ms debounce)
- 🔄 Real-time password strength indicator (300ms debounce)
- 🔄 Real-time password match validation
- 🎨 Visual feedback with color-coded messages
- ⏳ Loading states while checking
- ✅ Success states for valid inputs
- ❌ Error states for invalid inputs
- 📱 Mobile-friendly validation UI
- 🌈 Three-tier password strength (weak/medium/strong)

**Singleton Pattern Implementation:**
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

// Usage in main plugin file:
RM_Panel_Fluent_Forms_Module::get_instance();
```

---

### 6. **Survey Widgets** (widgets/*.php)

#### **Survey Listing Widget**
**Key Features:**
- Grid/List/Cards layouts
- Pagination (numbers, prev/next, load more)
- Category filtering
- Status filtering

**Important:** Always uses current post ID for `survey_id` parameter

#### **Survey Accordion Widget**
**Key Features:**
- Expandable survey items
- First item can auto-expand
- Allow multiple expanded option

#### **Survey Accordion Tabs Widget**
**Key Features:**
- Available Surveys tab
- Completed Surveys tab
- Invite/Referral system integration
- Completion details display

---

## 🔄 Key Workflows

### Survey Completion Flow
```
1. User clicks survey button
   → build_survey_url() adds parameters (sid, uid, token)

2. User redirected to external survey
   → External platform processes survey

3. External platform calls callback URL
   → /survey-callback/success/?sid=X&uid=Y&token=Z

4. handle_callback_request() validates token
   → Calls complete_survey()

5. If paid survey → approval_status = 'pending'
   If not paid → approval_status = 'auto_approved'

6. Redirect to thank you page
   → /survey-thank-you/?survey_id=X&status=success
```

### Fluent Forms Registration Flow with Real-time Validation
```
1. User opens registration form (with validation enabled for this form)
   → Scripts load conditionally based on form settings

2. User types in username field
   → JavaScript validates format (500ms debounce)
   → Shows "Checking username..." with spinner
   → AJAX call to check_username_availability()
   → Server checks: length, format, availability
   → Shows success (green) or error (red) feedback

3. User types in email field
   → JavaScript validates format (500ms debounce)
   → Shows "Checking email..." with spinner
   → AJAX call to check_email_availability()
   → Server checks: format, if already registered
   → Shows success (green) or error (red) feedback

4. User types in password field
   → JavaScript validates strength (300ms debounce)
   → Shows "Checking password strength..." with spinner
   → AJAX call to check_password_strength()
   → Server analyzes: length, uppercase, lowercase, numbers
   → Shows strength indicator (weak/medium/strong)
   → Color-coded feedback (red/yellow/green)

5. User types in confirm password field
   → JavaScript compares with password field
   → Instant feedback (no AJAX needed)
   → Shows "Passwords match!" or "Passwords do not match"

6. User submits form
   → Server-side validation runs (duplicate of all checks)
   → validate_password_confirmation() verifies:
      - Username: min 5 chars, valid format, not taken
      - Email: valid format, not registered
      - Passwords: match, meet complexity requirements
   → If any validation fails → Show errors
   → If all pass → Continue to before_submission()

7. Form submission successful
   → before_submission() is called
   → Optional: create_wordpress_user() creates WP user
   → User account created with hashed password

8. User created successfully
   → User meta saved (first_name, last_name, gender, country)
   → Optional: Welcome email sent
   → User can now login and access surveys
```

### Real-time Validation Flow (Detailed)

#### Username Validation Flow
```
1. User types in username field
   → JavaScript input event listener triggers

2. Client-side validation (instant)
   → Check if empty → Clear feedback
   → Check length < 5 → Show error immediately
   → Check format: /^[a-zA-Z0-9_]+$/ → Show error if invalid
   → If passes → Continue to server check

3. Debounce timer starts (500ms)
   → User stops typing for 500ms
   → Show "Checking username..." with spinner
   → Clear previous feedback

4. AJAX request to server
   → POST to wp-admin/admin-ajax.php
   → Action: check_username_availability
   → Data: {username: 'john_doe', nonce: 'abc123'}
   → Nonce verification for security

5. Server processing
   → sanitize_user() on input
   → Check strlen() < 5 → Return error
   → Check preg_match() format → Return error
   → Check username_exists() → Return error if taken
   → All pass → Return success

6. Client receives response
   → Parse JSON response
   → Update feedback element
   → Add appropriate class (success/error)
   → Show icon (✓ or ✗) + message
   → Apply color styling

7. Visual feedback shown
   → Green background + checkmark = Available
   → Red background + X = Taken/Invalid
   → Smooth animation (slideDown)
```

#### Email Validation Flow
```
1. User types in email field
   → JavaScript input event listener triggers

2. Client-side validation (instant)
   → Check if empty → Clear feedback
   → Regex test: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
   → If invalid format → Show error immediately
   → If valid format → Continue to server check

3. Debounce timer starts (500ms)
   → Show "Checking email..." with spinner

4. AJAX request to server
   → Action: check_email_availability
   → Data: {email: 'user@example.com', nonce: 'xyz789'}

5. Server processing
   → sanitize_email() on input
   → is_email() format check → Return error if invalid
   → email_exists() → Return error if registered
   → All pass → Return success

6. Visual feedback
   → Green = Email available
   → Red = Already registered/Invalid
```

#### Password Strength Validation Flow
```
1. User types in password field
   → JavaScript input event listener triggers

2. Debounce timer (300ms - faster for better UX)
   → Show "Checking password strength..."

3. AJAX request to server
   → Action: check_password_strength
   → Data: {password: 'MyP@ss123', confirm_password: 'MyP@ss123', nonce: 'def456'}

4. Server analyzes password
   → Check strlen() >= 8
   → Check preg_match('/[A-Z]/')
   → Check preg_match('/[a-z]/')
   → Check preg_match('/[0-9]/')
   → Calculate strength score

5. Server returns detailed response
   → strength: 'weak' | 'medium' | 'strong'
   → message: List of missing requirements
   → requirements: Array of what's needed
   → passwords_match: boolean

6. Visual feedback
   → Weak: Red background + list of missing items
   → Medium: Yellow/orange background + partial success
   → Strong: Green background + "Strong password!"
   → Strength bar indicator (optional)

7. Confirm password validation
   → Instant comparison (no AJAX)
   → Updates whenever either field changes
   → Shows match/mismatch immediately
```

### Parameter Building Flow
```php
// In survey-listing-widget.php or survey-accordion-widget.php
private function render_survey_item($settings) {
    $post_id = get_the_ID();  // ⚠️ CRITICAL: Always current post ID
    
    // Get saved parameters
    $parameters = get_post_meta($post_id, '_rm_survey_parameters', true);
    
    // Build URL
    foreach ($parameters as $param) {
        switch ($param['field']) {
            case 'survey_id':
                $value = $post_id;  // ⚠️ ALWAYS current post ID
                break;
            case 'user_id':
                $value = get_current_user_id();
                break;
            // ... other fields
        }
        $query_params[$param['variable']] = $value;
    }
    
    $final_url = add_query_arg($query_params, $base_url);
}
```

---

## 🗄️ Database Tables

### `wp_rm_survey_responses`
Primary table for tracking survey responses

**Schema:**
```sql
CREATE TABLE wp_rm_survey_responses (
    id bigint(20) PRIMARY KEY AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    survey_id bigint(20) NOT NULL,
    status varchar(50) DEFAULT 'started',
    completion_status varchar(50),
    start_time datetime,
    completion_time datetime,
    response_data longtext,
    ip_address varchar(100),
    user_agent text,
    referrer_url text,
    approval_status varchar(20) DEFAULT 'pending',
    approved_by bigint(20),
    approval_date datetime,
    country varchar(100),
    return_time datetime,
    admin_notes text,
    UNIQUE KEY user_survey (user_id, survey_id)
);
```

### `wp_rm_referrals`
Referral system tracking

**Schema:**
```sql
CREATE TABLE wp_rm_referrals (
    id bigint(20) PRIMARY KEY AUTO_INCREMENT,
    referrer_id bigint(20) NOT NULL,
    referred_id bigint(20) NOT NULL,
    survey_id bigint(20),
    referral_code varchar(50),
    status varchar(20) DEFAULT 'pending',
    reward_amount decimal(10,2) DEFAULT 0.00,
    reward_paid tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP
);
```

### `wp_options` (Plugin Settings)
Per-form validation settings stored as individual options

**Schema:**
```sql
-- Per-form validation settings
option_name: rm_fluent_form_validation_{form_id}
option_value: {"enable_realtime_validation":1}
autoload: 'no'

-- Examples:
rm_fluent_form_validation_1 = {"enable_realtime_validation":1}
rm_fluent_form_validation_2 = {"enable_realtime_validation":0}
rm_fluent_form_validation_5 = {"enable_realtime_validation":1}
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Survey ID is Wrong in URL
**Problem:** URL shows `sid=0` or wrong survey ID  
**Cause:** Using `$survey_id` variable instead of `$post_id`  
**Solution:** Always use `$post_id = get_the_ID()` in widget render methods

### Issue 2: Token Mismatch in Callbacks
**Problem:** "Invalid callback token" error  
**Cause:** Token generation differs between generation and verification  
**Solution:** Both use: `hash('sha256', 'survey_' . $survey_id . '_callback_' . wp_salt('auth'))`

### Issue 3: Parameters Not Saving
**Problem:** Parameters reset to defaults  
**Cause:** `save_meta_data()` not preserving non-default parameters  
**Solution:** Check that custom parameters are properly sanitized and saved

### Issue 4: User Sees Completed Surveys
**Problem:** Completed surveys still show in "Available"  
**Cause:** `get_available_surveys()` not filtering properly  
**Solution:** Ensure `get_user_completed_survey_ids()` is working

### Issue 5: Fluent Forms Password Validation Not Working
**Problem:** Form submits even with mismatched passwords  
**Cause:** Field names don't match expected names  
**Solution:** Ensure fields are named exactly `password` and `confirm_password`, or update field names in line 42 of module

### Issue 6: Fluent Forms Module Not Loading
**Problem:** Password validation doesn't run  
**Cause:** Fluent Forms not detected as active  
**Solution:** Check that `defined('FLUENTFORM')` returns true, and integration code is added to main plugin file

### Issue 7: Real-time Validation Not Showing
**Problem:** AJAX validation doesn't appear  
**Cause:** Validation not enabled for the specific form  
**Solution:** 
- Go to Fluent Forms → RM Validation
- Enable validation for the specific form
- Verify scripts are loading (check browser console)
- Ensure field names are correct (username, email, password)

### Issue 8: Validation Shows "Checking..." Forever
**Problem:** AJAX request never completes  
**Cause:** AJAX endpoint not registered or nonce verification failing  
**Solution:**
- Verify all AJAX hooks are registered (both `wp_ajax_` and `wp_ajax_nopriv_`)
- Check that nonces are generated correctly
- Verify AJAX URL in localized script
- Check browser console for JavaScript errors
- Check server error logs for PHP errors

### Issue 9: Validation Bypassed on Form Submit
**Problem:** Invalid data accepted despite client-side validation  
**Cause:** Server-side validation not implemented  
**Solution:** Ensure `validate_password_confirmation()` includes all validation checks

### Issue 10: Double Menu Item in Admin
**Problem:** "RM Validation" appears twice under Fluent Forms menu  
**Cause:** Module being initialized twice (once in main file, once at bottom of class file)  
**Solution:**
```php
// Remove from bottom of class-fluent-forms-module.php:
// if (defined('FLUENTFORM')) {
//     new RM_Panel_Fluent_Forms_Module();
// }

// OR implement singleton pattern:
class RM_Panel_Fluent_Forms_Module {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // ... initialization
    }
}

// In main plugin file:
RM_Panel_Fluent_Forms_Module::get_instance();
```

### Issue 11: Scripts Loading on All Pages
**Problem:** Validation scripts load even on pages without forms  
**Cause:** Script loading not conditional  
**Solution:** Scripts now load only when:
- Page contains a Fluent Forms shortcode
- The specific form has validation enabled
- Check `enqueue_validation_scripts()` method for logic

### Issue 12: CSS Conflicts with Theme
**Problem:** Validation feedback looks broken  
**Cause:** Theme CSS overriding validation styles  
**Solution:**
- Clear browser cache
- Check CSS specificity
- Add `!important` if necessary
- Verify CSS file is loading after theme styles

---

## 🎯 Quick Reference Commands

### Get Survey Parameters
```php
$parameters = get_post_meta($survey_id, '_rm_survey_parameters', true);
```

### Check if Survey is Active
```php
RM_Panel_Survey_Module::is_survey_active($survey_id);
```

### Get User's Survey History
```php
$tracker = new RM_Panel_Survey_Tracking();
$history = $tracker->get_user_survey_history($user_id, ['limit' => 10]);
```

### Generate Callback URLs
```php
$callbacks = new RM_Survey_Callbacks();
$urls = $callbacks->generate_callback_urls($survey_id, $user_id);
// Returns: ['success' => 'url', 'terminate' => 'url', 'quotafull' => 'url']
```

### Fluent Forms - Get/Set Per-Form Validation Settings
```php
// Get settings for form ID 5
$settings = get_option('rm_fluent_form_validation_5', []);
$enabled = isset($settings['enable_realtime_validation']) ? $settings['enable_realtime_validation'] : false;

// Enable validation for form ID 5
update_option('rm_fluent_form_validation_5', [
    'enable_realtime_validation' => 1
]);

// Disable validation for form ID 5
update_option('rm_fluent_form_validation_5', [
    'enable_realtime_validation' => 0
]);
```

### Fluent Forms - Create WordPress User from Form
```php
$fluent_forms = RM_Panel_Fluent_Forms_Module::get_instance();
$user_id = $fluent_forms->create_wordpress_user($formData);
// Returns: int (user ID) or WP_Error on failure
```

### Fluent Forms - Validate Password Strength
```php
$fluent_forms = RM_Panel_Fluent_Forms_Module::get_instance();
$is_valid = $fluent_forms->validate_password_strength($password);
// Returns: bool
```

### Fluent Forms - Check Username Availability (AJAX)
```javascript
// Client-side AJAX call
jQuery.ajax({
    url: rmFluentFormsValidation.ajax_url,
    type: 'POST',
    data: {
        action: 'check_username_availability',
        username: username,
        nonce: rmFluentFormsValidation.username_nonce
    },
    success: function(response) {
        if (response.success) {
            // Username available
        } else {
            // Username taken or invalid
        }
    }
});
```

### Fluent Forms - Check Email Availability (AJAX)
```javascript
jQuery.ajax({
    url: rmFluentFormsValidation.ajax_url,
    type: 'POST',
    data: {
        action: 'check_email_availability',
        email: email,
        nonce: rmFluentFormsValidation.email_nonce
    },
    success: function(response) {
        if (response.success) {
            // Email available
        } else {
            // Email already registered
        }
    }
});
```

### Fluent Forms - Check Password Strength (AJAX)
```javascript
jQuery.ajax({
    url: rmFluentFormsValidation.ajax_url,
    type: 'POST',
    data: {
        action: 'check_password_strength',
        password: password,
        confirm_password: confirmPassword,
        nonce: rmFluentFormsValidation.password_nonce
    },
    success: function(response) {
        // response.data.strength = 'weak' | 'medium' | 'strong'
        // response.data.message = Human readable message
        // response.data.passwords_match = boolean
    }
});
```

### Server-side Validation Checks
```php
// Check if username exists
if (username_exists($username)) {
    // Username is taken
}

// Check if email exists
if (email_exists($email)) {
    // Email is registered
}

// Validate username format
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    // Invalid format
}

// Check minimum length
if (strlen($username) < 5) {
    // Too short
}

// Check email format
if (!is_email($email)) {
    // Invalid email
}

// Check password complexity
if (!preg_match('/[A-Z]/', $password) || 
    !preg_match('/[a-z]/', $password) || 
    !preg_match('/[0-9]/', $password)) {
    // Does not meet requirements
}
```

---

## 📝 Shortcodes

- `[rm_survey_history]` - Display user's survey completion history
- `[rm_survey_tabs]` - Available/Completed surveys with tabs
- `[survey_thank_you]` - Thank you page after survey completion

---

## 🔧 Admin Pages

- **Main Dashboard:** `admin.php?page=rm-panel-extensions`
- **Settings:** `admin.php?page=rm-panel-extensions-settings`
- **Modules:** `admin.php?page=rm-panel-extensions-modules`
- **Responses:** `admin.php?page=rm-panel-survey-responses`
- **Pending Approvals:** `admin.php?page=rm-survey-approvals`
- **Referrals:** `admin.php?page=rm-referrals`
- **Fluent Forms Validation Settings:** `admin.php?page=fluent_forms&route=rm-fluent-forms-validation` (under Fluent Forms menu)

---

## 🔐 Important Security Notes

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

---

## 📚 Dependencies

**Required:**
- WordPress 5.0+
- PHP 7.0+

**Optional:**
- Elementor (for widgets)
- WPML (for translations)
- Fluent Forms (for form integration & real-time validation)

---

## 🔌 Module Loading Order

The plugin loads modules in this order:
1. Survey Module (independent)
2. Survey Tracking (depends on Survey Module)
3. Survey Callbacks (depends on Survey Module)
4. Elementor Module (if Elementor active)
5. Fluent Forms Module (if Fluent Forms active) - **Uses Singleton Pattern**
6. Referral System (depends on Survey Module)

**Integration Code Locations:**

**In `load_modules()` method:**
```php
// Load Fluent Forms module
if (defined('FLUENTFORM') || function_exists('wpFluentForm')) {
    $fluent_forms_file = RM_PANEL_EXT_PLUGIN_DIR . 'modules/fluent-forms/class-fluent-forms-module.php';
    if (file_exists($fluent_forms_file)) {
        require_once $fluent_forms_file;
        $core_modules['fluent-forms'] = 'RM_Panel_Fluent_Forms_Module';
    }
}
```

**In `init_modules()` method:**
```php
// Initialize Fluent Forms module (using singleton)
if (defined('FLUENTFORM') || function_exists('wpFluentForm')) {
    if (isset($this->modules['fluent-forms']) && class_exists($this->modules['fluent-forms'])) {
        RM_Panel_Fluent_Forms_Module::get_instance(); // Singleton pattern
    }
}
```

**⚠️ IMPORTANT: Module Class File Structure**
```php
// At the bottom of class-fluent-forms-module.php
// DO NOT initialize the class here, it's handled by main plugin file

// ❌ WRONG - Causes double menu:
// if (defined('FLUENTFORM')) {
//     new RM_Panel_Fluent_Forms_Module();
// }

// ✅ CORRECT - No initialization at bottom of file
// Module is initialized via singleton in main plugin file
```

---

## 🎨 Fluent Forms Field Configuration

### Registration Form Field Names
Use these exact field names in your Fluent Forms for automatic validation:

| Field Purpose | Field Name | Type | Required | Real-time Validation |
|--------------|------------|------|----------|---------------------|
| First Name | `first_name` | Text | Yes | - |
| Last Name | `last_name` | Text | Yes | - |
| Username | `username` | Text | Yes | ✅ Min 5 chars, format, availability |
| Email | `email` | Email | Yes | ✅ Format, availability |
| Password | `password` | Password | Yes | ✅ Strength indicator |
| Confirm Password | `confirm_password` | Password | Yes | ✅ Match validation |
| Gender | `gender` | Select/Radio | Optional | - |
| Country | `country` | Select | Optional | - |

### Custom Field Names
To use different field names, edit `class-fluent-forms-module.php`:

**For passwords (line ~42):**
```php
$password_field = 'your_custom_password_field';
$confirm_password_field = 'your_custom_confirm_field';
```

**For username (line ~100):**
```php
$username = isset($formData['your_custom_username_field']) ? sanitize_user($formData['your_custom_username_field']) : '';
```

**For email (line ~125):**
```php
$email = isset($formData['your_custom_email_field']) ? sanitize_email($formData['your_custom_email_field']) : '';
```

**Update JavaScript selectors (fluent-forms-validation.js):**
```javascript
// Line ~20
const $usernameField = $('input[name="your_custom_username_field"]');

// Line ~100
const $emailField = $('input[name="your_custom_email_field"]');

// Line ~180
const $passwordField = $('input[name="your_custom_password_field"]');
const $confirmPasswordField = $('input[name="your_custom_confirm_field"]');
```

### Real-time Validation Configuration

**Debounce Timing (JavaScript):**
```javascript
// Username check (line ~50)
usernameCheckTimeout = setTimeout(function() {
    checkUsernameAvailability(username, $feedback);
}, 500); // 500ms delay - adjust as needed

// Email check (line ~130)
emailCheckTimeout = setTimeout(function() {
    checkEmailAvailability(email, $feedback);
}, 500); // 500ms delay

// Password strength (line ~220)
passwordCheckTimeout = setTimeout(function() {
    checkPasswordStrength(password, confirmPassword, $passwordFeedback);
}, 300); // 300ms delay (faster for better UX)
```

**Validation Messages (Localized Script in PHP):**
```php
'messages' => [
    'username_checking' => __('Checking username...', 'rm-panel-extensions'),
    'username_available' => __('Username is available!', 'rm-panel-extensions'),
    'email_checking' => __('Checking email...', 'rm-panel-extensions'),
    'email_available' => __('Email is available!', 'rm-panel-extensions'),
    'password_checking' => __('Checking password strength...', 'rm-panel-extensions'),
    'password_strong' => __('Strong password!', 'rm-panel-extensions'),
    'passwords_match' => __('Passwords match!', 'rm-panel-extensions'),
    'passwords_no_match' => __('Passwords do not match', 'rm-panel-extensions')
]
```

---

## 🚀 Future Reference Usage

**Instead of pasting files, say:**
- "Check the Survey Tracking Flow section"
- "Reference: RM_Survey_Callbacks::generate_survey_token()"
- "See 'Issue 1: Survey ID is Wrong' in Common Issues"
- "Check Fluent Forms Registration Flow with Real-time Validation"
- "Reference: RM_Panel_Fluent_Forms_Module::validate_password_confirmation()"
- "Check Real-time Validation Flow (Detailed)"
- "Reference: RM_Panel_Fluent_Forms_Module::check_username_availability()"
- "Reference: RM_Panel_Fluent_Forms_Module::check_email_availability()"
- "Reference: RM_Panel_Fluent_Forms_Module::check_password_strength()"
- "See 'Issue 10: Double Menu Item' in Common Issues"

---

## 📊 Module Status Reference

Check module status at: **RM Panel Ext** → **Modules**

**Active Modules Indicators:**
- ✅ Survey Module - `class_exists('RM_Panel_Survey_Module')`
- ✅ Survey Tracking - `class_exists('RM_Panel_Survey_Tracking')`
- ✅ Elementor Widgets - `did_action('elementor/loaded')`
- ✅ Fluent Forms - `defined('FLUENTFORM')`
- ✅ WPML Support - `function_exists('icl_object_id')`

**Fluent Forms Validation Settings:**
- ✅ Admin Page - `admin.php?page=fluent_forms&route=rm-fluent-forms-validation`
- ✅ Per-form settings stored in wp_options
- ✅ Conditional script loading based on form settings

---

## 🎬 Frontend Assets Loading

### Fluent Forms Validation Scripts
**Loaded on:** Pages with Fluent Forms shortcodes (conditional loading)

**Loading Logic:**
```php
// Only loads if:
1. Page contains [fluentform id="X"] shortcode
2. Form ID X has validation enabled in settings
3. get_option('rm_fluent_form_validation_X')['enable_realtime_validation'] == 1
```

**CSS File:** `assets/css/fluent-forms-validation.css`
- Validation message styles (checking, success, error, warning)
- Color-coded feedback (blue/green/yellow/red)
- Spinner animations
- Mobile-responsive design
- Dark mode support

**JavaScript File:** `assets/js/fluent-forms-validation.js`
- Real-time username validation (500ms debounce)
- Real-time email validation (500ms debounce)
- Real-time password strength indicator (300ms debounce)
- Password match validation (instant)
- Format and length validation
- Visual feedback handling
- AJAX error handling

**Localized Data:**
```javascript
rmFluentFormsValidation = {
    ajax_url: 'https://site.com/wp-admin/admin-ajax.php',
    username_nonce: 'abc123...',
    email_nonce: 'def456...',
    password_nonce: 'ghi789...',
    messages: {
        username_checking: 'Checking username...',
        username_available: 'Username is available!',
        email_checking: 'Checking email...',
        email_available: 'Email is available!',
        password_checking: 'Checking password strength...',
        password_strong: 'Strong password!',
        passwords_match: 'Passwords match!',
        passwords_no_match: 'Passwords do not match'
    }
}
```

---

## 🧪 Testing Checklist

### Admin Settings Testing
- [ ] New "RM Validation" menu appears under Fluent Forms
- [ ] All Fluent Forms are listed with form IDs
- [ ] Can enable/disable validation per form
- [ ] Settings save correctly (check wp_options table)
- [ ] Page redirects with success message after save
- [ ] No duplicate menu items (singleton working)

### Username Validation Testing
- [ ] Type username < 5 characters → Shows error immediately
- [ ] Type username with special characters → Shows format error
- [ ] Type existing username → Shows "already taken" error (after AJAX)
- [ ] Type valid new username → Shows "available" message (green)
- [ ] Validation respects 500ms debounce (check Network tab)
- [ ] Visual feedback includes icon (✓ or ✗)
- [ ] Loading spinner appears during check
- [ ] Submit form with invalid username → Server blocks submission

### Email Validation Testing
- [ ] Type invalid email format → Shows error immediately
- [ ] Type registered email → Shows "already registered" error (after AJAX)
- [ ] Type valid new email → Shows "available" message (green)
- [ ] Validation respects 500ms debounce
- [ ] Visual feedback includes icon
- [ ] Loading spinner appears during check
- [ ] Submit form with invalid email → Server blocks submission

### Password Validation Testing
- [ ] Type weak password → Shows "weak" indicator (red)
- [ ] Type medium password → Shows "medium" indicator (yellow)
- [ ] Type strong password → Shows "strong" indicator (green)
- [ ] Shows specific requirements missing (uppercase, lowercase, numbers)
- [ ] Updates dynamically as you type
- [ ] Validation respects 300ms debounce
- [ ] Confirm password shows match status instantly
- [ ] Mismatch shown with red error message
- [ ] Submit form with weak password → Server blocks submission

### Conditional Loading Testing
- [ ] Scripts don't load on forms without validation enabled
- [ ] Scripts do load on forms with validation enabled
- [ ] Check browser Network tab for script requests
- [ ] Multiple forms on same page work correctly
- [ ] Settings page shows correct enabled/disabled status

### Server-side Validation Testing
- [ ] Disable JavaScript → Server validation still works
- [ ] Bypass client-side checks via browser console → Server blocks
- [ ] Submit form directly via Postman/curl → Server validates
- [ ] All AJAX checks have server-side equivalents
- [ ] Nonce verification prevents CSRF attacks

### Mobile Responsiveness Testing
- [ ] Validation messages display correctly on mobile
- [ ] Touch input triggers validation
- [ ] Visual feedback is clear on small screens
- [ ] No layout issues with validation messages
- [ ] Performance is acceptable on slower devices

### Browser Compatibility Testing
- [ ] Works in Chrome/Chromium
- [ ] Works in Firefox
- [ ] Works in Safari
- [ ] Works in Edge
- [ ] Check browser console for errors in each

### Error Handling Testing
- [ ] Network error shows appropriate message
- [ ] AJAX timeout handled gracefully
- [ ] Invalid nonce shows error
- [ ] Server error (500) shows user-friendly message
- [ ] Check browser console → No JavaScript errors
- [ ] Check server logs → No PHP errors

---

## 🔧 Customization Options

### Change Debounce Timing

**Username/Email (fluent-forms-validation.js):**
```javascript
// Line ~50 and ~130
setTimeout(function() {
    checkUsernameAvailability(username, $feedback);
}, 500); // Change to 300 for faster, 1000 for slower
```

**Password (fluent-forms-validation.js):**
```javascript
// Line ~220
setTimeout(function() {
    checkPasswordStrength(password, confirmPassword, $passwordFeedback);
}, 300); // Change to 200 for faster, 500 for slower
```

### Change Minimum Username Length

**Server-side (class-fluent-forms-module.php line ~100):**
```php
if (strlen($username) < 5) { // Change 5 to your preferred minimum
    $errors['username'] = [
        __('Username must be at least 5 characters long.', 'rm-panel-extensions')
    ];
}
```

**Client-side (fluent-forms-validation.js line ~35):**
```javascript
if (username.length < 5) { // Change 5 to match server-side
    $feedback.removeClass('checking success')
        .addClass('error')
        .text('Username must be at least 5 characters');
    return;
}
```

### Change Password Requirements

**Server-side (class-fluent-forms-module.php line ~75-95):**
```php
private function validate_password_strength($password) {
    if (strlen($password) < 8) { // Change minimum length
        return false;
    }

    // Remove uppercase requirement (comment out):
    // if (!preg_match('/[A-Z]/', $password)) {
    //     return false;
    // }

    // Add special character requirement (uncomment):
    // if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    //     return false;
    // }

    return true;
}
```

### Customize Validation Messages

**All messages (class-fluent-forms-module.php line ~210-230):**
```php
'messages' => [
    'username_checking' => __('Your custom checking message...', 'rm-panel-extensions'),
    'username_available' => __('Your custom success message!', 'rm-panel-extensions'),
    'email_checking' => __('Verifying email address...', 'rm-panel-extensions'),
    'email_available' => __('This email can be used!', 'rm-panel-extensions'),
    'password_checking' => __('Analyzing password...', 'rm-panel-extensions'),
    'password_strong' => __('Excellent password!', 'rm-panel-extensions'),
    'passwords_match' => __('Perfect match!', 'rm-panel-extensions'),
    'passwords_no_match' => __('Passwords differ', 'rm-panel-extensions')
]
```

### Customize Visual Feedback Colors

**CSS (fluent-forms-validation.css):**
```css
/* Success state - Change green colors */
.rm-validation-feedback.success {
    background-color: #dff6dd; /* Light green background */
    border: 1px solid #acf2a8; /* Green border */
    color: #1a7f37; /* Dark green text */
}

/* Error state - Change red colors */
.rm-validation-feedback.error {
    background-color: #ffebe9; /* Light red background */
    border: 1px solid #f5c7c7; /* Red border */
    color: #cf222e; /* Dark red text */
}

/* Warning state - Change yellow/orange colors */
.rm-validation-feedback.warning {
    background-color: #fff8e1; /* Light yellow background */
    border: 1px solid #ffe082; /* Yellow border */
    color: #f57c00; /* Orange text */
}
```

### Add Password Strength Meter

**CSS (fluent-forms-validation.css) - Already included:**
```css
.rm-password-strength-meter {
    margin-top: 8px;
    height: 4px;
    background-color: #e1e4e8;
    border-radius: 2px;
}

.rm-password-strength-bar.weak {
    width: 33%;
    background-color: #cf222e;
}

.rm-password-strength-bar.medium {
    width: 66%;
    background-color: #f57c00;
}

.rm-password-strength-bar.strong {
    width: 100%;
    background-color: #1a7f37;
}
```

**JavaScript (fluent-forms-validation.js) - Add after feedback element:**
```javascript
// After creating $passwordFeedback, add strength meter
$passwordField.after('<div class="rm-password-strength-meter"><div class="rm-password-strength-bar"></div></div>');
const $strengthBar = $passwordField.next('.rm-password-strength-meter').find('.rm-password-strength-bar');

// Update strength bar in checkPasswordStrength success callback:
$strengthBar.removeClass('weak medium strong').addClass(strength);
```

---

## 📈 Performance Optimization

### AJAX Request Optimization
- ✅ Debounce timers prevent excessive requests
- ✅ Requests only fire after user stops typing
- ✅ Client-side validation reduces server load
- ✅ Nonce caching reduces overhead

### Script Loading Optimization
- ✅ Conditional loading (only on pages with enabled forms)
- ✅ Scripts loaded in footer (doesn't block page render)
- ✅ Single JavaScript file (combined validation logic)
- ✅ Single CSS file (combined styles)

### Database Optimization
- ✅ Indexed columns in survey_responses table
- ✅ Per-form settings (no joins needed)
- ✅ Autoload set to 'no' for form settings

### Caching Considerations
- ⚠️ Form settings are checked on every page load (conditional loading)
- ✅ Consider implementing transient cache for settings
- ✅ Username/email existence checks hit database (unavoidable)

---

**Last Updated:** October 2025  
**Project Version:** 1.0.0  
**Latest Features:** 
- Real-time username validation with 5 character minimum
- Real-time email validation with availability checking
- Real-time password strength indicator (weak/medium/strong)
- Per-form validation settings in admin
- Conditional script loading based on form settings
- Singleton pattern to prevent double initialization
- Three separate AJAX endpoints with nonce security
- Comprehensive visual feedback system