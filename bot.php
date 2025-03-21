<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>T-bot</title>
<link rel='stylesheet' type='text/css' href='style.css?v=0.1'>
<script src="/js/jquery-3.2.1.min.js"></script>
</head>
<body>
<?php
require_once 'class-tbot.php';
require_once 'curl.php';
require_once 'db.php';

// bot
$tbot = new TBOT(BOT_INTERNAL_ID, BOT_TOKEN, BOT_CHAT_ID, BOT_HOST_ID);
CURL::$tbot = $tbot;
DB::$tbot = $tbot;

// init db
if (!DB::init()) {
	exit();
}

// action
$action = '';
if (!empty($_REQUEST['action'])) $action = $_REQUEST['action'];
?>

<form method="post">
action: <input name="action" value="<?=$action?>" style="width:calc(100vw - 200px)" />
</form>

<pre>
<?php
echo "Start!\n";

echo $action;
echo "\n";

// force a sending headers and start content
ob_end_flush();

// test message
// $posting_number = "23713478-0018-3";
// $response = $tbot->sendToMainChat("<b>OZON</b>\nНовый заказ: <code>{$posting_number}</code>", 'HTML');

// if (empty($action)) {
if ($action == 'up') {
	// bot_updates
	$response = $tbot->getUpdates();
} elseif($action == 'test-msg') {
	// bot_updates
	$response = $tbot->sendToMainChat('<b>Тестовое</b> <i>сообщение</i>', 'HTML');
} elseif (!empty($action)) {
	$response = $tbot->send($action);
}
if (!empty($response))
	print_r($response);


// up
if (isset($_GET['up'])) {
	print_r($tbot->getUpdates(true));
}


// watch dog
if (isset($_GET['watch-dog'])) {
	print_r($tbot->sendDayActivity());
}


// ping websites
if (isset($_GET['ping-websites'])) {
	print_r($tbot->pingWebsites());
}

if (isset($_GET['ping-url'])) {
	print_r($tbot->pingUrl($_GET['ping-url']));
}


// show list of last logs
if (isset($_GET['log'])) {
	$limit = intval($_GET['log']);
	if (!$limit) $limit = 5;
	echo "Last {$limit} from log:\n";
	$result = DB::get_last_log($limit);
	// print_r($response);
	?>
	<table id="log" class="db">
	<?php
	foreach ($result as $i => &$row) {
		if ($i === 0) {
			echo "<thead><tr>";
			foreach ($row as $key => &$val) echo "<th>$key</th>";
			echo "</tr></thead>\n";
		}
		echo "<tr>";
		foreach ($row as $key => &$val) {
			echo "<td class='t-$key'>$val</td>";
		}
		echo "</tr>\n";
	}
	?>
	</table>
	<?php
}


// show list of last postings
if (isset($_GET['last'])) {
	$limit = intval($_GET['last']);
	if (!$limit) $limit = 5;
	echo "Last {$limit} postings:\n";
	$result = DB::get_last_postings($limit);
	// print_r($response);
	?>
	<table id="postings" class="db">
	<?php
	foreach ($result as $i => &$row) {
		if ($i === 0) {
			echo "<thead><tr>";
			foreach ($row as $key => &$val) echo "<th>$key</th>";
			echo "</tr></thead>\n";
		}
		echo "<tr>";
		foreach ($row as $key => &$val) {
			if ($key == 'data') {
				$data = @json_decode($val);
				$val = $data? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) : $val;
			}
			echo "<td class='t-$key'>$val</td>";
		}
		echo "</tr>\n";
	}
	?>
	</table>
	<script language="javascript">
	var $=jQuery;
	$(document).ready(() => {
		$('#postings tbody td.t-data').on('click', function() {
			$(this).toggleClass('pre');
		}).addClass('pointer');
	});
	</script>
	<?php
}


$test_mode = isset($_GET['test']);

