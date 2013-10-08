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
        $twig   = option('twig');
        $params = array_merge(option(), $extra_params);
        $tmpl   = $twig->loadTemplate($template);
        return $tmpl->render($params);
    }

    //
    // Additional AppController methods
    //

    static function getUserInfoFromName($name) {
        $query = 'SELECT uid, username, email, permissions, password, submission FROM {{users}} WHERE username = "%s"';
        $info = self::getUserInfo($query, $name);
        return $info;
    }

    static function getUserInfoFromUid($id) {
        $query = 'SELECT uid, username, email, permissions, password, submission FROM {{users}} WHERE uid = %s';
        $info = self::getUserInfo($query, $id);
        return $info;
    }

    static function getUserInfo($query, $arg) {
        $db = option('db');
        $usr = option('empty_user');

        if ($result = $db->qry($query, $arg)) {
            while ($obj = $result->fetch_object()) {
                $usr['use']      = TRUE;
                $usr['uid']      = $obj->uid;
                $usr['name']     = $obj->username;
                $usr['perms']    = $obj->permissions;
                $usr['email']    = $obj->email;
                $usr['password'] = $obj->password;
                $usr['sub']      = $obj->submission;
            }
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
}
?>
