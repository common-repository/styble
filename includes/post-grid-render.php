<?php
namespace ShapedPlugin\Styble;

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Post Content render Class.
 */
class PostGrid {
	/**
	 * Constructor function
	 */
	public function __construct() {
		add_action( 'wp_ajax_styble_pagination', array( $this, 'styble_get_posts_for_pagination' ) );
		add_action( 'wp_ajax_nopriv_styble_pagination', array( $this, 'styble_get_posts_for_pagination' ) );
	}

	/**
	 * Post Grid item function.
	 *
	 * @param object $posts Post list.
	 * @param array  $data_attr Option data attr.
	 * @return statement
	 */
	public function post_grid_item( $posts, $data_attr = array() ) {
		$gap_between_item = isset( $data_attr['gapBetweenItem'] ) ? $data_attr['gapBetweenItem'] : 20;
		$enable_image     = isset( $data_attr['enable_image'] ) ? $data_attr['enable_image'] : '';
		$enable_title     = isset( $data_attr['enable_title'] ) ? $data_attr['enable_title'] : '';
		$title_tag        = isset( $data_attr['title_tag'] ) ? $data_attr['title_tag'] : '';
		$show_author      = isset( $data_attr['show_author'] ) ? $data_attr['show_author'] : '';
		$show_date        = isset( $data_attr['show_date'] ) ? $data_attr['show_date'] : '';
		$show_taxonomy    = isset( $data_attr['show_taxonomy'] ) ? $data_attr['show_taxonomy'] : '';
		$show_content     = isset( $data_attr['show_content'] ) ? $data_attr['show_content'] : '';
		$show_read_more   = isset( $data_attr['show_read_more'] ) ? $data_attr['show_read_more'] : '';
		$layout           = isset( $data_attr['layout'] ) ? $data_attr['layout'] : 'grid';
		$image_size       = isset( $data_attr['image_size'] ) ? $data_attr['image_size'] : 'full';
		$number_of_words  = isset( $data_attr['number_of_words'] ) ? $data_attr['number_of_words'] : 55;

		$masonry_data = ( 'masonry' === $layout ) ? 'data-masonry="{ &quot;itemSelector&quot;: &quot;.styble-post-item&quot;, &quot;gutter&quot;: ' . $gap_between_item . ' }"' : '';

		ob_start() ?>
			<div class='styble-post-grid-wrapper' <?php echo wp_kses_post( $masonry_data ); ?>>
			<?php
			while ( $posts->have_posts() ) {
				$posts->the_post();

				$title       = get_the_title();
				$link        = get_permalink();
				$excerpt     = get_the_excerpt();
				$author      = get_the_author();
				$author_link = get_author_posts_url( get_the_author_meta( 'ID' ) );

				?>
					<div class='styble-post-item'>
						<?php
						if ( true === filter_var( $enable_image, FILTER_VALIDATE_BOOLEAN ) ) {
							?>
							<div class='styble-post-image'>
								<?php
								if ( has_post_thumbnail( get_the_ID() ) ) {
									echo get_the_post_thumbnail( get_the_ID(), $image_size );
								}
								?>
							</div>
							<?php
						}
						?>
						<div class="styble-post-body">
							<?php
							if ( true === filter_var( $enable_title, FILTER_VALIDATE_BOOLEAN ) ) {
								?>
									<<?php echo esc_html( $title_tag ); ?> class='styble-post-title'><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></<?php echo esc_html( $title_tag ); ?>>
								<?php
							}
							?>
							<div class='styble-post-meta'>
								<?php
								if ( true === filter_var( $show_author, FILTER_VALIDATE_BOOLEAN ) ) {
									?>
										<span class='styble-post-author'><?php echo esc_html__( 'By', 'styble' ); ?> <a href=<?php echo esc_attr( $author_link ); ?>><?php echo esc_html( $author ); ?></a> </span>
									<?php
								}
								?>
								<?php
								if ( true === filter_var( $show_date, FILTER_VALIDATE_BOOLEAN ) ) {
									?>
										<time class='styble-post-date' dateTime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
											<?php echo esc_html__( 'On', 'styble' ) . ' ' . esc_html( get_the_date( '' ) ); ?>
										</time>
									<?php
								}
								?>
								<?php
								if ( true === filter_var( $show_taxonomy, FILTER_VALIDATE_BOOLEAN ) ) {
									?>
										<div class='styble-post-cat'><?php echo wp_kses_post( get_the_category_list( ', ' ) ); ?></div>
									<?php
								}
								?>
							</div>
							<?php
							if ( true === filter_var( $show_content, FILTER_VALIDATE_BOOLEAN ) ) {
								?>
									<div class='styble-post-content'>
										<?php
										if ( ! empty( $excerpt ) ) {
											echo '<p>' . wp_kses_post( implode( ' ', array_slice( explode( ' ', $excerpt ), 0, $number_of_words ) ) ) . '</p>';
										}
										?>
									</div>
								<?php
							}
							?>
							<?php
							if ( true === filter_var( $show_read_more, FILTER_VALIDATE_BOOLEAN ) ) {
								?>
								<div class='styble-post-btn'>
									<a class='styble-post-read-more' href='<?php echo esc_url( $link ); ?>'><span>Read More</span></a>
								</div>
								<?php
							}
							?>
						</div>
					</div>	
				<?php
			}
			?>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Ajax pagination function.
	 *
	 * @return void
	 */
	public function styble_get_posts_for_pagination() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : null;
		if ( ! wp_verify_nonce( $nonce, 'styble-post-grid-nonce' ) ) {
			return;
		}
		$paged      = isset( $_POST['paged'] ) ? sanitize_text_field( wp_unslash( $_POST['paged'] ) ) : 1;
		$data_query = isset( $_POST['data_query'] ) ? map_deep( wp_unslash( $_POST['data_query'] ), 'sanitize_text_field' ) : array();
		$data_attr  = isset( $_POST['data_attr'] ) ? map_deep( wp_unslash( $_POST['data_attr'] ), 'sanitize_text_field' ) : array();

		$posts = $this->post_query( $paged, $data_query );

		ob_start();
		?>
			<?php echo wp_kses_post( $this->post_grid_item( $posts, $data_attr ) ); ?>
			<?php echo wp_kses_post( $this->styble_pagination( $posts, $paged, 'ajax' ) ); ?>
		<?php
		wp_send_json_success( ob_get_clean() );
		exit();
	}

