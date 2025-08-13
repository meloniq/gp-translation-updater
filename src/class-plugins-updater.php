<?php
/**
 * Plugins Updater class for handling updates.
 *
 * @package Meloniq\GpTranslationUpdater
 */

namespace Meloniq\GpTranslationUpdater;

/**
 * Plugins Updater class for handling updates.
 */
class Plugins_Updater extends Updater {

	/**
	 * The WP API URL for updates.
	 *
	 * @var string
	 */
	protected $wp_api_url = 'https://api.wordpress.org/plugins/update-check/1.1/';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'extra_plugin_headers', array( $this, 'extra_headers' ) );

		parent::__construct();
	}

	/**
	 * Collect items from the HTTP request.
	 *
	 * @param array  $r   The request arguments.
	 * @param string $url The request URL.
	 *
	 * @return array Modified request arguments.
	 */
	public function collect_items( $r, $url ) {
		if ( ! $this->is_wp_api_request( $url ) ) {
			return $r;
		}

		$plugins = (array) $this->decode( $r['body']['plugins'] );
		if ( empty( $plugins ) ) {
			return $r;
		}

		$this->locale = isset( $plugins['locale'] ) ? $plugins['locale'] : array();
		if ( ! is_array( $this->locale ) ) {
			$this->locale = array();
		}

		foreach ( $plugins['plugins'] as $slug => $info ) {
			if ( empty( $info['GlotPress API URI'] ) || empty( $info['GlotPress API Path'] ) ) {
				continue;
			}

			$this->items[ $slug ] = $info;
		}

		return $r;
	}

	/**
	 * Alters the update requests based on the response.
	 *
	 * @param mixed  $response The HTTP response.
	 * @param array  $args     The request arguments.
	 * @param string $url      The request URL.
	 *
	 * @return mixed Modified response.
	 */
	public function alter_update_requests( $response, $args, $url ) {
		if ( ! $this->is_wp_api_request( $url ) ) {
			return $response;
		}

		$items = $this->get_items();
		if ( empty( $items ) ) {
			return $response;
		}

		$plugins = $this->decode( $response['body'] );
		if ( ! is_array( $plugins ) ) {
			$plugins = array();
		}

		// Check for updates for each item.
		foreach ( $items as $key => $item ) {

			$gp_updates = $this->check_for_updates( $item );
			if ( ! $gp_updates ) {
				continue;
			}

			$plugins['translations'][ $key ] = $this->prepare_update_response( $item, $gp_updates );
		}

		$response['body'] = $this->encode( $plugins );

		return $response;
	}

	/**
	 * Prepare update response.
	 *
	 * @param array $item The item to prepare.
	 * @param array $updates The updates to apply.
	 *
	 * @return array Prepared update response.
	 */
	protected function prepare_update_response( $item, $updates ) {
		foreach ( $updates as $key => $update ) {
			$updates[ $key ]['type']       = 'plugin';
			$updates[ $key ]['slug']       = $item['TextDomain']; // todo: split key.
			$updates[ $key ]['version']    = $item['Version'];
			$updates[ $key ]['autoupdate'] = true;
		}

		return $updates;
	}
}
