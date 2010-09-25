<?php

/**
* 
* Plugin for caching template output to a file.
* @package Savant3
* @author Alberto Fernández <afm.work@gmail.com>
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* @version $Id: Savant3_Plugin_cache.php 1817 2010-07-06 07:23:16Z admin $
*
*/

error_reporting(-1);

class Savant3_Plugin_cache extends Savant3_Plugin 
{

	// format for expire date: 1y-1m-1w-1d-1h-1m
	// use any limit for each date element: for example, if yo use: 2h-30m, it will take 2h30m till the next refresh
	// everything is converted to seconds, so you can use for example "45d-125m-8h" for size conventions we use:
	// 1 month = 30 days
	// 1 year = 365 days
	// 1 week = 7 days
	
	public $_cache_expire_default = "48h";
	public $_cache_file_ext = "html";
	
	private $_file_handle;
	
	// file data
	public $_filebase;
	
	// default cache folder
	public $_cache_file_path;
	
	// default cache file open mode
	public $_cache_mode = "w";
	
	// default time conversions for the verbose
	// time expire. you can personalize this, but 
	// is not recommended
	public $_time_conversions = array
	(
		"d" => 86400,
		"m" => 2592000,
		"w" => 604800,
		"h" => 3600,
		"y" => 31536000,
		"mt" => 60,
		"s" => 1
	);
		
	/**
	* 
	* Opens the file handler after some comprobations
	* 
	* @access public
	* @param string $file Full path to the file
	* @return bool Returns true if the handler could be opened, false if not
	* 
	*/
	private function file_manager($file, $mode)
	{
		if (!file_exists($file) && $mode != 'w' && $mode != 'w+') return false;
		
		$this->_file_handle = fopen($file, $mode);
		
        if (!$this->_file_handle) return false;
		
		return true;
	}
	
	/**
	* 
	* Write data to the file
	* 
	* @access public
	* @param string $contents Contents to be written in the file
	* @return bool Returns true if anything goes good, false if not
	* 
	*/	
	private function file_write($contents)
	{
		if(fwrite($this->_file_handle, $contents) == false)
			return false;
		
		return true;
	}

	/**
	* 
	* Formats the expire data string prompted by the user
	* 
	* @access public
	* @param string $expire
	* @return int Returns seconds that will be used for refreshing cache
	* 
	*/	
	private function format_expire_string($expire)
	{
		// if the expire data is null, we load the default one 
		// (or the setted by the user)
		if($expire == null)
			$expire = $this->_cache_expire_default;
	
		$str = explode("-", $expire);
		
		$ar_count = count($str);
		
		// if there is only 1 parameter, thats used by 
		// default
		if($ar_count == 0)
			$str[0] = $expire;
		
		$match = array();
		$time = 0;
		
		for($i=0; $i<$ar_count; $i++)
		{
			// look for coincidences in the string, separates the int value from the text
			preg_match_all("/(?P<f>\d+)(?P<v>\w+)/", $str[$i], $match[$i]);
			
			// look for wrong values in the text data
			if(!in_array($match[$i][2][0], array("d", "m", "w", "y", "mt", "s", "h")))
			{
				// and throw error
				return $this->Savant->error
				(
					"ERR_CACHE_EXPIRE_BAD_FORMATTING", 
					array
					(
						'expire_data' => $expire, 
						'bad_tag' => $match[$i][1][0] . $match[$i][2][0]
					)
				);
			}
			
			// calculate the time using the time conversion data
			$time += $match[$i][1][0] * $this->_time_conversions[$match[$i][2][0]];
			
		}
		
		return $time;
		
	}
	
	/**
	* 
	* Check for the filetime for updating the cache
	* 
	* @access public
	* @param int $expire The int value for the expire data
	* @return bool
	* 
	*/	
	protected function check_cache_expire($expire)
	{
		if($this->Savant->isError($expire))
			die($expire);
		
		if(file_exists($this->_filebase['dirname'] . '/' . $this->_filebase['basename']))
		{
			$filetime = filemtime($this->_filebase['dirname'] . '/' . $this->_filebase['basename']);
		}
		else
		{
			return false;
		}
		
		if(time() > $filetime+$expire)
			return false;
		
		return true;
	}
	
	/**
	* 
	* Includes the cache file if needed
	* 
	* @access public
	* @return true
	* 
	*/	
	protected function load_cache_file()
	{
		require_once($this->_filebase['dirname'] . '/' . $this->_filebase['basename']);
		return true;
	}
	
	/**
	* 
	* This function charges all the file data from the different options
	* 
	* @access public
	* @param string $tpl The template file
	* @param string $file_cache The cache filename
	* @return array Array containing all the file info
	* 
	*/		
	protected function load_file_params($tpl, $file_cache)
	{
		// if file_cache is null, we use the same path & name with the default extension
		if($file_cache == null)
		{
			$tpl_info = pathinfo($tpl);
			
			if($this->_cache_file_path == null)
			{
				$pathinfo['dirname'] = $tpl_info['dirname'];
			}
			
			$file_cache = substr($tpl_info['basename'], 0, -3) . $this->_cache_file_ext;
			
		}
		
		// obtain the file data from the string
		$pathinfo = pathinfo($file_cache);
		
		// if the default path is not setted ...
		if($this->_cache_file_path != null && $this->_cache_file_path != '')
			$pathinfo['dirname'] = $this->_cache_file_path;
			
		// if the especified cache file doesn't have extension, we load the default one
		if(array_key_exists('extension', $pathinfo) == false)
		{
			$pathinfo['basename'] = $pathinfo['basename'] . '.' . $this->_cache_file_ext;
		}
		
		$this->_filebase = $pathinfo;
		
	}
	
	/**
	* 
	* Main function
	* 
	* @access public
	* @param string $tpl
	* @param string $file_cache
	* @param string $expire
	* @return bool
	* 
	*/		
	public function cache($tpl = null, $file_cache = null, $expire = null)
	{
		// if not cache name is specified, use the default one: template_name.tpl.html -> template_name.defaul_extension
		$this->load_file_params($tpl, $file_cache);
		
		// if the cache is not expired, and the file exists (hasn't beed deleted by a third), and the $expire is not null ...
		if($this->check_cache_expire($this->format_expire_string($expire)) && $expire != null)
		{
			$this->load_cache_file();
		}
		else
		{
		
			// attempt to open the file handler, if not, calls the Savant3 error handler
			if(!($this->file_manager($this->_filebase['dirname'] . '/' . $this->_filebase['basename'], $this->_cache_mode)))
			{
				return $this->Savant->error
				(
					"ERR_CACHE_FILE_NOT_ACCESIBLE", 
					array
					(
						'cache_dir' => $this->_filebase['dirname'], 
						'cache_file' => $this->_filebase['basename'], 
						'template' => $tpl
					)
				);
			}
			
			// write the content data to the file
			if(!$this->file_write($this->Savant->getOutput($tpl)))
			{
				return $this->Savant->error
				(
					"ERR_CACHE_CANNOT_WRITE", 
					array
					(
						'cache_dir' => $this->_filebase['dirname'], 
						'cache_file' => $this->_filebase['basename'], 
						'template' => $tpl
					)
				);
			}
			
			// al finally load the file
			$this->load_cache_file();
		
		}
		
	}
	
}

class Savant3_Plugin_cache_timer extends Savant3_Plugin_cache
{
	
}

?>