// OZON (test)
if (isset($_GET['ozt'])) {
	echo "\n<b>OZON (test):</b>\n";
	$headers = [
		'Client-Id: ' . OZON_CLIENT_ID,
		'Api-Key: ' . OZON_API_KEY,
		'Content-Type: application/json'
	];
	
	echo "\n========================================================================\n";
	$url = 'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list';
	$datetime_from = ( new DateTime('now') )->sub( DateInterval::createFromDateString('2 days') ); // sub 2 days
	$datetime_to = ( new DateTime('now') )->sub( DateInterval::createFromDateString('1 hour') ); // sub 1 hour
	$postfields = json_encode([
		'dir' => 'ASC',
		'limit' => 100,
		'offset' => 0,
		'filter' => [
			'cutoff_from' => $datetime_from->format(DateTimeInterface::RFC3339),
			'cutoff_to' => $datetime_to->format(DateTimeInterface::RFC3339)
		]
	]);
	echo "POST {$url}\n<i>{$postfields}</i>\n";
	$response = CURL::post($url, $headers, $postfields);
	print_r($response);

	echo "\n========================================================================\n";
	$url = 'https://api-seller.ozon.ru/v3/posting/fbs/list';
	$datetime_from = ( new DateTime('now') )->sub( DateInterval::createFromDateString('2 days') );
	$datetime_to = ( new DateTime('now') )->sub( DateInterval::createFromDateString('1 hour') );
	$postfields = json_encode([
		'dir' => 'ASC',
		'limit' => 100,
		'offset' => 0,
		'filter' => [
			'since' => $datetime_from->format(DateTimeInterface::RFC3339),
			'to' => $datetime_to->format(DateTimeInterface::RFC3339)
		]
	]);
	echo "POST {$url}\n<i>{$postfields}</i>\n";
	$response = CURL::post($url, $headers, $postfields);
	print_r($response);

	echo "\n========================================================================\n";
	$url = 'https://api-seller.ozon.ru/v3/posting/fbs/list';
	$datetime_from = ( new DateTime('now') )->sub( DateInterval::createFromDateString('7 days') );
	$datetime_to = ( new DateTime('now') )->add( DateInterval::createFromDateString('1 hour') );
	$postfields = json_encode([
		'dir' => 'ASC',
		'limit' => 100,
		'offset' => 0,
		'filter' => [
			'since' => $datetime_from->format(DateTimeInterface::RFC3339),
			'to' => $datetime_to->format(DateTimeInterface::RFC3339)
		]
	]);
	echo "POST {$url}\n<i>{$postfields}</i>\n";
	$response = CURL::post($url, $headers, $postfields);
	print_r($response);
}


// OZON
if (isset($_GET['ozon'])) {
	echo "\n<b>OZON:</b>\n";
	$headers = [
		'Client-Id: ' . OZON_CLIENT_ID,
		'Api-Key: ' . OZON_API_KEY,
		'Content-Type: application/json'
	];
	if ($test_mode) {
		require 'test_ozon.php';
	} else {
		$url = 'https://api-seller.ozon.ru/v3/posting/fbs/list';
		if (isset($_GET['7d']))
			$datetime_from = ( new DateTime('now') )->sub( DateInterval::createFromDateString('7 days') ); // sub 7 days
		else
			$datetime_from = ( new DateTime('now') )->sub( DateInterval::createFromDateString('6 hours') ); // sub 6 hours
		// $datetime_to = ( new DateTime('now') )->sub( DateInterval::createFromDateString('1 hour') ); // sub 1 hour
		$datetime_to = new DateTime('now');
		$postfields = json_encode([
			'dir' => 'ASC',
			'limit' => 100,
			'offset' => 0,
			'filter' => [
				'since' => $datetime_from->format(DateTimeInterface::RFC3339),
				'to' => $datetime_to->format(DateTimeInterface::RFC3339)
			]
		]);
		echo "POST {$url}\n<i>{$postfields}</i>\n";
		$response = CURL::post($url, $headers, $postfields);
	}
	print_r($response);
	
	if (empty($response)) {
		$tbot->log('error', 'OZON empty response or JSON error');
	} elseif (!isset($response['result']) || !isset($response['result']['postings'])) {
		$tbot->log('error', 'OZON wrong response:', $response);
	} elseif (!empty($response['result']['postings'])) {
		$postings = $response['result']['postings'];
		$tbot->log('notice', 'OZON postings', $postings);
		// $response = $tbot->sendToMainChat("<b>OZON</b>\n<code>" . json_encode($postings, JSON_UNESCAPED_UNICODE) . "</code>", 'HTML');
		// print_r($response);
		// process postings
		foreach ($postings as &$posting) {
			if (!isset($posting['posting_number']) || !isset($posting['status'])) {
				$tbot->log('error', 'OZON wrong posting data:', $posting);
			} else {
				// prepare value
				$posting_number = $posting['posting_number'];
				$status = $posting['status'];
				// check stored records
				$sql = "SELECT count(*) FROM postings WHERE posting_number='{$posting_number}' AND type='ozon' AND bot_id={$tbot->bot_id}";
				$count = ($result = DB::fetch_row($sql))? $result[0] : 0;
				if ($count == 0 || $test_mode) {
					if (in_array($status, ['cancelled', 'delivering', 'delivered'])) {
						echo "\n<b>(!) Status is not for notify: {$status}</b>\n";
					} else {
						// notify about new posting
						$text = "<b>OZON</b>\nНовый заказ: <code>{$posting_number}</code>";
						if (isset($posting['products'])) {
							foreach ($posting['products'] as &$product) {
								if (isset($product['name'])) {
									$price = round($product['price']);
									$text .= "\n<i>{$product['name']} ({$product['offer_id']}) {$product['quantity']}шт. {$price} ₽</i>";
								}
							}
						}
						$response = $tbot->sendToMainChat($text, 'HTML');
						print_r($response);
					}
					if ($count == 0) {
						// store the posting
						DB::insert_posting($tbot->bot_id, 'ozon', $posting_number, $status, $posting);
					}
				}
			}
		}
	}
	
	// log
	if (!$test_mode && !DEV_MODE) $tbot->log('check', 'OZON');
}


