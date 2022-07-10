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

if (!class_exists('CardzNetCardsClass'))
{
	class CardzNetCardsClass // Define class
	{
		var	$card_defs = array();
		
		function __construct()
		{
			$cardNo = 1;
			$suits = array('clubs', 'diamonds', 'spades', 'hearts');
			$cards = array('two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'jack', 'queen', 'king', 'ace');
			foreach ($suits as $suitIndex => $suit)
			{
				foreach ($cards as $cardIndex => $card)
				{
					$cardScore = $this->GetCardScore($suit, $card);
					$name = $card.'-of-'.$suit;
					$this->card_defs[] = $this->CreateCardDef($name, $cardNo++, $suitIndex, $cardIndex, $cardScore);
				}
			}
			
			$cards = array('card-back', 'black-joker', 'red-joker', 'card-blank');
			foreach ($cards as $card)
			{
				$this->card_defs[] = $this->CreateCardDef($card, $cardNo++, 5, $cardIndex++, 0);
			}

			//CardzNetLibUtilsClass::print_r($this->card_defs, '$this->card_defs');
		}	

		function GetCardScore($suit, $card)
		{
			$cardScore = 0;
			
			return $cardScore;
		}
			
		function CreateCardDef($name, $cardNo, $suitNo = 0, $rank=0, $score=0 )
		{
			$cardDef = new stdclass();
			$cardDef->cardno = $cardNo;
			$cardDef->name = $name;
			$cardDef->suitNo = $suitNo;
			$cardDef->rank = $rank;
			$cardDef->score = $score;
			return $cardDef;			
		}
		
		function GetCardDef($cardNo)
		{
			// Keys start at 0 - card numbers start at 1
			$key = $cardNo-1;
			if (($key >= count($this->card_defs)) || ($key < 0))
				return $this->CreateCardDef('card-back', $cardNo);
				
			return $this->card_defs[$key];
		}
		
		function GetCardName($cardNo)
		{
			$cardDef = $this->GetCardDef($cardNo);
			return $cardDef->name;
		}
		
		function GetPrettyCardName($cardNo)
		{
			$cardName = $this->GetCardName($cardNo);
			$cardName = str_replace("-of-", " of ", $cardName);
			$cardName = ucwords($cardName);
			return $cardName;
		}
		
		function GetCardNo($cardName)
		{
			foreach ($this->card_defs as $cardDef)
			{
				if ($cardName == $cardDef->name)
				{
					return $cardDef->cardno;
				}
			}
			
			return null;
		}
	}
}

?>