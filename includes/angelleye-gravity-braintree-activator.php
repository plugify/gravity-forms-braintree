<?php

defined('ABSPATH') or die('Direct access not allowed');
class AngelleyeGravityBraintreeActivator
{
    public static function InstallDb(){
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        self::defaultPluginSetting();
    }

    public static function DeactivatePlugin(){

    }

    public static function Uninstall(){

    }

    public static function defaultPluginSetting(){

    }
}