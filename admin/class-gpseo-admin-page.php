<?php
/**
 * Admin screen: tabbed UI for Redirects, Broken Links, Audit and Settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GP_SEO_Admin_Page {

	const MENU_SLUG = 'gpseo-toolkit';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_gpseo_add_redirect', array( $this, 'handle_add_redirect' ) );
		add_action( 'admin_post_gpseo_delete_redirect', array( $this, 'handle_delete_redirect' ) );
		add_action( 'admin_post_gpseo_save_settings', array( $this, 'handle_save_settings' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'Technical SEO Toolkit', 'garion-projetos-technical-seo-toolkit' ),
			__( 'Technical SEO', 'garion-projetos-technical-seo-toolkit' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' ),
			'dashicons-search',
			80
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'gpseo-admin', GPSEO_URL . 'assets/css/admin.css', array(), GPSEO_VERSION );
		wp_enqueue_script( 'gpseo-admin', GPSEO_URL . 'assets/js/admin.js', array( 'wp-api-fetch' ), GPSEO_VERSION, true );

		wp_localize_script(
			'gpseo-admin',
			'gpseoData',
			array(
				'restNamespace' => GP_SEO_REST_Controller::NAMESPACE_,
				'i18n'          => array(
					'scanning' => __( 'Scanning... this page will refresh automatically when it finishes.', 'garion-projetos-technical-seo-toolkit' ),
					'done'     => __( 'Scan finished. Refreshing...', 'garion-projetos-technical-seo-toolkit' ),
				),
			)
		);
	}

	private function current_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'redirects'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab selector.

		return in_array( $tab, array( 'redirects', 'broken-links', 'audit', 'settings' ), true ) ? $tab : 'redirects';
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab = $this->current_tab();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Technical SEO Toolkit', 'garion-projetos-technical-seo-toolkit' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->tabs() as $slug => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"
						class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<div class="gpseo-tab-content">
				<?php
				switch ( $tab ) {
					case 'broken-links':
						$this->render_broken_links();
						break;
					case 'audit':
						$this->render_audit();
						break;
					case 'settings':
						$this->render_settings();
						break;
					default:
						$this->render_redirects();
				}
				?>
			</div>
		</div>
		<?php
	}

	private function tabs() {
		return array(
			'redirects'    => __( 'Redirects', 'garion-projetos-technical-seo-toolkit' ),
			'broken-links' => __( 'Broken Links', 'garion-projetos-technical-seo-toolkit' ),
			'audit'        => __( 'Page Audit', 'garion-projetos-technical-seo-toolkit' ),
			'settings'     => __( 'Settings', 'garion-projetos-technical-seo-toolkit' ),
		);
	}

	private function render_redirects() {
		$redirects = ( new GP_SEO_Redirects() )->get_all();
		?>
		<h2><?php esc_html_e( 'Add redirect', 'garion-projetos-technical-seo-toolkit' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'gpseo_add_redirect' ); ?>
			<input type="hidden" name="action" value="gpseo_add_redirect" />
			<table class="form-table">
				<tr>
					<th><label for="source_path"><?php esc_html_e( 'Source path', 'garion-projetos-technical-seo-toolkit' ); ?></label></th>
					<td><input type="text" id="source_path" name="source_path" class="regular-text" placeholder="/old-page" required /></td>
				</tr>
				<tr>
					<th><label for="destination_url"><?php esc_html_e( 'Destination URL', 'garion-projetos-technical-seo-toolkit' ); ?></label></th>
					<td><input type="text" id="destination_url" name="destination_url" class="regular-text" placeholder="https://example.com/new-page" required /></td>
				</tr>
				<tr>
					<th><label for="redirect_type"><?php esc_html_e( 'Type', 'garion-projetos-technical-seo-toolkit' ); ?></label></th>
					<td>
						<select id="redirect_type" name="redirect_type">
							<option value="301">301 (<?php esc_html_e( 'Permanent', 'garion-projetos-technical-seo-toolkit' ); ?>)</option>
							<option value="302">302 (<?php esc_html_e( 'Temporary', 'garion-projetos-technical-seo-toolkit' ); ?>)</option>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Add redirect', 'garion-projetos-technical-seo-toolkit' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Existing redirects', 'garion-projetos-technical-seo-toolkit' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Source', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Destination', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Type', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Hits', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $redirects ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No redirects yet.', 'garion-projetos-technical-seo-toolkit' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $redirects as $redirect ) : ?>
						<tr>
							<td><code><?php echo esc_html( $redirect->source_path ); ?></code></td>
							<td><?php echo esc_html( $redirect->destination_url ); ?></td>
							<td><?php echo esc_html( $redirect->redirect_type ); ?></td>
							<td><?php echo esc_html( $redirect->hits ); ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gpseo_delete_redirect&id=' . $redirect->id ), 'gpseo_delete_redirect_' . $redirect->id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this redirect?', 'garion-projetos-technical-seo-toolkit' ) ); ?>');">
									<?php esc_html_e( 'Delete', 'garion-projetos-technical-seo-toolkit' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_broken_links() {
		$broken_links = new GP_SEO_Broken_Links();
		$results      = $broken_links->get_results();
		$status       = $broken_links->get_status();
		?>
		<p>
			<button type="button" class="button button-primary" id="gpseo-scan-now" data-scanning="<?php echo esc_attr( 'running' === $status['status'] ? '1' : '0' ); ?>">
				<?php esc_html_e( 'Scan for broken links now', 'garion-projetos-technical-seo-toolkit' ); ?>
			</button>
			<span id="gpseo-scan-message"></span>
		</p>
		<p class="description">
			<?php
			if ( $status['last_run'] ) {
				printf(
					/* translators: %s: date/time of the last completed scan. */
					esc_html__( 'Last full scan completed: %s. A new batch also runs automatically every 10 minutes.', 'garion-projetos-technical-seo-toolkit' ),
					esc_html( $status['last_run'] )
				);
			} else {
				esc_html_e( 'No scan has completed yet. A batch runs automatically every 10 minutes, or click the button above.', 'garion-projetos-technical-seo-toolkit' );
			}
			?>
		</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Broken URL', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Anchor text', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Status', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Last checked', 'garion-projetos-technical-seo-toolkit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $results ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No broken links found yet.', 'garion-projetos-technical-seo-toolkit' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $results as $row ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $row->post_id ) ); ?>"><?php echo esc_html( get_the_title( $row->post_id ) ); ?></a></td>
							<td><a href="<?php echo esc_url( $row->url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row->url ); ?></a></td>
							<td><?php echo esc_html( $row->anchor_text ); ?></td>
							<td><?php echo esc_html( $row->http_status ); ?></td>
							<td><?php echo esc_html( $row->last_checked_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_audit() {
		$rows = ( new GP_SEO_Audit() )->run();
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Score', 'garion-projetos-technical-seo-toolkit' ); ?></th>
					<th><?php esc_html_e( 'Issues', 'garion-projetos-technical-seo-toolkit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No published content to audit yet.', 'garion-projetos-technical-seo-toolkit' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $row['post']->ID ) ); ?>"><?php echo esc_html( get_the_title( $row['post'] ) ); ?></a></td>
							<td><?php echo esc_html( $row['score'] ); ?>/100</td>
							<td>
								<?php if ( empty( $row['issues'] ) ) : ?>
									&#10003; <?php esc_html_e( 'No issues found.', 'garion-projetos-technical-seo-toolkit' ); ?>
								<?php else : ?>
									<ul style="margin:0;">
										<?php foreach ( $row['issues'] as $issue ) : ?>
											<li><?php echo esc_html( $issue ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_settings() {
		$org_name    = get_option( 'gpseo_org_name', get_bloginfo( 'name' ) );
		$org_logo    = get_option( 'gpseo_org_logo', '' );
		$robots_extra = get_option( 'gpseo_robots_txt_extra', '' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'gpseo_save_settings' ); ?>
			<input type="hidden" name="action" value="gpseo_save_settings" />
			<table class="form-table">
				<tr>
					<th><label for="gpseo_org_name"><?php esc_html_e( 'Organization name', 'garion-projetos-technical-seo-toolkit' ); ?></label></th>
					<td><input type="text" id="gpseo_org_name" name="gpseo_org_name" class="regular-text" value="<?php echo esc_attr( $org_name ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="gpseo_org_logo"><?php esc_html_e( 'Organization logo URL', 'garion-projetos-technical-seo-toolkit' ); ?></label></th>
					<td><input type="url" id="gpseo_org_logo" name="gpseo_org_logo" class="regular-text" value="<?php echo esc_attr( $org_logo ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="gpseo_robots_txt_extra"><?php esc_html_e( 'Extra robots.txt rules', 'garion-projetos-technical-seo-toolkit' ); ?></label></th>
					<td>
						<textarea id="gpseo_robots_txt_extra" name="gpseo_robots_txt_extra" rows="5" class="large-text" placeholder="Disallow: /private/"><?php echo esc_textarea( $robots_extra ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Appended to the virtual robots.txt generated by WordPress.', 'garion-projetos-technical-seo-toolkit' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	public function handle_add_redirect() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'gpseo_add_redirect' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'garion-projetos-technical-seo-toolkit' ) );
		}

		$source      = isset( $_POST['source_path'] ) ? sanitize_text_field( wp_unslash( $_POST['source_path'] ) ) : '';
		$destination = isset( $_POST['destination_url'] ) ? esc_url_raw( wp_unslash( $_POST['destination_url'] ) ) : '';
		$type        = isset( $_POST['redirect_type'] ) ? (int) $_POST['redirect_type'] : 301;

		if ( $source && $destination ) {
			( new GP_SEO_Redirects() )->add( $source, $destination, $type );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'redirects' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'garion-projetos-technical-seo-toolkit' ) );
		}

		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		check_admin_referer( 'gpseo_delete_redirect_' . $id );

		if ( $id ) {
			( new GP_SEO_Redirects() )->delete( $id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'redirects' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'gpseo_save_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'garion-projetos-technical-seo-toolkit' ) );
		}

		update_option( 'gpseo_org_name', isset( $_POST['gpseo_org_name'] ) ? sanitize_text_field( wp_unslash( $_POST['gpseo_org_name'] ) ) : '' );
		update_option( 'gpseo_org_logo', isset( $_POST['gpseo_org_logo'] ) ? esc_url_raw( wp_unslash( $_POST['gpseo_org_logo'] ) ) : '' );
		update_option( 'gpseo_robots_txt_extra', isset( $_POST['gpseo_robots_txt_extra'] ) ? sanitize_textarea_field( wp_unslash( $_POST['gpseo_robots_txt_extra'] ) ) : '' );

		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'tab' => 'settings' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
