<?php
/**
* XD Admin Settings
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Settings {

	/**
	 * Dashboard - Manage - Dictionary
	 *
	 * @since 0.9
	 * @updated 2.0
	 *
	 */
	public function xili_dictionary_settings() {
		global $wp_version;

		$action = '';
		$emessage = ''; // email
		$term_id = 0;
		$formtitle = esc_html__( 'Dialog box', 'xili-dictionary' );
		$formhow = ' ';
		$submit_text = esc_html__( 'Do &raquo;', 'xili-dictionary' );
		$cancel_text = esc_html__( 'Cancel' );

		$tagsnamelike = ''; // not yet used
		$selecttaxonomy = '';

		//$tagsnamelike = ( isset( $_POST['tagsnamelike'] ) ) ? $_POST['tagsnamelike'] : '';
		//if ( isset( $_GET['tagsnamelike'] ) )
			//$tagsnamelike = $_GET['tagsnamelike']; /* if link from table */
		$tagsnamesearch = ( isset( $_POST['tagsnamesearch'] ) ) ? sanitize_text_field( wp_unslash($_POST['tagsnamesearch'])) : '';
		if ( isset( $_GET['tagsnamesearch'] ) ) {
			$tagsnamesearch = sanitize_text_field( wp_unslash($_GET['tagsnamesearch']));
		}

		if ( isset( $_POST['reset'] ) ) {
			$action = $_POST['reset'];

		} elseif ( isset( $_POST['sendmail'] ) ) {
			//2.3.2
			$action = 'sendmail';

		} elseif ( isset( $_POST['setcapedit'] ) ) {
			$action = 'setcapedit';

		} elseif ( isset( $_POST['action'] ) ) {
			$action = $_POST['action']; // hidden input by default

		} elseif ( isset( $_GET['action'] ) ) {
			$action = $_GET['action'];
		}
		/* language delete or add */
		if ( isset( $_POST['lang_delete'] ) ) {
			$action = 'lang_delete';
		}
		if ( isset( $_POST['lang_add'] ) ) {
			$action = 'lang_add';
		}
		/* sub lists */
		if ( isset( $_POST['notagssublist'] ) ) {
			$action = 'notagssublist';
		}

		if ( isset( $_POST['tagssublist'] ) ) {
			$action = 'tagssublist';
		}
		if ( isset( $_GET['orderby'] ) ) :
			$orderby = $_GET['orderby'];
		else :
			$orderby = 't.term_id'; /* 0.9.8 */
		endif;
		if ( isset( $_POST['tagsgroup_parent_select'] ) && 'no_select' != $_POST['tagsgroup_parent_select'] ) {
			$this->subselect = $_POST['tagsgroup_parent_select'];
		} else {
			$this->subselect = '';
		}
		if ( isset( $_GET['tagsgroup_parent_select'] ) ) {
			$this->subselect = $_GET['tagsgroup_parent_select'];
		}

		if ( isset( $_POST['subselection'] ) ) {
			$action = 'subselection';
		}

		$cur_theme_name = $this->get_option_theme_name();

		$message = ''; //$action." = ";
		$msg = 0;

		switch ( $action ) {

			case 'setcapedit':
				$this->xili_settings['editor_caps'] = $_POST['editor_caps'];
				update_option( 'xili_dictionary_settings', $this->xili_settings );
				$actiontype = 'add';
				$message .= ' Editor role updated';
				break;
			case 'setstyle':
				// external xd-style.css
				check_admin_referer( 'xdsetstyle' );
				if ( isset( $_GET['what'] ) ) {
					$what = 'off';
					if ( 'on' == $_GET['what'] ) {
						$what = 'on';
					} elseif ( 'off' == $_GET['what'] ) {
						$what = 'off';
					}
					$this->xili_settings['external_xd_style'] = $what;
					update_option( 'xili_dictionary_settings', $this->xili_settings );
				}

				$actiontype = 'add';
				break;

			case 'lang_delete':
				$reflang = $_POST['langs_list'];
				$wp_lang = ( $this->get_wplang() ) ? strtolower( $this->get_wplang() ) : 'en_us';
				if ( 'no_select' != $reflang && 'en_us' != $reflang && $reflang != $wp_lang ) {
					$ids = term_exists( $reflang, TAXONAME );
					if ( $ids ) {
						if ( is_wp_error( $ids ) ) {
							$message .= ' ' . $reflang . ' error';
						} else {
							$t_id = $ids['term_id'];
							wp_delete_term( $t_id, TAXONAME );
							$message .= ' ' . $reflang . ' deleted';
						}
					} else {
						$message .= ' ' . $reflang . ' not exist';
					}
				} else {
					$message .= ' nothing to delete';
				}

				$actiontype = 'add';
				break;

			case 'lang_add':
				$reflang = ( '' != $_POST['lang_ISO'] ) ? $_POST['lang_ISO'] : '???';
				$reflangname = ( '' != $_POST['lang_name'] ) ? $_POST['lang_name'] : $reflang;
				if ( '???' != $reflang && ( ( 5 == strlen( $reflang ) && substr( '_' == $reflang, 2, 1 ) ) ) || ( 2 == strlen( $reflang ) ) ) {

					$args = array(
						'alias_of' => '',
						'description' => $reflangname,
						'parent' => 0,
						'slug' => strtolower( $reflang ),
					);
					$theids = $this->safe_lang_term_creation( $reflang, $args );
					if ( ! is_wp_error( $theids ) ) {
						wp_set_object_terms( $theids['term_id'], 'the-langs-group', TAXOLANGSGROUP );
					}
					$message .= ' ' . $reflang . $msg;
				} else {
					$message .= ' error ( ' . $reflang . ' ) ! no add';
				}

				$actiontype = 'add';
				break;

			case 'subselection':
				//$tagsnamelike = $_POST['tagsnamelike'];
				$tagsnamesearch = sanitize_text_field( wp_unslash($_POST['tagsnamesearch']));
				$message .= ' selection of ' . $_POST['tagsgroup_parent_select'];
				$actiontype = 'add';
				break;

			case 'notagssublist':
				$tagsnamelike = '';
				$tagsnamesearch = '';
				$message .= ' no sub list of msg';
				$actiontype = 'add';
				break;

			case 'tagssublist':
				//$message .= ' sub list of terms starting with ' . $_POST['tagsnamelike'];
				$actiontype = 'add';
				break;

			case 'export':
				$actiontype = 'exporting';
				$formtitle = esc_html__( 'Build mo file', 'xili-dictionary' );
				/* translators: */
				$formhow = sprintf( esc_html__( 'To build a %s file, choose language, destination (current theme or plugin) and origin.', 'xili-dictionary' ), '.mo' );
				/* translators: */
				$submit_text = sprintf( esc_html__( 'Build %s file &raquo;', 'xili-dictionary' ), '.mo' );
				break;

			case 'exporting':
				// MO
				check_admin_referer( 'xilidicoptions' );
				$actiontype = 'add';
				$selectlang = $_POST['language_file'];
				if ( '' != $selectlang ) {
					//$this->xili_create_mo_file(strtolower( $selectlang) );
					$file = '';
					$extract_array = array();
					$checked_themes = $this->checked_themes_array();

					if ( 'targettheme' == $_POST['target'] ) {

						if ( is_multisite() ) { /* complete theme's language with db structure languages (cats, desc,…) in uploads */
							//global $wpdb;
							//$thesite_ID = $wpdb->blogid;
							$superadmin = ( isset( $_POST['only-theme'] ) && 'theme-dir' == $_POST['only-theme'] ) ? true : false;
							$message .= ( isset( $_POST['only-theme'] ) && 'theme-dir' == $_POST['only-theme'] ) ? '- exported only in theme - ' : '- exported in uploads - ';

							if ( ( $uploads = xili_upload_dir() ) && false === $uploads['error'] ) {

								if ( true === $superadmin ) {
									if ( 'local' == $_POST['only-local'] ) {
										$local = 'local';
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = true;
										$file = $this->active_theme_directory . $this->langfolder . 'local-' . $selectlang . '.mo';
									} elseif ( 'wolocal' == $_POST['only-local'] ) {
										$extract_array['origin'] = $checked_themes;
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = false;
										$local = '';
										$file = '';
									} else {
										$extract_array ['origin'] = $checked_themes;
										$local = '';
										$file = '';
									}
								} else {
									if ( 'local' == $_POST['only-local'] ) {
										$local = 'local';
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = true;
										$file = $uploads['path'] . '/local-' . $selectlang . '.mo';

									} elseif ( 'wolocal' == $_POST['only-local'] ) {
										$extract_array['origin'] = $checked_themes;
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = false; // no local

										$file = $uploads['path'] . '/' . $selectlang . '.mo';
									} else {
										$extract_array['origin'] = $checked_themes;
										$local = '';
										$file = $uploads['path'] . '/' . $selectlang . '.mo';
									}
								}
									$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name;
									$mo = $this->from_cpt_to_pomo_wpmu( $selectlang, 'mo', $superadmin, $extract_array ); // do diff if not superadmin
							}
						} else {
							// not multisite

							if ( 'local' == $_POST['only-local'] ) {
								$local = 'local';
								$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
								$extract_array [ 'like-' . $this->msg_extracted_comments ] = true;
								if ( 'parenttheme' == $_POST['languagesfolder'] ) {
									$file = get_template_directory() . $this->parentlangfolder . 'local-' . $selectlang . '.mo'; // not used if child by xili-language
								} else {
									if ( 'contentlanguages' == $_POST['languagesfolder'] ) {
										$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . '-local-' . $selectlang . '.mo';
									} else {
										$file = $this->active_theme_directory . $this->langfolder . 'local-' . $selectlang . '.mo';
									}
								}
							} elseif ( 'wolocal' == $_POST['only-local'] ) {
								$extract_array['origin'] = $checked_themes;
								$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
								$extract_array[ 'like-' . $this->msg_extracted_comments ] = false; // no local
								$local = '';
								if ( 'parenttheme' == $_POST['languagesfolder'] ) {
									$file = get_template_directory() . $this->parentlangfolder . $selectlang . '.mo';
								} else {
									if ( 'contentlanguages' == $_POST['languagesfolder'] ) {
										$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . '-' . $selectlang . '.mo';
									} else {
										$file = $this->active_theme_directory . $this->langfolder . $selectlang . '.mo';
									}
								}
							} else {

								$extract_array['origin'] = $checked_themes;
								$local = '';
								if ( 'parenttheme' == $_POST['languagesfolder'] ) {
									$file = get_template_directory() . $this->parentlangfolder . $selectlang . '.mo';
								} else {
									if ( 'contentlanguages' == $_POST['languagesfolder'] ) {
										$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . '-' . $selectlang . '.mo';
									} else {
										$file = $this->active_theme_directory . $this->langfolder . $selectlang . '.mo';
									}
								}
							}
							$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name;
							$mo = $this->from_cpt_to_pomo( $selectlang, 'mo', $extract_array );
						}
					} else { // target plugin

						if ( array() != $checked_themes ) {
							$extract_array['origin'] = $checked_themes;
							$extract_array['projet_id_version'] = 'plugin = ' . $checked_themes[0];
							$mo = $this->from_cpt_to_pomo( $selectlang, 'mo', $extract_array );
							$file = $this->path_plugin_file( $checked_themes[0], $selectlang, 'mo', ( 'targetpluginwplang' == $_POST['target'] ) ); // 2.10.1
						}
					}

					if ( isset( $mo ) && count( $mo->entries ) > 0 ) {
						// 2.2
						$local = ( 'targetplugin' == $_POST['target'] || 'targetpluginwplang' == $_POST['target'] ) ? '&nbsp;-&nbsp;' . basename( $file, '.mo' ) : $local;
						if ( false === $this->save_mo_to_file( $selectlang, $mo, $file ) ) {
							/* translators: */
							$message .= ' ' . sprintf( esc_html__( 'error during exporting in %s file.', 'xili-dictionary' ), '<em>' . str_replace( WP_CONTENT_DIR, '', $file ) . '</em>' );
						} else {
							/* translators: */
							$message .= ' ' . sprintf( esc_html__( 'exported in %1$s file with %2$s msgids.', 'xili-dictionary' ), '<em>' . str_replace( WP_CONTENT_DIR, '', $file . '</em>' ), count( $mo->entries ) );
						}
					} else {
						/* translators: */
						$message .= sprintf( '<span class="alert">' . esc_html__( 'Nothing saved or updated in %s file !', 'xili-dictionary' ) . '</span>', str_replace( WP_CONTENT_DIR, '', $file ) );
					}
				} else {
					$message .= ' : error "' . $selectlang . '"';
				}
				$msg = 6;
				break;

			case 'exportpo':
				$actiontype = 'exportingpo';
				$formtitle = esc_html__( 'Build po file', 'xili-dictionary' );
				/* translators: */
				$formhow = sprintf( esc_html__( 'To build a %s file, choose language, destination (current theme or plugin) and origin.', 'xili-dictionary' ), '.po' );
				/* translators: */
				$submit_text = sprintf( esc_html__( 'Build %s file &raquo;', 'xili-dictionary' ), '.po' );
				break;

			case 'exportingpo':
				// PO
				check_admin_referer( 'xilidicoptions' );
				$actiontype = 'add';
				$selectlang = $_POST['language_file'];
				if ( '' != $selectlang ) {
					$file_suffix = ( 'pot-file' == $selectlang && ! is_multisite() ) ? '.pot' : '.po'; // 2.8.0

					$extract_array = array();
					$checked_themes = $this->checked_themes_array();

					if ( 'targettheme' == $_POST['target'] ) {

						if ( is_multisite() ) {
							/* complete theme's language with db structure languages (cats, desc,…) in uploads */

							$superadmin = ( isset( $_POST['only-theme'] ) && 'theme-dir' == $_POST['only-theme'] ) ? true : false;
							$message .= ( isset( $_POST['only-theme'] ) && 'theme-dir' == $_POST['only-theme'] ) ? '- exported only in theme - ' : '- exported in uploads - ';

							if ( ( $uploads = xili_upload_dir() ) && false === $uploads['error'] ) {

								if ( true === $superadmin ) {
									if ( 'local' == $_POST['only-local'] ) {
										$local = 'local';
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = true;
										$file = $this->active_theme_directory . $this->langfolder . 'local-' . $selectlang . '.po'; // theme folder

									} elseif ( 'wolocal' == $_POST['only-local'] ) {
										$local = '';
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = false; // no local
										$file = '';
									} else {
										$local = '';
										$file = '';
									}
								} else {
									if ( 'local' == $_POST['only-local'] ) {
										$local = 'local';
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = true;
										$file = $uploads['path'] . '/local-' . $selectlang . '.po'; // blogs.dir folder
									} elseif ( 'wolocal' == $_POST['only-local'] ) {
										$extract_array['origin'] = $checked_themes;
										$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
										$extract_array[ 'like-' . $this->msg_extracted_comments ] = false; // no
										$local = '';
										$file = $uploads['path'] . '/' . $selectlang . '.po';
									} else {
										$extract_array['origin'] = $checked_themes;

										$local = '';
										$file = $uploads['path'] . '/' . $selectlang . '.po';
									}
								}
							}
						} else { // standalone

							if ( 'local' == $_POST['only-local'] && '.pot' != $file_suffix ) {
								// no pot for local
								$local = 'local';
								$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
								$extract_array[ 'like-' . $this->msg_extracted_comments ] = true;

								if ( 'parenttheme' == $_POST['languagesfolder'] ) {
									$file = get_template_directory() . $this->parentlangfolder . 'local-' . $selectlang . '.po';
								} else {
									if ( 'contentlanguages' == $_POST['languagesfolder'] ) {
										$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . '-local-' . $selectlang . '.po';
									} else {
										$file = $this->active_theme_directory . $this->langfolder . 'local-' . $selectlang . '.po';
									}
								}
							} elseif ( 'wolocal' == $_POST['only-local'] ) {
								$local = '';
								$extract_array['origin'] = $checked_themes;
								$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
								$extract_array[ 'like-' . $this->msg_extracted_comments ] = false; // no local

								if ( 'parenttheme' == $_POST['languagesfolder'] ) {
									$selectlang1 = ( '.pot' == $file_suffix ) ? $this->theme_domain() : $selectlang;
									$file = get_template_directory() . $this->parentlangfolder . $selectlang1 . $file_suffix;
								} else {
									if ( 'contentlanguages' == $_POST['languagesfolder'] ) {
										$selectlang1 = ( '.pot' == $file_suffix ) ? '' : '-' . $selectlang;
										$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . $selectlang1 . $file_suffix;
									} else {
										$selectlang1 = ( '.pot' == $file_suffix ) ? $this->theme_domain() : $selectlang;
										$file = $this->active_theme_directory . $this->langfolder . $selectlang1 . $file_suffix;
									}
								}
							} else {
								$extract_array ['origin'] = $checked_themes;
								$local = '';

								if ( 'parenttheme' == $_POST['languagesfolder'] ) {
									$selectlang1 = ( '.pot' == $file_suffix ) ? $this->theme_domain() : $selectlang;
									$file = get_template_directory() . $this->parentlangfolder . $selectlang1 . $file_suffix;
								} else {
									if ( 'contentlanguages' == $_POST['languagesfolder'] ) {
										$selectlang1 = ( '.pot' == $file_suffix ) ? '' : '-' . $selectlang;
										$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . $selectlang1 . $file_suffix;
									} else {
										$selectlang1 = ( '.pot' == $file_suffix ) ? $this->theme_domain() : $selectlang;
										$file = $this->active_theme_directory . $this->langfolder . $selectlang1 . $file_suffix;
									}
								}
							}
						}
						$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name;
						$po = $this->from_cpt_to_pomo( $selectlang, substr( $file_suffix, 1 ), $extract_array ); // po or pot

					} else { // target plugin

						if ( array() != $checked_themes ) {
							$extract_array['origin'] = $checked_themes;
							$extract_array['projet_id_version'] = 'plugin = ' . $checked_themes[0];
							$po = $this->from_cpt_to_pomo( $selectlang, substr( $file_suffix, 1 ), $extract_array );
							$selectlang = ( '.pot' == $file_suffix ) ? 'plugin pot file' : $selectlang;
							$file = $this->path_plugin_file( $checked_themes[0], $selectlang, substr( $file_suffix, 1 ), ( 'targetpluginwplang' == $_POST['target'] ) ); //2.10.1
						}
					}

					if ( count( $po->entries ) > 0 ) {
						// 2.2
						$local = ( 'targetplugin' == $_POST['target'] || 'targetpluginwplang' == $_POST['target'] ) ? '&nbsp;-&nbsp;' . basename( $file, $file_suffix ) : $local;
						if ( false === $this->save_po_to_file( $selectlang, $po, $file ) ) {
							/* translators: */
							$message .= ' ' . sprintf( esc_html__( 'error during exporting in %s file.', 'xili-dictionary' ), '<em>' . str_replace( WP_CONTENT_DIR, '', $file ) . '</em>' );
						} else {
							/* translators: */
							$message .= ' ' . sprintf( esc_html__( 'exported in %1$s file with %2$s msgids.', 'xili-dictionary' ), '<em>' . str_replace( WP_CONTENT_DIR, '', $file . '</em>' ), count( $po->entries ) );
						}
					} else {
						/* translators: */
						$message .= sprintf( '<span class="alert">' . esc_html__( 'Nothing saved or updated in %s file !', 'xili-dictionary' ) . '</span>', str_replace( WP_CONTENT_DIR, '', $file ) );
					}
				} else {
					$message .= ' : error "' . $selectlang . '"';
				}
				break;

			case 'importbloginfos':
				// bloginfos and others since 1.1.0
				$actiontype = 'importingbloginfos';
				$formtitle = esc_html__( 'Import terms of blog info and others…', 'xili-dictionary' );
				$formhow = esc_html__( 'To import terms of blog info and others defining this current website (title, date, comment, archive...), click below.', 'xili-dictionary' );
				// current around 30 but...
				if ( class_exists( 'xili_language' ) ) {
					$formhow .= '<br />' . __( 'The process will import around 140 <strong>msgid</strong> from db and sources, so be patient.', 'xili-dictionary' );
				}

				// $UI_lang = get_locale();
				if ( 'en_US' != get_locale() ) {
					/* translators: */
					$formhow .= '<br />' . sprintf( __( 'The language of dashboard is not <em>en_US</em>, so the process will try to import translations in %s.', 'xili-dictionary' ), '<strong>' . get_locale() . '</strong>' );
				} else {
					$formhow .= '<br />' . __( 'If you switch language of dashboard in one other than in <em>en_US</em>, then the process will try to import translations of chosen language.', 'xili-dictionary' );
				}

				// detect xml
				if ( $this->available_theme_mod_xml() ) {
					$formhow .= '<hr />';
					$formhow .= $this->display_form_theme_mod_xml();
					$formhow .= '<hr />';
				}
				// detect pll - 2.12.2
				if ( get_option( 'polylang' ) ) {
					$formhow .= '<hr />';
					$formhow .= $this->display_form_pll_import();

					$formhow .= '<hr />';
				}

				$submit_text = esc_html__( 'Import blog info terms &raquo;', 'xili-dictionary' );
				break;

			case 'importingbloginfos':
				// bloginfos and others since 1.1.0
				check_admin_referer( 'xilidicoptions' );
				$actiontype = 'add';

				//$infosterms = $this->xili_import_infosterms_cpt ();

				$msg = 10;

				break;

			case 'importpluginmsgs':
				$actiontype = 'collectingpluginmsgs';
				$formtitle = esc_html__( 'Import terms from active plugins', 'xili-dictionary' );
				$formhow = esc_html__( 'To import terms …, click below.', 'xili-dictionary' );
				$submit_text = esc_html__( 'Import msgs &raquo;', 'xili-dictionary' );
				break;

			case 'collectingpluginmsgs':
				check_admin_referer( 'xilidicoptions' );

				$selectplugin_domain = $_POST['plugin_domain'];
				global $l10n;
				if ( isset( $l10n[ $selectplugin_domain ] ) ) {

					$formtitle = esc_html__( 'Start collecting terms from active plugins', 'xili-dictionary' );
					$formhow = esc_html__( 'To import terms, open a browser in front-end side.', 'xili-dictionary' );
					$submit_text = esc_html__( 'Stop msgs collecting &raquo;', 'xili-dictionary' );

					update_option( 'xd_test_importation', $selectplugin_domain );
					$actiontype = 'checkimportingpluginmsgs';
				} else {
					$formtitle = esc_html__( 'Error: no domain specified', 'xili-dictionary' );
					$formhow = esc_html__( 'Please specify a domain...', 'xili-dictionary' );
					$submit_text = esc_html__( 'End collecting &raquo;', 'xili-dictionary' );
					delete_option( 'xd_test_importation' );
					delete_option( 'xd_test_importation_list' );
					$actiontype = 'reset';
				}

				break;

			case 'checkimportingpluginmsgs':
				check_admin_referer( 'xilidicoptions' );

				$actiontype = 'importingpluginmsgs';
				$formtitle = esc_html__( 'Import terms from active plugins', 'xili-dictionary' );
				$formhow = esc_html__( 'To import terms, open a browser in front-end side.', 'xili-dictionary' );
				$submit_text = esc_html__( 'Import collected &raquo;', 'xili-dictionary' );

				break;

			case 'importingpluginmsgs':
				check_admin_referer( 'xilidicoptions' );
				$actiontype = 'add';
				// import into db
				$collected_msgs = get_option( 'xd_test_importation_list', array() );

				if ( array() != $collected_msgs ) {
					// the curlang of admin
					$locale = $this->get_wplang();
					$nbterms = $this->import_plugin_collected_msgs( $locale );
					// merge mo

					// the other if exists

				}
				if ( is_array( $collected_msgs ) && array() != $nbterms ) {
					$message .= esc_html__( 'imported terms = ', 'xili-dictionary' ) . $nbterms;
				} else {
					$message .= ' ' . $readfile . esc_html__( 'plugin’s terms pbs!', 'xili-dictionary' );
				}

				// reset values

				delete_option( 'xd_test_importation' );
				delete_option( 'xd_test_importation_list' );

				break;

			case 'importtaxonomy':
				$actiontype = 'importingtax';
				$formtitle = esc_html__( 'Import terms of taxonomy', 'xili-dictionary' );
				$formhow = esc_html__( 'To import terms of the current taxonomy named, click below.', 'xili-dictionary' );
				$submit_text = esc_html__( 'Import taxonomy’s terms &raquo;', 'xili-dictionary' );
				break;

			case 'importingtax':
				check_admin_referer( 'xilidicoptions' );
				$actiontype = 'add';
				$selecttaxonomy = $_POST['taxonomy_name']; //
				if ( taxonomy_exists( $selecttaxonomy ) ) {
					$nbterms = $this->xili_read_catsterms_cpt( $selecttaxonomy, $this->local_tag ); //$this->xili_read_catsterms();
					$msg = 4;
					if ( is_array( $nbterms ) ) {
						$message .= esc_html__( 'names = ', 'xili-dictionary' ) . $nbterms[0] . ' & ' . esc_html__( 'descs = ', 'xili-dictionary' ) . $nbterms[1];
					} else {
						/* translators: */
						$message .= ' ' . sprintf( esc_html__( 'taxonomy -%s- terms pbs!', 'xili-dictionary' ), $selecttaxonomy );
					}
				} else {
					$msg = 8;
					/* translators: */
					$message .= ' ' . sprintf( esc_html__( 'taxonomy -%s- do not exists', 'xili-dictionary' ), $selecttaxonomy );
				}

				break;

			case 'erasedictionary':
				$actiontype = 'erasingdictionary';
				$formtitle = esc_html__( 'Erase all terms', 'xili-dictionary' );
				$formhow = esc_html__( 'To erase terms of the dictionary, click below. (before, create a .po if necessary!)' );
				$submit_text = esc_html__( 'Erase all terms &raquo;', 'xili-dictionary' );
				break;

			case 'erasingdictionary':
				check_admin_referer( 'xilidicoptions' );

				$selection = ''; // $selecttaxonomy = $_POST['erasing_selection'];
				$this->erase_dictionary( $selection );

				$actiontype = 'add';
				$message .= ' ' . esc_html__( 'All terms erased !', 'xili-dictionary' );
				$msg = 7;
				// for next update
				break;

			case 'reset':
				$actiontype = 'add';
				break;

			case 'sendmail': // 2.3.2
				check_admin_referer( 'xilidicoptions' );
				$this->xili_settings['url'] = ( isset( $_POST['urlenable'] ) ) ? $_POST['urlenable'] : '';
				$this->xili_settings['theme'] = ( isset( $_POST['themeenable'] ) ) ? $_POST['themeenable'] : '';
				$this->xili_settings['wplang'] = ( isset( $_POST['wplangenable'] ) ) ? $_POST['wplangenable'] : '';
				$this->xili_settings['version-wp'] = ( isset( $_POST['versionenable'] ) ) ? $_POST['versionenable'] : '';
				$this->xili_settings['xiliplug'] = ( isset( $_POST['xiliplugenable'] ) ) ? $_POST['xiliplugenable'] : '';
				$this->xili_settings['webmestre-level'] = $_POST['webmestre']; // 1.8.2
				update_option( 'xili_dictionary_settings', $this->xili_settings );
				$contextual_arr = array();
				if ( 'enable' == $this->xili_settings['url'] ) {
					$contextual_arr[] = 'url=[ ' . get_bloginfo( 'url' ) . ' ]';
				}
				if ( isset( $_POST['onlocalhost'] ) ) {
					$contextual_arr[] = 'url=local';
				}
				if ( 'enable' == $this->xili_settings['theme'] ) {
					$contextual_arr[] = 'theme=[ ' . get_option( 'stylesheet' ) . ' ]';
				}
				if ( 'enable' == $this->xili_settings['wplang'] ) {
					$contextual_arr[] = 'WPLANG=[ ' . $this->get_wplang() . ' ]';
				}
				if ( 'enable' == $this->xili_settings['version-wp'] ) {
					$contextual_arr[] = 'WP version=[ ' . $wp_version . ' ]';
				}
				if ( 'enable' == $this->xili_settings['xiliplug'] ) {
					$contextual_arr[] = 'xiliplugins=[ ' . $this->check_other_xili_plugins() . ' ]';
				}

				$contextual_arr[] = $this->xili_settings['webmestre-level']; // 1.9.1

				$headers = 'From: xili-dictionary plugin page <' . get_bloginfo( 'admin_email' ) . '>' . "\r\n";
				if ( '' != $_POST['ccmail'] ) {
					$headers .= 'Cc: <' . $_POST['ccmail'] . '>' . "\r\n";
					$headers .= 'Reply-To: <' . $_POST['ccmail'] . '>' . "\r\n";
				}
				$headers .= '\\';
				$message = 'Message sent by: ' . get_bloginfo( 'admin_email' ) . "\n\n";
				$message .= 'Subject: ' . $_POST['subject'] . "\n\n";
				$message .= 'Topic: ' . $_POST['thema'] . "\n\n";
				$message .= 'Content: ' . $_POST['mailcontent'] . "\n\n";
				$message .= 'Checked contextual infos: ' . implode( ', ', $contextual_arr ) . "\n\n";
				$message .= "This message was sent by webmaster in xili-dictionary plugin settings page.\n\n";
				$message .= "\n\n";
				$result = wp_mail( 'contact@xiligroup.com', $_POST['thema'] . ' from xili-dictionary v.' . XILIDICTIONARY_VER . ' plugin settings page.', $message, $headers );
				$message = esc_html__( 'Email sent.', 'xili_tidy_tags' );
				$msg = 7;
				/* translators: */
				$emessage = sprintf( esc_html__( 'Thanks for your email. A copy was sent to %1$s (%2$s)', 'xili-dictionary' ), $_POST['ccmail'], $result );
				$actiontype = 'add';
				break;

			default:
				$actiontype = 'add';
				$message .= ' ' . esc_html__( 'Find below the list of msg.', 'xili-dictionary' );

		}
		/* register the main boxes always available */

		/* files import export*/
		add_meta_box( 'xili-dictionary-sidebox-3', esc_html__( 'Import & export', 'xili-dictionary' ), array( &$this, 'metabox_import_export_files' ), $this->thehook, 'side', 'core' );
		/* msg selection */
		add_meta_box( 'xili-dictionary-sidebox-4', esc_html__( 'Terms list management', 'xili-dictionary' ), array( &$this, 'metabox_msg_selection' ), $this->thehook, 'side', 'core' );
		if ( 'isactive' != $this->xililanguage && 'Polylang' != $this->multilanguage_plugin_active ) {
			/* Languages list when xili-language is absent */
			add_meta_box( 'xili-dictionary-sidebox-5', esc_html__( 'Languages list management', 'xili-dictionary' ), array( &$this, 'metabox_languages_list_management' ), $this->thehook, 'side', 'core' );
		}
		/* dialog input form shared */
		add_meta_box( 'xili-dictionary-normal-1', __( $formtitle, 'xili-dictionary' ), array( &$this, 'metabox_shared_by_dialogs' ), $this->thehook, 'normal', 'core' );
		/* list of terms*/

		add_meta_box( 'xili-dictionary-normal-cpt', esc_html__( 'Entries (Msgid and Msgstr)', 'xili-dictionary' ), array( &$this, 'metabox_with_cpt_content_list' ), $this->thehook, 'normal', 'core' );

		// since 1.2.2 - need to be upgraded...
		if ( 0 == $msg && '' != $message ) {
			$msg = 6; //by temporary default
		}
		$themessages[1] = esc_html__( 'A new msgid was added.', 'xili-dictionary' );
		$themessages[2] = esc_html__( 'A msg was updated.', 'xili-dictionary' );
		$themessages[3] = esc_html__( 'A msg was deleted.', 'xili-dictionary' );
		$themessages[4] = esc_html__( 'msg imported from WP: ', 'xili-dictionary' ) . $message;
		$themessages[5] = esc_html__( 'All msg imported !', 'xili-dictionary' ) . ' ( ' . $message . ' )';
		/* translators: */
		$themessages[6] = sprintf( esc_html__( 'Result log: %s', 'xili-dictionary' ), $message );
		$themessages[7] = esc_html__( 'All msgs erased !', 'xili-dictionary' );
		$themessages[8] = esc_html__( 'Error when adding !', 'xili-dictionary' ) . ' ( ' . $message . ' )';
		$themessages[9] = esc_html__( 'Error when updating !', 'xili-dictionary' );
		$themessages[10] = esc_html__( 'Wait during terms importing process until the entries (msgid and msgstr) list appears below!', 'xili-dictionary' );

		/* form datas in array for do_meta_boxes() */
		$data = array(
			'message' => $message,
			'action' => $action,
			'formtitle' => $formtitle,
			'submit_text' => $submit_text,
			'cancel_text' => $cancel_text,
			'formhow' => $formhow,
			'orderby' => $orderby,
			'term_id' => $term_id,
			'tagsnamesearch' => $tagsnamesearch,
			'tagsnamelike' => $tagsnamelike,
			'selecttaxonomy' => $selecttaxonomy,
			'emessage' => $emessage,
		);

		if ( isset( $dictioline ) ) {
			$data['dictioline'] = $dictioline;
		}
		?>
<div id="xili-dictionary-settings" class="wrap columns-2" style="min-width:850px">

	<h2><?php esc_html_e( 'Dictionary', 'xili-dictionary' ); ?></h2>
		<?php
		if ( 0 != $msg ) {
			?>
			<div id="message" class="updated fade"><p><?php echo $themessages[ $msg ]; ?></p></div>
			<?php
		}
		$poststuff_class = '';
		$postbody_class = 'class="metabox-holder columns-2"';
		$postleft_id = 'id="postbox-container-2"';
		$postright_id = 'postbox-container-1';
		$postleft_class = 'class="postbox-container"';
		$postright_class = 'postbox-container';
		?>
		<form name='add' id='add' method="post" action="<?php echo $this->xd_settings_page; ?>">
			<input type="hidden" name="action" value="<?php echo $actiontype; ?>" />
					<?php wp_nonce_field( 'xili-dictionary-settings' ); ?>
					<?php wp_nonce_field( 'xilidicoptions' ); ?>
					<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
					<?php
					wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
					/* 0.9.6 add has-right-sidebar for next wp 2.8*/
					?>
			<div id="poststuff" <?php echo $poststuff_class; ?> >

				<div id="post-body" <?php echo $postbody_class; ?> >
					<div id="<?php echo $postright_id; ?>" class="<?php echo $postright_class; ?>">
							<?php do_meta_boxes( $this->thehook, 'side', $data ); ?>
					</div>
					<div id="post-body-content" >
						<div <?php echo $postleft_id; ?> <?php echo $postleft_class; ?> style="min-width:360px">
		<?php
		if ( 'importingbloginfos' == $action ) {
			$infosterms = $this->xili_import_infosterms_cpt();
			if ( $infosterms[1] > 0 ) {
				/* translators: */
				$this->import_message = ' ( ' . $infosterms[1] . '/' . $infosterms[0] . ' ) ' . sprintf( esc_html__( 'msgid imported with success (%s msgstr)', 'xili-dictionary' ), $infosterms[2] );
			} else {
				$this->import_message = ' ' . esc_html__( 'already imported', 'xili-dictionary' ) . ' ( ' . $infosterms[0] . ' ) ';
			}
			// polylang - XL 2.20.3

			if ( isset( $_POST['pllimport'] ) ) {
				$results = $this->import_pll_db_mos();
				$nb_cat = $this->import_pll_categories_name_description();
				$s = array();
				foreach ( $results as $lang => $nb ) {
					$s[] = $lang . '=' . $nb;
				}
				/* translators: */
				$this->import_message .= ' - ' . sprintf( esc_html__( 'Polylang imported or refreshed (%1$s) - and %2$s category strings.', 'xili-dictionary' ), implode( ', ', $s ), $nb_cat );
			}
			do_meta_boxes( $this->thehook, 'normal', $data );
		} else {
			do_meta_boxes( $this->thehook, 'normal', $data );
		}
		?>
					</div>
					<h4><a href="http://dev.xiligroup.com/xili-dictionary" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo XILIDICTIONARY_PLUGIN_URL . '/images/XD-full-logo-32.png'; ?>" alt="xili-dictionary logo"/></a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php esc_html_e( 'Author' ); ?>" >xiligroup.com</a>™ - msc 2007-2019 - v. <?php echo XILIDICTIONARY_VER; ?></h4>
				</div>
			</div>
			<br class="clear" />
		</div>
	</form>
</div>

		<?php
		//end settings div
		$this->insert_js_for_datatable(
			array(
				'swidth2' => '60%',
				'screen' => 'toolbox',
			)
		);
	}

	/**
	 * metabox shared by dialogs before actions with XD list
	 *
	 */
	public function metabox_shared_by_dialogs( $data ) {
		extract( $data );
		$do = true;

		$cur_theme_name = $this->get_option_theme_full_name();

		?>
<div class="metabox-content" >
	<div class="dialogcontainer" >

		<p id="add_edit"><?php _e( $formhow, 'xili-dictionary' ); ?></p>
		<?php
		$cur_theme_name = $this->get_option_theme_full_name( true );
		if ( in_array( $action, array( 'importbloginfos', 'importtaxonomy', 'importpluginmsgs' ) ) ) {
			/* translators: */
			echo '<p><em>' . sprintf( esc_html__( 'Before importing terms, verify that the %1$strash%2$s is empty !', 'xili-dictionary' ), '<a href="edit.php?post_type=' . XDMSG . '">', '</a>' ) . '</em></p>';
		}

		if ( in_array( $action, array( 'export', 'exportpo', 'download' ) ) ) {
			// left column
			?>
			<div class="dialoglang">
				<label for="language_file">
					<select name="language_file" id="language_file" >
						<?php
						$default_lang = $this->get_wplang();
						$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );//get_terms(TAXONAME, array( 'hide_empty' => false) );
						if ( $listlanguages ) {
							foreach ( $listlanguages as $reflanguage ) {
								echo '<option value="' . $reflanguage->name . '" ';
								echo selected( $default_lang, $reflanguage->name, false ) . ' >' . __( $reflanguage->description, 'xili-dictionary' ) . '</option>';
							}
						}
						if ( 'exportpo' == $action && ! is_multisite() ) {
							echo '<option value="pot-file" >' . esc_html__( 'Build a .pot file', 'xili-dictionary' ) . '</option>';
						}
						?>
					</select>
				</label>

			</div>
			<div class="dialogfile">
		<?php
		// middle column

		if ( ( 'export' == $action || 'exportpo' == $action ) && is_multisite() && is_super_admin() && $this->xililanguage_ms ) {
			?>
			<p><?php /* translators: */ printf( esc_html__( 'Verify before that you are authorized to write in languages folder in theme named: %s', 'xili-dictionary' ), $cur_theme_name ); ?>
			</p>
		<?php
		}
		if ( ( 'export' == $action || 'exportpo' == $action ) && is_multisite() && is_super_admin() && ! $this->xililanguage_ms ) {
			?>
			<label for="only-theme">
			<?php
			if ( 'export' == $action ) { // if not checked = blog-dir
				/* translators: */
				printf( esc_html__( 'SuperAdmin: %sonly as theme .mo', 'xili-dictionary' ), '<br />' );
			} else {
				/* translators: */
				printf( esc_html__( 'SuperAdmin: %sonly as theme .po', 'xili-dictionary' ), '<br />' );
			}
			?>
				<input id="only-theme" name="only-theme" type="checkbox" value="theme-dir" />
			</label>

		<?php
		}
		if ( ! is_multisite() ) {
			$do_it = true;
		} else {
			$do_it = is_super_admin(); // only super admin can create plugin language file
		}
		if ( 'export' == $action || 'exportpo' == $action ) {
			?>

			<label for="target">
				<input checked="checked" class="target" name="target" type="radio" value="targettheme" />&nbsp;<?php esc_html_e( 'Theme', 'xili-dictionary' ); ?>
			</label>
			<div style="margin:10px 10px 0 23px;"><label for="only-local">
			<?php
			$filetype = ( 'export' == $action ) ? 'mo' : 'po';
			?>
			<select id="only-local" name="only-local">
				<?php
					/* translators: */
					echo '<option value="" >' . sprintf( esc_html__( 'Build and save %s file', 'xili-dictionary' ), $filetype );
					/* translators: */
					echo '<option value="local" selected="selected" >' . sprintf( esc_html__( 'Save only the local-xx_XX.%s sub-selection', 'xili-dictionary' ), $filetype );
					/* translators: */
					echo '<option value="wolocal" >' . sprintf( esc_html__( 'Save %s w/o local msgs', 'xili-dictionary' ), $filetype );
				?>
				</select>
			</label>
			<br />&nbsp;&nbsp;<label for="languagesfolder">
				<?php esc_html_e( 'In which languages subfolder', 'xili-dictionary' ); ?>
				<select id="languagesfolder" name="languagesfolder">
					<?php
					/* translators: */
					echo '<option value="currenttheme" >' . sprintf( esc_html__( 'Sub-folder (%1$s) in current theme (%2$s)', 'xili-dictionary' ), $this->langfolder, $cur_theme_name ) . '</option>';
					if ( is_child_theme() ) {
						/* translators: */
						echo '<option value="parenttheme" >' . sprintf( esc_html__( 'Languages sub-folder (%1$s) in parent theme ( %2$s )', 'xili-dictionary' ), $this->parentlangfolder, get_option( 'template' ) ) . '</option>';
					}
					echo '<option value="contentlanguages" >' . esc_html__( 'Themes sub-folder in wp-content/languages (WP_LANG_DIR)', 'xili-dictionary' ) . '</option>';
					?>
				</select>
			</label></div>
			<?php
			if ( $do_it ) {
			?>
		<hr /><label for="target"><input class="target" name="target" type="radio" value="targetplugin" />&nbsp;<?php esc_html_e( 'Plugin language folder', 'xili-dictionary' ); ?></label>
		<br /><label for="target"><input class="target" name="target" type="radio" value="targetpluginwplang" />&nbsp;<?php /* translators: */ printf( esc_html__( 'Plugin language file in %s', 'xili-dictionary' ), str_replace( WP_CONTENT_DIR, '', WP_LANG_DIR . '/plugins/' ) ); ?></label>
		<hr />
		<?php
			}
		}
		?>
		</div>
		<?php
		// check origin theme
		if ( 'export' == $action || 'exportpo' == $action ) {

			$list_plugins = get_plugins(); // 2.6.0

			$listterms = get_terms( 'origin', array( 'hide_empty' => false ) );
			if ( 'export' == $action ) {
				echo '<input type="hidden" value="mo" id="_xd_file_extend" name="_xd_file_extend" >';
			} else {
				echo '<input type="hidden" value="po" id="_xd_file_extend" name="_xd_file_extend" >';
			}

			echo '<div class="dialogorigin">';
			if ( $listterms ) {
				$checkline = esc_html__( 'Check Origin(s) to be exported', 'xili-dictionary' ) . ':<br />';
				$i = 0;
				echo '<table class="checktheme" ><tr>';
				foreach ( $listterms as $onetheme ) {
					if ( in_array( $onetheme->name, array_keys( $list_plugins ) ) && ! $do_it ) {
						continue;
					}
					// Origin for plugins
					if ( in_array( $onetheme->name, array_keys( $list_plugins ) ) ) {
						/* translators: */
						$title = sprintf( esc_html__( 'Plugin: %s', 'xili-dictionary' ), $list_plugins[ $onetheme->name ]['Name'] );
					} else {
						$title = $onetheme->name;
					}

					$checked = ( $onetheme->name == $cur_theme_name ) ? 'checked="checked"' : '';
					$checkline .= '<td><input type="checkbox" ' . $checked . ' id="theme-' . $onetheme->term_id . '" name="theme-' . $onetheme->term_id . '" value="' . $onetheme->name . '" />&nbsp;' . $title . '</td>';
					$i++;
					if ( 0 == ( $i % 2 ) ) {
						$checkline .= '</tr><tr>';
					}
				}
				echo $checkline . '</tr></table>';
			}
			echo '</div>';
		}
		// end container
		?>
		<div style="clear:both;">
			<div id="xd-file-exists"><p><?php esc_html_e( 'The previous file will be overwritten !', 'xili-dictionary' ); ?></p></div>
			<div id="xd-file-state"></div>
		</div>
	</div>


	<div class="dialogbbt">
		<input class="button" type="submit" name="reset" value="<?php echo $cancel_text; ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
		<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text; ?>" />
	</div>
</div>
<script type="text/javascript">
	//<![CDATA[
	<?php
	if ( function_exists( 'the_theme_domain' ) ) {// in new xili-language
		echo 'var potfile = "' . the_theme_domain() . '";';
	} else {
		echo 'var potfile = "' . $this->get_option_theme_name() . '";';
	}
	echo 'var curthemename = "' . $this->get_option_theme_name() . '";';
	if ( is_child_theme() ) {
		echo 'var parentthemename = "' . get_option( 'template' ) . '";';
	}

	$this->echo_js_plugins_datas();

	echo 'var pluginpotfile = "plugin pot file";';

	?>
	function update_ui_state() {
		var x = jQuery( '#_xd_file_extend' ).val();
		var place = jQuery( '#languagesfolder' ).val().replace ( 'current', '' );
		place = place.replace ( 'content', '' );
		var multiupload = 'blog-dir';
		if ( jQuery( '#only-theme' ).attr( 'checked' ) ) multiupload = 'theme-dir'; // multisite
		var local = jQuery( '#only-local' ).val(); // 2.16.0
		var la = jQuery( '#language_file' ).val();
		var target = jQuery( '.target:checked' ).val();
		if ( target == "targetplugin" || target == "targetpluginwplang" ) {
			place = target.replace( 'target', '' );
			var domain = jQuery( "table.checktheme input:checked" )
			.map(function() {
			return jQuery(this).val();
			}) .get();
			var domains = jQuery.makeArray( domain );
			var plugin = '';
			plugin = domains[0];

		} else {
			var plugin = '';
		}

		var a = from_file_exists( 'files', place, curthemename, plugin, la, x, local, multiupload ); //alert ( a + ' <> ' + place + curthemename + plugin + la + x );
		show_file_states( 'files', place, curthemename, plugin, la, x, local, multiupload );
		if ( a == "exists" ) {
			jQuery( '#xd-file-exists' ).show();
			jQuery( '#xd-file-state' ).show();
		} else {
			jQuery( '#xd-file-exists' ).hide();
			jQuery( '#xd-file-state' ).show();
		}

	}
	jQuery(document).ready( function() {
		update_ui_state ();
	});

	jQuery( '#language_file , #languagesfolder, #only-local, .target, table.checktheme input:checkbox , #only-theme' ).change(function() {
		update_ui_state ();
	});


	//]]>
</script>
		<?php
		// other actions
		} elseif ( in_array( $action, array( 'collectingpluginmsgs', 'checkimportingpluginmsgs', 'importbloginfos', 'importtaxonomy', 'erasedictionary', 'importpluginmsgs' ) ) ) {

			if ( 'importtaxonomy' == $action ) {
				?>
		<label for="taxonomy_name"><?php esc_html_e( 'Slug:', 'xili-dictionary' ); ?></label>
		<input name="taxonomy_name" id="taxonomy_name" type="text" value="<?php echo ( '' != $selecttaxonomy ) ? $selecttaxonomy : 'category'; ?>" /><br />
			<?php
			} elseif ( in_array( $action, array( 'collectingpluginmsgs', 'importpluginmsgs', 'checkimportingpluginmsgs' ) ) ) {

				global $l10n;
				echo '<br/>';
				$list_domains = array_keys( $l10n );
				$unlistable_domains = array( 'default', 'xili-language', 'bbpress', 'xili_xl_bbp_addon', 'xili_postinpost', 'xili_tidy_tags', 'xili-language-widget', 'xili-dictionary', 'twentyten' );
				$domains_checking = array_diff( $list_domains, $unlistable_domains );

				if ( 'importpluginmsgs' == $action ) {
					esc_html_e( 'Some active domains are detected in memory', 'xili-dictionary' );
					$checked_domains = array();
					foreach ( $domains_checking as $domain ) {
						$po = $l10n[ $domain ];
						if ( count( $po->entries ) > 0 ) {
							/* translators: */
							echo sprintf( esc_html__( 'This domain named %1$s has %2$d active entries.', 'xili-dictionary' ), '<strong>' . $domain . '</strong>', count( $po->entries ) ) . '</br>';
							print_r( $po->headers);
							$checked_domains[] = $domain;
						} else {
							/* translators: */
							echo sprintf( esc_html__( 'No entry in %s (or .mo file badly built) . ', 'xili-dictionary' ), '<strong>' . $domain . '</strong>' ) . '</br>';
						}
						echo '<br /><hr />';
					}
				}

				if ( 'checkimportingpluginmsgs' == $action ) {
					$collected_terms = get_option( 'xd_test_importation_list', array() );

					if ( $collected_terms ) {
						/* translators: */
						printf( esc_html__( 'Some terms collected from %s !', 'xili-dictionary' ), esc_html__( 'Domain : ' . get_option( 'xd_test_importation', '' ) ) );
						print_r( $collected_terms );

					} else {

						printf( esc_html__( 'No terms collected from %s !', 'xili-dictionary' ), esc_html__( 'Domain : ' . get_option( 'xd_test_importation', '' ) ) );
						$do = false;
					}
				}
			}

			if ( 'importpluginmsgs' == $action && array() != $checked_domains ) {
				echo '<select name="plugin_domain" id="plugin_domain" >';
				foreach ( $checked_domains as $one_domain ) {
					echo '<option value="' . $one_domain . '" >' . $one_domain . '</option>';
				}
				echo '</select>';
				?>
				<br />
				<?php
			} elseif ( in_array( $action, array( 'collectingpluginmsgs', 'importpluginmsgs' ) ) ) {
				echo esc_html__( 'Domain : ', 'xili-dictionary' ) . get_option( 'xd_test_importation', '' );
			}
		?>
		<br class="clear" />&nbsp;<br />

		<input class="button" type="submit" name="reset" value="<?php echo $cancel_text; ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
		<?php if ( true == $do ) { ?>
			<input id="import_start" class="button-primary" type="submit" name="submit" value="<?php echo $submit_text; ?>" /><br />
		<?php } ?>
	</div>
</div>
<script type="text/javascript">
				//<![CDATA[
				var plugin = '';
				<?php
				if ( function_exists( 'the_theme_domain' ) ) {
					// in new xili-language
					echo 'var potfile = "' . the_theme_domain() . '";';
				} else {
					echo 'var potfile = "' . $this->get_option_theme_name() . '";';
				}
				echo 'var curthemename = "' . $this->get_option_theme_name() . '";';
				if ( is_child_theme() ) {
					echo 'var parentthemename = "' . get_option( 'template' ) . '";';
				}

				$this->echo_js_plugins_datas();
				echo 'var pluginpotfile = "plugin pot file";';
				?>
				jQuery(document).ready( function() {
					var plugin = jQuery( '#_xd_plugin' ).val();
					var t = '<?php echo esc_js( $submit_text ); ?>';

					jQuery( '.source_pot' ).change(function() {
							var rb = jQuery(this).val();
							if ( rb == 'theme' ) {
								jQuery( '#_xd_plugin' ).val( '' );
								jQuery( "#import_start" ).val( t + ' : ' + curthemename );
								jQuery( '#backup_pot_label' ).html( '( '+ potfile + '.pot)' );
							} else if ( rb == 'plugin' ) {
								plugin = jQuery( '#_xd_plugin' ).val();
								if ( 'string' == typeof (plugin) && plugin != '' ) jQuery( '#backup_pot_label' ).html( '( '+ plugindatas[plugin]['domain'] + '.pot)' );
								if ( 'string' == typeof (plugin) && plugin != '' ) jQuery("#import_start").val( t + ' : ' + plugindatas[plugin]['name'] );
							}
					});

					jQuery( '#_xd_plugin' ).change(function() {
						jQuery( '#source_plugin' ).attr( 'checked','checked' );
						plugin = jQuery(this).val();
						if ( 'string' == typeof (plugin) && plugin != '' ) jQuery("#import_start").val( t + ' : ' + plugindatas[plugin]['name'] );
						if ( 'string' == typeof (plugin) && plugin != '' ) jQuery( '#backup_pot_label' ).html( '( '+ plugindatas[plugin]['domain'] + '.pot)' );
					});

				});
				//]]>
				</script>
		<?php
		// nothing inside
		} else {
			if ( 'importingbloginfos' == $action ) {
				// because special (to avoid ajax)
				echo '<p style="border-left:3px #00e000 solid; padding:5px 10px; background-color:#ffffff;">' . esc_html__( 'Now, all the blog and core terms are imported:', 'xili-dictionary' ) . $this->import_message . '.</p>';
			}
			echo '<p><em>' . esc_html__( 'This box is used for input dialog, leave it opened and visible…', 'xili-dictionary' ) . '</em></p></div></div>';
		}
	}

	/**
	 * @since 2.0 with datatables js (ex widefat)
	 *
	 */
	public function metabox_with_cpt_content_list( $data ) {
		extract( $data );
		$sortparent = ( ( '' == $this->subselect ) ? '' : '&amp;tagsgroup_parent_select=' . $this->subselect );
		?>
<div id="topbanner">
</div>
<div id="tableupdating" ><br /><br />
	<h2><?php esc_html_e( 'Drawing table...', 'xili-dictionary' ); ?></h2>
</div>
<table class="display" id="linestable" style="visibility:hidden;">
	<thead>
		<tr>
			<th scope="col" class="center colid"><a href="<?php echo $this->xd_settings_page; ?>" ><?php esc_html_e( 'ID' ); ?></a></th>
			<th scope="col" class="coltexte"><a href="<?php echo $this->xd_settings_page . '&amp;orderby=name' . $sortparent; ?>"><?php esc_html_e( 'Text' ); ?></a>
			</th>
			<th scope="col" class="colslug"><?php esc_html_e( 'relations', 'xili-dictionary' ); ?></th>
			<th scope="col" class="colgroup center"><?php esc_html_e( '.mo status', 'xili-dictionary' ); ?></th>
			<th colspan="2"><?php esc_html_e( 'Action' ); ?></th>
		</tr>
	</thead>
	<tbody id="the-list">
			<?php
			$this->xili_dict_cpt_row( $orderby, $tagsnamelike, $tagsnamesearch ); /* the lines */
			?>
	</tbody>
</table>
<div id="bottombanner">
</div>
	<?php
	}

	/**
	 * @since 1.0.2
	 * only if xili-language plugin is absent
	 */
	public function metabox_languages_list_management( $data = array() ) {
		extract( $data );
		?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php esc_html_e( 'Language to delete', 'xili-dictionary' ); ?></legend>
			<p><?php esc_html_e( 'Only the languages list is here modified (but not the dictionary\'s contents)', 'xili-dictionary' ); ?>
			</p>
			<select name="langs_list" id="langs_list" style="width:100%;">
				<option value="no_select" ><?php esc_html_e( 'Select...', 'xili-dictionary' ); ?></option>
				<?php echo $this->build_grouplist( '' ); ?>
			</select>
			<br />
			<p class="submit">
				<input type="submit" id="lang_delete" name="lang_delete" value="<?php esc_html_e( 'Delete a language', 'xili-dictionary' ); ?>" />
			</p>
		</fieldset><br />

		<?php
		$this->examples_list = array();
		$gp_locale_path = str_replace( 'xili-dictionary/', '', XILIDICTIONARY_PLUGIN_DIR ) . 'jetpack/locales.php';
		if ( ! class_exists( 'GP_Locales' ) && file_exists( $gp_locale_path ) ) {
			require_once $gp_locale_path; // from JetPack
		}
		if ( class_exists( 'GP_Locales' ) ) {
			$xl_locales = GP_Locales::instance();
			foreach ( $xl_locales->locales as $key => $one_locale ) {
				if ( isset( $one_locale->wp_locale ) && '' != $one_locale->wp_locale ) {
					/* translators: */
					$this->examples_list[ $one_locale->wp_locale ] = sprintf( _x( '%1$s/%2$s', 'locales', 'xili-dictionary' ), $one_locale->english_name, $one_locale->native_name );
				} else {
					// a * inserted if no existing WP_locale declared...
					/* translators: */
					$this->examples_list[ $key ] = sprintf( _x( '%1$s/%2$s *', 'locales', 'xili-dictionary' ), $one_locale->english_name, $one_locale->native_name );
				}
			}
		}

		?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php esc_html_e( 'Language to add', 'xili-dictionary' ); ?></legend>
			<?php if ( $this->examples_list ) { ?>
			<select name="language_name_list" id="language_name_list">
				<?php $this->example_langs_list( $action ); ?>
			</select><br />
			<?php } ?>
			<label for="lang_ISO"><?php esc_html_e( 'ISO (xx_YY)', 'xili-dictionary' ); ?></label>:&nbsp;
			<input name="lang_ISO" id="lang_ISO" type="text" value="" size="5"/><br />
			<label for="lang_name"><?php esc_html_e( 'Name (eng.)', 'xili-dictionary' ); ?></label>:&nbsp;
			<input name="lang_name" id="lang_name" type="text" value="" size="20" />
			<br />
			<p class="submit">
				<input type="submit" id="lang_add" name="lang_add" value="<?php esc_html_e( 'Add a language', 'xili-dictionary' ); ?>" />
			</p>
		</fieldset>
		<script type="text/javascript">
		//<![CDATA[
			jQuery(document).ready( function( $) {
				$( '#language_name_list' ).change(function() {
				var x = $(this).val();
				$( '#lang_ISO' ).val(x);
				var v = $( '#language_name_list option:selected' ).text();
				v1 = v.substring(0,v.indexOf('/',0) );
				v2 = v1.substring(0,v1.indexOf(" (",0) );
				if ( '' != v2 ) {
					v = v2;
				} else {
					v = v1;
				}
				$( '#lang_name' ).val(v);
			});
		});
		//]]>
		</script>
	<?php
	}

	/**
	 * private functions for admin page : the language example list
	 * @since 1.6.0
	 */
	private function example_langs_list( $state ) {

		/* reduce list according present languages in today list */
		if ( 'delete' != $state && 'edit' != $state ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );
			foreach ( $listlanguages as $language ) {
				if ( array_key_exists( $language->name, $this->examples_list ) ) {
					unset( $this->examples_list[ $language->name ] );
				}
			}
		}
		//
		echo '<option value="">' . esc_html__( 'Choose…', 'xili-dictionary' ) . '</option>';
		foreach ( $this->examples_list as $key => $value ) {
			// $selected = ( ''!=$language_name && $language_name == $key) ? 'selected=selected' : '';
			$selected = '';
			echo '<option value="' . $key . '" ' . $selected . '>' . $value . ' ( ' . $key . ' )</option>';
		}
	}

	/**
	 * @updated 1.0.2
	 * manage files
	 */
	public function metabox_import_export_files( $data ) {
		extract( $data );
		$default_lang_get = ( $this->get_wplang() ) ? '&amp;' . QUETAG . '=' . $this->get_wplang() : '';
		?>
		<h4 id="manage_file"><?php esc_html_e( 'The files', 'xili-dictionary' ); ?></h4>
		<a class="action-button blue-button" href="<?php echo $this->xd_settings_page . '&amp;action=export'; ?>" title="<?php esc_html_e( 'Create or Update mo file in current theme folder', 'xili-dictionary' ); ?>"><?php esc_html_e( 'Build mo file', 'xili-dictionary' ); ?></a>
		&nbsp;<br /><?php esc_html_e( 'Import po/mo file', 'xili-dictionary' ); ?>:<a class="small-action-button" href="edit.php?post_type=<?php echo XDMSG ?>&amp;page=import_dictionary_page&amp;extend=po<?php echo$default_lang_get; ?>" title="<?php esc_html_e( 'Import an existing .po file from current theme folder', 'xili-dictionary' ); ?>">PO</a>
		<a class="small-action-button" href="edit.php?post_type=<?php echo XDMSG; ?>&amp;page=import_dictionary_page&amp;extend=mo<?php echo$default_lang_get; ?>" title="<?php esc_html_e( 'Import an existing .mo file from current theme folder', 'xili-dictionary' ); ?>">MO</a><br />
		&nbsp;<br /><a class="action-button grey-button" href="<?php echo $this->xd_settings_page . '&amp;action=exportpo'; ?>" title="<?php esc_html_e( 'Create or Update po file in current theme folder', 'xili-dictionary' ); ?>"><?php esc_html_e( 'Build po file', 'xili-dictionary' ); ?></a>
		<br /><a class="action-button grey-button" href="edit.php?post_type=<?php echo XDMSG; ?>&amp;page=download_dictionary_page" title="<?php esc_html_e( 'Download po or file to your computer', 'xili-dictionary' ); ?>"><?php esc_html_e( 'Download file', 'xili-dictionary' ); ?></a>

		<h4 id="manage_categories"><?php esc_html_e( 'The taxonomies', 'xili-dictionary' ); ?></h4>
		<a class="action-button blue-button" href="<?php echo $this->xd_settings_page . '&amp;action=importtaxonomy'; ?>" title="<?php esc_html_e( 'Import name and description of taxonomy', 'xili-dictionary' ); ?>"><?php esc_html_e( 'Import texts of taxonomy', 'xili-dictionary' ); ?></a>

		<h4 id="manage_website_infos"><?php esc_html_e( 'The website infos (title, sub-title and more…)', 'xili-dictionary' ); ?></h4>
		<?php
		if ( class_exists( 'xili_language' ) && version_compare( XILILANGUAGE_VER, '2.3.9', '>' ) ) {
			esc_html_e( '…and comment, locale, date terms, archive,…', 'xili-dictionary' );
			echo '<br /><br />';
		}
		?>
		<a class="action-button blue-button" href="<?php echo $this->xd_settings_page . '&amp;action=importbloginfos'; ?>" title="<?php esc_html_e( 'Import infos of website and more to become translatable...', 'xili-dictionary' ); ?>"><?php _e( "Import texts of website's infos", 'xili-dictionary' ); ?></a>

		<h4 id="manage_dictionary"><?php esc_html_e( 'Dictionary in database', 'xili-dictionary' ); ?></h4>
			<a class="action-button grey-button" href="edit.php?post_type=<?php echo XDMSG; ?>&amp;page=erase_dictionary_page" title="<?php esc_html_e( 'Erase selected msg of dictionary ! (but not .mo or .po files)', 'xili-dictionary' ); ?>"><?php esc_html_e( 'Erase (selection of) msg', 'xili-dictionary' ); ?></a>
			<a class="action-button grey-button" href="edit.php?post_type=<?php echo XDMSG; ?>&amp;page=import_dictionary_page&amp;scan=sources" title="<?php esc_html_e( 'Import translatable texts from files', 'xili-dictionary' ); ?>"><?php esc_html_e( 'Import texts from source files', 'xili-dictionary' ); ?></a>
		<?php if ( isset( $_GET['test'] ) ) { /* during testing phase 2.3.5 */ ?>
		<h4 id="manage_dictionary"><?php esc_html_e( 'Selection of plugin’s msgs for front-end', 'xili-dictionary' ); ?></h4>
			<a class="action-button grey-button" href="<?php echo $this->xd_settings_page . '&amp;action=importpluginmsgs'; ?>" title="<?php esc_html_e( 'Import translatable texts for current active plugin', 'xili-dictionary' ); ?>"><?php esc_html_e( 'Import texts from plugins', 'xili-dictionary' ); ?></a>

		<?php
		}
	}

	/**
	 * @since 090423 -
	 * Sub selection box
	 */
	public function metabox_msg_selection( $data = array() ) {
		extract( $data );
		?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php esc_html_e( 'Sub list of msg', 'xili-dictionary' ); ?></legend>
			<?php
			/*
			<label for="tagsnamelike"><?php esc_html_e( 'Starting with:', 'xili-dictionary' ) ?></label>
			<input name="tagsnamelike" id="tagsnamelike" type="text" value="<?php echo $tagsnamelike; ?>" /><br />
			*/
			?>
			<label for="tagsnamesearch"><?php esc_html_e( 'Containing:', 'xili-dictionary' ); ?></label>
			<input name="tagsnamesearch" id="tagsnamesearch" type="text" value="<?php echo sanitize_text_field( wp_unslash( $tagsnamesearch)); ?>" />
			<p class="submit">
				<input type="submit" id="tagssublist" name="tagssublist" value="<?php esc_html_e( 'Sub select…', 'xili-dictionary' ); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" id="notagssublist" name="notagssublist" value="<?php esc_html_e( 'No select…', 'xili-dictionary' ); ?>" />
			</p>
		</fieldset>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php esc_html_e( 'Selection by language', 'xili-dictionary' ); ?></legend>
			<select name="tagsgroup_parent_select" id="tagsgroup_parent_select" style="width:100%;">
				<option value="no_select" ><?php esc_html_e( 'No sub-selection', 'xili-dictionary' ); ?></option>
				<?php
				$checked = ( 'msgid' == $this->subselect ) ? 'selected="selected"' : '';
				echo '<option value="msgid" ' . $checked . ' >' . esc_html__( 'Only MsgID (en_US)', 'xili-dictionary' ) . '</option>';
				$checked = ( 'msgstr' == $this->subselect ) ? 'selected="selected"' : '';
				echo '<option value="msgstr" ' . $checked . ' >' . esc_html__( 'Only Msgstr', 'xili-dictionary' ) . '</option>';
				$checked = ( 'msgstr_0' == $this->subselect ) ? 'selected="selected"' : '';
				echo '<option value="msgstr_0" ' . $checked . ' >' . esc_html__( 'Only Msgstr plural', 'xili-dictionary' ) . '</option>';
				echo $this->build_grouplist();
				echo $this->build_grouplist( 'nottransin_' ); // 2.1.2 - not translated in
				?>
			</select>
			<br />
			<p class="submit">
				<input type="submit" id="subselection" name="subselection" value="<?php esc_html_e( 'Sub select…', 'xili-dictionary' ); ?>" />
			</p>
		</fieldset>
		<?php
	}


}
