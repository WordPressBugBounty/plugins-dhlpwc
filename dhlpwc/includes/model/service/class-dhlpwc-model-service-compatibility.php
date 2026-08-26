<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Compatibility')) :

class DHLPWC_Model_Service_Compatibility extends DHLPWC_Model_Core_Singleton_Abstract
{
    public function woocommerce_version_is_at_least($version)
    {
        $woocommerce_version = $this->get_woocommerce_version();
        if (!$woocommerce_version) {
            return false;
        }

        return version_compare($woocommerce_version, $version, '>=');
    }

    public function get_woocommerce_version()
    {
        if (defined('WC_VERSION')) {
            return WC_VERSION;
        }

        if (function_exists('WC') && WC() && isset(WC()->version)) {
            return WC()->version;
        }

        return null;
    }
}

endif;