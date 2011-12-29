<?php

namespace Jammit;

class Loader {

	/**
	 * The yaml config that was loaded.
	 *
	 * @var  array
	 *
	 * @access protected
	 * @static
	 */
	protected static $_config = array();

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
	 * The extension used for templates
	 *
	 * @var string
	 *
	 * @access protected
	 * @static
	 */
	protected static $_tmpl_ext = 'jst';


	/**
	 * Is yaml already initialized.
	 *
	 * @var bool
	 *
	 * @access protected
	 * @static
	 */
	public static $is_initialized = false;

	/**
	 * Loads content from jammit yaml config.
	 *
	 * @access public
	 * @static
	 */
	public static function load()
	{
		if(static::$is_initialized)
		{
			return;
		}

		static::$_env = \FUEL::$env;
		static::$_tmpl_ext = \Config::get('jammit.tmpl_ext', 'jst');
		static::_read_config();
		static::_load_assets();
		static::$is_initialized = true;
	}

	public static function get_config()
	{
		return static::$_config;
	}

	protected static function _read_config()
	{
		$contents = \File::read(DOCROOT.'../config/assets.yml', true);
		static::$_config = \Format::forge($contents, 'yaml')->to_array();
	}

	/**
	 * Loads assets from Loader::get_config() to Fuel\Asset.
	 *
	 * @access protected
	 * @static
	 */
	protected static function _load_assets()
	{
		$yaml = static::get_config();
		$asset_types = array('javascripts', 'stylesheets');

		foreach($asset_types as $asset_type)
		{
			// Protect against empty asset types
			if(empty($yaml[$asset_type])) continue;

			$groups = $yaml[$asset_type];
			foreach($groups as $group => $assets)
			{
				if (static::$_env !== 'production')
				{
					$package_path = $yaml['package_path'];
					\Jammit\Jammit::add_path($package_path.'/');
					static::_load_production_asset($group, $asset_type);
				}
				else
				{
					foreach($assets as $asset)
					{
						$asset_split = static::_get_file_and_path($asset);
						\Jammit\Jammit::add_path($asset_split['path']);
						static::_add_asset($asset_split['file'], $group);
					}
				}
			}
		}
	}

	protected static function _load_production_asset($group, $asset_type)
	{
		$asset_ext = $asset_type === 'javascripts' ? 'js' : 'css';
		$asset = $group.'.'.$asset_ext;
		\Jammit\Jammit::$asset_ext($asset, array(), $group.'_'.$asset_ext);
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
			static::_add_wildcard($file, $group);
		}
		else
		{
			$asset_type = static::get_file_extension($file);
			\Jammit\Jammit::$asset_type($file, array(), "{$group}_{$asset_type}");
		}
	}

	/**
	 * Returns container folders for given file.
	 *
	 * @access protected
	 * @static
	 * @return array
	 */
	protected static function _get_folders_for_file($file_name)
	{
		$type = static::get_file_extension($file_name);
		$folders = array();
		$asset_folders = \Jammit\Jammit::get_folders();
		if(is_string($asset_folders[$type]))
		{
			$folders[] = $asset_folders[$type];
		}
		else
		{
			$folders = $asset_folders[$type];
		}

		return $folders;
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
		$folders     = array();
		$asset_folders = \Jammit\Jammit::get_folders();
		$ext         = static::get_file_extension($asset);
		$type        = is_string($asset_folders[$ext])
					 ? array($asset_folders[$ext])
					 : $asset_folders[$ext];
		$folders     = array_merge($folders, $type);
		$asset_paths = str_replace('/', '', implode('|', $folders));

		$regex = "@^public/(?P<path>.*/)(?:{$asset_paths})/(?P<rest>.*)$@";
		preg_match($regex, $asset, $matches);

		return array(
			'file' => $matches['rest'],
			'path' => $matches['path'],
		);
	}

	/**
	 * Get the file extension of a file
	 */
	public static function get_file_extension($file_name)
	{
		$ext = substr(strrchr($file_name,'.'),1);
		return $ext === static::$_tmpl_ext ? 'tmpl' : $ext;
	}

	/**
	 * Adds all assets found from wildcard to group
	 */
	protected static function _add_wildcard($file, $group)
	{
		// grab the first path, since last path added
		// is most likely the one
		$asset_type        = static::get_file_extension($file);
		// handle tmpl extensions properly
		if ($asset_type === 'tmpl')
		{
			$asset_ext = static::$_tmpl_ext;
		}
		else
		{
			$asset_ext = $asset_type;
		}
		$type_paths        = static::_get_folders_for_file($file);
		$asset_path        = \Jammit\Jammit::get_asset_paths();
		$asset_path		   = $asset_path[0];
		$end_path          = str_replace('*.'.$asset_ext, '', $file);
		$end_path_exploded = explode('/', $end_path);
		$wild_prefix       = end($end_path_exploded);
		$end_path          = str_replace($wild_prefix, '', $end_path);

		// find correct type path
		$path_found = false;
		foreach($type_paths as $type_path)
		{
			if ($path_found)
			{
				continue;
			}
			if(is_dir(DOCROOT . $asset_path . $type_path . $end_path))
			{
				$path       = DOCROOT . $asset_path . $type_path . $end_path;
				$path_found = true;
			}
		}
		if ( ! $path_found)
		{
			throw new \FuelException('Could not find asset path: '
				.DOCROOT.$asset_path.$type_path.$end_path);
		}

		$regex = $wild_prefix === '' ? array() : array($wild_prefix);
		$files = \File::read_dir($path, 1, $regex);

		foreach($files as $file_name)
		{
			$asset_name = $end_path . $file_name;

			if (! \Jammit\Jammit::in_group($asset_name, "{$group}_{$asset_type}"))
			{
				\Jammit\Jammit::$asset_type(
					$asset_name, array(),
					"{$group}_{$asset_type}"
				);
			}
		}
	}
}