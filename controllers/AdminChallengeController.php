<?php
require_once('AdminController.php');
class AdminChallengeController extends AdminController
{
	static function challenge() {
		if (self::checkPerms()) {
			$db = option('db_con');
			
			$result = $db->query('SELECT subvalue FROM {{submissions}} WHERE subkey = "WISEASS-2012-8"');
			$obj = $result->fetch_object();
			$sub = unserialize($obj->subvalue);
			
			echo '<pre>';
			print_r($sub);
			echo '</pre>';
		}
		else {
			halt(FORBIDDEN);
		}
	}
	
	static function challenge_add() {
		if (self::checkPerms()) {
			// When adding a new challenge, give every user a submission object where the sid is set to zero.
			// This will have a few great effects; 
			// first, it will make the homepage links work always for picks
			// second, it will make the new standings work, without error protection from missing submissions
			// third, it will mean that there is never a missing submission
			
			/*
			$db = option('db_con');
			$log = option('log');
			$challenge_week = option('challenge_week');
			
			$queries = array();
			$challenges = array();
			
			if ($results = $db->query('SELECT cid FROM {{challenges}} WHERE year = %s AND week = %s', FC_YEAR, $challenge_week)) {
				while ($obj = $results->fetch_object()) {
					$temp = new StdClass;
					$temp->cid = $obj->cid;
					$temp->sid = 0;
					$challenges[] = $temp;
				}
			}
			else {
				$log->log('error', 'Trying to select challenges for creating empty user submissions.', $db->error);
				flash('error:challenges', 'Trying to select challenges for creating empty user submissions.');
			}
			if ($results = $db->query('SELECT uid, username FROM {{users}}')) {
				while ($obj = $results->fetch_object()) {
					$challenge = new StdClass;
					$challenge->user = $obj->username;
					$challenge->week = $challenge_week;
					$challenge->challenges = $challenges;
					$challenge = serialize($challenge);
					$key = sprintf('%s-%s-%s', $obj->username, FC_YEAR, $challenge_week);
					// "subkey", "name", uid, "week", "year", "subvalue"
					$queries[] = $db->formatQuery(
						'INSERT INTO {{submissions}} VALUES ("%s", "%s", %s, "%s", "%s", "%s")',
						$key,
						$obj->username,
						$obj->uid,
						$challenge_week,
						FC_YEAR,
						$db->escape_string($challenge)
					);
				}
				$count = count($queries);
				$queries = implode(';', $queries);
				if ($db->multi_query($queries)) {
					flash('message:empty submissions', 'Created empty submissions for all users.');
				}
				else {
					$log->log('error', 'Trying to insert multiple queries.', $db->error, $queries, "Count: $count");
					flash('error:multi', 'Trying to insert multiple queries.');
				}
			}
			else {
				$log->log('error', 'Trying to select users for creating empty user submissions.', $db->error);
				flash('error:user select', 'Trying to select users for creating empty user submissions.');
			}
			*/
			
			return 'Coming soon (challenge_add)';
		}
		else {
			halt(FORBIDDEN);
		}
	}
	
	static function challenge_show_edit() {
		if (self::checkPerms()) {
			echo '<pre>';
			print_r(self::fetch_data());
			echo '</pre>';
			return 'Coming soon (challenge_show_edit)';
		}
		else {
			halt(FORBIDDEN);
		}
	}
	
	static function challenge_do_edit() {
		if (self::checkPerms()) {
			return 'Coming soon (challenge_do_edit)';
		}
		else {
			halt(FORBIDDEN);
		}
	}
	
	static function check() {
		$db = option('db_con');
		$log = option('log');
		$week = option('challenge_week');
		$now = time();
		$content = array();
		$start = 0;
		$str = '<strong>%s</strong> %s';
		
		// Get the challenges ordered by start time that have not had their winner set
		if ($result = $db->query('SELECT cid, gid, hsid, vsid, gametime FROM {{challenges}} WHERE week = %d AND year = %d AND wsid = 0 ORDER BY gametime, cid', $week, FC_YEAR)) {
			$challenges = array();
			while ($obj = $result->fetch_object()) {
				if ($start === 0) {
					$start = $obj->gametime;
				}
				$challenges[$obj->gid] = $obj;
			}
			
			// Is it currently later than the start time for the earliest game?
			if (count($challenges) === 0) {
				$content[] = 'All challenges have a winner set.';
			}
			elseif ($now > $start) {
				// Winner query
				$winner = 'UPDATE {{challenges}} SET wsid = %s WHERE cid = %s';
				// Collect all of our queries for a multi call later
				$queries = array();
				$ncaa = self::fetch_data($week);
				
				$content[] = sprintf('<a href="%s">%s</a>', $ncaa->filename, $ncaa->filename);
				foreach ($ncaa->scoreboard as $day) {
					foreach ($day->games as $game) {
						// Get the particular challenge
						if (array_key_exists($game->id, $challenges) && $challenge = $challenges[$game->id]) {
							$content[] = '&mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash;';
							$content[] = sprintf($str, 'Challenge Id:', $challenge->cid);
							$content[] = sprintf($str, 'Game Id:', $challenge->gid);
							$content[] = sprintf($str, 'Game time:', date('r', $challenge->gametime));
							if ($game->gameState == 'final') {
								$content[] = 'Game is over. Determine winner.';
								if ($game->home->winner == 'true') {
									$content[] = 'Home team won.';
									$queries[] = $db->formatQuery($winner, $challenge->hsid, $challenge->cid);
								}
								elseif ($game->away->winner == 'true') {
									$content[] = 'Away team won.';
									$queries[] = $db->formatQuery($winner, $challenge->vsid, $challenge->cid);
								}
								else {
									$content[] = 'No winner information is set.';
								}
							}
							else {
								$content[] = 'Game has not reached final state.';
							}
						}
					}
				}
				
				$count = count($queries);
				if ($count > 0) {
					$query = implode(';', $queries);
					if ($db->multi_query($queries)) {
						flash('message:check', 'Updated challenges with winner information.');
					}
					else {
						$log->log('error', 'Trying to insert multiple queries.', $db->error, $query, "Count: $count");
						flash('error:multi check', 'Trying to insert multiple queries.');
					}					
				}
				else {
					$log->log('message', sprintf('No queries collected for check games at %s.', date('r', $now)));
				}
			}
			else {
				$content[] = 'It\'s not time yet.';
			}
			
			// All decent output should have the current time and the first game time
			$content[] = '&mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash; &mdash;';
			$content[] = sprintf($str, 'Current time:', date('r', $now));
			$content[] = sprintf($str, 'Start time:', date('r', $start));
		}
		else {
			$log->log('error', 'Could not get challenges for game check.', $db->error);
		}
		
		return self::template('admin/check.html.twig', array(
			'content' => $content,
		));
	}
	
	/*
	 * Helpers
	 */
	
	static function fetch_data($week = FALSE) {
		// http://data.ncaa.com/jsonp/scoreboard/football/fbs/2011/03/scoreboard.html
		$filename = 'http://data.ncaa.com/jsonp/scoreboard/football/fbs/%d/%s/scoreboard.html';
		if (!$week) {
			$week = option('challenge_week');
		}
		$week = $week < 10 ? '0'. $week : $week . '';
		$filename = sprintf($filename, FC_YEAR, $week);
		$ncaa = file_get_contents($filename);
		$ncaa = str_replace(array('callbackWrapper(', ');'), array('', ''), $ncaa);
		$ncaa = json_decode($ncaa);
		$ncaa->filename = $filename;
		return $ncaa;
	}
}
?>