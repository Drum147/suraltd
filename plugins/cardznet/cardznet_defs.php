<?php
/* 
Description: CardzNet Defines 
 
Copyright 2020 Malcolm Shergold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if (defined('CARDZNETLIB_TRACK_INCLUDES_FILE'))
{
	include CARDZNETLIB_TRACK_INCLUDES_FILE;
	trackIncludes(__FILE__);
}
	
if (!defined('CARDZNET_DEFS_INCLUDED'))
{
	define('CARDZNET_FILE_PATH', dirname(__FILE__).'/');
	
	if (!defined('WP_CONTENT_DIR'))
		define ('WP_CONTENT_DIR', dirname(dirname(CARDZNET_FILE_PATH)));
		
	if (!isset($siteurl)) $siteurl = get_option('siteurl');
	if (is_ssl())
	{
		$siteurl = str_replace('http://', 'https://', $siteurl);
		define('CARDZNET_URLROOT', 'https');
	}
	else
	{
		$siteurl = str_replace('https://', 'http://', $siteurl);
		define('CARDZNET_URLROOT', 'http');
	}

	define('CARDZNET_DEFS_INCLUDED', true);

	define('CARDZNET_PLUGIN_ID', 'cardznet');

	define('CARDZNET_OPTIONS_NAME', 'cardznetsettings');
	define('CARDZNET_DBGOPTIONS_NAME', 'cardznetdbgsettings');
	
	define('CARDZNET_PLUGINDESC', 'CardzNet');
		
	define('CARDZNET_ADMIN_PATH', CARDZNET_FILE_PATH . 'admin/');
	define('CARDZNET_INCLUDE_PATH', CARDZNET_FILE_PATH . 'include/');
	define('CARDZNET_CARDS_PATH', CARDZNET_FILE_PATH . 'cards/');	
	define('CARDZNET_GAMES_PATH', CARDZNET_FILE_PATH . 'games/');	
	define('CARDZNET_TEMPLATES_PATH', CARDZNET_FILE_PATH.'templates/');
	define('CARDZNET_ADMINICON_PATH', CARDZNET_ADMIN_PATH . 'images/');
	define('CARDZNET_TEST_PATH', CARDZNET_FILE_PATH . 'test/');

	define('CARDZNET_FOLDER', basename(CARDZNET_FILE_PATH));
	define('CARDZNET_URL', plugin_dir_url(__FILE__));
	define('CARDZNET_DOWNLOADS_URL', CARDZNET_URL . CARDZNET_FOLDER . '_download.php');
	define('CARDZNET_ADMIN_URL', CARDZNET_URL . 'admin/');
	define('CARDZNET_ADMIN_IMAGES_URL', CARDZNET_ADMIN_URL . 'images/');
	define('CARDZNET_GAMES_URL', CARDZNET_URL . 'games/');	
	define('CARDZNET_IMAGES_URL', CARDZNET_URL . 'images/');	

	define('CARDZNETLIB_INCLUDE_PATH', CARDZNET_INCLUDE_PATH);
	
	define('CARDZNET_UPLOADS_URL', WP_CONTENT_URL.'/uploads/'.CARDZNET_FOLDER.'/');
	
	if (!defined('CARDZNET_UPLOADS_PATH'))
	{
		define('CARDZNET_UPLOADS_PATH', WP_CONTENT_DIR.'/uploads/'.CARDZNET_FOLDER);				
		define('CARDZNETLIB_UPLOADS_PATH', CARDZNET_UPLOADS_PATH);				
		//define('CARDZNETLIB_UPLOADS_PATH', WP_CONTENT_DIR.'/plugins/'.CARDZNET_FOLDER.'/templates');						
	}

	if (!defined('CARDZNET_SHORTCODE'))
		define('CARDZNET_SHORTCODE', 'cardznet');
	
	define('CARDZNET_CODE_PREFIX', CARDZNET_PLUGIN_ID);
	define('CARDZNET_DOMAIN_NAME', CARDZNET_PLUGIN_ID);

	define('CARDZNET_DOMAIN_MSG_OK', CARDZNET_DOMAIN_NAME.'-ok');
	define('CARDZNET_DOMAIN_MSG_UPDATE', CARDZNET_DOMAIN_NAME.'-update');
	define('CARDZNET_DOMAIN_MSG_ERROR', CARDZNET_DOMAIN_NAME.'-error');

	define('CARDZNET_MENUPAGE_ADMINMENU', CARDZNET_CODE_PREFIX.'_adminmenu');
	define('CARDZNET_MENUPAGE_OVERVIEW', CARDZNET_CODE_PREFIX.'_overview');
	define('CARDZNET_MENUPAGE_GROUPS', CARDZNET_CODE_PREFIX.'_groups');
	define('CARDZNET_MENUPAGE_GAMES', CARDZNET_CODE_PREFIX.'_games');
	define('CARDZNET_MENUPAGE_TOOLS', CARDZNET_CODE_PREFIX.'_tools');
	define('CARDZNET_MENUPAGE_SETTINGS', CARDZNET_CODE_PREFIX.'_settings');
	define('CARDZNET_MENUPAGE_DEVTEST', CARDZNET_CODE_PREFIX.'_devtest');
	define('CARDZNET_MENUPAGE_DIAGNOSTICS', CARDZNET_CODE_PREFIX.'_diagnostics');
	define('CARDZNET_MENUPAGE_TESTSETTINGS', CARDZNET_CODE_PREFIX.'_testsettings');

	define('CARDZNET_PLUGIN_NAME', 'CardzNet');
	
	define('CARDZNET_CAPABILITY_PLAYER', 'cardznet_player');			// A user that can play a game
	define('CARDZNET_CAPABILITY_MANAGER', 'cardznet_manager');		// A user that can start a game and add players to a group
	define('CARDZNET_CAPABILITY_ADMINUSER', 'cardznet_admin');		// A user that can administer WPPlayCards
	define('CARDZNET_CAPABILITY_SETUPUSER', 'cardznet_setup');		// A user that can change WPPlayCards settings
	define('CARDZNET_CAPABILITY_DEVUSER', 'cardznet_testing');		// A user that can use test pages

	define('CARDZNETLIB_CAPABILITY_DEVUSER', CARDZNET_CAPABILITY_DEVUSER);

	if (!defined('CARDZNET_TABLE_ROOT'))
		define('CARDZNET_TABLE_ROOT', 'cardznet_');
	global $wpdb;
	define('CARDZNET_TABLE_PREFIX', $wpdb->prefix.CARDZNET_TABLE_ROOT);
	
	// Set the DB tables names
	define('CARDZNET_GROUPS_TABLE', CARDZNET_TABLE_PREFIX.'groups');	
	define('CARDZNET_INVITES_TABLE', CARDZNET_TABLE_PREFIX.'invites');
	define('CARDZNET_MEMBERS_TABLE', CARDZNET_TABLE_PREFIX.'members');
	define('CARDZNET_GAMES_TABLE', CARDZNET_TABLE_PREFIX.'games');
	define('CARDZNET_PLAYERS_TABLE', CARDZNET_TABLE_PREFIX.'players');
	define('CARDZNET_ROUNDS_TABLE', CARDZNET_TABLE_PREFIX.'rounds');
	define('CARDZNET_HANDS_TABLE', CARDZNET_TABLE_PREFIX.'hands');
	define('CARDZNET_TRICKS_TABLE', CARDZNET_TABLE_PREFIX.'tricks');
	define('CARDZNET_SETTINGS_TABLE', CARDZNET_TABLE_PREFIX.'settings');

	define('CARDZNET_VISIBLE_NORMAL', 'Normal');
	define('CARDZNET_VISIBLE_ALWAYS', 'AlwaysVisible');
	define('CARDZNET_VISIBLE_NEVER', 'NeverVisible');

	define('CARDZNET_CALLBACK_ID', 'cardznet_cb');
}
