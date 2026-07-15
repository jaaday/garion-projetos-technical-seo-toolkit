<?php
/**
 * Plugin Name: Garion Projetos Technical SEO Toolkit
 * Description: Technical SEO tools for WordPress: redirects, broken link detection, structured data, canonical control and robots configuration.
 * Version: 0.2.0
 * Author: Garion Projetos
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: garion-projetos-technical-seo-toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GPSEO_VERSION', '0.2.0' );
define( 'GPSEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'GPSEO_URL', plugin_dir_url( __FILE__ ) );

require_once GPSEO_PATH . 'includes/class-gpseo-redirects.php';
require_once GPSEO_PATH . 'includes/class-gpseo-broken-links.php';
require_once GPSEO_PATH . 'includes/class-gpseo-structured-data.php';
require_once GPSEO_PATH . 'includes/class-gpseo-canonical.php';
require_once GPSEO_PATH . 'includes/class-gpseo-robots.php';
require_once GPSEO_PATH . 'includes/class-gpseo-audit.php';
require_once GPSEO_PATH . 'includes/class-gpseo-rest-controller.php';
require_once GPSEO_PATH . 'admin/class-gpseo-metabox.php';
require_once GPSEO_PATH . 'admin/class-gpseo-admin-page.php';

register_activation_hook(
	__FILE__,
	static function () {
		GP_SEO_Redirects::activate();
		GP_SEO_Broken_Links::activate();
	}
);

register_deactivation_hook( __FILE__, array( 'GP_SEO_Broken_Links', 'deactivate' ) );

add_action( 'plugins_loaded', 'gpseo_init' );

function gpseo_init() {
	new GP_SEO_Redirects();
	new GP_SEO_Broken_Links();
	new GP_SEO_Structured_Data();
	new GP_SEO_Canonical();
	new GP_SEO_Robots();
	new GP_SEO_REST_Controller();

	if ( is_admin() ) {
		new GP_SEO_Metabox();
		new GP_SEO_Admin_Page();
	}
}
