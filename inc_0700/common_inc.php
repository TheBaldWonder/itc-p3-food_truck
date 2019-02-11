<?php
/**
 * common_inc.php stores site-wide utility functions
 *
 * Version 2.02 adds support for absolute URLs in makeLinks() 
 * 
 * Version 2.04 adds true/false return for sessionStart(), benchmark in get_header()
 * 
 * Version 2.05 changes getENUM() to regex version - createImagePrefix() moved to common
 * 
 * Version 2.06 moves themes to separate folder, changes in get_header() function
 *
 * Version 2.07 adds htmlSelector(), smartTitle() addLink() and get/showFeedback() functions
 * Also fixes issue with config->BenchNote not showing up in header
 *
 * Version 2.08 rte() now looks for a file named fckeditor.css inside the theme folder for EditArea styles
 *
 * Version 2.09 added rotate() and randomize() functions
 *
 * Version 2.10 changed htmlSelector() to selector(), changed get/showFeedback() to feedback()/showFeedback()
 *
 * Version 2.20 removes ADMIN constants in favor of $config->admin properties
 * 
 * Version 2.21 merges version 2.20 and 2.103 which includes fix for formReq(), adding feedback() warning levels internally, documentation
 * 
 * Version 2.22 removes extension2fileType() and checkFileType() - these will be added to new version of upload files
 * 
 * @package nmCommon
 * @author Bill Newman <williamnewman@gmail.com>
 * @version 2.22 2013/05/28
 * @link http://www.newmanix.com/  
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @todo add getSET() to accommodate MySQL set
 * @todo create fallback on new version of getENUM() if no enum values
 * @todo replace version 2.21 (this version) into nmCommon, nmWDK and ITC290 Common Files
 */
 
/*
default settings moved to common_inc.php from config - rarely changed
*/

$config->adminLogin = ADMIN_PATH . 'admin_login.php'; # Admin login page - all Admin pages part of nmAdmin package
$config->adminValidate = ADMIN_PATH . 'admin_validate.php'; # Admin login validation page
$config->adminDashboard = ADMIN_PATH . 'admin_dashboard.php'; # Administrative (dashboard) page
$config->adminLogout = ADMIN_PATH . 'admin_logout.php'; # Administrative logout file
$config->adminAdd = ADMIN_PATH . 'admin_add.php'; # Add administrators here
$config->adminReset = ADMIN_PATH . 'admin_reset.php'; # Reset admin passwords here
$config->adminEdit = ADMIN_PATH . 'admin_edit.php'; # Edit admin data here
$config->titleTag = THIS_PAGE; #title tag must be unique 

/**
 * Forcibly passes user to a URL.  Accepts either an absolute or relative address.
 *
 * This function is a alternative to the PHP header() function.
 *  
 * Any page using myRedirect() needs ob_start() at the top of the page or header() errors 
 * will occur i.e.: 'headers already sent'.
 *
 * Will sniff for "http://", "https://", which will force an absolute redirect, otherwise assume local.
 * 
 * @param string $myURL locally referenced file, or absolute with 'http://' as destination for user
 * @return void
 * @todo examine HTTPS support
 */
function myRedirect($myURL)
{
	$httpCheck = strtolower(substr($myURL,0,8)); # http:// or https://
	if(strrpos($httpCheck,"http://")>-1 || strrpos($httpCheck,"https://")>-1){//absolute URL
		header("Location: " . $myURL);
	}else{//relative URL
		$myProtocol = strtolower($_SERVER["SERVER_PROTOCOL"]); # Cascade the http or https of current address
		if(strrpos($myProtocol,"https")>-1){$myProtocol = "https://";}else{$myProtocol = "http://";}
		$dirName = dirname($_SERVER['REQUEST_URI']);  #Path derives properly on Windows & UNIX. alternatives: SCRIPT_URL, PHP_SELF
		$char = substr($dirName,strlen($dirName) - 1);
		if($char != "/"){$dirName .= "/";} # Only add slash if required!
		header("Location: " . $myProtocol . $_SERVER['HTTP_HOST'] . $dirName . $myURL);
	}
	die(); //added for safety!
} #End myRedirect()

/**
 * Wrapper function for processing data pulled from db
 *
 * Forward slashes are added to MySQL data upon entry to prevent SQL errors.  
 * Using our dbOut() function allows us to encapsulate the most common functions for removing  
 * slashes with the PHP stripslashes() function, plus the trim() function to remove spaces.
 *
 * Later, we can add to this function sitewide, as new requirements or vulnerabilities develop.
 *
 * @param string $str data as pulled from MySQL
 * @return $str data cleaned of slashes, spaces around string, etc.
 * @see dbIn()
 * @todo none
 */
function dbOut($str)
{
	if($str!=""){$str = stripslashes(trim($str));}//strip out slashes entered for SQL safety
	return $str;
} #End dbOut()

/**
 * Filters data per MySQL standards before entering database. 
 *
 * Adds slashes and helps prevent SQL injection per MySQL standards.    
 * Function enclosed in 'wrapper' function to add further functionality when 
 * as vulnerabilities emerge.
 *
 * @param string $var data as entered by user
 * @return string returns data filtered by MySQL, adding slashes, etc.
 * @see dbOut()
 * @see idbIn()  
 * @todo Rebuild so global $myConn no longer involved
 */
function dbIn($var)
{
	global $myConn;//checks data against active DB connection

	if(isset($var) && $var != "")
	{
		return mysql_real_escape_string($var);
	}else{
		return "";
	}
} #End dbIn()

/**
 * mysqli version of dbIn()
 * 
 * Filters data per MySQL standards before entering database. 
 *
 * Adds slashes and helps prevent SQL injection per MySQL standards.    
 * Function enclosed in 'wrapper' function to add further functionality when 
 * as vulnerabilities emerge.
 *
 * @param string $var data as entered by user
 * @param object $myConn active mysqli DB connection, passed by reference.
 * @return string returns data filtered by MySQL, adding slashes, etc.
 * @see dbIn() 
 * @todo none
 */
function idbIn($var,&$iConn)
{
	if(isset($var) && $var != "")
	{
		return mysqli_real_escape_string($iConn,$var);
	}else{
		return "";
	}
	
} #End idbIn()

