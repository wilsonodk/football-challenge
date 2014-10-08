<?php
class MessengerController extends AppController
{
    static function show() {
        return self::getMessages();
    }

    static function create() {
        $db  = option('db');
        $log = option('log');

        // The ordemid, pid, uid, message (blob), posted, active, week (challenge week)
        $query     = 'INSERT INTO {{messages}} VALUES (NULL, %d, %s, "%s", %s, %s, %s)';
        $user_info = option('user_info');

        $pid = get_post('pid');
        $msg = get_post('message');

        $poster = $user_info['uid'];
        $replied_to = 0;

        if ($user_info['use'] === TRUE) {
            $pid = $pid === 'main' ? NULL : $pid;
            $msg = $db->escape_string(strip_tags($msg));
            $db->qry(
                $query,
                $pid,
                $poster,
                $msg,
                time(),
                1,
                option('challenge_week')
            );
        }

        // These values are needed for emails
        $site_name = option('site_name');
        $site_url  = sprintf('http://%s%s', $_SERVER['HTTP_HOST'], WODK_BASE_URI);
        $rn = "\r\n";

        // If a reply, send notification
        if ($pid) {
            if ($result = $db->qry('SELECT uid FROM {{messages}} WHERE mid = %s', $pid)) {
                $obj = $result->fetch_object();

                if ($poster !== $obj->uid) {
                    if ($result = $db->qry('SELECT uid, username, email FROM {{users}} WHERE active = 1 AND uid = %s AND notify = 1', $obj->uid)) {
                        $obj = $result->fetch_object();

                        if (isset($obj->username) && isset($obj->email)) {
                            $replied_to = $obj->uid;
                            $subject = 'Someone replied to your message';
                            $message = "Another user replied to your message on the {$site_name} site.{$rn}{$rn}{$site_url}";
                            self::notify($obj->username, $obj->email, $subject, $message);
                        }
                    }
                }
            }
        }

        // Let's check the message body to see if it mentions another player
        // Only checking for players that want notifications
        $query = 'SELECT username, email FROM {{users}} WHERE active = 1 AND notify = 1 AND uid != %s ORDER BY username';
        $users = array();
        if ($result = $db->qry($query, $replied_to)) {
            while ($obj = $result->fetch_object()) {
                $users[] = $obj;
            }
        }

        $msg = strtolower($msg);
        $matched = array();
        foreach ($users as $user) {
            if (strpos($msg, strtolower($user->username)) !== FALSE) {
                $matched[$user->username] = $user;
            }
        }

        foreach ($matched as $name => $user) {
            // Email each user
            $subject = 'Someone mentioned you';
            $message = "Another user on the {$site_name} site mentioned you in a post.{$rn}{$rn}{$site_url}";
            self::notify($name, $user->email, $subject, $message);
        }

        return '';
    }

    /* Helpers */

    static function getMessages() {
        $db       = option('db');
        $log      = option('log');
        $messages = array();
        $query    = 'SELECT m.mid AS mid, m.pid AS pid, m.message AS message, m.posted AS timestamp, u.username AS username, m.uid AS uid FROM {{messages}} m LEFT JOIN {{users}} u ON m.uid = u.uid WHERE m.active = 1 ORDER BY m.mid, m.pid, m.posted DESC';

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
