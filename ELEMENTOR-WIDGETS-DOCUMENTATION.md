# RM Panel Extensions - Elementor Widgets Documentation

## 📋 Overview

This document provides comprehensive documentation for all Elementor widgets included in the RM Panel Extensions plugin.

**Current Version:** 1.2.0  
**Elementor Compatibility:** 3.0+  
**WordPress Version:** 5.0+

---

## 🎨 Available Widgets

### 1. Login Widget
### 2. Survey Listing Widget
### 3. Survey Accordion Widget
### 4. Profile Picture Upload Widget

---

## 🔌 Widget Module Architecture

### Module Structure
```
modules/elementor/
├── class-elementor-module.php          # Main module controller
├── widgets/
│   ├── login-widget.php                # Login form widget
│   ├── survey-listing-widget.php       # Survey listing widget
│   ├── survey-accordion-widget.php     # Accordion display widget
│   └── profile-picture-widget.php      # Profile upload widget
├── templates/
│   ├── login-form.php                  # Login form template
│   └── survey-templates/               # Survey display templates
└── assets/
    ├── css/
    │   ├── elementor-widgets.css       # Widget styles
    │   └── survey-styles.css           # Survey-specific styles
    └── js/
        ├── elementor-widgets.js        # Widget scripts
        └── survey-scripts.js           # Survey-specific scripts
```

---

## 📦 Module Integration

### How Elementor Module Loads

```php
// In rm-panel-extensions.php

// Module loads conditionally
if ( did_action( 'elementor/loaded' ) ) {
    $this->load_module_file(
        'elementor-widgets',
        'modules/elementor/class-elementor-module.php',
        'RM_Panel_Elementor_Module',
        $core_modules
    );
}

// Module instantiation
if ( did_action( 'elementor/loaded' ) ) {
    if ( isset( $this->modules['elementor-widgets'] ) && 
         class_exists( $this->modules['elementor-widgets'] ) ) {
        new $this->modules['elementor-widgets']();
    }
}
```

### Module Class Structure

```php
/**
 * Elementor Module Class
 * 
 * @package RM_Panel_Extensions
 * @since 1.0.0
 */
class RM_Panel_Elementor_Module {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'add_widget_categories' ) );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets( $widgets_manager ) {
        // Load widget files
        require_once RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/login-widget.php';
        require_once RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/survey-listing-widget.php';
        require_once RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/survey-accordion-widget.php';
        require_once RM_PANEL_EXT_PLUGIN_DIR . 'modules/elementor/widgets/profile-picture-widget.php';
        
        // Register widgets
        $widgets_manager->register( new \RM_Panel_Login_Widget() );
        $widgets_manager->register( new \RM_Panel_Survey_Listing_Widget() );
        $widgets_manager->register( new \RM_Panel_Survey_Accordion_Widget() );
        $widgets_manager->register( new \RM_Panel_Profile_Picture_Widget() );
    }
    
    /**
     * Add widget categories
     */
    public function add_widget_categories( $elements_manager ) {
        $elements_manager->add_category(
            'rm-panel-widgets',
            array(
                'title' => __( 'RM Panel Widgets', 'rm-panel-extensions' ),
                'icon'  => 'fa fa-plug',
            )
        );
    }
}
```

---

## 🔐 1. Login Widget

### Overview
Custom login widget with role-based redirection, WPML support, and customizable styling.

### Widget Configuration

```php
/**
 * Login Widget Class
 */
class RM_Panel_Login_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm_login_widget';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __( 'Login Form', 'rm-panel-extensions' );
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-lock-user';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return array( 'rm-panel-widgets' );
    }
}
```

### Control Settings

#### Content Tab

**Login Form Settings:**
```php
$this->add_control(
    'form_title',
    array(
        'label'       => __( 'Form Title', 'rm-panel-extensions' ),
        'type'        => \Elementor\Controls_Manager::TEXT,
        'default'     => __( 'Login to Your Account', 'rm-panel-extensions' ),
        'placeholder' => __( 'Enter form title', 'rm-panel-extensions' ),
    )
);

$this->add_control(
    'show_remember',
    array(
        'label'        => __( 'Show Remember Me', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);

$this->add_control(
    'show_lost_password',
    array(
        'label'        => __( 'Show Lost Password Link', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);
```

