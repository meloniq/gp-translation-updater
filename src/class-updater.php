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

		// Only run in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// allow local requests.
			add_filter( 'http_request_host_is_external', '__return_true' );
			add_filter( 'http_request_args', array( $this, 'disable_ssl_verification' ) );
		}
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
	 * Prepare update response.
	 *
	 * @param array  $updates The updates to apply.
	 * @param array  $item The item to prepare.
	 * @param string $slug The slug of the item.
	 *
	 * @return array Prepared update response.
	 */
	abstract protected function prepare_update_response( $updates, $item, $slug );

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

		$locale = $this->get_locale();
		if ( empty( $locale ) || ! is_array( $locale ) ) {
			gp_error_log( 'Invalid locale data.' );
			return false;
		}

		$payload = array(
			'item'   => $item['GlotPress API Path'],
			'locale' => $locale,
		);

		$args = array(
			'timeout' => 10,
			'body'    => $this->encode( $payload ),
		);

		$raw_response = wp_remote_post( $api_url, $args );
		if ( is_wp_error( $raw_response ) ) {
			gp_error_log( 'GlotPress API request error: ' . $raw_response->get_error_message() );
			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
			gp_error_log( 'GlotPress API request failed with response code: ' . wp_remote_retrieve_response_code( $raw_response ) );
			gp_error_log( 'Response body: ' . wp_remote_retrieve_body( $raw_response ) );
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
	 * Prepares the download URL for the update package.
	 *
	 * @param string $package_url The package URL.
	 * @param array  $item The item to prepare.
	 * @param string $slug The slug of the item.
	 *
	 * @return string The prepared download URL.
	 */
	public function prepare_download_url( $package_url, $item, $slug ) {
		$textdomain = $slug;

		if ( ! empty( $item['Text Domain'] ) ) {
			$textdomain = $item['Text Domain'];
		}

		if ( ! empty( $item['TextDomain'] ) ) {
			$textdomain = $item['TextDomain'];
		}

		$package_url = add_query_arg(
			array(
				'textdomain' => $textdomain,
			),
			$package_url
		);

		return $package_url;
	}

	/**
	 * Disable SSL verification for local requests.
	 *
	 * @param array $args The request arguments.
	 *
	 * @return array Modified request arguments.
	 */
	public function disable_ssl_verification( $args ) {
		if ( ! isset( $args['sslverify'] ) || $args['sslverify'] ) {
			$args['sslverify'] = false;
		}

		return $args;
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
