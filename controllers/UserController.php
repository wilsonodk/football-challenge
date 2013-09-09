<?php

class UserController extends FootballChallengeController
{
	static function show_login() {
		return self::template('user/login.html.twig', array(
			'referrer' => self::getReferrer(),
		));	
	}
	
	static function do_login() {
		$db = option('db_con');
		$log = option('log');
		$referrer = get_post('referrer');
		$username = $db->escape_string(get_post('username'));
		$password = $db->escape_string(get_post('password'));
		
		if ($result = $db->query('SELECT uid, username, permissions FROM {{users}} WHERE username = "%s" AND password = "%s"', $username, $password)) {
			if ($result->num_rows === 1) {
				while ($obj = $result->fetch_object()) {
					// Set our cookie...
					$cookie = sprintf('%s|%s|%s', $obj->username, $obj->permissions, $obj->uid);
					setcookie(FC_COOKIE, $cookie, time() + (60 * 60 * 24 * 300));
				}
				
				// Cookie set, flash and redirect
				flash('message:logged in', 'You have been logged in!');
				redirect_to($referrer);
			}
			else {
				$log->log('warning', 'Login error due to username/password not being found.');
			
				// Flash and redirect
				flash('error:login credentials ', 'There was a problem logging with your login credentials. Please try again.');
				redirect_to('/');
			}
		}
		else {
			// Do logging for DB error
			$log->log('error', "Problem with query: `$query`.");
			
			// Flash and redirect
			flash('error:login', 'There was a problem logging in. Please try again later.');
			redirect_to('/');
		}
	}
	
	static function do_logout() {
		setcookie(FC_COOKIE, '', time() - 1000);
		flash('message:logged out', 'You have been logged out.');
		redirect_to('/');
	}
	
	static function show_account() {
		$db = option('db_con');
		$log = option('log');
		
		$user = option('user_info');
		if ($user['uid'] && $user['name'] && $user['perms']) {
			if ($result = $db->query('SELECT email FROM {{users}} WHERE uid = %s AND username = "%s" AND permissions = %s', $db->escape_string($user['uid']), $db->escape_string($user['name']), $db->escape_string($user['perms']))) {
				if ($result->num_rows === 1) {
					while ($obj = $result->fetch_object()) {
						$user['email'] = $obj->email;
					}
				}
				else {
					$log->log('warning', 'Error with finding user for my-account. User: `' . $user['name'] . '`.');
					flash('error:finding account', 'There was problem finding your account.');
					redirect_to('/logout');
				}
			}
	
			return self::template('user/account.html.twig', array(
				'referrer'	=> self::getReferrer(),
				'uid'		=> $user['uid'],
				'username'	=> $user['name'],
				'email'		=> $user['email'],
			));
		}
		else {
			$log->log('warning', 'Attempt to access my-account page while not logged in. User: `' . $user['name'] . '`.');
			flash('error:no view account page', 'You must be logged in to view the account page.');
			flash('error:please try again', 'Please login and try again.');
			redirect_to('/');
		}
	}
	
	static function edit_account() {
		$db = option('db_con');
		$log = option('log');
		$uid = get_post('uid');
		$username = get_post('username');
		$email = get_post('email');
		$current_email = get_post('current_email');
		$password = array(
			'old' => get_post('password'),
			'new' => get_post('new-password'),
			'again' => get_post('new-password-again'),
		);
		$db->setQuery(
				'validate',
				'SELECT password FROM {{users}} WHERE uid = %s AND username = "%s" AND password = "%s"',
				$db->escape_string($uid),
				$db->escape_string($username),
				$db->escape_string($password['old'])
			)
			->setQuery(
				'email',
				'UPDATE {{users}} SET email = "%s" WHERE uid = %s AND username = "%s" AND email = "%s"', 
				$db->escape_string($email),
				$db->escape_string($uid),
				$db->escape_string($username),
				$db->escape_string($current_email)
			);
		$pw_query = FALSE;
		$email_query = $email === $current_email ? FALSE : TRUE;
		
		// Do we want to change our password?
		if ($password['old'] && $password['new'] && $password['again']) {
			if ($result = $db->useQuery('validate')) {
				if ($result->num_rows === 1) {
					while ($obj = $result->fetch_object()) {
						if ($obj->password === $password['old']) {
							if ($password['new'] === $password['again']) {
								$pw_query = TRUE;
								$db->setQuery(
									'password',
									'UPDATE {{users}} SET password = "%s" WHERE uid = %s AND username = "%s" AND password = "%s"',
									$db->escape_string($password['new']),
									$db->escape_string($uid),
									$db->escape_string($username),
									$db->escape_string($password['old'])
								);
							}
							else {
								$log->log('warning', 'Password mismatch for user `' . $username . '` when updating password.');
								flash('error:password mismatch', "Your new password doesn't match, please try again.");
								redirect_to('/my-account');
							}
						}
						else {
							$log->log('warning', 'Log wrong password for user `' . $username . '` when updating password.');
							flash('error:wrong password', 'Your password was incorrect.');
							flash('error:try again', 'Please try again later.');
						}
					}
				}
				elseif ($result->num_rows > 1) {
					$log->log('error', 'Too many rows were returned when executing query `' . $db->getQuery('validate') . '`.');
					flash('error:fetch error', 'There was an error updating your password.');
					flash('error:try again', 'Please try again later.');
				}
				else {
					flash('error:validate', 'There was an error when trying to update your password.');
					flash('error:try again', 'Please try again later.');
				}
			}
			else {
				$log->log('error', 'Database connection issue with validate query in UserController.');
				flash('error:validate query', "There was an error with updating your password. db");
				flash('error:try again', "Please try again later.");
			}		
		}
		
		// Now execute our queries
		if ($email_query) {
			if ($db->useQuery('email')) {
				if ($db->affected_rows === 1) {
					flash('message:email updated', "Your email has been updated.");
				}
				elseif ($db->affected_rows > 1) {
					// TODO: Log issue with updating too many rows for email
					
					flash('error:update email account', "There was an issue with updating the email on your account.");
					redirect_to('/');
				}
			}
		}
		if ($pw_query) {
			if ($db->useQuery('password')) {
				if ($db->affected_rows === 1) {
					flash('message:password updated', "Your password has been updated.");
				}
				elseif ($db->affected_rows > 1) {
					$log->log('error', 'Too many rows were updated with password change.');
					flash('error:update password', "There was an issue with updating the password on your account.");
					redirect_to('/');
				}
			}
			else {
				$log->log('error', 'Password query did not work.', $db->getQuery('password'));
				flash('error:update account', "There was a problem updating your account.");
				flash('error:try again', "Please try again later.");
				redirect_to('/');
			}
		}		
		
		redirect_to('/my-account');
	}
}

?>