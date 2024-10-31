<?php
if (!class_exists('Ovic_VC_Params')) {
    class Ovic_VC_Params
    {
        /**
         * @var Ovic_VC_Params
         */
        private static $instance;

        public static function instance()
        {
            if (!isset(self::$instance) && !(self::$instance instanceof Ovic_VC_Params)) {
                self::$instance = new Ovic_VC_Params;
                self::$instance->params();
            }

            return self::$instance;
        }

        function params()
        {
            /* Add Shortcode Preview */
            add_action('admin_footer', array($this, 'preview_modal'));

            /* Add Shortcode Param */
            vc_add_shortcode_param('carousel', array($this, 'generate_field_carousel'));
            vc_add_shortcode_param('bootstrap_v3', array($this, 'generate_field_bootstrap_v3'));
            vc_add_shortcode_param('bootstrap_v4', array($this, 'generate_field_bootstrap_v4'));
            vc_add_shortcode_param('number', array($this, 'number_field'));
            vc_add_shortcode_param('uniqid', array($this, 'uniqid_field'));
            vc_add_shortcode_param('html_markup', array($this, 'html_markup_field'));
            vc_add_shortcode_param('select_preview', array($this, 'select_preview_field'));
            vc_add_shortcode_param('datepicker', array($this, 'datepicker_field'));
            vc_add_shortcode_param('taxonomy', array($this, 'taxonomy_field'));
            vc_add_shortcode_param('multiselect', array($this, 'multiselect_field'));
        }

        public static function is_boolean($string)
        {
            $string = strtolower($string);

            return (in_array($string, array("true", "false", "1", "0", "yes", "no"), true));
        }

        public static function preg_replace_callback($matches)
        {
            $name = str_replace(array('"', ':'), array('', ''), $matches[0]);
            if (is_numeric($name) || self::is_boolean($name) === true) {
                return ":{$name}";
            }

            return $matches[0];
        }

        public static function generate_grid_attr($data)
        {
            $classes = array();
            $atts    = array();
            parse_str(html_entity_decode($data), $atts);
            if (!empty($atts['settings'])) {
                $classes[] = $atts['settings']['rows_space'];
                $classes[] = $atts['settings']['desktop'];
                $classes[] = $atts['settings']['laptop'];
                $classes[] = $atts['settings']['ipad'];
                $classes[] = $atts['settings']['landscape_tablet'];
                $classes[] = $atts['settings']['portrait_tablet'];
                $classes[] = $atts['settings']['mobile'];
            }

            return implode(' ', $classes);
        }

        public static function generate_slide_attr($data)
        {
            $data_slick = '';
            if ($data) {
                if (is_array($data)) {
                    $check = htmlspecialchars(' data-slick='.json_encode($data).' style='.self::generate_style($data).' ');
                    return $check;
                }
                parse_str(html_entity_decode($data), $atts);
                if (!isset($atts['settings']['centerMode'])) {
                    unset($atts['settings']['centerPadding']);
                }
                if (!isset($atts['settings']['vertical'])) {
                    unset($atts['settings']['verticalSwiping']);
                }
                if (!isset($atts['settings']['autoplay'])) {
                    unset($atts['settings']['autoplaySpeed']);
                }
                if (!isset($atts['settings']['infinite'])) {
                    $atts['settings']['infinite'] = false;
                }
                if (is_rtl()) {
                    $atts['settings']['rtl'] = true;
                }
                $data_slick = array_merge($atts['settings'], array('responsive' => array_values($atts['responsive'])));
                $data_slick = preg_replace_callback(
                    '/:"[^"]+"/',
                    array(self::$instance, 'preg_replace_callback'),
                    json_encode($data_slick)
                );
            }

            return htmlspecialchars(' data-slick='.$data_slick.' style='.self::generate_style($atts).' ');
        }

        public static function generate_style($data)
        {
            $style = '';

            if (isset($data['settings']["slidesToShow"])) {
                $style .= '--show:'.(int)$data['settings']["slidesToShow"].';';
            }
            if (isset($data['settings']["slidesMargin"])) {
                $style .= '--margin:'.(int)$data['settings']["slidesMargin"].'px;';
            }
            if (isset($data["slidesToShow"])) {
                $style .= '--show:'.(int)$data["slidesToShow"].';';
            }
            if (isset($data["slidesMargin"])) {
                $style .= '--margin:'.(int)$data["slidesMargin"].'px;';
            }

            if (!empty($data["responsive"])) {
                foreach ($data["responsive"] as $tab) {
                    if (empty($tab['breakpoint'])) {
                        continue;
                    }
                    $breakpoint = $tab['breakpoint'];
                    $show       = !empty($tab['settings']['slidesToShow']) ? (int)$tab['settings']['slidesToShow'] : 0;
                    $margin     = !empty($tab['settings']['slidesMargin']) ? (int)$tab['settings']['slidesMargin'] : 0;

                    if ($breakpoint <= 1500 && $breakpoint > 1200) {
                        $style .= '--show-laptop:'.$show.';--margin-laptop:'.$margin.'px;';
                    } elseif ($breakpoint <= 1200 && $breakpoint > 992) {
                        $style .= '--show-ipad:'.$show.';--margin-ipad:'.$margin.'px;';
                    } elseif ($breakpoint <= 992 && $breakpoint > 768) {
                        $style .= '--show-landscape:'.$show.';--margin-landscape:'.$margin.'px;';
                    } elseif ($breakpoint <= 768 && $breakpoint > 480) {
                        $style .= '--show-portrait:'.$show.';--margin-portrait:'.$margin.'px;';
                    } elseif ($breakpoint <= 480) {
                        $style .= '--show-mobile:'.$show.';--margin-mobile:'.$margin.'px;';
                    }
                }
            }

            return $style;
        }

        public static function get_field($data)
        {
            $html = '';
            if (!empty($data)) {
                foreach ($data as $datum) {
                    $field = "field_{$datum['type']}";
                    $html  .= self::$field($datum);
                }
            }

            return $html;
        }

        public function getCategoryChildsFull($parent_id, $array, $level, &$dropdown)
        {
            $keys = array_keys($array);
            $i    = 0;
            while ($i < count($array)) {
                $key  = $keys[$i];
                $item = $array[$key];
                $i++;
                if ($item->category_parent == $parent_id) {
                    $name             = str_repeat('- ', $level).$item->name;
                    $value            = $item->slug;
                    $dropdown[$value] = array(
                        'label' => $name.'('.$item->count.')',
                        'value' => $value,
                    );
                    unset($array[$key]);
                    $array = $this->getCategoryChildsFull($item->term_id, $array, $level + 1, $dropdown);
                    $keys  = array_keys($array);
                    $i     = 0;
                }
            }

            return $array;
        }

        /**
         * taxonomy_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        function taxonomy_field($settings, $value)
        {
            $output        = '';
            $dependency    = '';
            $placeholder   = '';
            $multiple      = '';
            $size          = '';
            $value_arr     = $value;
            $terms_fields  = array();
            $hidden_fields = array();
            if (!is_array($value_arr)) {
                $value_arr = array_map('trim', explode(',', $value_arr));
            }
            if (isset($settings['settings']['hide_empty']) && $settings['settings']['hide_empty'] == true) {
                $settings['settings']['hide_empty'] = 1;
            } else {
                $settings['settings']['hide_empty'] = 0;
            }
            if (isset($settings['settings']['placeholder']) && $settings['settings']['placeholder']) {
                $placeholder                 = "data-placeholder='{$settings['settings']['placeholder']}'";
                $terms_fields['placeholder'] = "<option value=''>{$settings['settings']['placeholder']}</option>";
            }
            if (isset($settings['settings']['multiple']) && $settings['settings']['multiple'] == true) {
                $multiple                         = 'multiple="multiple"';
                $settings['settings']['multiple'] = true;
                $terms_fields['placeholder']      = '';
            } else {
                $placeholder                      = '';
                $settings['settings']['multiple'] = false;
            }
            if (isset($settings['settings']['size']) && $settings['settings']['size']) {
                $size = "size='{$settings['settings']['size']}'";
            }
            if (!empty($settings['settings']['taxonomy'])) {
                $attr  = array($multiple, $size, $dependency, $placeholder);
                $class = array(
                    "wpb-input",
                    "wpb-select",
                    "{$settings['param_name']}",
                    "{$settings['type']}_field",
                );
                $args  = array(
                    'type'         => 'post',
                    'child_of'     => 0,
                    'parent'       => '',
                    'orderby'      => 'name',
                    'order'        => 'ASC',
                    'hide_empty'   => $settings['settings']['hide_empty'],
                    'hierarchical' => 1,
                    'exclude'      => '',
                    'include'      => '',
                    'number'       => '',
                    'taxonomy'     => $settings['settings']['taxonomy'],
                    'pad_counts'   => false,
                );
                if (isset($settings['settings']['multiple']) && $settings['settings']['multiple'] == true) {
                    $class[] = "ovic-vc-chosen";
                } else {
                    $class[] = "wpb_vc_param_value";
                    $attr[]  = "name='{$settings['param_name']}'";
                }
                $categories          = get_categories($args);
                $categories_dropdown = array('');
                $this->getCategoryChildsFull(0, $categories, 0, $categories_dropdown);
                if (!empty($categories_dropdown)) {
                    foreach ($categories_dropdown as $category) {
                        if (!empty($category)) {
                            $selected = '';
                            if (in_array($category['value'], $value_arr)) {
                                $selected = ' selected="selected"';
                            }
                            $terms_fields[] = "<option value={$category['value']} {$selected}>{$category['label']}</option>";
                        }
                    }
                    if (!empty($value_arr)) {
                        foreach ($value_arr as $value) {
                            if (!empty($categories_dropdown[$value])) {
                                $data            = $categories_dropdown[$value];
                                $hidden_fields[] = "<option value={$data['value']} selected='selected'>{$data['label']}</option>";
                            }
                        }
                    }
                }
                ob_start(); ?>
                <label>
                    <?php if (isset($settings['settings']['multiple']) && $settings['settings']['multiple'] == true) : ?>
                        <select name="<?php echo esc_attr($settings['param_name']); ?>"
                                class="wpb_vc_param_value ovic-hidden-select" multiple="multiple" style="display:none;">
                            <?php echo implode($hidden_fields); ?>
                        </select>
                    <?php endif; ?>
                    <select class="<?php echo esc_attr(implode(' ', $class)); ?>" <?php echo implode(' ', $attr); ?>>
                        <?php echo implode($terms_fields); ?>
                    </select>
                </label>
                <?php
                $output = ob_get_clean();
            }

            return $output;
        }

        /**
         * multiselect_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        function multiselect_field($settings, $value)
        {
            $dependency = '';
            $value_arr  = $value;
            if (!is_array($value_arr)) {
                $value_arr = array_map('trim', explode(',', $value_arr));
            }
            $option_fields = array();
            $hidden_fields = array();
            if (!empty($settings['value'])) {
                foreach ($settings['value'] as $key => $item) {
                    $selected        = (in_array($key, $value_arr)) ? ' selected="selected"' : '';
                    $option_fields[] = "<option value='{$key}' {$selected}>{$item}</option>";
                }
                if (!empty($value_arr)) {
                    foreach ($value_arr as $value) {
                        if (!empty($settings['value'][$value])) {
                            $label           = $settings['value'][$value];
                            $hidden_fields[] = "<option value={$value} selected='selected'>{$label}</option>";
                        }
                    }
                }
            }
            $size  = !empty($settings['settings']['size']) ? 'size="'.$settings['settings']['size'].'"' : '';
            $attr  = array('multiple="multiple"', $size, $dependency);
            $class = array(
                "ovic-vc-chosen",
                "ovic_vc_taxonomy",
                "wpb-input",
                "wpb-select",
                "{$settings['param_name']}",
                "{$settings['type']}_field",
            );
            ob_start(); ?>
            <label>
                <select name="<?php echo esc_attr($settings['param_name']); ?>"
                        class="wpb_vc_param_value ovic-hidden-select" multiple="multiple" style="display:none;">
                    <?php echo implode($hidden_fields); ?>
                </select>
                <select class="<?php echo esc_attr(implode(' ', $class)); ?>" <?php echo esc_attr(implode(' ',
                    $attr)); ?>>
                    <?php echo implode($option_fields); ?>
                </select>
            </label>
            <?php
            return ob_get_clean();
        }

        /**
         * datepicker_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        function datepicker_field($settings, $value)
        {
            $dependency   = '';
            $current_date = date('m/d/Y h:i:s', time());
            $param_name   = isset($settings['param_name']) ? $settings['param_name'] : '';
            $type         = isset($settings['type ']) ? $settings['type'] : '';
            $class        = isset($settings['class']) ? $settings['class'] : '';
            $default      = isset($settings['std']) ? $settings['std'] : $current_date;
            if (!$value) {
                $value = $default;
            }
            $date_time  = explode(' ', $value);
            $date       = isset($date_time[0]) ? $date_time[0] : date('m/d/Y', time());
            $time       = isset($date_time[1]) ? $date_time[1] : date('h:i:s', time());
            $main_class = $param_name.' '.$type.' '.$class;
            ob_start();
            ?>
            <div class="vc-date-time-picker" xmlns="http://www.w3.org/1999/html">
                <label class="ovic-vc-field-date" <?php echo esc_attr($dependency); ?>>
                    <input value="<?php echo esc_attr($date); ?>" type="text" class="textfield vc-field-date"
                           style="margin-right:10px;width: auto">
                    <span><?php echo esc_html__('mm/dd/yy', 'ovic-vc'); ?></span>
                    <textarea class="ovic-vc-datepicker-options hidden">{"dateFormat":"m\/d\/yy"}</textarea>
                </label>
                <label>
                    <input value="<?php echo esc_attr($time); ?>" type="time" class="textfield vc-field-time"
                           style="width: auto;margin-left:10px;margin-right:10px;">
                    <span><?php echo esc_html__('hh:mm:ss', 'ovic-vc'); ?></span>
                </label>
                <input name="<?php echo esc_attr($param_name); ?>"
                       value="<?php echo esc_attr($value); ?>"
                       type="text"
                       class="hidden wpb_vc_param_value vc-field-date-value <?php echo esc_attr($main_class); ?>">
            </div>
            <?php
            return ob_get_clean();
        }

        function preview_modal()
        {
            global $pagenow;

            if (in_array($pagenow, array('post.php', 'post-new.php')) && class_exists('Ovic_Addon_Toolkit')) {
                ?>
                <div id="ovic-select-preview-modal" class="wp-core-ui ovic-modal-v2 ovic-select-preview-modal">
                    <div class="ovic-modal-table">
                        <div class="ovic-modal-table-cell">
                            <div class="ovic-modal-overlay"></div>
                            <div class="ovic-modal-inner ovic ovic-theme-dark">
                                <div class="ovic-header">
                                    <div class="ovic-header-inner">
                                        <div class="ovic-header-left">
                                            <h1><?php echo esc_html__('Preview Modal', 'ovic-vc'); ?>
                                                <small>by
                                                    <a href="https://kutethemes.com/" target="_blank">
                                                        Kutethemes
                                                    </a>
                                                </small>
                                            </h1>
                                        </div>
                                        <div class="ovic-header-right">
                                            <div class="ovic-buttons">
                                                <input class="button button-secondary ovic-warning-primary ovic-modal-close"
                                                       type="button"
                                                       value="<?php echo esc_html__('Close', 'ovic-vc'); ?>">
                                            </div>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                                <div class="ovic-modal-content">
                                    <div class="ovic-modal-load"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }

        /**
         * select_preview_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        function select_preview_field($settings, $value)
        {
            $options = $settings['value'];
            $default = isset($settings['default']) ? $settings['default'] : '';
            if (!empty($options)) {
                $preview_id = 'ovic_select_preview_'.uniqid();
                if (is_array($value) || !$value) {
                    $value = $default;
                }
                if (isset($options[$value]['preview'])) {
                    $preview = $options[$value]['preview'];
                } else {
                    $preview = "https://via.placeholder.com/450x200?text={$value}";
                }
                ob_start();
                ?>
                <div class="container-select_preview">
                    <label for="<?php echo esc_attr($preview_id); ?>">
                        <select id="<?php echo esc_attr($preview_id); ?>"
                                name="<?php echo esc_attr($settings['param_name']); ?>"
                                class="ovic_select_preview vc_select_image wpb_vc_param_value wpb-select <?php echo esc_attr($settings['param_name']); ?> <?php echo esc_attr($settings['type']); ?>_field">
                            <?php foreach ($options as $k => $option): ?>
                                <?php
                                $selected = ($k == $value) ? ' selected="selected"' : '';
                                if ($option['preview'] == '') {
                                    $option['preview'] = '';
                                }
                                ?>
                                <option data-preview="<?php echo esc_url($option['preview']); ?>"
                                        value='<?php echo esc_attr($k) ?>' <?php echo esc_attr($selected) ?>><?php echo esc_attr($option['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (class_exists('Ovic_Addon_Toolkit')): ?>
                            <button class="vc_general vc_ui-button vc_ui-button-action vc_ui-button-shape-rounded"
                                    value="ovic-select-preview-modal">
                                <?php echo esc_html__('modal', 'ovic-vc'); ?>
                            </button>
                        <?php endif; ?>
                    </label>
                    <div class="image-preview">
                        <img style="margin-top:10px;max-width:100%;height:auto;"
                             src="<?php echo esc_url($preview); ?>"
                             alt="<?php echo esc_attr($settings['param_name']); ?>">
                    </div>
                </div>
                <?php
            }

            return ob_get_clean();
        }

        /**
         * tabs_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        function html_markup_field($settings, $value)
        {
            return $settings['markup'];
        }

        /**
         * uniqid_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        function uniqid_field($settings, $value)
        {
            if (!$value) {
                $value = 'ovic_vc_css_id_'.uniqid();
            }
            $output = '<input type="text" class="wpb_vc_param_value wpb-textinput '.$settings['param_name'].' textfield" name="'.$settings['param_name'].'" value="'.esc_attr($value).'" />';

            return $output;
        }

        /**
         * number_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        public function number_field($settings, $value)
        {
            $dependency = '';
            $param_name = isset($settings['param_name']) ? $settings['param_name'] : '';
            $type       = isset($settings['type ']) ? $settings['type'] : '';
            $min        = isset($settings['min']) ? $settings['min'] : '';
            $max        = isset($settings['max']) ? $settings['max'] : '';
            $suffix     = isset($settings['suffix']) ? $settings['suffix'] : '';
            $class      = isset($settings['class']) ? $settings['class'] : '';
            if (!$value && $value == '' && isset($settings['value']) && $settings['value'] != '') {
                $value = $settings['value'];
            }
            $output = '<input type="number" min="'.esc_attr($min).'" max="'.esc_attr($max).'" class="wpb_vc_param_value textfield '.$param_name.' '.$type.' '.$class.'" name="'.$param_name.'" value="'.esc_attr($value).'" '.$dependency.' style="max-width:100px; margin-right: 10px;line-height:23px;height:auto;" />'.$suffix;

            return $output;
        }

        /**
         * bootstrap_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        public static function generate_field_bootstrap_v3($settings, $value)
        {
            if (!$value) {
                $data = array(
                    'settings' => array(
                        'rows_space'       => 'rows-space-30',
                        'desktop'          => 'col-bg-3',
                        'laptop'           => 'col-lg-3',
                        'ipad'             => 'col-md-4',
                        'landscape_tablet' => 'col-sm-6',
                        'portrait_tablet'  => 'col-xs-6',
                        'mobile'           => 'col-ts-6',
                    ),
                );
            } else {
                $data = array();
                parse_str(html_entity_decode($value), $data);
            }
            $setting_grid = array(
                'rows_space'       => array(
                    'type'       => 'select',
                    'id'         => 'rows_space',
                    'title'      => 'Rows space',
                    'desc'       => '',
                    'dependency' => array(),
                    'options'    => array(
                        'Default' => 'rows-space-0',
                        '5px'     => 'rows-space-5',
                        '10px'    => 'rows-space-10',
                        '15px'    => 'rows-space-15',
                        '20px'    => 'rows-space-20',
                        '25px'    => 'rows-space-25',
                        '30px'    => 'rows-space-30',
                        '35px'    => 'rows-space-35',
                        '40px'    => 'rows-space-40',
                        '45px'    => 'rows-space-45',
                        '50px'    => 'rows-space-50',
                        '60px'    => 'rows-space-60',
                        '70px'    => 'rows-space-70',
                        '80px'    => 'rows-space-80',
                        '90px'    => 'rows-space-90',
                        '100px'   => 'rows-space-100',
                    ),
                    'value'      => $data['settings']['rows_space'],
                    'default'    => 'rows-space-30',
                    'attr'       => '',
                ),
                'desktop'          => array(
                    'type'       => 'select',
                    'id'         => 'desktop',
                    'title'      => 'Items per row on Desktop',
                    'desc'       => '(Item per row on screen resolution of device >= 1500px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-bg-12',
                        '2 items' => 'col-bg-6',
                        '3 items' => 'col-bg-4',
                        '4 items' => 'col-bg-3',
                        '5 items' => 'col-bg-15',
                        '6 items' => 'col-bg-2',
                    ),
                    'value'      => $data['settings']['desktop'],
                    'default'    => 'col-bg-3',
                    'attr'       => '',
                ),
                'laptop'           => array(
                    'type'       => 'select',
                    'id'         => 'laptop',
                    'title'      => 'Items per row on Laptop',
                    'desc'       => '(Item per row on screen resolution of device >= 1200px and < 1500px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-lg-12',
                        '2 items' => 'col-lg-6',
                        '3 items' => 'col-lg-4',
                        '4 items' => 'col-lg-3',
                        '5 items' => 'col-lg-15',
                        '6 items' => 'col-lg-2',
                    ),
                    'value'      => $data['settings']['laptop'],
                    'default'    => 'col-lg-3',
                    'attr'       => '',
                ),
                'ipad'             => array(
                    'type'       => 'select',
                    'id'         => 'ipad',
                    'title'      => 'Items per row on Ipad',
                    'desc'       => '(Item per row on screen resolution of device >=992px and < 1200px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-md-12',
                        '2 items' => 'col-md-6',
                        '3 items' => 'col-md-4',
                        '4 items' => 'col-md-3',
                        '5 items' => 'col-md-15',
                        '6 items' => 'col-md-2',
                    ),
                    'value'      => $data['settings']['ipad'],
                    'default'    => 'col-md-4',
                    'attr'       => '',
                ),
                'landscape_tablet' => array(
                    'type'       => 'select',
                    'id'         => 'landscape_tablet',
                    'title'      => 'Items per row on landscape tablet',
                    'desc'       => '(Item per row on screen resolution of device >=768px and < 992px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-sm-12',
                        '2 items' => 'col-sm-6',
                        '3 items' => 'col-sm-4',
                        '4 items' => 'col-sm-3',
                        '5 items' => 'col-sm-15',
                        '6 items' => 'col-sm-2',
                    ),
                    'value'      => $data['settings']['landscape_tablet'],
                    'default'    => 'col-sm-6',
                    'attr'       => '',
                ),
                'portrait_tablet'  => array(
                    'type'       => 'select',
                    'id'         => 'portrait_tablet',
                    'title'      => 'Items per row on portrait tablet',
                    'desc'       => '(Item per row on screen resolution of device >= 480px  add < 768px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-xs-12',
                        '2 items' => 'col-xs-6',
                        '3 items' => 'col-xs-4',
                        '4 items' => 'col-xs-3',
                        '5 items' => 'col-xs-15',
                        '6 items' => 'col-xs-2',
                    ),
                    'value'      => $data['settings']['portrait_tablet'],
                    'default'    => 'col-xs-6',
                    'attr'       => '',
                ),
                'mobile'           => array(
                    'type'       => 'select',
                    'id'         => 'mobile',
                    'title'      => 'Items per row on Mobile',
                    'desc'       => '(Item per row on screen resolution of device < 480px)',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-ts-12',
                        '2 items' => 'col-ts-6',
                        '3 items' => 'col-ts-4',
                        '4 items' => 'col-ts-3',
                        '5 items' => 'col-ts-15',
                        '6 items' => 'col-ts-2',
                    ),
                    'value'      => $data['settings']['mobile'],
                    'default'    => 'col-ts-6',
                    'attr'       => '',
                ),
            );
            $setting_grid = apply_filters('ovic_grid_settings_field_v3', $setting_grid, $data);
            /* html */
            $html = '';
            $html .= '<div class="grid-field-settings">';
            $html .= '<form action="" class="form-grid-data">';
            $html .= '<div class="column vc_col-xs-12">';
            $html .= '<div class="field-wrapper">';
            $html .= self::get_field($setting_grid);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</form>';
            $html .= '<input type="hidden" name="'.esc_attr($settings['param_name']).'"
                       value="'.esc_attr($value).'"
                       class="wpb_vc_param_value '.esc_attr($settings['param_name']).'">';
            $html .= '</div>';

            return $html;
        }

        /**
         * bootstrap_field_v4
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        public static function generate_field_bootstrap_v4($settings, $value)
        {
            if (!$value) {
                $data = array(
                    'settings' => array(
                        'rows_space'       => 'rows-space-30',
                        'desktop'          => 'col-bg-3',
                        'laptop'           => 'col-xl-3',
                        'ipad'             => 'col-lg-4',
                        'landscape_tablet' => 'col-md-6',
                        'portrait_tablet'  => 'col-sm-6',
                        'mobile'           => 'col-6',
                    ),
                );
            } else {
                $data = array();
                parse_str(html_entity_decode($value), $data);
            }
            $setting_grid = array(
                'rows_space'       => array(
                    'type'       => 'select',
                    'id'         => 'rows_space',
                    'title'      => 'Rows space',
                    'desc'       => '',
                    'dependency' => array(),
                    'options'    => array(
                        'Default' => 'rows-space-0',
                        '5px'     => 'rows-space-5',
                        '10px'    => 'rows-space-10',
                        '15px'    => 'rows-space-15',
                        '20px'    => 'rows-space-20',
                        '25px'    => 'rows-space-25',
                        '30px'    => 'rows-space-30',
                        '35px'    => 'rows-space-35',
                        '40px'    => 'rows-space-40',
                        '45px'    => 'rows-space-45',
                        '50px'    => 'rows-space-50',
                        '60px'    => 'rows-space-60',
                        '70px'    => 'rows-space-70',
                        '80px'    => 'rows-space-80',
                        '90px'    => 'rows-space-90',
                        '100px'   => 'rows-space-100',
                    ),
                    'value'      => $data['settings']['rows_space'],
                    'default'    => 'rows-space-30',
                    'attr'       => '',
                ),
                'desktop'          => array(
                    'type'       => 'select',
                    'id'         => 'desktop',
                    'title'      => 'Items per row on Desktop',
                    'desc'       => '(Item per row on screen resolution of device >= 1500px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-bg-12',
                        '2 items' => 'col-bg-6',
                        '3 items' => 'col-bg-4',
                        '4 items' => 'col-bg-3',
                        '5 items' => 'col-bg-15',
                        '6 items' => 'col-bg-2',
                    ),
                    'value'      => $data['settings']['desktop'],
                    'default'    => 'col-bg-3',
                    'attr'       => '',
                ),
                'laptop'           => array(
                    'type'       => 'select',
                    'id'         => 'laptop',
                    'title'      => 'Items per row on Laptop',
                    'desc'       => '(Item per row on screen resolution of device >= 1200px and < 1500px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-xl-12',
                        '2 items' => 'col-xl-6',
                        '3 items' => 'col-xl-4',
                        '4 items' => 'col-xl-3',
                        '5 items' => 'col-xl-15',
                        '6 items' => 'col-xl-2',
                    ),
                    'value'      => $data['settings']['laptop'],
                    'default'    => 'col-xl-3',
                    'attr'       => '',
                ),
                'ipad'             => array(
                    'type'       => 'select',
                    'id'         => 'ipad',
                    'title'      => 'Items per row on Ipad',
                    'desc'       => '(Item per row on screen resolution of device >=992px and < 1200px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-lg-12',
                        '2 items' => 'col-lg-6',
                        '3 items' => 'col-lg-4',
                        '4 items' => 'col-lg-3',
                        '5 items' => 'col-lg-15',
                        '6 items' => 'col-lg-2',
                    ),
                    'value'      => $data['settings']['ipad'],
                    'default'    => 'col-lg-4',
                    'attr'       => '',
                ),
                'landscape_tablet' => array(
                    'type'       => 'select',
                    'id'         => 'landscape_tablet',
                    'title'      => 'Items per row on landscape tablet',
                    'desc'       => '(Item per row on screen resolution of device >=768px and < 992px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-md-12',
                        '2 items' => 'col-md-6',
                        '3 items' => 'col-md-4',
                        '4 items' => 'col-md-3',
                        '5 items' => 'col-md-15',
                        '6 items' => 'col-md-2',
                    ),
                    'value'      => $data['settings']['landscape_tablet'],
                    'default'    => 'col-md-6',
                    'attr'       => '',
                ),
                'portrait_tablet'  => array(
                    'type'       => 'select',
                    'id'         => 'portrait_tablet',
                    'title'      => 'Items per row on portrait tablet',
                    'desc'       => '(Item per row on screen resolution of device >= 540px  add < 768px )',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-sm-12',
                        '2 items' => 'col-sm-6',
                        '3 items' => 'col-sm-4',
                        '4 items' => 'col-sm-3',
                        '5 items' => 'col-sm-15',
                        '6 items' => 'col-sm-2',
                    ),
                    'value'      => $data['settings']['portrait_tablet'],
                    'default'    => 'col-sm-6',
                    'attr'       => '',
                ),
                'mobile'           => array(
                    'type'       => 'select',
                    'id'         => 'mobile',
                    'title'      => 'Items per row on Mobile',
                    'desc'       => '(Item per row on screen resolution of device < 540px)',
                    'dependency' => array(),
                    'options'    => array(
                        '1 item'  => 'col-12',
                        '2 items' => 'col-6',
                        '3 items' => 'col-4',
                        '4 items' => 'col-3',
                        '5 items' => 'col-15',
                        '6 items' => 'col-2',
                    ),
                    'value'      => $data['settings']['mobile'],
                    'default'    => 'col-6',
                    'attr'       => '',
                ),
            );
            $setting_grid = apply_filters('ovic_grid_settings_field_v4', $setting_grid, $data);
            /* html */
            $html = '';
            $html .= '<div class="grid-field-settings">';
            $html .= '<form action="" class="form-grid-data">';
            $html .= '<div class="column vc_col-xs-12">';
            $html .= '<div class="field-wrapper">';
            $html .= self::get_field($setting_grid);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</form>';
            $html .= '<input type="hidden" name="'.esc_attr($settings['param_name']).'"
                       value="'.esc_attr($value).'"
                       class="wpb_vc_param_value '.esc_attr($settings['param_name']).'">';
            $html .= '</div>';

            return $html;
        }

        /**
         * carousel_field
         *
         * @param $settings
         * @param $value
         *
         * @return string
         */
        public static function generate_field_carousel($settings, $value)
        {
            $default_settings = array(
                'rows'            => '1',
                'centerMode'      => 'false',
                'centerPadding'   => '30px',
                'vertical'        => 'false',
                'verticalSwiping' => 'false',
                'autoplay'        => 'false',
                'autoplaySpeed'   => '1000',
                'arrows'          => 'true',
                'dots'            => 'false',
                'infinite'        => 'false',
                'speed'           => '400',
                'slidesMargin'    => '30',
                'slidesToShow'    => '4',
            );
            if (!$value) {
                $data = array();
            } else {
                $data = array();
                parse_str(html_entity_decode($value), $data);
            }
            $data_settings      = isset($data['settings']) ? wp_parse_args($data['settings'],
                $default_settings) : $default_settings;
            $setting_slide      = array(
                'rows'            => array(
                    'type'       => 'select',
                    'id'         => 'rows',
                    'title'      => 'The number of rows',
                    'desc'       => '',
                    'dependency' => array(),
                    'options'    => array(
                        '1 Row'  => '1',
                        '2 Rows' => '2',
                        '3 Rows' => '3',
                        '4 Rows' => '4',
                        '5 Rows' => '5',
                        '6 Rows' => '6',
                    ),
                    'value'      => $data_settings['rows'],
                    'default'    => '1',
                    'attr'       => '',
                ),
                'centerMode'      => array(
                    'type'       => 'checkbox',
                    'id'         => 'centerMode',
                    'title'      => 'Enable Center Mode',
                    'desc'       => '',
                    'dependency' => array(
                        'data_dependency' => '.centerpadding',
                        'data_value'      => true,
                        'data_compare'    => 'check',
                    ),
                    'value'      => $data_settings['centerMode'],
                    'default'    => 'false',
                    'attr'       => '',
                ),
                'centerPadding'   => array(
                    'type'       => 'text',
                    'id'         => 'centerPadding',
                    'title'      => 'Center Padding',
                    'desc'       => '',
                    'dependency' => array(),
                    'value'      => $data_settings['centerPadding'],
                    'default'    => '30px',
                    'attr'       => 'style="display:none"',
                ),
                'vertical'        => array(
                    'type'       => 'checkbox',
                    'id'         => 'vertical',
                    'title'      => 'Enable Vertical',
                    'desc'       => '',
                    'dependency' => array(
                        'data_dependency' => '.verticalswiping',
                        'data_value'      => true,
                        'data_compare'    => 'check',
                    ),
                    'value'      => $data_settings['vertical'],
                    'default'    => 'false',
                    'attr'       => '',
                ),
                'verticalSwiping' => array(
                    'type'       => 'select',
                    'id'         => 'verticalSwiping',
                    'title'      => 'Enable Vertical Swiping',
                    'desc'       => '',
                    'dependency' => array(),
                    'options'    => array(
                        'True'  => 'true',
                        'False' => 'false',
                    ),
                    'value'      => $data_settings['verticalSwiping'],
                    'default'    => 'false',
                    'attr'       => 'style="display:none"',
                ),
                'autoplay'        => array(
                    'type'       => 'checkbox',
                    'id'         => 'autoplay',
                    'title'      => 'Enable Autoplay',
                    'desc'       => '',
                    'dependency' => array(
                        'data_dependency' => '.autoplayspeed',
                        'data_value'      => true,
                        'data_compare'    => 'check',
                    ),
                    'value'      => $data_settings['autoplay'],
                    'default'    => 'false',
                    'attr'       => '',
                ),
                'autoplaySpeed'   => array(
                    'type'       => 'text',
                    'id'         => 'autoplaySpeed',
                    'title'      => 'Autoplay Speed',
                    'desc'       => 'Autoplay speed in milliseconds',
                    'dependency' => array(),
                    'value'      => $data_settings['autoplaySpeed'],
                    'default'    => '1000',
                    'attr'       => 'style="display:none"',
                ),
                'arrows'          => array(
                    'type'       => 'select',
                    'id'         => 'arrows',
                    'title'      => 'Enable Navigation',
                    'desc'       => 'Show buton \'next\' and \'prev\' buttons.',
                    'dependency' => array(),
                    'options'    => array(
                        'True'  => 'true',
                        'False' => 'false',
                    ),
                    'value'      => $data_settings['arrows'],
                    'default'    => 'true',
                    'attr'       => '',
                ),
                'dots'            => array(
                    'type'       => 'select',
                    'id'         => 'dots',
                    'title'      => 'Enable Dots',
                    'desc'       => '',
                    'dependency' => array(),
                    'options'    => array(
                        'True'  => 'true',
                        'False' => 'false',
                    ),
                    'value'      => $data_settings['dots'],
                    'default'    => 'false',
                    'attr'       => '',
                ),
                'infinite'        => array(
                    'type'       => 'checkbox',
                    'id'         => 'infinite',
                    'title'      => 'Enable Loop',
                    'desc'       => '',
                    'dependency' => array(),
                    'value'      => $data_settings['infinite'],
                    'default'    => 'false',
                    'attr'       => '',
                ),
                'speed'           => array(
                    'type'       => 'text',
                    'id'         => 'speed',
                    'title'      => 'Slide Speed',
                    'desc'       => 'Slide speed in milliseconds',
                    'dependency' => array(),
                    'value'      => $data_settings['speed'],
                    'default'    => '400',
                    'attr'       => '',
                ),
                'slidesMargin'    => array(
                    'type'       => 'text',
                    'id'         => 'slidesMargin',
                    'title'      => 'Margin',
                    'desc'       => 'Distance( or space) between 2 item',
                    'dependency' => array(),
                    'value'      => $data_settings['slidesMargin'],
                    'default'    => '30',
                    'attr'       => '',
                ),
                'slidesToShow'    => array(
                    'type'       => 'text',
                    'id'         => 'slidesToShow',
                    'title'      => 'Slide To Show',
                    'dependency' => array(),
                    'value'      => $data_settings['slidesToShow'],
                    'default'    => '4',
                    'attr'       => '',
                ),
            );
            $default_responsive = array(
                'desktop' => array(
                    'breakpoint' => 1500,
                    'settings'   => array(
                        'rows'         => 1,
                        'slidesToShow' => 4,
                        'slidesMargin' => 30,
                    ),
                ),
                'laptop'  => array(
                    'breakpoint' => 1200,
                    'settings'   => array(
                        'rows'         => 1,
                        'slidesToShow' => 3,
                        'slidesMargin' => 30,
                    ),
                ),
                'tablet'  => array(
                    'breakpoint' => 992,
                    'settings'   => array(
                        'rows'         => 1,
                        'slidesToShow' => 3,
                        'slidesMargin' => 20,
                    ),
                ),
                'ipad'    => array(
                    'breakpoint' => 768,
                    'settings'   => array(
                        'rows'         => 1,
                        'slidesToShow' => 2,
                        'slidesMargin' => 15,
                    ),
                ),
                'mobile'  => array(
                    'breakpoint' => 480,
                    'settings'   => array(
                        'rows'         => 1,
                        'slidesToShow' => 2,
                        'slidesMargin' => 10,
                    ),
                ),
            );
            $responsive         = isset($data['responsive']) ? wp_parse_args(( array )$data['responsive'],
                $default_responsive) : $default_responsive;
            $setting_slide      = apply_filters('ovic_slide_settings_field', $setting_slide, $data);
            /* html */
            $html = '';
            $html .= '<div class="grid-field-settings">';
            $html .= '<form action="" class="form-grid-data">';
            $html .= '<div class="column vc_col-sm-4 vc_col-xs-12">';
            $html .= '<div class="field-wrapper">';
            $html .= self::get_field($setting_slide);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="column vc_col-sm-8 vc_col-xs-12">';
            $html .= '<div class="responsive-wrapper">';
            $html .= self::field_group(array(
                    'id'         => 'desktop',
                    'title'      => 'The items on desktop (Screen resolution of device >= 1200px and < 1500px )',
                    'desc'       => '',
                    'dependency' => array(),
                    'value'      => $responsive['desktop'],
                    'default'    => '',
                    'attr'       => '',
                )
            );
            $html .= self::field_group(array(
                    'id'         => 'laptop',
                    'title'      => 'The items on desktop (Screen resolution of device >= 992px < 1200px )',
                    'desc'       => '',
                    'dependency' => array(),
                    'value'      => $responsive['laptop'],
                    'default'    => '',
                    'attr'       => '',
                )
            );
            $html .= self::field_group(array(
                    'id'         => 'tablet',
                    'title'      => 'The items on tablet (Screen resolution of device >=768px and < 992px )',
                    'desc'       => '',
                    'dependency' => array(),
                    'value'      => $responsive['tablet'],
                    'default'    => '',
                    'attr'       => '',
                )
            );
            $html .= self::field_group(array(
                    'id'         => 'ipad',
                    'title'      => 'The items on mobile landscape(Screen resolution of device >=480px and < 768px)',
                    'desc'       => '',
                    'dependency' => array(),
                    'value'      => $responsive['ipad'],
                    'default'    => '',
                    'attr'       => '',
                )
            );
            $html .= self::field_group(array(
                    'id'         => 'mobile',
                    'title'      => 'The items on mobile (Screen resolution of device < 480px)',
                    'desc'       => '',
                    'dependency' => array(),
                    'value'      => $responsive['mobile'],
                    'default'    => '',
                    'attr'       => '',
                )
            );
            foreach ($responsive as $key => $item) {
                if (!array_key_exists($key, $default_responsive)) {
                    $html .= self::field_group(array(
                            'id'         => $key,
                            'title'      => 'New Screen',
                            'desc'       => '',
                            'dependency' => array(),
                            'value'      => $item,
                            'default'    => '',
                            'attr'       => '',
                        )
                    );
                }
            }
            $html .= '<p class="vc_param_group-add_content vc_empty-container"></p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</form>';
            $html .= '<input type="hidden" name="'.esc_attr($settings['param_name']).'"
                       value="'.esc_attr($value).'"
                       class="wpb_vc_param_value '.esc_attr($settings['param_name']).'">';
            $html .= '</div>';

            return $html;
        }

        public static function field_group($data)
        {
            $html     = '';
            $attr     = '';
            $id       = 'responsive_'.uniqid();
            $value    = isset($data['value']) ? (array)$data['value'] : $data['default'];
            $classes  = array('field-item', strtolower($data['id']));
            $vertical = isset($value['settings']['vertical']) ? 'false' : 'true';
            if (!empty($data['dependency'])) {
                $classes[] = 'dependency';
                $attr      .= ' data-dependency="'.$data['dependency']['data_dependency'].'" ';
                $attr      .= ' data-value="'.$data['dependency']['data_value'].'" ';
                $attr      .= ' data-compare="'.$data['dependency']['data_compare'].'" ';
            }
            $attr .= ' '.$data['attr'].' ';
            $html .= '<p class="'.implode(' ', $classes).'" '.$attr.'>';
            $html .= '<span class="wpb_element_label">'.$data['title'].'</span>';
            $html .= '<label data-tip="Screen Responsive"><input style="width:calc(100% - 180px);" name="responsive['.$data['id'].'][breakpoint]" type="text" class="value_input" value="'.$value['breakpoint'].'"></label>';
            $html .= '<label data-tip="Item to Show"><input style="width:60px;" name="responsive['.$data['id'].'][settings][slidesToShow]" type="text" class="value_input" value="'.$value['settings']['slidesToShow'].'"></label>';
            $html .= '<label data-tip="Margin Items"><input style="width:60px;" name="responsive['.$data['id'].'][settings][slidesMargin]" type="text" class="value_input" value="'.$value['settings']['slidesMargin'].'"></label>';
            $html .= '<label data-tip="Number Rows"><input style="width:60px;" name="responsive['.$data['id'].'][settings][rows]" type="text" class="value_input" value="'.$value['settings']['rows'].'"></label>';
            $html .= '<label for="'.$id.'" class="disable-vertical"><input id="'.$id.'" name="responsive['.$data['id'].'][settings][vertical]" type="checkbox" class="value_input" '.($vertical == 'false' ? 'checked="checked"' : '').' value="false"> Disable Vertical</label>';
            if (isset($data['desc'])) {
                $html .= '<span class="vc_description vc_clearfix">'.$data['desc'].'</span>';
            }
            if (strpos($data['id'], 'new_screen_') !== false) {
                $html .= '<span class="remove button">Remove</span>';
            }
            $html .= '</p>';

            return $html;
        }

        public static function field_text($data)
        {
            $html    = '';
            $attr    = '';
            $value   = $data['value'] != '' ? $data['value'] : $data['default'];
            $classes = array('field-item', strtolower($data['id']));
            if (!empty($data['dependency'])) {
                $classes[] = 'dependency';
                $attr      .= ' data-dependency="'.$data['dependency']['data_dependency'].'" ';
                $attr      .= ' data-value="'.$data['dependency']['data_value'].'" ';
                $attr      .= ' data-compare="'.$data['dependency']['data_compare'].'" ';
            }
            $attr .= ' '.$data['attr'].' ';
            $html .= '<p class="'.implode(' ', $classes).'" '.$attr.'>';
            $html .= '<span class="wpb_element_label">'.$data['title'].'</span>';
            $html .= '<input name="settings['.$data['id'].']" type="text" class="value_input" value="'.$value.'">';
            if (isset($data['desc'])) {
                $html .= '<span class="vc_description vc_clearfix">'.$data['desc'].'</span>';
            }
            $html .= '</p>';

            return $html;
        }

        public static function field_checkbox($data)
        {
            $html    = '';
            $attr    = '';
            $value   = isset($data['value']) ? $data['value'] : $data['default'];
            $value   = $value == 'true' ? 1 : 0;
            $classes = array('field-item', strtolower($data['id']));
            if (!empty($data['dependency'])) {
                $classes[] = 'dependency';
                $attr      .= ' data-dependency="'.$data['dependency']['data_dependency'].'" ';
                $attr      .= ' data-value="'.$data['dependency']['data_value'].'" ';
                $attr      .= ' data-compare="'.$data['dependency']['data_compare'].'" ';
            }
            $attr .= ' '.$data['attr'].' ';
            $html .= '<p class="'.implode(' ', $classes).'" '.$attr.'>';
            $html .= '<span class="wpb_element_label">'.$data['title'].'</span>';
            $html .= '<label class="checkbox">';
            $html .= '<input id="settings_'.$data['id'].'" name="settings['.$data['id'].']" '.($value == true ? 'checked="checked"' : '').' type="checkbox" class="value_input" value="true">';
            $html .= '<label for="settings_'.$data['id'].'"></label></label>';
            if (isset($data['desc'])) {
                $html .= '<span class="vc_description vc_clearfix">'.$data['desc'].'</span>';
            }
            $html .= '</p>';

            return $html;
        }

        public static function field_select($data)
        {
            $html    = '';
            $attr    = '';
            $value   = $data['value'] ? $data['value'] : $data['default'];
            $classes = array('field-item', strtolower($data['id']));
            if (!empty($data['dependency'])) {
                $classes[] = 'dependency';
                $attr      .= ' data-dependency="'.$data['dependency']['data_dependency'].'" ';
                $attr      .= ' data-value="'.$data['dependency']['data_value'].'" ';
                $attr      .= ' data-compare="'.$data['dependency']['data_compare'].'" ';
            }
            $attr .= ' '.$data['attr'].' ';
            $html .= '<p class="'.implode(' ', $classes).'" '.$attr.'>';
            $html .= '<span class="wpb_element_label">'.$data['title'].'</span>';
            $html .= '<select name="settings['.$data['id'].']" class="value_input">';
            foreach ($data['options'] as $key => $datum) {
                $html .= '<option value="'.$datum.'" '.($value === $datum ? 'selected="selected"' : '').'>'.$key.'</option>';
            }
            $html .= '</select>';
            if (isset($data['desc'])) {
                $html .= '<span class="vc_description vc_clearfix">'.$data['desc'].'</span>';
            }
            $html .= '</p>';

            return $html;
        }
    }

    Ovic_VC_Params::instance();
}