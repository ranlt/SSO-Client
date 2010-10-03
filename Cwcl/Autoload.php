<?php
/**
 * Cwcl_Autoload
 *
 * @category Cwcl
 * @package Cwcl_Autoload
 * @author Mikko Koppanen <mikko@ibuildings.com>
 * @author Lorenzo Alberton <lorenzo@ibuildings.com>
 */
class Cwcl_Autoload {
    
    /**
     * Registers the autoloading
     * 
     * @return void
     */
    public static function registerAutoload() {
        spl_autoload_register(array('Cwcl_Autoload', 'autoload'));
    }
    
    /**
     * Autoloading method
     * 
     * @param $className
     * @return void
     */
    public static function autoload($className){
        /* Do not try to load again if the class exists */
        if (class_exists($className, false)) {
            return;
        }
        
        if (strncmp($className, 'Zend_', 5) === 0) {
        	$key = 'internal_autoload::' . $className;
        } else {
	        if (defined('APPLICATION_PATH')) {
    	        $key = md5(APPLICATION_PATH) . '_autoload::' . $className;
        	} else {
            	$key = 'unknown_autoload::' . $className;
        	}
        }
        
        /* Would be more polite to use the cache abstraction but it might not be present
         * when this piece of code is executed for the first times */
        if ((($file = zend_shm_cache_fetch($key)) === false) || ($file === null)) {
            $classFile = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            /* stream_resolve_include_path would be a lot nicer,
             * but at the time of writing it's only in HEAD. */
            if (function_exists('stream_resolve_include_path')) {
                $file = stream_resolve_include_path($classFile);
            } else {
                foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
                    if (file_exists($path . '/' . $classFile)) {
                        $file = $path . '/' . $classFile;
                        break;
                    }
                }
            }
            /* Store the failure in case we are not in debug */
            if ($file === false) {
                if (defined('CW_DEBUG')) {
                    if (CW_DEBUG === false) {
                        zend_shm_cache_store($key, null, 86400);
                    }
                } else {
                    zend_shm_cache_store($key, null, 86400);
                }
            } else {
                zend_shm_cache_store($key, $file, 86400);
            }
        }
        /* If file is found, store it into the cache, classname <-> file association */
        if (($file !== false) && ($file !== null)) {
            include $file;
        }
    }
}
