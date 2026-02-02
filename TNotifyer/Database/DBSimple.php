<?php
/**
 * 
 * This file is part of Telegram Notifyer project.
 * 
 */
namespace TNotifyer\Database;

use TNotifyer\Engine\Storage;

/**
 * 
 * Single object to provide base communication with database via stored object.
 * 
 */
class DBSimple {
	
	/**
	 * Get rows from a table
	 * 
	 * @param string table name
	 * @param mixed structure with next params:
	 * {
	 *   @param int 'limit' limit to get (optional)
	 *   @param string 'orderby' order by (optional)
	 *   @param array 'where' array of where cases (optional)
	 *    where case like 'column' => null | $value | ['operation', $value] | ['operation'] (skipped if null)
	 *   @param string 'columns' to select (optional, * by default)
	 * }
	 */
	public static function select($table_name, $params) {
		$bind = '';
		$args = [];

		// prepare where sql part
		$where = [];
		if (!empty($params['where'])) {
			foreach ($params['where'] as $key => $value)
				// skip null values
				if (!is_null($value)) {
					$add_arg = true;
					if (is_array($value)) {
						$operation = $value[0];
						if (isset($value[1])) {
							$operation .= '?';
							$value = $value[1];
						} else {
							// no arg to add
							$add_arg = false;
						}
					} else {
						// default operation
						$operation = '=?';
					}
					if ($add_arg) {
						// add arg and bindings string
						$args[] = $value;
						$bind .= is_string($value)? 's' : 'i';
					}
					$where[] = $key . $operation;
				}
		}
		$where = implode(' AND ', $where);
		if (!empty($where)) $where = ' WHERE ' . $where;

		// prepare order by sql part
		$orderby = '';
		if (!empty($params['orderby'])) {
			$orderby = ' ORDER BY ' . $params['orderby'];
		}

		// prepare limit sql part
		$limit = '';
		if (!empty($params['limit'])) {
			$limit = ' LIMIT ?';
			$bind .= 'i';
			$args[] = $params['limit'];
		}

		// prepare columns to select
		$columns = empty($params['columns'])? '*' : $params['columns'];

		// prepare sql
		$sql = "SELECT {$columns} FROM {$table_name}{$where}{$orderby}{$limit}";
		
		// execute
		return self::result_by_sql($sql, $bind, ...$args);
	}


	/**
	 * Report about request error
	 * 
	 * @param mixed error message (get error from mysql by default)
	 */
	public static function log_result_error($msg = false) {
		Storage::get('DBSimple')->log_result_error($msg);
	}

	/**
	 * Initialize connection to database
	 * 
	 * @param bool silent on error (false by default)
	 */
	public static function init($silent_on_error = false) {
		return Storage::get('DBSimple')->init($silent_on_error);
	}

	/**
	 * Make a query
	 * 
	 * @param string sql to run
	 */
	public static function query($sql) {
		return Storage::get('DBSimple')->query($sql);
	}

	/**
	 * Run query and fetch result (via fetch_all)
	 * 
	 * @param string sql to run
	 */
	public static function fetch_all($sql) {
		return Storage::get('DBSimple')->fetch_all($sql);
	}

	/**
	 * Run query and fetch result row (via fetch_row)
	 * 
	 * @param string sql to run
	 */
	public static function fetch_row($sql) {
		return Storage::get('DBSimple')->fetch_row($sql);
	}

	/**
	 * Get result by SQL
	 * 
	 * @param string sql to run
	 * @param string params bindings string
	 * @param mixed params to use
	 */
	public static function result_by_sql($sql, $bindings, ...$args) {
		return Storage::get('DBSimple')->result_by_sql($sql, $bindings, ...$args);
	}

	/**
	 * Execute SQL
	 * 
	 * @param string sql to run
	 * @param string params bindings string
	 * @param mixed params to use
	 */
	public static function execute_sql($sql, $bindings, ...$args) {
		return Storage::get('DBSimple')->execute_sql($sql, $bindings, ...$args);
	}


	/**
	 * To check is connection initialized
	 */
	public static function is_ready() {
		return Storage::get('DBSimple')->is_ready();
	}

	/**
	 * Get last error message
	 */
	public static function last_error_message() {
		return Storage::get('DBSimple')->last_error_message();
	}

	/**
	 * Check has been there an error
	 */
	public static function last_error() {
		return Storage::get('DBSimple')->last_error();
	}

}
