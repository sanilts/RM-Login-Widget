<?php
/**
 * Fluent Forms Integration Module
 * 
 * File: modules/fluent-forms/class-fluent-forms-module.php
 * 
 * Handles Fluent Forms integration including password confirmation validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_Fluent_Forms_Module {

    /**
     * Constructor
     */
    public function __construct() {
        // Check if Fluent Forms is active
        if (!$this->is_fluent_forms_active()) {
            add_action('admin_notices', [$this, 'fluent_forms_missing_notice']);
            return;
        }

        $this->init_hooks();
    }

    /**
     * Check if Fluent Forms is active
     */
    private function is_fluent_forms_active() {
        return defined('FLUENTFORM') || function_exists('wpFluentForm');
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Password confirmation validation
        add_filter('fluentform/validation_errors', [$this, 'validate_password_confirmation'], 10, 4);

        // Additional custom validations
        add_action('fluentform/before_insert_submission', [$this, 'before_submission'], 10, 3);

        // Add custom validation messages
        add_filter('fluentform/validation_message_password', [$this, 'custom_password_messages'], 10, 3);
    }

    /**
     * Validate password confirmation
     * 
     * @param array $errors Validation errors
     * @param array $formData Form data submitted
     * @param object $form Form object
     * @param array $fields Form fields
     * @return array Modified errors
     */
    public function validate_password_confirmation($errors, $formData, $form, $fields) {
        // Define the field names you're using in your form
        $password_field = 'password';           // Adjust to your password field name
        $confirm_password_field = 'confirm_password'; // Adjust to your confirm password field name

        // Check if both fields exist in the submitted data
        $password = isset($formData[$password_field]) ? $formData[$password_field] : '';
        $confirm_password = isset($formData[$confirm_password_field]) ? $formData[$confirm_password_field] : '';

        // Check if password fields are present and not empty
        if (!empty($password) || !empty($confirm_password)) {
            
            // Validate password strength (optional)
            if (!empty($password) && strlen($password) < 8) {
                $errors[$password_field] = [
                    __('Password must be at least 8 characters long.', 'rm-panel-extensions')
                ];
            }

            // Check if passwords match
            if ($password !== $confirm_password) {
                $errors[$confirm_password_field] = [
                    __('Passwords do not match. Please ensure both password fields are identical.', 'rm-panel-extensions')
                ];
            }

            // Validate password complexity (optional)
            if (!empty($password) && !$this->validate_password_strength($password)) {
                $errors[$password_field] = [
                    __('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.', 'rm-panel-extensions')
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return bool
     */
    private function validate_password_strength($password) {
        // Check for at least 8 characters
        if (strlen($password) < 8) {
            return false;
        }

        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Check for special character (optional - comment out if not needed)
        // if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        //     return false;
        // }

        return true;
    }

    /**
     * Additional validation before submission
     * 
     * @param array $insertData Data to be inserted
     * @param array $formData Form data submitted
     * @param object $form Form object
     */
    public function before_submission($insertData, $formData, $form) {
        // Add any additional pre-submission logic here
        // For example, hash the password before storing
        
        // This is just an example - adjust based on your needs
        if (isset($formData['password'])) {
            // Don't store raw passwords in Fluent Forms submissions
            // If you need to create a WordPress user, do it here
            
            // Example: Create WordPress user
            // $this->create_wordpress_user($formData);
        }
    }

    /**
     * Custom password validation messages
     * 
     * @param string $message Error message
     * @param array $formData Form data
     * @param object $form Form object
     * @return string
     */
    public function custom_password_messages($message, $formData, $form) {
        return __('Please enter a valid password.', 'rm-panel-extensions');
    }

    /**
     * Create WordPress user from form submission (optional)
     * 
     * @param array $formData Form data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    private function create_wordpress_user($formData) {
        // Extract user data
        $username = isset($formData['username']) ? sanitize_user($formData['username']) : '';
        $email = isset($formData['email']) ? sanitize_email($formData['email']) : '';
        $password = isset($formData['password']) ? $formData['password'] : '';
        $first_name = isset($formData['first_name']) ? sanitize_text_field($formData['first_name']) : '';
        $last_name = isset($formData['last_name']) ? sanitize_text_field($formData['last_name']) : '';

        // Check if user already exists
        if (username_exists($username) || email_exists($email)) {
            return new WP_Error('user_exists', __('Username or email already exists.', 'rm-panel-extensions'));
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Update user meta
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ]);

        // Add custom meta data
        if (isset($formData['gender'])) {
            update_user_meta($user_id, 'gender', sanitize_text_field($formData['gender']));
        }

        if (isset($formData['country'])) {
            update_user_meta($user_id, 'country', sanitize_text_field($formData['country']));
        }

        // Send notification email (optional)
        wp_new_user_notification($user_id, null, 'both');

        return $user_id;
    }

    /**
     * Show admin notice if Fluent Forms is not active
     */
    public function fluent_forms_missing_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('RM Panel Extensions:', 'rm-panel-extensions'); ?></strong>
                <?php _e('Fluent Forms Integrations module requires Fluent Forms to be installed and activated.', 'rm-panel-extensions'); ?>
                <a href="<?php echo admin_url('plugin-install.php?s=fluent+forms&tab=search&type=term'); ?>">
                    <?php _e('Install Fluent Forms', 'rm-panel-extensions'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Validate specific field types (alternative approach)
     * 
     * Use this filter for field-specific validation:
     * fluentform/validate_input_item_{field_name}
     */
    public function validate_password_field($errorMessage, $field, $formData, $fields, $form) {
        $fieldName = $field['name'];
        $password = \FluentForm\Framework\Helpers\ArrayHelper::get($formData, $fieldName);

        if (!empty($password) && strlen($password) < 8) {
            return [__('Password must be at least 8 characters long.', 'rm-panel-extensions')];
        }

        return $errorMessage;
    }

    /**
     * Add validation for confirm password field
     */
    public function validate_confirm_password_field($errorMessage, $field, $formData, $fields, $form) {
        $fieldName = $field['name'];
        $confirmPassword = \FluentForm\Framework\Helpers\ArrayHelper::get($formData, $fieldName);
        $password = \FluentForm\Framework\Helpers\ArrayHelper::get($formData, 'password');

        if ($password !== $confirmPassword) {
            return [__('Passwords do not match.', 'rm-panel-extensions')];
        }

        return $errorMessage;
    }
}

// Initialize the module if Fluent Forms is active
if (defined('FLUENTFORM')) {
    new RM_Panel_Fluent_Forms_Module();
}