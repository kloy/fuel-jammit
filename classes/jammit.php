<?php
/**
 * Jammit class
 *
 * @version    1.0
 * @author     Keith Loy
 * @license    MIT License
 * @copyright  2011-2012 Keith Loy
 */

namespace Jammit;

class Jammit extends \Asset {

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
			'css'	=>	\Config::get('jammit.css_dir', 'css/'),
			'js' 	=>	\Config::get('jammit.js_dir', 'js/'),
			'img'	=>	\Config::get('jammit.img_dir', 'img/'),
			'tmpl'  =>  \Config::get('jammit.js_dir', 'js/'),
		);

		\Jammit\Loader::load();
		static::$is_initialized = true;
	}

	public static function render($group, $raw = false)
	{
		return \Jammit\Render::render($group, $raw);
	}

	public static function get_groups()
	{
		return static::$_groups;
	}

	/**
	 * Find out if a file exists in a group
	 */
	public static function in_group($file, $group = '')
	{
		$groups = static::get_groups();
		$group = isset($groups[$group])
				? $groups[$group] : array();
		$flattened_group = \Arr::flatten($group);

		return in_array($file, $flattened_group);
	}

	public static function get_folders()
	{
		return static::$_folders;
	}

	public static function get_asset_url()
	{
		return static::$_asset_url;
	}

	public static function get_add_mtime()
	{
		return static::$_add_mtime;
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

	public static function get_asset_paths()
	{
		return static::$_asset_paths;
	}

	/**
	 * Add a template to a group
	 */
	public static function tmpl($template, $attr = array(), $group)
	{
		static::_parse_assets('tmpl', $template, $attr, $group);
	}
}