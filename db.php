<?php
// database
require_once HOST_HOME . '/ini_h.php';

class DB {
	// max log code length
	const LOG_CODE_LENGTH = 10000;
	
	// DB handler
	protected static $mysqli;
	
	// silent on error
	public static $silent_on_error;

	// tbot to log errors
	public static $tbot;

	// last error message
	public static $last_error_message;


	// initialize
	public static function init($silent_on_error = false) {
		self::$last_error_message = false;
		self::$silent_on_error = $silent_on_error;
		if (!self::$silent_on_error)
			mysqli_report(MYSQLI_REPORT_ERROR);
		
		self::$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_DB );

		if (mysqli_connect_errno()) {
			self::$last_error_message = $msg = sprintf( "Не удалось подключиться: %s", mysqli_connect_error() );
			if (!self::$silent_on_error) echo $msg;
			if (!empty(self::$tbot)) self::$tbot->alarm($msg); // send error
			// exit();
			return false;
		}
		
		return self::$mysqli;
	}


	// report about request error
	public static function log_result_error($msg = false) {
		if (!$msg) $msg = 'Ошибка запроса: ' . self::$mysqli->error;
		self::$last_error_message = $msg;
		if (!self::$silent_on_error)
			echo $msg;
		// send error to bot
		if (!empty(self::$tbot))
			self::$tbot->alarm($msg);
	}


	// query
	public static function query($sql) {
		$result = self::$mysqli->query($sql);
		// report error
		if ($result === false)
			self::log_result_error();
		// return result
		return $result;
	}


	// fetch_all
	public static function fetch_all($sql) {
		if ($res = self::$mysqli->query($sql)) {
			return $res->fetch_all( MYSQLI_ASSOC );
		}
		return false;
	}


	// fetch_row
	public static function fetch_row($sql) {
		if ($res = self::$mysqli->query($sql)) {
			return $res->fetch_row();
		}
		return false;
	}


	// write to log table
	public static function log($type, $message, $bot_id = 0, $data = null) {
		if (!isset(self::$mysqli)) return; // return if not initialized
		if (is_null($data)) {
			$sql = 'INSERT INTO a_log (type, message, bot_id) VALUES (?,?,?)';
		} else {
			$sql = 'INSERT INTO a_log (type, message, bot_id, data) VALUES (?,?,?,?)';
		}
		if ($stmt = self::$mysqli->prepare($sql)) {
			if (is_null($data)) {
				$stmt->bind_param( 'ssi', $type, $message, $bot_id );
			} else {
				// $data = mb_strimwidth(json_encode($data), 0, self::LOG_CODE_LENGTH, "...");
				// TODO: make a limit
				$data = json_encode($data);
				$stmt->bind_param( 'ssis', $type, $message, $bot_id, $data );
			}
			if (!$stmt->execute()) {
				self::log_result_error('Ошибка stmt: ' . $stmt->error); // report error
				$stmt->close();
				return false;
			}
			$result = $stmt->affected_rows;
			$stmt->close();
		} else {
			self::log_result_error(); // report error
			return false;
		}
		// return result
		return $result;
	}


	// write to bot_updates table
	public static function insert_bot_updates($bot_id, $updates) {
		$sql = 'INSERT IGNORE INTO bot_updates (bot_id, update_id, cmd, value) VALUES (?,?,?,?)';
		$count = 0;
		foreach ($updates as &$update) {
			// prepare values
			$update_id = intval($update['update_id']);
			$keys = array_diff( array_keys($update), ['update_id'] );
			$cmd = array_shift($keys);
			$value = json_encode($update);
			// prepare sql and execute
			if ($stmt = self::$mysqli->prepare($sql)) {
				$stmt->bind_param( "iiss", $bot_id, $update_id, $cmd, $value );
				if (!$stmt->execute()) {
					self::log_result_error('Ошибка stmt: ' . $stmt->error); // report error
					$stmt->close();
					return false;
				}
				$result = $stmt->affected_rows;
				$stmt->close();
				// count
				$count += $result;
			} else {
				self::log_result_error(); // report error
				return false;
			}
		}
		// return count
		return $count;
	}


	// write to postings table
	public static function insert_posting($bot_id, $type, $posting_number, $status, $posting) {
		$json = json_encode($posting);
		$sql = 'INSERT IGNORE INTO postings (bot_id, type, posting_number, status, data) VALUES (?,?,?,?,?)';
		// prepare sql and execute
		if ($stmt = self::$mysqli->prepare($sql)) {
			$stmt->bind_param( "issss", $bot_id, $type, $posting_number, $status, $json );
			$result = ($stmt->execute())? $stmt->affected_rows : false;
			if ($result === false) self::log_result_error('Ошибка stmt: ' . $stmt->error); // report error
			$stmt->close();
		} else {
			self::log_result_error(); // report error
			return false;
		}
		// return result
		return $result;
	}


	// write to activity table
	public static function insert_activity($bot_id, $type, $article, $status, $data) {
		$json = json_encode($data);
		$sql = 'INSERT INTO activity (bot_id, type, article, status, data) VALUES (?,?,?,?,?)';
		// prepare sql and execute
		if ($stmt = self::$mysqli->prepare($sql)) {
			$stmt->bind_param( "issss", $bot_id, $type, $article, $status, $json );
			$result = ($stmt->execute())? $stmt->affected_rows : false;
			if ($result === false) self::log_result_error('Ошибка stmt: ' . $stmt->error); // report error
			$stmt->close();
		} else {
			self::log_result_error(); // report error
			return false;
		}
		// return result
		return $result;
	}

	// update or insert row in activity table
	public static function upsert_activity($bot_id, $type, $article, $status) {
		$sql = 'INSERT INTO activity (bot_id, type, article, status) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status=?';
		// prepare sql and execute
		if ($stmt = self::$mysqli->prepare($sql)) {
			$stmt->bind_param( "issss", $bot_id, $type, $article, $status, $status );
			$result = ($stmt->execute())? $stmt->affected_rows : false;
			if ($result === false) self::log_result_error('Ошибка stmt: ' . $stmt->error); // report error
			$stmt->close();
		} else {
			self::log_result_error(); // report error
			return false;
		}
		// return result
		return $result;
	}


	// get last from postings table
	public static function get_last_postings($limit) {
		$sql = 'SELECT * FROM postings ORDER BY id DESC LIMIT ?';
		// prepare sql and execute
		if ($stmt = self::$mysqli->prepare($sql)) {
			$stmt->bind_param( "i", $limit );
			$result = ($stmt->execute())? self::_get_result($stmt) : false;
			if ($result === false) self::log_result_error('Ошибка stmt: ' . $stmt->error); // report error
			$stmt->close();
		} else {
			self::log_result_error(); // report error
			return false;
		}
		// return result
		return $result;
	}


	// get last from a_log table
	public static function get_last_log($limit) {
		$sql = 'SELECT * FROM a_log ORDER BY id DESC LIMIT ?';
		// prepare sql and execute
		if ($stmt = self::$mysqli->prepare($sql)) {
			$stmt->bind_param( "i", $limit );
			$result = ($stmt->execute())? self::_get_result($stmt) : false;
			if ($result === false) self::log_result_error('Ошибка stmt: ' . $stmt->error); // report error
			$stmt->close();
		} else {
			self::log_result_error(); // report error
			return false;
		}
		// return result
		return $result;
	}


	// get last error message
	public static function last_error_message() {
		return self::$last_error_message;
	}

	// to check has been there an error
	public static function last_error() {
		return !(self::$last_error_message === false);
	}


	// get result from stmt
	static function _get_result($stmt) {
		$result = [];
		// prepare array to bind
		$meta = $stmt->result_metadata();
		while ($field = $meta->fetch_field()) {
			$params[] = &$row[$field->name];
		}
		// bind var for fetch
		call_user_func_array(array($stmt, 'bind_result'), $params);
		// fetch all data
		while ($stmt->fetch()) {
			foreach($row as $key => $val) {
				$c[$key] = $val;
			}
			$result[] = $c;
		}
		return $result;
	}
}
