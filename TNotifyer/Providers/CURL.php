<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Providers;

use TNotifyer\Providers\Log;
use TNotifyer\Exceptions\InternalException;

/**
 * 
 * Single object to provide a CURL functional.
 * 
 */
class CURL {
	/**
	 * @var bool make json decode after request
	 */
	public static $make_json_decode = true;

	/**
	 * @var bool silent on error
	 */
	public static $silent_on_error = false;

	/**
	 * @var string last error message
	 */
	public static $last_error_message;


	/**
	 * Make http GET or POST request
	 * 
	 * @param string url for request
	 * @param string request method ('GET' by default)
	 * @param array request headers (optional)
	 * @param mixed request body (optional)
	 */
	public static function request($url, $method = 'GET', $headers = [], $postfields = '') {
		$ch = curl_init();
		if ($ch === false) {
			$msg = 'Can not init curl';
			Log::put('error', $msg); // log error
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			throw new InternalException($msg);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if (!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// option to make more stable connection
		// curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

		// request method
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
		} elseif ($method != 'GET') {
			// Specify the HTTP method
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}

		// post data for request
		if (!empty($postfields)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		}

		$result = @curl_exec($ch);
		$error = @curl_error($ch);
		@curl_close($ch);

		if ($result === false || empty($result)) {
			// no data
			$msg = "No data from $url";
			if (!empty($error)) $msg .= ' | Error: ' . $error;
			Log::put('error', $msg); // log error
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			return false;
		}

		if (self::$make_json_decode) {
			$response = @json_decode($result, true);
			if (empty($response)) {
				// wrong data
				$msg = "Can't decode JSON: " . (json_last_error() != JSON_ERROR_NONE)? json_last_error_msg() : '';
				Log::put('error', $msg, $result); // log error
				if (!self::$silent_on_error) {
					echo $msg;
					print_r($result);
				}
				self::$last_error_message = $msg;
				return false;
			}
		} else {
			$response = $result;
		}
		
		return $response;
	}

	/**
	 * Make http GET request
	 * 
	 * @param string url for request
	 * @param array request headers (optional)
	 */
	public static function get($url, $headers = []) {
		return self::request($url, 'GET', $headers);
	}

	/**
	 * Make http POST request
	 * 
	 * @param string url for request
	 * @param array request headers (optional)
	 * @param mixed request body (optional)
	 */
	public static function post($url, $headers = [], $postfields = '') {
		return self::request($url, 'POST', $headers, $postfields);
	}

	/**
	 * Make http HEAD request
	 * 
	 * @param string url for request
	 * @param array request headers (optional)
	 */
	public static function head($url, $headers = []) {
		$ch = curl_init();
		if ($ch === false) {
			$msg = 'Can not init curl';
			Log::put('error', $msg); // log error
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			throw new InternalException($msg);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, true);
		if (!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// request method
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);

		// options to make more stable connection
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

		$result = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if (!empty($error)) {
			// has error
			$msg = 'HEAD request error: ' . $error;
			Log::put('warning', $msg); // log warning
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			return false;
		}
		
		return $result;
	}

	/**
	 * Check a status of website
	 * 
	 * @param string site url
	 * @param int time to sleep before repeat a check (optional)
	 */
	public static function pingUrl($url, $time = 2) {
		// $active = !empty(CURL::head($url)); // error on php 5.4
		$active = self::head($url);
		$active = !empty($active);

		// repeat check if down and $time > 0
		if (!$active && $time > 0) {
			set_time_limit($time * 60 + 30);
			sleep($time * 60);
			$active = self::head($url);
			$active = !empty($active);
		}
		
		return $active;
	}

	/**
	 * Get last error message
	 */
	public static function last_error_message() {
		return self::$last_error_message;
	}
}
