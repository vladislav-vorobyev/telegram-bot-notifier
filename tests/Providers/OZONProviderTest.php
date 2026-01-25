<?php
namespace TNotifyer\Providers;

use PHPUnit\Framework\TestCase;
use TNotifyer\Engine\Storage;
use TNotifyer\Engine\FakeRequest;
use TNotifyer\Database\FakeDBSimple;
use TNotifyer\Providers\FakeCURL;

class OZONProviderTest extends TestCase
{
    const POSTING_EXAMPLE = [
        "status" => "awaiting_packaging",
        "order_id" => 34454315739,
        "products" => [
            [
                "sku" => 2230185896,
                "imei" => [],
                "name" => "Стелька арамидная, 44",
                "price" => "5500.0000",
                "offer_id" => "стелька_44",
                "quantity" => 1,
                "currency_code" => "RUB",
                "is_blr_traceable" => false,
                "is_marketplace_buyout" => false
            ],
        ],
        "order_number" => "36615787-0025",
        "posting_number" => "36615787-0025-1",
    ];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        Storage::set('DBSimple', new FakeDBSimple());
        Storage::set('Bot', new FakeBot());
    }

    public function testCheckPosting() {
        $ozon = new OZONProvider('00','AA');
        $result = $ozon->checkPosting(self::POSTING_EXAMPLE);
        $this->assertEquals(true, $result);
        $this->assertNotEmpty(Storage::get('Bot')->last_main_msg);
        $this->assertEquals(
            [0, 'ozon', "36615787-0025-1", "awaiting_packaging", json_encode(self::POSTING_EXAMPLE)],
            Storage::get('DBSimple')->last_args
        );
    }

    public function testDoCheck() {
        Storage::set('CURL', new FakeCURL([
            'result' => [
                'postings' => [self::POSTING_EXAMPLE],
            ],
        ]));
        $ozon = new OZONProvider('00','AA');
        $ozon->doCheck();
        $this->assertEmpty($ozon->lastErrorMessage());
        $this->assertNotEmpty(Storage::get('Bot')->last_main_msg);
        $this->assertEquals([0, 'check', 'OZON'], Storage::get('DBSimple')->last_args);
    }

    /**
     * @dataProvider wrongCheckDataProvider
     */
    public function testWrongDoCheck($data)
    {
        $this->expectException('TNotifyer\Exceptions\ExternalRequestException');
        Storage::set('CURL', new FakeCURL($data));
        $ozon = new OZONProvider('00','AA');
        $ozon->doCheck();
    }
    
    public function wrongCheckDataProvider()
    {
        return [
            'wrong struct' => [['result' => []]],
            'empty' => [[]]
        ];
    }

}