**Redirect Settings:**
```php
$this->add_control(
    'redirect_type',
    array(
        'label'   => __( 'Redirect After Login', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'dashboard',
        'options' => array(
            'dashboard'  => __( 'Dashboard', 'rm-panel-extensions' ),
            'custom'     => __( 'Custom URL', 'rm-panel-extensions' ),
            'same_page'  => __( 'Same Page', 'rm-panel-extensions' ),
            'role_based' => __( 'Role-Based', 'rm-panel-extensions' ),
        ),
    )
);

// Role-based redirects
$this->add_control(
    'admin_redirect',
    array(
        'label'       => __( 'Administrator Redirect', 'rm-panel-extensions' ),
        'type'        => \Elementor\Controls_Manager::URL,
        'placeholder' => home_url( '/admin-dashboard/' ),
        'condition'   => array(
            'redirect_type' => 'role_based',
        ),
    )
);

$this->add_control(
    'subscriber_redirect',
    array(
        'label'       => __( 'Subscriber Redirect', 'rm-panel-extensions' ),
        'type'        => \Elementor\Controls_Manager::URL,
        'placeholder' => home_url( '/my-dashboard/' ),
        'condition'   => array(
            'redirect_type' => 'role_based',
        ),
    )
);
```

#### Style Tab

**Form Styling:**
```php
$this->add_control(
    'form_background_color',
    array(
        'label'     => __( 'Background Color', 'rm-panel-extensions' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#ffffff',
        'selectors' => array(
            '{{WRAPPER}} .rm-login-form' => 'background-color: {{VALUE}}',
        ),
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Typography::get_type(),
    array(
        'name'     => 'input_typography',
        'label'    => __( 'Input Typography', 'rm-panel-extensions' ),
        'selector' => '{{WRAPPER}} .rm-login-form input[type="text"], {{WRAPPER}} .rm-login-form input[type="password"]',
    )
);

$this->add_control(
    'button_background_color',
    array(
        'label'     => __( 'Button Background', 'rm-panel-extensions' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#0073aa',
        'selectors' => array(
            '{{WRAPPER}} .rm-login-submit' => 'background-color: {{VALUE}}',
        ),
    )
);
```

### Usage Example

**Elementor Editor:**
1. Drag "Login Form" widget onto canvas
2. Configure settings in left panel:
   - Set form title
   - Enable/disable remember me
   - Choose redirect type
   - Configure role-based redirects
3. Style the form:
   - Colors, typography, spacing
   - Button styles
4. Save and preview

**Shortcode (Alternative):**
```php
[rm_login_form redirect="dashboard"]
```

### WPML Integration

```php
// Make login widget translatable
public function get_wpml_support() {
    return array(
        'form_title',
        'button_text',
        'lost_password_text',
    );
}
```

---

## 📊 2. Survey Listing Widget

### Overview
Display surveys in various layouts with filtering, pagination, and user-specific controls.

### Widget Features
- Multiple layout options (grid, list, cards)
- Survey filtering by category
- Status-based display (active/completed)
- User progress tracking
- Responsive design
- AJAX pagination

### Control Settings

#### Content Tab

**Query Settings:**
```php
$this->add_control(
    'posts_per_page',
    array(
        'label'   => __( 'Surveys Per Page', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::NUMBER,
        'default' => 6,
        'min'     => 1,
        'max'     => 50,
    )
);

$this->add_control(
    'survey_categories',
    array(
        'label'    => __( 'Survey Categories', 'rm-panel-extensions' ),
        'type'     => \Elementor\Controls_Manager::SELECT2,
        'multiple' => true,
        'options'  => $this->get_survey_categories(),
        'default'  => array(),
    )
);

$this->add_control(
    'show_completed',
    array(
        'label'        => __( 'Show Completed Surveys', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'no',
    )
);

$this->add_control(
    'order_by',
    array(
        'label'   => __( 'Order By', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'date',
        'options' => array(
            'date'       => __( 'Date', 'rm-panel-extensions' ),
            'title'      => __( 'Title', 'rm-panel-extensions' ),
            'modified'   => __( 'Last Modified', 'rm-panel-extensions' ),
            'menu_order' => __( 'Menu Order', 'rm-panel-extensions' ),
        ),
    )
);
```

