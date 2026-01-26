<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Engine;

/**
 * 
 * A main object to run the application.
 * 
 */
class App {

    /**
     * Application variables
     */
    const VARIABLES = [
        'version' => '1.1.5',
        'name' => 'Telegram Notifyer',
    ];

    /**
     * 
     * Constructor.
     * 
     */
    public function __construct()
    {
    }

    /**
     * 
     * To run the application.
     * 
     */
    public function run()
    {
        // Get this application router and determine current request
        $current_request = Storage::get('Router')->getCurrent();

        // Execute the request controller method
        $controller = new $current_request->controller;
        $response = $controller->{$current_request->method}();

        // Output the response
        $response->render();
    }

    /**
     * 
     * Get application variable value.
     * 
     * @param string variable name
     * @param mixed default value
     * 
     * @return string value
     */
    public static function var($name, $default = '')
    {
        return self::VARIABLES[$name] ?? $default;
    }

    /**
     * 
     * Get environment variable value.
     * 
     * @param string variable name
     * @param mixed default value
     * 
     * @return string value
     */
    public static function env($name, $default = '')
    {
        return $_SERVER[$name] ?? $_SERVER['REDIRECT_'.$name] ?? $default;
    }

    /**
     * 
     * Get information about the application.
     * 
     * @return array data
     */
    public static function info()
    {
        return [
            'name' => self::var('name'),
            'version' => self::var('version'),
            'SITE_URI' => self::env('SITE_URI', '/'),
            'BOT_INTERNAL_ID' => self::env('BOT_INTERNAL_ID'),
            'BOT_HOST_ID' => self::env('BOT_HOST_ID'),
        ];
    }
}
