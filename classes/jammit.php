<?php

namespace Jammit;

class Jammit extends \Asset {

	/**
	 * The yaml that was loaded.
	 *
	 * @var  array
	 *
	 * @access protected
	 * @static
	 */
	protected static $_yaml = array();

	/**
	 * The current server environment.
	 *
	 * @var string
	 *
	 * @access protected
	 * @static
	 */
	protected static $_env = 'development';

	/**
	 * Is jammit already initialized.
	 *
	 * @var bool
	 *
	 * @access protected
	 * @static
	 */
	public static $is_initialized = false;

	/**
	 * Loads jammit assets into app.
	 *
	 * @access public
	 * @static
	 */
	public static function load()
	{
		// Prevent multiple initializations
		if(static::$is_initialized)
		{
			return;
		}

		\Config::load('jammit', true);

		static::$_folders = array(
			'css'	=>	\Config::get('jammit.css_dir'),
			'js' 	=>	\Config::get('jammit.js_dir'),
			'img'	=>	\Config::get('jammit.img_dir'),
		);

		static::$_env = \FUEL::$env;
		static::_load_yaml_config();
		static::_add_assets();

		static::$is_initialized = true;
	}

	/**
	 * Split $asset string on asset type css, js, tmpl. Remove
	 * public and asset type from path. Return an array
	 * containing a properly formatted Asset path and Asset.
	 *
	 * Example:
	 *
	 * Original: public/assets/vendor/jquery-ui-1.8.16.custom/
	 *			 css/smoothness/jquery-ui-1.8.16.custom.css
	 * Formatted...
	 * Path: assets/vendor/jquery-ui-1.8.16.custom/
	 * File: smoothness/jquery-ui-1.8.16.custom.css
	 *
	 * @param string $asset the asset path and file
	 *
	 * @return array the file and path seperately given a string
	 *
	 * @access protected
	 * @static
	 */
	protected static function _get_file_and_path($asset)
	{
		preg_match(
			'@^public/(?P<path>.*/)(?:css|js|tmpl)/(?P<rest>.*)$@',
			$asset,
			$matches
		);

		return array(
			'file' => $matches['rest'],
			'path' => $matches['path']
		);
	}

	/**
	 * Adds assets from static::$_yaml to Fuel\Asset.
	 *
	 * @access protected
	 * @static
	 */
	protected static function _add_assets()
	{
		$yaml = static::$_yaml;
		$asset_types = array('javascripts', 'stylesheets');

		foreach($asset_types as $asset_type)
		{
			// Protect against empty asset types
			if(empty($yaml[$asset_type])) continue;

			$groups = $yaml[$asset_type];
			foreach($groups as $group => $assets)
			{
				foreach($assets as $asset)
				{
					$asset_split = static::_get_file_and_path($asset);
					static::add_path($asset_split['path']);
					static::_add_asset($asset_split['file'], $group);
				}
			}
		}
	}

	/**
	 * Adds path to search when finding assets.
	 *
	 * @param string $path
	 *
	 * @access public
	 * @static
	 */
	public static function add_path($path)
	{
		if(!in_array($path, static::$_asset_paths))
		{
			array_unshift(static::$_asset_paths, str_replace('../', '', $path));
		}
	}

	/**
	 * Add asset to group.
	 *
	 * @param string $file assets file name
	 * @param string $group group asset belongs in
	 *
	 * @access protected
	 * @static
	 */
	protected static function _add_asset($file, $group)
	{
		// detect wildcard for all files in path
		if(strpos($file, '*') !== false)
		{

		}
		else
		{
			$asset_type = 'css';
			if(strpos($file, '.js') !== false)
			{
				$asset_type = 'js';
			}

			static::$asset_type($file, array(), "{$group}_{$asset_type}");
		}
	}

	/**
	 * Loads content from jammit yaml config.
	 *
	 * @return array content of jammit yaml config
	 *
	 * @access protected
	 * @static
	 */
	protected static function _load_yaml_config()
	{
		$contents = \File::read(DOCROOT.'../config/assets.yml', true);
		return static::$_yaml = \Format::forge($contents, 'yaml')->to_array();
	}
}