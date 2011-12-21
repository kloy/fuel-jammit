# Jammit

Allows for the ruby gem [Jammit](http://documentcloud.github.com/jammit/) to be used with [Fuel](http://fuelphp.com).

To use place a Config/assets.yml file at the project root directory.
For example, if your Project has a directory structure as follows:

	~/Foo
	~/Foo/Fuel
	~/Foo/public

your config would go...

	~/Foo/config/assets.yml

An example config file looks like...

	# jammit manifest
	# Tip: NEVER NAME A GROUP THE SAME AS A DIR BEING INCLUDED
	embed_assets: datauri # doesn't support ie7 and below
	compress_assets: off
	template_function: _.template
	package_path: production

	javascripts:
	    base:
	        - public/assets/vendor/js/jquery-1.7.1.js
	        - public/assets/vendor/jquery-ui-1.8.16.custom/js/jquery-ui-1.8.16.custom.js
	        - public/assets/vendor/js/underscore-1.2.3.js
	stylesheets:
	    base:
	        - public/assets/css/fuel.css
	        - public/assets/vendor/jquery-ui-1.8.16.custom/css/smoothness/jquery-ui-1.8.16.custom.css

To use thr Jammit class in your project include `jammit` in the packages config setting
and call `Jammit\Jammit::load()` in a controller to load all assets for rendering. Execute `echo Jammit\Jammit::render('group')` in a view to render assets in the view.
The render function works nearly identical to Fuel's `Asset::render('group')`.