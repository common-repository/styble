<?php

namespace ShapedPlugin\Styble;

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Main Styble Class.
 */
class Styble {
	/**
	 * This plugin's instance.
	 *
	 * @var Styble
	 */
	private static $instance;

	/**
	 * Main Styble Instance.
	 *
	 * Insures that only one instance of Styble exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @static
	 * @return object|Styble The one true Styble
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Styble ) ) {
			self::$instance = new Styble();
			self::$instance->init();
		}
		return self::$instance;
	}

	/**
	 * Load actions
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'plugins_loaded', array( $this, 'styble_plugins_loaded' ), 99 );
	}

	/**
	 * Loads the plugin.
	 *
	 * @access public
	 * @return void
	 */
	public function styble_plugins_loaded() {
		/**
		 * Register gutenberg block function hook.
		 */
		add_action( 'init', array( $this, 'styble_init' ) );
		add_action( 'enqueue_block_assets', array( $this, 'styble_scripts_enqueue' ) );

		add_filter( 'rest_post_collection_params', array( $this, 'add_rand_orderby_rest_post_collection_params' ) );

		remove_filter( 'admin_head', 'wp_check_widget_editor_deps' );

		// post/page dynamic css.
		require STYBLE_PLUGIN_DIR . 'includes/dynamic-css.php';

		// post grid render render.
		require STYBLE_PLUGIN_DIR . 'includes/post-grid-render.php';

		// Block template dynamic css.
		require STYBLE_PLUGIN_DIR . 'includes/block-template-dynamic-css.php';

		$dynamic_css = new Dynamic_Css();
		$dynamic_css::instance();

		/**
		 * Load language file function.
		 */
		$this->styble_load_textdomain();
		/**
		 * Blocks category function hook.
		 */
		if ( version_compare( $GLOBALS['wp_version'], '5.7', '<' ) ) {
			add_filter( 'block_categories', array( $this, 'styble_register_block_category' ), 10, 2 );
		} else {
			add_filter( 'block_categories_all', array( $this, 'styble_register_block_category' ), 10, 2 );
		}
	}

