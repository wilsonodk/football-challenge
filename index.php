<?php
// Setup constants
// Limonade constants
define('SITE_ROOT', __DIR__);
define('CACHE_DIR', SITE_ROOT . '/views/cache');
define('TEMPLATE_DIR', SITE_ROOT . '/views/templates');
define('SITE_NAME', 'Junkies Football Challenge');
define('FORBIDDEN', 403);
// Football Challenge constants
define('FC_LOG', SITE_ROOT . '/football-challenge.log');
define('FC_BASE_URI', '/junkies/');
define('FC_COOKIE', 'football-challenge');
define('FC_YEAR', 2013);
define('FC_NUM_WEEKS', 15);
define('FC_NUM_CHALLENGES', 10);

// Get the micro-framework Limonade
require_once('vendors/limonade.php');

// Load Database Class
require_once('vendors/Wodk/MyDB.php');

// Get Logger Class
require_once('vendors/Wodk/Logger.php');

// Get our templating engine Twig
require_once('vendors/Twig/Autoloader.php');
Twig_Autoloader::register();

// Load our Twig Extension
require_once('vendors/Wodk/TwigFilters.php');

// Get our routes
require_once('routes.php');

function get_post($var) {
	if (isset($_POST[$var])) {
		return $_POST[$var];
	}
	else {
		return NULL;
	}
}

// Do other pre-run actions
function configure() {
	// Set up logger
	$log = new Logger(FC_LOG);
	option('log', $log);
	
	// Setting environment
	$localhost = preg_match('/^localhost(\:\d+)?/', $_SERVER['HTTP_HOST']);
	$env =  $localhost ? ENV_DEVELOPMENT : ENV_PRODUCTION;
	option('env', $env);
	option('base_uri', FC_BASE_URI);
	option('site_name', SITE_NAME);

	// Setup database
	$db_config = $env === ENV_PRODUCTION ? 'db-prod.php' : 'db-dev.php';
	require_once($db_config);
	$db = new MyDB(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, DB_SOCK);
	option('db_con', $db->setPrefix(DB_PFIX));

	// Setup Twig
	#$cache = $env == ENV_PRODUCTION ? CACHE_DIR : FALSE;
	$cache	= $env == ENV_PRODUCTION ? FALSE : FALSE;
	$loader = new Twig_Loader_Filesystem(TEMPLATE_DIR);
	$twig	= new Twig_Environment($loader, array(
		'cache' => $cache,
	));
	$twig->getExtension('core')->setTimezone('America/New_York');
	$twig->addExtension(new TwigFilters());
	option('twig', $twig);
	
	// Challenge Weeks
	$challenge_weeks = array();
	if ($result = $db->query('SELECT DISTINCT week FROM {{challenges}} WHERE year = %s', FC_YEAR)) {
		while ($obj = $result->fetch_object()) {
			$challenge_weeks[] = array(
				'num'  => $obj->week,
				'path' => '/week/' . $obj->week,
				'name' => 'Week ' . $obj->week,
			);
		}
	}
	option('challenge_weeks', $challenge_weeks);
	
	// Challenge Week
	option('challenge_week', count($challenge_weeks));
	
	// Standings Week
	$standings_week = 0;
	if ($result = $db->query('SELECT DISTINCT week FROM {{challenges}} WHERE year = %s AND wsid > 0 ORDER BY week DESC LIMIT 1', FC_YEAR)) {
		while ($obj = $result->fetch_object()) {
			$standings_week = $obj->week;
		}
	}
	option('standings_week', $standings_week);
	
	// User info
	$empty_user = array(
		'use'	=> FALSE,
		'uid'	=> FALSE,
		'name'	=> FALSE,
		'perms'	=> FALSE,
	);
	option('empty_user', $empty_user);
	$user_info = $empty_user;
	if (isset($_COOKIE[FC_COOKIE])) {
		$str = $_COOKIE[FC_COOKIE];
		$str = explode('|', $str);
		$user_info = array(
			'use'	=> TRUE,
			'uid'	=> $str[2],
			'name'	=> $str[0],
			'perms'	=> $str[1],
		);
	}
	option('user_info', $user_info);
}

function before() {
	$db = option('db_con');
	
	// Footer
	$footer = array();
	if ($result = $db->query('SELECT username FROM {{users}} ORDER BY username')) {
		while ($obj = $result->fetch_object()) {
			$footer[] = array(
				'name' => $obj->username,
				'path' => '/picks/' . strtolower($obj->username),
			);
		}
	}
	option('footer', $footer);
	
	// DEBUG FLASH
	$all_flash_messages	= flash_now();
	$flash_errors		= array();
	$flash_messages		= array();
	foreach($all_flash_messages as $type => $message) {
		if (strpos($type, 'message') !== FALSE) {
			array_push($flash_messages, $message);
		}
		elseif (strpos($type, 'error') !== FALSE) {
			array_push($flash_errors, $message);
		}
	}
	if (count($flash_errors)) {
		option('have_flash_errors', TRUE);
		option('flash_errors', $flash_errors);
	}
	if (count($flash_messages)) {
		option('have_flash_messages', TRUE);
		option('flash_messages', $flash_messages);
	}
	
	// I should be able to remove this later, right?
	$ops = option();
	foreach ($ops as $key => $value) {
		$all_ops[] = array('name' => $key, 'value' => $value, 'type' => gettype($value));
	}
	option('all_ops', $all_ops);
}

function before_exit($exit) {
	$db = option('db_con');
	$db->close();
}
// This starts the app, defined in Limonade
run();

?>