// WILDBERRIES DATA list
if (isset($_GET['wbl'])) {
	echo "\n<b>WB DATA list:</b>\n";
	$headers = [
		'Authorization: ' . WB_API_KEY
	];
	// $datetime_from = ( new DateTime('now') )->sub( DateInterval::createFromDateString('1 month') );
	// $datetime_to = new DateTime('now');
	// $url = 'https://marketplace-api.wildberries.ru/api/v3/orders/new';
	// $url = 'https://marketplace-api.wildberries.ru/api/v3/orders?limit=100&next=0&dateFrom='.$datetime_from->getTimestamp().'&dateTo='.$datetime_to->getTimestamp();
	$url = 'https://marketplace-api.wildberries.ru/api/v3/orders?limit=100&next=0';
	echo "GET {$url}\n";
	$response = CURL::get($url, $headers);
	echo "\n</pre><pre>";
	print_r_tree($response);
	echo "\n</pre><pre>";
	print_r($response);
}


// WILDBERRIES
if (isset($_GET['wb'])) {
	echo "\n<b>WB:</b>\n";
	$headers = [
		'Authorization: ' . WB_API_KEY
	];
	if ($test_mode) {
		require 'test_wb.php';
	} else {
		// $url = 'https://suppliers-api.wildberries.ru/api/v3/orders/new'; - deprecated
		$url = 'https://marketplace-api.wildberries.ru/api/v3/orders/new';
		echo "GET {$url}\n";
		$response = CURL::get($url, $headers);
	}
	print_r($response);
	
	if (empty($response)) {
		$tbot->log('error', 'WB empty response or JSON error');
	} elseif (!isset($response['orders'])) {
		$tbot->log('error', 'WB wrong response:', $response);
	} elseif (!empty($response['orders'])) {
		// loading cards list
		$cards_list = [];
		$body = '{ "settings": { "cursor": { "limit": 100 }, "filter": { "withPhoto": -1 } } }';
		$cards_list_res = CURL::post('https://content-api.wildberries.ru/content/v2/get/cards/list', $headers, $body);
		if (empty($cards_list_res)) {
			$tbot->log('error', 'WB empty response of cards/list or JSON error');
		} elseif (!isset($cards_list_res['cards'])) {
			$tbot->log('error', 'WB wrong cards/list response:', $cards_list_res);
		} else {
			$cards_list = $cards_list_res['cards'];
		}
		// loading cards list from trash
		// $cards_list2 = [];
		// $cards_list2_res = CURL::post('https://content-api.wildberries.ru/content/v2/get/cards/trash', $headers, $body);
		// if (empty($cards_list2_res)) {
			// $msg = 'WB: empty response on cards/trash';
			// if ($json_error = json_last_error_msg()) $msg .= ', JSON Error: ' . $json_error;
			// $tbot->log('error', $msg);
		// } elseif (!isset($cards_list2_res['cards'])) {
			// $tbot->log('error', 'WB: wrong cards/trash response', $cards_list2_res);
		// } else {
			// $cards_list2 = $cards_list2_res['cards'];
		// }
		
		// process orders
		$now = new DateTimeImmutable('now');
		$orders = $response['orders'];
		$tbot->log('notice', 'WB orders', $orders);
		foreach ($orders as &$order) {
			if (!isset($order['id'])) {
				$tbot->log('error', 'WB wrong order data:', $order);
			} else {
				// prepare value
				$order_id = $order['id'];
				// check date + 1 hour
				$notify_datetime = new DateTime($order['createdAt']);
				$notify_datetime->add(new DateInterval('PT1H')); // add 1 hour
				$notify_datetime->setTimezone(new DateTimeZone('Europe/Moscow'));
				if ($now > $notify_datetime) { // 1 hour passed, we can notify
					// check stored records
					$sql = "SELECT count(*) FROM postings WHERE posting_number='{$order_id}' AND type='wb' AND bot_id={$tbot->bot_id}";
					$count = ($result = DB::fetch_row($sql))? $result[0] : 0;
					if ($count == 0 || $test_mode) {
						// notify about new posting
						// calc price
						if (!empty($order['salePrice'])) {
							$price = round( intval($order['salePrice']) / 100 );
						}
						if (empty($price)) {
							$price = round( intval($order['price']) / 100 );
						}
						// prepare notice
						$text = "<b>WILDBERRIES</b>\nНовый заказ: <code>{$order_id}</code>";
						// $text .= "\n<i>{$order['orderUid']} {$order['article']} {$price} ₽</i>";
						$text .= "\n<i>{$order['article']} {$price} ₽</i>";
						// card info
						foreach ($cards_list as &$card) {
							if ($card['nmID'] == $order['nmId']) break;
						}
						// if (empty($card) || $card['nmID'] != $order['nmId']) {
							// try to find in trash but there are no title
							// foreach ($cards_list2 as &$card) {
								// if ($card['nmID'] == $order['nmId']) break;
							// }
						// }
						if (!empty($card) && $card['nmID'] == $order['nmId']) {
							$text .= "\n<i>{$card['title']}</i>";
							if (!empty($card['characteristics'])) {
								foreach ($card['characteristics'] as &$charact) {
									if ($charact['id'] == 14177449) { // Цвет
										$value = $charact['value'];
										if (is_array($value)) $value = $value[0];
										$text .= "\n<i>{$charact['name']}: {$value}</i>";
									}
								}
							}
							if (!empty($card['sizes']) && !empty($order['chrtId'])) {
								foreach ($card['sizes'] as &$size) {
									if ($size['chrtID'] == $order['chrtId']) {
										if ($size['wbSize'] != '' && $size['wbSize'] != 'ONE SIZE') {
											$text .= "\n<i>Размер: №{$size['techSize']} ({$size['wbSize']})</i>";
										}
									}
								}
							}
						}
						// Комментарий покупателя
						if (!empty($order['comment'])) {
							$text .= "\n<i>Комментарий покупателя: {$order['comment']}</i>";
						}
						// send
						$response = $tbot->sendToMainChat($text, 'HTML');
						print_r($response);
						if ($count == 0) {
							// store the order
							DB::insert_posting($tbot->bot_id, 'wb', $order_id, 'new', $order);
						}
					}
				} else {
					// less 1 hour, will wait
					echo "\n<p>{$order_id} waiting until " . $notify_datetime->format('Y-m-d H:i:s') . "</p>\n";
					$sql = "SELECT count(*) FROM postings WHERE posting_number='{$order_id}' AND type='wb-waiting' AND bot_id={$tbot->bot_id}";
					$count = ($result = DB::fetch_row($sql))? $result[0] : 0;
					if ($count == 0) {
						DB::insert_posting($tbot->bot_id, 'wb-waiting', $order_id, 'new', $order);
						// prepare notice
						$msg = "<b>WILDBERRIES</b>\nНовый заказ: <code>{$order_id}</code>";
						$msg .= "\n<i>{$order['article']} " . round( intval($order['price']) / 100 ) . " ₽</i>";
						$msg .= "\nЖдем до: " . $notify_datetime->format('Y-m-d H:i:s');
						$tbot->sendMessage(TBOT::ALARM_CHAT_ID, $msg, 'HTML', false);
					}
				}
			}
		}
	}
	
	// log
	if (!$test_mode && !DEV_MODE) $tbot->log('check', 'WB');
}

