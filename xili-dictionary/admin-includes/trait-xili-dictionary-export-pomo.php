<?php

/**
* XD Admin export cpt pomo functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Export_Pomo {

	/**
	 * convert twinlines (msgid - msgstr) to MOs in wp-net
	 * @since 1.0.4
	 * @updated 2.0
	 * @params as from_twin_to_POMO and $superadmin
	 */
	public function from_cpt_to_pomo_wpmu( $curlang, $obj = 'mo', $superadmin = false, $extract = array() ) {
		global $user_identity,$user_url,$user_email;
		// the table array
		$table_mo = $this->from_cpt_to_pomo( $curlang, $obj, $extract );
		$site_mo = new MO();
		$current_theme_obj = wp_get_theme();
		$translation = '
	Project-Id-Version: theme: ' . $current_theme_obj->get( 'Name' ) . '\n
	Report-Msgid-Bugs-To: contact@xiligroup.com\n
	POT-Creation-Date: ' . date( 'c' ) . '\n
	PO-Revision-Date: ' . date( 'c' ) . '\n
	Last-Translator: ' . $user_identity . ' <' . $user_email . '>\n
	Language-Team: xili-dictionary WP plugin and ' . $user_url . ' <' . $user_email . '>\n
	MIME-Version: 1.0\n
	Content-Type: text/plain; charset=utf-8\n
	Content-Transfer-Encoding: 8bit\n
	Plural-Forms: ' . $this->plural_forms_rule( $curlang ) . ';\n
	X-Poedit-Language: ' . $curlang . '\n
	Language: ' . $curlang . '\n
	X-Generator: xili-dictionary ' . XILIDICTIONARY_VER . '\n
	X-Poedit-Country: ' . $curlang . '\n
	X-Poedit-SourceCharset: utf-8\n';

		$site_mo->set_headers( $site_mo->make_headers( $translation ) );
		// array diff
		if ( false === $superadmin ) {
			// special for superadmin who don't need diff.
			// the pomo array available in theme's folder
			$theme_mo = $this->import_mo_file_wpmu( $curlang, true );
			if ( false !== $theme_mo ) {
				// without keys available in theme' mo
				$site_mo->entries = array_diff_key( $table_mo->entries, $theme_mo->entries ); // those differents ex. categories
				// those with same keys but translations[0] diff
				$diff_mo_trans = array_uintersect_assoc( $table_mo->entries, $theme_mo->entries, array( &$this, 'test_translations' ) );

				$site_mo->entries += $diff_mo_trans;
				//print_r ( array_keys ( $diff_mo_trans ) );

			}
			return $site_mo;
		} elseif ( '' != $extract ) {

			return $table_mo;
		}
	}

	/**
	 * convert cpt (msgid - msgstr) to MO or PO
	 *
	 * @since 2.0
	 *
	 * @updated 2.8.0
	 *
	 */

	public function from_cpt_to_pomo( $curlang, $po_obj = 'mo', $extract = array() ) {
		global $user_identity, $user_url, $user_email;
		if ( 'mo' == $po_obj ) {
			$obj = 'mo';
			$mo = new MO(); /* par default */
		} else {
			$obj = 'po';
			$mo = new PO(); // po or pot - 2.8
		}

		/* header */

		if ( 'pot' == $po_obj ) {
			$translation = '
				Project-Id-Version: ' . $extract['projet_id_version'] . '\n
				Report-Msgid-Bugs-To: contact@xiligroup.com\n
				POT-Creation-Date: ' . date( 'c' ) . '\n
				MIME-Version: 1.0\n
				Content-Type: text/plain; charset=UTF-8\n
				Content-Transfer-Encoding: 8bit\n
				PO-Revision-Date: 2014-MO-DA HO:MI+ZONE\n
				Last-Translator: FULL NAME <EMAIL@ADDRESS>\n
				Language-Team: LANGUAGE <LL@domain-example.org>\n';
		} else {
			$translation = '
				Project-Id-Version: ' . $extract['projet_id_version'] . '\n
				Report-Msgid-Bugs-To: contact@xiligroup.com\n
				POT-Creation-Date: ' . date( 'c' ) . '\n
				PO-Revision-Date: ' . date( 'c' ) . '\n
				Last-Translator: ' . $user_identity . ' <' . $user_email . '>\n
				Language-Team: xili-dictionary WP plugin and ' . $user_url . ' <' . $user_email . '>\n
				MIME-Version: 1.0\n
				Content-Type: text/plain; charset=utf-8\n
				Content-Transfer-Encoding: 8bit\n
				Plural-Forms: ' . $this->plural_forms_rule( $curlang ) . ';\n
				Language: ' . $curlang . '\n
				X-Generator: xili-dictionary ' . XILIDICTIONARY_VER . '\n
				X-Poedit-Language: ' . $curlang . '\n
				X-Poedit-Country: ' . $curlang . '\n
				X-Poedit-SourceCharset: utf-8\n';
		}

		$mo->set_headers( $mo->make_headers( $translation ) );
		/* entries */

		$list_msgids = $this->get_cpt_msgids( $curlang, $obj, $extract ); // msgtype = msgid && $curlang in

		foreach ( $list_msgids as $cur_msgid ) {

			if ( '++' == $cur_msgid->post_content ) {
				continue; // no empty msgid
			}

			$getctxt = get_post_meta( $cur_msgid->ID, $this->ctxt_meta, true );
			$cur_msgid->ctxt = ( '' == $getctxt ) ? false : $getctxt;

			$cur_msgid->plural = false;
			$res = get_post_meta( $cur_msgid->ID, $this->msgchild_meta, false );
			$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : false;

			if ( $thechilds ) {
				if ( isset( $thechilds['msgid']['plural'] ) ) {
					$cur_msgid->plural = true;
					$plural_id = $thechilds['msgid']['plural'];

					$post_child_msgid = get_post( $plural_id );
					$cur_msgid->plural_post_content = $post_child_msgid->post_content;
				}
			}
			// force empty translation if pot...
			$list_msgstr = ( 'pot' == $po_obj ) ? false : $this->get_cpt_msgstr( $cur_msgid->ID, $curlang, $cur_msgid->plural );

			$noentry = true; /* to create po with empty translation */
			if ( false !== $list_msgstr ) {
				if ( 'mo' == $obj ) {
					if ( false === $cur_msgid->plural ) {
						if ( false === $cur_msgid->ctxt ) {
							$original = $cur_msgid->post_content;
						} else {
							$original = $cur_msgid->ctxt . chr( 4 ) . $cur_msgid->post_content;
						}
						$mo->add_entry( $mo->make_entry( $original, $list_msgstr->post_content ) );

					} else {
						$list_msgstr_plural_post_content = array();
						foreach ( $list_msgstr as $one_msgstr ) {
							$list_msgstr_plural_post_content[] = $one_msgstr->post_content;
						}
						if ( false === $cur_msgid->ctxt ) { // PLURAL
							$original = $cur_msgid->post_content . chr( 0 ) . $cur_msgid->plural_post_content;
							$translation = implode( chr( 0 ), $list_msgstr_plural_post_content );
							$mo->add_entry( $mo->make_entry( $original, $translation ) );
						} else {
							// CONTEXT + PLURAL
							$original = $cur_msgid->ctxt . chr( 4 ) . $cur_msgid->post_content . chr( 0 ) . $cur_msgid->plural_post_content;
							$translation = implode( chr( 0 ), $list_msgstr_plural_post_content );
							$mo->add_entry( $mo->make_entry( $original, $translation ) );
						}
					}
				} else { /* po */

					// comments prepare
					// *	- translator_comments (string) -- comments left by translators
					// *	- extracted_comments (string) -- comments left by developers
					// *	- references (array) -- places in the code this strings is used, in relative_to_root_path/file.php:linenum form
					// *	- flags (array) -- flags like php-format

					$comment_array = array(); // $list_msgstr because in msgstr (20120318)

					if ( false === $cur_msgid->plural ) {
						$translator_comments = get_post_meta( $list_msgstr->ID, $this->msg_translator_comments, true );
						if ( '' != $translator_comments ) {
							$comment_array['translator_comments'] = $translator_comments;
						}
					} else {
						$translator_comments = get_post_meta( $list_msgstr[0]->ID, $this->msg_translator_comments, true );
						if ( '' != $translator_comments ) {
							$comment_array['translator_comments'] = $translator_comments;
						}
					}

					$extracted_comments = get_post_meta( $cur_msgid->ID, $this->msg_extracted_comments, true );
					if ( '' != $extracted_comments ) {
						$comment_array['extracted_comments'] = $extracted_comments;
					}
					if ( '' != $cur_msgid->post_excerpt ) {
						$references = explode( '#: ', $cur_msgid->post_excerpt );
						$comment_array['references'] = $references;
					}
					$flags = get_post_meta( $cur_msgid->ID, $this->msg_flags, true );
					if ( '' != $flags ) {
						$comment_array['flags'] = explode( ', ', $flags );
					}

					if ( false === $cur_msgid->plural ) {
						if ( false === $cur_msgid->ctxt ) {
							$entry_array = array(
								'singular' => $cur_msgid->post_content,
								'translations' => array( $list_msgstr->post_content ),
							);
						} else {
							$entry_array = array(
								'context' => $cur_msgid->ctxt,
								'singular' => $cur_msgid->post_content,
								'translations' => array( $list_msgstr->post_content ),
							);
						}
					} else { // PLURAL
						$list_msgstr_plural_post_content = array();
						foreach ( $list_msgstr as $one_msgstr ) {
							$list_msgstr_plural_post_content[] = $one_msgstr->post_content;
						}

						if ( false === $cur_msgid->ctxt ) {
							$entry_array = array(
								'singular' => $cur_msgid->post_content,
								'plural' => $cur_msgid->plural_post_content,
								'is_plural' => 1,
								'translations' => $list_msgstr_plural_post_content,
							);
						} else { // CONTEXT + PLURAL
							$entry_array = array(
								'context' => $cur_msgid->ctxt,
								'singular' => $cur_msgid->post_content,
								'plural' => $cur_msgid->plural_post_content,
								'is_plural' => 1,
								'translations' => $list_msgstr_plural_post_content,
							);
						}
					}
					$entry = new Translation_Entry( array_merge( $entry_array, $comment_array ) );

					$mo->add_entry( $entry );
					$noentry = false;
				}
			}
			/* to create po with empty translations */
			if ( 'po' == $obj && true == $noentry ) { // noentry forced by pot
				$comment_array = array(); // $list_msgstr because in msgstr (20120318)

				$extracted_comments = get_post_meta( $cur_msgid->ID, $this->msg_extracted_comments, true );
				if ( '' != $extracted_comments ) {
					$comment_array['extracted_comments'] = $extracted_comments;
				}
				if ( '' != $cur_msgid->post_excerpt ) {
					$references = explode( '#: ', $cur_msgid->post_excerpt );
					$comment_array['references'] = $references;
				}
				$flags = get_post_meta( $cur_msgid->ID, $this->msg_flags, true );
				if ( '' != $flags ) {
					$comment_array['flags'] = explode( ', ', $flags );
				}

				// 2.8. - improve for po empty and context of plural

				if ( false === $cur_msgid->plural ) {
					if ( false === $cur_msgid->ctxt ) {
						$entry_array = array(
							'singular' => $cur_msgid->post_content,
							'translations' => '',
						);
					} else {
						$entry_array = array(
							'context' => $cur_msgid->ctxt,
							'singular' => $cur_msgid->post_content,
							'translations' => '',
						);
					}
				} else {
					if ( false === $cur_msgid->ctxt ) {
							$entry_array = array(
								'singular' => $cur_msgid->post_content,
								'plural' => $cur_msgid->plural_post_content,
								'is_plural' => 1,
								'translations' => '',
							);
					} else {
						// CONTEXT + PLURAL
						$entry_array = array(
							'context' => $cur_msgid->ctxt,
							'singular' => $cur_msgid->post_content,
							'plural' => $cur_msgid->plural_post_content,
							'is_plural' => 1,
							'translations' => '',
						);
					}
				}
				$entry = new Translation_Entry( array_merge( $entry_array, $comment_array ) );

				$mo->add_entry( $entry );
			}
		}
		return $mo;
	}

	/**
	 * Save MO object to file
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 * @updated 1.0.5 - wp-net
	 *
	 * @updated 2.1
	 */
	public function save_mo_to_file( $curlang, $mo, $createfile = '' ) {
		$filename = $this->from_slug_to_wp_locale( $curlang );
		$filename .= '.mo';
		if ( '' == $createfile ) {
			$createfile = $this->active_theme_directory . $this->langfolder . $filename;
		}
		//echo $createfile;
		if ( false === $mo->export_to_file( $createfile ) ) {
			return false;
		}
	}

	/**
	 * Save PO object to file
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 *
	 * @updated 2.1
	 */
	public function save_po_to_file( $curlang, $po, $createfile = '' ) {
		$filename = $this->from_slug_to_wp_locale( $curlang );
		$filename .= '.po';
		if ( '' == $createfile ) {
			$createfile = $this->active_theme_directory . $this->langfolder . $filename;
		}
		xili_xd_error_log( '# ' . __LINE__ . ' ---- XD po file ------- ' . $createfile );
		if ( false === $po->export_to_file( $createfile ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * thanks to http://urbangiraffe.com/articles/translating-wordpress-themes-and-plugins/2/#plural_forms
	 * @since 1.0 - only WP >= 2.8
	 * @since 2.13 Gp_locales
	 *
	 * called when creating po
	 */
	public function plural_forms_rule( $curlang ) {
		$wp_locale = $this->from_slug_to_wp_locale( $curlang );

		if ( class_exists( 'GP_Locales' ) ) { // rules from JetPack or xili-language

			$locale = GP_Locales::by_field( 'wp_locale', $wp_locale );
			if ( $locale ) {
				return 'nplurals=' . $locale->nplurals . '; plural=' . $locale->plural_expression;
			} else {
				return 'nplurals=2; plural=n != 1'; // by default
			}
		} else { // old rules

			$rulesarrays = array(
				'nplurals=1; plural=0' => array( 'tr_TR', 'ja_JA', 'ja' ),
				'nplurals=2; plural=1' => array( 'zh_ZH' ),
				'nplurals=2; plural=n != 1' => array( 'en_US', 'en_UK', 'es_ES', 'da_DA' ),
				'nplurals=2; plural=n>1' => array( 'fr_FR', 'fr_CA', 'fr_BE', 'pt_BR' ),
				'nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2' => array( 'lv_LV' ),
				'nplurals=3; plural=n==1 ? 0 : n==2 ? 1 : 2' => array( 'gd_GD' ),
				'nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2' => array( 'lt_LT' ),
				'nplurals=3; plural=n%100/10==1 ? 2 : n%10==1 ? 0 : (n+9)%10>3 ? 2 : 1' => array( 'hr_HR', 'cs_CS', 'ru_RU', 'uk_UK' ),
				'nplurals=3; plural=(n==1) ? 1 : (n>=2 && n<=4) ? 2 : 0' => array( 'sk_SK' ),
				'nplurals=3; plural=n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2' => array( 'pl_PL' ),
				'nplurals=4; plural=n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3' => array( 'sl_SL' ),
			);
			foreach ( $rulesarrays as $rule => $langs ) {
				if ( in_array( $wp_locale, $langs ) ) {
					return $rule;
				}
			}
			return 'nplurals=2; plural=n != 1'; /* english and most... */
		}
	}

	/**
	 * xili language slug to wp_locale (iso) - set country to uppercase
	 * @since 2.13
	 *
	 */
	public function from_slug_to_wp_locale( $lang_slug ) {
		$parts = explode( '_', $lang_slug );
		if ( isset( $parts[1] ) ) {
			return $parts[0] . '_' . strtoupper( $parts[1] );
		} else {
			return $lang_slug;
		}
	}

	public function test_translations( $table, $theme ) {
		if ( $table->translations[0] != $theme->translations[0] ) {
			if ( $table->singular == $theme->singular ) {
				//echo '--tQuote--not' . $table->translations[0];
				return 0;

			} else {
				return 1;
			}
		}
		if ( $table->singular > $theme->singular ) {
			return 1;
		}
		return -1;
	}


}
