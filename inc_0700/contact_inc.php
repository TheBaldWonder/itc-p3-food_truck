<?php
/**
 * contact_include.php hides the messy code that supports contact.php 
 *
 * contact.php is a postback application designed to provide a 
 * contact form for users to email our clients.  contact.php references 
 * recaptchalib.php as an include file which provides all the web service plumbing 
 * to connect and serve up the CAPTCHA image and verify we have a human entering data.
 *
 * v 2.0 adds support for email headers to avoid spam trap via function, email_handler()
 *
 * Only the form elements 'Email' and 'Name' are significant.  Any other form 
 * elements added, with any name or type (radio, checkbox, select, etc.) will be delivered via  
 * email with user entered data.  Form elements named with underscores like: "How_We_Heard" 
 * will be replaced with spaces to allow for a better formatted email:
 *
 * <code>
 * How We Heard: Internet
 * </code>
 *
 * If checkboxes are used, place "[]" at the end of each checkbox name, or PHP will not deliver 
 * multiple items, only the last item checked:
 *
 * <code>
 * <input type="checkbox" name="Interested_In[]" value="New Website" /> New Website <br />
 * <input type="checkbox" name="Interested_In[]" value="Website Redesign" /> Website Redesign <br />
 * <input type="checkbox" name="Interested_In[]" value="Lollipops" /> Complimentary Lollipops <br />
 * </code>
 *
 * The CAPTCHA is handled by reCAPTCHA requiring an API key for each separate domain. 
 * Get your reCAPTCHA private/public keys from: http://recaptcha.net/api/getkey
 *
 * Place your target email in the $toAddress variable.  Place a default 'noreply' email address 
 * for your domain in the $fromAddress variable.
 *
 * After testing, change the variable $sendEmail to TRUE to send email.
 *
 * Tech Stuff: To retain data entered during an incorrect CAPTCHA, POST data is embedded in JS array via a 
 * PHP function sendPOSTtoJS().  On page load a JS function named loadElements() matches the 
 * embedded JS array to the form elements on the page, and reloads all user data into the
 * form elements. 
 *
 * @package nmCAPTCHA
 * @author Bill Newman <williamnewman@gmail.com>
 * @version 2.2 2013/02/02
 * @link http://www.newmanix.com/
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @see demo_contact.php  
 * @see util.js
 * @see recaptchalib.php   
 * @todo none
 */

/**
 * handles POST data and formulates email response.
 * 
 * @param string $skipFields comma separated string of POST elements to be skipped
 * @param boolean $sendEmail indicates whether developer wants email sent or not
 * @param string $fromAddress fallback 'noreply' address for domain hosting page
 * @param string $toAddress address to receive email
 * @param string $website name of website where form was filled out
 * @param string $fromDomain name of website where form was filled out     
 * @return none 
 * @uses show_POST()
 * @todo none
 */
function handle_POST($skipFields,$sendEmail,$toName,$fromAddress,$toAddress,$website,$fromDomain)
{
	$aSkip = explode(",",$skipFields); #split form elements to skip into array
	$postData = show_POST($aSkip);#loops through and creates select POST data for display/email
	$fromAddress = "";//default
	if(is_email($_POST['Email']))
	{#Only use Email for return address if valid
		$fromAddress = $_POST['Email'];
		# extra email injector paranoia courtesy of DH: http://wiki.dreamhost.com/PHP_mail()#Mail_Header_Injection
		$fromAddress = preg_replace("([\r\n])", "", $fromAddress);
	}
	
	

    if($sendEmail)
	{#create email
		if(isset($_POST['Name'])){$Name = $_POST['Name'];}else{$Name = "";} #Name, if used part of subject
		
		foreach( $_POST as $value ){#Content-Type: is too similar to email injection to allow
		  $spaceless = str_replace(" ","",$value);#in case hacker is clever enough to remove spaces
		  if( stripos($spaceless,'Content-Type:') !== FALSE ){
			feedback("Incorrect form data. Email NOT sent. (error code #" . createErrorCode(THIS_PAGE,__LINE__) . ")","error");
			myRedirect(THIS_PAGE);
		  }
		}
		$Name = safe($Name);#Name is part of Subject/header - filter code further for email injection 
		
		if($Name != ""){$SubjectName = " from: " . $Name . ",";}else{$SubjectName = "";} #Name, if used part of subject
		$postData = str_replace("<br />",PHP_EOL . PHP_EOL,$postData);#replace <br /> tags with double c/r
    	$Subject= $website . " message" . $SubjectName . " " . date('F j, Y g:i a');
		$txt =  $Subject . PHP_EOL . PHP_EOL  . $postData;                        

		email_handler($toAddress,$toName,$Subject,$txt,$fromAddress,$Name,$website,$fromDomain);
	}else{//print data only
    	print "Data printed only.  Email <b>not</b> sent!<br />";
    	echo $postData; #Shows select POST data
		echo '<a href="' . THIS_PAGE . '">Reset Form</a><br />';
	}

}#end handlePOST()

