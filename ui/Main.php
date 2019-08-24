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
        $form = new Form('test_option', 0);

        $form->notice('Help me!', 'notice', true, true);

    }

}
