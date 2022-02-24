<?php

/**
 * Log Class
 * Whit this class you can log text and expressions
 *
 * @package Playa
 * @subpackage Tools
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class Plogger {

		/**
		 * Logs Path.
		 *
		 * @var string
		 */
		private static $_path = '';

		/**
		 * Log filename.
		 *
		 * @var string
		 */
		private static $_file_name;

		/**
		 * Log filename ext.
		 *
		 * @var string
		 */
		private static $_file_ext;

		/**
		 * Flag to indicate if decode the content from utf8.
		 *
		 * @var boolean
		 */
		private static $_utf8_decode;

		/**
		 * Plogger Init
		 *
		 * @param bool $utf8decode Decide if use utf8 or no
		 *
		 * @return void
		 */
		public static function init($utf8decode = TRUE)
		{
				self::$_file_name = date('d_m_Y');
				self::$_file_ext = '.txt';
				self::$_utf8_decode = $utf8decode OR FALSE;
				self::$_path = dirname(__FILE__) . '/../logs/';
		}

		/**
		 * Set Path for logging
		 *
		 * @param string $path Path for save logs
		 *
		 * @return void
		 */
		public static function set_path($path)
		{
				self::_is_init();
				self::$_path = $path;
		}

		/**
		 * Log string
		 *
		 * @param string $text Text to log
		 * @param boolean $saver_hour Decide if save our in log
		 *
		 * @return void
		 */
		public static function log($text, $saver_hour = TRUE)
		{
				self::_is_init();
				self::$_utf8_decode ? $text = utf8_decode($text) : NULL;
				$log_file = fopen(self::$_path . self::$_file_name . self::$_file_ext, 'a');
				if ($saver_hour) {
						fwrite($log_file, "[" . date("H:i:s") . "]" . str_repeat('_', 63) . "\r\n{$text}\r\n");
				}
				else {
						fwrite($log_file, $text . "\r\n");
				}
				fclose($log_file);
		}

		/**
		 * Log Expression
		 *
		 * @param mixed $expression Expression to log
		 * @param bool $debug Decide if save debud info in log
		 *
		 * @return void
		 */
		public static function log_var($expression, $debug = TRUE)
		{
				self::log((string) $expression);
				self::log('URI: ' . $_SERVER['REQUEST_URI'], FALSE);
				self::log('Expression:', FALSE);
				self::log(urlencode(serialize($expression)), FALSE);
				if ($debug) {
						self::log('Debug:', FALSE);
						self::log(urlencode(serialize(debug_backtrace())), FALSE);
						self::log('SERVER: ', FALSE);
						self::log(urlencode(serialize($_SERVER)), FALSE);
				}
		}

		/**
		 * Autoset Vars
		 *
		 * @return void
		 */
		private static function _is_init()
		{
				self::$_path === '' ? self::init() : NULL;
		}

		/**
		 * Decode serialized data and show result
		 *
		 * @return void
		 */
		public static function decode_log()
		{
				$html = <<<PQR
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<form action="" method="post">
    <textarea name="log"></textarea>
    <input type="submit">
</form>
PQR;
				echo $html;
				if ( ! empty($_POST['log'])) {
						$result = unserialize(urldecode($_POST['log']));
						self::var_dump($result);
				}
		}

		/**
		 * Set flag to decide if decode or not from utf8
		 *
		 * @param boolean $decode Set decoding in utf8 or not
		 *
		 * @return void
		 */
		public static function utf8_decode($decode)
		{
				self::$_utf8_decode = $decode OR FALSE;
		}

		/**
		 * Better var_dump
		 *
		 * @param mixed $expresion Expression to log
		 * @param boolean $debug Decide if save debug data
		 *
		 * @return void
		 */
		static function var_dump($expresion, $debug = FALSE)
		{
				if (ENVIRONMENT !== 'production') {
						echo '<pre style="clear:both;background:#CCC;border:solid 1px #EEE;border-top:solid 2px #666;color:#000;padding:7px;cursor:default">' . "\n";
						var_dump($expresion);
						if ($debug) {
								echo '<br><strong>Debug:</strong><br>';
								print_r(debug_backtrace());
						}
						echo '</pre>';
				}
				else {
						self::log_var($expresion, $debug);
				}
		}

}
