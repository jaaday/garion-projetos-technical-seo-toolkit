<?php
/**
 * Deterministic Schema.org JSON-LD output (Article/WebPage + Organization) for singular content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GP_SEO_Structured_Data {

	public function __construct() {
		add_action( 'wp_head', array( $this, 'output' ), 5 );
	}

	public function output() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		$image  = get_the_post_thumbnail_url( $post, 'large' );

		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type'            => 'post' === $post->post_type ? 'Article' : 'WebPage',
					'@id'              => get_permalink( $post ) . '#content',
					'headline'         => get_the_title( $post ),
					'description'      => $this->get_description( $post ),
					'url'              => get_permalink( $post ),
					'datePublished'    => get_the_date( DATE_W3C, $post ),
					'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
					'inLanguage'       => get_bloginfo( 'language' ),
					'isPartOf'         => array( '@id' => home_url( '/' ) . '#website' ),
					'author'           => array(
						'@type' => 'Person',
						'name'  => $author ? $author->display_name : get_bloginfo( 'name' ),
					),
				),
				array(
					'@type' => 'WebSite',
					'@id'   => home_url( '/' ) . '#website',
					'name'  => get_option( 'gpseo_org_name', get_bloginfo( 'name' ) ),
					'url'   => home_url( '/' ),
				),
				array(
					'@type' => 'Organization',
					'@id'   => home_url( '/' ) . '#organization',
					'name'  => get_option( 'gpseo_org_name', get_bloginfo( 'name' ) ),
					'url'   => home_url( '/' ),
					'logo'  => get_option( 'gpseo_org_logo', '' ),
				),
			),
		);

		if ( $image ) {
			$graph['@graph'][0]['image'] = $image;
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- pre-validated JSON built server-side.
	}

	private function get_description( $post ) {
		$meta_description = get_post_meta( $post->ID, '_gpseo_meta_description', true );

		if ( $meta_description ) {
			return $meta_description;
		}

		$excerpt = get_the_excerpt( $post );

		return $excerpt ? wp_strip_all_tags( $excerpt ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
	}
}
