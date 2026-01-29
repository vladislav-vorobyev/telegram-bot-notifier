<?php
namespace TNotifyer\Providers;

use PHPUnit\Framework\TestCase;
use TNotifyer\Engine\Storage;
use TNotifyer\Providers\FakeBot;
use TNotifyer\Database\FakeDBSimple;
use TNotifyer\Database\DB;

class DBTest extends TestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        Storage::set('DBSimple', new FakeDBSimple());
        Storage::set('Bot', new FakeBot(0));
    }

    /**
     * @dataProvider getLastLogDataProvider
     */
    public function testGetLastLog($args, $sql, $last_args)
    {
        $db = Storage::get('DBSimple');
        $db->reset();
        DB::get_last_log(...$args);

        $this->assertEquals($sql, substr( $db->last_sql, 0, strlen($sql) ));
        $this->assertEquals($last_args, $db->last_args);
    }

    public function getLastLogDataProvider()
    {
        $table_name = 'a_log';
        $orderby = 'id DESC';
        return [
            'limit' => [[5], "SELECT * FROM {$table_name} ORDER BY {$orderby} LIMIT ?", [5] ],
            'limit, bot_id' => [[5, 2], "SELECT * FROM {$table_name} WHERE bot_id=? ORDER BY {$orderby} LIMIT ?", [2, 5] ],
            'limit, bot_id(-1)' => [[5, -1], "SELECT * FROM {$table_name} WHERE bot_id=? ORDER BY {$orderby} LIMIT ?", [0, 5] ],
            'limit, bot_id, type' => [[5, 2, 't'], "SELECT * FROM {$table_name} WHERE bot_id=? AND type=? ORDER BY {$orderby} LIMIT ?", [2, 't', 5] ],
            'limit, type' => [[5, null, 't'], "SELECT * FROM {$table_name} WHERE type=? ORDER BY {$orderby} LIMIT ?", ['t', 5] ],
        ];
    }

    /**
     * @dataProvider getLastPostingsDataProvider
     */
    public function testGetLastPostings($args, $sql, $last_args)
    {
        $db = Storage::get('DBSimple');
        $db->reset();
        DB::get_last_postings(...$args);

        $this->assertEquals($sql, substr( $db->last_sql, 0, strlen($sql) ));
        $this->assertEquals($last_args, $db->last_args);
    }

    public function getLastPostingsDataProvider()
    {
        $table_name = 'postings';
        $orderby = 'id DESC';
        return [
            'limit' => [[5], "SELECT * FROM {$table_name} ORDER BY {$orderby} LIMIT ?", [5] ],
            'limit, bot_id' => [[5, 2], "SELECT * FROM {$table_name} WHERE bot_id=? ORDER BY {$orderby} LIMIT ?", [2, 5] ],
            'limit, bot_id(-1)' => [[5, -1], "SELECT * FROM {$table_name} WHERE bot_id=? ORDER BY {$orderby} LIMIT ?", [0, 5] ],
        ];
    }

    /**
     * @dataProvider getLastUpdatesDataProvider
     */
    public function testGetLastUpdates($args, $sql, $last_args)
    {
        $db = Storage::get('DBSimple');
        $db->reset();
        DB::get_last_updates(...$args);

        $this->assertEquals($sql, substr( $db->last_sql, 0, strlen($sql) ));
        $this->assertEquals($last_args, $db->last_args);
    }

    public function getLastUpdatesDataProvider()
    {
        $table_name = 'bot_updates';
        $orderby = 'created DESC, update_id';
        return [
            'limit' => [[5], "SELECT * FROM {$table_name} ORDER BY {$orderby} DESC LIMIT ?", [5] ],
            'limit, bot_id' => [[5, 2], "SELECT * FROM {$table_name} WHERE bot_id=? ORDER BY {$orderby} DESC LIMIT ?", [2, 5] ],
            'limit, bot_id(-1)' => [[5, -1], "SELECT * FROM {$table_name} WHERE bot_id=? ORDER BY {$orderby} DESC LIMIT ?", [0, 5] ],
        ];
    }

}
