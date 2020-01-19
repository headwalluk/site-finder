<?php

 /**
 * Main entry point for rendering site finder.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

require_once( 'site-finder/main.php' );

// Tools::enableDebug();

$site_finder = new SiteFinder();

$site_finder->writePageHeader();

$site_finder->initialise();

$site_finder->addChildDirectories( getcwd() );
$site_finder->blacklistPath( Tools::joinPaths( getcwd(), 'site-finder' ) );

$site_finder->findSites();

$site_finder->writeSitesHeader();
$site_finder->writeSitesBody();
$site_finder->writeSitesFooter();

$site_finder->writePageFooter();

?>