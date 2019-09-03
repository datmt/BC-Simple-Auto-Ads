<?php
/**
 * Plugin Name: Plugin name
 * Plugin URI: https://www.binarycarpenter.com/app/bc-menu-cart-icon-plugin/
 * Description: plugin descriptoin
 * Version: 1.31
 * Author: BinaryCarpenter.com
 * Author URI: https://www.binarycarpenter.com
 * License: GPL2
 * Text Domain: plugin text domain
 * WC requires at least: 3.0.0
 * WC tested up to: 3.6.3
 */

namespace BinaryCarpenter\PLUGIN_NS;

include_once 'inc/Options_Form.php';
include_once 'inc/Options.php';
include_once 'inc/Static_UI.php';
include_once 'inc/Core.php';
include_once 'inc/Config.php';
include_once 'ui/Main.php';
//include_once 'vendor/autoload.php';
use BinaryCarpenter\PLUGIN_NS\Options_Form;
use BinaryCarpenter\PLUGIN_NS\Options;
use BinaryCarpenter\PLUGIN_NS\Core as Core;
use BinaryCarpenter\PLUGIN_NS\Config as Config;
use BinaryCarpenter\PLUGIN_NS\UI\Main as Main;
class Initiator {


     public function __construct()
     {
        //register the action to handle options form submit
        add_action('wp_ajax_'. Options_Form::AJAX_SAVE_FORM, array('BinaryCarpenter\PLUGIN_NS\Options_Form', 'save_form_options'));

        //add menu, if not available
        add_action('admin_menu', array($this, 'add_to_menu'));
        

        //enqueue js and css
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_front'));

     }
    /**
    * Register and enqueue frontend styles and scripts
    *
    *
    */
    public function enqueue_front()
    {
        wp_register_style(Config::SLUG . '-frontend-style', plugins_url('bundle/css/frontend.css', __FILE__));
        
        wp_enqueue_style(Config::SLUG . '-frontend-style');

        wp_register_script(Config::SLUG . '-frontend-script', plugins_url('bundle/js/frontend-bundle.js', __FILE__));

        wp_enqueue_script(Config::SLUG . '-frontend-script');
    }


    public function enqueue_admin()
    {


    	$current_screen = get_current_screen();


    	if (stripos($current_screen->base, Config::SLUG))
	    {
		    wp_register_style(Config::SLUG . '-backend-style', plugins_url('bundle/css/backend.css', __FILE__));

		    wp_enqueue_style(Config::SLUG . '-backend-style');

		    wp_register_script(Config::SLUG . '-backend-script', plugins_url('bundle/js/backend-bundle.js', __FILE__));

		    wp_enqueue_script(Config::SLUG . '-backend-script');
	    }


    }

    public function add_to_menu()
    {
        (new Core())->admin_menu();
        //add sub menu page here
        $menu_title = '' . Config::MENU_NAME;//html content, with icon
        add_submenu_page(
                Core::MENU_SLUG,
                Config::NAME,
                $menu_title,
                'edit_posts',
                Config::SLUG,
                array($this, 'plugin_ui'));

    }

    public function plugin_ui()
    {
        Main::ui();
    }
}


new Initiator();
