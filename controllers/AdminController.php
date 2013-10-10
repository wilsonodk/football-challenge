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
        if ($result = $db->qry('SELECT uid, username FROM {{users}}')) {
            while ($obj = $result->fetch_object()) {
                $users[$obj->username] = array('wins' => 0, 'losses' => 0, 'total' => 0, 'uid' => $obj->uid);
            }

            $num_weeks = option('standings_week');
            for ($week = 1; $week <= $num_weeks; $week++) {
                $db->setQuery(
                    'winners',
                    'SELECT cid, winner_sid FROM {{challenges}} WHERE year = %s AND week = %s',
                    FC_YEAR,
                    $week
                );
                if ($winners_result = $db->useQuery('winners')) {
                    while ($obj = $winners_result->fetch_object()) {
                        $winners[$obj->cid] = array('cid' => $obj->cid, 'sid' => $obj->winner_sid);
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

    static function send_reminders() {
        $rn    = "\r\n";
        $db    = option('db');
        $log   = option('log');
        $week  = option('challenge_week');
        $query = 'SELECT username, email FROM {{users}} WHERE submission = 0 AND reminder = 1 AND email != ""';
        $url   = sprintf('http://%s%sweek/%s', $_SERVER['HTTP_HOST'], WODK_BASE_URI, $week);
        $acct  = sprintf('http://%s%s/my-account', $_SERVER['HTTP_HOST'], WODK_BASE_URI);
        $phpv  = phpversion();
        $appv  = option('app_version');

        if ($result = $db->qry($query)) {
            while ($obj = $result->fetch_object()) {
                $to = sprintf('%s <%s>', $obj->username, $obj->email);
                $subject = sprintf('Junkies Reminder Week %s', $week);
                $message = "{$obj->username}{$rn}{$rn}Time is running out to enter your picks!{$rn}{$rn}{$url}{$rn}{$rn}To change your email reminder settings, go to...{$rn}{$acct}";
                // TODO: Get commissioner emails for from, reply-to;
                $headers = "From: WISEASS <wilson@odk.com>{$rn}Reply-To: WISEASS <wilson@odk.com>{$rn}X-Mailer: Football Challenge/{$appv} PHP/{$phpv}";

                mail($to, $subject, $message, $headers);
            }
        }
    }
}

?>
