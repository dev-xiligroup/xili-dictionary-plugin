<?php
/**
* XD Admin import msg from core and more
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Msg_Import {


	/**
	 * bloginfo term and others in cpt
	 * @since 2.0
	 *
	 */
	public function xili_import_infosterms_cpt() {
		global $wp_version;
		$curlang = get_locale(); // admin language of config - import id and str

		$msg_counters = array( 0, 0, 0 ); // to import, imported, msgstr
		$terms_to_import = array();
		$temp = array();
		$temp['msgid'] = get_bloginfo( 'blogname', 'display' );
		$temp['extracted_comments'] = $this->local_tag . ' bloginfo - blogname';
		$terms_to_import[] = $temp;
		$temp['msgid'] = get_bloginfo( 'description', 'display' );
		$temp['extracted_comments'] = $this->local_tag . ' bloginfo - description';
		$terms_to_import[] = $temp;
		$temp['msgid'] = addslashes( get_option( 'time_format' ) );
		$temp['extracted_comments'] = $this->local_tag . ' bloginfo - time_format';
		$terms_to_import[] = $temp;
		$temp['msgid'] = addslashes( get_option( 'date_format' ) );
		$temp['extracted_comments'] = $this->local_tag . ' bloginfo - date_format';
		$terms_to_import[] = $temp;

		if ( class_exists( 'xili_language' ) ) {
			global $xili_language;
			foreach ( $xili_language->comment_form_labels as $key => $label ) {
				$temp = array();
				$temp['msgid'] = $label;
				if ( 'comment' == $key ) {
					$temp['ctxt'] = 'noun'; // 2.4.0
				}
				$temp['extracted_comments'] = $this->local_tag . ' comment_form_labels ' . $key;
				if ( 'en_US' != $curlang ) {
					$temp['msgstr'] = $this->default_translate_no_plural( $temp );
				}
				$terms_to_import[] = $temp;
			}

			// added 2.10.0 to extract comment default msgid excluding comment_form_labels
			$from_file_count = $this->import_msgid_from_one_wp_file( ABSPATH . WPINC, 'comment-template', $xili_language->comment_form_labels, true, true ); // local + msgstr
			$msg_counters[1] = $from_file_count;
			$msg_counters[0] += $from_file_count;

			// added 2.11.2 from post-template.php (password and private test)
			$temp = array();
			$temp[0]['msgid'] = 'Protected: %s';
			$temp[1]['msgid'] = 'Private: %s';
			$temp[2]['msgid'] = '(more&hellip;)';
			$temp[3]['msgid'] = 'There is no excerpt because this is a protected post.';
			$temp[4]['msgid'] = 'Pages:';
			$temp[5]['msgid'] = 'Next page';
			$temp[6]['msgid'] = 'Previous page';
			$temp[7]['msgid'] = 'This content is password protected. To view it please enter your password below:';
			$temp[8]['msgid'] = 'Password:';
			$temp[9]['msgid'] = 'Submit';

			foreach ( $temp as $oneline ) {
				$oneline['msgstr'] = '';
				if ( 'en_US' != $curlang ) {
					$oneline['msgstr'] = $this->default_translate_no_plural( $oneline );
				}
				$oneline['extracted_comments'] = $this->local_tag . ' post-template';
				$terms_to_import[] = $oneline;
			}

			// language description
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
			foreach ( $listlanguages as $reflanguage ) { // 2.1
				$temp = array();
				$temp['msgid'] = $reflanguage->description;
				$temp['extracted_comments'] = $this->local_tag . ' language with ISO ' . $reflanguage->name;
				$terms_to_import[] = $temp;
				$temp = array();
				$temp['msgid'] = $reflanguage->description;
				$temp['ctxt'] = 'linktitle';
				$temp['extracted_comments'] = $this->local_tag . ' language with ISO ' . $reflanguage->name . ' ctxt=linktitle';
				$terms_to_import[] = $temp;
				$temp = array();
				$temp['msgid'] = $reflanguage->description;
				$temp['ctxt'] = 'otherposts';
				$temp['extracted_comments'] = $this->local_tag . ' language with ISO ' . $reflanguage->name . ' ctxt=otherposts';
				$terms_to_import[] = $temp;
				$temp = array();
				$temp['msgid'] = $reflanguage->description;
				$temp['ctxt'] = 'searchform';
				$temp['extracted_comments'] = $this->local_tag . ' language with ISO ' . $reflanguage->name . ' ctxt=searchform';
				$terms_to_import[] = $temp;
			}

			if ( version_compare( XILILANGUAGE_VER, '2.3.9', '>' ) ) { // msgid and msgstr
				global $wp_locale;
				$wp_locale_array_trans = array(
					'Sunday' => $wp_locale->weekday[0],
					'Monday' => $wp_locale->weekday[1],
					'Tuesday' => $wp_locale->weekday[2],
					'Wednesday' => $wp_locale->weekday[3],
					'Thursday' => $wp_locale->weekday[4],
					'Friday' => $wp_locale->weekday[5],
					'Saturday' => $wp_locale->weekday[6],
					'S_Sunday_initial' => $wp_locale->weekday_initial[ $wp_locale->weekday[0] ],
					'M_Monday_initial' => $wp_locale->weekday_initial[ $wp_locale->weekday[1] ],
					'T_Tuesday_initial' => $wp_locale->weekday_initial[ $wp_locale->weekday[2] ],
					'W_Wednesday_initial' => $wp_locale->weekday_initial[ $wp_locale->weekday[3] ],
					'T_Thursday_initial' => $wp_locale->weekday_initial[ $wp_locale->weekday[4] ],
					'F_Friday_initial' => $wp_locale->weekday_initial[ $wp_locale->weekday[5] ],
					'S_Saturday_initial' => $wp_locale->weekday_initial[ $wp_locale->weekday[6] ],
					'Sun' => $wp_locale->weekday_abbrev[ $wp_locale->weekday[0] ],
					'Mon' => $wp_locale->weekday_abbrev[ $wp_locale->weekday[1] ],
					'Tue' => $wp_locale->weekday_abbrev[ $wp_locale->weekday[2] ],
					'Wed' => $wp_locale->weekday_abbrev[ $wp_locale->weekday[3] ],
					'Thu' => $wp_locale->weekday_abbrev[ $wp_locale->weekday[4] ],
					'Fri' => $wp_locale->weekday_abbrev[ $wp_locale->weekday[5] ],
					'Sat' => $wp_locale->weekday_abbrev[ $wp_locale->weekday[6] ],
					'January' => $wp_locale->month['01'],
					'February' => $wp_locale->month['02'],
					'March' => $wp_locale->month['03'],
					'April' => $wp_locale->month['04'],
					'May' => $wp_locale->month['05'],
					'June' => $wp_locale->month['06'],
					'July' => $wp_locale->month['07'],
					'August' => $wp_locale->month['08'],
					'September' => $wp_locale->month['09'],
					'October' => $wp_locale->month['10'],
					'November' => $wp_locale->month['11'],
					'December' => $wp_locale->month['12'],
					'Jan_January_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['01'] ],
					'Feb_February_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['02'] ],
					'Mar_March_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['03'] ],
					'Apr_April_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['04'] ],
					'May_May_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['05'] ],
					'Jun_June_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['06'] ],
					'Jul_July_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['07'] ],
					'Aug_August_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['08'] ],
					'Sep_September_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['09'] ],
					'Oct_October_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['10'] ],
					'Nov_November_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['11'] ],
					'Dec_December_abbreviation' => $wp_locale->month_abbrev[ $wp_locale->month['12'] ],
					'am' => $wp_locale->meridiem['am'],
					'pm' => $wp_locale->meridiem['pm'],
					'AM' => $wp_locale->meridiem['AM'],
					'PM' => $wp_locale->meridiem['PM'],
					'number_format_thousands_sep' => $wp_locale->number_format['thousands_sep'],
					'number_format_decimal_point' => $wp_locale->number_format['decimal_point'],
				);
				if ( isset( $wp_locale->text_direction ) ) {
					$wp_locale_array_trans['text_direction'] = $wp_locale->text_direction; //_x( 'ltr', 'text direction', $theme_domain ) ) ) )
				}

				$temp = array();
				foreach ( $wp_locale_array_trans as $key => $value ) {
					$temp['msgid'] = $key;
					if ( 'en_US' != $curlang ) {
						$temp['msgstr'] = $value;
					}
					if ( 'text_direction' == $key ) {
						$temp['ctxt'] = 'text direction';
					}
					$temp['extracted_comments'] = $this->local_tag . ' wp_locale ' . $key;
					$terms_to_import[] = $temp;
				}
			}
			if ( version_compare( XILILANGUAGE_VER, '2.8.6', '>' ) ) { // since 2.8.7
				if ( isset( $xili_language->xili_settings['list_link_title'] ) && array() != $xili_language->xili_settings['list_link_title'] ) {
					$temp = array();
					foreach ( $xili_language->xili_settings['list_link_title'] as $key => $title ) {

						$temp['msgid'] = $title;
						$temp['extracted_comments'] = $this->local_tag . ' language list title ' . $key;
						$terms_to_import[] = $temp;

					}
				}
			}

			if ( version_compare( $wp_version, '4.0', '>' ) ) { //2.10.3
				$temp = array();
				// msgi used in fonction get_the_archive_title()
				$temp[0]['msgid'] = 'Category: %s';
				$temp[1]['msgid'] = 'Tag: %s';
				$temp[2]['msgid'] = 'Author: %s';
				$temp[3]['msgid'] = 'Year: %s';
				$temp[4]['msgid'] = 'Y';
				$temp[4]['ctxt'] = 'yearly archives date format';
				$temp[5]['msgid'] = 'Month: %s';
				$temp[6]['msgid'] = 'F Y';
				$temp[6]['ctxt'] = 'monthly archives date format';
				$temp[7]['msgid'] = 'Day: %s';
				$temp[8]['msgid'] = 'F j, Y';
				$temp[8]['ctxt'] = 'daily archives date format';
				$temp[9]['msgid'] = 'Asides';
				$temp[9]['ctxt'] = 'post format archive title';
				$temp[10]['msgid'] = 'Galleries';
				$temp[10]['ctxt'] = 'post format archive title';
				$temp[11]['msgid'] = 'Images';
				$temp[11]['ctxt'] = 'post format archive title';
				$temp[12]['msgid'] = 'Videos';
				$temp[12]['ctxt'] = 'post format archive title';
				$temp[13]['msgid'] = 'Quotes';
				$temp[13]['ctxt'] = 'post format archive title';
				$temp[14]['msgid'] = 'Links';
				$temp[14]['ctxt'] = 'post format archive title';
				$temp[15]['msgid'] = 'Statuses';
				$temp[15]['ctxt'] = 'post format archive title';
				$temp[16]['msgid'] = 'Audio';
				$temp[16]['ctxt'] = 'post format archive title';
				$temp[17]['msgid'] = 'Chats';
				$temp[17]['ctxt'] = 'post format archive title';
				$temp[18]['msgid'] = 'Archives: %s';
				$temp[19]['msgid'] = '%1$s: %2$s';

				// search form - general template

				$temp[20]['msgid'] = 'Search for:';
				$temp[20]['ctxt'] = 'label';

				$temp[21]['msgid'] = 'Search';
				$temp[21]['ctxt'] = 'submit button';

				$temp[22]['msgid'] = 'Search &hellip;';
				$temp[22]['ctxt'] = 'placeholder';

				foreach ( $temp as $oneline ) {
					$oneline['msgstr'] = '';
					if ( 'en_US' != $curlang ) {
						$oneline['msgstr'] = $this->default_translate_no_plural( $oneline );
					}
					$oneline['extracted_comments'] = $this->local_tag . ' get_the_archive_title';
					$terms_to_import[] = $oneline;
				}
			}
		}

		if ( isset( $_POST['xmlimport'] ) ) {
			$to_be_filtered = $this->get_xml_contents();

			if ( $to_be_filtered ) {
				$theme_slug = get_option( 'stylesheet' );
				$oneline = array();
				foreach ( $to_be_filtered[ $theme_slug ] as $config_name ) {
					//$filtername = 'theme_mod_' . $config_name;
					$value = get_theme_mod( $config_name, false );
					if ( $value && is_string( $value ) ) {
						$oneline['msgid'] = $value;
						$oneline['extracted_comments'] = $this->local_tag . ' theme_mod_' . $config_name;
						$terms_to_import[] = $oneline;
					}
				}
			}
		}

		// shortcode [linked-post-in lang="fr_fr"]Voir cet article[/linked-post-in] - XL 2.18.2
		$oneline = array();
		$oneline['msgid'] = 'A similar post in %s';
		$oneline['ctxt'] = 'linktitle'; // default context
		$oneline['extracted_comments'] = $this->local_tag . ' shortcode_linked-post-in';
		$terms_to_import[] = $oneline;

		// import Widget titles and texts - 2.12.2
		global $wp_registered_widgets;
		$sidebars = wp_get_sidebars_widgets();
		foreach ( $sidebars as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' == $sidebar || empty( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $widget ) {
				// nothing can be done if the widget is created using pre WP2.8 API :( - as Fred says !
				// there is no object, so we can't access it to get the widget options
				if ( ! isset( $wp_registered_widgets[ $widget ]['callback'][0] ) || ! is_object( $wp_registered_widgets[ $widget ]['callback'][0] ) || ! method_exists( $wp_registered_widgets[ $widget ]['callback'][0], 'get_settings' ) ) {
					continue;
				}

				$widget_settings = $wp_registered_widgets[ $widget ]['callback'][0]->get_settings();
				$number = $wp_registered_widgets[ $widget ]['params'][0]['number'];

				$item_array = apply_filters( 'widget_text_items', array( 'title', 'text' ) ); // filter to add item from other plugins

				if ( $item_array ) {
					foreach ( $item_array as $item ) {
						if ( $item ) {
							$oneline = array();
							if ( ! empty( $widget_settings[ $number ][ $item ] ) ) {
								$oneline['msgid'] = $widget_settings[ $number ][ $item ];
								$oneline['extracted_comments'] = $this->local_tag . ' widget_' . $item;
								$terms_to_import[] = $oneline;
							}
						}
					}
				}
			}
		}

		// finally import the series...
		$this->importing_mode = true;
		$msg_counters[0] += count( $terms_to_import );
		foreach ( $terms_to_import as $term ) {

			$msg_counter = $this->one_term_in_cpt_xdmsg( $term, $curlang ); // 2.12.2
			if ( $msg_counter[1] ) {
				$msg_counters[1]++;
			}
			if ( $msg_counter[2] ) {
				$msg_counters[2]++;
			}
		}
		$this->importing_mode = false;
		return $msg_counters;

	}

	/**
	 * add default translate
	 *
	 */
	public function default_translate_no_plural( $oneline ) {
		$line = array();
		$is_plural = false;
		$line = $oneline;

		if ( isset( $line['ctxt'] ) ) {
			return translate_with_gettext_context( $line['msgid'], $line['ctxt'] );
		} else {
			return translate( $line['msgid'] );
		}
	}

	/**
	 * import one msg and translation in dictionary
	 * called by importing source terms - xili_import_infosterms_cpt
	 *
	 * @since 2.12
	 * @param entry as array
	 *
	 */
	public function one_term_in_cpt_xdmsg( $term, $curlang ) {
		$the_context = null;
		$msg_counter = array( 0, 0, 0 );
		if ( 'text_direction' == $term['msgid'] ) {
			$the_context = 'text direction';
		}
		if ( isset( $term['ctxt'] ) ) { // 2.3.6
			$the_context = $term['ctxt'];
		}

		$result = $this->msgid_exists( $term['msgid'], $the_context );

		$t_entry = array();
		$t_entry['extracted_comments'] = $term['extracted_comments'];
		$entry = (object) $t_entry;

		if ( false === $result ) {
			// create the msgid

			$msgid_post_id = $this->insert_one_cpt_and_meta( $term['msgid'], $the_context, 'msgid', 0, $entry );
			$msg_counter[1]++;
		} else {
			$msgid_post_id = $result[0];
		}

		if ( isset( $term['msgstr'] ) && '' != $term['msgstr'] ) {
			// now insert msgstr if exists

			$value = $term['msgstr'];
			$result = $this->msgstr_exists( $value, $msgid_post_id, $curlang ); // with lang of default (admin side)

			if ( false === $result ) {
				$msgstr_post_id = $this->insert_one_cpt_and_meta( $value, $the_context, 'msgstr', 0, $entry );
				$msg_counter[2]++;
				wp_set_object_terms( $msgstr_post_id, $this->target_lang( $curlang ), TAXONAME );
			} else {
				$msgstr_post_id = $result[0];
			}

			// create link according lang

			$res = get_post_meta( $msgid_post_id, $this->msglang_meta, false );
			$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
			$thelangs['msgstrlangs'][ $curlang ]['msgstr'] = $msgstr_post_id;
			update_post_meta( $msgid_post_id, $this->msglang_meta, $thelangs );
			update_post_meta( $msgstr_post_id, $this->msgidlang_meta, $msgid_post_id );

		}
		return $msg_counter;
	}

	/**
	 * Used to import msgid from one specific file
	 *
	 *
	 * @since 2.10.0
	 */
	public function import_msgid_from_one_wp_file( $path, $file, $entries_to_exclude, $local = true, $add_msgstr = false ) {
		global $wp_version;
		$curlang = get_locale(); // admin language of config - import id and str
		$this->get_list_languages(); // to prepare from_entries_to_xdmsg
		$entries = false;
		$the_file_project = array(
			'title'    => sprintf( 'File %s from WP version %s generated by ©xili-dictionary', $file, $wp_version ),
			'file'     => str_replace( '//', '/', $path . '/' . $file . '.pot' ),
			'excludes' => array(),
			'includes' => array( $file . '.php' ),
			'working_path' => $path,
		);
		$xd_extractor = new XD_extractor( array( $path . '/' . $file => $the_file_project ) );
		$entries = $xd_extractor->generate_entries( $path . '/' . $file );
		// reduce with form content managed by xl
		if ( 'comment-template' == $file ) {

			foreach ( $entries_to_exclude as $key => $value ) {
				if ( 'Comment' == $value ) {
					$value = 'noun' . chr( 4 ) . 'Comment'; // backward compat
				}
				if ( array_key_exists( $value, $entries->entries ) ) {
					unset( $entries->entries[ $value ] );
				}
			}
		}
		if ( $local ) {
			foreach ( $entries->entries as $key => &$entry ) {
				$entry->extracted_comments = $this->local_tag . ' ' . $entry->extracted_comments;
				if ( $add_msgstr ) {
					if ( 'en_US' != $curlang ) {
						if ( $entry->is_plural ) {
							$the_translations = get_translations_for_domain( 'default' );
							$entry->translations[0] = $the_translations->translate_plural( $entry->singular, $entry->plural, 1 );
							$entry->translations[1] = $the_translations->translate_plural( $entry->singular, $entry->plural, 2 );
						} else {
							if ( $entry->context ) {
								$entry->translations[0] = translate_with_gettext_context( $entry->singular, $entry->context );
							} else {
								$entry->translations[0] = translate( $entry->singular );
							}
						}
					}
				}
			}
		}

		$lines = $this->from_entries_to_xdmsg( $entries, $file, false, $curlang );

		unset( $xd_extractor );
		return $lines;
	}

	public function start_detect_plugin_msg() {
		$this->domain_to_detect_list = get_option( 'xd_test_importation_list', array() );
	}

	public function detect_plugin_frontent_msg( $translation, $text, $domain ) {
		global $locale;
		$domain_to_detect = get_option( 'xd_test_importation', false );

		if ( $domain_to_detect && $domain == $domain_to_detect && isset( $this->domain_to_detect_list ) ) {
			if ( ! isset( $this->domain_to_detect_list[ $locale ] )
				|| ! in_array(
					array(
						'msgid' => $text,
						'msgstr' => $translation,
					),
					$this->domain_to_detect_list[ $locale ]
				)
			) {
				$this->domain_to_detect_list[ $locale ][] = array(
					'msgid' => $text,
					'msgstr' => $translation,
				);
			}
		}

		return $translation;
	}

	public function end_detect_plugin_msg() {
		if ( isset( $this->domain_to_detect_list ) ) {
			update_option( 'xd_test_importation_list', $this->domain_to_detect_list );
		}
	}

	public function import_plugin_collected_msgs( $locale ) {

		$collected_msgs = get_option( 'xd_test_importation_list' );

		if ( isset( $collected_msgs[ $locale ] ) && is_array( $collected_msgs[ $locale ] ) && array() != $collected_msgs[ $locale ] ) {

			$the_context = null;

			foreach ( $collected_msgs[ $locale ] as $oneline ) {

				$t_entry = array();
				$t_entry['extracted_comments'] = $this->local_tag . ' plugin ';

				$entry = (object) $t_entry;

				$result = $this->msgid_exists( $oneline['msgid'], $the_context );

				if ( false === $result ) {
					// create the msgid

					$msgid_post_id = $this->insert_one_cpt_and_meta( $oneline['msgid'], $the_context, 'msgid', 0, $entry );

				} else {
					$msgid_post_id = $result[0];

				}

				$result = $this->msgstr_exists( $oneline['msgstr'], $msgid_post_id, $locale );
				if ( false === $result ) {
					$msgstr_post_id = $this->insert_one_cpt_and_meta( $oneline['msgstr'], $the_context, 'msgstr', 0, $entry );

					wp_set_object_terms( $msgstr_post_id, $this->target_lang( $locale ), TAXONAME );
				} else {
					$msgstr_post_id = $result[0];
				}

				// create link according lang

				$res = get_post_meta( $msgid_post_id, $this->msglang_meta, false );
				$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
				$thelangs['msgstrlangs'][ $locale ]['msgstr'] = $msgstr_post_id;
				update_post_meta( $msgid_post_id, $this->msglang_meta, $thelangs );
				update_post_meta( $msgstr_post_id, $this->msgidlang_meta, $msgid_post_id );

			}

			$nbterms = count( $collected_msgs[ $locale ] );

		} else {

			$nbterms = 0;
		}
		return $nbterms;
	}

}

