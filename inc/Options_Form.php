<?php
/**
 * Created by PhpStorm.
 * User: MYN
 * Date: 5/11/2019
 * Time: 10:10 AM
 */

namespace BinaryCarpenter\PLUGIN_NS;
use BinaryCarpenter\PLUGIN_NS\Config as Config;

/**
 * Class BC_Options_Form
 * @package BinaryCarpenter\PLUGIN_NS
 * This class, will be used across multiple BC plugins. They all share one common custom post type to store
 * plugins' settings
 *
 */
class Options_Form
{
    private $option_name, $option_post_id;
    private $options;
    const AJAX_SAVE_FORM = 'bc_1378x_aj_action';//Used to store form settings, must be different across plugins
    const REDIRECT_URL = 'redirect_url';


    /**
     * @param $option_name
     */
    public function __construct($option_name, $option_post_id)
    {
        $this->option_name = $option_name;
        $this->option_post_id = $option_post_id;
        $this->options = new Options($this->option_name, $option_post_id);

        //update the $option_post_id in case the id passed in is 0, the BC_Options class will create a new post
        $this->option_post_id = $this->options->get_post_id();
    }
    /**
    * Return the naem of the ajax action. Used in setting_fields() to output ajax action
    */
    public static function get_action_name()
    {
        return sprintf('%1$s', self::AJAX_SAVE_FORM);
    }

    /**
    * Return the id of current post
    */
    public function get_option_post_id()
    {
        return $this->option_post_id;
    }
    /**
    * print necessary js code to handle form submit via ajax.
    */
    public function js_post_form()
    { ?>

        <script>

            (function ($) {

                $(function () {
                    //save the settings on key press
                    $(window).bind('keydown', function(event) {
                        if (event.ctrlKey || event.metaKey) {
                            switch (String.fromCharCode(event.which).toLowerCase()) {
                                case 's'://bind Ctrl+S to save/submit form
                                    event.preventDefault();
                                    //save all forms
                                    _.each($('.bc-form-submit-button'), function(the_button){
                                        save_form($(the_button));
                                    });

                                    break;

                            }
                        }
                    });


                    $('.bc-form-submit-button').on('click', function (e) {
                        e.preventDefault();
                        save_form($(this));
                    });
                    /**
                    * when clicking on this button, add one more row. This is for settings that
                    * have value varied. For example the thank you page associate with categories 
                    * in the thank you page plugin
                    */
                    $(document).on('click', '.add-data-row', function(){
                        add_data_row($(this));
                    });
                    /**
                    * Same reason as above
                    */
                    $(document).on('click', '.minus-data-row', function(){
                        remove_data_row($(this));
                    });

                });
                /**
                * this function save form data via ajax
                * form action value (ajax action) is defined by the 
                * action field in form (printed by form_settings())
                * @param the_button button that clicked
                */
                function save_form(the_button)
                {
                    var data = {};

                    //Collect data from fields that have keys (name), if the fields don't have keys, they are part of a
                    //bigger field (such as key_select_select)

                    _.each(the_button.closest('form').find('input, select, textarea').not('.bc-no-key-field'), function (i) {

                        let input = $(i);
                        let input_name = (input).attr('name');
                        let input_value = undefined;

                        //for checkbox, get value of the checked one
                        if (input.attr('type') === 'checkbox')
                            input_value = input.is(":checked");
                        else if (input.attr('type') === 'radio') {
                            //for radio input, since there are many radios share the same name, only get the value of checked radio
                            if (input.is(':checked'))
                                input_value = input.val();
                        }
                        else
                            input_value = input.val();

                        if (typeof (input_value) !== 'undefined')
                            data[input_name] = input_value;


                    });
                    //save data from fields that aren't simple input, select but have multiple inputs, selects
                    _.each(the_button.closest('form').find('.bc-key-array-assoc-data-field'), function(field){
                        var data_rows = {};

                        _.each($(field).find('.bc-single-data-row'), function(single_data_row){

                            var data_key = $(single_data_row).find('.bc-single-data-value').eq(0).val();
                            var data_value = $(single_data_row).find('.bc-single-data-value').eq(1).val();
                            if (data_key !== '' )
                                data_rows[data_key] = data_value;

                        });

                        //update the data of this field to the total data
                        data[$(field).attr('data-name')] = data_rows;

                    });


                    $.post(ajaxurl, data, function (response) {

                        swal('', response.message, 'info');
                        if (typeof (response.redirect_url) !== 'undefined')
                        {
                            var current_tab = the_button.closest('.bc-single-tab').attr('id');
                            window.location.href = response.redirect_url + '&active_tab='+ current_tab;
                        }

                    });
                }

                //add one more data ro
                function add_data_row(add_button)
                {
                    //clone current row
                    var clone = add_button.closest('.bc-single-data-row').clone();
                    add_button.closest('.bc-data-field').append(clone);

                }

                function remove_data_row(remove_button)
                {
                    var current_row = remove_button.closest('.bc-single-data-row');
                    //don't remove if it's the last row
                    var data_field = remove_button.closest('.bc-data-field');

                    if (data_field.find('.bc-single-data-row').length <=1)
                        return;

                    current_row.remove();


                }


            })(jQuery);

        </script>


        <?php


    }
    /**
    * This function handle form submit (save form data...)
    * you need to add action and put this as the handler in the
    * constructor of this class
    */

