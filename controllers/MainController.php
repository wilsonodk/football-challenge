<?php

class MainController extends FootballChallengeController
{
	static function home() {
		// Get standings
		$standings = self::get_standings();
	
		return self::template('main/home.html.twig', array(
			'page_name' => 'Current Standings',
			'standings' => $standings,
		));
	}

	static function week_user($week_num, $username) {
		// Do week stuff
		$db = option('db_con');
		
		// Get user info
		$logged_user = option('user_info');
		$user_info = self::getUserInfoFromName($username);
		$challenge_active = FALSE;
		$show_form = FALSE;
		$challenge_week = option('challenge_week');
		
		if (is_numeric($week_num) && $week_num <= $challenge_week) {
			// First determine if challenge is active, default is no
			$challenge_active = FALSE;
			if ($week_num == $challenge_week) {
				// Maybe
				$now = time();
				if ($result = $db->query('SELECT DISTINCT week FROM {{challenges}} WHERE closetime > %s AND year = %s', $now, FC_YEAR)) {
					while ($obj = $result->fetch_object()) {
						$challenge_active = TRUE;
						$show_form = TRUE;
					}
				}
			}
			
			// There are some scenarios where we don't want to show the form
			if ($logged_user['use'] === FALSE) {
				// No one is logged in, definitely don't show the form
				$show_form = FALSE;
			}
			elseif (strtolower($user_info['name']) !== strtolower($logged_user['name'])) {
				// The userpage and logged in user don't match
				$show_form = FALSE;
			}
			
			// Get the challenge info
			$challenge_info = array();
			$challenge_query = 'SELECT c.cid, c.hsid AS home_sid, hs.school AS home_school, hs.conference AS home_conf, c.vsid AS away_sid, 
				vs.school AS away_school, vs.conference AS away_conf, c.closetime, c.wsid AS winner_sid, c.gametime
				FROM {{challenges}} c, {{schools}} hs, {{schools}} vs 
				WHERE c.hsid = hs.sid AND c.vsid = vs.sid AND c.week = %d AND c.year = %d';
				
			if ($results = $db->query($challenge_query, $week_num, FC_YEAR)) {
				while ($obj = $results->fetch_object()) {
					$temp = $obj;
					$temp->gametime_formatted = date('D, M j \a\t g:i A T', $obj->gametime);
					$temp->closetime_formatted = str_replace(' America/New_York', '', date('l, F j \a\t g:i A T e', $obj->closetime));
					$temp->active = $challenge_active;
					$challenge_info[] = $temp;
				}
			}
			
			// Get the user info
			$user_challenge_info = array();
			$user_data = new StdClass;
			$user_data->challenges = array();
			if ($user_info['use']) {
				$user_data_query = 'SELECT subvalue FROM {{submissions}} WHERE week = %s AND year = %s AND uid = %s';
				if ($result = $db->query($user_data_query, $week_num, FC_YEAR, $user_info['uid'])) {
					while ($obj = $result->fetch_object()) {
						// Deserialize 
						$user_data = unserialize($obj->subvalue);
					}
				}
				
				// Challenge objects have a cid and a sid
				foreach ($user_data->challenges as $challenge_obj) {
					$user_challenge_info[] = array('cid' => $challenge_obj->cid, 'sid' => $challenge_obj->sid);
				}
				
			}
		
			return self::template('main/week.html.twig', array(
				'page_name' => "Week $week_num Challenge",
				'challenge_active' => $challenge_active,
				'challenge_info' => $challenge_info,
				'user_challenge_info' => $user_challenge_info,
				'week_num' => $week_num,
				'show_form' => $show_form,
				'user_active' => $user_info['use'],
			));
		}
		else {
			halt(NOT_FOUND);
		}
	}

	static function week($week_num) {
		$user_info = option('user_info');
		
		return self::week_user($week_num, $user_info['name']);
	}

