<?php


/**
 * The WordPress site scanner. This will attempt to open a database connection
 * based on the contents of wp-config.php.
 *
 * IMPORTANT: This has not been extensively tested.
 * TODO: Replace the shell_exec() with a pure PHP parser for wp=config.php.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class WordPressScanner extends Scanner
{


	function __construct()
	{
		// ...
	}


	const WP_CONFIG_FILE_NAME = 'wp-config.php';


	public function scan( $site_definition )
	{
		$this->throwIfNotSiteDefinition( $site_definition, __FUNCTION__ );

		$config_file_name = Tools::joinPaths( $site_definition->full_path, self::WP_CONFIG_FILE_NAME );

		if( is_file( $config_file_name ) )
		{
			$site_definition->title = 'A Wordpress Site';
			$site_definition->icon_name = 'wordpress';

		    $db_name = $this->getSettingFromWpConfig( $config_file_name, 'DB_NAME' );
		    $db_user = $this->getSettingFromWpConfig( $config_file_name, 'DB_USER' );
		    $db_pass = $this->getSettingFromWpConfig( $config_file_name, 'DB_PASSWORD' );
		    $db_host = $this->getSettingFromWpConfig( $config_file_name, 'DB_HOST' );
		    if( $db_host === 'localhost' ) {
		    	$db_host = '127.0.0.1';
		    }

		    $site_definition->addKeyValuePair( 'server', $db_host );
		    $site_definition->addKeyValuePair( 'database', $db_name );
		    $site_definition->addKeyValuePair( 'user', $db_user );

		    $socket = NULL;

		    // Extract socket information from FB_HOST.
		    if( ( $delimiter_index = strpos( $db_host, ':' ) ) > 0 ) {
		    	$socket = substr( $db_host, $delimiter_index + 1 );
		    	$db_host = substr( $db_host, 0, $delimiter_index );
		    }

	        $conn = new mysqli( $db_host, $db_user, $db_pass, $db_name, NULL, $socket );
		    if( $conn->connect_error ) {
		        Tools::showError( 'Connection failed: ' . $conn->connect_error );
		    }
		    else
		    {
		    	$site_url = '';

				$sql = 'select option_name,option_value from wp_options where option_name=\'blogname\' or option_name like \'%url%\'';
				$result = mysqli_query( $conn, $sql );
				if (mysqli_num_rows($result) > 0)
				{
					while( $row = mysqli_fetch_assoc( $result ) )
					{
						switch( $row['option_name'] )
						{
							case 'blogname':
								$site_definition->title = $row['option_value'];
								break;

							case 'siteurl':
								$site_url = $row['option_value'];
								break;

							default:
								break;
						}
					}
				}

				$conn->close();

				if( !empty( $site_url ) ) {
					$site_definition->setMainLink( $site_url );
					$site_definition->setAdminLink( $site_url . '/wp-admin' );
				}

				$site_definition->is_valid = true;
		    }
		}
	}


	private function getSettingFromWpConfig( $file_name, $key_name )
	{
	    // return trim( shell_exec( 'grep -o "^define.*' . $key_name . '\'.*\'" ' . $file_name . ' | awk -F, \'{print $2}\' | tr -d "\'" | tr -d \' \'' ) );
	    return trim( shell_exec( 'grep -o "^define.*' . $key_name . '\'.*\'" ' . $file_name . ' | awk -F, \'{print $2}\' | tr -d "\' "' ) );
	}


}


?>