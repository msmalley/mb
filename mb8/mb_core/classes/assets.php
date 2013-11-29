<?php

class mongobase_assets extends mongobase_mb
{

	protected static $env;
	protected static $options;

	public function asset($slug = null)
	{
		if(!isset($slug))
		{
			if(isset($this::$env) && isset($this::$env->slug))
			{
				$slug = $this::$env->slug;
			}
		}
		if(isset($slug))
		{
			$asset_namespace = $this::$env->ns->assets;
			$asset_namespace_length = strlen($asset_namespace);
			$asset_url = str_replace($asset_namespace.'/', '', $slug);
			$asset_array = explode('.', $asset_url);
			$asset_filename = $asset_array[0];
			$asset_extension = $asset_array[1];
			$asset = $this->cascade('assets', $asset_filename, $asset_extension);

			// Certain browsers and situations require these specific file types

			if($asset_extension == 'css')
			{
				$asset_extension = 'text/css';
			}

			header('Content-Type: '.$asset_extension);
			//get the last-modified-date of this very file
			$lastModified=filemtime(__FILE__);
			//get a unique hash of this file (etag)
			$etagFile = hash('md5', $asset);
			//get the HTTP_IF_MODIFIED_SINCE header if set
			$ifModifiedSince=(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
			//get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
			$etagHeader=(isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
			//set last-modified header
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");
			//set etag-header
			header("Etag: $etagFile");
			//make sure caching is turned on
			header('Cache-Control: public');
			//check if page has changed. If not, send 304 and exit
			if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])==$lastModified && $etagHeader == $etagFile)
			{
				   header("HTTP/1.1 304 Not Modified");
				   exit;
			}
			echo parent::process($asset); exit;
		}
	}

	function __construct($options = array(), $key = 'assets')
	{
		$defaults = array(
			'key' => $key
		);
		// Merge options and defaults
		$settings = array_merge($defaults, $options);

		// REGISTER MODULE
		parent::__construct($settings, $settings['key']);

	}
}