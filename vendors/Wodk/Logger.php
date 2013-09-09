<?php
class Logger {
	private $logFilePath;

	function __construct($strLogFilePath = './logger.log') {
		$this->logFilePath = $strLogFilePath;
		// Check if file exists
		if (!file_exists($this->logFilePath)) {
			// Create the file
			$handle = fopen($this->logFilePath, 'a');
			fclose($handle);
		}
	}
	
	public function log() {
		// Add the $str to the end of the file at $logFilePath
		if (is_writable($this->logFilePath)) {
			$args 		= func_get_args();
			$num		= count($args);
			$handle    	= fopen($this->logFilePath, 'a');
			$timestamp 	= date('r');
			$message	= array(
				'simple' => "[%s] %s\n",
				'complex' => "[%s] (%s) %s\n",
			);
			
			$log_msg = '';
			if ($num === 1) {
				$log_msg = sprintf($message['simple'], $timestamp, $args[0]);
			}
			elseif ($num > 1) {
				$type = array_shift($args);				
				foreach ($args as $str) {
					$log_msg .= sprintf($message['complex'], $timestamp, $type, $str);
				}
			}
			else {
				$log_msg = sprintf($message['simple'], $timestamp, 'ERROR');
			}
			
			// Write to the file and close
			fwrite($handle, $log_msg);
			fclose($handle);
		}
	}
	
	public function read($as_array = FALSE, $reverse = FALSE) {
		$contents = $as_array ? file($this->logFilePath) : file_get_contents($this->logFilePath);
		$contents = $as_array && $reverse ? array_reverse($contents) : $contents;
		return $contents;
	}
}
