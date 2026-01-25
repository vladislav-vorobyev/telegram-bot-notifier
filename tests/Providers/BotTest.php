<?php
namespace TNotifyer\Providers;

use PHPUnit\Framework\TestCase;
use TNotifyer\Engine\Storage;
use TNotifyer\Engine\FakeRequest;
use TNotifyer\Database\FakeDBSimple;
use TNotifyer\Providers\FakeCURL;

class BotTest extends TestCase
{
    const OK_RESPONSE = [
        'ok' => 1,
        'result' => []
    ];

    const UPDATE_EXAMPLE = [
        'update_id' => 1,
        'message' => ['text' => 'test']
    ];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        Storage::set('DBSimple', new FakeDBSimple());
    }

    public function testCreation()
    {
        Storage::get('DBSimple')->rows = [['chat_id' => '11'], ['chat_id' => '22']];
        Storage::set('CURL', new FakeCURL(self::OK_RESPONSE));
        Storage::set('Bot', new Bot(0, 0, '00:AA', '00'));
        $this->assertEquals(
            ['11', '22'],
            Storage::get('Bot')->getMainChatsIds()
        );
    }

    /**
     * @dataProvider wrongTokenDataProvider
     */
    public function testWrongToken($response, $token)
    {
        $this->expectException('TNotifyer\Exceptions\InternalException');
        Storage::set('CURL', new FakeCURL($response));
        $bot = new Bot(0, 0, $token, '00');
    }
    
    public function wrongTokenDataProvider()
    {
        return [
            'wrong curl response' => ['', '00:AA'],
            'wrong token' => [self::OK_RESPONSE, 'AA'],
            'empty' => [self::OK_RESPONSE, null]
        ];
    }

    /**
     * @depends testCreation
     */
    public function testCheckUpdates()
    {
        Storage::get('DBSimple')->rows = [];
        Storage::set('CURL', new FakeCURL(self::OK_RESPONSE));
        $result = Storage::get('Bot')->checkUpdates();
        $this->assertEquals(self::OK_RESPONSE, $result);
        $this->assertEquals(
            [0],
            Storage::get('DBSimple')->last_args
        );
    }

    /**
     * @depends testCreation
     */
    public function testWebhook()
    {
        Storage::get('DBSimple')->rows = [];
        Storage::set('Request', new FakeRequest(
            'POST',
            '/webhook',
            self::UPDATE_EXAMPLE,
            ['X-Telegram-Bot-Api-Secret-Token' => 'AA']
        ));
        $result = Storage::get('Bot')->webhook();
        $this->assertEquals(true, $result);
        $this->assertEquals(
            [0, 1, 'message', json_encode(self::UPDATE_EXAMPLE)],
            Storage::get('DBSimple')->last_args
        );
    }

    /**
     * @depends testCreation
     */
    public function testForbiddenWebhook()
    {
        $this->expectException('TNotifyer\Exceptions\InternalException');
        Storage::set('Request', new FakeRequest(
            'POST',
            '/webhook',
            self::UPDATE_EXAMPLE,
        ));
        Storage::get('Bot')->webhook();
    }

    /**
     * @depends testCreation
     * @dataProvider memberUpdateDataProvider
     */
    public function testMemberUpdate($status, $sql, $args)
    {
        Storage::get('DBSimple')->rows = [];
        $update = [
            'update_id' => 1,
            'my_chat_member' => [
                'new_chat_member' => [
                    'status' => $status,
                    'user' => ['id' => '00']
                ],
                'chat' => [
                    'id' => 11,
                    'title' => 'test'
                ]
            ]
        ];
        Storage::set('CURL', new FakeCURL([
            'ok' => 1,
            'result' => [$update]
        ]));
        Storage::get('Bot')->checkUpdates();
        // $this->assertEquals([], Storage::get('DBSimple')->sql_history);
        $this->assertEquals(
            $sql,
            substr( Storage::get('DBSimple')->last_sql, 0, strlen($sql) )
        );
        $this->assertEquals(
            $args,
            Storage::get('DBSimple')->last_args
        );
    }
    
    public function memberUpdateDataProvider()
    {
        return [
            'join' => ['member', 'INSERT INTO bot_chats', [0, 11, 'main', 'test']],
            'left' => ['left', 'DELETE FROM bot_chats', [0, 11]],
        ];
    }

}
