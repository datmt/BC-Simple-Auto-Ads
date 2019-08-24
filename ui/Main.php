<?php

/**
* Main ui for the plugin
*/
namespace BinaryCarpenter\PLUGIN_NS\UI;
use BinaryCarpenter\PLUGIN_NS\Static_UI as UI;
use BinaryCarpenter\PLUGIN_NS\Options_Form as Form;
class Main
{
    public static function ui()
    {
        $first_tab = array(
            'title' => 'First tab',
            'content' => array(
                'first row'
                ),
             'is_active' => false,
             'is_disabled' => false
             );
        $second_tab = array(
            'title' => 'Second tab',
            'content' => array(
                'first row'
                ),
             'is_active' => true,
             'is_disabled' => false
             );

        UI::open_root();
//        $form = new Form('test_option', 0);
        UI::tabs(array($first_tab, $second_tab), true);
        UI::close_root();
    }

}
