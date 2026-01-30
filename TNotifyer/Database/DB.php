<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Database;

use TNotifyer\Engine\Storage;
use TNotifyer\Providers\Log;

/**
 * 
 * Single object to provide communication with database.
 * 
 */
class DB extends DBSimple {
	/**
	 * Max log code length
	 */
	const LOG_CODE_LENGTH = 5000;
	

	/**
	 * Store to log table
	 * 
	 * @param int bot id
	 * @param string type
	 * @param string message
	 * @param mixed data (optional)
	 * 
	 * @return int action result
	 */
	public static function log($bot_id, $type, $message, $data = null) {
		if (!self::is_ready()) return; // return if not initialized
		if (is_null($data)) {
			// store without data
			$sql = 'INSERT INTO a_log (bot_id, type, message) VALUES (?,?,?)';
			return self::execute_sql($sql, 'iss', $bot_id, $type, $message);
		} else {
			$json = json_encode($data);
			// make a limit
			if (strlen($json) > self::LOG_CODE_LENGTH) {
				$json = json_encode( mb_strimwidth($json, 0, self::LOG_CODE_LENGTH - 10, '...') );
			}
			// store
			$sql = 'INSERT INTO a_log (bot_id, type, message, data) VALUES (?,?,?,?)';
			return self::execute_sql($sql, 'isss', $bot_id, $type, $message, $json);
		}
	}


	/**
	 * Store to bot_updates table
	 * 
	 * @param int bot id
	 * @param array updates
	 * 
	 * @return int stored count
	 */
	public static function insert_bot_updates($bot_id, $updates) {
		$sql = 'INSERT IGNORE INTO bot_updates (bot_id, update_id, cmd, value) VALUES (?,?,?,?)';
		$count = 0;
		foreach ($updates as &$update) {
			// prepare values
			$update_id = intval($update['update_id']);
			$keys = array_diff( array_keys($update), ['update_id'] );
			$cmd = array_shift($keys);
			$value = json_encode($update);
			// execute
			$result = self::execute_sql($sql, 'iiss', $bot_id, $update_id, $cmd, $value);
			if ($result === false) {
				return false;
			}
			// count
			$count += $result;
		}
		// return count
		return $count;
	}

	/**
	 * Store to postings table
	 */
	public static function insert_posting($bot_id, $type, $posting_number, $status, $posting) {
		$json = json_encode($posting);
		$sql = 'INSERT IGNORE INTO postings (bot_id, type, posting_number, status, data) VALUES (?,?,?,?,?)';
		// execute
		return self::execute_sql($sql, 'issss', $bot_id, $type, $posting_number, $status, $json);
	}


	/**
	 * Store to activity table
	 */
	public static function insert_activity($bot_id, $type, $article, $status, $data) {
		$json = json_encode($data);
		$sql = 'INSERT INTO activity (bot_id, type, article, status, data) VALUES (?,?,?,?,?)';
		// execute
		return self::execute_sql($sql, 'issss', $bot_id, $type, $article, $status, $json);
	}


	/**
	 * Get rows from a table
	 * 
	 * @param string table name
	 * @param mixed structure with next params:
	 * {
	 *   @param int 'limit' limit to get (optional)
	 *   @param string 'orderby' order by (optional)
	 *   @param mixed 'where' where cases like ['column', $value] (optional)
	 *   @param string columns to select (optional, * by default)
	 * }
	 */
	public static function get_rows($table_name, $params) {
		$bind = '';
		$args = [];

		// prepare where sql part
		$where = [];
		if (!empty($params['where'])) {
			foreach ($params['where'] as $case) {
				$where[] = $case[0] . '=?';
				$args[] = $case[1];
				$bind .= is_string($case[1])? 's' : 'i';
			}
		}
		$where = implode(' AND ', $where);
		if (!empty($where)) $where = ' WHERE ' . $where;

		// prepare order by sql part
		$orderby = '';
		if (!empty($params['orderby'])) {
			$orderby = ' ORDER BY ' . $params['orderby'];
		}

		// prepare limit sql part
		$limit = '';
		if (!empty($params['limit'])) {
			$limit = ' LIMIT ?';
			$bind .= 'i';
			$args[] = $params['limit'];
		}

		// prepare columns to select
		$columns = empty($params['columns'])? '*' : $params['columns'];

		// prepare sql
		$sql = "SELECT {$columns} FROM {$table_name}{$where}{$orderby}{$limit}";
		
		// execute
		return self::result_by_sql($sql, $bind, ...$args);
	}

