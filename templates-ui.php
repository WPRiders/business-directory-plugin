<?php 
function wpbdp_list_categories( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'parent'       => null,
		'echo'         => false,
		'orderby'      => wpbdp_get_option( 'categories-order-by' ),
		'order'        => wpbdp_get_option( 'categories-sort' ),
		'show_count'   => wpbdp_get_option( 'show-category-post-count' ),
		'hide_empty'   => false,
		'parent_only'  => false,
		'parent'       => 0,
		'no_items_msg' => _x( 'No listing categories found.', 'templates', 'WPBDM' )
	) );

	$html = '';

	//if ( $categories = _wpbdp_list_categories_walk( 0, 0, $args ) ) { // @ 18-10-2016; changed the function call
	$categories = wpr_start_building_html( 0, 0, $args );
	if ( $categories ) {
		$html .= '<ul class="wpbdp-categories cf ' . apply_filters( 'wpbdp_categories_list_css', '' ) . '">';
		$html .= $categories;
		$html .= '</ul>';
	}

	$html = apply_filters( 'wpbdp_categories_list', $html );

	if ( $args['echo'] ) {
		echo $html;
	}

	return $html;
}

// This function will begin building the alphabetical order
function wpr_start_building_html( $parent = 0, $depth = 0, $args ) {
	$wpr_taxonomy_list = wpr_list_categories_walk( $parent = 0, $depth = 0, $args );
	$html              = '';
	$current_letter    = '';
	$row_action        = 0;
	foreach ( $wpr_taxonomy_list as $index => $values ) {
		$row_action++;

		if ( strtolower( $values['taxonomy_title'][0] ) === $current_letter ) {
			if ( 3 === $row_action ) {
				$html .= "</div><div class='wpr_row'>";
				$row_action = 0;
			}
			$html .= '<div class="listing-element">' . wpr_build_html_listing( $values ) . '</div>';

		} else {
			$row_action = 0;
			if ( ! empty( $html ) ) {
				$html .= '</div>';
				$html .= '</div>';
				$html .= '</div>';
			}
			$current_letter = strtolower( $values['taxonomy_title'][0] );
			$html .= "<div class='wpr_container wpr_letter-" . strtolower( $values['taxonomy_title'][0] ) . "'>";
			$html .= "<div class='wpr_letter'><div class='letter_properties'><span class='letter'>" . strtoupper( $values['taxonomy_title'][0] ) . "</span></div></div>";
			$html .= "<div class='wpr_listing_container'>";
			$html .= "<div class='wpr_row'>";
			$html .= '<div class="listing-element">' . wpr_build_html_listing( $values ) . '</div>';
		}
	}
	if ( ! empty( $html ) ) {
		$html .= '</div>';
		$html .= '</div>';
	}

	return $html;
}

// This function does queries to get the categories and subcategories and results a multidimensional array
function wpr_list_categories_walk( $parent = 0, $depth = 0, $args ) {
	$term_ids = get_terms( WPBDP_CATEGORY_TAX,
		array(
			'orderby'    => $args['orderby'],
			'order'      => $args['order'],
			'hide_empty' => false,
			'pad_counts' => false,
			'parent'     => is_object( $args['parent'] ) ? $args['parent']->term_id : intval( $args['parent'] ),
			'fields'     => 'ids'
		)
	);

	$terms = array();
	foreach ( $term_ids as $term_id ) {
		$t = get_term( $term_id, WPBDP_CATEGORY_TAX );
		// 'pad_counts' doesn't work because of WP bug #15626 (see http://core.trac.wordpress.org/ticket/15626).
		// we need a workaround until the bug is fixed.
		_wpbdp_padded_count( $t );

		$terms[] = $t;
	}

	// filter empty terms
	if ( $args['hide_empty'] ) {
		$terms = array_filter( $terms, create_function( '$x', 'return $x->count > 0;' ) );
	}

	$html = '';

	if ( ! $terms && $depth === 0 ) {
		if ( $args['no_items_msg'] ) {
			$html .= '<p>' . $args['no_items_msg'] . '</p>';
		}

		return $html;
	}

	$wpr_taxonomy_list = array();
	foreach ( $terms as &$term ) {
		$count_str = '';
		if ( $args['show_count'] ) {
			$count_str = ' <span class="wpr_count">(' . intval( $term->count ) . ')</span>';
			$count_str = apply_filters( 'wpbdp_categories_item_count_str', $count_str, $term );
		}

		$wpr_children = array();
		if ( ! $args['parent_only'] ) {
			$args['parent']    = $term->term_id;
			$wpr_subcategories = wpr_list_categories_walk( $term->term_id, $depth + 1, $args );
			if ( ! empty( $wpr_subcategories ) ) {
				$wpr_children = $wpr_subcategories;
			}
		}

		$wpr_taxonomy_list[] = array(
			'taxonomy_title' => esc_attr( $term->name ),
			'tag_title'      => esc_attr( strip_tags( apply_filters( 'category_description', $term->description, $term ) ) ),
			'count'          => $count_str,
			'link'           => esc_url( get_term_link( $term ) ),
			'subcategories'  => $wpr_children
		);
	}

	return $wpr_taxonomy_list;

}
// This function builds the HTML for a category and its subcategories ( a column )
function wpr_build_html_listing( $content, $depth = 0 ) {
	$html = '';
	if ( 0 === $depth ) {
		$count      = $content['count'];
		$count_html = '';
		if ( ! empty( $count ) ) {
			$count_html = $content['count'];
		}
		$html .= <<<HTMLCONTENT
	<div class="wpr_top_taxonomy">
		<a href="{$content['link']}">{$content['taxonomy_title']} $count_html</a>
	</div>
HTMLCONTENT;

		if ( ! empty( $content['subcategories'] ) ) {
			foreach ( $content['subcategories'] as $index => $values ) {
				$html .= wpr_build_html_listing( $values, 1 );
			}
		}
	} else {

		$count      = $content['count'];
		$count_html = '';
		if ( ! empty( $count ) ) {
			$count_html = $content['count'];
		}
		$html .= <<<HTMLCONTENT
	<div class="wpr_sub_taxonomy">
		<a href="{$content['link']}">{$content['taxonomy_title']} $count_html</a>
	</div>
HTMLCONTENT;
	}

	return $html;
}
