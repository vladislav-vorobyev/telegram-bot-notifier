<?php
// Telergam bot

require_once 'curl.php';
require_once 'db.php';

class TBOT {
	// notify chat on error
	const ALARM_CHAT_ID = '701162167';
	
	// max alarm code length
	const ALARM_CODE_LENGTH = 500;
	
	// bot id (in DB identity)
	public $bot_id;
	
	// API key
	protected $api_key;
	
	// Telergam API path
	protected $api_path;
	
	// Telergam main chat id
	public $main_chat_id;
	
	// bot host id (in chat identity)
	public $bot_host_id;
	
	// initialize
	public function __construct($bot_id, $api_key, $main_chat_id, $bot_host_id) {
		$this->bot_id = $bot_id;
		$this->api_key = $api_key;
		$this->api_path = 'https://api.telegram.org/bot' . $api_key . '/';
		$this->main_chat_id = $main_chat_id;
		$this->bot_host_id = $bot_host_id;
	}
	
	// prepare data as readable json for message
	public static function json_for_message($data) {
		return mb_strimwidth(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), 0, self::ALARM_CODE_LENGTH, "...");
	}
	
	// send alarm message
	public function alarm($message, $data = null) {
		$this->sendMessage(self::ALARM_CHAT_ID, "[{$this->bot_host_id}] $message", '', false);
		if (!is_null($data))
			$this->sendMessage(self::ALARM_CHAT_ID, '<code>' . self::json_for_message($data) . '</code>', 'HTML', false);
	}
	
	// log
	public function log($type, $message, $data = null) {
		// alarm chat notify
		if ($type == 'error')
			$this->alarm($message, $data);
		// log to DB
		DB::log($type, $message, $this->bot_id, $data);
	}
	
	// send an action to API
	public function send($action, $postfields = null, $do_log = true) {
		// do log
		if ($do_log)
			$this->log('tbot-send', $action, $postfields);
		// do request
		$response = CURL::post(
			$this->api_path . $action,
			['Content-Type: application/json'],
			is_null($postfields)? '' : json_encode($postfields)
		);
		// check
		if ($do_log) {
			if (!$response) {
				$this->log('error', "Empty response on $action");
			} elseif (!isset($response['ok']) || $response['ok'] != 1) {
				$this->log('error', "Wrong response on $action", $response);
			}
		}
		
		return $response;
	}
	
	// getUpdates
	public function getUpdates($new_only = false) {
		$action = 'getUpdates';
		// filter
		if ($new_only) {
			$sql = 'SELECT max(update_id)+1 FROM bot_updates WHERE bot_id=' . $this->bot_id;
			$update_id = ($result = DB::fetch_row($sql))? $result[0] : 1;
			$action .= '?offset=' . $update_id;
		}
		// get
		$response = $this->send($action);
		// save and check
		if ($response && isset($response['result'])) {
			DB::insert_bot_updates($this->bot_id, $response['result']);
			
			// my_chat_member
			foreach ($response['result'] as $update) {
				if (isset($update['my_chat_member']) && isset($update['my_chat_member']['new_chat_member']) && $update['my_chat_member']['new_chat_member']['status'] == 'member') {
					$chat = $update['my_chat_member']['chat'];
					// print_r($chat);
				}
			}
		}
		
		return $response;
	}
	
	// sendMessage
	public function sendMessage($chat_id, $text, $parse_mode = '', $do_log = true) {
		$action = 'sendMessage';
		$postfields = [
			'chat_id' => $chat_id,
			'text' => $text
		];
		if (!empty($parse_mode))
			$postfields['parse_mode'] = $parse_mode;
		// run
		$response = $this->send($action, $postfields, $do_log);
		
		return $response;
	}
	
	// sendToMainChat
	public function sendToMainChat($text, $parse_mode = '', $do_log = true) {
		return $this->sendMessage($this->main_chat_id, $text, $parse_mode, $do_log);
	}
	
	// sendDayActivity
	public function sendDayActivity() {
		// get
		$sql = 'SELECT count(*) FROM a_log WHERE created BETWEEN DATE_SUB(NOW(),INTERVAL 1 DAY) AND NOW() AND type=\'check\' AND bot_id=' . $this->bot_id;
		$count = ($result = DB::fetch_row($sql))? $result[0] : 0;
		// send
		$msg = "<b>TODAY:</b> checks count = $count";
		$response = $this->sendMessage(self::ALARM_CHAT_ID, $msg, 'HTML', false);
		
		return $response;
	}
	
	// pingUrl
	public function pingUrl($url, $time = 2) {
		// $active = !empty(CURL::head($url)); // php 5.4 error
		$active = CURL::head($url);
		$active = !empty($active);
		// repeat check if down and $time > 0
		if (!$active && $time > 0) {
			set_time_limit($time * 60 + 30);
			sleep($time * 60);
			$active = CURL::head($url);
			$active = !empty($active);
		}
		return $active;
	}
	
	// pingWebsites
	public function pingWebsites() {
		$response = '';
		// get
		$sql = 'SELECT * FROM a_websites';
		$websites = DB::fetch_all($sql);
		foreach ($websites as $website) {
			$url = $website['url'];
			$active = self::pingUrl($url);
			// compare status
			if ($website['active'] != $active) {
				// send
				$url_str = str_replace('https://', '', $url);
				$msg = "[{$this->bot_host_id}] <code>{$url_str}</code> is <b>" . ($active? 'UP' : 'DOWN') . "</b>";
				$response = $this->sendMessage(self::ALARM_CHAT_ID, $msg, 'HTML', false);
				// update
				$sql = 'UPDATE a_websites SET active='.($active?1:0).', updated=NOW() WHERE id='.$website['id'];
				if (DB::query($sql) != 1) {
					$this->log('error', 'DB: wrong update', $sql);
				}
			}
		}
		return $response;
	}
}
