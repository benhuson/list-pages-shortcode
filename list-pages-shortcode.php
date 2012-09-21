<?php

/*
Plugin Name: List Pages Shortcode
Plugin URI: http://wordpress.org/extend/plugins/list-pages-shortcode/
Description: Introduces the [list-pages], [sibling-pages] and [child-pages] <a href="http://codex.wordpress.org/Shortcode_API">shortcodes</a> for easily displaying a list of pages within a post or page.  Both shortcodes accept all parameters that you can pass to the <a href="http://codex.wordpress.org/Template_Tags/wp_list_pages">wp_list_pages()</a> function.  For example, to show a page's child pages sorted by title simply add [child-pages sort_column="post_title"] in the page's content.
Author: Aaron Harp, Ben Huson
Version: 1.4
Author URI: http://www.aaronharp.com
*/

class List_Pages_Shortcode {
	
	/**
	 * Constructor
	 */
	function List_Pages_Shortcode() {
		add_shortcode( 'child-pages', array( $this, 'shortcode_list_pages' ) );
		add_shortcode( 'sibling-pages', array( $this, 'shortcode_list_pages' ) );
		add_shortcode( 'list-pages', array( $this, 'shortcode_list_pages' ) );
		add_filter( 'list_pages_shortcode_excerpt', array( $this, 'excerpt_filter' ) );
	}
	
	function shortcode_list_pages( $atts, $content, $tag ) {
		global $post;
		
		// Child Pages
		$child_of = 0;
		if ( $tag == 'child-pages' )
			$child_of = $post->ID;
		if ( $tag == 'sibling-pages' )
			$child_of = $post->post_parent;
		
		// Set defaults
		$defaults = array(
			'class'       => $tag,
			'depth'       => 0,
			'show_date'   => '',
			'date_format' => get_option( 'date_format' ),
			'exclude'     => '',
			'include'     => '',
			'child_of'    => $child_of,
			'title_li'    => '',
			'authors'     => '',
			'sort_column' => 'menu_order, post_title',
			'sort_order'  => '',
			'link_before' => '',
			'link_after'  => '',
			'exclude_tree'=> '',
			'meta_key'    => '',
			'meta_value'  => '',
			'offset'      => '',
			'post_status' => 'publish',
			'exclude_current_page' => 0,
			'excerpt'     => 0
		);
		
		// Merge user provided atts with defaults
		$atts = shortcode_atts( $defaults, $atts );
		
		// Set necessary params
		$atts['echo'] = 0;
		if ( $atts['exclude_current_page'] && absint( $post->ID ) ) {
			if ( !empty( $atts['exclude'] ) )
				$atts['exclude'] .= ',';
			$atts['exclude'] .= $post->ID;
		}
		
		$atts = apply_filters( 'shortcode_list_pages_attributes', $atts, $content, $tag );
		
		// Use custom walker
		if ( $atts['excerpt'] ) {
			$atts['walker'] = new List_Pages_Shortcode_Walker_Page;
		}
		
		// Create output
		$out = wp_list_pages( $atts );
		if ( !empty( $out ) )
			$out = '<ul class="' . $atts['class'] . '">' . $out . '</ul>';
		
		return apply_filters( 'shortcode_list_pages', $out, $atts, $content, $tag );
	}
	
	/**
	 * Excerpt Filter
	 * Add a <div> around the excerpt by default.
	 *
	 * @param string $excerpt Excerpt.
	 * @return string Filtered excerpt.
	 */
	function excerpt_filter( $text ) {
		if ( ! empty( $text ) )
			return '<div class="excerpt">' . $text . '</div>';
		return $text;
	}
	
}

/**
 * Create HTML list of pages.
 * A copy of the WordPress Walker_Page class which adds an excerpt.
 */
class List_Pages_Shortcode_Walker_Page extends Walker_Page {
	
	function start_el( &$output, $page, $depth, $args, $current_page = 0 ) {
		if ( $depth )
			$indent = str_repeat("\t", $depth);
		else
			$indent = '';

		extract($args, EXTR_SKIP);
		$css_class = array('page_item', 'page-item-'.$page->ID);
		if ( !empty($current_page) ) {
			$_current_page = get_page( $current_page );
			if ( in_array( $page->ID, $_current_page->ancestors ) )
				$css_class[] = 'current_page_ancestor';
			if ( $page->ID == $current_page )
				$css_class[] = 'current_page_item';
			elseif ( $_current_page && $page->ID == $_current_page->post_parent )
				$css_class[] = 'current_page_parent';
		} elseif ( $page->ID == get_option('page_for_posts') ) {
			$css_class[] = 'current_page_parent';
		}

		$css_class = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

		$output .= $indent . '<li class="' . $css_class . '"><a href="' . get_permalink($page->ID) . '">' . $link_before . apply_filters( 'the_title', $page->post_title, $page->ID ) . $link_after . '</a>';

		if ( !empty($show_date) ) {
			if ( 'modified' == $show_date )
				$time = $page->post_modified;
			else
				$time = $page->post_date;

			$output .= " " . mysql2date($date_format, $time);
		}
		
		// Excerpt
		if ( $args['excerpt'] ) {
			$output .= apply_filters( 'list_pages_shortcode_excerpt', $page->post_excerpt, $page, $depth, $args, $current_page );
		}
	}
	
}

/**
 * [shortcode_list_pages] Function
 * Kept for legacy reasons in case people are using it directly.
 */
function shortcode_list_pages( $atts, $content, $tag ) {
	global $List_Pages_Shortcode;
	return $List_Pages_Shortcode->shortcode_list_pages( $atts, $content, $tag );
}

global $List_Pages_Shortcode;
$List_Pages_Shortcode = new List_Pages_Shortcode();

?>