**Display Settings:**
```php
$this->add_control(
    'layout',
    array(
        'label'   => __( 'Layout', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'grid',
        'options' => array(
            'grid'  => __( 'Grid', 'rm-panel-extensions' ),
            'list'  => __( 'List', 'rm-panel-extensions' ),
            'cards' => __( 'Cards', 'rm-panel-extensions' ),
        ),
    )
);

$this->add_responsive_control(
    'columns',
    array(
        'label'           => __( 'Columns', 'rm-panel-extensions' ),
        'type'            => \Elementor\Controls_Manager::SELECT,
        'default'         => '3',
        'tablet_default'  => '2',
        'mobile_default'  => '1',
        'options'         => array(
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '6' => '6',
        ),
        'condition'       => array(
            'layout' => 'grid',
        ),
    )
);

$this->add_control(
    'show_image',
    array(
        'label'        => __( 'Show Featured Image', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);

$this->add_control(
    'show_excerpt',
    array(
        'label'        => __( 'Show Excerpt', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);

$this->add_control(
    'excerpt_length',
    array(
        'label'     => __( 'Excerpt Length', 'rm-panel-extensions' ),
        'type'      => \Elementor\Controls_Manager::NUMBER,
        'default'   => 20,
        'condition' => array(
            'show_excerpt' => 'yes',
        ),
    )
);
```

**Survey Information:**
```php
$this->add_control(
    'show_questions_count',
    array(
        'label'        => __( 'Show Questions Count', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);

$this->add_control(
    'show_duration',
    array(
        'label'        => __( 'Show Duration', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);

$this->add_control(
    'show_earnings',
    array(
        'label'        => __( 'Show Earnings', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);

$this->add_control(
    'show_status',
    array(
        'label'        => __( 'Show Status Badge', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);
```

#### Style Tab

**Card Styling:**
```php
$this->add_control(
    'card_background',
    array(
        'label'     => __( 'Card Background', 'rm-panel-extensions' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#ffffff',
        'selectors' => array(
            '{{WRAPPER}} .rm-survey-card' => 'background-color: {{VALUE}}',
        ),
    )
);

$this->add_group_control(
    \Elementor\Group_Control_Border::get_type(),
    array(
        'name'     => 'card_border',
        'label'    => __( 'Card Border', 'rm-panel-extensions' ),
        'selector' => '{{WRAPPER}} .rm-survey-card',
    )
);

$this->add_responsive_control(
    'card_padding',
    array(
        'label'      => __( 'Card Padding', 'rm-panel-extensions' ),
        'type'       => \Elementor\Controls_Manager::DIMENSIONS,
        'size_units' => array( 'px', 'em', '%' ),
        'selectors'  => array(
            '{{WRAPPER}} .rm-survey-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        ),
    )
);

$this->add_responsive_control(
    'card_spacing',
    array(
        'label'      => __( 'Space Between Cards', 'rm-panel-extensions' ),
        'type'       => \Elementor\Controls_Manager::SLIDER,
        'size_units' => array( 'px' ),
        'range'      => array(
            'px' => array(
                'min' => 0,
                'max' => 100,
            ),
        ),
        'default'    => array(
            'size' => 20,
            'unit' => 'px',
        ),
        'selectors'  => array(
            '{{WRAPPER}} .rm-survey-card' => 'margin-bottom: {{SIZE}}{{UNIT}};',
        ),
    )
);
```

**Button Styling:**
```php
$this->add_control(
    'button_style',
    array(
        'label'   => __( 'Button Style', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'filled',
        'options' => array(
            'filled'   => __( 'Filled', 'rm-panel-extensions' ),
            'outlined' => __( 'Outlined', 'rm-panel-extensions' ),
            'text'     => __( 'Text Only', 'rm-panel-extensions' ),
        ),
    )
);

$this->add_control(
    'button_background',
    array(
        'label'     => __( 'Button Background', 'rm-panel-extensions' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#0073aa',
        'selectors' => array(
            '{{WRAPPER}} .rm-survey-button' => 'background-color: {{VALUE}}',
        ),
        'condition' => array(
            'button_style' => 'filled',
        ),
    )
);

$this->add_control(
    'button_hover_background',
    array(
        'label'     => __( 'Button Hover Background', 'rm-panel-extensions' ),
        'type'      => \Elementor\Controls_Manager::COLOR,
        'default'   => '#005a87',
        'selectors' => array(
            '{{WRAPPER}} .rm-survey-button:hover' => 'background-color: {{VALUE}}',
        ),
    )
);
```