    public static function save_form_options()
    {
        //save the option to the post ID
        if (!current_user_can('edit_posts')) {
            echo __('You have no right to perform this action.', Config::PLUGIN_TEXT_DOMAIN);
            die();
        }

        //check nonce and update the options
//        dump(check_ajax_referer(self::get_action_name()));
        if (!wp_verify_nonce(sanitize_text_field($_POST['bc_form_security']), sanitize_text_field($_POST['action']))) {
            wp_send_json(array(
                'status' => 'Error',
                'message' => 'You do not have the necessary rights to perform this action'
            ));
            die();
        }

        $option_name = sanitize_text_field($_POST['option_name']);
        $option_post_id = intval($_POST['option_post_id']);
        $option_object = new BC_Options($option_name, $option_post_id);
        //save the settings
        foreach ($_POST[$option_name] as $key => $value) {
            $option_object->set($key, $value);

            if ($key == 'title') {
                wp_update_post(
                    array(
                        'ID' => $option_post_id,
                        'post_title' => sanitize_text_field($value)
                    )
                );
            }
        }


        $option_object->set_option_name($option_name);

        $data = array(
            'status' => 'Success',
            'message' => 'Settings saved successfully');

        if (isset($_POST[self::REDIRECT_URL]))
        {
            $data[self::REDIRECT_URL] = $_POST[self::REDIRECT_URL];
        }
        wp_send_json(
            $data
        );
        die();
    }

    /**
     * output nonce, action ...
     */
    public function setting_fields()
    {
        echo sprintf('<input type="hidden" name="action" value="%1$s" />', $this->get_action_name());
        echo sprintf('<input type="hidden" name="option_post_id" value="%1$s" />', $this->option_post_id);
        echo sprintf('<input type="hidden" name="option_name" value="%1$s" />', $this->option_name);
        wp_nonce_field($this->get_action_name(), "bc_form_security");
    }


    private function get_option_value($option_form_field, $type = 'string')
    {
        switch ($type) {
            case 'string':
                return $this->options->get_string($option_form_field);
                break;
            case 'int':
                return $this->options->get_int($option_form_field);
                break;
            case 'float':
                return $this->options->get_float($option_form_field);
                break;
            case 'bool':
                return $this->options->get_bool($option_form_field);
                break;
            case 'array':
                return $this->options->get_array($option_form_field);
                break;
            default:
                return $this->options->get_string($option_form_field);
                break;
        }
    }

    /**
     * @param $option_form_field string: the actual field name in the form, will be prepend by $option_level_1[$option_level_2]
     */
    private function generate_form_field($option_form_field)
    {
        return sprintf('%1$s[%2$s]', $this->option_name, $option_form_field);
    }

