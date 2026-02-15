<?php
namespace TNotifyer\Engine;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testNotFound() {
        Storage::set('Response', new Response());
        Storage::set('Request', new FakeRequest('GET', '/api/v1/foo/'));
        $router = new Router();
        $route = $router->getCurrent();
        $this->assertEquals('SystemController', $route[0]);
        $this->assertEquals('notFound', $route[1]);
    }

    public function testGet() {
        Storage::set('Response', new Response());
        Storage::set('Request', new FakeRequest('GET', '/api/v1/foo/'));
        $router = new Router();
        $router->setRoute('GET', '/api/v1/foo/', ['FakeController', 'foo']);
        $route = $router->getCurrent();
        $this->assertEquals('FakeController', $route[0]);
        $this->assertEquals('foo', $route[1]);
    }

    public function testPost() {
        Storage::set('Response', new Response());
        Storage::set('Request', new FakeRequest('POST', '/api/v1/foo/', ['content'=>'foo']));
        $router = new Router();
        $router->setRoutes([
            ['GET', '/api/v1/', ['FakeController', 'foo']],
            ['GET', '/api/v1/foo/', ['FakeController', 'foo']],
            ['POST', '/api/v1/foo/', ['FakeController', 'foo']],
        ]);
        $route = $router->getCurrent();
        $this->assertEquals('FakeController', $route[0]);
        $this->assertEquals('foo', $route[1]);
    }
}
