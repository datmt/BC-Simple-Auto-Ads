<?php
/**
 * Created by PhpStorm.
 * User: MYN
 * Date: 5/9/2019
 * Time: 8:57 AM
 */
namespace BinaryCarpenter\PLUGIN_NS;
class Config
{
	/**
	 * option name to save setting for this plugin (for plugin that only need one custom post to store all settings)
	 */
	const OPTION_NAME = 'starter_option_name';
    const NAME = 'Starter plugin';
    const MENU_NAME = 'Starter plugin';
    const SLUG = 'starter_plugin';
    const TEXT_DOMAIN = 'starter-plugin-tx';
    const IS_FREE = false;//is free or pro
    const KEY_CHECK_OPTION = 'starter_key_check';//unique across plugins
}
