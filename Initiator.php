<?php
/**
 * Plugin Name: BC Menubar Cart Icon For WooCommerce
 * Plugin URI: https://www.binarycarpenter.com/app/bc-menu-cart-icon-plugin/
 * Description: Ultimate customization for your menubar cart icon on WooCommerce store
 * Version: 1.31
 * Author: BinaryCarpenter.com
 * Author URI: https://www.binarycarpenter.com
 * License: GPL2
 * Text Domain: bc-menu-cart-woo
 * WC requires at least: 3.0.0
 * WC tested up to: 3.6.3
 */


/*
 * free 1.3
 * pro 2.01
 */

//include_once 'vendor/autoload.php';
include_once 'inc/bc_core.php';

include_once 'inc/bc-cart-config.php';
include_once 'inc/BC_Options.php';
if (file_exists(plugin_dir_path(__FILE__).'inc/BC_Activation_x18794.php'))
    include_once 'inc/BC_Activation_x18794.php';
include_once 'inc/BC_Options_Form.php';
include_once 'inc/BC_Static_UI.php';
include_once 'inc/BC_Cart_Options_name.php';
if (file_exists(plugin_dir_path(__FILE__).'inc/BC_Cart_Details.php'))
    include_once 'inc/BC_Cart_Details.php';
else if (file_exists(plugin_dir_path(__FILE__).'inc/BC_Cart_Details_2.php'))
    include_once 'inc/BC_Cart_Details_2.php';


use BinaryCarpenter\BC_Cart_Options_name as Oname;
use BinaryCarpenter\BC_Cart_Details as Cart_Details;

use \BinaryCarpenter\BC_Menu_Cart_Config as BConfig;



/**
 * @property array|mixed|void options
 */
class Initiator {

	public static $plugin_slug;
	public static $plugin_basename;
	private $linked_options, $theme_cart_options;

