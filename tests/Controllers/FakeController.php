<?php
namespace TNotifyer\Controllers;

use TNotifyer\Engine\Response;
use TNotifyer\Engine\Storage;

/**
 * 
 * Controller to do tests.
 * 
 */
class FakeController {

    /**
     * @var Response
     */
    protected $response;

    /**
     * 
     * Constructor.
     * 
     */
    public function __construct()
    {
        $this->response = Storage::get('Response');
    }

    /**
     * 
     * Symple handler.
     * 
     */
    public function foo()
    {
        return $this->response->json([
            'content' => 'foo'
        ], 200);
    }
}