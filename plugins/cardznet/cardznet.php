<?php
/* 
Plugin Name: CardzNet
Plugin URI: http://www.corondeck.co.uk/
Version: 1.0.2
Author: Malcolm Shergold
Author URI: http://www.corondeck.co.uk
Description: Internet Connected Multiplayer Card Game 
 
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

if(!isset($_SESSION)) 
{
	// MJS - SC Mod - Register to use SESSIONS
	session_start();
}	

define('CARDZNET_PLUGIN_FILE', __FILE__);

include 'cardznet_defs.php';
if (!class_exists('CardzNetDBaseClass')) 
	include CARDZNET_INCLUDE_PATH.'cardznet_dbase_api.php';

if (!class_exists('CardzNetPluginClass')) 
{
	class CardzNetPluginClass
	{
		var $myDBaseObj;
		
		function __construct($pluginFile, $myDBaseObj) 
		{
			$this->myDBaseObj = $myDBaseObj;
			
			//parent::__construct($pluginFile);
			
			if (!isset($this->pluginDesc))
				$this->pluginDesc = CARDZNET_PLUGINDESC;
				
			if (!isset($this->myDomain))
				$this->myDomain = basename(dirname(__FILE__));
				
			$this->adminPagePrefix = basename(dirname($pluginFile));
			
			// Init options & tables during activation & deregister init option
			register_activation_hook( $pluginFile, array(&$this, 'activate') );
			register_deactivation_hook( $pluginFile, array(&$this, 'deactivate') );	
			
			$this->adminClassFilePrefix = 'cardznet';
			$this->adminClassPrefix = 'CardzNet';
			
			$this->env = array(
			    'Caller' => $pluginFile,
			    'PluginObj' => $this,
			    'DBaseObj' => $this->myDBaseObj,
			    'Domain' => $this->myDomain,
			);

			$this->GetCardzNetOptions();
			
			//Actions
			add_action('admin_menu', array(&$this, 'CardzNet_ap'));
			
			add_action('wp_print_styles', array(&$this, 'load_user_styles') );
			add_action('wp_print_scripts', array(&$this, 'load_user_scripts') );
			
			//add_action('admin_print_styles', array(&$this, 'load_admin_styles') );
			add_action('admin_enqueue_scripts', array(&$this, 'load_admin_styles') );

			// Add action for processing callbacks
			//add_action('wp_loaded', array(&$this, 'OnWPLoaded'));
			add_filter('the_content', array(&$this, 'OnPageLoad'), 10, 1);
						
			// Add actions for AJAX handlers
			add_action("wp_ajax_cardznet_ajax_request" , array(&$this, 'cardznet_ajax_call') );
			add_action("wp_ajax_nopriv_cardznet_ajax_request" , array(&$this, 'cardznet_ajax_call') );

			add_shortcode(CARDZNET_SHORTCODE, array(&$this, 'cardznet_wp_shortcode'));
			add_shortcode('cardznet', array(&$this, 'cardznet_wp_shortcode'));
			
			if ($myDBaseObj->checkVersion())
			{
				// Versions are different ... call activate() to do any updates
				$this->activate();
			}			
		}
		
		//Returns an array of admin options
		function GetCardzNetOptions() 
		{
			$myDBaseObj = $this->myDBaseObj;
			return $myDBaseObj->adminOptions;
		}
    
		// Saves the admin options to the options data table
		function SaveCardzNetOptions() 
		{
			$myDBaseObj = $this->myDBaseObj;
			$myDBaseObj->saveOptions();
		}
    
	    // ----------------------------------------------------------------------
	    // Activation / Deactivation Functions
	    // ----------------------------------------------------------------------
	    
	    function activate() 
		{
			$myDBaseObj = $this->myDBaseObj;
			$this->SaveCardzNetOptions();
      
			$myDBaseObj->upgradeDB();
		}

	    function deactivate()
	    {
	    }

		function load_user_styles() 
		{
			//Add Style Sheet
			$this->myDBaseObj->enqueue_style(CARDZNET_CODE_PREFIX.'-user', CARDZNET_URL.'css/cardznet.css'); // CardzNet core style
		}
		
		function load_user_scripts()
		{
			$myDBaseObj = $this->myDBaseObj;

			$reloadParam = false;
			if (defined('CARDZNETLIB_JS_NOCACHE')) $reloadParam = time();
			
			// Add our own Javascript
			$myDBaseObj->enqueue_script( 'cardznet-lib', plugins_url( 'js/cardznet.js', __FILE__));
			
			wp_enqueue_script('jquery');
		}	
		
		function load_admin_styles()
		{
			$myDBaseObj = $this->myDBaseObj;
			
			// Add our own style sheet
			$myDBaseObj->enqueue_style( CARDZNET_CODE_PREFIX.'-admin', plugins_url( 'admin/css/cardznet-admin.css', __FILE__ ));
			
			// Add our own Javascript
			$myDBaseObj->enqueue_script( 'cardznetlib_admin', plugins_url( 'admin/js/cardznetlib_admin.js', __FILE__));
		}

		function adminClass($env, $classId, $fileName)
		{
			$fileName = $env['PluginObj']->adminClassFilePrefix.'_'.$fileName.'.php';
			include 'admin/'.$fileName;
			
			$classId = $env['PluginObj']->adminClassPrefix.$classId;
			return new $classId($env);
		}
		
		function printAdminPage() 
		{
			$this->adminPageActive = true;

			$myDBaseObj = $this->myDBaseObj;
			$env = $this->env;
					
			//Prints out an admin page
			$pagePrefix = $this->adminPagePrefix;			
			$pageSubTitle = CardzNetLibUtilsClass::GetHTTPAlphaNumericElem('get', 'page');			
      		switch ($pageSubTitle)
      		{
				case CARDZNET_MENUPAGE_SETTINGS :
					$this->adminClass($env, 'SettingsAdminClass', 'manage_settings');
					break;
					
				case CARDZNET_MENUPAGE_GROUPS :
					$this->adminClass($env, 'GroupsAdminClass', 'manage_groups');
					break;
					
				case CARDZNET_MENUPAGE_GAMES :
					$this->adminClass($env, 'GamesAdminClass', 'manage_games');
					break;
					
				case CARDZNET_MENUPAGE_DEVTEST :
					include CARDZNET_TEST_PATH.'cardznetlib_devtestcaller.php';   
					new CardzNetLibDevCallerClass($this->env, 'CardzNet');
					break;
							
				case CARDZNET_MENUPAGE_DIAGNOSTICS :
					$this->adminClass($env, 'DebugAdminClass', 'debug');
					break;		
										
				case CARDZNET_MENUPAGE_OVERVIEW:
				case CARDZNET_MENUPAGE_ADMINMENU:
				default :
					$this->adminClass($env, 'OverviewAdminClass', 'manage_overview');
					break;
			}
		}
		
		function GetFirstCapability($capsList) 
		{
			$firstCap = '';
			
			foreach ($capsList as $cap)
			{
				if (current_user_can($cap))
				{
					$firstCap = $cap;
					break;
				}
			}		
			
			return $firstCap;			
		}
		
		function CardzNet_ap() 
		{
			if (function_exists('add_menu_page'))
			{
				$cardznet_caps = array(
					CARDZNET_CAPABILITY_DEVUSER,
					CARDZNET_CAPABILITY_SETUPUSER,
					CARDZNET_CAPABILITY_ADMINUSER,
					CARDZNET_CAPABILITY_MANAGER,
					);
				
				$adminCap = $this->GetFirstCapability($cardznet_caps);

				if ($adminCap == '') return;
				
				$icon_url = '';
				$pagePrefix = $this->adminPagePrefix;
				$pluginName = 'CardzNet';
				
				if ($adminCap != '')
				{
					add_menu_page($pluginName, $pluginName, $adminCap, CARDZNET_MENUPAGE_ADMINMENU, array(&$this, 'printAdminPage'), $icon_url);
						
					add_submenu_page( CARDZNET_MENUPAGE_ADMINMENU, __($this->pluginDesc.' - Overview', $this->myDomain),   __('Overview', $this->myDomain),  $adminCap,                        CARDZNET_MENUPAGE_ADMINMENU,  array(&$this, 'printAdminPage'));
					add_submenu_page( CARDZNET_MENUPAGE_ADMINMENU, __($this->pluginDesc.' - Groups', $this->myDomain),     __('Groups', $this->myDomain),    $adminCap,                        CARDZNET_MENUPAGE_GROUPS,     array(&$this, 'printAdminPage'));
					add_submenu_page( CARDZNET_MENUPAGE_ADMINMENU, __($this->pluginDesc.' - Games', $this->myDomain),      __('Games', $this->myDomain),     $adminCap,                        CARDZNET_MENUPAGE_GAMES,      array(&$this, 'printAdminPage'));
					add_submenu_page( CARDZNET_MENUPAGE_ADMINMENU, __($this->pluginDesc.' - Settings', $this->myDomain),   __('Settings', $this->myDomain),  CARDZNET_CAPABILITY_SETUPUSER, CARDZNET_MENUPAGE_SETTINGS,   array(&$this, 'printAdminPage'));
				}
				
				// Show test menu if enabled
				if ($this->myDBaseObj->InTestMode() && current_user_can(CARDZNET_CAPABILITY_DEVUSER))
				{
					add_submenu_page( 'options-general.php', $pluginName.' Test', $pluginName.' Test', CARDZNET_CAPABILITY_DEVUSER, CARDZNET_MENUPAGE_DIAGNOSTICS, array(&$this, 'printAdminPage'));
				}
				
				if (current_user_can(CARDZNETLIB_CAPABILITY_SYSADMIN) || current_user_can(CARDZNETLIB_CAPABILITY_DEVUSER))
				{
					if ( CardzNetLibUtilsClass::IsElementSet('session', 'cardznetlib_debug_test') && file_exists(CARDZNET_TEST_PATH.'cardznetlib_devtestcaller.php') ) 
					{
						include CARDZNET_TEST_PATH.'cardznetlib_devtestcaller.php';   
						$devTestFiles = CardzNetLibDevCallerClass::DevTestFilesList(CARDZNET_TEST_PATH);
						if (count($devTestFiles) > 0)
							add_submenu_page( CARDZNET_MENUPAGE_ADMINMENU, __('Dev TESTING', $this->myDomain), __('Dev TESTING', $this->myDomain), CARDZNETLIB_CAPABILITY_DEVUSER, CARDZNET_MENUPAGE_DEVTEST, array(&$this, 'printAdminPage'));
					}

				}
			}
		}
				
		function cardznet_LoginPage()
		{
		
			// Set up some defaults.
			// Set 'echo' to 'false' because we want it to always return instead of print for shortcodes. 
			$args = array(
				'label_username' => 'Username',
				'label_password' => 'Password',
				'echo' => false,
			);

			$lostmsg = __('Lost Your Password?', $this->myDomain);
			$clickmsg = __('Click Here!', $this->myDomain);
			$lostPasswordURL = get_option('siteurl').'/wp_login.php?action=lostpassword';
			
			$content = '
<div class=cardznet_msgpage>	
<div class=cardznet-login-container>
<h1>CardzNet Login</h1>	
<div class=cardznet-login-frame>';
			
			$content .= wp_login_form($args);
			$content .= "<div id=login-lost name=login-lost>$lostmsg <a href=\"$lostPasswordURL\">$clickmsg</a>";
			$content .= "</div></div></div></div>";
			
			echo $content;
		}

		function AddAJAXCode() 
		{ 
			$enableAJAX = $this->myDBaseObj->isDbgOptionSet('Dev_DisableAJAX') ? 'false' : 'true';
			ob_start();
?>
<script type="text/javascript" >
var enableAJAX = <?php echo $enableAJAX; ?>;

function cardznet_CallAJAX(data, callbackfn, errorfn)
{
    ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ) ?>'; // get ajaxurl
	
	data['action'] = 'cardznet_ajax_request';

    jQuery.ajax(
    {
        url: ajaxurl, // this will point to admin-ajax.php
        type: 'POST',
        data: data,
        success: function (response) 
        {
            callbackfn(response);                
        },
        error: function(jqXHR, textStatus, errorThrown) 
        {
        	errorfn(textStatus);
            return data;
        },   
    });
}
</script> 
<?php
			$html = ob_get_contents();
			ob_end_clean();
			return $html;
		}

		function OutputWindowOnLoadHandler($startTicker = true)
		{
			echo "<script>\n";

			if ($startTicker)
			{
				$onLoadHandler = 'cardznet_OnLoadTabletop';
				
				$tickerTimeout = intval($this->myDBaseObj->getOption('RefreshTime'));
				$activeTimeout = intval($this->myDBaseObj->getOption('TimeoutTime'));
				
				echo "tickerTimeout = $tickerTimeout; \n";
				echo "activeTimeout = $activeTimeout; \n";

			}
			else
			{
				$onLoadHandler = 'cardznet_OnLoadResponse';				
			}

			echo "CardzNetLib_addWindowsLoadHandler($onLoadHandler); \n";
			echo "</script> \n";
		}
		
		function cardznet_frontend($atts)
		{
			ob_start();
			$myDBaseObj = $this->myDBaseObj;
			
			// Output JS code for windows load handler before any <form> element
			$this->OutputWindowOnLoadHandler();
						
			if ( !is_user_logged_in() )
			{
				$this->cardznet_LoginPage();
				$html = ob_get_contents();
				ob_end_clean();
				
				return $html;
			}
			
			// Get game in progress here ....
			$gameDef = $myDBaseObj->GetGameDef($atts);
			
			if ($gameDef == null)
			{
				// Error initialising ... cannot play!
				$html  = "<form>\n";
				$html  = "<div class='cardznet_msgpage'>\n";
				$html .= $this->myDBaseObj->BannerMsg(__('Not currently in a game!', $this->myDomain), CARDZNET_DOMAIN_MSG_ERROR);
				$html .= "</div>\n";
				$html .= "</form>\n";
				
				return $html;
			}

			$gameClass = $gameDef->class;
			include CARDZNET_GAMES_PATH.$gameDef->srcfile;
	
	  		// Output the card table
			$tabletopObj = new $gameClass($myDBaseObj, $atts);

			$tabletopObj->CSS_JS_and_Includes($gameDef);

			$tabletopObj->OutputCardTable();
			
			$html = ob_get_contents();
			ob_end_clean();
			
			if ($myDBaseObj->isDbgOptionSet('Dev_LogHTML'))
			{
				$myDBaseObj->AddToStampedCommsLog($html);
			}
			
			return $html;
		}

		function cardznet_wp_shortcode($atts)
		{
			//echo "<!-- ************************ Shortcode Entry ************************ -->\n";			
			$this->cardznet_ajax_getpostvars();

			$html  = $this->AddAJAXCode();
			$html .= $this->cardznet_frontend($atts);
			//echo "<!-- ************************ Shortcode Exit ************************ -->\n";			
			return $html;
		}

		function cardznet_ajax_getpostvars()
		{
			// Change keys of Post vars with AJAX-******* keys
			foreach (CardzNetLibUtilsClass::GetArrayKeys('post') as $postId)
			{
				$postIdParts = explode('-', $postId);
				$noOfParts = count($postIdParts);
				if (($noOfParts < 2) || ($postIdParts[0] != 'AJAX'))
					continue;

				$postId1 = $postIdParts[1];				
				if ($noOfParts == 2)
				{
					$postVal = CardzNetLibUtilsClass::GetHTTPTextElem('post', $postId);
					CardzNetLibUtilsClass::SetElement('post', $postId1, $postVal);
				}					
				else if ($noOfParts == 3)
				{
					// Post entries with keys of the form AJAX-{key1}-{key2}
					// Convert to an array in Post
					$postVal = CardzNetLibUtilsClass::GetHTTPTextElem('post', $postId);
					$postId2 = $postIdParts[2];				
					CardzNetLibUtilsClass::SetElement('post', array($postId1, $postId2), $postVal);
				}					
				else
				{
					continue;
				}
				CardzNetLibUtilsClass::UnsetElement('post', $postId);
			}
		}

		function OnPageLoad($content)
		{
		    if ( !in_the_loop() || !is_main_query() ) 
		    	 return $content;
			
			if (!CardzNetLibUtilsClass::IsElementSet('get', CARDZNET_CALLBACK_ID))
				return $content;
			
			if (CardzNetLibUtilsClass::GetHTTPTextElem('get', 'action') != 'accept')
				return $content;
				
			$auth = CardzNetLibUtilsClass::GetHTTPTextElem('get', 'auth');
			if ($auth === '') die;
			
			$content = $this->OutputWindowOnLoadHandler(false);
						
			$content .= "<div class='cardznet_msgpage'>\n";
			$content .= "<h1>CardzNet - ".__('Invitation Response', CARDZNET_DOMAIN_NAME)."</h1>\n";
			
			// Add the status of the message
			$content .= $this->AcceptInvitation($auth);
			
			$content .= "</div>\n";
			
			return $content;
		}

		function cardznet_ajax_call()
		{
			$request = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'request', '');			
			
			$this->cardznet_ajax_getpostvars();
			if (current_user_can(CARDZNETLIB_CAPABILITY_SYSADMIN) && CardzNetLibUtilsClass::IsElementSet('post', 'isSeqMode'))
				$this->myDBaseObj->isSeqMode = (CardzNetLibUtilsClass::GetHTTPTextElem('post', 'isSeqMode') == 'true');
				
			switch ($request)
			{
				case 'dealcards':					
				case 'cardznet':
					$playerName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'thisPlayerName', '(Unknown)');
					if ($request == 'dealcards')
					{
						CardzNetLibUtilsClass::SetElement('post', 'dealcards', true);
						$this->myDBaseObj->AddToStampedCommsLog("$playerName - AJAX Deal Cards Request");
					}
					else if (CardzNetLibUtilsClass::IsElementSet('post', 'cardNo'))
					{
						$cardNo = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'cardNo');
						$this->myDBaseObj->AddToStampedCommsLog("$playerName - AJAX Play Card ($cardNo)");
					}
					else
					{
						$this->myDBaseObj->AddToStampedCommsLog("$playerName - AJAX Update Request ($request)");
					}
					$atts = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'atts');
					$atts['stripFormElems'] = true;					
					$response['html'] = $this->cardznet_frontend($atts, true);
				    break;
				    
				case 'ticker':
					$playerName = CardzNetLibUtilsClass::GetHTTPTextElem('post', 'thisPlayerName', '(Unknown)');
					$gameTicker = CardzNetLibUtilsClass::GetHTTPInteger('post', 'gameTicker');
					$gameId = CardzNetLibUtilsClass::GetHTTPInteger('post', 'gameId');
					$replyTicker = $this->myDBaseObj->GetTicker($gameId);
					$response['ticker'] = $replyTicker;
					$this->myDBaseObj->AddToStampedCommsLog("$playerName - AJAX Ticker Request Rxd - GameId: $gameId - Req Ticker: $gameTicker - Response: $replyTicker ");
					break;
					
				default:
					$response = 'Unknown AJAX Request';
					break;
			}

		    echo json_encode($response);
		    wp_die();
		}

		function AcceptInvitation($auth)
		{
			$myDBaseObj = $this->myDBaseObj;
			
			$rtnHTML = '';
			
			$inviteRecords = $myDBaseObj->GetInvitationByAuth($auth);
			if (count($inviteRecords) != 1)
			{
				$rtnHTML .= $this->myDBaseObj->BannerMsg(__("Invalid Request", CARDZNET_DOMAIN_NAME), CARDZNET_DOMAIN_MSG_ERROR);
				return $rtnHTML;
			}

			$inviteRecord = $inviteRecords[0];
				
			// Check if user already exists
			$existingUser = false;
			$user = get_user_by('email', $inviteRecord->inviteEMail);
			if ($user != null)
			{
				$userId = $user->ID;
				$existingUser = true;
				
				$myDBaseObj->SendEMailByTemplateID($inviteRecords, 'addedToGroupEMail', 'emails', $inviteRecord->inviteEMail);
				
				$rtnHTML .= $this->myDBaseObj->BannerMsg(__("Invitation Accepted - Check your EMail for details", CARDZNET_DOMAIN_NAME), CARDZNET_DOMAIN_MSG_OK);
			}
			else
			{
				// Find a Username
				$inviteRecord->username = $basename = $inviteRecord->inviteFirstName.$inviteRecord->inviteLastName;
				for ($i=2;;$i++)
				{
					if (!username_exists($inviteRecord->username)) break;
					$inviteRecord->username = $basename.$i;
				}
				
				$inviteRecord->password = wp_generate_password( 12, true );
				
				$userdata['user_login'] = $inviteRecord->username;
				$userdata['user_pass'] = $inviteRecord->password;
				$userdata['first_name'] = $inviteRecord->inviteFirstName;
				$userdata['last_name'] = $inviteRecord->inviteLastName;
				$userdata['user_email'] = $inviteRecord->inviteEMail;
				
				// Add the User to Wordpress
				$userId = wp_insert_user($userdata);
				
				$inviteRecord->siteName = get_bloginfo('name');
				$inviteRecord->loginURL = get_bloginfo('url').'/wp-admin/';
				
				// Email the user
				$myDBaseObj->SendEMailByTemplateID($inviteRecords, 'addedLoginEMail', 'emails', $inviteRecord->inviteEMail);
			
				$rtnHTML .= $this->myDBaseObj->BannerMsg(__("Invitation Accepted - Check your EMail for details", CARDZNET_DOMAIN_NAME), CARDZNET_DOMAIN_MSG_OK);
/*
				$message  = "Login created on {$inviteRecord->siteName} <br> \n";
				$message .= "User Name:{$inviteRecord->username} <br> \n";
				//$message .= "Password:{$inviteRecord->password} <br> \n";
				$message .= "Login URL:<a href=\"{$inviteRecord->loginURL}\">{$inviteRecord->loginURL}</a> <br> \n";
								
				$rtnHTML .= $message;			
*/
			}						
			
			// Add the user to the Group as an unverified member
			$myDBaseObj->AddMemberToGroup($inviteRecord->inviteGroupId, $userId);
			
			$myDBaseObj->DeleteInvitationByAuth($auth);

			// Add capability to user
			$user = new WP_User($userId);
			$user->add_cap(CARDZNET_CAPABILITY_PLAYER);		
			
			return $rtnHTML;	
		}
		
	}

}

new CardzNetPluginClass(__FILE__, new CardzNetDBaseClass(__FILE__));

?>