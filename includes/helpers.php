<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('Ovic_VC_Helpers')) {
    class Ovic_VC_Helpers
    {
        /**
         * Meta key.
         *
         * @var  string
         */
        protected $option_key = '_ovic_vc_options_responsive';
        protected $ad_key     = '_ovic_vc_options';

        public function __construct()
        {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('after_setup_theme', array($this, 'load_after_setup'));
            add_action('wp_ajax_ovic_vc_submit', array($this, 'ovic_vc_submit'));
        }

        public function admin_scripts($preflix)
        {
            global $post;
            if ((isset($post->ID) && function_exists('vc_check_post_type') && apply_filters('vc_is_valid_post_type_be', vc_check_post_type(get_post_type($post->ID)), get_post_type($post->ID))) || $preflix == 'ovic-plugins_page_ovic-vc-addon') {
                wp_enqueue_style('chosen', OVIC_VC_BACKEND_ASSETS_URL . '/css/chosen.min.css');
                wp_enqueue_style('ovic-vc-backend', OVIC_VC_BACKEND_ASSETS_URL . '/css/backend.min.css');

                /* SCRIPTS */
                wp_enqueue_script('jquery-ui-datepicker');
                wp_enqueue_script('chosen', OVIC_VC_BACKEND_ASSETS_URL . '/js/libs/chosen.min.js', array(), '1.8.7', true);
                wp_enqueue_script('chosen-order', OVIC_VC_BACKEND_ASSETS_URL . '/js/libs/chosen.order.min.js', array(), '1.2.1', true);
                wp_enqueue_script('ovic-vc-backend', OVIC_VC_BACKEND_ASSETS_URL . '/js/backend.min.js', array(
                    'jquery',
                    'jquery-migrate'
                ), null, true);

                // Add variables to scripts
                wp_localize_script('ovic-vc-backend', 'ovic_vc_params', [
                    'ajaxurl' => add_query_arg(
                        [
                            '_wpnonce' => wp_create_nonce('ovic_vc_nonce')
                        ],
                        admin_url('admin-ajax.php')
                    ),
                ]);
            }
        }

        public function load_after_setup()
        {
            add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        }

        public function frontend_scripts()
        {
            $css     = apply_filters('ovic_vc_main_custom_css', '');
            $content = preg_replace('/\s+/', ' ', $css);

            wp_enqueue_style('ovic-vc-style', OVIC_VC_FRONTEND_ASSETS_URL . '/css/frontend.css');

            wp_add_inline_style('ovic-vc-style', $content);
        }

        public static function default_screen()
        {
            return array(
                'desktop' => array(
                    'screen' => 999999,
                    'name'   => 'Desktop',
                    'media'  => 'max-width',
                ),
                'laptop'  => array(
                    'screen' => 1499,
                    'name'   => 'Laptop',
                    'media'  => 'max-width',
                ),
                'tablet'  => array(
                    'screen' => 1199,
                    'name'   => 'Tablet',
                    'media'  => 'max-width',
                ),
                'ipad'    => array(
                    'screen' => 991,
                    'name'   => 'Ipad',
                    'media'  => 'max-width',
                ),
                'mobile'  => array(
                    'screen' => 767,
                    'name'   => 'Mobile',
                    'media'  => 'max-width',
                ),
            );
        }

        public static function content_screen_editor($editor_names)
        {
            $default = Ovic_VC_Helpers::default_screen();
            ob_start();
            if (!empty($editor_names)) :
                arsort($editor_names);
                foreach ($editor_names as $key => $data) :
                    ?>
                    <tr class="item-vc">
                        <td>
                            <label>
                                <input type="text"
                                       name="name"
                                       value="<?php echo esc_attr($data['name']) ?>">
                            </label>
                        </td>
                        <td>
                            <label>
                                <?php if ($data['screen'] < 999999) : ?>
                                    <select type="text" name="media">
                                        <option value="max-width" <?php echo selected('max-width', $data['media']); ?>>
                                            <?php echo esc_html__('max-width', 'ovic-vc'); ?>
                                        </option>
                                        <option value="min-width" <?php echo selected('min-width', $data['media']); ?>>
                                            <?php echo esc_html__('min-width', 'ovic-vc'); ?>
                                        </option>
                                    </select>
                                <?php else: ?>
                                    <input type="hidden"
                                           name="media"
                                           value="none">
                                <?php endif; ?>
                            </label>
                        </td>
                        <td>
                            <label>
                                <?php if ($data['screen'] < 999999) : ?>
                                    <input type="number"
                                           name="screen"
                                           value="<?php echo esc_attr($data['screen']) ?>">
                                <?php else: ?>
                                    <input type="hidden"
                                           name="screen"
                                           value="<?php echo esc_attr($data['screen']) ?>">
                                <?php endif; ?>
                            </label>
                        </td>
                        <td style="text-align: center;vertical-align: middle;">
                            <?php if (!array_key_exists($key, $default)): ?>
                                <a href="#" class="remove"><?php echo esc_html__('Remove', 'ovic-vc'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
            endif;

            return apply_filters('ovic_vc_content_screen_editor', ob_get_clean());
        }

        function ovic_vc_submit()
        {
            if ( !current_user_can('manage_options') ) {
                wp_die (__("You don't have permission to access this page.") );
            }

            $response = array(
                'html'    => '',
                'message' => '',
                'success' => 'no',
            );

            if (!current_user_can('edit_theme_options')) {
                wp_send_json($response);
                wp_die();
            }

            $editor  = array();
            $options = array();
            $default = $this->default_screen();
            $data    = isset($_POST['data']) ? wp_unslash($_POST['data']) : array();
            $reset   = isset($_POST['reset']) ? absint($_POST['reset']) : 0;

            if (!empty($data)) {
                $options['advanced_options']  = isset($data['enable_advanced_options']) ? 'yes' : 'no';
                $options['screen_responsive'] = isset($data['enable_screen_responsive']) ? 'yes' : 'no';
                foreach ($data['name'] as $key => $name) {
                    if ($name != '' && $data['screen'][$key] != '') {
                        /* regen array */
                        if ($key == 0) {
                            $slug = 'desktop';
                        } else {
                            $delimiter = '_';
                            $slug      = strtolower(trim(preg_replace('/[\s-]+/', $delimiter, preg_replace('/[^A-Za-z0-9-]+/', $delimiter, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', $name)))), $delimiter));
                        }
                        if (!array_key_exists($slug, $editor)) {
                            $editor[$slug] = array(
                                'screen' => $data['screen'][$key],
                                'media'  => $data['media'][$key],
                                'name'   => $name,
                            );
                        }
                    }
                }
            }

            if ($reset == 1 || empty($data)) {
                $editor  = $default;
                $options = array();
            }

            /* UPDATE OPTIONS */
            if (get_option($this->option_key) !== false) {
                // The option already exists, so we just update it.
                update_option($this->option_key, $editor);
                update_option($this->ad_key, $options);
            } else {
                // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
                $deprecated = null;
                $autoload   = 'no';
                add_option($this->option_key, $editor, $deprecated, $autoload);
                add_option($this->ad_key, $options, $deprecated, $autoload);
            }

            $response['html']    = $this->content_screen_editor($editor);
            $response['success'] = 'ok';

            wp_send_json($response);
            wp_die();
        }
    }

    new Ovic_VC_Helpers();
}