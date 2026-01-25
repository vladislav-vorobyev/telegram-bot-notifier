<?php
namespace TNotifyer\Controllers;

use PHPUnit\Framework\TestCase;
use TNotifyer\Engine\Storage;
use TNotifyer\Engine\Response;
use TNotifyer\Engine\App;
use TNotifyer\Engine\FakeRequest;
use TNotifyer\Database\FakeDBSimple;
use TNotifyer\Providers\FakeCURL;

class ViewControllerTest extends TestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        Storage::set('Request', new FakeRequest('GET', '/api/v1/'));
        Storage::set('Response', new Response());
        Storage::set('App', new App());
        Storage::set('DBSimple', new FakeDBSimple());
    }

    public function testHello() {
        $controller = new ViewController();
        $response = $controller->hello();
        $this->assertInstanceof('TNotifyer\Engine\Response', $response);
        $this->assertEquals(200, $response->code);
    }

    public function testInfo() {
        $controller = new ViewController();
        $response = $controller->info();
        $this->assertInstanceof('TNotifyer\Engine\Response', $response);
        $this->assertEquals(200, $response->code);
    }

    public function testWrongTable() {
        Storage::get('DBSimple')->rows = [];
        $this->expectException('TNotifyer\Exceptions\InternalException');
        $controller = new ViewController();
        $response = $controller->log();
    }

    public function testLog() {
        Storage::get('DBSimple')->rows = [[1]];
        $controller = new ViewController();
        $response = $controller->log();
        $this->assertInstanceof('TNotifyer\Engine\Response', $response);
        $this->assertEquals(200, $response->code);
    }

    public function testPostings() {
        Storage::get('DBSimple')->rows = [[1]];
        $controller = new ViewController();
        $response = $controller->postings();
        $this->assertInstanceof('TNotifyer\Engine\Response', $response);
        $this->assertEquals(200, $response->code);
    }

    public function testUpdates() {
        Storage::get('DBSimple')->rows = [[1]];
        $controller = new ViewController();
        $response = $controller->updates();
        $this->assertInstanceof('TNotifyer\Engine\Response', $response);
        $this->assertEquals(200, $response->code);
    }
}
