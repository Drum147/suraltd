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

require_once "cardznetlib_utils.php";

if (!class_exists('CardzNetLibGenericDBaseClass'))
{
	if (!defined('CARDZNETLIB_DATETIME_ADMIN_FORMAT'))
		define('CARDZNETLIB_DATETIME_ADMIN_FORMAT', 'Y-m-d H:i');

	if (!defined('CARDZNETLIB_CAPABILITY_SYSADMIN'))
		define('CARDZNETLIB_CAPABILITY_SYSADMIN', 'manage_options');

	define ('CARDZNETLIB_SESSION_VALIDATOR', 'cardznetlib_session_valid');
	
	if (!defined('CARDZNETLIB_CACHEDPAGE_TIMEDELTA'))
		define('CARDZNETLIB_CACHEDPAGE_TIMEDELTA', 60);

	if (!defined('CARDZNETLIB_DATABASE_LOGFILE'))
		define('CARDZNETLIB_DATABASE_LOGFILE', 'SQL.log');
		
	class CardzNetLibGenericDBaseClass // Define class
	{
		const ADMIN_SETTING = 1;
		const DEBUG_SETTING = 2;
		
		var $hideSQLErrors = false;
		
		// This class does nothing when running under WP
		// Overload this class with DB access functions for non-WP access
		function __construct() //constructor		
		{
			$this->SetMySQLGlobals();
		}	
		
		function IsPageCached()
		{
			if (!CardzNetLibUtilsClass::IsElementSet('post', 'pageServerTime')) return false;
			if (!CardzNetLibUtilsClass::IsElementSet('post', 'pageClientTime')) return false;
			if (!CardzNetLibUtilsClass::IsElementSet('post', 'requestClientTime')) return false;
			
			$pageGeneratedServerTime = CardzNetLibUtilsClass::GetHTTPNumber('post', 'pageServerTime');
			$pageGeneratedClientTime = CardzNetLibUtilsClass::GetHTTPNumber('post', 'pageClientTime');

			$jQueryRequestClientTime = CardzNetLibUtilsClass::GetHTTPNumber('post', 'requestClientTime');
			$jQueryRequestServerTime = time();
			
			// Calculate Time Offset of Local Machine - +ve if Client is Set Slow
			$jQueryRequestTimeOffset = $jQueryRequestServerTime - $jQueryRequestClientTime;
			
			// Now calculate lenght of time page has been cached
			$pageGeneratedTimeOffset = $pageGeneratedServerTime - $pageGeneratedClientTime;
			$timeInCache = $jQueryRequestTimeOffset - $pageGeneratedTimeOffset;
			
			if ($this->getDbgOption('Dev_ShowMiscDebug'))
			{
				echo "pageGeneratedClientTime: $pageGeneratedClientTime <br>\n";
				echo "pageGeneratedServerTime: $pageGeneratedServerTime <br>\n";
				echo "jQueryRequestClientTime: $jQueryRequestClientTime <br>\n";
				echo "jQueryRequestServerTime: $jQueryRequestServerTime <br>\n";
				echo "jQueryRequestTimeOffset: $jQueryRequestTimeOffset <br>\n";
				echo "pageGeneratedTimeOffset: $pageGeneratedTimeOffset <br>\n";				
				echo "timeInCache: $timeInCache <br>\n";
			}
			          
			return (abs($timeInCache) >= CARDZNETLIB_CACHEDPAGE_TIMEDELTA);
		}

		function SessionVarsAvailable()
		{
			$rtnVal = CardzNetLibUtilsClass::IsElementSet('session', CARDZNETLIB_SESSION_VALIDATOR);
			return $rtnVal;
		}
		
		function SetMySQLGlobals()
		{			
			$this->hideSQLErrors = true;
			$rtnVal = $this->query("SET SQL_BIG_SELECTS=1");
			$this->hideSQLErrors = false;
			if (!$rtnVal)
			{
				// Use the old version of the query if it fails
				$rtnVal = $this->query("SET OPTION SQL_BIG_SELECTS=1");
			}
			
			// Get the sql mode 
			$globs = $this->GetMySQLGlobals();
			$hasFullGroupMode = strpos($globs->mode, 'ONLY_FULL_GROUP_BY');			
			$wantFullGroupMode = defined('CARDZNETLIB_ONLY_FULL_GROUP_BY');
			if ($hasFullGroupMode != $wantFullGroupMode)
			{
				if ($wantFullGroupMode)
				{
					// Add the ONLY_FULL_GROUP_BY mode
					$newModes = $globs->mode;
					if ($newModes != '') $newModes .= ',';
					$newModes .= 'ONLY_FULL_GROUP_BY';
				}
				else
				{
					// Remove the ONLY_FULL_GROUP_BY mode
					$modes = explode(',',$globs->mode);
					$newModes = '';
					foreach ($modes as $mode)
					{
						if ($mode == 'ONLY_FULL_GROUP_BY') continue;
						if ($newModes != '') $newModes .= ',';
						$newModes .= $mode;
					}					
				}
				$sql = "SET SESSION sql_mode='$newModes';";				
				$this->query($sql);
			}
			
			return $rtnVal;
		}
		
		function GetMySQLGlobals()
		{
			$sql = "SELECT VERSION() AS version, @@SESSION.sql_mode AS mode";
			$sqlFilters['noLoginID'] = true;
			$globResult = $this->get_results($sql, true, $sqlFilters);
			return $globResult[0];
		}
		
		function ShowDBErrors()
		{
			if ($this->hideSQLErrors)
				return;
				
			global $wpdb;
			if ($wpdb->last_error == '')
				return;
				
			echo '<div id="message" class="error"><p>'.$wpdb->last_error.'</p></div>';
		}

		function LogSQL($sql, $queryResult)
		{			
		}

		function ShowSQL($sql, $values = null)
		{			
			if (!$this->isDbgOptionSet('Dev_ShowSQL'))
			{
				return;				
			}
			
			if ($this->isDbgOptionSet('Dev_ShowMemUsage'))
			{
				echo "Memory Usage=".memory_get_usage()." Peak=".memory_get_peak_usage()." Max=".ini_get('memory_limit')." bytes<br>\n";				
			}
			
			if ($this->isDbgOptionSet('Dev_ShowCallStack'))
			{
				CardzNetLibUtilsClass::ShowCallStack();
			}
			
			$sql = htmlspecialchars($sql);
			$sql = str_replace("\n", "<br>\n", $sql);
			echo "<br>$sql<br>\n";
			if (isset($values))
			{
				print_r($values);
				echo "<br>\n";
			}
		}
		
		function queryWithPrepare($sql, $values)
		{
			global $wpdb;
			
			$sql = $wpdb->prepare($sql, $values);
			
			return $this->query($sql);
		}
		
		function query($sql)
		{
			global $wpdb;
			
			$this->ShowSQL($sql);

			if ($this->hideSQLErrors)
			{
				$suppress_errors = $wpdb->suppress_errors;
				$wpdb->suppress_errors = true;
			}
			$this->queryResult = $wpdb->query($sql);
			if ($this->isDbgOptionSet('Dev_ShowDBOutput'))
			{
				echo "Query Result: ".$this->queryResult." <br>\n";
			}
			$rtnStatus = ($this->queryResult !== false);	
			if ($this->hideSQLErrors)
			{
				$wpdb->suppress_errors = $suppress_errors;				
			}
			else
			{
				$this->ShowDBErrors();
			}		
			
			$this->LogSQL($sql, $this->queryResult);

			return $rtnStatus;		
		}

		function GetInsertId()
		{
			global $wpdb;
			
			return $wpdb->insert_id;
		}

		function getresultsWithPrepare($sql, $values)
		{
			global $wpdb;
			
			$sql = $wpdb->prepare($sql, $values);
			
			return $this->get_results($sql);
		}
		
		function get_results($sql, $debugOutAllowed = true, $sqlFilters = array())
		{
			global $wpdb;
			
			$this->ShowSQL($sql);
			$results = $wpdb->get_results($sql);
			if ($debugOutAllowed) $this->show_results($results);
			
			$this->ShowDBErrors();
			
			return $results;
		}
		
		function show_results($results)
		{
			if (!$this->isDbgOptionSet('Dev_ShowDBOutput'))
			{				
				if ($this->isDbgOptionSet('Dev_ShowSQL'))
				{
					$entriesCount = count($results);
					echo "Database Result Entries: $entriesCount<br>\n";
					return;				
				}
				return;
			}
				
			if (function_exists('wp_get_current_user'))
			{
				if (!$this->isSysAdmin())
					return;				
			}
				
			echo "<br>Database Results:<br>\n";
			for ($i = 0; $i < count($results); $i++)
				echo "Array[$i] = " . print_r($results[$i], true) . "<br>\n";
		}
		
		function ForceSQLDebug($activate=true)
		{
			if ($activate)
			{
				if (!isset($this->Last_Dev_ShowSQL))
				{
					$this->Last_Dev_ShowSQL = $this->isDbgOptionSet('Dev_ShowSQL');
					$this->Last_Dev_ShowDBOutput = $this->isDbgOptionSet('Dev_ShowDBOutput');
					$this->Last_Dev_ShowCallStack = $this->isDbgOptionSet('Dev_ShowCallStack');					
				}
				
				$this->dbgOptions['Dev_ShowSQL'] = true;
				$this->dbgOptions['Dev_ShowDBOutput'] = true;
				$this->dbgOptions['Dev_ShowCallStack'] = true;
			}
			else
			{
				if (isset($this->Last_Dev_ShowSQL))
				{
					$this->dbgOptions['Dev_ShowSQL'] = $this->Last_Dev_ShowSQL;
					$this->dbgOptions['Dev_ShowDBOutput'] = $this->Last_Dev_ShowDBOutput;
					$this->dbgOptions['Dev_ShowCallStack'] = $this->Last_Dev_ShowCallStack;
				}
			}
		}
		
		function GetSQLBlockEnd($sql, $startPosn, $startChar = '(', $endChar = ')')
		{
			$posn = $startPosn;
			$len = strlen($sql);
			$matchCount = 0;
			
			while ($posn < $len)
			{
				$nxtChar = $sql[$posn];
				if ($nxtChar == $startChar)
				{
					$matchCount++;
				}
				else if ($nxtChar == $endChar)
				{
					$matchCount--;
					if ($matchCount == 0)
					{
						return $posn;
					}
				}
				$posn++;
			}
			
			return -1;
		}
		
		function isSysAdmin()
		{
			if (!function_exists('wp_get_current_user')) 
			{
				return false;
			}
			
			if (current_user_can(CARDZNETLIB_CAPABILITY_SYSADMIN))
			{
				return true;
			}
				
			if (defined('CARDZNETLIB_CAPABILITY_DEVUSER') && current_user_can(CARDZNETLIB_CAPABILITY_DEVUSER))
			{
				return true;
			}
	
			return false;
		}
		
		function getOption($optionID, $optionClass = self::ADMIN_SETTING)
		{
			switch ($optionClass)
			{
				case self::ADMIN_SETTING: 
					$options = $this->adminOptions;
					break;

				case self::DEBUG_SETTING: 
					$options = $this->dbgOptions;
					break;
				
				default:
					return;					
			}
			
			$optionVal = '';		
			if (isset($options[$optionID]))
			{
				$optionVal = $options[$optionID];
			}

			return $optionVal;
		}
		
		function isDbgOptionSet($optionID)
		{
			$rtnVal = false;
			return $rtnVal;
		}
       
		function isOptionSet($optionID, $optionClass = self::ADMIN_SETTING)
		{
			return false;
		}
       
		function FormatCurrencyValue($amount, $asHTML = true)
		{
			$currencyText = sprintf($this->getOption('CurrencyFormat'), $amount);
			return $currencyText;
		}
		
		function FormatCurrency($amount, $asHTML = true)
		{
			$currencyText = $this->FormatCurrencyValue($amount, $asHTML);
			if (!$this->getOption('UseCurrencySymbol'))
				return $currencyText;
				
			if ($asHTML)
			{
				$currencyText = $this->getOption('CurrencySymbol').$currencyText;				
			}
			else
			{
				$currencyText = $this->getOption('CurrencyText').$currencyText;
			}

			return $currencyText;
		}
		
		static function FormatDateForAdminDisplay($dateInDB)
		{
			// Convert time string to UNIX timestamp
			$timestamp = strtotime( $dateInDB );
			
			// Get Time & Date formatted for display to user
			return date(CARDZNETLIB_DATETIME_ADMIN_FORMAT, $timestamp);
		}
		
	}
}



