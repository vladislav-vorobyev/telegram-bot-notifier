<?php
namespace TNotifyer\Providers;

use PHPUnit\Framework\TestCase;
use TNotifyer\Engine\Storage;
use TNotifyer\Engine\FakeRequest;
use TNotifyer\Database\FakeDBSimple;
use TNotifyer\Providers\FakeCURL;

class BotTest extends TestCase
{
    const ARG_ANY_VALUE = '***';

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
        Storage::get('DBSimple')->reset([['chat_id' => '11'], ['chat_id' => '22']]);
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
        Storage::get('DBSimple')->reset();
        Storage::set('CURL', new FakeCURL(self::OK_RESPONSE));
        $result = Storage::get('Bot')->checkUpdates();
        $this->assertEquals(self::OK_RESPONSE, $result);
        $this->assertEquals(
            'SELECT max(update_id)+1 FROM bot_updates WHERE bot_id=0',
            Storage::get('DBSimple')->last_sql
        );
    }

    /**
     * @depends testCreation
     */
    public function testWebhook()
    {
        Storage::get('DBSimple')->reset();
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
     */
    public function testSetWebhook()
    {
        Storage::get('DBSimple')->reset();
        Storage::get('Bot')->setWebhook();
        $sql = 'INSERT INTO a_log';
        $this->assertEquals($sql, substr( Storage::get('DBSimple')->last_sql, 0, strlen($sql) ));
        $this->assertEquals(
            [0, 'tbot-send', 'setWebhook', '{"url":"webhook","secret_token":"AA"}'],
            Storage::get('DBSimple')->last_args
        );
    }

    /**
     * @depends testCreation
     */
    public function testAlarm()
    {
        Storage::get('DBSimple')->reset();
        $status = Storage::get('Bot')->alarm('Test alarm', ['test'=>1]);
        $this->assertEquals(true, $status);
        $this->assertEquals(false, isset( Storage::get('DBSimple')->last_sql ));
    }

    /**
     * @depends testCreation
     * @dataProvider memberUpdateDataProvider
     */
    public function testMemberUpdate($status, $sql, $args)
    {
        Storage::get('DBSimple')->reset();
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

    /**
     * @depends testCreation
     * @dataProvider botCommandsDataProvider
     */
    public function testBotCommands($text, $db_rows, $db_history)
    {
        Storage::get('DBSimple')->reset($db_rows);
        $update = [
            'update_id' => 1,
            'message' => [
                'text' => $text,
                'chat' => ['id' => '00']
            ]
        ];
        Storage::get('Bot')->checkUpdate($update);
        $db = Storage::get('DBSimple');
        $last_index = count($db->sql_history) - 1;
        // compare each history step with stored history starting from last
        foreach ($db_history as $i => $step) {
            list($sql, $args) = $step;
            // take sql and args from db history step by step backward
            $db_sql = $db->sql_history[$last_index - $i];
            $db_args = $db->args_history[$last_index - $i];
            $this->assertEquals($sql, substr( $db_sql, 0, strlen($sql) ));
            foreach ($args as $k => $arg)
                if ($arg === self::ARG_ANY_VALUE) $db_args[$k] = self::ARG_ANY_VALUE;
            $this->assertEquals($args, $db_args);
        }
    }

    public function botCommandsDataProvider()
    {
        return [
            ['/test', [], [
                ['INSERT INTO a_log', [0, 'tbot-send', 'sendMessage', self::ARG_ANY_VALUE]]
            ]],
            ['/info', [], [
                ['SELECT * FROM bot_options', [0, 'chat_00_status']]
            ]],
            ['/ozon', [], [
                ['SELECT * FROM bot_options', [0, Bot::ON_OZON_API_KEY]]
            ]],
            ['/ozon id', [], [
                ['SELECT * FROM bot_options', [0, Bot::ON_OZON_CLI_ID]]
            ]],
            ['/ozon set id', [], [
                ['INSERT INTO bot_options', [0, 'chat_00_status', '/ozon set id']]
            ]],
            ['123', [['value'=>'/ozon set id']], [
                ['INSERT INTO bot_options', [0, 'chat_00_status', '']],
                ['SELECT * FROM bot_options', [0, 'chat_00_status']],
                ['INSERT INTO bot_options', [0, Bot::ON_OZON_CLI_ID, '123']],
            ]],
            ['/ozon set key', [], [
                ['INSERT INTO bot_options', [0, 'chat_00_status', '/ozon set key']]
            ]],
            ['123', [['value'=>'/ozon set key']], [
                ['INSERT INTO bot_options', [0, 'chat_00_status', '']],
                ['SELECT * FROM bot_options', [0, 'chat_00_status']],
                ['INSERT INTO bot_options', [0, Bot::ON_OZON_API_KEY, self::ARG_ANY_VALUE]],
            ]],
            ['/cancel', [['value'=>'/ozon set key']], [
                ['INSERT INTO bot_options', [0, 'chat_00_status', '']],
                ['SELECT * FROM bot_options', [0, 'chat_00_status']],
                ['SELECT * FROM bot_options', [0, 'chat_00_status']],
            ]],
        ];
    }

    /**
     * @depends testCreation
     */
    public function testFailBotCommand()
    {
        Storage::get('DBSimple')->reset();
        $update = [
            'update_id' => 1,
            'message' => [
                'text' => '/info',
                'chat' => ['id' => '11']
            ]
        ];
        Storage::get('Bot')->checkUpdate($update);
        $sql = 'INSERT INTO a_log';
        $this->assertEquals($sql, substr( Storage::get('DBSimple')->last_sql, 0, strlen($sql) ));
        $this->assertEquals(
            [0, 'warning', 'Access forbidden from the chat 11'],
            Storage::get('DBSimple')->last_args
        );
    }

    /**
     * @depends testCreation
     */
    public function testSendTbotDayActivity()
    {
        Storage::get('DBSimple')->reset();
        Storage::get('Bot')->sendTbotDayActivity();
        $sql = 'SELECT count(*) FROM a_log WHERE created BETWEEN DATE_SUB(NOW(),INTERVAL 1 DAY)';
        $this->assertEquals($sql, substr( Storage::get('DBSimple')->last_sql, 0, strlen($sql) ));
    }

    /**
     * @depends testCreation
     */
    public function testPingWebsites()
    {
        Storage::get('DBSimple')->reset([['id'=>1, 'url'=>'http://www.test.com', 'active'=>false]]);
        Storage::get('Bot')->pingWebsites();
        $sql = 'UPDATE a_websites SET active=1, updated=NOW() WHERE id=1';
        $this->assertEquals($sql, substr( Storage::get('DBSimple')->last_sql, 0, strlen($sql) ));
    }

}
