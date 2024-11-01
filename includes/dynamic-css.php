<?php
namespace ShapedPlugin\Styble;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Dynamic_Css class.
 */
class Dynamic_Css {

	/**
	 * This class instance.
	 *
	 * @var Dynamic_Css
	 */
	private static $instance;

	/**
	 * Main Dynamic_Css Instance.
	 *
	 * Insures that only one instance of Dynamic_Css exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @static
	 * @return object|Dynamic_Css The one true NextBlocks
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Dynamic_Css ) ) {
			self::$instance = new Dynamic_Css();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Enqueue page dynamic css.
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}
		add_action( 'enqueue_block_assets', array( $this, 'styble_single_page_dynamic_css' ) );
	}

	/**
	 * Single page dynamic css enqueue.
	 *
	 * @return void
	 */
	public function styble_single_page_dynamic_css() {
		$styble_dynamic_style = '';
		$google_fonts         = array();

		$template_dynamic_css = new Template_Dynamic_Style();

		$google_fonts = array_merge( $google_fonts, $template_dynamic_css->google_fonts );

		$google_fonts = array_unique( $google_fonts );

		$google_fonts = array_filter(
			$google_fonts,
			function( $filter ) {
				$has_family = explode( ':', $filter );
				if ( $has_family[0] ) {
					return ':' !== $filter && '' !== $filter;
				}

			}
		);

		if ( $google_fonts ) {
			wp_enqueue_style( 'styble-google-fonts', esc_url( add_query_arg( 'family', urlencode( implode( '|', $google_fonts ) ), '//fonts.googleapis.com/css' ) ), array(), STYBLE_VERSION, false );
		}

		if ( ! empty( $template_dynamic_css->template_css ) ) {
			$styble_dynamic_style .= $template_dynamic_css->template_css;
		}

		wp_add_inline_style( 'styble-style', $styble_dynamic_style );
	}
}
