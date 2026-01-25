<?php
namespace TNotifyer\Providers;

use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
    public function test() {
        $crypto = new Crypto('AAEIKfv7J-mq5dwhdosnQWZ','7Jmq5dwhdosnQWZA');
        $this->assertEquals(true, $crypto->test());
    }
}
