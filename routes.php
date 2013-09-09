<?php
/*
 * NOTE: 
 * Limonade can use _method to allow HTML forms to send PUT and DELETE methods
 * 
 * GET	Show
 * POST	Create
 * PUT	Edit
 * DELETE
 */
/*
 * == MainController ==
 * Method	Path				Controller
 * GET		/					MainController::home
 * GET		/week/picks			MainController::week_picks
 * GET		/week/:num			MainController::week
 * POST		/week/:num			MainController::week_add
 * GET		/week/:num/:user	MainController::week_user
 * GET		/picks/:user		MainController::picks
 */
dispatch('/', array('MainController', 'home'));
dispatch('/week/:num', array('MainController', 'week'));
dispatch_post('/week/:num', array('MainController', 'week_add'));
//dispatch('/week/:num/picks', array('MainController', 'week_picks'));
dispatch('/week/:num/:user', array('MainController', 'week_user'));
dispatch('/picks/:user', array('MainController', 'picks'));

/*
 * == AdminController ==
 * Method	Path							Controller
 * GET		/commissioner					AdminController::home
 * GET		/commissioner/logs				AdminController::logs
 * GET		/commissioner/standings			AdminController::standings
 * GET		/commissioner/challenge			AdminChallengeController::challenge
 * POST		/commissioner/challenge			AdminChallengeController::challenge_add
 * GET		/commissioner/challenge/edit	AdminChallengeController::challenge_show_edit
 * PUT 		/commissioner/challenge/edit	AdminChallengeController::challenge_do_edit
 * GET		/commissioner/challenge/check	AdminChallengeController::check
 * GET		/commissioner/players			AdminPlayerController::player
 * GET		/commissioner/player			AdminPlayerController::player
 * GET		/commissioner/player/:id		AdminPlayerController::player_show
 * GET		/commissioner/player/new		AdminPlayerController::player_add
 * POST		/commissioner/player/new		AdminPlayerController::player_do_add
 * GET		/commissioner/player/:id/edit	AdminPlayerController::player_show_edit
 * PUT		/commissioner/player/:id/edit	AdminPlayerController::player_do_edit
 * GET		/commissioner/player/:id/delete	AdminPlayerController::player_show_delete
 * DELETE	/commissioner/player/:id/delete	AdminPlayerController::player_do_delete
 */
dispatch('/commissioner', array('AdminController', 'home'));
dispatch('/commissioner/logs', array('AdminController', 'logs'));
dispatch('/commissioner/standings', array('AdminController', 'standings'));
// Challenge
dispatch('/commissioner/challenge', array('AdminChallengeController', 'challenge'));
dispatch_post('/commissioner/challenge', array('AdminChallengeController', 'challenge_add'));
dispatch('/commissioner/challenge/edit', array('AdminChallengeController', 'challenge_show_edit'));
dispatch_put('/commissioner/challenge/edit', array('AdminChallengeController', 'challenge_do_edit'));
dispatch('/commissioner/challenge/check', array('AdminChallengeController', 'check'));
// Player
dispatch('/commissioner/player', array('AdminPlayerController', 'player'));
dispatch('/commissioner/players', array('AdminPlayerController', 'player'));
dispatch('/commissioner/player/new', array('AdminPlayerController', 'player_add'));
dispatch_post('/commissioner/player/new', array('AdminPlayerController', 'player_do_add'));
dispatch('/commissioner/player/:id', array('AdminPlayerController', 'player_show'));
dispatch('/commissioner/player/:id/edit', array('AdminPlayerController', 'player_show_edit'));
dispatch_put('/commissioner/player/:id/edit', array('AdminPlayerController', 'player_do_edit'));
dispatch('/commissioner/player/:id/delete', array('AdminPlayerController', 'player_show_delete'));
dispatch_delete('/commissioner/player/:id/delete', array('AdminPlayerController', 'player_do_delete'));

/*
 * == UserController ==
 * Method	Path			Controller
 * GET		/login			UserController::show_login
 * POST		/login			UserController::do_login
 * GET		/logout			UserController::do_logout
 * GET		/my-account		UserController::show_account
 * PUT		/my-account		UserController::edit_account
 */
dispatch('/login', array('UserController', 'show_login'));
dispatch_post('/login', array('UserController', 'do_login'));
dispatch('/logout', array('UserController', 'do_logout'));
dispatch('/my-account', array('UserController', 'show_account'));
dispatch_put('/my-account', array('UserController', 'edit_account'));

/*
 * == MessengerController ==
 * Method	Path				Controller
 * GET		/messages			MessengerController::show
 * POST		/message/new		MessengerController::create
 * Coming Soon
 * PUT		/message/:id/edit	MessengerController::edit
 * DELETE	/message/:id/delete MessengerController::delete
 */
dispatch('/messages', array('MessengerController', 'show'));
dispatch_post('/message/new', array('MessengerController', 'create'));

?>
