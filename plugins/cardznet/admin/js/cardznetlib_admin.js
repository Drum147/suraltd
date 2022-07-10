
function cardznetlib_OnSettingsLoad()
{
	/* Get Disabled GatewaysList */
	
	var selectedTabId = jQuery("#lastTabId").val();
	if (selectedTabId == '')
	{
		selectedTabId = cardznetlib_GetURLParam('tab');
		if (selectedTabId != '')
		{		
			selectedTabId = selectedTabId.replace(/_/g,'-');
			selectedTabId = selectedTabId.toLowerCase()
			selectedTabId = selectedTabId + '-tab';
		}
	}
	
	if (selectedTabId == '')
	{
		selectedTabId = tabIdsList[defaultTabIndex];
	}
	
	cardznetlib_SelectTab(selectedTabId);
	
	var selectedItemId = cardznetlib_GetURLParam('focus');
	if (selectedItemId != '')
	{		
		var focusElem;
		
		// Get the header 'Tab' Element					
		focusElem = document.getElementById(selectedItemId);
		focusElem.focus();
	}
	
	cardznetlib_headerModesInitialise();
}

function cardznetlib_ClickGateway(obj)
{
	cardznetlib_SelectTab('gateway-settings-tab');
}

function cardznetlib_ClickHeader(obj)
{
	cardznetlib_SelectTab(obj.id);
}

function cardznetlib_GetURLParam(paramID)
{
	var rtnVal = '';
	
	var Url = location.href;
	Url.match(/\?(.+)$/);
 	var Params = RegExp.$1;
 	
	Variables = Params.split ('&');
	for (i = 0; i < Variables.length; i++) 
	{
		Separ = Variables[i].split('=');
		if (Separ[0] == paramID)
		{
			rtnVal = Separ[1];
			break;
		}
	}
	
	return rtnVal;
}

function cardznetlib_SelectTab(selectedTabID)
{
	for (index = 0; index < tabIdsList.length-1; index++)
	{
		tabId = tabIdsList[index];
		cardznetlib_ShowOrHideTab(tabId, selectedTabID);
	}
	
	lastTabElem = document.getElementById('lastTabId');
	if (lastTabElem)
	{
		lastTabElem.value = selectedTabID;
	}
	
}

function cardznetlib_HideElement(elemID)
{
	thisElem = document.getElementById(elemID);
	thisElem.style.display = 'none';	
}

function cardznetlib_ShowOrHideTab(tabID, selectedTabID)
{
	var headerElem, tabElem, pageElem, tabWidth, rowstyle;
	
	selectedGatewayTag = '';
	if (tabID == selectedTabID)
	{
		// Show the matching settings rows
		rowstyle = '';
		
		gatewayElem = document.getElementById('GatewaySelected');
		if (gatewayElem)
		{
			var gatewayId = gatewayElem.value;
			gatewayParts = gatewayId.split('_');
			gatewayBase = gatewayParts[0];
			selectedGatewayTag = '-tab-'+gatewayBase+'-row';
		}
	}
	else
	{
		// Hide the matching settings rows
		rowstyle = 'none';
	}
	
	
	// Get the header 'Tab' Element					
	tabElem = document.getElementById(tabID);
	
	// Get the Body Element					
	pageElem = document.getElementById('recordoptions');

	// Get all <tr> entries for this TabID and hide/show them as required
	var tabElements = pageElem.getElementsByTagName("tr");
	for(var i = 0; i < tabElements.length; i++) 
	{
		rowElem = tabElements[i];
		id = rowElem.id;
		
   		if (id.indexOf('-settings-tab') > 0) 
    	{
		    if (id.indexOf(tabID) == 0) 
		    {
		    	if ( (id.indexOf('-tab-') > 0) && (id.indexOf('-tab-row') < 0) )
		    	{
		    		if (selectedGatewayTag != '')
			    	{
			    		/* Must be a Gateway specific entry */
				    	if (id.indexOf(selectedGatewayTag) < 0)
				    	{
							rowElem.style.display = 'none';		
							continue;		
						}		
					}			
				}
				
				// Show or Hide the settings row
				rowElem.style.display = rowstyle;				
			}
	    }
    }

	if (tabID == selectedTabID)
	{
		// Make the font weight normal and background Grey
		tabElem.style.fontWeight = 'bold';	
		tabElem.style.borderBottom = '0px red solid';
		//tabElem.style.backgroundColor = '#F9F9F9';
	}
	else
	{
		// Make the font weight normal and background Grey
		tabElem.style.fontWeight = 'normal';	
		tabElem.style.borderBottom = '1px black solid';		
		//tabElem.style.backgroundColor = '#F1F1F1';
	}	
}

