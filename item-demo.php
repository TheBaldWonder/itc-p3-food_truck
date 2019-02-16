<?php
// ********************************************************* //
// *                                                       * //
// * item_demo.php                                         * //
// *                                                       * //
// * Tijuana Taco Truck Landing Page                       * //
// *                                                       * //
// * @package Taco Truck                                   * //
// * @author Group 3 <emorri08@seattlecentral.edu>         * //
// * @version 1.3 2019/02/12                               * //
// * @link http://ellycodes.com/                           * //
// * @license https://www.apache.org/licenses/LICENSE-2.0  * //
// *                                                       * //
// ********************************************************* //

require 'inc_0700/config_inc.php';
include 'items.php'; 
# Read the value of 'action' whether it is passed via $_POST or $_GET with $_REQUEST
if(isset($_REQUEST['act'])){$myAction = (trim($_REQUEST['act']));}else{$myAction = "";}
switch ($myAction) //check 'act' for type of process
{ 
    case "display": # 2)Display user's order total!
        showData();
        break;
        
    default: # 1)Ask user to place order 
        showForm();
} //end switch
function showForm() # shows form so user can order from the food truck.
{
    global $config;
    get_header(); #defaults to header_inc.php	
	
    echo 
        '<script type="text/javascript" src="' . VIRTUAL_PATH . 'include/util.js"></script>
        <script type="text/javascript">
        function checkForm(thisForm)
		{ //check form data for valid info
            if(empty(thisForm.YourName,"Please Enter Your Name")){return false;}
            return true; //if all is passed, submit!
		}
	</script> 
	<form action="' . THIS_PAGE . '" method="post" onsubmit="return checkForm(this);">
             ';
  
    foreach($config->items as $item)
    {       
        echo '<p><strong>' . $item->Name . '</strong></p>';
        
        echo '<p style="padding-left: 15px";><strong>$ ' . number_format($item->Price, 2) . '</strong></p>';
            
        echo '<p>' . $item->Description . '</p>';
            
        echo '<p>Order Amount <input type="number" min="0" name="item_' . $item->ID . '" /></p>';
    } //end foreach($config->items as $item)      
    
        echo '
            <p>
				<input type="submit" value="Get Order Total">
            </p>
            <input type="hidden" name="act" value="display" />
        </form>
	   ';
	get_footer(); #defaults to footer_inc.php
} //end showForm()
function showData() #form submits here we show itmes ordered
{ 
    get_header(); #defaults to footer_inc.php
	
    echo '<h3 align="center">Your Order</h3>';
    
    $order_subtotal = 0;
 	foreach($_POST as $name => $value)//loop the form elements 
    {      
        
        if(substr($name,0,5)=='item_') //if form name attribute starts with 'item_', process it
        {   //explode the string into an array on the "_"
            $name_array = explode('_',$name);
            //id is the second element of the array -- forcibly cast to an int in the process
            $id = (int)$name_array[1];
            
            $thisItem = getItem($id);
		
		
		 /*if(!is_numeric($value))  //<<<<<<-------ISSUES
	{//data must be numeric 
		feedback("Please Enter a whole number");
		myRedirect(THIS_PAGE);
         }*/
		
		
            if($value!=""){
                (float)$subtotal=$value*$thisItem->Price;
                
                $order_subtotal += $subtotal;
                
                echo "<p>You ordered $value $thisItem->Name(s) which costs $" . number_format($subtotal, 2) . "</p>";
                
            } //end nested if($value!="")
         
        } // end if(substr($name,0,5)=='item_')   
    } //end foreach
    
    $tax = $order_subtotal * .12;
    $total = $order_subtotal + $tax;
    
    echo '<p>Your order subtotal is: $' . number_format($order_subtotal,2) . '</p>';
    echo '<p>Your order tax is: $' . number_format($tax,2) . '</p>';
    echo '<p>Your order total is: $' . number_format($total,2) . '</p>';
    
    echo '<p align="center"><a href="' . THIS_PAGE . '">RESET</a></p>';
	get_footer(); #defaults to footer_inc.php
} //end showData
function getItem($id) {
    global $config;
    
    foreach($config->items as $item){
        if($item->ID==$id){
            return $item; 
        } //end if
    } // end foreach
} // end getItem function