if (isset($_GET['wbt'])) {
	echo "\n<p><b>WB(test):</b></p>\n";
	$headers = [
		'Authorization: ' . WB_API_KEY
	];
	
	require 'test_wb.php';
	
	echo "<p><b>Now:</b>\n";
	$now = new DateTimeImmutable('now');
	print_r($now);
	echo "\n\n";
	
	$orders = $response['orders'];
	foreach ($orders as &$order) {
		// prepare value
		$order_id = $order['id'];
		// check date
		echo "<p><b>{$order['createdAt']}</b>\n";
		$notify_datetime = new DateTime($order['createdAt']);
		print_r($notify_datetime);
		$notify_datetime->add(new DateInterval('PT1H'));
		$notify_datetime->setTimezone(new DateTimeZone('Europe/Moscow'));
		print_r($notify_datetime);
		if ($now > $notify_datetime) {
			echo "\n<p>OK</p>\n";
		} else {
			echo "\n<p>wait...</p>\n";
		}
	}
	
}

if (isset($_GET['wbt2'])) {
	echo "\n<p><b>WB(test2):</b></p>\n";
	
	$headers = [
		'Authorization: ' . WB_API_KEY,
		'Content-Type: application/json'
	];
	
	// list
	$url = 'https://content-api.wildberries.ru/content/v2/get/cards/list';
	// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
	$body = '{ "settings": { "sort": { "ascending": false }, "cursor": { "limit": 100 }, "filter": { "withPhoto": -1 } } }';
	
	$response = CURL::post($url, $headers, $body);
	echo "\n<p><b>{$url}:</b></p>\n";
	print_r($response);
	
	// trash
	$url = 'https://content-api.wildberries.ru/content/v2/get/cards/trash';
	$body = '{ "settings": { "sort": { "ascending": false }, "cursor": { "limit": 100 }, "filter": { "withPhoto": -1 } } }';
	
	$response = CURL::post($url, $headers, $body);
	echo "\n<p><b>{$url}:</b></p>\n";
	print_r($response);
	
	// error/list
	$url = 'https://content-api.wildberries.ru/content/v2/cards/error/list';
	
	$response = CURL::get($url, $headers);
	echo "\n<p><b>{$url}:</b></p>\n";
	print_r($response);
	
	// DB::log('test', 'WB//cards/list', 0, $response);
	DB::log('test', 'WB//cards/list', 0);
	
}

echo "\nFin";


function print_r_tree($data)
{
	// capture the output of print_r
	$out = print_r($data, true);

	// replace ')' on its own on a new line (surrounded by whitespace is ok) with '</div>
	// $out = preg_replace('/^\s*\)\s*$/m', '</div>', $out);
	$out = str_replace(')', ')</div>', $out);

	// replace something like '[element] => <newline> (' with <a href="javascript:toggleDisplay('...');">...</a><div id="..." style="display: none;">
	$out = preg_replace_callback(
		'/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n([ \t]*\()/iU',
		function ($matches) {
			$id = substr(md5(rand().$matches[0]), 0, 7);
			return "{$matches[1]}<a href=\"javascript:toggleDisplay('{$id}');\">{$matches[2]}</a><div id=\"{$id}\" style=\"display: none;\">{$matches[3]}";
		},
		$out
	);

	// print the javascript function toggleDisplay() and then the transformed output
	echo '<script language="Javascript">function toggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display == "block") ? "none" : "block"; }</script>'."\n$out";
}