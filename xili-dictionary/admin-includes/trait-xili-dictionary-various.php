<?php

/**
* XD Admin class several functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Various {
	/**
	 * detects other xili plugins used in email support form
	 *
	 *
	 * @since 2.3.2
	 */
	public function check_other_xili_plugins() {
		$list = array(); {
			$list[] = 'xili-language';
		}
		if ( class_exists( 'xili_tidy_tags' ) ) {
			$list[] = 'xili-tidy-tags';
		}

		if ( class_exists( 'xilithemeselector' ) ) {
			$list[] = 'xilitheme-select';
		}
		if ( function_exists( 'insert_a_floom' ) ) {
			$list[] = 'xili-floom-slideshow';
		}
		if ( class_exists( 'xili_postinpost' ) ) {
			$list[] = 'xili-postinpost';
		}
		if ( class_exists( 'xili_re_un_attach_media' ) ) {
			$list[] = 'xili re/un-attach media';
		}
		return implode( ', ', $list );
	}

	/**
	 * return the current theme name as saved in option with parent
	 * param: true if parent appended
	 */
	public function get_option_theme_name( $child_of = false ) {
		if ( is_child_theme() ) { // 1.8.1 and WP 3.0
			$theme_name = get_option( 'stylesheet' );
			if ( $child_of ) {
				$theme_name .= ' ' . __( 'child of', 'xili-dictionary' ) . ' ' . get_option( 'template' );
			}
		} else {
			$theme_name = get_option( 'template' );
		}
		return $theme_name;
	}

}
