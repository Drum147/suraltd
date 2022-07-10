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

require_once CARDZNET_INCLUDE_PATH.'cardznetlib_debug.php';      
require_once CARDZNET_INCLUDE_PATH.'cardznetlib_adminlist.php';      

if (!class_exists('CardzNetDebugAdminClass')) 
{
	class CardzNetDebugAdminClass extends CardzNetLibDebugSettingsClass // Define class
	{		
		function __construct($env, $editMode = false) //constructor
		{	
			// Call base constructor
			parent::__construct($env, $editMode);
		}
		
		static function GetOptionsDefs($inherit = true)
		{
			$testOptionDefs = array(
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Disable AJAX',         CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_DisableAJAX',     CardzNetLibTableClass::TABLEPARAM_AFTER => 'Dev_ShowDBOutput', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show Misc Debug',      CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_ShowMiscDebug',   CardzNetLibTableClass::TABLEPARAM_AFTER => 'Dev_DisableAJAX', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Log HTML',             CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_LogHTML',         CardzNetLibTableClass::TABLEPARAM_AFTER => 'Dev_ShowMiscDebug', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show Call Stack',      CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_ShowCallStack',   CardzNetLibTableClass::TABLEPARAM_AFTER => 'Dev_LogHTML', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Debug To Log',         CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_DebugToLog',      CardzNetLibTableClass::TABLEPARAM_AFTER => 'Dev_ShowCallStack', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show Test Controls',   CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_ShowTestCtrls', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Disable JS/CSS Cache', CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_DisableJSCache', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Disable Ticker',       CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_DisableTicker', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show Rerun Game',      CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_RerunGame', ),
			);
		
			$testOptionDefs = CardzNetLibAdminListClass::MergeSettings(parent::GetOptionsDefs(), $testOptionDefs);
			
			self::RemoveOptionDef('EMail', $testOptionDefs);

			return $testOptionDefs;
		}
		
		static function RemoveOptionDef($srchLabel, &$OptionDefs)
		{
			foreach ($OptionDefs as $index =>$OptionDef)
			{
				$label = $OptionDef[CardzNetLibTableClass::TABLEPARAM_LABEL];
				if (strpos($label, $srchLabel) !== false)
				{
					unset($OptionDefs[$index]);
				}
			}
		}
		
	}
}
		
?>