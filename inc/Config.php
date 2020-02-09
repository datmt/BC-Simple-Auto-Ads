<?php
/**
 * Created by PhpStorm.
 * User: MYN
 * Date: 5/9/2019
 * Time: 8:57 AM
 */
namespace BinaryCarpenter\BC_AA;
class Config
{
	/**
	 * option name to save setting for this plugin (for plugin that only need one custom post to store all settings)
	 */
	const OPTION_NAME = 'bc_auto_ads_option_name';
    const NAME = 'BC Simple Auto Ads';
    const MENU_NAME = 'BC Simple Auto Ads';
    const SLUG = 'bc_simple_auto_ads';
    const TEXT_DOMAIN = 'bc-simple-auto-ads-tx';
    const IS_FREE = false;//is free or pro
    const KEY_CHECK_OPTION = 'bc_simple_auto_ads_key_check';//unique across plugins
}
