<?php
/* 
Description: Core Library Admin Page functions
 
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

if (!class_exists('CardzNetLibAdminBaseClass')) 
{
	if (!defined('CARDZNETLIB_AJAXNONCEKEY'))
		define('CARDZNETLIB_AJAXNONCEKEY', 'cardznetlib-ajax-nonce-key');
		
	class CardzNetLibAdminBaseClass // Define class
  	{
		var $caller;				// File Path of descendent class
		var $myPluginObj;
		var $myDBaseObj;
		var $myDomain;
		var $usesAjax = false;
		
		function __construct($env)	 //constructor	
		{
			$this->caller = $env['Caller'];
			$this->myPluginObj = $env['PluginObj'];
			$this->myDBaseObj = $env['DBaseObj'];
			$this->myDomain = $env['Domain'];
			
			$this->isAJAXCall = isset($env['ajax']);			
		}
		
		function WPNonceField($referer = '', $name = '_wpnonce', $echo = true)
		{
			$this->myDBaseObj->WPNonceField($referer, $name, $echo);
		}
		
		function CheckAdminReferer($referer = '')
		{
			// AJAX calls are validated by the AJAX calback function
			if ($this->isAJAXCall) return true;
			
			return $this->myDBaseObj->CheckAdminReferer($referer);
		}

		// Bespoke translation functions added to remove these translations from .po file
		function getTL8($text, $domain = 'default') 
		{ 
			return __($text, $domain);
		}
		
		function echoTL8($text, $domain = 'default') 
		{ 
			return _e($text, $domain);
		}
		
  	}
}

if (!class_exists('CardzNetLibAdminClass')) 
{
  class CardzNetLibAdminClass extends CardzNetLibAdminBaseClass// Define class
  {
		var $env;
		
		var $currentPage;		
		var $adminOptions;
		var $adminListUsesSerializedPost = false;

		var $editingRecord;
      		
		function __construct($env)	 //constructor	
		{
			parent::__construct($env);
			
			$this->adminOptions = $this->myDBaseObj->adminOptions;
				
			$this->env = $env;
			$this->env['parent'] = $this;
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj  = $this->myDBaseObj;
			
			$myDBaseObj->AllUserCapsToServervar();
			
			$this->editingRecord = false;			
			$this->pluginName = basename(dirname($this->caller));
			
			if (!isset($this->pageTitle)) $this->pageTitle = "***  pageTitle Undefined ***";
			
			$this->adminMsg = '';			

			$bulkAction = '';
			if ( CardzNetLibUtilsClass::IsElementSet('post', 'doaction_t' ) )
			{
				$bulkAction = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'action_t'); 
			}
			
			if ( CardzNetLibUtilsClass::IsElementSet('post', 'doaction_b' ) )
			{
				$bulkAction = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'action_b'); 
			}
			
 			if (($bulkAction !== '') && CardzNetLibUtilsClass::IsElementSet('post', 'rowSelect'))
 			{
				// Bulk Action Apply button actions
				$this->CheckAdminReferer();
				$actionError = false;
				
				$selectedRows = CardzNetLibUtilsClass::GetHTTPIntegerArray('post', 'rowSelect');
				foreach($selectedRows as $recordId)
				{
					$actionError |= $this->DoBulkPreAction($bulkAction, $recordId);
				}
						
				$actionCount = 0;
				if (!$actionError)
				{
					foreach($selectedRows as $recordId)
					{
						if ($this->DoBulkAction($bulkAction, $recordId))
						{
							$actionCount++;
						}
					}
				}
				
				$actionMsg = $this->GetBulkActionMsg($bulkAction, $actionCount);
				if ($actionCount > 0)
				{
					$this->myDBaseObj->PurgeDB();						
					echo '<div id="message" class="updated"><p>'.$actionMsg.'</p></div>';
				}
				else
				{										
					echo '<div id="message" class="error"><p>'.$actionMsg.'</p></div>';
				}
				
 			}
 			
			$pluginID = $this->myDBaseObj->get_domain();
			
			$adminHideClass = $pluginID.'-admin-hide';
			echo "<style>.$adminHideClass { display: none; } </style>\n";
			
			$tableClass = $pluginID.'-admin';			
			echo '<div class="wrap '.$tableClass.'">';

			$this->GetSearchParams();
			
			ob_start();
			$this->ProcessActionButtons();
			$actionPage = ob_get_contents();
			ob_end_clean();
			
			$this->OutputTitle();
			
			echo $actionPage;			
			if (!isset($this->donePage))
			{
				$this->Output_MainPage($this->adminMsg !== '');				
			}
			
			echo '</div>';
		}
		
		function OutputTitle()
		{
			if ($this->isAJAXCall) return;
			
			if ($this->pageTitle != '')
			{
				$iconID = 'icon-'.$this->myDomain;
				echo '
					<div id="'.$iconID.'" class="icon32"></div>
					<h2>'.$this->myDBaseObj->get_pluginName().' - '.__($this->pageTitle, $this->myDomain).'</h2>'."\n";				
			}
			
		}
		
		static function ValidateEmail($ourEMail)
		{
			if (strpos($ourEMail, '@') === false)
				return false;
				
			return true;
		}

		static function IsOptionChanged($adminOptions, $optionID)
		{
			if (CardzNetLibUtilsClass::IsElementSet('post', $optionID) && (trim(CardzNetLibUtilsClass::GetArrayElement($adminOptions, $optionID)) !== CardzNetLibUtilsClass::GetHTTPTextElem('post', $optionID)))
			{
				return true;
			}
						
			return false;
		}
		
		function UpdateHiddenRowValues($result, $index, $settings, $dbOpts)
		{
			// Save option extensions
			foreach ($settings as $setting)
			{
				$settingId = $setting[CardzNetLibTableClass::TABLEPARAM_ID];
				
				if ($setting[CardzNetLibTableClass::TABLEPARAM_TYPE] == CardzNetLibTableClass::TABLEENTRY_CHECKBOX)
				{
					$newVal = CardzNetLibUtilsClass::IsElementSet('post', $settingId.$index) ? 1 : 0;
					CardzNetLibUtilsClass::SetElement('post', $settingId.$index, $newVal);
				}
				else if (!CardzNetLibUtilsClass::IsElementSet('post', $settingId.$index)) 
					continue;
				else if ($setting[CardzNetLibTableClass::TABLEPARAM_TYPE] != CardzNetLibTableClass::TABLEENTRY_TEXTBOX)
				{
					$newVal = CardzNetLibUtilsClass::GetHTTPTextElem('post', $settingId.$index);
				}
				else if (isset($setting[CardzNetLibTableClass::TABLEPARAM_ALLOWHTML]))
				{
					$newVal = CardzNetLibUtilsClass::GetHTTPTextHttpElem('post', $settingId.$index);
				}
				else
				{
					$newVal = CardzNetLibUtilsClass::GetHTTPTextareaElem('post', $settingId.$index);
				}
				// self::TABLEENTRY_TEXTBOX
					
				if ($newVal != $result->$settingId)
				{
					$this->myDBaseObj->UpdateASetting($newVal, $dbOpts['Table'], $settingId, $dbOpts['Index'], $index);					
				}
			}
		}
		
		function DoBulkPreAction($bulkAction, $recordId)
		{
			return false;
		}
				
		function DoBulkAction($bulkAction, $recordId)
		{
			echo "DoBulkAction() function not defined in ".get_class()."<br>\n";
			return false;
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			echo "GetBulkActionMsg() function not defined in ".get_class()."<br>\n";
		}
		
		function CreateAdminListObj($env, $editMode = false)
		{
			$className = get_class($this);
			$classId = str_replace('AdminClass', 'AdminListClass', $className);
			
			$adminListObj = new $classId($env, $editMode);
			if ($adminListObj->tableUsesSerializedPost)
			{
				$this->adminListUsesSerializedPost = true;
			}
			
			return $adminListObj;
		}
				
		function OutputPostButton($buttonId, $buttonText, $buttonClass = "button-secondary", $scanClass = '')
		{
			if ($scanClass == '') $scanClass = 'cardznetlib_PostVal';

			$clickEvent = '';	
			if ($this->adminListUsesSerializedPost)	
			{
				$clickEvent="return cardznetlib_JSONEncodePost(this, '$scanClass')";				
			}
			$this->OutputButton($buttonId, $buttonText, $buttonClass, $clickEvent);			
		}
		
		function OuputSearchButton($label = '', $buttonId = '')
		{
			if ($label == '') 
			{
				$label = __("Search Sales", $this->myDomain);
				if ($buttonId == '') $buttonId = 'searchsales';
			}
			if ($buttonId == '') $buttonId = strtolower(str_replace(" ", "", $label));
			$searchTextInput = $buttonId.'text';
			
			$searchText = CardzNetLibUtilsClass::GetHTTPTextElem('request', $searchTextInput); 
			echo '<div class="'.$this->myDomain.'-'.$buttonId.'"><input type="text" maxlength="'.PAYMENT_API_SALEEMAIL_TEXTLEN.'" size="20" name="'.$searchTextInput.'" id="'.$searchTextInput.'" value="'.$searchText.'" autocomplete="off" />'."\n";
			$this->OutputButton($buttonId."button", $label);					
			echo '</div>'."\n";
		}
		
		function OutputButton($buttonId, $buttonText, $buttonClass = "button-secondary", $clickEvent = '')
		{
			$buttonText = __($buttonText, $this->myDomain);
			
			if ($clickEvent != '')
			{
				$clickEvent = ' onclick="'.$clickEvent.'" ';
			}
			
			echo "<input class=\"$buttonClass\" type=\"submit\" $clickEvent name=\"$buttonId\" value=\"$buttonText\" />\n";
		}
		
		function Output_MainPage($updateFailed)
		{
			echo "Output_MainPage() function not defined in ".get_class()."<br>\n";
		}
		
		function GetSearchParams()
		{
		}
		
		function ProcessActionButtons()
		{
			echo "ProcessActionButtons() function not defined in ".get_class()."<br>\n";
		}
	
		function AdminUpgradeNotice() 
		{ 
?>
	<div id="message" class="updated fade">
		<p><?php echo '<strong>Plugin is ready</strong>'; ?></p>
	</div>
<?php
		}
	
 	}
}



