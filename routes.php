<?php
/*
 * NOTE:
 * Limonade can use _method to allow HTML forms to send PUT and DELETE methods
 *
 * GET   Show
 * POST  Create
 * PUT   Edit
 * DELETE
 */
/*
 * == MainController ==
 * Method  Path              Controller
 * GET     /                 MainController::home
 * GET     /week/:num        MainController::week
 * POST    /week/:num        MainController::week_add
 * GET     /week/:num/:user  MainController::week_user
 * GET     /picks/:user      MainController::picks
 * GET     /picks-week/:num  MainController::picks_week
 */
dispatch('/',                'MainController::home');
dispatch('/week/:num',       'MainController::week');
dispatch_post('/week/:num',  'MainController::week_add');
dispatch('/week/:num/:user', 'MainController::week_user');
dispatch('/picks/:user',     'MainController::picks');
dispatch('/picks-week/:num', 'MainController::picks_week');


/*
 * == AdminController ==
 * Method  Path                             Controller
 * GET     /commissioner                    AdminController::home
 * GET     /commissioner/logs               AdminController::logs
 * GET     /commissioner/standings          AdminController::standings
 * GET     /commissioner/challenge          AdminChallengeController::challenge
 * POST    /commissioner/challenge          AdminChallengeController::challenge_add
 * GET     /commissioner/challenge/edit     AdminChallengeController::challenge_show_edit
 * PUT     /commissioner/challenge/edit     AdminChallengeController::challenge_do_edit
 * GET     /commissioner/challenge/check    AdminChallengeController::check
 * GET     /commissioner/players            AdminPlayerController::player
 * GET     /commissioner/player             AdminPlayerController::player
 * GET     /commissioner/player/:id         AdminPlayerController::player_show
 * GET     /commissioner/player/new         AdminPlayerController::player_add
 * POST    /commissioner/player/new         AdminPlayerController::player_do_add
 * GET     /commissioner/player/:id/edit    AdminPlayerController::player_show_edit
 * PUT     /commissioner/player/:id/edit    AdminPlayerController::player_do_edit
 * GET     /commissioner/player/:id/delete  AdminPlayerController::player_show_delete
 * DELETE  /commissioner/player/:id/delete  AdminPlayerController::player_do_delete
 */
dispatch('/commissioner',           'AdminController::home');
dispatch('/commissioner/logs',      'AdminController::logs');
dispatch('/commissioner/standings', 'AdminController::standings');
// Challenge
dispatch('/commissioner/challenge',             'AdminChallengeController::challenge');
dispatch_post('/commissioner/challenge',        'AdminChallengeController::challenge_add');
dispatch('/commissioner/challenge/edit',        'AdminChallengeController::challenge_show_edit');
dispatch_put('/commissioner/challenge/edit',    'AdminChallengeController::challenge_do_edit');
dispatch_delete('/commissioner/challenge/edit', 'AdminChallengeController::challenge_do_delete');
dispatch('/commissioner/challenge/check',       'AdminChallengeController::check');
// Player
dispatch('/commissioner/player',                   'AdminPlayerController::player');
dispatch('/commissioner/players',                  'AdminPlayerController::player');
dispatch('/commissioner/player/new',               'AdminPlayerController::player_add');
dispatch_post('/commissioner/player/new',          'AdminPlayerController::player_do_add');
dispatch('/commissioner/player/:id',               'AdminPlayerController::player_show');
dispatch('/commissioner/player/:id/edit',          'AdminPlayerController::player_show_edit');
dispatch_put('/commissioner/player/:id/edit',      'AdminPlayerController::player_do_edit');
dispatch('/commissioner/player/:id/delete',        'AdminPlayerController::player_show_delete');
dispatch_delete('/commissioner/player/:id/delete', 'AdminPlayerController::player_do_delete');

/*
 * == UserController ==
 * Method  Path         Controller
 * GET     /login       UserController::show_login
 * POST    /login       UserController::do_login
 * GET     /logout      UserController::do_logout
 * GET     /my-account  UserController::show_account
 * PUT     /my-account  UserController::edit_account
 */
dispatch('/login',          'UserController::show_login');
dispatch_post('/login',     'UserController::do_login');
dispatch('/logout',         'UserController::do_logout');
dispatch('/my-account',     'UserController::show_account');
dispatch_put('/my-account', 'UserController::edit_account');

/*
 * == MessengerController ==
 * Method  Path       Controller
 * GET     /messages  MessengerController::show
 * POST    /messages  MessengerController::create
 * Coming Soon
 * PUT     /messages/:id  MessengerController::edit
 * DELETE  /messages/:id  MessengerController::delete
 */
dispatch('/messages',      'MessengerController::show');
dispatch_post('/messages', 'MessengerController::create');
//dispatch_put('/messages/:id', 'MessengerController::edit');
//dispatch_delete('/messages/:delete', 'MessengerController::delete');

?>
