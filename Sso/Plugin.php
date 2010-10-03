<?php

abstract class Sso_Plugin
{
    static $plugins = array (
    );
    
    public static function notify($subject)
    {
        foreach (self::$plugins as $plugin) {
            $concrete = "Sso_Plugin_".$plugin;
            $implementation = new $concrete();
            $implementation->delegate($subject);
        }
    }
}