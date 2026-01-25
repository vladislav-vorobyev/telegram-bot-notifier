<?php
namespace TNotifyer\Engine;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testNotFound() {
        Storage::set('Response', new Response());
        Storage::set('Request', new FakeRequest('GET', '/api/v1/foo/'));
        $router = new Router();
        $request = $router->getCurrent();
        $this->assertInstanceof('TNotifyer\Engine\Request', $request);
        $this->assertEquals('TNotifyer\Controllers\SystemController', $request->controller);
        $this->assertEquals('notFound', $request->method);
    }

    public function testGet() {
        Storage::set('Response', new Response());
        Storage::set('Request', new FakeRequest('GET', '/api/v1/foo/'));
        $router = new Router();
        $router->setRoute('GET', '/api/v1/foo/', ['FakeController', 'foo']);
        $request = $router->getCurrent();
        $this->assertInstanceof('TNotifyer\Engine\Request', $request);
        $this->assertEquals('TNotifyer\Controllers\FakeController', $request->controller);
        $this->assertEquals('foo', $request->method);
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
        $request = $router->getCurrent();
        $this->assertInstanceof('TNotifyer\Engine\Request', $request);
        $this->assertEquals('TNotifyer\Controllers\FakeController', $request->controller);
        $this->assertEquals('foo', $request->method);
    }
}
