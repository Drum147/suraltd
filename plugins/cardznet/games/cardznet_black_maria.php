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
include CARDZNETLIB_INCLUDE_PATH.'cardznet_tabletop.php';

if (!class_exists('CardzNetBlackMariaClass'))
{
	class CardzNetBlackmariaCardsClass extends CardzNetCardsClass
	{
		function GetCardScore($suit, $card)
		{
			$cardScore = 0;
			switch ($suit)
			{
				case 'hearts': return 1;
				case 'spades': 
				{
					switch ($card)
					{
						case 'ace': return 7; 
						case 'king': return 10;
						case 'queen': return 13; 
					}
				}
			}
			
			return parent::GetCardScore($suit, $card);
		}
	}
	
	define('CARDZNET_GAMEOPTS_ENDSCORE_ID', 'endScore');
	define('CARDZNET_GAMEOPTS_ENDSCORE_DEF', 100);
		
	define('CARDZNET_GAMEOPTS_ENABLESLAM_ID', 'slamEnabled');
	define('CARDZNET_GAMEOPTS_ENABLESLAM_DEF', CARDZNET_GAMEOPTS_STATE_NO);
	
	class CardzNetBlackMariaClass extends CardzNetGamesBaseClass // Define class
	{
		var $currTrick = null;
		var $noOfCardsToPassOn = 1;
		
		const ROUND_PASSCARD = 'passcard';
		
		static function GetGameName()
		{
			return 'Black Maria';			
		}
		
		function __construct($myDBaseObj, $atts = array())
		{
			parent::__construct($myDBaseObj, $atts);
			
			$this->cardDefClass = 'CardzNetBlackmariaCardsClass';
			$this->noOfCardsToPassOn = 1;
			
			$this->MIN_NO_OF_PLAYERS = 3;
			$this->MAX_NO_OF_PLAYERS = 5;
			
			$this->MAX_SCORE = 43;
		}
		
		function GetCurrentPlayer()
		{
			$myDBaseObj = $this->myDBaseObj;
			$roundState = $myDBaseObj->GetRoundState();
			$myDBaseObj->OutputDebugMessage("GetCurrentPlayer - Round state is $roundState <br>\n");
			if ($roundState == self::ROUND_PASSCARD)
			{
				$myDBaseObj->OutputDebugMessage("Round state is ROUND_PASSCARD <br>\n");
				
				// Get players list with "ready" State
				$cardsLeftWhenready = $this->myDBaseObj->cardsPerPlayer - $this->GetNoOfCardsToPassOn();

				$results = $myDBaseObj->GetPlayersReadyStatus("(noOfCards > $cardsLeftWhenready)");
				
				// Update the ready state for each player 		
				foreach ($results as $index => $result)
				{
					$myDBaseObj->SetPlayerReady($result->playerId, $result->ready);
				}
				
				// Set next player to Dealer ...
				$nextPlayerId = $myDBaseObj->SelectPlayerBeforeNext();
			}
			
			parent::GetCurrentPlayer();
			
			if ($roundState == self::ROUND_PASSCARD)
			{
				foreach ($results as $index => $result)
				{
					$myDBaseObj->SetPlayerReady($result->playerId, $result->ready);
				}

				// Ready state shows that player can play a card
				if ($myDBaseObj->IsPlayerReady())
				{
					$followingPlayerName = $this->GetPassedCardsTargetName();
					$this->promptMsg = __("Select a card to pass on to", $this->myDomain)." $followingPlayerName!";			
				}
				else
					$this->promptMsg = __("Waiting for other players!", $this->myDomain);			
			}
		}

		function GetNoOfCardsToPassOn()
		{
			return $this->noOfCardsToPassOn;
		}
		
		function GetPassedCardsTargetOffset()
		{
			// Black Maria always passes to the next player
			return 1;
		}
		
		function GetPassedCardsTargetName()
		{
			$offset = $this->GetPassedCardsTargetOffset();			
			if ($offset == 0)
				return '';

			return $this->myDBaseObj->GetFollowingPlayerName($offset);
		}

		function GetGameOptions($currOpts)
		{
			$html = parent::GetGameOptions($currOpts);

			$endOfGameText = __('End of game score', $this->myDomain);
			if (isset($currOpts[CARDZNET_GAMEOPTS_ENDSCORE_ID]))
				$gameEndScore = $currOpts[CARDZNET_GAMEOPTS_ENDSCORE_ID];
			else
				$gameEndScore = CARDZNET_GAMEOPTS_ENDSCORE_DEF;
			
			$html  = "<tr class='addgame_row_endgamescore'><td class='gamecell'>$endOfGameText</td>\n";
			$html .= "<td class='gamecell' colspan=2><input type=text id=gameMeta_EndScore name=gameMeta_EndScore value='$gameEndScore'></td></tr>\n";

			$slamScoresMaxText = __('Slam Scores Maximum', $this->myDomain);
			if (isset($currOpts[CARDZNET_GAMEOPTS_ENABLESLAM_ID]))
				$slamScoresMaxLast = $currOpts[CARDZNET_GAMEOPTS_ENABLESLAM_ID];
			else
				$slamScoresMaxLast = CARDZNET_GAMEOPTS_ENABLESLAM_DEF;
			
			$html .= "<tr class='addgame_row_slamScoresMax'><td class='gamecell'>$slamScoresMaxText</td>\n";
			$html .= "<td class='gamecell' colspan=2>";
			
			$selected = '';
			$slamScoresMaxOptions = array(
				CARDZNET_GAMEOPTS_STATE_NO => __('No', CARDZNET_DOMAIN_NAME),
				CARDZNET_GAMEOPTS_STATE_YES => __('Yes', CARDZNET_DOMAIN_NAME),
				);
				
			$html .= "<select id=gameMeta_slamScoresMax name=gameMeta_slamScoresMax>\n";
			foreach ($slamScoresMaxOptions as $slamScoresMaxOption => $slamScoresMaxText)
			{
				$selected = ($slamScoresMaxOption == $slamScoresMaxLast) ? ' selected=""' : '';
				$html .= '<option value="'.$slamScoresMaxOption.'"'.$selected.'>'.$slamScoresMaxText.'&nbsp;&nbsp;</option>'."\n";
			}
			$html .= "</select>\n";
			$html .= "</td></tr>\n";

			return $html;
		}
		
		function ProcessGameOptions($gameOpts = array())
		{
			$gameOpts = parent::ProcessGameOptions($gameOpts);

			$gameOpts[CARDZNET_GAMEOPTS_ENDSCORE_ID] = CardzNetLibUtilsClass::GetHTTPInteger('post', 'gameMeta_EndScore', CARDZNET_GAMEOPTS_ENDSCORE_DEF);
			$gameOpts[CARDZNET_GAMEOPTS_ENABLESLAM_ID] = CardzNetLibUtilsClass::GetHTTPInteger('post', 'gameMeta_slamScoresMax', CARDZNET_GAMEOPTS_ENABLESLAM_DEF);

			return $gameOpts;
		}
		
		function GetDealDetails($noOfPlayers = 0)
		{
			if ($noOfPlayers == 0)
			{
				$noOfPlayers = $this->myDBaseObj->GetNoOfPlayers();
			}
			
			$dealDetails = new stdClass();
			$dealDetails->noOfPacks = 1;
			$dealDetails->excludedCardNos = array();
			
			switch ($noOfPlayers)
			{
				case 3:
					$dealDetails->cardsPerPlayer = 17;
					$dealDetails->excludedCards = 'two-of-diamonds';
					break;
					
				case 4:
					$dealDetails->cardsPerPlayer = 13;
					$dealDetails->excludedCards = '';
					break;
				
				case 5:
					$dealDetails->cardsPerPlayer = 10;
					$dealDetails->excludedCards = 'two-of-diamonds, two-of-clubs';
					break;
					
				default:
					$dealDetails->gameCardsPerPlayer = 0;
					$dealDetails->errMsg = __('Invalid number of players', $this->myDomain);
					return $dealDetails;
			}
			
			$excludedCardNos = array();
			$excludedList = explode(',', $dealDetails->excludedCards);
			$dealDetails->excludedCardNos = array();
			foreach ($excludedList as $excludedCard)
			{
				$cardNo = $this->GetCardNo($excludedCard);
				$dealDetails->excludedCardNos[$cardNo] = true;
			}
			
			return $dealDetails;
		}
		
		function DealCards($details = null)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			if ($myDBaseObj->getDbgOption('Dev_ShowMiscDebug'))
				$myDBaseObj->AddToStampedCommsLog("******* Dealing Cards *******");
			
			if ($details == null)
			{
				$details = $this->GetDealDetails();
			}
			
			$roundState = ($this->GetPassedCardsTargetOffset() > 0) ? self::ROUND_PASSCARD : CardzNetDBaseClass::ROUND_READY;
			$roundId = $myDBaseObj->AddRound($roundState);
			
			parent::DealCards($details);
		}
		
		function AddRoundScores()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Get Players total trick scores for this round
			$scores = $myDBaseObj->GetLastRoundScores();

			$slamPlayerId = 0;
			
			$slamScoreEnabled = false;
			$gameOpts = $myDBaseObj->GetGameOptions();
			if (isset($gameOpts[CARDZNET_GAMEOPTS_ENABLESLAM_ID]))
			{
				$slamScoreEnabled = ($gameOpts[CARDZNET_GAMEOPTS_ENABLESLAM_ID] !== 'no');
			}
			
			if ($slamScoreEnabled)
			{
				// Scan for a slam (maximum score)
				foreach ($scores as $score)
				{
					if ($score->roundScore == $this->MAX_SCORE)
						$slamPlayerId = $score->playerId;
				}
			}
			
			// Add Trick Scores to totals
			foreach ($scores as $score)
			{
				if ($slamPlayerId == 0)
				{
					$newScore = $score->score + $score->roundScore;	
				}
				else if ($slamPlayerId != $score->playerId)
				{
					$newScore = $score->score + $this->MAX_SCORE;
				}
				else
				{
					continue;
				}
					
				$myDBaseObj->UpdateScore($score->playerId, $newScore);
			}
		}
		
		function GetWinner($trickCards)
		{
			$winnerObj = new stdClass();
			
			$trickSuitNo = 0;
			$trickRank = 0;
			$trickScore = 0;

			$noOfCards = count($trickCards);
			
			foreach ($trickCards as $index => $cardNo)
			{
				$cardDef = $this->GetCardDef($cardNo);
				if ($index == 0)
				{
					$trickSuitNo = $cardDef->suitNo;
					$trickRank = $cardDef->rank;
					$winnerIndex = $index;
				}
				else if (($trickSuitNo == $cardDef->suitNo) && ($trickRank < $cardDef->rank))
				{
					$trickSuitNo = $cardDef->suitNo;
					$trickRank = $cardDef->rank;
					$winnerIndex = $index;
				}
				else
				{

				}
				
				$trickScore += $cardDef->score;
			}
			
			$winnerObj->index = $winnerIndex;
			$winnerObj->score = $trickScore;
			
			return $winnerObj;
		}
	
		function OutputPlayersHand($playersHand, $visible)
		{
			echo "<div class=\"tablediv playercards cards-p\">\n";
			$cardZIndex = 0;

			$cardOptions = array();
			$cardOptions['visible'] = $visible;
			foreach ($playersHand->cards as $index => $cardNo)
			{
				$cardDef = $this->GetCardDef($cardNo);
				$active = $this->IsMyTurn() && $playersHand->canPlay[$index];
				$cardOptions['active'] = $cardOptions['hasClick'] = $active;				
				echo $this->OutputCard($cardOptions, $cardDef, $cardZIndex++);
			}
			echo "</div>\n";
		}
	
		function OutputCardsOnTable()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$trickVisible = ($myDBaseObj->GetRoundState() == CardzNetDBaseClass::ROUND_READY);
			$trickCards = $myDBaseObj->GetTrickCards();

			if ($trickCards != null)
			{
				echo '<div class="tablediv centrecards cards-p">';
				$cardOptions = array();
				$cardOptions['active'] = false;
				$cardOptions['visible'] = $trickVisible;
				
				$cardZIndex = 0;
				foreach ($trickCards as $cardNo)
				{
					$cardDef = $this->GetCardDef($cardNo);
					if (!$trickVisible) 
					{
						$cardDef->cardNo = 0;
						$cardDef->name = 'card-hidden';
					}
					echo $this->OutputCard($cardOptions, $cardDef, $cardZIndex++);
				}
				echo "</div>\n";
			}		
						
			$lastTrick = $myDBaseObj->GetLastTrick();
			if ($lastTrick != null)
			{
				$points = 0;
				echo "<div class=\"lasttrick-frame\"> \n";
				echo '<div class="tablediv lasttrick cards-p">';
				$cardOptions['active'] = false;
				$cardOptions['visible'] = true;
				$cardZIndex = 0;
				$cardOptions = array();
				foreach ($lastTrick->cardsList as $cardNo)
				{
					$cardDef = $this->GetCardDef($cardNo);
					$points += $cardDef->score;
					echo $this->OutputCard($cardOptions, $cardDef, $cardZIndex++);
				}
				
				$winner = $lastTrick->winnerName;
				$titleText  = __("Last Trick", CARDZNET_DOMAIN_NAME);
				$winnerText = __("Winner", CARDZNET_DOMAIN_NAME);
				$pointsText = __("Points", CARDZNET_DOMAIN_NAME);
				echo "</div>\n";
				
				echo '<div class="tablediv lastwinner">';
				echo "$titleText<br><br>$winnerText:<br>$winner<br>$points $pointsText";
				echo "</div>\n";
				echo "</div>\n";
			}	
			
		}
		
		function AllCardsPassed($trickId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$offset = $this->GetPassedCardsTargetOffset();
			$myDBaseObj->RevertPassedCards($offset, CardzNetDBaseClass::LEAVE_CARDS);
									
			// Delete the trick 
			$myDBaseObj->DeleteTrick($trickId);
			
			// Change round state to ready (ROUND_READY)
			$myDBaseObj->UpdateRoundState(CardzNetDBaseClass::ROUND_READY);
			
			// Update the ticker
			$myDBaseObj->IncrementTicker();
	
			$this->GetCurrentPlayer();
			
			// "Cancel" the user prompt ... 
			$this->promptMsg = '';
		}
		
		function UpdateRound($cardNo)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$isPlayingHand = ($myDBaseObj->GetRoundState() == CardzNetDBaseClass::ROUND_READY);
			
			// Check that this is the correct player
			if ($isPlayingHand)
			{
				$playerId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'playerId', 0);
				if ($myDBaseObj->nextPlayerId != $playerId) return 'Wrong Player';
			}
			
			// Check if a trick is in progress
