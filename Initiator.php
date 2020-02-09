<?php
/**
 * Plugin Name: BC Simple Auto Ads
 * Plugin URI: https://www.binarycarpenter.com/how-to-exclude-categories-from-adsense-auto-ads/
 * Description: BC Simple Auto Ads lets you insert adsense auto ads code with option to exclude certain categories
 * Version: 1.0
 * Author: BinaryCarpenter.com
 * Author URI: https://www.binarycarpenter.com
 * License: GPL2
 * Text Domain: bc-simple-auto-ads-tx
 */

namespace BinaryCarpenter\BC_AA;

include_once 'inc/Options_Form.php';
include_once 'inc/Options.php';
include_once 'inc/Static_UI.php';
include_once 'inc/Core.php';
include_once 'inc/Option_Names.php';
include_once 'inc/Config.php';
include_once 'ui/Main.php';
//include_once 'vendor/autoload.php';
use BinaryCarpenter\BC_AA\Options_Form;
use BinaryCarpenter\BC_AA\Options;
use BinaryCarpenter\BC_AA\Core as Core;
use BinaryCarpenter\BC_AA\Config as Config;
use BinaryCarpenter\BC_AA\UI\Main as Main;

class Initiator {


     public function __construct()
     {
        //register the action to handle options form submit
        add_action('wp_ajax_'. Options_Form::AJAX_SAVE_FORM, array('BinaryCarpenter\BC_AA\Options_Form', 'save_form_options'));

        //add menu, if not available
        add_action('admin_menu', array($this, 'add_to_menu'));
        

        //enqueue js and css
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));

        add_action('wp_head', array($this, 'display_ads'));

	     add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
     }


	public function action_links($links)
	{
		$custom_links = array();
		$custom_links[] = '<a href="' . admin_url( 'admin.php?page='. Config::SLUG ) . '">' . __( 'Get started', Config::TEXT_DOMAIN ) . '</a>';
		$custom_links[] = '<a target="_blank" href="https://tickets.binarycarpenter.com/open.php">' . __( 'Supports', Config::TEXT_DOMAIN ) . '</a>';
		return array_merge( $custom_links, $links );
	}


     public function display_ads()
     {
     	//Only display ads on single posts
     	if (is_single())
        {
        	$post_id = get_the_ID();

        	$options = Options::get_the_only_option(Config::OPTION_NAME);

        	if ($options ==  null)
        		return;
        	$ads_code = ($options->get_string(Option_Names::AUTO_ADS_CODE_HTML));

        	if ($ads_code == '')
        		return;

        	$excluded_categories = $options->get_array(Option_Names::EXCLUDED_CATEGORIES);
        	$excluded_post_types = $options->get_array(Option_Names::EXCLUDED_POST_TYPES);

	        /**
	         * If no categories excluded, simply echo the ads code
	         */
        	if (count($excluded_categories) == 0 && count($excluded_post_types) == 0)
        		echo $ads_code;
        	else
	        {
	        	$post_type = get_post_type($post_id);


	        	if (!in_category($excluded_categories, $post_id) && !in_array($post_type, $excluded_post_types))
	        		echo $ads_code;
	        }

        }
     }



    public function enqueue_admin()
    {

    	$current_screen = get_current_screen();


    	if (stripos($current_screen->base, Config::SLUG))
	    {
		    wp_register_style(Config::SLUG . '-backend-style', plugins_url('bundle/css/backend.css', __FILE__));

		    wp_enqueue_style(Config::SLUG . '-backend-style');

		    wp_register_script(Config::SLUG . '-backend-script', plugins_url('bundle/js/backend-bundle.js', __FILE__));
		    wp_enqueue_script('underscore');
		    wp_enqueue_script(Config::SLUG . '-backend-script');
	    }


    }

    public function add_to_menu()
    {
        (new Core())->admin_menu();
        //add sub menu page here
	    $image_tag = '<img  style="width: 14px; height: 14px;" src="data:image/png;base64,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"> ';
	    $menu_title = $image_tag . Config::MENU_NAME;//html content, with icon
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
