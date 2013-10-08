<?php
// Since we do lots of date/time stuff, set default timezone
date_default_timezone_set('America/New_York');
// Setup constants
// Wodk Web App constants
define('SITE_ROOT',     __DIR__);
define('CACHE_DIR',     SITE_ROOT . '/views/cache');
define('TEMPLATE_DIR',  SITE_ROOT . '/views/templates');
define('SITE_NAME',     'Football Challenge');
define('WODK_LOG',      SITE_ROOT . '/web_app.log');
define('WODK_BASE_URI', '/junkies/');
define('FORBIDDEN',     403); // Use this with halt() to send a 403 Forbidden

// Football Challenge constants
define('FC_LOG',            SITE_ROOT . '/football-challenge.log');
define('FC_COOKIE',         'football-challenge');
define('FC_YEAR',           2013);
define('FC_NUM_WEEKS',      15);
define('FC_NUM_CHALLENGES', 10);
// How much to truncate the Messages when a new challenge is created.
// 0 - No messages truncated
// 1 - Messages from last week (and older) are truncated
// 2 - Messages from two weeks ago (and older) are truncated
// ...
define('FC_MSGS_TRUNCATE', 1);

// Get the micro-framework Limonade
require_once('vendors/limonade.php');

// Get our templating engine Twig
require_once('vendors/Twig/Autoloader.php');
Twig_Autoloader::register();

// Autoload our Wodk classes
require_once('vendors/Wodk/Autoloader.php');
Wodk_Autoloader::register();

// Autoload our controllers
require_once('controllers/AppController.php');
AppController::register();

// Get our routes
require_once('routes.php');

// Global helpers
function get_post($var) {
    if (isset($_POST[$var])) {
        return $_POST[$var];
    }
    elseif (isset($GLOBALS['_JSON']->$var)) {
        return $GLOBALS['_JSON']->$var;
    }
    else {
        $json = json_decode(file_get_contents("php://input"));

        if (isset($json)) {
            if (isset($json->$var)) {
                return $json->$var;
            }
        }
    }

    return NULL;
}

function get_flash_messages($all) {
    $errs = array();
    $msgs = array();

    foreach ($all as $type => $msg) {
        if (strpos($type, 'error') !== FALSE) {
            array_push($errs, $msg);
        }
        elseif (strpos($type, 'message') !== FALSE) {
            array_push($msgs, $msg);
        }
    }

    option('have_flash_errors', count($errs) ? TRUE : FALSE);
    option('flash_errors', $errs);
    option('have_flash_messages', count($msgs) ? TRUE : FALSE);
    option('flash_messages', $msgs);

    return array('errors' => $errs, 'messages' => $msgs);
}

// Limonade
function configure() {
    // Setup logging
    $log = new Wodk_Logger(WODK_LOG);
    option('log', $log);

    // Setup environment
    $localhost = preg_match('/^localhost(\:\d+)?/', $_SERVER['HTTP_HOST']);
    $env =  $localhost ? ENV_DEVELOPMENT : ENV_PRODUCTION;
    option('env', $env);
    option('base_uri', WODK_BASE_URI);
    option('site_name', SITE_NAME);

    // Setup database
    $db_config = $env === ENV_PRODUCTION ? 'db-prod.php' : 'db-dev.php';
    require_once($db_config);
    $db = new Wodk_DB(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, DB_SOCK);
    option('db', $db->setPrefix(DB_PFIX));

    // Setup template engine
    $cache  = $env == ENV_PRODUCTION ? CACHE_DIR : FALSE;
    $loader = new Twig_Loader_Filesystem(TEMPLATE_DIR);
    $twig   = new Twig_Environment($loader, array(
        'cache' => $cache,
    ));
    $twig->getExtension('core')->setTimezone('America/New_York');
    $twig->addExtension(new Wodk_TwigExtensions());
    option('twig', $twig);

    //
    // Setup other application configurations
    //

    // Challenge Weeks
    $challenge_weeks = array();
    if ($result = $db->qry('SELECT DISTINCT week FROM {{challenges}} WHERE year = %s', FC_YEAR)) {
        while ($obj = $result->fetch_object()) {
            $challenge_weeks[] = array(
                'num'  => $obj->week,
                'path' => '/picks-week/' . $obj->week,
                'name' => 'Week ' . $obj->week,
            );
        }
    }
    $challenge_weeks_len = count($challenge_weeks);
    $challenge_weeks[$challenge_weeks_len - 1]['path'] = '/week/' . $challenge_weeks[$challenge_weeks_len - 1]['num'];
    option('challenge_weeks', $challenge_weeks);

    // Challenge Week
    option('challenge_week', $challenge_weeks_len);

    // Standings Week
    $standings_week = 0;
    if ($result = $db->qry('SELECT DISTINCT week FROM {{challenges}} WHERE year = %s AND winner_sid > 0 ORDER BY week DESC LIMIT 1', FC_YEAR)) {
        while ($obj = $result->fetch_object()) {
            $standings_week = $obj->week;
        }
    }
    option('standings_week', $standings_week);

    // User info
    $empty_user = array(
        'use'   => FALSE,
        'uid'   => FALSE,
        'name'  => FALSE,
        'perms' => FALSE,
    );
    option('empty_user', $empty_user);
    $user_info = $empty_user;
    if (isset($_COOKIE[FC_COOKIE])) {
        $str = $_COOKIE[FC_COOKIE];
        $str = explode('|', $str);
        $user_info = array(
            'use'   => TRUE,
            'uid'   => $str[2],
            'name'  => $str[0],
            'perms' => $str[1],
        );
    }
    option('user_info', $user_info);

    // Get all users simple info
    $all_users = array();
    if ($users = $db->qry('SELECT uid, username FROM {{users}}')) {
        while ($user = $users->fetch_object()) {
            $all_users[] = $user;
        }
    }
    else {
        $log->log('error', 'Trying to select users while in configure()', $db->error);
    }
    option('all_users', $all_users);

    option('build_version', file_get_contents('BUILD_VERSION.txt'));
    option('app_version', file_get_contents('VERSION.txt'));
}

function before() {
    // Load flash
    get_flash_messages(flash_now());

    /*
     * Other application tasks
     */

    // Footer
    $db = option('db');
    $footer = array();
    if ($result = $db->qry('SELECT username FROM {{users}} ORDER BY username')) {
        while ($obj = $result->fetch_object()) {
            $footer[] = array(
                'name' => $obj->username,
                'path' => '/picks/' . strtolower($obj->username),
            );
        }
    }
    option('footer', $footer);
}

function before_exit($exit) {
    $db = option('db');
    $db->close();
}

// Start app
run();

?>