/**
 * br2nl() changes '<br />' tags  to '\n' (newline)  
 * Preserves user formatting for preload of <textarea>
 *
 * <code>
 * $myText = br2nl($myText); # <br /> changed to \n
 * </code>
 *
 * @param string $text Data from DB to be loaded into <textarea>
 * @return string Data stripped of <br /> tag variations, replaced with new line 
 * @todo none 
 */
function br2nl($text)
{
	$nl = "\n";   //new line character
    $text = str_replace("<br />",$nl,$text);  //XHTML <br />
    $text = str_replace("<br>",$nl,$text); //HTML <br>
    $text = str_replace("<br/>",$nl,$text); //bad break!
    return $text;
    /* reference (unsused)
    $cr = chr(13); // 0x0D [\r] (carriage return)
	$lf = chr(10); // 0x0A [\n] (line feed)
	$crlf = $cr . $lf; // [\r\n] carriage return/line feed)
    */
} #End br2nl()

/**
 * nl2br2() changes '\n' (newline)  to '<br />' tags
 * Break tags can be stored in DB and used on page to replicate user formatting
 * Use on input/update into DB from forms
 *
 * <code>
 * $myText = nl2br2($myText); # \n changed to <br />
 * </code>
 * 
 * @param string $text Data from DB to be loaded into <textarea>
 * @return string Data stripped of <br /> tag variations, replaced with new line 
 * @todo none
 */
function nl2br2($text)
{
	$text = str_replace(array("\r\n", "\r", "\n"), "<br />", $text);
	return $text;
} #End nl2br2()

/**
 * wrapper function for PHP session_start(), to prevent 'session already started' error messages. 
 *
 * To view any session data, sessions must be explicitly started in PHP.  
 * In order to use sessions in a variety of INC files, we'll check to see if a session 
 * exists first, then start the session only when necessary.
 *
 * 
 * @return void
 * @todo none 
 */
function startSession()
{
	//if(!isset($_SESSION)){@session_start();}
	if(isset($_SESSION))
	{
		return true;
	}else{
		@session_start();
	}
	if(isset($_SESSION)){return true;}else{return false;}
} #End startSession()

/**
 * Checks for email pattern using PHP regular expression.  
 *
 * Returns true if matches pattern.  Returns false if it doesn't.   
 * It's advised not to trust any user data that fails this test.
 *
 * @param string $str data as entered by user
 * @return boolean returns true if matches pattern.
 * @todo none
 */
function onlyEmail($myString)
{
  if(preg_match("/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9_\-\.]+\.[a-zA-Z0-9_\-]+$/",$myString))
  {return true;}else{return false;}
}#end onlyEmail()

/**
 * Checks data for alphanumeric characters using PHP regular expression.  
 *
 * Returns true if matches pattern.  Returns false if it doesn't.   
 * It's advised not to trust any user data that fails this test.
 *
 * @param string $str data as entered by user
 * @return boolean returns true if matches pattern.
 * @todo none
 */
function onlyAlphaNum($myString)
{
  if(preg_match("/[^a-zA-Z0-9]/",$myString))
  {return false;}else{return true;} //opposite logic from email?
}#end onlyAlphaNum()

/**
 * Checks data for numeric characters using PHP regular expression.  
 *
 * Returns true if matches pattern.  Returns false if it doesn't.   
 * It's advised not to trust any user data that fails this test.
 *
 * @param string $str data as entered by user
 * @return boolean returns true if matches pattern.
 * @todo none
 */
function onlyNum($myString)
{
  if(preg_match("/[^0-9]/",$myString))
  {return false;}else{return true;} //opposite logic from email?
}#end onlyNum()

/**
 * Checks data for alphanumeric characters using PHP regular expression.  
 *
 * Returns true if matches pattern.  Returns false if it doesn't.   
 * It's advised not to trust any user data that fails this test.
 *
 * @param string $str data as entered by user
 * @return boolean returns true if matches pattern.
 * @todo none
 */
function onlyAlpha($myString)
{
  if(preg_match("/[^a-zA-Z]/",$myString))
  {return false;}else{return true;} //opposite logic from email?  
}#end onlyAlpha()

/**
 * Requires data submitted as isset() and passes dat to 
 * dbIn() which processes per MySQL standards, adding slashes and 
 * attempting to prevent SQL injection.     
 * Upon failure, user is forcibly redirected to global variable,  
 * $redirect, which is applied just before checking a series of form values.
 *
 *<code>
 * $redirect = THIS_PAGE; //global redirect
 * $myVar = formReq($_POST['myVar']);
 *</code>
 *
 * @uses dbIn()
 * @param string $var data as entered by user
 * @return string returns data filtered by MySQL, adding slashes, etc.
 * @todo none
 */
function formReq($var)
{
	/**
	 * $redirect stores page to redirect user to upon failure 
	 * This variable is declared in the page, just before the form fields are tested.
	 *
	 * @global string $redirect
	 */
	global $redirect;

	if(!isset($_POST[$var]))
	{
		feedback("Required Form Data Not Passed","error");
		
		if(!isset($redirect) || $redirect == "")
		{//if no redirect indicated, use the current page!
			myRedirect(THIS_PAGE);		
		}else{
			myRedirect($redirect);	
		}
	}else{
		return dbIn($_POST[$var]);
	}
}#end formReq()

/**
 * Requires data submitted as isset() and passes dat to 
 * dbIn() which processes per MySQL standards, adding slashes and 
 * attempting to prevent SQL injection.     
 * Upon failure, user is forcibly redirected to global variable,  
 * $redirect, which is applied just before checking a series of form values.
 *
 *<code>
 * $redirect = THIS_PAGE; //global redirect
 * $myVar = formReq($_POST['myVar']);
 * $otherVar = formReq($_POST['otherVar']);
 *</code>
 *
 * @uses dbIn()
 * @param string $var data as entered by user
 * @return string returns data filtered by MySQL, adding slashes, etc.
 * @todo merge formReq (uses global) with form_Req (preferred) below:
 */
function form_Req($var,$redirect)
{
	/**
	 * $redirect stores page to redirect user to upon failure 
	 * This variable is declared in the page, just before the form fields are tested.
	 *
	 * @global string $redirect
	 */
	global $redirect;

	if(!isset($_POST[$var]))
	{
		feedback("Required Form Data Not Passed","error");
		
		if(!isset($redirect) || $redirect == "")
		{//if no redirect indicated, use the current page!
			myRedirect(THIS_PAGE);		
		}else{
			myRedirect($redirect);	
		}
	}else{
		return dbIn($_POST[$var]);
	}
	
}

