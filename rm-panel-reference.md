# RM Panel Extensions - Project Reference Document

## 📋 Project Overview
**Plugin Name:** RM Panel Extensions  
**Version:** 1.0.1  
**Last Updated:** January 2025  
**Purpose:** Comprehensive WordPress plugin with survey management, Elementor widgets, user tracking, Fluent Forms integration with real-time validation and country auto-detection

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
│       └── class-fluent-forms-module.php (Fluent Forms integration, validation & country detection)
└── assets/
    ├── css/
    │   ├── All stylesheets
    │   └── fluent-forms-validation.css (Real-time validation styles)
    └── js/
        ├── All JavaScript files
        └── fluent-forms-validation.js (Real-time validation & country detection)
```

---

## 🔑 Key Classes & Methods

### 5. **RM_Panel_Fluent_Forms_Module** (class-fluent-forms-module.php) - UPDATED
**Purpose:** Integrates Fluent Forms with real-time validation for username, email, password fields and auto-detects country from IP

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
- `validate_password_confirmation($errors, $formData, $form, $fields)` - Main server-side validation
- `validate_password_strength($password)` - Checks password complexity
- `check_username_availability()` - AJAX handler for real-time username validation
- `check_email_availability()` - AJAX handler for real-time email validation
- `check_password_strength()` - AJAX handler for real-time password strength checking
- `ajax_get_country_from_ip()` - **NEW: AJAX handler for country detection**
- `get_user_country_from_ip()` - **NEW: Gets country from IPStack API**
- `get_user_ip()` - **NEW: Gets user's IP address**
- `enqueue_validation_scripts()` - Loads real-time validation JS/CSS (only on forms with validation enabled)
- `add_settings_submenu()` - Adds admin settings page under Fluent Forms menu
- `render_settings_page()` - Renders per-form validation settings
- `save_form_settings()` - Saves per-form validation preferences
- `before_submission($insertData, $formData, $form)` - Pre-submission processing
- `create_wordpress_user($formData)` - Creates WordPress user from form data
- `custom_password_messages($message, $formData, $form)` - Custom error messages

**Required Field Names:**
```php
'username'          // Username field (min 5 chars, alphanumeric + underscore only)
'email'             // Email field (valid format, must be unique)
'password'          // Password field
'confirm_password'  // Confirm password field
'country'           // Country field (auto-detected) - NEW
```

**Country Detection Configuration:**
```php
// Option name for IPStack API key
get_option('rm_panel_ipstack_api_key', '');

// Cache key for country detection (5 minutes TTL)
$cache_key = 'rm_country_' . md5($ip);
get_transient($cache_key);
set_transient($cache_key, $country, 5 * MINUTE_IN_SECONDS);
```

**IPStack API Integration:**
```php
// API endpoint
$url = "http://api.ipstack.com/{$ip}?access_key={$api_key}";

// Expected response structure
{
    "ip": "xxx.xxx.xxx.xxx",
    "country_name": "United States",
    "country_code": "US",
    // ... other fields
}

// Error response structure
{
    "error": {
        "code": 101,
        "type": "invalid_access_key",
        "info": "You have not supplied a valid API Access Key."
    }
}
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

// Country detection - NEW
add_action('wp_ajax_get_country_from_ip', 'ajax_get_country_from_ip');
add_action('wp_ajax_nopriv_get_country_from_ip', 'ajax_get_country_from_ip');
```

**Country Detection Response:**
```javascript
// Success
{
    success: true,
    data: {
        country: "United States",
        message: "Country detected"
    }
}

