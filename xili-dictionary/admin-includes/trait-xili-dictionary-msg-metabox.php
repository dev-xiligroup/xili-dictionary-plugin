<?php
/**
* XD Admin msg metaboxes
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Msg_Metabox {

	/**
	 * Main "dashboard" box in msg edit to display and link the series of msg
	 *
	 * @since 2.0
	 * @updated 2.1.2 - called by action add_meta_boxes
	 *
	 */
	public function add_custom_box_in_post_msg() {
		$msg = esc_html__( 'msg', 'xili-dictionary' );
		/* translators: */
		add_meta_box( 'msg_state', sprintf( esc_html__( 'the entry with the %s', 'xili-dictionary' ), $msg ), array( &$this, 'msg_state_box' ), XDMSG, 'normal', 'high' );
		if ( get_current_screen()->action != 'add' ) {
			// only for edit not new
			/* translators: */
			add_meta_box( 'msg_untranslated_list', sprintf( esc_html__( 'List of %s from entries to translate', 'xili-dictionary' ), $msg ), array( &$this, 'msg_untranslated_list_box' ), XDMSG, 'normal', 'high' );
			if ( current_user_can( 'xili_dictionary_edit_save' ) ) {
				// 2.3.2
				add_meta_box( 'msg_tools_shortcuts', esc_html__( 'Shortcuts to update mo files', 'xili-dictionary' ), array( &$this, 'msg_tools_shortcuts_box' ), XDMSG, 'side' );
			}
		}

	}

	// add messages called by add_filter( 'post_updated_messages' @since 2.1.2
	public function msg_post_messages( $messages ) {
		$messages['post'][33] = esc_html__( 'MO file updating started: see result in meta-box named - Shortcuts... - below buttons', 'xili-dictionary' );
		$messages['post'][34] = esc_html__( 'Local MO updating started: see result in meta-box named - Shortcuts... - below buttons', 'xili-dictionary' );
		return $messages;
	}

	/**
	 * update current .mo
	 *
	 * called add_action( 'add_meta_boxes_' . XDMSG
	 *
	 * to have values before metaboxes built
	 */
	public function msg_update_action( $post ) {
		$extract_array = array();
		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ( '' != $langfolderset ) ? $langfolderset . '/' : '/';
		// doublon
		$this->langfolder = str_replace( '//', '/', $this->langfolder ); // upgrading... 2.0 and sub folder sub
		if ( isset( $_GET['msgupdate'] ) && isset( $_GET['langstr'] ) ) { // shortcut to update .mo - 2.1.2
			check_admin_referer( 'xd-updatemo' );
			$filetype = $_GET['msgupdate'];
			$selectlang = $_GET['langstr'];

			$cur_theme_name = $this->get_option_theme_name();

			if ( is_multisite() ) {
				if ( ( $uploads = xili_upload_dir() ) && false === $uploads['error'] ) {

					if ( 'updatelocal' == $filetype ) {
						// only current site - need tools for other superadmin place
						$local = 'local-';
						$extract_array[ $this->msg_extracted_comments ] = $this->local_tag;
						$extract_array[ 'like-' . $this->msg_extracted_comments ] = true;
						$file = $uploads['path'] . '/local-' . $selectlang . '.mo';

					} else {

						$extract_array['origin'] = array( $cur_theme_name ); // only if assigned to current theme domain

						$local = '';
						$file = $uploads['path'] . '/' . $selectlang . '.mo';

					}
					$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name;
					$mo = $this->from_cpt_to_pomo_wpmu( $selectlang, 'mo', true, $extract_array ); // do diff if not superadmin
				}
			} else { // standalone

				if ( 'updatelocal' == $filetype ) {
					$local = 'local-';
					$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
					$extract_array [ 'like-' . $this->msg_extracted_comments ] = true;
					$file = $this->active_theme_directory . $this->langfolder . 'local-' . $selectlang . '.mo';

				} else {

					$extract_array ['origin'] = array( $cur_theme_name );
					$local = '';
					$file = '';
				}
				$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name;
				$mo = $this->from_cpt_to_pomo( $selectlang, 'mo', $extract_array );
			}

			if ( isset( $mo ) && count( $mo->entries ) > 0 ) {

				if ( false === $this->save_mo_to_file( $selectlang, $mo, $file ) ) {
					/* translators: */
					$this->msg_action_message = sprintf( '<span class="alert">' . esc_html__( 'Error with File %s !', 'xili-dictionary' ) . '</span> ( ' . $file . ' )', $local . $selectlang . '.mo' );
				} else {
					/* translators: */
					$this->msg_action_message = sprintf( esc_html__( 'File %1$s updated with %2$s msgids', 'xili-dictionary' ), $local.$selectlang . '.mo', count( $mo->entries ) );
				}
			} else {
				/* translators: */
				$this->msg_action_message = sprintf( '<span class="alert">' . esc_html__( 'Nothing modified in %s, file not updated', 'xili-dictionary' ) . '</span>', $local . $selectlang . '.mo' );
			}
		}
	}

	// the first lang of msgstr or false for msgid
	public function cur_lang( $post_ID ) {
		$langs = wp_get_object_terms( $post_ID, TAXONAME );
		if ( ! is_wp_error( $langs ) && ! empty( $langs ) ) {
			return apply_filters( 'other_multilingual_plugin_filter_term', $langs[0] );
		} elseif ( ! is_wp_error( $langs ) && empty( $langs ) ) {
			// try to repair if msgstr w/o taxonomy language
			$type = get_post_meta( $post_ID, $this->msgtype_meta, true );
			if ( 'msgid' != $type ) {
				$msgid_id = get_post_meta( $post_ID, $this->msgidlang_meta, true );
				$res = get_post_meta( $msgid_id, $this->msglang_meta, false );
				$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
				if ( '' != $res && is_array( $thelangs ) ) {
					if ( ! empty( $thelangs['msgstrlangs'] ) ) {
						foreach ( $thelangs['msgstrlangs'] as $one_lang => $msgtrs ) {
							if ( ! empty( $msgtrs['msgstr'] ) && $msgtrs['msgstr'] == $post_ID ) {

								// repair
								$ret = wp_set_object_terms( $post_ID, $this->target_lang( $one_lang ), TAXONAME );
								xili_xd_error_log( $msgid_id . ' ---STR- ' . $post_ID . ' ---- ' . $one_lang . ' -- REPAIR -- ' . serialize( $ret ) );

								$the_lang = get_term_by( 'name', $one_lang, TAXONAME );
								return apply_filters( 'other_multilingual_plugin_filter_term', $the_lang );
							}
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Normal metabox : List to display untranslated msgid in target lang like msgstr currently displayed
	 *
	 * @since 2.1.2
	 */
	public function msg_untranslated_list_box( $post ) {
		$post_ID = $post->ID;
		$type = get_post_meta( $post_ID, $this->msgtype_meta, true );
		$msglang = '';
		$message = '';
		$arraylink = array();
		$sortparent = ( ( '' == $this->subselect ) ? '' : '&amp;tagsgroup_parent_select=' . $this->subselect );
		$listlanguages = $this->get_list_languages();
		foreach ( $listlanguages as $language ) {
			$arraylink[] = sprintf( '<a href="%s" >' . $language->name . '</a>', 'post.php?post=' . $post_ID . '&action=edit&workinglang=' . $language->slug );
		}
		$listlink = implode( ' ', $arraylink );
		$working_lang = ( isset( $_GET['workinglang'] ) ) ? $_GET['workinglang'] : '';

		if ( 'msgstr' == $type ) {

			$lang = $this->cur_lang( $post_ID );

			if ( $lang ) {
				$msglang = $lang->slug;

				$this->subselect = ( '' == $working_lang ) ? $msglang : $working_lang;
				$this->searchtranslated = 'not';
				/* translators: */
				$message = sprintf( esc_html__( 'MSGs not translated in %1$s. <em>Sub-select in %2$s</em>', 'xili-dictionary' ), $this->languages_key_slug[ $this->subselect ]['name'], $listlink );
			}
		} else { // msgid

			$this->subselect = $working_lang;
			/* translators: */
			$message = ( '' == $working_lang ) ? sprintf( esc_html__( 'No selection: Sub-select in %s', 'xili-dictionary' ), $listlink ) : sprintf( esc_html__( 'MSGs not translated in %1$s. <em>Sub-select in %2$s</em>', 'xili-dictionary' ), $_GET['workinglang'], $listlink );
			$this->searchtranslated = ( '' == $working_lang ) ? '' : 'not';
		}

	?>
		<p><?php echo $message; ?></p>
		<div id="topbanner">
		</div>
		<div id="tableupdating">
		</div>

		<table class="display" id="linestable">
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

					$this->xili_dict_cpt_row(); /* the lines */
					?>
			</tbody>
		</table>
		<div id="bottombanner">
		</div>
		<?php
		$this->insert_js_for_datatable(
			array(
				'swidth2' => '50%',
				'screen' => 'post-edit',
			)
		);
	}

	/**
	 * insert js for datatable - used in post and in tools
	 *
	 * @since 2.1.2
	 *
	 */
	public function insert_js_for_datatable( $args ) {
		?>
		<script type="text/javascript">

			//<![CDATA[
			jQuery(document).ready( function( $) {

				var termsTable = $( '#linestable' ).dataTable( {
					"iDisplayLength": 20,
					"bStateSave": true,
					"bAutoWidth": false,
					"sDom": '<"topbanner"ipf>rt<"bottombanner"lp><"clear">',
					"sPaginationType": "full_numbers",
					"aLengthMenu": [[20, 30, 60, -1], [20, 30, 60, "<?php esc_html_e( 'All lines', 'xili-dictionary' ); ?>"]],
					"oLanguage": {
						"oPaginate": {
							"sFirst": "<?php esc_html_e( 'First', 'xili-dictionary' ); ?>",
							"sLast": "<?php esc_html_e( 'Last page', 'xili-dictionary' ); ?>",
							"sNext": "<?php esc_html_e( 'Next', 'xili-dictionary' ); ?>",
							"sPrevious": "<?php esc_html_e( 'Previous', 'xili-dictionary' ); ?>"
						},
						"sInfo": "<?php esc_html_e( 'Showing (_START_ to _END_) of _TOTAL_ entries', 'xili-dictionary' ); ?>",
						"sInfoFiltered": "<?php esc_html_e( '(filtered from _MAX_ total entries)', 'xili-dictionary' ); ?>",
						"sEmptyTable": "<?php esc_html_e( 'Empty table', 'xili-dictionary' ); ?>",
						"sInfoEmpty": "<?php esc_html_e( 'No entry', 'xili-dictionary' ); ?>",
						"sLengthMenu": "<?php esc_html_e( 'Show _MENU_ entries', 'xili-dictionary' ); ?>",
						"sSearch": "<?php esc_html_e( 'Filter msg:', 'xili-dictionary' ); ?>"

					},
					"aaSorting": [[1,'asc']],
					"aoColumns": [
						{ "bSearchable": false, "sWidth" : "30px" },
						{ "sWidth" : "<?php echo $args['swidth2']; ?>" },
						{ "bSortable": false, "bSearchable": false },
						{ "bSortable": false, "bSearchable": false, "sWidth" : "105px" },
						{ "bSortable": false, "bSearchable": false, "sWidth" : "70px" } ]
				} );

				$( '#tableupdating' ).hide();
				$( '#linestable' ).css({ visibility:'visible' });

				<?php if ( 'toolbox' == $args['screen'] ) { ?>
					// close postboxes that should be closed
					jQuery( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
					// postboxes setup
					postboxes.add_postbox_toggles( '<?php echo $this->thehook; ?>' );
				<?php } ?>
			});
			//]]>
		</script>
	<?php
	}

	public function msg_state_box() {
		global $post_ID, $post;

		$type = get_post_meta( $post_ID, $this->msgtype_meta, true );

		$this->mo_files_array();

		?>
		<div id="msg-states">
			<?php $this->msg_status_display( $post_ID ); ?>
		</div>
		<div id="msg-states-comments">
			<?php $for_bottom_box = $this->msg_status_comments( $post_ID ); ?>
		</div>
		<div id="msg-states-actions" >
			<strong>
			<?php
			esc_html_e( 'Informations and actions about files .po / mo', 'xili-dictionary' );
			echo ':</strong><br />';
			?>
			<div class="msg-states-actions-left" >
			<?php echo $for_bottom_box['link'] . '<br />'; ?>
			<?php
			$origins = get_the_terms( $post_ID, 'origin' );
			$names = array();
			if ( $origins ) {
				foreach ( $origins as $origin ) {
					$names[] = $origin->name;
				}
				echo esc_html__( 'Come from:', 'xili-dictionary' ) . ' ' . implode( ' ', $names ) . '<br />';
			} else {
				if ( ! $for_bottom_box['state'] ) {
					if ( 'msgid' == $type ) {
						esc_html_e( 'Not yet assigned', 'xili-dictionary' );
					}
				}
			}
			?>
			</div>
			<div class="msg-states-actions-right" >
			<?php
			$context = get_post_meta( $post_ID, $this->ctxt_meta, true );
			$res = $this->is_saved_cpt_in_theme( htmlspecialchars_decode( $post->post_content ), $type, $context );
			/* translators: */
			$save_state = '<br />' . ( ( false === strpos( $res[0], '**</span>' ) ) ? sprintf( esc_html__( 'theme folder %s', 'xili-dictionary' ), $res[0] ) : '' ) . ( ( false == strpos( $res[2], '?</span>' ) ) ? ' (theme local-' . $res[2] . ' )' : '' );
			if ( is_multisite() ) {
				$save_state .= '<br />' . esc_html__( 'this site', 'xili-dictionary' ) . ( ( false === strpos( $res[1], '**</span>' ) ) ? sprintf( esc_html__( 'folder %s', 'xili-dictionary' ), $res[1] ) : ' ' ) . ( ( false == strpos( $res[3], '?</span>' ) ) ? ' (theme local-' . $res[3] . ' )' : '' );
			}

			echo $type . ' <em>' . $post->post_content . '</em> ' . esc_html__( 'saved in ', 'xili-dictionary' ) . $save_state;

			//$plugin_path = 'xili-postinpost/xili-postinpost.php';
			foreach ( $names as $plugin_path ) {
				$plugin_res = $this->is_saved_cpt_in_plugin( $plugin_path, htmlspecialchars_decode( $post->post_content ), $type, $context );
				if ( $plugin_res ) {
					/* translators: */
					echo sprintf( '<br /><small>' . esc_html__( 'Plugin ( %s ):', 'xili-dictionary' ), $this->get_plugin_name( $plugin_path ) ) . '</small> ' . implode( ' - ', $plugin_res ) . '<br />';
				}
			}
			?>
			</div>
			<p class="xdversion">XD v. <?php echo XILIDICTIONARY_VER; ?></p>
		</div>
	<?php
	}

	/**
	 * msg dashboard left
	 *
	 * @since 2.0
	 *
	 */
	public function msg_status_display( $id ) {
		global $post;
		$spanred = '<span class="alert">';
		$spanend = '</span>';

		$type = get_post_meta( $id, $this->msgtype_meta, true );
		// search msgid
		if ( 'msgid' == $type ) {
			$msgid_id = $id;
		} elseif ( 'msgid_plural' == $type ) {
			$temp_post_msg_id_plural = $this->temp_get_post( $id );
			$msgid_id = $temp_post_msg_id_plural->post_parent;
			$temp_post_msg_id = $this->temp_get_post( $msgid_id );
		} else {
			$msgid_id = get_post_meta( $id, $this->msgidlang_meta, true );
		}

		if ( $temp_post_msg_id = $this->temp_get_post( $msgid_id ) ) {

			$res = get_post_meta( $msgid_id, $this->msgchild_meta, false );
			$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();

			$res = get_post_meta( $msgid_id, $this->msglang_meta, false );
			$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();

			if ( isset( $_GET['msgaction'] ) && isset( $_GET['langstr'] ) ) { // action to create child and default line - single or plural...
				check_admin_referer( 'xd-langstr' );
				$target_lang = $_GET['langstr'];

				// verify
				$type_msgstr = ( ! isset( $thechilds['msgid']['plural'] ) ) ? 'msgstr' : 'msgstr_0';
				if ( ! isset( $thelangs['msgstrlangs'][ $target_lang ] ) ) {
					$doit = true;
				} else {
					if ( ! empty( $thelangs['msgstrlangs'][ $target_lang ][ $type_msgstr ] ) ) {
						$temp_post = $this->temp_get_post( $thelangs['msgstrlangs'][ $target_lang ][ $type_msgstr ] );
					}
					if ( $temp_post ) {
						$doit = false;
					} else {
						$doit = true; // ID bu w/o post = not updated
					}
				}

				if ( ( 'msgstr' == $_GET['msgaction'] ) && $doit ) {
					// create post
					if ( ! isset( $thechilds['msgid']['plural'] ) ) {
						/* translators: */
						$msgstr_post_id = $this->insert_one_cpt_and_meta( sprintf( esc_html__( 'XD say to translate in %s:', 'xili-dictionary' ), $target_lang ) . ' ' . $temp_post_msg_id->post_content, null, 'msgstr', 0 );
						wp_set_object_terms( $msgstr_post_id, $this->target_lang( $target_lang ), TAXONAME );
						$thelangs['msgstrlangs'][ $target_lang ]['msgstr'] = $msgstr_post_id;
						update_post_meta( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta( $msgstr_post_id, $this->msgidlang_meta, $msgid_id );

						$translated_post_id = $msgstr_post_id;
						//printf( 'msgstr created in %s <br/>', $target_lang );

					} else {
						// create msgstr_0
						/* translators: */
						$msgstr_post_id = $this->insert_one_cpt_and_meta( sprintf( esc_html__( 'XD say to translate in %s (msgstr[0] ): ', 'xili-dictionary' ), $target_lang ) . ' ' . $temp_post_msg_id->post_content, null, 'msgstr_0', 0 );
						wp_set_object_terms( $msgstr_post_id, $this->target_lang( $target_lang ), TAXONAME );
						$thelangs['msgstrlangs'][ $target_lang ]['msgstr_0'] = $msgstr_post_id;
						update_post_meta( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta( $msgstr_post_id, $this->msgidlang_meta, $msgid_id );

						$translated_post_id = $msgstr_post_id;
						//printf( 'msgstr[0] created in %s <br/>', $target_lang );

						// create msgstr_1
						$temp_post_msg_id_plural = $this->temp_get_post( $thechilds['msgid']['plural'] );
						$content_plural = htmlspecialchars( $temp_post_msg_id_plural->post_content );
						/* translators: */
						$msgstr_1_post_id = $this->insert_one_cpt_and_meta( sprintf( esc_html__( 'XD say to translate in %s (msgstr[1] ): ', 'xili-dictionary' ), $target_lang ) . ' ' . $content_plural, null, 'msgstr_1',  $msgstr_post_id );
						wp_set_object_terms( $msgstr_1_post_id, $this->target_lang( $target_lang ), TAXONAME );
						$thelangs['msgstrlangs'][ $target_lang ]['plural'][1] = $msgstr_1_post_id;
						update_post_meta( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta( $msgstr_1_post_id, $this->msgidlang_meta, $msgid_id );

						//printf( 'msgstr[1] created in %s <br/>', $target_lang );
					}
					// redirect


					$url_redir = admin_url() . 'post.php?post=' . $translated_post_id . '&action=edit';

				?>
	<script type="text/javascript">
	<!--
		window.location= <?php echo "'" . $url_redir . "'"; ?>;
	//-->
	</script><br />
				<?php
				}
			} elseif ( isset( $_GET['msgaction'] ) && 'msgid_plural' == $_GET['msgaction'] && ! isset( $thelangs['msgstrlangs'] ) ) {
				check_admin_referer( 'xd-plural' );
				$msgid_plural_post_id = $this->insert_one_cpt_and_meta( esc_html__( 'XD say id to plural: ', 'xili-dictionary' ) . $temp_post_msg_id->post_content, null, 'msgid_plural', $msgid_id );
				$res = get_post_meta( $msgid_id, $this->msgchild_meta, false );
				$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
				$url_redir = admin_url() . 'post.php?post=' . $msgid_plural_post_id . '&action=edit';
				//2.3
				?>
	<script type="text/javascript">
	<!--
		window.location= <?php echo "'" . $url_redir . "'"; ?>;
	//-->
	</script><br />
<?php

			} elseif ( 'msgid' == $type && isset( $_GET['msgaction'] ) && 'setlocal' == $_GET['msgaction'] ) {
				check_admin_referer( 'xd-setlocal' );
				$extracted_comments = get_post_meta( $msgid_id, $this->msg_extracted_comments, true );
				$extracted_comments = $this->local_tag . ' ' . $extracted_comments;
				update_post_meta( $msgid_id, $this->msg_extracted_comments, $extracted_comments );

			} elseif ( 'msgid' == $type && isset( $_GET['msgaction'] ) && 'unsetlocal' == $_GET['msgaction'] ) {
				check_admin_referer( 'xd-setlocal' );
				$extracted_comments = get_post_meta( $msgid_id, $this->msg_extracted_comments, true );
				$extracted_comments = str_replace( $this->local_tag . ' ', '', $extracted_comments );
				update_post_meta( $msgid_id, $this->msg_extracted_comments, $extracted_comments );
			}

			// display current saved content

			//if ( $type != "msgid" ) {
			$line = esc_html__( 'msgid:', 'xili-dictionary' );
			$line .= '&nbsp;<strong>' . htmlspecialchars( $temp_post_msg_id->post_content ) . '</strong>';
			if ( $post->ID != $msgid_id ) {
				/* translators: */
				$line .= sprintf( __( '( <a href="%1$s" title="link to:%2$d" >%3$s</a> )<br />', 'xili-dictionary' ), 'post.php?post=' . $msgid_id . '&action=edit', $msgid_id, esc_html__( 'Edit' ) );
			} else {
				$line .= '<br />';
			}
			$this->hightlight_line( $line, $type, 'msgid' );
			//}
			if ( isset( $thechilds['msgid']['plural'] ) ) {
				$post_status = get_post_status( $thechilds['msgid']['plural'] );
				$line = '';
				if ( 'trash' == $post_status || false === $post_status ) {
					$line .= $spanred;
				}
				$line .= '<span class="msgid_plural">' . esc_html__( 'msgid_plural:', 'xili-dictionary' ) . '</span>&nbsp;';
				if ( 'trash' == $post_status || false === $post_status ) {
					$line .= $spanend;
				}
				$temp_post_msg_id_plural = $this->temp_get_post( $thechilds['msgid']['plural'] );
				$content_plural = htmlspecialchars( $temp_post_msg_id_plural->post_content );
				$line .= '<strong>' . $content_plural . '</strong> ';
				if ( $post->ID != $thechilds['msgid']['plural'] ) {
					/* translators: */
					$line .= sprintf( __( '( <a href="%1$s" title="link to:%2$d" >%3$s</a> )<br />', 'xili-dictionary' ), 'post.php?post=' . $thechilds['msgid']['plural'] . '&action=edit', $thechilds['msgid']['plural'], esc_html__( 'Edit' ) );
				}
				$this->hightlight_line( $line, $type, 'msgid_plural' );

			} else {
				//2.3
				if ( 'auto-draft' != $post->post_status && ! isset( $thelangs['msgstrlangs'] ) && ! isset( $thechilds['msgid']['plural'] ) ) { // not yet translated

					$nonce_url = wp_nonce_url( 'post.php?post=' . $id . '&action=edit&msgaction=msgid_plural', 'xd-plural' );
					/* translators: */
					printf( __( '&nbsp;<a href="%s" >Create msgid_plural</a>', 'xili-dictionary' ), $nonce_url );
					echo '<br />';
				}
			}

			// display series
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
			if ( isset( $thelangs['msgstrlangs'] ) ) {
				$translated_langs = array();
				echo '<br /><table class="widefat"><thead><tr><th class="column-msgtrans">';
				esc_html_e( 'translated in', 'xili-dictionary' );
				echo '</th><th>‟msgstr”</th></tr></thead><tbody>';
				foreach ( $thelangs['msgstrlangs'] as $curlang => $msgtr ) {

					$strid = 0;
					if ( isset( $msgtr['msgstr'] ) ) {
						$strid = $msgtr['msgstr'];
						$str_plural = false;

						$typeref = 'msgstr';
					} elseif ( isset( $msgtr['msgstr_0'] ) ) {
						$strid = $msgtr['msgstr_0'];
						$str_plural = true;
						// $translated_langs[] = $curlang; // move below - 2.12.2
						$typeref = 'msgstr_0';
					}

					if ( 0 != $strid ) {
						$target_lang = implode( ' ', wp_get_object_terms( $id, TAXONAME, array( 'fields' => 'names' ) ) );

						$temp_post = $this->temp_get_post( $strid );

						if ( $temp_post ) { // if base corrupted - 2.12.2
							echo '<tr class="lang-' . strtolower( $curlang ) . '" ><th><span>';
							printf( '%s : ', $curlang );
							echo '</span></th><td>';
							$translated_langs[] = $curlang;
							$content = htmlspecialchars( $temp_post->post_content );
							$line = '';
							if ( $str_plural ) {
								$line .= '[0] ';
							}

							$line .= '‟<strong>' . $content . '</strong>”';
							$post_status = get_post_status( $strid );
							if ( 'trash' == $post_status || false === $post_status ) {
								$line .= $spanred;
							}
							if ( $post->ID != $strid ) {
								$line .= sprintf( ' ( <a href="%s" title="link to:%d">%s</a> )<br />', 'post.php?post=' . $strid . '&action=edit', $strid, esc_html__( 'Edit' ) );
							} else {
								$line .= '<br />';
							}

							if ( 'trash' == $post_status || false === $post_status ) {
								$line .= $spanend;
							}

							$this->hightlight_line_str( $line, $type, $typeref, $curlang, (int) $id ); // now id

							if ( $str_plural ) {
								$res = get_post_meta( $strid, $this->msgchild_meta, false );
								$strthechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
								foreach ( $strthechilds['msgstr']['plural'] as $key => $strchildid ) {
									$temp_post = $this->temp_get_post( $strchildid );
									$content = htmlspecialchars( $temp_post->post_content );
									$line = '';
									$post_status = get_post_status( $strchildid ); // fixed 2.1
									if ( 'trash' == $post_status || false === $post_status ) {
										$line .= $spanred;
									}
									$line .= sprintf( '[%s] ', $key );
									if ( 'trash' == $post_status || false === $post_status ) {
										$line .= $spanend;
									}
									if ( $post->ID != $strchildid ) {
										$line .= sprintf( '‟<strong>%s</strong>” ( %s )', $content, '<a href="post.php?post=' . $strchildid . '&action=edit" title="link to:' . $strchildid . '">' . esc_html__( 'Edit' ) . '</a>' );
									} else {
										$line .= sprintf( '‟<strong>%s</strong>”', $content );
									}
									$this->hightlight_line_str( $line, $type, 'msgstr_' . $key, $curlang, (int) $id );
									echo '<br />';
								}
											// if possible against current lang add links - compare to count of $strthechilds['msgstr']['plural']

							}
							echo '</td></tr>';
						}
					}
				}

				$this->create_line_lang = '';
				if ( count( $translated_langs ) != count( $listlanguages ) ) {
							//echo '<br />';
					$this->create_line_lang = esc_html__( 'Create msgstr in: ', 'xili-dictionary' );
					foreach ( $listlanguages as $tolang ) {
						if ( ! in_array( $tolang->name, $translated_langs ) ) {
							$nonce_url = wp_nonce_url( 'post.php?post=' . $id . '&action=edit&msgaction=msgstr&langstr=' . $tolang->name, 'xd-langstr' );
							$this->create_line_lang .= sprintf( '&nbsp; <a class="lang-' . strtolower( $tolang->name ) . '" href="%s" >' . $tolang->name . '</a>', $nonce_url );
							echo '<tr class="lang-' . strtolower( $tolang->name ) . '" ><th><span>';
							printf( '%s : ', $tolang->name );
							echo '</span></th><td>';
							printf( '&nbsp; <a class="lang-' . strtolower( $tolang->name ) . '" href="%s" >' . esc_html__( 'Create and edit', 'xili-dictionary' ) . '</a>', $nonce_url );
							echo '</td></tr>';
						}
					}
				}

				echo '</tbody></table>';

			} else {
				$this->create_line_lang = '';
				if ( ! isset( $_POST['msgaction'] ) || ( isset( $_GET['msgaction'] ) && 'msgid_plural' == $_GET['msgaction'] ) ) {
					/* translators: */
					esc_html_e( 'not yet translated.', 'xili-dictionary' );
					echo '&nbsp;';
					printf( esc_html__( 'Status: %s', 'xili-dictionary' ), $post->post_status );
					if ( 'auto-draft' != $post->post_status ) {
						echo '<br /><table class="widefat"><thead><tr><th class="column-msgtrans">';
						esc_html_e( 'Translation in', 'xili-dictionary' );
						echo '</th><th>‟msgstr”</th></tr></thead><tbody>';

						$this->create_line_lang = esc_html__( 'Create msgstr in: ', 'xili-dictionary' );
						foreach ( $listlanguages as $tolang ) {
							$nonce_url = wp_nonce_url( 'post.php?post=' . $id . '&action=edit&msgaction=msgstr&langstr=' . $tolang->name, 'xd-langstr' );
							$this->create_line_lang .= sprintf( '&nbsp; <a class="lang-' . strtolower( $tolang->name ) . '" href="%s" >' . $tolang->name . '</a>', $nonce_url );
							echo '<tr class="lang-' . strtolower( $tolang->name ) . '" ><th><span>';
								printf( '%s : ', $tolang->name );
								echo '</span></th><td>';
								/* translators: */
								printf( '&nbsp; <a class="lang-' . strtolower( $tolang->name ) . '" href="%s" >' . esc_html__( 'Create and edit', 'xili-dictionary' ) . '</a>', $nonce_url );
								echo '</td></tr>';

						}
						echo '</tbody></table>';
					}
				}
			}
		} else {
			/* translators: */
			printf( esc_html__( 'The msgid (%d) was deleted. The msg series must be recreated.', 'xili-dictionary' ), $msgid_id );
		}
	}

	public function hightlight_line( $line, $cur_type, $type ) {
		if ( $cur_type == $type ) {
			echo '<span class="editing msgidstyle">' . $line . '</span>';
		} else {
			echo '<span class="msgidstyle">' . $line . '</span>';
		}
	}

	public function hightlight_line_str( $line, $cur_type, $type, $cur_lang, $lang_or_id ) {
		if ( is_int( $lang_or_id ) ) {
			$lang = ( is_object( $this->cur_lang( $lang_or_id ) ) ) ? $this->cur_lang( $lang_or_id )->name : $this->cur_lang( $lang_or_id ); // false if msgid
		} else {
			$lang = $lang_or_id;
		}

		if ( $cur_type == $type && $cur_lang == $lang ) {
			echo '<span class="editing msgstrstyle">' . $line.'</span>';
		} else {
			echo '<span class="msgstrstyle">' . $line . '</span>';
		}
	}

	/**
	 * private function for admin page : one line of taxonomy
	 *
	 *
	 */
	public function xili_dict_cpt_row( $listby = 'name', $tagsnamelike = '', $tagsnamesearch = '' ) {
		/* the lines */

		// select msg
		$special_query = false;
		switch ( $this->subselect ) {

			case 'msgid':
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgid';
				break;
			case 'msgstr':
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgstr';
				break;
			case 'msgstr_0':
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgstr_0';
				break;
			case '':
				$meta_key_val = '';
				$meta_value_val = '';
				break;
			default:
				if ( false !== strpos( $this->subselect, 'only=' ) ) {
					$exps = explode( '=', $this->subselect );
					$special_query = 'strlang';
					$curlang = $exps[1];

				} else {
					if ( false !== strpos( $this->subselect, 'nottransin_' ) ) {
						$exps = explode( '_', $this->subselect );
						$special_query = 'idlang';
						$curlang = $exps[1];
						$this->searchtranslated = 'not'; // 2.1.2
					} else {
						// msgid + language
						$curlang = $this->subselect;
						$special_query = 'idlang';
					}
				}
		}
		if ( 'idlang' == $special_query ) {
			if ( 'not' != $this->searchtranslated ) {
				$listdictiolines = $this->get_cpt_msgids( $curlang );
			} else {
				$listdictiolines = $this->get_cpt_msgids( $curlang, 'mo', array(), true ); // search not translated in target language
			}
		} elseif ( 'strlang' == $special_query ) {
			$listdictiolines = get_posts(
				array(
					'numberposts' => -1,
					'offset' => 0,
					'category' => 0,
					'orderby' => 'ID',
					'order' => 'ASC',
					'include' => array(),
					'exclude' => array(),
					'post_type' => XDMSG,
					'suppress_filters' => true,
					's' => $tagsnamesearch,
					'tax_query' => array(
						array(
							'taxonomy' => TAXONAME,
							'field' => 'name',
							'terms' => $curlang,
						),
					),
					'meta_query' => array(
						array(
							'key' => $this->msgtype_meta,
							'value' => array( 'msgstr', 'msgstr_0', 'msgstr_1' ),
							'compare' => 'IN',
						),
					),
				)
			);

		} else {
			$listdictiolines = get_posts(
				array(
					'numberposts' => -1,
					'offset' => 0,
					'category' => 0,
					'orderby' => 'ID',
					'order' => 'ASC',
					'include' => array(),
					'exclude' => array(),
					'meta_key' => $meta_key_val,
					'meta_value' => $meta_value_val,
					'post_type' => XDMSG,
					'suppress_filters' => true,
					's' => $tagsnamesearch,
				)
			);
		}
		$class = '';
		$this->mo_files_array();

		foreach ( $listdictiolines as $dictioline ) {

			$class = ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || " class='alternate'" == $class ) ? '' : " class='alternate'";

			$type = get_post_meta( $dictioline->ID, $this->msgtype_meta, true );
			$context = get_post_meta( $dictioline->ID, $this->ctxt_meta, true );

			$res = $this->is_saved_cpt_in_theme( $dictioline->post_content, $type, $context );

			$save_state = $res[0] . ' (local-' . $res[2] . ' )'; // improve for str and multisite

			if ( is_multisite() ) {
				$save_state .= '<br />' . esc_html__( 'this site', 'xili-dictionary' ) . ': ' . $res[1] . ' (local-' . $res[3] . ' )';
			}

			$origins = get_the_terms( $dictioline->ID, 'origin' );
			$names = array();
			if ( $origins ) {
				foreach ( $origins as $origin ) {
					$names[] = $origin->name;
				}
				$p = '';
				foreach ( $names as $plugin_path ) {
					$plugin_res = $this->is_saved_cpt_in_plugin( $plugin_path, htmlspecialchars_decode( $dictioline->post_content ), $type, $context );
					if ( $plugin_res ) {
						$p .= sprintf( '<small>' . esc_html__( 'P{%s}:', 'xili-dictionary' ), $this->get_plugin_name( $plugin_path) ) . ' ' . implode( ' - ', $plugin_res ) . '</small><br />';
					}
				}
				$save_state .= '<br />' . $p;
			}

			$edit = "<a href='post.php?post=$dictioline->ID&action=edit' >" . esc_html__( 'Edit' ) . '</a></td>';

			$line = "<tr id='cat-$dictioline->ID'$class>
				<td scope='row' style='text-align: center'>$dictioline->ID</td>

				<td>" . htmlspecialchars( $dictioline->post_content ) . '</td>

				<td>';
			echo $line;
			$this->msg_link_display( $dictioline->ID, false, $dictioline );
			$line = "</td>
				<td class='center'>$save_state</td>

				<td class='center'>$edit</td>\n\t</tr>\n"; /*to complete*/
			echo $line;

		}
	}

	/**
	 * display msg comments
	 *
	 * @param post ID
	 *
	 */
	public function msg_status_comments( $id ) {
		global $post;
		$type = get_post_meta( $id, $this->msgtype_meta, true );
		// search msgid
		if ( 'msgid' == $type ) {
			$target_id = $id;
		} elseif ( 'msgid_plural' == $type ) {
			$temp_post = $this->temp_get_post( $id );
			$target_id = $temp_post->post_parent;
		} else {
			$target_id = get_post_meta( $id, $this->msgidlang_meta, true );
		}
		$for_bottom_box = array(
			'link' => '',
			'state' => false,
		);
		if ( $temp_post = $this->temp_get_post( $target_id ) ) {

			$ctxt = get_post_meta( $target_id, $this->ctxt_meta, true );
			if ( '' != $ctxt && 'msgid' != $type ) {
				printf( '<strong>ctxt:</strong> %s <br /><br />', $ctxt );
			}
			if ( 'msgid' == $type ) {
				if ( isset( $_GET['msgaction'] ) && 'addctxt' == $_GET['msgaction'] ) {
					?>
<label for="add_ctxt"><?php esc_html_e( 'Context', 'xili-dictionary' ); ?></label>
<input id="add_ctxt" name="add_ctxt" value="<?php echo $ctxt; ?>" style="width:80%" />
					<?php

				} else {
					if ( '' != $ctxt ) {
						printf( '<strong>ctxt:</strong> %s <br /><br />', $ctxt );
						/* translators: */
						printf( __( '&nbsp;<a href="%s" >Edit context</a>', 'xili-dictionary' ), 'post.php?post=' . $id . '&action=edit&msgaction=addctxt' );
					} else {
						if ( 'auto-draft' != $post->post_status ) {
							// link to add ctxt
							// /* translators: */
							printf( __( '&nbsp;<a href="%s" >Create context</a>', 'xili-dictionary' ), 'post.php?post=' . $id . '&action=edit&msgaction=addctxt' );
						} else {
							/* translators: */
							echo '&nbsp;<em>' . sprintf( esc_html__( 'After saving this msgid as draft,%s it will be possible to add a context', 'xili-dictionary' ), '<br />' ) . '</em>';
						}
					}
				}
			}
			// local or not
			$linktotax = '';

			$extracted_comments = get_post_meta( $target_id, $this->msg_extracted_comments, true );
			if ( '' != $extracted_comments ) {

				$pattern = '/([^local\]].*?)from\s(.*?)\swith/';
				$matches = array();
				if ( 1 == preg_match( $pattern, $extracted_comments, $matches ) ) {

					$search = '';
					if ( 'msgid' == $type && false !== strpos( $extracted_comments, 'name from' ) ) {
						$search = '&s=' . str_replace( ' ', '+', $temp_post->post_content );
					}
					/* translators: */
					$linktotax = sprintf( '<a href="%1s" >%2s</a>', 'edit-tags.php?taxonomy=' . $matches[2] . '&post_type=post' . $search, sprintf( esc_html__( 'Return to %s list', 'xili-dictionary' ), $matches[2] ) );

				}
			}

			echo '<p>';

			if ( '' != $extracted_comments ) {
				/* translators: */
				printf( esc_html__( 'Extracted comments: %s', 'xili-dictionary' ) . '<br />', $extracted_comments );
			}

			$translator_comments = get_post_meta( $target_id, $this->msg_translator_comments, true );
			if ( '' != $translator_comments ) {
				/* translators: */
				printf( esc_html__( 'Translator comments: %s', 'xili-dictionary' ) . '<br />', $translator_comments );
			}
			$flags = get_post_meta( $target_id, $this->msg_flags, true );
			if ( '' != $flags ) {
				/* translators: */
				printf( esc_html__( 'Flags: %s', 'xili-dictionary' ) . '<br />', $flags );
			}

			echo '</p>';
			if ( 'msgstr' == $type || 'msgstr_0' == $type ) {
				$translator_comments = get_post_meta( $id, $this->msg_translator_comments, true );
				//if ( $translator_comments != "") printf( esc_html__( 'Msgstr Translator comments: %s', 'xili-dictionary' ) . '<br />', $translator_comments );

				?>
<label for="add_translator_comments"><?php esc_html_e( 'msgstr Translator comments', 'xili-dictionary' ); ?></label>
<input id="add_translator_comments" name="add_translator_comments" value="<?php echo $translator_comments; ?>" style="width:80%" />
				<?php
			}

			$lines = $temp_post->post_excerpt;
			if ( '' != $lines ) {
				echo '<p>';
				/* translators: */
				printf( esc_html__( 'Lines: %s', 'xili-dictionary' ) . '<br />', $lines );
				echo '</p>';
			}
			if ( current_user_can( 'xili_dictionary_admin' ) ) {
				/* translators: */
				echo '<p><strong>' . sprintf( __( 'Return to <a href="%s" title="Go to msg list">msg list</a>', 'xili-dictionary' ), $this->xd_settings_page) . '</strong> ' . $linktotax . '</p>';
			} // 2.3.2
			//echo ( $this->create_line_lang != "" ) ? '<p><strong>' . $this->create_line_lang.'</strong></p>' : "-";

			if ( 'msgid' == $type ) {
				if ( ( '' == $extracted_comments ) || ( '' != $extracted_comments && false === strpos( $extracted_comments, $this->local_tag . ' ' ) ) ) {

					$nonce_url = wp_nonce_url( 'post.php?post=' . $id . '&action=edit&msgaction=setlocal', 'xd-setlocal' );
					/* translators: */
					$for_bottom_box['link'] = sprintf( __( 'Set in theme (<a href="%s" >set local</a>)', 'xili-dictionary' ), $nonce_url );

				} else {
					$nonce_url = wp_nonce_url( 'post.php?post=' . $id . '&action=edit&msgaction=unsetlocal', 'xd-setlocal' );
					/* translators: */
					$for_bottom_box['link'] = sprintf( __( 'Set in local (<a href="%s" >unset</a>)', 'xili-dictionary' ), $nonce_url );
					$for_bottom_box['state'] = true; // false by default
				}
			}
		} else {
			/* translators: */
			printf( esc_html__( 'The msgid (%d) was deleted. The msg series must be recreated and commented.', 'xili-dictionary' ), $target_id );
			if ( current_user_can( 'xili_dictionary_admin' ) ) {
				echo '<p><strong>' . sprintf( __( 'Return to <a href="%s" title="Go to msg list">msg list</a>', 'xili-dictionary' ), $this->xd_settings_page ) . '</strong></p>';
			}
		}
		return $for_bottom_box;
	}

	public function get_list_languages() {
		$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );
		$this->languages_key_slug = array();
		$this->languages_names = array();
		foreach ( $listlanguages as $language ) {
			$this->languages_key_slug[ $language->slug ] = array(
				'name' => $language->name,
				'description' => $language->description,
			);
			$this->languages_names[ $language->slug ] = $language->name; // 2.6.1 for importing pot
		}
		return $listlanguages;
	}

	/**
	 * display shortcut links to update mo
	 *
	 * called add_meta_box( 'msg_tools_shortcuts'
	 *
	 * @since 2.3.2
	 */
	public function msg_tools_shortcuts_box( $post ) {
		$post_ID = $post->ID;
		$lang = $this->cur_lang( $post_ID );

		if ( $lang ) {
			$link_theme_mo = wp_nonce_url( admin_url() . 'post.php?post=' . $post_ID . '&action=edit&msgupdate=updatetheme&langstr=' . $lang->name . '&message=33', 'xd-updatemo' );
			$link_local_mo = wp_nonce_url( admin_url() . 'post.php?post=' . $post_ID . '&action=edit&msgupdate=updatelocal&langstr=' . $lang->name . '&message=34', 'xd-updatemo' );

			$cur_theme_name = $this->get_option_theme_name();
			/* translators: */
			echo '<p>' . sprintf( esc_html__( 'This msg translation is in %1$s (%2$s)', 'xili-dictionary' ), $lang->description, $lang->name ) . '</p>';
			echo '<h4>' . esc_html__( 'Updating shortcuts', 'xili-dictionary' ) . '</h4>';

			if ( $this->count_msgids( $lang->name, true ) > 0 ) {
				echo '<p>' . sprintf( '<a class="action-button blue-button" onClick="verifybefore(1)" href="%2$s" >' . esc_html__( 'Update', 'xili-dictionary' ) . ' local-%3$s.mo</a>', '#', '#', $lang->name ) . '</p>';
			} else {
				/* translators: */
				echo '<p class="action-button grey-button">' . sprintf( esc_html__( 'No local translated msgid to be saved in %s', 'xili-dictionary' ), ' local-' . $lang->name . '.mo' ) . '</p>';
			}
			/* translators: */
			echo '<p>' . sprintf( esc_html__( 'It is possible to update the .mo files of current theme %s', 'xili-dictionary' ), '<strong>' . $cur_theme_name . '</strong>' ) . '</p>';

			if ( current_user_can( 'xili_dictionary_admin' ) ) {

				echo '<p><em>' . esc_html__( 'Before to use this button, it is very important that you verify that your term list is quite achieved inside the dictionary. It is because the original .mo delivered with theme is updated (erased) !!!', 'xili-dictionary' ) . '</em></p>';

				if ( $this->count_msgids( $lang->name, false, $cur_theme_name ) > 0 ) {
					/* translators: */
					echo '<p>' . sprintf( '<a class="action-button grey-button" onClick="verifybefore(0)" href="%1$s" >' . esc_html__( 'Update', 'xili-dictionary' ) . ' %3$s.mo</a>', '#', '#', $lang->name ) . '</p>';
				} else {
					/* translators: */
					echo '<p class="action-button grey-button">' . sprintf( esc_html__( 'No translated msgid to be saved in %s', 'xili-dictionary' ), $lang->name . '.mo' ) . '</p>';
				}
			}

			//echo '<p>- ' . sprintf( '<a href="%1$s" >%3$s.mo</a><br />- <a href="%2$s" >' . esc_html__( 'local', 'xili-dictionary' ) . '-%3$s.mo</a>',$link_theme_mo, $link_local_mo, $lang->name) . '</p>';
			echo '<small>' . $this->msg_action_message . '</small>';

		} else {

			echo '<p>' . esc_html__( 'Links are available if a translation (msgstr) is edited.', 'xili-dictionary' ) . '</p>';
		}

		if ( $lang ) {
		?>

		<p class="xdversion">XD v. <?php echo XILIDICTIONARY_VER; ?></p>
		<script type="text/javascript">
function verifybefore(id) {
 var link = new array();

 link[0] = "<?php echo str_replace( 'amp;', '', $link_theme_mo ); ?>";
 link[1] = "<?php echo str_replace( 'amp;', '', $link_local_mo ); ?>";
 var confirmmessage = "<?php esc_html_e( 'Are you sure you want to update mo ? ', 'xili-dictionary' ); ?>";
 var message = "Action Was Cancelled By User ";
 if (confirm(confirmmessage) ) {

	window.location = link[id];

 } else {

 // alert(message);
}

}
</script>
		<?php
		}
	}

	/**
	 * Detect if cpt are saved in theme's languages folder
	 * @since 2.0
	 *
	 */
	public function is_saved_cpt_in_theme( $msg, $type, $context = '' ) {
		$thelist = array();
		$thelistsite = array();
		$thelist_local = array();
		$thelistsite_local = array();
		$outputsite = '';
		$localfile_site = '';
		$output = '';
		$localfile = '';

		if ( defined( 'TAXONAME' ) ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );

			foreach ( $listlanguages as $reflanguage ) {
				if ( isset( $this->theme_mos[ $reflanguage->slug ] ) ) {
					if ( $this->is_intheme_mos( $msg, $type, $this->theme_mos[ $reflanguage->slug ], $context ) ) {
						$thelist[] = $reflanguage->name . '.mo';
					}
				}
				// local data
				if ( isset( $this->local_mos[ $reflanguage->slug ] ) ) {
					if ( $this->is_intheme_mos( $msg, $type, $this->local_mos[ $reflanguage->slug ], $context ) ) {
						$thelist_local[] = $reflanguage->name . '.mo';
					}
				}

				if ( is_multisite() ) {
					if ( isset( $this->file_site_mos[ $reflanguage->slug ] ) ) {
						if ( $this->is_intheme_mos( $msg, $type, $this->file_site_mos[ $reflanguage->slug ], $context ) ) {
							$thelistsite[] = $reflanguage->name . '.mo';
						}
					}
					// local data
					if ( isset( $this->file_site_local_mos[ $reflanguage->slug ] ) ) {
						if ( $this->is_intheme_mos( $msg, $type, $this->file_site_local_mos[ $reflanguage->slug ], $context ) ) {
							$thelistsite_local[] = $reflanguage->name . '.mo';
						}
					}
				}
			}

			$output = ( array() == $thelist ) ? '<br /><small><span style="color:black" title="' . esc_html__( 'No translations saved in theme’s .mo files', 'xili-dictionary' ) . '">**</span></small>' : '<br /><small><span style="color:green" title="' . esc_html__( 'Original with translations saved in theme’s files: ', 'xili-dictionary' ) . '" >' . implode( ', ', $thelist ) . '</small></small>';

			$localfile = ( array() == $thelist_local ) ? '<small><span style="color:black" title="' . esc_html__( 'No translations saved in local-xx_XX .mo files', 'xili-dictionary' ) . '">?</span></small>' : '<small><span style="color:green" title="' . esc_html__( 'Original with translations saved in local-xx_XX files: ', 'xili-dictionary' ) . '" >' . implode( ', ', $thelist_local ) . '</small></small>';

			if ( is_multisite() ) {

				$outputsite = ( array() == $thelistsite ) ? '<br /><small><span style="color:black" title="' . esc_html__( 'No translations saved in site’s .mo files', 'xili-dictionary' ) . '">**</span></small>' : '<br /><small><span style="color:green" title="' . __( "Original with translations saved in site's files: ", 'xili-dictionary' ) . '" >' . implode( ', ', $thelistsite ) . '</small></small>';

				$localfile_site = ( array() == $thelistsite_local ) ? '<small><span style="color:black" title="' . esc_html__( 'No translations saved in site’s local .mo files', 'xili-dictionary' ) . '">?</span></small>' : '<small><span style="color:green" title="' . esc_html__( 'Original with translations saved in site’s local files: ', 'xili-dictionary' ) . '" >' . implode( ', ', $thelistsite_local ) . '</small></small>';

			}

			return array( $output, $outputsite, $localfile, $localfile_site );
		}
	}

	public function is_saved_cpt_in_plugin( $plugin_path, $msg, $type, $context = '' ) {
		$thelist = array();
		if ( defined( 'TAXONAME' ) ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );

			foreach ( $listlanguages as $reflanguage ) {
				if ( isset( $this->plugin_mos[ $plugin_path ][ $reflanguage->slug ] ) ) {
					if ( $this->is_intheme_mos( $msg, $type, $this->plugin_mos[ $plugin_path ][ $reflanguage->slug ], $context ) ) {
						if ( isset( $this->plugin_mos[ $plugin_path ]['WLD'][ $reflanguage->slug ] ) ) {
							$thelist[] = '*&nbsp;' . $reflanguage->name . '.mo'; //2.10.1
						} else {
							$thelist[] = $reflanguage->name . '.mo';
						}
					}
				}
			}
			return $thelist;
		}
	}

}
