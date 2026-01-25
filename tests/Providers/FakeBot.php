<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Providers;

/**
 * 
 * Provides the bot behavior for tests.
 * 
 */
class FakeBot {

	/**
	 * @var string last message to main chat
	 */
	public $last_main_msg = '';

	/**
	 * @var string last message to alarm chat
	 */
	public $last_alarm_msg = '';

	/**
	 * @var array Bot options
	 */
	public $options = [];

	/**
	 * @var mixed Ok API response
	 */
	public $okResponse = ['ok' => 1];

    /**
     * @var string Telegram bot API id
     */
	public $api_id;
	
    /**
     * @var string Telegram bot API key
     */
	public $api_key;
	
    /**
     * @var string Full Telergam API path for bot
     */
	public $api_path;
	
    /**
     * @var string Telergam bot webhook secret token
     */
	public $api_secret_token;
	
    /**
     * @var mixed Telergam bot user (got from API via getMe)
     */
	public $info = [];
	
    /**
     * @var int T-bot id (in DB identity)
     */
	public $bot_id;
	
    /**
     * @var int T-bot host id (in chat identity)
     */
	public $bot_host_id;
	
    /**
     * @var string Telergam alarm chat id (to notify on error)
     */
	public $admin_chat_id;

    /**
     * @var array Telergam main chats ids
     */
	public $main_chats_ids;
	

	/**
	 * 
	 * Constructor
	 * 
	 * @param int T-bot id (in DB identity)
	 * @param int T-bot host id (web hosting identity)
	 * @param string Telegram bot API token
	 * @param string Telergam admin/alarm chat id (to manage and notify on error)
	 */
	public function __construct($bot_id = 0, $bot_host_id = 0, $api_token = '00:AA', $admin_chat_id = '00') {

		$this->bot_id = $bot_id;
		$this->bot_host_id = $bot_host_id;
		$this->admin_chat_id = $admin_chat_id;

		// split token into id and key
		list($this->api_id, $this->api_key) = explode(':', $api_token, 2);

		// prepare API request uri and secret_token
		$this->api_path = '/';
		$this->api_secret_token = 'SS';
		$this->main_chats_ids = [];
	}
	
	/**
	 * API Id getter
	 * 
	 * @return int bot API id
	 */
	public function getAPIId() {
		return $this->api_id;
	}
	
	/**
	 * Id getter
	 * 
	 * @return int bot id
	 */
	public function getId() {
		return $this->bot_id;
	}
	
	/**
	 * Host id getter
	 * 
	 * @return int host id
	 */
	public function getHostId() {
		return $this->bot_host_id;
	}
	
	/**
	 * Admin chat id getter
	 * 
	 * @return int admin chat id
	 */
	public function getAdminChatId() {
		return $this->admin_chat_id;
	}
	
	/**
	 * Check an API response for good status
	 * 
	 * @param mixed API response
	 * 
	 * @return bool status
	 */
	public static function isOK($response) {
		return (isset($response['ok']) && $response['ok'] == 1)? true : false;
	}
	
	/**
	 * 
	 * Send an action to Telegram bot
	 * 
	 * @return mixed API response
	 */
	public function send($action, $postfields = null, $do_log = true) {
		return $this->okResponse;
	}
	
	/**
	 * 
	 * Get and save updates from Telegram bot
	 * 
	 * @return mixed API response
	 */
	public function getUpdates($new_only = true) {
		return $this->okResponse;
	}
	
	/**
	 * 
	 * Get and check updates from Telegram bot
	 * 
	 * @return mixed API response
	 */
	public function checkUpdates() {
		return $this->okResponse;
	}
	
	/**
	 * Check an update from Telegram bot.
	 * Add/remove a chats to/from main list.
	 */
	public function checkUpdate($update) {
	}
	
	/**
	 * 
	 * Telegram bot webhook handler
	 * 
	 * @return mixed response to API
	 */
	public function webhook() {
		return true;
	}
	
	/**
	 * 
	 * Prepare a Telegram bot webhook URL
	 * 
	 * @return string webhook URL
	 */
	public function getWebhookUrl() {
		return '/';
	}
	
	/**
	 * 
	 * Set a webhook to Telegram bot
	 * 
	 * @return mixed response from API
	 */
	public function setWebhook() {
		return $this->okResponse;
	}
	
	/**
	 * 
	 * Remove a webhook from Telegram bot
	 * 
	 * @return mixed response from API
	 */
	public function removeWebhook() {
		return $this->okResponse;
	}
	
	/**
	 * 
	 * Send a message to Telegram chat
	 * 
	 * @return bool status of the operation
	 */
	public function sendMessage($chat_id, $text, $parse_mode = '', $do_log = true) {
		return true;
	}
	
	/**
	 * Send a text message to the main Telegram chats
	 * 
	 * @return bool status of the operation
	 */
	public function sendToMainChats($text, $parse_mode = '', $do_log = true) {
		$this->last_main_msg = $text;
		return true;
	}
	
	/**
	 * Send a text message to the alarm Telegram chat
	 */
	public function sendToAlarmChat($message, $parse_mode = '') {
		$this->last_alarm_msg = $message;
		return true;
	}
	
	/**
	 * Send an alarm message
	 */
	public function alarm($message, $data = null) {
		return $this->sendToAlarmChat($message);
	}
	
	/**
	 * Prepare data as readable json for a message
	 */
	public static function convertToJson($data) {
		return mb_strimwidth(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), 0, 20, "...");
	}
	
	/**
	 * Get information about the bot
	 * 
	 * @return array bot information
	 */
	public function info() {
		return $this->info;
	}
    

	/**
	 * Get the bot option
	 */
	public function getOption($key, $default = false) {
		return $this->options[$key] ?? $default;
	}
	
	/**
	 * Set the bot option
	 */
	public function setOption($key, $value) {
		return true;
	}
	
	/**
	 * Get OZON client id and api key as array
	 */
	public function getOZONToken() {
		return ['', ''];
	}
	
	/**
	 * Change the bot option and sending success/fail message
	 */
	public function changeOptionAct($option_name, $value, $success_msg = '', $fail_msg = 'Ошибка изменения!') {
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
	}
	
	/**
	 * 
	 * Check a status of websites (by the list from DB)
	 * 
	 */
	public function pingWebsites() {
	}
}
