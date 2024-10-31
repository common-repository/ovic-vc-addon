<?php
/**
 * Ovic Framework setup
 *
 * @author   KHANH
 * @category API
 * @package  Ovic_Plugins_Dashboard
 * @since    1.0.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( !class_exists( 'Ovic_Plugins_Dashboard' ) ) {
	class Ovic_Plugins_Dashboard
	{
		public $tabs = array();

		public function __construct()
		{
			$this->set_tabs();
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 5 );
		}

		public function admin_menu()
		{
			if ( current_user_can( 'edit_theme_options' ) ) {
				add_menu_page(
                    'Ovic Plugins',
                    'Ovic Plugins',
                    'manage_options',
                    'ovic-plugins',
                    array($this, 'welcome'),
                    OVIC_VC_PLUGIN_URL.'assets/icon-menu.png',
                    3
                );
			}
		}

		public function set_tabs()
		{
			$tabs       = array(
				'dashboard' => 'Welcome',
				'plugins'   => 'Plugins',
			);
			$tabs       = apply_filters( 'ovic_plugins_registered_dashboard_tabs', $tabs );
			$this->tabs = $tabs;
		}

		public function active_plugin()
		{
			if ( empty( $_GET['magic_token'] ) || wp_verify_nonce( $_GET['magic_token'], 'panel-plugins' ) === false ) {
				echo 'Permission denied';
				die;
			}
			if ( isset( $_GET['plugin_slug'] ) && $_GET['plugin_slug'] != "" ) {
				$plugin_slug = $_GET['plugin_slug'];
				$plugins     = TGM_Plugin_Activation::$instance->plugins;
				foreach ( $plugins as $plugin ) {
					if ( $plugin['slug'] == $plugin_slug ) {
						activate_plugins( $plugin['file_path'] );
						?>
                        <script type="text/javascript">
                            window.location = "admin.php?page=ovic-plugins&tab=plugins";
                        </script>
						<?php
						break;
					}
				}
			}
		}

		public function deactivate_plugin()
		{
			if ( empty( $_GET['magic_token'] ) || wp_verify_nonce( $_GET['magic_token'], 'panel-plugins' ) === false ) {
				echo 'Permission denied';
				die;
			}
			if ( isset( $_GET['plugin_slug'] ) && $_GET['plugin_slug'] != "" ) {
				$plugin_slug = $_GET['plugin_slug'];
				$plugins     = TGM_Plugin_Activation::$instance->plugins;
				foreach ( $plugins as $plugin ) {
					if ( $plugin['slug'] == $plugin_slug ) {
						deactivate_plugins( $plugin['file_path'] );
						?>
                        <script type="text/javascript">
                            window.location = "admin.php?page=ovic-plugins&tab=plugins";
                        </script>
						<?php
						break;
					}
				}
			}
		}

		public function plugins()
		{

		}

		public function dashboard()
		{
			?>
            <div class="dashboard">
                <h1>Welcome to Plugins Ovic</h1>
                <p class="about-text">Thanks for using our theme, we have worked very hard to release a great product
                    and we will do our absolute best to support this theme and fix all the issues. </p>
                <div class="dashboard-intro">
                    <p><a href="<?php echo esc_url( 'https://kutethemes.com' ); ?>" target="_blank">
                            <strong>Contact Us</strong></a> For More Plugins
                        Useful</p>
                </div>
            </div>
			<?php
		}

		public function welcome()
		{
			$tab = 'dashboard';
			if ( isset( $_GET['tab'] ) ) {
				$tab = $_GET['tab'];
			}
			?>
            <div class="ovic-wrap">
                <div id="tabs-container" role="tabpanel">
                    <div class="nav-tab-wrapper">
						<?php foreach ( $this->tabs as $key => $value ): ?>
                            <a class="nav-tab <?php if ( $tab == $key ): ?> nav-tab-active<?php endif; ?>"
                               href="admin.php?page=ovic-plugins&tab=<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></a>
						<?php endforeach; ?>
                    </div>
                    <div class="tab-content">
						<?php
						ob_start();
						$this->$tab();
						$content_tab = ob_get_clean();
						$content_tab = apply_filters( 'ovic_plugins_dashboard_tab_content', $content_tab, $tab );
						echo $content_tab;
						?>
                    </div>
                </div>
            </div>
			<?php
		}
	}

	new Ovic_Plugins_Dashboard();
}