	/**
	 * Ajax Pagination render function
	 *
	 * @param object $posts All Post.
	 * @param int    $paged Page number.
	 * @param string $type Pagination type.
	 * @return statement
	 */
	public function styble_pagination( $posts, $paged, $type ) {
		$args            = array(
			'format'       => '?paged=%#%',
			'total'        => $posts->max_num_pages,
			'current'      => max( 1, intval( $paged ) ),
			'show_all'     => false,
			'type'         => 'array',
			'end_size'     => 2,
			'mid_size'     => 1,
			'prev_next'    => true,
			'prev_text'    => sprintf( '<i></i> %1$s', __( 'Prev', 'styble' ) ),
			'next_text'    => sprintf( '%1$s <i></i>', __( 'Next', 'styble' ) ),
			'add_args'     => false,
			'add_fragment' => '',
		);
		$page_links      = paginate_links( $args );
		$page_links      = is_array( $page_links ) ? $page_links : array();
		$pagination_html = '';
		foreach ( $page_links as $page_link ) {
			$class = 'page-numbers ';
			if ( strpos( $page_link, 'current' ) !== false ) {
				$class .= 'current ';
			}
			if ( strpos( $page_link, 'next' ) !== false ) {
				$class .= 'next ';
			} elseif ( strpos( $page_link, 'prev' ) !== false ) {
				$class .= ' prev';
			} elseif ( strpos( $page_link, 'dots' ) !== false ) {
				$class .= ' dots';
			}
			$page_link        = preg_replace( '/<span[^>]*>/', '<a href="#" class="' . $class . '">', $page_link );
			$page_link        = str_replace( '</span>', '</a>', $page_link );
			$pagination_html .= $page_link;
		}
		ob_start();
		?>
		<div class="styble-post-pagination">
			<?php
				echo wp_kses_post( $pagination_html );
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Post Query function
	 *
	 * @param int   $paged Page Number.
	 * @param array $post_query Post Query.
	 * @return object
	 */
	public function post_query( $paged, $post_query = array() ) {
		$attr = array(
			'posts_per_page'      => 3,
			'post_status'         => 'publish',
			'post_type'           => 'post',
			'order'               => 'desc',
			'orderby'             => 'date',
			'ignore_sticky_posts' => 1,
			'paged'               => intval( $paged ),
		);

		$attr = wp_parse_args( $post_query, $attr );

		return new \Wp_Query( $attr );
	}

	/**
	 * Post Grid render function
	 *
	 * @param array $attributes Block attributes.
	 * @return statement
	 */
	public function post_grid_render( $attributes ) {
		$unique_id        = isset( $attributes['uniqueId'] ) ? 'styble-post-grid-' . $attributes['uniqueId'] : '';
		$posts_per_page   = isset( $attributes['postPerPage']['value'] ) ? $attributes['postPerPage']['value'] : 9999;
		$gap_between_item = isset( $attributes['gapBetweenItem']['value'] ) ? $attributes['gapBetweenItem']['value'] : 20;
		$enable_image     = isset( $attributes['enableImage'] ) ? $attributes['enableImage'] : true;
		$enable_title     = isset( $attributes['showTitle'] ) ? $attributes['showTitle'] : true;
		$title_tag        = isset( $attributes['titleTag'] ) ? $attributes['titleTag'] : 'h2';
		$show_author      = isset( $attributes['showAuthor'] ) ? $attributes['showAuthor'] : true;
		$show_date        = isset( $attributes['showDate'] ) ? $attributes['showDate'] : true;
		$show_taxonomy    = isset( $attributes['showTaxonomy'] ) ? $attributes['showTaxonomy'] : true;
		$show_content     = isset( $attributes['showContent'] ) ? $attributes['showContent'] : true;
		$show_read_more   = isset( $attributes['showReadMore'] ) ? $attributes['showReadMore'] : true;
		$show_pagination  = isset( $attributes['showPagination'] ) ? $attributes['showPagination'] : true;
		$layout           = isset( $attributes['layout'] ) ? $attributes['layout'] : 'grid';
		$additional_class = isset( $attributes['additionalClass'] ) ? $attributes['additionalClass'] : '';
		$order_by         = isset( $attributes['orderBy'] ) ? $attributes['orderBy'] : 'date';
		$order            = isset( $attributes['order'] ) ? $attributes['order'] : 'desc';
		$cate_list        = isset( $attributes['cateList'] ) ? $attributes['cateList'] : array();
		$image_size       = isset( $attributes['imageSize'] ) ? $attributes['imageSize'] : 'full';
		$number_of_words  = isset( $attributes['maxNumberOfWords']['value'] ) ? $attributes['maxNumberOfWords']['value'] : 55;

		$query_attr = array(
			'posts_per_page' => $posts_per_page,
			'orderby'        => $order_by,
			'order'          => $order,
		);

		if ( count( $cate_list ) > 0 ) {
			$cats = array();
			foreach ( $cate_list as $value ) {
				array_push( $cats, $value['value'] );
			}
			$query_attr['category__in'] = $cats;
		}

		$data_attr = array(
			'enable_image'    => $enable_image,
			'enable_title'    => $enable_title,
			'title_tag'       => $title_tag,
			'show_author'     => $show_author,
			'show_date'       => $show_date,
			'show_taxonomy'   => $show_taxonomy,
			'show_content'    => $show_content,
			'show_read_more'  => $show_read_more,
			'gapBetweenItem'  => $gap_between_item,
			'layout'          => $layout,
			'image_size'      => $image_size,
			'number_of_words' => $number_of_words,
		);

		$paged = get_query_var( 'paged' );
		$posts = $this->post_query( $paged, $query_attr );

		$post_grid_class = 'styble-post-grid ' . $additional_class;

		ob_start();
		?>
		<div <?php echo wp_kses_post( get_block_wrapper_attributes() ); ?>>
			<div class='<?php echo esc_attr( $post_grid_class ); ?>' data_query=<?php echo esc_attr( wp_json_encode( $query_attr ) ); ?> data_attributes=<?php echo esc_attr( wp_json_encode( $data_attr ) ); ?> id=<?php echo esc_attr( $unique_id ); ?>>
				<?php echo wp_kses_post( $this->post_grid_item( $posts, $data_attr ) ); ?>
				<?php
				if ( true === $show_pagination ) {
					echo wp_kses_post( $this->styble_pagination( $posts, $paged, 'normal' ) );
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();

	}
}
