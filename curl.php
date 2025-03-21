<?php
// CURL functional

class CURL {
	// json decode
	public static $make_json_decode = true;

	// silent on error
	public static $silent_on_error = false;

	// tbot to log errors
	public static $tbot;

	// last error message
	public static $last_error_message;

	// make http GET or POST request
	public static function request($url, $method = 'GET', $headers = [], $postfields = '') {
		$ch = curl_init();
		if ($ch === false) {
			$msg = 'Can not init curl';
			if (!empty(self::$tbot)) self::$tbot->log('error', $msg); // log error
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			// exit();
			return false;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if (!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// option to make more stable connection to ozon
		// curl_setopt($ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

		// request method
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			// data for POST request
			if (!empty($postfields))
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		}

		$result = @curl_exec($ch);
		$error = @curl_error($ch);
		@curl_close($ch);

		if ($result === false || empty($result)) {
			// no data
			$msg = "No data from $url";
			if (!empty($error)) $msg .= ' | Error: ' . $error;
			if (!empty(self::$tbot)) self::$tbot->log('error', $msg); // log error
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			// exit();
			return $result;
		}

		if (self::$make_json_decode) {
			$response = @json_decode($result, true);
			if (empty($response)) {
				// wrong data
				$msg = "Can't decode JSON: " . (json_last_error() != JSON_ERROR_NONE)? json_last_error_msg() : '';
				if (!empty(self::$tbot)) self::$tbot->log('error', $msg, $result); // log error
				if (!self::$silent_on_error) {
					echo $msg;
					print_r($result);
				}
				self::$last_error_message = $msg;
				// exit();
				return $response;
			}
		}
		
		return $response;
	}

	// make GET request
	public static function get($url, $headers = []) {
		return self::request($url, 'GET', $headers);
	}

	// make POST request
	public static function post($url, $headers = [], $postfields = '') {
		return self::request($url, 'POST', $headers, $postfields);
	}

	// make HEAD request
	public static function head($url, $headers = []) {
		$ch = curl_init();
		if ($ch === false) {
			$msg = 'Can not init curl';
			if (!empty(self::$tbot)) self::$tbot->log('error', $msg); // log error
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			// exit();
			return false;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, true);
		if (!empty($headers))
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// request method
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
			if (!empty(self::$tbot)) self::$tbot->log('warning', $msg); // log warning
			if (!self::$silent_on_error) echo $msg;
			self::$last_error_message = $msg;
			// exit();
			return false;
		}
		
		return $result;
	}

	// get last error message
	public static function last_error_message() {
		return self::$last_error_message;
	}
}
