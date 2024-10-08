<?php
/**
 * Plugin Name:          DHL eCommerce for WooCommmerce
 * Plugin URI:           https://www.dhlecommerce.nl
 * Description:          This is the official DHL eCommerce (Benelux) for WooCommerce plugin.
 * Author:               DHL eCommerce
 * Version:              2.1.8
 * Requires at least:    4.7.16
 * Tested up to:         6.6
 * Requires PHP:         5.6
 * WC requires at least: 3.0.0
 * WC tested up to:      8.5.2
 * License:              GPL v3 or later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:          dhlpwc
 * Domain Path:          /languages
 */

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC')) :

class DHLPWC
{
    public function __construct()
    {
        // Only load this plugin if WooCommerce is loaded
        if (
            (
                is_array($active_plugins = apply_filters('active_plugins', get_option('active_plugins')))
                && in_array('woocommerce/woocommerce.php', $active_plugins)
            ) || (
                is_array($active_sitewide_plugins = apply_filters('active_plugins', get_site_option('active_sitewide_plugins')))
                && array_key_exists('woocommerce/woocommerce.php', $active_sitewide_plugins)
            )
        ) {
            add_action('plugins_loaded', array($this, 'init'));
        }
    }

    public function init()
    {
        if (!$this->country_check()) {
            return;
        }

        // Declare compatible with HPOS
        $this->set_compatible_with_hpos();

        // Autoloader
        include_once('includes/class-dhlpwc-autoloader.php');

        // Set constants
        $this->define('DHLPWC_PLUGIN_VERSION', '2.1.8');
        $this->define('DHLPWC_PLUGIN_FILE', __FILE__);
        $this->define('DHLPWC_PLUGIN_BASENAME', plugin_basename(__FILE__));
        $this->define('DHLPWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        $this->define('DHLPWC_PLUGIN_URL', plugins_url('/', __FILE__));

        $this->define('DHLPWC_RELATIVE_PLUGIN_DIR', $this->get_relative_plugin_dir());

        // Load translation
        load_plugin_textdomain('dhlpwc', false, DHLPWC_RELATIVE_PLUGIN_DIR . DIRECTORY_SEPARATOR .'languages' );

        // Load functions
        include_once('includes/function-dhlpwc-esc-template.php');

        // Load controllers

        // These controllers will not be encapsulated in an availability check, due to it providing screens
        // necessary to enable the plugin and setting up the plugin.
        new DHLPWC_Controller_Settings();
        new DHLPWC_Controller_Admin_Settings();

        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_API)) {
            new DHLPWC_Controller_Admin_Order_Metabox();
            new DHLPWC_Controller_Admin_Order();
            new DHLPWC_Controller_Admin_Product();

            new DHLPWC_Controller_Checkout();
            new DHLPWC_Controller_Cart();
            new DHLPWC_Controller_Account();
            new DHLPWC_Controller_Mail();
            new DHLPWC_Controller_Autoprint();
        }
    }

    protected function get_relative_plugin_dir()
    {
        // Check if the full dir is equal to the plugin dir. For example, if it's symlinked, this following
        // logic to get the relative path won't work. Instead we will return the relative directory
        if (substr(DHLPWC_PLUGIN_DIR, 0, strlen(WP_PLUGIN_DIR)) !== WP_PLUGIN_DIR) {
            return trim(basename(dirname(__FILE__)));
        }

        $relative_dir = substr(DHLPWC_PLUGIN_DIR, strlen(WP_PLUGIN_DIR), strlen(DHLPWC_PLUGIN_DIR));
        return trim($relative_dir, '/\\');
    }

    protected function country_check() {
        if (!function_exists('wc_get_base_location')) {
            return false;
        }

        $country_code = wc_get_base_location();
        if (!isset($country_code['country'])) {
            return false;
        }

        $valid_countries = array(
            'NL',
            'BE',
            'LU',
        );

        if (!in_array($country_code['country'], $valid_countries)) {
            return false;
        }

        return true;
    }

    protected function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    protected function set_compatible_with_hpos()
    {
        add_action('before_woocommerce_init', function() {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });
    }

}

// Run immediately
$DHLPWC = new DHLPWC();

endif;
