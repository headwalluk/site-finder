<?php


/**
 * Detect a phpMyAdmin installation.
 *
 * IMPORTANT: Only tested with phpMyAdmin 4.9.2
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class PhpMyAdminScanner extends Scanner
{


	function __construct()
	{
		// ...
	}


	const PACKAGE_NAME = 'phpmyadmin';


	public function scan( $site_definition )
	{
		$this->throwIfNotSiteDefinition( $site_definition, __FUNCTION__ );

		$package = Tools::loadJsonPackage( $site_definition->full_path );

		// Tools::dump_array( $package );

		if( isset( $package['name'] ) && $package['name'] === self::PACKAGE_NAME )
		{
			$site_definition->title = 'phpMyAdmin ' . $package['version'];
			$site_definition->icon_name = 'database';
			$site_definition->addKeyValuePair( 'info', $package['description'] );

			// $site_definition->set_main_link( $site_definition->url_suffix );
			
			$site_definition->is_valid = true;
		}
	}


}


?>