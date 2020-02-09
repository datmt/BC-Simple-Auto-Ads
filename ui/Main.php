<?php

/**
* Main ui for the plugin
*/
namespace BinaryCarpenter\BC_AA\UI;

use BinaryCarpenter\BC_AA\Static_UI as UI;
use BinaryCarpenter\BC_AA\Options_Form as Form;
use BinaryCarpenter\BC_AA\Options;
use BinaryCarpenter\BC_AA\Option_Names as Oname;
use BinaryCarpenter\BC_AA\Config;
class Main
{
    public static function ui()
    {

    	$id = Options::get_the_only_option_id(Config::OPTION_NAME);
		$option_ui = new Form(Config::OPTION_NAME, $id);
		$categories = array();

		$wp_categories = get_categories();

		foreach ($wp_categories as $wp_cat)
		{
			$categories[$wp_cat->slug] = $wp_cat->name;
		}

	    $custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names', 'and');

	    $post_types_ui_list = array();

	    foreach($custom_post_types as $key=>$value)
	    {
		    $post_types_ui_list[] = $value;
	    }


        UI::open_root();
            UI::open_form(true);
                UI::heading(__('Simple Auto Ads', 'bc-simple-auto-ads-tx'), 1, true);
                UI::heading(__('Enter your auto ads code', 'bc-simple-auto-ads-tx'), 3);
                $option_ui->html_string(Oname::AUTO_ADS_CODE_HTML, __('put your auto ads code here. It will be inserted to your head tag', 'bc-simple-auto-ads-tx'), 8);

	            UI::heading(__('Exclude the following categories from displaying ads', 'bc-simple-auto-ads-tx'), 3);


                $option_ui->multiple_checkbox(Oname::EXCLUDED_CATEGORIES,
	                $categories
                 );



	            UI::heading(__('Exclude the following post types from displaying ads', 'bc-simple-auto-ads-tx'), 3);

			    $option_ui->multiple_checkbox(Oname::EXCLUDED_POST_TYPES,
				    $custom_post_types
			    );

				$option_ui->setting_fields(true);

				UI::js_post_form();
				UI::hr();
				$option_ui->submit_button(__('Save settings', 'bc-simple-auto-ads-tx'));
            UI::close_form(true);
        UI::close_root();
    }

}
