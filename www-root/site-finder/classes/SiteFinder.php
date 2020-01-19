<?php


/**
 * The main Site Finder class.
 *
 * use addPath() to add a directory that may contain a web site/app.
 * Use addChildDirectories() to scan child directories for possible apps/sites.
 *
 * Typical workflow when called from index.php:
 * 
 * // Start.
 * $site_finder = new SiteFinder();
 * $site_finder->writePageHeader();
 * $site_finder->initialise();
 * $site_finder->addChildDirectories( getcwd() );
 * $site_finder->blacklistPath( Tools::joinPaths( getcwd(), 'site-finder' ) );
 * $site_finder->findSites();
 * $site_finder->writeSitesHeader();
 * $site_finder->writeSitesBody();
 * $site_finder->writeSitesFooter();
 * $site_finder->writePageFooter();
 * // End.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class SiteFinder
{


	function __construct()
	{
		set_exception_handler( array( 'Tools', 'exceptionHandler' ) );
	}


	private const CONFIG_FILE_NAME = 'site-finder-settings.json';


	public function initialise()
	{
		$this->clearDirectories();

		$config_file_name = realpath( self::CONFIG_FILE_NAME );

		// Tools::showDebug( $config_file_name );

		try
		{
			$this->loadConfigurationFile( $config_file_name );
		}
		catch( Exception $e )
		{
			Tools::showError( 'Error loading configuration: ' . $e->getMessage() );
		}

		try
		{
			$this->loadPlugins();
		}
		catch( Exception $e )
		{
			Tools::showError( 'Error loading plugins: ' . $e->getMessage() );
		}
	}

	
	// -------------------------------------------------------------------------
	//
	// Main "Find Sites" logic.
	//
	// -------------------------------------------------------------------------


	private $site_definitions = array();


	public function findSites()
	{
		Tools::showDebug( 'Searching or sites...' );

		$scanners = self::getScanners();

		if( count( $scanners ) === 0 ) {
			Tools::showError( 'findSites(): No scanners have been registered. Have the plugins been loaded without error?' );
		}
		else
		{
			rsort( $scanners );

			$this->site_definitions = array();

			$added_paths = array();
			foreach( $this->directories as $directory )
			{
				$path = $directory['path'];

				if( !$this->isPathBlacklisted( $path ) && !empty( $path ) && is_dir( $path ) && !in_array( $path, $added_paths ) )
				{
					array_push( $added_paths, $path );

					$site_definition = new SiteDefinition();
					$site_definition->full_path = $path;
					$site_definition->url_suffix = $directory['url_suffix'];

					$site_definition->addKeyValuePair( 'folder', $path );
					array_push( $this->site_definitions, $site_definition );
				}
			}

			// Diagnostics.
			// Tools::dumpArray( $this->site_definitions );

			foreach( $this->site_definitions as $site_definition )
			{
				if( ! $site_definition->is_valid )
				{
					Tools::showDebug( 'Checking Possible Site: ' . $site_definition->full_path );
					
					foreach ($scanners as $key => $value )
					{
						$priority = $value[0];
						$scanner = $value[1];

						Tools::showDebug( 'Scanning With: ' . get_class( $scanner ) );

						try
						{
							$scanner->scan( $site_definition );
						}
						catch( Exception $exception )
						{
							Tools::showError( $exception->getMessage() );
						}

						if( $site_definition->is_valid ) {
							break;
						}
					}
				}
			}
		}
	}


	// -------------------------------------------------------------------------
	//
	// Configuration file management.
	//
	// -------------------------------------------------------------------------


	public function loadConfigurationFile( $file_name )
	{
		if( !is_file( $file_name ) ) {
			// Tools::showError( 'Configuration file not found or invalid: ' . $file_name );
		}
		else {
			// Tools::showDebug( 'Found config file: ' . $file_name );

			$directories = array();

			try
			{
				$serialised_configuration = file_get_contents( $file_name, true );

				$configuration = json_decode( $serialised_configuration, true );

				if( !isset( $configuration ) ) {
					throw new Exception( 'Failed to parse configuration file: "' . $file_name . '"' );
				}

				if( isset( $configuration['number_of_columns'] ) ) {
					$this->number_of_columns = $configuration['number_of_columns'];
				}

				// Tools::dumpArray( $configuration );

				foreach( $configuration['directories'] as $directory ) {
					if( $directory['is_scanned'] ) {
						$this->addPath(
							$directory['path'],
							$directory['url_suffix']
						);
					}

					if( $directory['are_children_scanned'] ) {
						$this->addChildDirectories(
							$directory['path'],
							$directory['url_suffix']
						);
					}
				}

			}
			catch( Exception $exception )
			{
				Tools::show_error( 'Error loading configuration file "' . $config_file_name . '": ' . $exception->getMessage() );
			}
		}
	}


	// -------------------------------------------------------------------------
	//
	// Site Paths.
	//
	// -------------------------------------------------------------------------


	private $directories = array();
	private $blacklisted_directory_names = array();


	public function clearDirectories()
	{
		$this->directories = array();
	}


	public function blacklistPath( $directory_name = '' )
	{
		if( !empty( $directory_name ) && !in_array( $directory_name, $this->blacklisted_directory_names ) ) {
			Tools::showDebug( 'Blacklisting path: ' . $directory_name );

			array_push( $this->blacklisted_directory_names, $directory_name );
		}
	}


	public function isPathBlacklisted( $directory_name = '' )
	{
		return !empty( $directory_name ) && in_array( $directory_name, $this->blacklisted_directory_names );
	}


	public function addPath( $directory_name = '', $url_suffix = '' )
	{
		// throw new Exception( 'add_path()' );

		if( !isset($directory_name) || empty( $directory_name ) ) {
			Tools::showError( __FUNCTION__ . ' $directory_name cannot be null.' );
		}
		else if( !is_dir($directory_name ) ) {
			Tools::showError( __FUNCTION__ . ' "' . $directory_name . '" is not a directory.' );
		}
		// else if( in_array( $directory_name, $this->directory_names ) ) {
		// 	// Do nothing.
		// }
		else {
			// Tools::showDebug( 'Adding path: ' . $directory_name );
			// array_push( $this->directory_names, $directory_name );
			array_push(
				$this->directories,
				array(
					'path' => $directory_name,
					'url_suffix' => $url_suffix
				)
			);
		}
	}


	public function addChildDirectories( $directory_name, $url_suffix = '' )
	{
		if( !empty( $directory_name ) )
		{
			$entries = scandir ( $directory_name );
			$entries = array_diff( $entries, array( '.', '..' ) );

			foreach( $entries as $entry )
			{
				$full_path = Tools::joinPaths( $directory_name, $entry );

				if( is_dir( $full_path ) ) {
					// TODO: Check to see if $url_suffix already has a '/' at
					// the end.
					$this->addPath( $full_path, $url_suffix . '/' . $entry );
				}
			}
		}
	}


	// -------------------------------------------------------------------------
	//
	// Plugins
	//
	// -------------------------------------------------------------------------


	const PLUGINS_DIRECTORY_NAME = '../plugins';
	const PLUGIN_FILE_NAME = 'plugin.php';


	private function loadPlugins()
	{
		$plugins_path = realpath( Tools::joinPaths( dirname( __FILE__ ), self::PLUGINS_DIRECTORY_NAME ) );

		if( !is_dir( $plugins_path ) ) {
			Tools::showError( 'Plugins directory not found: ' . $plugins_path );
		}
		else {
			// Tools::showDebug( 'Loading plugins from: ' . $plugins_path );

			$paths = Tools::getChildPaths( $plugins_path );

			foreach( $paths as $path )
			{
				if( is_dir( $path ) )
				{
					$plugin_file_name = Tools::joinPaths( $path, self::PLUGIN_FILE_NAME );

					if( is_file( $plugin_file_name ) ) {
						// Tools::showDebug( 'Loading plugin: ' . $plugin_file_name );

						include_once( $plugin_file_name );
					}
				}
			}
		}
	}


	// -------------------------------------------------------------------------
	//
	// Scanners.
	//
	// -------------------------------------------------------------------------


	public const HIGH_SCANNER_PRIORITY = 10;
	public const DEFAULT_SCANNER_PRIORITY = 5;
	public const LOW_SCANNER_PRIORITY = 0;


	private static $scanners = array();


	public static function registerScanner( $scanner = NULL, $priority = self::DEFAULT_SCANNER_PRIORITY )
	{
		if( is_null( $scanner ) ) {
			$Tools::showDebug( 'Failed to register scanner. $scanner cannot be null.' );
		}
		else {
			// Tools::dumpArray( $scanner );
			Tools::showDebug( 'Registering scanner: Class=' . get_class( $scanner ) . ' Pri=' . $priority );

			array_push(
				self::$scanners,
				array(
					$priority,
					$scanner
				)
			);
		}
	}


	public static function getScanners()
	{
		return self::$scanners;
	}


	// -------------------------------------------------------------------------
	//
	// Page header and footer.
	//
	// -------------------------------------------------------------------------


	private $number_of_columns = 4;
	private $grid_width = 12;
	private $column_grid_span = 0;
	private $is_config_editor_enabled = false;


	public function writePageHeader()
	{

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

   <script type="text/javascript" charset="utf-8" async defer>
   		// Note yet implemented for the configuration editor.
	    //   	$(document).ready(function(){
		//       var i=1;
		//      $("#add_row").click(function(){b=i-1;
		//       $('#addr'+i).html($('#addr'+b).html()).find('td:first-child').html(i+1);
		//       $('#tab_logic').append('<tr id="addr'+(i+1)+'"></tr>');
		//       i++; 
		//   });
		//      $("#delete_row").click(function(){
		//     	 if(i>1){
		// 		 $("#addr"+(i-1)).html('');
		// 		 i--;
		// 		 }
		// 	 });
		// });

	</script>

    <title>Sites @ <?php echo $_SERVER['HTTP_HOST']; ?></title>
</head>
<body class="bg-dark text-white">

	<nav class="navbar navbar-expand-sm bg-dark navbar-dark sticky-top">
<?php
		if( $this->is_config_editor_enabled)
		{
?>
		<ul class="navbar-nav">
			<li class="nav-item">
				<button id="toggle-menu-button" data-toggle="collapse" data-target="#demo" class="btn btn-primary"><i id="main-menu-button-icon" class="fa fa-chevron-circle-down"></i></button>
			</li>
		</ul>
<?php
		}
?>
		<ul class="nav navbar-nav navbar-logo mx-auto">
			<li class="nav-item"><h4><?php echo $_SERVER['HTTP_HOST']; ?></h4></li>
		</ul>
	</nav>
<?php
		
		if( $this->is_config_editor_enabled )
		{
?>

<script type="text/javascript">
$('#toggle-menu-button').click(function(){
    $(this).find('#main-menu-button-icon').toggleClass('fa-chevron-circle-down fa-chevron-circle-up');
});
</script>

	<div id="demo" class="collapse">

		<div class="container mb-5 mt-5">
    		<h3>Scanned Directories</h3>
			<table class="table table-striped table-dark" id="tab_logic">
				<tbody>
					<tr id='addr0'>
						<td class="align-middle">
							<input type="text" name="path[]" placeholder="Path" class="form-control"/>
						</td>
						<td class="align-middle">
							<input type="email" name="urlSuffuxes[]" placeholder="URL Suffix" class="form-control"/>
						</td>
						<td class="align-middle">
							<div class="custom-control custom-checkbox">
								<input name="isScanned[]" type="checkbox" class="custom-control-input" id="customCheck1" checked>
								<label class="custom-control-label" for="customCheck1">Scan this dir?</label>
							</div>
						</td>
						<td class="align-middle">
							<div class="custom-control custom-checkbox">
								<input name="areChildrenScanned[]" type="checkbox" class="custom-control-input" id="customCheck2" checked>
								<label class="custom-control-label" for="customCheck2">Scan children?</label>
							</div>
						</td>
						<td class="align-middle">
							<button class="btn btn-danger float-right"><i class="fa fa-trash"></i></button>
						</td>
					</tr>
                    <tr id='addr1'></tr>
				</tbody>
			</table>

			<!--
				</div>
			</div>
			-->
			<!--<button id="add_row" class="btn btn-default pull-left">Add Row</button><button id='delete_row' class="pull-right btn btn-default">Delete Row</button>-->
			<div class="clearfix">
				<button class="btn btn-light"><i class="fa fa-plus"></i>&nbsp;Add Directory</button>
				<button class="btn btn-light float-right"><i class="fa fa-floppy-o"></i>&nbsp;Save</button>
			</div>
		</div>

	</div>

	<div style="clear: both;"></div>

	<?php

		}

	}

	
	public function writePageFooter()
	{
?></body></html><?php
	}


	// -------------------------------------------------------------------------
	//
	// Content.
	//
	// -------------------------------------------------------------------------


	private const HEADER_ICON_SIZE = 'fa-lg';

	private const VALID_COLUMN_COUNTS = array( 1, 2, 3, 4, 6, 12 );


	public function writeSitesHeader()
	{
		if( !in_array( $this->number_of_columns, self::VALID_COLUMN_COUNTS ) ) {
			Tools::showError( "number_of_columns must be one of: " . implode( ', ', SELF::VALID_COLUMN_COUNTS ). '. Current value is "' . $this->number_of_columns . '"' );
		}

		$this->column_grid_span = $this->grid_width / $this->number_of_columns;

    	// printf( '<div class="container-fluid p-%d my-%d">', $this->number_of_columns, $this->number_of_columns );
    	printf( '<div class="container-fluid">' );
	}


	
	public function writeSitesFooter()
	{
    	printf( '</div>' );
	}


	public function writeSitesBody()
	{
		if( $this->number_of_columns > 0 )
		{
			$column_index = 0;

			Tools::showDebug( 'column_grid_span: ' . $this->column_grid_span );

			if( count( $this->site_definitions ) > 0 )
			{
				$is_row_open = false;
				// printf( '<div class="row">' );

				foreach( $this->site_definitions as $site_definition )
				{
					if( $site_definition->is_valid )
					{
						if( ! $is_row_open ) {
							echo '<div class="row">';
							$is_row_open = true;
						}

						$this->writeSite( $site_definition );

						++$column_index;
						if( $column_index >= $this->number_of_columns )
						{
							printf( '</div>' );
							$column_index = 0;
							$is_row_open = false;
						}
					}
				}

				if( $is_row_open ) {
					printf( '</div>' );
				}
			}
			else
			{
				// ...
			}
		}
	}


	public function writeSite( $site_definition )
	{
		Tools::throwIfNotInstanceOf( $site_definition, Scanner::SITE_DEFINITION_CLASS_NAME, __FUNCTION__ );


		printf( '<div class="col-lg-%s mb-3">', $this->column_grid_span );
		echo '<div class="card bg-light text-dark h-100">';


      	// Card header.
      	$icon_html = '';
      	if( !empty( $site_definition->icon_name ) ) {
      		$icon_html = '<i class="float-right fa ' . self::HEADER_ICON_SIZE .' fa-' . $site_definition->icon_name . '"></i>';
      	}

		echo '<div class="card-header">';
      	printf( '<h4 class="card-title">%s%s</h4>', $site_definition->title, $icon_html );
      	echo '</div>';


      	// Body.
      	$key_value_pairs = $site_definition->getKeyValuePairs();
      	if( count( $key_value_pairs ) > 0 )
      	{
	      	echo '<div class="card-body">';
	      	echo '<table class="table-sm">';
	      	echo '<tbody>';

	      	foreach( $key_value_pairs as $key_value_pair ) {
	      		echo '<tr>';
	      		printf( '<td><i class="fa fa-%s"></i></td><td>%s</td>', $key_value_pair[0], $key_value_pair[1] );
	      		echo '</tr>';
	      	}
	      	
	      	echo '</tbody>';
	      	echo '</table>';
	      	echo '</div>';
      	}


      	// Footer - links.
      	$links = $site_definition->getLinks();
      	if( count( $links ) > 0 )
      	{
      		echo '<div class="card-footer">';

      		$link_index = 0;
      		foreach( $links as $link )
      		{
	            $classes = 'btn';
	            $text = $link['text'];

	            if( $link['is_primary'] ) {
	              $classes .= ' btn-primary';
	              $text = '<i class="fa fa-globe"></i>&nbsp;' . $text;
	            }
	            elseif( $link['is_admin'] ) {
	              $classes .= ' btn-warning float-right';
	            }
	            else {
	              // ...
	            }

	            if( $link_index > 0 ) {
	              echo '&nbsp;';
	            }

	            printf( '<a href="%s" class="%s" role="button">%s</a>', $link['url'], $classes, $text );

	            ++$link_index;
      		}

      		echo '</div>';
      	}


      	echo '</div>';	// Card.
      	echo '</div>';	// Column.
	}


	
}

?>
