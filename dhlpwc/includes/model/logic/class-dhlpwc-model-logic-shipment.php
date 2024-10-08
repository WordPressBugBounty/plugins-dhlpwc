<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Shipment')) :

class DHLPWC_Model_Logic_Shipment extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function prepare_data($order_id, $options = array(), $replace_shipping_address = null)
    {
        $order = wc_get_order($order_id);
        $receiver_address = $order->get_address('shipping') ?: $order->get_address();
        $receiver_billing_address = $order->get_address('billing') ?: $order->get_address();
        $receiver_address['email'] = $receiver_billing_address['email'];
        $receiver_address['phone'] = preg_replace('/\D+/', '', $receiver_billing_address['phone']);
        unset($receiver_billing_address);

        $business = isset($options['to_business']) && $options['to_business'] ? true : false;

        $service = DHLPWC_Model_Service_Settings::instance();
        $shipper_address = $service->get_default_address()->to_array();

        $keys = isset($options['label_options']) && is_array($options['label_options']) ? $options['label_options'] : array();
        $option_data = isset($options['label_option_data']) && is_array($options['label_option_data']) ? $options['label_option_data'] : array();
        $service = DHLPWC_Model_Service_Shipment_Option::instance();
        $request_options = $service->get_request_options($keys, $option_data);

        $pieces = array_map(function ($piece) {
            return new DHLPWC_Model_API_Data_Shipment_Piece($piece);
        }, $options['pieces']);

        $shipment = new DHLPWC_Model_API_Data_Shipment_Request(array(
            'shipment_id'     => (string)new DHLPWC_Model_UUID(),
            'order_reference' => (string)$order_id,
            'receiver'        => $this->prepare_address_data($receiver_address, $business),
            'shipper'         => $this->prepare_address_data($shipper_address, true),
            'account_id'      => $this->get_account_id(),
            'options'         => $request_options,
            'pieces'          => $pieces,
            'application'     => 'woocommerce',
        ));

        if ($replace_shipping_address) {
            // Use the same country as the default
            $replace_shipping_address['country'] = $shipper_address['country'];
            $shipment->on_behalf_of = $this->prepare_address_data($replace_shipping_address, true);
        }

        // Allow developers to update the shipment request
        $shipment = apply_filters('dhlpwc_shipment_request', $shipment, $order_id, $options);

        return $shipment;
    }

    public function get_return_data($shipment_data)
    {
        /* @var DHLPWC_Model_API_Data_Shipment_Request $shipment_data */
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $alternate_return = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_ALTERNATE_RETURN_ADDRESS);

        if ($alternate_return) {
            $service = DHLPWC_Model_Service_Settings::instance();
            $receiver = $this->prepare_address_data($service->get_return_address()->to_array(), true);
        } else if (!empty($shipment_data->on_behalf_of->address)) {
            $receiver = $shipment_data->on_behalf_of;
        } else {
            $receiver = $shipment_data->shipper;
        }
        $shipper = $shipment_data->receiver;

        $shipment_data->shipment_id = (string)new DHLPWC_Model_UUID();
        $shipment_data->return_label = true;
        $shipment_data->shipper = $shipper;
        $shipment_data->receiver = $receiver;

        $service = DHLPWC_Model_Service_Shipment_Option::instance();
        $shipment_data->options = $service->get_request_options(array(
            DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
        ));

        $shipment_data->on_behalf_of = null;

        return $shipment_data;
    }

    public function check_return_option($label_options)
    {
        return in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL, $label_options);
    }

    public function remove_return_option($label_options)
    {
        return array_diff($label_options, array(
            DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL,
        ));
    }

    public function get_reference_data($label_data)
    {
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE, $label_data)) {
            return null;
        }

        $cleaned_data = wp_unslash(wc_clean($label_data[DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE]));
        return $cleaned_data;
    }

    public function get_reference2_data($label_data)
    {
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE2, $label_data)) {
            return null;
        }

        $cleaned_data = wp_unslash(wc_clean($label_data[DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE2]));
        return $cleaned_data;
    }

    public function get_insurance_data($label_data)
    {
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_INS, $label_data)) {
            return null;
        }

        $cleaned_data = wp_unslash(wc_clean($label_data[DHLPWC_Model_Meta_Order_Option_Preference::OPTION_INS]));
        return $cleaned_data;
    }

    public function get_hide_sender_data($label_data)
    {
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SSN, $label_data)) {
            return null;
        }

        $cleaned_data = wp_unslash(wc_clean($label_data[DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SSN]));
        $parsed_data = json_decode($cleaned_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!$this->validate_flat_address($parsed_data)) {
            return null;
        }

        return $parsed_data;
    }

    public function validate_flat_address($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        // Check if all keys exist
        $expected_keys = array(
            'first_name',
            'last_name',
            'company',
            'postcode',
            'city',
            'number',
            'addition',
            'email',
            'phone',
        );

        foreach($expected_keys as $expected_key) {
            if (!array_key_exists($expected_key, $data)) {
                return false;
            }
        }

        return true;
    }

    public function remove_hide_sender_data($label_data)
    {
        return array_diff_key($label_data, array(
            DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SSN => true,
        ));
    }

    /**
     * @param DHLPWC_Model_API_Data_Shipment_Request $shipment
     * @return array|bool|mixed|null|object
     */
    public function send_request($shipment)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $test_mode = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_IS_TEST_MODE);

        if ($test_mode) {
            return array(
                'shipmentId' => $shipment->shipment_id,
                'product' => 'DFY-B2C',
                'pieces' => array(array(
                    'labelId' => uniqid('TEST-LABEL-ID-'),
                    'trackerCode' => 'JVGL0999999999123456789',
                    'parcelType' => 'SMALL',
                    'pieceNumber' => 1,
                    'labelType' => 'B2X_Generic_A4_Third'
                )),
                'orderReference' => $shipment->order_reference,
                'deliveryArea' => [
                    'remote' => false,
                    'type' => 'NonRemote'
                ]
            );
        }

        $connector = DHLPWC_Model_API_Connector::instance();
        return $connector->post('shipments', $shipment->to_array());
    }

    protected function prepare_address_data($address, $business = false)
    {
        $address = $this->prepare_street_address($address);
        $address = $this->format_postcode($address);

        return new DHLPWC_Model_API_Data_Shipment_Address(array(
            'name'          => array(
                'first_name'   => $address['first_name'],
                'last_name'    => $address['last_name'],
                'company_name' => $address['company'],
            ),
            'address'       => array(
                'country_code' => $address['country'],
                'postal_code'  => $address['postcode'],
                'city'         => $address['city'],
                'street'       => $address['street'],
                'number'       => $address['number'],
                'is_business'  => $business,
                'addition'     => $address['addition'],
            ),
            'email'         => $address['email'],
            'phone_number' => $address['phone'],
        ));
    }

    protected function format_postcode($address)
    {
        $address['postcode'] = WC_Validation::format_postcode( $address['postcode'], $address['country'] );
        switch ($address['country']) {
            case 'CZ':
            case 'DK':
            case 'LV':
            case 'SK':
                $address['postcode'] = str_replace([$address['country'], '-'], '', $address['postcode']);
                break;
        }

        return $address;
    }

    protected function prepare_street_address($address)
    {
        $skip_addition_check = false;
        $skip_reverse_check = false;
        $cutout = '';

        if (!isset($address['street'])) {
            $address['street'] = trim(join(' ', array(
                isset($address['address_1']) ? trim($address['address_1']) : '',
                isset($address['address_2']) ? trim($address['address_2']) : ''
            )));

            // Cutout starting special numbers from regular parsing logic
            $parsable_parts = explode(' ', trim($address['street']), 2);

            // Check if it has a number with letters
            if (preg_match('/[0-9]+[a-z]+/i', reset($parsable_parts)) === 1) {
                $cutout = reset($parsable_parts) . ' ';
                $skip_reverse_check = true;
                unset($parsable_parts[0]);

            // Check if it has a number with more than just letters, but also other available numbers
            } else if (preg_match('/[0-9]+[^0-9]+/', reset($parsable_parts)) === 1 && preg_match('/\d/', end($parsable_parts)) === 1) {
                $cutout = reset($parsable_parts) . ' ';
                $skip_reverse_check = true;
                unset($parsable_parts[0]);

            // Check if it has something before a number
            } else if (preg_match('/[^0-9]+[0-9]+/', reset($parsable_parts)) === 1) {
                $cutout = reset($parsable_parts) . ' ';
                $skip_reverse_check = true;
                unset($parsable_parts[0]);

            // Check if starts with number (with anything), but also has numbers in the rest of the address
            } else if (preg_match('/[^0-9]*[0-9]+[^0-9]*/', reset($parsable_parts)) === 1 && preg_match('/\d/', end($parsable_parts)) === 1) {
                $cutout = reset($parsable_parts) . ' ';
                $skip_reverse_check = true;
                unset($parsable_parts[0]);
            }

            $parsable_street = implode(' ', $parsable_parts);

            preg_match('/([^0-9]*)\s*(.*)/', trim($parsable_street), $street_parts);
            $address = array_merge($address, [
                'street' => isset($street_parts[1]) ? trim($street_parts[1]) : '',
                'number' => isset($street_parts[2]) ? trim($street_parts[2]) : '',
                'addition' => '',
            ]);

            // Check if $street is empty
            if (strlen($address['street']) === 0 && !$skip_reverse_check) {
                // Try a reverse parse
                preg_match('/([\d]+[\w.-]*)\s*(.*)/i', trim($parsable_street), $street_parts);
                $address['street'] = isset($street_parts[2]) ? trim($street_parts[2]) : '';
                $address['number'] = isset($street_parts[1]) ? trim($street_parts[1]) : '';
                $skip_addition_check = true;
            }

            // Check if $number has no numbers
            if (preg_match('/\d/', $address['number']) === 0) {
                $address['street'] = trim($parsable_street);
                $address['number'] = '';

            // Addition check
            } else if (!$skip_addition_check) {
                // If there are no letters, but has additional spaced numbers, use last number as number, no addition
                preg_match('/([^a-z]+)\s+([\d]+)$/i', $address['number'], $number_parts);
                if (isset($number_parts[2])) {
                    $address['street'] .= ' ' . $number_parts[1];
                    $address['number'] = $number_parts[2];

                // Regular number / addition split
                } else {
                    preg_match('/([\d]+)[ .-]*(.*)/i', $address['number'], $number_parts);
                    $address['number'] = isset($number_parts[1]) ? trim($number_parts[1]) : '';
                    $address['addition'] = isset($number_parts[2]) ? trim($number_parts[2]) : '';
                }
            }

            // Reassemble street
            if (isset($address['street'])) {
                $address['street'] = $cutout . $address['street'];
            }
        }

        // Be sure these fields are filled
        $address['number'] = isset($address['number']) ? $address['number'] : '';
        $address['addition'] = isset($address['addition']) ? $address['addition'] : '';

        // Clean any starting punctuations
        preg_match('/^[[:punct:]\s]+(.*)/', $address['street'], $clean_street);
        if (isset($clean_street[1])) {
            $address['street'] = $clean_street[1];
        }
        preg_match('/^[[:punct:]\s]+(.*)/', $address['number'], $clean_number);
        if (isset($clean_number[1])) {
            $address['number'] = $clean_number[1];
        }
        preg_match('/^[[:punct:]\s]+(.*)/', $address['addition'], $clean_addition);
        if (isset($clean_addition[1])) {
            $address['addition'] = $clean_addition[1];
        }

        return $address;
    }

    protected function get_account_id()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        return $shipping_method['account_id'];
    }

}

endif;
