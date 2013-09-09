<?php

class AdminPlayerController extends AdminController
{
	static function player() {
		if (self::checkPerms()) {
			$db = option('db_con');
			$players = array();
			$query = 'SELECT uid, username AS name FROM {{users}} ORDER BY username';
			if ($result = $db->query($query)) {
				while ($obj = $result->fetch_object()) {
					$players[] = $obj;
				}
			}
			
			return self::template('admin/player-list.html.twig', array(
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
				'activity' => 'Create',
			));
		}
		else {
			halt(FORBIDDEN);
		}
	}
	
	static function player_do_add() {
		if (self::checkPerms()) {
			$db = option('db_con');
			$log = option('log');
			// uid, username, email, password, wins, loses, permissions
			$query = 'INSERT INTO {{users}} VALUES (NULL, "%s", "%s", "%s", 0, 0, %s)';
			
			if ($db->query(
					$query, 
					$db->escape_string(strtoupper(get_post('username'))), 
					$db->escape_string(strtolower(get_post('email'))), 
					$db->escape_string(get_post('password')), 
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
			$db = option('db_con');
			$log = option('log');
			// uid, username, email, password, wins, loses, permissions
			$db->setQuery(
				'update',
				'UPDATE {{users}} SET username = "%s", email = "%s", password = "%s", permissions = %s WHERE uid = %s',
				$db->escape_string(strtoupper(get_post('username'))),
				$db->escape_string(strtolower(get_post('email'))),
				$db->escape_string(get_post('password')),
				$db->escape_string(get_post('permissions')),
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
			$db = option('db_con');
			$log = option('log');
			$query = 'DELETE FROM {{users}} WHERE uid = %s';
			if ($db->query($query, $id)) {
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