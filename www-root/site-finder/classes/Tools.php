<?php


/**
 * Some useful tools for use within Site Finder and plug-ins.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class Tools
{


	// -------------------------------------------------------------------------
	//
	// Class helpers.
	//
	// -------------------------------------------------------------------------


	public static function throwIfNotInstanceOf( $object = NULL, $class_name = '', $calling_function_name = '' )
	{
		if( is_null( $object ) ) {
			throw new Exception( __FUNCTION__ . ': $object cannot be null.' );
		}
		else if( empty( $class_name ) ) {
			throw new Exception( __FUNCTION__ . ': $class_name cannot be null.' );	
		}
		else if( !is_a( $object, $class_name ) ) {
			$message = 'Object is the wrong type. It should be a "' . $class_name . '" but it is a "' . gettype( $object ) . '".';

			if( empty( $calling_function_name ) ) {
				throw new Exception( $message );
			}
			else {
				throw new Exception( $calling_function_name . '() ' . $message );
			}
		}
		else
		{
			// Ok.
		}
	}


	// -------------------------------------------------------------------------
	//
	// Package helpers.
	//
	// -------------------------------------------------------------------------


	public static function loadJsonPackage( $directory_name = '' )
	{
		$package = NULL;

		if( !empty( $directory_name ) )
		{
			$file_name = self::joinPaths( $directory_name, 'package.json' );

			if( is_file( $file_name ) )
			{
				$serialised_package = file_get_contents( $file_name, true );
				$package = json_decode( $serialised_package, true );
				
				if( is_null( $package ) ) {
					self::showError( 'Error decoding JSON file: ' . $file_name );
				}
			}
		}

		if( empty( $package ) ) {
			$package = array();
		}

		return $package;
	}


	// -------------------------------------------------------------------------
	//
	// File system helpers.
	//
	// -------------------------------------------------------------------------


	public static function getRequestUrl()
	{
		// return '#';
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}


	// -------------------------------------------------------------------------
	//
	// File system helpers.
	//
	// -------------------------------------------------------------------------


	public static function joinPaths()
	{
		$is_rooted = false;
		$args = func_get_args();
		$paths = array();
		$is_first_arg= true;
		foreach ($args as $arg)
		{
			if( $is_first_arg && substr( $arg, 0, 1 ) === "/" ) {
				$is_rooted = true;
			}

			$paths = array_merge($paths, (array)$arg);
			$is_first_arg = false;
		}

		$paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
		$paths = array_filter($paths);

		$combined_path = join('/', $paths);

		if( $is_rooted ) {
			$combined_path = '/' . $combined_path;
		}

		return $combined_path;
	}


	public static function getChildPaths( $path )
	{
		$entries = scandir( $path );
		$entries = array_diff( $entries, array( '.', '..' ) );

		$paths = array();

		if( is_array( $entries ) )
		{
			foreach( $entries as $entry ) {
				array_push( $paths, self::joinPaths( $path, $entry ) );
			}
		}

		return $paths;
	}


	// -------------------------------------------------------------------------
	//
	// String helpers.
	//
	// -------------------------------------------------------------------------


	function startsWith( $haystack, $needle )
	{
		$length = strlen($needle);

		return (substr($haystack, 0, $length) === $needle);
	}


	function endsWith( $haystack, $needle )
	{
		$length = strlen($needle);

		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}


	// -------------------------------------------------------------------------
	//
	// Diagnostic messages.
	//
	// -------------------------------------------------------------------------


	private static $is_debug_enabled = false;


	public static function exceptionHandler( $exception )
	{
		if( is_null( $exception ) ) {
			self::showError( 'Exception handler called with a $exception set as NULL.' );
		}
		else {
?><div class="alert alert-danger alert-dismissible fade show">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<table class='table-sm'>
		<tbody>
			<tr>
				<td><em>File</em></td>
				<td><?php echo $exception->getFile(); ?></td>
			</tr>
			<tr>
				<td><em>Line</em></td>
				<td><?php echo $exception->getLine(); ?></td>
			</tr>
			<tr>
				<td><em>Message</em></td>
				<td><?php echo $exception->getMessage(); ?></td>
			</tr>
		</tbody>
	</table>
</div><?php
		}
	}


	public static function enableDebug()
	{
		self::$is_debug_enabled = true;
	}


	public static function disableDebug()
	{
		self::$is_debug_enabled = false;
	}


	public static function showInfo( $message )
	{
?><div class="alert alert-info alert-dismissible fade show">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<?php echo $message; ?>
</div><?php
	}


	public static function showDebug( $message )
	{
		if( self::$is_debug_enabled )
		{
?><div class="alert alert-warning alert-dismissible fade show">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<?php echo $message; ?>
</div><?php
		}
	}


	public static function showError( $message )
	{
?><div class="alert alert-danger alert-dismissible fade show">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<?php echo $message; ?>
</div><?php
	}


	public static function dumpArray( $array )
	{
?><div class="alert alert-secondary alert-dismissible fade show">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
<pre>
<?php print_r( $array ); ?>
</pre>
</div><?php
	}


}


?>