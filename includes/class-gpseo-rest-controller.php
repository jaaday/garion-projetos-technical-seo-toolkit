<?php
/**
 * REST API endpoints used by the admin screen to trigger and poll the broken-link scan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GP_SEO_REST_Controller {

	const NAMESPACE_ = 'garion-projetos-technical-seo-toolkit/v1';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_,
			'/broken-links/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/broken-links/scan',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'start_scan' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	public function get_status() {
		$broken_links = new GP_SEO_Broken_Links();

		return rest_ensure_response( $broken_links->get_status() );
	}

	public function start_scan() {
		$broken_links = new GP_SEO_Broken_Links();
		$broken_links->trigger_scan_now();

		return rest_ensure_response( $broken_links->get_status() );
	}
}
