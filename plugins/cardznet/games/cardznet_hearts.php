<?php
/* 
Description: Code for a CardzNet Game
 
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
include CARDZNET_GAMES_PATH.'cardznet_black_maria.php';

if (!class_exists('CardzNetHeartsCardsClass'))
{
	class CardzNetHeartsCardsClass extends CardzNetBlackmariaCardsClass
	{

		function GetCardScore($suit, $card)
		{
			$cardScore = 0;
			switch ($suit)
			{
				case 'spades': 
				{
					switch ($card)
					{
						case 'ace': return 0;
						case 'king': return 0;
					}
				}
			}
			
			return parent::GetCardScore($suit, $card);
		}

	}
	
	define('CARDZNET_GAMEOPTS_PASSPATTERN_ID', 'passPattern');
	define('CARDZNET_GAMEOPTS_PASSPATTERN_LEFT', 'left');
	define('CARDZNET_GAMEOPTS_PASSPATTERN_STD', 'std');
	define('CARDZNET_GAMEOPTS_PASSPATTERN_DEF', CARDZNET_GAMEOPTS_PASSPATTERN_STD);
/*	
	define('CARDZNET_GAMEOPTS_BREAKHEARTS_ID', 'breakHearts');
	define('CARDZNET_GAMEOPTS_BREAKHEARTS_DEF', CARDZNET_GAMEOPTS_STATE_NO);
*/	
	class CardzNetHeartsClass extends CardzNetBlackMariaClass // Define class
	{
		static function GetGameName()
		{
			return 'Hearts';			
		}
		
		function AddGameIncludes($gameName, $plugin_version)
		{
			parent::AddGameIncludes(parent::GetGameName(), $plugin_version);
		}
		
		static function GetGameIncludeDefs($gameName)
		{
			$gameName = parent::GetGameName();
			
			$rslt = parent::GetGameIncludeDefs($gameName);
			
			return $rslt;
		}
		
		function __construct($myDBaseObj, $atts = array())
		{			
			parent::__construct($myDBaseObj, $atts);

			$this->cardDefClass = 'CardzNetHeartsCardsClass';
			$this->noOfCardsToPassOn = 3;
			
			$this->MIN_NO_OF_PLAYERS = 4;
			$this->MAX_NO_OF_PLAYERS = 4;
			
			$this->MAX_SCORE = 23;
		}
		
		function AllCardsPassed($trickId)
		{
			parent::AllCardsPassed($trickId);
			
			$playerId = $this->SetFirstPlayer();
			$this->GetCurrentPlayer();
		}
		
		function SetFirstPlayer()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$playerId = 0;
			
			// Find the cardNo of the two of clubs ...
			$cardNo = $this->GetCardNo('two-of-clubs');			

			// Get the list of players hands
			$hands = $myDBaseObj->GetAllHands();

			// Check each players cards for the two of clubs
			$noOfPlayers = count($hands);
			foreach ($hands as $hand)
			{
				$cardsList = unserialize($hand->cardsList);
				if (in_array($cardNo, $cardsList))
				{
					$playerId = $hand->playerId;
					$myDBaseObj->SetNextPlayer($playerId);
					break;
				}
			}		
			
			return $playerId;	
		}
		
		function GetPassedCardsTargetOffset()
		{
			$myDBaseObj = $this->myDBaseObj;

			$gameOpts = $myDBaseObj->GetGameOptions();
			if (isset($gameOpts[CARDZNET_GAMEOPTS_PASSPATTERN_ID]))
			{
				switch($gameOpts[CARDZNET_GAMEOPTS_PASSPATTERN_ID])
				{
					case CARDZNET_GAMEOPTS_PASSPATTERN_LEFT:
						return 1;
						
					default:
						break;
				}
			}
				
			$noOfPlayers = $myDBaseObj->GetNoOfPlayers();
			$noOfRounds = $myDBaseObj->GetNumberOfRounds();
			
			$offsets = array(1, 3, 2, 0);
			$offsetIndex = ($noOfRounds % $noOfPlayers);
				
			$offset = $offsets[$offsetIndex];
			return $offset;
		}
		
		function GetGameOptions($currOpts)
		{
			$html = parent::GetGameOptions($currOpts);

			$passCardPattern = __('Pass Cards', $this->myDomain);
			if (isset($currOpts[CARDZNET_GAMEOPTS_PASSPATTERN_ID]))
				$passPatternLast = $currOpts[CARDZNET_GAMEOPTS_PASSPATTERN_ID];
			else
				$passPatternLast = CARDZNET_GAMEOPTS_PASSPATTERN_DEF;
			
			$html .= "<tr class='addgame_row_passcardpattern'><td class='gamecell'>$passCardPattern</td>\n";
			$html .= "<td class='gamecell' colspan=2>";
			
			$passPatterns = array(
				CARDZNET_GAMEOPTS_PASSPATTERN_LEFT => __('Always Left', CARDZNET_DOMAIN_NAME),
				CARDZNET_GAMEOPTS_PASSPATTERN_STD => __('Left, Right, Opposite, None', CARDZNET_DOMAIN_NAME),
				);
				
			$html .= "<select id=gameMeta_passcardpattern name=gameMeta_passcardpattern>\n";
			foreach ($passPatterns as $passPattern => $passText)
			{
				$selected = ($passPattern == $passPatternLast) ? ' selected=""' : '';
				$html .= '<option value="'.$passPattern.'"'.$selected.'>'.$passText.'&nbsp;&nbsp;</option>'."\n";
			}
			$html .= "</select>\n";
			$html .= "</td></tr>\n";

			$breakHeartsText = __('Enforce Break Hearts', $this->myDomain);
/*
			if (isset($currOpts[CARDZNET_GAMEOPTS_BREAKHEARTS_ID]))
				$breakHeartsLast = $currOpts[CARDZNET_GAMEOPTS_BREAKHEARTS_ID];
			else
				$breakHeartsLast = CARDZNET_GAMEOPTS_BREAKHEARTS_DEF;
*/			
			$html .= "<tr class='addgame_row_breakHearts'><td class='gamecell'>$breakHeartsText</td>\n";
			$html .= "<td class='gamecell' colspan=2>";
			
			$selected = '';
			$breakHeartsOptions = array(
				CARDZNET_GAMEOPTS_STATE_NO => __('No', CARDZNET_DOMAIN_NAME),
/*
				CARDZNET_GAMEOPTS_STATE_YES => __('Yes', CARDZNET_DOMAIN_NAME),
*/
				);
				
			$html .= "<select id=gameMeta_breakHearts name=gameMeta_breakHearts>\n";
			foreach ($breakHeartsOptions as $breakHeartsOption => $breakHeartsText)
			{
				$breakHeartsText .= ' ('.__('Feature not Implemented!', CARDZNET_DOMAIN_NAME).')';
//				$selected = ($breakHeartsOption == $breakHeartsLast) ? ' selected=""' : '';
				$html .= '<option value="'.$breakHeartsOption.'"'.$selected.'>'.$breakHeartsText.'&nbsp;&nbsp;</option>'."\n";
			}
			$html .= "</select>\n";
			$html .= "</td></tr>\n";

			return $html;
		}
		
		function ProcessGameOptions($gameOpts = array())
		{
			$gameOpts = parent::ProcessGameOptions($gameOpts);

			$gameOpts[CARDZNET_GAMEOPTS_PASSPATTERN_ID] = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'gameMeta_passcardpattern', 'unknown');
//			$gameOpts[CARDZNET_GAMEOPTS_BREAKHEARTS_ID] = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'gameMeta_breakHearts', 'unknown');

			return $gameOpts;
		}
		
		function DealCards($details = null)
		{
			parent::DealCards($details);
			
			$this->SetFirstPlayer();
			//$this->GetCurrentPlayer();
		}
		
		function OutputCard($cardOptions, $cardDef, $cardZIndex=0)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// If roundState==ROUND_READY
			if ($myDBaseObj->GetUnplayedCardsCount() == $myDBaseObj->GetNoOfCardsDealt())
			{
				// If If first card of first hand
				if ($myDBaseObj->GetRoundState() == CardzNetDBaseClass::ROUND_READY)
				{
					if ($cardDef->cardno != 1)
					{
						// Only enable Two of Clubs ... disable everything else
						unset($cardOptions['active']);
						unset($cardOptions['hasClick']);
					}
				}
			}
			
			return parent::OutputCard($cardOptions, $cardDef, $cardZIndex);
		}
	}
}

?>