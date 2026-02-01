<?php
namespace TNotifyer\Providers;

use TNotifyer\Framework\LocalTestCase;
use TNotifyer\Engine\Storage;
use TNotifyer\Engine\FakeRequest;
use TNotifyer\Database\FakeDBSimple;
use TNotifyer\Providers\FakeCURL;

class OZONProviderTest extends LocalTestCase
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
        Storage::set('Bot', new FakeBot(0));
    }

    public function testCreation()
    {
        Storage::get('DBSimple')->reset();
        Storage::set('OZON', new OZONProvider('00','AA'));
        $this->assertEmpty(Storage::get('OZON')->lastErrorMessage());
    }

    /**
     * @depends testCreation
     */
    public function testCheckPosting()
    {
        Storage::get('DBSimple')->reset();
        $result = Storage::get('OZON')->checkPosting(self::POSTING_EXAMPLE);

        $this->assertEquals(true, $result);
        $this->assertNotEmpty(Storage::get('Bot')->last_main_msg);
        // $this->outputDBHistory();
        $this->assertDBHistory([
            ['INSERT INTO posting_status', [0, 'ozon', "36615787-0025-1", "awaiting_packaging", '{"11":99}', "awaiting_packaging"]],
            ['INSERT IGNORE INTO postings', [0, 'ozon', "36615787-0025-1", "awaiting_packaging", json_encode(self::POSTING_EXAMPLE)]],
        ]);
    }

    /**
     * @depends testCreation
     * @dataProvider checkTimeDataProvider
     */
    public function testGetLastCheckTime($rows, $value)
    {
        Storage::get('DBSimple')->reset($rows);
        $result = Storage::get('OZON')->getLastCheckTime();

        // $this->outputDBHistory();
        $this->assertDBHistory([[
            'SELECT TIME_TO_SEC( TIMEDIFF( NOW(), created ) ) AS sec, created FROM a_log WHERE bot_id=? AND type=? AND message=? ORDER BY id DESC LIMIT ?',
            [0, 'check', 'OZON', 1]
        ]]);
        $this->assertEquals($value, $result);
    }
    
    public function checkTimeDataProvider()
    {
        return [
            '115 seconds' => [ [['sec'=>'115']], 115 ],
            'empty' => [ [], 0 ]
        ];
    }

    /**
     * @depends testGetLastCheckTime
     * @depends testCreation
     */
    public function testDoCheck()
    {
        Storage::get('DBSimple')->reset();
        Storage::set('CURL', new FakeCURL([
            'result' => [
                'postings' => [self::POSTING_EXAMPLE],
            ],
        ]));
        $ozon = Storage::get('OZON');
        $ozon->doCheck();

        $this->assertEmpty($ozon->lastErrorMessage());
        $this->assertNotEmpty(Storage::get('Bot')->last_main_msg);
        // $this->outputDBHistory();
        $this->assertDBHistory([[
            'INSERT INTO a_log', [0, 'check', 'OZON']
        ]]);
    }

    /**
     * @depends testGetLastCheckTime
     * @depends testCreation
     * @dataProvider wrongCheckDataProvider
     */
    public function testWrongDoCheck($data)
    {
        $this->expectException('TNotifyer\Exceptions\ExternalRequestException');
        Storage::set('CURL', new FakeCURL($data));
        Storage::get('OZON')->doCheck();
    }
    
    public function wrongCheckDataProvider()
    {
        return [
            'wrong struct' => [['result' => []]],
            'empty' => [[]]
        ];
    }

}
