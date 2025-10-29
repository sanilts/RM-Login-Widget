<?php

/**
 * FluentCRM Integration Helper
 * File: modules/fluent-crm/class-fluent-crm-helper.php
 */
if (!defined('ABSPATH')) {
    exit;
}

class RM_Panel_FluentCRM_Helper {

    /**
     * Check if FluentCRM is active
     */
    public static function is_active() {
        return defined('FLUENTCRM') || function_exists('FluentCrmApi');
    }

    /**
     * Get contact by user ID
     */
    public static function get_contact_by_user_id($user_id) {
        if (!self::is_active()) {
            return null;
        }

        try {
            $contact = \FluentCrm\App\Models\Subscriber::where('user_id', $user_id)->first();
            return $contact;
        } catch (Exception $e) {
            error_log('RM Panel: FluentCRM error - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get contact's country - IMPROVED VERSION
     */
    public static function get_contact_country($user_id) {
        $contact = self::get_contact_by_user_id($user_id);

        if (!$contact) {
            error_log("RM Panel: No FluentCRM contact found for user $user_id");
            return null;
        }

        // FluentCRM stores country in 'country' field
        $country = $contact->country;

        // Debug log
        error_log("RM Panel: User $user_id - Raw country from FluentCRM: " . var_export($country, true));

        if (empty($country)) {
            return null;
        }

        // Clean and normalize
        $country = trim($country);
        $country = strtoupper($country);

        // Convert country name to code if needed
        $country = self::normalize_country_code($country);

        error_log("RM Panel: User $user_id - Normalized country: $country");

        return $country;
    }

    /**
     * Normalize country code/name to standard 2-letter code
     */
    private static function normalize_country_code($country) {
        $country = strtoupper(trim($country));

        // If already 2-letter code, return as is
        if (strlen($country) === 2) {
            return $country;
        }

        // Map common country names to codes
        $country_map = [
            'INDIA' => 'IN',
            'UNITED STATES' => 'US',
            'UNITED STATES OF AMERICA' => 'US',
            'USA' => 'US',
            'UNITED KINGDOM' => 'GB',
            'UK' => 'GB',
            'CANADA' => 'CA',
            'AUSTRALIA' => 'AU',
            'GERMANY' => 'DE',
            'FRANCE' => 'FR',
            'CHINA' => 'CN',
            'JAPAN' => 'JP',
            'BRAZIL' => 'BR',
            'MEXICO' => 'MX',
            'SOUTH KOREA' => 'KR',
            'SPAIN' => 'ES',
            'ITALY' => 'IT',
            'NETHERLANDS' => 'NL',
            'POLAND' => 'PL',
            'RUSSIA' => 'RU',
            'TURKEY' => 'TR',
            'ARGENTINA' => 'AR',
            'BELGIUM' => 'BE',
            'AUSTRIA' => 'AT',
            'SWITZERLAND' => 'CH',
            'DENMARK' => 'DK',
            'FINLAND' => 'FI',
            'GREECE' => 'GR',
            'IRELAND' => 'IE',
            'NORWAY' => 'NO',
            'PORTUGAL' => 'PT',
            'SWEDEN' => 'SE',
            'THAILAND' => 'TH',
            'VIETNAM' => 'VN',
            'INDONESIA' => 'ID',
            'MALAYSIA' => 'MY',
            'PHILIPPINES' => 'PH',
            'SINGAPORE' => 'SG',
            'NEW ZEALAND' => 'NZ',
            'SOUTH AFRICA' => 'ZA',
            'EGYPT' => 'EG',
            'NIGERIA' => 'NG',
            'KENYA' => 'KE',
            'PAKISTAN' => 'PK',
            'BANGLADESH' => 'BD',
        ];

        return isset($country_map[$country]) ? $country_map[$country] : $country;
    }

    /**
     * Get contact's full data
     */
    public static function get_contact_data($user_id) {
        $contact = self::get_contact_by_user_id($user_id);

        if (!$contact) {
            return null;
        }

        return [
            'id' => $contact->id,
            'email' => $contact->email,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'country' => $contact->country,
            'state' => $contact->state,
            'city' => $contact->city,
            'postal_code' => $contact->postal_code,
            'status' => $contact->status,
        ];
    }

    /**
     * Check if user's country matches survey target countries - IMPROVED
     */
    public static function matches_survey_location($user_id, $survey_id) {
        // Get survey location settings
        $location_type = get_post_meta($survey_id, '_rm_survey_location_type', true);

        error_log("RM Panel: Survey $survey_id - Location type: " . var_export($location_type, true));

        // If targeting all countries, always return true
        if ($location_type !== 'specific') {
            error_log("RM Panel: Survey $survey_id - Targeting ALL countries");
            return true;
        }

        // Get target countries
        $target_countries = get_post_meta($survey_id, '_rm_survey_countries', true);

        error_log("RM Panel: Survey $survey_id - Target countries: " . var_export($target_countries, true));

        // If no countries specified, allow all
        if (empty($target_countries) || !is_array($target_countries)) {
            error_log("RM Panel: Survey $survey_id - No target countries specified, allowing all");
            return true;
        }

        // Normalize target countries (ensure uppercase)
        $target_countries = array_map('strtoupper', array_map('trim', $target_countries));

        // Get user's country from FluentCRM
        $user_country = self::get_contact_country($user_id);

        error_log("RM Panel: User $user_id - Country: " . var_export($user_country, true));

        // If user has no country set, don't show
        if (empty($user_country)) {
            error_log("RM Panel: User $user_id - No country set, hiding survey $survey_id");
            return false;
        }

        // Check if user's country is in target countries
        $matches = in_array($user_country, $target_countries);

        error_log("RM Panel: User $user_id, Survey $survey_id - Match result: " . ($matches ? 'YES' : 'NO'));

        return $matches;
    }

    /**
     * Get readable country name from code
     */
    public static function get_country_name($country_code) {
        $countries = [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AR' => 'Argentina',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'BD' => 'Bangladesh',
            'BE' => 'Belgium',
            'BR' => 'Brazil',
            'BG' => 'Bulgaria',
            'CA' => 'Canada',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Colombia',
            'CR' => 'Costa Rica',
            'HR' => 'Croatia',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'EG' => 'Egypt',
            'FI' => 'Finland',
            'FR' => 'France',
            'DE' => 'Germany',
            'GR' => 'Greece',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JP' => 'Japan',
            'KE' => 'Kenya',
            'MY' => 'Malaysia',
            'MX' => 'Mexico',
            'NL' => 'Netherlands',
            'NZ' => 'New Zealand',
            'NG' => 'Nigeria',
            'NO' => 'Norway',
            'PK' => 'Pakistan',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'ES' => 'Spain',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'TW' => 'Taiwan',
            'TH' => 'Thailand',
            'TR' => 'Turkey',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'VN' => 'Vietnam',
        ];

        return isset($countries[strtoupper($country_code)]) ? $countries[strtoupper($country_code)] : $country_code;
    }

    /**
     * Update contact's avatar/profile picture
     * 
     * @param int $user_id WordPress user ID
     * @param int $attachment_id WordPress attachment ID
     * @return bool Success status
     */
    public static function update_contact_avatar($user_id, $attachment_id) {
        if (!self::is_active()) {
            return false;
        }

        try {
            $contact = self::get_contact_by_user_id($user_id);

            if (!$contact) {
                error_log("RM Panel: No FluentCRM contact found for user $user_id");
                return false;
            }

            // Get the image URL (use 'medium' size for optimization)
            $image_url = wp_get_attachment_image_url($attachment_id, 'medium');

            if (!$image_url) {
                error_log("RM Panel: Could not get image URL for attachment $attachment_id");
                return false;
            }

            // Update contact's avatar field
            $contact->avatar = $image_url;
            $contact->save();

            error_log("RM Panel: Updated FluentCRM avatar for user $user_id to $image_url");

            return true;
        } catch (Exception $e) {
            error_log('RM Panel: FluentCRM avatar update error - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get contact's avatar URL
     * 
     * @param int $user_id WordPress user ID
     * @return string|null Avatar URL or null
     */
    public static function get_contact_avatar($user_id) {
        $contact = self::get_contact_by_user_id($user_id);

        if (!$contact) {
            return null;
        }

        return !empty($contact->avatar) ? $contact->avatar : null;
    }

    /**
     * Remove contact's avatar
     * 
     * @param int $user_id WordPress user ID
     * @return bool Success status
     */
    public static function remove_contact_avatar($user_id) {
        if (!self::is_active()) {
            return false;
        }

        try {
            $contact = self::get_contact_by_user_id($user_id);

            if (!$contact) {
                return false;
            }

            $contact->avatar = '';
            $contact->save();

            error_log("RM Panel: Removed FluentCRM avatar for user $user_id");

            return true;
        } catch (Exception $e) {
            error_log('RM Panel: FluentCRM avatar removal error - ' . $e->getMessage());
            return false;
        }
    }
}
