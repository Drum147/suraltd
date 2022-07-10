<?php
/* 
Description: Code for Managing CardzNet Debug Settings
 
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
include CARDZNETLIB_INCLUDE_PATH.'cardznet_cards.php';

if (!class_exists('CardzNetGamesBaseClass'))
{
	define('CARDZNET_GAMEOPTS_STATE_NO', 'no');
	define('CARDZNET_GAMEOPTS_STATE_YES', 'yes');
	
	class CardzNetGamesBaseClass // Define class
	{
		var $atts;
		var $cardDefObj = null;
		var $stripFormElems = false;
		var $cardGUID = 0;
		
		var $cardDefClass = 'CardzNetCardsClass';
		
		var $promptMsg = '';
		
		var $MIN_NO_OF_PLAYERS = 0;
		var $MAX_NO_OF_PLAYERS = 0;
		
		static function GetGameName()
		{
			return 'TBD';			
		}
		
		static function GetGameIncludeDefs($gameName)
		{
			$rslt = new stdClass();
			
			$gameRootName = "cardznet_".str_replace(' ', '_', strtolower($gameName));
			
			$rslt->cssFile = 'css/'.$gameRootName.".css";
			$rslt->cssId = $gameRootName."-css";
			$rslt->jsFile = 'js/'.$gameRootName.".js";
			$rslt->jsId = $gameRootName."-js";

			return $rslt;
		}
		
		function LoadCSSandJS($cardsSet, $plugin_version)
		{
			$cssId = CARDZNET_CODE_PREFIX.'-cards';
			$cardsSetCssURL = CARDZNET_URL."cards/$cardsSet/css/cardznet_cards.css";

			// Add Style Sheet for Cards 
			$this->myDBaseObj->enqueue_style($cssId, $cardsSetCssURL);
			
			// Include size defs for PHP 
			$cardsSetCardDefs = CARDZNET_CARDS_PATH."$cardsSet/cardznet_cards.php";
			include $cardsSetCardDefs;
		}
				
		function AddGameIncludes($gameName, $plugin_version)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$gameFileDefs = $this->GetGameIncludeDefs($gameName);

			$gamesPath = CARDZNET_GAMES_PATH;
			$gamesURL = CARDZNET_GAMES_URL;
			
			// It is possible to override includes in uploads folder 
			$gamesUploadsPath = str_replace('plugins', 'uploads', $gamesPath);
			$gamesUploadsURL = str_replace('plugins', 'uploads', $gamesURL);
					
			// Add game Javascript file
			$jsFilePath = $gamesUploadsPath.$gameFileDefs->jsFile;
			if (file_exists($jsFilePath))
			{
				$jsFileURL = $gamesUploadsURL.$gameFileDefs->jsFile;				
			}
			else
			{
				$jsFilePath = $gamesPath.$gameFileDefs->jsFile;
				$jsFileURL = $gamesURL.$gameFileDefs->jsFile;				
			}
			if (file_exists($jsFilePath))
			{
				$jsId = $gameFileDefs->jsId;
				$myDBaseObj->enqueue_script($jsId, $jsFileURL);
			}
			
			// Add game CSS file
			$cssFilePath = $gamesUploadsPath.$gameFileDefs->cssFile;
			if (file_exists($cssFilePath))
			{
				$cssFileURL = $gamesUploadsURL.$gameFileDefs->cssFile;				
			}
			else
			{
				$cssFilePath = $gamesPath.$gameFileDefs->cssFile;
				$cssFileURL = $gamesURL.$gameFileDefs->cssFile;				
			}
			if (file_exists($cssFilePath))
			{
				$cssId = $gameFileDefs->cssId;
				$myDBaseObj->enqueue_style($cssId, $cssFileURL);
			} 
		}
		
		function CSS_JS_and_Includes($gameDef)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$gameName = $gameDef->name;
			$plugin_version = $myDBaseObj->get_JSandCSSver();
			$this->AddGameIncludes($gameName, $plugin_version);
			
			$cardsSet = $gameDef->gameCardFace;
			$this->LoadCSSandJS($cardsSet, $plugin_version);
		}
		
		function __construct($myDBaseObj, $atts = array())
		{
			$this->myDBaseObj = $myDBaseObj;
			$this->myDomain = $myDBaseObj->get_domain();
			$this->atts = $atts;
			
			//$this->CSS_JS_and_Includes();
		}
		
		function GetCardDef($cardNo)
		{
			if ($this->cardDefObj == null) 
				$this->cardDefObj = new $this->cardDefClass();
				
			return $this->cardDefObj->GetCardDef($cardNo);			
		}
		
		function GetCardNo($cardName)
		{
			if ($this->cardDefObj == null) 
				$this->cardDefObj = new $this->cardDefClass();
				
			return $this->cardDefObj->GetCardNo($cardName);			
		}
		
		function GetGameAndPlayer($gameId = 0)
		{
			$userId = $this->myDBaseObj->GetOurUserId($this->atts);

			$results = $this->Initialise($userId, $this->atts);
			if ($results == null) return false;
			
			return true;
		}
		
		function Initialise($userId, $atts)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if ($myDBaseObj->GetGameByUser($userId, $atts) === null)
				return null;
				
			if ($myDBaseObj->GetPlayersList() == null)
				return null;
			
			$this->GetCurrentPlayer();
			
			return true;
		}
		
		function GetGameDetailsForm($addHtml = '')
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$gameName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'gameName');
			
			// Look for a "Pre-Config" file in the site root
			$preConfigFilePath = WP_CONTENT_DIR."/../cardznet_cfg.php";

			$lastGameDetails = $myDBaseObj->GetLastGame($gameName);
			if (count($lastGameDetails) > 0)
			{		
				$loginNames = array();
				$names = array();
				$visibilities = array();
				foreach ($lastGameDetails as $lastPlayer)
				{
					$loginName = CardzNetDBaseClass::GetUserName($lastPlayer->userId);
					$loginNames[] = $loginName;
					$name = '';
					if ($loginName !== $lastPlayer->playerName)
						$name = $lastPlayer->playerName;
					$names[] = $name;
					$visibilities[] = $lastPlayer->hideCardsOption;
				}
				$noOfPlayers = count($lastGameDetails);
			}
			else if (file_exists($preConfigFilePath))
			{
				$preConfigData = file_get_contents($preConfigFilePath);
				$preConfigData = str_replace(' ', '', $preConfigData);
				$preConfigLines = explode("\n", $preConfigData);
				$loginNames = explode(',', $preConfigLines[1]);
				$names = explode(',', $preConfigLines[2]);
				$noOfPlayers = count($loginNames);
			}
			else
			{
				$noOfPlayers = 4;
			}

			for ($no = 0; $no < $this->MAX_NO_OF_PLAYERS; $no++)
			{
				if (($no==0) && (!current_user_can(CARDZNET_CAPABILITY_ADMINUSER)))
				{
					$userId = get_current_user_id();
					$userName = CardzNetDBaseClass::GetUserName($userId);
					$selectors[] = "<input type=hidden id=userId0 name=userId0 value=$userId>$userName";
					continue;
				}
				$loginName = isset($loginNames[$no]) ? $loginNames[$no] : '';
				$selectors[] = $myDBaseObj->GetMemberSelector($no, $loginName);
			}
			
			$loginText = __('Login', $this->myDomain);
			$nameText = __('Player Name', $this->myDomain);
			$playerText = __('Player', $this->myDomain);
			$noOfPlayersText = __('No of Players', $this->myDomain);
			

			$html  = "<form method=post>\n";
			$html .= "<input type=hidden id=gameName name=gameName value=\"$gameName\" >\n";
			$html .= "<table>\n";
			$html .= "<tr><td><h3>".__("Players", $this->myDomain).":</td></tr></h3>\n";
			
			$html .= "<tr><td>";
			$html .= __("Number of Players", $this->myDomain)." - ";
			$html .= __("Minimum", $this->myDomain).": {$this->MIN_NO_OF_PLAYERS} ";
			$html .= __("Maximum", $this->myDomain).": {$this->MAX_NO_OF_PLAYERS} ";
			$html .= "</td></tr>\n";
			
			$html .= "<tr><td class='gamecell'>&nbsp;</td><td class='gamecell'>$loginText</td><td class='gamecell'>$nameText</td>";
			$html .= "</tr>\n";
			for ($no = 0; $no < $this->MAX_NO_OF_PLAYERS; $no++)
			{
				$name = isset($names[$no]) ? $names[$no] : '';
				$playerNo = $no + 1;
				$selector = $selectors[$no];
				$style = ($no >= $noOfPlayers) ? ' style="display:none"' : '';
				$html .= "<tr class='addgame_row_login'><td class='gamecell'>$playerText $playerNo</td>";
				$html .= "<td class='gamecell'>".$selector."</td>";
				$html .= "<td class='gamecell'><input id='name$no' name='name$no' value='$name'></td>";
				$html .= "</tr>\n";
			}
						
			$html .= "<tr><td><h3>".__("Other Details", $this->myDomain).":</td></tr></h3>\n";
			$html .= $addHtml;

			$lastCardFace = (count($lastGameDetails) > 0) ? $lastGameDetails[0]->gameCardFace : '';
			$html .= $this->CardFaceSelectRow($lastCardFace);
			
			if ($myDBaseObj->isDbgOptionSet('Dev_RerunGame'))
			{
				$html .= $this->CopyGameSelectRow();
			}
			
			$currOpts = (count($lastGameDetails) > 0) ? unserialize($lastGameDetails[0]->gameMeta) : array();
			if (count($currOpts) > 0)
			{
				$html .= "<tr><td><h3>".__("Options for Selected Game", $this->myDomain).":</td></tr></h3>\n";
				$html .= $this->GetGameOptions($currOpts);
			}
			
			$html .= "<tr class='addgame_row_submit'><td colspan=3><input class='button-secondary' type='submit' name=addGameDetails value='Add Game'></td></tr>\n";

			$html .= "</table>\n";
			$html .= "</form>\n";
			
			return $html;
		}
		
		function GetGameOptions($currOpts)
		{
			return '';
		}
		
		function CardFaceSelectRow($defaultCardFace = '')
		{
			$cardFaceText = __("Card Face", CARDZNET_DOMAIN_NAME);
			
			$html  = "<tr class='addgame_cardface'><td>$cardFaceText</td>\n";
			$html .= "<td class='gamecell'>";
			$html .= $this->myDBaseObj->GetCardFaceSelector($defaultCardFace);
			$html .= "</td></tr>";
			
			return $html;
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
		
		function CopyGameSelectRow()
		{
			$gameName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'gameName');
			
			$copyGameText = __("Copy Game", CARDZNET_DOMAIN_NAME);
			$nocopyGameText = __("No Copy", CARDZNET_DOMAIN_NAME);
			
			$selector = $this->myDBaseObj->GetGameSelector($gameName, $nocopyGameText);
			if ($selector == '') return '';
			
			$html  = "<tr class='addgame_copygame'><td>$copyGameText</td>\n";
			$html .= "<td colspan=2 class='gamecell'>";
			$html .= $selector;		
			$html .= "</td></tr>";
			
			return $html;
		}
		
		function ProcessGameOptions($gameOpts = array())
		{
			return array();
		}
		
		function ProcessGameDetailsForm($gameName)
		{
			$gameUserIDs = array();
			$tmpList = array();
			
			$firstPlayerUserIdAndName = '';
			if ($this->myDBaseObj->isDbgOptionSet('Dev_RerunGame'))
			{
				$prevroundId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'prevroundId');
				if ($prevroundId != 0)
				{
					// Get the deck from an earlier round
					$lastRounds = $this->myDBaseObj->GetRounds($prevroundId);
					$lastFirstPlayerId = $lastRounds[0]->firstPlayerId;
					
					$playerDetails = $this->myDBaseObj->GetNextPlayer($lastFirstPlayerId);
					$firstPlayerUserIdAndName = "{$playerDetails->userId}.{$playerDetails->playerName}";
				}
			}
			
			$hasVisibility = CardzNetLibUtilsClass::IsElementSet('post', 'cardsVisibleOption_0');
			$idAndNameList = array();
			for ($no=0; $no < $this->MAX_NO_OF_PLAYERS; $no++)
			{
				$userId = CardzNetLibUtilsClass::GetHTTPInteger('post', "userId$no");
				if ($userId == 0) continue;

				$name = CardzNetLibUtilsClass::GetHTTPTextElem('post', "name$no");
				if ($name == '')
				{
					$name = CardzNetDBaseClass::GetUserName($userId);
				}
				if ($userId == 0) continue;
				
				$idAndName = $userId.$name;
				if (isset($idAndNameList[$idAndName]))
				{
					echo $this->myDBaseObj->BannerMsg(__("Login and Player Name must be unique", $this->myDomain), 'error');
					return;
				}
				$idAndNameList[$idAndName] = true;
				
				$gameUserIDs[] = array('id' => $userId, 'name' => $name);
				
				if ($hasVisibility)
				{
					$vis = CardzNetLibUtilsClass::GetHTTPTextElem('post', "cardsVisibleOption_$no");
					$gameUserIDs[$no]['visibility'] = $vis;
				}
				
				if ($firstPlayerUserIdAndName != '')
				{
					$thisPlayerIdAndName = "{$userId}.{$name}";
					if ($thisPlayerIdAndName == $firstPlayerUserIdAndName)
						$gameUserIDs[$no]['first'] = true;
				}
				$tmpList[] = $no;
			}

			if (count($gameUserIDs) < $this->MIN_NO_OF_PLAYERS)
			{
				echo $this->myDBaseObj->BannerMsg(__("Not enough players", $this->myDomain), 'error');
				return;
			}
			
			// First player is random .... but players are always in the same order
			if ($firstPlayerUserIdAndName == '')
			{
				shuffle($tmpList);
				foreach ($tmpList as $tmp)
				{
					$playerIndex = $tmp;
					break;
				}
				$gameUserIDs[$playerIndex]['first'] = true;
			}

			$gameNoOfPlayers = count($gameUserIDs);
			$gameDealDetails = $this->GetDealDetails($gameNoOfPlayers);
			$gameCardsPerPlayer = $gameDealDetails->cardsPerPlayer;
			$gameCardFace = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'gameCardFace', '');
			
			$this->myDBaseObj->AddGame($gameName, $gameUserIDs, $gameCardsPerPlayer, $gameCardFace);			

			for ($no=0; $no < $this->MAX_NO_OF_PLAYERS; $no++)
			{
				$userId = CardzNetLibUtilsClass::GetHTTPTextElem('post', "userId$no");
			}
			
			$this->DealCards($gameDealDetails);			
			
			$gameOpts = $this->ProcessGameOptions();
			if (count($gameOpts) > 0)
				$this->myDBaseObj->UpdateGameOptions($gameOpts);	

			echo $this->myDBaseObj->BannerMsg(__("Game Added", $this->myDomain), 'updated');
		}
		
		function GetDealDetails($noOfPlayers = 0)
		{
			$this->NotImplemented('GetDealDetails');
		}
		
		function PlayerColour($details, $playerNo)
		{
			return '';
		}
		
		function DealCards($details = null)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$cards = $myDBaseObj->GetDeck($details);
			
			$cardIndex = 0;
			
			$noOfPlayers = $myDBaseObj->GetNoOfPlayers();
			$cardsPerPlayer = $myDBaseObj->cardsPerPlayer;
			for ($playerNo = 0; $playerNo<$noOfPlayers; $playerNo++)
			{
				$playerId = $myDBaseObj->AddDeckToHand($playerNo, $cards, $cardIndex, $cardsPerPlayer);
				
				// Add player "colours"
				if (isset($details->noOfTeams))
				{
					$playerColour = $this->PlayerColour($details, $playerNo);
					$myDBaseObj->SetPlayerColour($playerId, $playerColour);
				}
			}
				
			$myDBaseObj->SetNextCardInDeck($cardIndex);			
		}
		
		function GetCurrentPlayer()
		{
			$this->myDBaseObj->FindCurrentPlayer();
		}

		function OutputNoGame()
		{
			echo '<form id=nogame name=nogame method=post>'."\n";
			echo "<div>\n";	
			
			echo $this->myDBaseObj->BannerMsg(__('Not currently in a game!', $this->myDomain), 'error');

			echo "</div>\n";	
			echo "</form>\n";	
		}
				
		function OutputCardTable($gameId = 0)
		{
			$stripFormElems = isset($this->atts['stripFormElems']);			
			if (!$stripFormElems) echo '<form id=cardznet name=cardznet method=post>'."\n";
			
			if (!$this->GetGameAndPlayer($gameId))
			{
				// Error initialising ... cannot play!
				$this->OutputNoGame();
			}
			else
			{
				$this->OutputTabletop();				
			}

			echo $this->myDBaseObj->AJAXVarsTags(); 
			
			if ($stripFormElems) return;
			
			echo "</form>\n";	
			
			echo $this->myDBaseObj->JSGlobals(); 			
		}
		
		function OutputCard($cardOptions, $cardDef, $cardZIndex=0)
		{
			$active = false;
			$visible = true;
			$hasClick = false;

			if (isset($cardOptions['active']))
				$active = $cardOptions['active'];

			if (isset($cardOptions['hasClick']))
				$hasClick = $cardOptions['hasClick'];

			if (isset($cardOptions['visible']))
				$visible = $cardOptions['visible'];

			foreach ($cardOptions as $layoutId => $layoutVal)
				$cardDef->$layoutId = $layoutVal;
			
			$cardNo = $cardDef->cardno;
			
			$hspace = (isset($cardOptions['hspace'])) ? $cardOptions['hspace'] : 50;
			$hoffset = (isset($cardOptions['hoffset'])) ? $cardOptions['hoffset'] : 20;
			$vspace = (isset($cardOptions['vspace'])) ? $cardOptions['vspace'] : 0;
			$voffset = (isset($cardOptions['voffset'])) ? $cardOptions['voffset'] : 0;
			$suffix  = isset($cardDef->cardDirn) && ($cardDef->cardDirn != '') ? $cardDef->cardDirn : '-p';
			$suffix .= isset($cardDef->cardSize) && ($cardDef->cardSize != '') ? '-'.$cardDef->cardSize : '';
			$cardClass  = "cardNo_$cardNo card$suffix ".$cardDef->name;
			$cardClass .= $active ? ' activecard' : '';
			$cardClass .= $visible ? '' : ' card-back';	
			if (isset($cardOptions['class']))
				$cardClass .= ' '.$cardOptions['class'];	

			$frameClass = '';
			if (isset($cardOptions['frameclass']))
				$frameClass .= ' '.$cardOptions['frameclass'];	

			$left = $hspace * $cardZIndex;
			$left += $hoffset;
			$style = "left: ".$left."px;";
			
			$top = $vspace * $cardZIndex;
			$top += $voffset;
			$style .= "top: ".$top."px;";

			if (isset($cardOptions['id']))
				$cardId = $cardOptions['id'];
			else
			{
				$this->cardGUID++;
				$cardId = "cardGUID_".$this->cardGUID;
			}
			
			$onClick = $hasClick ? "onclick=cardznet_playcardClick('$cardId');" : "";

			$counterhtml = '';
			if (isset($cardOptions['counterclass']) && ($cardOptions['counterclass'] != ''))
			{
				$counterClass = $cardOptions['counterclass'];
				//$url = CARDZNET_GAMES_URL.'images/'.$counterColour.'.png';
				//$counterhtml = "<img src=\"$url\" class=\"counter $counterColour\" ></img>\n";
				$counterhtml = "<div class=\"counter {$counterClass}\"></div>";
			}

			$html  = "<div class='card-frame card-div $frameClass' style='$style'>\n";
			$html .= "<div class='card-face  card-div $cardClass' id=$cardId name=$cardId $onClick >$counterhtml</div>\n";
			$html .= "</div>\n";
			
			return $html;
		}
		
		function PlayCard($cardNo)
		{
			$playersHand = $this->myDBaseObj->GetHand();
			$cardIndex = array_search($cardNo, $playersHand->cards);

			if ($cardIndex === false) 
			{
				//CardzNetLibUtilsClass::print_r($playersHand, '$playersHand');
				die("$cardNo is not in hand");
				return false;
			}
			
			// Remove this card from the hand and add to played list
			unset($playersHand->cards[$cardIndex]);
			$playersHand->played[] = $cardNo;
			$this->myDBaseObj->UpdateCurrentHand($playersHand->cards, $playersHand->played);
			
			$this->playerNoOfCards = count($playersHand->cards);
			
			return true;
		}
		
		function CheckCanPlay(&$playersHand, $state = true)
		{
			foreach ($playersHand->cards as $key => $card)
			{
				$playersHand->canPlay[$key] = $state;
			}

			return count($playersHand->cards);
		}
		
		function OutputCards($playersHand)
		{
			$this->showingCards = true;
			
			$myDBaseObj = $this->myDBaseObj;

			$this->CheckCanPlay($playersHand);
		
			$visible = $myDBaseObj->CanShowCards();
			
			$this->OutputPlayersHand($playersHand, $visible);

			$this->OutputCardsOnTable();
			
			$promptMsg = $this->GetUserPrompt();
			if ($promptMsg != '')
				echo "<div class=\"prompt\">$promptMsg</div>\n";
				
			$this->OutputTableView($visible);
			
			if (!$visible)
			{
				$unhideButton = __('Click Here or Press Space Bar to Turn Cards Over', $this->myDomain);
				echo '<div class="tablediv controls">';
				echo '<input type=button id=unhidecardsbutton name=unhidecardsbutton class=secondary value="'.$unhideButton.'" onclick="cardznet_unhidecardsClick();" >';
				echo "</div>\n";
			}		
			
		}
		
		function OutputTableView($visible = true)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			switch ($myDBaseObj->getOption('NextPlayerMimicDisplay'))
			{
				case CARDZNET_MIMICVISIBILITY_ALWAYS:
					$visible = true;
					break;
					
				case CARDZNET_MIMICVISIBILITY_NEVER:
					return;
			}
			
			$classes = 'tableview ';
			if (!$visible) $classes .= ' hidden-div';
			$html = "<div class=\"$classes\" >\n";
			$players = $myDBaseObj->playersList;

			$noOfPlayers = count($players);

			//$tableurl = CARDZNET_IMAGES_URL.'table.png';
			$html .= '<div class="tabletopdiv" >';
			//$html .= "<img src=\"$tableurl\" class=\"tabletop\" ></img>\n";
			$html .= "</div>\n";

			$playerurl = CARDZNET_IMAGES_URL.'player.png';
			
			$firstPlayerIndex = $myDBaseObj->thisPlayer->index;
			switch ($myDBaseObj->getOption('NextPlayerMimicRotation'))
			{
				case CARDZNET_MIMICMODE_ADMIN:
					$firstPlayerIndex = 0;
					break;
				
				case CARDZNET_MIMICMODE_DEALER:
					for ($index = 0; $index<$noOfPlayers; $index++)
					{
						if ($players[$index]->isFirstPlayer)
						{
							$firstPlayerIndex = $index;
							break;
						}
					}
					break;
				
				case CARDZNET_MIMICMODE_PLAYER:
				default:
					// Use default ....
					break;
			}
			
			for ($index = $firstPlayerIndex, $playerNo=1; $playerNo<=$noOfPlayers; $playerNo++, $index++)
			{
				if ($index >= $noOfPlayers) $index = 0;
				$iconId = "playericon-{$playerNo}of{$noOfPlayers}";
				$html .= "<div class=playericon id=$iconId name=$iconId>";
				$html .= "<img src=\"$playerurl\" ></img>\n";

				$player = $players[$index];
				$divName = 'player_'.$playerNo.'_of_'.$noOfPlayers;
				$playerName = $player->name;
				$divClass = 'player_view player_name';
				$divClass .= ($player->isActive) ? ' player_view_active' : ' player_view_inactive';
				
				$html .= "<div class=\"player_view_frame\" >";
				$html .= "<div class=\"$divClass\" >$playerName</div>\n";
				$html .= "</div></div>\n";
			}

			$html .= "</div>\n";
			
			echo $html;
		}
		
		function SortScores($object1, $object2)
		{
			if ($object1->roundScore == $object2->roundScore)
			{
				return $object1->score > $object2->score;
			}
			return $object1->roundScore > $object2->roundScore;
		}
		
		function GetGameScores()
		{
/*
			$totalResults = $this->myDBaseObj->GetScores();
			$roundResults = $this->myDBaseObj->GetLastRoundScores();
CardzNetLibUtilsClass::print_r($totalResults, '$totalResults');
CardzNetLibUtilsClass::print_r($roundResults, '$roundResults');
			foreach ($totalResults as $index => $totalResult)				
			{
				$totalResults[$index]->lastRoundScore = $roundResults[$index]->roundScore;
			}
			
			usort($totalResults, array($this, 'SortScores'));

			return $totalResults;
*/
			$roundResults = $this->myDBaseObj->GetLastRoundScores();
			usort($roundResults, array($this, 'SortScores'));
			
			return $roundResults;
		}
		
		function IsGameComplete()
		{
			return false;
		}
				
		function OutputScores()
		{
			$this->showingScores = true;
			
			$myDBaseObj = $this->myDBaseObj;
			
			echo "<div id='scores_header' name='scores_header' >Scores</div><br>\n";
			
			$totalResults = $this->GetGameScores();
			
			$onClick = 'onclick="cardznet_sortScoresClick(event)"';
			
			$html = "<table id=scores_table>\n";
			$html .= "<tr class='scores_table_header'>";
			$html .= "<td id='playerName' class='playerName' $onClick >Name</td>";
			$html .= "<td id='playerScore' class='playerScore' $onClick >Last Round</td>";
			$html .= "<td id='playerTotal' class='playerTotal' $onClick >Total</td></tr>\n";
			foreach ($totalResults as $index => $totalResult)
			{
				$playerName = $totalResult->playerName;
				$roundScore = $totalResult->roundScore;
				$totalScore = $totalResult->score;
				
				$html .= "<tr class='scores_table_row' >";
				$html .= "<td class='playerName'>$playerName</td>";
				$html .= "<td class='playerScore'>$roundScore</td>";
				$html .= "<td class='playerScore'>$totalScore</td>";
				$html .= "</tr>\n";
			}
			$html .= "</table>\n";
			
			echo $html;
			
			// Check if game is finished
			if ($this->IsGameComplete())
			{
				echo __("Game Complete", $this->myDomain)."<br>\n";
			}
			else if ($myDBaseObj->IsNextPlayer())
			{
				echo __("You are the dealer", $this->myDomain)."<br>\n";
				$dealButton = __("Click Here or Press Enter to Deal the Cards", $this->myDomain);
				$buttonId = ($this->myDBaseObj->isDbgOptionSet('Dev_DisableAJAX')) ? 'dealcards' : 'ajaxdealcards';
				echo "<input type=submit id=\"$buttonId\" name=\"$buttonId\" class=\"dealcards secondary\" value=\"$dealButton\" onclick=\"return cardznet_dealcardsClick();\" ><br>";
			}
			else
			{
				$dealerName = $myDBaseObj->GetNextPlayerName();
				printf( __( 'Waiting for %s to deal', CARDZNET_DOMAIN_NAME ), $dealerName );
			}
			
		}
		
		function IsMyTurn()
		{	
			return $this->myDBaseObj->IsNextPlayer();		
		}
			
		function GetUserPrompt()
		{
			return '';
		}
		
		function OutputPlayerId()
		{
			echo $this->myDBaseObj->GetPlayerIdHiddenTag();
		}
		
		function OutputTabletop()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Show the tabletop
			echo "<div id=cardznet>\n";
			echo "<div class=page>\n";
			
			// Get filename of audio file from Settings
			$optionIds = array('mp3_Ready', 'mp3_RevealCards', 'mp3_SelectCard', 'mp3_PlayCard');
			foreach ($optionIds as $optionId)
			{
				$mp3Path = $myDBaseObj->getOption($optionId);
				if ($mp3Path != '')
				{
					// i.e. /wp-content/plugins/cardznet/mp3/beep-07.wav
					if (strpos($mp3Path, "/") === false)
						$mp3Path = CARDZNET_UPLOADS_URL.'/mp3/'.$mp3Path;
						
					$id = "cardznet_".$optionId;
					echo '<audio id="'.$id.'" src="'.$mp3Path.'"></audio>'."\n";
				}
			}

			// Get the players cards
			$playersHand = $myDBaseObj->GetHand();
			
			// NOTE: Could Output the player info
			$playerInfo = $this->GetPlayerInfo();
			$colourClass = $this->IsMyTurn() ? " readyToPlay " : " waitingToPlay ";
			
			$tickerTellDiv = "<div id=tickertell></div>";
			echo "<div class='info_frame $colourClass'><div class=info>{$playerInfo}{$tickerTellDiv}</div></div>\n";

			$runesLinkText = __('Click for Rules', CARDZNET_DOMAIN_NAME);
			$gameName = $this->GetGameName();
			$rulesFile = 'rules_'.strtolower(str_replace(' ', '_', $gameName)).'.pdf';
			$rulesURL = plugins_url("games/rules/{$rulesFile}", dirname(__FILE__));			
			echo "<div class='ruleslink_frame'><div class=ruleslink><a target=\"_blank\" href=\"{$rulesURL}\">{$runesLinkText}</a></div></div>\n";

			//if ((count($playersHand->cards) == 0) && $this->IsRoundComplete())
			if ($this->IsRoundComplete())
				$this->OutputScores();
			else
				$this->OutputCards($playersHand);
			
			$buttonText = __("Timed Out: Click to Restart", $this->myDomain);
			$divId = 'restartdiv';
			$buttonId = 'restartbutton';
			$onClick = ' onclick="cardznet_restartTickerTimer();" ';
			echo '<div id='.$divId.' id='.$divId.' class="refresh cardznet_hide" >';
			echo '<input type=button id='.$buttonId.' name='.$buttonId.$onClick.' class="secondary tablediv" value="'.$buttonText.'" >';
			echo '</div>';
			
			$tickerURL = CARDZNET_UPLOADS_URL.$myDBaseObj->GetTickFilename();
			$tickerURLParts = explode('_', $tickerURL);
			$tickerURLTemplate = $tickerURLParts[0].'_%g_'.$tickerURLParts[2];
			echo "<script>\n";
			echo 'var tickerURLTemplate = "'.$tickerURLTemplate.'"'.";\n";
			echo "</script>\n";
			
			$this->OutputPlayerId();
			
			echo "</div>\n";
			echo "</div>\n";		
		}
		
		function GetPlayerInfo()
		{
			$playerText = __('Player', CARDZNET_DOMAIN_NAME);
			$playerInfo = "$playerText: ".$this->myDBaseObj->GetPlayerName();
			return "<div class=info_player>$playerInfo</div>";
		}
		
		function NotImplemented($funcName = 'function')
		{
			$className = get_class();
			echo "No implementation of $funcName in $className <br>\n";
			//CardzNetLibUtilsClass::ShowCallStack();
			//die;
		}
		
	}
}

?>