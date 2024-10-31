<?php
/**
 * Ovic VC Shortcode setup
 *
 * @author   KHANH
 * @category API
 * @package  Ovic_VC_Shortcode
 * @since    1.0.3
 */
if ( ! class_exists( 'Ovic_VC_Shortcode' ) ) {
	class Ovic_VC_Shortcode {
		/**
		 * Shortcode name.
		 *
		 * @var  string
		 */
		public $shortcode      = '';
		public $enqueue        = '';
		public $path_templates = '';
		/**
		 * Meta key.
		 *
		 * @var  string
		 */
		protected $css_key = '_Ovic_VC_Shortcode_Custom_Css';

		public function __construct()
		{
			$this->shortcode_actions();

			// folder template
			$this->path_templates = apply_filters( 'ovic_include_templates_shortcode', 'vc_templates',
				$this->shortcode );

			// add scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 999 );

			// add shortcode
			if ( ! empty( $this->shortcode ) ) {
				$this->enqueue = "shortcode_enqueue_{$this->shortcode}";
				add_shortcode( "{$this->shortcode}", array( $this, 'output_html' ) );
			}

			if ( class_exists( 'Vc_Manager' ) ) {
				add_action( 'save_post', array( $this, 'update_post' ) );
			}
		}

		public function shortcode_actions()
		{
		}

		function template_directory( $extension )
		{
			$directory_shortcode = '';
			if ( is_file( get_template_directory() . "/{$this->path_templates}/{$this->shortcode}.{$extension}" ) ) {
				$min = '';
				if ( is_file( get_template_directory() . "/{$this->path_templates}/{$this->shortcode}.min.{$extension}" ) ) {
					$min = '.min';
				}
				$directory_shortcode = get_theme_file_uri( "/{$this->path_templates}/{$this->shortcode}{$min}.{$extension}" );
			}

			return $directory_shortcode;
		}

		function enqueue()
		{
			$directory_css = $this->template_directory( 'css' );
			$directory_js  = $this->template_directory( 'js' );
			if ( $directory_css != '' ) {
				wp_register_style( $this->enqueue, esc_url( $directory_css ), array(), OVIC_VC_VERSION );
			}
			if ( $directory_js != '' ) {
				wp_register_script( $this->enqueue, esc_url( $directory_js ), array(), OVIC_VC_VERSION, true );
			}
		}

		function locate_template( $template_name, $template_path = '', $default_path = '' )
		{
			if ( ! $template_path ) {
				$template_path = $this->path_templates . '/layout/';
			}
			if ( ! $default_path ) {
				$default_path = OVIC_VC_PLUGIN_DIR . "templates/shortcode/layout/";
			}
			// Look within passed path within the theme - this is priority.
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				)
			);
			// Get default template/.
			if ( ! $template ) {
				$template = $default_path . $template_name;
			}

			// Return what we found.
			return apply_filters( 'ovic_shortcode_locate_template', $template, $template_name, $template_path );
		}

		function get_template( $template_name, $args = array(), $template_path = '', $default_path = '' )
		{
			if ( ! empty( $args ) && is_array( $args ) ) {
				extract( $args ); // @codingStandardsIgnoreLine
			}
			$located = $this->locate_template( $template_name, $template_path, $default_path );
			if ( ! file_exists( $located ) ) {
				echo '<code>' . esc_html__( 'File does not exist.', 'ovic-vc' ) . '</code>';
			} else {
				// Allow 3rd party plugin filter template file from their plugin.
				$located = apply_filters( 'ovic_shortcode_get_template', $located, $template_name, $args,
					$template_path, $default_path );
				do_action( 'ovic_shortcodebefore_template_part', $template_name, $template_path, $located, $args );
				require $located;
				do_action( 'ovic_shortcode_after_template_part', $template_name, $template_path, $located, $args );
			}
		}

		/**
		 * Replace and save custom css to post meta.
		 *
		 * @param  int  $post_id
		 *
		 * @return  void
		 */
		public function update_post( $post_id )
		{
			if ( ! wp_is_post_revision( $post_id ) ) {
				// Set and replace content.
				$post = $this->replace_post( $post_id );
				if ( $post ) {
					// Generate custom CSS.
					$css = $this->buildShortcodesCustomCss( $post->post_content );
					// Update post to post meta.
					$this->save_post( $post );
					// Update save CSS to post meta.
					$this->save_css_postmeta( $post_id, $css );
					do_action( 'ovic_vc_save_post', $post_id );
				} else {
					$this->save_css_postmeta( $post_id, '' );
				}
			}
		}

		public function save_post( $post )
		{
			// Update post content.
			global $wpdb;
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_content' => $post->post_content,    // string
				),
				array(
					'ID' => $post->ID,
				),
				array( '%s' ),
				array( '%d' )
			);
			// Update post cache.
			wp_cache_replace( $post->ID, $post, 'posts' );
		}

		/**
		 * Replace shortcode used in a post with real content.
		 *
		 * @param  int  $post_id  Post ID.
		 *
		 * @return  WP_Post object or null.
		 */
		public function replace_post( $post_id )
		{
			// Get post.
			$post = get_post( $post_id );
			if ( $post ) {
				$post->post_content = preg_replace_callback(
					'/(ovic_vc_custom_id)="[^"]+"/',
					array( $this, 'ovic_shortcode_replace_post_callback' ),
					$post->post_content
				);
			}

			return $post;
		}

		function ovic_shortcode_replace_post_callback( $matches )
		{
			// Generate a random string to use as element ID.
			$id = 'ovic_vc_custom_' . uniqid();

			return $matches[1] . '="' . $id . '"';
		}

		/**
		 * Parse shortcode custom css string.
		 *
		 * @param  string  $content
		 *
		 * @return  string $css
		 */
		public function buildShortcodesCustomCss( $content )
		{
			$css = '';
			if ( ! class_exists( 'WPBMap' ) ) {
				return $css;
			}
			WPBMap::addAllMappedShortcodes();
			if ( preg_match_all( '/' . get_shortcode_regex() . '/', $content, $shortcodes ) ) {
				foreach ( $shortcodes[2] as $index => $tag ) {
					$atts  = shortcode_parse_atts( trim( $shortcodes[3][ $index ] ) );
					$class = 'Shortcode_' . implode( '_', array_map( 'ucfirst', explode( '-', $tag ) ) );
					if ( class_exists( $class ) && method_exists( $class, 'add_css_generate' ) ) {
						$css .= $class::add_css_generate( $atts );
					}
					$css .= self::add_css_editor( $atts, $tag );
				}
				foreach ( $shortcodes[5] as $shortcode_content ) {
					$css .= self::buildShortcodesCustomCss( $shortcode_content );
				}
			}

			return $css;
		}

		/**
		 * Update extra post meta.
		 *
		 * @param  int  $post_id  Post ID.
		 * @param  string  $css  Custom CSS.
		 *
		 * @return  void
		 */
		public function save_css_postmeta( $post_id, $css )
		{
			if ( $post_id && $this->css_key ) {
				if ( ! $css ) {
					delete_post_meta( $post_id, $this->css_key );
				} else {
					update_post_meta( $post_id, $this->css_key, preg_replace( '/[\t\r\n]/', '', $css ) );
				}
			}
		}

		/**
		 * Generate custom CSS.
		 *
		 * @param  array  $atts  Shortcode parameters.
		 *
		 * @return  string
		 */
		static public function add_css_generate( $atts )
		{
			return '';
		}

		public function generate_style_font( $container_data )
		{
			$style_font_data     = array();
			$styles              = array();
			$font_container_data = explode( '|', $container_data );
			foreach ( $font_container_data as $value ) {
				if ( $value != '' ) {
					$data_style                        = explode( ':', $value );
					$style_font_data[ $data_style[0] ] = $data_style[1];
				}
			}
			foreach ( $style_font_data as $key => $value ) {
				if ( 'tag' !== $key && strlen( $value ) ) {
					if ( preg_match( '/description/', $key ) ) {
						continue;
					}
					if ( 'font_size' === $key || 'line_height' === $key ) {
						$value = preg_replace( '/\s+/', '', $value );
					}
					if ( 'font_size' === $key ) {
						$pattern = '/^(\d*(?:\.\d+)?)\s*(px|\%|in|cm|mm|em|rem|ex|pt|pc|vw|vh|vmin|vmax)?$/';
						// allowed metrics: http://www.w3schools.com/cssref/css_units.asp
						$regexr = preg_match( $pattern, $value, $matches );
						$value  = isset( $matches[1] ) ? (float) $matches[1] : (float) $value;
						$unit   = isset( $matches[2] ) ? $matches[2] : 'px';
						$value  = $value . $unit;
					}
					if ( strlen( $value ) > 0 ) {
						$styles[] = str_replace( '_', '-', $key ) . ': ' . urldecode( $value );
					}
				}
			}

			return ! empty( $styles ) ? implode( ' !important;', $styles ) . ' !important;' : '';
		}

		public function get_google_font_data( $atts, $key = 'google_fonts' )
		{
			extract( $atts );
			$google_fonts_data = '';

			if ( class_exists( 'Vc_Google_Fonts' ) ) {
				$google_fonts_field          = WPBMap::getParam( "ovic_vc_{$this->shortcode}", $key );
				$google_fonts_obj            = new Vc_Google_Fonts();
				$google_fonts_field_settings = isset( $google_fonts_field['settings'], $google_fonts_field['settings']['fields'] ) ? $google_fonts_field['settings']['fields'] : array();
				$google_fonts_data           = strlen( $atts[ $key ] ) > 0 ? $google_fonts_obj->_vc_google_fonts_parse_attributes( $google_fonts_field_settings,
					$atts[ $key ] ) : '';
			}

			return $google_fonts_data;
		}

		public function add_css_editor( $atts, $tag )
		{
			$css          = '';
			$main_css     = '';
			$inner_css    = '';
			$target_class = '';
			if ( $tag == 'vc_column' || $tag == 'vc_column_inner' ) {
				$inner_css = ' > .vc_column-inner ';
			}
			if ( ! class_exists( 'Ovic_VC_Init' ) ) {
				return $css;
			}
			$editor_names = Ovic_VC_Init::responsive_data();
			/* generate main css */
			if ( isset( $atts['css'] ) && $atts['css'] != '' ) {
				$main_css = str_replace( "{", "{$inner_css}{", $atts['css'] );
			}
			if ( ! empty( $editor_names ) && isset( $atts['ovic_vc_custom_id'] ) ) {
				arsort( $editor_names );
				$unit_css     = [];
				$shortcode_id = '.' . $atts['ovic_vc_custom_id'];
				foreach ( $editor_names as $key => $data ) {
					$generate_css = '';
					if ( $key == 'desktop' ) {
						$main_css   = '';
						$param_name = 'css';
					} else {
						$param_name = "css_{$key}";
					}
					if ( isset( $atts["width_unit_{$key}"] ) ) {
						$unit_css[$key] = $atts["width_unit_{$key}"] != 'none' ? $atts["width_unit_{$key}"] : '';
					} else {
						$unit_css[$key] = '%';
					}
					/* TARGET CHILD */
					if ( ! empty( $atts["target_main_{$key}"] ) ) {
						$target_main  = wp_specialchars_decode( $atts["target_main_{$key}"] );
						$inner_css    .= " {$target_main} ";
						$target_class .= " {$target_main} ";
					}
					/* SCREEN CSS */
					if ( isset( $atts[ $param_name ] ) && $atts[ $param_name ] != '' ) {
						$generate_css .= str_replace( "{", "{$inner_css}{", $atts[ $param_name ] );
					}
					/* FONT CSS */
					if ( isset( $atts["responsive_font_{$key}"] ) && $this->generate_style_font( $atts["responsive_font_{$key}"] ) != '' ) {
						$generate_css .= "{$shortcode_id}{$inner_css}{{$this->generate_style_font( $atts["responsive_font_{$key}"] )}}";
					}
					/* STYLE WIDTH CSS */
					if ( isset( $atts["width_rows_{$key}"] ) && $atts["width_rows_{$key}"] != '' ) {
						$generate_css .= "{$shortcode_id}{$target_class}{width: {$atts["width_rows_{$key}"]}{$unit_css[$key]} !important}";
					}
					/* DISABLE BACKGROUND CSS */
					if ( isset( $atts["disable_bg_{$key}"] ) && $atts["disable_bg_{$key}"] == 'yes' ) {
						$generate_css .= "{$shortcode_id}{$inner_css}{background-image: none !important;}";
					}
					/* DISABLE ELEMENT CSS */
					if ( isset( $atts["disable_element_{$key}"] ) && $atts["disable_element_{$key}"] == 'yes' ) {
						$generate_css .= "{$shortcode_id}{$inner_css}{display: none !important;}";
					}
					/* LETTER SPACING CSS */
					if ( isset( $atts["letter_spacing_{$key}"] ) && $atts["letter_spacing_{$key}"] != '' ) {
						$generate_css .= "{$shortcode_id}{$inner_css}{letter-spacing: {$atts["letter_spacing_{$key}"]} !important;}";
					}
					/* GOOGLE FONT */
					$google_fonts_data = array();
					if ( isset( $atts["google_fonts_{$key}"] ) ) {
						$google_fonts_data = Ovic_VC_Init::get_google_font_data( $tag, $atts, "google_fonts_{$key}" );
					}
					if ( ( ! isset( $atts["use_theme_fonts_{$key}"] ) || 'yes' !== $atts["use_theme_fonts_{$key}"] ) && ! empty( $google_fonts_data ) && isset( $google_fonts_data['values'], $google_fonts_data['values']['font_family'], $google_fonts_data['values']['font_style'] ) ) {
						$google_fonts_family = explode( ':', $google_fonts_data['values']['font_family'] );
						$styles              = array();
						if ( ! empty( $google_fonts_family[0] ) ) {
							$styles[] = 'font-family:' . $google_fonts_family[0];
						}
						$google_fonts_styles = explode( ':', $google_fonts_data['values']['font_style'] );
						if ( ! empty( $google_fonts_styles[1] ) ) {
							$styles[] = 'font-weight:' . $google_fonts_styles[1];
						}
						if ( ! empty( $google_fonts_styles[2] ) ) {
							$styles[] = 'font-style:' . $google_fonts_styles[2];
						}
						if ( ! empty( $styles ) ) {
							$generate_css .= "{$shortcode_id}{$inner_css}{" . implode( ';', $styles ) . "}";
						}
					}
					/* TARGET CHILD */
					if ( ! empty( $atts["target_child_{$key}"] ) ) {
						$target_child = wp_specialchars_decode( $atts["target_child_{$key}"] );
						$target_class = " {$target_child} ";
					}
					/* CUSTOM CSS */
					if ( ! empty( $atts["custom_css_{$key}"] ) ) {
						$custom_css   = trim( strip_tags( $atts["custom_css_{$key}"] ) );
						$generate_css .= "{$shortcode_id}{$target_class}{{$custom_css}}";
					}
					/* GERENERATE MEDIA */
					if ( $generate_css != '' ) {
						if ( $data['screen'] < 999999 ) {
							$css .= "@media ({$data['media']}: {$data['screen']}px){{$generate_css}}";
						} else {
							$css .= $generate_css;
						}
					}
				}
			}
			$css .= $main_css;

			return $css;
		}

		public function output_html( $atts, $content = null )
		{
			return '';
		}

		/**
		 * @param $css_animation
		 *
		 * @return string
		 */
		public function getCSSAnimation( $css_animation )
		{
			$output = '';
			if ( class_exists( 'Vc_Manager' ) && '' !== $css_animation && 'none' !== $css_animation ) {
				wp_enqueue_script( 'vc_waypoints' );
				wp_enqueue_style( 'vc_animate-css' );
				$output = ' wpb_animate_when_almost_visible wpb_' . $css_animation . ' ' . $css_animation;
			}

			return $output;
		}

		/* do_action( 'vc_enqueue_font_icon_element', $font ); // hook to custom do enqueue style */
		public function constructIcon( $section )
		{
			$class = 'vc_tta-icon';

			if ( function_exists( 'vc_icon_element_fonts_enqueue' ) ) {
				vc_icon_element_fonts_enqueue( $section['i_type'] );
				if ( isset( $section[ 'i_icon_' . $section['i_type'] ] ) ) {
					$class .= ' ' . $section[ 'i_icon_' . $section['i_type'] ];
				} else {
					$class .= ' fa fa-adjust';
				}
			}

			return '<i class="' . $class . '"></i>';
		}

		public static function convertAttributesToNewProgressBar( $atts )
		{
			if ( isset( $atts['values'] ) && strlen( $atts['values'] ) > 0 && function_exists( 'vc_param_group_parse_atts' ) ) {
				$values = vc_param_group_parse_atts( $atts['values'] );
				if ( ! is_array( $values ) ) {
					$temp        = explode( ',', $atts['values'] );
					$paramValues = array();
					foreach ( $temp as $value ) {
						$data               = explode( '|', $value );
						$colorIndex         = 2;
						$newLine            = array();
						$newLine['percent'] = isset( $data[0] ) ? $data[0] : 0;
						$newLine['title']   = isset( $data[1] ) ? $data[1] : '';
						if ( isset( $data[1] ) && preg_match( '/^\d{1,3}\%$/', $data[1] ) ) {
							$colorIndex         += 1;
							$newLine['percent'] = (float) str_replace( '%', '', $data[1] );
							$newLine['title']   = isset( $data[2] ) ? $data[2] : '';
						}
						if ( isset( $data[ $colorIndex ] ) ) {
							$newLine['customcolor'] = $data[ $colorIndex ];
						}
						$paramValues[] = $newLine;
					}
					$atts['values'] = urlencode( json_encode( $paramValues ) );
				}
			}

			return $atts;
		}

		function get_all_attributes( $tag, $text )
		{
			preg_match_all( '/' . get_shortcode_regex() . '/s', $text, $matches );
			$out               = array();
			$shortcode_content = array();
			if ( isset( $matches[5] ) ) {
				$shortcode_content = $matches[5];
			}
			if ( isset( $matches[2] ) ) {
				$i = 0;
				foreach ( (array) $matches[2] as $key => $value ) {
					if ( $tag === $value ) {
						$out[ $i ]            = shortcode_parse_atts( $matches[3][ $key ] );
						$out[ $i ]['content'] = $matches[5][ $key ];
					}
					$i ++;
				}
			}

			return $out;
		}
	}

	new Ovic_VC_Shortcode();
}