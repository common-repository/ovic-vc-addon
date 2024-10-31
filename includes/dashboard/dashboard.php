<?php
/**
 * Ovic Framework setup
 *
 * @author   KHANH
 * @category API
 * @package  Ovic_VC_Dashboard
 * @since    1.0.1
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( !class_exists( 'Ovic_VC_Dashboard' ) ) {
	class Ovic_VC_Dashboard
	{
		public function __construct()
		{
			$this->includes();
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		}

		public function admin_menu()
		{
			if ( current_user_can( 'edit_theme_options' ) ) {
				add_submenu_page( 'ovic-plugins', 'Ovic WPBakery', 'Ovic WPBakery', 'manage_options', 'ovic-vc-addon', array( $this, 'options_setting' ) );
			}
		}

		function includes()
		{
			include_once OVIC_VC_PLUGIN_DIR . 'includes/helpers.php';
			include_once OVIC_VC_PLUGIN_DIR . 'includes/dashboard/welcome.php';
			if ( class_exists( 'Vc_Manager' ) ) {
				include_once OVIC_VC_PLUGIN_DIR . 'includes/params.php';
				include_once OVIC_VC_PLUGIN_DIR . 'includes/visual-composer.php';
			}
		}

		public function options_setting()
		{
			$tabs = array(
				'general' => 'General',
				'license' => 'Get Pro? ( License )',
			);
			$tab  = 'general';
			if ( isset( $_GET['tab'] ) ) {
				$tab = $_GET['tab'];
			}
			?>
            <div class="ovic-wrap">
                <div id="tabs-container" role="tabpanel">
                    <div class="nav-tab-wrapper">
						<?php foreach ( $tabs as $key => $value ): ?>
							<?php
							$url = add_query_arg(
								array(
									'page' => 'ovic-vc-addon',
									'tab'  => $key,
								),
								admin_url( 'admin.php' )
							);
							?>
                            <a class="nav-tab <?php if ( $tab == $key ): ?> nav-tab-active<?php endif; ?>"
                               href="<?php echo esc_url( $url ); ?>">
								<?php echo esc_html( $value ); ?>
                            </a>
						<?php endforeach; ?>
                    </div>
                    <div class="tab-content">
						<?php $this->$tab(); ?>
                    </div>
                </div>
            </div>
			<?php
		}

		public function license()
		{
			?>
            <div id="dashboard-license" class="dashboard-license tab-panel">
				<?php do_action( 'ovic_license_ovic-vc-addon_page' ); ?>
            </div>
			<?php
		}

		public function general()
		{
			$editor_names             = class_exists( 'Ovic_VC_Init' ) ? Ovic_VC_Init::responsive_data( false ) : array();
			$editor_options           = get_option( '_ovic_vc_options' );
			$enable_advanced_options  = 'checked';
			$enable_screen_responsive = 'checked';
			if ( isset( $editor_options['advanced_options'] ) && $editor_options['advanced_options'] == 'no' ) {
				$enable_advanced_options = '';
			}
			if ( isset( $editor_options['screen_responsive'] ) && $editor_options['screen_responsive'] == 'no' ) {
				$enable_screen_responsive = '';
			}
			?>
            <div id="dashboard-ovic-vc" class="dashboard-ovic-vc tab-panel">
                <h1 class="title">Welcome to Plugins Responsive WPBakery</h1>
                <div class="dashboard-intro">
                    <form method="post" class="ovic_vc_options">
                        <div class="alert-tool"></div>
                        <div class="head-options">
                            <div class="field-item ovic-vc-checkbox-field" style="padding-right: 20px">
                                <strong class="title"><?php echo esc_html__( 'Enable Screen Responsive', 'ovic-vc' ); ?></strong>
                                <div class="inner-field">
                                    <label class="vc_checkbox-label">
                                        <input id="enable_screen_responsive"
                                               class="wpb_vc_param_value"
                                               name="enable_screen_responsive" type="checkbox"
											<?php echo esc_attr( $enable_screen_responsive ); ?>>
                                        <label for="enable_screen_responsive"></label>
                                    </label>
                                </div>
                            </div>
                            <div class="field-item ovic-vc-checkbox-field">
                                <strong class="title"><?php echo esc_html__( 'Enable Advanced Options', 'ovic-vc' ); ?></strong>
                                <div class="inner-field">
                                    <label class="vc_checkbox-label">
                                        <input id="enable_advanced_options"
                                               class="wpb_vc_param_value"
                                               name="enable_advanced_options" type="checkbox"
											<?php echo esc_attr( $enable_advanced_options ); ?>>
                                        <label for="enable_advanced_options"></label>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <table class="wp-list-table widefat striped pages group-item"
                               style="width:100%">
                            <thead>
                            <tr>
                                <td style="width:30%">
                                    <strong><?php echo esc_html__( 'Name Screen', 'ovic-vc' ); ?></strong>
                                </td>
                                <td style="width:30%">
                                    <strong><?php echo esc_html__( 'Media Feature', 'ovic-vc' ); ?></strong>
                                </td>
                                <td style="width:30%">
                                    <strong><?php echo esc_html__( 'Breakpoint', 'ovic-vc' ); ?></strong>
                                </td>
                                <td style="width:10%;text-align: center">
                                    <strong><?php echo esc_html__( 'Remove', 'ovic-vc' ); ?></strong>
                                </td>
                            </tr>
                            </thead>
                            <tbody>
							<?php echo Ovic_VC_Helpers::content_screen_editor( $editor_names ); ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td>
                                    <label class="add-screen">
                                        <a href="#"
                                           class="button"><?php echo esc_html__( 'Add Screen', 'ovic-vc' ); ?></a>
                                    </label>
                                </td>
                                <td>
                                    <label>
                                        <button type="submit" class="button-primary">
											<?php echo esc_html__( 'Save', 'ovic-vc' ); ?>
                                        </button>
                                    </label>
                                </td>
                                <td colspan="2" style="text-align: end">
                                    <label style="display: inline-block;">
                                        <button type="submit" class="reset preview button">
											<?php echo esc_html__( 'Reset', 'ovic-vc' ); ?>
                                        </button>
                                    </label>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </form>
                    <div class="tab-banner">
                        <a href="<?php echo esc_url( 'https://kutethemes.com/' ); ?>" target="_blank">
                            <img src="<?php echo OVIC_VC_BACKEND_ASSETS_URL . '/images/banner.jpg'; ?>"
                                 alt="" style="max-width: 100%;">
                        </a>
                    </div>
                </div>
            </div>
			<?php
		}
	}

	new Ovic_VC_Dashboard();
}