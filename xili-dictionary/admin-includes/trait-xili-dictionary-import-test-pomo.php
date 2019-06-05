<?php

/**
* XD Admin import or test pomo functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Import_Test_Pomo {

	// need langfolder
	public function mo_files_array() {
		$this->theme_mos = $this->get_pomo_from_theme();
		$this->local_mos = $this->get_pomo_from_theme( true ); // 2.1
		if ( is_multisite() ) {
			$this->file_site_mos = $this->get_pomo_from_site(); // since 1.2.0 - mo of site
			$this->file_site_local_mos = $this->get_pomo_from_site( true );
		}
		// test if plugin has msgid
		$list_plugins = $this->get_origin_plugin_paths();
		if ( $list_plugins ) {
			foreach ( $list_plugins as $plugin_path ) {
				$this->plugin_mos[ $plugin_path ] = $this->get_pomo_from_plugin( $plugin_path ); // update for lang
			}
		}
	}

	/**
	 * create an array of mo content of theme (maintained by super-admin)
	 *
	 * @since 1.1.0
	 */
	public function get_pomo_from_theme( $local = false ) {
		$theme_mos = array();
		if ( defined( 'TAXONAME' ) ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );

			foreach ( $listlanguages as $reflanguage ) {
				$res = $this->pomo_import_mo( $reflanguage->name, '', $local );
				if ( false !== $res ) {
					$theme_mos[ $reflanguage->slug ] = $res->entries;
				}
			}
		}
		return $theme_mos;
	}

	public function get_pomo_from_plugin( $plugin_path ) {
		if ( ! isset( $this->plugin_mos[ $plugin_path ] ) ) {
			$this->plugin_mos[ $plugin_path ] = array();
		}
		if ( defined( 'TAXONAME' ) ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );

			foreach ( $listlanguages as $reflanguage ) {
				$res = $this->pomo_import_mo( $reflanguage->name, '', false, $plugin_path ); // 2.10.1 only in plugin language folder
				if ( false !== $res ) {
					$this->plugin_mos[ $plugin_path ][ $reflanguage->slug ] = $res->entries;
				}
				$res = $this->pomo_import_mo( $reflanguage->name, '', false, $plugin_path, true ); // 2.10.1 only in content language folder
				if ( false !== $res ) {
					$this->plugin_mos[ $plugin_path ][ $reflanguage->slug ] = $res->entries; // WP_LANG_DIR has priority
					$this->plugin_mos[ $plugin_path ]['WLD'][ $reflanguage->slug ] = 1;
				}
			}
		}
		return $this->plugin_mos[ $plugin_path ];
	}

	/**
	 * create an array of mo content of theme (maintained by admin of current site)
	 * currently contains the msgid which are not in theme mo
	 *
	 * @since 1.2.0
	 */
	public function get_pomo_from_site( $local = false ) {
		$theme_mos = array();
		if ( defined( 'TAXONAME' ) ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
			foreach ( $listlanguages as $reflanguage ) {
				$res = $this->import_mo_file_wpmu( $reflanguage->name, false, $local ); // of current site
				if ( false !== $res ) {
					$theme_mos[ $reflanguage->slug ] = $res->entries;
				}
			}
		}
		return $theme_mos;
	}

	/**
	 * test if line is in entries
	 * @since
	 */
	public function is_intheme_mos( $msg, $type, $entries, $context ) {
		foreach ( $entries as $entry ) {
			$diff = 1;
			switch ( $type ) {
				case 'msgid':
					$diff = strcmp( $msg, $entry->singular );
					if ( '' != $context ) {
						if ( null != $entry->context ) {
							$diff += strcmp( $context, $entry->context );
						}
					}
					break;
				case 'msgid_plural':
					$diff = strcmp( $msg, $entry->plural );
					break;
				case 'msgstr':
					if ( isset( $entry->translations[0] ) ) {
						$diff = strcmp( $msg, $entry->translations[0] );
					}
					break;
				default:
					if ( false !== strpos( $type, 'msgstr_' ) ) {
						$indice = (int) substr( $type, -1 );
						if ( isset( $entry->translations[ $indice ] ) ) {
							$diff = strcmp( $msg, $entry->translations[ $indice ] );
						}
					}
			}

			//if ( $diff != 0) { echo $msg.' i= '.strlen( $msg); echo $entry->singular.' ) e= '.strlen( $entry->singular); }
			if ( 0 == $diff ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Detect if cpt are saved in theme's languages folder
	 * @since 2.3.4
	 *
	 */
	public function is_msg_saved_in_localmos( $msg, $type, $context = '', $mode = 'list' ) {

		$thelist = array();
		$thelistsite = array();
		$outputsite = '';
		$output = '';
		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ( '' != $langfolderset ) ? $langfolderset . '/' : '/';
		// doublon
		$this->langfolder = str_replace( '//', '/', $this->langfolder ); // upgrading... 2.0 and sub folder sub
		$this->mo_files_array();

		if ( defined( 'TAXONAME' ) ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );

			foreach ( $listlanguages as $reflanguage ) {

				if ( isset( $this->local_mos[ $reflanguage->slug ] ) ) {
					if ( 'list' == $mode && $this->is_intheme_mos( $msg, $type, $this->local_mos[ $reflanguage->slug ], $context ) ) {
						$thelist[] = '<span class="lang-' . $reflanguage->slug . '" >' . $reflanguage->name . '</span>';
					} elseif ( 'single' == $mode ) {
						$res = $this->get_msg_in_entries( $msg, $type, $this->local_mos[ $reflanguage->slug ], $context );
						if ( array() != $res ) {
							$thelist[ $reflanguage->name ] = $res;
						}
					}
				}

				if ( is_multisite() ) {
					if ( isset( $this->file_site_local_mos[ $reflanguage->slug ] ) ) {
						if ( $this->get_msg_in_entries( $msg, $type, $this->file_site_local_mos[ $reflanguage->slug ], $context ) ) {
							$thelistsite[] = '<span class="lang-' . $reflanguage->slug . '" >' . $reflanguage->name . '</span>';
						}
					}
				}
			}

			if ( 'list' == $mode ) {

				$output = ( array() == $thelist ) ? '<br /><small><span style="color:black" title="' . __( "No translations saved in theme's .mo files", 'xili-dictionary' ) . '">**</span></small>' : '<br /><small><span style="color:green" title="' . __( "Original with translations saved in theme's files: ", 'xili-dictionary' ) . '" >' . implode( ' ', $thelist ) . '</small></small>';

				if ( is_multisite() ) {

					$outputsite = ( array() == $thelistsite ) ? '<br /><small><span style="color:black" title="' . __( "No translations saved in site's .mo files", 'xili-dictionary' ) . '">**</span></small>' : '<br /><small><span style="color:green" title="' . __( "Original with translations saved in site's files: ", 'xili-dictionary' ) . '" >' . implode( ', ', $thelistsite ) . '</small></small>';

				}
			} elseif ( 'single' == $mode ) {

				if ( array() == $thelist ) {

					$output = esc_html__( 'Not yet translated in any language (not in any .mo files)', 'xili-dictionary' ) . '<br />';
				} else {
					$output = '';
					foreach ( $thelist as $key => $msg ) {

						$output .= '<span title="' . esc_html__( 'Translated in', 'xili-dictionary' ) . ' ' . $key . '" class="lang-' . strtolower( $key ) . '" >' . $key . '</span> : ' . $msg['msgstr'] . '<br />';
					}
				}
			}
		}
		return array( $output, $outputsite );

	}

	/**
	 * Import MO file in class MO
	 *
	 *
	 * @since 1.0.2 - only WP >= 2.8.4
	 * @updated 1.0.5 - for wp-net
	 * @param lang
	 * @param $mofile since 1.0.5
	 * @updated 2.1 - local-xx_XX
	 */
	public function pomo_import_mo( $lang = '', $mofile = '', $local = false, $plugin_path = false, $wp_lang_dir = false ) {
		$mo = new MO();

		if ( $plugin_path ) {
			$mofile = $this->path_plugin_file( $plugin_path, $lang, 'mo', $wp_lang_dir ); // 2.10.1
		} else {
			if ( '' == $mofile && true == $local ) {
				$mofile = $this->active_theme_directory . $this->langfolder . 'local-' . $lang . '.mo';
			} elseif ( '' == $mofile ) {
				$mofile = $this->active_theme_directory . $this->langfolder . $lang . '.mo';
			}
		}

		if ( file_exists( $mofile ) ) {
			if ( ! $mo->import_from_file( $mofile ) ) {
				return false;
			} else {
				return $mo;
			}
		} else {
			return false;
		}
	}

	/**
	 * import mo for temporary diff mo files or check if saved
	 *
	 * @since 1.0.6
	 *
	 */
	public function import_mo_file_wpmu( $lang = '', $istheme = true, $local = false ) {
		if ( true == $istheme ) {
			return $this->pomo_import_mo( $lang, '', $local );
		} else {
			global $wpdb;
			//$thesite_ID = $wpdb->blogid;
			if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
				//if ( $thesite_ID > 1) {
				if ( true == $local ) {
					$mofile = $uploads['basedir'] . '/languages/local-' . $lang . '.mo';
				} else {
					$mofile = $uploads['basedir'] . '/languages/' . $lang . '.mo'; //normally inside theme's folder if root wp-net
				}

				return $this->pomo_import_mo( $lang, $mofile, $local );
				//} else {
					//return false; // normally inside theme's folder if root wp-net
				//}
			} else {
				return false;
			}
		}
	}

}