<?php
/**
 * Updater class for handling updates.
 *
 * @package Meloniq\GpTranslationUpdater
 */

namespace Meloniq\GpTranslationUpdater;

/**
 * Abstract class for handling updates.
 */
abstract class Updater {

	/**
	 * The WP API URL for updates.
	 *
	 * @var string
	 */
	protected $wp_api_url;

	/**
	 * The items to be updated.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * The locale for the updates.
	 *
	 * @var array
	 */
	protected $locale = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'http_request_args', array( $this, 'collect_items' ), 10, 2 );
		add_filter( 'http_response', array( $this, 'alter_update_requests' ), 10, 3 );
	}

	/**
	 * Collect items from the HTTP request.
	 *
	 * @param array  $r   The request arguments.
	 * @param string $url The request URL.
	 *
	 * @return array Modified request arguments.
	 */
	abstract public function collect_items( $r, $url );

	/**
	 * Alters the update requests based on the response.
	 *
	 * @param mixed  $response The HTTP response.
	 * @param array  $args     The request arguments.
	 * @param string $url      The request URL.
	 *
	 * @return mixed Modified response.
	 */
	abstract public function alter_update_requests( $response, $args, $url );

	/**
	 * Adds extra headers for the GlotPress API URI and Path.
	 *
	 * @param array $headers The existing headers.
	 *
	 * @return array Modified headers.
	 */
	public function extra_headers( $headers ) {
		$headers['GlotPress API URI']  = 'GlotPress API URI';
		$headers['GlotPress API Path'] = 'GlotPress API Path';

		return $headers;
	}

	/**
	 * Gets the items for the update request.
	 *
	 * @return array The items to be updated.
	 */
	protected function get_items() {
		return $this->items;
	}

	/**
	 * Gets the locale for the update request.
	 *
	 * @return string The locale.
	 */
	protected function get_locale() {
		return $this->locale;
	}

	/**
	 * Is WP API request.
	 *
	 * @param string $url The request URL.
	 *
	 * @return bool True if the request is a WP API request, false otherwise.
	 */
	protected function is_wp_api_request( $url ) {
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		return strpos( $url, $this->wp_api_url ) !== false;
	}

	/**
	 * Checks for updates by sending a request to the API URL.
	 *
	 * @param array $item The item to check for updates.
	 *
	 * @return mixed|false The response body if successful, false otherwise.
	 */
	protected function check_for_updates( $item ) {
		$api_url = $this->get_api_url( $item['GlotPress API URI'] );
		if ( ! $api_url ) {
			return false;
		}

		$payload = array(
			'item'   => $item['GlotPress API Path'],
			'locale' => $this->get_locale(),
		);

		$args = array(
			'timeout' => 30,
			'body'    => $this->encode( $payload ),
		);

		$raw_response = wp_remote_post( $api_url, $args );
		if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
			return false;
		}

		$body = $this->decode( wp_remote_retrieve_body( $raw_response ) );
		if ( ! is_array( $body ) || empty( $body ) ) {
			return false;
		}

		return $body;
	}

	/**
	 * Gets the API URL for the item.
	 *
	 * @param string $uri The GlotPress API URI.
	 *
	 * @return string|false The API URL or false if not valid.
	 */
	protected function get_api_url( $uri ) {
		if ( empty( $uri ) || ! filter_var( $uri, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$api_url = trailingslashit( $uri ) . 'wp-json/gp/translations/update-check/1.1/';
		$api_url = apply_filters( 'gp_translation_updater_api_url', $api_url, $uri );
		if ( empty( $api_url ) || ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		return $api_url;
	}

	/**
	 * Decodes the data.
	 *
	 * @param string $data The data to decode.
	 *
	 * @return mixed The decoded data.
	 */
	protected function decode( $data ) {
		return json_decode( $data, true );
	}

	/**
	 * Encodes the data.
	 *
	 * @param mixed $data The data to encode.
	 *
	 * @return string The encoded data.
	 */
	protected function encode( $data ) {
		return wp_json_encode( $data );
	}
}
