<?php
/*
 * Plugin Name: Dibsy Checkout
 * Plugin URI: https://docs.dibsy.one/plugins/woo-commerce
 * Description: Accept credit card payments on your store using Dibsy.
 * Author: Dibsy
 * Author URI: http://dibsy.one
 * Version: 2.0.1
 * Requires at least: 5.0
 * Tested up to: 5.8.1
 * WC requires at least: 3.0
 * WC tested up to: 5.7
 * 
 */


/**
 * Required minimums and constants
 */
define('WC_DIBSY_VERSION', '2.0.1');
define('WC_DIBSY_MIN_WC_VER', '3.0');
define('WC_DIBSY_MAIN_FILE', __FILE__);
define('WC_DIBSY_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));



/**
 * WooCommerce is required 
 */
function woocommerce_dibsy_missing_wc_notice()
{
    echo '<div class="error"><p><strong>' . sprintf('Dibsy requires WooCommerce to be installed and active. You can download it from %s', '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

add_filter('woocommerce_payment_gateways', 'dibsy_gateway_class');
function dibsy_gateway_class($gateways)
{
    $gateways[] = 'WC_Dibsy_Gateway';
    $gateways[] = 'WC_Dibsy_NAPS_Gateway'; // your class name is here
    return $gateways;
}


// init the dibsy gateway
function woocommerce_gateway_dibsy()
{
    static $plugin;

    if (!isset($plugin)) {

        class WC_Dibsy
        {

            /**
             * The *Singleton* instance of this class
             *
             * @var Singleton
             */
            private static $instance;

            /**
             * Returns the *Singleton* instance of this class.
             *
             * @return Singleton The *Singleton* instance.
             */
            public static function get_instance()
            {
                if (null === self::$instance) {
                    self::$instance = new self();
                }
                return self::$instance;
            }



            /**
             * Private clone method to prevent cloning of the instance of the
             * *Singleton* instance.
             *
             * @return void
             */
            public function __clone()
            {
            }

            /**
             * Private unserialize method to prevent unserializing of the *Singleton*
             * instance.
             *
             * @return void
             */
            public function __wakeup()
            {
            }

            /**
             * Protected constructor to prevent creating a new instance of the
             * *Singleton* via the `new` operator from outside of this class.
             */
            public function __construct()
            {
                add_action('admin_init', [$this, 'install']);

                $this->init();
            }

            /**
             * Init the plugin after plugins_loaded so environment variables are set.
             *
             * @since 1.0.0
             * @version 5.0.0
             */
            public function init()
            {
                require_once dirname(__FILE__) . '/includes/utils/wc-dibsy-payment.php';
                require_once dirname(__FILE__) . '/includes/utils/wc-dibsy-logger.php';
                require_once dirname(__FILE__) . '/includes/utils/wc-dibsy-exception.php';
                require_once dirname(__FILE__) . '/includes/utils/wc-dibsy-helper.php';
                require_once dirname(__FILE__) . '/includes/utils/wc-dibsy-api.php';
                require_once dirname(__FILE__) . '/includes/abstracts/wc-gateway-dibsy-abstract.php';
                require_once dirname(__FILE__) . '/includes/webhooks/wc-dibsy-webhook-state.php';
                require_once dirname(__FILE__) . '/includes/webhooks/wc-dibsy-webhook-handler.php';
                require_once dirname(__FILE__) . '/includes/payment-methods/wc-gateway-dibsy-creditcard.php';
                //require_once dirname(__FILE__) . '/includes/payment-methods/wc-gateway-dibsy-naps.php';
                require_once dirname(__FILE__) . '/includes/controllers/wc-dibsy-payment-controller.php';


                add_filter('woocommerce_payment_gateways', [$this, 'add_gateways']);
                add_filter('pre_update_option_woocommerce_dibsy-v2_settings', [$this, 'gateway_settings_update'], 10, 2);
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);
                add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);

                if (version_compare(WC_VERSION, '3.4', '<')) {
                    add_filter('woocommerce_get_sections_checkout', [$this, 'filter_gateway_order_admin']);
                }
            }

            /**
             * Updates the plugin version in db
             *
             * @since 3.1.0
             * @version 4.0.0
             */
            public function update_plugin_version()
            {
                delete_option('wc_dibsy_version');
                update_option('wc_dibsy_version', WC_DIBSY_VERSION);
            }

            /**
             * Handles upgrade routines.
             *
             * @since 3.1.0
             * @version 3.1.0
             */
            public function install()
            {
                if (!is_plugin_active(plugin_basename(__FILE__))) {
                    return;
                }

                if (!defined('IFRAME_REQUEST') && (WC_DIBSY_VERSION !== get_option('wc_dibsy_version'))) {
                    do_action('woocommerce_dibsy_updated');

                    if (!defined('WC_DIBSY_INSTALLING')) {
                        define('WC_DIBSY_INSTALLING', true);
                    }
                    $this->update_plugin_version();
                }
            }

            /**
             * Add plugin action links.
             *
             * @since 1.0.0
             * @version 4.0.0
             */
            public function plugin_action_links($links)
            {
                $plugin_links = [
                    '<a href="admin.php?page=wc-settings&tab=checkout&section=dibsy">' . esc_html__('Settings', 'woocommerce-gateway-dibsy') . '</a>',
                ];
                return array_merge($plugin_links, $links);
            }

            /**
             * Add plugin action links.
             *
             * @since 4.3.4
             * @param  array  $links Original list of plugin links.
             * @param  string $file  Name of current file.
             * @return array  $links Update list of plugin links.
             */
            public function plugin_row_meta($links, $file)
            {
                if (plugin_basename(__FILE__) === $file) {
                    $row_meta = [
                        'docs'    => '<a href="' . esc_url(apply_filters('woocommerce_gateway_dibsy_docs_url', 'https://docs.woocommerce.com/document/dibsy/')) . '" title="' . esc_attr(__('View Documentation', 'woocommerce-gateway-dibsy')) . '">' . __('Docs', 'woocommerce-gateway-dibsy') . '</a>',
                        'support' => '<a href="' . esc_url(apply_filters('woocommerce_gateway_dibsy_support_url', 'https://woocommerce.com/my-account/create-a-ticket?select=18627')) . '" title="' . esc_attr(__('Open a support request at WooCommerce.com', 'woocommerce-gateway-dibsy')) . '">' . __('Support', 'woocommerce-gateway-dibsy') . '</a>',
                    ];
                    return array_merge($links, $row_meta);
                }
                return (array) $links;
            }

            /**
             * Add the gateways to WooCommerce.
             *
             * @since 1.0.0
             * @version 4.0.0
             */
            public function add_gateways($methods)
            {

                // you can always add many gateway options if you want
                $methods[] = 'WC_Dibsy_Gateway';
                $methods[] = 'WC_Dibsy_NAPS_Gateway';

                return $methods;
            }

            /**
             * Modifies the order of the gateways displayed in admin.
             *
             * @since 4.0.0
             * @version 4.0.0
             */
            public function filter_gateway_order_admin($sections)
            {
                unset($sections['dibsy']);

                $sections['dibsy']            = 'Dibsy';

                return $sections;
            }

            /**
             * Provide default values for missing settings on initial gateway settings save.
             *
             * @since 4.5.4
             * @version 4.5.4
             *
             * @param array      $settings New settings to save.
             * @param array|bool $old_settings Existing settings, if any.
             * @return array New value but with defaults initially filled in for missing settings.
             */
            public function gateway_settings_update($settings, $old_settings)
            {
                if (false === $old_settings) {
                    $gateway  = new WC_Dibsy_Gateway();
                    $fields   = $gateway->get_form_fields();
                    $defaults = array_merge(array_fill_keys(array_keys($fields), ''), wp_list_pluck($fields, 'default'));
                    return array_merge($defaults, $settings);
                }
                return $settings;
            }
        }

        $plugin = WC_Dibsy::get_instance();
    }

    return $plugin;
}

/**
 * Display the test mode notice.
 **/
function dibsy_testmode_notice()
{

    if (!current_user_can('manage_options')) {
        return;
    }

    $dibsy_settings = get_option('woocommerce_dibsy-v2_settings');
    $test_mode         = isset($dibsy_settings['testmode']) ? $dibsy_settings['testmode'] : '';

    if ('yes' === $test_mode) {
        echo '<div class="error"><p>Dibsy test mode is still enabled, Click <strong><a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=dibsy-v2')) . '">here</a></strong> to disable it when you want to start accepting live payment on your site.</p></div>';
    }
}

add_action('plugins_loaded', 'dibsy_init_gateway_class');
function dibsy_init_gateway_class()
{

    // woocommerce is required for dibsy gateway
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'woocommerce_dibsy_missing_wc_notice');
        return;
    }


    if (version_compare(WC_VERSION, WC_DIBSY_MIN_WC_VER, '<')) {
        add_action('admin_notices', 'woocommerce_dibsy_wc_not_supported');
        return;
    }

    add_action('admin_notices', 'dibsy_testmode_notice');


    // init the gateways 
    woocommerce_gateway_dibsy();
}
