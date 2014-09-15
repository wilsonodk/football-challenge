<?php
class AppController
{
    //
    // Required methods
    //

    // Registers the class as an SPL autoloader
    static public function register() {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'));
    }

    // Autoloader method
    static public function autoload($class) {
        if (strpos($class, 'Controller') === FALSE) {
            return;
        }

        if (is_file($file = dirname(__FILE__).'/'.$class.'.php')) {
            require $file;
        }
    }

    // Template method
    static function template($template, $extra_params = array()) {
        $twig      = option('twig');
        $constants = get_defined_constants(TRUE);
        $params    = array_merge(option(), $constants['user'], $extra_params);
        $tmpl      = $twig->loadTemplate($template);

        return $tmpl->render($params);
    }

    //
    // Additional AppController methods
    //

    static function password($user, $raw) {
        return md5(SITE_NAME . strtoupper($user) . $raw);
    }

    static function getUserInfoFromName($name) {
        $query = 'SELECT uid, username, email, permissions, password, reminder, submission FROM {{users}} WHERE username = "%s"';
        $info = self::getUserInfo($query, $name);
        return $info;
    }

    static function getUserInfoFromUid($id) {
        $query = 'SELECT uid, username, email, permissions, password, reminder, submission FROM {{users}} WHERE uid = %s';
        $info = self::getUserInfo($query, $id);
        return $info;
    }

    static function getUserInfo($query, $arg) {
        $db = option('db');
        $log = option('log');
        $usr = option('empty_user');

        if ($result = $db->qry($query, $arg)) {
            while ($obj = $result->fetch_object()) {
                $usr['use']      = TRUE;
                $usr['uid']      = $obj->uid;
                $usr['name']     = $obj->username;
                $usr['perms']    = $obj->permissions;
                $usr['email']    = $obj->email;
                $usr['password'] = $obj->password;
                $usr['reminder'] = $obj->reminder;
                $usr['sub']      = $obj->submission;
            }
        } else {
            $log->log('error', 'Issue fetching user info', $db->error);
        }

        return $usr;
    }

    static function getReferrer() {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $needle = 'http://' . $_SERVER['HTTP_HOST'] . WODK_BASE_URI;
            $replace = '/';
            $haystack = $_SERVER['HTTP_REFERER'];
            $referrer = str_replace($needle, $replace, $haystack);
        }
        else {
            $referrer = option('base_uri');
        }

        return $referrer;
    }

    static function getEnv() {
        return (int) option('env');
    }

    static function notify($name, $email, $subject, $message) {
        $db  = option('db');
        $log = option('log');

        $rn   = "\r\n";
        $env  = self::getEnv();
        $base = sprintf('http://%s%s', $_SERVER['HTTP_HOST'], WODK_BASE_URI);
        $acct = sprintf('%smy-account', $base);
        $phpv = phpversion();
        $appv = trim(option('app_version'));

        $commissioners = array();

        if ($result = $db->qry('SELECT username, email FROM {{users}} WHERE permissions = 2')) {
            while ($obj = $result->fetch_object()) {
                $commissioners[] = "{$obj->username} <{$obj->email}>";
            }

            $commissioners = implode(', ', $commissioners);
        }

        $to = "$name <$email>";
        $message = $message . "{$rn}{$rn}- - -{$rn}{$rn}To change your email settings, go to...{$rn}{$acct}{$rn}";
        $headers = "From: {$commissioners}{$rn}Reply-To: {$commissioners}{$rn}X-Mailer: Football Challenge/{$appv} PHP/{$phpv}";

        if ($env === ENV_PRODUCTION) {
            $log->log('message', sprintf('Email sent: ', $subject));
            mail($to, $subject, $message, $headers);
        } elseif ($env === ENV_DEVELOPMENT) {
            $log->log('message', htmlspecialchars("{$headers}{$rn}{$rn}To: {$to}{$rn}{$rn}{$subject}{$rn}{$rn}$message"));
        } else {
            $log->log('error', sprintf('Unmatched environment variable env(%s):%s', $env, gettype($env)));
        }

        return true;
    }
}
?>
