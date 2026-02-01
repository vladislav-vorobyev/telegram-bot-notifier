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

		$commands = [

			'/help' => function($bot) {
				$help = ''
					. '<b>/info</b> <i>- информация о приложении на хостинге</i>' . "\n"
					. '<b>/mainchats</b> <i>- список привязанных чатов</i>' . "\n"
					. '<b>/test</b> <i>- отправить тестовое сообщение в привязанные чаты</i>' . "\n"
					. '<b>/ozon</b> <i>- информация об OZON аккаунте</i>' . "\n"
					. '<b>/ozonid</b> <i>- отобразить установленный OZON CLIENT ID</i>' . "\n"
					. '<b>/ozonsetid</b> <i>- установить OZON CLIENT ID</i>' . "\n"
					. '<b>/ozonsetkey</b> <i>- установить OZON API KEY</i>';
				$bot->sendToAlarmChat($help, 'HTML');
			},

			'/test' => function($bot) {
				if ($mes_id = $bot->sendToMainChats('<b>Тестовое</b> <i>сообщение</i>', 'HTML'))
					$bot->sendToMainChats(
						'<b>Ответное</b> <i>сообщение</i>', 'HTML', true, array_map( function($val){ return ['reply_parameters' => ['message_id' => $val]]; }, $mes_id )
					);
			},

			'/info' => function($bot) {
				$data = Storage::get('App')->info();
				$bot->sendToAlarmChat('<code>' . Bot::convertToJson($data) . '</code>', 'HTML');
			},

			'/mainchats' => function($bot) {
				$data = array_reduce( $this->getMainChatsInfo(), function($a, $val) {
					$a[0] .= (++$a[1]) . '. ' . $val . "\n";
					return $a;
				}, ['', 0]);
				$bot->sendToAlarmChat($data[0]);
			},

			'/ozon' => function($bot) {
				$data = Storage::get('OZON')->getInfo();
				$bot->sendToAlarmChat('<code>' . Bot::convertToJson($data) . '</code>', 'HTML');
			},

			'/ozonid' => function($bot) {
				$data = $bot->getOption(Bot::ON_OZON_CLI_ID);
				$bot->sendToAlarmChat('<code>' . $data . '</code>', 'HTML');
			},

			'/ozonsetid' => [
				'Передайте пожалуйста OZON CLIENT ID или /cancel для отмены команды.',
				function($bot, $text) {
					$bot->changeOptionAct(Bot::ON_OZON_CLI_ID, $text, 'OZON CLIENT ID обновлен.');
				}
			],
			
			'/ozonsetkey' => [
				'Передайте пожалуйста OZON API KEY или /cancel для отмены команды.',
				function($bot, $text) {
					$text = Storage::get('Crypto')->encrypt($text);
					$bot->changeOptionAct(Bot::ON_OZON_API_KEY, $text, 'OZON API KEY обновлен.');
				}
			],
		];

		// inspecting message update
		$message = $update['message'] ?? $update['edited_message'] ?? [];
		$r_text = &$message['text'];
		$r_chat_id = &$message['chat']['id'];
		if (!empty($r_text) && !empty($r_chat_id)) {

			// get bot status on this chat
			$status_option_name = "chat_{$r_chat_id}_status";
			$chat_status = $this->getOption($status_option_name);

			if (empty($chat_status)) {
				// no specific status then try to find a command
				$r_command = &$commands[$r_text];
				if (!empty($r_command) && $this->checkCommandAccess($r_chat_id)) {
					if (is_callable($r_command)) {
						$r_command($this);
					} elseif (is_array($r_command)) {
						// two steps command
						$this->changeOptionAct($status_option_name, $r_text, $r_command[0]);
					}
				}

			} elseif ($r_text == '/cancel') {
				// bot status is not empty and we got the cancel command
				$this->changeOptionAct($status_option_name, '', 'Отмена команды');

			} else {
				// determine a command by bot status (two steps command)
				$r_command = &$commands[$chat_status][1];
				if (!empty($r_command) && is_callable($r_command) && $this->checkCommandAccess($r_chat_id)) {
					$r_command($this, $r_text);
				}
				// clear the status
				$this->changeOptionAct($status_option_name, '');
			}
		}
	}
	
	/**
	 * 
	 * Check an access from the chat to bot commands
	 * 
	 * @param string chat id
	 */
	public function checkCommandAccess($r_chat_id) {
		if ($r_chat_id != $this->admin_chat_id) {
			Log::put('warning', "Access forbidden from the chat {$r_chat_id}");
			return false;
		}
		return true;
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
	 * Get information about main chats
	 * 
	 * @return array list of chats info
	 */
	public function getMainChatsInfo() {
		return array_map( function($chat_id){ return $this->getChatTitle($chat_id); }, $this->getMainChatsIds() );
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
				$msg = "[{$this->getHostId()}] <code>{$url_str}</code> is <b>" . ($active? 'UP' : 'DOWN') . "</b>";
				$this->sendMessage($this->getAdminChatId(), $msg, 'HTML', false);
				
				// update stored data
				$sql = 'UPDATE a_websites SET active=' . ($active? 1 : 0) . ', updated=NOW() WHERE id=' . $website['id'];
				if (DB::query($sql) != 1) {
					Log::put('error', 'DB: wrong update', $sql);
				}
			}
		}
	}
}
