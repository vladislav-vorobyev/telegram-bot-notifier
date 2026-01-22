<?php
/**
 * 
 * This file is part of TNotifyer project.
 * 
 */

// define hosting path
define( 'HOST_HOME', dirname(dirname($_SERVER['DOCUMENT_ROOT'])) );

// define DB_HOST, DB_USER, DB_PASS, DB_DB
require_once HOST_HOME . '/ini_h.php';

use TNotifyer\Engine\App;
use TNotifyer\Engine\DI;
use TNotifyer\Engine\Storage;

try {
    // globals initialization
    DI::start();

    // set routes
    Storage::get('Router')->setRoutes([
        ['GET', '/', ['ViewController', 'hello']],
        ['GET', '/info', ['ViewController', 'info']],
        ['GET', '/log', ['ViewController', 'log']],
        ['GET', '/last', ['ViewController', 'postings']],
        ['GET', '/updates', ['ViewController', 'updates']],
        ['POST', '/webhook', ['BotController', 'webhook']],
        ['GET', '/set-webhook', ['BotController', 'setWebhook']],
        ['GET', '/remove-webhook', ['BotController', 'removeWebhook']],
        ['GET', '/up', ['BotController', 'checkUpdates']],
        ['GET', '/watch-dog', ['BotController', 'sendTbotDayActivity']],
        ['GET', '/ping-websites', ['BotController', 'pingWebsites']],
        ['GET', '/test-msg', ['BotController', 'testMessage']],
        ['GET', '/bot-info', ['BotController', 'botInfo']],
        ['GET', '/ozon', ['OZONController', 'doCheck']],
        ['GET', '/ozon-info', ['OZONController', 'info']],
        ['GET', '/ozon-test', ['OZONController', 'doTest']],
        ['GET', '/crypto-test', ['SystemController', 'testCrypto']],
    ]);

    // run the app
    Storage::get('App')->run();
    
} catch (\Exception $e) {
    Storage::get('Response')->json(
        ['error' => $e->getMessage()],
        400
    )->render();
}
