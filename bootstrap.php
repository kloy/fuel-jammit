<?php
/**
 * Jammit package
 *
 * @package    Jammit
 * @version    0.1
 * @author     Keith Loy
 * @license    MIT License
 * @copyright  2011-2012 Keith Loy
 */

Autoloader::add_core_namespace('Jammit');

Autoloader::add_classes(array(
    'Jammit\\Jammit' => __DIR__.'/classes/jammit.php',
    'Jammit\\Loader' => __DIR__.'/classes/loader.php',
    'Jammit\\Render' => __DIR__.'/classes/render.php',
));

/* End of file bootstrap.php */

