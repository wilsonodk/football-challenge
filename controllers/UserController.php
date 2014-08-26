<?php

class UserController extends AppController
{
    static function show_login() {
        return self::template('user/login.html.twig', array(
            'page_name' => 'Login',
            'referrer' => self::getReferrer(),
        ));
    }

    static function do_login() {
        $db       = option('db');
        $log      = option('log');
        $referrer = get_post('referrer');
        $username = $db->escape_string(get_post('username'));
        $password = self::password(get_post('username'), get_post('password'));

        if ($result = $db->qry('SELECT uid, username, permissions FROM {{users}} WHERE username = "%s" AND password = "%s"', $username, $password)) {
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
        $db  = option('db');
        $log = option('log');
        $user = option('user_info');

        if ($user['uid'] && $user['name'] && $user['perms']) {
            if ($result = $db->qry('SELECT email, reminder FROM {{users}} WHERE uid = %s AND username = "%s" AND permissions = %s', $db->escape_string($user['uid']), $db->escape_string($user['name']), $db->escape_string($user['perms']))) {
                if ($result->num_rows === 1) {
                    while ($obj = $result->fetch_object()) {
                        $user['email']    = $obj->email;
                        $user['reminder'] = $obj->reminder;
                    }
                }
                else {
                    $log->log('warning', 'Error with finding user for my-account. User: `' . $user['name'] . '`.');
                    flash('error:finding account', 'There was problem finding your account.');
                    redirect_to('/logout');
                }
            }

            return self::template('user/account.html.twig', array(
                'page_name' => 'My Account',
                'referrer'  => self::getReferrer(),
                'uid'       => $user['uid'],
                'username'  => $user['name'],
                'email'     => $user['email'],
                'reminder'  => $user['reminder'],
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
        $db               = option('db');
        $log              = option('log');
        $uid              = get_post('uid');
        $username         = get_post('username');
        $email            = get_post('email');
        $current_email    = get_post('current_email');
        $reminder         = get_post('reminder') === 'yes' ? 1 : 0;
        $current_reminder = (int) get_post('current_reminder');
        $password         = array(
            'old'   => self::password(get_post('username'), get_post('password')),
            'new'   => self::password(get_post('username'), get_post('new-password')),
            'again' => self::password(get_post('username'), get_post('new-password-again')),
        );
        $pw_query         = FALSE;
        $email_query      = $email === $current_email ? FALSE : TRUE;
        $reminder_query   = $reminder === $current_reminder ? FALSE : TRUE;

        $db->setQuery(
            'validate',
            'SELECT password FROM {{users}} WHERE uid = %s AND username = "%s" AND password = "%s"',
            $db->escape_string($uid),
            $db->escape_string($username),
            $password['old']
        )
        ->setQuery(
            'email',
            'UPDATE {{users}} SET email = "%s" WHERE uid = %s AND username = "%s" AND email = "%s"',
            $db->escape_string($email),
            $db->escape_string($uid),
            $db->escape_string($username),
            $db->escape_string($current_email)
        )
        ->setQuery(
            'reminder',
            'UPDATE {{users}} SET reminder = %s WHERE uid = %s AND username = "%s"',
            $db->escape_string($reminder),
            $db->escape_string($uid),
            $db->escape_string($username)
        );

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
                                    $password['new'],
                                    $db->escape_string($uid),
                                    $db->escape_string($username),
                                    $password['old']
                                );
                            }
                            else {
                                $log->log('warning', 'Password mismatch for user `' . $username . '` when updating password.');
                                flash('error:password mismatch', 'Your new password does not match, please try again.');
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
                    $log->log('error', 'Error validating email.', $db->error);
                    flash('error:validate', 'There was an error when trying to update your password.');
                    flash('error:try again', 'Please try again later.');
                }
            }
            else {
                $log->log('error', 'Database connection issue with validate query in UserController.');
                flash('error:validate query', 'There was an error with updating your password. db');
                flash('error:try again', 'Please try again later.');
            }
        }

        // Now execute our queries
        if ($reminder_query) {
            if ($db->useQuery('reminder')) {
                if ($db->affected_rows === 1) {
                    flash('message:reminder updated', 'Your email reminder settings have been updated.');
                    $log->log('message', sprintf('User %s updated their email reminder settings', $username));
                }
                elseif ($db->affected_rows > 1) {
                    flash('error:reminder too many', 'There was an issue while updating your email reminder settings.');
                    $log->log('error', sprintf('Issue with email reminder query were updated on last query: %s', $db->getQuery('reminder')), $db->error);
                    redirect_to('/');
                }
            }
            else {
                flash('error:reminder generic', 'There was an error updating your email reminder settings.');
                $log->log('error', sprintf('Error with reminder query: %s', $db->getQuery('reminder')), $db->error);
                redirect_to('/');
            }
        }

        if ($email_query) {
            if ($db->useQuery('email')) {
                if ($db->affected_rows === 1) {
                    flash('message:email updated', 'Your email has been updated.');
                    $log->log('message', sprintf('User %s updated their email.', $username));
                }
                elseif ($db->affected_rows > 1) {
                    flash('error:update email account', 'There was an issue with updating the email on your account.');
                    $log->log('error', sprintf('Too many rows were updated on last query: %s', $db->getQuery('email')));
                    redirect_to('/');
                }
            }
            else {
                flash('error:update email generic', 'There was an error updating your email.');
                $log->log('error', sprintf('Error with reminder query: %s', $db->getQuery('reminder')));
                redirect_to('/');
            }
        }

        if ($pw_query) {
            if ($db->useQuery('password')) {
                if ($db->affected_rows === 1) {
                    flash('message:password updated', 'Your password has been updated.');
                    $log->log('message', sprintf('User %s updated their password.', $username));
                }
                elseif ($db->affected_rows > 1) {
                    flash('error:update password', 'There was an issue with updating the password on your account.');
                    $log->log('error', sprintf('Too many rows were updated with password change. %s', $db->getQuery('password')));
                    redirect_to('/');
                }
            }
            else {
                flash('error:update account', 'There was a problem updating your account.');
                flash('error:try again', 'Please try again later.');
                $log->log('error', 'Password query did not work.', $db->getQuery('password'));
                redirect_to('/');
            }
        }

        redirect_to('/my-account');
    }
}

?>