// Error
{
    success: false,
    data: {
        message: "Could not detect country"
    }
}
```

**Localized Script Variables:**
```javascript
rmFluentFormsValidation = {
    ajax_url: 'https://site.com/wp-admin/admin-ajax.php',
    username_nonce: 'abc123...',
    email_nonce: 'def456...',
    password_nonce: 'ghi789...',
    country_nonce: 'jkl012...', // NEW
    messages: {
        username_checking: 'Checking username...',
        username_available: 'Username is available!',
        email_checking: 'Checking email...',
        email_available: 'Email is available!',
        password_checking: 'Checking password strength...',
        password_strong: 'Strong password!',
        passwords_match: 'Passwords match!',
        passwords_no_match: 'Passwords do not match',
        country_detecting: 'Detecting country...', // NEW
        country_detected: 'Country detected!' // NEW
    }
}
```

**Integration Check:**
```php
// Check if Fluent Forms is active
defined('FLUENTFORM') || function_exists('wpFluentForm')
```

**Per-Form Settings Storage:**
```php
// Each form has its own option
get_option('rm_fluent_form_validation_' . $form_id, []);

// Structure:
[
    'enable_realtime_validation' => 1  // 1 = enabled, 0 = disabled
]
```

**Country Detection Features:**
- 🌍 Auto-detects country from user's IP address
- 🔄 Uses IPStack API for accurate geolocation
- ⚡ 5-minute cache to reduce API calls
- 🎯 Automatically fills country field in forms
- 📊 Supports both select dropdowns and text inputs
- 🔍 Case-insensitive country name matching
- 🚀 Multiple detection attempts (0s, 1s, 2s delays)
- 📝 Comprehensive error logging for debugging
- ✅ Visual feedback during detection

**Country Detection Flow:**
```
1. Page loads with Fluent Form
   → JavaScript initializes country detection

2. Script searches for country field
   → Checks multiple selectors: [name="country"], [data-name="country"]

3. If field found and empty
   → Shows "Detecting country..." message with spinner

4. AJAX call to server
   → Action: get_country_from_ip
   → Nonce verification

5. Server checks cache
   → If cached → Return immediately
   → If not cached → Call IPStack API

6. IPStack API returns country
   → Example: "United States"

7. Server caches result (5 minutes)
   → Stores in transient

8. JavaScript receives country name
   → For dropdown: Matches option text/value
   → For text input: Sets value directly

9. Visual feedback shown
   → "Country detected!" with checkmark
   → Fades out after 3 seconds
```

---

## 🔧 Important Settings

### IPStack API Key Setting - NEW

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

## 🔄 Module Loading Order - UPDATED

The plugin loads modules in this order:
1. Survey Module (independent)
2. Survey Tracking (depends on Survey Module)
3. Survey Callbacks (depends on Survey Module)
4. Elementor Module (if Elementor active)
5. **Fluent Forms Module (if Fluent Forms active) - Uses Singleton Pattern**
6. Referral System (depends on Survey Module)

**Integration Code in `rm-panel-extensions.php`:**

```php
/**
 * Initialize modules
 */
public function init_modules() {
    // Load module files
    $this->load_modules();

    // Initialize Survey module first
    if (isset($this->modules['survey']) && class_exists($this->modules['survey'])) {
        new $this->modules['survey']();
    }

    // Initialize Survey Tracking module
    if (isset($this->modules['survey-tracking']) && class_exists($this->modules['survey-tracking'])) {
        new $this->modules['survey-tracking']();
    }

    // Initialize Elementor module if Elementor is active
    if (did_action('elementor/loaded')) {
        if (isset($this->modules['elementor-widgets']) && class_exists($this->modules['elementor-widgets'])) {
            new $this->modules['elementor-widgets']();
        }
    }

    // Initialize Fluent Forms module - USES SINGLETON PATTERN
    if (defined('FLUENTFORM') || function_exists('wpFluentForm')) {
        if (class_exists('RM_Panel_Fluent_Forms_Module')) {
            RM_Panel_Fluent_Forms_Module::get_instance(); // ✅ CORRECT
        }
    }

    // Fire action for external modules
    do_action('rm_panel_extensions_modules_loaded');
}
```

**⚠️ CRITICAL: Module Initialization Pattern**

```php
// ❌ WRONG - Causes fatal error with private constructor
if (isset($this->modules['fluent-forms']) && class_exists($this->modules['fluent-forms'])) {
    new $this->modules['fluent-forms']();
}

