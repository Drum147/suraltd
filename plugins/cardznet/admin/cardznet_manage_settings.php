<?php
/* 
Description: Code for Managing CardzNet Settings
 
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

if (!class_exists('CardzNetSettingsAdminListClass')) 
{
	define('CARDZNET_URL_TEXTLEN', 110);
	define('CARDZNET_PARSERKEY_TEXTLEN', 80);
	
	class CardzNetSettingsAdminListClass extends CardzNetLibAdminListClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{	
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->defaultTabId = 'cardznet-sounds-settings-tab';
			$this->HeadersPosn = CardzNetLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "cardznet-settings";
		}
		
		function GetRecordID($result)
		{
			return '';
		}
	
		function GetTableRowCount()
		{
			return 1;
		}
	
		function GetMainRowsDefinition()
		{
			$rowDefs = array();
		
			$this->isTabbedOutput = true;
			
			$rowDefs = array(			
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'General', CardzNetLibTableClass::TABLEPARAM_ID => 'cardznet-gen-settings-tab', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Sounds', CardzNetLibTableClass::TABLEPARAM_ID => 'cardznet-sounds-settings-tab', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Comms', CardzNetLibTableClass::TABLEPARAM_ID => 'cardznet-comms-settings-tab', ),
				
			);
		
			//$rowDefs = $this->MergeSettings(parent::GetMainRowsDefinition(), $rowDefs);
			return $rowDefs;
		}		
		
		function GetDetailsRowsDefinition()
		{
			$pluginID = CARDZNET_FOLDER;
/*			
			$pageStyles = trim('
#wrapper { width: 100% !important; }
.page #wrapper { margin: 0px !important; }
.page #wrapper { padding: 0px !important; }
#main { width: 100% !important; }
#main { padding: 0px !important; }
#content { width: auto !important; }
#html  { margin: 0px !important; }
			');
*/
			$pageStyles = '';
								
			$mp3_Ready = 'chimes.mp3';
			$mp3_RevealCards = 'ding.mp3';
			$mp3_SelectCard = '';
			$mp3_PlayCard = 'foreground.mp3';
			
			$RefreshTime = 3000;
			$TimeoutTime = 600000;

			$nextPlayerMimicRotationOptions = array(
				CARDZNET_MIMICMODE_PLAYER.'|'.__('Player At Bottom', $this->myDomain),
				CARDZNET_MIMICMODE_DEALER.'|'.__('Dealer At Bottom', $this->myDomain),
				CARDZNET_MIMICMODE_ADMIN.'|'.__('Admin At Bottom', $this->myDomain),
			);
		
			$nextPlayerMimicDisplayOptions = array(
				CARDZNET_MIMICVISIBILITY_ALWAYS.'|'.__('Always Visible', $this->myDomain),
				CARDZNET_MIMICVISIBILITY_WITHHAND.'|'.__('When Hand Visible', $this->myDomain),
				CARDZNET_MIMICVISIBILITY_NEVER.'|'.__('Always Hidden', $this->myDomain),
			);
		
			$uploadedImagesPath = CARDZNETLIB_UPLOADS_PATH . '/images';
			$uploadedEMailsPath = CARDZNETLIB_UPLOADS_PATH . '/emails';
			$uploadedSoundsPath = CARDZNETLIB_UPLOADS_PATH . '/mp3';
			
			$rowDefs = array(
				array(self::TABLEPARAM_LABEL => 'Ready',                         self::TABLEPARAM_TAB => 'cardznet-sounds-settings-tab',      self::TABLEPARAM_ID => 'mp3_Ready',               self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ADDEMPTY => true, self::TABLEPARAM_DIR => $uploadedSoundsPath, self::TABLEPARAM_EXTN => 'mp3,wav', self::TABLEPARAM_DEFAULT => $mp3_Ready, ),
				array(self::TABLEPARAM_LABEL => 'Reveal Cards',                  self::TABLEPARAM_TAB => 'cardznet-sounds-settings-tab',      self::TABLEPARAM_ID => 'mp3_RevealCards',         self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ADDEMPTY => true, self::TABLEPARAM_DIR => $uploadedSoundsPath, self::TABLEPARAM_EXTN => 'mp3,wav', self::TABLEPARAM_DEFAULT => $mp3_RevealCards, ),
				array(self::TABLEPARAM_LABEL => 'Select Card',                   self::TABLEPARAM_TAB => 'cardznet-sounds-settings-tab',      self::TABLEPARAM_ID => 'mp3_SelectCard',          self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ADDEMPTY => true, self::TABLEPARAM_DIR => $uploadedSoundsPath, self::TABLEPARAM_EXTN => 'mp3,wav', self::TABLEPARAM_DEFAULT => $mp3_SelectCard, ),
				array(self::TABLEPARAM_LABEL => 'Play Card',                     self::TABLEPARAM_TAB => 'cardznet-sounds-settings-tab',      self::TABLEPARAM_ID => 'mp3_PlayCard',            self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ADDEMPTY => true, self::TABLEPARAM_DIR => $uploadedSoundsPath, self::TABLEPARAM_EXTN => 'mp3,wav', self::TABLEPARAM_DEFAULT => $mp3_PlayCard, ),

				array(self::TABLEPARAM_LABEL => 'Refresh Time (ms)',             self::TABLEPARAM_TAB => 'cardznet-comms-settings-tab',       self::TABLEPARAM_ID => 'RefreshTime',             self::TABLEPARAM_TYPE => self::TABLEENTRY_INTEGER,  self::TABLEPARAM_LEN => 5, self::TABLEPARAM_DEFAULT => $RefreshTime, ),
				array(self::TABLEPARAM_LABEL => 'Timeout Time (ms)',             self::TABLEPARAM_TAB => 'cardznet-comms-settings-tab',       self::TABLEPARAM_ID => 'TimeoutTime',             self::TABLEPARAM_TYPE => self::TABLEENTRY_INTEGER,  self::TABLEPARAM_LEN => 5, self::TABLEPARAM_DEFAULT => $TimeoutTime, ),

				array(self::TABLEPARAM_LABEL => 'Logs Folder Path',              self::TABLEPARAM_TAB => 'cardznet-gen-settings-tab',         self::TABLEPARAM_ID => 'LogsFolderPath',          self::TABLEPARAM_TYPE => self::TABLEENTRY_TEXT,     self::TABLEPARAM_LEN => CARDZNET_PARSERKEY_TEXTLEN, self::TABLEPARAM_SIZE => CARDZNET_PARSERKEY_TEXTLEN, ),
				
				array(self::TABLEPARAM_LABEL => 'Next Player Mimic Rotation',    self::TABLEPARAM_TAB => 'cardznet-gen-settings-tab',         self::TABLEPARAM_ID => 'NextPlayerMimicRotation', self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ITEMS => $nextPlayerMimicRotationOptions, ),
				array(self::TABLEPARAM_LABEL => 'Next Player Mimic Visibility',  self::TABLEPARAM_TAB => 'cardznet-gen-settings-tab',         self::TABLEPARAM_ID => 'NextPlayerMimicDisplay',  self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ITEMS => $nextPlayerMimicDisplayOptions, ),
				array(self::TABLEPARAM_LABEL => 'Admin Items per Page',          self::TABLEPARAM_TAB => 'cardznet-gen-settings-tab',         self::TABLEPARAM_ID => 'PageLength',              self::TABLEPARAM_TYPE => self::TABLEENTRY_INTEGER,  self::TABLEPARAM_LEN => 3, self::TABLEPARAM_DEFAULT => 10),
				
				array(self::TABLEPARAM_LABEL => 'EMail Logo Image File',         self::TABLEPARAM_TAB => 'cardznet-comms-settings-tab',       self::TABLEPARAM_ID => 'PayPalLogoImageFile',     self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_ADDEMPTY => true, self::TABLEPARAM_DIR => $uploadedImagesPath, self::TABLEPARAM_EXTN => 'gif,jpeg,jpg,png', self::TABLEPARAM_DEFAULT => 'cardznet_logo.png', ),

				array(self::TABLEPARAM_LABEL => 'Invitation Time Limit (Hours)', self::TABLEPARAM_TAB => 'cardznet-comms-settings-tab',       self::TABLEPARAM_ID => 'inviteTimeLimit',         self::TABLEPARAM_TYPE => self::TABLEENTRY_INTEGER,  self::TABLEPARAM_LEN => 3, self::TABLEPARAM_DEFAULT => 10),

				array(self::TABLEPARAM_LABEL => 'Invitation EMail',              self::TABLEPARAM_TAB => 'cardznet-comms-settings-tab',       self::TABLEPARAM_ID => 'inviteEMail',             self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_DIR => $uploadedEMailsPath, self::TABLEPARAM_EXTN => 'php', self::TABLEPARAM_DEFAULT => 'cardznet_invitationEMail.php', CardzNetLibTableClass::TABLEPARAM_BUTTON => 'Edit', ),
				array(self::TABLEPARAM_LABEL => 'Login Created EMail',           self::TABLEPARAM_TAB => 'cardznet-comms-settings-tab',       self::TABLEPARAM_ID => 'addedLoginEMail',         self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_DIR => $uploadedEMailsPath, self::TABLEPARAM_EXTN => 'php', self::TABLEPARAM_DEFAULT => 'cardznet_addedLoginEMail.php', CardzNetLibTableClass::TABLEPARAM_BUTTON => 'Edit', ),
				array(self::TABLEPARAM_LABEL => 'Added to Group EMail',          self::TABLEPARAM_TAB => 'cardznet-comms-settings-tab',       self::TABLEPARAM_ID => 'addedToGroupEMail',       self::TABLEPARAM_TYPE => self::TABLEENTRY_SELECT,   self::TABLEPARAM_DIR => $uploadedEMailsPath, self::TABLEPARAM_EXTN => 'php', self::TABLEPARAM_DEFAULT => 'cardznet_addedToGroupEMail.php', CardzNetLibTableClass::TABLEPARAM_BUTTON => 'Edit', ),
			);
			
			return $rowDefs;
		}
				
		function JS_Bottom($defaultTab)
		{
			$jsCode  = parent::JS_Bottom($defaultTab);		
			$jsCode .= "
<script>
CardzNetLib_addWindowsLoadHandler(cardznetlib_OnSettingsLoad); 
</script>
			";
			
			return $jsCode;
		}
		
	}
}
		
require_once CARDZNET_INCLUDE_PATH.'cardznetlib_settingsadmin.php';

if (!class_exists('CardzNetSettingsAdminClass')) 
{
	class CardzNetSettingsAdminClass extends CardzNetLibSettingsAdminClass // Define class
	{		
		function __construct($env)
		{
			// Call base constructor
			parent::__construct($env);
		}
		
		function GetAdminListClass()
		{
			return 'CardzNetSettingsAdminListClass';			
		}
		

		function ProcessActionButtons()
		{
			$donePage = false;
			$donePage |= $this->EditTemplate('inviteEMail');
			$donePage |= $this->EditTemplate('addedLoginEMail');
			$donePage |= $this->EditTemplate('addedToGroupEMail');

			if ($donePage) return;
			
			parent::ProcessActionButtons();		
		
		}
	
	}
}
		

?>