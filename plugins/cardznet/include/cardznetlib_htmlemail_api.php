<?php
/* 
Description: Core Library EMail API functions
 
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

if (!class_exists('CardzNetLibHTMLMailer')) 
{
	ABSPATH . WPINC . '/class-phpmailer.php';
	if (file_exists(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php'))
	{
		require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
		require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		
		class CardzNetLibWPMailer extends PHPMailer\PHPMailer\PHPMailer {};
	}
	else
	{
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';		
		
		class CardzNetLibWPMailer extends PHPMailer {};
	}
	
	define('CARDZNETLIB_FILENAME_EMAILLOG', 'EMailLog.log');
	
	class CardzNetLibHTMLMailer extends CardzNetLibWPMailer
	{
		var $lastError = '';
		
		function preSend()
		{
			// Force the From name
			$this->FromName = $this->StageShow_FromName;
			
			$status = parent::preSend();

			if ($this->SMTPDebug)
			{
				echo "<br>Called PHPMailer::preSend() - Return status=$status<br>\n";
			}
						
			if ($status)
			{
				$origMailHeaders = explode( "\n", $this->MIMEHeader );

				$MIMEHeader = '';
				
				$contentTypeDefined = false;	
				$contentTypeMarker = 'content-type:';
				$contentTypeLen = strlen($contentTypeMarker);
				
				foreach ($origMailHeaders as $origMailHeader)
				{
					if (strlen($origMailHeader) == 0)
						continue;
						
					if (strpos($origMailHeader, ':') === false)
						continue;
						
					if (substr($origMailHeader, 0, 1) === '=')
						continue;
						
					if (strtolower(substr($origMailHeader, 0, $contentTypeLen)) === $contentTypeMarker)
					{
						// This is a MIME content specifier - Reject if Content-Type already defined
						if ($contentTypeDefined)
						{
							continue;
						}
						
						// The first entry will be the Content-Type entry we added ... so define type here
						if (!empty($this->ourContentType))
							$origMailHeader = 'Content-Type: '.$this->ourContentType;
							
						$contentTypeDefined = true;							
					}
						
					if ($MIMEHeader !== '') $MIMEHeader .= "\n";
					$MIMEHeader .= $origMailHeader;
				}

				$this->MIMEHeader = $MIMEHeader;				
			}

			return $status;
		}  	
		
		public function postSend()
		{
			if ($this->SMTPDebug)
			{
				echo "Mailer: ".$this->Mailer."<br>\n";
			}
			
			if ($this->EMailLogPath != '')
			{
				$debugMessage = "Sent EMail using ".$this->Mailer." Mailer\n\n";
				$debugMessage .= "MIMEHeader:\n".$this->MIMEHeader."\n\n";
				$debugMessage .= "MIMEBody:\n".$this->MIMEBody."\n\n";

                $this->LogEMail($this->EMailLogPath, $debugMessage);
			}
			
			$status = parent::postSend();
			
			if ($this->SMTPDebug)
			{
				echo "Called PHPMailer::PostSend() - Return status=$status<br>\n";
			}
			
			return $status;
		}
		
		function LogEMail($LogsFolder, $DebugMessage)
		{
			$LogNotifyFile = CARDZNETLIB_FILENAME_EMAILLOG;				
			$logFileObj = new CardzNetLibLogFileClass($LogsFolder);
			$logFileObj->StampedLogToFile($LogNotifyFile, $DebugMessage, CardzNetLibDBaseClass::ForAppending);
		}

	}
}
  
if (!class_exists('CardzNetLibHTMLEMailAPIClass')) 
{
	class CardzNetLibHTMLEMailAPIClass // Define class
	{	
		var $imageobjs = array();
		var $fileobjs = array();
		var $ourContentType = '';
		
		const EMBEDDED_IMAGE_MARKER = "data:image/png;base64,";

		var $parentObj;
		var $adminEMail;
		
		function __construct($ourParentObj)	
		{
			$this->parentObj = $ourParentObj;	
			$this->adminEMail = get_option('admin_email');		
		}
				
		function createPHPMailerObj($SMTPDebug, $EMailLogPath)
		{
			global $phpmailer;
			$phpmailer = new CardzNetLibHTMLMailer( true );		
			$phpmailer->SMTPDebug = $SMTPDebug;
			$phpmailer->EMailLogPath = $EMailLogPath;
			if (!empty($this->ourContentType))
			{
				$phpmailer->ourContentType = $this->ourContentType;
			}
		}
		
		static function DoEmbeddedFileImage($filePath)
		{
			$imagedata = file_get_contents($filePath);
			$imageBase64 = chunk_split(base64_encode($imagedata));
				
			$imageFile = basename($filePath);
			
			$embeddedImage = self::EMBEDDED_IMAGE_MARKER."$imageBase64";
			return $embeddedImage;
		}
		
		function AddFileImage($filePath)
		{
			$imageObj = new stdClass();
			
			$imagedata = file_get_contents($filePath);
				
			$imageFile = basename($filePath);
			$CIDFile = 'File.'.$imageFile;
			
			$imageObj->file = $imageFile;
			$imageObj->cid = $CIDFile;
			$imageObj->image = chunk_split(base64_encode($imagedata));
			
			$this->AddImage($imageObj);
			return $CIDFile;
		}
		
		function AddImage($imageObj)
		{
			// Check if image is already in list
			foreach ($this->imageobjs as $currImageObj)
			{
				if ($currImageObj->cid === $imageObj->cid)
				{
					return true;
				}
			}
			
			$this->imageobjs[] = $imageObj;
			return true;
		}
		
		function AddAttachment($filePath)
		{
			$imagedata = file_get_contents($filePath);
			if (strlen($imagedata) == 0) return;
			
			$this->AddAttachmentData($imagedata, $filePath);
		}
			
		function AddAttachmentData($imagedata, $filePath)
		{
			$fileObj = new stdClass();
			
			$imageFile = basename($filePath);
			
			$fileTypePosn = strripos($filePath, '.');
			if (!$fileTypePosn) return;
			
			$fileExtn = strtolower(substr($filePath, $fileTypePosn+1));
			$fileObj->mimeType = 'application/'.$fileExtn;
			
			$fileObj->file = $imageFile;			
			$fileObj->image = chunk_split(base64_encode($imagedata));
			
			$this->fileobjs[] = $fileObj;
		}
		
		function sendMail($to, $from, $subject, $content1, $content2 = '', $headers = '')
		{
	  		// FUNCTIONALITY: EMail - Send MIME format EMail with text and HTML versions
			if ((strlen($content1) > 0) && (stripos($content1, '<html>') !== false))
			{
				$contentHTML = $content1;
				$contentTEXT = $content2;
				
				if (strlen($contentTEXT) == 0)
				{
	  				// FUNCTIONALITY: EMail - Create TEXT content from HTML content
					
					// Change <br> and <p> to line feeds
					$contentTEXT = $contentHTML;
					
					// Convert HTML Anchor to ... Anchor_Text(Anchor_HREF)					
					$noOfMatches = preg_match_all('|\<a.*?href=(.*?)\>(.*?)\<\/a\>|', $contentTEXT, $regexResults);
					for ($i=0; $i<$noOfMatches; $i++)
					{
						$origLink = $regexResults[0][$i];
						$origURL  = $regexResults[1][$i];
						$origText = $regexResults[2][$i];

						$origURL = str_replace('"', '', $origURL);
						$origURL = str_replace('mailto:', '', $origURL);
						
						if ($origText == $origURL)
							$targetText = $origText;
						else if ($origText == '')
							$targetText = '';
						else
							$targetText = "$origText($origURL)";
						
						$contentTEXT = str_replace($origLink, $targetText, $contentTEXT);
					}
					
					$contentTEXT = htmlspecialchars_decode($contentTEXT);

					$search = array (
						"'<script[^>]*?>.*?</script>'si",		// Javascript
						"'([\r\n])[\s]+'",						// White space
						"'<(br|p|\/tr).*?>'i",					// End of Line
						"'<[/!]*?[^<>]*?>'si");					// All HTML tags
						
					$replace = array (
						"",
						"",
						"\n",
						"");

					$contentTEXT = preg_replace($search, $replace, $contentTEXT);
				}

				// Tidy up the line ends
				$search = array (
					"'\r\n'",		// CR LF
					"'\r'");		// CR
					
				$replace = array (
					"\n",
					"\n");

				$contentHTML = preg_replace($search, $replace, $contentHTML);
				
				if (current_user_can(CARDZNETLIB_CAPABILITY_SYSADMIN)) 
				{
					if (CardzNetLibWPMailer::hasLineLongerThanMax($contentTEXT))
					{
						echo "WARNING: Text Content has one or more lines longer than SMTP allows<br>\n";
						echo "<br>\n<br>\n";
					}
					
					if (CardzNetLibWPMailer::hasLineLongerThanMax($contentHTML))
					{
						echo "WARNING: HTML Content has one or more lines longer than SMTP allows<br>\n";
						echo "<br>\n<br>\n";
					}					
				}
				      			
				// Create a unique boundary string using the MD5 algorithm to generate a random hash
				$MIMEMarker = md5(date('r', time()));
				$this->MIMEboundaryA  = "Part_A_".$MIMEMarker;
				$this->MIMEboundaryB  = "Part_B_".$MIMEMarker;

				$this->ourContentType .= "multipart/alternative; boundary=\"$this->MIMEboundaryA\"";	// boundary string and mime type specification

				// Add the MIME headers
				if (strlen($headers) > 0) $headers .= "\r\n";
				$headers .= "MIME-Version: 1.0";				
				$headers .= "\r\nContent-Type: {$this->ourContentType}";

				// Build the MIME encoded email body
				$message  = '';
				$message .= "This is a message with multiple parts in MIME format\n";
				$message .= "--$this->MIMEboundaryA\n";
				$message .= "Content-Type: text/plain\n";
				$message .= "Content-Transfer-Encoding: 8bit\n";
				$message .= "\n";
				$message .= $contentTEXT;
				$message .= "\n--$this->MIMEboundaryA\n";
				
				$message .= "Content-Type: multipart/related; boundary=\"$this->MIMEboundaryB\"\n\n";
				$message .= "--$this->MIMEboundaryB\n";
				
				$message .= "Content-Type: text/html; charset=\"utf-8\"\n";
				$message .= "Content-Transfer-Encoding: 8bit\n";
				//$message .= "Content-Transfer-Encoding: quoted-printable\n";
				$message .= "\n";
				
				$message .= $contentHTML;
				$message .= "\n";

				foreach ($this->imageobjs as $imageobj)
				{
					$message .= $this->OutputMIMEImage($imageobj);
				}			

				foreach ($this->fileobjs as $fileobj)
				{
					$message .= $this->OutputMIMEFile($fileobj);
				}
							
				$message .= "--$this->MIMEboundaryB--\n";				
				$message .= "\n";
				$message .= "--$this->MIMEboundaryA--\n";
			}
			else
			{
				$message = $content1;
			}

			global $phpmailer;
			
			$SMTPDebug = $this->parentObj->getDbgOption('Dev_ShowEMailMsgs');
			$EMailLogPath = $this->parentObj->getDbgOption('Dev_LogEMailMsgs') ? $this->parentObj->getOption('LogsFolderPath') : '';
			$this->createPHPMailerObj($SMTPDebug, $EMailLogPath);
			
			$replyTo = $from;
			
			// Define the email headers - separated with \r\n
			if (strlen($headers) > 0) $headers .= "\r\n";
			$headers .= "From: $from";	
			$headers .= "\r\nReply-To: $replyTo";	

			if ($SMTPDebug)
			{
				// FUNCTIONALITY: General - Echo EMail when Dev_ShowEMailMsgs selected - Body Encoded with htmlspecialchars
				echo "To:<br>\n";
				echo htmlspecialchars($to);
				echo "<br>\n<br>\n";
				echo "Subject:<br>\n";
				echo htmlspecialchars($subject);
				echo "<br>\n<br>\n";
				echo "Headers:<br>\n";
				echo str_replace("\r\n", "<br>\r\n", htmlspecialchars($headers));
				echo "<br>\n<br>\n";
				echo "Message:<br>\n";
				echo htmlspecialchars($message);
				echo "<br>\n<br>\n";
			}
					
			$bracket_pos = strpos( $from, '<' );
			if ( $bracket_pos !== false ) 
				{
				// Text before the bracketed email is the "From" name.
				if ( $bracket_pos > 0 ) 
				{
					$from_name = substr( $from, 0, $bracket_pos - 1 );
					$from_name = str_replace( '"', '', $from_name );
					$from_name = trim( $from_name );
				}

				$from_email = substr( $from, $bracket_pos + 1 );
				$from_email = str_replace( '>', '', $from_email );
				$from_email = trim( $from_email );
			} 
			else
			{
				$from_email = trim( $from );
				$from_name = '';
			}
			$phpmailer->StageShow_From = $from_email;
			$phpmailer->StageShow_FromName = $from_name;
									
			// Register a function to get any EMail errors
			add_action('wp_mail_failed', array(&$this, 'Get_EMailErrors'));
			
			// FUNCTIONALITY: General - Send EMail
			$toList = explode(';', $to);
			if (!$this->parentObj->getDbgOption('Dev_BlockEMailSend'))
			{
				if (!wp_mail($toList, $subject, $message, $headers))
				{
					return $this->lastError;
				}
			}
			else
			{
				echo "<br><strong>Sending of EMails Blocked </strong><br><br>\n";	
			}

			return 'OK';
		}	
		
		function OutputMIMEFile($fileobj)
		{
			$message = '';

			$message .= "--$this->MIMEboundaryB\n";
			$message .= 'Content-Type: "'.$fileobj->mimeType.'"; name="'.$fileobj->file."\n";
			$message .= 'Content-Disposition: attachment; filename="'.$fileobj->file.'" '."\n";
			$message .= 'Content-Transfer-Encoding: base64'."\n";
			$message .= "\n";

			$message .= $fileobj->image;
			$message .= "\n";
			
			return $message;
		}
		
		function OutputMIMEImage($imageobj)
		{
			$message = '';

			$message .= "--$this->MIMEboundaryB\n";
			$message .= "Content-Type: image/png; name=\"".$imageobj->file."\"\n";
			$message .= "Content-Transfer-Encoding: base64\n";
			$message .= "Content-ID: <".$imageobj->cid.">\n";
						
			//$message .= "Content-Disposition: inline; filename=\"".$imageobj->file."\"\n";
			$message .= "\n";

			$message .= $imageobj->image;
			$message .= "\n";
			
			return $message;
		}		
		
		function Get_EMailErrors($excp)
		{
			$errMsg = $excp->get_error_message();
			$this->lastError = $errMsg;
		}

	}
}

?>