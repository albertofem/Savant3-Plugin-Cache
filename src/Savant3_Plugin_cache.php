<?php

/*
 * Plugin for caching template output to a file.
 *
 * Copyright (C) 2010-2012 Alberto Fernández
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alberto Fernández <albertofem@gmail.com>
 * @version 1.1
 */

/**
 * Plugin class
 *
 * @package Savant3
 * @author Alberto <albertofem@gmail.com>
 */
class Savant3_Plugin_cache extends Savant3_Plugin
{
	/**
	 * @var string
	 */
	public $defaultExpiration = "48h";

	/**
	 * @var string
	 */
	public $cachePath = '.';

	/**
	 * @var string
	 */
	private $fileExtension = "html";

	/**
	 * @var resource
	 */
	private $fileBuffer;
	
	/**
	 * @var array
	 */
	private $cacheFilePathInfo;
	
	/**
	 * @var string
	 */
	private $fileMode = "w";
	
	/**
	 * @var array
	 */
	private $timeConversions = array
	(
		"y" => 31536000,
		"m" => 2592000,
		"w" => 604800,
		"d" => 86400,
		"h" => 3600,
		"mt" => 60,
		"s" => 1
	);

	/**
	 * Attempts to recover from cache a template previously
	 * renderized. If cache is not found, the template will
	 * be rendered and cache based in an expiration time given
	 * in the parameter $expiration, with the next format:
	 * <pre>
	 * 		Xy - X years
	 * 		Xm - X months
	 * 		Xw - X weeks
	 * 		Xd - X days
	 * 		Xh - X hours
	 * 		Xmt - X minutes
	 * 		Xs - X seconds
	 * </pre>
	 *
	 * Use a dash to compose complex expiration times, for example:
	 * <pre>
	 * 		2w-2d-15m
	 * </pre>
	 *
	 * The cache will expire in 2 weeks, 2 days and 15 minutes. By
	 * default, the expire time is 24h.
	 *
	 * File to write the cache contents will be determined based on
	 * the original filename and cachePath configuration, but you can
	 * override this behaviour by providing a valid file path
	 *
	 * @param string $tpl
	 * @param string $expiration
	 * @param string $cacheFile
	 */
	public function cache($tpl = null, $expiration = null, $cacheFile = null)
	{
		// if not cache name is specified, use the default one: template_name.tpl.html -> template_name.defaulExtension
		$this->loadFilePathInfo($tpl, $cacheFile);

		// if the cache is not expired, and the file exists (hasn't beed deleted by a third), and the $expire is not null ...
		if($this->checkCacheExpiration($this->formatExpireString($expiration)) && $expiration != null)
		{
			$this->loadCache();
		}
		else
		{
			// attempt to open the file handler, if not, calls the Savant3 error handler
			if(!($this->openFileHandler($this->cacheFilePathInfo['dirname'] . '/' .
				$this->cacheFilePathInfo['basename'], $this->fileMode)))
			{
				return $this->Savant->error
				(
					"ERR_CACHE_FILE_NOT_ACCESIBLE",
					array
					(
						'cache_dir' => $this->cacheFilePathInfo['dirname'],
						'cache_file' => $this->cacheFilePathInfo['basename'],
						'template' => $tpl
					)
				);
			}

			// write the content data to the file
			if(!$this->write($this->Savant->getOutput($tpl)))
			{
				return $this->Savant->error
				(
					"ERR_CACHE_FILE_CANNOT_WRITE",
					array
					(
						'cache_dir' => $this->cacheFilePathInfo['dirname'],
						'cache_file' => $this->cacheFilePathInfo['basename'],
						'template' => $tpl
					)
				);
			}

			$this->loadCache();
		}
	}
		
	/**
	 * Tries to open a file buffer
	 *
	 * @param $file
	 * @param $mode
	 *
	 * @return bool
	 */
	private function openFileHandler($file, $mode)
	{
		if(!file_exists($file) && $mode != 'w' && $mode != 'w+')
			return false;
		
		$this->fileBuffer = fopen($file, $mode);
		
        if(!$this->fileBuffer)
			return false;
		
		return true;
	}
	
	/**
	 * Write contents to current buffer
	 *
	 * @param $contents
	 * @return bool
	 */
	private function write($contents)
	{
		if(fwrite($this->fileBuffer, $contents) == false)
			return false;
		
		return true;
	}

	/**
	 * Formats the expire data string prompted by the user
	 *
	 * @param $expire
	 *
	 * @return int Seconds until cache refresh
	 */
	private function formatExpireString($expire)
	{
		// if the expire data is null, we load the default one 
		// (or the setted by the user)
		if($expire == null)
			$expire = $this->defaultExpiration;
	
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
			if(!in_array($match[$i][2][0], array_keys($this->timeConversions)))
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
			$time += $match[$i][1][0] * $this->timeConversions[$match[$i][2][0]];
			
		}
		
		return $time;
	}
	
	/**
	 * Check if a file cache has expired
	 *
	 * @param $expireTime
	 *
	 * @return bool
	 */
	protected function checkCacheExpiration($expireTime)
	{
		$fileFullPath = $this->cacheFilePathInfo['dirname'] . '/' . $this->cacheFilePathInfo['basename'];

		if(file_exists($fileFullPath))
		{
			$filetime = filemtime($fileFullPath);
		}
		else
		{
			return false;
		}
		
		if(time() > $filetime+$expireTime)
			return false;
		
		return true;
	}
	
	/**
	 * Includes the cached file
	 *
	 * @return bool
	 */
	protected function loadCache()
	{
		require $this->cacheFilePathInfo['dirname'] . '/' . $this->cacheFilePathInfo['basename'];
	}
	
	/**
	 * Loads the cache file path info, using user-input
	 * options or the default ones
	 *
	 * @param $tpl
	 * @param $cacheFile
	 */
	protected function loadFilePathInfo($tpl, $cacheFile)
	{
		// if file_cache is null, we use the same path & name with the default extension
		if(is_null($cacheFile))
		{
			$originalFile = pathinfo($tpl);
			
			if($this->cachePath == null)
			{
				$pathinfo['dirname'] = $originalFile['dirname'];
			}
			
			$cacheFile = substr($originalFile['basename'], 0, -3) . $this->fileExtension;
		}
		
		// obtain the file data from the string
		$pathinfo = pathinfo($cacheFile);
		
		// if the default path is not setted ...
		if($this->cachePath != null && $this->cachePath != '')
			$pathinfo['dirname'] = $this->cachePath;
			
		// if the especified cache file doesn't have extension, we load the default one
		if(array_key_exists('extension', $pathinfo) == false)
		{
			$pathinfo['basename'] = $pathinfo['basename'] . '.' . $this->fileExtension;
		}
		
		$this->cacheFilePathInfo = $pathinfo;
	}

	/**
	 * @param string $cachePath
	 */
	public function setCachePath($cachePath)
	{
		$this->cachePath = $cachePath;
	}
}