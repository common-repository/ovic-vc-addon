<?php
/**
 * Plugin Name: Ovic: Responsive WPBakery
 * Plugin URI: https://kutethemes.com/wordpress-plugins/
 * Description: Support responsive all shortcode Visual Composer, add some new params, add shortcode.
 * Author: Ovic Team
 * Author URI: https://themeforest.net/user/kutethemes
 * Version: 1.3.0
 * Text Domain: ovic-vc
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Ovic_VC' ) ) {
	class  Ovic_VC {
		/**
		 * @var Ovic_VC The one true Ovic_VC
		 */
		private static $instance;

		public static function instance()
		{
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Ovic_VC ) ) {
				self::$instance = new Ovic_VC;
				self::$instance->setup_constants();
				add_action( 'plugins_loaded', array( self::$instance, 'load_setup_plugins' ) );
				add_filter( 'plugin_row_meta', array( self::$instance, 'plugin_row_meta' ), 10, 2 );
			}

			return self::$instance;
		}

		public function setup_constants()
		{
			// Plugin version.
			if ( ! defined( 'OVIC_VC_VERSION' ) ) {
				define( 'OVIC_VC_VERSION', '1.3.0' );
			}
			// Plugin basename.
			if ( ! defined( 'OVIC_VC_BASENAME' ) ) {
				define( 'OVIC_VC_BASENAME', plugin_basename( __FILE__ ) );
			}
			// Plugin Folder Path.
			if ( ! defined( 'OVIC_VC_PLUGIN_DIR' ) ) {
				define( 'OVIC_VC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}
			// Plugin Folder URL.
			if ( ! defined( 'OVIC_VC_PLUGIN_URL' ) ) {
				define( 'OVIC_VC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}
			// Plugin Backend Assest.
			if ( ! defined( 'OVIC_VC_BACKEND_ASSETS_URL' ) ) {
				define( 'OVIC_VC_BACKEND_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/backend' );
			}
			// Plugin Frontend Assest.
			if ( ! defined( 'OVIC_VC_FRONTEND_ASSETS_URL' ) ) {
				define( 'OVIC_VC_FRONTEND_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets/frontend' );
			}
			// Plugin Shortcode Templates.
			if ( ! defined( 'OVIC_VC_SHORTCODE_TEMPLATES_PATH' ) ) {
				define( 'OVIC_VC_SHORTCODE_TEMPLATES_PATH', plugin_dir_path( __FILE__ ) . 'includes/shortcode' );
			}
		}

		public function includes()
		{
			require_once OVIC_VC_PLUGIN_DIR . 'includes/shortcode.php';
			require_once OVIC_VC_PLUGIN_DIR . 'includes/dashboard/dashboard.php';
		}

		function admin_notice__error()
		{
			if ( ! class_exists( 'Vc_Manager' ) ) {
				?>
                <div class="notice notice-error">
                    <p>
						<?php esc_html_e( 'Require plugin', 'ovic-vc' ); ?>
                        <a href="<?php echo esc_url( 'https://wpbakery.com/' ); ?>" target="_blank">
							<?php esc_html_e( 'WPBakery Page Builder', 'ovic-vc' ); ?>
                        </a>
						<?php esc_html_e( 'for use plugin "Ovic VC Addon"', 'ovic-vc' ); ?>
                    </p>
                </div>
				<?php
			}
		}

		/**
		 * Show row meta on the plugin screen.
		 *
		 * @param  mixed  $links  Plugin Row Meta.
		 * @param  mixed  $file  Plugin Base file.
		 *
		 * @return array
		 */
		public function plugin_row_meta( $links, $file )
		{
			if ( OVIC_VC_BASENAME === $file ) {
				$row_meta = array(
					'docs'    => '<a href="' . esc_url( 'https://kutethemes.com/how-to-use-plugin-ovic-responsive-wpbakery/' ) . '" target="_blank" aria-label="' . esc_attr__( 'View Ovic Import Demo documentation',
							'ovic-vc' ) . '">' . esc_html__( 'Documentation', 'ovic-vc' ) . '</a>',
				);

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		public function load_setup_plugins()
		{
			self::$instance->includes();
			add_action( 'admin_notices', array( self::$instance, 'admin_notice__error' ) );
			load_plugin_textdomain( 'ovic-vc', false, OVIC_VC_PLUGIN_DIR . 'languages' );
		}

		public function auto_update_plugins()
		{
			if ( is_admin() ) {
				require_once OVIC_VC_PLUGIN_DIR . 'includes/license/updater-admin.php';
				/* UPDATE PLUGIN AUTOMATIC */
				if ( class_exists( 'Ovic_Updater_Admin' ) ) {
					$config  = array(
						'item_name'       => 'Responsive WPBakery', // Name of plugin
						'item_slug'       => 'ovic-vc-addon',       // plugin slug
						'version'         => OVIC_VC_VERSION,       // The current version of this plugin
						'root_uri'        => __FILE__,              // The root file of this plugin
						'item_link'       => 'https://kutethemes.com/plugins/responsive-wpbakery/',
						'setting_license' => admin_url( 'admin.php?page=ovic-vc-addon&tab=license' ),
					);
					$license = new Ovic_Updater_Admin( $config );
					$license->updater();
				}
			}
		}
	}
}
if ( ! function_exists( 'Ovic_VC' ) ) {
	function Ovic_VC()
	{
		return Ovic_VC::instance();
	}
}
Ovic_VC();