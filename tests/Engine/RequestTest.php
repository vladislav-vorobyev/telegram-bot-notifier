<?php
namespace TNotifyer\Engine;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public static $_HEADERS = [];

    public function testGet() {
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/foo?q=1';
        $request = new Request('/api/v1');
        $this->assertEquals('GET', $request->request_method);
        $this->assertEquals('/foo', $request->path);
        $this->assertEquals(['q'=>'1'], $request->params);
        $this->assertEmpty($request->post);
    }

    public function testPost() {
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/v1/foo/';
        $_POST = ['q'=>'1'];
        $request = new Request('/api/v1/');
        $this->assertEquals('POST', $request->request_method);
        $this->assertEquals('/foo/', $request->path);
        $this->assertEmpty($request->params);
        $this->assertEquals(['q'=>'1'], $request->post);
    }

    public function testJson() {
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/v1/foo/';
        self::$_HEADERS = ['Content-Type' => 'application/json'];
        $_POST = '{"q":1}';
        $request = new Request('/api/v1/');
        $this->assertEquals('POST', $request->request_method);
        $this->assertEquals(['q'=>'1'], $request->post);
        self::$_HEADERS = [];
    }
}

/**
 * Fake getallheaders function
 */
function getallheaders() {
    return RequestTest::$_HEADERS;
}

/**
 * Fake file_get_contents function
 */
function file_get_contents(string $filename) {
    return $_POST;
}
