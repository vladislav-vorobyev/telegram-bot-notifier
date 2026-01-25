<?php
namespace TNotifyer\Controllers;

use PHPUnit\Framework\TestCase;
use TNotifyer\Engine\Storage;
use TNotifyer\Engine\FakeRequest;
use TNotifyer\Engine\Response;

class SystemControllerTest extends TestCase
{
    public function testNotFound() {
        Storage::set('Request', new FakeRequest());
        Storage::set('Response', new Response());
        $controller = new SystemController();
        $response = $controller->notFound();
        $this->assertInstanceof('TNotifyer\Engine\Response', $response);
        $this->assertEquals(404, $response->code);
        $content = json_decode($response->content);
        $this->assertNotEmpty($content->error);
    }
}
