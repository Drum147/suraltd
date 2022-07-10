/* 
Description: Javascript & jQuery Code for CardzNet
 
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

var gameId;
var gameName;

var tickerTimeout = 5000;	// Default - Updated by WP settings 
var tickerColour = "";
var tickerCount = 0;
var tickerTimer = 0;
var activeTimeout = 600000; // 10 minutes
var timeToTimeout = 0;
var selectedCardIndex = -1;
var noOfActiveCards = 0;

var lastTimeout = 0;

var hideWPElements = true;
var specialTestmode = false;

var timerRunning = false;
var AJAXActive = false;
var gameEnded = false;


function CardzNetLib_addWindowsLoadHandler(newHandler)
{
	jQuery(document).ready(
		function() 
		{
		    newHandler();
		}
	);
}

function cardznet_OnLoadTabletop()
{
	cardznet_setupScreen();
	gameId = cardznet_getHiddenInput('AJAX-gameId');
	if ( gameId == 'undefined') return;

	gameName = cardznet_getHiddenInput('AJAX-gameName');
	
	cardznet_OnRefresh();
	
	timeToTimeout = activeTimeout;
	
	cardznet_getActiveCards();
	cardznet_addkeydownhandler("body");
	cardznet_startTickerTimer(tickerTimeout, 'onLoad');
}

function cardznet_OnLoadResponse()
{
	cardznet_setupScreen();
}

function cardznet_OnRefresh()
{
	if (typeof cardznet_OnRefreshGameBoard === 'function') 
		cardznet_OnRefreshGameBoard();
}

function cardznet_get_topelem()
{
	var topElem = jQuery('.cardznet_msgpage').parent();
	if (topElem.length > 0) return topElem;
		
	topElem = jQuery('#cardznet').parent();
	if (topElem.length > 0) return topElem;
	
	topElem = jQuery('#loginform').parent().parent().parent();
	if (topElem.length > 0) return topElem;
	
	topElem = jQuery('#message').parent();
	if (topElem.length > 0) return topElem;
	
	return topElem;
}

function cardznet_setupScreen()
{
	if (!hideWPElements) return;
	
	var parentElem = cardznet_get_topelem();

	cardznet_removeWPElements(parentElem);

	cardznet_removeThemeStyles();
	
	// Change the id of the html element so we can style it 
	var htmlElems = jQuery('html');
	htmlElems.removeClass();
	htmlElems.attr('id', "cardznet_html");
}

function cardznet_removeThemeStyles()
{
	var headElem = jQuery('head');
	
	// Now get theme styles in headElem
	var themeStyles = headElem.children().filter(
		function(index)
		{
			var rtnVal = false;
			
			switch (this.tagName)
			{
				case 'LINK':
					var href = this.href;
					if (href.search("themes/") != -1)
					{
						rtnVal = true;
					}
					break;
					
				case 'STYLE':
					rtnVal = true;
					break;
					
				default:
					break;
			}
			
			return rtnVal;
		}
	);
	
	themeStyles.remove();
}

function cardznet_removeWPElements(anchorElem)
{
	var tagName = anchorElem[0].tagName;
	if (tagName == 'BODY')
	{
		anchorElem.removeClass();
		anchorElem.attr('id', "cardznet_body");
		return;
	}
	
	// Remove its siblings
	var siblings  = anchorElem.siblings();
	siblings = siblings.filter(
		function(index)
		{
			var rtnVal = true;
			
			switch (this.tagName)
			{
				case 'LINK':
				case 'SCRIPT':
					var tagId = this.id;
					if (tagId.search("cardznet") != -1)
					{
						rtnVal = false;
					}
					break;
					
				default:
					break;
			}
			
			return rtnVal;
		}
	);
	siblings.remove();

	// Now clear the id and classes of this element
	anchorElem.removeAttr("id role");
	//anchorElem.id="";
	anchorElem.removeClass();
	
	var parentElem = anchorElem.parent();
	cardznet_removeWPElements(parentElem);
}

function cardznet_addkeydownhandler(elemSelector)
{
	var elemsList = jQuery(elemSelector);
	if (elemsList.length == 0) return;
	var mainElem = elemsList[0];
	elemsList.on('keydown', cardznet_onkeydown);
}


function cardznet_getSelectedElem()
{
	var selectedFrameElems = jQuery(".selectedcard");
	if (selectedFrameElems.length == 0) selectedFrameElems = jQuery(".playedcard");
	if (selectedFrameElems.length == 0) return "";
	
	selectedCardElems = selectedFrameElems.find('.card-face');
	
	return selectedCardElems[0];
}

function cardznet_getSelectedCardGID()
{
	var selectedcardElem = cardznet_getSelectedElem();
	if (selectedcardElem == "") return "";
	return selectedcardElem.id;
}
	
function cardznet_getSelectedCardId()
{
	var selectedcardElem = cardznet_getSelectedElem();
	if (selectedcardElem == "") return "";
	var cardId = cardznet_cardIdFromClass(selectedcardElem.className);
	return cardId;
}

function cardznet_getSelectedCardName()
{
	var selectedcardElem = cardznet_getSelectedElem();
	if (selectedcardElem == "") return "";
	var cardName = cardznet_cardNameFromClass(selectedcardElem.className);
	return cardName;
}

function cardznet_playSelectedCard()
{
	var cardGID = cardznet_getSelectedCardGID();
	cardznet_playcardClick(cardGID);
}

function cardznet_cardNameFromClass(cardClass)
{
	var classesList = cardClass.split(" ");
	for (i = 0; i < classesList.length; i++) 
	{
		var nextClass = classesList[i];
		if (nextClass.indexOf('-of-') != -1)
			return nextClass;
	}
	
	return "";
}

function cardznet_cardIdFromClass(cardClass)
{
	var classesList = cardClass.split(" ");
	for (i = 0; i < classesList.length; i++) 
	{
		var nextClass = classesList[i];
		var prefix = nextClass.substr(0, 7);
 		if (prefix == "cardNo_")
		{
			return 'card_'+nextClass.substr(7);
		}
			
	}
	
	return "";
}

function cardznet_deselectAllCards()
{
	if (noOfActiveCards == 0) return 0;
	
	var activecardElems = jQuery(".activecard");
	if (activecardElems.hasClass('card-back')) return;
	
	jQuery(".selectedcard").removeClass('selectedcard');
	jQuery(".playedcard").removeClass('playedcard');
	
	return activecardElems;
}

function cardznet_selectACard(offset)
{
	if (noOfActiveCards == 0) return;
	
	activecardElems = cardznet_deselectAllCards();
	
	selectedCardIndex = selectedCardIndex + offset;
	if (selectedCardIndex < 0) 
		selectedCardIndex = activecardElems.length-1;
	else if (selectedCardIndex >= activecardElems.length)
		selectedCardIndex = 0;
		
	var selectedElemId = activecardElems[selectedCardIndex].id;
	jQuery("#"+selectedElemId).parent('div').addClass('selectedcard');

	cardznet_playSound('SelectCard');
}

function cardznet_removeClassFromAll(classid)
{
	var matchingElemsList = jQuery("."+classid);
	if (matchingElemsList.length > 0)
		matchingElemsList.removeClass(classid);
}

function cardznet_unhidecardsClick()
{
	cardznet_removeClassFromAll('card-back');
	cardznet_removeClassFromAll('hidden-div');
	
	var buttonElems = jQuery("#unhidecardsbutton");
	buttonElems.remove();
	
	cardznet_playSound('RevealCards');
	
	cardznet_getActiveCards();
}

function cardznet_hashiddencards()
{
	var hiddenCardElemsList = jQuery(".playercards").find(".card-back");
	return (hiddenCardElemsList.length > 0);
}

function cardznet_getHiddenInput(tagId)
{
	var hiddenElems = jQuery('#'+tagId);
	if (hiddenElems.length == 0) return 'undefined';
	
	var tagVal = hiddenElems[0].value;
	var tagIntVal = parseInt(tagVal, 10);
  
	if (!isNaN(tagIntVal)) 
		return tagIntVal;
  	
	return tagVal;
}

function cardznet_addHiddenInput(targetElem, tagId, elemValue)
{
	var input = jQuery("<input>")
		.attr("type", "hidden")
		.attr("name", tagId).val(elemValue);
 	jQuery(targetElem).append(jQuery(input));	   
}

function cardznet_getActiveCards()
{
	var activeElems = jQuery(".activecard");
	noOfActiveCards = activeElems.length;
	if (noOfActiveCards == 1)
	{
		var hideButtonElem = jQuery("#unhidecardsbutton");		
		if (hideButtonElem.length == 0)
		{
			// Only one possible card ... select it!
			activeElems.parent().addClass('selectedcard');	/* CHECKIT */
		}
	}
	
	return (noOfActiveCards > 0);
}

