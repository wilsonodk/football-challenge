<?php
class MessengerController extends AppController
{
	static function show() {
		return self::getMessages();
	}
	
	static function create() {
		$db = option('db');
		$log = option('log');
		// mid, pid, uid, message (blob), posted, active, week (challenge week)
		$query = 'INSERT INTO {{messages}} VALUES (NULL, %d, %s, "%s", %s, %s, %s)';
		$user_info = option('user_info');
		
		$pid = get_post('pid');
		$msg = get_post('message');
		
		if ($user_info['use'] === TRUE) {
			$pid = $pid === 'main' ? NULL : $pid;
			$msg = $db->escape_string(strip_tags($msg));
			$db->qry(
				$query,
				$pid,
				$user_info['uid'],
				$msg,
				time(),
				1,
				option('challenge_week')
			);
		}
		
		return ''; #self::getMessages();
	}
	
	/* COMING SOON */
	
	static function edit($id) {
		$db = option('db');
		$log = option('log');
		$query = 'UPDATE {{messages}} SET message = "%s" WHERE mid = %s';
		$user_info = option('user_info');
		$username = get_post('username');
		
		if ($user_info['use'] && $user_info['name'] === $username) {
			$mid = get_post('mid');
			$msg = $db->escape_string(strip_tags(get_post('message')));
			
			if (!$db->qry($query, $msg, $mid)) {
				$log->log('error', "Attempt to update message $mid failed", $db->error);
			}
			
			return json_encode($GLOBALS['_PUT']);
		}
	}
	
	
	
	static function delete($id) {
		return 'Coming soon';
	}
	
	/* Helpers */
	
	static function getMessages() {
		$db = option('db');
		$log = option('log');
		$messages = array();
		$query = 'SELECT m.mid AS mid, m.pid AS pid, m.message AS message, m.posted AS timestamp, u.username AS username, m.uid AS uid FROM {{messages}} m LEFT JOIN {{users}} u ON m.uid = u.uid WHERE m.active = 1 ORDER BY m.mid, m.pid, m.posted DESC';
		
		if ($results = $db->qry($query)) {
			while ($obj = $results->fetch_object()) {
				$obj->timestamp = $obj->timestamp * 1000;
				$messages[] = $obj;
			}
		}
		else {
			$log->log('error', 'Could not get messeges.', $db->error);	
		}
		
		$all = array();
		foreach ($messages as $msg) {
			// Make sure everyone has an replies array
			if (!isset($msg->replies)) {
				$msg->replies = array();
			}
			// Check for parent
			if ($msg->pid) {
				// Found a message with a parent, find it's parent in the messages
				foreach ($messages as $msg2) {
					if ($msg2->mid === $msg->pid) {
						if (!isset($msg2->replies)) {
							$msg2->replies = array();
						}
						// Found parent, push on the message						
						$msg2->replies[] = $msg;
					}
				}
			}
			else {
				$all[] = $msg;
			}
		}
		
		header('Content-type: application/json');
		return json_encode($all);
	}
}
?>