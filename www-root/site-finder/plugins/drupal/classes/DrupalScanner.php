<?php


/**
 * Scan for a Drupal installation.
 *
 * IMPORTANT: This is unfinished!
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class DrupalScanner extends Scanner
{


	function __construct()
	{
		// ...
	}


	public function scan( $site_definition )
	{
		$this->throwIfNotSiteDefinition( $site_definition, __FUNCTION__ );

		$config_file_name = Tools::joinPaths( $site_definition->full_path, 'core/drupalci.yml' );

		if( is_file( $config_file_name ) )
		{
			$site_definition->title = 'A Drupal Site';
			$site_definition->icon_name = 'drupal';
			$site_definition->is_valid = true;
		}
	}


}


?>