function cardznet_playcardSubmit()
{
	var formElem = jQuery("#cardznet")[0];

	formElem.submit();

	return true;
}

function cardznet_dealcardsClick()
{
	if (enableAJAX)
		cardznet_AJAXdealcards();
	else
		cardznet_playcardSubmit();
	
	return false;
}

function cardznet_RequestUpdate(newTicker)
{
	var data = cardznet_AJAXPrepare('cardznet');	
	data['newTicker'] = newTicker;
	cardznet_CallAJAX(data, cardznet_AJAXcb_refresh, cardznet_AJAXcb_error);
}

function cardznet_AJAXPrepare(action)
{
	cardznet_stopTickerTimer(action);
	
	AJAXActive = true;
	
    var data = {
        'request': action, // your action name 
    };
    
	var hiddenElems = jQuery('input[type=hidden]');
	for (i=0; i<hiddenElems.length; i++)
	{
		var elemName = hiddenElems[i].name;
		var elemValue = hiddenElems[i].value;
		data[elemName] = elemValue;
	}
	
	if (!cardznet_hashiddencards())
	{
		data['cardsVisible'] = true;
	}
	
	return data;
}

function cardznet_AJAXplaycard()
{
	var data = cardznet_AJAXPrepare('cardznet');	
	cardznet_CallAJAX(data, cardznet_AJAXcb_refresh, cardznet_AJAXcb_error);
}