	/**
	 * Get last from postings table
	 * 
	 * @param int limit to get
	 * @param int bot id (optional, -1 = use Bot from Storage)
	 */
	public static function get_last_postings($limit, $bot_id = null) {
		$where = [];
		if (!is_null($bot_id)) $where[] = ['bot_id', $bot_id === -1? Storage::get('Bot')->getId() : $bot_id];
		// execute
		return self::get_rows('postings', ['limit' => $limit, 'orderby' => 'id DESC', 'where' => $where]);
	}

	/**
	 * Get last from a_log table
	 * 
	 * @param int limit to get
	 * @param int bot id (optional, -1 = use Bot from Storage)
	 * @param string type (optional)
	 */
	public static function get_last_log($limit, $bot_id = null, $type = null) {
		$where = [];
		if (!is_null($bot_id)) $where[] = ['bot_id', $bot_id === -1? Storage::get('Bot')->getId() : $bot_id];
		if (!is_null($type)) $where[] = ['type', $type];
		// execute
		return self::get_rows('a_log', ['limit' => $limit, 'orderby' => 'id DESC', 'where' => $where]);
	}

	/**
	 * Get last from bot_updates table
	 * 
	 * @param int limit to get
	 * @param int bot id (optional, -1 = use Bot from Storage)
	 */
	public static function get_last_updates($limit, $bot_id = null) {
		$where = [];
		if (!is_null($bot_id)) $where[] = ['bot_id', $bot_id === -1? Storage::get('Bot')->getId() : $bot_id];
		// execute
		return self::get_rows('bot_updates', ['limit' => $limit, 'orderby' => 'created DESC, update_id DESC', 'where' => $where]);
	}


	/**
	 * Determine a time from last record of type
	 * 
	 * @param int bot id (optional, -1 = use Bot from Storage)
	 * @param string type (optional)
	 * @param string message (optional)
	 * 
	 * @return array ['sec', 'created'] time in seconds and created time
	 */
	public static function get_last_log_time($bot_id = null, $type = null, $message = null) {
		$where = [];
		if (!is_null($bot_id)) $where[] = ['bot_id', $bot_id === -1? Storage::get('Bot')->getId() : $bot_id];
		if (!is_null($type)) $where[] = ['type', $type];
		if (!is_null($message)) $where[] = ['message', $message];
		// $sql = "SELECT TIME_TO_SEC( TIMEDIFF( NOW(), created ) ) AS sec, created FROM a_log {$where} ORDER by id DESC LIMIT 1";
		// execute
		return self::get_rows('a_log', [
			'columns' => 'TIME_TO_SEC( TIMEDIFF( NOW(), created ) ) AS sec, created',
			'where' => $where,
			'orderby' => 'id DESC',
			'limit' => 1,
		]);
	}


	/**
	 * Store to bot chats table
	 */
	public static function insert_bot_chats($bot_id, $chat_id, $type, $title) {
		$sql = 'INSERT INTO bot_chats (bot_id, chat_id, type, title) VALUES (?,?,?,?)';
		// execute
		return self::execute_sql($sql, 'isss', $bot_id, $chat_id, $type, $title);
	}

	/**
	 * Remove from bot chats table
	 */
	public static function remove_bot_chats($bot_id, $chat_id) {
		$sql = 'DELETE FROM bot_chats WHERE bot_id = ? AND chat_id = ?';
		// execute
		return self::execute_sql($sql, 'is', $bot_id, $chat_id);
	}

	/**
	 * Get from bot chats table
	 */
	public static function get_bot_chats($bot_id) {
		$sql = 'SELECT * FROM bot_chats WHERE bot_id = ?';
		// execute
		return self::result_by_sql($sql, 'i', $bot_id);
	}


	/**
	 * Store to bot options table
	 * 
	 * @param int bot internal id
	 * @param string option key
	 * @param string option value
	 * 
	 * @return int action result
	 */
	public static function save_bot_option($bot_id, $key, $value) {
		// get id
		$old = self::get_bot_option($bot_id, $key);
		$r_id = &$old['id'];
		if (!empty($r_id)) {
			// update
			$sql = 'UPDATE bot_options SET `value` = ? WHERE id = ?';
			return self::execute_sql($sql, 'si', $value, $r_id);
		} else {
			// insert new
			$sql = 'INSERT INTO bot_options (bot_id, `key`, `value`) VALUES (?,?,?)';
			return self::execute_sql($sql, 'iss', $bot_id, $key, $value);
		}
	}

	/**
	 * Get from bot options table
	 * 
	 * @param int bot internal id
	 * @param string option key
	 * 
	 * @return array option row or empty array
	 */
	public static function get_bot_option($bot_id, $key) {
		$sql = 'SELECT * FROM bot_options WHERE bot_id = ? AND `key` = ?';
		// execute
		$res = self::result_by_sql($sql, 'is', $bot_id, $key);
		// return first row if found
		return (!empty($res))? $res[0] : [];
	}

}
