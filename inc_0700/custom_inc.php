<?php
/**
 * custom_inc.php stores custom functions specific to your application
 * 
 * Keeping common_inc.php clear of your functions allows you to upgrade without conflict
 * 
 * @package nmCommon
 * @author Bill Newman <williamnewman@gmail.com>
 * @version 2.091 2011/06/17
 * @link http://www.newmanix.com/  
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @todo add safeEmail to common_inc.php
 */
 
/**
 * Place your custom functions below so you can upgrade common_inc.php without trashing 
 * your custom functions.
 *
 * An example function is commented out below as a documentation example  
 *
 * View common_inc.php for many more examples of documentation and starting 
 * points for building your own functions!
 */ 

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

/* 
function onlyAlphaEXAMPLE($myString)
{
  if(preg_match("/[^a-zA-Z]/",$myString))
  {return false;}else{return true;} //opposite logic from email?  
}#end onlyAlpha() 
*/ 