<?php
/**
 * @package CBW
 */
/*
Plugin Name: Connectid Business
Plugin URI: https://bysafeonline.com/connectid-business/connectid-business-resources/
Description: Wordpress Connectid Business is a plugin to connect with your <strong>Connectid Business</strong> service.
Version: 1.0.0
Author: SafeOnline
Author URI: https://bysafeonline.com/
License: GPLv2 or later
Text Domain: CBW
 Domain Path: /languages
*/
namespace socbw;
use Composer\Autoload\ClassLoader;
use socbw\inc\Helper;
use socbw\inc\OptionHelper;
use socbw\inc\Page;
// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hey, you can\'t access this file, you silly human!';
	exit;
}
define('SO_CB_SLUG', 'socb-cbw-sl');
define('SO_CB_PREFIX', 'socb');
define('SO_CB_ROOT_FILE', __FILE__);
define('SO_CB_TEXT_DOMAIN', 'CBW');
define('SO_CB_URI', plugin_dir_url(SO_CB_ROOT_FILE));
define('SO_CB_DIR', plugin_dir_path(SO_CB_ROOT_FILE));
define('SO_CB_DIR_ASSETS', SO_CB_DIR . 'assets');
define('SO_CB_URI_ASSETS',SO_CB_URI . 'assets');
define('SO_CB_DIR_JS', SO_CB_DIR_ASSETS . '/js');
define('SO_CB_DIR_CSS', SO_CB_DIR_ASSETS . '/css');
define('SO_CB_URI_JS', SO_CB_URI_ASSETS . '/js');
define('SO_CB_URI_CSS', SO_CB_URI_ASSETS . '/css');

spl_autoload_register(__NAMESPACE__ . '\\autoload');
add_action('plugins_loaded', array(CBW::getInstance(), 'init'));
add_action( 'widgets_init', array(CBW::getInstance(), 'register_our_widgets'));
add_action( 'init', array(CBW::getInstance(), 'register_our_blocks'));
add_shortcode('requestportal', array(CBW::getInstance(), 'register_our_shortcodes')); 

Class CBW {
    /** @var null */
    private static $instance = null;
    public function init() {
        if (is_admin()) {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            add_action('admin_menu', array(Page::getInstance(), 'addAdminMenu'));
            add_action('admin_enqueue_scripts', array($this, 'loadAdminAssets'), 999);
        }
    }
	function register_our_shortcodes() {
        $login_user = wp_get_current_user();
        $user_id = null;
        if($login_user)
        {
            $user_id = $login_user->ID;
        }
        $link_url = OptionHelper::GetRequestPortalLinkUrl();
        $link_text = OptionHelper::GetRequestPortalLinkText();
        if($user_id){
            $link_url = $link_url . '?customerid=' . $user_id;
        }
        return '<a class="request-portal-link" href="' . $link_url . '" target="_blank">' .  $link_text . '</a>';
    }
    function register_our_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        wp_register_script(
            'socb-rpl-block',
            plugins_url( 'inc/request_portal_link.js', __FILE__ ),
            array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
            filemtime( plugin_dir_path( __FILE__ ) . 'inc/request_portal_link.js' )
        );
        register_block_type('socb-gutenberg/request-portal-link', array(
            'editor_script' => 'socb-rpl-block',
        ) );
    }

    public function loadAdminAssets() {
        wp_enqueue_style('socbw.admin.css', SO_CB_URI_CSS . '/admin.css', array(), filemtime(SO_CB_DIR_CSS . '/admin.css'));
    }
    /**
     * @return null|Action
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public static function register_our_widgets() { 
        register_widget( 'socbw\inc\RequestPortalLink' ); 
    }
}

/**
 * @param string $class
 */
function autoload($class = '') {	
    if (!strstr($class, 'socbw')) {
        return;
    }
    $result = str_replace('socbw\\', '', $class);
    $result = str_replace('\\', '/', $result);
    require $result . '.php';
}