### Render Method

```php
protected function render() {
    $settings = $this->get_settings_for_display();
    
    // Build query arguments
    $args = array(
        'post_type'      => 'rm_survey',
        'post_status'    => 'publish',
        'posts_per_page' => $settings['posts_per_page'],
        'orderby'        => $settings['order_by'],
        'order'          => $settings['order'],
    );
    
    // Add category filter
    if ( ! empty( $settings['survey_categories'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'survey_category',
                'field'    => 'term_id',
                'terms'    => $settings['survey_categories'],
            ),
        );
    }
    
    // User-specific filtering
    if ( is_user_logged_in() && $settings['show_completed'] !== 'yes' ) {
        $user_id = get_current_user_id();
        $completed_surveys = $this->get_user_completed_surveys( $user_id );
        
        if ( ! empty( $completed_surveys ) ) {
            $args['post__not_in'] = $completed_surveys;
        }
    }
    
    $query = new \WP_Query( $args );
    
    if ( $query->have_posts() ) {
        echo '<div class="rm-survey-listing rm-layout-' . esc_attr( $settings['layout'] ) . '" data-columns="' . esc_attr( $settings['columns'] ) . '">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            $this->render_survey_card( $settings );
        }
        
        echo '</div>';
        
        // Pagination
        if ( $settings['show_pagination'] === 'yes' ) {
            $this->render_pagination( $query );
        }
    } else {
        echo '<p class="rm-no-surveys">' . __( 'No surveys found.', 'rm-panel-extensions' ) . '</p>';
    }
    
    wp_reset_postdata();
}
```

### Usage Example

```php
// In Elementor:
// 1. Drag "Survey Listing" widget
// 2. Configure query (categories, order, count)
// 3. Choose layout (grid/list/cards)
// 4. Set columns and spacing
// 5. Toggle information display
// 6. Style cards and buttons
```

---

## 📑 3. Survey Accordion Widget

### Overview
Display surveys in an accordion/tabs layout with collapsible sections.

### Widget Features
- Accordion or tabs layout
- Category-based grouping
- Icon customization
- Smooth animations
- Responsive design
- WPML support

### Control Settings

```php
$this->add_control(
    'accordion_type',
    array(
        'label'   => __( 'Display Type', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'accordion',
        'options' => array(
            'accordion' => __( 'Accordion', 'rm-panel-extensions' ),
            'tabs'      => __( 'Tabs', 'rm-panel-extensions' ),
        ),
    )
);

$this->add_control(
    'group_by',
    array(
        'label'   => __( 'Group By', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'category',
        'options' => array(
            'category' => __( 'Category', 'rm-panel-extensions' ),
            'status'   => __( 'Status', 'rm-panel-extensions' ),
            'none'     => __( 'No Grouping', 'rm-panel-extensions' ),
        ),
    )
);

$this->add_control(
    'icon_position',
    array(
        'label'   => __( 'Icon Position', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::SELECT,
        'default' => 'left',
        'options' => array(
            'left'  => __( 'Left', 'rm-panel-extensions' ),
            'right' => __( 'Right', 'rm-panel-extensions' ),
        ),
    )
);

$this->add_control(
    'animation_duration',
    array(
        'label'   => __( 'Animation Duration (ms)', 'rm-panel-extensions' ),
        'type'    => \Elementor\Controls_Manager::NUMBER,
        'default' => 300,
        'min'     => 0,
        'max'     => 1000,
        'step'    => 50,
    )
);
```

---

## 📸 4. Profile Picture Upload Widget

### Overview
Allow users to upload and manage their profile pictures with image preview and cropping.

### Widget Features
- Drag & drop upload
- Image preview
- Cropping functionality
- File validation
- AJAX upload
- FluentCRM sync (optional)

