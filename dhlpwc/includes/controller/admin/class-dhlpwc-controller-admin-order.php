<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Admin_Order')) :

class DHLPWC_Controller_Admin_Order
{

    public function __construct()
    {
        if (!is_admin()) {
            return;
        }

        add_action('admin_enqueue_scripts', array($this, 'load_styles'));
        add_action('admin_enqueue_scripts', array($this, 'load_scripts'));

        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'parcelshop_info'), 10, 1);

        $service = DHLPWC_Model_Service_Access_Control::instance();
        $settings_service = DHLPWC_Model_Service_Settings::instance();
        if ($settings_service->is_hpos_enabled()) {
            add_filter('handle_bulk_actions-woocommerce_page_wc-orders', array($this, 'handle_wc_bulk_actions'), 10, 2);
        }

        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_COLUMN_INFO)) {
            if ($settings_service->is_hpos_enabled()) {
                add_filter('woocommerce_shop_order_list_table_columns', array($this, 'add_label_column'), 10, 1);
                add_action('woocommerce_shop_order_list_table_custom_column', array($this, 'add_label_column_content'), 10, 2);
            } else {
                add_filter('manage_edit-shop_order_columns', array($this, 'add_label_column'), 10, 1);
                add_action('manage_shop_order_posts_custom_column', array($this, 'add_label_column_content'), 10, 2);
            }
        }

        if ($bulk_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_CREATE)) {
            $bulk_services = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_SERVICES);
            if ($settings_service->is_hpos_enabled()) {
                add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'add_bulk_create_actions'));
            } else {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_create_actions'));
            }
            foreach ($bulk_options as $bulk_option) {
                add_action('admin_action_dhlpwc_create_labels_' . $bulk_option, function() {
                    if (empty($_REQUEST['action'])) {
                        return;
                    }

                    $bulk_parameters = $this->split_bulk_parameters($_REQUEST['action']);

                    $this->create_multiple_labels($bulk_parameters['size'], $bulk_parameters['service_options']);
                });

                if ($bulk_services) {
                    foreach ($bulk_services as $bulk_service) {
                        add_action('admin_action_dhlpwc_create_labels_' . $bulk_option. '_service_' . $bulk_service, function() {
                            if (empty($_REQUEST['action'])) {
                                return;
                            }

                            $bulk_parameters = $this->split_bulk_parameters($_REQUEST['action']);

                            $this->create_multiple_labels($bulk_parameters['size'], $bulk_parameters['service_options']);
                        });
                    }
                }
            }
            add_action('admin_notices', array($this, 'bulk_create_notice'));
        }

        add_action('admin_action_dhlpwc_download_label', array($this, 'download_label'));

        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_DOWNLOAD)) {
            if ($settings_service->is_hpos_enabled()) {
                add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'add_bulk_download_action'));
            } else {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_download_action'));
                add_action('admin_action_dhlpwc_download_labels', array($this, 'download_multiple_labels'));
            }
        }

        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_PRINTER)) {
            if ($settings_service->is_hpos_enabled()) {
                add_filter('bulk_actions-woocommerce_page_wc-orders', array($this, 'add_bulk_print_action'));
            } else {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_print_action'));
                add_action('admin_action_dhlpwc_print_labels', array($this, 'print_multiple_labels'));
            }
            add_action('admin_notices', array($this, 'bulk_print_notice'));
        }

        if ($settings_service->is_hpos_enabled()) {
            add_filter('woocommerce_shop_order_list_table_columns', array($this, 'add_delivery_time_column'), 10, 1);
            add_action('woocommerce_shop_order_list_table_custom_column', array($this, 'add_delivery_time_column_content'), 10, 2);
        } else {
            add_filter('manage_edit-shop_order_columns', array($this, 'add_delivery_time_column'));
            add_action( 'manage_shop_order_posts_custom_column', array($this, 'add_delivery_time_column_content'), 10, 2 );
        }

        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES)) {
            if ($settings_service->is_hpos_enabled()) {
                add_filter('views_woocommerce_page_wc-orders', array($this, 'add_delivery_times_filter'), 10, 1);
                add_filter('manage_woocommerce_page_wc-orders_sortable_columns', array($this, 'sort_delivery_time_column'));
                add_filter('woocommerce_order_list_table_prepare_items_query_args', array($this, 'delivery_date_orderby_hpos'));

            } else {
                add_filter('views_edit-shop_order', array($this, 'add_delivery_times_filter'), 10, 1);
                add_filter('manage_edit-shop_order_sortable_columns', array($this, 'sort_delivery_time_column'));
                add_action('pre_get_posts', array($this, 'delivery_date_orderby'));
            }
        }
    }

    public function handle_wc_bulk_actions($redirect_to, $action)
    {
        if (substr($action, 0, 6) !== 'dhlpwc') {
            return $redirect_to;
        }

        switch ($action) {
            case 'dhlpwc_download_label':
                $this->download_label();
                break;
            case 'dhlpwc_download_labels':
                $this->download_multiple_labels();
                break;
            case 'dhlpwc_print_labels':
                $this->print_multiple_labels();
                break;
            default:
                $bulk_parameters = $this->split_bulk_parameters($action);

                $this->create_multiple_labels($bulk_parameters['size'], $bulk_parameters['service_options']);
                break;
        }
    }

    public function add_delivery_times_filter($views)
    {
        $result = wc_get_orders(array(
            'status' => $this->get_available_statuses(),
            'meta_query' => array(array(
            'key'     => DHLPWC_Model_Service_Delivery_Times::ORDER_TIME_SELECTION,
            'value' => serialize('timestamp'),
            'compare' => 'LIKE',
        ))));

        $url = 'edit.php?post_type=shop_order&orderby=dhlpwc_delivery_date&order=asc';
        $settings_service = DHLPWC_Model_Service_Settings::instance();
        if ($settings_service->is_hpos_enabled()) {
            $url = 'admin.php?page=wc-orders&orderby=dhlpwc_delivery_date&order=asc';
        }

        $views['dhlpwc_delivery_date'] = sprintf('%1$s%2$s%3$s%4$s',
            '<a href="'.admin_url($url).'">',
            esc_attr(__('Delivery date', 'dhlpwc')),
            '</a>',
            '<span class="count">(' . count($result) . ')</span>');

        return $views;
    }

    public function sort_delivery_time_column($columns)
    {
        $columns['dhlpwc_delivery_time'] = 'dhlpwc_delivery_date';

        return $columns;
    }

    public function delivery_date_orderby($query)
    {
        if (!$this->is_ordergrid_screen()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'dhlpwc_delivery_date') {
            $meta_query = array(
                array(
                    'key'     => DHLPWC_Model_Service_Delivery_Times::ORDER_TIME_SELECTION,
                    'value' => serialize('timestamp'),
                    'compare' => 'LIKE',
                ),
            );

            $query->set('meta_query', $meta_query);
            $query->set('post_status', $this->get_available_statuses());
            $query->set('orderby', 'meta_value');
        }
    }

    public function delivery_date_orderby_hpos($query)
    {
        $settings_service = DHLPWC_Model_Service_Settings::instance();
        if ($settings_service->is_hpos_enabled() && isset($_GET['orderby']) && $_GET['orderby'] === 'dhlpwc_delivery_date') {
            $query['meta_key'] = '_dhlpwc_order_time_selection';
            $query['orderby'] = 'meta_value';
            $query['order'] = isset($_GET['order']) ? wc_clean($_GET['order']) : 'asc';
            $query['status'] = $this->get_available_statuses();
        }

        return $query;
    }

    public function add_delivery_time_column($columns)
    {
        $orders = wc_get_orders(array('meta_query' => array(array(
            'key'     => DHLPWC_Model_Service_Delivery_Times::ORDER_TIME_SELECTION,
            'value' => serialize('timestamp'),
            'compare' => 'LIKE',
        ))));

        if (count($orders) > 0) {
            $columns['dhlpwc_delivery_time'] = __('Delivery date', 'dhlpwc');
        }

        return $columns;
    }

    public function add_delivery_time_column_content($column, $order_id)
    {
        if ($column !== 'dhlpwc_delivery_time') {
            return;
        }

        $service = DHLPWC_Model_Service_Delivery_Times::instance();
        $time_selection = $service->get_order_time_selection($order_id);

        if (!$time_selection) {
            return;
        }

        $current_timestamp = time();
        $time_left = human_time_diff($current_timestamp, $time_selection->timestamp);

        if ($current_timestamp > $time_selection->timestamp) {
            $time_left = null;
        }

        $option_service = DHLPWC_Model_Service_Order_Meta_Option::instance();
        $preselected_options = $option_service->get_keys($order_id);

        $delivery_time = $service->parse_time_frame($time_selection->date, $time_selection->start_time, $time_selection->end_time);
        $shipping_advice = $service->get_shipping_advice($time_selection->timestamp, $preselected_options);
        $shipping_advice_class = $service->get_shipping_advice_class($time_selection->timestamp, $preselected_options);

        if (!empty($delivery_time)) {
            $view = new DHLPWC_Template('admin.order.delivery-times');
            $view->render(array(
                'time_left'             => $time_left,
                'delivery_time'         => $delivery_time,
                'shipping_advice'       => $shipping_advice,
                'shipping_advice_class' => $shipping_advice_class,
            ));
        }
    }

    public function add_bulk_create_actions($bulk_actions)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $bulk_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_CREATE);
        $bulk_services = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_SERVICES);

        $dhl_actions = [];

        foreach ($bulk_options as $bulk_option) {
            $bulk_string = DHLPWC_Model_Service_Translation::instance()->bulk($bulk_option);
            $dhl_actions['dhlpwc_create_labels_' . $bulk_option] = sprintf(__('DHL - Create label (%s)', 'dhlpwc'), $bulk_string);

            if ($bulk_services) {
                foreach ($bulk_services as $bulk_service) {
                    $dhl_actions['dhlpwc_create_labels_' . $bulk_option . '_service_' . $bulk_service] = sprintf('%s (+%s)', $dhl_actions['dhlpwc_create_labels_' . $bulk_option], DHLPWC_Model_Service_Translation::instance()->option(strtoupper($bulk_service)));
                }
            }
        }

        return $dhl_actions + $bulk_actions;
    }

    public function create_multiple_labels($option, $service_options = array())
    {
        $order_ids = isset($_GET['post']) && is_array($_GET['post']) ? wc_clean($_GET['post']) : array();
        if (empty($order_ids)) {
            $order_ids = isset($_GET['id']) && is_array($_GET['id']) ? wc_clean($_GET['id']) : array();
        }

        if (empty($order_ids)) {
            $order_ids = isset($_GET['order']) && is_array($_GET['order']) ? wc_clean($_GET['order']) : array();
        }

        if (empty($order_ids)) {
            return;
        }

        $service = DHLPWC_Model_Service_Shipment::instance();
        $success_data = $service->bulk($order_ids, $option, $service_options);

        $download_id = crc32(json_encode($order_ids));
        set_transient('dhlpwc_bulk_download_' . $download_id, $order_ids, 1 * DAY_IN_SECONDS);

        $query_vars = array(
            'post_type'             => 'shop_order',
            'dhlpwc_labels_created' => 1,
            'dhlpwc_create_count'   => $success_data['success'],
            'dhlpwc_fail_count'     => $success_data['fail'],
            'dhlpwc_download_id'    => $download_id,
        );
        $query_vars = apply_filters('dhlpwc_create_redirect_query_array', $query_vars);

        $location = add_query_arg($query_vars, 'edit.php');
        $location = apply_filters('dhlpwc_create_redirect_admin_url', $location);

        $settings_service = DHLPWC_Model_Service_Settings::instance();
        if ($settings_service->is_hpos_enabled()) {
            $query_vars = array(
                'page'                  => 'wc-orders',
                'dhlpwc_labels_created' => 1,
                'dhlpwc_create_count'   => $success_data['success'],
                'dhlpwc_fail_count'     => $success_data['fail'],
                'dhlpwc_download_id'    => $download_id,
            );
            $query_vars = apply_filters('dhlpwc_create_redirect_query_array', $query_vars);

            $location = add_query_arg($query_vars, 'admin.php');
            $location = apply_filters('dhlpwc_create_redirect_admin_url', $location);
        }

        wp_redirect(admin_url($location));
        exit;
    }

    public function bulk_create_notice()
    {
        if ($this->is_ordergrid_screen()) {
            if (isset($_GET['dhlpwc_labels_created'])) {
                $created = isset($_GET['dhlpwc_create_count']) && is_numeric($_GET['dhlpwc_create_count']) ? wc_clean($_GET['dhlpwc_create_count']) : 0;
                $failed = isset($_GET['dhlpwc_fail_count']) && is_numeric($_GET['dhlpwc_fail_count']) ? wc_clean($_GET['dhlpwc_fail_count']) : 0;
                $download_id = isset($_GET['dhlpwc_download_id']) && is_numeric($_GET['dhlpwc_download_id']) ? wc_clean($_GET['dhlpwc_download_id']) : null;

                $messages = array();
                if ($created) {
                    $messages[] = sprintf(_n('Label successfully created.', '%s labels successfully created.', number_format_i18n($created), 'dhlpwc'), number_format_i18n($created));
                }
                if ($failed) {
                    $messages[] = sprintf(_n('Label could not be created.', '%s labels failed to create.', number_format_i18n($failed), 'dhlpwc'), number_format_i18n($failed));
                }

                // Create action links
                $links = array();
                $service = DHLPWC_Model_Service_Access_Control::instance();

                if ($created && $download_id) {
                    $order_ids = get_transient('dhlpwc_bulk_download_' . $download_id);
                    if (!empty($order_ids) && is_array($order_ids)) {
                        $order_texts = preg_filter('/^/', '#', $order_ids);
                        $order_list = implode(', ', $order_texts);

                        $settings_service = DHLPWC_Model_Service_Settings::instance();
                        $hpos_enabled = $settings_service->is_hpos_enabled();
                        $nonce = wp_create_nonce('bulk-orders');

                        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_DOWNLOAD)) {
                            $url = admin_url('edit.php?post_type=shop_order&action=dhlpwc_download_labels');
                            if ($hpos_enabled) {
                                $url = add_query_arg(
                                    array(
                                        'page' => 'wc-orders',
                                        '_wpnonce' => $nonce,
                                        'action' => 'dhlpwc_download_labels',
                                        // Omitting the id parameter somehow causes a redirect without our action being called
                                        'id' => ''
                                    ), admin_url('admin.php'));
                            }
                            $link = new DHLPWC_Model_Data_Notice_Custom_Links();
                            $link->url = add_query_arg(array(
                                'post' => $order_ids,
                            ), $url);
                            $link->message = sprintf(_n('%sDownload label%s For order: %s.', '%sDownload labels%s For orders: %s.', count($order_ids), 'dhlpwc'), '%s', '%s', $order_list);
                            $link->target = '_blank';
                            $links[] = $link;
                        }

                        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_PRINTER)) {
                            $url = admin_url('edit.php?post_type=shop_order&action=dhlpwc_print_labels');
                            if ($hpos_enabled) {
                                $url = add_query_arg(array(
                                    'page' => 'wc-orders',
                                    '_wpnonce' => $nonce,
                                    'action' => 'dhlpwc_print_labels',
                                    // Omitting the id parameter somehow causes a redirect without our action being called
                                    'id' => ''
                                ), admin_url('admin.php'));
                            }

                            $link = new DHLPWC_Model_Data_Notice_Custom_Links();
                            $link->url = add_query_arg(array(
                                'post' => $order_ids,
                            ), $url);
                            $link->message = sprintf(_n('%sPrint label%s For order: %s.', '%sPrint labels%s For orders: %s.', count($order_ids), 'dhlpwc'), '%s', '%s', $order_list);
                            $link->target = '_self';
                            $links[] = $link;
                        }
                    }
                }

                if (!empty($messages)) {
                    $view = new DHLPWC_Template('admin.notice');
                    $view->render(array(
                        'messages'     => $messages,
                        'custom_links' => $links,
                    ));
                }
            }
        }
    }

    public function download_label()
    {
        $label_id = isset($_GET['label_id']) && is_string($_GET['label_id']) ? wc_clean($_GET['label_id']) : null;

        if (!$label_id) {
            wp_redirect('');
        }

        $service = DHLPWC_Model_Service_Label::instance();
        $path = $service->single($label_id);

        if (!$path) {
            wp_redirect('');
        }

        $file = explode(DIRECTORY_SEPARATOR, $path);
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.end($file).'"');
        header('Cache-Control: must-revalidate');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function add_bulk_download_action($bulk_actions)
    {
        $dhl_actions = ['dhlpwc_download_labels' => __('DHL - Download label', 'dhlpwc')];

        return $dhl_actions + $bulk_actions;
    }

    public function download_multiple_labels()
    {
        $order_ids = isset($_GET['post']) && is_array($_GET['post']) ? wc_clean($_GET['post']) : array();
        if (empty($order_ids)) {
            $order_ids = isset($_GET['id']) && is_array($_GET['id']) ? wc_clean($_GET['id']) : array();
        }

        if (empty($order_ids)) {
            $order_ids = isset($_GET['order']) && is_array($_GET['order']) ? wc_clean($_GET['order']) : array();
        }

        $service = DHLPWC_Model_Service_Label::instance();
        $path = $service->combine($order_ids);

        if (!$path) {
            wp_redirect('');
        }

        $file = explode(DIRECTORY_SEPARATOR, $path);
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.end($file).'"');
        header('Cache-Control: must-revalidate');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function add_bulk_print_action($bulk_actions)
    {
        $dhl_actions = ['dhlpwc_print_labels' => __('DHL - Print label', 'dhlpwc')];

        return $dhl_actions + $bulk_actions;
    }

    public function print_multiple_labels()
    {
        $order_ids = isset($_GET['post']) && is_array($_GET['post']) ? wc_clean($_GET['post']) : array();
        if (empty($order_ids)) {
            $order_ids = isset($_GET['id']) && is_array($_GET['id']) ? wc_clean($_GET['id']) : array();
        }

        if (empty($order_ids)) {
            $order_ids = isset($_GET['order']) && is_array($_GET['order']) ? wc_clean($_GET['order']) : array();
        }

        $order_count = intval(count($order_ids));

        $service = DHLPWC_Model_Service_Printer::instance();
        $label_ids = $service->get_label_ids($order_ids);
        $label_count = intval(count($label_ids));

        $success = $service->send($label_ids);

        $download_id = crc32(json_encode($order_ids));
        set_transient('dhlpwc_bulk_download_' . $download_id, $order_ids, 1 * DAY_IN_SECONDS);

        $query_vars = array(
            'post_type'             => 'shop_order',
            'dhlpwc_labels_printed' => 1,
            'dhlpwc_order_count'    => $order_count,
            'dhlpwc_label_count'    => $label_count,
            'dhlpwc_success'        => $success ? 'true' : 'false',
            'dhlpwc_download_id'    => $download_id,
        );
        $query_vars = apply_filters('dhlpwc_print_redirect_query_array', $query_vars);

        $location = add_query_arg($query_vars, 'edit.php');
        $location = apply_filters('dhlpwc_print_redirect_admin_url', $location);

        wp_redirect(admin_url($location));
        exit;
    }

    public function bulk_print_notice()
    {
        if ($this->is_ordergrid_screen()) {
            if (isset($_GET['dhlpwc_labels_printed'])) {
                $orders = isset($_GET['dhlpwc_order_count']) && is_numeric($_GET['dhlpwc_order_count']) ? wc_clean($_GET['dhlpwc_order_count']) : 0;
                $labels = isset($_GET['dhlpwc_label_count']) && is_numeric($_GET['dhlpwc_label_count']) ? wc_clean($_GET['dhlpwc_label_count']) : 0;
                $success = isset($_GET['dhlpwc_success']) ? boolval(wc_clean($_GET['dhlpwc_success']) == 'true') : false;
                $download_id = isset($_GET['dhlpwc_download_id']) && is_numeric($_GET['dhlpwc_download_id']) ? wc_clean($_GET['dhlpwc_download_id']) : null;

                $messages = array();
                if ($orders) {
                    $messages[] = sprintf(_n('Order processed.', '%s orders processed.', number_format_i18n($orders), 'dhlpwc'), number_format_i18n($orders));
                }
                if ($success && $labels) {
                    $messages[] = sprintf(_n('Label sent to printer.', '%s labels sent to printer.', number_format_i18n($labels), 'dhlpwc'), number_format_i18n($labels));
                } else {
                    $messages[] = sprintf(_n('Failed to print label.', 'Failed to print labels.', number_format_i18n($labels), 'dhlpwc'), number_format_i18n($labels));
                }

                // Create action links
                $links = array();
                $service = DHLPWC_Model_Service_Access_Control::instance();
                if ($download_id) {
                    $order_ids = get_transient('dhlpwc_bulk_download_' . $download_id);
                    if (!empty($order_ids) && is_array($order_ids)) {
                        $order_texts = preg_filter('/^/', '#', $order_ids);
                        $order_list = implode(', ', $order_texts);

                        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_DOWNLOAD)) {
                            $link = new DHLPWC_Model_Data_Notice_Custom_Links();
                            $link->url = add_query_arg(array(
                                'post' => $order_ids,
                            ), admin_url('edit.php?post_type=shop_order&action=dhlpwc_download_labels'));
                            $link->message = sprintf(_n('%sDownload label%s For order: %s.', '%sDownload labels%s For orders: %s.', count($order_ids), 'dhlpwc'), '%s', '%s', $order_list);
                            $link->target = '_blank';
                            $links[] = $link;
                        }
                    }
                }

                if (!empty($messages)) {
                    $view = new DHLPWC_Template('admin.notice');
                    $view->render(array(
                        'messages'   => $messages,
                    ));
                }
            }
        }
    }

    public function add_label_column($columns)
    {
        $offset = (integer)array_search("order_total", array_keys($columns));
        return array_slice($columns, 0, ++$offset, true) +
            array('dhlpwc_label_created' => __('DHL label info', 'dhlpwc')) +
            array_slice($columns, $offset, null, true);
    }

    public function add_label_column_content($column, $order_id)
    {
        switch($column) {
            case 'dhlpwc_label_created':
                $service = DHLPWC_Model_Service_Access_Control::instance();
                $external_link = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPEN_LABEL_LINKS_EXTERNAL);

                $service = DHLPWC_Model_Service_Order_Meta::instance();
                $labels = $service->get_labels($order_id);

                foreach($labels as $label) {
                    $view = new DHLPWC_Template('order.meta.label');
                    if (!is_array($label) || !isset($label['label_size']) || !isset($label['tracker_code'])) {
                        continue;
                    }
                    $is_return = (!empty($label['is_return'])) ? $label['is_return'] : false;
                    $logic = DHLPWC_Model_Logic_Label::instance();

                    $view->render(array(
                        'url'               => $logic->get_pdf_url($label),
                        'label_size'        => $label['label_size'],
                        'label_description' => DHLPWC_Model_Service_Translation::instance()->parcelType($label['label_size']),
                        'tracker_code'      => $label['tracker_code'],
                        'is_return'         => $is_return,
                        'external_link'     => $external_link,
                    ));
                }
                break;
            case 'shipping_address':
                $this->parcelshop_info(new WC_Order($order_id), true);
                break;
        }
    }

    /**
     * DHL ServicePoint information screen for an order.
     *
     * @param WC_Order $order
     * @param bool $compact
     */
    public function parcelshop_info($order, $compact = false)
    {
        $service = new DHLPWC_Model_Service_Order_Meta_Option();
        $parcelshop_meta = $service->get_option_preference($order->get_id(), DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS);

        if ($parcelshop_meta) {
            $service = new DHLPWC_Model_Service_Parcelshop();
            if (is_callable(array($order, 'get_shipping_country'))) {
                // WooCommerce 3.2.0+
                $parcelshop = $service->get_parcelshop($parcelshop_meta['input'], $order->get_shipping_country());
            } else {
                // WooCommerce < 3.2.0
                $parcelshop = $service->get_parcelshop($parcelshop_meta['input'], $order->shipping_country);
            }

            if (!$parcelshop || !isset($parcelshop->name) || !isset($parcelshop->address)) {
                $view = new DHLPWC_Template('unavailable');
                $view->render();
                return;
            }

            $view = new DHLPWC_Template('parcelshop-info');
            $view->render(array(
                'warning' => __('Send to DHL ServicePoint', 'dhlpwc'),
                'name' => $parcelshop->name,
                'address' => $parcelshop->address,
                'compact' => $compact
            ));
        }
    }

    public function load_styles()
    {
        if ($this->is_ordergrid_screen()) {
            wp_enqueue_style('dhlpwc-admin-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.css', array(), DHLPWC_PLUGIN_VERSION);
        }
    }

    public function load_scripts()
    {
        if ($this->is_ordergrid_screen()) {
            $service = DHLPWC_Model_Service_Access_Control::instance();
            $external_link = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPEN_LABEL_LINKS_EXTERNAL);
            if ($external_link) {
                wp_enqueue_script('dhlpwc-admin-external', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.admin.external.js', array('jquery'), DHLPWC_PLUGIN_VERSION);
            }
        }
    }

    protected function is_ordergrid_screen()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!isset($screen)) {
            return false;
        }

        $settings_service = DHLPWC_Model_Service_Settings::instance();

        return $settings_service->is_hpos_enabled()
            ? $screen->base == wc_get_page_screen_id( 'shop-order' )
            : $screen->base == 'edit' && $screen->post_type == 'shop_order';
    }

    protected function get_available_statuses()
    {
        $statuses = wc_get_order_statuses();

        unset($statuses['wc-completed']);
        unset($statuses['wc-refunded']);
        unset($statuses['wc-failed']);

        return array_keys($statuses);
    }

    protected function split_bulk_parameters($action_name)
    {
        $size_services = substr($action_name, strlen('dhlpwc_create_labels_'));
        $parameters = explode('_service_', $size_services);

        return array (
            'size' => array_shift($parameters),
            'service_options' => array_map('strtoupper', $parameters)
        );
    }

}

endif;
