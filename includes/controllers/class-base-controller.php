<?php
/**
 * Base Controller Class
 *
 * Shared request checks for the AJAX / admin-post controllers.
 *
 * @package Bulk_Price_Discount_Editor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk_Pricer_Base_Controller Class
 */
abstract class Bulk_Pricer_Base_Controller
{
    /**
     * Verify the nonce and the user's capability for an AJAX request
     *
     * Sends a JSON error (and stops) when either check fails.
     *
     * @since 2.1.0
     */
    protected function authorize()
    {
        check_ajax_referer('sbp_bulk_nonce', 'security');

        // Capability check (defense in depth - the nonce is not an authorization check)
        if (!current_user_can(Bulk_Pricer_Loader::capability())) {
            wp_send_json_error(__('Unauthorized', 'bulk-price-discount-editor-for-woocommerce'));
        }
    }

    /**
     * Verify the nonce and the user's capability for an admin-post request
     *
     * @since 2.1.0
     */
    protected function authorize_admin_post()
    {
        check_admin_referer('sbp_bulk_nonce', 'security');

        if (!current_user_can(Bulk_Pricer_Loader::capability())) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bulk-price-discount-editor-for-woocommerce'), '', array('response' => 403));
        }
    }

    /**
     * Validate the submitted form; on failure send/display the reason
     *
     * @since 2.1.0
     * @param bool $ajax True to answer with JSON, false to wp_die()
     * @return array Validated request data
     */
    protected function get_validated_request($ajax = true)
    {
        $validator = new Bulk_Pricer_Validator();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in authorize()/authorize_admin_post().
        $validated = $validator->validate_request($_POST);

        if (!$validated) {
            $message = $validator->get_error() ? $validator->get_error() : __('Invalid input data', 'bulk-price-discount-editor-for-woocommerce');

            if ($ajax) {
                wp_send_json_error($message);
            }
            wp_die(esc_html($message), '', array('response' => 400, 'back_link' => true));
        }

        return $validated;
    }

    /**
     * Read the product IDs the user removed from the preview
     *
     * @since 2.1.0
     * @return int[]
     */
    protected function get_excluded_ids()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in authorize()/authorize_admin_post().
        if (empty($_POST['excluded_ids'])) {
            return array();
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $decoded = json_decode(sanitize_text_field(wp_unslash($_POST['excluded_ids'])), true);

        return is_array($decoded) ? array_values(array_unique(array_map('intval', $decoded))) : array();
    }

    /**
     * Read a positive integer from POST
     *
     * @since 2.1.0
     * @param string $key Field name
     * @return int
     */
    protected function get_post_int($key)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in authorize().
        return isset($_POST[$key]) ? absint(wp_unslash($_POST[$key])) : 0;
    }
}