function cardznet_AJAXdealcards()
{
	var data = cardznet_AJAXPrepare('dealcards');
	data['dealcards'] = true;
	cardznet_CallAJAX(data, cardznet_AJAXcb_refresh, cardznet_AJAXcb_error);
}

function cardznet_AJAXcb_refresh(response)
{
	try 
	{
		// AJAX Callback - Check for Updated frontend
		reply = JSON.parse(response);
 	
	               
		var formElem = jQuery("#cardznet")[0];
		formElem.innerHTML = reply['html'];

		// Updated AJAXVars hidden element is in the HTML
		selectedCardIndex = -1;	// No card selected
		
		var tickTime = tickerTimeout;
		if (gameEnded)
		{
			refreshGameId = cardznet_getHiddenInput('AJAX-gameId');
			if (gameId != refreshGameId)
			{
				gameEnded = false;
				gameId = refreshGameId;
				
				refreshGameName = cardznet_getHiddenInput('AJAX-gameName');
				if (refreshGameName != gameName)
				{
					// Reload the page - Loads new Javascript 
					formElem.innerHTML = '';
					location.reload();
					return;
				}
			}
			else
			{
				// Still waiting for a new game 
				timeToTimeout = 0;
			}
		}
		
		if (cardznet_getActiveCards())
			cardznet_playSound('Ready');
		
		cardznet_OnRefresh();
	} 
	catch (err) 
	{
    }
	
	cardznet_SetBusy(false, 'card');
	cardznet_startTickerTimer(tickerTimeout, 'refresh');
	AJAXActive = false;
}

function cardznet_restartTickerTimer()
{
	var refreshButtonElems = jQuery("#restartdiv");
	refreshButtonElems.addClass('cardznet_remove');
	timeToTimeout = activeTimeout;

	cardznet_startTickerTimer(tickerTimeout, 'restart');
}

function cardznet_startTickerTimer(timeout, context)
{
	if (specialTestmode) return;
	
	isSeqMode = cardznet_getHiddenInput('AJAX-isSeqMode');

	if (isSeqMode == 'true') return;
	
	if (timerRunning)
	{
		// Reject request if timer is already running ...
		return;
	}

	// Start the timer
	tickerTimer = setTimeout(cardznet_checkTicker, timeout);
	lastTimeout = timeout;
	timerRunning = true;
}

function cardznet_stopTickerTimer(context)
{
	if (!timerRunning) return;
	
	clearTimeout(tickerTimer);
	tickerTimer = 0;
	timerRunning = false;
}

