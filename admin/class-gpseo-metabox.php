<?php
/**
 * Post-editor metabox: canonical URL override, meta description, noindex/nofollow.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GP_SEO_Metabox {

	const NONCE_ACTION = 'gpseo_metabox_save';
	const NONCE_NAME   = 'gpseo_metabox_nonce';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	public function add_metabox() {
		foreach ( array( 'post', 'page' ) as $screen ) {
			add_meta_box(
				'gpseo-metabox',
				__( 'Technical SEO Toolkit', 'garion-projetos-technical-seo-toolkit' ),
				array( $this, 'render' ),
				$screen,
				'normal',
				'default'
			);
		}
	}

	public function render( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$meta_description = get_post_meta( $post->ID, '_gpseo_meta_description', true );
		$canonical         = get_post_meta( $post->ID, '_gpseo_canonical_url', true );
		$noindex           = get_post_meta( $post->ID, '_gpseo_noindex', true );
		$nofollow          = get_post_meta( $post->ID, '_gpseo_nofollow', true );
		?>
		<p>
			<label for="gpseo_meta_description"><strong><?php esc_html_e( 'Meta description', 'garion-projetos-technical-seo-toolkit' ); ?></strong></label><br />
			<textarea id="gpseo_meta_description" name="gpseo_meta_description" rows="3" style="width:100%;" maxlength="160"><?php echo esc_textarea( $meta_description ); ?></textarea>
		</p>
		<p>
			<label for="gpseo_canonical_url"><strong><?php esc_html_e( 'Canonical URL override', 'garion-projetos-technical-seo-toolkit' ); ?></strong></label><br />
			<input type="url" id="gpseo_canonical_url" name="gpseo_canonical_url" style="width:100%;" value="<?php echo esc_attr( $canonical ); ?>" placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>" />
		</p>
		<p>
			<label><input type="checkbox" name="gpseo_noindex" value="1" <?php checked( $noindex ); ?> /> <?php esc_html_e( 'Noindex (hide from search engines)', 'garion-projetos-technical-seo-toolkit' ); ?></label>
			&nbsp;&nbsp;
			<label><input type="checkbox" name="gpseo_nofollow" value="1" <?php checked( $nofollow ); ?> /> <?php esc_html_e( 'Nofollow (do not follow links on this page)', 'garion-projetos-technical-seo-toolkit' ); ?></label>
		</p>
		<?php
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['gpseo_meta_description'] ) ) {
			update_post_meta( $post_id, '_gpseo_meta_description', sanitize_textarea_field( wp_unslash( $_POST['gpseo_meta_description'] ) ) );
		}

		if ( isset( $_POST['gpseo_canonical_url'] ) ) {
			update_post_meta( $post_id, '_gpseo_canonical_url', esc_url_raw( wp_unslash( $_POST['gpseo_canonical_url'] ) ) );
		}

		update_post_meta( $post_id, '_gpseo_noindex', ! empty( $_POST['gpseo_noindex'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_gpseo_nofollow', ! empty( $_POST['gpseo_nofollow'] ) ? 1 : 0 );
	}
}
