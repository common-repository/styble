<?php
namespace ShapedPlugin\Styble;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Block template dynamic css class.
 */
class Template_Dynamic_Style {

	/**
	 * This class instance.
	 *
	 * @var Template_Dynamic_Style
	 */
	private static $instance;

	/**
	 * Template dynamic css.
	 *
	 * @var string
	 */
	public $template_css = '';

	/**
	 * Template google fonts.
	 *
	 * @var array
	 */
	public $google_fonts = array();

	/**
	 * Our Blocks lists.
	 *
	 * @var array
	 */
	public $active_block_lists = array();

	/**
	 * Constructor function
	 */
	public function __construct() {

		$this->styble_block_template_dynamic_css();
	}

	/**
	 * Template dynamic css function
	 *
	 * @return void
	 */
	public function styble_block_template_dynamic_css() {
		global $_wp_current_template_content, $post;
		$style           = null;
		$font_list       = array();
		$block_lists     = array();
		$enqueue_scripts = array();

		if ( ! empty( $post ) ) {
			$blocks = $this->parse_blocks( $post->post_content );
			$blocks = $this->flatten_blocks( $blocks );
			$this->loop_blocks( $blocks, $style, $font_list, $block_lists, $enqueue_scripts );
		}

		if ( ! empty( $_wp_current_template_content ) ) {
			$blocks = $this->parse_blocks( $_wp_current_template_content );
			$blocks = $this->flatten_blocks( $blocks );
			$this->loop_blocks( $blocks, $style, $font_list, $block_lists, $enqueue_scripts );
		}

		if ( ! wp_is_block_theme() && ! empty( self::styble_get_widget_data_for_all_sidebars() ) ) {
			foreach ( self::styble_get_widget_data_for_all_sidebars() as $widget ) {
				$widgets = self::object_to_array( $widget );
				foreach ( $widgets as $block ) {
					if ( isset( $block['content'] ) ) {
						$blocks = $this->parse_blocks( $block['content'] );
						$blocks = $this->flatten_blocks( $blocks );
						$this->loop_blocks( $blocks, $style, $font_list, $block_lists, $enqueue_scripts );
					}
				}
			}
		}

		if ( ! empty( $font_list ) && is_array( $font_list ) ) {
			$google_fonts = array();

			foreach ( $font_list as $fonts ) {
				if ( is_array( $fonts ) ) {
					foreach ( $fonts as $font ) {
						$google_fonts[] = $font;
					}
				}
			}

			$google_fonts = array_unique( $google_fonts );

			$this->google_fonts = $google_fonts;
		}

		$this->active_block_lists = $block_lists;

		if ( ! empty( $style ) ) {
			$this->template_css = $style;
		}
	}

