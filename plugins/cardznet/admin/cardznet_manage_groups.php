<?php
/* 
Description: Code for Managing Groups
 
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
require_once CARDZNET_INCLUDE_PATH.'cardznetlib_htmlemail_api.php';      

if (!class_exists('CardzNetGroupsAdminListClass')) 
{
	// --- Define Class: CardzNetGroupsAdminListClass
	class CardzNetGroupsAdminListClass extends CardzNetLibAdminListClass // Define class
	{	
		function __construct($env) //constructor
		{
			$this->hiddenRowsButtonId = 'TBD';		

			// Call base constructor
			parent::__construct($env, true);
			
			$this->hiddenRowsButtonId = __('Details', CARDZNET_DOMAIN_NAME);		
			
			//$this->SetRowsPerPage(self::CARDZNETLIB_EVENTS_UNPAGED);
			
			if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				$this->bulkActions = array(
					self::BULKACTION_DELETE => __('Delete', CARDZNET_DOMAIN_NAME),
					);
			}
					
			$this->HeadersPosn = CardzNetLibTableClass::HEADERPOSN_BOTH;
			
		}
		
		function GetRecordID($result)
		{
			return $result->groupId;
		}
		
		function GetCurrentURL() 
		{			
			$currentURL = parent::GetCurrentURL();
			return $currentURL;
		}
		
		function GetDetailsRowsFooter()
		{
			$ourOptions = array(
			);
		
			$ourOptions = self::MergeSettings(parent::GetDetailsRowsFooter(), $ourOptions);
			
			return $ourOptions;
		}
		
		function GetTableID($result)
		{
			return "cardznet-groups-tab";
		}
		
		function ShowGroupDetails($result)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$groupId = $result->groupId;
			
			$groupResults = $myDBaseObj->GetMembersById($groupId);				
			$invitationsList = $myDBaseObj->GetInvitationByGroupId($groupId);
			if ((count($groupResults) > 0) || (count($invitationsList) > 0))
			{
				$groupDetails = $this->BuildGroupDetails($groupId, $groupResults, $invitationsList);
			}
			else
			{
				$groupDetails = __("No Members", CARDZNET_DOMAIN_NAME);
			}

			return $groupDetails;
		}
				
		function GetListDetails($result)
		{
			return $this->myDBaseObj->GetGroupById($result->groupId);
		}
		
		function BuildGroupDetails($groupId, $groupResults, $invitationsList)
		{
			$env = $this->env;

			foreach ($groupResults as $groupRec)
			{
				$groupRec->memberName = CardzNetDBaseClass::GetUserName($groupRec->memberUserId);
				$groupRec->memberEMail = CardzNetDBaseClass::GetUserEMail($groupRec->memberUserId);
				$groupRec->memberStatus = __("Confirmed", CARDZNET_DOMAIN_NAME);
			}
			
			foreach ($invitationsList as $invitation)
			{
				$dateTime = $this->myDBaseObj->FormatDateForAdminDisplay($invitation->inviteDateTime);
				
				$inviteRec = new stdClass();
				$inviteRec->groupId = $groupId;
				$inviteRec->memberUserId = 0;
				$inviteRec->memberName = trim("{$invitation->inviteFirstName} {$invitation->inviteLastName}");
				$inviteRec->memberEMail = $invitation->inviteEMail;
				$inviteRec->memberStatus = __("Invited", CARDZNET_DOMAIN_NAME)." - $dateTime";
				$groupResults[] = $inviteRec;
			}
			
			$groupDetailsList = $this->CreateGroupAdminDetailsListObject($env, $this->editMode, $groupResults);	
			
			// Set Rows per page to disable paging used on main page
			$groupDetailsList->enableFilter = false;
			
			ob_start();	
			$groupDetailsList->OutputList($groupResults);	
			$memberDetailsOutput = ob_get_contents();
			ob_end_clean();

			return $memberDetailsOutput;
		}
		
		function NeedsConfirmation($bulkAction)
		{
			switch ($bulkAction)
			{
				default:
					return parent::NeedsConfirmation($bulkAction);
			}
		}
		
		function ExtendedSettingsDBOpts()
		{
			return parent::ExtendedSettingsDBOpts();
		}
		
		function FormatUserName($userId, $result)
		{
			return CardzNetDBaseClass::GetUserName($result->groupUserId);
		}	
								
		function GetMainRowsDefinition()
		{
			$columnDefs = array(
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Group Name',    CardzNetLibTableClass::TABLEPARAM_ID => 'groupName',    CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Group Manager', CardzNetLibTableClass::TABLEPARAM_ID => 'groupUserId',  CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, self::TABLEPARAM_DECODE => 'FormatUserName'),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'No of Members', CardzNetLibTableClass::TABLEPARAM_ID => 'noOfMembers',  CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
			);
			
			if (current_user_can(CARDZNET_CAPABILITY_MANAGER))
			{
				$buttonOptions = array(
					array(CardzNetLibTableClass::TABLEPARAM_LABEL => '&nbsp;', CardzNetLibTableClass::TABLEPARAM_ID => 'groupId',  CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_FUNCTION, CardzNetLibTableClass::TABLEPARAM_FUNC => 'AddGroupButtons')
				);
				$columnDefs = self::MergeSettings($columnDefs,$buttonOptions);	
			}
					
			return $columnDefs;
		}		

		function GetTableRowCount()
		{
			$userId = current_user_can(CARDZNET_CAPABILITY_ADMINUSER) ? 0 : get_current_user_id();
			return $this->myDBaseObj->GetGroupsCount($userId);		
		}		

		function GetTableData(&$results, $rowFilter)
		{
			$sqlFilters['sqlLimit'] = $this->GetLimitSQL();
/*
			if ($rowFilter != '')
			{
				$sqlFilters['whereSQL'] = $this->GetFilterSQL($rowFilter);
			}
*/
			// Get list of sales (one row per sale)
			$userId = current_user_can(CARDZNET_CAPABILITY_ADMINUSER) ? 0 : get_current_user_id();
			$results = $this->myDBaseObj->GetGroups($userId, $sqlFilters);
		}

		function GetDetailsRowsDefinition()
		{
			$ourOptions = array(
				array(CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_FUNCTION, CardzNetLibTableClass::TABLEPARAM_FUNC => 'ShowGroupDetails'),
			);

			$rowDefs = self::MergeSettings(parent::GetDetailsRowsDefinition(), $ourOptions);

			return $rowDefs;
		}
		
		function AddGroupButtons($result)
		{
			$buttonHTML = '';		
			
			if (current_user_can('manage_options'))
				$buttonHTML .=  $this->myDBaseObj->ActionButtonHTML('Add Existing User', $this->caller, CARDZNET_DOMAIN_NAME, '', $this->GetRecordID($result), 'adduser'); 
			$buttonHTML .=  $this->myDBaseObj->ActionButtonHTML('Invite New Member', $this->caller, CARDZNET_DOMAIN_NAME, '', $this->GetRecordID($result), 'addmember'); 
				
			return $buttonHTML;
		}
		
		function CreateGroupAdminDetailsListObject($env, $editMode = false, $groupResults)
		{
			return new CardzNetGroupsAdminDetailsListClass($env, $editMode, $groupResults);	
		}
		
	}
}


