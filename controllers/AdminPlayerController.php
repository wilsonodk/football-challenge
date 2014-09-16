<?php

class AdminPlayerController extends AdminController
{
    static function player() {
        if (self::checkPerms()) {
            $db = option('db');
            $players = array();
            $query = 'SELECT uid, username AS name FROM {{users}} ORDER BY username';
            if ($result = $db->qry($query)) {
                while ($obj = $result->fetch_object()) {
                    $players[] = $obj;
                }
            }

            return self::template('admin/player-list.html.twig', array(
                'page_name' => 'Player Administration',
                'players' => $players,
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_show($id) {
        if (self::checkPerms()) {
            return self::template('admin/player.html.twig', array(
                'page_name' => 'Player Administration',
                'activity' => 'View',
                'player' => self::getUserInfoFromUid($id),
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_add() {
        if (self::checkPerms()) {
            return self::template('admin/player.html.twig', array(
                'page_name' => 'Player Administration',
                'activity' => 'Create',
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_do_add() {
        if (self::checkPerms()) {
            $db = option('db');
            $log = option('log');
            // uid, username, email, password, wins, loses, permissions, submission, reminder, notify
            $query = 'INSERT INTO {{users}} VALUES (NULL, "%s", "%s", "%s", 0, 0, %s, 0, 1, 1)';

            if ($db->qry(
                    $query,
                    $db->escape_string(strtoupper(get_post('username'))),
                    $db->escape_string(strtolower(get_post('email'))),
                    self::password(get_post('username'), get_post('password')),
                    $db->escape_string(get_post('permissions'))
                ))
            {
                $uid = $db->getInsertId();

                flash('message:added player', 'Successfully added a new player.');
                flash('message:player uid', "Player has UID of $uid.");
            }
            else {
                $log->log('error', 'Commisssioner attempted to add player.', $db->error);
                flash('error:player add', 'Error occurred while attempting to add player.');
            }

            // TODO: Add submissions for each week that already exists

            redirect_to(self::getReferrer());
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_show_edit($id) {
        if (self::checkPerms()) {
            return self::template('admin/player.html.twig', array(
                'page_name' => 'Player Administration',
                'activity' => 'Edit',
                'player' => self::getUserInfoFromUid($id),
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_do_edit($id) {
        if (self::checkPerms()) {
            $db = option('db');
            $log = option('log');
            // uid, username, email, password, wins, loses, permissions, reminder, notify
            $db->setQuery(
                'update',
                'UPDATE {{users}} SET username = "%s", email = "%s", password = "%s", permissions = %s, reminder = %s, notify = %s WHERE uid = %s',
                $db->escape_string(strtoupper(get_post('username'))),
                $db->escape_string(strtolower(get_post('email'))),
                self::password(get_post('username'), get_post('password')),
                $db->escape_string(get_post('permissions')),
                get_post('reminder') === 'yes' ? 1 : 0,
                get_post('notify') === 'yes' ? 1 : 0,
                $id
            );

            if ($result = $db->useQuery('update')) {
                flash('message:updated player', 'Successfully updated the player.');
            }
            else {
                $log->log('error', 'Commissioner attempted to update player.', $db->error, $db->getQuery('update'));
                flash('error:player update', 'Error occurred while attempting to update player.');
            }
            redirect_to(self::getReferrer());
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_show_delete($id) {
        if (self::checkPerms()) {
            return self::template('admin/player.html.twig', array(
                'page_name' => 'Player Administration',
                'activity' => 'Delete',
                'player' => self::getUserInfoFromUid($id),
            ));
        }
        else {
            halt(FORBIDDEN);
        }
    }

    static function player_do_delete($id) {
        if (self::checkPerms()) {
            $db = option('db');
            $log = option('log');
            $query = 'DELETE FROM {{users}} WHERE uid = %s';
            if ($db->qry($query, $id)) {
                flash('message:deleted player', 'Successfully deleted the player.');
            }
            else {
                $log->log('error', 'Commissioner attempted to delete player.', $db->error);
                flash('error:player delete', 'Error occurred while attempting to delete a player.');
            }
            redirect_to('/commissioner/player');
        }
        else {
            halt(FORBIDDEN);
        }
    }
}

?>
