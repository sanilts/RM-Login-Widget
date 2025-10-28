<?php
/**
 * Profile Picture Widget
 * 
 * Displays user profile picture, name, email, and country with upload/edit functionality
 */

namespace RMPanelExtensions\Modules\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Profile_Picture_Widget extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'rm-profile-picture';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Profile Picture', 'rm-panel-extensions');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-person';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        $settings = get_option('rm_panel_extensions_settings', []);
        $category = isset($settings['custom_widget_category']) ? $settings['custom_widget_category'] : 'RM Panel Widgets';
        return [$category];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['profile', 'picture', 'avatar', 'user', 'photo', 'upload', 'rm panel'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content Settings', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_name',
            [
                'label' => __('Show Full Name', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_email',
            [
                'label' => __('Show Email', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_country',
            [
                'label' => __('Show Country', 'rm-panel-extensions'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'rm-panel-extensions'),
                'label_off' => __('Hide', 'rm-panel-extensions'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'default_avatar',
            [
                'label' => __('Default Avatar', 'rm-panel-extensions'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );

        $this->add_control(
            'upload_button_text',
            [
                'label' => __('Upload Button Text', 'rm-panel-extensions'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Click to Upload Photo', 'rm-panel-extensions'),
            ]
        );

        $this->end_controls_section();

        // Style Section - Profile Picture
        $this->start_controls_section(
            'style_picture_section',
            [
                'label' => __('Profile Picture', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'picture_size',
            [
                'label' => __('Picture Size', 'rm-panel-extensions'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 300,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 150,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-picture-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'picture_border',
                'selector' => '{{WRAPPER}} .rm-profile-picture-image',
            ]
        );

        $this->add_control(
            'picture_border_radius',
            [
                'label' => __('Border Radius', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'unit' => '%',
                    'top' => 50,
                    'right' => 50,
                    'bottom' => 50,
                    'left' => 50,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-picture-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'picture_box_shadow',
                'selector' => '{{WRAPPER}} .rm-profile-picture-image',
            ]
        );

        $this->end_controls_section();

        // Style Section - Text
        $this->start_controls_section(
            'style_text_section',
            [
                'label' => __('Text Styling', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'name_heading',
            [
                'label' => __('Full Name', 'rm-panel-extensions'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'name_typography',
                'selector' => '{{WRAPPER}} .rm-profile-name',
            ]
        );

        $this->add_control(
            'name_color',
            [
                'label' => __('Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'email_heading',
            [
                'label' => __('Email', 'rm-panel-extensions'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'email_typography',
                'selector' => '{{WRAPPER}} .rm-profile-email',
            ]
        );

        $this->add_control(
            'email_color',
            [
                'label' => __('Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-email' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'country_heading',
            [
                'label' => __('Country', 'rm-panel-extensions'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'country_typography',
                'selector' => '{{WRAPPER}} .rm-profile-country',
            ]
        );

        $this->add_control(
            'country_color',
            [
                'label' => __('Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-country' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Container
        $this->start_controls_section(
            'style_container_section',
            [
                'label' => __('Container', 'rm-panel-extensions'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_alignment',
            [
                'label' => __('Alignment', 'rm-panel-extensions'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'rm-panel-extensions'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'rm-panel-extensions'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'rm-panel-extensions'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-picture-container' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => __('Padding', 'rm-panel-extensions'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-picture-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'container_background',
            [
                'label' => __('Background Color', 'rm-panel-extensions'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rm-profile-picture-container' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     */
    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>' . __('Please log in to view your profile.', 'rm-panel-extensions') . '</p>';
            return;
        }

        $settings = $this->get_settings_for_display();
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        // Get user data
        $full_name = $current_user->display_name;
        $email = $current_user->user_email;
        
        // Get country from FluentCRM if available, otherwise from user meta
        $country = '';
        if (class_exists('RM_Panel_FluentCRM_Helper')) {
            $country = \RM_Panel_FluentCRM_Helper::get_contact_country($user_id);
        }
        if (empty($country)) {
            $country = get_user_meta($user_id, 'country', true);
        }

        // Get profile picture URL
        $profile_picture_id = get_user_meta($user_id, 'rm_profile_picture', true);
        if ($profile_picture_id) {
            $profile_picture_url = wp_get_attachment_image_url($profile_picture_id, 'medium');
        } else {
            $profile_picture_url = !empty($settings['default_avatar']['url']) ? 
                $settings['default_avatar']['url'] : 
                get_avatar_url($user_id, ['size' => 150]);
        }

        ?>
        <div class="rm-profile-picture-container">
            <div class="rm-profile-picture-wrapper">
                <div class="rm-profile-picture-image-wrapper">
                    <img src="<?php echo esc_url($profile_picture_url); ?>" 
                         alt="<?php echo esc_attr($full_name); ?>" 
                         class="rm-profile-picture-image"
                         data-user-id="<?php echo esc_attr($user_id); ?>">
                    <div class="rm-profile-picture-overlay">
                        <span class="rm-profile-picture-icon">
                            <i class="eicon-camera"></i>
                        </span>
                        <span class="rm-profile-picture-text">
                            <?php echo esc_html($settings['upload_button_text']); ?>
                        </span>
                    </div>
                </div>

                <div class="rm-profile-info">
                    <?php if ($settings['show_name'] === 'yes') : ?>
                        <div class="rm-profile-name"><?php echo esc_html($full_name); ?></div>
                    <?php endif; ?>

                    <?php if ($settings['show_email'] === 'yes') : ?>
                        <div class="rm-profile-email"><?php echo esc_html($email); ?></div>
                    <?php endif; ?>

                    <?php if ($settings['show_country'] === 'yes' && !empty($country)) : ?>
                        <div class="rm-profile-country">
                            <i class="eicon-globe"></i> <?php echo esc_html($country); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <div class="rm-profile-picture-modal" id="rm-profile-picture-modal">
            <div class="rm-modal-content">
                <div class="rm-modal-header">
                    <h3><?php _e('Update Profile Picture', 'rm-panel-extensions'); ?></h3>
                    <span class="rm-modal-close">&times;</span>
                </div>
                <div class="rm-modal-body">
                    <div class="rm-upload-area" id="rm-upload-area">
                        <div class="rm-upload-icon">
                            <i class="eicon-upload"></i>
                        </div>
                        <p><?php _e('Click to upload or drag and drop', 'rm-panel-extensions'); ?></p>
                        <p class="rm-upload-hint"><?php _e('PNG, JPG, GIF up to 5MB', 'rm-panel-extensions'); ?></p>
                        <input type="file" id="rm-profile-picture-input" accept="image/*" style="display: none;">
                    </div>
                    <div class="rm-preview-area" id="rm-preview-area" style="display: none;">
                        <img src="" alt="Preview" id="rm-preview-image">
                        <div class="rm-preview-actions">
                            <button type="button" class="rm-btn rm-btn-secondary" id="rm-change-image">
                                <?php _e('Change Image', 'rm-panel-extensions'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="rm-modal-footer">
                    <button type="button" class="rm-btn rm-btn-secondary rm-modal-cancel">
                        <?php _e('Cancel', 'rm-panel-extensions'); ?>
                    </button>
                    <button type="button" class="rm-btn rm-btn-primary" id="rm-save-profile-picture">
                        <span class="rm-btn-text"><?php _e('Save Changes', 'rm-panel-extensions'); ?></span>
                        <span class="rm-btn-loader" style="display: none;">
                            <i class="eicon-loading eicon-animation-spin"></i>
                        </span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <#
        var defaultAvatar = settings.default_avatar.url || 'https://via.placeholder.com/150';
        #>
        <div class="rm-profile-picture-container">
            <div class="rm-profile-picture-wrapper">
                <div class="rm-profile-picture-image-wrapper">
                    <img src="{{ defaultAvatar }}" alt="Profile Picture" class="rm-profile-picture-image">
                    <div class="rm-profile-picture-overlay">
                        <span class="rm-profile-picture-icon">
                            <i class="eicon-camera"></i>
                        </span>
                        <span class="rm-profile-picture-text">
                            {{ settings.upload_button_text }}
                        </span>
                    </div>
                </div>

                <div class="rm-profile-info">
                    <# if (settings.show_name === 'yes') { #>
                        <div class="rm-profile-name">John Doe</div>
                    <# } #>

                    <# if (settings.show_email === 'yes') { #>
                        <div class="rm-profile-email">john.doe@example.com</div>
                    <# } #>

                    <# if (settings.show_country === 'yes') { #>
                        <div class="rm-profile-country">
                            <i class="eicon-globe"></i> United States
                        </div>
                    <# } #>
                </div>
            </div>
        </div>
        <?php
    }
}