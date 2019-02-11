<?php
//MyAutoLoader.php

/**
 * This class stores a collection of static methods to load any number of 
 * library configurations.
 *
 * It must be referenced inside the config file to be active:
 *
 * <code>
 * include INCLUDE_PATH . 'MyAutoLoader.php'; #Allows multiple versions of AutoLoaded classes
 * </code>
 *
 * Each method must be registered before a class is called. 
 * 
 * Registration looks like the following: 
 *
 * <code>
 * spl_autoload_register('MyAutoLoader::NamespaceLoader');
 * </code>
 */

class MyAutoLoader
{
    /**
	 * Uses context of calling file's relative path to call 
	 * a class in a relative sub-folder accessible by it's namespace:
	 * 
	 * <code>
	 * $mySurvey = new SurveySez\Survey(1);
	 * </code>
	 */
	public static function NamespaceLoader($class)
    {
        //namespaces use backslashes, file paths use forward slashes in UNIX.  
		//we convert them here, but use a constant to remain platform independent
		$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $class);
        $path = $path . '.php';

		//if file exists, include and load class file
		if (file_exists($path)) {
			include $path;
			return; //go no farther
		}
    }#end NamespaceLoader()
	
    /**
	 * Adds a default check to the inc_0700 folder of the current app
     * 
     * This will pick up any class inside the folder as long as not namespaced	
	 */

    public static function IncludePathLoader($class)
    {
        //namespaces use backslashes, file paths use forward.  Swap them here
		$class = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $class);
		
		//add the path to the folder we're working in to the physical path
        $class_path = INCLUDE_PATH . $class . '.php';
		$legacy_path = INCLUDE_PATH . $class . '_inc.php';
        
		//if file exists, include and load class file
		if (file_exists($class_path)) {
			include $class_path;
			return;
		}
		
		//also check _inc.php version of file name as that was legacy
		if (file_exists($legacy_path)) {
			include $legacy_path;
			return;
		}
    }#end IncludePathLoader()

}#end MyAutoLoader class

spl_autoload_register('MyAutoLoader::IncludePathLoader');#Always check inc_0700 folder for classes	