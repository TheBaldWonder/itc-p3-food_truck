<?php
/**
 * Pager_inc.php stores Pager class for paging records
 * 
 * The Pager class creates simple records paging by deconstructing the existing 
 * SQL statement and adding MySQL limits to the statement.
 * 
 * Once the Pager object is loaded with the SQL statement, a method named 'showTotal()' 
 * returns the possible number of records, and another named 'showNav()' places the 
 * Paging Nav (next & previous arrows, etc.) on the page.
 *
 * 3/4/2012 - removed rc, record count being stored on querystring
 *
 * @package nmPager
 * @author Bill Newman <williamnewman@gmail.com>
 * @version 3.03 2012/03/04
 * @link http://www.newmanix.com/ 
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @todo none
 */

class Pager
{
	/**
	 * encapsulates an easy to use, query string based records paging system
	 *
	 * Once the Pager object is loaded with the SQL statement, a method named 'showTotal()' 
	 * returns the possible number of records, and another named 'showNav()' places the 
	 * Paging Nav (next & previous arrows, etc.) on the page.
	 *
	 * A pager can be instantiated minimally by declaring the number of records per page:
	 *
	 * <code>
	 * $myPager = new Pager(10);
	 * </code>
	 *
	 * However for the pager to operate properly, the SQL statement to be used on the page must be
	 * passed through the pager to determine the number of pages required:
	 * <code>
	 * $sql = $myPager->loadSQL($sql);  //adapt existing SQL statement
	 * </code>
	 */
		
	/**
	 * The following variables are the default button implementations.  
	 * These can be overridden during instantiation of the object.
	 */
	private $first = '&lt;&lt;'; //internal variables are declared as private.  Not available outside class  
	private $prev = '&lt;';
	private $next = '&gt;';
	private $last = '&gt;&gt;';
	
	//uncomment, to declare image default arrow implementations here:
	/*
	private $first = '<img src="' . VIRTUAL_PATH . 'images/arrow_first.gif" border="0" />';
	private $prev = '<img src="' . VIRTUAL_PATH . 'images/arrow_prev.gif" border="0" />';
	private $next = '<img src="' . VIRTUAL_PATH . 'images/arrow_next.gif" border="0" />';
	private $last = '<img src="' . VIRTUAL_PATH . 'images/arrow_last.gif" border="0" />';
	*/
	
	private $sqlLoaded = FALSE; #When SQL is loaded this is true.  If false, Pager is not ready
	
	/**
	 * Constructor function identifies parameters of the pager object upon creation
	 * 
	 * Constructor allows a simple default configuration, which applies simple text 'arrows'
	 * and 10 records per page.  Upon creation the developer can identify a different number of 
	 * records per page, and implement images for arrows or text in any combination
	 * 
	 * The minimal example creates the pager object with 10 records and default 'first'
	 * 'prev', 'next', 'last' arrows: 
	 *
	 * <code>
	 * $myPager = new pager(10);
	 * </code>
	 *
	 * This example instantiates the pager object with 20 records and images for previous & next
	 * arrows, and no first & last arrows:
	 *  
	 * <code>
	 * $myPager = new pager(20,'','<img src="images/arrow_prev.gif" border="0" />','<img src="images/arrow_next.gif" border="0" />','');
	 * </code>
	 *
	 * Note the use of single quotes, '', to indicate no 'first' or 'last' icon above
	 * 
	 * @param integer $rowsPerPage maximum number of records per page
	 * @param string $first img HTML or chars like: << (&lt;&lt;)
	 * @param string $prev img HTML or chars like: < (&lt;)
	 * @param string $next img HTML or chars like: > (&gt;)
	 * @param string $last img HTML or chars like: >> (&gt;&gt;)
	 */
	function __construct($rowsPerPage,$first='&lt;&lt;',$prev='&lt;',$next='&gt;',$last='&gt;&gt;')
	//function __construct($rowsPerPage,$first,$prev,$next,$last)
	{//constructor sets stage by adding variables to object
		$this->rowsPerPage = $rowsPerPage;
		if(isset($first)){$this->first = $first;}
		if(isset($prev)){$this->prev = $prev;}
		if(isset($next)){$this->next = $next;}
		if(isset($last)){$this->last = $last;}
		//use get var, 'page' to track current page
		if(isset($_GET['pg']) && is_numeric($_GET['pg'])){$this->pageNum = $_GET['pg'];}else{$this->pageNum = 1;}
	}

	/**
	 * For the pager to work, the class must adapt the SQL statement to be used.
	 *
	 * Since MySQL limits the number of records returned, this function will 
	 * disassemble the SQL statement and re-assemble it to retrieve the total 
	 * number of records per page.
	 *
	 * The adapted SQL statement is returned to be used by the page.
	 *
	 * This step is required for the Pager to operate.
	 *
	 * <code>
	 * $myPager = new Pager(10); //create new pager object
	 * $sql = $myPager->loadSQL($sql);  //adapt existing SQL statement
	 * </code>
	 *
	 * IMPORTANT: The pager needs to adapt the SQL BEFORE the SQL statement is used by the page.
	 * 
	 * @param string $sql The SQL statement to provide the number of records involved
	 * @return string The adapted SQL statement to be used by the page.
	 */
	
