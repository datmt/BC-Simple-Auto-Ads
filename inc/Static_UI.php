<?php

/**
 * This class print UI elements that aren't dependent on any particular form (without creating a form instance)
 */
namespace BinaryCarpenter\PLUGIN_NS;

class Static_UI
{
    /**
     * Echos an label element
     *
     * @param $field_id
     * @param string $text
     *
     * @return string
     */
    public static function label($field_id, $text, $echo = true)
    {
        $output = sprintf('<label for="%1$s" class="bc-doc-label">%2$s</label>', $field_id, $text);
        if ($echo)
            echo $output;
        else
            return $output;
    }

    /**
     * Create a row
     *
     * @param $content array of content, each element of this array represents a column
     * array(
     *       'content' => 'array of html content of the col',
     *       'width' => 'width of the col'
     *      )
     *
     * @param $echo boolean, whether to echo or not
     *
     * @return void|string
     */
    public static function row($content, $echo = true)
    {
        $html = '';
        foreach ($content as $col)
        {
            //if a width is specified, it should be in this format 1-2, 1-3, 1-4... or 1-2@m...
            $width = isset($col['width']) ? $col['width'] : 'auto@m';

            $width = 'bc-uk-width-'. $width;
            $html .= sprintf('<div class="%1$s">%2$s</div>', $width, implode("", $col['content']));
        }

        $html = sprintf('<div bc-uk-grid>%1$s</div>', $html);

        if ($echo)
            echo $html;
        else
            return $html;
    }
    /**
     * Create tabs
     * 
     * @param array $content array(array('title' => 'title', 'content' => array of string, 'is_active" => false, 'is_disabled' => false))
     * @param bool $echo echo or not
     *
     * @return string|void
     */
    public static function tabs($content, $echo = false)
    {
        $tab_head = '';
        $tab_body = '';

        foreach ($content as $item)
        {
            $active_class = isset($item['is_active']) && $item['is_active']? 'bc-uk-active' : '';
            $disabled_class = isset($item['is_disabled']) && $item['is_disabled']? 'bc-uk-disabled' : '';

            /**
            * generate a random tab ID for each tab. Along with bc-single-tab (below), this will be used to select
            * the correct tab after form saved and redirected
            *
            * @var $random_id
            */
            $random_id = 'bc-tab-' . rand(1,20000);
            //add the class bc-single-tab here. It will be used to redirect and select the tab later when form is saved (function save_form in Options_Form.php)
            $tab_head .= sprintf('<li class="%1$s %2$s bc-single-tab" id="%4$s" ><a href="#">%3$s</a></li>', $disabled_class, $active_class, $item['title'], $random_id);

            $tab_body .= sprintf('<li>%1$s</li>', implode("", $item['content']));
        }

        $tab_head = sprintf('<ul bc-uk-tab>%1$s</ul>', $tab_head);

        $tab_body = sprintf('<ul class="bc-uk-switcher">%1$s</ul>', $tab_body);

        $html = $tab_head.$tab_body;

        if ($echo)
            echo $html;
        else
            return $html;

    }


    /**
     * Create heading
     *
     * @param string $content HTML content of the heading, usually just text
     * @param int $level heading level, similar to h1 to h6 but with smaller text. There are only three levels
     * with text size 38px, 24px and 18px
     *
     * @return string
     *
     */
    public static function heading($content, $level = 1, $echo = true)
    {

        $output = sprintf('<div class="bc-doc-heading-%1$s">%2$s</div>', $level, $content);

        if ($echo)
            echo $output;
        else
            return $output;

    }



    /**
     * Create a notice
     * 
     * @param string $content html content
     * @param string $type [error|info|warning|success]
     * @param bool $closable
     * @param bool $echo
     * 
     * @return string
     */
    public static function notice($content, $type, $closable = false, $echo = true)
    {

        switch ($type)
        {
            case 'info':
                $type_class = 'bc-uk-alert-primary';
                break;

            case 'success':
                $type_class = 'bc-uk-alert-success';
                break;

            case 'warning':
                $type_class = 'bc-uk-alert-warning';
                break;

            case 'error':
                $type_class = 'bc-uk-alert-danger';
                break;

            default:
                $type_class = 'bc-uk-alert-primary';
                break;

        }

        $closable = $closable ? '<a class="bc-uk-alert-close" bc-uk-close></a>' : '';

        $output = sprintf('<div class="%1$s" bc-uk-alert> %2$s <p>%3$s</p> </div>', $type_class, $closable, $content);

        if ($echo)
            echo $output;
        else
            return $output;

    }
    /**
    * Create a flex section with content. Content is an array of HTML
    *
    * @param array $content: array of HTML
    * @param string $flex_class: css class, from UI kit
    *
    * @return string HTML 
    */
    public static function flex_section($content, $flex_class = 'bc-uk-flex-left')
    {
        $html = sprintf('<div class="bc-uk-flex %1$s">', $flex_class);

        foreach ($content as $c)
            $html .= sprintf('<div>%1$s</div>', $c);

        return $html . '</div>';
    }





}
