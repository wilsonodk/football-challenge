<?php

class AdminPlayerPicksController extends AdminController
{
    static function players() {
        if (self::checkPerms()) {
            $db = option('db');
            $log = option('log');

            $players = array();
            $query = 'SELECT uid, username FROM {{users}} WHERE active = 1 ORDER BY username';

            if ($result = $db->qry($query)) {
                while ($obj = $result->fetch_object()) {
                    $players[] = $obj;
                }
            }

            return self::template('admin/player-picks-list.html.twig', array(
                'page_name' => 'Pick Player to Edit Picks',
                'players' => $players,
                'back_url' => '..',
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_picks($uid=FALSE) {
        if (self::checkPerms()) {
            $db = option('db');
            $log = option('log');

            $username = self::get_username($uid);

            if (!$username) {
                halt(NOT_FOUND);
            }

            // Week[] = {num: 1, status: true|false}
            $week_limit = (int) option('challenge_week');
            $weeks = array();
            for ($i = 1; $i <= $week_limit; $i++) {
                $status = self::has_submission_for_week($username, $i);
                $weeks[] = array(
                    'num' => $i,
                    'status' => $status,
                );
            }

            return self::template('admin/player-picks-list.html.twig', array(
                'page_name' => ': Pick the Week to Edit',
                'username' => $username,
                'weeks' => $weeks,
                'back_url' => option('base_uri') . 'commissioner/players/picks',
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_picks_week($uid=FALSE, $week=FALSE) {
        if (self::checkPerms()) {
            $week = (int) $week;
            $week_limit = (int) option('challenge_week');

            if ($week && $week <= $week_limit) {
                $db = option('db');
                $log = option('log');

                $username = self::get_username($uid);
                $subkey = self::get_subkey($username, $week);

                if (!$username) {
                    halt(NOT_FOUND);
                }

                // Get the submission object
                $submission = NULL;
                $submission_query = 'SELECT subvalue FROM {{submissions}} WHERE subkey = "%s"';
                if ($result = $db->qry($submission_query, $subkey)) {
                    while ($obj = $result->fetch_object()) {
                        $submission = unserialize($obj->subvalue);
                    }
                }
                else {
                    $log->log('error', 'Could not fetch user submission for '. $subkey, $db->error);
                }

                // Challenges
                // {home: {sid, name, selected}, away: {sid, name, selected}, none: {selected}}
                $challenges = array();
                $challenge_query = 'SELECT
                c.cid, c.home_sid AS home_sid, hs.school AS home_school, hs.conference AS home_conf, c.away_sid AS away_sid,
                vs.school AS away_school, vs.conference AS away_conf, c.closetime, c.winner_sid AS winner_sid, c.gametime
                FROM {{challenges}} c, {{schools}} hs, {{schools}} vs
                WHERE c.home_sid = hs.sid AND c.away_sid = vs.sid AND c.year = %d AND c.week = %d';

                if ($result = $db->qry($challenge_query, FC_YEAR, $week)) {
                    while ($obj = $result->fetch_object()) {
                        $challenges[] = $obj;
                    }
                }

                // Sync player submission data to challenges
                foreach ($challenges as $challenge) {
                    if ($submission) {
                        $challenge->user_selection = self::get_user_selection($challenge->cid, $submission->challenges);
                    }
                    else {
                        $challenge->user_selection = FC_DEFAULT_VALUE;
                    }
                }

                return self::template('admin/player-edit-picks.html.twig', array(
                    'page_name' => sprintf('Edit %s\'s Picks', $username),
                    'challenges' => $challenges,
                    'action' => $submission ? 'Update' : 'Create',
                ));
            }
            else {
                halt(NOT_FOUND);
            }
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function save_player_picks_week($uid=FALSE, $week=FALSE) {
        if (self::checkPerms()) {
            $week = (int) $week;
            $week_limit = (int) option('challenge_week');

            if ($week && $week <= $week_limit) {
                $db = option('db');
                $log = option('log');

                $username = self::get_username($uid);
                $subkey = self::get_subkey($username, $week);

                // Get all the challenge id so we can get the post info
                $challenges = array();
                $query = 'SELECT cid FROM {{challenges}} WHERE year = %s AND week = %s';
                if ($result = $db->qry($query, FC_YEAR, $week)) {
                    while ($obj = $result->fetch_object()) {
                        $temp = new StdClass;
                        $temp->cid = $obj->cid;
                        $temp->sid = get_post('challenge-' . $obj->cid);
                        $challenges[] = $temp;
                    }
                }
                else {
                    $log->log('error', 'Unable to get challenges to update player picks.', $db->error);
                }

                $submission = new StdClass;
                $submission->user = $username;
                $submission->week = $week;
                $submission->challenges = $challenges;
                $submission = serialize($submission);

                // Insert or Update?
                $is_create = (get_post('submit') === 'Create Picks');
                if ($is_create)  {
                    // s-subkey, s-name, i-uid, s-week, s-year, s-subvalue
                    $db->setQuery(
                        'submission',
                        'INSERT INTO {{submissions}} VALUES ("%s", "%s", %s, "%s", "%s", "%s")',
                        $db->escape_string($subkey),
                        $db->escape_string($username),
                        $uid,
                        $db->escape_string($week),
                        FC_YEAR,
                        $db->escape_string($submission)
                    );
                }
                else {
                    $db->setQuery(
                        'submission',
                        'UPDATE {{submissions}} SET subvalue = "%s" WHERE subkey = "%s"',
                        $db->escape_string($submission),
                        $db->escape_string($subkey)
                    );
                }

                $action = $is_create ? 'created' : 'updated';
                if ($result = $db->useQuery('submission')) {
                    $message = sprintf('%s\'s picks were %s for week %s', $username, $action, $week);
                    flash('message', $message);
                }
                else {
                    $log->log('error', 'Issue creating/updating user picks.', $db->error, $db->getLastQuery());
                    $message = sprintf('%s\'s picks were NOT %s for week %s', $username, $action, $week);
                    flash('error', $message);
                }
                $url = sprintf('/commissioner/player/%s/picks', $uid);
                redirect_to($url);
            }
            else {
                halt(NOT_FOUND);
            }
        }
        else {
            halt(FORBIDDEN);
        }
    }

    /* Helpers */

    static function has_submission_for_week($username, $week) {
        $db = option('db');
        $log = option('log');

        $subkey = self::get_subkey($username, $week);
        $query = 'SELECT subvalue FROM {{submissions}} WHERE subkey = "%s"';
        $has_submission = FALSE;

        if ($result = $db->qry($query, $subkey)) {
            if ($result->num_rows === 1) {
                $has_submission = TRUE;
            }
        }
        else {
            $log->log('error', 'Failed to get submission', $db->error);
        }

        return $has_submission;
    }

    static function get_user_selection($cid, $challenges) {
        $sid = FC_DEFAULT_VALUE;

        foreach ($challenges as $challenge) {
            if ($challenge->cid === $cid) {
                $sid = $challenge->sid;
            }
        }

        return $sid;
    }

    static function get_subkey($username, $week) {
        return sprintf('%s-%s-%s', $username, FC_YEAR, $week);
    }

    static function get_username($uid) {
        $db = option('db');
        $log = option('log');

        $uid = $db->escape_string($uid);
        $username = NULL;

        $player_query = 'SELECT username FROM {{users}} WHERE uid = %s';
        if ($result = $db->qry($player_query, $uid)) {
            while ($obj = $result->fetch_object()) {
                $username = $obj->username;
            }
        }

        return $username;
    }

}

?>
