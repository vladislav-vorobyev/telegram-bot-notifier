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
	 * Prepare JSON value to save in database
	 * 
	 * @param mixed data
	 * 
	 * @return string json string
	 */
	public static function _json($data) {
		$json = json_encode($data);
		// make a limit
		if (strlen($json) > self::LOG_CODE_LENGTH) {
			$json = json_encode( mb_strimwidth($json, 0, self::LOG_CODE_LENGTH - 10, '...') );
		}
		return $json;
	}
	

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
			// store
			$sql = 'INSERT INTO a_log (bot_id, type, message, data) VALUES (?,?,?,?)';
			return self::execute_sql($sql, 'isss', $bot_id, $type, $message, self::_json($data));
		}
	}


	/**
	 * Store to bot_log table
	 * 
	 * @param int bot id
	 * @param string action
	 * @param mixed request
	 * @param mixed response
	 */
	public static function insert_bot_log($bot_id, $action, $request, $response) {
		if (!self::is_ready()) return; // return if not initialized
		// store
		$sql = 'INSERT INTO bot_log (bot_id, action, request, response) VALUES (?,?,?,?)';
		return self::execute_sql($sql, 'isss', $bot_id, $action, self::_json($request), self::_json($response));
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
	 * 
	 * @param int bot internal id
	 * @param string type
	 * @param string posting number
	 * @param string status value
	 * @param mixed posting data
	 */
	public static function insert_posting($bot_id, $type, $posting_number, $status, $posting) {
		$json = json_encode($posting);
		$sql = 'INSERT IGNORE INTO postings (bot_id, type, posting_number, status, data) VALUES (?,?,?,?,?)';
		// execute
		return self::execute_sql($sql, 'issss', $bot_id, $type, $posting_number, $status, $json);
	}

	/**
	 * Store to activity table
	 * 
	 * @param int bot internal id
	 * @param string type
	 * @param string article
	 * @param string status value
	 * @param mixed data
	 */
	public static function insert_activity($bot_id, $type, $article, $status, $data) {
		$json = json_encode($data);
		$sql = 'INSERT INTO activity (bot_id, type, article, status, data) VALUES (?,?,?,?,?)';
		// execute
		return self::execute_sql($sql, 'issss', $bot_id, $type, $article, $status, $json);
	}

	/**
	 * Store to posting status table
	 * 
	 * @param int bot internal id
	 * @param string type
	 * @param string posting number
	 * @param string status value
	 * @param mixed message_id (optional)
	 * @param string created (optional)
	 */
	public static function save_posting_status($bot_id, $type, $posting_number, $status, $message_id = [], $created = '') {
		if (empty($created)) {
			$sql = 'INSERT INTO posting_status (bot_id, type, posting_number, status, message_id) VALUES (?,?,?,?,?)'
			. ' ON DUPLICATE KEY UPDATE `status`=?, `updated`=NOW()';
			return self::execute_sql($sql, 'isssss', $bot_id, $type, $posting_number, $status, self::_json($message_id), $status);
		} else {
			$sql = 'INSERT INTO posting_status (bot_id, type, posting_number, status, message_id, created) VALUES (?,?,?,?,?,?)'
			. ' ON DUPLICATE KEY UPDATE `status`=?, `updated`=NOW()';
			return self::execute_sql($sql, 'issssss', $bot_id, $type, $posting_number, $status, self::_json($message_id), $created, $status);
		}
	}


	/**
	 * Get last from postings table
	 * 
	 * @param int limit to get
	 * @param int bot internal id (optional, -1 = use Bot from Storage)
	 * @param string type (optional)
	 * @param string posting number (optional)
	 * @param string posting status (optional)
	 */
	public static function get_last_postings($limit, $bot_id = null, $type = null, $posting_number = null, $status = null) {
		if ($bot_id === -1) $bot_id = Storage::get('Bot')->getId();
		// execute
		return self::select('postings', [
			'where' => ['bot_id' => $bot_id, 'type' => $type, 'posting_number' => $posting_number, 'status' => $status],
			'orderby' => 'id DESC',
			'limit' => $limit
		]);
	}

	/**
	 * Get last from a_log table
	 * 
	 * @param int limit to get
	 * @param int bot internal id (optional, -1 = use Bot from Storage)
	 * @param string type (optional)
	 */
	public static function get_last_log($limit, $bot_id = null, $type = null) {
		if ($bot_id === -1) $bot_id = Storage::get('Bot')->getId();
		// execute
		return self::select('a_log', [
			'where' => ['bot_id' => $bot_id, 'type' => $type], 'orderby' => 'id DESC', 'limit' => $limit
		]);
	}

	/**
	 * Get last from bot_updates table
	 * 
	 * @param int limit to get
	 * @param int bot internal id (optional, -1 = use Bot from Storage)
	 */
	public static function get_last_updates($limit, $bot_id = null) {
		if ($bot_id === -1) $bot_id = Storage::get('Bot')->getId();
		// execute
		return self::select('bot_updates', [
			'where' => ['bot_id' => $bot_id], 'orderby' => 'created DESC, update_id DESC', 'limit' => $limit
		]);
	}

	/**
	 * Get from posting status table
	 * 
	 * @param int limit to get
	 * @param int bot internal id (optional, -1 = use Bot from Storage)
	 * @param string type (optional)
	 * @param string posting number (optional)
	 * @param string posting status (optional)
	 */
	public static function get_posting_status($limit, $bot_id = null, $type = null, $posting_number = null, $status = null) {
		if ($bot_id === -1) $bot_id = Storage::get('Bot')->getId();
		// execute
		return self::select('posting_status', [
			'where' => ['bot_id' => $bot_id, 'type' => $type, 'posting_number' => $posting_number, 'status' => $status],
			'orderby' => 'created DESC',
			'limit' => $limit
		]);
	}

	/**
	 * Determine a time from last record of type
	 * 
	 * @param int bot id (optional, -1 = use Bot from Storage)
	 * @param string type (optional)
	 * @param string message (optional)
	 * 
	 * @return array ['sec', 'created'] time in seconds and created datetime
	 */
	public static function get_last_log_time($bot_id = null, $type = null, $message = null) {
		if ($bot_id === -1) $bot_id = Storage::get('Bot')->getId();
		// execute
		return self::select('a_log', [
			'columns' => 'TIME_TO_SEC( TIMEDIFF( NOW(), created ) ) AS sec, created',
			'where' => ['bot_id' => $bot_id, 'type' => $type, 'message' => $message],
			'orderby' => 'id DESC',
			'limit' => 1,
		]);
	}

	/**
	 * Determine a days period to earliest record of posting_status
	 * 
	 * @param int bot id (optional, -1 = use Bot from Storage)
	 * @param string type (optional)
	 * @param string posting number (optional)
	 * @param string posting status (optional)
	 * 
	 * @return array ['days', 'created'] days period and created datetime
	 */
	public static function get_days_of_status($bot_id = null, $type = null, $posting_number = null, $status = null) {
		if ($bot_id === -1) $bot_id = Storage::get('Bot')->getId();
		// execute
		return self::select('posting_status', [
			'columns' => 'DATEDIFF( NOW(), created ) AS days, created',
			'where' => ['bot_id' => $bot_id, 'type' => $type, 'posting_number' => $posting_number, 'status' => $status],
			'orderby' => 'created ASC',
			'limit' => 1,
		]);
	}


	/**
	 * Store to bot chats table
	 * 
	 * @param int bot internal id
	 * @param string chat id
	 * @param string type
	 * @param string chat title
	 */
	public static function insert_bot_chats($bot_id, $chat_id, $type, $title) {
		$sql = 'INSERT INTO bot_chats (bot_id, chat_id, type, title) VALUES (?,?,?,?)';
		// execute
		return self::execute_sql($sql, 'isss', $bot_id, $chat_id, $type, $title);
	}

	/**
	 * Remove from bot chats table
	 * 
	 * @param int bot internal id
	 * @param string chat id
	 */
	public static function remove_bot_chats($bot_id, $chat_id) {
		$sql = 'DELETE FROM bot_chats WHERE bot_id = ? AND chat_id = ?';
		// execute
		return self::execute_sql($sql, 'is', $bot_id, $chat_id);
	}

	/**
	 * Get from bot chats table
	 * 
	 * @param int bot internal id
	 * @param string type (optional)
	 */
	public static function get_bot_chats($bot_id, $type = null) {
		return self::select('bot_chats', ['where' => ['bot_id' => $bot_id, 'type' => $type]]);
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
		if (!empty($old['id'])) {
			// update
			$sql = 'UPDATE bot_options SET `value` = ? WHERE id = ?';
			return self::execute_sql($sql, 'si', $value, $old['id']);
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