    /**
     * Returns nonce field HTML
     *
     * @param string $action
     * @param string $name
     * @param bool $referer
     * @internal param bool $echo
     * @return string
     */
    public static function nonce_field($action = -1, $name = '_wpnonce', $referer = true)
    {
        $name = esc_attr($name);
        $return = '<input type="hidden" name="' . $name . '" value="' . wp_create_nonce($action) . '" />';

        if ($referer) {
            $return .= wp_referer_field(false);
        }

        return $return;
    }


    /**
     * @param string $content html content
     * @param string $type [error|info|warning|success]
     * @param bool $closable
     * @param bool $echo
     * @return string
     */
    public function notice($content, $type, $closable = false, $echo = true)
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
     * Returns an input text element
     *
     * @param $setting_field_name
     */
    public function hidden($setting_field_name)
    {
        $current_value = $this->get_option_value($setting_field_name, 'string');

        echo sprintf('<input type="hidden" name="%1$s" value="%2$s" />', $this->generate_form_field($setting_field_name), $current_value);

    }

    public function raw_hidden($key, $value)
    {
        echo sprintf('<input type="hidden" name="%1$s" value="%2$s" />', $key, $value);
    }

    /**
     * Echos an label element
     *
     * @param $field_id
     * @param string $text
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
     * Echos an input text element
     *
     * @param $setting_field_name
     * @param string $type
     * @param bool $disabled
     * @return string
     */
    public function input_field($setting_field_name, $type = 'text', $label = '', $disabled = false, $width = 200)
    {

        $current_value = $this->get_option_value($setting_field_name);
        $disabled = $disabled ? 'disabled' : '';
        $html = '';
        $html .= '<div class="bc-uk-card">';
        if ($label != '')
            $html .= sprintf('<label class="bc-doc-label" for="%1$s">%2$s</label>', $setting_field_name, $label);
        $html .= sprintf('<input class="bc-uk-input" type="%1$s" id="%2$s" name="%2$s" value="%3$s" %4$s style="width: %5$s;"/>', $type, $this->generate_form_field($setting_field_name), $current_value, $disabled, $width . 'px');
        $html .= '&nbsp;&nbsp;</div>';

        return $html;

    }



    public function image_picker($setting_field_name, $button_title, $label, $disabled)
    {
        $disabled = $disabled ? 'disabled' : '';
        $current_value = $this->get_option_value($setting_field_name);
        $html = '<div class="bc-image-picker">';

        $label = $label != '' ? sprintf('<label class="bc-doc-label">%1$s</label>', $label) : '';

        $html .= $label;

        $html .= '<img class="bc_image_preview" src="' . $current_value . '" />';
        $html .= sprintf('<a class="bc-doc__image-picker-button bc-uk-button bc-uk-button-primary" %1$s>%2$s</a>', $disabled, $button_title);
        $html .= sprintf('<input type="hidden" id="%1$s" class="bc_image_picker_hidden_input" name="%1$s" value="%3$s" %4$s/>', $this->generate_form_field($setting_field_name), $this->option_name, $current_value, $disabled);

        return $html . '</div>';

    }


    /**
     * Generate a section where one key is associated with an associative array
     * _key => array(
        'key' => 'value'
     * )
     */
    public function key_select_select(
            $setting_field_name,
            $array_values_1,
            $array_values_2,
            $value_1_title,
            $value_2_title,
            $disabled = false)
    {
        //get the current value, this should be an associated array
        $current_value = $this->get_option_value($setting_field_name, 'array');

        if (count($current_value) == 0)
        {
            $current_value = array(
                    '' => ''
            );
        }

        $html = '';
        foreach ($current_value as $key => $value)
        {
            $html .= self::flex_data_row(array(
                $this->raw_select($array_values_1, $key),
                $this->raw_select($array_values_2, $value)
            ), true, true);
        }



        return sprintf('<div class="bc-key-select-select bc-data-field" data-name="%1$s">%2$s</div>', $setting_field_name, $html);

    }


