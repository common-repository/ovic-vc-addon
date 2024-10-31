<?php
/**
 * Ovic_VC Visual composer setup
 *
 * @author   KHANH
 * @category API
 * @package  Ovic_VC_Init
 * @since    1.0.2
 */
if (!class_exists('Ovic_VC_Init')) {
    class Ovic_VC_Init
    {
        public function __construct()
        {
            /* OPTIONS DEFAULT */
            add_action('vc_before_init', array($this, 'map_shortcode'));
            /* CUSTOM CSS EDITOR */
            add_action('vc_after_mapping', array($this, 'add_param_all_shortcode'));
            add_filter('vc_shortcodes_css_class', array($this, 'shortcodes_css_class'), 10, 3);
            add_filter('ovic_vc_main_custom_css', array($this, 'main_custom_css'));
            /* INCLUDE SHORTCODE */
            add_action('vc_after_init', array($this, 'include_shortcode'));
        }

        public static function responsive_data($screen = true)
        {
            $default        = Ovic_VC_Helpers::default_screen();
            $editor_options = get_option('_ovic_vc_options');
            $options        = get_option('_ovic_vc_options_responsive', array());
            if (empty($options)) {
                $options = $default;
            }
            if ($screen && isset($editor_options['screen_responsive']) && $editor_options['screen_responsive'] == 'no') {
                $options = array($options['desktop']);
            }

            return apply_filters('responsive_data', $options);
        }

        public function main_custom_css($css)
        {
            $id_page    = '';
            $inline_css = array();
            if (is_front_page() || is_home()) {
                $id_page = get_queried_object_id();
            } elseif (is_singular()) {
                if (!$id_page) {
                    $id_page = get_the_ID();
                }
            } elseif (function_exists('is_woocommerce') && is_woocommerce()) {
                $id_page = get_option('woocommerce_shop_page_id');
            }
            if ($id_page != '') {
                $inline_css[] = get_post_meta($id_page, '_Ovic_VC_Shortcode_Custom_Css', true);
                if (!empty($inline_css)) {
                    $css .= implode(' ', $inline_css);
                }
            }

            return $css;
        }

        public function font_container_output_data($data, $fields, $values, $settings)
        {
            if (isset($fields['text_align'])) {
                $data['text_align'] = '
                <div class="vc_row-fluid vc_column">
                    <div class="wpb_element_label">'.esc_html__('Text align', 'ovic-vc').'</div>
                    <div class="vc_font_container_form_field-text_align-container">
                        <select class="vc_font_container_form_field-text_align-select">
                            <option value="" class="" '.('' === $values['text_align'] ? 'selected="selected"' : '').'>'.esc_html__('None', 'ovic-vc').'</option>
                            <option value="left" class="left" '.('left' === $values['text_align'] ? 'selected="selected"' : '').'>'.esc_html__('left', 'ovic-vc').'</option>
                            <option value="right" class="right" '.('right' === $values['text_align'] ? 'selected="selected"' : '').'>'.esc_html__('right', 'ovic-vc').'</option>
                            <option value="center" class="center" '.('center' === $values['text_align'] ? 'selected="selected"' : '').'>'.esc_html__('center', 'ovic-vc').'</option>
                            <option value="justify" class="justify" '.('justify' === $values['text_align'] ? 'selected="selected"' : '').'>'.esc_html__('justify', 'ovic-vc').'</option>
                        </select>
                    </div>';
                if (isset($fields['text_align_description']) && strlen($fields['text_align_description']) > 0) {
                    $data['text_align'] .= '
                    <span class="vc_description clear">'.$fields['text_align_description'].'</span>
                    ';
                }
                $data['text_align'] .= '</div>';
            }

            return $data;
        }

        public static function get_google_font_data($tag, $atts, $key = 'google_fonts')
        {
            extract($atts);
            $google_fonts_field          = WPBMap::getParam($tag, $key);
            $google_fonts_obj            = new Vc_Google_Fonts();
            $google_fonts_field_settings = isset($google_fonts_field['settings'], $google_fonts_field['settings']['fields']) ? $google_fonts_field['settings']['fields'] : array();
            $google_fonts_data           = strlen($atts[$key]) > 0 ? $google_fonts_obj->_vc_google_fonts_parse_attributes($google_fonts_field_settings, $atts[$key]) : '';

            return $google_fonts_data;
        }

        public function shortcodes_css_class($class_string, $tag, $atts)
        {
            $editor_names = $this->responsive_data();
            $atts         = function_exists('vc_map_get_attributes') ? vc_map_get_attributes($tag, $atts) : $atts;
            // Extract shortcode parameters.
            extract($atts);
            $google_fonts_data = array();
            $class_string      = array($class_string);
            $class_string[]    = isset($atts['el_class']) ? $atts['el_class'] : '';
            $class_string[]    = isset($atts['ovic_vc_custom_id']) ? $atts['ovic_vc_custom_id'] : '';
            $settings          = get_option('wpb_js_google_fonts_subsets');
            if (is_array($settings) && !empty($settings)) {
                $subsets = '&subset='.implode(',', $settings);
            } else {
                $subsets = '';
            }
            if (strpos($tag, 'vc_wp') === false && $tag != 'vc_btn' && $tag != 'vc_tta_section' && $tag != 'vc_icon') {
                $class_string[] = isset($atts["css"]) ? vc_shortcode_custom_css_class($atts["css"], '') : '';
            }
            if (!empty($editor_names)) {
                foreach ($editor_names as $key => $data) {
                    $class_string[] = (isset($atts["css_{$key}"]) && $key != 'desktop') ? vc_shortcode_custom_css_class($atts["css_{$key}"], '') : '';
                    /* GOOGLE FONT */
                    if (isset($atts["google_fonts_{$key}"])) {
                        $google_fonts_data = $this->get_google_font_data($tag, $atts, "google_fonts_{$key}");
                    }
                    if ((!isset($atts["use_theme_fonts_{$key}"]) || 'yes' !== $atts["use_theme_fonts_{$key}"]) && isset($google_fonts_data['values']['font_family'])) {
                        wp_enqueue_style('vc_google_fonts_'.vc_build_safe_css_class($google_fonts_data['values']['font_family']), '//fonts.googleapis.com/css?family='.$google_fonts_data['values']['font_family'].$subsets);
                    }
                }
            }

            return preg_replace('/\s+/', ' ', implode(' ', $class_string));
        }

        public function add_param_all_shortcode()
        {
            global $shortcode_tags;

            $enable_advanced   = true;
            $editor_names      = $this->responsive_data();
            $editor_options    = get_option('_ovic_vc_options');
            $advanced_options  = isset($editor_options['advanced_options']) ? $editor_options['advanced_options'] : 'yes';
            $screen_responsive = isset($editor_options['screen_responsive']) ? $editor_options['screen_responsive'] : 'yes';

            if ($advanced_options == 'no' || $screen_responsive == 'no') {
                $enable_advanced = false;
            }

            WPBMap::addAllMappedShortcodes();
            if (!empty($shortcode_tags) && !empty($editor_names)) {
                $dismiss     = array(
                    'vc_btn',
                    'vc_icon',
                    'vc_tta_section',
                );
                $none_editor = array(
                    'woocommerce_cart',
                    'woocommerce_checkout',
                    'woocommerce_order_tracking',
                );
                foreach ($shortcode_tags as $tag => $function) {
                    if (class_exists('WooCommerce') && in_array($tag, $none_editor)) {
                        continue;
                    }

                    vc_remove_param($tag, 'el_class');

                    if (strpos($tag, 'vc_wp') === false && !in_array($tag, $dismiss)) {
                        /* UPDATE POST META */
                        vc_remove_param($tag, 'css');

                        add_filter('vc_base_build_shortcodes_custom_css', '__return_empty_string');
                        add_filter('vc_font_container_output_data', array($this, 'font_container_output_data'), 10, 4);

                        /* MARKUP HTML TAB */
                        $html_tab = '';
                        if (!empty($editor_names) && count($editor_names) > 1) {
                            $html_tab .= '<div class="plugin-tabs-css">';
                            foreach ($editor_names as $key => $data) {
                                $name     = ucfirst($data['name']);
                                $active   = ($key == 'desktop') ? ' active' : '';
                                $html_tab .= "<span class='tab-item {$key}{$active}' data-tabs='{$key}'>{$name}</span>";
                            }
                            $html_tab .= '</div>';
                        }

                        /* MARKUP HTML TITLE */
                        $html_title = '<div class="tabs-title">';
                        $html_title .= "<h3 class='title'>".esc_html__('Advanced Options', 'ovic-vc')."</h3>";
                        $html_title .= '</div>';
                        $attributes = array(
                            array(
                                'type'        => 'textfield',
                                'heading'     => esc_html__('Extra class name', 'ovic-vc'),
                                'param_name'  => 'el_class',
                                'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'ovic-vc'),
                            ),
                            array(
                                'param_name' => 'ovic_hidden_markup',
                                'type'       => 'html_markup',
                                'markup'     => $html_tab,
                                'group'      => esc_html__('Design Options', 'ovic-vc'),
                            ),
                            array(
                                'param_name'       => 'ovic_vc_custom_id',
                                'heading'          => esc_html__('Hidden ID', 'ovic-vc'),
                                'type'             => 'uniqid',
                                'edit_field_class' => 'hidden',
                            ),
                        );
                        /* CSS EDITOR */
                        if (!empty($editor_names)) {
                            foreach ($editor_names as $key => $data) {
                                $editor     = array();
                                $name       = ucfirst($data['name']);
                                $hidden     = ($key != 'desktop') ? ' hidden' : '';
                                $param_name = ($key == 'desktop') ? "css" : "css_{$key}";
                                $screen     = $data['screen'] < 999999 ? " ( {$data['media']}: {$data['screen']}px )" : '';

                                /* CSS EDITOR */
                                $attributes[] = array(
                                    'type'             => 'css_editor',
                                    'heading'          => esc_html__("Screen {$name}{$screen}", 'ovic-vc'),
                                    'param_name'       => $param_name,
                                    'group'            => esc_html__('Design Options', 'ovic-vc'),
                                    'edit_field_class' => "vc_col-xs-12 {$key}{$hidden}",
                                );
                                if ($enable_advanced) {
                                    $editor = array(
                                        array(
                                            'param_name'       => "hidden_markup_{$key}",
                                            'type'             => 'html_markup',
                                            'markup'           => $html_title,
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 {$key}{$hidden}",
                                        ),
                                        /* CHECKBOX BACKGROUND */
                                        array(
                                            'type'             => 'checkbox',
                                            'heading'          => esc_html__('Disable Background', 'ovic-vc'),
                                            'param_name'       => "disable_bg_{$key}",
                                            'value'            => array(
                                                "<label for='disable_bg_{$key}-yes'></label>" => 'yes',
                                            ),
                                            'edit_field_class' => "ovic-vc-checkbox-field vc_col-xs-12 vc_col-sm-6 {$key}{$hidden}",
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                        ),
                                        /* CHECKBOX DISPLAY NONE */
                                        array(
                                            'type'             => 'checkbox',
                                            'heading'          => esc_html__('Disable Element', 'ovic-vc'),
                                            'param_name'       => "disable_element_{$key}",
                                            'value'            => array(
                                                "<label for='disable_element_{$key}-yes'></label>" => 'yes',
                                            ),
                                            'edit_field_class' => "ovic-vc-checkbox-field vc_col-xs-12 vc_col-sm-6 {$key}{$hidden}",
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                        ),
                                        /* WIDTH CONTAINER */
                                        array(
                                            'type'             => 'textfield',
                                            'heading'          => esc_html__("Width {$name}", 'ovic-vc'),
                                            'param_name'       => "width_rows_{$key}",
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 vc_col-sm-4 {$key}{$hidden}",
                                        ),
                                        /* UNIT CSS WIDTH */
                                        array(
                                            'type'             => 'dropdown',
                                            'heading'          => esc_html__('Unit', 'ovic-vc'),
                                            'param_name'       => "width_unit_{$key}",
                                            'value'            => array(
                                                esc_html__('Percent (%)', 'ovic-vc')     => '%',
                                                esc_html__('Pixel (px)', 'ovic-vc')      => 'px',
                                                esc_html__('Em (em)', 'ovic-vc')         => 'em',
                                                esc_html__('View Width (vw)', 'ovic-vc') => 'vw',
                                                esc_html__('Custom Width', 'ovic-vc')    => 'none',
                                            ),
                                            'std'              => '%',
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 vc_col-sm-4 {$key}{$hidden}",
                                        ),
                                        /* TEXT FONT */
                                        array(
                                            'type'             => 'textfield',
                                            'heading'          => esc_html__('Letter Spacing', 'ovic-vc'),
                                            'param_name'       => "letter_spacing_{$key}",
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 vc_col-sm-4 {$key}{$hidden}",
                                        ),
                                        array(
                                            'type'             => 'font_container',
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'param_name'       => "responsive_font_{$key}",
                                            'edit_field_class' => "vc_col-xs-12 {$key}{$hidden}",
                                            'settings'         => array(
                                                'fields' => array(
                                                    'text_align',
                                                    'font_size',
                                                    'line_height',
                                                    'color',
                                                    'text_align_description'  => esc_html__('Select text alignment.', 'ovic-vc'),
                                                    'font_size_description'   => esc_html__('Enter font size.', 'ovic-vc'),
                                                    'line_height_description' => esc_html__('Enter line height.', 'ovic-vc'),
                                                    'color_description'       => esc_html__('Select heading color.', 'ovic-vc'),
                                                ),
                                            ),
                                        ),
                                        array(
                                            'type'             => 'checkbox',
                                            'heading'          => esc_html__('Use theme default font family?', 'ovic-vc'),
                                            'param_name'       => "use_theme_fonts_{$key}",
                                            'value'            => array(
                                                "<label for='use_theme_fonts_{$key}-yes'></label>" => 'yes',
                                            ),
                                            'std'              => 'yes',
                                            'description'      => esc_html__('Use font family from the theme.', 'ovic-vc'),
                                            'edit_field_class' => "ovic-vc-checkbox-field vc_col-xs-12 {$key}{$hidden}",
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                        ),
                                        array(
                                            'type'             => 'google_fonts',
                                            'param_name'       => "google_fonts_{$key}",
                                            'value'            => 'font_family:Abril%20Fatface%3Aregular|font_style:400%20regular%3A400%3Anormal',
                                            'settings'         => array(
                                                'fields' => array(
                                                    'font_family_description' => esc_html__('Select font family.', 'ovic-vc'),
                                                    'font_style_description'  => esc_html__('Select font styling.', 'ovic-vc'),
                                                ),
                                            ),
                                            'dependency'       => array(
                                                'element'            => "use_theme_fonts_{$key}",
                                                'value_not_equal_to' => 'yes',
                                            ),
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 {$key}{$hidden}",
                                        ),
                                        /* CUSTOM CSS */
                                        array(
                                            'type'             => 'textfield',
                                            'heading'          => esc_html__('Target Main', 'ovic-vc'),
                                            'param_name'       => "target_main_{$key}",
                                            'description'      => esc_html__('Enter Child Target Element Name for This Screen.', 'ovic-vc'),
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 {$key}{$hidden}",
                                        ),
                                        array(
                                            'type'             => 'textfield',
                                            'heading'          => esc_html__('Target Child', 'ovic-vc'),
                                            'param_name'       => "target_child_{$key}",
                                            'description'      => esc_html__('Enter Child Target Name.', 'ovic-vc'),
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 {$key}{$hidden}",
                                        ),
                                        array(
                                            'type'             => 'textarea',
                                            'heading'          => esc_html__('Custom CSS', 'ovic-vc'),
                                            'param_name'       => "custom_css_{$key}",
                                            'description'      => esc_html__('Enter css Properties.', 'ovic-vc'),
                                            'group'            => esc_html__('Design Options', 'ovic-vc'),
                                            'edit_field_class' => "vc_col-xs-12 {$key}{$hidden}",
                                        ),
                                    );
                                }
                                $attributes = array_merge($attributes, $editor);
                            }
                        }
                    } else {
                        $attributes = array(
                            array(
                                'type'        => 'textfield',
                                'heading'     => esc_html__('Extra class name', 'ovic-vc'),
                                'param_name'  => 'el_class',
                                'description' => esc_html__('If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'ovic-vc'),
                            ),
                            array(
                                'param_name'       => 'ovic_vc_custom_id',
                                'heading'          => esc_html__('Hidden ID', 'ovic-vc'),
                                'type'             => 'uniqid',
                                'edit_field_class' => 'hidden',
                            ),
                        );
                    }

                    vc_add_params($tag, $attributes);
                }
            }
        }

        /**
         *
         * Class name: must "Shortcode_{ base name shortcode }"
         **/
        public function param_visual_composer()
        {
            $param = array();

            return apply_filters('ovic_vc_add_param_visual_composer', $param);
        }

        public function map_shortcode()
        {
            $param_maps = $this->param_visual_composer();
            if (!empty($param_maps)) {
                foreach ($param_maps as $map) {
                    if (function_exists('vc_map')) {
                        vc_map($map);
                    }
                }
            }
        }

        private function get_templates($template_name)
        {
            $directory_shortcode = '';
            $path_templates      = apply_filters('ovic_include_templates_shortcode', 'vc_templates', $template_name);
            if (is_file(OVIC_VC_SHORTCODE_TEMPLATES_PATH."/{$template_name}.php")) {
                $directory_shortcode = OVIC_VC_SHORTCODE_TEMPLATES_PATH;
            }
            if (is_file(get_template_directory()."/{$path_templates}/{$template_name}.php")) {
                $directory_shortcode = get_template_directory()."/{$path_templates}";
            }
            if ($directory_shortcode != '') {
                include_once "{$directory_shortcode}/{$template_name}.php";
            }
        }

        function include_shortcode()
        {
            $param_maps = $this->param_visual_composer();
            if (!empty($param_maps)) {
                foreach ($param_maps as $shortcode) {
                    $this->get_templates($shortcode['base']);
                }
            }
        }
    }

    new Ovic_VC_Init();
}