/**
 * mysqli version of formReq()
 * 
 * Requires data submitted as isset() and passes data to 
 * idbIn() which processes per MySQL standards, adding slashes and 
 * attempting to prevent SQL injection.
 *     
 * Upon failure, user is forcibly redirected to global variable,  
 * $redirect, which is applied just before checking a series of form values.
 *
 * mysqli version requires explicit connection, $myConn
 *
 *<code>
 * $iConn = conn("admin",TRUE); //mysqli connection
 * $myVar = iformReq($_POST['myVar'],$iConn);
 * $otherVar = iformReq($_POST['otherVar'],$iConn);
 *</code>
 *
 * @uses idbIn()
 * @see formReq() 
 * @param string $var data as entered by user
 * @param object $myConn active mysqli DB connection, passed by reference.
 * @return string returns data filtered by MySQL, adding slashes, etc.
 * @todo none
 */
function iformReq($var,&$iConn)
{
	/**
	 * $redirect stores page to redirect user to upon failure 
	 * These variables are declared in the page, just before the form fields are tested.
	 *
	 * @global string $redirect
	 */
	global $redirect;

	if(!isset($_POST[$var]))
	{
		feedback("Required Form Data Not Passed","error");
		
		if(!isset($redirect) || $redirect == "")
		{//if no redirect indicated, use the current page!
			myRedirect(THIS_PAGE);		
		}else{
			myRedirect($redirect);	
		}
	}else{
		return idbIn($_POST[$var],$iConn);
	}
	
}#end iformReq()


/**
 * Automatically loads classes by name when called.  This saves resources, as  
 * only pages that need to reference the class actually load it.
 *
 * The autoload function allows us to name a file after the class, and customize it to 
 * meet our needs, but load the file requiring the class by assuming the file name based on 
 * the name of the class.  In our case, we'll add '_inc.php', which follows our pattern. 
 *
 *<code>
 *$myObject = new myClass();
 *</code>
 *  
 * @param str $class_name Class to be loaded as needed
 * @return void
 */
/*
AUTOLOAD REMOVED 7/6/2015 - LEFT FOR REFERENCE ONLY
function __autoload($class_name) 
{
    if(file_exists(INCLUDE_PATH . $class_name . '_inc.php'))
    {
    	include INCLUDE_PATH . $class_name . '_inc.php';
	}
}#end __autoload()
*/


/**
 * Will accept optional 'header' on per page basis as parameter sent 
 * to function.  The file referenced by name must exist in the 
 * PHP include folder:
 *
 * <code>
 * get_header('newheader_inc.php');
 * </code>
 *
 * If no parameter passed will attempt to retrieve matching 
 * header/footer files based on name of 'theme' declared inside 
 * config_inc.php:
 *
 * <code>
 * $config->theme = "TwoTone";
 * </code>
 * 
 * In which case the function will attempt to load a header and footer
 * named header_inc.php and footer_inc.php from the 'TwoTone' subfolder in the 
 * 'themes' folder.
 *
 * @see get_footer()
 * @param string Optional name of include file
 * @return none
 * @todo none
 */
function get_header($myInclude = '')
{
	global $config; #config info made global for theme, links, meta tags, etc.
	static $headerDone = 0; #static allows the get_header() function for the footer, and not confuse programmer
	$headerDone++;
	
	if(!defined('THEME_PHYSICAL')){define('THEME_PHYSICAL', PHYSICAL_PATH . 'themes/' . $config->theme . '/');}
	if(!defined('THEME_PATH')){define('THEME_PATH', VIRTUAL_PATH . 'themes/' . $config->theme . '/');}
	
	if ($myInclude != "" && file_exists(THEME_PHYSICAL . $myInclude))
	{#load explicitly referenced header in theme folder
		$myInclude = THEME_PHYSICAL . $myInclude;
	}else{#load theme header/footer or fallback
		if (file_exists(THEME_PHYSICAL . 'header_inc.php') && file_exists(THEME_PHYSICAL . 'footer_inc.php')) {//load theme!
			if($headerDone == 1){$myInclude = THEME_PHYSICAL . 'header_inc.php';}else{$myInclude = THEME_PHYSICAL . 'footer_inc.php';}
		} else {//if theme files do not exist (checked header & footer) load 'empty' default
			if($headerDone == 1){$myInclude = INCLUDE_PATH . 'header_inc.php';}else{$myInclude = INCLUDE_PATH . 'footer_inc.php';}
		}
	}

	if(isset($config->benchmarking) && $config->benchmarking == true && $headerDone == 2)
	{#part of benchmarking package - calculate on load of footer to be able to add page load to copyright for admins
		$config->timer->setMarker('END'); #set final point to measure
		$config->timer->stop();
		$mytime = $config->timer->timeElapsed('Start','END');
		$mytime = number_format($mytime,4);
		logBenchmark($mytime);  //write to benchmark file? or not.  See benchmark_inc.php
		$config->timer = ""; #clear resources
		#add page load to copyright for admins
		if(startSession() && isset($_SESSION['AdminID'])){ $config->copyright .= ' <em>(Page load: ' . $mytime . ' seconds.)</em> ';}
	}
	
	include $myInclude; #load resulting file
}#end getHeader()

/**
 * Retrieves structural footer INC file appropriate to the  
 * file.  Place code to filter tiny URLs, etc. or by file prefix
 *
 * The get_footer() function calls the get_header() function to place code in 
 * one place and not confuse the programmer that the functions are the same.
 *
 * Inside get_header() a static var named $headerDone keeps straight which to call the 
 * header or footer INC file.
 *
 *<code>
 * get_footer();
 *</code>
 *
 * @see get_header()
 * @param none
 * @return none
 * @todo none
 */
function get_footer($myInclude = '')
{//creates an alias, so as not to confuse the programmer
	get_header($myInclude);	
}#end getFooter()