/**
 * formats PHP POST data to text for email, feedback
 * 
 * @param Array $aSkip array of POST elements to be skipped
 * @return string text of all POST elements & data, underscores removed
 * @todo none
 */
function show_POST($aSkip)
{#formats PHP POST data to text for email, feedback
	$myReturn = ""; #init return var
	foreach($_POST as $varName=> $value)
	{#loop POST vars to create JS array on the current page - include email
	 	if(!in_array($varName,$aSkip) || $varName == 'Email')
	 	{#skip passover elements
	 		$strippedVarName = str_replace("_"," ",$varName);#remove underscores
			if(is_array($_POST[$varName]))
		 	{#checkboxes are arrays, and we need to loop through each checked item to insert
		 	    $myReturn .= $strippedVarName . ": " . sanitize_it(implode(",",$_POST[$varName])) . "<br />";
	 		}else{//not an array, create line
	 			$strippedValue = nl_2br2($value); #turn c/r to <br />
	 			$strippedValue = str_replace("<br />","~!~!~",$strippedValue);#change <br /> to our 'unique' string: "~!~!~"
	 			//sanitize_it() function commented out as it can cause errors - see word doc
	 			//$strippedValue = sanitize_it($strippedValue); #remove hacker bits, etc. 
	 			$strippedValue = str_replace("~!~!~","\n",$strippedValue);#our 'unique string changed to line break
	 			$myReturn .= $strippedVarName . ": " . $strippedValue . "<br />"; #
	 		}
		}
	}
	return $myReturn;
}#end show_POST()

/**
 * sends PHP POST data to a JS array, where it will be picked up and   
 * matched to form elements, then elements will be reloaded via JS loadElement()
 * 
 * @param string $skipFields comma separated string of POST elements to be skipped
 * @return none
 * @todo none
 */
function send_POSTtoJS($skipFields)
{#sends PHP POST data to a JS array, where it will be picked up and matched to form elements, then elements will be reloaded
	$aSkip = explode(",",$skipFields); #split form elements to skip into array
	echo '<script type="text/javascript">';
	echo 'var POST = new Array();'; #JS Array is named POST
	foreach($_POST as $varName=> $value)
	{#loop POST vars to create JS array on the current page
	 	if(!in_array($varName,$aSkip)|| $varName == 'Email')
	 	{#skip passover elements - all except Email!
			if(is_array($_POST[$varName]))
		 	{#checkboxes are arrays, and we need to loop through each checked item to insert
		 	    echo 'POST["' . $varName . '"] = new Array();';
		 		foreach($_POST[$varName] as $key=>$val)
		 		{#here we have an array as an element of an array
		 	    	echo 'POST["' . $varName . '"][' . $key . ']="' . $val . '";';
		 		}
	 		}else{//not an array, so likely text, radio or select
	 			echo 'POST["' . $varName . '"] = "' .  nl_2br2($value) . '";'; #nl_2br2() changes c/r to <br /> on the fly, helps JS array!
	 		}
		}
	 		
	}
	echo 'addOnload(loadElements);'; #loadElements in util.js will match form objects to POST array
	echo '</scr';
	echo 'ipt>';	
}#end send_POSTtoJS()


/**
 * Strips tags & extraneous stuff, leaving text, numbers, punctuation.  
 *
 * Not recommended for databases, but since we're only sending email,
 * this is hopefully better than nothing
 *
 * Change in version 1.11 is to use spaces as replacement instead of empty strings
 *
 * @param string $str data as entered by user
 * @return data returned after 'sanitized'
 * @todo none
 */