	/**
	 * Js Css file enqueue function
	 */
	public function styble_scripts_enqueue() {
		// Css file.
		$template_dynamic_style = new Template_Dynamic_Style();
		$template_blocks        = $template_dynamic_style->active_block_lists;
		$our_blocks_name        = array( 'styble/accordions', 'styble/buttons', 'styble/infobox', 'styble/tabs', 'styble/container', 'styble/iconlist', 'styble/video', 'styble/gallery', 'styble/post-grid' );

		$check_our_blocks = false;

		foreach ( $our_blocks_name as $name ) {
			if ( has_block( $name ) || in_array( $name, $template_blocks, true ) ) {
				$check_our_blocks = true;
				break;
			}
		}

		if ( is_admin() || $check_our_blocks ) {
			wp_enqueue_style( 'styble-style', STYBLE_PLUGIN_URL . 'assets/css/style.min.css', array(), STYBLE_VERSION, 'all' );
			wp_enqueue_style( 'styble-fontawesome', STYBLE_PLUGIN_URL . 'assets/fontawesome/css/fontawesome.min.css', array(), STYBLE_VERSION, 'all' );
		}

		if ( is_admin() || ( has_block( 'styble/gallery' ) || in_array( 'styble/gallery', $template_blocks, true ) ) ) {
			wp_enqueue_style( 'styble-fancybox', STYBLE_PLUGIN_URL . 'assets/css/fancybox.css', array(), STYBLE_VERSION, 'all' );
		}

		if ( is_admin() || ( has_block( 'styble/accordions' ) || in_array( 'styble/accordions', $template_blocks, true ) ) ) {
			wp_enqueue_style( 'styble-accordion', STYBLE_PLUGIN_URL . 'assets/css/accordion.min.css', array(), STYBLE_VERSION, 'all' );
		}

		if ( is_admin() || ( has_block( 'styble/tabs' ) || in_array( 'styble/tabs', $template_blocks, true ) ) ) {
			wp_enqueue_style( 'styble-tabs', STYBLE_PLUGIN_URL . 'assets/css/tabs.min.css', array(), STYBLE_VERSION, 'all' );
		}

		if ( is_admin() || ( has_block( 'styble/tabs' ) || has_block( 'styble/accordions' ) ) || ( in_array( 'styble/tabs', $template_blocks, true ) || in_array( 'styble/accordions', $template_blocks, true ) ) ) {
			wp_enqueue_script( 'styble-accordion', STYBLE_PLUGIN_URL . 'assets/js/accordion.min.js', array(), STYBLE_VERSION, true );
		}

		if ( is_admin() || ( has_block( 'styble/gallery' ) || in_array( 'styble/gallery', $template_blocks, true ) ) ) {
			wp_enqueue_script( 'styble-fancybox', STYBLE_PLUGIN_URL . 'assets/js/fancybox.umd.js', array(), STYBLE_VERSION, true );
		}

		if ( is_admin() || ( has_block( 'styble/gallery' ) || in_array( 'styble/gallery', $template_blocks, true ) || has_block( 'styble/post-grid' ) || in_array( 'styble/post-grid', $template_blocks, true ) ) ) {
			wp_enqueue_script( 'styble-masonry', STYBLE_PLUGIN_URL . 'assets/js/masonry.js', array(), STYBLE_VERSION, true );
		}

		if ( ( has_block( 'styble/video' ) ) || ( in_array( 'styble/video', $template_blocks, true ) ) ) {
			wp_enqueue_script( 'styble-React-player', STYBLE_PLUGIN_URL . 'assets/js/react-player.min.js', array(), STYBLE_VERSION, true );
		}

		wp_enqueue_script( 'styble-scripts', STYBLE_PLUGIN_URL . 'assets/js/scripts.min.js', array( 'jquery' ), STYBLE_VERSION, true );

		wp_localize_script(
			'styble-scripts',
			'stybleLocalize',
			array(
				'pluginUrl'  => STYBLE_PLUGIN_URL,
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'styble-post-grid-nonce' ),
				'image_size' => $template_dynamic_style->get_image_sizes(),
			)
		);
	}


	/**
	 * Register block function
	 *
	 * @return void
	 */
	public function styble_init() {
		register_block_type( STYBLE_DIR . '/build/blocks/accordions' );
		register_block_type( STYBLE_DIR . '/build/blocks/accordion' );
		register_block_type( STYBLE_DIR . '/build/blocks/button' );
		register_block_type( STYBLE_DIR . '/build/blocks/buttons' );
		register_block_type( STYBLE_DIR . '/build/blocks/infobox' );
		register_block_type( STYBLE_DIR . '/build/blocks/tab' );
		register_block_type( STYBLE_DIR . '/build/blocks/tabs' );
		register_block_type( STYBLE_DIR . '/build/blocks/icon-list' );
		register_block_type( STYBLE_DIR . '/build/blocks/icon-list-child' );
		register_block_type( STYBLE_DIR . '/build/blocks/container' );
		register_block_type( STYBLE_DIR . '/build/blocks/column' );
		register_block_type( STYBLE_DIR . '/build/blocks/video' );
		register_block_type( STYBLE_DIR . '/build/blocks/gallery' );
		register_block_type( STYBLE_DIR . '/build/blocks/post-grid', array( 'render_callback' => array( new PostGrid(), 'post_grid_render' ) ) );
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @access public
	 * @return void
	 */
	public function styble_load_textdomain() {
		load_plugin_textdomain( 'styble', false, basename( STYBLE_PLUGIN_DIR ) . '/languages' );
	}

	/**
	 * Register category function
	 *
	 * @param array  $categories Categories list.
	 * @param object $post Post.
	 * @return array
	 */
	public function styble_register_block_category( $categories, $post ) {
		return array_merge(
			array(
				array(
					'slug'  => 'styble',
					'title' => __( 'Styble', 'styble' ),
				),
			),
			$categories
		);
	}

	/**
	 * Add `rand` as an option for orderby param in REST API.
	 * Hook to `rest_{$this->post_type}_collection_params` filter.
	 *
	 * @param array $query_params Accepted parameters.
	 * @return array
	 */
	public function add_rand_orderby_rest_post_collection_params( $query_params ) {
		$query_params['orderby']['enum'] = array_merge( $query_params['orderby']['enum'], array( 'rand', 'menu_order' ) );
		return $query_params;
	}
}
