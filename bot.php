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
	print_db_table($result);
}


// show list of last postings
if (isset($_GET['last'])) {
	$limit = intval($_GET['last']);
	if (!$limit) $limit = 5;
	echo "Last {$limit} postings:\n";
	$result = DB::get_last_postings($limit);
	// print_r($response);
	print_db_table($result);
}


$test_mode = isset($_GET['test']);

// ===== OZON (test) ===========================================================
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


// ===== OZON ==================================================================
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


// ===== WILDBERRIES ===========================================================
if (isset($_GET['wb'])) {
	echo "\n<b>WB:</b>\n";
	$headers = [
		'Authorization: ' . WB_API_KEY
	];
	if ($test_mode) {
		require 'test_wb.php';
	} else {
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
						// $price = round( intval($order['salePrice']) / 100 );
						$price = round( intval($order['price']) / 100 );
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


// ===== WILDBERRIES Orders list =====
if (isset($_GET['wbl'])) {
	echo "\n<b>WB Orders list:</b>\n";
	$headers = [
		'Authorization: ' . WB_API_KEY,
		'Content-Type: application/json'
	];
	// $datetime_from = ( new DateTime('now') )->sub( DateInterval::createFromDateString('1 month') );
	// $datetime_to = new DateTime('now');
	// $time_filter = '&dateFrom='.$datetime_from->getTimestamp().'&dateTo='.$datetime_to->getTimestamp();
	$url = 'https://marketplace-api.wildberries.ru/api/v3/orders?limit=100&next=0'; //.$time_filter;
	echo "GET {$url}\n";
	$response = CURL::get($url, $headers);
	if (isset($response['orders'])) {
		echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
		print_r_tree2($response);
		echo "\n</pre><pre>";
		// get statuses
		$ids = [];
		$orders = [];
		foreach ($response['orders'] as &$order) {
			$ids[] = $order['id'];
			// $orders[$order['id']] = ['order' => $order];
		}
		$body = json_encode(['orders' => $ids]);
		// $body = '{"orders": [3154889391]}';
		$url = 'https://marketplace-api.wildberries.ru/api/v3/orders/status';
		echo "POST {$url}\n{$body}\n";
		$statuses = CURL::post($url, $headers, $body);
		if (isset($statuses['orders'])) {
			foreach ($statuses['orders'] as &$order) {
				$orders[$order['id']] = ['supplierStatus' => $order['supplierStatus'], 'wbStatus' => $order['wbStatus']];
			}
			foreach ($response['orders'] as &$order) {
				$orders[$order['id']]['article'] = $order['article'];
				$orders[$order['id']]['order'] = $order;
			}
			echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
			print_r_tree2($orders);
			echo "\n</pre><pre>";
		} else {
			echo "\n<b>ERROR</b>\n";
			print_r($statuses);
		}
	} else {
		echo "\n<b>ERROR</b>\n";
		print_r($response);
	}
}

// ===== WILDBERRIES Prices list =====
/*
Бот каждые 10 минут запрашивает список товаров по адресу:
https://discounts-prices-api.wildberries.ru/api/v2/list/goods/filter?limit=1000
Далее вычисляет размер скидки для каждого размера товара, исходя из цены и цены со скидкой.
Если обнаруживает превышение максимальной скидки (15%), то отправляет сообщение
в телеграм и запрос на изменение скидки на нормальную (11%) по адресу:
https://discounts-prices-api.wildberries.ru/api/v2/upload/task
*/
if (!defined('MAX_DISCOUNT')) define('MAX_DISCOUNT', 15);
if (!defined('NORM_DISCOUNT')) define('NORM_DISCOUNT', 11);

if (isset($_GET['wbdis'])) {
	echo "\n<b>WB Set discount:</b>\n";
	$headers = [
		'Authorization: ' . WB_API_KEY,
		'Content-Type: application/json'
	];
	$body = json_encode(['data' => [['nmID' => 306652549, 'discount' => 16]]]); // бр_взр_скр_бел
	$url = 'https://discounts-prices-api.wildberries.ru/api/v2/upload/task';
	echo "POST {$url}\n{$body}\n";
	$response = CURL::post($url, $headers, $body);
	echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
	print_r_tree2($response);
}

if (isset($_GET['wbp'])) {
	echo "\n<b>WB Prices list:</b>\n";
	$headers = [
		'Authorization: ' . WB_API_KEY,
		'Content-Type: application/json'
	];
	$url = 'https://discounts-prices-api.wildberries.ru/api/v2/list/goods/filter?limit=1000';
	echo "GET {$url}\n";
	$response = CURL::get($url, $headers);
	echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
	print_r_tree2($response);
	?>
	</pre>
	<table>
	<tr><th>vendorCode</th><th>techSizeName</th><th>price</th><th>discountedPrice</th><th>%</th><th>clubDiscountedPrice</th><th>%</th></tr>
	<?php
	$alerts = [];
	$alert_off = false; // something updated back to off
	foreach ($response['data']['listGoods'] as &$item) {
		foreach ($item['sizes'] as &$size) {
			$discount = 100 - round($size['discountedPrice']/$size['price']*100);
			$clubDiscount = 100 - round($size['clubDiscountedPrice']/$size['price']*100);
			if ($discount > MAX_DISCOUNT || $clubDiscount > MAX_DISCOUNT) {
				// add to list for alert
				$size['nmID'] = $item['nmID'];
				$size['discount'] = max($discount, $clubDiscount);
				$size['discountedPrice'] = min($size['discountedPrice'], $size['clubDiscountedPrice']);
				$alerts[$item['vendorCode']] = $size;
			} else {
				if ($result = DB::upsert_activity($tbot->bot_id, 'alert', $item['vendorCode'], 'off')) {
					$alert_off = true;
				} elseif ($result === false) {
					break; // break on error
				}
			}
			echo "<tr>";
			echo "<td>{$item['vendorCode']}</td>";
			echo "<td>{$size['techSizeName']}</td>";
			echo "<td>{$size['price']}</td>";
			echo "<td>{$size['discountedPrice']}</td>";
			echo "<td style='".($discount > MAX_DISCOUNT? 'color:red' : '')."'>{$discount}%</td>";
			echo "<td>{$size['clubDiscountedPrice']}</td>";
			echo "<td style='".($clubDiscount > MAX_DISCOUNT? 'color:red' : '')."'>{$clubDiscount}%</td>";
			echo "</tr>\n";
		}
		if (DB::last_error()) break; // break on error
	}
	?>
	</table>
	<pre>
	<?php
	if (count($alerts)) {
		// prepare and send notification if something changed and prepare request list
		$msg = '';
		$uplist = [];
		foreach ($alerts as $vendorCode => &$size) {
			if ($result = DB::upsert_activity($tbot->bot_id, 'alert', $vendorCode, 'on')) {
				// add to notice
				$msg .= "\n<code>{$vendorCode}</code> <b>{$size['discount']}%</b> (цена со скидкой <i>{$size['discountedPrice']}₽</i>)";
				$uplist[] = ['nmID' => $size['nmID'], 'discount' => NORM_DISCOUNT];
			} elseif ($result === false) {
				break; // break on error
			}
		}
		if (strlen($msg)) {
			// send notification
			$tbot->sendToMainChat("<b>WILDBERRIES</b>\n<b>Высокая скидка!!!</b>" . $msg, 'HTML');
			// send update request if option is on
			if (isset($_GET['wbdofix'])) {
				$body = json_encode(['data' => $uplist]);
				$url = 'https://discounts-prices-api.wildberries.ru/api/v2/upload/task';
				echo "POST {$url}\n{$body}\n";
				DB::log('wbdofix', 'POST discount fix', $tbot->bot_id, $uplist);
				$response = CURL::post($url, $headers, $body);
				DB::log('wbdofix', 'Fix result', $tbot->bot_id, $response);
				echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
				print_r_tree2($response);
			}
		}
	} elseif($alert_off) {
		// notify about all is ok
		$tbot->sendToMainChat("<b>WILDBERRIES</b>\nСкидки в пределах ".MAX_DISCOUNT."%.", 'HTML');
	}
}

// ===== WILDBERRIES test =====
if (isset($_GET['wbt'])) {
	echo "\n<p><b>WB(test):</b></p>\n";
	
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

// ===== WILDBERRIES testing cards list =====
if (isset($_GET['wbtcl'])) {
	echo "\n<p><b>WB testing cards list:</b></p>\n";
	
	$headers = [
		'Authorization: ' . WB_API_KEY,
		'Content-Type: application/json'
	];
	
	// list
	$url = 'https://content-api.wildberries.ru/content/v2/get/cards/list';
	// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
	$body = '{ "settings": { "sort": { "ascending": false }, "cursor": { "limit": 100 }, "filter": { "withPhoto": -1 } } }';
	echo "\nPOST {$url}\n{$body}\n";
	$response = CURL::post($url, $headers, $body);
	echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
	print_r_tree2($response);
	
	// trash
	$url = 'https://content-api.wildberries.ru/content/v2/get/cards/trash';
	$body = '{ "settings": { "sort": { "ascending": false }, "cursor": { "limit": 100 }, "filter": { "withPhoto": -1 } } }';
	echo "\nPOST {$url}\n{$body}\n";
	$response = CURL::post($url, $headers, $body);
	echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
	print_r_tree2($response);
	
	// error/list
	$url = 'https://content-api.wildberries.ru/content/v2/cards/error/list';
	echo "\nGET {$url}\n";
	$response = CURL::get($url, $headers);
	echo "\n</pre><pre class=print-r-tree style='--line-limit: 80vw;'>";
	print_r_tree2($response);
	
	// DB::log('test', 'WB//cards/list', 0, $response);
	DB::log('test', 'WB//cards/list', 0);
	
}

echo "\nFin";


function print_db_table($rows)
{
	?>
	<table class="db">
	<?php
	foreach ($rows as $i => &$row) {
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
		$('table.db tbody td.t-data').off('click').on('click', function() {
			$(this).toggleClass('pre');
		}).addClass('pointer');
	});
	</script>
	<?php
}


function print_r_tree($data)
{
	// capture the output of print_r
	$out = print_r($data, true);

	// replace ')' on its own on a new line (surrounded by whitespace is ok) with '</div>
	$out = preg_replace('/^\s*\)\s*$/m', '</div>', $out);

	// replace something like '[element] => <newline> (' with <a href="javascript:toggleDisplay('...');">...</a><div id="..." style="display: none;">
	$out = preg_replace_callback(
		'/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n([ \t]*\()/iU',
		function ($matches) {
			$id = substr(md5(rand().$matches[0]), 0, 7);
			return "{$matches[1]}<a href=\"javascript:toggleDisplay('{$id}');\">{$matches[2]}</a><div id=\"{$id}\" style=\"display: none;\">";
		},
		$out
	);

	// print the javascript function toggleDisplay() and then the transformed output
	echo '<script language="Javascript">function toggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display == "block") ? "none" : "block"; }</script>'."\n$out";
}

function print_r_tree2($data)
{
	// capture the output of print_r
	$out = print_r($data, true);

	// adding </span></span> after ')'
	// $out = str_replace(")\n", ")\n</span></span>", $out);
	$out = preg_replace('/^(\s*)\)\s*$/m', '\1)</span></span>', $out);

	// insert into something like '[element] => <newline> (' the <span class=a-box> actions and one more <span>
	$out = preg_replace_callback(
		'/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n([ \t]*)\(/iU',
		function ($matches) {
			return "{$matches[1]}{$matches[2]}<span class=a-box><b class=toggle-a></b><b class=open-all></b><b class=close-all></b>\n<span>{$matches[3]}(";
		},
		$out
	);

	// print the transformed output
	echo $out;
	?>
	<script language="javascript">
	(function($) {
		if (window.print_r_tree_click_handler == undefined) {
			window.print_r_tree_click_handler = function() { $(this).parent().toggleClass('open'); }
			window.print_r_tree_clickp_handler = function() { $(this).parent().find('.a-box').addClass('open'); }
			window.print_r_tree_clickm_handler = function() { $(this).parent().find('.a-box').removeClass('open'); }
			$(document).ready(() => {
				$('.print-r-tree .toggle-a').off('click').on('click', window.print_r_tree_click_handler).addClass('pointer');
				$('.print-r-tree .open-all').off('click').on('click', window.print_r_tree_clickp_handler).addClass('pointer');
				$('.print-r-tree .close-all').off('click').on('click', window.print_r_tree_clickm_handler).addClass('pointer');
			});
		}
	})(jQuery);
	</script>
	<?php
}