<?php

/**
* Main ui for the plugin
*/
namespace BinaryCarpenter\PLUGIN_NS\UI;

use BinaryCarpenter\PLUGIN_NS\Static_UI as UI;
use BinaryCarpenter\PLUGIN_NS\Options_Form as Form;
use BinaryCarpenter\PLUGIN_NS\Options;
use BinaryCarpenter\PLUGIN_NS\Option_Names as Oname;
use BinaryCarpenter\PLUGIN_NS\Config;
class Main
{
    public static function ui()
    {

    	$id = Options::get_the_only_option_id(Config::OPTION_NAME);
		$option_ui = new Form(Config::OPTION_NAME, $id);


        UI::open_root();
            UI::open_form(true);
                UI::heading('Sample form', 2, true);
                UI::label('', 'Sample label', true);

                $option_ui->input_field('sample_input_field');

				$option_ui->setting_fields(true);

				UI::js_post_form();
				$option_ui->submit_button('Save settings');
            UI::close_form(true);
        UI::close_root();
    }

}