	static function week_add($week_num) {
		$db = option('db_con');
		$log = option('log');
		$user_info = option('user_info');
		$submission = new StdClass;
		
		if ($user_info['use']) {
			$submission->user = $user_info['name'];
			$submission->week = $week_num;
			$submission->challenges = array();
			
			foreach ($_POST as $key => $value) {
				if (strpos($key, 'challenge-') !== FALSE) {
					$cid = explode('-', $key);
					$cid = $cid[1];
					$temp = new StdClass;
					$temp->cid = $cid;
					$temp->sid = $value;
					array_push($submission->challenges, $temp);
				}
			}
			
			// Key: name-year-week
			$submission_key = sprintf('%s-%s-%s', $user_info['name'], FC_YEAR, $week_num);
			$submission = $db->escape_string(serialize($submission));
			
			// New or update?
			$db
				// Let's check
				->setQuery(
					'check-submission',
					'SELECT subkey FROM {{submissions}} WHERE subkey = "%s"',
					$submission_key
				)
				// New: subkey, name, uid, week, year, subvalue
				->setQuery(
					'new-submission',
					'INSERT INTO {{submissions}} VALUES ("%s", "%s", %s, "%s", "%s", "%s")',
					$submission_key, 
					$user_info['name'], 
					$user_info['uid'], 
					$week_num, 
					FC_YEAR, 
					$submission
				)
				// Update
				->setQuery(
					'update-submission',
					'UPDATE {{submissions}} SET subvalue = "%s" WHERE subkey = "%s"',
					$submission,
					$submission_key
				);
			
			if ($result = $db->useQuery('check-submission')) {
				if ($result->num_rows == 0) {
					// New
					if ($response = $db->useQuery('new-submission')) {
						flash('message', 'Your submission has been saved.');
					}
					else {
						$log->log('error', 'Error with new submission.', $db->error);
						flash('error:new submission', 'There was an error while processing your submission.');
						flash('error:try again', 'Please try again later.');
					}
				}
				elseif ($result->num_rows == 1) {
					// Update
					if ($response = $db->useQuery('update-submission')) {
						flash('message', 'Your submission has been updated.');
					}
					else {
						$log->log('error', 'Error with update submission.', $db->error);
						flash('error:update submission', 'There was an error while processing your submission.');
						flash('error:try again', 'Please try again later.');
					}
				}
				else {
					$log->log('error', 'Error with checking on submission.', $db->error);
				}
			}
			else {
				$log->log('error', $db->getQuery('check-submission'), $db->error);
			}
		}
		else {
			flash('error:no user', 'Please login.');
		}
		
		redirect_to(self::getReferrer());
	}

	static function picks($user) {
		$user_info = self::getUserInfoFromName($user);
	
		if ($user_info['name'] && $user_info['uid']) {
			$db = option('db_con');
			$log = option('log');
			
			$db
			->setQuery(
				'challenge',
				'SELECT c.cid, c.week, c.hsid AS home_sid, hs.school AS home_school, 
				hs.conference AS home_conf, c.vsid AS away_sid, vs.school AS away_school, vs.conference AS away_conf, c.wsid AS winner_sid 
				FROM {{challenges}} c, {{schools}} hs, {{schools}} vs WHERE c.hsid = hs.sid AND c.vsid = vs.sid AND c.year = %s',
				FC_YEAR
			)
			->setQuery(
				'user',
				'SELECT week, subvalue FROM {{submissions}} WHERE uid = %s',
				$user_info['uid']
			);
			
			// Get all of our challenges
			$challenges = array();
			if ($results = $db->useQuery('challenge')) {
				while ($obj = $results->fetch_object()) {
					$challenges[$obj->week][] = $obj;
				}
			}
			else {
				$log->log('error', 'Issue with challenge query while getting picks.', $db->error);
				flash('error:picks challenge', 'Error fetching data.');
			}
			
			// Get the user's submissions
			$user_challenge_info = array();
			if ($results = $db->useQuery('user')) {
				while ($obj = $results->fetch_object()) {
					$user_challenge_info[$obj->week] = unserialize($obj->subvalue);
				}
			}
			else {
				$log->log('error', 'Issue with user query while getting submissions.', $db->error);
				flash('error:picks submissions', 'Error fetching data.');
			}
		
			return self::template('main/picks.html.twig', array(
				'title' => sprintf('%s\'s Picks', strtoupper($user_info['name'])),
				'challenges' => array_reverse($challenges),
				'user_subs' => $user_challenge_info,
			));
		}
		else {
			halt(NOT_FOUND);
		}
	}
	
	/**************************************************************************/
	/* HELPERS                                                                */
	/**************************************************************************/
	
	static function get_standings() {
		$db	= option('db_con');
		$i = 1;
		$score = 0;
		$week = option('challenge_week');
		$year = FC_YEAR;
		$arr = array();
		
		if ($results = $db->query('SELECT username, wins, loses FROM {{users}} ORDER BY wins DESC, loses DESC, username')) {
			while ($obj = $results->fetch_object()) {
				$temp = new StdClass();
				$temp->name	 = $obj->username;
				$temp->wins	 = $obj->wins;
				$temp->loses = $obj->loses;
				$temp->ready = FALSE;
				$temp->place = '';
				$temp->path	 = "/week/$week/".strtolower($obj->username);
				if (($temp->wins + $temp->loses) > 0) {
					$temp->per = round(($temp->wins / ($temp->wins + $temp->loses)) * 100, 2);
				} else {
					$temp->per = 0;
				}

				// Set proper place
				if ($temp->wins !== $score) {
					$score = $temp->wins;
					$temp->place = $i;
				}
				
				// Get ready state
				if ($result = $db->query('SELECT subkey FROM {{submissions}} WHERE name = "%s" AND week = "%s" AND year = "%s"', $temp->name, $week, $year)) {
					while ($item = $result->fetch_object()) {
						$temp->ready = $item->subkey ? TRUE : FALSE;
					}
				}
				
				$i++;
				$arr[] = $temp;
			}
		}
		else {
			die($db->error);
		}
		return $arr;
	}
}

?>
