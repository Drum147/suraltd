<?php
/* 
Description: Code for Managing Development Testing
 
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
	
include 'cardznetlib_testbase.php';  

if (!class_exists('CardzNetLibDevCallerClass')) 
{
	define('CARDZNETLIB_DEVTESTCALLER', true);
	
	class CardzNetLibDevCallerClass extends CardzNetLibAdminClass // Define class
	{
		static function DevTestFilesList($testDir)
		{
			$filePath = $testDir.'*_test_*.php';
			$testFiles = glob( $filePath );
			return $testFiles;
		}
		
		function __construct($env, $domain = '') //constructor	
		{
			if ($domain == '') $domain = $env['Domain'];
			$this->ourClassPrefix = $domain.'_Test_';			
			$this->libFilePrefix = 'cardznetlib_test_';
			$this->libClassPrefix = 'CardzNetLib_Test_';
			$this->pageTitle = 'Dev TESTING';
						
			// Call base constructor
			parent::__construct($env);			
		}

		function ProcessActionButtons()
		{
		}
		
		function Output_MainPage($updateFailed)
		{
			$localServerRoot = 'U:\Internet';
			$onLocalServer = (substr(__FILE__, 0, strlen($localServerRoot)) == $localServerRoot);

			$myPluginObj = $this->myPluginObj;
			$myDBaseObj = $this->myDBaseObj;
			
			// Stage Show TEST HTML Output - Start 				
			$ourTestFilePrefix = strtolower($this->ourClassPrefix);
			$ourTestFilePrefixLen = strlen($ourTestFilePrefix);
			
			$libTestFilePrefix = $this->libFilePrefix;
			$libTestFilePrefixLen = strlen($libTestFilePrefix);
			
			$testClasses = array();
			$maxIndex = 0;
			$testDir = dirname(__FILE__).'/';			
			//$testFiles = scandir( $testDir );			
			$testFiles = $this->DevTestFilesList($testDir);

			foreach ($testFiles as $filePath)
			{
				$testFile = basename($filePath);
				$testName = str_replace('.php','', $testFile);
				if (substr($testName, 0, $libTestFilePrefixLen) == $libTestFilePrefix)
				{
					$testName = substr($testName, $libTestFilePrefixLen);
					$testClass = $this->libClassPrefix.$testName;
				}
				else
				{
					$parts = explode('_test_', $testName);
					$testClass = str_replace('_test_', '_Test_', $testName);
					$testClass = str_replace('wp_', 'WP', $testClass);
					$testName = $parts[1];
				}
					
				//echo "Test File: $testFile - Class: $testClass <br><br>\n";
								
				include $filePath;
		
				if (!class_exists($testClass)) continue;
			
				$testObj = new $testClass($this->env);
				$orderIndex = $testObj->GetOrder();
				if ($orderIndex <= 0) continue;
				
				if (!$onLocalServer && ($testObj->isDevTest))
				{
					continue;
				}
				
				if (CardzNetLibUtilsClass::GetHTTPTextElem('post', 'lastdevtestclass') == $testName)
				{
					$orderIndex = 0;
				}
				$index = $orderIndex * 10;
				
				if (isset($testClasses[$index]))
				{
					echo "<br><strong>Duplicate Index($orderIndex) - $testClass</strong> - Moved to next available location</br>\n";
					while (isset($testClasses[$index])) 
					{
						$index++;
					}
				}				
				$testClassInfo = new stdClass;
				$testClassInfo->Name = $testName;
				$testClassInfo->Path = $filePath;
				$testClassInfo = $testObj->AddPostArgs($testClassInfo);
				$testClassInfo->Obj = $testObj;
				
				$testClasses[$index] = $testClassInfo;
				
				$maxIndex = ($index > $maxIndex) ? $index : $maxIndex;
			}
			
			//CardzNetLibUtilsClass::print_r($testClasses, 'testObjs');
			
			for ($index = 0; $index<=$maxIndex; $index++)
			{
				if (!isset($testClasses[$index]))
				{
					continue;
				}
				$testClassInfo = $testClasses[$index];
				$testObj = $testClassInfo->Obj;

				$postArgs  = 'method="post"';
				if ($testClassInfo->Target != "") $postArgs .= ' action="'.$testClassInfo->Target.'"';
				
				echo "<form $postArgs>\n";
				$this->WPNonceField($testClassInfo->Referer);
				echo '<input type="hidden" name="lastdevtestclass" id="lastdevtestclass" value="'.$testClassInfo->Name.'"/>'."\n";
				$testObj->Show();
				echo '</form>'."\n";
				
				if ($testObj->isRunning) break;
			}
			
		}				 


	}
}

?>