	public function loadSQL($sql)
	{//SQL statement must be loaded to extract the numrows
		$this->sqlLoaded = TRUE; #was getting errors if SQL was not loaded
		$sql = str_replace(";","",$sql); //remove semi-colons
	    # create mysqli (improved) connection to MySQL
		$iConn = IDB::conn();
		$testsql = strtolower($sql); //make lowercase to test for ' from '. Use original SQL to keep case
		$findFrom = strrpos($testsql," from "); //find ' from ' in select statement
		
		#Receiving an error on the following line means your SQL statement must include a single space around 'FROM' to rebuild your SQL;
		if(!$findFrom){die(trigger_error("SQL statement must include a single space on each side of the SQL keyword ' from '", E_USER_ERROR));}

		$myFrom = substr($sql,$findFrom + 1);  //eliminate select fields so we can re-create count sql
		$rowsql   = "SELECT COUNT(*) AS numrows " . $myFrom;//rows in db
		$result  = mysqli_query($iConn,$rowsql) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		list($this->numrows) = mysqli_fetch_row($result) or die(trigger_error(mysqli_error($iConn), E_USER_ERROR));
		$myOffset = ($this->pageNum - 1) * $this->rowsPerPage;//get page offset
		return $sql . " LIMIT  " . $myOffset . ", " . $this->rowsPerPage; //add on limiting	
	}
	
	/**
	 * Public function returns total number of records.
	 *
	 * Can be used on page to tell user how many results are available.
	 *
	 * @return integer The total number of possible records available to the original SQL statement 
	 */
	public function showTotal()
	{//return total number of records
		#Receiving an error on the following line means your page didn't pass the SQL statement through the method: $sql = $myPager->loadSQL($sql);
		if($this->sqlLoaded){return $this->numrows;}else {die(trigger_error("SQL statement not processed by Pager::loadSQL()", E_USER_ERROR));}
	}
	
	/**
	 * calling this function shows the paging 'nav' element, if there are enough records
	 *
	 * Will return an empty string if not enough records to meet minimum number of records to 
	 * require paging.
	 *
	 * Minimal implementation:
	 * <code>
	 * $myPager->showNav();
	 * </code>
	 * 
	 * This example shows how to place our own prefix & suffix to 'wrap' the HTML and 
	 * apply formatting to pager Nav 
	 *  
	 * <code>
	 * $myPager->showNav('<span class="myClass">','</span>');
	 * </code>
	 * 
	 * @param string $prefix optional string to show up before nav, if applicable
	 * @param string $suffix optional string to show up after nav, if applicable
	 * @return string the adjusted SQL statement, with 
	 */
	
	public function showNav($prefix='<div align="center">',$suffix='</div>')
	{//creates the NAV icons for paging
		#Receiving an error on the following line means your page didn't pass the SQL statement through the method: $sql = $myPager->loadSQL($sql);
		if(!$this->sqlLoaded){die(trigger_error("SQL statement not processed by Pager::loadSQL()", E_USER_ERROR));}
		if($this->numrows > $this->rowsPerPage)
		{//show paging element, since more records than one page
		    $qstr = "";  //rebuild querystring for sorting passed on qstring, etc.
			foreach ($_GET as $varName=> $value)
			{
				switch($varName)
				{
					case "pg":  //don't re-add page/number of records
					  	break;
					default:   //rebuild rest of qstring
						$qstr .= "&" . $varName . "=" . $value;	 
				}	
			}
			
			$maxPage = ceil($this->numrows/$this->rowsPerPage); //total pages
			$self = $this->base_URL() . "/" . basename($_SERVER['PHP_SELF']);
			if ($this->pageNum > 1)
			{
				$page = $this->pageNum - 1;
				$this->prev = ' <a href="' . $self . '?pg=' . $page . $qstr .  '">' . $this->prev . '</a> ';
				if($this->first != ""){$this->first = ' <a href="' . $self . '?pg=1' . $qstr . '">' . $this->first . '</a> ';}
			}else{
				$this->prev = ''; // we're on page one, don't enable 'previous' link
				$this->first = ''; // nor 'first page' link
			}
			if ($this->pageNum < $maxPage)
			{// print 'next' link only if we're not on the last page
				$page = $this->pageNum + 1;
				$this->next = ' <a href="' . $self . '?pg=' . $page . $qstr . '">' . $this->next . '</a> ';
				if($this->last != ""){$this->last = ' <a href="'. $self . '?pg=' . $maxPage . $qstr . '">' . $this->last . '</a> ';}
			}else{
				$this->next = ''; // we're on the last page, don't enable 'next' link
				$this->last = ''; // nor 'last page' link
			}
			// print the page navigation link
			$myReturn = $prefix;
			$myReturn .= $this->first . $this->prev . ' Page <strong>' . $this->pageNum . '</strong> of <strong>' . $maxPage . '</strong> ' . $this->next . $this->last;
			$myReturn .= $suffix;
			return $myReturn;
		}else{
			return ""; //return empty string	
		}
	}# end showNav()
	
	/**
	 * Attempts to determine the virtual path to the application folder
	 *
	 * @return string virtual (relative) path
	 * @todo base_URL() may need to move to common_inc.php
	 * @todo base_URL() currently only supports http://, needs https also
	 */ 
	private function base_URL()
	{
		if(@isset($_SERVER["SCRIPT_URI"]))
		{//SCRIPT_URI is easiest version of virtual path
			return dirname($_SERVER["SCRIPT_URI"]);
		}else if(@isset($_SERVER["REQUEST_URI"])){//try the next most likely
			return "http://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER['REQUEST_URI']);
		}else{//ok, windows?
			return "http://" . $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
		}
	}
}
?>