function cardznet_checkTicker()
{
	timerRunning = false;
	
	// Request ticker and wait for response .....
	cardznet_AJAXticker();
}

function cardznet_AJAXticker()
{
	tickerCount++;
	cardznet_GetTickerDirect();
}

function cardznet_handle_ticker_response(newTicker)
{
	if (newTicker <= 0) 
	{
		gameEnded = true;
		cardznet_RequestUpdate(newTicker);
		return;
	}
	
	gameTicker = cardznet_getHiddenInput('AJAX-gameTicker');

	if (!cardznet_can_update_screen)
		return;
		
	if (newTicker != gameTicker)
	{
		timeToTimeout = activeTimeout;	// Reset Timeout
		cardznet_RequestUpdate(newTicker);
		return;
	}
	
	timeToTimeout -= lastTimeout;
	if (timeToTimeout <= 0)
	{
		// Show the Restart Button and exit
		var refreshButtonElems = jQuery("#restartdiv");
		refreshButtonElems.removeClass('cardznet_remove');
		return;
	}
	
	cardznet_toggle_tickertell(newTicker);
	cardznet_startTickerTimer(tickerTimeout, 'timeout');
}

function cardznet_toggle_tickertell(newTicker)
{
	var tickertellElem = jQuery("#tickertell");
	if (tickertellElem.length != 1) return;
	
	if (tickerColour == 'white')
		tickerColour = 'yellow';
	else
		tickerColour = 'white';
	tickertellElem.css('background-color', tickerColour);
}

function cardznet_AJAXcb_error(status)
{
	cardznet_startTickerTimer(tickerTimeout, 'cb_error');
	AJAXActive = false;
}

function cardznet_EnableControls(classId, state, disable)
{
	var classSpec = "."+classId;
	var buttonElemsList = jQuery(classSpec);
	jQuery.each(buttonElemsList,
		function(i, listObj) 
		{
			var uiElemSpec = "#" + listObj.name;
			var uiElem = jQuery(uiElemSpec);
			
			if (state)
			{
				uiElem.prop("disabled", false);			
				uiElem.css("cursor", "default");				
			}
			else
			{
				if (disable) uiElem.prop("disabled", true);			
				uiElem.css("cursor", "progress");
			}
				
	    	return true;
		}
	);
	
	return state;		
}

function cardznet_playSound(soundId) 
{
	try
	{
		var soundElemId = "cardznet_mp3_" + soundId;
		var soundElem = document.getElementById(soundElemId);
		if (soundElem != null)
			soundElem.play();
	}
	catch (err)
	{
	}
}

function cardznet_SetBusy(isBusy, elemClassId, buttonsClassId) 
{
	if (isBusy)
	{
		jQuery(".page").addClass('busypage');
	
		jQuery("body").css("cursor", "progress");		
		jQuery(".card-frame").css("cursor", "progress");		
		cardznet_EnableControls(elemClassId, false, true);
	}
	else
	{
		cardznet_EnableControls(elemClassId, true, true);
		jQuery("body").css("cursor", "default");	
		if (buttonsClassId !== undefined)	
			jQuery("." + buttonsClassId).css("cursor", "pointer");		
	}
}

function cardznet_GetTickerDirect()
{
    /* Implement Manual Sale EMail */
	var postvars = {
		gameId: gameId,
		action: "ticker",
		jquery: "true"
	};
	
	url = tickerURLTemplate.replace('%g', gameId);
	
	/* Get New HTML from Server */
    jQuery.post(url, postvars,
	    function(data, status)
	    {
	    	if (status == "success") 
	    	{
				var newTicker = parseInt(data, 10); 
	    		cardznet_handle_ticker_response(data);
			}
			else
			{
			}
	    }
    ).fail(function(e)
    	{ 
    		// Handle errors here 
    		if(e.status == 404)
    		{ 
    			// Not found ... ticker file has disappeared!
	    		cardznet_handle_ticker_response(-1);
    		}
     	}
    	);
/*    
    	function(e)
    	{ 
    		if(e.status == 404)
    		{ 
    			// ... 
    		}
    	);
*/   	
    return 0;
}

function cardznet_sortScoresClick(event)
{
	var action = event.target.id;
	switch (action)
	{
		case 'playerName':
		case 'playerScore':
		case 'playerTotal':
			break;
	}
}