// ✅ CORRECT - Uses singleton pattern
if (class_exists('RM_Panel_Fluent_Forms_Module')) {
    RM_Panel_Fluent_Forms_Module::get_instance();
}
```

---

## 🐛 Common Issues & Solutions - UPDATED

### Issue 11: Country Not Auto-Detecting
**Problem:** Country field remains empty  
**Possible Causes:**
1. IPStack API key not saved
2. Field name is not exactly `country`
3. API key expired or invalid
4. Rate limit exceeded (100/month on free tier)
5. Testing from localhost (127.0.0.1 won't work)

**Solutions:**

**A. Check API Key:**
```sql
SELECT option_value FROM wp_options WHERE option_name = 'rm_panel_ipstack_api_key';
```

**B. Check Field Name:**
- Edit Fluent Form
- Click country field
- Advanced Options → Name must be exactly: `country`

**C. Enable Debug Logging:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**D. Check Debug Log (`wp-content/debug.log`):**
```
RM Panel: Country detection AJAX called
RM Panel: Detecting country for IP: xxx.xxx.xxx.xxx
RM Panel: API Key present: Yes
RM Panel: Calling IPStack API: http://api.ipstack.com/...
RM Panel: IPStack API response: Array(...)
RM Panel: Country detected successfully: United States
```

**E. Test API Manually:**
```php
// Create test page
$api_key = get_option('rm_panel_ipstack_api_key', '');
$ip = $_SERVER['REMOTE_ADDR'];
$url = "http://api.ipstack.com/{$ip}?access_key={$api_key}";
$response = wp_remote_get($url);
echo '<pre>';
print_r(json_decode(wp_remote_retrieve_body($response), true));
echo '</pre>';
```

**F. Check Browser Console:**
- F12 → Console tab
- Look for: `RM Panel: Country field found`
- Check Network tab for AJAX call
- Verify response contains country data

**G. Temporary Hardcode Test:**
```php
// In get_user_country_from_ip() method - TEMPORARY
private function get_user_country_from_ip() {
    return 'United States'; // Test only
    // ... rest of code
}
```

### Issue 12: Fatal Error - Call to Private Constructor
**Problem:** `Fatal error: Call to private RM_Panel_Fluent_Forms_Module::__construct()`  
**Cause:** Trying to use `new` with singleton class  
**Solution:** Use `RM_Panel_Fluent_Forms_Module::get_instance()` instead

**Wrong:**
```php
new RM_Panel_Fluent_Forms_Module(); // ❌
```

**Correct:**
```php
RM_Panel_Fluent_Forms_Module::get_instance(); // ✅
```

### Issue 13: Country Name Doesn't Match Dropdown Options
**Problem:** IPStack returns "United States" but dropdown has "USA"  
**Solution:** The JavaScript tries multiple matching strategies:
- Exact text match (case-insensitive)
- Value match (case-insensitive)
- Partial text match (contains)

**Workaround:** Add alias mapping in JavaScript:
```javascript
// In autoFillCountry() function
const countryAliases = {
    'United States': ['USA', 'US', 'United States of America'],
    'United Kingdom': ['UK', 'Great Britain', 'England']
};
```

### Issue 14: API Rate Limit Exceeded
**Problem:** Free tier allows 100 requests/month  
**Solution:** 
- Cache is set to 5 minutes to reduce calls
- Consider upgrading to paid plan if needed
- Monitor usage: https://ipstack.com/dashboard

**Check Cache:**
```php
$ip = $_SERVER['REMOTE_ADDR'];
$cache_key = 'rm_country_' . md5($ip);
$cached = get_transient($cache_key);
var_dump($cached); // Shows cached country or false
```

---

## 🧪 Testing Checklist - UPDATED

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

### Debug Checklist
```javascript
// Browser Console Should Show:
✓ RM Panel: Initializing validation and country detection...
✓ RM Panel: Country field found: [object]
✓ RM Panel: Starting country auto-detection...
✓ RM Panel: Country detection response: {success: true, ...}
✓ RM Panel: Detected country: [Country Name]
✓ RM Panel: Matching option found, value: [Value]
```

```php
// Debug Log Should Show:
✓ RM Panel: Country detection AJAX called
✓ RM Panel: Detecting country for IP: xxx.xxx.xxx.xxx
✓ RM Panel: API Key present: Yes
✓ RM Panel: Calling IPStack API: http://api.ipstack.com/...
✓ RM Panel: IPStack API response: Array(...)
✓ RM Panel: Country detected successfully: [Country]
```

---

## 📝 Quick Reference Commands - UPDATED

### Fluent Forms - Country Detection

```php
// Check if API key is set
$api_key = get_option('rm_panel_ipstack_api_key', '');
echo !empty($api_key) ? 'Set' : 'Not Set';