    /**
    * Print single data row. Used in fields that have more than one inputs or selects (a key then value is an array of assocciated array)
    */
    private static function flex_data_row(array $content, $equal_width = false, $display_add_row = false, $display_minus_row = false)
    {
        $html = '';
        $add_sign = $display_add_row ? '<span class="add-data-row">+</span>' : '';
        $minus_sign = $display_add_row ? '<span class="minus-data-row">-</span>' : '';
        foreach($content as $c)
        {
            $width_class = $equal_width ? 'bc-uk-width-1-1' : '';
            $html .= $single_row = sprintf('<div class="%1$s">%2$s</div>', $width_class, $c);

        }



        //the class .bc-single-data-row will be used to collect data on a single row when the form is saved
        return sprintf ('<div class="bc-single-data-row bc-uk-flex">%1$s %2$s %3$s </div>', $html, $minus_sign, $add_sign);
    }

    /**
     * print the select, without field name.
     * @param array $values_array must be an associative array
     * This function print a select as a big field (that has key, which is the field name)
     * One case is to have a key then two selects
     * For example, select which category goes with which popup
     */
    private function raw_select($values_array, $selected_value, $multiple = false)
    {
        $html = '';
        foreach($values_array as $value => $name)
        {
            $selected = $value == $selected_value ? 'selected' : '';
            $html .= sprintf('<option value="%1$s" %2$s>%3$s</option>', $value, $selected, $name);
        }

        $multiple = $multiple ? 'multiple' : '';
        //mark this select field as bc-no-key-field so js will exclude it later when saving values later
        return sprintf('<select class="bc-uk-select bc-no-key-field" %1$s>%2$s</select>', $multiple, $html);
    }

    /**
     * Echos an select element
     *
     * @param $setting_field_name
     * @param $values
     * @param bool $disabled
     * @return string
     */
    public function select($setting_field_name, $values, $label = '',
                           $disabled = false, $multiple = false)
    {

        $current_value = $this->get_option_value($setting_field_name);

        $multiple_text = $multiple ? 'multiple' : '';

//        dump($current_value);
        $multiple_markup = $multiple ? '[]' : '';
        $disabled = $disabled ? 'disabled' : '';
        $html = sprintf('<select class="bc-uk-select" %2$s name="%1$s%4$s" %3$s>', $this->generate_form_field($setting_field_name), $disabled, $multiple_text, $multiple_markup);

        foreach ($values as $v => $text) {
            if (!$multiple)
                $selected = $v == $current_value ? 'selected' : '';
            else {
                if (is_array($current_value))
                    $selected = in_array($v, $current_value) ? 'selected' : '';
                else
                    $selected = '';
            }
            $html .= sprintf('<option value="%1$s" %3$s>%2$s</option>', $v, $text, $selected);
        }

        if ($label != '')
            $html = sprintf('<label for="%1$s">%2$s</label>', $setting_field_name, $label) . $html;
        return $html . '</select>';
    }

    /**
     * @param string $content HTML content of the heading, usually just text
     * @param int $level heading level, similar to h1 to h6 but with smaller text. There are only three levels
     * with text size 38px, 24px and 18px
     *
     * @return string
     *
     */
    public function heading($content, $level = 1, $echo = true)
    {

        $output = sprintf('<div class="bc-doc-heading-%1$s">%2$s</div>', $level, $content);

        if ($echo)
            echo $output;
        else
            return $output;

    }