//$trickCards = $myDBaseObj->GetTrickCards();
			
			// NOTE: Could Check that the card laid is valid 
			
			// Mark card as played
			$cardStatus = $this->PlayCard($cardNo);
			if (!$cardStatus)
			{
				return 'PlayCard returned false ';
			}
			
			$endOfRound = false;
			$playerAdvance = 1;
			// Add card to the trick
			$trickCards = $myDBaseObj->GetTrickCards();
			if ($trickCards == null)
			{
				$trickId = $myDBaseObj->NewTrick($cardNo);
				$trickComplete = false;
			}
			else
			{
				$noOfPlayers = $this->myDBaseObj->GetNoOfPlayers();
				$trickCards[] = $cardNo;
				
				$noOfCardsInTrick = $isPlayingHand ? $noOfPlayers : ($noOfPlayers * $this->GetNoOfCardsToPassOn());				
				$myDBaseObj->OutputDebugMessage("********* noOfCardsInTrick = $noOfCardsInTrick");
				
				$trickComplete = (count($trickCards) == $noOfCardsInTrick);
			
				$trickId = $myDBaseObj->AddToTrick($cardNo);
				
				if ($trickComplete)
				{
					if ($isPlayingHand)
					{
						$winnerObj = $this->GetWinner($trickCards);
						
						// If last card in trick .... update scores																																{
						$playerAdvance = $winnerObj->index+1;	
						
						$winnerPlayerId = $myDBaseObj->AdvancePlayer($playerAdvance);
						$winnerScore = $winnerObj->score;
								
						$myDBaseObj->CompleteTrick($winnerPlayerId, $winnerScore);
						
						$endOfRound = ($this->playerNoOfCards == 0);
						
						if ($endOfRound)
						{
							// Add round scores to players
							$this->AddRoundScores();
							
							// Mark this round as complete
							$gameComplete = $this->IsGameComplete();
							$myDBaseObj->UpdateRoundState(CardzNetDBaseClass::ROUND_COMPLETE, $gameComplete);
						}

					}
					else
					{
						$this->AllCardsPassed($trickId);
					}
				}
				
			}
			
			// Get next player			
			if ($isPlayingHand)
			{
				if ($endOfRound)
				{
					// Next dealer 
					$nextPlayerId = $myDBaseObj->GetNextDealer();
				}
				else
				{
					$nextPlayerId = $myDBaseObj->AdvancePlayer($playerAdvance);
				}
				
				// Update next player and tick count 
				$this->nextPlayerId = $myDBaseObj->SetNextPlayer($nextPlayerId);			
				$this->GetCurrentPlayer();
			}
			else
			{
				// Update Tick Count 
				$myDBaseObj->IncrementTicker();
				
				// Get next playerId for shared logins
				$this->GetGameAndPlayer();
			}
							
			return 'OK';
		}
		
		function IsRoundComplete()
		{
			return ($this->myDBaseObj->GetUnplayedCardsCount() == 0);
		}
		
		function IsGameComplete()
		{
			$totalResults = $this->myDBaseObj->GetScores();
			foreach ($totalResults as $index => $totalResult)				
			{
				if ($totalResult->roundScore >= $totalResult->gameOpts['endScore'])
					return true;
			}
			
			return false;			
		}
		
		function CheckCanPlay(&$playersHand, $state = true)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// NOTE: Could Use ready in $players_list[]
			if ($myDBaseObj->GetRoundState() == self::ROUND_PASSCARD)
			{
				$noOfCards = $myDBaseObj->GetNoOfCards();
				$canPlay = ($noOfCards > ($this->myDBaseObj->cardsPerPlayer - $this->GetNoOfCardsToPassOn()));
				
				$myDBaseObj->OutputDebugMessage('CheckCanPlay() $canPlay='.$canPlay);
				
				return parent::CheckCanPlay($playersHand, $canPlay);
			}
		
			$trickCards = $this->myDBaseObj->GetTrickCards();
			if ($trickCards == null)
				return parent::CheckCanPlay($playersHand, $state);
				
			$firstSuitNo = 0;		
			foreach ($trickCards as $key => $cardNo)
			{
				$firstCardDef = $this->GetCardDef($cardNo);
				$firstSuitNo = $firstCardDef->suitNo;
				break;
			}
			
			$noOfCardsToPlay = 0;
			foreach ($playersHand->cards as $key => $cardNo)
			{
				$cardDef = $this->GetCardDef($cardNo);
				$canPlay = ($cardDef->suitNo == $firstSuitNo);
				$playersHand->canPlay[$key] = $canPlay;
				if ($canPlay) $noOfCardsToPlay++;
			}
			
			if ($noOfCardsToPlay == 0)
				return parent::CheckCanPlay($playersHand, $state);
				
			return $noOfCardsToPlay;
		}
		
		function IsMyTurn()
		{	
			$myDBaseObj = $this->myDBaseObj;
			
			if ($myDBaseObj->GetRoundState() == self::ROUND_PASSCARD)
			{
				$isMyTurn = ($myDBaseObj->GetNoOfCards() > ($this->myDBaseObj->cardsPerPlayer - $this->GetNoOfCardsToPassOn()));
			}
			else
				$isMyTurn = parent::IsMyTurn();		

			//$myDBaseObj->OutputDebugMessage("IsMyTurn() returns $isMyTurn");
			
			return $isMyTurn;
		}
			
		function GetUserPrompt()
		{			
			return $this->promptMsg;
		}
		
		function OutputPlayerId()
		{
			if (!isset($this->showingCards)) return;
			parent::OutputPlayerId();
		}
		
		function OutputTabletop()
		{
			$myDBaseObj = $this->myDBaseObj;
			if (CardzNetLibUtilsClass::IsElementSet('post', 'cardNo'))
			{
				$cardNoPost = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'cardNo');
				$cardParts = explode('card_', $cardNoPost);
				$cardNo = intval($cardParts[1]);
				$rtnStatus = $this->UpdateRound($cardNo);
				if ($myDBaseObj->isDbgOptionSet('Dev_ShowMiscDebug'))
					$myDBaseObj->OutputDebugMessage("UpdateRound returned $rtnStatus <br>\n");
			}
			else if (CardzNetLibUtilsClass::IsElementSet('post', 'dealcards'))
			{
				$gameId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'gameId');

				// Check this user is the dealer ...
				if ($myDBaseObj->IsNextPlayer() && $this->IsRoundComplete())
				{
					$this->DealCards();
					$nextPlayerId = $myDBaseObj->AdvancePlayer(1, 0, true);
					$this->nextPlayerId = $myDBaseObj->SetNextPlayer($nextPlayerId);			
					$this->GetCurrentPlayer();
					CardzNetLibUtilsClass::UnsetElement('post', 'playerId');
				}
			}
			
			parent::OutputTabletop();
		}
	}
}

?>