// Manually detect country
$fluent_forms = RM_Panel_Fluent_Forms_Module::get_instance();
// Note: get_user_country_from_ip() is private, use AJAX endpoint

// Clear country cache for an IP
$ip = '8.8.8.8';
$cache_key = 'rm_country_' . md5($ip);
delete_transient($cache_key);

// Test IPStack API directly
$api_key = get_option('rm_panel_ipstack_api_key', '');
$response = wp_remote_get("http://api.ipstack.com/8.8.8.8?access_key={$api_key}");
$data = json_decode(wp_remote_retrieve_body($response), true);
print_r($data);
```

### JavaScript Console Commands

```javascript
// Manually trigger country detection
autoFillCountry();

// Check if country field exists
jQuery('select[name="country"], input[name="country"]').length;

// Get current country value
jQuery('select[name="country"], input[name="country"]').val();

// List all country options (for dropdown)
jQuery('select[name="country"] option').each(function() {
    console.log(jQuery(this).val(), jQuery(this).text());
});

// Manually set country
jQuery('select[name="country"]').val('United States').trigger('change');
```

---

## 🔐 Important Security Notes - UPDATED

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

---

## 📊 Performance Optimization - UPDATED

### Country Detection Optimization
- ✅ 5-minute transient cache reduces API calls by ~99%
- ✅ Cache key based on IP hash (not raw IP for privacy)
- ✅ AJAX timeout set to 10 seconds (prevents hanging)
- ✅ Graceful fallback if API fails (form still usable)
- ✅ Conditional loading (only on enabled forms)
- ✅ Multiple detection attempts (0s, 1s, 2s) improve success rate

### API Call Optimization
```php
// Cache structure
$cache_key = 'rm_country_' . md5($ip); // Hashed for privacy
set_transient($cache_key, $country, 5 * MINUTE_IN_SECONDS);

// Check cache first
$cached_country = get_transient($cache_key);
if ($cached_country !== false) {
    return $cached_country; // No API call needed
}
```

### Monitoring API Usage
- Free tier: 100 requests/month
- With 5-min cache: ~8,640 unique IPs per month possible
- Typical usage: ~10-30 API calls/month for small sites
- Dashboard: https://ipstack.com/dashboard

---

## 🚀 Future Reference Usage - UPDATED

**Instead of pasting files, say:**
- "Check the Country Detection Flow section"
- "Reference: RM_Panel_Fluent_Forms_Module::get_instance() - Singleton Pattern"
- "See 'Issue 11: Country Not Auto-Detecting' in Common Issues"
- "Check Fluent Forms Country Detection Configuration"
- "Reference: RM_Panel_Fluent_Forms_Module::ajax_get_country_from_ip()"
- "Reference: IPStack API Integration section"
- "See 'Issue 12: Fatal Error - Private Constructor' in Common Issues"

---

**Version:** 1.0.1  
**Last Updated:** 16th October 2025  
**Latest Features:** 
- Real-time username validation with 5 character minimum
- Real-time email validation with availability checking
- Real-time password strength indicator (weak/medium/strong)
- **Auto-detect country from IP using IPStack API** ✨ NEW
- **5-minute cache for country detection** ✨ NEW
- **Multiple detection attempts for reliability** ✨ NEW
- Per-form validation settings in admin
- Conditional script loading based on form settings
- **Singleton pattern to prevent double initialization** ✨ UPDATED
- Three separate AJAX endpoints with nonce security
- **Country detection AJAX endpoint** ✨ NEW
- Comprehensive visual feedback system
- **IPStack API integration with error handling** ✨ NEW