if (!class_exists('CardzNetGroupsAdminDetailsListClass')) 
{
	class CardzNetGroupsAdminDetailsListClass extends CardzNetLibAdminDetailsListClass // Define class
	{		
		function __construct($env, $editMode, $groupResults) //constructor
		{
			// Call base constructor
			parent::__construct($env, $editMode);
			
			$this->SetRowsPerPage(self::CARDZNETLIB_EVENTS_UNPAGED);
			
			$this->HeadersPosn = CardzNetLibTableClass::HEADERPOSN_TOP;
		}
		
		function GetTableID($result)
		{
			return "cardznet-groups-list-tab";
		}
		
		function GetRecordID($result)
		{
			if (!isset($result->groupId))
			{
				$filename = basename(__FILE__);
				$fileline = __LINE__;
				echo "groupId not defined at line $fileline in $filename <br>\n";
				//debug_print_backtrace();
				CardzNetLibUtilsClass::print_r($result, '$result');
				die;
			}		
				
			return $result->groupId;
		}
		
		function GetDetailID($result)
		{
			return '_'.$result->memberUserId;
		}
		
		function AddDeleteMemberButton($result)
		{
			if ($result->memberUserId == 0) return '';
			
			$gidParam  = 'gid='.$result->groupId;
			$html = $this->myDBaseObj->ActionButtonHTML(__('Remove', CARDZNET_DOMAIN_NAME), $this->caller, CARDZNET_DOMAIN_NAME, '', $result->memberId, 'removemember', $gidParam);    				
			return $html;
		}
		
		function GetMainRowsDefinition()
		{
			$rtnVal = array(
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Name',    CardzNetLibTableClass::TABLEPARAM_ID => 'memberName',   CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'EMail',   CardzNetLibTableClass::TABLEPARAM_ID => 'memberEMail',  CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),
				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Status',  CardzNetLibTableClass::TABLEPARAM_ID => 'memberStatus', CardzNetLibTableClass::TABLEPARAM_TYPE => CardzNetLibTableClass::TABLEENTRY_VIEW, ),

				array(CardzNetLibTableClass::TABLEPARAM_LABEL => 'Action',  CardzNetLibTableClass::TABLEPARAM_ID => 'memberId',     self::TABLEPARAM_TYPE => self::TABLEENTRY_FUNCTION, self::TABLEPARAM_FUNC => 'AddDeleteMemberButton'),
			);
			
			return $rtnVal;
		}
		
		function IsRowInView($result, $rowFilter)
		{
			return true;
		}		
				
	}
}
	
