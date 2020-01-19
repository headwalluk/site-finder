<?php


/**
 * The phpMyAdmin scanner plug-in.
 *
 * IMPORTANT: Only tested with phpMyAdmin 4.9.2
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


require_once( 'classes/PhpMyAdminScanner.php' );

SiteFinder::registerScanner( new PhpMyAdminScanner() );

?>
