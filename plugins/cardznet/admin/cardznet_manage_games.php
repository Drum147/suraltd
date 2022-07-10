<?php
/* 
Description: Code for Managing Games
 
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

require_once CARDZNET_INCLUDE_PATH.'cardznetlib_adminlist.php';
require_once CARDZNET_INCLUDE_PATH.'cardznetlib_admin.php';      

if (!class_exists('CardzNetGamesAdminListClass')) 
{
	// --- Define Class: CardzNetGamesAdminListClass
	class CardzNetGamesAdminListClass extends CardzNetLibAdminListClass // Define class
	{	
		const BULKACTION_ENDGAME = 'endgame';
	
		function __construct($env) //constructor
		{
			$this->hiddenRowsButtonId = 'TBD';		
			
			// Call base constructor
			parent::__construct($env, true);
			
			$this->allowHiddenTags = false;
			
			$this->hiddenRowsButtonId = __('Details', $this->myDomain);		
			
			//$this->SetRowsPerPage(self::CARDZNETLIB_EVENTS_UNPAGED);
			
			$this->bulkActions = array(
				self::BULKACTION_ENDGAME => __('End Game', $this->myDomain),
				);
					
			if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				$this->bulkActions[self::BULKACTION_DELETE] = __('Delete', $this->myDomain);
			}
					
			$this->HeadersPosn = CardzNetLibTableClass::HEADERPOSN_BOTH;
			
		}
		
		function GetRecordID($result)
		{
			return $result->gameId;
		}
		
		function GetCurrentURL() 
		{			
			$currentURL = parent::GetCurrentURL();
			return $currentURL;
		}
		
		function GetDetailsRowsFooter()
		{
			$ourOptions = array(
			);
		
			$ourOptions = self::MergeSettings(parent::GetDetailsRowsFooter(), $ourOptions);
			
			return $ourOptions;
		}
		
		function GetTableID($result)
		{
			return "cardznet-games-tab";
		}
		
		function ShowGameDetails($result)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$gameDetails = '';
			$gameResults = $myDBaseObj->GetScoresByGame($result->gameId);
			if (count($gameResults) > 0)
			{
				$gameDetails = $this->BuildGameDetails($gameResults);
			}

			return $gameDetails;
		}
				
		function GetListDetails($result)
		{
			return $this->myDBaseObj->GetGameById($result->gameId);
		}
		
		function BuildGameDetails($gameResults)
		{
			$env = $this->env;

			$gameDetailsList = $this->CreateGameAdminDetailsListObject($env, $this->editMode, $gameResults);	

			// Set Rows per page to disable paging used on main page
			$gameDetailsList->enableFilter = false;
			
			ob_start();	
			$gameDetailsList->OutputList($gameResults);	
			$zoneDetailsOutput = ob_get_contents();
			ob_end_clean();

			return $zoneDetailsOutput;
		}
		
		function NeedsConfirmation($bulkAction)
		{
			switch ($bulkAction)
			{
				default:
					return parent::NeedsConfirmation($bulkAction);
			}
		}
		
		function ExtendedSettingsDBOpts()
		{
			return parent::ExtendedSettingsDBOpts();
		}
		
		function FormatUserName($userId, $result)
		{
			return CardzNetDBaseClass::GetUserName($userId);
		}
		
		function FormatDateForAdminDisplay($dateInDB)
		{
			return CardzNetDBaseClass::FormatAdminDateTime($dateInDB);
		}
		
		function GetMainRowsDefinition()
		{
			$columnDefs = array(
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Game',          CardzNetLibTableClass::TABLEPARAM_ID => 'gameName',          CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Start Date',    CardzNetLibTableClass::TABLEPARAM_ID => 'gameStartDateTime', CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW,  CardzNetLibTableClass::TABLEPARAM_DECODE => 'FormatDateForAdminDisplay', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'No of Players', CardzNetLibTableClass::TABLEPARAM_ID => 'gameNoOfPlayers',   CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
				//array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'End Score',     CardzNetLibTableClass::TABLEPARAM_ID => 'gameEndScore',      CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'End Date',      CardzNetLibTableClass::TABLEPARAM_ID => 'gameEndDateTime',   CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW,  CardzNetLibTableClass::TABLEPARAM_DECODE => 'FormatDateForAdminDisplay', ),
			);
			
			if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				$adminDefs = array(
					array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Manager',   CardzNetLibTableClass::TABLEPARAM_ID => 'gameLoginId',      CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, self::TABLEPARAM_DECODE => 'FormatUserName', CardzNetLibTableClass::TABLEPARAM_AFTER => 'gameName', ),
				);
				
				$columnDefs = self::MergeSettings($columnDefs, $adminDefs);
			}
			
			return $columnDefs;
		}		

		function GetTableRowCount()
		{
			$userId = current_user_can(CARDZNET_CAPABILITY_ADMINUSER) ? 0 : get_current_user_id();
			return $this->myDBaseObj->GetGamesCount($userId);		
		}		

		function GetTableData(&$results, $rowFilter)
		{
			$sqlFilters['sqlLimit'] = $this->GetLimitSQL();
/*
			if ($rowFilter != '')
			{
				$sqlFilters['whereSQL'] = $this->GetFilterSQL($rowFilter);
			}
*/
			// Get list of sales (one row per sale)
			$userId = current_user_can(CARDZNET_CAPABILITY_ADMINUSER) ? 0 : get_current_user_id();
			$results = $this->myDBaseObj->GetGames($userId, $sqlFilters);
		}

		function GetDetailsRowsDefinition()
		{
			$ourOptions = array(
				array(CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_FUNCTION, CardzNetLibTableClass::TABLEPARAM_FUNC => 'ShowGameDetails'),
			);
			
			$rowDefs = self::MergeSettings(parent::GetDetailsRowsDefinition(), $ourOptions);

			return $rowDefs;
		}
		
		function CreateGameAdminDetailsListObject($env, $editMode = false, $gameResults)
		{
			return new CardzNetGamesAdminDetailsListClass($env, $editMode, $gameResults);	
		}
		
	}
}


