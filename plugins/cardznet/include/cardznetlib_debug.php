<?php
/* 
Description: Code for Managing Debug Options
 
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

include CARDZNETLIB_INCLUDE_PATH.'cardznetlib_admin.php';
include CARDZNETLIB_INCLUDE_PATH.'cardznetlib_table.php';

if (!class_exists('CardzNetLibDebugSettingsClass')) 
{
	class CardzNetLibDebugSettingsClass extends CardzNetLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Diagnostics';
			
			// Call base constructor
			parent::__construct($env);			
		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{
			$customTestPath = dirname(dirname(__FILE__)).'/test/cardznetlib_customtest.php';
			if (file_exists($customTestPath))
			{
				// cardznetlib_customtest.php must create and run test class object
				include $customTestPath;
				if (class_exists('CardzNetLibCustomTestClass'))
				{
					new CardzNetLibCustomTestClass($this->env);	
					return;						
				}
			}
			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$this->submitButtonID = $myDBaseObj->get_name()."_testsettings";
			
			// TEST Settings HTML Output - Start 			
			echo '<form method="post">'."\n";
			$this->WPNonceField();
			
			$this->Test_DebugSettings(); 

			echo '</form>'."\n";			
			// TEST HTML Output - End
		}
		
		static function GetOptionsDefs($inherit = true)
		{
			$testOptionDefs = array(
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show SQL',          CardzNetLibTableClass::TABLEPARAM_NAME => 'cbShowSQL',          CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_ShowSQL', ),				
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show DB Output',    CardzNetLibTableClass::TABLEPARAM_NAME => 'cbShowDBOutput',     CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_ShowDBOutput', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show Memory Usage', CardzNetLibTableClass::TABLEPARAM_NAME => 'cbShowMemUsage',     CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_ShowMemUsage', ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Show EMail Msgs',   CardzNetLibTableClass::TABLEPARAM_NAME => 'cbShowEMailMsgs',    CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_ShowEMailMsgs', ),				
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Block EMail Send',  CardzNetLibTableClass::TABLEPARAM_NAME => 'cbBlockEMailSend',   CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_BlockEMailSend', ),				
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Log EMail Msgs',    CardzNetLibTableClass::TABLEPARAM_NAME => 'cbLogEMailMsgs',     CardzNetLibTableClass::TABLEPARAM_ID => 'Dev_LogEMailMsgs', ),				
			);
		
			return $testOptionDefs;
		}
		
		function GetOptionsDescription($optionName)
		{
			switch ($optionName)
			{
				case 'Show SQL':		return 'Show SQL Query Strings';
				case 'Show DB Output':	return 'Show SQL Query Output';
				case 'Show EMail Msgs': return 'Output EMail Message Content to Screen';
				
				default:	
					return "No Description Available for $optionName";					
			}
		}
		
		function Test_DebugSettings() 
		{
			$doneCheckboxes = false;
			
			$myDBaseObj = $this->myDBaseObj;
			
			if (CardzNetLibUtilsClass::IsElementSet('post', 'testbutton_SaveDebugSettings')) 
			{
				$this->CheckAdminReferer();
					
				$optDefs = $this->GetOptionsDefs();
				foreach ($optDefs as $optDef)
				{
					$label = $optDef[CardzNetLibTableClass::TABLEPARAM_LABEL];
					if ($label === '') continue;
					
					$settingId = $optDef[CardzNetLibTableClass::TABLEPARAM_ID];
					$ctrlId = isset($optDef[CardzNetLibTableClass::TABLEPARAM_NAME]) ? $optDef[CardzNetLibTableClass::TABLEPARAM_NAME] : 'ctrl'.$settingId;
					$settingValue = CardzNetLibUtilsClass::GetHTTPTextElem('post',$ctrlId);
					$myDBaseObj->dbgOptions[$settingId] = $settingValue;
				}
					
				$myDBaseObj->saveOptions();
				
				echo '<div id="message" class="updated"><p>Debug options updated</p></div>';
			}
			
			if (CardzNetLibUtilsClass::IsElementSet('post', 'testbutton_DescribeDebugSettings')) 
			{
				$optDefs = $this->GetOptionsDefs();
				echo "<table>\n";
				foreach ($optDefs as $optDef)
				{
					$label = $optDef[CardzNetLibTableClass::TABLEPARAM_LABEL];
					$ctrlDesc = $this->GetOptionsDescription($label);
					echo "<tr><td><strong>$label</strong></td><td>$ctrlDesc</td></tr>\n";
				}
				echo "</table>\n";
			}
		
		$tableClass = $this->myDomain."-settings-table";
		
		echo '
		<h3>Debug Settings</h3>
		<table class="'.$tableClass.'">
		';
			
		if ($myDBaseObj->ShowDebugModes())
			echo '<br>';
		
		$optDefs = $this->GetOptionsDefs();
		$count = 0;
		$checkboxesPerLine = 4;

		foreach ($optDefs as $optDef)
		{
			$label = $optDef[CardzNetLibTableClass::TABLEPARAM_LABEL];
			
			if ($count == 0) echo '<tr valign="top">'."\n";
			if ($label !== '')
			{
				$settingId = $optDef[CardzNetLibTableClass::TABLEPARAM_ID];
				$ctrlId = isset($optDef[CardzNetLibTableClass::TABLEPARAM_NAME]) ? $optDef[CardzNetLibTableClass::TABLEPARAM_NAME] : 'ctrl'.$settingId;
				$optValue = CardzNetLibUtilsClass::GetArrayElement($myDBaseObj->dbgOptions, $settingId);
				if (!isset($optDef[CardzNetLibTableClass::TABLEPARAM_TYPE]))
				{
					$optDef[CardzNetLibTableClass::TABLEPARAM_TYPE] = CardzNetLibTableClass::TABLEENTRY_CHECKBOX;
				}
				
				switch ($optDef[CardzNetLibTableClass::TABLEPARAM_TYPE])
				{
					case 'TBD':
						$optText = ($optValue == 1) ? __('Enabled') : __('Disabled');
						$optEntry = $label. '&nbsp;('.$optText.')';
						break;
					
					case CardzNetLibTableClass::TABLEENTRY_TEXT:
						$label .= '&nbsp;';
						if ($count != 0) echo '</tr>';
						if (!$doneCheckboxes) echo '<tr><td>&nbsp;</td></tr>';
						echo '<tr valign="top">'."\n";
						echo '<td align="left" valign="middle" width="25%">'.$label.'</td>'."\n";
						echo '<td align="left" colspan='.$checkboxesPerLine.'>';
						echo '<input name="'.$ctrlId.'" type="input" autocomplete="off" maxlength="127" size="60" value="'.$optValue.'" />'."\n";
						echo '</td>'."\n";
						$count = $checkboxesPerLine;
						break;
	
					case CardzNetLibTableClass::TABLEENTRY_CHECKBOX:
						$optIsChecked = ($optValue == 1) ? 'checked="yes" ' : '';
						echo '<td align="left" width="25%">';
						echo '<input name="'.$ctrlId.'" type="checkbox" value="1" '.$optIsChecked.' />&nbsp;'.$label;
						echo '</td>'."\n";
						break;
				}
			}
			else
				echo '<td align="left">&nbsp;</td>'."\n";
			$count++;
			if ($count >= $checkboxesPerLine) 
			{
				echo '</tr>'."\n";
				$count = 0;
			}
		}

?>			
			<tr valign="top" colspan="4">
				<td>
				</td>
				<td>&nbsp;</td>
				<td>
				</td>
			</tr>
		</table>
		
		<input class="button-primary" type="submit" name="testbutton_SaveDebugSettings" value="Save Debug Settings"/>
		<input class="button-secondary" type="submit" name="testbutton_DescribeDebugSettings" value="Describe Debug Settings"/>
	<br>
<?php
		}
		
	}
}

?>