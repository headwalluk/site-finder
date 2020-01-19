<?php


/**
 * The Wordpress scanner plug-in.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


require_once( 'classes/WordPressScanner.php' );

SiteFinder::registerScanner( new WordPressScanner() );

?>