/**
 * Creates and pre-loads radio, checkbox & options from passed delineated strings  
 *
 * Pass arrays, or strings of data for value, label and database match to the function 
 * and identify if you wish to create a select option, or a set of 
 * radio buttons or checkboxes.
 *
 * Form elements will be 'pre-loaded' with database values ($dbStr) so a 
 * user can change their selection, or see their original choice. 
 * 
 * <code>
 * $valuStr = "1,2,3,4,5";
 * $dbStr = "1,2,5";  
 * $lblStr = "chocolate,bananas,nuts,caramel,butterscotch";
 * createSelect2("checkbox","Toppings",$valuStr,$dbStr,$lblStr,",");
 * </code>
 *
 * @param string $elType type of input element created, 'select', 'radio' or 'checkbox'
 * @param string $elName name of element
 * @param string/array $valuArray delimiter separated string of values to choose
 * @param string/array $dbArray delimiter separated string of DB items to match
 * @param string/array $lblArray delimiter separated string of labels to view
 * @param string $char delimiter, default is comma     
 * @return void string is printed within function
 * @todo none
 */

function createSelect($elType,$elName,$valuArray,$dbArray,$lblArray,$char=',')
{
if(!is_array($valuArray)){$valuArray = explode($char,$valuArray);}//if not array, blow it up!	
if(!is_array($dbArray)){$dbArray = explode($char,$dbArray);}  //db values
if(!is_array($lblArray)){$lblArray = explode($char,$lblArray);}  //labels identify
	
$x = 0; $y = 0; $sel = "";//init stuff
   switch($elType)
   {
   case "radio":
   case "checkbox":
        for($x=0;$x<count($valuArray);$x++)
        {
             for($y=0;$y<count($dbArray);$y++)
             {
                   if($valuArray[$x]==$dbArray[$y])
                   {
                        $sel = " checked=\"checked\"";
                   }
             }//y for
              print "<input type=\"" . $elType . "\" name=\"" . $elName . "\" value=\"" . $valuArray[$x] . "\"" . $sel . ">" . $lblArray[$x] . "<br>\n";
		 $sel = "";
        }//x for
        break;
   case "select":
	print "<select name=\"" . $elName . "\">";
        for($x=0;$x<count($valuArray);$x++)
        {
             for($y=0;$y<count($dbArray);$y++)
             {
                   if($valuArray[$x]==$dbArray[$y])
                   {
                       $sel = " selected=\"selected\"";
                   }
             }//y for
              print "<option value=\"" . $valuArray[$x] . "\"" . $sel . ">" . $lblArray[$x] . "</option>\n";
	      $sel = "";
        }//x for
        print "</select>";
        break;
   }
}#end createSelect()

/* 
 * rte() function allows multiple RTE edit points on a page.
 *
 * Provides session protected wiring of fckeditor Rich Text Editor. 
 * If not logged in, shows data on page only.  If logged in, shows 'edit' 
 * button for each RTE, and allows RTE editing of data.
 *
 *<code>
 * rte(1); //mimimum, id of RTE only
 * rte(2,'50%','300','Default'); //all but border identified
 * rte(3,'300','400','Basic',TRUE);  //full implementation
 *</code>
 *
 * @param int $RTEID id number of RTE field to store data
 * @param str $Width width in percent or pixels of RTE edit box
 * @param str $Height height in pixels of RTE edit box
 * @param str $ToolBar configured in fckconfig.js, our implementations include "Default" & "Basic"
 * @param boolean $showBorder true will place a border around the entire RTE area and edit button  
 * @return void
 */
 	
