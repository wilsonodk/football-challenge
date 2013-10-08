<?php

class AdminChallengeController extends AdminController
{
    static function challenge() {
        if (self::checkPerms()) {
            $db = option('db');
            $current_week = option('challenge_week') + 1;

            $ncaa = self::fetch_data($current_week);

            return self::template('admin/challenge.html.twig', array(
                'page_name'      => 'Challenge',
                'num_challenges' => FC_NUM_CHALLENGES,
                'scoreboard'     => $ncaa->scoreboard,
                'current_week'   => $current_week,
                'task_type'      => 'create',
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function challenge_add() {
        if (self::checkPerms()) {
            // Let's create our new challenge
            $db = option('db');
            $log = option('log');
            $current_week = $_POST['current-week'];
            $insert = 'INSERT INTO {{challenges}} VALUES (NULL, "%s", %d, %d, 0, %d, %d, %d, %d, %d, %d)';
            $user_challenges = array();

            list($challenges, $first_start) = self::process_challenges($_POST, $db, $log);

            $queries = array();
            foreach ($challenges as $value) {
                $now = time();
                $queries[] = $db->formatQuery(
                    $insert,
                    $value['gid'],
                    $value['home_id'],
                    $value['away_id'],
                    $current_week,
                    FC_YEAR,
                    $first_start,
                    $value['date'],
                    $now,
                    $now
                );
                $temp = new StdClass;
                $temp->cid = $db->insert_id;
                $temp->sid = 0;
                $user_challenges[] = $temp;
            }

            // When adding a new challenge, give every user a submission object where the sid is set to zero.
            // This will have a few great effects;
            // first, it will make the homepage links work always for picks
            // second, it will make the new standings work, without error protection from missing submissions
            // third, it will mean that there is never a missing submission
            $users = option('all_users');
            foreach ($users as $user) {
                $challenge = new StdClass;
                $challenge->user = $user->username;
                $challenge->week = $current_week;
                $challenge->challenges = $user_challenges;
                $challenge = serialize($challenge);
                $key = sprintf('%s-%s-%s', $user->username, FC_YEAR, $current_week);
                $queries[] = $db->formatQuery(
                    'INSERT INTO {{submissions}} VALUES ("%s", "%s", %s, "%s", "%s", "%s")',
                    $key,
                    $user->username,
                    $user->uid,
                    $current_week,
                    FC_YEAR,
                    $db->escape_string($challenge)
                );
                $queries[] = $db->formatQuery('UPDATE {{users}} SET submission = 0 WHERE uid = %s', $user->uid);
            }

            // When creating a challenge, check to see if we should truncate the messages database
            if (FC_MSGS_TRUNCATE) {
                $truncate_week = $current_week - FC_MSGS_TRUNCATE;
                $queries[] = $db->formatQuery('UPDATE {{messages}} SET active = 0 WHERE week <= %s', $truncate_week);
                $log->log('message', sprintf('Truncating messages for week %s', $truncate_week));
            }

            $count = count($queries);
            $i = 1;
            if ($count > 0) {
                foreach ($queries as $query) {
                    if ($result = $db->qry($query)) {
                        $log->log('message', sprintf('Creating challenges, query %s of %s', $i++, $count));
                    }
                    else {
                        $log->log('error', 'Problem while creating challenge', $db->error);
                    }
                }
            }
            else {
                flash('error:create challenge count', 'No queries for creating a challenge.');
                $log->log('error', 'No queries for creating a challenge.');
            }

            redirect_to('/commissioner');
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function challenge_show_edit() {
        if (self::checkPerms()) {
            $db = option('db');
            $current_week = option('challenge_week');

            $ncaa = self::fetch_data($current_week);

            $challenges = array();
            if ($result = $db->qry('SELECT cid, gid, home_sid, away_sid FROM {{challenges}} WHERE year = %s AND week = %s ORDER BY cid', FC_YEAR, $current_week)) {
                while ($obj = $result->fetch_object()) {
                    $challenges[] = $obj;
                }
            }
            else {
                $log->log('error', 'Could not select challenges.', $db->error);
                flash('error:challenge select', 'Could not select challenges.');
            }

            return self::template('admin/challenge.html.twig', array(
                'page_name'      => 'Challenge',
                'challenges'     => $challenges,
                'num_challenges' => FC_NUM_CHALLENGES,
                'scoreboard'     => $ncaa->scoreboard,
                'current_week'   => $current_week,
                'task_type'      => 'edit',
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function challenge_do_edit() {
        if (self::checkPerms()) {
            $db = option('db');
            $log = option('log');
            $update = 'UPDATE {{challenges}} SET gid = "%s", home_sid = %d, away_sid = %d, gametime = %d, closetime = %d, last_modified = %d WHERE cid = %d';

            list($challenges, $first_start) = self::process_challenges($_POST, $db, $log);

            $queries = array();
            foreach ($challenges as $value) {
                $queries[] = $db->formatQuery(
                    $update,
                    $value['gid'],
                    $value['home_id'],
                    $value['away_id'],
                    $value['date'],
                    $first_start,
                    time(),
                    $value['cid']
                );
            }

            $count = count($queries);
            if ($count > 0) {
                foreach ($queries as $query) {
                    if ($db->qry($query)) {
                        flash('message:challenges updated', 'Updated challenges.');
                        $log->log('message', sprintf('Updated challenge: %s', $query));
                    } else {
                        flash('error:challenges updated', 'Unable to update challenges.');
                        $log->log('error', 'Unable to update challenges', $db->error, $query);
                    }
                }
            }

            redirect_to('/commissioner');
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function challenge_do_delete() {
        if (self::checkPerms()) {
            $db     = option('db');
            $log     = option('log');
            $week     = option('challenge_week');
            $year     = FC_YEAR;
            $delete = 'DELETE FROM {{challenges}} WHERE year = %s AND week = %s';
            $subdel = 'DELETE FROM {{submissions}} WHERE year = %s AND week = %s';

            if ($db->qry($delete, $year, $week)) {
                flash('message:challenge delete', "Challenge for Week $week was deleted");
                $log->log('message', "Challenge for Week $week was deleted");
            }
            else {
                flash('error:challenge delete', 'Problem while deleting challenge');
                $log->log('error', 'Problem while deleting challenge', $db->error);
            }

            if ($db->qry($subdel, $year, $week)) {
                flash('message:submissions delete', "Submissions for Week $week were deleted");
                $log->log('message', "Submissions for Week $week were deleted");
            }
            else {
                flash('error:submissions delete', 'Problem while deleting submissions');
                $log->log('error', 'Problem while deleting submissions', $db->error);
            }

            redirect_to('/commissioner');
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function check() {
        $db = option('db');
        $log = option('log');
        $week = option('challenge_week');
        $now = time();
        $content = array();
        $start = 0;
        $str = '<strong>%s</strong> %s';

        // Get the challenges ordered by start time that have not had their winner set
        if ($result = $db->qry('SELECT cid, gid, home_sid, away_sid, gametime FROM {{challenges}} WHERE week = %d AND year = %d AND winner_sid = 0 ORDER BY gametime, cid', $week, FC_YEAR)) {
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
                $winner = 'UPDATE {{challenges}} SET winner_sid = %s WHERE cid = %s';
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
                                    $queries[] = $db->formatQuery($winner, $challenge->home_sid, $challenge->cid);
                                }
                                elseif ($game->away->winner == 'true') {
                                    $content[] = 'Away team won.';
                                    $queries[] = $db->formatQuery($winner, $challenge->away_sid, $challenge->cid);
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
                    foreach ($queries as $query) {
                        $log->log('message', sprintf('Challenge Check: Using query: %s', $query));
                        $db->query($query);
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
            'page_name' => 'Check Challenges',
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

    static function build_week_dropdown($ncaa, $cid = FALSE, $gid = FALSE) {
        $game = array(
            'cid'  => $cid,
            'gid'  => $gid,
            'home' => NULL,
            'away' => NULL,
        );
    }

    static function get_full_name($name) {
        $abbr = array('Mich.',    'Cent.',   'St.',   'La.',       'Ill.',     'Fla.',    'Southern California', 'BYU',           'NC State',             'Tenn.',);
        $full = array('Michigan', 'Central', 'State', 'Louisiana', 'Illinois', 'Florida', 'USC',                 'Brigham Young', 'North Carolina State', 'Tennessee',);
        return str_replace($abbr, $full, $name);
    }

    static function get_school_id($name, $db, $log) {
        $name = html_entity_decode($name);

        if ($result = $db->qry('SELECT sid FROM {{schools}} WHERE school LIKE "%s"', $name)) {
            while ($obj = $result->fetch_object()) {
                if ($obj->sid) {
                    $log->log('message', "School '$name' has an id = '$obj->sid'");
                    return $obj->sid;
                } else {
                    flash('errror:school id', "Could not find school '$name'");
                    $log->log('error', "Could not find school id with name = '$name'");
                    return -1;
                }
            }
        }
        else {
            flash('errror:school idx', "Error with query to find school '$name'");
            $log->log('error', "Could not find school id with name = '$name'");
            return -1;
        }
    }

    static function my_strtotime($time) {
        // Incoming format: 2011-09-24 3:30PM ET
        // Incoming format: 2012-09-22 12:00 PM ET
        $pieces = explode(' ', $time);
        $count = count($pieces);
        if (strpos($pieces[1], 'TBA') !== FALSE) {
            // Sometimes, $time looks like `2011-09-24 TBA`
            $pieces[1] = '12:00PM';
        }
        if ($count == 4) {
            $pieces[1] = $pieces[1] . $pieces[2];
        }
        $str = $pieces[0] .' '. $pieces[1];
        return strtotime($str);
    }

    static function process_challenges($post, $db, $log) {
        $first_start = 4102462800;
        $challenges = array();
        foreach($post['challenge'] as $value) {
            $temp = explode('|', $value);
            $arr = array();
            $arr['gid']     = $temp[0];
            $arr['away']    = self::get_full_name($temp[1]);
            $arr['away_id'] = self::get_school_id($arr['away'], $db, $log);
            $arr['home']    = self::get_full_name($temp[2]);
            $arr['home_id'] = self::get_school_id($arr['home'], $db, $log);
            $arr['date']    = self::my_strtotime($temp[3]);
            $arr['cid']     = isset($temp[4]) ? $temp[4] : 0;
            if ($arr['date'] < $first_start) {
                $first_start = $arr['date'];
            }
            $challenges[] = $arr;
        }
        return array($challenges, $first_start);
    }
}
?>
