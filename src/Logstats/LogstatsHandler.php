<?php  namespace Logstats;

use Monolog\Handler\AbstractProcessingHandler;

class LogstatsHandler extends AbstractProcessingHandler {

	private $projectToken;
	private $webUrl;
	private $queuedRecords = [];
	private $sendOnDestruct;

	/**
	 * @param string Application URL
	 * @param string $projectToken Token of the project
	 */
	public function __construct($webUrl, $token, $sendOnDestruct = true) {
		$this->projectToken = $token;
		$this->webUrl = $webUrl;
		$this->sendOnDestruct = $sendOnDestruct;
	}

	/**
	 * Writes the record down to the logstats log
	 *
	 * @param  array $record
	 * @return void
	 */
	public function write(array $record) {
		$recordData = $this->getRecordDataInLogstatsFormat($record);
		$this->queuedRecords[] = $recordData;

		if (!$this->sendOnDestruct) {
			$this->send();
		}
	}

	/**
	 * Send queued records
	 */
	public function send() {
		$postData = http_build_query($this->getPostData());
		$this->queuedRecords = [];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->webUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_exec($ch);
		curl_close($ch);
	}

	public function __destruct() {
		if ($this->sendOnDestruct) {
			$this->send();
		}

		parent::__destruct();
	}

	private function getRecordDataInLogstatsFormat($record) {
		$recordData['message'] = $record['message'];
		$recordData['level'] = strtolower($record['level_name']);
		$recordData['context'] = $record['context'];
		$recordData['time'] = time();

		return $recordData;
	}

	private function getPostData() {
		return [
			"project" => $this->projectToken,
			"messages" => json_encode($this->queuedRecords)
		];
	}
}