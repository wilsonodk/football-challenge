<?php

require_once('FootballChallengeController.php');

class AdminController extends FootballChallengeController
{
	static function checkPerms() {
		$active_user = option('user_info');
		$user_info	 = self::getUserInfoFromUid($active_user['uid']);
		return $user_info['perms'] === '2';
	}

	static function home() {
		if (self::checkPerms()) {
			$db = option('db_con');
			$log = option('log');
			$week = option('challenge_week');
			
			$state = 0;
			$query = 'SELECT COUNT(*) as count FROM junkies_challenges WHERE wsid <> 0 AND week = %d AND year = %d';
			if ($result = $db->query($query, $week, FC_YEAR)) {
				while ($obj = $result->fetch_object()) {
					if ($week === 0 || $obj->count === '10') {
						$state = 'create';	
					}
					elseif ($obj->count === '0') {
						$state = 'edit';	
					}
					else {
						$state = 'wait';
					}
				}
			}
		
			return self::template('admin/home.html.twig', array(
				'challenge_state' => $state,
			));
		}
		else {
			halt(FORBIDDEN);
		}
	}
	
	static function logs() {
		if (self::checkPerms()) {
			$log = option('log');
			return self::template('admin/logs.html.twig', array(
				'log_data' => $log->read(TRUE, TRUE),
			));
		}
		else {
			halt(FORBIDDEN);
		}
	}
	
	static function standings() {
		$db = option('db_con');
		$log = option('log');
		
		$users = array();
		$winners = array();
		if ($result = $db->query('SELECT uid, username FROM {{users}}')) {
			while ($obj = $result->fetch_object()) {
				$users[$obj->username] = array('wins' => 0, 'losses' => 0, 'total' => 0, 'uid' => $obj->uid);
			}
			
			$num_weeks = option('standings_week');
			for ($week = 1; $week <= $num_weeks; $week++) {
				$db->setQuery(
					'winners',
					'SELECT cid, wsid FROM {{challenges}} WHERE year = %s AND week = %s',
					FC_YEAR,
					$week
				);
				if ($winners_result = $db->useQuery('winners')) {
					while ($obj = $winners_result->fetch_object()) {
						$winners[$obj->cid] = array('cid' => $obj->cid, 'sid' => $obj->wsid);
					}
					
					foreach ($users as $user => $results) {
						$key = sprintf('%s-%s-%s', $user, FC_YEAR, $week);
						$db->setQuery(
							'user',
							'SELECT subvalue FROM {{submissions}} WHERE subkey = "%s"',
							$key
						);
						
						if ($user_result = $db->useQuery('user')) {
							while ($obj = $user_result->fetch_object()) {
								$submission = unserialize($obj->subvalue);
							}
							
							$challenges = $submission->challenges;
							$wins = $losses = 0;
							foreach ($challenges as $index => $challenge) {
								if ($challenge->sid == $winners[$challenge->cid]['sid']) {
									$wins++;
								}
								else {
									$losses++;
								}
							}
							
							$users[$user]['wins'] += $wins;
							$users[$user]['losses'] += $losses;
							$users[$user]['total'] += $wins + $losses;
						}
						else {
							$log->log('error', 'Could not get user submission data.', $db->getQuery('user'), $db->error);
							flash('error:submissions', 'Could not get user submission data.');
						}
					}
				}
				else {
					$log->log('error', 'Could not get winner data.', $db->getQuery('winners'), $db->error);
					flash('error:winners', 'Could not get winner\'s data.');
				}
			}
			
			// Have all the user data
			foreach ($users as $name => $data) {
				$db->setQuery(
					'userUpdate',
					'UPDATE {{users}} SET wins = %s, loses = %s WHERE uid = %s', 
					$data['wins'], 
					$data['losses'], 
					$data['uid']
				);
				if ($db->useQuery('userUpdate')) {
					$log->log('message', "Updated $name's standings.");
				}
				else {
					$log->log('error', 'Could not update the user data.', $db->getQuery('userUpdate'), $db->error);
					flash('error:updates', 'Could not update the user data.');
				}
			}
			flash('message:done', 'Finished with standings.');
		}
		else {
			$log->log('error', 'Could not get users for standings.', $db->error);
			flash('error:users', 'Could not get users for standings.');
		}
		redirect_to(self::getReferrer());
	}
}

?>