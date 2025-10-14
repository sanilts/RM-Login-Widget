# RM Panel Extensions - Project Reference Document

## 📋 Project Overview
**Plugin Name:** RM Panel Extensions  
**Version:** 1.0.0  
**Purpose:** Comprehensive WordPress plugin with survey management, Elementor widgets, and user tracking

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
│   └── referral/
│       └── class-referral-system.php (Referral tracking)
└── assets/
    ├── css/ (All stylesheets)
    └── js/ (All JavaScript files)
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

### 5. **Survey Widgets** (widgets/*.php)

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

---

## 🔐 Important Security Notes

1. **Token Validation:** All callback URLs MUST include valid token
2. **Nonce Verification:** All AJAX requests use `wp_verify_nonce()`
3. **User Capabilities:** Admin functions check `manage_options`
4. **SQL Injection:** All queries use `$wpdb->prepare()`

---

## 📚 Dependencies

**Required:**
- WordPress 5.0+
- PHP 7.0+

**Optional:**
- Elementor (for widgets)
- WPML (for translations)

---

## 🚀 Future Reference Usage

**Instead of pasting files, say:**
- "Check the Survey Tracking Flow section"
- "Reference: RM_Survey_Callbacks::generate_survey_token()"
- "See 'Issue 1: Survey ID is Wrong' in Common Issues"

**Last Updated:** October 2025  
**Project Version:** 1.0.0
