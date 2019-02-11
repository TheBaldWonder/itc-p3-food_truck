<?php
/**
 * footer_inc.php provides the right panel and footer for our site pages 
 *
 * Includes dynamic copyright data 
 *
 * @package nmCommon
 * @author Bill Newman <williamnewman@gmail.com>
 * @version 2.091 2011/06/17
 * @link http://www.newmanix.com/  
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @see template.php
 * @see header_inc.php 
 * @todo none
 */
?>
	  <!-- footer include starts here -->
	  </td>
	  <!-- right panel starts here -->	
	  <!-- change right panel color here -->
      	<td width="175" valign="top">
		<? echo $config->sidebar2; ?>
        </td>
	</tr>
      <!-- change footer color here -->
	<tr>
		<td colspan="3">
		    <p align="center"><b>Footer Goes Here!</b></p>
			<p align="center">Always include some sort of copyright notice, for example:</p>
	        <p align="center"><em>&copy; My Company, 2007 - <?php echo date("Y");?></em></p>
		</td>
  </tr>
</table>
</body>
</html>