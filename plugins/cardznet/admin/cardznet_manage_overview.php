<?php
/* 
Description: Code for CardzNet Overview Page
 
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

if (!class_exists('CardzNetOverviewAdminClass')) 
{
	class CardzNetOverviewAdminClass extends CardzNetLibAdminClass
	{		
		function __construct($env)
		{
			$this->pageTitle = 'Overview';
			
			// Call base constructor
			parent::__construct($env);
		}
		
		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{
			$myDBaseObj = $this->myDBaseObj;

			if (!$myDBaseObj->SettingsOK())
				return;
				
			// CardzNet Overview HTML Output - Start 
			$this->Output_Overview();
			//$this->Output_Help();
			$this->Output_TrolleyAndShortcodesHelp();
		}
		
		function Output_Overview()
		{
			$myDBaseObj = $this->myDBaseObj;

			// CardzNet Overview HTML Output - Start 
?>
<div class="wrap">
	<div id="icon-cardznet" class="icon32"></div>
	<br></br>
	<form method="post" action="admin.php?page=cardznet_settings">
		<table class="widefat" cellspacing="0">
			<tbody>
				<tr>
					<td>No Of Users</td>
					<td><?php echo $myDBaseObj->GetUsersCount(); ?></td>
				</tr>
			</tbody>
		</table>
<br></br>
<?php
if(false)
{
	echo '<input class="button-primary" type="submit" name="createsample" value="'.__('Create Sample', CARDZNET_DOMAIN_NAME).'"/>';
}
?>
    </form>

<?php
        	// CardzNet Overview HTML Output - End
		}
		
		function Output_TrolleyAndShortcodesHelp()
		{
			echo '<h2>'.__("Plugin Info", $this->myDomain)."</h2>\n";		
			$this->myDBaseObj->Output_PluginHelp();
			
			if (!current_user_can(CARDZNET_CAPABILITY_SETUPUSER)) return;
			
			echo '<h2>'.__("Shortcodes", $this->myDomain)."</h2>\n";			
			echo '<br>'.__('CardzNet generates output to your Wordpress pages for the following shortcodes:', $this->myDomain)."<br><br>\n";
			$this->Output_ShortcodeHelp(CARDZNET_SHORTCODE);
		}
		
		function Output_ShortcodeHelp($shortcode)
		{
			// FUNCTIONALITY: Overview - Show Plugin Specific Help for Shortcode(s))
?>
			<div class="cardznet-overview-info">
			<table class="widefat" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Shortcode', $this->myDomain); ?></th>
						<th><?php _e('Description', $this->myDomain); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>[<?php echo $shortcode; ?>]</td>
						<td><?php _e('Outputs card table', $this->myDomain); ?></td>
					</tr>
					<tr>
						<td>[<?php echo $shortcode.'-login'; ?>]</td>
						<td><?php _e('Outputs remote login form', $this->myDomain); ?></td>
					</tr>
				</tbody>
			</table>
			</div>
			<?php
		}	

	}
}
?>