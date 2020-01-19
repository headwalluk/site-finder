<?php


/**
 * The Flat/Simple Site scanner plug-in.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


require_once( 'classes/FlatSiteScanner.php' );

SiteFinder::registerScanner( new FlatSiteScanner(), SiteFinder::LOW_SCANNER_PRIORITY );

?>
