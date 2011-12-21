<?php
/**
 * Config for Jammit package.
 *
 * @version    1.0
 * @author     Keith Loy
 * @license    MIT License
 * @copyright  2011-2012 Keith Loy
 */

/**
 * NOTICE:
 *
 * If you need to make modifications to the default configuration, copy
 * this file to your app/config folder, and make them in there.
 *
 * This will allow you to upgrade jammit without losing your custom config.
 */

return array(

	/**
	 * Asset Sub-folders
	 *
	 * Names for the img, js and css folders (inside the asset path).
	 *
	 * Examples:
	 *
	 * img/
	 * js/
	 * css/
	 *
	 * This MUST include the trailing slash ('/')
	 *
	 * Arrays of paths are allowed as well as a string like in Asset's config
	 */
	'img_dir' => 'img/',
	'js_dir' => 'js/',
	'css_dir' => 'css/'
);