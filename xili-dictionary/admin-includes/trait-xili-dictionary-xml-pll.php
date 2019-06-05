<?php
/**
* XD Admin class xml and pll functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Xml_Pll {

	// to be cleanly attached w/o creating terms (iso is passed)
	public function target_lang( $target_lang ) {
		if ( is_int( $target_lang ) ) {
			return $target_lang;
		}
		if ( 'Polylang' == $this->multilanguage_plugin_active ) {
			return (int) $this->iso_to_term_id[ $target_lang ];
		} else {
			return $target_lang;
		}
	}

	/**
	 * to be compatible with multilingual other plugins (first = Polylang)
	 *
	 */
	public function other_multilingual_compat( $languages ) {
		if ( class_exists( 'Polylang' ) ) { // 2.12
			$this->multilanguage_plugin_active = 'Polylang';
			// attach languages to group
			foreach ( $languages as $language ) {
				wp_set_object_terms( $language->term_id, 'the-langs-group', TAXOLANGSGROUP ); // link to group
				$desc_array = unserialize( $language->description );
				if ( false !== $desc_array ) {
					// when back to pll from xl
					$this->iso_to_term_id[ $desc_array['locale'] ] = $language->term_id;
				}
			}
			add_filter( 'other_multilingual_plugin_filter_terms', array( &$this, 'polylang_language_terms_compat' ) );
			add_filter( 'other_multilingual_plugin_filter_term', array( &$this, 'polylang_language_one_term_compat' ) );
		}
	}
	// series
	public function polylang_language_terms_compat( $languages ) {
		$adapted_languages = array();
		if ( $languages ) {
			foreach ( $languages as $language ) {
				// array
				$adapted_languages[] = $this->Polylang_language_one_term_compat( $language );
			}
			return $adapted_languages;
		}
		return $languages;
	}
	// one term
	public function polylang_language_one_term_compat( $language ) {
		$adapted_language = array();
		$adapted_language = (array) $language; // all values but with array to avoid error
		$desc_array = unserialize( $language->description );
		$adapted_language['name'] = $desc_array['locale']; // ISO
		$adapted_language['description'] = $language->name; // full name
		return (object) $adapted_language;
	}

	/**
	 * to search and import xml file
	 *
	 *
	 * @since 2.12
	 */
	public function available_theme_mod_xml() {
		if ( is_child_theme() ) {
			$file_in_parent = file_exists( get_template_directory() . '/wpml-config.xml' );
			$file_in_child = file_exists( get_stylesheet_directory() . '/wpml-config.xml' );
			return ( $file_in_child ) ? $file_in_child : $file_in_parent; // child has priority
		} else {
			$file_in_parent = file_exists( get_stylesheet_directory() . '/wpml-config.xml' );
			return $file_in_parent;
		}
	}

	public function display_form_theme_mod_xml() {
		$output = esc_html__( 'A config.xml file is available.', 'xili-dictionary' );
		if ( extension_loaded( 'simplexml' ) ) {
			$output .= ' ' . esc_html__( 'Check below if you want to add in dictionary the terms described inside this file. <br />(Do not forget to fill values in customizer before.)', 'xili-dictionary' );
			$output .= '<br /><label for="xmlimport">';
			$output .= '<input type="checkbox" id="xmlimport" name="xmlimport" value="xmlimport" />' . esc_html__( 'import theme_mods datas', 'xili-dictionary' );
			$output .= '</label>';
		} else {
			$output .= ' ' . esc_html__( 'BUT current PHP install needs <em>simplexml</em> extension !', 'xili-dictionary' );
		}
		return $output;
	}

	/**
	 * to read content of xml like wpml-config.xml
	 *
	 *
	 * @since 2.12
	 */
	public function get_xml_contents( $theme_slug = false ) {
		if ( is_child_theme() ) {
			$file_in_parent = file_exists( $parent_file_path = get_template_directory() . '/wpml-config.xml' );
			$file_in_child = file_exists( $child_file_path = get_stylesheet_directory() . '/wpml-config.xml' );
			$file = ( $file_in_child ) ? $child_file_path : ( ( $file_in_parent ) ? $parent_file_path : false );
		} else {
			$file_in_parent = file_exists( $child_file_path = get_stylesheet_directory() . '/wpml-config.xml' );
			$file = ( $file_in_parent ) ? $child_file_path : false;
		}

		if ( extension_loaded( 'simplexml' ) && $file ) {
			if ( false === $theme_slug ) {
				$theme_slug = get_option( 'stylesheet' );
			}
			$xml_config = simplexml_load_file( $file ); // new SimpleXMLElement( $xmlstr);
			if ( isset( $xml_config->{'admin-texts'}->key[0] ) ) {
				$theme_mod_list = array( $theme_slug => array() );
				foreach ( $xml_config->{'admin-texts'}->key[0]  as $one ) {
					$theme_mod_list[ $theme_slug ][] = (string) $one['name']; // force attribute to become string
				}
				return $theme_mod_list;
			}
		}
		return false;
	}

	/**
	 * display form to import pll
	 *
	 *
	 * @since 2.12.2
	 */
	public function display_form_pll_import() {
		$output = esc_html__( 'Polylang translation strings are available.', 'xili-dictionary' );

		$output .= ' ' . esc_html__( 'Check below if you want to add in dictionary the terms (and translations) made by Polylang', 'xili-dictionary' );
		$output .= '<br /><label for="pllimport">';
		$output .= '<input type="checkbox" id="pllimport" name="pllimport" value="pllimport" />' . esc_html__( 'import Polylang strings', 'xili-dictionary' );
		$output .= '</label>';

		return $output;
	}

	/**
	 * to import category data
	 *
	 *
	 * @since 2.12.2
	 */
	public function import_pll_categories_name_description() {
		$this->importing_mode = true;
		$i = 0;
		if ( 'isactive' == $this->xililanguage && ( $categories_group = get_option( 'xili_language_pll_term_category_groups' ) ) ) {
			$pll_languages = get_option( 'xili_language_pll_languages' ); // pll=>xl_slug

			foreach ( $categories_group as $one_group ) {
				$available_langs = array();
				$msgid = array();
				$msgstr = array();
				foreach ( $one_group['pll_links'] as $pll_key => $term_id ) {
					$term = get_term_by( 'id', (int) $term_id, 'category' );
					$lang = $pll_languages[ $pll_key ];
					if ( 'en_us' == $lang ) { // to fixe if not default ???
						$msgid['name'] = $term->name;
						$msgid['description'] = $term->description;
					} elseif ( $term ) {
						$available_langs[] = $lang;
						$msgstr['name'][ $lang ] = $term->name;
						$msgstr['description'][ $lang ] = $term->description;
					}
				}
				// add terms in dictionary

				if ( ! empty( $msgid['name'] ) ) { // to fixe if not default ???
					foreach ( $available_langs as $lang_slug ) {
						$onelang = get_term_by( 'slug', $lang_slug, TAXONAME );
						$oneline = array();
						$oneline['msgid'] = $msgid['name'];
						$oneline['msgstr'] = $msgstr['name'][ $lang_slug ];
						$oneline['extracted_comments'] = $this->local_tag . ' pll_category';
						$this->one_term_in_cpt_xdmsg( $oneline, $onelang->name );
						$i++;
						if ( ! empty( $msgid['description'] ) ) { // description associated w name
							$oneline = array();
							$oneline['msgid'] = $msgid['description'];
							$oneline['msgstr'] = $msgstr['description'][ $lang_slug ];
							$oneline['extracted_comments'] = $this->local_tag . ' pll_category';
							$this->one_term_in_cpt_xdmsg( $oneline, $onelang->name );
							$i++;
						}
					}
				}
			}
		}
		$this->importing_mode = false;
		return $i;
	}

	/**
	 * to search and import polylang_mo data
	 *
	 *
	 * @since 2.12.2
	 */
	public function import_pll_db_mos() {
		$listlanguages = $this->get_list_languages();
		$results = array();
		$this->importing_mode = true;
		foreach ( $listlanguages as $language ) {
			$lines = $this->import_pll_post_mo( $language );
			$key = $language->name;
			$results[ $key ] = $lines;
		}
		$this->importing_mode = false;
		return $results;
	}

	/**
	 * to read and import polylang_mo data inside dictionary
	 *
	 *
	 * @since 2.12.2
	 * @param lang as object
	 */
	public function import_pll_post_mo( $lang ) {
		$mo_id = $this->get_id( $lang );
		$mo = new MO();
		$post = get_post( $mo_id, OBJECT );
		$strings = unserialize( $post->post_content );
		if ( is_array( $strings ) && array( '', '' ) != $strings ) {
			$lines = 0;
			foreach ( $strings as $msg ) {
				if ( ! empty( $msg[0] ) ) {
					$mo->add_entry( $mo->make_entry( $msg[0], $msg[1] ) );
				}
			}
			foreach ( $mo->entries as $pomsgid => $pomsgstr ) {
				$pomsgstr->extracted_comments = $this->local_tag . ' pll_mo_imported';
				$this->pomo_entry_to_xdmsg(
					$pomsgid,
					$pomsgstr,
					$lang->name,
					array(
						'importing_po_comments' => 'replace',
						'origin_theme' => '',
					)
				);
				$lines++;
			}
			return $lines;
		}
		return false;
	}

	/**
	 * returns the post id of the custom post polylang_mo_ storing the strings translations
	 *
	 * @since 2.12.2 - 1.4 - pll
	 *
	 * @param object $lang
	 * @return int
	 */
	public function get_id( $lang ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type= %s", 'polylang_mo_' . $lang->term_id, 'polylang_mo' ) );
	}
}
