<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Parceltype')) :

class DHLPWC_Model_API_Data_Parceltype extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'dimensions' => 'DHLPWC_Model_API_Data_Parceltype_Dimensions',
    );

    public $key;
    public $min_weight_kg;
    public $max_weight_kg;
    public $min_weight_grams;
    public $max_weight_grams;
    /** @var DHLPWC_Model_API_Data_Parceltype_Dimensions */
    public $dimensions;
    public $display_weight;

}

endif;
