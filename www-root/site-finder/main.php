<?php

/**
 * Class-loader for site-finder,
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

const CLASSES_DIRECTORY_NAME = 'classes/';

spl_autoload_register( function( $class_name ) {
    include CLASSES_DIRECTORY_NAME . $class_name . '.php';
});

?>