function rte($RTEID,$Width='100%',$Height='400',$ToolBar = 'Basic',$showBorder=FALSE)
{
	global $config;
	$FCKPath = VIRTUAL_PATH . 'fckeditor/'; #JS path
	include_once PHYSICAL_PATH . 'fckeditor/fckeditor.php'; #PHP file
	if(isset($_REQUEST['tb'])){$ToolBar = $_REQUEST['tb'];} # toolbar setting comes from one of 2 places, POST or GET
	if(isset($_POST['edit'])){$edit = $_POST['edit'];}else{$edit = "";}
	startSession(); //session_start() wrapper
	if(isset($_SESSION['AdminID']) && is_numeric($_SESSION['AdminID']))
	{# only admins can see edit fields
		echo '<div align="center"><form name="exitForm" action="' . $config->adminDashboard . '" method="get">';
		echo '<input type="submit" value="EXIT TO ADMIN" /></form></div>';
		
		if (isset($_POST['submitForm']) && $_POST['submitForm'] == 1) { # If an administrator submits the form, update the page's RTEText.
			if($RTEID == $_POST['RTEID'])
			{//only try to update if matches current RTEID
				$RTEText = mysqli_real_escape_string(IDB::conn(),trim($_POST['FCKeditor']));
				$sql = sprintf("select Files from RTE WHERE RTEID = %d",$_POST['RTEID']);
				$result = @mysqli_query(IDB::conn(),$sql) or die(trigger_error(mysqli_error(IDB::conn()), E_USER_ERROR));
				$myPages = ''; #init
				if(mysqli_num_rows($result) > 0)
				{# update!
					while($row = mysqli_fetch_assoc($result))
					{# var stores all files where this RTE has been used
					   $myPages =  strip_tags($row['Files']);
					}	
					
					$addFile = TRUE; #if matches, WILL NOT add current file!
					if($myPages != "")
					{# If the field is not currently empty - check to see if it's already listed
						$aPages = explode(",",$myPages); # Split array of pages
						if(is_array($aPages))
						{
							for($x=0;$x<count($aPages);$x++)
							{
								if($aPages[$x] == THIS_PAGE){$addFile = FALSE;} # File already present, no need to add  		
							}
						}else{//not array, but matches!
							if($myPages == THIS_PAGE){$addFile = FALSE;}
						}
						if($addFile)
						{
							$myPages .= ',' . THIS_PAGE;
						} # Add current page to existing pages
					}else{ # No RTE including files, update with current page
						$myPages = THIS_PAGE;	
					}
					$sql = sprintf("UPDATE RTE SET RTEText = '%s', Files = '%s', AdminID=%d,LastUpdated=NOW() WHERE RTEID = %d",$RTEText,$myPages,$_SESSION['AdminID'],$_POST['RTEID']);
				}else{# insert!
					$sql = sprintf("INSERT INTO RTE (RTEText,Files,AdminID,LastUpdated) VALUES('%s','%s',%d,NOW())",$RTEText,THIS_PAGE,$_SESSION['AdminID']);
				}
		    	$result = @mysqli_query(IDB::conn(),$sql) or die(trigger_error(mysqli_error(IDB::conn()), E_USER_ERROR));
    		}
		}
	}
    $sql = sprintf("SELECT RTEText FROM RTE WHERE RTEID = %d",$RTEID); //select data to show, or place in edit box
    $result = mysqli_query(IDB::conn(),$sql) or die(trigger_error(mysqli_error(IDB::conn()), E_USER_ERROR));
    $row = mysqli_fetch_array($result);
    $RTEText = stripslashes($row['RTEText']);
    if(isset($_SESSION['AdminID']))
    {
	    if(isset($_POST['RTEID']) && $RTEID == $_POST['RTEID'])
	    {//only load editor info for current story
		  	if($edit=="yes")
			{//clicked edit, open RTE for editing
				echo '<div class="rte"><form action="' . THIS_PAGE . '" method="post">';
		    	echo '<input type="hidden" name="RTEID" value="' . $RTEID . '" />';
		        echo '<input type="hidden" name="submitForm" value="1" />';
	        	$oFCKeditor = new FCKeditor('FCKeditor');
	        	$oFCKeditor -> BasePath = $FCKPath;
				if(defined('THEME_PATH') && defined('THEME_PHYSICAL') && file_exists(THEME_PHYSICAL . 'fckeditor.css'))
				{#if a file named 'fckeditor.css' exists in the current theme, use it for style, otherwise fall back to fckconfig.js
					$oFCKeditor->Config['EditorAreaCSS'] = THEME_PATH . 'fckeditor.css';
				}
				
				if(isset($config->skinPath) && $config->skinPath != "")
				{#if configuration property of skinPath is set, use that skin instead!
					$oFCKeditor->Config['SkinPath'] = 'skins/' . $config->skinPath;
				}
				$oFCKeditor->Width  = $Width;
				$oFCKeditor->Height = $Height;
	        	$oFCKeditor->ToolbarSet = $ToolBar;
	        	$oFCKeditor -> Value = $RTEText;
	        	echo $oFCKeditor -> CreateHtml();
	        	echo '<input type="hidden" name="tb" value="' . $ToolBar . '" /><br />';
	        	if($_SESSION['Privilege']== "developer"){$thisRTE = " to #" . $RTEID;}else{$thisRTE = "";}//only dev level see ID
	        	echo '<input type="submit" value="SAVE CHANGES'. $thisRTE . '" /></form>';
	        	echo '<form name="backForm" action="' . THIS_PAGE . '" method="post" >';
	    		echo '<input type="hidden" name="tb" value="' . $ToolBar . '" />';
	    		echo '<input type="hidden" name="edit" value="no" />';
	    		echo '<div align="center"><input type="button" value="EXIT WITHOUT CHANGES" onclick="document.backForm.submit();" /></div></form></div>';
			}else{//show with edit button
	    		if($showBorder){echo '<div style="border-width:thin; border-style:solid;">';}
				echo $RTEText;
	    		echo '<div class="rte"><form name="editForm' . $RTEID . '" action="' . THIS_PAGE . '" method="post" >';
	    		echo '<input type="hidden" name="tb" value="' . $ToolBar . '" />';
	    		echo '<input type="hidden" name="edit" value="yes" />';
	    		echo '<input type="hidden" name="RTEID" value="' . $RTEID . '" />';
	    		if($_SESSION['Privilege']== "developer"){$thisRTE = " #" . $RTEID;}else{$thisRTE = "";}//only dev level see ID
	    		echo '<div align="center"><input type="button" value=" &nbsp; EDIT ' . $thisRTE . ' &nbsp; " onclick="document.editForm' . $RTEID . '.submit();" /></div></form></div>';
	    		if($showBorder){echo '</div>';}
			}  
	 
	    }else{
		    if($showBorder){echo '<div style="border-width:thin; border-style:solid;">';}
		    echo $RTEText;
    		echo '<div class="rte"><form name="editForm' . $RTEID . '" action="' . THIS_PAGE . '" method="post" >';
    		echo '<input type="hidden" name="tb" value="' . $ToolBar . '" />';
    		echo '<input type="hidden" name="edit" value="yes" />';
    		echo '<input type="hidden" name="RTEID" value="' . $RTEID . '" />';
    		if($_SESSION['Privilege']== "developer"){$thisRTE = " #" . $RTEID;}else{$thisRTE = "";}//only dev level see ID
    		echo '<div align="center"><input type="button" value=" &nbsp; EDIT ' . $thisRTE . ' &nbsp; " onclick="document.editForm' . $RTEID . '.submit();" /></div></form></div>';
    		if($showBorder){echo '</div>';}
	    }
	}else{//not admin, show
	    echo $RTEText;
    }
    if(isset($_REQUEST['tb'])){unset($_REQUEST['tb']);}//reset request var, prevents cascade to next RTE on page
}

/**
 * getENUM retrieves an array of all possible choices in a MySQL ENUM
 *
 * Using an ENUM allows us to avoid an extra link-table of limited choices
 *
 * regex version update from: http://barrenfrozenwasteland.com/index.php?q=node/7
 *
 * <code>
 * $privileges = getENUM(PREFIX . 'Admin','Privilege'); #grab all possible 'Privileges' from ENUM
 * </code>
 */