### Control Settings

```php
$this->add_control(
    'max_file_size',
    array(
        'label'       => __( 'Max File Size (MB)', 'rm-panel-extensions' ),
        'type'        => \Elementor\Controls_Manager::NUMBER,
        'default'     => 2,
        'min'         => 1,
        'max'         => 10,
        'description' => __( 'Maximum file size for upload', 'rm-panel-extensions' ),
    )
);

$this->add_control(
    'allowed_formats',
    array(
        'label'       => __( 'Allowed Formats', 'rm-panel-extensions' ),
        'type'        => \Elementor\Controls_Manager::SELECT2,
        'multiple'    => true,
        'default'     => array( 'jpg', 'jpeg', 'png' ),
        'options'     => array(
            'jpg'  => 'JPG',
            'jpeg' => 'JPEG',
            'png'  => 'PNG',
            'gif'  => 'GIF',
        ),
        'description' => __( 'Select allowed image formats', 'rm-panel-extensions' ),
    )
);

$this->add_control(
    'enable_cropping',
    array(
        'label'        => __( 'Enable Image Cropping', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
    )
);

$this->add_control(
    'sync_to_fluentcrm',
    array(
        'label'        => __( 'Sync to FluentCRM', 'rm-panel-extensions' ),
        'type'         => \Elementor\Controls_Manager::SWITCHER,
        'label_on'     => __( 'Yes', 'rm-panel-extensions' ),
        'label_off'    => __( 'No', 'rm-panel-extensions' ),
        'return_value' => 'yes',
        'default'      => 'yes',
        'condition'    => array(
            'fluentcrm_active' => 'yes',
        ),
    )
);
```

### JavaScript Handler

```javascript
jQuery(document).ready(function($) {
    $('.rm-profile-picture-upload').on('change', function(e) {
        var file = e.target.files[0];
        var formData = new FormData();
        
        formData.append('action', 'rm_upload_profile_picture');
        formData.append('nonce', rmProfilePicture.nonce);
        formData.append('file', file);
        
        $.ajax({
            url: rmProfilePicture.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('.rm-profile-picture-preview').attr('src', response.data.url);
                    // Show success message
                }
            }
        });
    });
});
```

---

## 🎨 Widget Styling Guidelines

### CSS Structure

```css
/* Widget Container */
.rm-panel-widget {
    padding: 20px;
    background: #fff;
    border-radius: 4px;
}

/* Login Widget */
.rm-login-form {
    max-width: 400px;
    margin: 0 auto;
}

.rm-login-form input[type="text"],
.rm-login-form input[type="password"] {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.rm-login-submit {
    width: 100%;
    padding: 12px;
    background: #0073aa;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

/* Survey Listing */
.rm-survey-listing {
    display: grid;
    gap: 20px;
}

.rm-survey-listing[data-columns="3"] {
    grid-template-columns: repeat(3, 1fr);
}

.rm-survey-card {
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.rm-survey-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Accordion Widget */
.rm-survey-accordion-item {
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.rm-accordion-header {
    padding: 15px;
    background: #f5f5f5;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rm-accordion-content {
    padding: 15px;
    display: none;
}

.rm-accordion-content.active {
    display: block;
}

/* Profile Picture Widget */
.rm-profile-picture-container {
    text-align: center;
}

.rm-profile-picture-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 20px;
    display: block;
}

.rm-profile-upload-area {
    border: 2px dashed #ddd;
    padding: 40px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.rm-profile-upload-area:hover {
    border-color: #0073aa;
    background: #f5f5f5;
}
```

---

## 🔧 Advanced Customization

### Custom Widget Development

```php
/**
 * Create a custom widget
 */
class RM_Custom_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'rm_custom_widget';
    }
    
    public function get_title() {
        return __( 'Custom Widget', 'rm-panel-extensions' );
    }
    
    public function get_icon() {
        return 'eicon-code';
    }
    
    public function get_categories() {
        return array( 'rm-panel-widgets' );
    }
    
    protected function register_controls() {
        // Add controls here
    }
    
    protected function render() {
        // Render widget output
    }
}
```

### Register Custom Widget

