<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Engine;

/**
 * 
 * Single object to collect all global objects.
 * 
 */
class Storage {

    /**
     * @var array storage of dependency
     */
    static private $map = [];

    /**
     * 
     * Get an object from storage or from load.
     * 
     * @param string object name
     * 
     * @return Object an object from storage
     */
    static public function get($name)
    {
        return self::$map[$name] ?? DI::load($name);
    }

    /**
     * 
     * Put an object to storage.
     * 
     * @param string object name
     * @param Object an object to put
     * 
     * @return Object set object
     */
    static public function set($name, $dependency)
    {
        self::$map[$name] = $dependency;
        return self::$map[$name];
    }
}
