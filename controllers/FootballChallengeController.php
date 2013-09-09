<?php

class FootballChallengeController
{
	static function template($template, $extra_params = array()) {
		$twig 	= option('twig');
		$params = array_merge(option(), $extra_params);
		$tmpl 	= $twig->loadTemplate($template);
		return $tmpl->render($params);
	}
	
	static function getUserInfoFromName($name) {
		$query = 'SELECT uid, username, email, permissions, password FROM {{users}} WHERE username = "%s"';
		$info = self::getUserInfo($query, $name);
		return $info;
	}
	
	static function getUserInfoFromUid($id) {
		$query = 'SELECT uid, username, email, permissions, password FROM {{users}} WHERE uid = %s';
		$info = self::getUserInfo($query, $id);
		return $info;
	}
	
	static function getUserInfo($query, $arg) {
		$db = option('db_con');
		$usr = option('empty_user');
		
		if ($result = $db->query($query, $arg)) {
			while ($obj = $result->fetch_object()) {
				$usr['use'] 	 = TRUE;
				$usr['uid'] 	 = $obj->uid;
				$usr['name'] 	 = $obj->username;
				$usr['perms'] 	 = $obj->permissions;
				$usr['email']	 = $obj->email;
				$usr['password'] = $obj->password;
			}
		}
		
		return $usr;
	}
	
	static function getReferrer() {
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : option('base_uri');
	}
}

?>