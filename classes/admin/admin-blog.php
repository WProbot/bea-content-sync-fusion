<?php
class BEA_CSF_Admin_Blog {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Add actions link into sites list
		add_filter( 'manage_sites_action_links', array(__CLASS__, 'manage_sites_action_links'), 10, 3 );
		add_action( 'wpmuadminedit', array(__CLASS__, 'wpmuadminedit') );
		add_action( 'network_sites_updated_message_'.'resync_bea_csf_content', array(__CLASS__, 'network_sites_updated_message') );

		// Add widget on each site dashboard
		add_action( 'wp_dashboard_setup', array(__CLASS__, 'wp_dashboard_setup') );
	}

	/**
	 * Add an item for resync 
	 * 
	 * @param  array $actions
	 * @param  integer $blog_id
	 * @param  string $blogname
	 * @return array
	 */
	public static function manage_sites_action_links( $actions, $blog_id, $blogname ) {
		$actions['resync'] = '<a href="' . esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=resync_bea_csf_content&amp;id=' . $blog_id ), 'resync_blog_content_' . $blog_id ) ) . '">' . __( 'Resync content', BEA_CSF_LOCALE ) . '</a>';

		return $actions;
	}

	/**
	 * Check GET call for resync site contents
	 */
	public static function wpmuadminedit() {
		$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

		if ( isset($_GET['action']) && $_GET['action'] == 'resync_bea_csf_content' && $id > 0 ) {
			check_admin_referer( 'resync_blog_content_' . $id );

			BEA_CSF_Multisite::wpmu_new_blog( $id );

			wp_safe_redirect( add_query_arg( array( 'updated' => 'resync_bea_csf_content' ), wp_get_referer() ) );
			exit();
		}
	}

	/**
	 * Customize message notifiation
	 * 
	 * @param  string
	 * @return string
	 */
	public static function network_sites_updated_message( $msg = '' ) {
		if ( isset($_GET['updated']) && $_GET['updated'] == 'resync_bea_csf_content' ) {
			return __( 'Resync website OK.', BEA_CSF_LOCALE );
		}

		return $msg;
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 */
	public static function wp_dashboard_setup() {
		wp_add_dashboard_widget(
			'bea_csf_blog_admin_widget',
			__('Content synchronization status', BEA_CSF_LOCALE),
			array(__CLASS__, 'widget_render')
		);	
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	public static function widget_render() {
		if ( isset($_POST['bea_csf_force_blog_refresh']) ) {
			check_admin_referer( 'bea-csf-force-refresh' );

			// Process 30 items only
			BEA_CSF_Async::process_queue( 30, get_current_blog_id() );
			wp_cache_flush();
		}

		// Include template
		include( BEA_CSF_DIR . 'views/admin/blog-widget.php' );

		return true;
	}
}