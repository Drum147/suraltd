<?php
/* 
Description: Settings Admin Page functions
 
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

include 'cardznetlib_admin.php';
include 'cardznetlib_utils.php';

if (!class_exists('CardzNetLibSettingsAdminClass')) 
{
	class CardzNetLibSettingsAdminClass extends CardzNetLibAdminClass // Define class
	{
		function __construct($env) //constructor	
		{
			$this->pageTitle = 'Settings';
			
			$env['adminObj'] = $this;
			
			$this->adminListObj = $this->CreateAdminListObj($env, true);			
			
			// Call base constructor
			parent::__construct($env);	
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;			
					
			$SettingsUpdateMsg = '';
				
			if (CardzNetLibUtilsClass::IsElementSet('post', 'savechanges') || $this->isAJAXCall)
			{
				$this->CheckAdminReferer();
				
				if ($SettingsUpdateMsg === '')
				{
					$this->SaveSettings($myDBaseObj);					
					//$myDBaseObj->saveOptions();
					
					echo '<div id="message" class="updated"><p>'.__('Settings have been saved', $this->myDomain).'</p></div>';
				}
				else
				{
					$this->Reload();		
					
					echo '<div id="message" class="error"><p>'.$SettingsUpdateMsg.'</p></div>';
					echo '<div id="message" class="error"><p>'.__('Settings have NOT been saved.', $this->myDomain).'</p></div>';
				}
			}
			
		}
		
		function Output_MainPage($updateFailed)
		{			
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// Settings HTML Output - Start 
			
			$formClass = $this->myDomain.'-admin-form';
			echo '<div class="'.$formClass.'">'."\n";
?>	
	<form method="post">
<?php

			$this->WPNonceField();
			
			$this->adminListObj->detailsRowsDef = apply_filters($this->myDomain.'_filter_settingslist', $this->adminListObj->detailsRowsDef, $this->myDBaseObj);

			/*
			Usage: 
			
			add_filter('stageshow_filter_settingslist', 'StageShowFilterSettingsList', 10, 2);
			function StageShowFilterSettingsList($detailsRowsDef, $myDBaseObj)
			{
				$settingsCount = $myDBaseObj->getDbgOption('Dev_SettingCount');
				if (is_numeric($settingsCount))
				{				
					$newDefs = array();
					$i = 0;
					foreach ($detailsRowsDef as $index => $def)
					{
						$tabParts = explode('-', $def['Tab']);
						if ((count($tabParts) == 4) && ($tabParts[3] !== 'paypal'))
						{
							continue;
						}
						$newDefs[] = $def;
						$i++;
						if ($i >= $settingsCount) break;
					}		
					$detailsRowsDef = $newDefs;				
				}

				return $detailsRowsDef;
			}
			*/
						
						
			// Get setting as stdClass object
			$results = $myDBaseObj->GetAllSettingsList();

			if (count($results) == 0)
			{
				echo "<div class='noconfig'>" . __('No Settings Configured', $this->myDomain) . "</div>\n";
			}
			else
			{
				$this->adminListObj->OutputList($results, $updateFailed);
				
				if (!isset($this->adminListObj->editMode) || ($this->adminListObj->editMode))
				{
					if ((count($results) > 0) && !$this->usesAjax)
					{
						$this->OutputPostButton("savechanges", __("Save Changes", $this->myDomain), "button-primary");				
					}
				}
			}
			
?>
	</form>
	</div>
