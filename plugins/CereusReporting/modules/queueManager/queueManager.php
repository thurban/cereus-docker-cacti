<?php
/*
Simple PHP Task Queue implementation.
Author: Raivo Ratsep http://raivoratsep.com on 6/01/2011
Version: 0.1a
*/
class Queue {

	public function run() {
		foreach ($this->get_tasks() as $task) {
			$result = $this->execute_script($task['run_script'], $task['method'], unserialize($task['script_params']));
			if($result === true) {
				$this->mark_complete($task['id']);
				echo "Task id {$task['id']} complete.<br>";
			} else {
				echo "Task id {$task['id']} not complete.<br>";
			}
		}
	}

	private function mark_complete($task_id) {
		// Get DB Instance
		$db = DBCxn::get();
		$sql = "UPDATE plugin_CereusReporting_queue SET completed = 1, completed_datetime = NOW() WHERE id = ? LIMIT 1;";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(1, $task_id, PDO::PARAM_INT);
		$stmt->execute();
		$stmt->closeCursor();
	}

	private function execute_script($script_to_run, $method, $param_array) {
		global $config;
		$query_string = http_build_query($param_array);
		// TODO: Change URL
		switch ($method) {
			case 'POST':
				$urlConn = curl_init ("http://".CRON_DIR_USERNAME.":".CRON_DIR_PASSWORD."@{$_SERVER['HTTP_HOST']}".$config['url_path']."/plugins/CereusReporting/queueScripts/{$script_to_run}.php");
				curl_setopt ($urlConn, CURLOPT_POST, 1);
				curl_setopt ($urlConn, CURLOPT_POSTFIELDS, $query_string);  //submitting an array did not work :(
				break;

			case 'GET':
				$urlConn = curl_init ("http://".CRON_DIR_USERNAME.":".CRON_DIR_PASSWORD."@{$_SERVER['HTTP_HOST']}".$config['url_path']."/plugins/CereusReporting/queueScripts/{$script_to_run}.php?$query_string");
				curl_setopt ($urlConn, CURLOPT_HTTPGET, 1);
				break;
		}

		ob_start(); // prevent the buffer from being displayed
		curl_exec($urlConn);
		$raw_response = ob_get_contents();
		ob_end_clean();
		curl_close($urlConn);       // close the connection


		$result_array = json_decode($raw_response, true);
		if(isset($result_array['status'])) {
			return $result_array['status'];
		} else {
			return -1;
		}
	}

	private function get_tasks() {
		// Get DB Instance
		$db = DBCxn::get();
		$sql = "SELECT * FROM plugin_CereusReporting_queue WHERE completed = 0;";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$out = array();
		while($row = $stmt->fetch()) {
			$out[] = $row;
		}
		$stmt->closeCursor();
		return $out;
	}

	private function get_status() {
		// Get DB Instance
		$db = DBCxn::get();
		$sql = "SELECT * FROM plugin_CereusReporting_queue WHERE completed = 0;";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$out = array();
		while($row = $stmt->fetch()) {
			$out[] = $row;
		}
		$stmt->closeCursor();
		return $out;
	}

	public static function add($run_script, Array $params, $method = 'GET') {
		// Get DB Instance
		$db = DBCxn::get();
		$sql = "INSERT INTO plugin_CereusReporting_queue (run_script, script_params, inserted_datetime, task_hash, method) VALUES (?,?, NOW(),?,?);";
		$stmt = $db->prepare($sql);
		$serialized_params = serialize($params);
		$stmt->bindValue(1, $run_script, PDO::PARAM_STR);
		$stmt->bindValue(2, $serialized_params, PDO::PARAM_STR);
		$stmt->bindValue(3, hash('sha256', $run_script.$serialized_params), PDO::PARAM_STR);
		$stmt->bindValue(4, strtoupper($method));
		$stmt->execute();
		$stmt->closeCursor();
	}

	public static function exists($run_script, Array $params) {
		// Get DB Instance
		$db = DBCxn::get();
		$sql = "SELECT id FROM plugin_CereusReporting_queue WHERE task_hash = ?;";
		$stmt = $db->prepare($sql);
		$serialized_params = serialize($params);
		$stmt->bindParam("1", hash('sha256',$run_script.$serialized_params), PDO::PARAM_STR);
		$stmt->execute();
		$rows = $stmt->rowCount();
		$stmt->closeCursor();

		if($rows > 0) {
			return true;
		} else {
			return false;
		}
	}
}