	/**
	 * Object to array.
	 *
	 * @param object $data meta data.
	 * @return array
	 */
	public static function object_to_array( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			$result = array();
			foreach ( $data as $key => $value ) {
				$result[ $key ] = ( is_array( $value ) || is_object( $value ) ) ? self::object_to_array( $value ) : $value;
			}
			return $result;
		}
		return $data;
	}

	/**
	 * Parse Gutenberg Block.
	 *
	 * @param string $content the content string.
	 */
	public function parse_blocks( $content ) {
		global $wp_version;

		return ( version_compare( $wp_version, '5', '>=' ) ) ? parse_blocks( $content ) : parse_blocks( $content );
	}

	/**
	 * Get Pattern Content.
	 *
	 * @param array $attributes Attributes.
	 */
	public function get_pattern_content( $attributes ) {
		$content = '';

		if ( isset( $attributes['slug'] ) ) {
			$block   = \WP_Block_Patterns_Registry::get_instance()->get_registered( $attributes['slug'] );
			$content = isset( $block ) ? $block['content'] : $content;
		}

		return $content;
	}

	/**
	 * Callback function Flatten Blocks for lower version.
	 *
	 * @param blocks $blocks .
	 *
	 * @return blocks.
	 */
	public function flatten_blocks( $blocks ) {

		if ( self::styble_block_compatible() ) {
			// use Gutenberg or WP 5.9 & above version.
			return _flatten_blocks( $blocks );
		}

		/**
		 * Below is the native functionality of "_flatten_blocks".
		 * Just to prevent fatal error if somehow user able to install this plugin on WP below 5.9.
		 */
		$all_blocks = array();
		$queue      = array();
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}

		$counted_queue = count( $queue );

		while ( $counted_queue > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}
		}

		return $all_blocks;
	}
	/**
	 * Gutenberg version check.
	 *
	 * @return boolean
	 */
	public static function styble_block_compatible() {
		return defined( 'GUTENBERG_VERSION' ) || version_compare( $GLOBALS['wp_version'], '5.9', '>=' );
	}

	/**
	 * Get Template Part Content.
	 *
	 * @param array $attributes Attributes.
	 */
	public function get_template_part_content( $attributes ) {
		$template_part_id = null;
		$area             = WP_TEMPLATE_PART_AREA_UNCATEGORIZED;

		return self::styble_template_part_content( $attributes, $template_part_id, $area );
	}

	/**
	 * Gutenberg block Template Part Content.
	 *
	 * @param array  $attributes Attributes.
	 * @param string $template_part_id Template Part ID.
	 * @param string $area Area.
	 *
	 * @return string
	 */
	public static function styble_template_part_content( $attributes, &$template_part_id, &$area ) {
		$content = '';

		if (
		isset( $attributes['slug'] ) &&
		isset( $attributes['theme'] ) &&
		wp_get_theme()->get_stylesheet() === $attributes['theme']
		) {
			$template_part_id    = $attributes['theme'] . '//' . $attributes['slug'];
			$template_part_query = new \WP_Query(
				array(
					'post_type'      => 'wp_template_part',
					'post_status'    => 'publish',
					'post_name__in'  => array( $attributes['slug'] ),
					'tax_query'      => array(
						array(
							'taxonomy' => 'wp_theme',
							'field'    => 'slug',
							'terms'    => $attributes['theme'],
						),
					),
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				)
			);
			$template_part_post  = $template_part_query->have_posts() ? $template_part_query->next_post() : null;
			if ( $template_part_post ) {
				// A published post might already exist if this template part was customized elsewhere
				// or if it's part of a customized template.
				$content    = $template_part_post->post_content;
				$area_terms = get_the_terms( $template_part_post, 'wp_template_part_area' );
				if ( ! is_wp_error( $area_terms ) && false !== $area_terms ) {
					$area = $area_terms[0]->name;
				}
				/**
				 * Fires when a block template part is loaded from a template post stored in the database.
				 *
				 * @param string  $template_part_id   The requested template part namespaced to the theme.
				 * @param array   $attributes         The block attributes.
				 * @param WP_Post $template_part_post The template part post object.
				 * @param string  $content            The template part content.
				 */
			} else {
				// Else, if the template part was provided by the active theme,
				// render the corresponding file content.
				$parent_theme_folders        = get_block_theme_folders( get_template() );
				$child_theme_folders         = get_block_theme_folders( get_stylesheet() );
				$child_theme_part_file_path  = get_theme_file_path( '/' . $child_theme_folders['wp_template_part'] . '/' . $attributes['slug'] . '.html' );
				$parent_theme_part_file_path = get_theme_file_path( '/' . $parent_theme_folders['wp_template_part'] . '/' . $attributes['slug'] . '.html' );
				$template_part_file_path     = 0 === validate_file( $attributes['slug'] ) && file_exists( $child_theme_part_file_path ) ? $child_theme_part_file_path : $parent_theme_part_file_path;

				if ( is_child_theme() ) {
					// need to find if file exist on child themes.
					$child_path = get_stylesheet_directory() . '/' . $child_theme_folders['wp_template_part'] . '/' . $attributes['slug'] . '.html';
				}

				if ( 0 === validate_file( $attributes['slug'] ) && file_exists( $template_part_file_path ) ) {
					$content = wp_remote_get( $template_part_file_path );

					if ( version_compare( $GLOBALS['wp_version'], '6.4.0', '<' ) ) {
						$content = is_string( $content ) && '' !== $content
						? _inject_theme_attribute_in_block_template_content( $content )
						: '';
					} else {
						$content = is_string( $content ) && '' !== $content
						? traverse_and_serialize_blocks( parse_blocks( $content ), '_inject_theme_attribute_in_template_part_block' )
						: '';
					}
				}
			}
		}

		return $content;
	}

	/**
	 * Get Block Style Instance.
	 *
	 * @param string $name Block Name.
	 * @param array  $attrs Block Attribute.
	 *
	 * @return array
	 */
	public function get_block_style_instance( $name, $attrs ) {
		$instance = null;

		switch ( $name ) {
			case 'styble/buttons':
				$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
				$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/button':
				$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
				$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/accordions':
				$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
				break;
			case 'styble/accordion':
				$instance['style'] = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
				break;
			case 'styble/infobox':
				$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
				$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/tabs':
				$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
				$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/iconlistchild':
					$instance['style'] = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
				break;
			case 'styble/iconlist':
					$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
					$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/container':
					$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
					$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/column':
					$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
					$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/video':
					$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
					$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			case 'styble/gallery':
					$instance['style']                  = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
					$instance['fontList']               = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
					$instance['gallery_script_enqueue'] = isset( $attrs['scriptEnqueue'] ) ? $attrs['scriptEnqueue'] : '';
				break;
			case 'styble/post-grid':
					$instance['style']    = isset( $attrs['dynamicCss'] ) ? json_decode( $attrs['dynamicCss'] ) : '';
					$instance['fontList'] = isset( $attrs['fontList'] ) ? json_decode( $attrs['fontList'] ) : '';
				break;
			default:
				$instance = null;
		}

		return $instance;
	}

	/**
	 * Generate Block Style.
	 *
	 * @param array  $block Detail of block.
	 * @param string $style Style string.
	 * @param string $font_list Font list.
	 * @param string $block_lists Blocks list.
	 * @param array  $enqueue_scripts enqueue script list.
	 */
	public function generate_block_style( $block, &$style, &$font_list, &$block_lists, &$enqueue_scripts ) {
		$instance = $this->get_block_style_instance( $block['blockName'], $block['attrs'] );

		$our_blocks_name = array( 'styble/accordions', 'styble/tabs', 'styble/buttons', 'styble/infobox', 'styble/iconlist', 'styble/iconlistchild', 'styble/container', 'styble/column', 'styble/video', 'styble/gallery', 'styble/post-grid' );

		$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

		if ( ! is_null( $instance ) ) {
			$style      .= isset( $instance['style'] ) ? $instance['style'] : '';
			$font_list[] = isset( $instance['fontList'] ) ? $instance['fontList'] : '';
			if ( isset( $instance['gallery_script_enqueue'] ) && is_array( $instance['gallery_script_enqueue'] ) ) {
				$enqueue_scripts[] = $instance['gallery_script_enqueue'];
			}
			if ( in_array( $block_name, $our_blocks_name, true ) ) {
				$block_lists[] = $block_name;
			}
		}
	}

	/**
	 * Loop Block.
	 *
	 * @param array  $blocks Array of blocks.
	 * @param string $style Style string.
	 * @param string $font_list Font list.
	 * @param string $block_lists Blocks list.
	 */
	public function loop_blocks( $blocks, &$style, &$font_list, &$block_lists, &$enqueue_scripts ) {
		foreach ( $blocks as $block ) {

			$this->generate_block_style( $block, $style, $font_list, $block_lists, $enqueue_scripts );

			if ( isset( $block['attrs']['ref'] ) && ! empty( $block['attrs']['ref'] ) && 'core/block' === $block['blockName'] ) {
				$reusable_block = get_post( $block['attrs']['ref'] );
				$reusable_block = parse_blocks( isset( $reusable_block->post_content ) ? $reusable_block->post_content : '' );
				$reusable_block = $this->flatten_blocks( $reusable_block );
				$this->loop_blocks( $reusable_block, $style, $font_list, $block_lists, $enqueue_scripts );
			}

			if ( 'core/template-part' === $block['blockName'] ) {
				$parts = $this->get_template_part_content( $block['attrs'] );
				$parts = parse_blocks( $parts );
				$parts = $this->flatten_blocks( $parts );
				$this->loop_blocks( $parts, $style, $font_list, $block_lists, $enqueue_scripts );
			}

			if ( 'core/pattern' === $block['blockName'] ) {
				$parts = $this->get_pattern_content( $block['attrs'] );
				$parts = parse_blocks( $parts );
				$parts = $this->flatten_blocks( $parts );
				$this->loop_blocks( $parts, $style, $font_list, $block_lists, $enqueue_scripts );
			}
		}
	}

	/**
	 * Get all sidebars name and data.
	 *
	 * @return array
	 */
	public static function styble_get_widget_data_for_all_sidebars() {
		global $wp_registered_sidebars;

		$output = array();
		foreach ( $wp_registered_sidebars as $sidebar ) {
			if ( empty( $sidebar['name'] ) ) {
				continue;
			}
			$sidebar_name            = isset( $sidebar['name'] ) ? $sidebar['name'] : '';
			$output[ $sidebar_name ] = self::styble_get_widget_data( $sidebar_name );
		}
		return $output;
	}

	/**
	 * Get widget block data.
	 *
	 * @param string $sidebar_name Sidebar name.
	 * @return array
	 */
	public static function styble_get_widget_data( $sidebar_name ) {
		global $wp_registered_sidebars, $wp_registered_widgets;

		// Holds the final data to return.
		$output = array();

		// Loop over all of the registered sidebars looking for the one with the same name as $sidebar_name.
		$sidebar_id = false;
		foreach ( $wp_registered_sidebars as $sidebar ) {
			if ( $sidebar['name'] === $sidebar_name ) {
				// We now have the Sidebar ID, we can stop our loop and continue.
				$sidebar_id = isset( $sidebar['id'] ) ? $sidebar['id'] : '';
				break;
			}
		}

		if ( ! $sidebar_id ) {
			// There is no sidebar registered with the name provided.
			return $output;
		}

		// A nested array in the format $sidebar_id => array( 'widget_id-1', 'widget_id-2' ... ).
		$sidebars_widgets = wp_get_sidebars_widgets();
		$widget_ids       = $sidebars_widgets[ $sidebar_id ];

		if ( ! $widget_ids ) {
			// Without proper widget_ids we can't continue.
			return array();
		}

		// Loop over each widget_id so we can fetch the data out of the wp_options table.
		foreach ( $widget_ids as $id ) {
			// The name of the option in the database is the name of the widget class.
			$option_name = isset( $wp_registered_widgets[ $id ]['callback'][0]->option_name ) ? $wp_registered_widgets[ $id ]['callback'][0]->option_name : '';

			// Widget data is stored as an associative array. To get the right data we need to get the right key which is stored in $wp_registered_widgets.
			$key = isset( $wp_registered_widgets[ $id ]['params'][0]['number'] ) ? $wp_registered_widgets[ $id ]['params'][0]['number'] : '';

			$widget_data = get_option( $option_name );

			// Add the widget data on to the end of the output array.
			if ( isset( $widget_data[ $key ] ) ) {
				$output[] = (object) $widget_data[ $key ];
			}
		}

		return $output;
	}

	/**
	 * Get size information for all currently-registered image sizes.
	 *
	 * @global $_wp_additional_image_sizes
	 * @uses   get_intermediate_image_sizes()
	 * @link   https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
	 * @return array $sizes Data for all currently-registered image sizes.
	 */
	public static function get_image_sizes() {

		global $_wp_additional_image_sizes;

		$sizes       = get_intermediate_image_sizes();
		$image_sizes = array();

		$image_sizes[] = array(
			'value' => 'full',
			'label' => esc_html__( 'Full', 'styble' ),
		);

		foreach ( $sizes as $size ) {
			if ( in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
				$image_sizes[] = array(
					'value' => $size,
					'label' => ucwords( trim( str_replace( array( '-', '_' ), array( ' ', ' ' ), $size ) ) ),
				);
			} else {
				$image_sizes[] = array(
					'value' => $size,
					'label' => sprintf(
						'%1$s (%2$sx%3$s)',
						ucwords( trim( str_replace( array( '-', '_' ), array( ' ', ' ' ), $size ) ) ),
						$_wp_additional_image_sizes[ $size ]['width'],
						$_wp_additional_image_sizes[ $size ]['height']
					),
				);
			}
		}
		return $image_sizes;
	}
}