if (!class_exists('CardzNetGroupsAdminClass')) 
{
	// --- Define Class: CardzNetGroupsAdminClass
	class CardzNetGroupsAdminClass extends CardzNetLibAdminClass // Define class
	{		
		var $results;
		var $showOptionsID = 0;
		
		function __construct($env)
		{
			$this->pageTitle = __('Groups', CARDZNET_DOMAIN_NAME);
			
			parent::__construct($env, true);
		}
		
		function ProcessActionButtons()
		{
			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;				
				
			if (!current_user_can(CARDZNET_CAPABILITY_MANAGER)) 
			{
				die("User must have ".CARDZNET_CAPABILITY_MANAGER." Permission");
				return;
			}
			
			if (CardzNetLibUtilsClass::IsElementSet('post', 'addGroupRequest'))
			{
				// Check that the referer is OK
				$this->CheckAdminReferer();		

				// Add Group to Database - Show group setup page
				$html = $this->GetGroupDetailsForm();
				echo $html;
				
				$this->donePage = true;
			}
			else if (CardzNetLibUtilsClass::IsElementSet('post', 'addGroupDetails'))
			{
				$groupName = CardzNetLibUtilsClass::GetHTTPAlphaNumericElem('post', 'groupName');
				
				$groupId = 0;
				if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
					$groupId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'userId');
				
				// Check if this group already exists				
				if ($myDBaseObj->GroupExists($groupName))
				{
					echo $this->myDBaseObj->BannerMsg(__("Group Name Already Exists", CARDZNET_DOMAIN_NAME), 'error');
					return;
				}

				$this->myDBaseObj->AddGroup($groupName, $groupId);

				echo $this->myDBaseObj->BannerMsg(__("Group Added", CARDZNET_DOMAIN_NAME), 'updated');
			}
			else if (CardzNetLibUtilsClass::IsElementSet('get', 'action'))
			{
				// Check that the referer is OK
				$this->CheckAdminReferer();		

				switch (CardzNetLibUtilsClass::GetHTTPTextElem('get', 'action'))
				{
					case 'addmember':
						$sentInvite = false;
						if (CardzNetLibUtilsClass::IsElementSet('post', 'sendInvitationDetails'))
						{
							// Check that user is admin or group owner
							if (!current_user_can('manage_options')) 
							{
								$groupId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'id');
								$groupDef = $myDBaseObj->GetGroupById($groupId);
								$user = wp_get_current_user();
								if ((count($groupDef) == 0) || ($groupDef[0]->groupUserId != $user->ID))
								{
									echo $myDBaseObj->BannerMsg(__("You are not the group administrator", CARDZNET_DOMAIN_NAME), 'error');
									return;
								}
							}
							
							// NOTE: Could Check that new memeber is not already a memeber of this group
							
							// Callback from submitted member details
							$sentInvite = $this->SendMemberInvitation();
						}
						
						if (!$sentInvite)
						{
							// Add Group to Database - Show group setup page
							$html = $this->GetMemberDetailsForm();
							echo $html;
							$this->donePage = true;
						}
						break;
					
					case 'adduser':
						if (!CardzNetLibUtilsClass::IsElementSet('post', 'adduserId'))
						{
							// Add Group to Database - Show group setup page
							$html = $this->GetAddUserForm();
							echo $html;
							$this->donePage = true;
						}
						else
						{
							// Callback from submitted member details
							$userId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'user');
							$groupId = CardzNetLibUtilsClass::GetHTTPInteger('get', 'id');
							if (($userId == 0) || ($groupId == 0)) die;
							
							// Add the user to the Group as an unverified member
							$myDBaseObj->AddMemberToGroup($groupId, $userId);
							
							echo $myDBaseObj->BannerMsg(__("User added to group", CARDZNET_DOMAIN_NAME), 'updated');
						}						
						break;
					
					case 'removemember':
						if (!CardzNetLibUtilsClass::IsElementSet('get', 'gid')) die("gid missing");
						if (!CardzNetLibUtilsClass::IsElementSet('get', 'id')) die("id missing");
						
						$groupId = CardzNetLibUtilsClass::GetHTTPInteger('get', 'gid');
						$memberId = CardzNetLibUtilsClass::GetHTTPInteger('get', 'id');
						
						$myDBaseObj->RemoveMemberFromGroup($groupId, $memberId);
						
						echo $this->myDBaseObj->BannerMsg(__("Member Removed", CARDZNET_DOMAIN_NAME), 'updated');
						
						break;
					
					default:
						die("Invalid Action");
				}
			}
			else if (CardzNetLibUtilsClass::IsElementSet('post', 'savechanges'))
			{
			}			
			else if (CardzNetLibUtilsClass::IsElementSet('get', 'action'))
			{
				$this->CheckAdminReferer();
				$this->DoActions();
			}

		}
		
		function GetGroupDetailsForm($addHtml = '')
		{
			$addGroupText = __('Add Group', CARDZNET_DOMAIN_NAME);
			$groupNameText = __('Group Name', CARDZNET_DOMAIN_NAME);
			$groupManagerText = __('Group Manager', CARDZNET_DOMAIN_NAME);
			
			$name = $this->myDBaseObj->GetDefaultGroupName();
			
			$html  = "<form method=post>\n";
			$html .= "<table>\n";
			
			$html .= "<tr class='addgroup_row_group'>\n";
			$html .= "<td class='groupcell'>$groupNameText</td>";
			$html .= "<td class='groupcell'><input id=groupName name=groupName value='$name'></td>";
			$html .= "</tr>\n";
						
			$html .= "<tr class='addgroup_row_login'>\n";
			$html .= "<td class='groupcell'>$groupManagerText</td>";
			$html .= "<td class='groupcell'>";
			
			$userName = CardzNetDBaseClass::GetCurrentUserName();			
			$user = wp_get_current_user();
			if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				$html .= CardzNetDBaseClass::GetUserSelector('', $userName, CARDZNET_CAPABILITY_MANAGER);
			}
			else
			{
				// Just Show the current user name
				$html .= "<span>$userName</span>";
			}
			$html .= "</td></tr>\n";
			
			if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
				$html .= "<tr class='addgroup_row_submit'><td colspan=3><input <input class='button-secondary' type='submit' name=addGroupDetails value='$addGroupText'></td></tr>\n";

			$html .= "</table>\n";
			$html .= "</form>\n";
			
			echo $html;
		}
		
		function GetAddUserForm()
		{
			if (!current_user_can('manage_options'))
			{
				return __("Permission to add User Denied", CARDZNET_DOMAIN_NAME);
			}
			
			$myDBaseObj = $this->myDBaseObj;
			
			$groupId = CardzNetLibUtilsClass::GetHTTPInteger('get', 'id');
						
			$groupDef = $myDBaseObj->GetGroupById($groupId);
			if (count($groupDef) == 0)
			{
				echo $myDBaseObj->BannerMsg(__("Invalid Group", CARDZNET_DOMAIN_NAME), 'error');
				return;
			}
			$excludesList = array($groupDef[0]->groupUserId);
			
			$addUserText = __('Add User', CARDZNET_DOMAIN_NAME);
			$memberEMailAddressText = __('User Login', CARDZNET_DOMAIN_NAME);
			
			// Exclude existing group members
			$membersDefs = $myDBaseObj->GetMembersById($groupId);
			foreach ($membersDefs as $memberDef)
			{
				$excludesList[] = $memberDef->memberUserId;
			}
			
			$all_users = get_users();
			$includesList = array();

			foreach($all_users as $user)
			{
			    if (!$user->has_cap(CARDZNET_CAPABILITY_PLAYER))
			    	continue;
			    	
			    if (in_array($user->ID, $excludesList))
			    	continue;
			    	
		        $includesList[] = $user->ID;
			}

			if (count($includesList) == 0)
			{
				$text = __("No users available to add", CARDZNET_DOMAIN_NAME);
				$linkText = __("Go back", CARDZNET_DOMAIN_NAME);
				CardzNetDBaseClass::GoToPageLink($text, $linkText, CARDZNET_MENUPAGE_GROUPS);
				return;
			}
			
			$html  = "<form method=post>\n";
			$html .= "<table>\n";
			
			$html .= "<tr class='addmember_row_member'>\n";
			$html .= "<td class='membercell'>$memberEMailAddressText</td>";
			$html .= "<td class='membercell'>";
			$html .= wp_dropdown_users(array(
			    'echo' => false,
			    'show' => '',
			    'include' => $includesList,
				));
				
			$html .= "</td>";
			$html .= "</tr>\n";
						
			$html .= "<tr class='addmember_row_submit'><td colspan=3><input <input class='button-secondary' type='submit' name=adduserId value='$addUserText'></td></tr>\n";

			$html .= "</table>\n";
			$html .= "</form>\n";
			
			echo $html;
		}

		function GetMemberDetailsForm()
		{
			if (!current_user_can(CARDZNET_CAPABILITY_MANAGER))
			{
				return __("Permission to add Member Denied", CARDZNET_DOMAIN_NAME);
			}
			
			$sendInvitationText = __('Send Invitation', CARDZNET_DOMAIN_NAME);
			$memberFirstNameText = __('First Name', CARDZNET_DOMAIN_NAME);
			$memberLastNameText = __('Last Name', CARDZNET_DOMAIN_NAME);
			$memberEMailAddressText = __('EMail Address', CARDZNET_DOMAIN_NAME);
			
			$memberFirstName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'memberFirstName');
			$memberLastName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'memberLastName');
			$memberEMail = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'memberEMail');

			$html  = "<form method=post>\n";
			$html .= "<table>\n";
			
			$html .= "<tr class='addmember_row_member'>\n";
			$html .= "<td class='membercell'>$memberFirstNameText</td>";
			$html .= "<td class='membercell'><input type=text autocomplete=off size=20 id=memberFirstName name=memberFirstName value='$memberFirstName'></td>";
			$html .= "</tr>\n";
						
			$html .= "<tr class='addmember_row_member'>\n";
			$html .= "<td class='membercell'>$memberLastNameText</td>";
			$html .= "<td class='membercell'><input type=text autocomplete=off size=20 id=memberLastName name=memberLastName value='$memberLastName'></td>";
			$html .= "</tr>\n";
						
			$html .= "<tr class='addmember_row_member'>\n";
			$html .= "<td class='membercell'>$memberEMailAddressText</td>";
			$html .= "<td class='membercell'><input type=text autocomplete=off size=80 id=memberEMail name=memberEMail value='$memberEMail'></td>";
			$html .= "</tr>\n";
						
			$html .= "<tr class='addmember_row_submit'><td colspan=3><input <input class='button-secondary' type='submit' name=sendInvitationDetails value='$sendInvitationText'></td></tr>\n";

			$html .= "</table>\n";
			$html .= "</form>\n";
			
			echo $html;
		}
		
		function SendMemberInvitation()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$groupId = CardzNetLibUtilsClass::GetHTTPInteger('get', 'id');
			$memberFirstName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'memberFirstName');
			$memberLastName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'memberLastName');
			$memberEMail = CardzNetLibUtilsClass::GetHTTPEMail('post', 'memberEMail');
			
			$groupDef = $myDBaseObj->GetGroupById($groupId);
			if (count($groupDef) == 0)
			{
				echo $myDBaseObj->BannerMsg(__("Invalid Group", CARDZNET_DOMAIN_NAME), 'error');
				return false;
			}
			
			// Validate User ....
			if ((strlen($memberFirstName) < 1) || (strlen($memberLastName) < 1))
			{
				echo $myDBaseObj->BannerMsg(__("Invalid Name", CARDZNET_DOMAIN_NAME), 'error');
				return false;
			}
			
			if (!is_email($memberEMail))
			{
				echo $myDBaseObj->BannerMsg(__("Invalid EMail", CARDZNET_DOMAIN_NAME), 'error');
				return false;
			}

    		$userdata = get_user_by('email', $memberEMail);
			if ($userdata)
			{
				$membersList = $myDBaseObj->GetMembersById($groupId, $userdata->ID);
				if (count($membersList) > 0)
				{
					echo $myDBaseObj->BannerMsg(__("Member is already in this group", CARDZNET_DOMAIN_NAME), 'error');
					return false;
				}
			}

			// Add the user details to the Invitations DB Table
			$inviteId = $myDBaseObj->AddInvitation($memberFirstName, $memberLastName, $memberEMail, $groupId);
			
			// Send and email to the user (and optionally to the admin)
			$inviteRecords = $myDBaseObj->GetInvitationById($inviteId);

			$cbURL  = get_option('siteurl');
			$cbURL .= '?'.CARDZNET_CALLBACK_ID."=$groupId";
			$cbURL .= '&action=accept';
			$cbURL .= '&auth='.$inviteRecords[0]->inviteHash;
			
			//$inviteRecords[0]->url = 'TBD_'.CARDZNET_CALLBACK_ID;
			$inviteRecords[0]->inviteURL = $cbURL;

			$myDBaseObj->SendEMailByTemplateID($inviteRecords, 'inviteEMail', 'emails', $memberEMail);
			
			echo $myDBaseObj->BannerMsg(__("Invitation Sent", CARDZNET_DOMAIN_NAME), 'updated');
			return true;
		}
		
		function Output_MainPage($updateFailed)
		{
			if (isset($this->pageHTML))	
			{
				echo $this->pageHTML;
				return;
			}

			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;				
			
			if (!$myDBaseObj->SettingsOK())
				return;
				
			$actionURL = remove_query_arg('action');
			$actionURL = remove_query_arg('id', $actionURL);
			
			// HTML Output - Start 
			$formClass = CARDZNET_DOMAIN_NAME.'-admin-form '.CARDZNET_DOMAIN_NAME.'-groups-editor';
			echo '
				<div class="'.$formClass.'">
				<form method="post" action="'.$actionURL.'">
				';

			if (isset($this->saleId))
				echo "\n".'<input type="hidden" name="saleID" value="'.$this->saleId.'"/>'."\n";
				
			$this->WPNonceField();
				 
			$noOfGroups = $this->OutputGroupsList($this->env);
			if ($noOfGroups == 0)
			{
				echo "<div class='noconfig'>".__('No Groups', CARDZNET_DOMAIN_NAME)."</div>\n";
			}
			else 
			{
			}

			if (current_user_can(CARDZNET_CAPABILITY_ADMINUSER))
			{
				$this->OutputButton("addGroupRequest", __("Add Group", CARDZNET_DOMAIN_NAME));
			}
			
			if ($noOfGroups > 0)
			{
				//$this->OutputButton("savechanges", __("Save Changes", CARDZNET_DOMAIN_NAME), "button-primary");
			}

?>
	<br></br>
	</form>
	</div>
<?php
		} // End of function Output_MainPage()


		function OutputGroupsList($env)
		{
			$myPluginObj = $this->myPluginObj;

			$classId = $myPluginObj->adminClassPrefix.'GroupsAdminListClass';
			$groupsListObj = new $classId($env);
			$groupsListObj->showOptionsID = $this->showOptionsID;
			return $groupsListObj->OutputList($this->results);		
		}
				
		function DoActions()
		{
			$rtnVal = false;
			$myDBaseObj = $this->myDBaseObj;

			switch (CardzNetLibUtilsClass::GetHTTPTextElem('get', 'action'))
			{
				default:
					$rtnVal = false;
					break;
					
			}
				
			return $rtnVal;
		}

		function DoBulkPreAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Reset error count etc. on first pass
			if (!isset($this->errorCount)) $this->errorCount = 0;
			
			$results = $myDBaseObj->GetGroupById($recordId);
			
			switch ($bulkAction)
			{
				case CardzNetLibAdminListClass::BULKACTION_DELETE:
					// FUNCTIONALITY: Groups - Bulk Action Delete			
					if (count($results) == 0)
						$this->errorCount++;
					return ($this->errorCount > 0);
			}
			
			return false;
		}
		
		function DoBulkAction($bulkAction, $recordId)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$listClassId = $this->myPluginObj->adminClassPrefix.'GroupsAdminListClass';
			
			switch ($bulkAction)
			{
				case CardzNetLibAdminListClass::BULKACTION_DELETE:		
					$myDBaseObj->DeleteGroup($recordId);
					return true;
			}
				
			return parent::DoBulkAction($bulkAction, $recordId);
		}
		
		function GetBulkActionMsg($bulkAction, $actionCount)
		{
			$actionMsg = '';
			
			$listClassId = $this->myPluginObj->adminClassPrefix.'GroupsAdminListClass';
			
			switch ($bulkAction)
			{
				case CardzNetLibAdminListClass::BULKACTION_DELETE:	
					if ($this->errorCount > 0)
						$actionMsg = $this->errorCount . ' ' . _n("Group does not exist in Database", "Groups do not exist in Database", $this->errorCount, CARDZNET_DOMAIN_NAME);
					else if ($actionCount > 0)
						$actionMsg = $actionCount . ' ' . _n("Group has been deleted", "Groups have been deleted", $actionCount, CARDZNET_DOMAIN_NAME);
					else
						$actionMsg =  __("Nothing to Delete", CARDZNET_DOMAIN_NAME);
					break;
					
				default:
					$actionMsg = parent::GetBulkActionMsg($bulkAction, $actionCount);

			}
			
			return $actionMsg;
		}
		
	}

}

?>