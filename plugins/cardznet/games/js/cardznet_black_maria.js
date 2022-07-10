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
	
		switch (keyCode)
		{
			case 'ArrowLeft':
			case 'ArrowDown':
				cardznet_selectACard(-1);
				break;
				
			case 'ArrowRight':
			case 'ArrowUp':
				cardznet_selectACard(1);
				break;
				
			case 'Enter':
			case 'NumpadEnter':
				// Select card
				cardznet_playSelectedCard();
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
	
	console.log('KeyDown TRAPPED! ('+keyCode+')');
	event.preventDefault();
}

function cardznet_playcardClick(cardGUI)
{
	if (AJAXActive) return;

	var divElem = jQuery("#"+cardGUI);
	if (divElem.hasClass('card-back')) return;
	
	var cardClass = divElem[0].className;
	var cardId = cardznet_cardIdFromClass(cardClass);
	
	var input = jQuery("<input>")
		.attr("type", "hidden")
		.attr("name", "cardNo").val(cardId);
 	jQuery(divElem).append(jQuery(input));	
              
	input = jQuery("<input>")
		.attr("type", "hidden")
		.attr("name", "cardClass").val(cardClass);               
	jQuery(divElem).append(jQuery(input));	
	
	cardznet_SetBusy(true, 'card');
	cardznet_playSound('PlayCard');
	if (enableAJAX)
		cardznet_AJAXplaycard();
	else
		cardznet_playcardSubmit();

	return true;
}

function cardznet_can_update_screen()
{
	return true;
}

