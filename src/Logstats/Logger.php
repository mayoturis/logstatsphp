<?php  namespace Logstats; 

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger {

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var array Context for current object instance
	 */
	private $permanentContext = [];

	/**
	 * @var array Context for all instances
	 */
	private static $staticPermanentContext = [];

	/**
	 * @var array
	 */
	private $records = [];

	/**
	 * @param string $webUrl Application URL
	 * @param string $projectToken Token of the project
	 */
	public function __construct($webUrl, $token, $options = []) {
		$this->options = array_merge($this->getDefaultOptions(), $options);
		$this->handler = new LogstatsHandler($webUrl, $token, $this->options['log_on_destruct']);
		$this->registerDefaultLoggingContext();
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function log($level, $message, array $context = array()) {
		$record = $this->createRecord($level, $message, $context);
		$this->handler->write($record);
	}


	/**
	 * Create record in monolog format
	 *
	 * @param $level
	 * @param $message
	 * @param $context
	 * @return array
	 */
	private function createRecord($level, $message, $context) {
		return [
			'level_name' => $level,
			'message' => $message,
			'context' => $this->merge_arrays([self::$staticPermanentContext, $this->permanentContext, $context]),
		];
	}

	/**
	 * Add context for all records from this instance
	 *
	 * @param array $context
	 */
	public function addPermanentContext(array $context) {
		foreach (Arr::dot($context) as $dotKey => $value) {
			Arr::set($this->permanentContext, $dotKey, $value);
		}
	}

	/**
	 * Add context for all records from all instances
	 *
	 * @param array $context
	 */
	public static function addStaticPermanentContext(array $context) {
		foreach (Arr::dot($context) as $dotKey => $value) {
			Arr::set(self::$staticPermanentContext, $dotKey, $value);
		}
	}

	/**
	 * Get default options
	 *
	 * @return array
	 */
	private function getDefaultOptions() {
		return [
			'log_server' => false,
			'log_on_destruct' => true
		];
	}

	/**
	 * Merge arrays in one. Latter array with higher priority
	 *
	 * @param array $arrays
	 * @return array
	 */
	private function merge_arrays(array $arrays) {
		if (empty($arrays)) {
			return [];
		}
		$firstArray = array_shift($arrays);
		foreach ($arrays as $mergingArray) {
			foreach (Arr::dot($mergingArray) as $dotKey => $value) {
				Arr::set($firstArray, $dotKey, $value);
			}
		}

		return $firstArray;
	}

	/**
	 * Register default context
	 */
	private function registerDefaultLoggingContext() {
		if ($this->options['log_server']) {
			$this->addPermanentContext(['server' => $_SERVER]);
		}
	}
}