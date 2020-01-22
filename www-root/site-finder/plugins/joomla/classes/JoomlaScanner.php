<?php


/**
 * The Joomla site scanner.
 *
 * IMPORTANT: This has not been extensively tested.
 * TODO: Replace the shell_exec() with a pure PHP parser for wp=config.php.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class JoomlaScanner extends Scanner
{


	function __construct()
	{
		// ...
	}


	const PACKAGE_FILE_NAME = 'index.php';
	const CONFIGURATION_FILE_NAME = 'configuration.php';
	const JOOMLA_PACKAGe_NAME = 'Joomla.Site';
	const ADMIN_URL_SUFFIX = 'administrator';


	public function scan( $site_definition )
	{
		$index_file_name = Tools::joinPaths( $site_definition->full_path, self::PACKAGE_FILE_NAME );
		$configuration_file_name = Tools::joinPaths( $site_definition->full_path, self::CONFIGURATION_FILE_NAME );

		if( is_file( $index_file_name ) && is_file( $configuration_file_name ) )
		{
			$package_nane = trim( shell_exec( 'grep "@package" "' . $index_file_name . '" | tr -d "*" | awk \'{print $2}\'' ) );

			if( !empty( $package_nane ) && ( $package_nane === self::JOOMLA_PACKAGe_NAME ) ) {
				$site_definition->title = 'A Joomla Site';
				$site_definition->icon_name = 'joomla';

			    $db_name = $this->getSettingFromJConfig( $configuration_file_name, 'db' );
			    $db_user = $this->getSettingFromJConfig( $configuration_file_name, 'user' );
			    // $db_pass = $this->getSettingFromJConfig( $configuration_file_name, 'password' );
			    $db_host = $this->getSettingFromJConfig( $configuration_file_name, 'host' );
			    // if( $db_host === 'localhost' ) {
			    // 	$db_host = '127.0.0.1';
			    // }

			    $site_name = $this->getSettingFromJConfig( $configuration_file_name, 'sitename' );
			    if( !empty( $site_name ) ) {
					$site_definition->title = $site_name;
			    }

			    $site_definition->addKeyValuePair( 'server', $db_host );
			    $site_definition->addKeyValuePair( 'database', $db_name );
			    $site_definition->addKeyValuePair( 'user', $db_user );

			    // $socket = NULL;

			    // We can extract everything that we need from configuration.php.
			    // We don't need to actually connect to the database to extract
			    // our meta data.
				// Extract socket information from FB_HOST.
				//   if( ( $delimiter_index = strpos( $db_host, ':' ) ) > 0 ) {
				//   	$socket = substr( $db_host, $delimiter_index + 1 );
				//   	$db_host = substr( $db_host, 0, $delimiter_index );
				//   }

				//      $conn = new mysqli( $db_host, $db_user, $db_pass, $db_name, NULL, $socket );
				//   if( $conn->connect_error ) {
				//       Tools::showError( 'Connection failed: ' . $conn->connect_error );
				//   }
				//   else
				//   {
				// $conn->close();
				//   }


				$site_url = $site_definition->url_suffix;
				$live_site_url = $this->getSettingFromJConfig( $configuration_file_name, 'live_site' );

				if( !empty( $live_site_url ) ) {
					$site_url = $live_site_url;
				}


				$admin_url = Tools::joinUrls( $site_url, self::ADMIN_URL_SUFFIX );

				$site_definition->setMainLink( $site_url );
				$site_definition->setAdminLink( $admin_url );

				$site_definition->is_valid = true;
			}
		}

		return;
	}


	private function getSettingFromJConfig( $file_name, $key_name )
	{
		// return trim( shell_exec( 'grep "^\s*public.*\$' . $key_name . '\s" "' . $file_name . '" | awk \'{print $4}\' | tr -d "\';"' ) );
		return trim( shell_exec( 'grep "^\s*public.*\$' . $key_name . '\s" "' . $file_name . '" | sed -n -e \'s/^.*\s=\s//p\' | tr -d "\';"' ) );
	}


}

?>