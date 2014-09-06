<?php

class AdminController extends AppController
{
    static function checkPerms() {
        $active_user = option('user_info');
        $user_info     = self::getUserInfoFromUid($active_user['uid']);
        return $user_info['perms'] === '2';
    }

    static function home() {
        if (self::checkPerms()) {
            $db = option('db');
            $log = option('log');
            $week = option('challenge_week');

            $state = 0;
            $query = 'SELECT COUNT(*) as count FROM {{challenges}} WHERE winner_sid <> 0 AND week = %d AND year = %d';
            if ($result = $db->qry($query, $week, FC_YEAR)) {
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
                'page_name' => 'Commissioner',
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
                'page_name' => 'Log',
                'log_data' => $log->read(TRUE, TRUE),
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function standings() {
        $db = option('db');
        $log = option('log');

        $users = array();
        $winners = array();

        $num_weeks = option('standings_week');

        $get_users   = 'SELECT uid, username FROM {{users}}';
        $get_winners = 'SELECT cid, winner_sid FROM {{challenges}} WHERE year = %s AND week = %s';
        $get_subs    = 'SELECT subvalue FROM {{submissions}} WHERE subkey = "%s"';
        $set_users   = 'UPDATE {{users}} SET wins = %s, loses = %s, submission = 0 WHERE uid = %s';

        /* select all users
         * go through each week,
         *  get the winner_sid
         *  get the users' submissions for that week
         * compare the users select to the winner_sid
         *  tally wins/losses
         * update the user
         */

        // Get all users and create user object for each, resetting wins/losses/totals
        if ($result = $db->qry($get_users)){
            while ($obj = $result->fetch_object()) {
                $users[$obj->username] = array(
                    'wins'   => 0,
                    'losses' => 0,
                    'total'  => 0,
                    'uid'    => $obj->uid
                );
            }
        }
        else {
            $log->log('error', 'Could not get users for standings.', $db->error);
            flash('error:users', 'Could not get users for standings.');
        }

        // Go through all weeks and get cid and winner_sid
        for ($week = 1; $week <= $num_weeks; $week++) {
            $weekly_winners = array();
            if ($winners_result = $db->qry($get_winners, FC_YEAR, $week)) {
                // cid, winner_sid
                while ($obj = $winners_result->fetch_object()) {
                    $winners[$obj->cid] = array('cid' => $obj->cid, 'sid' => $obj->winner_sid);
                    $weekly_winners[$obj->cid] = array('cid' => $obj->cid, 'sid' => $obj->winner_sid);
                }
            }
            else {
                $log->log('error', 'Could not get winner data.', $db->error);
                flash('error:winners', 'Could not get winner\'s data.');
            }

            // Get the users' submissions for this $week
            foreach ($users as $user => $results) {
                $key = sprintf('%s-%s-%s', $user, FC_YEAR, $week);
                if ($user_result = $db->qry($get_subs, $key)) {
                    while ($obj = $user_result->fetch_object()) {
                        $submission = unserialize($obj->subvalue);
                    }

                    $challenges = self::find_missing_challenges($submission->challenges, $weekly_winners);
                    $wins = 0;
                    $losses = 0;

                    foreach ($challenges as $index => $challenge) {
                        $w = isset($winners[$challenge->cid]['sid']) ? $winners[$challenge->cid]['sid'] : -1;
                        if (isset($winners[$challenge->cid]['sid']) && $challenge->sid == $winners[$challenge->cid]['sid']) {
                            //$log->log('message', "{$user} winner: {$y}-{$week} = {$challenge->cid}: winner({$w}) picked($challenge->sid)\n");
                            $wins++;
                        }
                        else {
                            //$log->log('message', "{$user} loss: {$y}-{$week} = {$challenge->cid}: winner({$w}) picked($challenge->sid)\n");
                            $losses++;
                        }
                    }

                    $users[$user]['wins'] += $wins;
                    $users[$user]['losses'] += $losses;
                    $users[$user]['total'] += ($wins + $losses);
                }
                else {
                    $log->log('error', 'Could not get user submission data.', $db->error);
                    flash('error:submissions', 'Could not get user submission data.');
                }
            }
        }

        // Have all the user data
        foreach ($users as $name => $data) {
            if ($db->qry($set_users, $data['wins'], $data['losses'], $data['uid'])) {
                $log->log('message', "Updated {$name}'s standings; wins({$data['wins']}) losses({$data['losses']}).");
            }
            else {
                $log->log('error', 'Could not update the user data.', $db->error);
                flash('error:updates', 'Could not update the user data.');
            }
        }

        // Finished
        flash('message:done', 'Finished with standings.');
        redirect_to(self::getReferrer());
    }

    static function find_missing_challenges($arr, $winners) {
        if (count($arr) === FC_NUM_CHALLENGES) {
            return $arr;
        } else {
            // Get only the challenge ideas that the user has
            $temp = array();
            foreach ($arr as $obj) {
                $temp[] = $obj->cid;
            }

            // Compare what they have to what they shoulc have
            foreach ($winners as $key => $winner) {
                if (! in_array($key, $temp)) {
                    // If it's not in TEMP, put it there with a bad pick
                    $missing = new StdClass;
                    $missing->cid = $key;
                    $missing->sid = 9999;

                    // Add it to their arr
                    $arr[] = $missing;
                }
            }
            // Return their modified array
            return $arr;
        }
    }

    static function send_reminders() {
        $db   = option('db');
        $log  = option('log');

        $now  = time();
        $day  = (60 * 60 * 24);
        $week = option('challenge_week');

        if ($result = $db->qry('SELECT closetime, reminder_sent FROM {{challenges}} WHERE year = %s AND week = %s LIMIT 1;', FC_YEAR, $week)) {
            while ($obj = $result->fetch_object()) {
                if ($obj->reminder_sent == 0 && $now > ($obj->closetime - $day)) {
                    // No reminder has been sent AND it's within 24 hours of the closetime
                    self::send_emails($week);
                }
                else {
                    echo 'Not time for reminder';
                }
            }
        }
        else {
            $log->log('error', 'Error getting close time for challenge.', $db->error);
        }
    }

    static function send_emails($week) {
        $db = option('db');
        $log = option('log');

        $rn = "\r\n";

        $base = sprintf('http://%s%s', $_SERVER['HTTP_HOST'], WODK_BASE_URI);
        $url  = sprintf('%sweek/%s', $base, $week);
        $acct = sprintf('%smy-account', $base);

        $phpv = phpversion();
        $appv = trim(option('app_version'));

        $commissioners = array();

        $site_name = SITE_NAME;

        $env = self::getEnv();

        // Get commissioners
        if ($result = $db->qry('SELECT username, email FROM {{users}} WHERE permissions = 2')) {
            while ($obj = $result->fetch_object()) {
                $commissioners[] = "{$obj->username} <{$obj->email}>";
            }

            $commissioners = implode(', ', $commissioners);

            // Send the email to users without a submission, that want reminders and have an email
            if ($result = $db->qry('SELECT username, email FROM {{users}} WHERE submission = 0 AND reminder = 1 AND email != ""')) {
                while ($obj = $result->fetch_object()) {
                    $to      = "{$obj->username} <{$obj->email}>";
                    $subject = "{$site_name} Reminder Week {$week}";
                    $message = "{$obj->username},{$rn}Time is running out to enter your picks!{$rn}{$rn}{$url}{$rn}{$rn}{$rn}{$rn}To change your email reminder settings, go to...{$rn}{$acct}{$rn}";
                    $headers = "From: {$commissioners}{$rn}Reply-To: {$commissioners}{$rn}X-Mailer: Football Challenge/{$appv} PHP/{$phpv}";
                    if ($env === ENV_PRODUCTION) {
                        $log->log('message', sprintf('Reminder email sent to %s', htmlspecialchars($to)));
                        mail($to, $subject, $message, $headers);
                    }
                    elseif ($env === ENV_DEVELOPMENT) {
                        echo htmlspecialchars("{$headers}{$rn}{$rn}To: {$to}{$rn}{$rn}{$subject}{$rn}{$rn}$message{$rn}- - -{$rn}");
                    }
                    else {
                        $log->log('error', sprintf('Unmatched environment variable env(%s):%s', $env, gettype($env)));
                    }
                }
            }
            else {
                $log->log('error', 'Error getting all the users to email.', $db->error);
            }

            if ($env === ENV_PRODUCTION) {
                // We've sent our reminder, let's update the challenge
                if (! $db->qry('UPDATE {{challenges}} SET reminder_sent = 1 WHERE year = %s AND week = %s', FC_YEAR, $week)) {
                    $log->log('error', 'Error updating the challenges.', $db->error);
                }
            }
        }
        else {
            $log->log('error', 'Error getting all the commissioners.', $db->error);
        }
    }
}

?>
