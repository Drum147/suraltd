<?php
/* 
Description: Core Library Database Access functions

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

if(!isset($_SESSION)) 
{
	// MJS - SC Mod - Register to use SESSIONS
	session_start();
}	

require_once CARDZNETLIB_INCLUDE_PATH.'cardznetlib_utils.php';
require_once CARDZNETLIB_INCLUDE_PATH.'cardznetlib_dbase_base.php';

if (!class_exists('CardzNetLibDBaseClass'))
{
	if (!defined('CARDZNETLIB_EVENTS_PER_PAGE'))
		define('CARDZNETLIB_EVENTS_PER_PAGE', 20);
	
	class CardzNetLibDBaseClass extends CardzNetLibGenericDBaseClass // Define class
	{
		const MYSQL_DATE_FORMAT = 'Y-m-d';
		const MYSQL_TIME_FORMAT = 'H:i:s';
		const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
		const MYSQL_DATETIME_NOSECS_FORMAT = 'Y-m-d H:i';
		
		const ForReading = 1;
		const ForWriting = 2;
		const ForAppending = 8;
		
		const SessionDebugPrefix = 'cardznetlib_debug_';
		
		var $optionsTable = '';
		var $optionsID;
		
		var $adminOptions;
		var $dbgOptions;
		var $pluginInfo;
		var $opts;
		
		var	$buttonImageURLs = array();
	
		function __construct($opts = null) //constructor		
		{					
			$dbPrefix = $this->getTablePrefix();
			$this->DBTables = $this->getTableNames($dbPrefix);

			parent::__construct($opts);
			
			$this->opts = $opts;
			$this->getOptions();

		}

		function PurgeDB($alwaysRun = false)
		{
			if (current_user_can(CARDZNETLIB_CAPABILITY_DEVUSER))
				echo "PurgeDB not defined in descendent class <br>\n";
		}
		
	    function uninstall()
	    {
		}
		
		function AllUserCapsToServervar()
		{
			$this->UserCapToServervar(CARDZNETLIB_CAPABILITY_SYSADMIN);
			CardzNetLibUtilsClass::SetElement('session', CARDZNETLIB_SESSION_VALIDATOR, true);
		}
		
		function UserCapToServervar($capability)
		{
			CardzNetLibUtilsClass::SetElement('session', 'Capability_'.$capability, current_user_can($capability));
		}
		
		static function GetIPAddr()
		{
			//Get the forwarded IP if it exists
			if (CardzNetLibUtilsClass::IsElementSet('server', 'X-Forwarded-For'))
			{
				$the_ip  = CardzNetLibUtilsClass::GetHTTPTextElem('server', 'X-Forwarded-For');
			} 
			elseif (CardzNetLibUtilsClass::IsElementSet('server', 'HTTP_X_FORWARDED_FOR'))
			{
				$the_ip  = CardzNetLibUtilsClass::GetHTTPTextElem('server', 'HTTP_X_FORWARDED_FOR');
			} 
			else 
			{				
				$the_ip  = CardzNetLibUtilsClass::GetHTTPTextElem('server', 'REMOTE_ADDR');
			}
			
			return $the_ip;
		}
		
		function IfButtonHasURL($buttonID)
		{
			$ourButtonURL = $this->ButtonURL($buttonID);
			if ($ourButtonURL == '')
				return false;
			
			return true;
		}
		
		function ButtonHasURL($buttonID, &$buttonURL)
		{
			$ourButtonURL = $this->ButtonURL($buttonID);
			if ($ourButtonURL == '')
				return false;
			
			$buttonURL = $ourButtonURL;	
			return true;
		}
		
		function ButtonURL($buttonID)
		{
			if (!isset($this->buttonImageURLs[$buttonID])) return '';				
			return $this->buttonImageURLs[$buttonID];	
		}
		
		function IsButtonClicked($buttonID)
		{
			$normButtonID = $this->GetButtonID($buttonID);
			$rtnVal = (CardzNetLibUtilsClass::IsElementSet('request', $normButtonID) || CardzNetLibUtilsClass::IsElementSet('request', $normButtonID.'_x'));
			return $rtnVal;
		}
				
		function GetButtonID($buttonID)
		{
			return $buttonID;
		}
				
		function getDebugFlagsArray()
		{
			$debugFlagsArray = array();
			
			$len = strlen(self::SessionDebugPrefix);
			foreach (CardzNetLibUtilsClass::GetArrayKeys('session') as $key)
			{
				if (substr($key, 0, $len) != self::SessionDebugPrefix)
					continue;
				$debugFlagsArray[] = $key;
			}
			return $debugFlagsArray;
		}
		
		function getTablePrefix()
		{
			global $wpdb;
			return $wpdb->prefix;
		}
		
		function getTableNames($dbPrefix)
		{
			$DBTables = new stdClass();
			
			$DBTables->Settings = $dbPrefix.'mjslibOptions';
			
			return $DBTables;
		}
	
		function AddGenericFields($EMailTemplate)
		{
			return $EMailTemplate;
		}
		
		function AddGenericDBFields(&$saleDetails)
		{
			$saleDetails->organisation = $this->adminOptions['OrganisationID'];
			if ($this->isOptionSet('HomePageURL'))
			{
				$saleDetails->url = $this->getOption('HomePageURL');
			}
			else
			{
				$saleDetails->url = get_option('home');
			}
		}
		
		function GetWPNonceField($referer = '', $name = '_wpnonce')
		{
			return $this->WPNonceField($referer, $name, false);
		}
		
		function WPNonceField($referer = '', $name = '_wpnonce', $echo = true)
		{
			$nonceField = '';
			
			if ($referer == '')
			{
				$caller = $this->opts['Caller'];
				$referer = plugin_basename($caller);
			}
			
			if ( function_exists('wp_nonce_field') ) 
			{
				if ($this->getDbgOption('Dev_ShowWPOnce'))
					$nonceField .= "<!-- wp_nonce_field($referer) ".$this->GetNOnceElements($referer)." -->\n";
				$nonceField .= wp_nonce_field($referer, $name, false, false);
				$nonceField .=  "\n";
			}
			
			if ($echo) echo $nonceField;
			return $nonceField;
		}
		
		function AddParamAdminReferer($caller, $theLink)
		{
			if (!function_exists('add_query_arg'))
				return $theLink;
			
			if ($caller == '')
				return $theLink;
			
			$baseName = plugin_basename($caller);
			$nonceVal = wp_create_nonce( $baseName );

			if ($this->getDbgOption('Dev_ShowWPOnce'))
			{
				$user = wp_get_current_user();
				$uid  = (int) $user->ID;
				$token = wp_get_session_token();
				$i     = wp_nonce_tick();
				echo "\n<!-- AddParamAdminReferer  NOnce:$nonceVal  ".$this->GetNOnceElements($baseName)." -->\n";
			}
			
			$theLink = add_query_arg( '_wpnonce', $nonceVal, $theLink );
			
			return $theLink;
		}
		
		function CheckAdminReferer($referer = '')
		{
			if ($referer == '')
			{
				$caller = $this->opts['Caller'];
				$referer = plugin_basename($caller);
			}
			
			if ($this->getDbgOption('Dev_ShowWPOnce'))
			{
				echo "<!-- check_admin_referer($referer) -->\n";
				if (!CardzNetLibUtilsClass::IsElementSet('request', '_wpnonce'))
					echo "<br><strong>check_admin_referer FAILED - _wpnonce NOT DEFINED</strong></br>\n";
				else 
				{
					$nOnceVal = CardzNetLibUtilsClass::GetHTTPTextElem('request', '_wpnonce'); 
					$nOnceValExp = wp_create_nonce($referer);
					if (!wp_verify_nonce($nOnceVal, $referer))
					{
						echo "<br><strong>check_admin_referer FAILED - NOnce:$nOnceVal  Expected:$nOnceValExp <br>".$this->GetNOnceElements($referer)."</strong></br>\n";
					}
				}
				return;
			}
			
			check_admin_referer($referer);
		}

		function GetNOnceElements($action)
		{
			$user = wp_get_current_user();
			$uid  = (int) $user->ID;
			$token = wp_get_session_token();
			$i     = wp_nonce_tick();
			return "elems:{$i}|{$action}|{$uid}|{$token}";
		}
		
		function ActionButtonHTML($buttonText, $caller, $domainId, $buttonClass, $elementId = 0, $buttonAction = '', $extraParams = '', $target = '')
		{
			//if ($buttonAction == '') $buttonAction = strtolower(str_replace(" ", "", $buttonText));
			$buttonText = __($buttonText, $domainId);
			$page = CardzNetLibUtilsClass::GetHTTPTextElem('get', 'page'); 
			
			$buttonId = $domainId.'-'.$buttonAction.'-'.$elementId;
			
			$editLink = 'admin.php?page='.$page;
			if ($buttonAction !== '') $editLink .= '&action='.$buttonAction;
			if ($elementId !== 0) $editLink .= '&id='.$elementId;
			$editLink = $this->AddParamAdminReferer($caller, $editLink);
			if ($extraParams != '') $editLink .= '&'.$extraParams;
			if ($target != '') $target = 'target='.$target;
			
			$editControl = "<a id=$buttonId name=$buttonId $target".' class="button-secondary" href="'.$editLink.'">'.$buttonText.'</a>'."\n";  
			if ($buttonClass != '')
			{
				$editControl = '<div class='.$buttonClass.'>'.$editControl.'</div>'."\n";  
			}
			return $editControl;    
		}
		
		function DeleteCapability($capID)
		{
			if (!isset($wp_roles))
			{
				$wp_roles = new WP_Roles();
				$wp_roles->use_db = true;
			}
			
			// Get all roles
			global $wp_roles;
			$roleIDs = $wp_roles->get_names();
			foreach ($roleIDs as $roleID => $publicID) 
			$wp_roles->remove_cap($roleID, $capID);
		}
		
		function checkVersion()
		{
			// Check if updates required
			
			// Get current version from Wordpress API
			$currentVersion = $this->get_name().'-'.$this->get_version();
			
			// Get last known version from adminOptions
			$lastVersion = $this->adminOptions['LastVersion'];
			
			// Compare versions
			if ($currentVersion === $lastVersion)
				return false;
			
			// Save current version to options			
			$this->adminOptions['LastVersion'] = $currentVersion;
			$this->saveOptions();
			
			return ($lastVersion != '');
		}
		
		function get_pluginInfo($att = '')
		{
			if (!isset($this->pluginInfo))
			{
				if (!function_exists('get_plugins'))
					require_once(ABSPATH . 'wp-admin/includes/plugin.php');
				$allPluginsInfo = get_plugins();				
				if (isset($this->opts['PluginFolder']))
				{
					$basename = $this->opts['PluginFolder'];
				}
				else
				{
					$basename = plugin_basename(__FILE__);
					for ($i = 0; ($i < 10) && strpos($basename, '/'); $i++)
						$basename = dirname($basename);
				}
								
				foreach ($allPluginsInfo as $pluginPath => $pluginInfo)
				{
					if ($basename == dirname($pluginPath))
					{
						$this->pluginInfo = $pluginInfo;
						break;
					}
				}
			}
			
			if ($att == '')
				return $this->pluginInfo;
			
			return isset($this->pluginInfo[$att]) ? $this->pluginInfo[$att] : '';
		}
		
		function get_domain()
		{
			// This function returns a default profile (for translations)
			// Descendant classes can override this if required)
			return basename(dirname(dirname(__FILE__)));
		}
		
		function get_pluginName()
		{
			return $this->get_name();
		}
		
		function get_name()
		{
			return $this->get_pluginInfo('Name');
		}
		
		function get_version()
		{
			return $this->get_pluginInfo('Version');
		}
		
		function get_author()
		{
			return $this->get_pluginInfo('Author');
		}
		
		function get_pluginURI()
		{
			return $this->get_pluginInfo('PluginURI');
		}
		
		function ShowDebugModes()
		{
			$debugFlagsArray = $this->getDebugFlagsArray();
			asort($debugFlagsArray);
			if (count($debugFlagsArray) > 0)
			{
				echo  '<strong>'.__('Session Debug Modes', $this->get_domain()).':</strong> ';	
				$comma = '';		
				foreach ($debugFlagsArray as $debugMode)
				{
					$debugMode = str_replace(self::SessionDebugPrefix, '', $debugMode);
					echo "$comma$debugMode";
					$comma = ', ';
				}
				echo "<br>\n";
				$hasDebug = true;			
			}
			else
			{
				$hasDebug = false;			
			}
			
			if (defined('CARDZNETLIB_BLOCK_HTTPS'))
			{
				echo  '<strong>'.__('SSL over HTTP', $this->get_domain()).":</strong> Blocked<br>\n";	
			}
			
			return $hasDebug;
		}
		
		function InTestMode()
		{
			if (!CardzNetLibUtilsClass::IsElementSet('session', 'cardznetlib_debug_test')) return false;
		
			if (!function_exists('wp_get_current_user')) return false;
			
			return current_user_can(CARDZNETLIB_CAPABILITY_DEVUSER);
		}
		
		function createDBTable($table_name, $tableIndex, $dropTable = false)
		{
			if ($dropTable)
				$this->DropTable($table_name);

			$sql  = "CREATE TABLE ".$table_name.' (';
			$sql .= $tableIndex.' INT UNSIGNED NOT NULL AUTO_INCREMENT, ';
			$sql .= $this->getTableDef($table_name);
			$sql .= 'UNIQUE KEY '.$tableIndex.' ('.$tableIndex.')
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';
			
			//excecute the query
			$this->dbDelta($sql);	
		}
		
		function getTableDef($tableName)
		{
			$sql = "";
			
			if ($tableName == $this->DBTables->Settings)
			{
				$sql .= "
					option_name VARCHAR(50),
					option_value LONGTEXT,
				";
			}
					
			return $sql;
		}

		function dbDelta($sql)
		{
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			// Remove any blank lines - dbDelta is fussy and doesn't like them ...'
			$sql = preg_replace('/^[ \t]*[\r\n]+/m', '', $sql);
			$this->ShowSQL($sql);
			dbDelta($sql);
		}
		
		function tableExists($table_name)
		{
			global $wpdb;
			
			$sql = "SHOW TABLES LIKE '$table_name'";			
			$rslt = $this->get_results($sql);
			
			return ( count($rslt) > 0 );			
		}
		
		static function GetSafeString($paramId, $defaultVal = '')
		{
			$rtnVal = CardzNetLibHTTPIO::GetRequestedString($paramId, $defaultVal);
			$rtnVal = self::_real_escape($rtnVal);
			return $rtnVal;
		}
		
		static function _real_escape($string) 
		{
			global $wpdb;
			return $wpdb->_real_escape($string);
		}
		
		function GetInsertId()
		{
			global $wpdb;
			
			return $wpdb->insert_id;
		}

		function getColumnSpec($table_name, $colName)
		{
			$sql = "SHOW COLUMNS FROM $table_name WHERE field = '$colName'";			 

			$typesArray = $this->get_results($sql);

			return isset($typesArray[0]) ? $typesArray[0] : '';
		}
		
		function DeleteColumnIfExists($table_name, $colName)
		{
			if (!$this->IfColumnExists($table_name, $colName))
				return;
				
			$this->deleteColumn($table_name, $colName);
		}
		
		function deleteColumn($table_name, $colName)
		{
 			$sql = "ALTER TABLE $table_name DROP $colName";

			$this->query($sql);	
			return "OK";							
		}
		
		function IfColumnExists($table_name, $colName)
		{
			if (!$this->tableExists($table_name)) return false;
			
			$colSpec = $this->getColumnSpec($table_name, $colName);
			return (isset($colSpec->Field));
		}
		
		function CopyTable($src_table_name, $dest_table_name, $dropTable = false)
		{
			global $wpdb;

			$sql  = "CREATE TABLE ".$dest_table_name.' ';
			$sql .= "SELECT * FROM ".$src_table_name.';';
			
			$this->ShowSQL($sql);
			$wpdb->query($sql);			
		}
					
		function DropTable($table_name)
		{
			global $wpdb;
			
			$sql = "DROP TABLE IF EXISTS $table_name";
			$this->ShowSQL($sql);
			$wpdb->query($sql);			
		}
		
		function TruncateTable($table_name)
		{
			global $wpdb;
			
			$sql = "TRUNCATE TABLE $table_name";
			$this->ShowSQL($sql);
			$wpdb->query($sql);			
		}
		
		function StartTransaction()
		{
			$sql = "START TRANSACTION";
			$this->query($sql);			
		}
		
		function RollbackTransaction()
		{
			$sql = "ROLLBACK";
			$this->query($sql);			
		}
		
		function GetSearchSQL($searchtext, $searchFields)
		{
			$this->searchText = $searchtext;
			if ($searchtext == '') return '';
			
			$sqlWhere = '(';
			$sqlOr = '';				
			foreach ($searchFields as $searchField)
			{
				$sqlWhere .= $sqlOr;
				$sqlWhere .= $searchField.' LIKE "%'.$searchtext.'%"';
				$sqlOr = ' OR ';
			}
			$sqlWhere .= ')';
			
			$this->searchSQL = $sqlWhere;
			return $sqlWhere;
		}
		
		function AddSearchParam(&$currentURL)
		{
			if (isset($this->searchText) && ($this->searchText != ''))
			{
				$currentURL .= '&lastsalessearch='.$this->searchText;
			}
		}
		
		function getOptionsFromDB()
		{
			if (!isset($this->opts['CfgOptionsID']))
			{
				echo 'CfgOptionsID must be defined<br>';
				exit;
			}
			
			if (!isset($this->opts['DbgOptionsID']))
			{
				echo 'DbgOptionsID must be defined<br>';
				exit;
			}
			
			// Get current values from MySQL
			$currOptions = $this->ReadSettings($this->opts['CfgOptionsID']);
			if (CardzNetLibUtilsClass::IsElementSet('get', 'nodbg'))
			{
				$this->dbgOptions = array();
				$this->WriteSettings($this->opts['DbgOptionsID'], $this->dbgOptions);
			}
			else
			{
				$this->dbgOptions = $this->ReadSettings($this->opts['DbgOptionsID']);
			}

			return $currOptions;
		}
		
		function getOptions($childOptions = array())
		{			
			// Initialise settings array with default values			
			$ourOptions = array(
				'ActivationCount' => 0,
				'LastVersion' => '',
				
				'OrganisationID' => get_bloginfo('name'),
				
				'BccEMailsToAdmin' => true,
				'UseCurrencySymbol' => false,
				
				'LogsFolderPath' => 'logs',
				'PageLength' => CARDZNETLIB_EVENTS_PER_PAGE,
				
				'Unused_EndOfList' => ''
			);
			
			$ourOptions = array_merge($ourOptions, $childOptions);
			
			// Get current values from MySQL
			$currOptions = $this->getOptionsFromDB();
			
			// Now update defaults with values from DB
			if (!empty($currOptions))
			{
				$saveToDB = false;
				foreach ($currOptions as $key => $option)
					$ourOptions[$key] = $option;
			}
			else
			{
				// New options ... save to DB
				$saveToDB = true;
			}

			$this->pluginInfo['Name'] = $this->get_name();
			$this->pluginInfo['Version'] = $this->get_version();
			$this->pluginInfo['Author'] = $this->get_author();
			$this->pluginInfo['PluginURI'] = $this->get_pluginURI();
			$ourOptions['pluginInfo'] = $this->pluginInfo;
			
			$this->adminOptions = $ourOptions;
			
			if ($saveToDB)
				$this->saveOptions();// Saving Options - in getOptions functions
				
			
			return $ourOptions;
		}
		
		function GetAllSettingsList()
		{			
			$ourOptions = $this->getOptions();
			
			$current = new stdClass;

			foreach ($ourOptions as $key => $value)
			{
				$current->$key = $value;				
			}
			
			$settingsList[0] = $current;
			return $settingsList;
		}
		
		function getDbgOption($optionID)
		{
			$rtnVal = '';
			return $rtnVal;
		}
		
		function setOption($optionID, $optionValue, $optionClass = self::ADMIN_SETTING)
		{
			switch ($optionClass)
			{
				case self::ADMIN_SETTING: 
					$this->adminOptions[$optionID] = $optionValue;
					break;

				case self::DEBUG_SETTING: 
					$this->dbgOptions[$optionID] = $optionValue;
					break;
				
				default:
					return '';					
			}
			
			return $optionValue;
		}
		
		function isDbgOptionSet($optionID)
		{
			$rtnVal = false;
			
			return $rtnVal;
		}
		
		function isOptionSet($optionID, $optionClass = self::ADMIN_SETTING)
		{
			$value = $this->getOption($optionID, $optionClass);
			if ($value == '')
				return false;
			
			return true;
		}
		
		// Saves the admin options to the options data table
		function saveOptions()
		{
			$this->WriteSettings($this->opts['CfgOptionsID'], $this->adminOptions);
			$this->WriteSettings($this->opts['DbgOptionsID'], $this->dbgOptions);
		}
		
		function NormaliseSettings($settings)
		{		
			return $settings;	
		}
		
		function ReadSettings($optionName)
		{
			static $firstTime = true;
			
			if ($firstTime)
			{
				if (!$this->tableExists($this->DBTables->Settings))
				{
					$dbPrefix = $this->getTablePrefix();
					$defaultSettingsTable = $dbPrefix.'mjslibOptions';
					if ( ($this->DBTables->Settings != $defaultSettingsTable)
					  && ($this->tableExists($defaultSettingsTable)) )
					{
						$this->CopyTable($defaultSettingsTable, $this->DBTables->Settings);
						$this->DropTable($defaultSettingsTable);
					}
					else
						$this->createDBTable($this->DBTables->Settings, 'optionID');				
				}
				$firstTime = false;
			}		
			
			$sql = "SELECT * FROM ".$this->DBTables->Settings." WHERE option_name='$optionName'";
			$rslt = $this->get_results($sql);
			if (count($rslt) == 0) 
			{
				$settings = get_option($optionName, null);
				if ($settings === null)
				{
					$settings = get_option($optionName.'_', null);
				}
				if ($settings === null)
				{
					$settings = array();
				}
				$serializedValue = addslashes(serialize($settings));
				$sql  = 'INSERT INTO '.$this->DBTables->Settings.'(option_name, option_value)';
				$sql .= ' VALUES("'.$optionName.'", "'.$serializedValue.'")';
				$this->query($sql);	
				
				delete_option($optionName);
				delete_option($optionName.'_');
			}
			else
			{
				$settings = $rslt[0]->option_value;
				$settings = unserialize($settings);				
			}
			return $settings;
		}
		
		function WriteSettings($optionName, $settings)
		{
			$settings = addslashes(serialize($settings));
			$sql  = "UPDATE ".$this->DBTables->Settings." SET ";
			$sql .= 'option_value = "'.$settings.'" ';
			$sql .= "WHERE option_name='$optionName'";
			$this->query($sql);	
		}
		
		function DeleteSettings($optionName)
		{
			delete_option($optionName);		// Settings were in wp_options
			
			$sql  = "DELETE FROM ".$this->DBTables->Settings." ";
			$sql .= "WHERE option_name='$optionName'";
			$this->query($sql);	
		}
		
		function dev_ShowTrolley()
		{
			$rtnVal = false;
			
			if ($this->isDbgOptionSet('Dev_ShowTrolley') || CardzNetLibUtilsClass::IsElementSet('session', 'cardznetlib_debug_trolley'))
			{
				if ($this->getDbgOption('Dev_ShowCallStack') || CardzNetLibUtilsClass::IsElementSet('session', 'cardznetlib_debug_stack'))
				{
					CardzNetLibUtilsClass::ShowCallStack();
				}
				$rtnVal = true;
			}

			return $rtnVal;
		}
		
		function GetRowsPerPage()
		{
			if (isset($this->adminOptions['PageLength']))
				$rowsPerPage = $this->adminOptions['PageLength'];
			else
				$rowsPerPage = CARDZNETLIB_EVENTS_PER_PAGE;
			
			return $rowsPerPage;
		}
		
		function clearAll()
		{
			$this->DropTable($this->DBTables->Settings);
		}
		
		function createDB($dropTable = false)
		{
		}
		
		function ArrayValsToDefine($optionsList, $indent = '    ')
			{
			$defines = " array(\n";
				foreach ($optionsList as $optionID => $optionValue)
				{
				if (is_array($optionValue))
				{
					$optionValue = $this->ArrayValsToDefine($optionValue, $indent.'    ');			
				}				
				else
				{
					$optionValue = "'$optionValue'";
				}
				$defines .= "$indent'$optionID' => $optionValue,\n";
			}
			
			$defines .= "$indent)";			
								
			return $defines;
		}
		
		function OptionsToDefines($globalVarId, $optionsList)
		{
			$optionID = '$'.$globalVarId;
			
			$defines = '$'.$globalVarId." = ";
					
			$defines .= $this->ArrayValsToDefine($optionsList).";\n\n";			

			return $defines;
		}
		
		static function StripURLRoot($url)
		{
			$url = substr($url, strpos($url, '://')+3);
			return $url;
		}
		
		static function GetTimeFormat()
		{
			if (defined('CARDZNETLIB_TIME_BOXOFFICE_FORMAT'))
				$timeFormat = CARDZNETLIB_TIME_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Time Format
				$timeFormat = get_option( 'time_format' );
				
			return $timeFormat;
		}

		static function GetDateFormat()
		{
			if (defined('CARDZNETLIB_DATE_BOXOFFICE_FORMAT'))
				$dateFormat = CARDZNETLIB_DATE_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Date Format
				$dateFormat = get_option( 'date_format' );
				
			return $dateFormat;
		}

		static function GetDateTimeFormat()
		{
			if (defined('CARDZNETLIB_DATETIME_BOXOFFICE_FORMAT'))
				$dateFormat = CARDZNETLIB_DATETIME_BOXOFFICE_FORMAT;
			else
				// Use Wordpress Date and Time Format
				$dateFormat = get_option( 'date_format' ).' '.get_option( 'time_format' );
				
			return $dateFormat;
		}
		
		static function GetLocalDateTime()
		{
			$localTime = date(CardzNetLibDBaseClass::MYSQL_DATETIME_FORMAT, current_time('timestamp'));
			return $localTime;
		}
		
		function get_JSandCSSver()
		{
			static $ver = false;
			
			if ($ver == false)
			{
				if ($this->isDbgOptionSet('Dev_DisableJSCache')) 
					$ver = time();
				else
					$ver = $this->get_version();
			}
			
			return $ver;
		}
		
		function enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' )
		{
			$ver = $this->get_JSandCSSver();
			wp_enqueue_style($handle, $src, $deps, $ver, $media);
		}
		
		function enqueue_script($handle, $src = false, $deps = array(), $ver = false, $in_footer = false)
		{
			$ver = $this->get_JSandCSSver();
			wp_enqueue_script($handle, $src, $deps, $ver, $in_footer);
		}
		
		function DoTemplateLoop($section, $loopType, $saleRecord)	
		{				
			$emailContent = '';
			
			switch ($loopType)
			{
				case '[startloop]':
					foreach($saleRecord as $ticket)
					{
						$emailContent .= $this->AddEMailFields($section, $ticket);
					}
					break;
				
				default:
					$emailContent = "<br><strong>Unknown Loop Definition in Template ($loopType)</strong><br><br>";
					break;
			}
			
			return $emailContent;
		}
		
		function AddFieldsToTemplate($dbRecord, $mailTemplate, &$EMailSubject, &$emailContent)	
		{				
			$emailContent = '';
			
			// Find the line with the open php entry then find the end of the line
			$posnPHP = stripos($mailTemplate, '<?php');
			if ($posnPHP !== false) $posnPHP = strpos($mailTemplate, "\n", $posnPHP);
			if ($posnPHP !== false) $posnEOL = strpos($mailTemplate, "\n", $posnPHP+1);
			if (($posnPHP !== false) && ($posnEOL !== false)) 
			{
				$EMailSubject = $this->AddEMailFields(substr($mailTemplate, $posnPHP, $posnEOL-$posnPHP), $dbRecord[0]);
				$mailTemplate = substr($mailTemplate, $posnEOL);
			}
						
			// Find the line with the close php entry then find the start of the line
			$posnPHP = stripos($mailTemplate, '?>');
			if ($posnPHP !== false) $posnPHP = strrpos(substr($mailTemplate, 0, $posnPHP), "\n");
			if ($posnPHP !== false) $mailTemplate = substr($mailTemplate, 0, $posnPHP);

			$loopCount = 0;
			for (; $loopCount < 10; $loopCount++)
			{
				if (preg_match('/(\[[a-zA-Z0-9]*loop\])/', $mailTemplate, $matches) != 1)
					break;

				$loopStart = stripos($mailTemplate, $matches[0]);
				$loopEnd = stripos($mailTemplate, '[endloop]');

				if (($loopStart === false) || ($loopEnd === false))
					break;

				$section = substr($mailTemplate, 0, $loopStart);
				$emailContent .= $this->AddEMailFields($section, $dbRecord[0]);

				$loopStart += strlen($matches[0]);
				$loopLen = $loopEnd - $loopStart;

				$section = substr($mailTemplate, $loopStart, $loopLen);
				$emailContent .= $this->DoTemplateLoop($section, $matches[0], $dbRecord);

				$loopEnd += strlen('[endloop]');
				$mailTemplate = substr($mailTemplate, $loopEnd);
			}

			// Process the rest of the mail template
			$emailContent .= $this->AddEMailFields($mailTemplate, $dbRecord[0]);
			
			return 'OK';		
		}
		
		function FormatEMailField($tag, $field, &$saleDetails)
		{
			return $saleDetails->$field;
		}
		
		function AddEMailFields($EMailTemplate, $saleDetails)
		{
			foreach ($saleDetails as $key => $value)
			{
				$tag = '['.$key.']';
				$value = $this->FormatEMailField($tag, $key, $saleDetails);
				$EMailTemplate = str_replace($tag, $value, $EMailTemplate);
			}
			
			$EMailTemplate = $this->DoEmbeddedImage($EMailTemplate, 'logoimg', 'PayPalLogoImageFile');
			
			return $EMailTemplate;
		}
					
		function GetAdminEMail()
		{
			return '';
		}
					
		function GetServerEmail()
		{
			return '';
		}
					
		function ReadTemplateFile($Filepath)
		{
			$hfile = fopen($Filepath,"r");
			if ($hfile != 0)
			{
				$fileLen = filesize($Filepath);
				if ($fileLen > 0)
					$fileContents = fread($hfile, $fileLen);
				else
					$fileContents = '';
				fclose($hfile);
			}
			else
			{
				echo "Error reading $Filepath<br>\n";
				$fileContents = '';
			}

			return $fileContents;
		}
		
		function ReadEMailTemplateFile($Filepath)
		{
			// Added check for template file lines beginning with a dot '.'
			// Add a space before to prevent any problem with SMTP "dot stuffing"
			// See https://tools.ietf.org/html/rfc5321#section-4.5.2 
			$template = $this->ReadTemplateFile($Filepath);
			$this->mailTemplate_origLen = strlen($template);
			$template = preg_replace("/^(\..*)/m", "  $1", $template);
			$this->mailTemplate_newLen = strlen($template);
			
			if ( ($this->mailTemplate_origLen != $this->mailTemplate_newLen) 
			  && (CardzNetLibUtilsClass::IsElementSet('post', 'EMailSale_DebugEnabled')) )
			{
				echo "************************************************************************<br>\n";
				echo __("WARNING: EMail Template contains one or mores lines with leading dots ('.')", $this->get_domain())."<br>\n";
				echo "************************************************************************<br><br>\n";
			}

			return $template;
		}
		
		function AddRecordToTemplate($dbRecord, $templatePath, &$EMailSubject, &$emailContent)	
		{				
			$EMailSubject = "EMail Subject NOT Defined";
			$mailTemplate = $this->ReadEMailTemplateFile($templatePath);
			if (strlen($mailTemplate) == 0)
				return "EMail Template Not Found ($templatePath)";
			
			$posnEndPHP = stripos($mailTemplate, '?>');
			$templateFooter = substr($mailTemplate, $posnEndPHP);
			$footerLines = explode("\n", $templateFooter);
			foreach ($footerLines as $footerLine)
			{
				$fields = explode(':', $footerLine);
				if (count($fields) > 1)
				{
					switch ($fields[0])
					{
						case 'Attach':
							$filename = trim($fields[1]);
							
							$filepath = dirname(WP_CONTENT_DIR).'/'.$filename;
							if (isset($this->emailObj))	
							{
								$this->emailObj->AddAttachment($filepath);
							}
							else
							{
								$this->emailAttachments[] = $filepath;
							}
							break;
							
						default:
							break;
					}
					
				}
			}				

			$status = $this->AddFieldsToTemplate($dbRecord, $mailTemplate, $EMailSubject, $emailContent);
			$emailContent = apply_filters($this->get_domain().'_filter_emailbody', $emailContent, $dbRecord[0]);
			return $status;
		}
		
		function BuildEMailFromTemplate($eventRecord, $templatePath)
		{		
			$EMailSubject = '';
			$emailContent = '';

			include $this->emailClassFilePath;
			$this->emailObj = new $this->emailObjClass($this);
			$this->emailObj->adminEMail = $this->GetAdminEMail();
			
			$rtnval['status'] = $this->AddRecordToTemplate($eventRecord, $templatePath, $EMailSubject, $emailContent);	
			if ($rtnval['status'] == 'OK')
			{
				$rtnval['subject'] = $EMailSubject;
				$rtnval['email'] = $emailContent;
			}
			
			return $rtnval;
		}
		
		function SendEMailWithDefaults($eventRecord, $EMailSubject, $EMailContent, $EMailTo = '', $headers = '')
		{
			// Get email address and organisation name from settings
			$EMailFrom = $this->GetServerEmail();

			$rtnStatus = $this->emailObj->sendMail($EMailTo, $EMailFrom, $EMailSubject, $EMailContent, '', $headers);

			return $rtnStatus;		
		}
		
		function SendEMailFromTemplate($eventRecord, $templatePath, $EMailTo = '')
		{		
			$emailRslt = $this->BuildEMailFromTemplate($eventRecord, $templatePath, $EMailTo);
			$rtnstatus = $emailRslt['status'];
			
			if ($rtnstatus == 'OK')
			{
				$rtnstatus = $this->SendEMailWithDefaults($eventRecord, $emailRslt['subject'], $emailRslt['email'], $EMailTo);
			}
			
			return $rtnstatus;
		}
		
		function SendEMailByTemplateID($eventRecord, $templateID, $folder, $EMailTo = '')
		{	
			// EMail Template defaults to templates folder
			$pluginID = basename(dirname(dirname(__FILE__)));
			$templateRoot = CARDZNETLIB_UPLOADS_PATH.'/'.$folder.'/';
			$templatePath = $templateRoot.$this->adminOptions[$templateID];

			return $this->SendEMailFromTemplate($eventRecord, $templatePath, $EMailTo);
		}
		
	}
}