	/**
	 * Construct.
	 */
	public function __construct() {
		self::$plugin_slug = basename(dirname(__FILE__));
		self::$plugin_basename = plugin_basename(__FILE__);

		$this->linked_options = \BinaryCarpenter\BC_Options::get_all_options('bc_menu_cart_linked_menu')->posts;
		$this->theme_cart_options = \BinaryCarpenter\BC_Options::get_all_options('bc_menu_cart_theme_cart_icon')->posts;

		add_shortcode('bc_cart_icon', array($this, 'shortcode'));

		// load the localisation & classes
		add_action( 'plugins_loaded', array( &$this, 'languages' ), 0 ); // or use init?
		add_filter( 'load_textdomain_mofile', array( $this, 'textdomain_fallback' ), 10, 2 );

		add_action( 'init', array( $this, 'load_classes' ) );
//		add_action( 'init', array( \BinaryCarpenter\BC_Options::class, 'create_option_post_type'));

		// enqueue scripts & ajax
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts_styles_frontend') ); // Load scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts_styles_backend') ); // Load backend script

        //this is to save settings via aja
        add_action('wp_ajax_' . \BinaryCarpenter\BC_Options_Form::BC_OPTION_COMMON_AJAX_ACTION, array(\BinaryCarpenter\BC_Options_Form::class, 'handle_post_save_options') );

        add_action('wp_ajax_bc_menu_cart_remove_product', array($this, 'remove_item_from_cart'));
        add_action('wp_ajax_nopriv_bc_menu_cart_remove_product', array($this, 'remove_item_from_cart'));

        //remove one item from cart
        add_action('wp_ajax_bc_menu_cart_product_change_amount', array($this, 'product_cart_change_amount'));
        add_action('wp_ajax_nopriv_bc_menu_cart_product_change_amount', array($this, 'product_cart_change_amount'));

		// add filters to selected menus to add cart item <li>
		add_action( 'init', array( &$this, 'filter_nav_menus' ) );


        add_filter( 'woocommerce_add_to_cart_fragments', array(&$this, 'update_cart_fragment_ajax') );

        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
    }


    public function action_links($links)
    {
        $custom_links = array();
        $custom_links[] = '<a href="' . admin_url( 'admin.php?page=bc_menu_bar_cart' ) . '">' . __( 'Get started', BConfig::PLUGIN_TEXT_DOMAIN ) . '</a>';
        $custom_links[] = '<a target="_blank" href="https://tickets.binarycarpenter.com/open.php">' . __( 'Supports', BConfig::PLUGIN_TEXT_DOMAIN ) . '</a>';
        if (BConfig::IS_FREE)
            $custom_links[] = '<a target="_blank" href="https://www.binarycarpenter.com/app/bc-menu-cart-icon-plugin/">' . __( 'Get pro', BConfig::PLUGIN_TEXT_DOMAIN ) . '</a>';
        return array_merge( $custom_links, $links );
    }

    public function product_cart_change_amount()
    {


        //remove the requested product from cart
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item)
        {

            $product_id = intval($_POST['product_id']);
            $product_new_amount = intval($_POST['new_amount']);
            $passed_cart_item_key = sanitize_text_field($_POST['cart_item_key']);
            if($cart_item['product_id'] == $product_id && $cart_item_key ==  $passed_cart_item_key)
            {
                if ($product_new_amount <= 0)
                    WC()->cart->remove_cart_item($cart_item_key);
                else
                {
                    WC()->cart->set_quantity($cart_item_key, $product_new_amount, true);
                }

            }
        }

        $design_option_id = intval($_POST['cart_design_id']);


        // Fragments and mini cart are returned
        $data = array(
            'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array(
                    '.bc-mnc__style-'.$design_option_id => $this->generate_menu_item_html($design_option_id, false),
                    'div.bc-mnc__cart-total' => $this->generate_cart_total(),
//                'a.bc-mnc__cart-link.bc-mnc__style-'.$design_option_id => $this->generate_cart_icon_and_circle_item_count($design_options),
                    'div.bc-mnc__cart-details--cart-total__amount' => $this->generate_cart_total(),
                    '.bc-mnc__cart-details[data-option-id='.$design_option_id.'] section' => Cart_Details::generate_cart_items_list( new \BinaryCarpenter\BC_Options(BC_MenuCart_General_Settings::OPTION_NAME, $design_option_id))
                )
            ),
            'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() ? md5( json_encode( WC()->cart->get_cart_for_session() ) ) : '', WC()->cart->get_cart_for_session() )
        );

        wp_send_json( $data );

        die();
    }

    public function shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts, 'bc_cart_icon');

        if ($atts['id'] == 0)
            return "";

        return $this->generate_menu_item_html($atts['id'], 'div');

    }

    public function remove_item_from_cart()
    {


        //remove the requested product from cart
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item)
        {

            $product_id = intval($_POST['product_id']);
            $passed_cart_item_key = sanitize_text_field($_POST['cart_item_key']);
            if($cart_item['product_id'] == $product_id && $cart_item_key ==  $passed_cart_item_key)
            {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }

        $design_option_id = intval($_POST['cart_design_id']);


        // Fragments and mini cart are returned
        $data = array(
            'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array(
                    '.bc-mnc__style-'.$design_option_id => $this->generate_menu_item_html($design_option_id, false),
                'div.bc-mnc__cart-total' => $this->generate_cart_total(),
//                'a.bc-mnc__cart-link.bc-mnc__style-'.$design_option_id => $this->generate_cart_icon_and_circle_item_count($design_options),
                'div.bc-mnc__cart-details--cart-total__amount' => $this->generate_cart_total(),
                 '.bc-mnc__cart-details[data-option-id='.$design_option_id.'] section' => Cart_Details::generate_cart_items_list( new \BinaryCarpenter\BC_Options(BC_MenuCart_General_Settings::OPTION_NAME, $design_option_id))
                )
            ),
            'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() ? md5( json_encode( WC()->cart->get_cart_for_session() ) ) : '', WC()->cart->get_cart_for_session() )
        );

        wp_send_json( $data );

        die();
    }

    /**
     * Get all the linked menu options and print the fragments accordingly
     * @param $fragments
     * @return mixed
     */
    public function update_cart_fragment_ajax($fragments)
    {
        //get the design options that has menu attached to them
        $active_design_options = $this->get_designs_id_that_have_menu_linked();
        if (count($active_design_options) == 0)
            return $fragments;


        foreach ($active_design_options as $design_option_id)
        {
            $design_option = new \BinaryCarpenter\BC_Options(BC_MenuCart_General_Settings::OPTION_NAME, $design_option_id);
            $fragments['a.bc-mnc__style-' . $design_option_id] = $this->generate_cart_a($design_option);
//            $fragments['a.bc-mnc__style-' . $design_option_id] = $this->generate_menu_item_html($design_option_id, 'li');
            $fragments['div.bc-mnc__cart-total'] = $this->generate_cart_total();
            $fragments['.bc-mnc__cart-details[data-option-id='.$design_option_id.'] section'] = Cart_Details::generate_cart_items_list( $design_option);

        }

        return $fragments;

    }



	/**
	 * Load classes
	 * @return void
	 */
	public function load_classes() {
		include_once('inc/BC_Cart_General_Settings.php');
		new BC_MenuCart_General_Settings();
        include_once( 'inc/bc_menu_bar_cart-woocommerce.php' );
        $this->shop = new BC_Menu_Cart_Woo_Helper();

	}


	/**
	 * Get an array of all active plugins, including multisite
	 * @return array active plugin paths
	 */
	public static function get_active_plugins() {
		$active_plugins = (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		if (is_multisite()) {
			// get_site_option( 'active_sitewide_plugins', array() ) returns a 'reversed list'
			// like [hello-dolly/hello.php] => 1369572703 so we do array_keys to make the array
			// compatible with $active_plugins
			$active_sitewide_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			// merge arrays and remove doubles
			$active_plugins = (array) array_unique( array_merge( $active_plugins, $active_sitewide_plugins ) );
		}

		return $active_plugins;
	}



	/**
	 * Load translations.
	 */
	public function languages() {
		$locale = apply_filters( 'plugin_locale', get_locale(), BConfig::PLUGIN_TEXT_DOMAIN );
		$dir    = trailingslashit( WP_LANG_DIR );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * 		- WP_LANG_DIR/wp-menu-cart/wp-menu-cart-LOCALE.mo
		 * 	 	- WP_LANG_DIR/plugins/wp-menu-cart-LOCALE.mo
		 * 	 	- wp-menu-cart/languages/wp-menu-cart-LOCALE.mo (which if not found falls back to:)
		 * 	 	- WP_LANG_DIR/plugins/wp-menu-cart-LOCALE.mo
		 */
		load_textdomain( BConfig::PLUGIN_TEXT_DOMAIN, $dir . 'wp-menu-cart/wp-menu-cart-' . $locale . '.mo' );
		load_textdomain( BConfig::PLUGIN_TEXT_DOMAIN, $dir . 'plugins/wp-menu-cart-' . $locale . '.mo' );
		load_plugin_textdomain( BConfig::PLUGIN_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Maintain textdomain compatibility between main plugin (wp-menu-cart) and WooCommerce version (woocommerce-menu-bar-cart)
	 * so that wordpress.org language packs can be used for both
	 */
	public function textdomain_fallback( $mofile, $textdomain ) {
		$main_domain = BConfig::PLUGIN_TEXT_DOMAIN;
		$wc_domain = 'woocommerce-menu-bar-cart';

		// check if this is filtering the mofile for this plugin
		if ( $textdomain === $main_domain ) {
			$wc_mofile = str_replace( "{$textdomain}-", "{$wc_domain}-", $mofile ); // with trailing dash to target file and not folder
			if ( file_exists( $wc_mofile ) ) {
				// we have a wc override - use it
				return $wc_mofile;
			}
		}

		return $mofile;
	}



	//load css,
    public function load_scripts_styles_backend()
    {
        global $current_screen;
        if (stripos($current_screen->base, 'bc_menu_bar_cart') !== false)
        {
            wp_enqueue_media();
            wp_enqueue_script(BConfig::PLUGIN_SLUG . '_admin_scripts', plugins_url('bundle/js/backend-bundle.js', __FILE__), array('jquery', 'underscore'), false, false);
            //enque
            wp_enqueue_style(BConfig::PLUGIN_SLUG . '_admin_styles', plugins_url('bundle/css/backend.css', __FILE__), array());
        }
    }

	/**
	 * Load CSS
	 */
	public function load_scripts_styles_frontend() {



        wp_register_script(
            'bc_menu_bar_cart_frontend',
            plugins_url( '/bundle/js/frontend-bundle.js' , __FILE__ ),
            array( 'jquery', 'underscore' ),
            '2.7.5',
            false
        );
        wp_enqueue_script(
            'bc_menu_bar_cart_frontend'
        );

		wp_register_style( BConfig::PLUGIN_COMMON_HANDLER . '-frontend', plugins_url('bundle/css/frontend.css', __FILE__), array(), '', 'all' );
		wp_enqueue_style( BConfig::PLUGIN_COMMON_HANDLER . '-frontend');


		if (count($this->theme_cart_options) > 0)
        {
            $theme_cart_option = new \BinaryCarpenter\BC_Options('bc_menu_cart_theme_cart_icon', $this->theme_cart_options[0]->ID);

            //check if hiding theme cart is checked
            if ($theme_cart_option->get_bool(Oname::HIDE_THEME_CART))
            {

                $theme_cart_css_selector = $theme_cart_option->get_string(Oname::THEME_CART_CSS_SELECTOR, '', true);

                if ($theme_cart_css_selector !== '')
                    wp_add_inline_style( BConfig::PLUGIN_COMMON_HANDLER . '-frontend' , '.et-cart-info ,.site-header-cart ,'.$theme_cart_css_selector.' { display:none !important; }' );
                else
                    wp_add_inline_style( BConfig::PLUGIN_COMMON_HANDLER . '-frontend' , '.et-cart-info ,.site-header-cart { display:none !important; }' );
            }

        }



		//Load Stylesheet if twentytwelve is active
		if ( wp_get_theme() == 'Twenty Twelve' ) {
			wp_register_style( 'bc_menu_bar_cart-twentytwelve', plugins_url( '/css/bc_menu_bar_cart-twentytwelve.css', __FILE__ ), array(), '', 'all' );
			wp_enqueue_style( 'bc_menu_bar_cart-twentytwelve' );
		}

		//Load Stylesheet if twentyfourteen is active
		if ( wp_get_theme() == 'Twenty Fourteen' ) {
			wp_register_style( 'bc_menu_bar_cart-twentyfourteen', plugins_url( '/css/bc_menu_bar_cart-twentyfourteen.css', __FILE__ ), array(), '', 'all' );
			wp_enqueue_style( 'bc_menu_bar_cart-twentyfourteen' );
		}		


	}

	/**
	 * Add filters to selected menus to add cart item <li>
	 */
	public function filter_nav_menus() {

		//get the linked menu option
        $linked_options = \BinaryCarpenter\BC_Options::get_all_options('bc_menu_cart_linked_menu')->posts;

        if (count($linked_options) == 0)
            return;


        $menus = BC_MenuCart_General_Settings::get_menu_array();

        if(count($menus) == 0)
            return;

        //get the BC_Options object
        //get only the first item since we only store the options in one post
        $linked_menu_options = new \BinaryCarpenter\BC_Options('bc_menu_cart_linked_menu', $linked_options[0]->ID);

        //add filter to menu that has design attached to it
		foreach ($menus as $menu)
        {
            if ($linked_menu_options->get_int($menu['slug']) > 0)
                add_filter( 'wp_nav_menu_' . $menu['slug'] . '_items', array( &$this, 'add_cart_icon_to_menu' ) , PHP_INT_MAX, 2 );
        }

	}

    /**
     * @return array
     */
	private function get_designs_id_that_have_menu_linked()
    {
        //get the linked menu option
        $linked_options = \BinaryCarpenter\BC_Options::get_all_options('bc_menu_cart_linked_menu')->posts;

        if (count($linked_options) == 0)
            return array();


        $menus = BC_MenuCart_General_Settings::get_menu_array();

        if(count($menus) == 0)
        {
            //there is no menu available, so skip
            return array();
        }

        //get the BC_Options object
        //get only the first item since we only store the options in one post
        $linked_menu_options = new \BinaryCarpenter\BC_Options('bc_menu_cart_linked_menu', $linked_options[0]->ID);

        $result = array();

        //add filter to menu that has design attached to it
        foreach ($menus as $menu)
        {
            $design_option_id = $linked_menu_options->get_int($menu['slug']);
            if ($design_option_id > 0)
                $result[] = $design_option_id;
        }

        return array_unique($result);
    }



	/**
	 * Add Menu Cart to menu
     * This is a filter function that hooked into wp_nav_menu_
	 * 
	 * @return string menu items + Menu Cart item
	 */
	public function add_cart_icon_to_menu($items , $args) {
	    $menu_slug = $args->menu->slug;


        if (count($this->linked_options) == 0)
            return $items;
        $options = new \BinaryCarpenter\BC_Options('bc_menu_cart_linked_menu', $this->linked_options[0]->ID);

        //now, check if this menu has an option attached to it. The id of the design option is the meta_value of
        //a meta which has $menu_slug as key

        if (!($options->get_int($menu_slug) > 0))
            return $items;


        $item_html = $this->generate_menu_item_html($options->get_int($menu_slug), 'li');


        $all_html = $items . $item_html;


		return $all_html;
	}


	public function generate_cart_icon_and_circle_item_count(\BinaryCarpenter\BC_Options $design_options)
    {

        $cart_layout = $design_options->get_int(Oname::CART_LAYOUT);

        $cart_html = '';
        $my_cart_text = $design_options->get_string(Oname::MY_CART_TEXT) != '' ? $design_options->get_string(Oname::MY_CART_TEXT) : 'My cart';


        switch ($cart_layout)
        {
            case 0:
                $cart_html = sprintf('<div class="bc-mnc__cart-link--container bc-mnc__cart-link-layout-01">%1$s%2$s</div>', $this->generate_cart_icon_html($design_options), $this->generate_cart_count_circle($design_options));
                break;
            case 1:
                $cart_html = sprintf('<div class="bc-mnc__cart-link--container bc-mnc__cart-link-layout-02">%1$s <div class="bc-menu-cart-text-container">%2$s <hr class="bc-menu-cart-hr" /> %3$s</div> </div>', $this->generate_cart_icon_html($design_options), $this->generate_cart_item_count(), $this->generate_cart_total());
                break;
            case 2:
                $cart_html = sprintf('<div class="bc-mnc__cart-link--container bc-mnc__cart-link-layout-03">%1$s <div class="bc-menu-cart-text-container">%2$s <hr class="bc-menu-cart-hr" /> %3$s</div> </div>', $this->generate_cart_icon_html($design_options), $my_cart_text, $this->generate_cart_item_count());
                break;
            case 3:
                $cart_html = sprintf('<div class="bc-mnc__cart-link--container bc-mnc__cart-link-layout-04">%1$s <div class="bc-menu-cart-text-container">%2$s <hr class="bc-menu-cart-hr" /> %3$s</div> </div>', $this->generate_cart_icon_html($design_options), $my_cart_text, $this->generate_cart_total());
                break;
            case 4:
                $cart_html = sprintf('<div class="bc-mnc__cart-link--container bc-mnc__cart-link-layout-05">%1$s%2$s</div>', $this->generate_cart_icon_html($design_options), $this->generate_cart_count_circle($design_options));
                break;
            case 5:
                $cart_html = sprintf('<div class="bc-mnc__cart-link--container bc-mnc__cart-link-layout-06">%1$s%2$s</div>', $this->generate_cart_total(), $this->generate_cart_item_count());
                break;
        }

        return $cart_html;
    }


    public function generate_cart_a(\BinaryCarpenter\BC_Options $design_options)
    {


        $cart_icon_with_layout = $this->generate_cart_icon_and_circle_item_count($design_options);

        //1. Build the item skeleton




        $on_icon_click = $design_options->get_string(Oname::ON_CART_ICON_CLICK, '', true);
        $on_icon_hover = $design_options->get_string(Oname::ON_CART_ICON_HOVER);




        //these classes will determine the action of class on click or hover
        $on_click_class = '';
        $on_hover_class = '';
        $link_href = '#';

        switch ($on_icon_click)
        {
            case 'go_to_cart':
                $link_href = wc_get_cart_url();
                break;
            case 'show_cart_list':
                $on_click_class = 'bc-mnc__cart-link--show-details-on-click';
                break;
            case 'do_nothing':
                $link_href = '#';
                break;
            default:
                $link_href = wc_get_cart_url();

        }


        if ($on_icon_hover == 'show_cart_list')
            $on_hover_class = 'bc-mnc__cart-link--show-details-on-hover';



        //attach the design option id to the outer class, it will be used to update the cart later
        $outer_class = "bc-mnc__style-" . $design_options->get_post_id();

        $on_action_classes =$on_hover_class . ' ' . $on_click_class;


        return sprintf('<a href="%1$s" class="bc-mnc__cart-link %2$s %3$s">%4$s</a>', $link_href, $on_action_classes, $outer_class, $cart_icon_with_layout);


    }

    /**
     * @param $design_option_id
     * @param string $wrapper : default is li as it is displayed on a menu. However, in case of shortcode, it could be other things
     *
     * actually get the HTML for the menu item
     * @return string
     */
	public function generate_menu_item_html($design_option_id, $wrapper = 'li')
    {

        if (get_post_status($design_option_id) != 'publish')
        {
            return '';
        }

        $design_options = new \BinaryCarpenter\BC_Options(BC_MenuCart_General_Settings::OPTION_NAME, $design_option_id);

        /**
         * If the user sets hide cart when it's empty, then do not display it when cart is empty
         */
        $always_display_cart = $design_options->get_bool(Oname::ALWAYS_DISPLAY, false);

        if (!$always_display_cart && WC()->cart->is_empty())
            return '';


        //generate the cart link (a) section
        $cart_a_section = $this->generate_cart_a($design_options);

        $on_icon_click = $design_options->get_string(Oname::ON_CART_ICON_CLICK, '', true);
        $on_icon_hover = $design_options->get_string(Oname::ON_CART_ICON_HOVER);



        $cart_list_style_class = $design_options->get_string(Oname::CART_LIST_STYLE_CLASS, 'bc-mnc__cart-details-style-1');

        $cart_float = $design_options->get_string(Oname::CART_FLOAT, 'bc-mnc__float-none');

        //if the user set to not show cart details on hover nor click, don't generate the cart details list
        $cart_details_html = ($on_icon_hover == 'do_nothing' && $on_icon_click == 'do_nothing') ? '' : Cart_Details::generate_cart_items_list($design_options);



        $admin_bar_class = '';
        //if there is an user logged in and the site is on mobile, add a margin of 46px to accommodate with the admin bar
        if (wp_is_mobile() && is_user_logged_in())
        {
            $admin_bar_class = 'bc-mnc__mobile-logged-in';
        }

        //pass the design id here to update the fragments later via ajax (the function to remove item from cart needs this)
        if ($wrapper == 'li')
        {
            $returned_html  = sprintf('<li class="bc-mnc %5$s %6$s">%1$s <div class="bc-mnc__cart-details bc-root %2$s" data-option-id="%3$s">%4$s</div></li>', $cart_a_section, $cart_list_style_class, $design_option_id, $cart_details_html, $cart_float, $admin_bar_class);
        }
        else if ($wrapper === false) //return the content, will be used to update the cart via ajax
        {
            $returned_html =  $cart_a_section;
        }
        else
        {
            $returned_html = sprintf('<div class="bc-mnc  %5$s %6$s">%1$s <div data-option-id="%3$s" class="bc-mnc__cart-details bc-root %4$s">%2$s</div></div>', $cart_a_section, $cart_details_html , $design_option_id, $cart_list_style_class, $cart_float, $admin_bar_class);
        }

        return $returned_html;

    }


    /**
     *
     * @return integer number of items in cart
     */
    public  function generate_cart_count_circle(\BinaryCarpenter\BC_Options $design_options)
    {
        $text_color = $design_options->get_string(Oname::ITEM_COUNT_CIRCLE_TEXT_COLOR, '#fff', false);
        $bg_color = $design_options->get_string(Oname::ITEM_COUNT_CIRCLE_BG_COLOR, '#ff6000', false);
        $width =  $design_options->get_int(Oname::ITEM_COUNT_CIRCLE_WIDTH, 16, false);
        $height = $design_options->get_int(Oname::ITEM_COUNT_CIRCLE_HEIGHT, 16, false);

        $font_size = $design_options->get_int(Oname::ITEM_COUNT_CIRCLE_FONT_SIZE, 12, false);
        //get the number of items in cart


        return sprintf('<div class="bc-mnc__cart-link--count-circle" style="color: %2$s; background: %3$s; width: %4$s; height: %5$s; line-height: %6$s; font-size: %7$s;">%1$s</div>', WC()->cart->get_cart_contents_count(), $text_color, $bg_color, $width . 'px', $height . 'px', $height . 'px', $font_size . 'px');
    }

    /**
     * Generate cart content, number of items and the word items
     */
    public static function generate_cart_item_count()
    {
        return sprintf ( _n( '<div class="bc-mnc__cart-details--cart-items-count">%d item</div>', '<div class="bc-mnc__cart-details--cart-items-count">%d items</div>', WC()->cart->get_cart_contents_count() ), WC()->cart->get_cart_contents_count() );
    }

    public static function generate_cart_total()
    {
        return sprintf('<div class="bc-mnc__cart-details--cart-total__amount">%1$s</div>', WC()->cart->get_cart_total());
    }

    /**
     * @param \BinaryCarpenter\BC_Options $design_option
     * @return string the HTML string of the Cart icon ONLY
     */
    private function generate_cart_icon_html(\BinaryCarpenter\BC_Options $design_option)
    {
        $icon_type = $design_option->get_string(Oname::CART_ICON_TYPE, 'font_icon', false);

        $icon_font = $design_option->get_string(Oname::CART_ICON_FONT, 'icon-cart-01', false);

        $icon_font_size = $design_option->get_int(Oname::CART_ICON_FONT_SIZE, 24, false);


        $icon_width = $design_option->get_int(Oname::CART_ICON_WIDTH, 40, false);
        $icon_height = $design_option->get_int(Oname::CART_ICON_HEIGHT, 40, false);
        $icon_image = $design_option->get_string(Oname::CART_ICON_IMAGE);

        $icon_display = $design_option->get_bool(Oname::DISPLAY_CART_ICON, true);

        $icon_color = $design_option->get_string(Oname::CART_ICON_COLOR, '#000000', false);

        $html = '';



        if ($icon_type == 'font_icon')
        {
            $html = sprintf('<i style="width:%1$s; height: %2$s; font-size: %3$s; color: %5$s;" class="%4$s bc-menu-cart-icon"></i>', $icon_width . 'px', $icon_height . 'px', $icon_font_size . 'px', $icon_font, $icon_color);
        } else
        {
            if ($icon_image!= '')
            {
                $html = sprintf('<img src="%1$s" style="width:%2$s; height: %3$s;" />', $icon_image, $icon_width . 'px', $icon_height . 'px');
            }

        }
        $hidden = !$icon_display ? 'style="display:none;"' : '';

        $html = sprintf('<div class="bc-mnc__cart-link--cart-icon" %1$s>%2$s</div>', $hidden, $html);



        return $html;



    }

}

$bcMenuCart = new BC_Menu_Cart_Display();