function sanitize_it($str)
{#We would like to trust the user, and aren't using a DB, but we'll limit input to alphanumerics & punctuation
	$str = strip_tags($str); #remove HTML & script tags	
	$str = preg_replace("/[^[:alnum:][:punct:]]/"," ",$str);  #allow alphanumerics & punctuation - convert the rest to single spaces
	return $str;
}#end sanitize_it()

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
function is_email($myString)
{
  if(preg_match("/^[a-zA-Z0-9_\-\.]+@[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+$/",$myString))
  {return true;}else{return false;}
}#end is_email()

/**
 * br2nl() changes '<br />' tags  to '\n' (newline)  
 * Preserves user formatting for preload of <textarea>
 *
 * <code>
 * $myText = br_2nl($myText); # <br /> changed to \n
 * </code>
 *
 * @param string $text Data from DB to be loaded into <textarea>
 * @return string Data stripped of <br /> tag variations, replaced with new line 
 * @todo none 
 */
function br_2nl($text)
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
}#end br2nl()

/**
 * nl2br2() changes '\n' (newline)  to '<br />' tags
 * Break tags can be stored in DB and used on page to replicate user formatting
 * Use on input/update into DB from forms
 *
 * <code>
 * $myText = nl_2br2($myText); # \n changed to <br />
 * </code>
 * 
 * @param string $text Data from DB to be loaded into <textarea>
 * @return string Data stripped of <br /> tag variations, replaced with new line 
 * @todo none
 */
function nl_2br2($text)
{
	$text = str_replace(array("\r\n", "\r", "\n"), "<br />", $text);
	return $text;
}#end nl2br2()

function email_handler($toEmail,$toName,$subject,$body,$fromEmail,$fromName,$website,$domain,$bccEmail = '',$bccName = '')
{
	$debug=false;//true may show message
	if($fromName==""){$fromName = $website;} //default to website if name not provided
	$headers   = array();
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/plain; charset=iso-8859-1";
	$headers[] = "From: {$fromName} <noreply@{$domain}>";
	if(isset($bccEmail) && $bccEmail != "")
	{//only add BCC info if applicable
		$bccArray = array(); //init
		$bccEmail = explode(',',$bccEmail);
		$bccName = explode(',',$bccName);
		if(count($bccEmail)==count($bccName))
		{//only create formatted "to" section if matches
			for($x=0;$x<count($bccEmail);$x++)
			{//comma separated emails all formatted!
				$bccArray[] = $bccName[$x] . ' <' . $bccEmail[$x]. '>';	
			}
			$bccEmail = implode(',', $bccArray);
		}
		$headers[] = "Bcc:" . implode(',', $bccArray);
	}
	if(isset($fromEmail) && $fromEmail != "")
	{//only add reply info if provided
		$headers[] = "Reply-To: {$fromName} <{$fromEmail}>";
	}else{
		$headers[] = "Reply-To: No Reply <noreply@{$domain}>";
	}
	$headers[] = "Subject: {$subject}";
	$headers[] = "X-Mailer: PHP/".phpversion();
	
	if(isset($toName) && $toName != "")
	{//to name must be filled out, and emails
		$emailArray = array(); //init
		$toEmail = explode(',',$toEmail);
		$toName = explode(',',$toName);
		if(count($toEmail)==count($toName))
		{//only create formatted "to" section if matches
			for($x=0;$x<count($toEmail);$x++)
			{//comma separated emails all formatted!
				$emailArray[] = $toName[$x] . ' <' . $toEmail[$x]. '>';	
			}
			$toEmail = implode(',', $emailArray);
		}
	}
	
	if(@mail($toEmail, $subject, $body, implode(PHP_EOL, $headers)))
	{//only echo if debug is true
		if($debug){echo 'Email sent! ' . date("m/d/y, g:i A");}
	}else{
		if($debug){echo 'Email NOT sent! Unknown error. ' . date("m/d/y, g:i A");}	
	}	

}

//http://www.nyphp.org/phundamentals/8_Preventing-Email-Header-Injection
function safe( $name ) {
   return( str_ireplace(array( "\r", "\n", "%0a", "%0d", "Content-Type:", "bcc:","to:","cc:" ), "", $name ) );
}

?>