<?php			
		}
		
		function SaveSettings($dbObj)
		{
			$settingOpts = $this->adminListObj->GetDetailsRowsDefinition();

			// Save admin settings to database
			foreach ($settingOpts as $settingOption)
				{	
					if (isset($settingOption[CardzNetLibTableClass::TABLEPARAM_READONLY]))
					{
						continue;
					}
					
					$controlId = $settingOption[CardzNetLibTableClass::TABLEPARAM_ID];
					if ($this->isAJAXCall && !CardzNetLibUtilsClass::IsElementSet('post', $controlId))
					{
						continue;
					}

					switch ($settingOption[CardzNetLibTableClass::TABLEPARAM_TYPE])
					{
						case CardzNetLibTableClass::TABLEENTRY_READONLY:
						case CardzNetLibTableClass::TABLEENTRY_VIEW:
							break;
						
						case CardzNetLibTableClass::TABLEENTRY_CHECKBOX:
							$controlId = $settingOption[CardzNetLibTableClass::TABLEPARAM_ID];
							$dbObj->adminOptions[$controlId] = CardzNetLibUtilsClass::IsElementSet('post', $controlId) ? true : false;
							break;
						
						case CardzNetLibAdminListClass::TABLEENTRY_DATETIME:
							// Text Settings are "Trimmed"
							$controlId = $settingOption[CardzNetLibTableClass::TABLEPARAM_ID];
							$dbObj->adminOptions[$controlId] = CardzNetLibUtilsClass::GetHTTPDateTime('post', $controlId);	
							break;
							
						case CardzNetLibTableClass::TABLEENTRY_TEXT:
							// Text Settings are "Trimmed"
							$controlId = $settingOption[CardzNetLibTableClass::TABLEPARAM_ID];
							$dbObj->adminOptions[$controlId] = trim(CardzNetLibUtilsClass::GetHTTPTextElem('post', $controlId));	
							break;
						
						case CardzNetLibTableClass::TABLEENTRY_TEXTBOX:
							// Text Settings are "Trimmed"
							$controlId = $settingOption[CardzNetLibTableClass::TABLEPARAM_ID];
							$dbObj->adminOptions[$controlId] = CardzNetLibUtilsClass::GetHTTPTextareaElem('post', $controlId);	
							break;
						
						default:
							$controlId = $settingOption[CardzNetLibTableClass::TABLEPARAM_ID];
							$dbObj->adminOptions[$controlId] = CardzNetLibUtilsClass::GetHTTPTextElem('post', $controlId);	
							break;
					}
				}	
			
			$dbObj->saveOptions();		
			
			if ($this->isAJAXCall) $this->donePage = true;	
		}
		
		function EditTemplate($templateID, $folder='emails', $isEMail = true)
		{
			if (!current_user_can( 'manage_options' )) return false;
			
			$pluginRoot = str_replace('plugins', 'uploads', dirname(dirname(__FILE__)));
			$pluginId = basename($pluginRoot);	
			
/*
			$len = strlen($templateID);
			foreach (CardzNetLibUtilsClass::GetArrayKeys('post') as $postKey)
			{
				$postVal = CardzNetLibUtilsClass::GetHTTPTextElem('post', $postKey); 
				if (substr($postKey, 0, $len) !== $templateID) continue;
				$postKeyParts = explode('-', $postKey);
				if (count($postKeyParts) < 2) continue;
				if (($postKeyParts[1] === 'Button') || ($postKeyParts[1] === 'Save'))
				{
					$templateID = $postKeyParts[0];
					break;
				}
			}
*/			
			if (CardzNetLibUtilsClass::IsElementSet('post', $templateID.'-Button'))
			{
				$templateFile = CardzNetLibUtilsClass::GetHTTPFilenameElem('post', $templateID); 
				$templatePath = $pluginRoot;
				if ($folder != '') $templatePath .= '/'.$folder;
				$templatePath .= '/'.$templateFile;
		
				$editorID = $templateID.'-Editor';
				
				$contents = file_get_contents($templatePath);
				if ($isEMail)
				{
					$pregRslt = preg_match('/(.*[\n])(.*[\n])([\s\S]*?)(\*\/[\s]*?\?\>)/', $contents, $matches);
					if ($pregRslt != 1)
					{
						echo '<div id="message" class="error"><p>' . __('Error parsing file.', $this->myDomain).' - '.$templateFile. '</p></div>';
						$this->donePage = true;
						return true;					
					}
					$subject = $matches[2];
					$contents = $matches[3];
					$htmlMode = (strpos($contents, '</html>') > 0);
					$styles = '';
					if ($htmlMode)	
					{
						// Extract any styles from the source - Editor removes them
						if (preg_match_all('/\<style[\s\S]*?\>([\s\S]*?)\<\/style\>[\s\S]*?/', $contents, $matches) >= 1)
						{
							foreach ($matches[1] as $style)
							{
								$styles .= "\n<style>$style</style>\n";
							}
						}

						$pregRslt = preg_match('/\<body[\s\S]*?\>([\s\S]*?)\<\/body/', $contents, $matches);
						if ($pregRslt != 1)
						{
							echo '<div id="message" class="error"><p>' . __('Error parsing HTML in file', $this->myDomain).' - '.$templateFile. '</p></div>';
							return true;											
						}
						else
						{
							$contents = $matches[1];
						}
						$contents = str_replace("\n", "", $contents);		// Remove all line ends
						$mystyle = '
	<style Xtype="text/css">
	#'.$editorID.'_ifr
	{
		border: solid black 1px;
	}
	</style>';
						$settings = array(
							'wpautop' => false,
						    'editor_css' => $mystyle
						);
					}
					else
					{
						echo '
	<style Xtype="text/css">
	#'.$editorID.'-tmce,
	#qt_'.$editorID.'_toolbar,
	#wp-'.$editorID.'-media-buttons
	{
		display: none;
	}
	</style>';
						$settings = array();
					}								
				}
				else
				{
					// Just need a text editor
					$htmlMode = false;
					$settings = array(
						'wpautop' => true,
					    'media_buttons' => false,
					    'editor_css' => '', 
					    'tinymce' => false,
					    'quicktags' => false
						);
				}
				
				$saveButtonId = $templateID.'-Save';
				$buttonValue = __('Save', $this->myDomain);
				$buttonCancel = __('Cancel', $this->myDomain);
				
				$this->pageTitle .= " ($templateFile)";
				
				echo '<form method="post" id="'.$pluginId.'-fileedit">'."\n";
				if ($isEMail)
				{
					echo "<div id=".$pluginId."-fileedit-div-subject>\n";
					echo __("Subject", $this->myDomain)."&nbsp;<input name=\"$pluginId-fileedit-subject\" id=\"$pluginId-fileedit-subject\" type=\"text\" value=\"$subject\" maxlength=80 size=80 /></div>\n";
				}
				
				wp_editor($contents, $editorID, $settings);
				if ($htmlMode)
				{
					echo "<input name=\"$pluginId-fileedit-html\" id=\"$pluginId-fileedit-html\" type=\"hidden\" value=1 />\n";				
				}	
				echo "<input name=\"$pluginId-fileedit-isEMail\" id=\"$pluginId-fileedit-isEMail\" type=\"hidden\" value=\"$isEMail\" />\n";
				echo "<input name=\"$pluginId-fileedit-name\" id=\"$pluginId-fileedit-name\" type=\"hidden\" value=\"$templateFile\" />\n";
				echo "<input name=\"$pluginId-fileedit-folder\" id=\"$pluginId-fileedit-folder\" type=\"hidden\" value=\"$folder\" />\n";
				if (isset($styles)) echo "<input name=\"$pluginId-fileedit-styles\" id=\"$pluginId-fileedit-styles\" type=\"hidden\" value=\"".$styles."\" />\n";
				echo "<input class=\"button-primary\" name=\"$saveButtonId\" id=\"$saveButtonId\" type=\"submit\" value=\"$buttonValue\" />\n";
				echo "<input class=\"button-secondary\" type=\"submit\" value=\"$buttonCancel\" />\n";
				echo "</form>\n";

				$this->donePage = true;
				return true;				
			}				
			
			if (CardzNetLibUtilsClass::IsElementSet('post', $templateID.'-Save'))
			{
				$templateFile = CardzNetLibUtilsClass::GetHTTPFilenameElem('post', $pluginId.'-fileedit-name'); 
				$templateDir = CardzNetLibUtilsClass::GetHTTPFilenameElem('post', $pluginId.'-fileedit-folder'); 
				$fileParts = pathinfo($templateFile);
				$templateName = $fileParts['filename'];
				$templateExtn = $fileParts['extension'];
				$templateContents = CardzNetLibUtilsClass::GetHTTPTextareaElem('post', $templateID.'-Editor');
				switch($templateExtn)
				{
					case 'css':	
					case 'js':	
						$folderName = $templateExtn;
						$templateFolder = CARDZNETLIB_UPLOADS_PATH.'/'.$folderName.'/';
						$isPHP = false;
						$subject = '';
						break;
						
					default:
						if ($templateDir == '')
						{
							$folderName = '';
							$templateFolder = CARDZNETLIB_UPLOADS_PATH.'/';
							$isPHP = false;
							$subject = '';
							break;
						}
						$folderName = 'emails';
						$templateFolder = CARDZNETLIB_UPLOADS_PATH.'/'.$folderName.'/';
						$isPHP = true;
						$subject = CardzNetLibUtilsClass::GetHTTPTextElem('post', $pluginId.'-fileedit-subject')."\n";
						if (strpos($subject, '*/') || strpos($templateContents, '*/'))
						{
							echo '<div id="message" class="error"><p>'.__('Template not saved - Invalid Content', $this->myDBaseObj->get_domain()).'</p></div>';
							return false;
						}
					break;					
				}
				$defaultTemplateFolder = CARDZNETLIB_DEFAULT_TEMPLATES_PATH.$folderName;
				if (file_exists($defaultTemplateFolder.'/'.$templateFile))
				{
					// The template is a default template - Save with new name
					$fileNumber = 1;
					while (true)
					{
						$destFileName = $templateName."-$fileNumber.".$templateExtn;
						if ($destFileName == $templateFolder.$templateFile)
							break;
						if (!file_exists($templateFolder.$destFileName))
						{
							$templateFile = $destFileName;
							$this->myDBaseObj->adminOptions[$templateID] = $destFileName;
							$this->myDBaseObj->saveOptions();			
							break;							
						}
						$fileNumber++;
						if ($fileNumber > 1000) 
						{
							echo '<div id="message" class="error"><p>' . __('Template Not Saved - Could not rename file.', $this->myDomain).' - '.$templateFile. '</p></div>';
							return false;
						}
					}
				}
				
				$htmlMode = CardzNetLibUtilsClass::IsElementSet('post', $pluginId.'-fileedit-html');
				
				$contents  = '';
				
				if ($isPHP)
				{
					$contents .= "<?php /* Hide template from public access ... Next line is email subject - Following lines are email body\n";
					$contents .= $subject;					
				}
				
				if ($htmlMode)
				{
					$contents .= '
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">';

					if (CardzNetLibUtilsClass::IsElementSet('post', "$pluginId-fileedit-styles"))
					{
						$contents .= CardzNetLibUtilsClass::GetHTTPTextElem('post', "$pluginId-fileedit-styles");
					}

					$contents .= '
</head>
<body text="#000000" bgcolor="#FFFFFF">';
					$contents .= str_replace("<br />", "<br />\n", $templateContents);
					$contents .= '
</body>
</html>
';
				}
				else
				{
					$contents .= $templateContents;
				}
				
				if ($isPHP)
				{
					$contents .= "\n*/ ?>\n";
				}
				
				file_put_contents($templateFolder.$templateFile, $contents);
				echo '<div id="message" class="updated"><p>' . __('Template Updated.', $this->myDomain).' - '.$templateFile. '</p></div>';
			}				
			
			return false;
		}
		
	}
}