function getENUM($table,$column)
{
	$iConn = IDB::conn();

	$sql = "SHOW COLUMNS FROM $table LIKE '$column'";
	$result = @mysqli_query($iConn,$sql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
	$enum = mysqli_fetch_object($result);
	preg_match_all("/'([\w ]*)'/", $enum->Type, $values);
	return $values[1];
}

/* 
 * allows creation of links from associative array of link data and 
 * HTML prefix & suffix to each link
 *
 * Link arrays created in config_inc.php, and makeLinks() function called in 
 * header or footer include as required.
 *
 * Allows different HTML treatments per header/footer combo while containing 
 * same links in different header/footer combos, if desired
 *
 * <code>
 * $nav1 = array();
 * $nav1['index.php'] = "INDEX";
 * $nav1['about_us.php'] = "ABOUT US";
 * $nav1['contact.php'] = "CONTACT";
 * $nav1['links.php'] = "LINKS";
 * echo makeLinks($nav1,'<p align="center">','</p>');
 * </code>
 *
 * @param array $linkArray associative array of link data
 * @param str $prefix optional HTML string to add to front of link
 * @param str $suffix optional HTML string to add to end of link
 * @return str link segment as created by array
 */

function makeLinks($linkArray,$prefix='',$suffix='',$separator="~")
{
	$myReturn = '';
	foreach($linkArray as $url => $text)
	{
		$target = ' target="_blank"'; #will be removed if relative URL
		$httpCheck = strtolower(substr($url,0,8)); # http:// or https://
		if(!(strrpos($httpCheck,"http://")>-1) && !(strrpos($httpCheck,"https://")>-1))
		{//relative url - add path
			$url = VIRTUAL_PATH . $url;
			$target = "";
		}else if(strrpos($url,ADMIN_PATH . 'admin_')>-1){$target = "";}# clear target="_blank" for admin files
		$pos = strrpos($text, $separator); #tilde as default separator
		if ($pos === false)
		{// note: three equal signs - not found!
			$myReturn .= $prefix . "<a href=\"" . $url . "\"" . $target . ">" . $text . "</a>" . $suffix . "\n";
		}else{//found!  explode into title!
			$aText = explode($separator,$text); #split into an array on separator
			$myReturn .= $prefix . "<a href=\"" . $url . "\" title=\"" . $aText[1] . "\"" . $target . ">" . $aText[0] . "</a>" . $suffix . "\n";	
		}
	}	
	return $myReturn;	
}

/* 
 * troubleshooting wrapper function for var_dump
 *
 * saves annoyance of needing to type pre-tags
 *
 * Optional parameter $adminOnly if set to TRUE will require 
 * currently logged in admin to view crash - will not interfere with 
 * public's view of the page
 *
 * WARNING: Use for troubleshooting only: will crash page at point of call!
 *
 * <code>
 * dumpDie($myObject);
 * </code>
 *
 * @param object $myObj any object or data we wish to view internally 
 * @param boolean $adminOnly if TRUE will only show crash to logged in admins (optional) 
 * @return none
 */
function dumpDie($myObj,$adminOnly = FALSE)
{
	if(!$adminOnly || startSession() && isset($_SESSION['AdminID'])) 
	{#if optional TRUE passed to $adminOnly check for logged in admin
		echo '<pre>';
		var_dump($myObj);
		echo '</pre>';
		die;
	}
}

/**
 * Creates and pre-loads radio, checkbox & options from passed delineated strings  
 *
 * Pass arrays, or strings of data for value, label and database match to the function 
 * and identify if you wish to create a select option, or a set of 
 * radio buttons or checkboxes.
 *
 * Form elements will be 'pre-loaded' with database values ($dbStr) so a 
 * user can change their selection, or see their original choice. 
 * 
 * <code>
 * $html = "<h3>Here's my Muffin only Sidebar!</h3>";
 * selector("sidebar2",$html,"muffin_list.php~muffin_view.php","file","replace","~");
 * </code>
 *
 * This version appends a message only to files that start with "model_";
 *
 * <code>
 * $html = "<p>This gets tacked to the bottom of all model pages!</p>";
 * selector("sidebar2",$html,"model_","string","after","~");
 * </code>
 *
 * added version 2.07, changed to selector 2.10 
 *
 * @param string $property name of config property we'll be adding HTML to or changing
 * @param string $html HTML we'll be adding
 * @param string/array $match array or string to be split of files to match, or string to match
 * @param string $mySelector "file" or "string", identifies what we're trying to match
 * @param string $location "replace","before" or "after", identifies if we use HTML to replace or append
 * @param string $char delimiter, default is tilde     
 * @return void $config property is altered via the function if matches
 * @todo none
 */

function selector($property="",$html="",$match="",$mySelector="file",$location="replace",$separator="~")
{
	global $config;
	$mySelector = strtolower($mySelector); #de-specify case
	$foundMatch = FALSE; #will turn to true if match
	if(strtolower($mySelector) == "string")
	{#search for part of string in file name
		if (strpos(THIS_PAGE, $match) !== false)
		{#only alter/add HTML if current page includes selector as part of file name
			//$config->$property = $html;
			$foundMatch = TRUE;
		}
	
	}else{#match set of files
		if(!is_array($match)){$match = explode($separator,$match);}//if not array, blow it up!
		if (in_array(THIS_PAGE,$match))
		{#only add alter/add data if current page is part of selector
		   //$config->$property = $html;
		   $foundMatch = TRUE;
		}
	}

	if($foundMatch)
	{#If we've found a match, add/append HTML
		switch($location)
		{#choices are before, after or replace
			case "before": #prepend HTML
			$config->$property = $html . $config->$property; #note variable as property
			break;
			
			case "after": #append HTML
			$config->$property = $config->$property . $html;
			break;
			
			default: #replace HTML
			$config->$property = $html;
		}
	}
}#end selector()

/**
 * Creates a smart (sic) title from words present in the php file name (page)
 *
 * If no string is input, will take current PHP file name, strip of extension 
 * and replace "-" and "_" with spaces
 *
 * Will also title case first letter of significant words in title
 *
 * A comma separated string named $skip can be used to add/delete more 
 * words that are NOT title cased
 *
 * First word is always title case by default
 *
 * <code>
 * $config->titleTag = smartTitle();
 * </code>
 * 
 * added version 2.07
 *
 * @param string $myTitle file name or etc to amend (optional)
 * @return string converted title cased version of file name/string
 * @todo none
 */
function smartTitle($myTitle = '')
{
	if($myTitle == ''){$myTitle = THIS_PAGE;}
	$myTitle = strtolower(substr($myTitle, 0, strripos($myTitle, '.'))); #remove extension, lower case
	$separators = array("_", "-");  #array of possible separators to remove
	$myTitle = str_replace($separators, " ", $myTitle); #replace separators with spaces
	$myTitle = explode(" ",$myTitle); #create an array from the title
	$skip = "this|is|of|a|an|the|but|or|not|yet|at|on|in|over|above|under|below|behind|next to| beside|by|among|between|by|till|since|during|for|throughout|to|and|my";
	$skip = explode("|",$skip); # words to skip in title case
	
	for($x=0;$x<count($myTitle);$x++)
	{#title case words not skipped
		if($x == 0 || !in_array($myTitle[$x], $skip)) {$myTitle[$x] = ucwords($myTitle[$x]);}
		//echo $word . '<br />';
	}
	return implode(" ",$myTitle); #return imploded (spaces re-added) version
}# End smartTitle()

/**
 * adds links to associative nav array of $config object
 *
 * Will add link before or after current associative array, default is after 
 *
 * <code>
 * addLink('nav1','about_us.php','About Us','Here is a page About Us!','before'); #prepends link to $config->nav1  
 * </code>
 * 
 * added version 2.07
 *
 * @param string $property config object property as associative nav array (ie, nav1)
 * @param string $url address of page for nav array
 * @param string $text text for link for nav array
 * @param string $title onhover (optional) for link for nav array
 * @param string $position 'before' or 'after' to indicate where link goes in nav array
 * @return string converted title cased version of file name/string
 * @todo none
 */
function addLink($property = '',$url='',$text='',$title='',$position='after')
{
	global $config;
	if($title != ''){$text .= '~' . $title;} #add title if not empty
	$position = strtolower($position); #de-specify case
	switch($position)
	{#choices are before, after or replace
		case "before": #prepend link
		$config->$property = array($url=>$text) + $config->$property; 
		break;

		default: #add link after list
		$config->$property += array($url=>$text);
	}
}# End addLink()

/**
 * loads a quick user message (flash/heads up) to provide user feedback
 *
 * Uses a Session to store the data until the data is displayed via showFeedback() loaded 
 * inside the bottom of header_inc.php (or elsewhere) 
 *
 * <code>
 * feedback('Flash!  This is an important message!'); #will show up next running of showFeedback()
 * </code>
 * 
 * added version 2.07
 *
 * @param string $msg message to show next time showFeedback() is invoked
 * @return none 
 * @see showFeedback() 
 * @todo none
 */

#flash message is a temporary message sent to the user
#load it here and show it one time when showFeedback() is called
function feedback($msg,$level="warning")
{
	startSession();
	$_SESSION['feedback'] = $msg;
	$_SESSION['feedback-level'] = $level;

}

/**
 * shows a quick user message (flash/heads up) to provide user feedback
 *
 * Uses a Session to store the data until the data is displayed via showFeedback()
 *
 * Related feedback() function used to store message 
 *
 * <code>
 * echo showFeedback(); #will show then clear message stored via feedback()
 * </code>
 * 
 * changed from showFeedback() version 2.10
 *
 * @param none 
 * @return string html & potentially CSS to style feedback
 * @see feedback() 
 * @todo none
 */
function showFeedback()
{
	startSession();//startSession() does not return true in INTL APP!
	
	$myReturn = "";  //init
	if(isset($_SESSION['feedback']) && $_SESSION['feedback'] != "")
	{#show message, clear flash
		if(defined('THEME_PHYSICAL') && file_exists(THEME_PHYSICAL . 'feedback.css'))
		{//check to see if feedback.css exists - if it does use that
			$myReturn .= '<link href="' . THEME_PATH . 'feedback.css" rel="stylesheet" type="text/css" />' . PHP_EOL;
		}else{//create css for feedback
			$myReturn .= 
				'
				<style type="text/css">
				.feedback {  /* default style for div */
					border: 1px solid #000;
					margin:auto;
					width:100%;
					text-align:center;
					font-weight: bold;
				}
			
				.error {
				  color: #000;
				  background-color: #ee5f5b; /* error color */
				}
			
				.warning {
				  color: #000;
				  background-color: #f89406; /* warning color */
				}
			
				.notice {
				  color: #000;
				  background-color: #5bc0de; /* notice color */
				}
				
				.success {
				  color: #000;
				  background-color: #62c462; /* notice color */
				}
				</style>
				';
				
		}
	
		if(isset($_SESSION['feedback-level'])){$level = $_SESSION['feedback-level'];}else{$level = 'warning';}
		$myReturn .= '<div class="feedback ' . $level . '">'  . $_SESSION['feedback'] . '</div>';
		$_SESSION['feedback'] = ""; #cleared
		$_SESSION['feedback-level'] = "";
		return $myReturn; //data passed back for printing
		
	}
}
 

/**
 * returns a random item from an array sent to it.
 *
 * Uses count of array to determine highest lega random number.
 *
 * Used to show random HTML segments in sidebar, etc.
 *
 * <code>
 * $arr[] = '<img src="mypic1.jpg" />';
 * $arr[] = '<img src="mypic2.jpg" />';
 * $arr[] = '<img src="mypic3.jpg" />';  
 * echo randomize($arr); #will one of three random images
 * </code>
 * 
 * added version 2.09
 *
 * @param array array of HTML strings to display randomly
 * @return string HTML at random index of array
 * @see rotate() 
 * @todo none
 */
function randomize ($arr)
{//randomize function is called in the left sidebar - an example of random (on page reload)
	if(is_array($arr))
	{//Generate random item from array and return it
		return $arr[mt_rand(0, count($arr) - 1)];
	}else{
		return $arr;
	}
}#end randomize()

/**
 * returns a daily item from an array sent to it.
 *
 * Uses count of array to determine highest legal rotated item.
 *
 * Uses day of month and modulus to rotate through daily items in sidebar, etc.
 *
 * <code>
 * $arr[] = '<img src="mypic1.jpg" />';
 * $arr[] = '<img src="mypic2.jpg" />';
 * $arr[] = '<img src="mypic3.jpg" />';  
 * echo rotate($arr); #will return a different image each day for three days
 * </code>
 * 
 * added version 2.09
 *
 * @param array array of HTML strings to display on a daily rotation
 * @return string HTML at specific index of array based on day of month
 * @see rotate() 
 * @todo none
 */
function rotate ($arr)
{//rotate function is called in the right sidebar - an example of rotation (on day of month)
	if(is_array($arr))
	{//Generate random item from array and return it
		return $arr[((int)date("j")) % count($arr)];
	}else{
		return $arr;
	}
}#end rotate

/**
 * Provides active connection to MySQL DB.
 *
 * A set of default credentials should be placed in the conn() function, and optional 
 * levels of access can be chosen on a case by case basis on specific pages.  
 *
 * One of 5 strings indicating a MySQL user can be passed to the function  
 *
 * 1 admin
 * 2 delete
 * 3 insert
 * 4 update
 * 5 select
 *  
 * MySQL accounts must be setup for each level, with 'select' account only able 
 * to access db via 'select' command, and update able to 'select' and 'update' etc. 
 * Each credential set must exist in MySQL before it can be used.
 *
 * If no data is entered into conn() function when it is called, a mysqli connection with the 
 * default access is returned:
 *
 *<code>
 * $myConn = conn();
 *</code>
 *
 * If you create multiple MySQL users and have a 'select only' user, you can create a 'select only' connection:
 *
 * <code>
 * $myConn = conn("select");
 * </code>
 *
 * You can also create a mysql classic (mysql) connection by declaring FALSE as a second optional argument:
 *
 * <code>
 * $iConn = conn("select",FALSE);
 * </code>
 *
 * There are times you may want to use a mysql classic connnection over mysqli for security or compatibility
 *
 * @param string $access represents level of access
 * @param boolean $improved If TRUE, uses mysqli improved connection 
 * @return object Returns active connection to MySQL db.
 * @todo error logging, or emailing admin not implemented
 */ 

function conn($access="",$improved = TRUE)
{
	$myUserName = "";
	$myPassword = "";
	
	if($access != "")
	{#only check access if overwritten in function
		switch(strtolower($access))
		{# Optionally overwrite access level via function
			case "admin":	
				$myUserName = ""; #your MySQL username
				$myPassword = ""; #your MySQL password	
				break;
			case "delete":	
				$myUserName = ""; 
				$myPassword = ""; 
				break;	
			case "insert":	
				$myUserName = ""; 
				$myPassword = ""; 
				break;
			case "update":	
				$myUserName = ""; 
				$myPassword = ""; 
				break;
			case "select":	
				$myUserName = ""; 
				$myPassword = ""; 
				break;		
			
		}
	}
	
	if($myUserName == ""){$myUserName = DB_USER;}#fallback to constants
	if($myPassword == ""){$myPassword = DB_PASSWORD;}#fallback to constants
	if($improved)
	{//create mysqli improved connection
		$myConn = @mysqli_connect(DB_HOST, $myUserName, $myPassword, DB_NAME) or die(trigger_error(mysqli_connect_error(), E_USER_ERROR));
	}else{//create standard connection
		$myConn = @mysql_connect(DB_HOST,$myUserName,$myPassword) or die(trigger_error(mysql_error(), E_USER_ERROR));
		@mysql_select_db(DB_NAME, $myConn) or die(trigger_error(mysql_error(), E_USER_ERROR));
	}
	return $myConn;
}

/** 
 * Placing the DB connection inside a class allows us to create a shared 
 * connection to improve use of resources.
 *
 * Returns a mysqli connection:
 *
 * <code>
 * $iConn = IDB::conn();
 * </code>
 *
 * All calls to this class will use the same shared connection.
 * 
 */ 

class IDB 
{ 
	private static $instance = null; #stores a reference to this class

	private function __construct() 
	{#establishes a mysqli connection - private constructor prevents direct instance creation 
		#hostname, username, password, database
		$this->dbHandle = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD, DB_NAME) or die(trigger_error(mysqli_connect_error(), E_USER_ERROR)); 
	} 

	/** 
	* Creates a single instance of the database connection 
	* 
	* @return object singleton instance of the database connection
	* @access public 
	*/ 
	public static function conn() 
    { 
      if(self::$instance == null){self::$instance = new self;}#only create instance if does not exist
      return self::$instance->dbHandle;
    }
}