if (!class_exists('CardzNetGamesAdminDetailsListClass')) 
{
	class CardzNetGamesAdminDetailsListClass extends CardzNetLibAdminDetailsListClass // Define class
	{		
		function __construct($env, $editMode, $gameResults) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->allowHiddenTags = false;
			
			$this->SetRowsPerPage(self::CARDZNETLIB_EVENTS_UNPAGED);
			
			$this->HeadersPosn = CardzNetLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "cardznet-games-list-tab";
		}
		
		function GetRecordID($result)
		{
			return $result->gameId;
		}
		
		function GetDetailID($result)
		{
			return '_'.$result->playerId;
		}
		
		function GetMainRowsDefinition()
		{
			$rtnVal = array(
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Player',  CardzNetLibTableClass::TABLEPARAM_ID => 'playerName',   CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Score',   CardzNetLibTableClass::TABLEPARAM_ID => 'playerScore',  CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
			);
			
			return $rtnVal;
		}
		
		function IsRowInView($result, $rowFilter)
		{
			return true;
		}		
				
	}
}
	
if (!class_exists('CardzNetGamesAdminClass')) 
{
	// --- Define Class: CardzNetGamesAdminClass
	class CardzNetGamesAdminClass extends CardzNetLibAdminClass // Define class
	{		
		var $results;
		var $showOptionsID = 0;
		
		function __construct($env)
		{
			$this->pageTitle = __('Games', $this->myDomain);
			
			parent::__construct($env, true);
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;				
				
			if (CardzNetLibUtilsClass::IsElementSet('post', 'addGameRequest'))
			{
				// Check that the referer is OK
				$this->CheckAdminReferer();		

				// Add Game to Database	- Show game setup page
				$gameName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'gameName');
				$gameDef = $myDBaseObj->GameNameToFileAndClass($gameName);
				$gameClass = $gameDef->class;
				$gameDefFile = $gameDef->srcfile;
				
				include CARDZNET_GAMES_PATH.$gameDefFile;
				
				// Make setup game screen
				$gameObj = new $gameClass($myDBaseObj);
				$titleText = __('Enter Settings for', $this->myDomain);
				echo "<h2>$titleText $gameName</h2>\n";
				echo $gameObj->GetGameDetailsForm();
				
				$this->donePage = true;
			}
			else if (CardzNetLibUtilsClass::IsElementSet('post', 'addGameDetails') && CardzNetLibUtilsClass::IsElementSet('post', 'gameName'))
			{
				// NOTE: Could Check if any users are already in a game
				// For now .... just complete any pending games
				
				// If (already in a game)
				// Get confirmation
				// Otherwise continue with adding game
				
				$gamesList = $myDBaseObj->GetGamesList();
				$gameName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'gameName');

				if (!isset($gamesList[$gameName])) return;
				$gameEntry = $gamesList[$gameName];
				
				$gameClass = $gameEntry->class;
				$gameDefFile = $gameEntry->filename;
			
				include CARDZNET_GAMES_PATH.$gameDefFile;
				
				// NOTE: Could Move create "Game" object to generic class 
				$gameObj = new $gameClass($myDBaseObj);
				$gameObj->ProcessGameDetailsForm($gameName);
			}
			else if (CardzNetLibUtilsClass::IsElementSet('post', 'savechanges'))
			{
			}			
			else if (CardzNetLibUtilsClass::IsElementSet('get', 'action'))
			{
				$this->CheckAdminReferer();
				$this->DoActions();
			}

		}
		
		function Output_MainPage($updateFailed)
		{
			if (isset($this->pageHTML))	
			{
				echo $this->pageHTML;
				return;
			}

			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;				
			
			if (!$myDBaseObj->SettingsOK())
				return;
			
			$groupSelectorHTML = $myDBaseObj->GetGroupSelector();
			if ($groupSelectorHTML == '')
			{
				$text = __("Setup a group first", CARDZNET_DOMAIN_NAME);
				$linkText = __("here", CARDZNET_DOMAIN_NAME);
				CardzNetDBaseClass::GoToPageLink($text, $linkText, CARDZNET_MENUPAGE_GROUPS);
				return;
			}
			
			$actionURL = remove_query_arg('action');
			$actionURL = remove_query_arg('id', $actionURL);
			
			// HTML Output - Start 
			$formClass = $this->myDomain.'-admin-form '.$this->myDomain.'-games-editor';
			echo '
				<div class="'.$formClass.'">
				<form method="post" action="'.$actionURL.'">
				';

			if (isset($this->saleId))
				echo "\n".'<input type="hidden" name="saleID" value="'.$this->saleId.'"/>'."\n";
				
			$this->WPNonceField();
				 
			$noOfGames = $this->OutputGamesList($this->env);;
			if($noOfGames == 0)
			{
				echo "<div class='noconfig'>".__('No Games', $this->myDomain)."</div>\n";
				$lastGameName = '';
			}
			else 
			{				
				$userId = current_user_can(CARDZNET_CAPABILITY_ADMINUSER) ? 0 : get_current_user_id();
				$lastGameName = $this->myDBaseObj->GetLastGameName($userId);
			}

			// Output a selector to choose a new game to play
			echo "<div id=addgame>\n";
			$gamesList = $myDBaseObj->GetGamesList();
			
			echo "<select name=gameName id=gameName>\n";
			foreach ($gamesList as $gameName => $gameEntry)
			{
				$selected = ($gameName == $lastGameName) ? ' selected=""' : '';
				echo "<option $selected>$gameName</option>\n";
			}
			echo "</select>\n";			
			
			echo $groupSelectorHTML;
			$this->OutputButton("addGameRequest", __("Add Game", $this->myDomain));
			echo "</div>\n";
			
/*			
			if (count($this->results) > 0)
			{
				$this->OutputButton("savechanges", __("Save Changes", $this->myDomain), "button-primary");
			}
*/
?>
	<br></br>
	</form>
	</div>
<?php
		} // End of function Output_MainPage()


		function OutputGamesList($env)
		{
			$myPluginObj = $this->myPluginObj;
			
			$classId = $myPluginObj->adminClassPrefix.'GamesAdminListClass';
			$gamesListObj = new $classId($env);
			$gamesListObj->showOptionsID = $this->showOptionsID;
			return $gamesListObj->OutputList($this->results);		
		}
				
		function DoActions()
		{
			$rtnVal = false;
			$myDBaseObj = $this->myDBaseObj;

			switch (CardzNetLibUtilsClass::GetHTTPTextElem('get', 'action'))
			{
				default:
					$rtnVal = false;
					break;
					
			}
				
			return $rtnVal;
		}

		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Reset error count etc. on first pass
			if (!isset($this->errorCount)) $this->errorCount = 0;
			
			$results = $myDBaseObj->GetGameById($recordId);

			switch ($bulkAction)
			{
				case CardzNetGamesAdminListClass::BULKACTION_ENDGAME:
					if (count($results) == 0)
						$this->errorCount++;
					else if ($results[0]->gameStatus != CardzNetDBaseClass::GAME_INPROGRESS)
						$this->errorCount++;					
					return ($this->errorCount > 0);
					
				case CardzNetGamesAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Games - Bulk Action Delete			
					if (count($results) == 0)
						$this->errorCount++;
					return ($this->errorCount > 0);
			}
			
			return false;
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$listClassId = $this->myPluginObj->adminClassPrefix.'GamesAdminListClass';
			
			switch ($bulkAction)
			{
				case CardzNetGamesAdminListClass::BULKACTION_DELETE:		
					$myDBaseObj->DeleteGame($recordId);
					return true;
					
				case CardzNetGamesAdminListClass::BULKACTION_ENDGAME:		
					$myDBaseObj->EndGame($recordId);
					return true;
			}
				
			return parent::DoBulkAction($bulkAction, $recordId);
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			$actionMsg = '';
			
			$listClassId = $this->myPluginObj->adminClassPrefix.'GamesAdminListClass';
			
			switch ($bulkAction)
			{
				case CardzNetGamesAdminListClass::BULKACTION_DELETE:	
					if ($this->errorCount > 0)
						$actionMsg = $this->errorCount . ' ' . _n("Game does not exist in Database", "Games do not exist in Database", $this->errorCount, $this->myDomain);
					else if ($actionCount > 0)
						$actionMsg = $actionCount . ' ' . _n("Game has been deleted", "Games have been deleted", $actionCount, $this->myDomain);
					else
						$actionMsg =  __("Nothing to Delete", $this->myDomain);
					break;
					
				case CardzNetGamesAdminListClass::BULKACTION_ENDGAME:	
					if ($this->errorCount > 0)
						$actionMsg = $this->errorCount . ' ' . _n("Game does not exist in Database", "Games do not exist in Database", $this->errorCount, $this->myDomain);
					else if ($actionCount > 0)
						$actionMsg = $actionCount . ' ' . _n("Game has been ended", "Games have been ended", $actionCount, $this->myDomain);
					else
						$actionMsg =  __("Nothing to End", $this->myDomain);
					break;
					
				default:
					$actionMsg = parent::GetBulkActionMsg($bulkAction, $actionCount);

			}
			
			return $actionMsg;
		}
		
	}

}

?>