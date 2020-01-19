<?php


/**
 * Describes a site definition, and whether the path contains a valid site or
 * not.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


class SiteDefinition
{


	public $is_valid = false;
	public $full_path = '';
	public $title = '';
	public $icon_name = '';
	public $url_suffix = '';


	function __construct()
	{
		// ...
	}


	private $key_value_pairs = array();


	public function addKeyValuePair( $key, $value )
	{
		array_push(
			$this->key_value_pairs,
			array(
				$key,
				$value
			)
		);
	}


	public function getKeyValuePairs()
	{
		return $this->key_value_pairs;
	}


	private $links = array();
	private $has_main_link_been_added = false;
	private $has_admin_link_been_added = false;


	public function setMainLink( $url )
	{
		$this->has_main_link_been_added = true;
		$this->addLink( 'Visit', $url, true, false );
	}


	public function setAdminLink( $url )
	{
		$this->has_admin_link_been_added = true;
		$this->addLink( 'Admin', $url, false, true );
	}


	public function addLink( $text, $url, $is_primary = true, $is_admin = false )
	{
		array_push(
			$this->links,
			array(
				'text' => $text,
				'url' => $url,
				'is_primary' => $is_primary,
				'is_admin' => $is_admin
			)
		);
	}


	public function getLinks()
	{
		if( ! $this->has_main_link_been_added && !empty( $this->url_suffix ) ) {
			$this->setMainLink( $this->url_suffix );
		}

		return $this->links;
	}



}


?>