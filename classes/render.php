<?php

namespace Jammit;

class Render {
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
			$groups = \Jammit\Jammit::get_groups();
			$group = isset($groups[$group])
				? $groups[$group] : array();
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
				$asset_folders = \Jammit\Jammit::get_folders();
				if(is_string($asset_folders[$type]))
				{
					$folders[] = $asset_folders[$type];
				}
				else
				{
					$folders = $asset_folders[$type];
				}

				$found_file = false;
				foreach($folders as $folder)
				{
					if ($found_file)
					{
						continue;
					}
					if (($file = \Jammit\Jammit::find_file($filename, $folder)) !== false)
					{
						$found_file = true;
					}
				}

				if ( ! $found_file)
				{
					throw new \FuelException('Could not find asset: '.$filename);
				}

				$raw or $file = \Jammit\Jammit::get_asset_url()
					.$file
					.(\Jammit\Jammit::get_add_mtime() ? '?'.filemtime($file) : '');
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
		$template_name  = substr(end($template_array), 0, -4);
		$template_name  = explode('?', $template_name);
		$template_name  = array_shift($template_name);
		$template_name  = str_replace('.jst', '', $template_name);
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
}