    /**
     * Echos a group of radio elements
     * values: value => label pair or
     *  value => array(label, disabled, postfix)
     * @param $setting_field_name
     * @param $values
     * @param string $layout
     * @param $label_type string either: text (normal text), image(image url), icon_font (icon class)
     * @param string $title
     * @param array $dimensions width and height of image or icon, default 16 x 16
     * @return string
     */
    public function radio($setting_field_name, $values, $layout = 'row', $label_type = 'text', $title = '', $dimensions = array(16, 16))
    {


        $current_value = $this->get_option_value($setting_field_name);

        $html = '';

        $top_row = array();
        $bottom_row = array();


        //$label is actually an array ['label', 'disabled'] e.g. ['content' => 'Option 1', 'disabled' => false]
        foreach ($values as $v => $label_array) {
            $checked = $v == $current_value ? 'checked' : '';
            $disabled = $label_array['disabled'] ? 'disabled' : '';
            $label_content = $label_array['content'];


            $radio = sprintf('<input class="bc-uk-radio" type="radio" name="%1$s" value="%2$s" %3$s %4$s/> ', $this->generate_form_field($setting_field_name), $v, $checked, $disabled);

            switch ($label_type) {
                case 'text':

                    $top_row[] = sprintf('<span>%1$s %2$s&nbsp;&nbsp;</span>', $radio, $label_content);

                    break;
                case 'image':

                    $top_row[] = sprintf('<a href="%1$s" data-rel="lightcase"><img style="width: %2$s; height: %3$s; margin: auto;" src="%1$s" /></a>', $label_content, $dimensions[0] > 0 ? $dimensions[0] . 'px' : '', $dimensions[1] > 0 ? $dimensions[1] . 'px' : '');
                    $bottom_row[] = $radio;
                    break;
                case 'icon_font':
                    $top_row[] = sprintf('<i class="%1$s"></i>', $label_content);
                    $bottom_row[] = $radio;
                    break;
                default:
                    $top_row[] = sprintf('<p>%1$s</p>', $label_content);
                    break;

            }


        }


        $top_row_string = '';

        $bottom_row_string = '';

        foreach ($top_row as $content)
            $top_row_string .= '<td>' . $content . '</td>';

        foreach ($bottom_row as $content)
            $bottom_row_string .= '<td>' . $content . '</td>';

        $html = sprintf('<table><tbody><tr style="text-align: center;">%1$s</tr><tr style="text-align: center;">%2$s</tr></tbody></table>', $top_row_string, $bottom_row_string);


        if ($title != '')
            $html = sprintf('<label class="bc-doc-label">%1$s</label>', $title) . $html;

        return $html;

    }

    public static function flex_section($content, $flex_class = 'bc-uk-flex-left')
    {
        $html = sprintf('<div class="bc-uk-flex %1$s">', $flex_class);

        foreach ($content as $c)
            $html .= sprintf('<div>%1$s</div>', $c);

        return $html . '</div>';
    }


    /**
     * Echos an input text element
     *
     * @param $setting_field_name
     * @param string $placeholder
     * @param bool $disabled
     */
    public function textarea($setting_field_name, $placeholder = '', $disabled = false)
    {

        $current_value = $this->get_option_value($setting_field_name);

        $disabled = $disabled ? 'disabled' : '';
        echo sprintf('<textarea name="%1$s" placeholder="%4$s" class="bc-uk-textarea"  %3$s>%2$s</textarea>', $this->generate_form_field($setting_field_name), $current_value, $disabled, $placeholder);
    }


    /**
     * Echos an input checkbox element
     *
     * @param $setting_field_name
     * @param bool $disabled
     * @param string $label
     * @return string
     */
    public function checkbox($setting_field_name, $disabled = false, $label = '')
    {

        $current_value = $this->get_option_value($setting_field_name, 'bool');

        $disabled = $disabled ? 'disabled' : '';
        $state = checked(1, $current_value, false);
        return '<div>' .
            sprintf('<label class="bc-doc-label" for="%1$s"><input type="checkbox" name="%1$s" %2$s %3$s class="bc-uk-checkbox" value="1" id="%2$s" /> %4$s &nbsp;&nbsp;</label>', $this->generate_form_field($setting_field_name), $state, $disabled, $label)
            . '</div>';

    }

    public function card_section($title, $content)
    {
        $html = '<div class="bc-uk-card-body bc-uk-card bc-uk-card-default">';
        $html .= sprintf('<div class="bc-doc-heading-2">%1$s</div>', $title);

        $html .= implode("", $content) . '</div>';

        echo $html;
    }


    public function hr($echo = false)
    {
        if ($echo)
            echo '<hr class="bc-uk-hr" />';
        else
            return '<hr class="bc-uk-hr" />';
    }

    public function submit_button($text)
    {

        echo sprintf('<button name="submit"  type="submit" class="bc-uk-button-primary bc-uk-button bc-form-submit-button" >%1$s</button>', $text);
    }

}
