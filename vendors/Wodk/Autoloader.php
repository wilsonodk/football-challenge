<?php
/*
 * This file is part of Wodk.
 *
 * (c) 2012 Wilson Wise
 *
 * Autoloads Wodk classes.
 *
 */
class Wodk_Autoloader
{
    /**
     * Registers Wodk_Autoloader as an SPL autoloader.
     */
    static public function register()
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * Handles autoloading of classes.
     *
     * @param string $class A class name.
     */
    static public function autoload($class)
    {
        if (strpos($class, 'Wodk') !== 0) {
            return;
        }

        if (is_file($file = dirname(__FILE__).'/../'.str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
            require $file;
        }
    }
}
