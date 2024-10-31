<?php
/**
 * Theme updater admin page and functions.
 *
 * @package Ovic Theme Updater
 */
if ( !class_exists( 'Ovic_Updater_Admin' ) ) {
	class Ovic_Updater_Admin
	{
		/**
		 * Variables required for the updater
		 *
		 * @since 1.0.0
		 * @type string
		 */
		private $remote_api_url  = null;
		private $item_slug       = null;
		private $version         = null;
		private $author          = null;
		private $download_id     = null;
		private $renew_url       = null;
		private $strings         = null;
		private $root_uri        = null;
		private $item_link       = null;
		private $setting_license = null;

		/**
		 * Initialize the class.
		 *
		 * @since 1.0.0
		 */
		function __construct( $config = array() )
		{
			$config  = wp_parse_args( $config, array(
					'remote_api_url'  => 'https://kutethemes.com',
					'item_slug'       => '',
					'item_name'       => '',
					'license'         => '',
					'version'         => '',
					'author'          => 'Ovic Team',
					'download_id'     => '',
					'renew_url'       => '',
					'root_uri'        => '',
					'plugin_slug'     => '',
					'item_link'       => '',
					'setting_license' => '',
				)
			);
			$strings = array(
				'item-license'              => __( 'Theme License' ),
				'enter-key'                 => __( 'Enter your item license key.' ),
				'license-key'               => __( 'License Key' ),
				'license-action'            => __( 'License Action' ),
				'deactivate-license'        => __( 'Deactivate License' ),
				'activate-license'          => __( 'Activate License' ),
				'status-unknown'            => __( 'License status is unknown.' ),
				'renew'                     => __( 'Renew?' ),
				'unlimited'                 => __( 'unlimited' ),
				'license-key-is-active'     => __( 'License key is active.' ),
				'expires%s'                 => __( 'Expires %s.' ),
				'%1$s/%2$-sites'            => __( 'You have %1$s / %2$s sites activated.' ),
				'license-key-expired-%s'    => __( 'License key expired %s.' ),
				'license-key-expired'       => __( 'License key has expired.' ),
				'license-keys-do-not-match' => __( 'License keys do not match.' ),
				'license-is-inactive'       => __( 'License is inactive.' ),
				'license-key-is-disabled'   => __( 'License key is disabled.' ),
				'site-is-inactive'          => __( 'Site is inactive.' ),
				'license-status-unknown'    => __( 'License status is unknown.' ),
				'update-notice'             => __( 'Updating this item will lose any customizations you have made. \'Cancel\' to stop, \'OK\' to update.' ),
				'update-available'          => __( '<strong>%1$s %2$s</strong> is available. <a href="%3$s" class="thickbox" title="%4s">Check out what\'s new</a> or <a href="%5$s"%6$s>update now</a>.' ),
			);
			// Set config arguments
			$this->remote_api_url  = $config['remote_api_url'];
			$this->item_name       = $config['item_name'];
			$this->item_slug       = sanitize_key( $config['item_slug'] );
			$this->version         = $config['version'];
			$this->author          = $config['author'];
			$this->download_id     = $config['download_id'];
			$this->renew_url       = $config['renew_url'];
			$this->root_uri        = !empty( $config['root_uri'] ) ? $config['root_uri'] : $config['plugin_slug'];
			$this->item_link       = $config['item_link'];
			$this->setting_license = $config['setting_license'];
			// Strings passed in from the updater config
			$this->strings = $strings;
			add_action( 'ovic_license_' . $this->item_slug . '_page', array( $this, 'license_page' ) );
			add_action( 'admin_init', array( $this, 'register_option' ) );
			add_action( 'admin_init', array( $this, 'license_action' ) );
			add_action( 'admin_notices', array( $this, 'display_message' ) );
			add_action( 'update_option_' . $this->item_slug . '_license_key', array( $this, 'activate_license' ), 10, 2 );
		}

		/**
		 * Creates the updater class.
		 *
		 * since 1.0.0
		 */
		function updater()
		{
			$license_key = get_option( $this->item_slug . '_license_key' );
			$auto_update = dirname( __FILE__ ) . '/plugin-update-checker/plugin-update-checker.php';
			/* If there is no valid license key status, don't allow updates. */
			if ( get_option( $this->item_slug . '_license_key_status', false ) != 'valid' ) {
				return;
			}
			/* UPDATE PLUGIN AUTOMATIC */
			if ( $license_key != '' && file_exists( $auto_update ) ) {
				if ( !class_exists( 'Puc_v4_Factory' ) ) {
					include_once $auto_update;
				}
				$api_param     = add_query_arg(
					array(
						'edd_action' => 'get_version',
						'license'    => $license_key,
						'item_name'  => $this->item_name,    // id of this product in EDD
						'slug'       => $this->item_slug,    // id of this product in EDD
						'author'     => $this->author,  // author of this plugin
						'url'        => home_url( '/' ),
					),
					$this->remote_api_url
				);
				$updatechecker = Puc_v4_Factory::buildUpdateChecker(
					$api_param,
					$this->root_uri,
					$this->item_slug
				);
			}
		}

		/**
		 * Outputs the markup used on the license page.
		 *
		 * since 1.0.0
		 */
		function license_page()
		{
			$strings = $this->strings;
			$license = trim( get_option( $this->item_slug . '_license_key' ) );
			$status  = get_option( $this->item_slug . '_license_key_status', false );
			// Checks license status to display under license key
			if ( !$license ) {
				$message = $strings['enter-key'];
			} else {
				// delete_transient( $this->item_slug . '_license_message' );
				if ( !get_transient( $this->item_slug . '_license_message' ) ) {
					set_transient( $this->item_slug . '_license_message', $this->check_license(), ( 60 * 60 * 24 ) );
				}
				$message = get_transient( $this->item_slug . '_license_message' );
			}
			ob_start(); ?>
            <div class="wrap-license">
                <h2><?php _e( 'Plugin License Options' ); ?></h2>
                <form method="post" action="options.php">
					<?php
					settings_fields( $this->item_slug . '-license' );
					?>
                    <table class="form-table">
                        <tbody>
                        <tr valign="top">
                            <th scope="row" valign="top">
								<?php echo $strings['license-key']; ?>
                            </th>
                            <td>
                                <input id="<?php echo $this->item_slug; ?>_license_key"
                                       name="<?php echo $this->item_slug; ?>_license_key" type="text"
                                       class="regular-text"
                                       value="<?php echo esc_attr( $license ); ?>"/>
                                <p class="description">
									<?php echo $message; ?>
                                </p>
                            </td>
                        </tr>
						<?php if ( $license ) { ?>
                            <tr valign="top">
                                <th scope="row" valign="top">
									<?php echo $strings['license-action']; ?>
                                </th>
                                <td>
									<?php
									wp_nonce_field( $this->item_slug . '_nonce', $this->item_slug . '_nonce' );
									if ( 'valid' == $status ) { ?>
                                        <input type="submit" class="button-secondary"
                                               name="<?php echo $this->item_slug; ?>_license_deactivate"
                                               value="<?php esc_attr_e( $strings['deactivate-license'] ); ?>"/>
									<?php } else { ?>
                                        <input type="submit" class="button-secondary"
                                               name="<?php echo $this->item_slug; ?>_license_activate"
                                               value="<?php esc_attr_e( $strings['activate-license'] ); ?>"/>
									<?php }
									?>
                                </td>
                            </tr>
						<?php } ?>
                        </tbody>
                    </table>
					<?php submit_button(); ?>
                </form>
            </div>
			<?php
		}

		public function display_message()
		{
			$license = trim( get_option( $this->item_slug . '_license_key' ) );
			$status  = get_option( $this->item_slug . '_license_key_status', false );
			$cookie  = "ovic_hide_activationmsg_{$this->item_slug}";
			// Checks license status to display under license key
			if ( !$license || $status != 'valid' ) {
				if ( isset( $_COOKIE[$cookie] ) && $_COOKIE[$cookie] == 'hide' ) {
					return;
				}
				?>
                <script type="text/javascript">
                    (function ($) {
                        var setCookie = function (c_name, value, exdays) {
                            var exdate = new Date();
                            exdate.setDate(exdate.getDate() + exdays);
                            var c_value     = encodeURIComponent(value) + ((null === exdays) ? "" : "; expires=" + exdate.toUTCString());
                            document.cookie = c_name + "=" + c_value;
                        };
                        $(document).on('click.ovic-notice-dismiss',
                            '.ovic-notice-dismiss',
                            function (e) {
                                e.preventDefault();
                                var $el = $(this).closest('.ovic_license-activation-notice'),
                                    $id = $el.attr('data-cookie');
                                
                                $el.fadeTo(100, 0, function () {
                                    $el.slideUp(100, function () {
                                        $el.remove();
                                    });
                                });
                                setCookie($id, 'hide', 30);
                            });
                    })(window.jQuery);
                </script>
                <div data-cookie="<?php echo esc_attr( $cookie ); ?>"
                     class="notice-error settings-error notice ovic_license-activation-notice"
                     style="position: relative;">
                    <p>
                        <strong>Warning!</strong> You didn't set license key for the following products:
                        <code><?php echo esc_html( $this->item_name ); ?></code>
                        which means you're missing out on updates and support.
                        <a href="<?php echo esc_url( $this->setting_license ); ?>">Enter your license key</a> , or
                        <a href="<?php echo esc_url( $this->item_link ); ?>">Buy product</a>,
                        please.
                    </p>
                    <button type="button" class="ovic-notice-dismiss notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
				<?php
			}
		}

		/**
		 * Registers the option used to store the license key in the options table.
		 *
		 * since 1.0.0
		 */
		function register_option()
		{
			register_setting(
				$this->item_slug . '-license',
				$this->item_slug . '_license_key',
				array( $this, 'sanitize_license' )
			);
		}

		/**
		 * Sanitizes the license key.
		 *
		 * since 1.0.0
		 *
		 * @param string $new License key that was submitted.
		 *
		 * @return string $new Sanitized license key.
		 */
		function sanitize_license( $new )
		{
			$old = get_option( $this->item_slug . '_license_key' );
			if ( $old && $old != $new ) {
				// New license has been entered, so must reactivate
				delete_option( $this->item_slug . '_license_key_status' );
				delete_transient( $this->item_slug . '_license_message' );
			}

			return $new;
		}

		/**
		 * Makes a call to the API.
		 *
		 * @param array $api_params to be used for wp_remote_get.
		 *
		 * @return array $response decoded JSON response.
		 * @since 1.0.0
		 *
		 */
		function get_api_response( $api_params )
		{
			// Call the custom API.
			$response = wp_remote_get(
				add_query_arg( $api_params, $this->remote_api_url ),
				array( 'timeout' => 15, 'sslverify' => false )
			);
			// Make sure the response came back okay.
			if ( is_wp_error( $response ) ) {
				return array();
			}
			$response = json_decode( wp_remote_retrieve_body( $response ) );

			return $response;
		}

		/**
		 * Activates the license key.
		 *
		 * @since 1.0.0
		 */
		function activate_license()
		{
			$license = trim( get_option( $this->item_slug . '_license_key' ) );
			// Data to send in our API request.
			$api_params   = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( $this->item_name ),
			);
			$license_data = $this->get_api_response( $api_params );
			// $response->license will be either "active" or "inactive"
			if ( $license_data && isset( $license_data->license ) ) {
				update_option( $this->item_slug . '_license_key_status', $license_data->license );
				delete_transient( $this->item_slug . '_license_message' );
			}
		}

		/**
		 * Deactivates the license key.
		 *
		 * @since 1.0.0
		 */
		function deactivate_license()
		{
			// Retrieve the license from the database.
			$license = trim( get_option( $this->item_slug . '_license_key' ) );
			// Data to send in our API request.
			$api_params   = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_name'  => urlencode( $this->item_name ),
			);
			$license_data = $this->get_api_response( $api_params );
			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data && ( $license_data->license == 'deactivated' ) ) {
				delete_option( $this->item_slug . '_license_key_status' );
				delete_transient( $this->item_slug . '_license_message' );
			}
		}

		/**
		 * Constructs a renewal link
		 *
		 * @since 1.0.0
		 */
		function get_renewal_link()
		{
			// If a renewal link was passed in the config, use that
			if ( '' != $this->renew_url ) {
				return $this->renew_url;
			}
			// If download_id was passed in the config, a renewal link can be constructed
			$license_key = trim( get_option( $this->item_slug . '_license_key', false ) );
			if ( '' != $this->download_id && $license_key ) {
				$url = esc_url( $this->remote_api_url );
				$url .= '/checkout/?edd_license_key=' . $license_key . '&download_id=' . $this->download_id;

				return $url;
			}

			// Otherwise return the remote_api_url
			return $this->remote_api_url;
		}

		/**
		 * Checks if a license action was submitted.
		 *
		 * @since 1.0.0
		 */
		function license_action()
		{
			if ( isset( $_POST[$this->item_slug . '_license_activate'] ) ) {
				if ( check_admin_referer( $this->item_slug . '_nonce', $this->item_slug . '_nonce' ) ) {
					$this->activate_license();
				}
			}
			if ( isset( $_POST[$this->item_slug . '_license_deactivate'] ) ) {
				if ( check_admin_referer( $this->item_slug . '_nonce', $this->item_slug . '_nonce' ) ) {
					$this->deactivate_license();
				}
			}
		}

		/**
		 * Checks if license is valid and gets expire date.
		 *
		 * @return string $message License status message.
		 * @since 1.0.0
		 *
		 */
		function check_license()
		{
			$license      = trim( get_option( $this->item_slug . '_license_key' ) );
			$strings      = $this->strings;
			$api_params   = array(
				'edd_action' => 'check_license',
				'license'    => $license,
				'item_name'  => urlencode( $this->item_name ),
			);
			$license_data = $this->get_api_response( $api_params );
			// If response doesn't include license data, return
			if ( !isset( $license_data->license ) ) {
				$message = $strings['license-unknown'];

				return $message;
			}
			if ( isset( $license_data->license ) && $license_data->license == 'invalid' ) {
				$message = 'License keys do not match.';

				return $message;
			}
			// Get expire date
			$expires = false;
			if ( isset( $license_data->expires ) ) {
				$expires    = date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires ) );
				$renew_link = '<a href="' . esc_url( $this->get_renewal_link() ) . '" target="_blank">' . $strings['renew'] . '</a>';
			}
			// Get site counts
			$site_count    = $license_data->site_count;
			$license_limit = $license_data->license_limit;
			// If unlimited
			if ( 0 == $license_limit ) {
				$license_limit = $strings['unlimited'];
			}
			if ( $license_data->license == 'valid' ) {
				$message = $strings['license-key-is-active'] . ' ';
				if ( $expires ) {
					$message .= sprintf( $strings['expires%s'], $expires ) . ' ';
				}
				if ( $site_count && $license_limit ) {
					$message .= sprintf( $strings['%1$s/%2$-sites'], $site_count, $license_limit );
				}
			} else if ( $license_data->license == 'expired' ) {
				if ( $expires ) {
					$message = sprintf( $strings['license-key-expired-%s'], $expires );
				} else {
					$message = $strings['license-key-expired'];
				}
				if ( $renew_link ) {
					$message .= ' ' . $renew_link;
				}
			} else if ( $license_data->license == 'invalid' ) {
				$message = $strings['license-keys-do-not-match'];
			} else if ( $license_data->license == 'inactive' ) {
				$message = $strings['license-is-inactive'];
			} else if ( $license_data->license == 'disabled' ) {
				$message = $strings['license-key-is-disabled'];
			} else if ( $license_data->license == 'site_inactive' ) {
				// Site is inactive
				$message = $strings['site-is-inactive'];
			} else {
				$message = $strings['license-status-unknown'];
			}

			return $message;
		}
	}
}