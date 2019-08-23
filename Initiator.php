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
include_once 'inc/Core.php';
use BinaryCarpenter\PLUGIN_NS\Options_Form;
use BinaryCarpenter\PLUGIN_NS\Core as Core;
class Initiator {


     public function __construct()
     {

        add_action('wp_ajax_'. Options_Form::AJAX_SAVE_FORM, array('Options_Form', 'save_form_options')); 


        add_action('admin_menu', array($this, 'add_to_menu'));
     }


    public function add_to_menu()
    {
        (new Core())->admin_menu();
        //add sub menu page here

    }
}


new Initiator();
