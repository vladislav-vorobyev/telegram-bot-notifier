<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Providers;

use TNotifyer\Database\DB;
use TNotifyer\Engine\Storage;
use TNotifyer\Providers\Log;

/**
 * 
 * Provides the bot behavior and some additional actions.
 * 
 */
class Bot extends TelegramBot {

	/**
	 * OZON client id option name
	 */
	const ON_OZON_CLI_ID = 'ozon-id';
	
	/**
	 * OZON api key option name
	 */
	const ON_OZON_API_KEY = 'ozon-key';
	
	/**
	 * Get the bot option
	 * 
	 * @param string option key
	 * @param string option default value
	 * 
	 * @return mixed option value or default
	 */
	public function getOption($key, $default = false) {
		$option = DB::get_bot_option($this->bot_id, $key);
		return $option['value'] ?? $default;
	}
	
	/**
	 * Set the bot option
	 * 
	 * @param string option key
	 * @param string option
	 * 
	 * @return bool action status
	 */
	public function setOption($key, $value) {
		return 1 == DB::save_bot_option($this->bot_id, $key, $value);
	}
	
	/**
	 * Get OZON client id and api key as array
	 * 
	 * @return array token
	 */
	public function getOZONToken() {
		return [$this->getOption(self::ON_OZON_CLI_ID), Storage::get('Crypto')->decrypt($this->getOption(self::ON_OZON_API_KEY))];
	}
	
	/**
	 * 
	 * Check an update from Telegram bot (overrides parent).
	 * Provides some bot actions via messages.
	 * 
	 * @param mixed incoming API update
	 */
	public function checkUpdate($update) {
		parent::checkUpdate($update);

		if (empty($update)) return;

		// inspecting message update
		$message = $update['message'] ?? $update['edited_message'] ?? [];
		$r_chat_id = &$message['chat']['id'];
		if (!empty($message['text']) && !empty($r_chat_id)) {

			// get bot status on this chat
			$status_option_name = "chat_{$r_chat_id}_status";
			$chat_status = $this->getOption($status_option_name);

			if (empty($chat_status)) {
				// no specific status
				// check the message text
				switch ($message['text']) {

					case '/test':
						$this->sendToMainChats('<b>Тестовое</b> <i>сообщение</i>', 'HTML');
						break;

					case '/info':
						$data = Storage::get('App')->info();
						$this->sendToAlarmChat('<code>' . self::convertToJson($data) . '</code>', 'HTML');
						break;

					case '/ozon':
						$data = Storage::get('OZON')->getInfo();
						$this->sendToAlarmChat('<code>' . self::convertToJson($data) . '</code>', 'HTML');
						break;

					case '/ozon id':
						$data = $this->getOption(self::ON_OZON_CLI_ID);
						$this->sendToAlarmChat('<code>' . $data . '</code>', 'HTML');
						break;

					case '/ozon set id':
						$this->changeOptionAct(
							$status_option_name,
							'ozon-set-id',
							'Передайте пожалуйста новый OZON CLIENT ID или /cancel для отмены команды.'
						);
						break;

					case '/ozon set key':
						$this->changeOptionAct(
							$status_option_name,
							'ozon-set-key',
							'Передайте пожалуйста новый OZON API KEY или /cancel для отмены команды.'
						);
						break;
				}

			} elseif($message['text'] == '/cancel') {
				// bot status is not standard and we got the cancel command
				$this->changeOptionAct($status_option_name, '', 'Отмена команды');

			} else {
				// determine by bot status
				switch ($chat_status) {

					case 'ozon-set-id':
						$value = $message['text'];
						$this->changeOptionAct(self::ON_OZON_CLI_ID, $value, 'OZON CLIENT ID обновлен.');
						break;

					case 'ozon-set-key':
						$value = Storage::get('Crypto')->encrypt($message['text']);
						$this->changeOptionAct(self::ON_OZON_API_KEY, $value, 'OZON API KEY обновлен.');
						break;
				}

				// clear the status
				$this->changeOptionAct($status_option_name, '');
			}
		}
	}
	
	/**
	 * 
	 * Change the bot option and sending success/fail message
	 * 
	 * @param string option name
	 * @param string new value
	 * @param string success message text (optional)
	 * @param string fail message text (optional)
	 */
	public function changeOptionAct($option_name, $value, $success_msg = '', $fail_msg = 'Ошибка изменения!') {
		if ($this->setOption($option_name, $value)) {
			if (!empty($success_msg))
				$this->sendToAlarmChat($success_msg, 'HTML');
		} else {
			$this->sendToAlarmChat($fail_msg, 'HTML');
		}
	}
	
	/**
	 * 
	 * Run jobs activity of the bot
	 * 
	 */
	public function runJobs() {
	}
	
	/**
	 * 
	 * Send a TelegramBot day activity message to alarm Telegram chat
	 * 
	 */
	public function sendTbotDayActivity() {
		// get activity statistic from DB
		$sql = 'SELECT count(*) FROM a_log WHERE created BETWEEN DATE_SUB(NOW(),INTERVAL 1 DAY) AND NOW() AND type=\'check\' AND bot_id=' . $this->getId();
		$count = ($result = DB::fetch_row($sql))? $result[0] : 0;

		// send a message
		$msg = "<b>TODAY:</b> bot [{$this->getId()}] checks count = $count";
		$this->sendToAlarmChat($msg, 'HTML');
	}
	
	/**
	 * 
	 * Check a status of websites (by the list from DB)
	 * 
	 */
	public function pingWebsites() {
		// get a list from DB
		$sql = 'SELECT * FROM a_websites';
		$websites = DB::fetch_all($sql);

		// check status for each website
		foreach ($websites as $website) {
			$url = $website['url'];

			// get site status
			$active = Storage::get('CURL')->pingUrl($url);

			// compare the status with stored
			if ($website['active'] != $active) {
				// send a message about status change to alarm chat
				$url_str = str_replace('https://', '', $url);
				$msg = "[{$this->bot_host_id}] <code>{$url_str}</code> is <b>" . ($active? 'UP' : 'DOWN') . "</b>";
				$this->sendMessage($this->alarm_chat_id, $msg, 'HTML', false);
				
				// update stored data
				$sql = 'UPDATE a_websites SET active=' . ($active? 1 : 0) . ', updated=NOW() WHERE id=' . $website['id'];
				if (DB::query($sql) != 1) {
					Log::put('error', 'DB: wrong update', $sql);
				}
			}
		}
	}
}