```php
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    require_once __DIR__ . '/widgets/custom-widget.php';
    $widgets_manager->register( new \RM_Custom_Widget() );
});
```

---

## 🌍 WPML Integration

### Make Widgets Translatable

```php
/**
 * Register WPML strings
 */
public function register_wpml_strings() {
    if ( function_exists( 'icl_register_string' ) ) {
        icl_register_string(
            'rm-panel-extensions',
            'login_widget_title',
            $this->get_title(),
            false
        );
    }
}

/**
 * Get translated string
 */
public function get_translated_string( $string, $name ) {
    if ( function_exists( 'icl_t' ) ) {
        return icl_t( 'rm-panel-extensions', $name, $string );
    }
    return $string;
}
```

---

## 🚀 Performance Optimization

### Conditional Asset Loading

```php
/**
 * Enqueue widget assets only when needed
 */
public function enqueue_widget_assets() {
    // Check if widget is on page
    if ( ! $this->is_widget_on_page() ) {
        return;
    }
    
    wp_enqueue_style(
        'rm-survey-widget',
        RM_PANEL_EXT_PLUGIN_URL . 'assets/css/survey-widget.css',
        array(),
        RM_PANEL_EXT_VERSION
    );
    
    wp_enqueue_script(
        'rm-survey-widget',
        RM_PANEL_EXT_PLUGIN_URL . 'assets/js/survey-widget.js',
        array( 'jquery' ),
        RM_PANEL_EXT_VERSION,
        true
    );
}
```

---

## 🐛 Troubleshooting

### Common Issues

#### Widget Not Appearing

**Problem:** Widget doesn't show in Elementor panel

**Solution:**
```php
// Check if Elementor is loaded
if ( ! did_action( 'elementor/loaded' ) ) {
    return;
}

// Verify widget registration
add_action( 'elementor/widgets/register', 'register_widgets', 10 );
```

#### Styles Not Applied

**Problem:** Widget styles not loading

**Solution:**
```php
// Ensure CSS is enqueued
add_action( 'wp_enqueue_scripts', function() {
    if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
        wp_enqueue_style( 'rm-widget-styles' );
    }
});
```

#### AJAX Not Working

**Problem:** Profile picture upload fails

**Solution:**
```javascript
// Verify nonce and AJAX URL
console.log('AJAX URL:', rmProfilePicture.ajax_url);
console.log('Nonce:', rmProfilePicture.nonce);

// Check server response
$.ajax({
    // ... ajax settings
    error: function(xhr, status, error) {
        console.error('AJAX Error:', error);
    }
});
```

---

## 📚 Additional Resources

### Official Documentation
- [Elementor Developer Docs](https://developers.elementor.com/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Elementor Widget Development](https://developers.elementor.com/creating-a-new-widget/)

### Code Examples
```php
// Example: Get all active surveys for current user
function get_user_active_surveys() {
    if ( ! is_user_logged_in() ) {
        return array();
    }
    
    $user_id = get_current_user_id();
    
    $args = array(
        'post_type'      => 'rm_survey',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_rm_survey_status',
                'value'   => 'active',
                'compare' => '=',
            ),
        ),
    );
    
    return get_posts( $args );
}
```

---

## ✅ Best Practices

### Widget Development
1. ✅ Use proper naming conventions
2. ✅ Add comprehensive controls
3. ✅ Implement responsive settings
4. ✅ Provide default values
5. ✅ Include help text
6. ✅ Validate user input
7. ✅ Sanitize output
8. ✅ Test with WPML
9. ✅ Optimize performance
10. ✅ Document code

### Performance
- ✅ Conditional asset loading
- ✅ Minify CSS/JS
- ✅ Use caching where appropriate
- ✅ Optimize database queries
- ✅ Lazy load images

### Security
- ✅ Escape all output
- ✅ Sanitize all input
- ✅ Use nonces for AJAX
- ✅ Check capabilities
- ✅ Validate file uploads

---

**Version:** 1.2.0  
**Last Updated:** January 31, 2025  
**Author:** Research and Metric Development Team

---

**Need Help?**
- 📧 Email: support@researchandmetric.com
- 🌐 Website: https://researchandmetric.com
- 📖 Main Documentation: CODE-ORGANIZATION-GUIDE.md
