<?php
namespace TNotifyer\Engine;

use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        $router = new Router();
        $router->setRoutes([
            ['GET', '/api/v1/', ['FakeController', 'foo']],
            ['GET', '/api/v1/foo/', ['FakeController', 'foo']],
            ['GET', '/api/v1/fo/', ['FakeController', 'fo']],
            ['GET', '/api/v1/f/', ['Fake', 'foo']],
        ]);
        Storage::set('Router', $router);
        Storage::set('Response', new Response());
    }

    public function testGet()
    {
        Storage::set('Request', new FakeRequest('GET', '/api/v1/foo/'));
        $app = new App();
        $route = Storage::get('Router')->getCurrent();
        $response = $app->execute($route);
        $this->assertInstanceof('TNotifyer\Engine\Response', $response);
        $this->assertEquals(200, $response->code);
        $this->assertEquals('{"content":"foo"}', $response->content);
    }

    public function testNotFound()
    {
        $this->expectOutputString('{"error":"not found"}');
        Storage::set('Request', new FakeRequest('GET', '/api/v1/foo1/'));
        $app = new App();
        $app->run();
    }

    /**
     * @dataProvider exceptionsDataProvider
     */
    public function testExceptions($path)
    {
        $this->expectException('TNotifyer\Exceptions\NotFoundException');
        Storage::set('Request', new FakeRequest('GET', $path));
        $app = new App();
        $route = Storage::get('Router')->getCurrent();
        $response = $app->execute($route);
    }

    public function exceptionsDataProvider()
    {
        return [
            ['/api/v1/fo/'],
            ['/api/v1/f/'],
        ];
    }

}
