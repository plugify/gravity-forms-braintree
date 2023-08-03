<?php
/**
 * Plugin Name: Gravity Forms Braintree Payments
 * Plugin URI: https://angelleye.com/products/gravity-forms-braintree-payments
 * Description: Allow your customers to purchase goods and services through Gravity Forms via Braintree Payments.
 * Author: Angell EYE
 * Version: 4.0.7
 * Author URI: https://angelleye.com
 * Text Domain: angelleye-gravity-forms-braintree

 *************
 * Attribution
 *************
 * This plugin is a derivative work of the code from Plugify,
 * which is licensed with GPLv2.
 */

// Ensure WordPress has been bootstrapped
if( !defined( 'ABSPATH' ) ) {
    exit;
}

if (!defined('AEU_ZIP_URL')) {
    define('AEU_ZIP_URL', 'https://updates.angelleye.com/ae-updater/angelleye-updater/angelleye-updater.zip');
}

if (!defined('GRAVITY_FORMS_BRAINTREE_ASSET_URL')) {
    define('GRAVITY_FORMS_BRAINTREE_ASSET_URL', plugin_dir_url(__FILE__));
}

if (!defined('PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL')) {
    define('PAYPAL_FOR_WOOCOMMERCE_PUSH_NOTIFICATION_WEB_URL', 'https://www.angelleye.com/');
}

require_once dirname(__FILE__) . '/includes/angelleye-gravity-braintree-activator.php';
require_once dirname(__FILE__) . '/includes/angelleye-plugin-requirement-checker.php';

class AngelleyeGravityFormsBraintree{

    protected static $instance = null;
    public static $plugin_base_file;
    public static $version = '4.0.7';

    public static function getInstance()
    {
        self::$plugin_base_file = plugin_basename(__FILE__);
        if(self::$instance==null)
            self::$instance = new AngelleyeGravityFormsBraintree();

        return self::$instance;
    }

    public function __construct()
    {
        register_activation_hook( __FILE__, array(AngelleyeGravityBraintreeActivator::class,"InstallDb") );
        register_deactivation_hook( __FILE__, array(AngelleyeGravityBraintreeActivator::class,"DeactivatePlugin") );
        register_uninstall_hook( __FILE__, array(AngelleyeGravityBraintreeActivator::class,'Uninstall'));

        add_action('plugins_loaded', [$this, 'requirementCheck']);
	    add_action( 'wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }


    public function enqueue_scripts() {
	    wp_register_script('braintreegateway-dropin', "https://js.braintreegateway.com/web/dropin/1.26.0/js/dropin.min.js");
	    wp_enqueue_script('braintreegateway-dropin');
    }

	public function requirementCheck() {
		$checker = new Angelleye_Plugin_Requirement_Checker('Gravity Forms Braintree Payments', self::$version, self::$plugin_base_file);
		$checker->setPHP('7.2');
		$checker->setRequiredClasses(['GFForms' => 'The Gravity Forms plugin is required in order to run Gravity Forms Braintree Payments.']);
		$checker->setRequiredExtensions(['xmlwriter', 'openssl', 'dom', 'hash', 'curl']);
		$checker->setRequiredPlugins(['gravityforms/gravityforms.php'=>['min_version'=>'2.4', 'install_link'=>'https://rocketgenius.pxf.io/c/1331556/445235/7938', 'name'=>'Gravity Forms']]);
		//$checker->setDeactivatePlugins([self::$plugin_base_file]);
		if($checker->check()===true) {
			$this->init();
		}
    }

    public function init()
    {
        $path = trailingslashit( dirname( __FILE__ ) );

        // Ensure Gravity Forms (payment addon framework) is installed and good to go
        if( is_callable( array( 'GFForms', 'include_payment_addon_framework' ) ) ) {

            // Bootstrap payment addon framework
            GFForms::include_payment_addon_framework();
	        GFForms::include_addon_framework();

            // Require Braintree Payments core
	        if(!class_exists('Braintree')) {
		        require_once $path . 'lib/Braintree.php';
	        }

            // Require plugin entry point
	        require_once $path . 'includes/angelleye-gravity-braintree-helper.php';
            require_once $path . 'lib/class.plugify-gform-braintree.php';
	        require_once $path . 'includes/class-angelleye-gravity-braintree-ach-field.php';
	        require_once $path . 'includes/class-angelleye-gravity-braintree-ach-toggle-field.php';
	        require_once $path . 'lib/angelleye-gravity-forms-payment-logger.php';
            require_once $path . 'includes/angelleye-gravity-braintree-field-mapping.php';
            require_once $path . 'includes/class-angelleye-gravity-braintree-creditcard.php';

            /**
             * Required functions
             */
            if (!function_exists('angelleye_queue_update')) {
                require_once( 'includes/angelleye-functions.php' );
            }

            // Fire off entry point
            new Plugify_GForm_Braintree();
            new AngelleyeGravityBraintreeFieldMapping();

	        /**
	         * Register the ACH form field and Payment Method toggle field
	         */
	        GF_Fields::register( new Angelleye_Gravity_Braintree_ACH_Field() );
	        GF_Fields::register( new Angelleye_Gravity_Braintree_ACH_Toggle_Field() );
	        GF_Fields::register( new Angelleye_Gravity_Braintree_CreditCard_Field() );
            AngellEYE_GForm_Braintree_Payment_Logger::instance();

        }
    }

    public static function isBraintreeFeedActive()
    {
        global $wpdb;
        $addon_feed_table_name = $wpdb->prefix . 'gf_addon_feed';
        $is_active = $wpdb->get_var("select is_active from ".$addon_feed_table_name." where addon_slug='gravity-forms-braintree' and is_active=1");

        return $is_active=='1';
    }
}

AngelleyeGravityFormsBraintree::getInstance();