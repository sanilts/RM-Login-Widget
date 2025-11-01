# Survey Module - Technical Documentation

**Version:** 2.1.0  
**Last Updated:** November 2025  
**Author:** Research and Metric Development Team

---

## Table of Contents

1. [Module Overview](#module-overview)
2. [Architecture & File Structure](#architecture--file-structure)
3. [Core Components](#core-components)
4. [Database Schema](#database-schema)
5. [API & Endpoints](#api--endpoints)
6. [Features & Capabilities](#features--capabilities)
7. [Integration Points](#integration-points)
8. [Frontend Implementation](#frontend-implementation)
9. [Admin Interface](#admin-interface)
10. [Enhancement Roadmap](#enhancement-roadmap)
11. [Technical Debt](#technical-debt)
12. [Performance Optimization](#performance-optimization)
13. [Security Considerations](#security-considerations)
14. [Testing Strategy](#testing-strategy)
15. [Deployment Guidelines](#deployment-guidelines)

---

## Module Overview

### Purpose
The Survey Module is a comprehensive WordPress solution for managing market research surveys with advanced features including:
- External survey platform integration
- User response tracking and analytics
- Payment/reward management
- Geographic targeting
- Multi-status approval workflows

### Key Capabilities
- **Survey Management:** Create, configure, and manage surveys with detailed metadata
- **Response Tracking:** Monitor user participation, completion rates, and outcomes
- **Payment Processing:** Handle paid surveys with approval workflows
- **Geographic Targeting:** Target surveys to specific countries using FluentCRM integration
- **Callback Integration:** Seamless integration with external survey platforms
- **Real-time Reporting:** Live monitoring and comprehensive analytics

---

## Architecture & File Structure

### Directory Structure
```
modules/survey/
├── class-survey-module.php              # Core survey post type & management
├── class-survey-tracking.php            # Response tracking & analytics
├── class-survey-callbacks.php           # External platform callbacks
├── class-survey-approval-admin.php      # Payment approval interface
├── class-survey-tabs-shortcode.php      # Frontend display shortcode
├── class-survey-thank-you.php           # Completion confirmation
└── class-survey-database-upgrade.php    # Database versioning

modules/elementor/widgets/
└── survey-accordion-tabs-widget.php     # Elementor integration

modules/reports/
├── class-survey-live-monitor.php        # Real-time monitoring
├── class-survey-reports.php             # Response reports
└── class-user-reports.php               # User analytics

assets/
├── css/
│   ├── survey-accordion-tabs.css        # Widget styles (v2.1.0)
│   ├── survey-accordion.css             # Legacy accordion
│   ├── survey-tabs.css                  # Tab navigation
│   ├── survey-styles.css                # General survey styles
│   └── survey-reports.css               # Admin report styles
└── js/
    ├── survey-admin.js                  # Admin interface
    ├── survey-tracking.js               # Frontend tracking
    ├── survey-tabs.js                   # Tab functionality
    ├── survey-callback-admin.js         # Callback URL management
    ├── survey-approval.js               # Approval workflow
    └── survey-reports.js                # Report filtering
```

### Class Hierarchy

```
RM_Panel_Survey_Module (Main Controller)
├── Registers CPT and Taxonomies
├── Handles Meta Boxes
├── Manages Admin UI
└── Processes Survey Redirects

RM_Panel_Survey_Tracking (Response Manager)
├── Tracks User Responses
├── Calculates Earnings
├── Manages Approval Status
└── Generates Reports

RM_Survey_Callbacks (External Integration)
├── Generates Callback URLs
├── Verifies Security Tokens
├── Processes Platform Returns
└── Updates Response Status

RM_Survey_Approval_Admin (Payment Workflow)
├── Displays Pending Responses
├── Handles Approval/Rejection
├── Sends Email Notifications
└── Updates Payment Status

RM_Panel_Survey_Accordion_Tabs_Widget (Frontend Display)
├── Available Surveys Tab
├── Completed Surveys Tab
├── Accordion Display
└── Referral System Integration
```

---

## Core Components

### 1. Survey Post Type (`class-survey-module.php`)

#### Post Type Registration
```php
// Post Type: rm_survey
// Supports: title, editor, thumbnail, excerpt, custom-fields, revisions
// Has Archive: true
// Rewrite: /survey/
```

#### Taxonomies
```php
// survey_category - Hierarchical survey categorization
// survey_user_category - User demographic targeting
```

#### Custom Meta Fields

##### Survey Type & Payment
```php
_rm_survey_type              // 'paid' | 'not_paid'
_rm_survey_amount            // float (decimal 2 places)
```

##### Survey URL & Parameters
```php
_rm_survey_url              // External survey URL
_rm_survey_parameters       // Array of parameter configurations
// Structure:
[
    [
        'field' => 'survey_id|user_id|username|email|...',
        'variable' => 'url_parameter_name',
        'custom_value' => 'optional_custom_value'
    ]
]
```

##### Duration Settings
```php
_rm_survey_duration_type    // 'never_ending' | 'date_range'
_rm_survey_start_date       // Y-m-d format
_rm_survey_end_date         // Y-m-d format
```

##### Survey Details
```php
_rm_survey_questions_count  // Integer
_rm_survey_estimated_time   // Integer (minutes)
_rm_survey_target_audience  // Text description
```

##### Access Control
```php
_rm_survey_status           // 'draft'|'active'|'paused'|'closed'
_rm_survey_requires_login   // '1' | '0'
_rm_survey_allow_multiple   // '1' | '0'
_rm_survey_anonymous        // '1' | '0'
```

##### Geographic Targeting
```php
_rm_survey_location_type    // 'all' | 'specific'
_rm_survey_countries        // Array of country codes ['US','GB','IN']
```

### 2. Survey Tracking (`class-survey-tracking.php`)

#### Database Table: `wp_rm_survey_responses`

```sql
CREATE TABLE wp_rm_survey_responses (
    id                  BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    user_id             BIGINT(20) NOT NULL,
    survey_id           BIGINT(20) NOT NULL,
    status              VARCHAR(50) DEFAULT 'started',
    completion_status   VARCHAR(50) NULL,
    start_time          DATETIME DEFAULT CURRENT_TIMESTAMP,
    completion_time     DATETIME NULL,
    response_data       LONGTEXT NULL,
    ip_address          VARCHAR(100) NULL,
    user_agent          TEXT NULL,
    referrer_url        TEXT NULL,
    notes               TEXT NULL,
    approval_status     VARCHAR(20) DEFAULT 'pending',
    approved_by         BIGINT(20) NULL,
    approval_date       DATETIME NULL,
    country             VARCHAR(100) NULL,
    return_time         DATETIME NULL,
    admin_notes         TEXT NULL,
    
    KEY user_id (user_id),
    KEY survey_id (survey_id),
    KEY status (status),
    KEY completion_status (completion_status),
    KEY start_time (start_time),
    UNIQUE KEY user_survey (user_id, survey_id)
);
```

#### Status Constants

```php
// Response Status
STATUS_STARTED = 'started'           // User clicked survey
STATUS_COMPLETED = 'completed'       // User finished survey

// Completion Status
STATUS_SUCCESS = 'success'           // Qualified completion
STATUS_QUOTA_COMPLETE = 'quota_complete'  // Survey full
STATUS_DISQUALIFIED = 'disqualified'      // User screened out
STATUS_ABANDONED = 'abandoned'            // User left mid-survey

// Approval Status (for paid surveys)
APPROVAL_PENDING = 'pending'         // Awaiting admin review
APPROVAL_APPROVED = 'approved'       // Payment approved
APPROVAL_REJECTED = 'rejected'       // Payment denied
APPROVAL_AUTO_APPROVED = 'auto_approved'  // Non-paid surveys
```

#### Key Methods

```php
// Start tracking
start_survey($user_id, $survey_id)
    → Returns: response_id | WP_Error

// Complete tracking
complete_survey($user_id, $survey_id, $completion_status, $response_data)
    → Returns: update_result | WP_Error

// Approval workflow
approve_survey_response($response_id, $admin_notes)
reject_survey_response($response_id, $admin_notes)
    → Triggers: payment processing, email notifications

// Data retrieval
get_user_survey_response($user_id, $survey_id)
get_user_survey_history($user_id, $args)
get_survey_stats($survey_id)
get_available_surveys($user_id)
get_pending_count()
```

### 3. Callback System (`class-survey-callbacks.php`)

#### Security Token Generation

```php
// Survey-level token (works for all users)
generate_survey_token($survey_id)
    → Returns: SHA-256 hash of survey_id + wp_salt

// Verification
verify_survey_token($survey_id, $provided_token)
    → Returns: boolean (hash_equals comparison)
```

#### Callback URL Structure

```
Base Pattern: {site_url}/survey-callback/{status}/

Parameters:
- sid: Survey ID
- uid: User ID
- token: Security token

Example:
https://example.com/survey-callback/success/?sid=123&uid=456&token=abc123...

Status Types:
- success    → Maps to STATUS_SUCCESS
- terminate  → Maps to STATUS_DISQUALIFIED
- quotafull  → Maps to STATUS_QUOTA_COMPLETE
```

#### Rewrite Rules

```php
// Registered endpoints
^survey-callback/success/?$     → index.php?rm_callback=success
^survey-callback/terminate/?$   → index.php?rm_callback=terminate
^survey-callback/quotafull/?$   → index.php?rm_callback=quotafull
```

#### URL Generation

```php
generate_callback_urls($survey_id, $user_id = null)
    → Returns: Array of callback URLs
    [
        'success' => 'https://...',
        'terminate' => 'https://...',
        'quotafull' => 'https://...'
    ]
```

---

## Database Schema

### Survey Responses Table (v1.1.0)

```sql
-- Core tracking fields
id                  BIGINT(20)      -- Primary key
user_id             BIGINT(20)      -- WordPress user ID
survey_id           BIGINT(20)      -- Survey post ID
status              VARCHAR(50)     -- started|completed
completion_status   VARCHAR(50)     -- success|quota_complete|disqualified

-- Timing fields
start_time          DATETIME        -- When user clicked survey
completion_time     DATETIME        -- When survey completed
return_time         DATETIME        -- When user returned to site

-- Data fields
response_data       LONGTEXT        -- JSON encoded response
ip_address          VARCHAR(100)    -- User IP
user_agent          TEXT            -- Browser info
referrer_url        TEXT            -- Where user came from
country             VARCHAR(100)    -- User country (from FluentCRM)

-- Approval workflow (v1.1.0)
approval_status     VARCHAR(20)     -- pending|approved|rejected
approved_by         BIGINT(20)      -- Admin user ID
approval_date       DATETIME        -- When approved/rejected
admin_notes         TEXT            -- Admin comments
notes               TEXT            -- System notes

-- Indexes for performance
KEY user_id (user_id)
KEY survey_id (survey_id)
KEY status (status)
KEY completion_status (completion_status)
KEY start_time (start_time)
UNIQUE KEY user_survey (user_id, survey_id)  -- One response per user
```

### Database Version Management

```php
// Version tracking
add_option('rm_panel_survey_db_version', '1.1.0')

// Upgrade checks
class RM_Survey_Database_Upgrade
    → Checks version on plugins_loaded
    → Runs upgrade methods if needed
    → Updates version option
```

---

## API & Endpoints

### AJAX Actions

#### Frontend AJAX

```php
// Start survey tracking
Action: wp_ajax_rm_start_survey
       wp_ajax_nopriv_rm_start_survey
Nonce: rm_survey_nonce
Data:  { survey_id: int }
Return: { success: bool, response_id: int }

// Complete survey
Action: wp_ajax_rm_complete_survey
       wp_ajax_nopriv_rm_complete_survey
Nonce: rm_survey_nonce
Data:  { 
    survey_id: int,
    completion_status: string,
    response_data: object
}
Return: { success: bool, message: string }
```

#### Admin AJAX

```php
// Approve response
Action: wp_ajax_rm_approve_survey
Nonce: rm_approval_nonce
Capability: manage_options
Data:  { response_id: int, notes: string }
Return: { success: bool, message: string }

// Reject response
Action: wp_ajax_rm_reject_survey
Nonce: rm_approval_nonce
Capability: manage_options
Data:  { response_id: int, notes: string }
Return: { success: bool, message: string }

// Generate user-specific callback URLs
Action: wp_ajax_copy_callback_urls
Nonce: rm_callback_nonce
Data:  { survey_id: int, user_id: int }
Return: { 
    success: bool,
    data: {
        success: string,
        terminate: string,
        quotafull: string
    }
}
```

### REST API Endpoints

```php
// Survey post type REST support
Namespace: wp/v2
Endpoint: /surveys
Methods: GET, POST, PUT, DELETE

// Custom endpoints could be added:
Namespace: rm-panel/v1
Potential endpoints:
- /surveys/{id}/stats
- /surveys/{id}/responses
- /users/{id}/survey-history
```

### Custom Query Vars

```php
// Registered query variables
rm_callback          // Callback type (success|terminate|quotafull)
rm_survey_callback   // Main callback identifier
```

---

## Features & Capabilities

### 1. Survey Configuration

#### Survey Types
- **Paid Surveys:** Include monetary compensation
- **Unpaid Surveys:** Standard research surveys
- **Amount Configuration:** Decimal precision to 2 places

#### Duration Management
- **Never Ending:** Perpetually available surveys
- **Date Range:** Specific start and end dates
- **Automatic Expiration:** System checks date validity

#### Access Control
- **Login Requirements:** Force authentication
- **Multiple Submissions:** Allow/prevent repeat participation
- **Anonymous Mode:** No user tracking (planned)

#### Geographic Targeting
- **Global Surveys:** Available to all countries
- **Country-Specific:** Target specific regions
- **FluentCRM Integration:** Uses contact country data
- **Auto-Filtering:** Hides surveys from non-targeted users

### 2. URL Parameter System

#### Default Parameters (Always Included)
```php
// Survey ID
field: 'survey_id'
variable: 'sid' (customizable)
value: Current survey post ID

// User ID  
field: 'user_id'
variable: 'uid' (customizable)
value: Current WordPress user ID
```

#### Available User Fields
```php
'username'      → $current_user->user_login
'email'         → $current_user->user_email
'first_name'    → $current_user->first_name
'last_name'     → $current_user->last_name
'display_name'  → $current_user->display_name
'user_role'     → implode(',', $current_user->roles)
'timestamp'     → time()
'custom'        → Custom static value
```

#### Parameter Structure
```php
[
    'field' => 'email',           // What data to pass
    'variable' => 'user_email',   // URL parameter name
    'custom_value' => ''          // For custom field type
]

// Generates: ?user_email=john@example.com
```

### 3. Response Tracking

#### User Journey Tracking
1. **Survey Discovery:** User sees available survey
2. **Click Tracking:** start_survey() called on button click
3. **External Redirect:** User sent to survey platform with parameters
4. **Survey Completion:** External platform calls callback URL
5. **Return Processing:** Callback validates and updates status
6. **Thank You Redirect:** User sees completion message

#### Tracked Data Points
- IP Address
- User Agent (Browser/Device)
- Referrer URL
- Country (via FluentCRM)
- Start Time
- Completion Time
- Return Time
- Response Duration (calculated)

### 4. Approval Workflow

#### Process Flow
```
Survey Completed (Paid)
    ↓
Status: pending
    ↓
Admin Review
    ↓
┌─────────────┴─────────────┐
│                           │
Approve                 Reject
│                           │
Status: approved        Status: rejected
│                           │
Process Payment        Send Notification
│                           │
Email User             Email User
```

#### Admin Interface
- Pending count badge in admin menu
- Filterable list (pending/approved/rejected)
- Bulk actions support (planned)
- Detailed response information
- Admin notes system

#### Notification System
```php
// Approval email
Subject: Survey Approved: {Survey Title}
Content: Congratulations message, amount, date

// Rejection email  
Subject: Survey Response Update: {Survey Title}
Content: Status update, admin notes, support info
```

### 5. Reward System

#### Earning Calculation
```php
// Only for successful paid survey completions
if ($survey_type === 'paid' && $completion_status === 'success') {
    $amount = get_post_meta($survey_id, '_rm_survey_amount', true);
    
    // Update user meta
    $total_earned = get_user_meta($user_id, 'rm_survey_total_earned', true);
    $total_earned += $amount;
    update_user_meta($user_id, 'rm_survey_total_earned', $total_earned);
    
    // Record individual earning
    add_user_meta($user_id, 'rm_survey_earning', [
        'survey_id' => $survey_id,
        'amount' => $amount,
        'date' => current_time('mysql'),
        'status' => 'pending'  // Changes to 'approved' after admin review
    ]);
}
```

#### Payment Integration Points
```php
// Action hooks for payment systems
do_action('rm_survey_reward_earned', $user_id, $survey_id, $amount);
do_action('rm_survey_approved', $user_id, $survey_id, $response_id);
do_action('rm_survey_rejected', $user_id, $survey_id, $response_id);
do_action('rm_survey_completed', $user_id, $survey_id, $completion_status, $response_data);
```

---

## Integration Points

### 1. FluentCRM Integration

#### Country Detection
```php
class RM_Panel_FluentCRM_Helper

// Get contact country
get_contact_country($user_id)
    → Returns: Country code or null

// Check survey location match
matches_survey_location($user_id, $survey_id)
    → Returns: boolean

// Get country name
get_country_name($country_code)
    → Returns: Full country name
```

#### Usage in Survey Filtering
```php
// Only show surveys matching user's country
$available_surveys = $tracker->get_available_surveys($user_id);

// Filtered by:
// 1. Survey status = 'active'
// 2. Date range (if applicable)
// 3. Country targeting (FluentCRM)
// 4. Not already completed
```

### 2. Elementor Integration

#### Widget: Survey Accordion Tabs
```php
Class: RM_Panel_Survey_Accordion_Tabs_Widget
Category: rm-panel-widgets
Icon: eicon-tabs
Keywords: survey, accordion, tabs, completed, available
```

#### Widget Controls

**Layout Tab:**
- Columns (1-4)
- Content display toggles
- Header meta options
- Button configuration
- Custom messages

**Style Tab:**
- Tab navigation colors
- Accordion item styling
- Title typography
- Button styles (start/invite)

#### Widget Features
- Available surveys tab with accordion
- Completed surveys tab with details
- Responsive design (mobile-first)
- Real-time tab switching
- Invite/referral modal
- Social sharing buttons
- Survey start tracking

### 3. Referral System Integration

#### Referral Link Generation
```php
$registration_url = get_option('rm_referral_registration_url', wp_registration_url());
$referral_link = add_query_arg('ref', $user_id, $registration_url);

// Example: https://example.com/register/?ref=123
```

#### Social Sharing
```php
// Supported platforms
- WhatsApp
- Facebook
- Twitter/X
- Email

// Share buttons in invite modal
// Tracking: RM_Referral_System (if active)
```

### 4. WPML Integration (Planned)

```php
// Survey translation support
// Currently: Post type registered with show_in_rest = true
// Future: WPML configuration for survey strings
```

---

## Frontend Implementation

### 1. Shortcodes

#### Survey History
```php
[rm_survey_history limit="10" show_earnings="yes"]

// Displays user's completed surveys
// Shows total earnings
// Filterable table view
```

#### Survey Tabs (v2.0)
```php
[rm_survey_tabs columns="3" show_amount="yes" show_category="yes"]

// Two-tab interface
// Available vs Completed
// Card-based layout
```

#### Thank You Page
```php
[survey_thank_you]

// Displays completion message
// Shows earned amount (if paid)
// Links to dashboard and browse surveys
```

### 2. Survey Accordion Widget (Elementor)

#### Available Surveys Tab

**Display Elements:**
- Survey title (H3)
- Survey description/excerpt
- Meta information:
  - Amount (if paid)
  - Questions count
  - Estimated time
  - Days remaining (if date-limited)
- Survey type card
- Duration card
- Survey length box (highlighted yellow)
- Target audience box (highlighted blue)
- Start survey button
- Invite friends button

**Filtering:**
- Active status only
- Date range validation
- Country targeting (FluentCRM)
- Already completed (excluded)

#### Completed Surveys Tab

**Display Elements:**
- Survey title
- Completion date
- Completion status badge
- Approval status (for paid)
- Amount earned
- Admin notes (if any)

**Status Indicators:**
```php
// Completion Status Colors
success          → Green (#d4edda)
quota_complete   → Yellow (#fff3cd)
disqualified     → Red (#f8d7da)

// Approval Status Colors
pending          → Yellow
approved         → Green
rejected         → Red
```

### 3. CSS Architecture (v2.1.0)

#### File: `survey-accordion-tabs.css`

**CSS Variables:**
```css
:root {
    --rm-primary-blue: #3b82f6;
    --rm-primary-blue-hover: #2563eb;
    --rm-success-green: #10b981;
    --rm-text-dark: #1e293b;
    --rm-text-gray: #64748b;
    --rm-text-light: #94a3b8;
    --rm-border-light: #e2e8f0;
    --rm-white: #ffffff;
    --rm-background: #f8fafc;
    --rm-radius: 12px;
    --rm-radius-sm: 8px;
    --rm-transition: all 0.2s ease;
}
```

**Key Components:**
- Tab Navigation (active state with blue background)
- Accordion Items (smooth expand/collapse)
- Info Cards Grid (responsive auto-fit)
- Highlighted Sections (yellow for length, blue for audience)
- Responsive Breakpoints (768px, 480px)

#### Animation System
```css
/* Tab content fade-in */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Accordion expand (CSS-only) */
.rm-survey-accordion-content {
    max-height: 0;
    transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.active .rm-survey-accordion-content {
    max-height: 4000px;
}
```

### 4. JavaScript Functionality

#### Tab Switching (`survey-tabs.js`)
```javascript
$('.rm-accordion-tab-btn').on('click', function() {
    var tab = $(this).data('tab');
    
    // Update buttons
    $('.rm-accordion-tab-btn').removeClass('active');
    $(this).addClass('active');
    
    // Update content
    $('.rm-accordion-tab-content').removeClass('active');
    $('#' + tab + '-tab').addClass('active');
});
```

#### Accordion Toggle (v1.1.1 - CSS Animation)
```javascript
// Simple class toggle - CSS handles animation
$('.rm-survey-accordion-header').on('click', function() {
    var $item = $(this).closest('.rm-survey-accordion-item');
    $item.toggleClass('active');
});
```

#### Invite Modal
```javascript
// Open modal
$('.rm-invite-button').on('click', function() {
    $('#invite-modal').fadeIn();
});

// Copy referral link
$('.copy-link-btn').on('click', function() {
    var $input = $('.referral-link-input');
    $input.select();
    document.execCommand('copy');
    
    $(this).text('Copied!');
    setTimeout(() => $(this).text('Copy Link'), 2000);
});
```

---

## Admin Interface

### 1. Survey Edit Screen

#### Meta Boxes

**Survey Type & Payment** (Normal, High)
- Type selector (paid/not paid)
- Amount field (conditional display)
- Payment calculation info

**Survey URL & Parameters** (Normal, High)
- External survey URL input
- Survey ID display (read-only)
- Dynamic parameter table
  - Default params (survey_id, user_id) - cannot be removed
  - Custom param rows (add/remove)
  - Custom value input for custom field type
- Live URL preview with placeholders

**Survey Duration** (Normal, High)
- Duration type (never_ending/date_range)
- Start date picker (conditional)
- End date picker (conditional)
- Date validation

**Survey Details** (Normal, High)
- Questions count
- Estimated time (minutes)
- Target audience description

**Survey Settings** (Side, Default)
- Status dropdown (draft/active/paused/closed)
- Requires login checkbox
- Allow multiple submissions checkbox
- Anonymous responses checkbox

**Survey Location Targeting** (Side, High)
- Location type (all/specific)
- Country multi-select (conditional)
- FluentCRM integration note

**Survey Callback URLs** (Normal, High)
- Success URL with copy button
- Terminate URL with copy button
- Quota Full URL with copy button
- User-specific test URLs
- Token information display
- Debug mode link

#### Admin JavaScript (`survey-admin.js`)

**Dynamic Behaviors:**
```javascript
// Toggle payment amount field
togglePaymentAmount()

// Toggle duration date fields
toggleDurationFields()

// Update URL preview with parameters
updatePreviewUrl()

// Add parameter row
$('#add_survey_parameter').on('click', ...)

// Remove parameter row (not defaults)
$('.remove-parameter').on('click', ...)

// Show/hide custom value field
$(document).on('change', '#survey-parameters-table select', ...)

// Update preview on input change
$(document).on('input', 'input[name*="[variable]"]', ...)
```

### 2. Admin Columns

#### Surveys List Table

**Custom Columns:**
- Survey Type (Paid/Not Paid with color)
- Survey Status (Badge with color)
- Duration (Date range or "Never Ending")
- Amount ($X.XX or "—")
- Target Countries (First 3 + count)
- Responses (Completed/Total with tooltip)

**Column Sorting:**
- Survey Type
- Survey Status
- Amount
- Date (default)

**Quick Edit Support:**
- Status change
- Date modification
- Amount update

### 3. Approval Admin Page

**Location:** `Admin → RM Panel Ext → Pending Approvals`

**Menu Badge:** Shows pending count in real-time

**Filter Tabs:**
- Pending (default)
- Approved
- Rejected

**Table Columns:**
- Response ID
- User (name + email)
- Survey (linked to edit)
- Country
- Start Time
- Completion Time
- Duration (calculated)
- Amount
- Status Badge
- Actions (Approve/Reject buttons)

**Modal Dialogs:**
```javascript
// Approval Modal
- Response ID (hidden)
- Admin notes (optional textarea)
- Confirm button
- Cancel button

// Rejection Modal
- Response ID (hidden)
- Rejection reason (required textarea)
- Confirm button
- Cancel button
```

**AJAX Processing:**
```javascript
// Approval
Action: rm_approve_survey
Data: { response_id, notes }
Result: Success message → page reload

// Rejection
Action: rm_reject_survey
Data: { response_id, notes }
Validation: Notes required
Result: Success message → page reload
```

### 4. Reports Interface

#### Survey Reports Page

**Filters:**
- Date range picker
- Survey selector
- Status filter
- Export button

**Data Table:**
- User information
- Survey details
- Response metrics
- Completion status
- Approval status

**Export Options:**
- CSV export
- Filtered results
- Date range selection

#### Live Monitor Dashboard

**Real-time Metrics:**
- Active users count
- Completion rate
- Average duration
- Success rate
- Revenue tracking

**Auto-refresh:**
- Configurable interval
- WebSocket support (planned)

---

## Enhancement Roadmap

### Phase 1: Core Improvements (Q1 2026)

#### 1.1 Enhanced Analytics
**Priority:** High  
**Effort:** Medium

```php
// New database table
wp_rm_survey_analytics
    - id
    - survey_id
    - date
    - views_count
    - starts_count
    - completions_count
    - success_rate
    - average_duration
    - total_revenue
```

**Features:**
- Daily aggregation cron job
- Trend analysis charts
- Completion funnel visualization
- Response time heat maps
- Geographic distribution maps

#### 1.2 Advanced Targeting
**Priority:** High  
**Effort:** Medium

```php
// Extended targeting options
_rm_survey_target_age_min       // Integer
_rm_survey_target_age_max       // Integer
_rm_survey_target_gender        // Array ['male','female','other']
_rm_survey_target_income        // Range array [min, max]
_rm_survey_target_education     // Array of education levels
_rm_survey_target_occupation    // Array of job categories
```

**Implementation:**
- FluentCRM custom field mapping
- Advanced query builder
- Eligibility pre-screening
- Auto-qualification system

#### 1.3 Response Quality Control
**Priority:** Medium  
**Effort:** High

```php
// Quality metrics
wp_rm_survey_quality_flags
    - response_id
    - flag_type           // speedster|straight_liner|inconsistent
    - flag_severity       // low|medium|high
    - auto_detected       // boolean
    - admin_confirmed     // boolean
    - created_at
```

**Features:**
- Completion time analysis (speedster detection)
- Pattern recognition (straight-lining)
- Attention check validation
- IP fraud detection
- Multi-account detection
- Quality score calculation
- Auto-rejection rules

### Phase 2: User Experience (Q2 2026)

#### 2.1 Progress Tracking
**Priority:** High  
**Effort:** Low

```php
// User dashboard widget
- Surveys taken today/week/month
- Earnings this period
- Available surveys count
- Pending approvals
- Next payout date
- Achievement badges
```

#### 2.2 Notification System
**Priority:** Medium  
**Effort:** Medium

```php
// Notification types
- New survey available (matching your profile)
- Survey approved/rejected
- Payment processed
- Survey expiring soon
- Quota alert (survey filling up)
- Achievement unlocked
```

**Delivery Channels:**
- In-site notifications
- Email (with preferences)
- Browser push (optional)
- SMS (premium feature)

#### 2.3 Gamification
**Priority:** Low  
**Effort:** Medium

```php
wp_rm_user_achievements
    - user_id
    - achievement_type    // first_survey|streak_7|earnings_milestone
    - unlocked_at
    - metadata
```

**Features:**
- Survey streak tracking
- Milestone badges
- Leaderboards
- Bonus multipliers
- Referral rewards
- Level system

### Phase 3: Platform Integration (Q3 2026)

#### 3.1 Multi-Platform Support
**Priority:** High  
**Effort:** High

```php
// Platform configurations
wp_rm_survey_platforms
    - platform_id
    - platform_name       // Qualtrics|SurveyMonkey|Typeform
    - api_key
    - webhook_secret
    - default_parameters
    - status_mappings
```

**Supported Platforms:**
- Qualtrics
- SurveyMonkey
- Typeform
- Google Forms
- Cint
- Lucid
- Custom API

#### 3.2 Webhook System
**Priority:** Medium  
**Effort:** Medium

```php
// Webhook configurations
wp_rm_survey_webhooks
    - survey_id
    - event_type          // completed|approved|rejected
    - target_url
    - secret_key
    - retry_count
    - last_triggered
```

**Events:**
- Survey completed
- Response approved
- Response rejected
- User registered
- Payment processed

#### 3.3 API Development
**Priority:** High  
**Effort:** High

```php
// REST API v2
Namespace: rm-panel/v2

Endpoints:
POST   /surveys                    // Create survey
GET    /surveys                    // List surveys
GET    /surveys/{id}              // Get survey
PUT    /surveys/{id}              // Update survey
DELETE /surveys/{id}              // Delete survey

POST   /surveys/{id}/responses    // Submit response
GET    /surveys/{id}/responses    // List responses
GET    /surveys/{id}/stats        // Survey stats

GET    /users/{id}/surveys        // User's surveys
GET    /users/{id}/earnings       // User's earnings
GET    /users/{id}/stats          // User stats

// Authentication
- API Key authentication
- OAuth2 support
- Rate limiting (100 req/hour)
- Webhook signatures
```

### Phase 4: Advanced Features (Q4 2026)

#### 4.1 Quota Management
**Priority:** Medium  
**Effort:** Medium

```php
// Quota system
_rm_survey_quota_enabled        // boolean
_rm_survey_quota_total          // integer
_rm_survey_quota_per_day        // integer
_rm_survey_quota_by_demographic // array
[
    'gender' => ['male' => 50, 'female' => 50],
    'age' => ['18-24' => 25, '25-34' => 25, '35-44' => 25, '45+' => 25],
    'country' => ['US' => 100, 'UK' => 50, 'Other' => 50]
]
```

**Features:**
- Real-time quota tracking
- Auto-close when full
- Demographic balancing
- Overquota handling
- Quota alerts

#### 4.2 Survey Templates
**Priority:** Low  
**Effort:** Medium

```php
wp_rm_survey_templates
    - template_id
    - template_name
    - category
    - json_config
    - preview_image
```

**Template Categories:**
- Customer Satisfaction
- Market Research
- Employee Feedback
- Product Testing
- Brand Awareness
- Exit Surveys
- NPS Surveys

#### 4.3 A/B Testing
**Priority:** Low  
**Effort:** High

```php
wp_rm_survey_variants
    - variant_id
    - survey_id
    - variant_name
    - traffic_percentage
    - url_override
    - parameter_override
```

**Features:**
- Multiple URL variants
- Traffic splitting
- Performance comparison
- Automatic winner selection
- Statistical significance testing

#### 4.4 Schedule & Automation
**Priority:** Medium  
**Effort:** Medium

```php
// Scheduled surveys
_rm_survey_schedule_enabled     // boolean
_rm_survey_schedule_start       // datetime
_rm_survey_schedule_end         // datetime
_rm_survey_schedule_days        // array ['monday','wednesday']
_rm_survey_schedule_times       // array ['09:00','14:00','18:00']
```

**Features:**
- Auto-activate/deactivate
- Recurring schedules
- Time-zone support
- Blackout periods
- Holiday calendar integration

---

## Technical Debt

### Current Issues

#### High Priority

**1. Survey Parameters Validation**
```php
// Issue: Weak validation in save_meta_data()
// Location: class-survey-module.php line ~850
// Impact: Malformed data can be saved

// Current:
$parameters[] = $param;  // No validation

// Needed:
if (!$this->validate_parameter($param)) {
    continue;
}

// Add method:
private function validate_parameter($param) {
    $valid_fields = [...];
    return (
        !empty($param['field']) &&
        in_array($param['field'], $valid_fields) &&
        !empty($param['variable'])
    );
}
```

**2. SQL Injection Risk in get_survey_stats()**
```php
// Issue: Direct variable interpolation
// Location: class-survey-tracking.php line ~180
// Risk: Medium (only admin access, but still risky)

// Current:
$stats = $wpdb->get_row($wpdb->prepare(
    "SELECT ... WHERE survey_id = %d", $survey_id
));

// Actually safe, but inconsistent with other methods
// Recommendation: Add phpdoc and input validation
```

**3. Race Condition in start_survey()**
```php
// Issue: UNIQUE constraint can cause errors on concurrent starts
// Location: class-survey-tracking.php line ~350
// Impact: Duplicate key error on rapid clicks

// Solution:
$existing = $this->get_user_survey_response($user_id, $survey_id);
if ($existing) {
    if ($existing->status === 'completed') {
        // Check allow_multiple
    } else {
        // Return existing response_id
        return $existing->id;
    }
} else {
    // Insert with error handling
    $result = $wpdb->insert(...);
    if ($result === false) {
        if ($wpdb->last_error contains 'Duplicate') {
            // Get the existing record
            $existing = $this->get_user_survey_response($user_id, $survey_id);
            return $existing->id;
        }
    }
}
```

#### Medium Priority

**4. Hard-coded Country List**
```php
// Issue: Static country array in get_countries_list()
// Location: class-survey-module.php line ~430
// Maintenance: Requires manual updates for new countries

// Solution: Use WordPress's built-in list or external library
// Option 1: wp_countries() if available
// Option 2: Store in database
// Option 3: Use ISO 3166-1 package
```

**5. Missing Transactional Integrity**
```php
// Issue: Multi-table updates without transactions
// Location: approve_survey_response() line ~620
// Risk: Partial updates if error occurs

// Solution:
$wpdb->query('START TRANSACTION');
try {
    // Update response
    $wpdb->update(...);
    
    // Update user meta
    update_user_meta(...);
    
    // Send email
    wp_mail(...);
    
    $wpdb->query('COMMIT');
} catch (Exception $e) {
    $wpdb->query('ROLLBACK');
    return new WP_Error(...);
}
```

**6. Memory Issues with Large Datasets**
```php
// Issue: get_available_surveys() loads all surveys into memory
// Location: class-survey-tracking.php line ~750
// Impact: Performance degrades with 1000+ surveys

// Solution: Implement pagination
public function get_available_surveys($user_id, $args = []) {
    $defaults = [
        'per_page' => 20,
        'page' => 1,
        'orderby' => 'start_date',
        'order' => 'DESC'
    ];
    $args = wp_parse_args($args, $defaults);
    
    // Use WP_Query with pagination
    $query_args = [
        'posts_per_page' => $args['per_page'],
        'paged' => $args['page'],
        // ... rest of query
    ];
}
```

#### Low Priority

**7. Inconsistent Error Handling**
```php
// Issue: Mix of WP_Error, false returns, and exceptions
// Impact: Difficult error handling for developers

// Standardize to WP_Error everywhere
// Add helper method:
private function error($code, $message, $data = []) {
    return new WP_Error($code, $message, $data);
}
```

**8. Missing Type Hints**
```php
// Issue: No type declarations (PHP 7.0+)
// Impact: Runtime type errors, harder to debug

// Add throughout:
public function start_survey(int $user_id, int $survey_id): int|WP_Error
public function get_survey_stats(int $survey_id): ?stdClass
private function validate_parameter(array $param): bool
```

### Code Quality Improvements

#### 1. Extract Repeated Logic

**URL Building Logic**
```php
// Appears in 3 places: widget, shortcode, redirect
// Extract to helper:
class RM_Survey_URL_Builder {
    public static function build($survey_id, $user_id = null) {
        $base_url = get_post_meta($survey_id, '_rm_survey_url', true);
        $parameters = get_post_meta($survey_id, '_rm_survey_parameters', true);
        
        // ... centralized logic
        
        return $final_url;
    }
}
```

**Status Label Mapping**
```php
// Appears in 4+ places
// Extract to helper:
class RM_Survey_Status {
    const LABELS = [
        'success' => 'Successful',
        'quota_complete' => 'Quota Full',
        'disqualified' => 'Disqualified',
        // ...
    ];
    
    public static function label($status) {
        return self::LABELS[$status] ?? $status;
    }
    
    public static function badge_class($status) {
        return 'status-' . sanitize_html_class($status);
    }
}
```

#### 2. Separate Concerns

**Survey Module is Too Large (2000+ lines)**

Split into:
```
class-survey-module.php              (Core CPT registration)
class-survey-meta-boxes.php          (Meta box rendering)
class-survey-admin-columns.php       (Admin list table)
class-survey-redirects.php           (URL handling)
class-survey-validation.php          (Input validation)
```

#### 3. Add Caching

**Expensive Queries**
```php
// Cache survey stats (changes infrequently)
public function get_survey_stats($survey_id) {
    $cache_key = 'rm_survey_stats_' . $survey_id;
    $stats = wp_cache_get($cache_key, 'rm_surveys');
    
    if (false === $stats) {
        $stats = $this->query_survey_stats($survey_id);
        wp_cache_set($cache_key, $stats, 'rm_surveys', HOUR_IN_SECONDS);
    }
    
    return $stats;
}

// Clear cache on response update
do_action('rm_survey_response_updated', $survey_id);
add_action('rm_survey_response_updated', function($survey_id) {
    wp_cache_delete('rm_survey_stats_' . $survey_id, 'rm_surveys');
});
```

#### 4. Documentation Standards

**PHPDoc Requirements**
```php
/**
 * Start survey tracking for a user
 *
 * Creates a new response record or updates existing started response.
 * Validates that user hasn't already completed survey unless multiple
 * submissions are allowed.
 *
 * @since 1.0.0
 * @since 1.1.0 Added country tracking
 *
 * @param int $user_id   WordPress user ID
 * @param int $survey_id Survey post ID
 *
 * @return int|WP_Error Response ID on success, WP_Error on failure
 *
 * @throws WP_Error If survey doesn't exist
 * @throws WP_Error If already completed and multiple not allowed
 */
public function start_survey(int $user_id, int $survey_id) {}
```

---

## Performance Optimization

### Database Optimization

#### 1. Add Missing Indexes

```sql
-- Add composite indexes for common queries
ALTER TABLE wp_rm_survey_responses 
ADD INDEX idx_user_status (user_id, status),
ADD INDEX idx_survey_status (survey_id, status),
ADD INDEX idx_approval_status (approval_status, approval_date),
ADD INDEX idx_country_completion (country, completion_status);

-- Add covering index for stats query
ALTER TABLE wp_rm_survey_responses
ADD INDEX idx_stats_coverage (survey_id, status, completion_status);
```

#### 2. Query Optimization

**Before:**
```php
// Loads all user meta
$total_earned = get_user_meta($user_id, 'rm_survey_total_earned', true);

// Separate queries for each survey
foreach ($surveys as $survey) {
    $amount = get_post_meta($survey->ID, '_rm_survey_amount', true);
    $type = get_post_meta($survey->ID, '_rm_survey_type', true);
}
```

**After:**
```php
// Batch fetch meta
$user_meta = get_user_meta($user_id);
$total_earned = $user_meta['rm_survey_total_earned'][0] ?? 0;

// One query for all survey meta
$survey_ids = wp_list_pluck($surveys, 'ID');
$meta_data = $wpdb->get_results($wpdb->prepare(
    "SELECT post_id, meta_key, meta_value 
     FROM {$wpdb->postmeta}
     WHERE post_id IN (" . implode(',', array_map('intval', $survey_ids)) . ")
     AND meta_key IN ('_rm_survey_amount', '_rm_survey_type')"
));

// Process into array
$survey_meta = [];
foreach ($meta_data as $meta) {
    $survey_meta[$meta->post_id][$meta->meta_key] = $meta->meta_value;
}
```

#### 3. Implement Object Caching

```php
// Use persistent object cache (Redis/Memcached)
class RM_Survey_Cache {
    private static $group = 'rm_surveys';
    
    public static function get_survey($survey_id) {
        $cache_key = 'survey_' . $survey_id;
        $survey = wp_cache_get($cache_key, self::$group);
        
        if (false === $survey) {
            $survey = get_post($survey_id);
            wp_cache_set($cache_key, $survey, self::$group, DAY_IN_SECONDS);
        }
        
        return $survey;
    }
    
    public static function get_available_surveys($user_id) {
        $cache_key = 'available_' . $user_id;
        $surveys = wp_cache_get($cache_key, self::$group);
        
        if (false === $surveys) {
            $surveys = $this->query_available_surveys($user_id);
            wp_cache_set($cache_key, $surveys, self::$group, 5 * MINUTE_IN_SECONDS);
        }
        
        return $surveys;
    }
    
    public static function clear($survey_id = null) {
        if ($survey_id) {
            wp_cache_delete('survey_' . $survey_id, self::$group);
        } else {
            wp_cache_flush();
        }
    }
}
```

### Frontend Optimization

#### 1. Lazy Load Images

```php
// Add loading attribute to survey thumbnails
add_filter('post_thumbnail_html', function($html, $post_id) {
    if (get_post_type($post_id) === 'rm_survey') {
        $html = str_replace('<img', '<img loading="lazy"', $html);
    }
    return $html;
}, 10, 2);
```

#### 2. Conditional Asset Loading

```php
// Only load CSS/JS when needed
public function enqueue_frontend_scripts() {
    // Check if survey content is present
    if (!$this->has_survey_content()) {
        return;
    }
    
    wp_enqueue_style('rm-survey-accordion-tabs');
    wp_enqueue_script('rm-survey-tracking');
}

private function has_survey_content() {
    return (
        is_singular('rm_survey') ||
        is_post_type_archive('rm_survey') ||
        has_shortcode(get_post()->post_content ?? '', 'rm_survey_tabs')
    );
}
```

#### 3. CSS Optimization

```css
/* Use CSS containment for accordion items */
.rm-survey-accordion-item {
    contain: layout style paint;
}

/* Use will-change for animated elements */
.rm-accordion-tab-content.active {
    will-change: opacity, transform;
}

/* Optimize font loading */
@font-face {
    font-family: 'System-UI';
    font-display: swap;
    src: local('System UI');
}
```

### AJAX Optimization

#### 1. Debounce Requests

```javascript
// Debounce parameter preview updates
let previewTimeout;
function updatePreviewUrl() {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(() => {
        // Actually update preview
    }, 300);
}
```

#### 2. Batch Requests

```javascript
// Instead of one request per action
// Batch multiple operations
const batchQueue = [];

function queueOperation(operation) {
    batchQueue.push(operation);
    
    if (batchQueue.length >= 5) {
        processBatch();
    }
}

function processBatch() {
    if (batchQueue.length === 0) return;
    
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'rm_batch_operations',
            operations: batchQueue
        },
        success: function(response) {
            batchQueue = [];
        }
    });
}
```

---

## Security Considerations

### Current Security Measures

#### 1. Nonce Verification
```php
// All AJAX actions use nonces
wp_nonce_field('rm_survey_meta_box', 'rm_survey_meta_box_nonce');
check_ajax_referer('rm_survey_nonce', 'nonce');
check_ajax_referer('rm_approval_nonce', 'nonce');
```

#### 2. Capability Checks
```php
// Admin actions require manage_options
if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
}

// Post editing uses WordPress defaults
if (!current_user_can('edit_post', $post_id)) {
    return;
}
```

#### 3. Input Sanitization
```php
// All inputs are sanitized
$survey_type = sanitize_text_field($_POST['rm_survey_type']);
$survey_url = esc_url_raw($_POST['rm_survey_url']);
$survey_amount = floatval($_POST['rm_survey_amount']);
```

#### 4. SQL Injection Prevention
```php
// All queries use $wpdb->prepare
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE user_id = %d AND survey_id = %d",
    $user_id,
    $survey_id
));
```

#### 5. Callback Token System
```php
// SHA-256 hashing with wp_salt
$token = wp_hash($survey_id . wp_salt('auth'));

// Constant-time comparison
if (!hash_equals($expected_token, $provided_token)) {
    wp_die('Invalid token');
}
```

### Security Enhancements Needed

#### 1. Rate Limiting

```php
class RM_Survey_Rate_Limiter {
    private static $limits = [
        'survey_start' => ['count' => 10, 'period' => 60],      // 10 per minute
        'ajax_request' => ['count' => 100, 'period' => 3600],  // 100 per hour
    ];
    
    public static function check($action, $identifier = null) {
        $identifier = $identifier ?: self::get_client_identifier();
        $key = "rate_limit_{$action}_{$identifier}";
        
        $count = get_transient($key) ?: 0;
        $limit = self::$limits[$action];
        
        if ($count >= $limit['count']) {
            return new WP_Error('rate_limit', 'Too many requests');
        }
        
        set_transient($key, $count + 1, $limit['period']);
        return true;
    }
    
    private static function get_client_identifier() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_id = get_current_user_id();
        return md5($ip . $user_id);
    }
}

// Use in AJAX handlers
public function ajax_start_survey() {
    $check = RM_Survey_Rate_Limiter::check('survey_start');
    if (is_wp_error($check)) {
        wp_send_json_error($check->get_error_message());
    }
    
    // ... rest of code
}
```

#### 2. CSRF Protection Enhancement

```php
// Add action-specific nonces
public function generate_action_nonce($action, $survey_id, $user_id) {
    return wp_hash($action . $survey_id . $user_id . wp_salt('auth'));
}

// Verify with context
public function verify_action_nonce($nonce, $action, $survey_id, $user_id) {
    $expected = $this->generate_action_nonce($action, $survey_id, $user_id);
    return hash_equals($expected, $nonce);
}
```

#### 3. Content Security Policy

```php
// Add CSP headers
add_action('send_headers', function() {
    if (is_singular('rm_survey') || is_post_type_archive('rm_survey')) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com;");
    }
});
```

#### 4. XSS Prevention

```php
// Escape all output
echo esc_html($survey->post_title);
echo esc_url($survey_url);
echo esc_attr($meta_value);

// Use wp_kses for rich content
echo wp_kses_post($survey_description);

// Never trust user input
$allowed_statuses = ['success', 'quota_complete', 'disqualified'];
if (!in_array($status, $allowed_statuses)) {
    return new WP_Error('invalid_status', 'Invalid status');
}
```

#### 5. Audit Logging

```php
class RM_Survey_Audit_Log {
    public static function log($action, $survey_id, $user_id, $details = []) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'rm_audit_log',
            [
                'action' => $action,
                'survey_id' => $survey_id,
                'user_id' => $user_id,
                'details' => json_encode($details),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'created_at' => current_time('mysql')
            ]
        );
    }
}

// Log sensitive actions
RM_Survey_Audit_Log::log('survey_approved', $survey_id, $admin_id);
RM_Survey_Audit_Log::log('survey_rejected', $survey_id, $admin_id);
RM_Survey_Audit_Log::log('callback_received', $survey_id, $user_id);
```

---

## Testing Strategy

### Unit Testing

#### 1. PHPUnit Setup

```php
// tests/test-survey-tracking.php
class Test_RM_Survey_Tracking extends WP_UnitTestCase {
    
    private $tracker;
    private $user_id;
    private $survey_id;
    
    public function setUp() {
        parent::setUp();
        
        $this->tracker = new RM_Panel_Survey_Tracking();
        $this->user_id = $this->factory->user->create();
        $this->survey_id = $this->factory->post->create([
            'post_type' => 'rm_survey'
        ]);
    }
    
    public function test_start_survey_creates_response() {
        $response_id = $this->tracker->start_survey($this->user_id, $this->survey_id);
        
        $this->assertIsInt($response_id);
        $this->assertGreaterThan(0, $response_id);
    }
    
    public function test_start_survey_prevents_duplicate() {
        // First start
        $response_id_1 = $this->tracker->start_survey($this->user_id, $this->survey_id);
        
        // Second start
        $response_id_2 = $this->tracker->start_survey($this->user_id, $this->survey_id);
        
        $this->assertEquals($response_id_1, $response_id_2);
    }
    
    public function test_complete_survey_updates_status() {
        $this->tracker->start_survey($this->user_id, $this->survey_id);
        $result = $this->tracker->complete_survey(
            $this->user_id,
            $this->survey_id,
            'success'
        );
        
        $this->assertNotFalse($result);
        
        $response = $this->tracker->get_user_survey_response(
            $this->user_id,
            $this->survey_id
        );
        
        $this->assertEquals('completed', $response->status);
        $this->assertEquals('success', $response->completion_status);
    }
    
    public function test_complete_survey_prevents_double_completion() {
        $this->tracker->start_survey($this->user_id, $this->survey_id);
        $this->tracker->complete_survey($this->user_id, $this->survey_id, 'success');
        
        $result = $this->tracker->start_survey($this->user_id, $this->survey_id);
        
        $this->assertWPError($result);
        $this->assertEquals('already_completed', $result->get_error_code());
    }
}
```

#### 2. Integration Testing

```php
// tests/test-survey-integration.php
class Test_RM_Survey_Integration extends WP_UnitTestCase {
    
    public function test_survey_creation_flow() {
        // Create survey
        $survey_id = wp_insert_post([
            'post_type' => 'rm_survey',
            'post_title' => 'Test Survey',
            'post_status' => 'publish'
        ]);
        
        // Add meta
        update_post_meta($survey_id, '_rm_survey_type', 'paid');
        update_post_meta($survey_id, '_rm_survey_amount', 5.00);
        update_post_meta($survey_id, '_rm_survey_status', 'active');
        
        // Test visibility
        $tracker = new RM_Panel_Survey_Tracking();
        $available = $tracker->get_available_surveys($this->user_id);
        
        $this->assertContains($survey_id, wp_list_pluck($available, 'ID'));
    }
    
    public function test_callback_url_generation() {
        $callbacks = new RM_Survey_Callbacks();
        $urls = $callbacks->generate_callback_urls($this->survey_id, $this->user_id);
        
        $this->assertArrayHasKey('success', $urls);
        $this->assertArrayHasKey('terminate', $urls);
        $this->assertArrayHasKey('quotafull', $urls);
        
        // Verify URL structure
        $parsed = parse_url($urls['success']);
        $this->assertEquals('/survey-callback/success/', $parsed['path']);
        
        parse_str($parsed['query'], $params);
        $this->assertEquals($this->survey_id, $params['sid']);
        $this->assertEquals($this->user_id, $params['uid']);
        $this->assertNotEmpty($params['token']);
    }
}
```

### Functional Testing

#### 1. Selenium Tests

```javascript
// tests/e2e/survey-workflow.spec.js
describe('Survey Workflow', () => {
    
    beforeEach(() => {
        cy.login('testuser', 'password');
    });
    
    it('should display available surveys', () => {
        cy.visit('/surveys');
        cy.get('.rm-survey-accordion-item').should('be.visible');
        cy.get('.rm-accordion-tab-btn[data-tab="available"]').should('have.class', 'active');
    });
    
    it('should start survey tracking', () => {
        cy.visit('/surveys');
        cy.get('.rm-survey-button').first().click();
        
        // Should be redirected
        cy.url().should('include', 'external-survey-platform.com');
        
        // Check tracking in database
        cy.task('getResponse', {userId: 1, surveyId: 1}).then((response) => {
            expect(response.status).to.equal('started');
        });
    });
    
    it('should handle callback and show thank you page', () => {
        const callbackUrl = `/survey-callback/success/?sid=1&uid=1&token=validtoken`;
        cy.visit(callbackUrl);
        
        cy.url().should('include', '/survey-thank-you/');
        cy.contains('Survey Completed Successfully!').should('be.visible');
    });
    
    it('should show completed survey in history', () => {
        cy.visit('/surveys');
        cy.get('.rm-accordion-tab-btn[data-tab="completed"]').click();
        
        cy.get('.rm-survey-accordion-item').should('be.visible');
        cy.contains('Completed').should('be.visible');
    });
});
```

### Load Testing

#### 1. Apache JMeter Configuration

```xml
<!-- survey-load-test.jmx -->
<ThreadGroup>
    <numThreads>100</numThreads>
    <rampUp>10</rampUp>
    
    <HTTPSamplerProxy>
        <path>/survey-callback/success/</path>
        <method>GET</method>
        <queryString>sid=${surveyId}&amp;uid=${userId}&amp;token=${token}</queryString>
    </HTTPSamplerProxy>
    
    <ResultCollector>
        <saveConfig>
            <time>true</time>
            <latency>true</latency>
            <success>true</success>
        </saveConfig>
    </ResultCollector>
</ThreadGroup>
```

#### 2. Performance Benchmarks

```
Target Metrics:
- Survey listing page: < 500ms load time
- Survey start AJAX: < 200ms response
- Callback processing: < 300ms
- Available surveys query: < 100ms (cached)
- Admin approval page: < 1s load time

Concurrent Users:
- 100 simultaneous survey starts
- 50 simultaneous callbacks
- 20 simultaneous admin actions
```

---

## Deployment Guidelines

### Pre-Deployment Checklist

#### 1. Code Review
- [ ] All functions documented with PHPDoc
- [ ] No debugging code (var_dump, error_log, console.log)
- [ ] All TODO comments resolved or ticketed
- [ ] Security review completed
- [ ] Performance profiling done

#### 2. Database
- [ ] Backup current database
- [ ] Test upgrade script on staging
- [ ] Verify indexes created
- [ ] Check database version updated
- [ ] Test rollback procedure

#### 3. Testing
- [ ] Unit tests pass (100% critical paths)
- [ ] Integration tests pass
- [ ] E2E tests pass on staging
- [ ] Load testing completed
- [ ] Browser compatibility verified (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsiveness tested

#### 4. Assets
- [ ] CSS minified for production
- [ ] JavaScript minified for production
- [ ] Images optimized
- [ ] SVG sprites created
- [ ] Fonts subset and optimized

#### 5. Configuration
- [ ] WP_DEBUG set to false
- [ ] Error logging configured
- [ ] Caching enabled
- [ ] CDN configured (if applicable)
- [ ] Security headers set

### Deployment Steps

#### 1. Backup Current Version
```bash
# Database backup
wp db export backup-$(date +%Y%m%d).sql

# Plugin files backup
cd wp-content/plugins
tar -czf rm-panel-extensions-backup-$(date +%Y%m%d).tar.gz rm-panel-extensions/
```

#### 2. Upload New Version
```bash
# Upload via SFTP or use WP-CLI
wp plugin install rm-panel-extensions.zip --force

# Or via Git
cd wp-content/plugins/rm-panel-extensions
git pull origin production
```

#### 3. Run Database Migrations
```bash
# Deactivate and reactivate to trigger upgrade
wp plugin deactivate rm-panel-extensions
wp plugin activate rm-panel-extensions

# Or manually via WP admin
# Navigate to Plugins → Deactivate → Activate
```

#### 4. Verify Installation
```bash
# Check plugin version
wp plugin list | grep rm-panel-extensions

# Verify database version
wp option get rm_panel_survey_db_version

# Test core functionality
wp eval 'echo class_exists("RM_Panel_Survey_Module") ? "OK" : "FAIL";'
```

#### 5. Clear Caches
```bash
# WordPress object cache
wp cache flush

# Rewrite rules
wp rewrite flush

# If using W3 Total Cache or similar
wp w3-total-cache flush all

# If using Redis
redis-cli FLUSHALL
```

#### 6. Smoke Testing
```
Manual checks:
1. Create new survey → Save → Verify meta saved
2. View survey on frontend → Click start → Verify redirect
3. Test callback URL → Verify status updated
4. Check admin approval page → Verify list displays
5. Test accordion widget → Verify tabs work
```

### Rollback Procedure

#### If Issues Occur

```bash
# 1. Deactivate plugin immediately
wp plugin deactivate rm-panel-extensions

# 2. Restore previous version
cd wp-content/plugins
rm -rf rm-panel-extensions
tar -xzf rm-panel-extensions-backup-YYYYMMDD.tar.gz

# 3. Restore database if needed
wp db import backup-YYYYMMDD.sql

# 4. Reactivate plugin
wp plugin activate rm-panel-extensions

# 5. Clear caches
wp cache flush
wp rewrite flush
```

### Post-Deployment

#### 1. Monitor Error Logs
```bash
# Watch WordPress debug log
tail -f wp-content/debug.log

# Watch PHP error log
tail -f /var/log/php/error.log

# Watch server error log
tail -f /var/log/apache2/error.log
```

#### 2. Monitor Performance
- Check response times (should be < targets)
- Monitor database query count
- Check memory usage
- Verify caching effectiveness

#### 3. User Communication
- Announce new features
- Update documentation
- Send email to admins
- Monitor support tickets

---

## Appendix

### A. Database Diagram

```
┌─────────────────────────────────────────┐
│ wp_posts (Surveys)                      │
├─────────────────────────────────────────┤
│ ID                                      │
│ post_title                              │
│ post_content (description)              │
│ post_excerpt                            │
│ post_status                             │
│ post_type = 'rm_survey'                 │
└──────────────┬──────────────────────────┘
               │
               │ 1:N
               │
┌──────────────▼──────────────────────────┐
│ wp_postmeta (Survey Settings)           │
├─────────────────────────────────────────┤
│ _rm_survey_type                         │
│ _rm_survey_amount                       │
│ _rm_survey_url                          │
│ _rm_survey_parameters                   │
│ _rm_survey_status                       │
│ _rm_survey_location_type                │
│ _rm_survey_countries                    │
│ ... (20+ meta keys)                     │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ wp_users                                │
├─────────────────────────────────────────┤
│ ID                                      │
│ user_login                              │
│ user_email                              │
│ display_name                            │
└──────────────┬──────────────────────────┘
               │
               │ 1:N
               │
┌──────────────▼──────────────────────────┐
│ wp_rm_survey_responses                  │
├─────────────────────────────────────────┤
│ id (PK)                                 │
│ user_id (FK → wp_users.ID)              │
│ survey_id (FK → wp_posts.ID)            │
│ status                                  │
│ completion_status                       │
│ start_time                              │
│ completion_time                         │
│ approval_status                         │
│ approved_by (FK → wp_users.ID)          │
│ approval_date                           │
│ country                                 │
│ ... (15+ columns)                       │
│ UNIQUE(user_id, survey_id)              │
└─────────────────────────────────────────┘
```

### B. Action Hooks Reference

```php
// Survey lifecycle
do_action('rm_survey_created', $survey_id);
do_action('rm_survey_updated', $survey_id);
do_action('rm_survey_deleted', $survey_id);

// Response tracking
do_action('rm_survey_started', $user_id, $survey_id, $response_id);
do_action('rm_survey_completed', $user_id, $survey_id, $completion_status, $response_data);
do_action('rm_survey_callback_processed', $survey_id, $user_id, $status);

// Approval workflow
do_action('rm_survey_approved', $user_id, $survey_id, $response_id);
do_action('rm_survey_rejected', $user_id, $survey_id, $response_id);
do_action('rm_survey_reward_earned', $user_id, $survey_id, $amount);

// Module initialization
do_action('rm_panel_extensions_modules_loaded');
do_action('rm_panel_survey_module_loaded');
```

### C. Filter Hooks Reference

```php
// Survey query modification
apply_filters('rm_panel_available_surveys_args', $args, $user_id);
apply_filters('rm_panel_survey_redirect_url', $redirect_url, $survey_id, $query_params);

// Content modification
apply_filters('rm_panel_survey_title', $title, $survey_id);
apply_filters('rm_panel_survey_content', $content, $survey_id);
apply_filters('rm_panel_survey_excerpt', $excerpt, $survey_id);

// Meta data
apply_filters('rm_panel_survey_meta_value', $value, $meta_key, $survey_id);

// Modules
apply_filters('rm_panel_extensions_modules', $modules);
```

### D. JavaScript Events Reference

```javascript
// Tab switching
jQuery(document).trigger('rm-tab-changed', [tabId]);

// Accordion toggle
jQuery(document).trigger('rm-accordion-toggled', [itemId, isOpen]);

// Survey start
jQuery(document).trigger('rm-survey-started', [surveyId]);

// Modal events
jQuery(document).trigger('rm-modal-opened', [modalId]);
jQuery(document).trigger('rm-modal-closed', [modalId]);
```

### E. Constants Reference

```php
// Plugin constants
RM_PANEL_EXT_VERSION                    // '1.0.0'
RM_PANEL_EXT_FILE                       // Plugin main file path
RM_PANEL_EXT_PLUGIN_DIR                 // Plugin directory path
RM_PANEL_EXT_PLUGIN_URL                 // Plugin URL
RM_PANEL_EXT_PLUGIN_BASENAME            // Plugin basename

// Status constants (RM_Panel_Survey_Tracking)
RM_Panel_Survey_Tracking::STATUS_STARTED
RM_Panel_Survey_Tracking::STATUS_COMPLETED
RM_Panel_Survey_Tracking::STATUS_SUCCESS
RM_Panel_Survey_Tracking::STATUS_QUOTA_COMPLETE
RM_Panel_Survey_Tracking::STATUS_DISQUALIFIED
RM_Panel_Survey_Tracking::STATUS_ABANDONED

// Post type
RM_Panel_Survey_Module::POST_TYPE       // 'rm_survey'
RM_Panel_Survey_Module::TAXONOMY        // 'survey_category'
RM_Panel_Survey_Module::USER_CATEGORY_TAXONOMY  // 'survey_user_category'
```

---

## Document Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | Nov 2025 | Sanil T | Initial documentation |
| 2.0.0 | TBD | TBD | Phase 1 enhancements |
| 2.1.0 | TBD | TBD | Phase 2 features |

---

**END OF DOCUMENTATION**

For questions or clarifications, contact: [dev@researchandmetric.com](mailto:dev@researchandmetric.com)
