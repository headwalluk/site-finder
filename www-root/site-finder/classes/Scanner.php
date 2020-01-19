<?php

/**
 * Abstract class that scanners (plug-ins) should inherit from. Common
 * functionality should either go in here or in the Tools class.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


abstract class Scanner
{


	function __construct()
	{
		// ...
	}


	public const SITE_DEFINITION_CLASS_NAME = 'SiteDefinition';


	public function throwIfNotSiteDefinition( $site_definition, $calling_function_name = '' )
	{
		Tools::throwIfNotInstanceOf( $site_definition, self::SITE_DEFINITION_CLASS_NAME, $calling_function_name );
	}


	public abstract function scan( $site_definition );


}


?>