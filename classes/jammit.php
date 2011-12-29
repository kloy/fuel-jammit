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
	 * The yaml that was loaded.
	 *
	 * @var  array
	 *
	 * @access protected
	 * @static
	 */
	protected static $_yaml = array();

	/**
	 * Files already loaded.
	 *
	 * @var  array
	 *
	 * @access protected
	 * @static
	 */
	protected static $_loaded_files = array();

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

		static::$_tmpl_ext = \Config::get('jammit.tmpl_ext', 'jst');
		static::$_env = \FUEL::$env;
		static::_load_yaml_config();
		static::_add_assets();

		static::$is_initialized = true;
	}

	/**
	 * Renders the given group.  Each tag will be separated by a line break.
	 * You can optionally tell it to render the files raw.  This means that
	 * all CSS and JS files in the group will be read and the contents included
	 * in the returning value.
	 *
	 * @param   mixed   the group to render
	 * @param   bool    whether to return the raw file or not
	 * @return  string  the group's output
	 */
	public static function render($group, $raw = false)
	{
		if (is_string($group))
		{
			$group = isset(static::$_groups[$group])
				? static::$_groups[$group] : array();
		}

		$css = '';
		$js = '';
		$img = '';
		$tmpl = '';
		foreach ($group as $key => $item)
		{
			$type = $item['type'];
			$filename = $item['file'];
			$attr = $item['attr'];

			if ( ! preg_match('|^(\w+:)?//|', $filename))
			{
				// make sure $folders is an array
				$folders = array();
				if(is_string(static::$_folders[$type]))
				{
					$folders[] = static::$_folders[$type];
				}
				else
				{
					$folders = static::$_folders[$type];
				}

				$found_file = false;
				foreach($folders as $folder)
				{
					if ($found_file)
					{
						continue;
					}
					if (($file = static::find_file($filename, $folder)) !== false)
					{
						$found_file = true;
					}
				}

				if ( ! $found_file)
				{
					throw new \FuelException('Could not find asset: '.$filename);
				}

				$raw or $file = static::$_asset_url.$file.(static::$_add_mtime ? '?'.filemtime($file) : '');
			}
			else
			{
				$file = $filename;
			}

			switch($type)
			{
				case 'css':
					if ($raw)
					{
						return '<style type="text/css">'.PHP_EOL.file_get_contents($file).PHP_EOL.'</style>';
					}
					$attr['rel'] = 'stylesheet';
					$attr['type'] = 'text/css';
					$attr['href'] = $file;

					$css .= html_tag('link', $attr).PHP_EOL;
				break;
				case 'js':
					if ($raw)
					{
						return html_tag('script', array('type' => 'text/javascript'), PHP_EOL.file_get_contents($file).PHP_EOL).PHP_EOL;
					}
					$attr['type'] = 'text/javascript';
					$attr['src'] = $file;

					$js .= html_tag('script', $attr, '').PHP_EOL;
				break;
				case 'img':
					$attr['src'] = $file;
					$attr['alt'] = isset($attr['alt']) ? $attr['alt'] : '';

					$img .= html_tag('img', $attr );
				break;
				case 'tmpl':
					$tmpl .= static::_render_tmpl($file);
				break;
			}

		}

		// return them in the correct order
		return $css.$js.$img.$tmpl;
	}

	protected static function _render_tmpl($template)
	{
		$template_array = explode('/', $template);
        $template_name = substr(end($template_array), 0, -4);
        $template_name = explode('?', $template_name);
        $template_name = array_shift($template_name);
        $template_name = str_replace('.jst', '', $template_name);
		$template_contents = addslashes(
			preg_replace("/[\n\r\t ]+/"," ",file_get_contents($template))
		);
		$script_contents = '(function(){'
						 . PHP_EOL
						 . 'window.JST = window.JST || {};'
						 . PHP_EOL
						 . "window.JST['{$template_name}'] = _.template("
						 . "'{$template_contents}');"
						 . PHP_EOL
						 . '})();'
						 . PHP_EOL;

		return html_tag('script', array('type' => 'text/javascript'),
			$script_contents).PHP_EOL;
	}

	/**
	 * Find out if a file exists in a group
	 */
	public static function in_group($file, $group = '')
	{
		$group = isset(static::$_groups[$group])
				? static::$_groups[$group] : array();
		$flattened_group = \Arr::flatten($group);

		return in_array($file, $flattened_group);
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
	 * Add a template to a group
	 */
	public static function tmpl($template, $attr = array(), $group)
	{
		static::_parse_assets('tmpl', $template, $attr, $group);
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
		$type = static::_get_file_extension($file_name);
		$folders = array();
		if(is_string(static::$_folders[$type]))
		{
			$folders[] = static::$_folders[$type];
		}
		else
		{
			$folders = static::$_folders[$type];
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
		$ext         = static::_get_file_extension($asset);
		$type        = is_string(static::$_folders[$ext])
					 ? array(static::$_folders[$ext])
					 : static::$_folders[$ext];
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
	protected static function _get_file_extension($file_name)
	{
		$ext = substr(strrchr($file_name,'.'),1);
		return $ext === static::$_tmpl_ext ? 'tmpl' : $ext;
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
			$asset_type = static::_get_file_extension($file);
			static::$asset_type($file, array(), "{$group}_{$asset_type}");
		}
	}

	/**
	 * Adds all assets found from wildcard to group
	 */
	protected static function _add_wildcard($file, $group)
	{
		// grab the first path, since last path added
		// is most likely the one
		$asset_type        = static::_get_file_extension($file);
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
		$asset_path        = static::$_asset_paths[0];
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

			if (! static::in_group($asset_name, "{$group}_{$asset_type}"))
			{
				static::$asset_type($asset_name, array(), "{$group}_{$asset_type}");
			}
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