function pdo()
{//return PDO object
/*
PDO & SQL Injection: 
PDO tutorial: http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers
*/
	try {
	   $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',DB_USER,DB_PASSWORD);
	} catch(PDOException $ex) {
	   trigger_error($ex->getMessage(), E_USER_ERROR);
	}
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//make errors catchable
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);//disable emulated prepared statements

	return $db;

}

/*
$today = date("Y-m-d H:i:s");
$to = 'bill@example.com';
$subject = 'Test Email, No ReplyTo: ' . $today;
$message = '
	Test Message Here.  Below should be a carriage return or two: ' . PHP_EOL . PHP_EOL .
	'Here is some more text.  Hopefully BELOW the carriage return!
';
*/

/**
 * adds links to associative nav array of $config object
 *
 * Will add link before or after current associative array, default is after 
 *
 * <code>
 * $today = date("Y-m-d H:i:s");
 * $to = 'client@example.com';
 * $subject = 'Test Email, No ReplyTo, Text, not HTML format: ' . $today;
 * $message = '
 * 	Test Message Here.  Below should be a carriage return or two: ' . PHP_EOL . PHP_EOL .
 * 	'Here is some more text.  Hopefully BELOW the carriage return!';
 *
 * safeEmail($to, $subject, $message,'','');//replyTo and contentType are eliminated here
 * </code>
 * 
 * added version 2.07
 *
 * @param string $to email address where message will be received
 * @param string $subject message shown in header of email
 * @param string $message body of email
 * @param string $replyTo (optional) used for reply to so client can respond to user
 * @param string $contentType(optional) defaults to HTML
 * @return boolean true or false to indicate if PHP found an error while trying to send email
 * @todo none
*/

