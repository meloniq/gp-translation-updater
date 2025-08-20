<?php
/**
 * Themes Updater class for handling updates.
 *
 * @package Meloniq\GpTranslationUpdater
 */

namespace Meloniq\GpTranslationUpdater;

/**
 * Themes Updater class for handling updates.
 */
class Themes_Updater extends Updater {

	/**
	 * The WP API URL for updates.
	 *
	 * @var string
	 */
	protected $wp_api_url = 'https://api.wordpress.org/themes/update-check/1.1/';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'extra_theme_headers', array( $this, 'extra_headers' ) );

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

		$themes = $this->decode( $r['body']['themes'] );
		if ( empty( $themes ) ) {
			return $r;
		}

		$this->locale = isset( $r['body']['locale'] ) ? $this->decode( $r['body']['locale'] ) : array();
		if ( ! is_array( $this->locale ) ) {
			$this->locale = array();
		}

		$themes_to_check = $this->get_marked_themes();

		foreach ( $themes['themes'] as $name => $info ) {
			if ( ! is_array( $info ) ) {
				continue;
			}

			if ( ! array_key_exists( $name, $themes_to_check ) ) {
				continue;
			}

			$info['GlotPress API URI']  = $themes_to_check[ $name ]['uri'];
			$info['GlotPress API Path'] = $themes_to_check[ $name ]['path'];

			$this->items[ $name ] = $info;
		}

		return $r;
	}

	/**
	 * Get themes that have the 'GlotPress API URI' and 'GlotPress API Path' header,
	 * since it's not passed to the updater request.
	 *
	 * @return array
	 */
	protected function get_marked_themes() {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			return array();
		}

		$marked = array();

		foreach ( wp_get_themes() as $key => $theme ) {
			if ( $theme->get( 'GlotPress API URI' ) ) {
				$marked[ $key ] = array(
					'uri'  => $theme->get( 'GlotPress API URI' ),
					'path' => $theme->get( 'GlotPress API Path' ),
				);
			}
		}

		return $marked;
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

		$themes = $this->decode( $response['body'] );
		if ( ! is_array( $themes ) ) {
			$themes = array();
		}

		// Check for updates for each item.
		foreach ( $items as $key => $item ) {

			$gp_updates = $this->check_for_updates( $item );
			if ( ! $gp_updates ) {
				continue;
			}

			$prepared_updates = $this->prepare_update_response( $gp_updates, $item, $key );
			foreach ( $prepared_updates as $update_key => $update ) {
				$themes['translations'][] = $update;
			}
		}

		$response['body'] = $this->encode( $themes );

		return $response;
	}

	/**
	 * Prepare update response.
	 *
	 * @param array  $gp_updates The updates to apply.
	 * @param array  $item The item to prepare.
	 * @param string $slug The slug of the item.
	 *
	 * @return array Prepared update response.
	 */
	protected function prepare_update_response( $gp_updates, $item, $slug ) {
		$updates = array();
		foreach ( $gp_updates as $gp_update ) {
			$updates[] = array(
				'type'       => 'theme',
				'slug'       => $slug,
				'language'   => $gp_update['language'],
				'version'    => $item['Version'],
				'updated'    => $gp_update['updated'],
				'package'    => $this->prepare_download_url( $gp_update['package'], $item, $slug ),
				'autoupdate' => true,
			);
		}

		return $updates;
	}
}
