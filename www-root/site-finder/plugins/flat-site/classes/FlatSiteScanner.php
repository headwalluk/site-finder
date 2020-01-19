<?php


/**
 * This should be registered as a low-priority scanner because it will set a
 * site_definition as being valid/detected whenever it finds one of
 * index.php|html|htm.
 *
 * IMPORTANT: This is unfinished!
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class FlatSiteScanner extends Scanner
{


	function __construct()
	{
		// ...
	}


	private $index_file_names = array(
		'index.html',
		'index.htm',
		'index.php'
	);


	public function scan( $site_definition )
	{
		$this->throwIfNotSiteDefinition( $site_definition, __FUNCTION__ );

		foreach( $this->index_file_names as $index_file_name )
		{
			$full_path = Tools::joinPaths( $site_definition->full_path, $index_file_name );
			
			if( is_file( $full_path ) )
			{
				$site_definition->title = basename( dirname( $full_path ) );

				// $class_name = get_class( $site_definition );
				// $method_names = get_class_methods( $class_name );
				// foreach( $method_names as $method_name )
				// {
				// 	Tools::show_debug( $class_name . '->' . $method_name . '()' );
				// }


				$icon_name = 'file-code-o';
				// if( Tools::ends_with( $full_path, '.php' ) ) {
				// 	$icon_name = 'php';
				// }
				// else {
				// 	// ...
				// }


				$site_definition->icon_name = $icon_name;
				$site_definition->addKeyValuePair( $icon_name, $index_file_name );
				
				// $site_definition->set_main_link( $site_definition->url_suffix );
				// $site_definition->foo();

				$site_definition->is_valid = true;

				break;
			}
		}
	}


}


?>