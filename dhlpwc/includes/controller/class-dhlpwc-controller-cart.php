<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Cart')) :

class DHLPWC_Controller_Cart
{

    public function __construct()
    {
        add_action('wp_loaded', array($this, 'set_parcelshop_hooks'));
        add_action('wp_loaded', array($this, 'set_delivery_time_hooks'));
        add_filter('woocommerce_package_rates', array($this, 'sort_rates'), 10, 2);
    }

    public function set_parcelshop_hooks()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_PARCELSHOP)
            || $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_USE_SHIPPING_ZONES)) {
            add_action('wp_enqueue_scripts', array($this, 'load_parcelshop_styles'));
            add_action('wp_enqueue_scripts', array($this, 'load_parcelshop_scripts'));

            add_action('woocommerce_after_shipping_rate', array($this, 'show_parcelshop_shipping_method'), 10, 2);

            add_action('wp_ajax_dhlpwc_load_parcelshop_selection', array($this, 'parcelshop_modal_content'));
            add_action('wp_ajax_nopriv_dhlpwc_load_parcelshop_selection', array($this, 'parcelshop_modal_content'));

            add_action('wp_ajax_dhlpwc_parcelshop_selection_sync', array($this, 'parcelshop_selection_sync'));
            add_action('wp_ajax_nopriv_dhlpwc_parcelshop_selection_sync', array($this, 'parcelshop_selection_sync'));

            add_action('wp_ajax_dhlpwc_delivery_time_selection_sync', array($this, 'delivery_time_selection_sync'));
            add_action('wp_ajax_nopriv_dhlpwc_delivery_time_selection_sync', array($this, 'delivery_time_selection_sync'));

            add_action('wp_ajax_dhlpwc_get_initial_parcelshop', array($this, 'get_initial_parcelshop'));
            add_action('wp_ajax_nopriv_dhlpwc_get_initial_parcelshop', array($this, 'get_initial_parcelshop'));

            add_action('wp_ajax_dhlpwc_get_delivery_times', array($this, 'get_delivery_times'));
            add_action('wp_ajax_nopriv_dhlpwc_get_delivery_times', array($this, 'get_delivery_times'));
        }
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DISPLAY_ZERO_FEE_NUMBER)) {
            add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'display_zero_fee_number'), 10, 2);
        } else if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DISPLAY_ZERO_FEE_TEXT)) {
            add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'display_zero_fee_text'), 10, 2);
        }
    }

    public function get_initial_parcelshop()
    {
        $json_response = new DHLPWC_Model_Response_JSON();

        if (!isset($_POST['postal_code']) || !isset($_POST['country_code'])) {
            $json_response->set_error('Please provide a postal code and country');

            wp_send_json($json_response->to_array(), 400);

            return;
        }

        $postal_code = wc_clean($_POST['postal_code']);
        $country_code = wc_clean($_POST['country_code']);

        $service = DHLPWC_Model_Service_Parcelshop::instance();
        $parcelshop = $service->search_parcelshop($postal_code, $country_code);
        // TODO: Find out why this doesn't seem to work properly
//        if ($parcelshop) {
//            WC()->session->set('dhlpwc_parcelshop_selection_sync', array($parcelshop->id, $country_code, $postal_code));
//        }

        $json_response->set_data(array('parcelshop' => $parcelshop));
        wp_send_json($json_response->to_array(), 200);
    }

    public function display_zero_fee_number($label, $method)
    {
        if (isset($method->id) && substr($method->id, 0, 6) === 'dhlpwc' && !($method->cost > 0)) {
            $label .= ': ' . wc_price(0);
        }
        return $label;
    }

    public function display_zero_fee_text($label, $method)
    {
        if (isset($method->id) && substr($method->id, 0, 6) === 'dhlpwc' && !($method->cost > 0)) {
            $label .= sprintf(' (%s)', __('Free', 'dhlpwc'));
        }
        return $label;
    }

    public function set_delivery_time_hooks()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES)) {
            add_action('wp_enqueue_scripts', array($this, 'load_delivery_time_scripts'));
        }

        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES_ACTIVE)) {
            add_action('woocommerce_after_shipping_rate', array($this, 'show_delivery_times_shipping_method'), 10, 2);
        }
    }

    public function sort_rates($rates, $package)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if (!$service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_SORT)) {
            return $rates;
        }

        $service = DHLPWC_Model_Service_Shipping_Preset::instance();
        return $service->sort_rates($rates);
    }

    public function parcelshop_modal_content()
    {
        $view = new DHLPWC_Template('cart.parcelshop-locator');
        $parcelshop_locator_view = $view->render(array(), false);

        $view = new DHLPWC_Template('modal');
        $modal_view = $view->render(array(
            'content' => $parcelshop_locator_view,
            'logo' => DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_logo.png',
        ), false);

        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $modal_view,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    public function parcelshop_selection_sync()
    {
        $json_response = new DHLPWC_Model_Response_JSON();

        if (isset($_POST['parcelshop_id']) && isset($_POST['country_code'])) {
            $parcelshop_id = wc_clean($_POST['parcelshop_id']);
            $country_code = wc_clean($_POST['country_code']);
        } else {
            $parcelshop_id = null;
            $country_code = null;
        }

        $service = DHLPWC_Model_Service_Checkout::instance();
        $postal_code = $service->get_cart_shipping_postal_code() ?: null;

        WC()->session->set('dhlpwc_parcelshop_selection_sync', array($parcelshop_id, $country_code, $postal_code));
        wp_send_json($json_response->to_array(), 200);
    }

    public function delivery_time_selection_sync()
    {
        $json_response = new DHLPWC_Model_Response_JSON();

        $selected = !empty($_POST['selected']) ? wc_clean($_POST['selected']) :  null;
        $date = !empty($_POST['date']) ? wc_clean($_POST['date']) : null;
        $start_time = !empty($_POST['start_time']) ? wc_clean($_POST['start_time']): null;
        $end_time = !empty($_POST['end_time']) ? wc_clean($_POST['end_time']) : null;

        WC()->session->set('dhlpwc_delivery_time_selection_sync', array($selected, $date, $start_time, $end_time));

        wp_send_json($json_response->to_array(), 200);
    }

    public function get_delivery_times()
    {
        $json_response = new DHLPWC_Model_Response_JSON();
        $shipping_method = wc_clean($_POST['shipping_method']);
        $postal_code = wc_clean($_POST['postal_code']);
        $country_code = wc_clean($_POST['country_code']);

        $service = DHLPWC_Model_Service_Delivery_Times::instance();
        $delivery_times = $service->get_time_frames($postal_code, $country_code);
        $delivery_times = $service->filter_time_frames($delivery_times);

        $service = DHLPWC_Model_Service_Access_Control::instance();
        $sdd_as_time_window = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SAME_DAY_AS_TIME_WINDOW);
        $is_sdd_method = $shipping_method === 'dhlpwc-home-same-day' || $shipping_method === 'dhlpwc-home-no-neighbour-same-day';

        if ($sdd_as_time_window || !$is_sdd_method) {
            // Remove today as delivery time when same day is a separate shipping method
            if (!$sdd_as_time_window) {
                $service = DHLPWC_Model_Service_Delivery_Times::instance();
                $delivery_times = $service->remove_same_day_time_frame($delivery_times);
            }
        } elseif ($is_sdd_method && !$sdd_as_time_window) {
            $service = DHLPWC_Model_Service_Delivery_Times::instance();

            $delivery_times = [$service->get_same_day_time_frame($delivery_times)];
        } else {
            $delivery_times = array();
        }
        // Grab either daytime or evening depending on the selected shipping method
        if (strpos($shipping_method, 'evening') !== false) {
            $delivery_times = array_filter($delivery_times, function ($delivery_time) {
                return $delivery_time->preset_frontend_id === 'home-evening';
            });
        } else {
            $delivery_times = array_filter($delivery_times, function ($delivery_time) {
                return $delivery_time->preset_frontend_id === 'home' || $delivery_time->preset_frontend_id === 'home-same-day';
            });
        }

        $json_response->set_data($delivery_times);

        wp_send_json($json_response->to_array(), 200);
    }

    public function show_delivery_times_shipping_method($method, $index)
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        // This logic shows extra content on the currently selected shipment method
        if ($method->id == $chosen_shipping) {
            switch($chosen_shipping) {
                case 'dhlpwc-home-no-neighbour':
                case 'dhlpwc-home-no-neighbour-same-day':
                case 'dhlpwc-home-no-neighbour-next-day':
                case 'dhlpwc-home-no-neighbour-evening':
                    $no_neighbour = true;

                case 'dhlpwc-home':
                case 'dhlpwc-home-same-day':
                case 'dhlpwc-home-next-day':
                case 'dhlpwc-home-evening':
                    // Get variables
                    $sync = WC()->session->get('dhlpwc_delivery_time_selection_sync');
                    if ($sync) {
                        list($selected) = $sync;
                    } else {
                        list($selected) = array(null, null, null, null);
                    }

                    $service = DHLPWC_Model_Service_Checkout::instance();
                    $postal_code = $service->get_cart_shipping_postal_code();
                    $country_code = $service->get_cart_shipping_country_code();

                    $service = DHLPWC_Model_Service_Delivery_Times::instance();
                    $delivery_times = $service->get_time_frames($postal_code, $country_code, $selected);
                    $delivery_times = $service->filter_time_frames($delivery_times, !empty($no_neighbour), $selected);

                    $service = DHLPWC_Model_Service_Access_Control::instance();
                    $sdd_as_time_window = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SAME_DAY_AS_TIME_WINDOW);
                    $is_sdd_method =  $chosen_shipping === 'dhlpwc-home-same-day' || $chosen_shipping === 'dhlpwc-home-no-neighbour-same-day';

                    if (empty($delivery_times)) {
                        break;
                    }

                    if ($sdd_as_time_window || !$is_sdd_method) {
                        // Remove today as delivery time when same day is a separate shipping method
                        if (!$sdd_as_time_window) {
                            $service = DHLPWC_Model_Service_Delivery_Times::instance();
                            $delivery_times = $service->remove_same_day_time_frame($delivery_times, !empty($no_neighbour));
                        }

                        $view = new DHLPWC_Template('cart.delivery-times-option');
                        $view->render(array(
                            'country_code'   => $country_code,
                            'postal_code'    => $postal_code,
                            'delivery_times' => $delivery_times,
                        ));
                    } elseif ($is_sdd_method && !$sdd_as_time_window) {
                        $service = DHLPWC_Model_Service_Delivery_Times::instance();
                        $sdd_delivery_time = $service->get_same_day_time_frame($delivery_times, !empty($no_neighbour));

                        if ($sdd_delivery_time) {
                            $view = new DHLPWC_Template('cart.delivery-times-info-box');
                            $view->render(array(
                                'delivery_time' => $sdd_delivery_time,
                            ));
                        }
                    }

                    break;
                default:
                    // Always empty selection sync if it's a different method
                    WC()->session->set('dhlpwc_delivery_time_selection_sync', array(null, null, null, null));
                    break;
            }
        }
    }

    public function show_parcelshop_shipping_method($method, $index)
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        // This logic shows extra content on the currently selected shipment method
        if ($method->id == $chosen_shipping) {
            switch($chosen_shipping) {
                case 'dhlpwc-parcelshop':
                    $sync = WC()->session->get('dhlpwc_parcelshop_selection_sync');
                    if ($sync) {
                        list($parcelshop_id, $country_code, $postal_code_memory) = $sync;
                    } else {
                        list($parcelshop_id, $country_code, $postal_code_memory) = array(null, null, null);
                    }

                    $service = DHLPWC_Model_Service_Checkout::instance();

                    // Validate country change
                    $cart_country = $service->get_cart_shipping_country_code();
                    if (!empty($country_code) && $country_code != $cart_country) {
                        // Reset selection, due to countries being out of sync
                        list($parcelshop_id, $country_code, $postal_code_memory) = array(null, null, null);
                        WC()->session->set('dhlpwc_parcelshop_selection_sync', array(null, null, null));
                    }

                    $postal_code = $service->get_cart_shipping_postal_code() ?: null;
                    $country_code = $country_code ?: $cart_country;

                    // Attempt to select a default parcelshop when none is selected or postal code is changed
                    $service = DHLPWC_Model_Service_Parcelshop::instance();
                    if (!$parcelshop_id || $postal_code != $postal_code_memory) {
                        $parcelshop = $service->search_parcelshop($postal_code, $country_code);
                        if ($parcelshop) {
                            WC()->session->set('dhlpwc_parcelshop_selection_sync', array($parcelshop->id, $country_code, $postal_code));
                        }
                    } else {
                        $parcelshop = $service->get_parcelshop($parcelshop_id, $country_code);
                    }

                    $view = new DHLPWC_Template('cart.parcelshop-option');
                    $view->render(array(
                        'country_code' => $country_code,
                        'postal_code' => $postal_code,
                        'parcelshop' => $parcelshop,
                    ));

                    break;
                default:
                    // Always empty selection sync if it's a different method
                    WC()->session->set('dhlpwc_parcelshop_selection_sync', array(null, null, null));
                    break;
            }
        }
    }

    public function load_parcelshop_scripts()
    {
        if (is_cart() || is_checkout()) {

            $dependencies = array('jquery');

            $service = DHLPWC_Model_Service_Settings::instance();
            $google_map_key = $service->get_maps_key();

            $service = DHLPWC_Model_Service_Parcelshop::instance();
            $gateway = $service->get_parcelshop_gateway();

            $translations = $service->get_component_translations();

            $service = DHLPWC_Model_Service_Checkout::instance();
            $country_code = $service->get_cart_shipping_country_code();
            $postal_code = $service->get_cart_shipping_postal_code();

            $locale = get_locale();
            $locale_parts = explode('_', $locale);
            $language = strtolower(reset($locale_parts));

            $service = DHLPWC_Model_Service_Access_Control::instance();
            $delivery_times_enabled = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES);
            $sdd_as_time_window = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SAME_DAY_AS_TIME_WINDOW);

            if (function_exists('has_block') && has_block('woocommerce/checkout')) {
                wp_add_inline_script(
                    'dhlpwc-dhlpwc-block-view-script',
                    'window.dhlpwc_block_data = ' . json_encode(
                        array(
                            'gateway'                 => $gateway,
                            'postal_code'             => $postal_code,
                            'country_code'            => $country_code,
                            'limit'                   => 7,
                            'ajax_url'                => admin_url( 'admin-ajax.php' ),
                            'modal_background'        => DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_top_bg.jpg',
                            'google_map_key'          => $google_map_key,
                            'translations'            => $translations,
                            'language'                => $language,
                            'deliverytimes_enabled'   => $delivery_times_enabled,
                            'sdd_as_time_window'      => $sdd_as_time_window,
                            'initial_shipping_method' => isset(WC()->session->get('chosen_shipping_methods')[0]) ? WC()->session->get('chosen_shipping_methods')[0] : ''
                        )
                    ),
                    'before'
                );
            } else {
                wp_enqueue_script('dhlpwc-checkout-parcelshop-locator-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.parcelshop.locator.js', $dependencies, DHLPWC_PLUGIN_VERSION);
                wp_localize_script(
                    'dhlpwc-checkout-parcelshop-locator-script',
                    'dhlpwc_parcelshop_locator',
                    array(
                        'gateway'          => $gateway,
                        'postal_code'      => $postal_code,
                        'country_code'     => $country_code,
                        'limit'            => 7,
                        'ajax_url'         => admin_url( 'admin-ajax.php' ),
                        'modal_background' => DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_top_bg.jpg',
                        'google_map_key'   => $google_map_key,
                        'translations'     => $translations,
                        'language'         => $language
                    )
                );
            }
        }
    }

    public function load_delivery_time_scripts()
    {
        if (is_cart() || is_checkout()) {
            $select_woo_active = $this->version_check('3.2');
            $dependencies = array('jquery');
            if ($select_woo_active) {
                $dependencies[] = 'selectWoo';
            }

            $service = DHLPWC_Model_Service_Access_Control::instance();
            $sdd_as_time_window = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SAME_DAY_AS_TIME_WINDOW);

            wp_enqueue_script('dhlpwc-checkout-delivery-time-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.deliverytime.js', $dependencies, DHLPWC_PLUGIN_VERSION);
            wp_localize_script(
                'dhlpwc-checkout-delivery-time-script',
                'dhlpwc_delivery_time_object',
                array(
                    'ajax_url'           => admin_url('admin-ajax.php'),
                    'select_woo_active'  => $select_woo_active ? 'true' : 'false',
                    'sdd_as_time_window' => $sdd_as_time_window ? true : false
                )
            );
        }
    }

    public function load_parcelshop_styles()
    {
        if (is_cart() || is_checkout()) {
            wp_enqueue_style('dhlpwc-checkout-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.cart.css', array(), DHLPWC_PLUGIN_VERSION);
            wp_enqueue_style('dhlpwc-checkout-modal-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.modal.css', array(), DHLPWC_PLUGIN_VERSION);
            wp_enqueue_style('dhlpwc-checkout-parcelshop-dsl-style', 'https://static.dhlecommerce.nl/fonts/Delivery.css', array(), DHLPWC_PLUGIN_VERSION);
        }
    }

    protected function format_city($string)
    {
        $parts = explode(' ', $string);
        $formatted = array();
        foreach($parts as $part) {
            $formatted[] = strlen($part) > 1 ? ucfirst(strtolower($part)) : "'".strtolower($part);
        }
        return implode(' ', $formatted);
    }

    protected function version_check($version = '3.2')
    {
        if (class_exists('WooCommerce')) {
            global $woocommerce;
            if (version_compare($woocommerce->version, $version, ">=")) {
                return true;
            }
        }
        return false;
    }

}

endif;
