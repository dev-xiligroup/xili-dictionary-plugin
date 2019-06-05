<?php

/**
* XD Admin class help and pointer functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Help {

	/**
	 * Contextual help
	 *
	 * @since 1.2.2
	 */
	public function add_help_text( $contextual_help, $screen_id, $screen ) {

		$more_infos =
		'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
		'<p>' . __( '<a href="http://wiki.xiligroup.org" target="_blank">Xili Plugins Documentation and WIKI</a>', 'xili-dictionary' ) . '</p>' .
		'<p>' . __( '<a href="http://wordpress.org/plugins/xili-dictionary/" target="_blank">Xili-dictionary Plugin Documentation</a>', 'xili-dictionary' ) . '</p>' .
		'<p>' . __( '<a href="http://codex.wordpress.org/" target="_blank">WordPress Documentation</a>', 'xili-dictionary' ) . '</p>' .
		'<p>' . __( '<a href="http://dev.xiligroup.com/?post_type=forum" target="_blank">Support Forums</a>', 'xili-dictionary' ) . '</p>';

		if ( 'xdmsg_page_dictionary_page' == $screen->id ) {
			$about_infos =
			'<p>' . /* translators: */ sprintf( __( 'xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the post tables of WordPress as custom post type (%s). With this dictionary, it is possible to create and update .mo file in the current theme folder. And more...', 'xili-dictionary' ), '<em>' . $this->xdmsg . '</em>' ) .
			'</p>' .
			'<p>' . __( 'Things to remember to set xili-dictionary:', 'xili-dictionary' ) . '</p>' .
			'<ul>' .
			'<li>' . __( 'Verify that the theme is localizable (like WP bundled themes from twentyten to twentyfourteen).', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'Define the list of targeted languages.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'Prepare a sub-folder like /languages/ for .po and .mo files in each language (use the default delivered with the theme or add the pot of the theme and put them inside.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'If you have files: import them to create a base dictionary. If not : add a term or use buttons import and/or build in metabox.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'For xili-language, xili-dictionary creates local-xx_YY files (like local-fr_FR for french) containing customized website inputs to avoid mixing with items of the theme itself. .mo is the file used and .po is the text form.', 'xili-dictionary' ) . '</li>' .
			'</ul>';

			$screen->add_help_tab(
				array(
					'id' => 'about-xili-dictionary',
					'title' => __( 'About xili-dictionary', 'xili-dictionary' ),
					'content' => $about_infos,
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'more-infos',
					'title' => __( 'For more infos', 'xili-dictionary' ),
					'content' => $more_infos,
				)
			);

		} elseif ( in_array( $screen->id, array( 'xdmsg', 'edit-xdmsg' ) ) ) {
			if ( 'edit-xdmsg' == $screen->id ) {

				$about_infos_edit_msg =
				'<p>' . __( 'Things to remember about the list of translations (msgstr) of term (msgid) with xili-dictionary:', 'xili-dictionary' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'Column Metas give infos about type of msg and his language', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'Links in Metas redirect to msg in another language if translation exists.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'It is possible to filter according language or the Origin of msg.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'When type of MSG is ‘local’, the couple msgid/msgstr is saved in a local-xx_YY file of current theme. This translation overhides all the others.', 'xili-dictionary' ) . '</li>' .
				'</ul>' .
				/* translators: */
				'<p>' . sprintf( __( 'Look %1$s this page %2$s to find a dynamic full list and more tools.', 'xili-dictionary' ), '<a href="edit.php?post_type=xdmsg&page=dictionary_page" >', '</a>' ) . '</p>';

				$screen->add_help_tab(
					array(
						'id' => 'about-xili-dictionary-msg',
						'title' => __( 'About MSG list page', 'xili-dictionary' ),
						'content' => $about_infos_edit_msg,
					)
				);

			} else {
				// edit or new
				$about_infos_msg =
				'<p>' . __( 'Things to remember before to create or edit a new translation (msgstr) or a term (msgid) with xili-dictionary:', 'xili-dictionary' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'As in po files, msgid must be unique if no context is specified.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'msgstr is attached (taxonomy) to a language.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'if msgstr is edited, the box with msg series show untranslated msg.', 'xili-dictionary' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'How to update mo files ?', 'xili-dictionary' ) . '</p>' .
				'<ul>' .
				'<li>' . /* translators: */ sprintf( __( 'if msgstr is edited, the box - shortcuts to update mo files - is available to quickly update local-xx_YY.mo file w/o going to %s page.', 'xili-dictionary' ), '<a href="edit.php?post_type=xdmsg&page=dictionary_page">' . __( 'Tools', 'xili-dictionary' ) . '</a>' ) . __( 'The language of mo file is here the language of msgstr being edited.', 'xili-dictionary' ) . '</li>' .
				'</ul>' .
				'<p>' . /* translators: */ sprintf( __( 'Look %1$s this page %2$s to find more tools.', 'xili-dictionary' ), '<a href="edit.php?post_type=xdmsg&page=dictionary_page" >', '</a>' ) . '</p>';

				$screen->add_help_tab(
					array(
						'id' => 'about-xili-dictionary-msg',
						'title' => __( 'About MSG edit', 'xili-dictionary' ),
						'content' => $about_infos_msg,
					)
				);

			}

			$screen->add_help_tab(
				array(
					'id' => 'more-infos',
					'title' => __( 'For more infos', 'xili-dictionary' ),
					'content' => $more_infos,
				)
			);

		} elseif ( 'edit-category' == $screen->id ) {
			// 2.3.8
			$wikilink = $this->wikilink . '/index.php/Xili-language:_languages_list_insertion_in_nav_menu';
			$to_remember =
			'<p><strong>' . __( 'Things to remember to insert terms of category in dictionary msg list:', 'xili-dictionary' ) . '</strong></p>' .
			'<ul>' .
			'<li>' . __( 'Import Categories terms button: Transfer / import terms (name, description) as msg in dictionary.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'Display this Categories Terms button: Switch to list of these msgs in dictionary.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'In table, column Language:.', 'xili-dictionary' ) .
			'<ul>' .
			'<li>' . __( 'Indications if terms are present in .mo files.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'Display in XD button: Show only name and description of the selected category in XD msg list.', 'xili-dictionary' ) . '</li>' .
			'</ul></li>' .
			'<li>' . __( 'Find more infos in forum and wiki.', 'xili-dictionary' ) . '</li>' .
			'</ul><p>'
			/* translators: */
			. sprintf( __( '<a href="%s" target="_blank">Xili Wiki Documentation</a>', 'xili-dictionary' ), $wikilink ) . '</p>';

			$screen->add_help_tab(
				array(
					'id' => 'xili-language-list',
					/* translators: */
					'title' => sprintf( __( 'About %s dictionary tools', 'xili-dictionary' ), '[©xili]' ),
					'content' => $to_remember,
				)
			);

		} elseif ( 'edit-origin' == $screen->id ) {
			$wikilink = $this->wikilink;
			$to_remember =
			'<p><strong>' . __( 'Things to remember about Origin in xili-dictionary', 'xili-dictionary' ) . '</strong></p>' .
			'<ul>' .
			'<li>' . __( 'Origin is a taxonomy to describe the origin of a msgid. So it is possible to share a msg between several theme and rebuild a new language file.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'To manage language file from a plugin, it is important to have only one origin per plugin. The plugin uses this origin to know from where is the msg and to build in the good sub-folder the language file (mo or po).', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'In multisite context (WordPress Network), only superadmin can save language files to plugin folder.', 'xili-dictionary' ) . '</li>' .
			'</ul>' .
			'<p>' . /* translators: */ sprintf( __( '<a href="%s" target="_blank">Xili Wiki Documentation</a>', 'xili-dictionary' ), $wikilink ) . '</p>';

			$screen->add_help_tab(
				array(
					'id' => 'xd-edit-origin',
					/* translators: */
					'title' => sprintf( __( 'About %s dictionary origins', 'xili-dictionary' ), '[©xili]' ),
					'content' => $to_remember,
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'more-infos',
					'title' => __( 'For more infos', 'xili-dictionary' ),
					'content' => $more_infos,
				)
			);
		} elseif ( 'edit-writer' == $screen->id ) {
			$wikilink = $this->wikilink;
			$to_remember =
			'<p><strong>' . __( 'Things to remember about Writer in xili-dictionary', 'xili-dictionary ' ) . '</strong></p>' .
			'<ul>' .
			'<li>' . __( 'Writer is a hierarchic taxonomy to describe writer or translator of a msg. It can be used as is.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'The hierarchy can be used to group like in office or by language.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'More settings in next releases.', 'xili-dictionary' ) . '</li>' .
			'</ul>' .
			'<p>' . /* translators: */ sprintf( __( '<a href="%s" target="_blank">Xili Wiki Documentation</a>', 'xili-dictionary' ), $wikilink ) . '</p>';

			$screen->add_help_tab(
				array(
					'id' => 'xd-edit-origin',
					/* translators: */
					'title' => sprintf( __( 'About %s dictionary writers', 'xili-dictionary' ), '[©xili]' ),
					'content' => $to_remember,
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'more-infos',
					'title' => __( 'For more infos', 'xili-dictionary' ),
					'content' => $more_infos,
				)
			);

		} elseif ( 'xdmsg_page_import_dictionary_page' == $screen->id ) {
			if ( isset( $_GET['scan'] ) ) {
				$to_remember =
				'<p><strong>' . __( 'Things to remember about scanning source files and importing inside xili-dictionary', 'xili-dictionary' ) . '</strong></p>' .
				'<ul>' .
				'<li>' . __( 'Here, you can scan files from the current theme or a selected plugin to import translatable entries (terms) in dictionary.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'Each msg (id) become a “custom post type xdmsg” and ready to be translated. Use this way only if the author do not provide po or pot files.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'Msgid attributes are filled with Origin (theme or plugin) and comments available in sources to help translation.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'If you want to create a pot file just check the option. This file will be saved in the language sub-folder.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'During scanning process, a temporary window appears at bottom of the screen with some progressing infos.', 'xili-dictionary' ) . '</li>' .
				'</ul>' .
				'<p>' . /* translators: */ sprintf( __( '<a href="%s" target="_blank">Xili Wiki Documentation</a>', 'xili-dictionary' ), $this->wikilink ) . '</p>';

				$screen->add_help_tab(
					array(
						'id' => 'xd-import-files',
						/* translators: */
						'title' => sprintf( __( 'About scanning source files with %s dictionary', 'xili-dictionary' ), '[©xili]' ),
						'content' => $to_remember,
					)
				);

			} else {
				$to_remember =
				'<p><strong>' . __( 'Things to remember about importing mo/po files in xili-dictionary', 'xili-dictionary' ) . '</strong></p>' .
				'<ul>' .
				'<li>' . __( 'Importing process inserts po/mo files contents inside dictionary. Each msg (id or str) become a “custom post type xdmsg”.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'Files are coming from current theme or installed plugin languages folders.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'Msg attributes are filled with Origin, language, comment infos. A msgstr is linked to his msgid if it is a translation.', 'xili-dictionary' ) . '</li>' .
				'<li>' . __( 'During importing process, a temporary window appears at bottom of the screen with some progressing infos.', 'xili-dictionary' ) . '</li>' .
				'</ul>' .
				/* translators: */
				'<p>' . sprintf( __( '<a href="%s" target="_blank">Xili Wiki Documentation</a>', 'xili-dictionary' ), $this->wikilink ) . '</p>';

				$screen->add_help_tab(
					array(
						'id' => 'xd-import-files',
						/* translators: */
						'title' => sprintf( __( 'About %s dictionary files importing', 'xili-dictionary' ), '[©xili]' ),
						'content' => $to_remember,
					)
				);
			}
		} elseif ( 'xdmsg_page_download_dictionary_page' == $screen->id ) {

			$wikilink = $this->wikilink;
			$to_remember =
			'<p><strong>' . __( 'Things to remember about download language files with xili-dictionary', 'xili-dictionary' ) . '</strong></p>' .
			'<ul>' .
			'<li>' . __( 'With this screen, it is possible to choose a language file (.mo or .po) to download to your computer. Useful for further works or archiving.', 'xili-dictionary' ) . '</li>' .
			'<li>' . __( 'When the selection is successful, click prepare and the link for download appears in the green message space under the title.', 'xili-dictionary' ) . '</li>' .
			'</ul>' .
			/* translators: */
			'<p>' . sprintf( __( '<a href="%s" target="_blank">Xili Wiki Documentation</a>', 'xili-dictionary' ), $wikilink ) . '</p>';

			$screen->add_help_tab(
				array(
					'id' => 'xd-download-files',
					/* translators: */
					'title' => sprintf( __( 'About %s dictionary download files', 'xili-dictionary' ), '[©xili]' ),
					'content' => $to_remember,
				)
			);
			$screen->add_help_tab(
				array(
					'id' => 'more-infos',
					'title' => __( 'For more infos', 'xili-dictionary' ),
					'content' => $more_infos,
				)
			);
		}
		return $contextual_help;
	}

	/**
	 * News pointer for tabs
	 *
	 * @since 2.14
	 *
	 */
	public function localize_admin_js( $case_news, $news_id ) {
		$about = __( 'Docs about xili-dictionary', 'xili-dictionary' );
		$pointer_edge = '';
		$pointer_at = '';
		$pointer_my = '';
		$pointer_div = '';
		$pointer_dismiss = '';

		switch ( $case_news ) {

			case 'xd_new_version':
				$pointer_text = '<h3>' . esc_js( __( 'xili-dictionary updated', 'xili-dictionary' ) ) . '</h3>';
				/* translators: */
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'xili-dictionary was updated to version %s', 'xili-dictionary' ), XILIDICTIONARY_VER ) ) . '.</p>';
				/* translators: */
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'This new version %s is compatible with WP 5.2 and XL 2.23+ able to import parent sources if child theme active, writers are displayed in list, also compatible with Polylang taxonomy and xml importing.', 'xili-dictionary' ), XILIDICTIONARY_VER ) ) . '</p>';
				/* translators: */
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'In previous version of %s, if you switch language of dashboard is other than in en_US, then the import process of sources msgid will try to import translations of chosen language.', 'xili-dictionary' ), XILIDICTIONARY_VER ) ) . '</p>';

				$pointer_text .= '<p>' . esc_js( __( 'See submenu', 'xili-dictionary' ) . ' “<a href="edit.php?post_type=xdmsg&page=dictionary_page">' . __( 'Tools, Files po mo', 'xili-dictionary' ) . '</a>”' ) . '.</p>';
				/* translators: */
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'Before to question dev.xiligroup support, do not forget to visit %s documentation', 'xili-dictionary' ), '<a href="http://wiki.xiligroup.org" title="' . $about . '" >wiki</a>' ) ) . '.</p>';
				$pointer_dismiss = 'xd-new-version-' . str_replace( '.', '-', XILIDICTIONARY_VER );
				$pointer_div = '#menu-posts-xdmsg';
				$pointer_edge = 'left';
				$pointer_my = 'left';
				$pointer_at = 'right';
				break;

			default: // nothing
				$pointer_text = '';
		}

		// inspired from www.generalthreat.com
		// Get the list of dismissed pointers for the user
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		if ( in_array( $pointer_dismiss, $dismissed ) && 'xd-new-version-' . str_replace( '.', '-', XILIDICTIONARY_VER == $pointer_dismiss ) ) {
			$pointer_text = '';
		} elseif ( in_array( $pointer_dismiss, $dismissed ) ) {
			$pointer_text = '';
		}

		return array(
			'pointerText' => html_entity_decode( (string) $pointer_text, ENT_QUOTES, 'UTF-8' ),
			'pointerDismiss' => $pointer_dismiss,
			'pointerDiv' => $pointer_div,
			'pointerEdge' => ( '' == $pointer_edge ) ? 'top' : $pointer_edge,
			'pointerAt' => ( '' == $pointer_at ) ? 'left top' : $pointer_at,
			'pointerMy' => ( '' == $pointer_my ) ? 'left top' : $pointer_my,
			'newsID' => $news_id,
		);
	} /* end of pointer infos */

	// called by each pointer
	private function insert_news_pointer( $case_news ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer', false, array( 'jquery' ), XILIDICTIONARY_VER );
			++$this->news_id;
			$this->news_case[ $this->news_id ] = $case_news;
	}

	// insert the pointers registered before
	public function print_the_pointers_js() {
		if ( 0 != $this->news_id ) {
			for ( $i = 1; $i <= $this->news_id; $i++ ) {
				$this->print_pointer_js( $i );
			}
		}
	}

	private function print_pointer_js( $indice ) {

		$args = $this->localize_admin_js( $this->news_case[ $indice ], $indice );
		if ( '' != $args['pointerText'] ) {
			// only if user don't read it before
		?>
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function() {

			var strings<?php echo $indice; ?> = <?php echo json_encode( $args ); ?>;

			<?php /** Check that pointer support exists AND that text is not empty - inspired www.generalthreat.com */ ?>

			if (typeof(jQuery().pointer) != 'undefined' && strings<?php echo $indice; ?>.pointerText != '' ) {
				jQuery( strings<?php echo $indice; ?>.pointerDiv ).pointer({
					content : strings<?php echo $indice; ?>.pointerText,
					position: { edge: strings<?php echo $indice; ?>.pointerEdge,
						at: strings<?php echo $indice; ?>.pointerAt,
						my: strings<?php echo $indice; ?>.pointerMy,
						offset: strings<?php echo $indice; ?>.pointerOffset
					},
					close : function() {
						jQuery.post( ajaxurl, {
							pointer: strings<?php echo $indice; ?>.pointerDismiss,
							action: 'dismiss-wp-pointer'
						});
					}
				}).pointer( 'open' );
			}
		});
		//]]>
		</script>
		<?php
		}
	}

}
