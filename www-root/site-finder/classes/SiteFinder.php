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


	public const APP_NAME = 'Site Finder';
	public const APP_VERSION = '1.1';
	public const APP_SOURCE_URL = 'https://github.com/headwalluk/site-finder';


	function __construct()
	{
		set_exception_handler( array( 'Tools', 'exceptionHandler' ) );

		$this->config_file_name = Tools::joinPaths( getcwd(), self::CONFIG_FILE_NAME );
	}


	private const CONFIG_FILE_NAME = 'site-finder-settings.json';

	private $config_file_name = NULL;


	public static function getVersionString()
	{
		return self::APP_NAME . ' v' . self::APP_VERSION;
	}


	public function initialise()
	{
		// Reset everything.
		$this->clearDirectories();

		// Scan children of the start-up direcctory.
		$this->addChildDirectories( getcwd() );

		// Blacklist the site-finder subdirectory.
		$this->blacklistPath( Tools::joinPaths( getcwd(), 'site-finder' ) );

		try
		{
			$this->loadConfigurationFile( $this->config_file_name );
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
	// Process post data.
	//
	// -------------------------------------------------------------------------


	public function processPostData()
	{
		if( $_SERVER["REQUEST_METHOD"] == "POST") {

			// Diagnostics.
			// print_r( $_POST );

			$paths = array();
			$url_suffixes = array();
			$is_scanned = array();
			$are_children_scanned = array();

			if( isset( $_POST['paths'] ) && is_array( $_POST['paths'] ) ) {
				$paths = $_POST["paths"];
			}

			$path_count = count( $paths );

			if( isset( $_POST['urlSuffuxes'] ) && is_array( $_POST['urlSuffuxes'] ) ) {
				$url_suffixes = $_POST["urlSuffuxes"];
				
				while( count( $url_suffixes ) < $path_count ) {
					array_push( $url_suffixes, '' );
				}
			}

			if( isset( $_POST['isScanned'] ) && is_array( $_POST['isScanned'] ) ) {
				$is_scanned = $_POST["isScanned"];
				
				while( count( $is_scanned ) < $path_count ) {
					array_push( $is_scanned, 'yes' );
				}
			}

			if( isset( $_POST['areChildrenScanned'] ) && is_array( $_POST['areChildrenScanned'] ) ) {
				$are_children_scanned = $_POST["areChildrenScanned"];
				
				while( count( $are_children_scanned ) < $path_count ) {
					array_push( $are_children_scanned, 'no' );
				}
			}


			$path_index = 0;

			$configuration = array();

			$configuration['number_of_columns']			= $this->number_of_columns;
			$configuration['is_config_editor_enabled']	= $this->is_config_editor_enabled;
			$configuration['directories']				= array();

			if( isset( $_POST['numberOfDisplayColumns'] ) ) {
				$configuration['number_of_columns'] = intval( $_POST['numberOfDisplayColumns'] );
			}

			if( isset( $_POST['isEditorEnabled'] ) ) {
				$configuration['is_config_editor_enabled'] = true;
			}
			else {
				$configuration['is_config_editor_enabled'] = false;	
			}

			for( $path_index = 0; $path_index < $path_count; ++$path_index )
			{
				$path = $paths[$path_index];

				if( !empty( $path ) ) {
					$url_suffix = $url_suffixes[$path_index];

					if( empty( $url_suffix ) ) {
						$url_suffix = '';
					}

					array_push(
						$configuration['directories'],
						array(
							'path'						=> $path,
							'url_suffix'				=> $url_suffix,
							'is_scanned'				=> ( $is_scanned[$path_index] === 'yes' ),
							'are_children_scanned'		=> ( $are_children_scanned[$path_index] === 'yes' )
						)
					);
				}

				// echo $path . "\n";
			}

			$serialised_configuration = json_encode( $configuration );

			// 	print_r( $serialised_configuration );
			
			if( file_exists( $this->config_file_name ) ) {
				unlink( $this->config_file_name );
			}
			
			file_put_contents( $this->config_file_name, $serialised_configuration );
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


			$this->writeExistingPathsScript();
		}
	}


	private function writeExistingPathsScript()
	{
		if( count( $this->directories ) > 0 )
		{
			?>
<script>
	$(function() {
			<?php

			if( isset( $this->configuration['directories'] ) && is_array( $this->configuration['directories'] ) )
			{
				foreach( $this->configuration['directories'] as $directory ) {

					printf(
						'addDirectory( "%s", "%s", %s, %s )',
						$directory['path'],
						$directory['url_suffix'],
						$directory['is_scanned'] ? "true" : "false",
						$directory['are_children_scanned'] ? "true" : "false"
					);

					printf( "\n" );
				}
			}

			?>
	});
</script>
			<?php
		}
	}


	// -------------------------------------------------------------------------
	//
	// Configuration management.
	//
	// -------------------------------------------------------------------------


	private $configuration = array();
	private $does_configuration_file_exist = false;
	private $is_configuration_writable = false;


	public function loadConfigurationFile( $file_name )
	{
		if( !is_file( $file_name ) ) {

			$this->is_configuration_writable = is_writable( getcwd() );
		}
		else {
			// Tools::showDebug( 'Found config file: ' . $file_name );
			$this->does_configuration_file_exist = true;
			$this->is_configuration_writable = is_writable( $file_name );

			$directories = array();

			try
			{
				$serialised_configuration = file_get_contents( $file_name, true );

				$this->configuration = json_decode( $serialised_configuration, true );

				if( !isset( $this->configuration ) ) {
					throw new Exception( 'Failed to parse configuration file: "' . $file_name . '"' );
				}

				if( isset( $this->configuration['number_of_columns'] ) ) {
					$this->number_of_columns = $this->configuration['number_of_columns'];
				}

				if( isset( $this->configuration['is_config_editor_enabled'] ) ) {
					$this->is_config_editor_enabled = $this->configuration['is_config_editor_enabled'];
				}

				// Tools::dumpArray( $this->configuration );

				foreach( $this->configuration['directories'] as $directory ) {
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
	private $is_config_editor_enabled = true;


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

    <script src="site-finder/assets/directory-list-editor.js"></script>
    <link rel="stylesheet" href="site-finder/assets/site-finder.css">

    <title>Sites @ <?php echo $_SERVER['HTTP_HOST']; ?></title>
</head>
<body class="bg-dark text-white">

	<!--<nav class="navbar navbar-expand-sm bg-dark navbar-dark">-->
	<nav class="navbar navbar-expand-sm navbar-dark bg-dark" role="navigation">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item">
<?php
		if( $this->is_config_editor_enabled )
		{
?>
				<button id="toggle-menu-button" data-toggle="collapse" data-target="#demo" class="btn btn-primary"><i id="main-menu-button-icon" class="fa fa-bars"></i></button>
<?php
		}
?>
			</li>
		</ul>
		<ul class="navbar-nav mx-auto">
			<li class="nav-item"><a class="nav-link" href="<?php echo Tools::getRequestUrl(); ?>"><?php echo $_SERVER['HTTP_HOST']; ?></a></li>
		</ul>
		
		<ul class="navbar-nav ml-auto">
			<li class="nav-item"><a class="nav-link" href="<?php echo self::APP_SOURCE_URL; ?>"><i class="fa fa-sm fa-github"></i>&nbsp;<?php echo self::APP_NAME ?> <small>v<?php echo self::APP_VERSION ?></small></a></li>
		</ul>
	</nav>

<?php
		
		if( $this->is_config_editor_enabled )
		{
?>

	<div id="demo" class="collapse pl-5 pr-5">

		<form method="post">
			<div class="container table-responsive mb-5 mt-5">
<?php
			if( !$this->does_configuration_file_exist ) {
				Tools::showWarning( 'Configuration file "' . self::CONFIG_FILE_NAME . '" does not (yet) exist.' );
			}

			if( !$this->is_configuration_writable ) {
				Tools::showError( 'Configuration file "' . self::CONFIG_FILE_NAME . '" is not writable.' );
			}
?>
				<h3>General Options</h3>
				<!-- <div class="container-fluid"> -->
				<div class="row">
					<div class="col-sm-2 form-group">
						<label for="numberOfDisplayColumns" class="control-label">Display Columns</label>
				        <select class="form-control" name="numberOfDisplayColumns" id="numberOfDisplayColumns">
<?php
			foreach( self::VALID_COLUMN_COUNTS as $number_of_columns ) {
				$selected_property = '';

				if( $number_of_columns === $this->number_of_columns ) {
					$selected_property = ' selected="selected"';
				}

				printf( '<option value="%s"%s>%s</option>', $number_of_columns, $selected_property, $number_of_columns );
			}
?>
				        </select>
			        </div>
			        <div class="col-sm-8 form-group">
			        	<p>&nbsp;</p>
			        	<label class="custom-control custom-checkbox">
				        	Enable Configuration Editor
                            <input name="isEditorEnabled" type="checkbox" class="custom-control-input" checked />
                            <span class="custom-control-indicator"></span>
                        </label>
                        <p><span class="badge badge-danger">IMPORTANT</span>&nbsp;If you disable the editor then you will need to manually edit <strong><?php echo self::CONFIG_FILE_NAME; ?></strong> to re-enable it.</p>
			        </div>
			        <div class="col-sm-2 form-group">
			        	<p>&nbsp;</p>
			        	<button class="btn btn-primary float-right w-100" type="submit"><i class="fa fa-floppy-o"></i>&nbsp;Save</button>
			        </div>
			    </div>
				<!-- </div> -->

				<div class="row mt-5">
			        <div class="col-sm-10 form-group">
			        	<h3>Scanned Directories</h3>
			        </div>
			        <div class="col-sm-2 form-group">
			        	<button id="addDirectoryButton" class="btn btn-light float-right w-100" type="button"><i class="fa fa-plus"></i>&nbsp;Add Directory</button>
			        </div>
				</div>

	    		
				<table id="directories-table" class="table table-striped table-dark m-0">
					<tbody>
						<tr id='directory-template-row' style="display: none;">
							<td class="align-middle">
								<!-- <input type="hidden" name="directoryIndex"/> -->
								<input type="text" name="paths[]" placeholder="/home/USER_NAME/public_html" class="form-control"/>
							</td>
							<td class="align-middle">
								<input type="text" name="urlSuffuxes[]" placeholder="~USER_NAME" class="form-control"/>
							</td>
							<td class="align-bottom">
	                            <label class="custom-control custom-checkbox">
	                            	Scan this directory?
	                            	<input name="isScanned[]" type="hidden" value="yes" />
	                                <input name="isScannedCb" type="checkbox" class="custom-control-input" checked />
	                                <span class="custom-control-indicator"></span>
	                            </label>
							</td>
							<td class="align-bottom">
	                            <label class="custom-control custom-checkbox">
	                            	Scan child directories?
	                            	<input name="areChildrenScanned[]" type="hidden" value="yes" />
	                                <input name="areChildrenScannedCb" type="checkbox" class="custom-control-input" checked />
	                                <span class="custom-control-indicator"></span>
	                            </label>
							</td>
							<td class="align-middle">
								<button name="removeDirectoryButton" class="btn btn-danger float-right"><i class="fa fa-trash"></i></button>
							</td>
						</tr>
					</tbody>
				</table>

				<div class="clearfix">
					
					
				</div>
			</div>
		</form>

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


	public function writeSites()
	{
		$this->writeSitesHeader();
		$this->writeSitesBody();
		$this->writeSitesFooter();
	}


	private function writeSitesHeader()
	{
		if( !in_array( $this->number_of_columns, self::VALID_COLUMN_COUNTS ) ) {
			Tools::showError( "number_of_columns must be one of: " . implode( ', ', SELF::VALID_COLUMN_COUNTS ). '. Current value is "' . $this->number_of_columns . '"' );
		}

		$this->column_grid_span = $this->grid_width / $this->number_of_columns;

    	// printf( '<div class="container-fluid p-%d my-%d">', $this->number_of_columns, $this->number_of_columns );
    	printf( '<div class="container-fluid">' );
	}


	
	private function writeSitesFooter()
	{
    	printf( '</div>' );
	}


	private function writeSitesBody()
	{
		if( $this->number_of_columns > 0 )
		{
			$column_index = 0;

			Tools::showDebug( 'column_grid_span: ' . $this->column_grid_span );

			$found_sites_count = 0;

			if( count( $this->site_definitions ) > 0 )
			{
				$is_row_open = false;
				// printf( '<div class="row">' );

				foreach( $this->site_definitions as $site_definition )
				{
					if( $site_definition->is_valid )
					{
						++$found_sites_count;

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
			
			if( $found_sites_count == 0 ) {
				$this->writeNoSitesFound();
			}
		}
	}


	private function writeNoSitesFound()
	{
?>
<div class="jumbotron bg-dark text-light text-center">
  <h1>No Sites Found</h1>
</div>
<script>
	isAutoMenuEnabled = true;
</script>
<?php
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
