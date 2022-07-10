<?php
/* 
Description: CardzNet Plugin Database Access functions
 
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

if (!class_exists('CardzNetLibDBaseClass')) 
	include CARDZNETLIB_INCLUDE_PATH.'cardznetlib_dbase_api.php';
	
include CARDZNETLIB_INCLUDE_PATH.'cardznetlib_logfile.php';
	
if (!class_exists('CardzNetDBaseClass')) 
{
	global $wpdb;
	
	$dbPrefix = $wpdb->prefix.'cardznet_';
	
	if (!defined('CARDZNET_TABLE_PREFIX'))
	{
		define('CARDZNET_TABLE_PREFIX', $dbPrefix);
	}
	
	if( !defined( 'CARDZNET_FILENAME_TEXTLEN' ) )
		define('CARDZNET_FILENAME_TEXTLEN', 80);
	
	define('CARDZNET_CAPABILITY_TEXTLEN', 32);			

	define('CARDZNET_MENUMODE_MAINMENU', 'MainMenu');	
	define('CARDZNET_MENUMODE_SUBMENU', 'SubMenu');	
	define('CARDZNET_MENUMODE_NONADMINMAINMENU', 'NonAdminMainMenu');	

	define('CARDZNET_FILENAME_COMMSLOG', 'CardzNet.log');
	
	define('CARDZNET_MIMICMODE_PLAYER', 'player');
	define('CARDZNET_MIMICMODE_DEALER', 'dealer');
	define('CARDZNET_MIMICMODE_ADMIN', 'admin');

	define('CARDZNET_MIMICVISIBILITY_ALWAYS', 'always');
	define('CARDZNET_MIMICVISIBILITY_WITHHAND', 'withcards');
	define('CARDZNET_MIMICVISIBILITY_NEVER', 'never');

	// Set the DB tables names
	class CardzNetDBaseClass extends CardzNetLibDBaseClass
	{
		var $adminOptions;
		var $errMsg;
    
    	var $gameId = 0;
    	var $roundId = 0;
    	
/*    	
    	var $thisPlayerId = 0;
    	var $thisPlayerName = 'TBD';
    	var $hideCardsOption = CARDZNET_VISIBLE_NORMAL;
*/
		var $thisPlayer;
		
    	var $nextPlayerId = 0;
    	
    	var $playersList = array();
    	var $playersPerUser = 0;
    	
    	var $isSeqMode = false;
    	
    	var $jsGlobals = array();
    	var $ajaxVars = array();
   	
   		const ROUND_READY = 'ready';
   		const ROUND_COMPLETE = 'complete';
   		
   		const GAME_INPROGRESS = 'in-progress';
   		const GAME_COMPLETE = 'complete';
   		const GAME_ENDED = 'ended';
   		
   		const CLEAR_CARDS = true;
   		const LEAVE_CARDS = false;
   		
		static function GameNameToFileAndClass($gameName)
		{
			$rslt = new stdClass();
			
			$gameName = ucwords($gameName);
			$classRoot = str_replace(' ', '', $gameName);
			$gameClass = "CardzNet".$classRoot."Class";
			
			$srcRoot = str_replace(' ', '_', $gameName);
			$gameRootName = "cardznet_".strtolower($srcRoot);
			
			$rslt->name = $gameName;			
			$rslt->class = $gameClass;
			$rslt->srcfile = $gameRootName.".php";

			return $rslt;
		}
		
		function __construct($caller) 
		{
			$opts = array (
				'Caller'             => $caller,
				'Domain'             => CARDZNET_FOLDER,
				'PluginFolder'       => CARDZNET_FOLDER,
				'CfgOptionsID'       => CARDZNET_OPTIONS_NAME,
				'DbgOptionsID'       => CARDZNET_DBGOPTIONS_NAME,				
			);	
					
			$this->emailObjClass = 'CardzNetLibHTMLEMailAPIClass';
			$this->emailClassFilePath = CARDZNETLIB_INCLUDE_PATH.'cardznetlib_htmlemail_api.php';   

			// Call base constructor
			parent::__construct($opts);
		}

		function Output_PluginHelp()
		{
			$timezone = get_option('timezone_string');
			if ($timezone == '')
			{
				$settingsPageURL = get_option('siteurl').'/wp-admin/options-general.php';
				$statusMsg = __('Timezone not set - Set it', $this->get_domain())." <a href=$settingsPageURL>".__('Here', $this->get_domain()).'</a>';
				echo '<div id="message" class="error"><p>'.$statusMsg.'</p></div>';
			}
			
			echo  '<strong>'.__('Plugin', $this->get_domain()).':</strong> '.$this->get_pluginName()."<br>\n";			
			echo  '<strong>'.__('Version', $this->get_domain()).':</strong> '.$this->get_version()."<br>\n";			
			echo  '<strong>'.__('Timezone', $this->get_domain()).':</strong> '.$timezone."<br>\n";			

			if (!$this->isDbgOptionSet('Dev_DisableTestMenus'))
				$this->ShowDebugModes();
		}
	
		function OutputDebugStart()
		{
			if (!isset($this->debugToLog))
				$this->debugToLog = $this->isDbgOptionSet('Dev_DebugToLog');
			if ($this->debugToLog) ob_start();
		}
		
		function OutputDebugEnd()
		{
			if ($this->debugToLog)
			{
				$debugOutput = ob_get_contents();
				ob_end_clean();
				if ($debugOutput != '')
				{
					$this->AddToStampedCommsLog($debugOutput);
					if (strpos($debugOutput, 'id="message"') !== false) 
						echo $debugOutput;
				}
			}
		}
		
		function SettingsOK()
		{
			if (isset($this->adminOptions['RefreshTime'])) return true;
			
			$text = __("Review and Save settings first", $this->get_domain());
			$linkText = __("here", $this->get_domain());
			self::GoToPageLink($text, $linkText, CARDZNET_MENUPAGE_SETTINGS);
			
			return false;
		}
		
		static function GoToPageLink($promptText, $linkText, $page, $echo=true)
		{
			$pageURL = CardzNetLibUtilsClass::GetPageBaseURL();
			$pageURL = add_query_arg('page', $page, $pageURL);

			$msg = "$promptText - <a href=\"$pageURL\">$linkText</a>";
			
			if ($echo) echo $msg;
			return $msg;
		}
		
		function ShowSQL($sql, $values = null)
		{			
			if (!$this->isDbgOptionSet('Dev_ShowSQL'))
			{
				return;		
			}
			
			$this->OutputDebugStart();
			//if (!$this->isDbgOptionSet('Dev_ShowCaller'))
			{
				ob_start();		
				debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);		
				$callStack = ob_get_contents();
				ob_end_clean();
				
				$callStack = preg_split('/#[0-9]+[ ]+/', $callStack, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
				$caller = explode("(", $callStack[2]);
				$caller = str_replace("->", "::", $caller[0]);
				echo "SQL Caller: $caller() \n";
			}
			
			parent::ShowSQL($sql, $values);
			$this->OutputDebugEnd();
		}
		
		function show_results($results)
		{
			if ( !$this->isDbgOptionSet('Dev_ShowSQL') 
			  && !$this->isDbgOptionSet('Dev_ShowDBOutput') )
			{
				return;
			}
			
			$this->OutputDebugStart();
			parent::show_results($results);
			$this->OutputDebugEnd();
		}
	    
		function query($sql)
		{
			if ( !$this->isDbgOptionSet('Dev_DebugToLog') 
			  || !$this->isDbgOptionSet('Dev_ShowDBOutput'))
			  	return parent::query($sql);
			
			$this->OutputDebugStart();
			$result = parent::query($sql);
			$this->OutputDebugEnd();
			
			return $result;
		}
    
	    function upgradeDB() 
	    {
			// Add DB Tables
			$this->createDB();
			
			// Add administrator capabilities
			$adminRole = get_role('administrator');
			if ( !empty($adminRole) ) 
			{
				$adminRole->add_cap(CARDZNET_CAPABILITY_PLAYER);				
				$adminRole->add_cap(CARDZNET_CAPABILITY_MANAGER);				
				$adminRole->add_cap(CARDZNET_CAPABILITY_ADMINUSER);
				$adminRole->add_cap(CARDZNET_CAPABILITY_SETUPUSER);
				$adminRole->add_cap(CARDZNET_CAPABILITY_DEVUSER);				
			}
			
			// Add subscriber capabilities
			$rolesList = array('subscriber', 'contributor', 'author', 'editor');
			foreach ($rolesList as $role)
			{
				$playerRole = get_role($role);
				if ( !empty($playerRole) ) 
				{
					$playerRole->add_cap(CARDZNET_CAPABILITY_PLAYER);				
				}
			}
			
			// Create directory for Ticker File
			if (!is_dir(CARDZNET_UPLOADS_PATH))
			{
				mkdir(CARDZNET_UPLOADS_PATH, CARDZNETLIB_PHPFOLDER_PERMS, true);
			}
			
			$defaultTemplatesPath = WP_CONTENT_DIR . '/plugins/' . CARDZNET_FOLDER . '/templates';
			$uploadsTemplatesPath = WP_CONTENT_DIR . '/uploads/'.CARDZNET_FOLDER;
			
			// FUNCTIONALITY: DBase - On upgrade ... Copy sales templates to working folder
			// Copy release templates to plugin persistent templates and images folders
			if (!CardzNetLibUtilsClass::recurse_copy($defaultTemplatesPath, $uploadsTemplatesPath))
			{				
			}
			
		}
		
		function getTableNames($dbPrefix)
		{
			$DBTables = new stdClass();
			
			$DBTables->Settings = CARDZNET_SETTINGS_TABLE;
			
			return $DBTables;
		}

		function getTableDef($tableName)
		{
			$sql = parent::getTableDef($tableName);
			switch($tableName)
			{
				case CARDZNET_GROUPS_TABLE:		
					$sql .= '
						groupName TEXT,
						groupUserId INT,
					';
					break;
				
				case CARDZNET_INVITES_TABLE:		
					$sql .= '
						inviteGroupId INT,
						inviteDateTime DATETIME NOT NULL,
						inviteFirstName TEXT,
						inviteLastName TEXT,
						inviteEMail TEXT,
						inviteHash TEXT,
					';
					break;
				
				case CARDZNET_MEMBERS_TABLE:		
					$sql .= '
						groupId INT,
						memberUserId INT,
					';
					break;
			
				case CARDZNET_GAMES_TABLE:		
					$sql .= '
						gameName VARCHAR(32),
						gameStartDateTime DATETIME NOT NULL,
						gameEndDateTime DATETIME,
						gameStatus VARCHAR(20) DEFAULT "'.self::GAME_INPROGRESS.'",
						gameLoginId INT,
						gameNoOfPlayers INT,
						gameCardsPerPlayer INT DEFAULT 0,
						gameCardFace VARCHAR(30) DEFAULT "Standard",
						firstPlayerId INT DEFAULT 0,
						nextPlayerId INT DEFAULT 0,
						gameMeta TEXT DEFAULT "",
						gameTicker INT DEFAULT 1,
						gameTickFilename TEXT,
					';
					break;
					
				case CARDZNET_PLAYERS_TABLE:
					$sql .= '
						gameId INT UNSIGNED NOT NULL,
						userId INT,
						playerName TEXT,
						playerColour TEXT DEFAULT "",
						score INT DEFAULT 0,
						hideCardsOption VARCHAR(20) DEFAULT "'.CARDZNET_VISIBLE_NORMAL.'",
					';
					break;
				
				case CARDZNET_ROUNDS_TABLE:		
					$sql .= '
						gameId INT UNSIGNED NOT NULL,
						roundStartDateTime DATETIME NOT NULL,
						roundEndDateTime DATETIME NOT NULL,
						roundDeck TEXT DEFAULT "",
						roundNextCard INT DEFAULT 0,
						roundState TEXT DEFAULT "",
					';
					break;
					
				case CARDZNET_HANDS_TABLE:		
					$sql .= '
						roundID INT UNSIGNED NOT NULL,
						playerId INT,
						noOfCards INT,
						cardsList TEXT,
						playedList TEXT DEFAULT "",
						handMeta TEXT DEFAULT "",
					';
					break;
					
				case CARDZNET_TRICKS_TABLE:		
					$sql .= '
						roundID INT UNSIGNED NOT NULL,
						playerId INT DEFAULT 0,
						playerOrder INT DEFAULT 0,
						cardsList TEXT,
						winnerId INT DEFAULT 0,
						complete BOOL DEFAULT FALSE,
						score INT DEFAULT 0,
					';
					break;
			}
							
			return $sql;
		}
		
		//Returns an array of admin options
		function getOptions($childOptions = array(), $saveToDB = true) 
		{
			$ourOptions = array(
				'Unused_EndOfList' => ''
			);
				
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			return parent::getOptions($ourOptions);
		}
		
		function setDefault($optionID, $optionValue, $optionClass = self::ADMIN_SETTING)
		{
			$currVal = $this->getOption($optionID, $optionClass);
			if ($currVal == '')
				$this->setOption($optionID, $optionValue, $optionClass);
				
			return ($currVal == '');
		}
		
		function clearAll()
		{
		}
		
		function createDB($dropTable = false)
		{
			$this->createDBTable(CARDZNET_GROUPS_TABLE, 'groupId', $dropTable);			
			$this->createDBTable(CARDZNET_INVITES_TABLE, 'inviteId', $dropTable);
			$this->createDBTable(CARDZNET_MEMBERS_TABLE, 'memberId', $dropTable);
			$this->createDBTable(CARDZNET_GAMES_TABLE, 'gameId', $dropTable);
			$this->createDBTable(CARDZNET_PLAYERS_TABLE, 'playerId', $dropTable);			
			$this->createDBTable(CARDZNET_ROUNDS_TABLE, 'roundId',  $dropTable);
			$this->createDBTable(CARDZNET_HANDS_TABLE, 'handId',  $dropTable);
			$this->createDBTable(CARDZNET_TRICKS_TABLE, 'trickId',  $dropTable);			
		}
		
		function GetAllSettingsList()
		{			
			$settingsList = parent::GetAllSettingsList();

			return $settingsList;
		}
		
	    function uninstall()
	    {
			$this->DeleteCapability(CARDZNET_CAPABILITY_ADMINUSER);
			$this->DeleteCapability(CARDZNET_CAPABILITY_SETUPUSER);
			
			parent::uninstall();
		}
			 
		function resetDB()
		{
			$this->createDB(true);
		}
				
		function GetUsersCount()
		{
			$members = get_users();
			return count($members);
		}
				
		function GetOurUserId($atts)
		{
			$userId = 0;
			if (current_user_can(CARDZNETLIB_CAPABILITY_SYSADMIN))
			{
				if (isset($atts['login']))
				{
					$userDetails = get_user_by('login', $atts['login']);
					if ($userDetails === false) exit;
					$userId = $userDetails->data->ID;
				}
				else if (isset($atts['userId']))
				{
					$userId = $atts['userId'];
				}
			}

			if ($userId == 0)
			{
				$user = wp_get_current_user();
				$userId = $user->ID;				
			}
			
			return $userId;
		}
		
		function SetSeqMode($atts, $seqMode=true)
		{
			if (!isset($atts['mode'])) 
			{
				return "SetSeqMode - No mode in atts";
			}
			
			if ($atts['mode'] != 'seq')  
			{
				return "SetSeqMode - Mode is not seq";
			}
			
			if (!current_user_can(CARDZNETLIB_CAPABILITY_SYSADMIN))  
			{
				return "SetSeqMode - No sysadmin perm";
			}
			
			$this->isSeqMode = $seqMode;
			return 'SetSeqMode Set: '.($seqMode ? 'TRUE' : 'FALSE');
		}
		
		function GetHiddenInputTag($tagId, $tagValue)
		{
			return "<input type=hidden name=$tagId id=$tagId value='$tagValue'/>";
		}
		
		function AddToAJAXVars($varId, $value, $addToJSVars = false)
		{
			// Add to hidden values list (can be overwritten by another call)
			$this->ajaxVars[$varId] = $value;
			
			if ($addToJSVars)
			{
				$this->AddToJSGlobals($varId, $value);			
			}
			
			return $value;
		}
		
		function AddBoolToAJAXVars($varId, $value, $addToJSVars = false)
		{
			$boolvalue = $value ? 'true' : 'false';
			return $this->AddToAJAXVars($varId, $boolvalue, $addToJSVars);
		}
		
		function AJAXVarsTags()
		{
			$html = '';
			foreach ($this->ajaxVars as $ajaxVarId => $ajaxVarVal)
			{
				if (!is_array($ajaxVarVal))
				{
					$html .= $this->AJAXVarTag($ajaxVarId, $ajaxVarVal);
					continue;
				}

				foreach ($ajaxVarVal as $ajaxArrayId => $ajaxArrayVal)
				{
					$html .= $this->AJAXVarTag($ajaxVarId.'-'.$ajaxArrayId, $ajaxArrayVal);
				}
			}
			return $html;
		}
		
		function AJAXVarTag($ajaxVarId, $ajaxVarVal)
		{
			return $this->GetHiddenInputTag('AJAX-'.$ajaxVarId, $ajaxVarVal)."\n";
		}
		
		function AddToJSGlobals($varId, $value)
		{
			// Add to globals list (can be overwritten by another call)
			$this->jsGlobals[$varId] = $value;
			
			return $value;
		}
		
		function JSGlobals()
		{
			if (count($this->jsGlobals) == 0) return "<!-- No JSGlobals -->\n";
			
			$jsCode = "<script>\n";
			foreach ($this->jsGlobals as $varId => $value)
			{
				$jsCode .= "var $varId = $value;\n";
			}
			$jsCode .= "</script>\n";
			
			return $jsCode;
		}
		
		function LockTables($tablesList)
		{
			if (!is_array($tablesList))
				$tablesList = array($tablesList);
				
			$sql = '';
			foreach ($tablesList as $tableName)				
			{
				if ($sql != '') $sql .= ", ";
				$sql .= "$tableName WRITE";
			}
			
			$sql = 'LOCK TABLES '.$sql;
			$this->query($sql);
		}
		
		function UnLockTables()
		{
			$sql  = 'UNLOCK TABLES';
			$this->query($sql);
		}
		
		function GetGroupById($groupId = 0)
		{
			$sql  = 'SELECT * FROM '.CARDZNET_GROUPS_TABLE.' ';
//			$sql .= 'LEFT JOIN '.CARDZNET_MEMBERS_TABLE.' ON '.CARDZNET_MEMBERS_TABLE.'.groupId='.CARDZNET_GROUPS_TABLE.'.groupId ';
			if ($groupId != 0)
				$sql .= 'WHERE '.CARDZNET_GROUPS_TABLE.'.groupId='.$groupId.' ';
			
			$results = $this->get_results($sql);
			
			return $results;
		}
		
		function GetGroupsCount($userId = 0)
		{
			$sql  = 'SELECT COUNT(groupUserId) as groupsCount FROM '.CARDZNET_GROUPS_TABLE.' ';
			if ($userId != 0)
				$sql .= 'WHERE '.CARDZNET_GROUPS_TABLE.'.groupUserId='.$userId.' ';
			$results = $this->get_results($sql);
			
			return (count($results) == 0) ? 0 : $results[0]->groupsCount;
		}
		
		function GetGroups($groupUserId = 0, $sqlFilters = array())
		{
			// Get the list of groups
			$sql  = 'SELECT '.CARDZNET_GROUPS_TABLE.'.*, COUNT(memberUserId) as noOfMembers FROM '.CARDZNET_GROUPS_TABLE.' ';
			$sql .= 'LEFT JOIN '.CARDZNET_MEMBERS_TABLE.' ON '.CARDZNET_MEMBERS_TABLE.'.groupId='.CARDZNET_GROUPS_TABLE.'.groupId ';
			if ($groupUserId != 0)
				$sql .= 'WHERE '.CARDZNET_GROUPS_TABLE.'.groupUserId='.$groupUserId.' ';
			$sql .= 'GROUP BY '.CARDZNET_GROUPS_TABLE.'.groupId ';
			if (isset($sqlFilters['sqlLimit']))
				$sql .= $sqlFilters['sqlLimit'];
			
			$results = $this->get_results($sql);
			
			return $results;
		}

		function GroupExists($groupName)
		{
			$sql  = 'SELECT * FROM '.CARDZNET_GROUPS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_GROUPS_TABLE.'.groupName="'.$groupName.'" ';
			
			$results = $this->get_results($sql);
			
			return (count($results) > 0);
		}

		function GetDefaultGroupName()
		{
			$groupName = '';
			for ($groupNumber = 1;; $groupNumber++)
			{
				$groupName = "Group $groupNumber";
				if (!$this->GroupExists($groupName))
					break;
			}
			
			return $groupName;
		}
		
		function AddGroup($groupName, $groupUserId = 0)
		{
			if ($groupUserId == 0)
				$groupUserId = get_current_user_id();
					
			$sql  = 'INSERT INTO '.CARDZNET_GROUPS_TABLE.'(groupName, groupUserId) ';
			$sql .= 'VALUES("'.$groupName.'", "'.$groupUserId.'")';
			$this->query($sql);	
					
     		$groupId = $this->GetInsertId();
      		return $groupId;
		}

		function GetMembersById($groupId = 0, $userId = 0)
		{
			$sql  = 'SELECT * FROM '.CARDZNET_GROUPS_TABLE.' ';
			$sql .= ' JOIN '.CARDZNET_MEMBERS_TABLE.' ON '.CARDZNET_MEMBERS_TABLE.'.groupId='.CARDZNET_GROUPS_TABLE.'.groupId ';
			if ($groupId != 0)
			{
				$sql .= 'WHERE '.CARDZNET_GROUPS_TABLE.'.groupId='.$groupId.' ';
				if ($userId != 0)
					$sql .= 'AND '.CARDZNET_MEMBERS_TABLE.'.memberUserId='.$userId.' ';
			}
			
			$results = $this->get_results($sql);
			
			return $results;
		}
		
		function PurgeInvitations()
		{
			$sql  = 'DELETE FROM '.CARDZNET_INVITES_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_INVITES_TABLE.'.inviteDateTime < %s ';
					
			$inviteLimitHours = $this->getOption('inviteTimeLimit');

			$limitDateTime = date(self::MYSQL_DATETIME_FORMAT, strtotime("-$inviteLimitHours hours"));
			$results = $this->queryWithPrepare($sql, array($limitDateTime));
			
			return $results;
		}
		
		function GetInvitationByGroupId($groupId)
		{
			$this->PurgeInvitations();
			
			$sql  = 'SELECT * FROM '.CARDZNET_GROUPS_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_INVITES_TABLE.' ON '.CARDZNET_GROUPS_TABLE.'.groupId='.CARDZNET_INVITES_TABLE.'.inviteGroupId ';
			$sql .= 'WHERE '.CARDZNET_GROUPS_TABLE.'.groupId=%d ';
			
			$results = $this->getresultsWithPrepare($sql, array($groupId));
			
			return $results;
		}
		
		function GetInvitationById($inviteId)
		{
			$this->PurgeInvitations();
			
			$sql  = 'SELECT * FROM '.CARDZNET_INVITES_TABLE.' ';
			$sql .= 'LEFT JOIN '.CARDZNET_GROUPS_TABLE.' ON '.CARDZNET_GROUPS_TABLE.'.groupId='.CARDZNET_INVITES_TABLE.'.inviteGroupId ';
			$sql .= 'WHERE '.CARDZNET_INVITES_TABLE.'.inviteId=%d ';
			
			$results = $this->getresultsWithPrepare($sql, array($inviteId));
			
			return $results;
		}
		
		function GetInvitationByAuth($auth)
		{
			$this->PurgeInvitations();
			
			$sql  = 'SELECT * FROM '.CARDZNET_INVITES_TABLE.' ';
			$sql .= 'LEFT JOIN '.CARDZNET_GROUPS_TABLE.' ON '.CARDZNET_GROUPS_TABLE.'.groupId='.CARDZNET_INVITES_TABLE.'.inviteGroupId ';
			$sql .= 'WHERE '.CARDZNET_INVITES_TABLE.'.inviteHash=%s ';
			
			$results = $this->getresultsWithPrepare($sql, array($auth));
			
			return $results;
		}
		
		function DeleteInvitationByAuth($auth)
		{
			$this->PurgeInvitations();
			
			$sql  = 'DELETE FROM '.CARDZNET_INVITES_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_INVITES_TABLE.'.inviteHash=%s ';
			
			$results = $this->queryWithPrepare($sql, array($auth));
			
			return $results;
		}
		
		function AddInvitation($inviteFirstName, $inviteLastName, $inviteEMail, $inviteGroupId)
		{
			$this->PurgeInvitations();
			
			$inviteDateTime = date(CardzNetLibDBaseClass::MYSQL_DATETIME_FORMAT);
			$inviteHash = md5(date('r', time())); // "HashTBD-$inviteDateTime";
			
			$sql  = 'INSERT INTO '.CARDZNET_INVITES_TABLE.'(inviteGroupId, inviteDateTime, inviteFirstName, inviteLastName, inviteEMail, inviteHash) ';
			$sql .= 'VALUES(%d, %s, %s, %s, %s, %s)';
			$this->queryWithPrepare($sql, array($inviteGroupId, $inviteDateTime, $inviteFirstName, $inviteLastName, $inviteEMail, $inviteHash));	
			
			$inviteId = $this->GetInsertId();
			
     		return $inviteId;
		}
		
		function AddMemberToGroup($groupId, $memberId)
		{		
			$sql  = 'INSERT INTO '.CARDZNET_MEMBERS_TABLE.'(groupId,	memberUserId) ';
			$sql .= 'VALUES("'.$groupId.'", "'.$memberId.'")';
			$this->query($sql);	
			
     		return $groupId;
		}
		
		function RemoveMemberFromGroup($groupId, $memberId)
		{		
			$sql  = 'DELETE FROM '.CARDZNET_MEMBERS_TABLE.' ';
			$sql .= 'WHERE groupId='.$groupId.' ';
			$sql .= 'AND memberId='.$memberId.' ';
			return $this->query($sql);				
		}
		
		function DeleteGroup($groupId)
		{
			$sql  = 'DELETE FROM '.CARDZNET_GROUPS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_GROUPS_TABLE.'.groupId='.$groupId.' ';
			$this->query($sql);	
			
			$this->PurgeDB(true);
		}

		function GetGameDef($atts)
		{			
			$userId = $this->GetOurUserId($atts);
			
			$results = $this->GetActiveGames($userId);
			if (count($results) != 1) 
			{
				unset($this->game);
				return null;
			}

			$this->game = $results[0];
			
			$gameName = $results[0]->gameName;	
					
			$def = $this->GameNameToFileAndClass($gameName);			
			$def->gameCardFace = $results[0]->gameCardFace;

			return $def;
		}
		
		function GetNoOfCardsDealt()
		{
			return ($this->game->gameNoOfPlayers * $this->game->gameCardsPerPlayer);
		}
		
		function GetActiveGames($userId = 0, $limit = 1)
		{			
			// Get the gameId, roundId and nextPlayerId
			// Note: This uses the roundId to get the most recent game 
			$sql  = 'SELECT '.CARDZNET_GAMES_TABLE.'.* ';
			$sql .= ', roundId, roundState ';
			$sql .= 'FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'LEFT JOIN '.CARDZNET_ROUNDS_TABLE.' ON '.CARDZNET_ROUNDS_TABLE.'.gameId='.CARDZNET_GAMES_TABLE.'.gameId ';;
			if ($userId > 0) $sql .= 'LEFT JOIN '.CARDZNET_PLAYERS_TABLE.' ON '.CARDZNET_PLAYERS_TABLE.'.gameId='.CARDZNET_GAMES_TABLE.'.gameId ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameTicker > 0 ';
			if ($userId > 0) $sql .= 'AND '.CARDZNET_PLAYERS_TABLE.'.userId='.$userId.' ';
			$sql .= 'ORDER BY '.CARDZNET_PLAYERS_TABLE.'.gameId DESC,roundId DESC ';
			if ($limit > 0) $sql .= "LIMIT $limit ";
			
			$results = $this->get_results($sql);
			
			return $results;
		}
		
		function GetGameByUser($userId, $atts)
		{			
			$this->SetSeqMode($atts);
				
			$this->OutputDebugMessage("DB Initialise - userId: $userId <br>\n");
			
			$this->userId = $userId;
			$this->atts = $atts;
			$this->AddToAJAXVars('atts', $atts);
/*			
			$results = $this->GetActiveGames($userId);
			if (count($results) != 1) return null;
			
			$result = $results[0];
*/
			$result = $this->game;
						
			$this->roundId = $result->roundId;
			$this->roundState = $result->roundState;
			$this->firstPlayerId = $result->firstPlayerId;
			$this->nextPlayerId = $result->nextPlayerId;
			
			$this->gameId = $this->AddToAJAXVars('gameId', $result->gameId);
			$this->gameTicker = $this->AddToAJAXVars('gameTicker', $result->gameTicker);

			$this->AddToAJAXVars('gameName', $result->gameName);

			$this->cardsPerPlayer = $result->gameCardsPerPlayer;
			
			$gameMeta = '';
			if ($result->gameMeta != '')
				$gameMeta = unserialize($result->gameMeta);
			$this->gameMeta = $gameMeta;

			$this->AddBoolToAJAXVars('isSeqMode', $this->isSeqMode);	// AddToAJAXVars
			
			return $this->gameId;
		}
			
		function CardsVisibleOptions($index, $currSetting)
		{
			if (!current_user_can(CARDZNETLIB_CAPABILITY_DEVUSER)) return '';

			$options = array(
				CARDZNET_VISIBLE_NORMAL => __('Normal', $this->get_domain()), 
				CARDZNET_VISIBLE_ALWAYS => __('Always Visible', $this->get_domain()), 
				CARDZNET_VISIBLE_NEVER  => __('Never Visible ', $this->get_domain()), 
				);

			$html  = "<select name=cardsVisibleOption_$index>\n";
			foreach ($options as $optionValue => $optionText)
			{
				$selected = ($optionValue === $currSetting) ? ' selected="" ' : '';
				$html .= "<option value=\"$optionValue\" $selected>$optionText</option>\n";
			}
			$html .= "</select>\n";
			
			return $html;
		}
		
		function GetPlayersList()
		{			
			$this->playersList = array();
			
			// Get the players list for this round in the order of play
			$sql  = 'SELECT * FROM '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_GAMES_TABLE.' ON '.CARDZNET_GAMES_TABLE.'.gameId='.CARDZNET_PLAYERS_TABLE.'.gameId ';;
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$this->gameId.' ';
			$sql .= 'ORDER BY '.CARDZNET_PLAYERS_TABLE.'.playerId ';
			$results = $this->get_results($sql);
			$noOfPlayers = count($results);
			if ($noOfPlayers == 0) return null;
			
			foreach ($results as $index => $result)
			{
				$player = new stdClass();
				$player->index = count($this->playersList);
				$player->Id = $result->playerId;
				$player->userId = $result->userId;
				$player->name = $result->playerName;
				$player->colour = $result->playerColour;
				$player->ready = true;
				$player->isActive = false;
				$player->isFirstPlayer = ($result->playerId == $result->firstPlayerId);
				$player->hideCardsOption = $result->hideCardsOption;
				
				$this->playersList[] = $player;
			}
			
			return $noOfPlayers;
		}
		
		function GetPlayersReadyStatus($readySql = 'true')
		{
			// NOTE: Could Allow different number of passed cards 
			$sql  = 'SELECT '.CARDZNET_PLAYERS_TABLE.'.*, ';
			$sql .= 'roundState, ';
			$sql  = 'SELECT *, ';
			$sql .= '(roundState = "'.self::ROUND_READY.'") AS roundReady, ';
			$sql .= '(noOfCards = gameCardsPerPlayer) AS fullDeck, ';
			$sql .= '((roundState = "'.self::ROUND_READY.'") || '.$readySql.') AS ready ';
			$sql .= 'FROM '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_GAMES_TABLE.' ON '.CARDZNET_GAMES_TABLE.'.gameId='.CARDZNET_PLAYERS_TABLE.'.gameId ';;
			$sql .= 'LEFT JOIN '.CARDZNET_ROUNDS_TABLE.' ON '.CARDZNET_ROUNDS_TABLE.'.gameId='.CARDZNET_GAMES_TABLE.'.gameId ';;
			$sql .= 'LEFT JOIN '.CARDZNET_HANDS_TABLE.' ON '.CARDZNET_HANDS_TABLE.'.roundId='.CARDZNET_ROUNDS_TABLE.'.roundId ';	
			$sql .= 'AND '.CARDZNET_HANDS_TABLE.'.playerId='.CARDZNET_PLAYERS_TABLE.'.playerId ';
			$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$this->roundId.' ';
			$sql .= 'ORDER BY '.CARDZNET_PLAYERS_TABLE.'.playerId ';
			
			$results = $this->get_results($sql);
			if (count($results) == 0) return null;
			
			return $results;
			
		}
		
		function GetNoOfPlayers()
		{
			return count($this->playersList);
		}
		
		function GetPlayerObject($playerId)
		{
			$index = $this->GetPlayerIndex($playerId);
			if ($index === null) return null;
			
			return $this->playersList[$index];
		}
		
		function GetPlayerIndex($playerId)
		{
			foreach ($this->playersList as $index => $player)
			{
				if ($player->Id == $playerId)
				{
					return $index;
				}
			}

			return null;
		}

		function SetPlayerReady($playerId, $ready)
		{
			$index = $this->GetPlayerIndex($playerId);
			$player = $this->playersList[$index];
			$player->ready = $ready;
			$player->isActive = $ready;
		}

		function FindCurrentPlayer()
		{
			$userId = $this->userId;
			
			$matchingUsers = 0;		

			$nextPlayerIndex = $this->GetPlayerIndex($this->nextPlayerId);	
			if ($nextPlayerIndex === null) $this->dbError('FindCurrentPlayer', 'No Match for Next Player', null);
			
			// Find the first player that matches (in case there are none ready)
			foreach ($this->playersList as $index => $player)
			{
				if ($userId == $player->userId)
				{
					if (!$this->isSeqMode) $thisPlayerIndex = $index;
					$matchingUsers++;
				}
			}
			if ($matchingUsers == 0) $this->dbError('FindCurrentPlayer', 'No Matching Users', null);
			
			$noOfPlayers = count($this->playersList);
			
			// If the next player matches this players UserID then they are the current player	
			if ($this->isSeqMode)
			{
				$thisPlayerIndex = $nextPlayerIndex;
				for ($loop=0; $loop<$noOfPlayers; $loop++, $thisPlayerIndex++)
				{
					if ($thisPlayerIndex >= $noOfPlayers) $thisPlayerIndex = 0;
					
					$player = $this->playersList[$thisPlayerIndex];
					if ($player->ready) break;
				}
				$this->userId = $userId = $player->userId;
			}
			else
			{
				$this->playersPerUser = 0;
				foreach ($this->playersList as $index => $player)
				{
					if ($userId == $player->userId)
					{
						$this->playersPerUser++;
					}
				}
				
				if ($this->playersPerUser > 1)
				{
					// Multiple players on the same screen ... find the next one to play
					$key = $nextPlayerIndex;
					for ($loop=0; $loop<$noOfPlayers; $loop++, $key++)
					{
						if ($key >= $noOfPlayers) $key = 0;
						
						$player = $this->playersList[$key];
						if (($player->userId == $userId) && ($player->ready))
						{
							$thisPlayerIndex = $key;
							break;
						}
					}
				}
				
			}

			for ($index=0; $index<count($this->playersList); $index++)
				$this->playersList[$index]->isActive = false;
			$this->playersList[$nextPlayerIndex]->isActive = true;
			
			$this->thisPlayer = $this->playersList[$thisPlayerIndex];
			
			$this->AddToAJAXVars('thisPlayerName', $this->thisPlayer->name);
			
			return true;
		}
		
		function GetPlayerId()
		{	
			return $this->thisPlayer->Id;
		}
		
		function IsNextPlayer()
		{	
			return ($this->nextPlayerId == $this->thisPlayer->Id);		
		}
			
		function GetNextPlayerName()
		{
			$player = $this->GetPlayerObject($this->nextPlayerId);
			return $player->name;
		}
		
		function IsPlayerReady()
		{	
			$player = $this->playersList[$this->thisPlayer->index];
			return ($player->ready);		
		}
			
		function GetFollowingPlayerName($offset = 1)
		{
			$noOfPlayers = count($this->playersList);
			while ($offset >= $noOfPlayers) $offset -= $noOfPlayers;
			
			$selIndex = $this->thisPlayer->index + $offset;
			if ($selIndex >= $noOfPlayers)
				$selIndex -= $noOfPlayers;
				
			$player = $this->playersList[$selIndex];
			return $player->name;
		}
		
		function CanShowCards()
		{
			switch ($this->thisPlayer->hideCardsOption)	
			{
				case CARDZNET_VISIBLE_ALWAYS:
					$canShow = true;
					$this->OutputDebugMessage("CanShowCards TRUE - hideCardsOption is ALWAYS <br>\n");
					break;
					
				case CARDZNET_VISIBLE_NEVER:
					$canShow = false;
					$this->OutputDebugMessage("CanShowCards FALSE - hideCardsOption is NEVER <br>\n");
					break;
					
				case CARDZNET_VISIBLE_NORMAL:
				default:
					if ($this->playersPerUser <= 1)
					{
						$canShow = true;
					}
					else if (CardzNetLibUtilsClass::GetHTTPInteger('post', 'playerId') != $this->thisPlayer->Id)
					{
						$canShow = false;
					}
					else if (CardzNetLibUtilsClass::IsElementSet('post', 'cardsVisible'))
					{
						$canShow = true;
					}
					else
					{
						$canShow = false;
					}
					break;
			}
					
			return $canShow;
		}
			
		function GetCardFacesList()
		{
			if (isset($this->cardFacesList))
				return $this->cardFacesList;
				
			$this->cardFacesList = array();
			$cardFaceFilePath = CARDZNET_CARDS_PATH.'*';
			$cardFacePaths = glob( $cardFaceFilePath );
			foreach ($cardFacePaths as $cardFacePath)
			{
				$this->cardFacesList[] = basename($cardFacePath);
			}

			return $this->cardFacesList;
		}
			
		function GetCardFaceSelector($lastCardFace = '')
		{
			if ($lastCardFace == '')
				$lastCardFace = 'Standard';
				
			$cardFaces = $this->GetCardFacesList();

			$html  = "<select id=gameCardFace name=gameCardFace>\n";
			foreach ($cardFaces as $cardFace)
			{
				$selected = ($cardFace == $lastCardFace) ? ' selected=""' : '';
				$html .= '<option value="'.$cardFace.'"'.$selected.'>'.$cardFace.'&nbsp;&nbsp;</option>'."\n";
			}
			$html .= "</select>\n";
			return $html;
		}
		
		function GetGamesList()
		{
			if (isset($this->gamesList))
				return $this->gamesList;
				
			$this->gamesList = array();
			
			$gameFilePath = CARDZNET_GAMES_PATH.'cardznet_*.php';
			$gamePaths = glob( $gameFilePath );

			foreach ($gamePaths as $gamePath)			
			{
				$gameEntry = new stdClass();
				
				$gameEntry->filepath = $gamePath;
				$gameEntry->filename = basename($gamePath);
				$gameBasename = str_replace('.php', '', $gameEntry->filename);
				$gameBasename = str_replace('cardznet_', '', $gameBasename);
				$gameName = ucwords(str_replace('_', ' ', $gameBasename));			
				$gameEntry->name = $gameName;
				$gameClassBase = str_replace(' ', '', $gameName);
				$gameEntry->class = 'CardzNet'.$gameClassBase.'Class';
				
				$this->gamesList[$gameName] = $gameEntry;
			}
			
			return $this->gamesList;
		}
		
		function GetGamesCount($userId = 0)
		{			
			$sql  = 'SELECT COUNT(gameLoginId) as gamesCount FROM '.CARDZNET_GAMES_TABLE.' ';
			if ($userId != 0)
				$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameLoginId='.$userId.' ';
			$results = $this->get_results($sql);
			
			return (count($results) == 0) ? 0 : $results[0]->gamesCount;
		}
		
		function GetGames($userId = 0, $sqlFilters = array())
		{			
			$sql  = 'SELECT * FROM '.CARDZNET_GAMES_TABLE.' ';
			if ($userId != 0)
				$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameLoginId='.$userId.' ';
			$sql .= 'ORDER BY '.CARDZNET_GAMES_TABLE.'.gameStartDateTime DESC ';

			if (isset($sqlFilters['sqlLimit']))
				$sql .= $sqlFilters['sqlLimit'];

			$results = $this->get_results($sql);
			return $results;
		}
				
		function GetLastGameName($userId)
		{
			$sqlFilters['sqlLimit'] = 'LIMIT 0,1';
			$results = $this->GetGames($userId, $sqlFilters);
			
			return (count($results) > 0) ? $results[0]->gameName : '';
		}
		
		function GetGameById($gameId)
		{			
			$sql  = 'SELECT * FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_PLAYERS_TABLE.' ON '.CARDZNET_GAMES_TABLE.'.gameId='.CARDZNET_PLAYERS_TABLE.'.gameId ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			$results = $this->get_results($sql);
			return $results;
		}
			
		function GetGameStatus($gameId)
		{
			$sql  = 'SELECT gameStatus FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			$results = $this->get_results($sql);
			
			if (count($results) == 0) return '';
			
			return $results[0]->gameStatus;
		}
		
		function AddGame($gameName, $gamePlayersList, $gameCardsPerPlayer, $gameCardFace='', $gameMeta='')
		{			
			$this->gameId = 0;
			
			$gameFirstPlayerIndex = 0;
			
			$gameStartDateTime = date(CardzNetLibDBaseClass::MYSQL_DATETIME_FORMAT);
			$gameNoOfPlayers = count($gamePlayersList);

			$this->cardsPerPlayer = $gameCardsPerPlayer;
			$this->gameMeta = $gameMeta;
			
			if ($gameMeta != '')
				$gameMeta = serialize($gameMeta);
			
			$user = wp_get_current_user();
			$gameLoginId = $user->ID;
						
			$sql  = 'INSERT INTO '.CARDZNET_GAMES_TABLE.'(gameName, gameStartDateTime, gameLoginId, gameNoOfPlayers, gameCardsPerPlayer, gameMeta) ';
			$sql .= 'VALUES("'.$gameName.'", "'.$gameStartDateTime.'", "'.$gameLoginId.'", "'.$gameNoOfPlayers.'", "'.$gameCardsPerPlayer.'", "'.$gameMeta.'")';
			$this->query($sql);	
					
     		$gameId = $this->GetInsertId();
			$rand = str_pad(rand(0, pow(10, 4)-1), 4, '0', STR_PAD_LEFT);
			$gameTickFilename = "tick_{$gameId}_{$rand}.txt";
			
			$gameTicker = $this->gameTicker = 1;
			
			$this->LockTables(CARDZNET_GAMES_TABLE);
			
			$sql  = 'UPDATE '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'SET gameTickFilename="'.$gameTickFilename.'" ';
			$sql .= ', gameTicker="'.$gameTicker.'" ';
			if ($gameCardFace != '')
				$sql .= ', gameCardFace="'.$gameCardFace.'" ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			$this->query($sql);	
			     		
			$this->UpdateTickPage($gameTicker, $gameId);
			
			$this->UnLockTables();
			
     		$this->playersList = array();
     		
     		$userIdsList = array();
     		foreach ($gamePlayersList as $index => $playerEntry)
			{
				if (isset($playerEntry['login']))
				{
					$userDetails = get_user_by('login', $playerEntry['login']);
					$userId = $userDetails->data->ID;
					$gamePlayersList[$index]['id'] = $userId;
				}
				else
					$userId = $playerEntry['id'];
				$userIdsList[$userId] = $userId;
			}
			
			$GamesEnded = $this->EndGameByUsers($userIdsList);
			// NOTE: Could echo '$GamesEnded = '."$GamesEnded <br>\n";
			
     		foreach ($gamePlayersList as $index => $playerEntry)
			{
				$userId = $playerEntry['id'];
				if (isset($playerEntry['name']))
					$name = $playerEntry['name'];
				else
					$name = self::GetUserName($userId);

				$vis = CARDZNET_VISIBLE_NORMAL;
				if (isset($playerEntry['visibility']))
				{
					$vis = $playerEntry['visibility'];
				}
				
				$sql  = 'INSERT INTO '.CARDZNET_PLAYERS_TABLE.'(gameId, userId, playerName, hideCardsOption) ';
				$sql .= 'VALUES("'.$gameId.'", "'.$userId.'", "'.$name.'", "'.$vis.'")';
				$this->query($sql);	
				
				$playerId = $this->GetInsertId();
				
				if (isset($playerEntry['first']))
				{
					$gameFirstPlayerIndex = $playerId;							
				}
				
				$player = new stdClass();
				$player->index = count($this->playersList);
				$player->Id = $playerId;
				$player->name = $name;
				$player->userId = $userId;
				$player->ready = true;
				$player->isActive = false;
				$player->hideCardsOption = CARDZNET_VISIBLE_NEVER;
				
				$this->playersList[] = $player;
			}
			
			if ($gameFirstPlayerIndex != 0)
			{
				$sql  = 'UPDATE '.CARDZNET_GAMES_TABLE.' ';
				$sql .= 'SET firstPlayerId="'.$gameFirstPlayerIndex.'"';
				$sql .= ', nextPlayerId="'.$gameFirstPlayerIndex.'" ';
				$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
				
				$this->query($sql);	
			}
			
			$this->gameId = $gameId;
			return '';
		}
			
		function GetGameOptions()
		{
			return $this->gameMeta; // unserialize
		}
		
		function UpdateGameOptions($gameMeta)
		{
			$ser_gameMeta = serialize($gameMeta);
			
			$sql  = 'UPDATE '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'SET gameMeta=%s ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId=%d ';
			
			$results = $this->queryWithPrepare($sql, array($ser_gameMeta, $this->gameId));
			
			return $results;
		}
			
		function GetLastGame($gameName)
		{
			$user = wp_get_current_user();
			
			$sql  = 'SELECT * FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'WHERE gameLoginId='.$user->ID.' ';
			$sql .= 'AND gameName="'.$gameName.'" ';
			$sql .= 'ORDER BY gameId DESC ';
			$sql .= 'LIMIT 1 ';
			$results = $this->get_results($sql);
			if (count($results) == 0) return array();
			
			$gameId = $results[0]->gameId;
			
			$sql  = 'SELECT * FROM '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_GAMES_TABLE.' ON '.CARDZNET_GAMES_TABLE.'.gameId='.CARDZNET_PLAYERS_TABLE.'.gameId ';;
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			$results = $this->get_results($sql);
			
			return $results;
		}
		
		function DeleteGame($gameId)
		{			
			// Delete tick page when game complete
			$tickPagePath = $this->GetTickFilename($gameId);
			CardzNetLibUtilsClass::DeleteFile($tickPagePath);
			
			$sql  = 'DELETE FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			$this->query($sql);	
												
			$this->PurgeDB(true);
		}
		
		function EndGameByUsers($userIds)
		{
			$gamesCount = 0;
			foreach ($userIds as $userId)
			{
				$gamesList = $this->GetActiveGames($userId, 0);
				foreach ($gamesList as $game)
				{
					$this->EndGame($game->gameId);
					$gamesCount++;
				}
			}
			
			return $gamesCount;
		}
		
		function EndGame($gameId)
		{
			// End the game .... 
			$this->gameId = $gameId;
			
			$gameStatus = $this->GetGameStatus($gameId);
			if ($gameStatus != self::GAME_INPROGRESS)
				return $gameStatus;
				
			// Set the game end time
			$this->SetGameEndTime($gameId, self::GAME_ENDED);
			
			// Set the game ticker to 0
			$this->SetTicker(0, $gameId);
			
			// NOTE: Could Delete any unfinished rounds ?
			$noOfRounds = $this->GetNumberOfRounds();	
			
			return $gameStatus;	
		}
		
		function UpdateHideCardsOption($playerId, $hideCardsOption)
		{
			return $this->UpdateTable(CARDZNET_PLAYERS_TABLE, 'hideCardsOption', $hideCardsOption, 'playerId', $playerId);
		}
		
		function UpdateTable($tableId, $fieldId, $value, $whereId, $where)
		{
			if (true) $value = '"'.$value.'"';
			
			$sql  = "UPDATE $tableId ";
			$sql .= "SET $fieldId=$value ";
			$sql .= "WHERE $whereId=$where ";
			$this->query($sql);	
			
			return $this->roundId;
		}

		function PurgeDB($alwaysRun = false)
		{
			if (!$alwaysRun && isset($this->PurgeDBDone)) return;
			
			$this->PurgeOrphans(array(CARDZNET_MEMBERS_TABLE.'.memberId', CARDZNET_GROUPS_TABLE.'.groupId'));
			$this->PurgeOrphans(array(CARDZNET_PLAYERS_TABLE.'.playerId', CARDZNET_GAMES_TABLE.'.gameId'));
			$this->PurgeOrphans(array(CARDZNET_ROUNDS_TABLE.'.roundId', CARDZNET_GAMES_TABLE.'.gameId'));
			$this->PurgeOrphans(array(CARDZNET_HANDS_TABLE.'.handId', CARDZNET_ROUNDS_TABLE.'.roundId'));
			$this->PurgeOrphans(array(CARDZNET_TRICKS_TABLE.'.trickId', CARDZNET_ROUNDS_TABLE.'.roundId'));
			
			$this->PurgeDBDone = true;
		}
		
		function PurgeOrphans($dbFields, $condition = '')
		{
			// Removes DB rows that have lost their parent in DB
			$masterCol = $dbFields[0];
			
			$dbFieldParts = explode('.', $masterCol);
			$masterTable = $dbFieldParts[0];
			$masterIndex = $dbFieldParts[1];
			
			$subCol = $dbFields[1];
			
			$dbFieldParts = explode('.', $subCol);
			$subTable = $dbFieldParts[0];
			$subIndex = $dbFieldParts[1];
			
			$sqlSelect  = 'SELECT '.$masterCol.' AS id ';
			$sql  = 'FROM '.$masterTable.' ';
			$sql .= 'LEFT JOIN '.$subTable.' ON '.$masterTable.'.'.$subIndex.'='.$subTable.'.'.$subIndex.' ';
			$sql .= 'WHERE '.$subTable.'.'.$subIndex.' IS NULL ';
			
			if ($condition != '')
			{
				$sql .= 'AND '.$condition.' ';
			}
	
			if ($this->isDbgOptionSet('Dev_ShowDBOutput'))
			{
				$this->OutputDebugStart();
				echo "<br>Run SELECT * just to see result of next query.\n";
				$this->get_results('SELECT * '.$sql);
				$this->OutputDebugEnd();
			}
			
			$sql = $sqlSelect.$sql;
			$idsList = $this->get_results($sql);
			if (count($idsList) == 0) return;
			
			$ids = '';
			foreach ($idsList AS $idEntry)
			{
				if ($ids != '') $ids .= ',';
				$ids .= $idEntry->id;
			}
			
			$sql  = 'DELETE FROM '.$masterTable.' ';
			$sql .= 'WHERE '.$masterIndex.' IN ( ';
			$sql .= $ids;
			$sql .= ') ';
			
			$this->query($sql);
		}
		
		function GetTicker($gameId)
		{
			// NOTE: Could Detect that the game has finished ....
			$sql  = 'SELECT gameTicker FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			$results = $this->get_results($sql);
			if ($results == null) return null;
			
			return $results[0]->gameTicker;
		}
		
		function AddRound($roundState = self::ROUND_READY)
		{			
			$roundStartDateTime = date(CardzNetLibDBaseClass::MYSQL_DATETIME_FORMAT);
			
			$sql  = 'INSERT INTO '.CARDZNET_ROUNDS_TABLE.'(gameId, roundStartDateTime, roundState) ';
			$sql .= 'VALUES("'.$this->gameId.'", "'.$roundStartDateTime.'", "'.$roundState.'")';
			$this->query($sql);	
					
			$this->roundState = $roundState;
			
     		$this->roundId = $this->GetInsertId();
     		return $this->roundId;
		}		
		
		function GetNumberOfRounds($completeRounds = false)
		{
			$gameId = $this->gameId;
			
			$sql  = 'SELECT COUNT(roundStartDateTime) AS noOfRounds FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_ROUNDS_TABLE.' ON '.CARDZNET_ROUNDS_TABLE.'.gameId='.CARDZNET_GAMES_TABLE.'.gameId ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			if ($completeRounds)
				$sql .= 'AND '.CARDZNET_ROUNDS_TABLE.'.roundState="'.self::ROUND_COMPLETE.'" ';
				
			$results = $this->get_results($sql);
			if ($results == null) return null;
			
			return $results[0]->noOfRounds;
		}
		
		function GetRoundState()
		{
			return $this->roundState;
		}
		
		function UpdateRoundState($roundState, $gameComplete = false)
		{
			$sql  = 'UPDATE '.CARDZNET_ROUNDS_TABLE.' ';
			$sql .= 'SET roundState="'.$roundState.'" ';
			if ($roundState == self::ROUND_COMPLETE)
			{
				$roundEndDateTime = date(CardzNetLibDBaseClass::MYSQL_DATETIME_FORMAT);
				$sql .= ', roundEndDateTime="'.$roundEndDateTime.'" ';
			}
			$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$this->roundId.' ';
			$this->query($sql);	
			
			if (($roundState == self::ROUND_COMPLETE) && $gameComplete)
			{
				$this->SetGameEndTime($this->gameId, self::GAME_COMPLETE, $roundEndDateTime);
			}
			
			$this->roundState = $roundState;
			if (isset($this->game))
				$this->game->roundState = $roundState;
				
			return $this->roundId;
		}
		
		function AddDeckToRound($cards)
		{
			$ser_cards = serialize($cards);
			
			$sql  = 'UPDATE '.CARDZNET_ROUNDS_TABLE.' ';
			$sql .= 'SET roundDeck="'.$ser_cards.'" ';
			$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$this->roundId.' ';
			$this->query($sql);	
		}
		
		function SetNextCardInDeck($nextCard)
		{
			$sql  = 'UPDATE '.CARDZNET_ROUNDS_TABLE.' ';
			$sql .= 'SET roundNextCard="'.$nextCard.'" ';
			$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$this->roundId.' ';
			$this->query($sql);	
		}
		
		function GetRounds($roundId=0, $limit=0)
		{			
			$sql  = 'SELECT * FROM '.CARDZNET_ROUNDS_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_GAMES_TABLE.' ON '.CARDZNET_GAMES_TABLE.'.gameId='.CARDZNET_ROUNDS_TABLE.'.gameId ';
			if ($roundId > 0)
				$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$roundId.' ';
			$sql .= 'ORDER BY '.CARDZNET_GAMES_TABLE.'.gameId DESC,roundId DESC ';
			if ($limit > 0) $sql .= "LIMIT $limit ";

			$results = $this->get_results($sql);
			return $results;
		}
		
		function GetNextCardFromDeck()
		{			
			$sql  = 'SELECT roundDeck, roundNextCard FROM '.CARDZNET_ROUNDS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$this->roundId.' ';
				
			$results = $this->get_results($sql);
			if ($results == null) return null;
			
			$cardIndex = $results[0]->roundNextCard;
			$deck = unserialize($results[0]->roundDeck);
			
			if ($cardIndex >= count($deck)) return null;
			
			$cardNo = $deck[$cardIndex];			
			$cardIndex++;
			$this->SetNextCardInDeck($cardIndex);
			
			return $cardNo;
		}

		function GetDeck($details)
		{
			$noOfPacks = $details->noOfPacks;
			$noOfJokers = isset($details->noOfJokers) ? $details->noOfJokers : 0; 
			$excludedCardNos = isset($details->excludedCardNos) ? $details->excludedCardNos : array(); 
			
			$cards = array();
			for ($cardNo=1; $cardNo<=52; $cardNo++)
			{
				if (!empty($excludedCardNos[$cardNo]))
				{
					continue;
				}
				
				for ($cardCount = 1; $cardCount <= $noOfPacks; $cardCount++)
				{
					$cards[] = $cardNo;
				}
			}
			
			$jokerCardNo = 53; // NOTE: Could Get using GetCardNo('black-joker');
			for ($jCount=0; $jCount<$noOfJokers; $jCount++)
			{
				$cards[] = $jokerCardNo;
			}
			
			shuffle($cards);
	
			if ($this->isDbgOptionSet('Dev_RerunGame'))
			{
				$prevroundId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'prevroundId');
				if ($prevroundId != 0)
				{
					// Get the deck from an earlier round
					$lastRounds = $this->GetRounds($prevroundId);
					$cards = unserialize($lastRounds[0]->roundDeck);
				}
			}
			
			$shuffledCards = array();
			foreach ($cards as $card)
			{
				$shuffledCards[] = $card;
			}
			
			$this->AddDeckToRound($shuffledCards);
			
			return $shuffledCards;
		}
		
		function AddDeckToHand($playerNo, $cards, &$cardIndex, $noOfCards)
		{			
			for ($i=1; $i<=$noOfCards; $i++)
			{
				$cardNo = $cards[$cardIndex];
				$cardsList[] = $cardNo;
				$cardIndex++;
			}
			
			sort($cardsList);
			
			$player = $this->playersList[$playerNo];
			$playerId = $player->Id;

			$this->AddHand($playerId, $cardsList);
			
			return $playerId;
		}
		
		function AddHand($playerId, $cardsList)
		{			
			$ser_cardsList = serialize($cardsList);
			$noOfCards = count($cardsList);
			
			$sql  = 'INSERT INTO '.CARDZNET_HANDS_TABLE.'(roundId, playerId, noOfCards, cardsList) ';
			$sql .= 'VALUES("'.$this->roundId.'", "'.$playerId.'", "'.$noOfCards.'", "'.$ser_cardsList.'")';
			$this->query($sql);	
					
     		return $this->GetInsertId();
		}		
		
		function UpdateCurrentHand($cardsList, $playedList = null)
		{			
			$playerId = $this->thisPlayer->Id;
			return $this->UpdateHand($playerId, $cardsList, $playedList);
		}		
		
		function UpdateHand($playerId, $cardsList, $playedList = null)
		{			
			$ser_cardsList = serialize($cardsList);
			$noOfCards = count($cardsList);
			
			$sql  = 'UPDATE '.CARDZNET_HANDS_TABLE.' ';
			$sql .= 'SET cardsList="'.$ser_cardsList.'" ';
			$sql .= ', noOfCards="'.$noOfCards.'" ';
			if ($playedList != null)
			{
				$ser_playedList = serialize($playedList);
				$sql .= ', playedList="'.$ser_playedList.'" ';				
			}
			$sql .= 'WHERE '.CARDZNET_HANDS_TABLE.'.roundId='.$this->roundId.' ';
			$sql .= 'AND '.CARDZNET_HANDS_TABLE.'.playerId='.$playerId.' ';
			$this->query($sql);	
					
     		return $this->GetInsertId();
		}		
		
		function AddCardToHand($cardNo)
		{			
			$playerId = $this->thisPlayer->Id;
			
			$sql  = 'SELECT cardsList FROM '.CARDZNET_HANDS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_HANDS_TABLE.'.roundId='.$this->roundId.' ';
			$sql .= 'AND '.CARDZNET_HANDS_TABLE.'.playerId='.$playerId.' ';
			$results = $this->get_results($sql);
			if (count($results) != 1) return 0;
			
			$cardsList = unserialize($results[0]->cardsList);
			$cardsList[] = $cardNo;
			
			sort($cardsList);
			
			return $this->UpdateHand($playerId, $cardsList);
		}		
		
		function GetAllHands()
		{
			return $this->GetFilteredHandsList();
		}		
		
		function GetHand($playerId = 0)
		{
			$useCurrentPlayerId = ($playerId == 0);

			if ($useCurrentPlayerId)
				$playerId = $this->thisPlayer->Id;
			
			$results = $this->GetFilteredHandsList($playerId);
			if (count($results) != 1) $this->dbError('GetHand', '', $results);
			
			$playersHand = new stdClass();
			$playersHand->cards = unserialize($results[0]->cardsList);
			$playersHand->played = unserialize($results[0]->playedList);
			$playersHand->noOfCards = $results[0]->noOfCards;
			$playersHand->roundState = $results[0]->roundState;
			
			if ($useCurrentPlayerId)
				$this->noOfCards = $playersHand->noOfCards;
			
			return $playersHand;
		}		
		
		function GetFilteredHandsList($playerId = 0)
		{
			// Get the players hand
			$sql  = 'SELECT * FROM '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_GAMES_TABLE.' ON '.CARDZNET_GAMES_TABLE.'.gameId='.CARDZNET_PLAYERS_TABLE.'.gameId ';
			$sql .= 'LEFT JOIN '.CARDZNET_ROUNDS_TABLE.' ON '.CARDZNET_ROUNDS_TABLE.'.gameId='.CARDZNET_GAMES_TABLE.'.gameId ';
			$sql .= 'LEFT JOIN '.CARDZNET_HANDS_TABLE.' ON '.CARDZNET_HANDS_TABLE.'.roundId='.CARDZNET_ROUNDS_TABLE.'.roundId ';
			$sql .= 'AND '.CARDZNET_HANDS_TABLE.'.playerId='.CARDZNET_PLAYERS_TABLE.'.playerId ';
			$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$this->roundId.' ';
			if ($playerId != 0)
				$sql .= 'AND '.CARDZNET_PLAYERS_TABLE.'.playerId='.$playerId.' ';
			$results = $this->get_results($sql);
			
			return $results;
		}
		
		function RevertPassedCards($destPlayerOffset = 0, $clearPlayedCards = self::CLEAR_CARDS)
		{
			// Get "Played cards list
			$hands = $this->GetAllHands();
			
			// Add played cards to next player
			$noOfPlayers = count($hands);
			for ($src=0; $src<$noOfPlayers; $src++)
			{
				$cardsList[$src] = unserialize($hands[$src]->cardsList);
				$playedList[$src] = unserialize($hands[$src]->playedList);
			}			

			$cardsMoved = 0;
			for ($src=0; $src<$noOfPlayers; $src++)
			{
				if (!isset($playedList[$src][0])) continue;
				
				$dest = $src + $destPlayerOffset;
				if ($dest >= $noOfPlayers) $dest -= $noOfPlayers;
				
				// Copy the playedList to the destination
				foreach ($playedList[$src] as $cardNo)
				{
					$cardsList[$dest][] = $cardNo;
					$cardsMoved++;		
				}
			}
						
			// Re-sort Cards Lists, (optionally) Clear played cards list & write back to database
			for ($src=0; $src<$noOfPlayers; $src++)
			{
				if ($clearPlayedCards == self::CLEAR_CARDS) $playedList[$src] = array();

				sort($cardsList[$src]);
				$this->UpdateHand($hands[$src]->playerId, $cardsList[$src], $playedList[$src]);
			}
			
			return $cardsMoved;
		}
		
		function CardsPerPlayer()
		{
			return $this->cardsPerPlayer;
		}
		
		function GetNoOfCards($playerId = 0)
		{
			if ($playerId == 0)
				return $this->noOfCards;
				
			$playersHand=$this->GetHand($playerId);
			return $playersHand->noOfCards;
		}
		
		function GetUnplayedCardsCount()
		{
			$sql  = 'SELECT SUM(noOfCards) AS totalCards FROM '.CARDZNET_ROUNDS_TABLE.' ';
			$sql .= 'LEFT JOIN '.CARDZNET_HANDS_TABLE.' ON '.CARDZNET_HANDS_TABLE.'.roundId='.CARDZNET_ROUNDS_TABLE.'.roundId ';
			$sql .= 'WHERE '.CARDZNET_ROUNDS_TABLE.'.roundId='.$this->roundId.' ';
			$results = $this->get_results($sql);
			if (count($results) != 1) return 0;
			
			return $results[0]->totalCards;
		}
		
		function GetTricksCount()
		{
			$sql  = 'SELECT COUNT(roundId) AS noOfTricks FROM '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.roundId='.$this->roundId.' ';
			$results = $this->get_results($sql);
			
			return $results[0]->noOfTricks;
		}
		
		function GetAllTricks($playerId = 0, $onlyComplete = true)
		{
			// playerId can be an integer(playerId) or string (playerColour)
			
			$sql  = 'SELECT * FROM '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'LEFT JOIN '.CARDZNET_PLAYERS_TABLE.' ON '.CARDZNET_PLAYERS_TABLE.'.playerId='.CARDZNET_TRICKS_TABLE.'.playerId ';;
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.roundId='.$this->roundId.' ';
			if ($onlyComplete)
				$sql .= 'AND NOT '.CARDZNET_TRICKS_TABLE.'.complete ';
			if ($playerId == 0)
			{
				$sql .= 'ORDER BY '.CARDZNET_TRICKS_TABLE.'.playerOrder ASC ';
				$sql .= ', '.CARDZNET_TRICKS_TABLE.'.trickId ASC ';
			}
			else if (is_string($playerId))
			{
				$playerColour = $playerId;
				$sql .= 'AND '.CARDZNET_TRICKS_TABLE.'.playerColour='.$playerColour.' ';
			}
			else
				$sql .= 'AND '.CARDZNET_TRICKS_TABLE.'.playerId='.$playerId.' ';
			$results = $this->get_results($sql);
			
			return $results;
		}
		
		function GetCurrentTrick($playerId = 0)
		{
			$sql  = 'SELECT * FROM '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.roundId='.$this->roundId.' ';
			$sql .= 'AND NOT '.CARDZNET_TRICKS_TABLE.'.complete ';
			if ($playerId != 0)
				$sql .= 'AND '.CARDZNET_TRICKS_TABLE.'.playerId='.$playerId.' ';
			$results = $this->get_results($sql);
			if (count($results) != 1) return null;
			
			$results[0]->cardsList = unserialize($results[0]->cardsList);
			return $results[0];
		}
		
		function GetLastTrick()
		{
			$sql  = 'SELECT * FROM '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.roundId='.$this->roundId.' ';
			$sql .= 'AND '.CARDZNET_TRICKS_TABLE.'.complete ';
			$sql .= 'ORDER BY '.CARDZNET_TRICKS_TABLE.'.trickId DESC ';
			$sql .= 'LIMIT 1 ';
			
			$results = $this->get_results($sql);
			if (count($results) != 1) return null;
			
			$results[0]->cardsList = unserialize($results[0]->cardsList);
			
			$winnerId = $results[0]->winnerId;
			$winnerIndex = $this->GetPlayerIndex($winnerId);
			$results[0]->winnerName = $this->playersList[$winnerIndex]->name;

			return $results[0];
		}
		
		function NewTrick($cardNo = 0, $playerOrder = 0)
		{
			$playerId = $this->thisPlayer->Id;
			
			$cardsList = array();
			if ($cardNo != 0) $cardsList[] = $cardNo;
			$ser_cardsList = serialize($cardsList);
			
			$sql  = 'INSERT INTO '.CARDZNET_TRICKS_TABLE.'(roundID, playerId, playerOrder, cardsList) ';
			$sql .= 'VALUES("'.$this->roundId.'", "'.$playerId.'", "'.$playerOrder.'", "'.$ser_cardsList.'") ';
			$this->query($sql);	
					
     		return $this->GetInsertId();
		}
		
		function AddToTrick($cardNo)
		{
			if (!isset($this->currTrick)) $this->dbError('AddToTrick', 'trickId unknown', '');
			
			$trickId = $this->currTrick->trickId;
			
			$this->currTrick->cardsList[] = $cardNo;
			
			$cardsInTrick = count($this->currTrick->cardsList);
			$ser_cardsList = serialize($this->currTrick->cardsList);
			
			$sql  = 'UPDATE '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'SET cardsList="'.$ser_cardsList.'" ';
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.trickId='.$trickId.' ';
			$this->query($sql);	
					
     		return $trickId;
		}
		
		function UpdateTrick($cardsList, $playerId)
		{
			$ser_cardsList = serialize($cardsList);
			
			$sql  = 'UPDATE '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'SET cardsList="'.$ser_cardsList.'" ';
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.playerId='.$playerId.' ';
			$sql .= 'AND '.CARDZNET_TRICKS_TABLE.'.roundId='.$this->roundId.' ';
			
			$this->query($sql);	
		}
		
		function DeleteTrick($trickId)
		{
			$sql  = 'DELETE FROM '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.trickId='.$trickId.' ';
			$rtnStatus = $this->query($sql);
			
			unset($this->currTrick);
			
			return $rtnStatus;		
		}
		
		function CompleteTrick($winnerPlayerId, $winnerScore)
		{
			if (!isset($this->currTrick)) $this->dbError('CompleteTrick', 'trickId unknown', '');
			
			$trickId = $this->currTrick->trickId;
			
			$sql  = 'UPDATE '.CARDZNET_TRICKS_TABLE.' ';
			$sql .= 'SET complete=TRUE ';
			$sql .= ', winnerId="'.$winnerPlayerId.'" ';
			$sql .= ', score="'.$winnerScore.'" ';
			$sql .= 'WHERE '.CARDZNET_TRICKS_TABLE.'.trickId='.$trickId.' ';
			$this->query($sql);	
					
			$this->currTrick = null;
			
     		return $this->GetInsertId();
		}
		
		function GetTrickCards($refresh = false, $playerId = 0)
		{
			if (!isset($this->currTrick) || $refresh)
				$this->currTrick = $this->GetCurrentTrick($playerId);

			if ($this->currTrick == null)
				return null;
				
			return $this->currTrick->cardsList;
		}
		
		function GetPlayerDetails($playerId)
		{
			$sql  = 'SELECT * FROM '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_PLAYERS_TABLE.'.playerId='.$playerId.' ';
			$results = $this->get_results($sql);
			if (count($results) != 1) $this->dbError('GetPlayerDetails', $sql, $results);

			return $results[0];
		}
		
		function SetPlayerColour($playerId, $playerColour)
		{			
			$sql  = 'UPDATE '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'SET playerColour="'.$playerColour.'" ';
			$sql .= 'WHERE '.CARDZNET_PLAYERS_TABLE.'.playerId='.$playerId.' ';
			$this->query($sql);	
		}
		
		function GetNextPlayer($playerId)
		{
			$sql  = 'SELECT * FROM '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'WHERE '.CARDZNET_PLAYERS_TABLE.'.playerId='.$playerId.' ';
			$results = $this->get_results($sql);
			if (count($results) != 1) $this->dbError('GetNextPlayer', $sql, $results);

			return $results[0];
		}
		
		function GetNextDealer()
		{
			$noOfRounds = $this->GetNumberOfRounds();
			$dealerId = $this->AdvancePlayer($noOfRounds-1, $this->firstPlayerId);
			return $dealerId;
		}
		
		function AdvancePlayer($numPlayers = 1, $lastPlayerId = 0, $dbg = false)
		{
			if ($lastPlayerId == 0) $lastPlayerId = $this->nextPlayerId;
			
			// Get the players list for this round
			$index = $this->GetPlayerIndex($lastPlayerId);
			$index += $numPlayers;
			$noOfPlayers = count($this->playersList);
			while ($index > $noOfPlayers-1) $index -= $noOfPlayers;
			
			$player = $this->playersList[$index];
			$playerId = $player->Id;

			return $player->Id;
		}
		
		function SetNextPlayer($playerId)
		{
			$gameTicker = $this->gameTicker+1;
			
			$this->LockTables(CARDZNET_GAMES_TABLE);
			
			$sql  = 'UPDATE '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'SET nextPlayerId="'.$playerId.'" ';
			$sql .= ', gameTicker="'.$gameTicker.'" ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$this->gameId.' ';
			$this->query($sql);	
			
			$this->nextPlayerId = $playerId;

			if (isset($this->game))
				$this->game->nextPlayerId = $playerId;
				
			$this->UpdateTickPage($gameTicker);

			$this->UnLockTables();
			
			return $playerId;
		}
		
		function SelectPlayerBeforeNext()
		{
			$nextPlayerIndex = $this->GetPlayerIndex($this->nextPlayerId);
			if (--$nextPlayerIndex < 0) $nextPlayerIndex = count($this->playersList)-1;
			$player = $this->playersList[$nextPlayerIndex];
			$this->nextPlayerId = $player->Id;
			return $this->nextPlayerId;
		}

		function IncrementTicker()
		{
			$gameTicker = $this->gameTicker+1;
			return $this->SetTicker($gameTicker);
		}

		function SetTicker($gameTicker, $gameId = 0)
		{
			if ($this->isDbgOptionSet('Dev_DisableTicker')) return 0;
			
			if ($gameId == 0) $gameId = $this->gameId;
			
			$this->LockTables(CARDZNET_GAMES_TABLE);
			
			$sql  = 'UPDATE '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'SET gameTicker="'.$gameTicker.'" ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$this->gameId.' ';
			$this->query($sql);	
			
			// Update page at gameTickFilename
			$this->UpdateTickPage($gameTicker);
			
			$this->UnLockTables();
			
			$this->gameTicker = $gameTicker;
			
			return $gameTicker;
		}

		function GetTickFilename($gameId = 0)
		{
			if ($gameId == 0)
				$gameId = $this->gameId;

			$sql  = 'SELECT gameTickFilename FROM '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'WHERE gameId='.$gameId.' ';
			$results = $this->get_results($sql);
			if (count($results) == 0) return '';
			
			return $results[0]->gameTickFilename;
		}

		function UpdateTickPage($gameTicker, $gameId = 0)
		{
			if ($gameId == 0)
				$gameId = $this->gameId;
				
			$tickPagePath = $this->GetTickFilename($gameId);
			if ($tickPagePath != '')
			{
				$tickPagePath = CARDZNET_UPLOADS_PATH.'/'.$tickPagePath;
				//$msg = "Ticker: $gameTicker --> $tickPagePath <br>\n";
				//$this->AddToStampedCommsLog($msg);
				file_put_contents($tickPagePath, $gameTicker);				
			}
		}
		
		function SetGameEndTime($gameId = 0, $gameStatus = '', $gameEndDateTime = '')
		{
			if ($gameId == 0) $gameId = $this->gameId;
			if ($gameEndDateTime == '') $gameEndDateTime = date(CardzNetLibDBaseClass::MYSQL_DATETIME_FORMAT);
			
			$sql  = 'UPDATE '.CARDZNET_GAMES_TABLE.' ';
			$sql .= 'SET gameEndDateTime="'.$gameEndDateTime.'" ';
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$gameId.' ';
			$this->query($sql);	
		}

		function GetPlayerName()
		{
			return $this->thisPlayer->name;
		}
			
		function GetPlayerColour()
		{
			return $this->thisPlayer->colour;
		}
			
		function GetPlayerIdHiddenTag()
		{
			return "<input type=hidden id=playerId name=playerId value=".$this->thisPlayer->Id.">\n";
		}	
				
		function GetLastRoundScores()
		{
			return $this->GetScores($this->roundId);
		}
		
		function GetLastRoundScore($playerId = 0)
		{
			return $this->GetScores($this->roundId, $playerId);
		}
		
		function GetScoresByGame($gameId)
		{
			$this->gameId = $gameId;
			return $this->GetScores();
		}
		
		function GetScores($roundId = 0, $playerId = 0)
		{
			// Get the players list for this round in the order of play
			$sql  = 'SELECT '.CARDZNET_PLAYERS_TABLE.'.*, winnerId, gameMeta, ';
			$sql .= 'COALESCE(SUM('.CARDZNET_TRICKS_TABLE.'.score),0) AS roundScore ';
			$sql .= 'FROM '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'JOIN '.CARDZNET_GAMES_TABLE.' ON '.CARDZNET_GAMES_TABLE.'.gameId='.CARDZNET_PLAYERS_TABLE.'.gameId ';;
			$sql .= 'LEFT JOIN '.CARDZNET_ROUNDS_TABLE.' ON '.CARDZNET_ROUNDS_TABLE.'.gameId='.CARDZNET_GAMES_TABLE.'.gameId ';;
			$sql .= 'LEFT JOIN '.CARDZNET_TRICKS_TABLE.' ON '.CARDZNET_TRICKS_TABLE.'.winnerId='.CARDZNET_PLAYERS_TABLE.'.playerId ';;
			$sql .= 'AND '.CARDZNET_TRICKS_TABLE.'.roundId='.CARDZNET_ROUNDS_TABLE.'.roundId ';;
			$sql .= 'WHERE '.CARDZNET_GAMES_TABLE.'.gameId='.$this->gameId.' ';
			if ($roundId > 0)
			{
				$sql .= 'AND '.CARDZNET_ROUNDS_TABLE.'.roundId='.$roundId.' ';
			}
			if ($playerId > 0)
			{
				$sql .= 'AND '.CARDZNET_PLAYERS_TABLE.'.playerId='.$playerId.' ';
			}
			
			$sql .= 'GROUP BY '.CARDZNET_PLAYERS_TABLE.'.playerId ';
			$sql .= 'ORDER BY playerId ASC';
			$results = $this->get_results($sql);
			if (count($results) == 0) return null;
						
			for ($index=0; $index<count($results); $index++)
			{
				$results[$index]->gameOpts = unserialize($results[$index]->gameMeta);
			}
			return $results;
		}
		
		function UpdateScore($playerId, $newScore)
		{
			$sql  = 'UPDATE '.CARDZNET_PLAYERS_TABLE.' ';
			$sql .= 'SET score="'.$newScore.'" ';
			$sql .= 'WHERE '.CARDZNET_PLAYERS_TABLE.'.playerId='.$playerId.' ';
			$this->query($sql);	
		}
		
		function SendEMailByTemplateID($eventRecord, $templateID, $folder, $EMailTo = '')
		{	
			$eventRecord[0]->organisation = get_bloginfo('name');
			
			$groupUserId = $eventRecord[0]->groupUserId;
			$eventRecord[0]->groupAdminName = $this->GetUserName($groupUserId);
			$eventRecord[0]->groupAdminEMail = $this->GetUserEMail($groupUserId);
						
			return parent::SendEMailByTemplateID($eventRecord, $templateID, $folder, $EMailTo);
		}
		
		static function GetUserEMail($userId)
		{
			$user = get_user_by('id', $userId);
			if (!isset($user->user_email)) return '';
			return $user->user_email;
		}
		
		static function GetUserName($userId)
		{
			$user = get_user_by('id', $userId);
			return self::GetUserNameFromObj($user);
		}
		
		static function GetCurrentUserName()
		{
			$user = wp_get_current_user();
			return self::GetUserNameFromObj($user);
		}
		
		static function GetUserNameFromObj($user)
		{
			if (isset($user->display_name) && (strlen($user->display_name) > 0))
				$userName = $user->display_name;
			else if (isset($user->user_login))
				$userName = $user->user_login;
			else
				$userName = 'Unknown User';
				
			return $userName;
		}
		
		static function GetUserSelector($no, $name, $cap = '')
		{
			$showNone = false;
			if ($cap == '')
			{
				$cap = CARDZNET_CAPABILITY_PLAYER;
				$showNone = true;
			}
			
			$dropDownAtts = array('echo' => 0);

			$excludeList = '';
			$usersList = get_users();
			
			foreach ($usersList as $user)
			{
				if (!isset($user->allcaps[$cap]))
				{
					$excludeList .= $user->ID.',';
				}
				
				if (isset($user->data->display_name) && ($user->data->display_name != ''))
					$userName = $user->data->display_name;
				else
					$userName = $user->data->user_login;
					
				if ($userName = $name)
				{
					$defaultUserId = $user->ID;
					$dropDownAtts['selected'] = $defaultUserId;
				}
			}

			$dropDownAtts['name'] = "userId$no";			
			$dropDownAtts['exclude'] = $excludeList;	
					
			$html = wp_dropdown_users($dropDownAtts);
			
			if ($showNone)
			{
				$noneText = __("None", CARDZNET_DOMAIN_NAME);
				$noneSelected = isset($dropDownAtts['selected']) ? '' : ' selected="selected" ';
				$noneSelect = "<option value=0 $noneSelected>($noneText)</option>";
				$html = str_replace('</select>', $noneSelect.'</select>', $html);
			}
			
			return $html;
		}
		
		function GetMemberSelector($no, $name = '')
		{
			$showNone = true;
			$selectorName = "userId$no";

			if (!CardzNetLibUtilsClass::IsElementSet('post', 'gameGroupId')) die("No gameGroupId");
			$groupId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'gameGroupId');
			if (!current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				if ($groupId == 0) die("Invalid gameGroupId");
			}
			
			$groupDef = $this->GetGroupById($groupId);
			if (count($groupDef) == 0) return '';
			$groupUserId = $groupDef[0]->groupUserId;
					
			$listFromDB = $this->GetMembersById($groupId);
			
			$userId = get_current_user_id();
			if (!current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				if ($groupUserId != $userId) die("User does not match groupUserId");
			}

			$membersList = array();
			$membersList[$groupUserId] = $this->GetUserName($groupUserId);
			
			foreach ($listFromDB as $member)
			{
				$membersList[$member->memberUserId] = $this->GetUserName($member->memberUserId);		
			}
			asort($membersList);

			$rowSelected = false;
			$html = "<select id=$selectorName name=$selectorName>";
			foreach ($membersList as $memberId => $memberName)
			{
				$selected = ($name == $memberName) ? ' selected="selected" ' : '';
				$rowSelected |= ($selected != '');
				$html .= "<option value=$memberId $selected>$memberName</option> \n";
			}
			
			if ($showNone)
			{
				$noneText = __("None", CARDZNET_DOMAIN_NAME);
				$noneSelected = $rowSelected ? '' : ' selected="selected" ';
				$noneSelect = "<option value=0 $noneSelected>($noneText)</option>";
				$html .= $noneSelect;
			}
			
			$html .= "</select>";
			
			return $html;
		}
		
		function GetGroupSelector()
		{
			$groupUserId = 0;
			if (!current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
				$groupUserId = get_current_user_id();

			$groupsList = $this->GetGroups($groupUserId);
			if (count($groupsList) == 0) return '';
			
			$html = "<select id=gameGroupId name=gameGroupId>";
			foreach ($groupsList as $group)
			{
				$html .= "<option value={$group->groupId}>{$group->groupName}</option> \n";
			}
			if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				$allGroupsText = __("All Groups", CARDZNET_DOMAIN_NAME);
				$html .= "<option value=0>$allGroupsText</option> \n";
			}
			$html .= "</select>";
			
			return $html;
		}
		
		function GetGameSelector($gameName = '', $nocopyGameText = '')
		{
			$rounds = $this->GetRounds(0, 10);
			$roundsCount = 0;

			$html  = "<select id=prevroundId name=prevroundId>\n";
			
			if ($nocopyGameText != '')
				$html .= '<option value="0">'.$nocopyGameText.'&nbsp;&nbsp;</option>'."\n";
			
			foreach ($rounds as $round)
			{
				if (($gameName != '') && ($round->gameName != $gameName)) continue;
				
				$roundId = $round->roundId;
				$roundDetails = "{$round->gameName} @ {$round->roundStartDateTime}";
				
				$html .= '<option value="'.$roundId.'">'.$roundDetails.'&nbsp;&nbsp;</option>'."\n";
				$roundsCount++;
			}
			$html .= "</select>\n";			
			
			if ($roundsCount == 0) return '';
			
			return $html;
		}
		
		static function LocaliseAndFormatTimestamp($timestamp = null) 
		{
			$tz_string = get_option('timezone_string');
			$tz_offset = get_option('gmt_offset', 0);

			$date_format = get_option('date_format');
			$time_format = get_option('time_format');

			$format = "$date_format $time_format";

			if (!empty($tz_string)) 
			{
				// If site timezone option string exists, use it
				$timezone = $tz_string;
			} 
			elseif ($tz_offset == 0) 
			{
				// get UTC offset, if it isn’t set then return UTC
				$timezone = 'UTC';
			} 
			else 
			{
				$timezone = $tz_offset;

				if(substr($tz_offset, 0, 1) != "-" && substr($tz_offset, 0, 1) != "+" && substr($tz_offset, 0, 1) != "U") 
				{
					$timezone = "+" . $tz_offset;
				}
			}

			$datetime = new DateTime();
			$datetime->setTimestamp($timestamp);
			$datetime->setTimezone(new DateTimeZone($timezone));
			
			return $datetime->format($format);
		}

		static function FormatAdminDateTime($dateInDB)
		{
			// Convert time string to UNIX timestamp
			$timestamp = strtotime($dateInDB);
			if ($timestamp == 0) return '';
			
			return self::LocaliseAndFormatTimestamp($timestamp);
		}
		
		function BannerMsg($msg, $class)
		{
			return '<div id="message" class="'.$class.'"><p>'.$msg.'</p></div>';
		}
		
		function AddToStampedCommsLog($logMessage)
		{
			return $this->WriteCommsLog($logMessage, true);
		}
		
		function AddObjectToCommsLog($objName, $obj)
		{
			$logMsg = CardzNetLibUtilsClass::print_r($obj, $objName, true);
			return $this->AddToCommsLog($logMsg);
		}
		
		function AddToCommsLog($logMessage)
		{
			return $this->WriteCommsLog($logMessage, false);
		}
		
		function WriteCommsLog($logMessage, $addTimestamp = false, $mode = CardzNetLibLogFileClass::ForAppending)
		{
			$logMessage .= "\n";

			if (!isset($this->logFileObj))
			{
				// Create log file using mode passed in call
				$LogsFolder = ABSPATH.$this->getOption('LogsFolderPath');
				$this->logFileObj = new CardzNetLibLogFileClass($LogsFolder);
				$this->logFileObj->LogToFile(CARDZNET_FILENAME_COMMSLOG, '', $mode);
				$mode = CardzNetLibLogFileClass::ForAppending;
			}
			
			if ($addTimestamp)
				$logMessage = current_time('D, d M y H:i:s').' '.$logMessage;

			$this->logFileObj->LogToFile(CARDZNET_FILENAME_COMMSLOG, $logMessage, $mode);			
		}
	
		function ClearCommsLog()
		{
			return $this->WriteCommsLog('Log Cleared', true, CardzNetLibLogFileClass::ForWriting);
		}
		
		function GetCommsLog()
		{
			$LogsFolder = ABSPATH.$this->getOption('LogsFolderPath');
			$logsPath = $LogsFolder.'/'.CARDZNET_FILENAME_COMMSLOG;
			return file_get_contents($logsPath);
		}
		
		function GetCommsLogSize()
		{
			$LogsFolder = ABSPATH.$this->getOption('LogsFolderPath');
			$logsPath = $LogsFolder.'/'.CARDZNET_FILENAME_COMMSLOG;
			if (!file_exists($logsPath)) return "0";
			$logSize = filesize($logsPath);
			if ($logSize === false) return "0";
			
			return round($logSize/1024, 2).'k';
		}

		function OutputDebugMessage($msg, $dbgOption = 'Dev_ShowMiscDebug')
		{
			if (($dbgOption != '') && !$this->isDbgOptionSet($dbgOption))
				return;
			
			if ($this->isDbgOptionSet('Dev_DebugToLog'))
				$this->AddToStampedCommsLog($msg);
			else
				echo $msg;	
		}
		
		function print_r($obj, $name='', $return = false, $eol = "<br>")
		{
			//if (!$this->isDbgOptionSet('Dev_ShowMiscDebug')) return '';
			
			return CardzNetLibUtilsClass::print_r($obj, $name, $return, $eol);
		}
		
		function dbError($fname, $sql, $results)			
		{
			echo "<br><br>********************<br>Error in <strong>$fname</strong> call<br>\n";
			CardzNetLibUtilsClass::print_r($sql, '$sql');
			CardzNetLibUtilsClass::print_r($results, '$results');
			exit;
		}

		function DoEmbeddedImage($eMailFields, $fieldMarker, $optionID)
		{
			$fieldMarker = '['.$fieldMarker.']';
			if (!strpos($eMailFields, $fieldMarker))
				return $eMailFields;
				
			if (isset($this->emailObj))
			{
				$imageFile = $this->getOption($optionID);
				if ($imageFile == '')
					return $eMailFields;
				
				$imagesPath = CARDZNETLIB_UPLOADS_PATH.'/images/';				
				
				// Add Image to EMail Images List
				$CIDFile = $this->emailObj->AddFileImage($imagesPath.$imageFile);
				$imageSrc = "cid:".$CIDFile;
			}
			else
			{
				$imageSrc = $this->getImageURL($optionID);
			}
				
			$eMailFields = str_replace($fieldMarker, $imageSrc, $eMailFields);
				
			return $eMailFields;
		}
		
		// Commented out Class Def (StageShowPlusCartDBaseClass)
		function sendMail($to, $from, $subject, $content1, $content2 = '', $headers = '')
		{
			include $this->emailClassFilePath;
			$emailObj = new $this->emailObjClass($this);
			$emailObj->sendMail($to, $from, $subject, $content1, $content2, $headersn);
		}
		
		
	}
}

?>