function cardznetlib_OnTicketButtonClick(showEMailURL)
{
	var saleSelectObj = document.getElementById('TestSaleID');
	saleId = saleSelectObj.value;
	cardznetlib_OpenTicketView(saleId, showEMailURL);
}

function cardznetlib_OpenTicketView(saleId, showEMailURL)
{
	var wpnonceObj = document.getElementById('ShowEMailNOnce');
	
	saleParam = 'id=' + saleId;
	wpnonceParam = '_wpnonce=' + wpnonceObj.value;
	url = showEMailURL + '?' + saleParam + '&' + wpnonceParam;
	
	var templateObj = document.getElementById('emailTemplate');
	if (templateObj)
	{
		var templateFile = templateObj.value;
		url += '&template=' + templateFile;
	}
	
	window.open(url);
}

function cardznetlib_serialiseText(text)	
{
	text = encodeURIComponent(text);
	var serialiseText = 's:'+text.length+':"'+text+'";';
	return serialiseText;
}
	
function cardznetlib_serialiseArrayElem(key, value)	
{
	var serialiseText = cardznetlib_serialiseText(key) + cardznetlib_serialiseText(value);
	return serialiseText;
}
	
function cardznetlib_serialisePost(obj, classId)	
{	
	var formElem = obj.form;
	
	var elemsList = jQuery(formElem).find("." + classId);
	var elemLen = elemsList.length + 1;
	var serializedString = "a:"+elemLen+":{";
	
	serializedString += cardznetlib_serialiseArrayElem('post-classId', classId);
	
	for (i=0; i<elemsList.length; i++)
	{
		elemObj = elemsList[i];
		elemId = elemObj.id;
		
		if (elemObj.type != 'checkbox')
		{
			elemVal = elemObj.value;
		}
		else
		{
			elemVal = elemObj.checked;
		}	
		
		serializedString += cardznetlib_serialiseArrayElem(elemId, elemVal);
		elemObj.removeAttribute("name");
	}
	
	serializedString += "}";
		
	var classParts = classId.split('-');
	if (classParts.length > 1)
	{
		elemsList = jQuery(formElem).find("." + classParts[0]);
		for (i=0; i<elemsList.length; i++)
		{
			elemObj = elemsList[i];
			elemObj.removeAttribute("name");
		}		
	}
	
	var input = jQuery("<input>")
		.attr("type", "hidden")
		.attr("name", "cardznetlib_PostVars").val(serializedString);
               
	jQuery(formElem).append(jQuery(input));	
	
	return true;
}

function cardznetlib_headerModesInitialise()
{
	var HeaderImageModeElem = document.getElementById("PayPalHeaderImageMode");
	if (!HeaderImageModeElem) return;
	
	if (HeaderImageModeElem.addEventListener) 
	{
        HeaderImageModeElem.addEventListener("click", cardznetlib_headerModesVisibility, false);
    } 
    else 
    {
        HeaderImageModeElem.attachEvent("onclick", cardznetlib_headerModesVisibility);
    }  
	
	cardznetlib_headerModesVisibility();
}

function cardznetlib_headerModesVisibility()
{
	HeaderImageModeElem = document.getElementById("PayPalHeaderImageMode");
	HeaderImageFileElem = document.getElementById("PayPalHeaderImageFile");
	HeaderImageURLElem = document.getElementById("PayPalHeaderURL");
	
	switch (HeaderImageModeElem.value)
	{
		default:	
			HeaderImageFileElem.style.display = '';
			HeaderImageURLElem.style.display = 'none';
			break;
			
		case "ImagesURL":		
			HeaderImageFileElem.style.display = 'none';
			HeaderImageURLElem.style.display = '';
			break;
	}
	
}