function safeEmail($to, $subject, $message, $replyTo='',$contentType='text/html; charset=ISO-8859-1 ')
{#builds and sends a safe email, using Reply-To properly!
	$fromDomain = $_SERVER["SERVER_NAME"];
	$fromAddress = "noreply@" . $fromDomain; //form always submits from domain where form resides

	if($replyTo==''){$replyTo='';}

	$headers = 'From: ' . $fromAddress . PHP_EOL .
		'Content-Type: ' . $contentType . PHP_EOL .
		'Reply-To: ' . $replyTo . PHP_EOL .
		'X-Mailer: PHP/' . phpversion();
	return mail($to, $subject, $message, $headers);
}

/**
 * requires POST or GET params or redirect, etc. back to calling form or 
 * safe page
 *
 * <code>
 * $params = array('last_name','first_name','email');#required fields to register	
 * 
 * if(!required_params($params,true))
 * {//abort - required fields not sent
 *		feedback("Data not entered/updated. (error code #" . createErrorCode(THIS_PAGE,__LINE__) . ")","error");
 *		myRedirect(VIRTUAL_PATH . 'index.php');
 * 	die();
 * }
 * </code>
 *
 * @param array names of all POST/GET required fields
 * @param boolean if true, only allow the passed in params, no others
 * @return void
 * @todo none 
 */
 
 function required_params($params,$exclusive=false) {
	foreach($params as $param) {
		if(!isset($_POST[$param])) {
			return false;
		}
	}
	if($exclusive)
	{//if any field submitted is different from required params, disallow
		foreach($_POST as $name => $value)
		{
			if(!in_array($name,$params)){return false;}
		}
	}
	return true;
}#end required_params()
