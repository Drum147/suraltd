/* 
Description: CardzNet Javascript
 
Copyright 2014 Malcolm Shergold

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

/*
Classes involved with choosing a card with the keyboard

selectedcard - Added to a single card selected in players hand
playedcard
card-highlight
activecard - When included allows a card to be played 

*/

var selectingCardInHand = true;
var selectTargetIndex = 0;
var numberOfTargets = 0;
var oneEyedJacks;
var twoEyedJacks;

var unhideButtonUpdate = 50;
var unhideButtonMoving = false;

function cardznet_OnRefreshGameBoard()
{
	var unhideContainer = jQuery("#unhidecardsbutton").parent();
	if (typeof unhideContainer === 'undefined') return;
	
	if (!unhideButtonMoving)
	{
		unhideContainer.css('left', '400px');
		unhideButtonMoving = true;
		setTimeout(cardznet_moveClickHere, unhideButtonUpdate);
	}
}

function cardznet_moveClickHere()
{
	unhideButtonMoving = false;
	
	var unhideContainer = jQuery("#unhidecardsbutton").parent();
	if (typeof unhideContainer === 'undefined') return;
	
	var unhideTop = unhideContainer.css('top');
	if (typeof unhideTop === 'undefined') return;
	
	var top = parseInt(unhideTop.replace('px', ''));
	if (top < 600) top++;
	else top = 60;
	unhideContainer.css('top', top + "px");
	unhideButtonMoving = true;
	setTimeout(cardznet_moveClickHere, unhideButtonUpdate);
}

function cardznet_playcardClick(cardGUI)
{
	if (noOfActiveCards == 0) return;
	
	var selectedElems = jQuery("#"+cardGUI);
	
	var cardClass = selectedElems[0].className;
	
	if (cardGUI.indexOf("spaceontable_") != -1)
	{
		// Click active card on table
		if (selectedElems.parent().hasClass('card-highlight'))
		{
			cardznet_addCardToBoard(cardGUI);
		}
		
		return;
	}
	
	if (selectedElems.parent().hasClass('card-highlight'))
	{
		// Pass dead card back to server 
		cardznet_addCardToBoard(cardGUI);
	}
	else if (selectedElems.parent().hasClass('playedcard'))
	{
		cardznet_revertToSelectingCard();		
	}	
	else if (selectedElems.parent().hasClass('selectedcard'))	// Card is already selected - play it
	{
		var selectedcardElems = jQuery('.card-highlight');
		
		selectingCardInHand = false;
		selectedElems.parent('div').addClass('playedcard');
		selectedElems.parent('div').removeClass('selectedcard');
		
		cardznet_showAMatchingTarget(0);
	}	
	else
	{
		// Deselect all cards in players hand
		activecardElems = cardznet_deselectAllCards();		
		
		// Select the one we clicked
		selectedElems.parent('div').addClass('selectedcard');
		
		// Update selectTargetIndex to match selected card
		var cardId = selectedElems[0].id;
		selectedCardIndex = parseInt(cardId.replace('cardGUID_', ''))-1;
		
		cardznet_showMatchingTargets();							
	}

	cardznet_playSound('SelectCard');
}

function cardznet_revertToSelectingCard()
{
	selectingCardInHand = true;
	
	cardznet_selectACard(0);
	cardznet_showMatchingTargets();							
}

function cardznet_onkeydown(event)
{
	origEvt = event.originalEvent;
	if (typeof origEvt !== 'undefined')
	{
		var keyCode = origEvt.code
		
		var dealcardsElems = jQuery("#ajaxdealcards");
		if (dealcardsElems.length > 0)
		{
			switch (keyCode)
			{
				case 'Enter':
				case 'NumpadEnter':				
					cardznet_dealcardsClick();
					break;
			}
			return;
		}
	
		if (selectingCardInHand)
		{
			switch (keyCode)
			{
				case 'ArrowDown':
					cardznet_selectACard(1);
					cardznet_showMatchingTargets();
					break;
					
				case 'ArrowUp':
					cardznet_selectACard(-1);
					cardznet_showMatchingTargets();
					break;
					
				case 'ArrowRight':
				case 'Enter':
				case 'NumpadEnter':
					if (cardznet_showAMatchingTarget(0))
					{
						cardznet_playSelectedCard();
						selectingCardInHand = false;
					}
					break;
					
				case 'ArrowLeft':
					break;
					
				case 'Space':
					cardznet_unhidecardsClick();
					break;
					
				case 'Tab':	
					// "Blocked" keys		
					break;
					
				default:
					console.log('KeyDown Ignored! '+keyCode+' - default processing');
					return;	// Do nothing ... Allow default
			}
		}
		else
		{
			switch (keyCode)
			{
				case 'ArrowDown':
					if (numberOfTargets > 2)
						cardznet_showNextMatchingTargetRow(1);
					else if (numberOfTargets > 0)
						cardznet_showNextMatchingTarget(1);
					else
					{
						cardznet_selectACard(1);
						cardznet_showMatchingTargets();
						selectingCardInHand = true;
					}
					break;
					
				case 'ArrowUp':
					if (numberOfTargets > 2)
						cardznet_showNextMatchingTargetRow(-1);
					else if (numberOfTargets > 0)
						cardznet_showNextMatchingTarget(-1);
					else
					{
						cardznet_selectACard(-1);
						cardznet_showMatchingTargets();
						selectingCardInHand = true;
					}
					break;
					
				case 'Enter':
				case 'NumpadEnter':
					// Select card
					var targetId = cardznet_getSelectedTarget();
					cardznet_playcardClick(targetId);
					break;
				
				case 'ArrowLeft':
				case 'Escape':
					cardznet_revertToSelectingCard();
					break;
					
				case 'ArrowRight':
					if (numberOfTargets > 0)
						cardznet_showNextMatchingTarget(1);
					break;
					
				default:
					console.log('KeyDown Ignored! '+keyCode+' - default processing');
					return;	// Do nothing ... Allow default
			}
		}

	}
	
	console.log('KeyDown TRAPPED! ('+keyCode+')');
	event.preventDefault();
}

function cardznet_clearMatchingTargets()
{
	var matchingCardElems = jQuery('.card-highlight');
	matchingCardElems.removeClass('card-highlight');
}

function cardznet_getTargetSelector()
{
	var cardName = cardznet_getSelectedCardName();
	var cardId = cardznet_getSelectedCardId();
	var cardSelector = "undefined";
	if (oneEyedJacks.indexOf(cardName) != -1)
	{
		// One eyed jacks remove
		cardSelector = ".not-my-colour";
	}
	else if (twoEyedJacks.indexOf(cardName) != -1)
	{
		// Two eyed jacks add
		cardSelector = ".spaceontable";
	}
	else
	{
		// Look for a specific card
		cardSelector = ".spaceontable_"+cardName;
	}
	
	return cardSelector;
}

function cardznet_showMatchingTargets()
{
	cardznet_clearMatchingTargets();

	var cardSelector = cardznet_getTargetSelector();

	var matchingList = jQuery(cardSelector);
	numberOfTargets = matchingList.length;
	if (numberOfTargets > 0)
	{
		matchingList.parent('div').addClass('card-highlight');
	}
	else
	{
		// Dead card - No target cards 
		var deadCardElem = jQuery('.selectedcard');
		deadCardElem.addClass('playedcard');		
		deadCardElem.removeClass('selectedcard');		
		deadCardElem.addClass('card-highlight');		
		
		selectingCardInHand = false;
	}
	
}

function cardznet_showAMatchingTarget(index)
{
	cardznet_clearMatchingTargets();
	var cardSelector = cardznet_getTargetSelector();
	var matchingList = jQuery(cardSelector);
	if (matchingList.length == 0) return false;
	
	if (index >= matchingList.length)
		index = 0;
	else if (index < 0)
		index = matchingList.length-1;
		
	var matchedId = matchingList[index].id;
	var matchedTarget = jQuery('#'+matchedId);
	matchedTarget.parent('div').addClass('card-highlight');
	
	selectTargetIndex = index;
	return true;
}

function cardznet_getRowNumber(cardElem)
{
	cardIdElems = cardElem.id.split("_");
	return cardIdElems[1];
}

function cardznet_getColNumber(cardElem)
{
	cardIdElems = cardElem.id.split("_");
	return cardIdElems[2];
}

function cardznet_showNextMatchingTargetRow(roffset)
{
	cardznet_clearMatchingTargets();
	var cardSelector = cardznet_getTargetSelector();
	var matchingList = jQuery(cardSelector);
	if (matchingList.length == 0) return false;
	
	var lastCol = cardznet_getColNumber(matchingList[selectTargetIndex]);
	var lastRow = cardznet_getRowNumber(matchingList[selectTargetIndex]);

	var srchOffset;
	if (roffset > 0) srchOffset = 1;
	else srchOffset = -1;
	
	var srchIndex = selectTargetIndex;
	for (i=0; i<matchingList.length; i++)
	{
		srchIndex += srchOffset;
		if (srchIndex >= matchingList.length) break;
		if (srchIndex < 0) break;
		
		var srchRow = cardznet_getRowNumber(matchingList[srchIndex]);
		var srchRowOffset = srchRow - lastRow;
		if (srchRowOffset == 0)
			continue;
				
		var srchCol = cardznet_getColNumber(matchingList[srchIndex]);
		if ((srchOffset > 0) && (srchRowOffset == 1))
		{
			if (srchCol < lastCol)
			continue;
		}
			
		if ((srchOffset < 0) && (srchRowOffset == -1))
		{
			if (srchCol > lastCol)
			continue;
		}
			
		cardznet_showAMatchingTarget(srchIndex);
		return;
	}
	
	if (srchOffset < 0)
		cardznet_showAMatchingTarget(matchingList.length-1);
	else
		cardznet_showAMatchingTarget(0);
	
}

function cardznet_showNextMatchingTarget(offset)
{
	index = selectTargetIndex+offset;
	cardznet_showAMatchingTarget(index);
}

function cardznet_getSelectedTarget()
{
	var selectedcardElems = jQuery('.card-highlight');
	if (selectedcardElems.length != 1) return "";
	
	return selectedcardElems.find('.card-face')[0].id;
}

function cardznet_addCardToBoard(targetId)
{
	if (AJAXActive) return;

	// Get the card the player has selected
	var cardName = cardznet_getSelectedCardName();

	// OK - Do it!
	var divElem = jQuery(".playercards");
	cardznet_addHiddenInput(divElem, "cardId", cardName);
	cardznet_addHiddenInput(divElem, "targetId", targetId);

	cardznet_SetBusy(true, 'card');
	cardznet_playSound('PlayCard');
	
	selectingCardInHand = true;

	if (enableAJAX)
		cardznet_AJAXplaycard();
	else
		cardznet_playcardSubmit();

	return true;
}

function cardznet_can_update_screen()
{
	if (cardznet_getActiveCards() > 0)
		return false;
		
	return true;
}

