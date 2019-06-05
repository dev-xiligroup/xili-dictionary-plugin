<?php
/**
* XD Admin class sideboxes
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Dashboard {

	public function on_sidebox_info_content() {

		$template_directory = $this->active_theme_directory;

		$cur_theme_name = $this->get_option_theme_full_name( true );
		if ( $this->xililanguage_ms ) {
			echo '<p><em>' . esc_html__( 'xili-language-ms is active !', 'xili-dictionary' ) . '</em></p>';

		} else {
			switch ( $this->xililanguage ) {
				case 'neveractive':
					echo '<p>' . esc_html__( 'xili-language plugin is not present !', 'xili-dictionary' ) . '</p>';
					break;
				case 'wasactive':
					echo '<p>' . esc_html__( 'xili-language plugin is not activated !', 'xili-dictionary' ) . '</p><br />';
					break;
			}
		}
		?>
<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;">
	<legend><?php echo esc_html__( 'Theme’s informations:', 'xili-dictionary' ) . ' ( ' . $cur_theme_name . ' )'; ?></legend>
	<p>
				<?php
				$langfolder = $this->xili_settings['langs_folder'];
				echo esc_html__( 'Languages sub-folder:', 'xili-dictionary' ) . ' ' . $langfolder;
				?>
				<br />
				<?php
				if ( 'unknown' == $langfolder ) {
					?>
					<span style='color:red'>
						<?php
						esc_html_e( "No languages files are present in theme's folder or theme's sub-folder: <strong>add at least a .po or a .mo inside.</strong><br /> Errors will occur if you try to import or export!", 'xili-dictionary' );
						echo '<br />';
						?>
				</span>
				<?php
				} else {
					esc_html_e( 'Available MO files:', 'xili-dictionary' );
					echo '<br />';
					if ( file_exists( $template_directory ) ) {
						// when theme was unavailable
						$this->find_files( $template_directory, '/.mo$/', array( &$this, 'available_mo_files' ) );
					}
				}

				?>
	</p>
</fieldset>


		<?php
	}

	public function on_sidebox_settings_content() {

		?>
	<p> <?php esc_html_e( 'External file xd-style.css for dashboard (flags, customization)', 'xili-dictionary' ); ?></p>
		<?php
		if ( ! $this->exists_style_ext ) {

			echo '<p>' . esc_html__( 'There is no style for dashboard', 'xili-dictionary' ) . ' ( ' . $this->style_message . ' )</p>';

		} else {

			echo '<p>' . $this->style_message . '</p>';
		}

		if ( 'on' == $this->xili_settings['external_xd_style'] ) {

			$style_action = esc_html__( 'No style for dashboard', 'xili-dictionary' );
			$what = 'off';

		} else {

			$style_action = esc_html__( 'Activate style for dashboard', 'xili-dictionary' );
			$what = 'on';
		}
		?>

		<fieldset style="margin:2px; padding:6px 6px; "><strong><?php esc_html_e( 'Dictionary Styles', 'xili-dictionary' ); ?></strong><br /><br />
		<?php
			$url = '?post_type=xdmsg&action=setstyle&what=' . $what . '&amp;page=dictionary_page';
			$nonce_url = wp_nonce_url( $url, 'xdsetstyle' );
		?>
			<a class="action-button grey-button" href="<?php echo $nonce_url; ?>" title="<?php esc_html_e( 'Change style mode', 'xili-dictionary' ); ?>"><?php _e( $style_action ); ?></a>

		</fieldset>
		<hr />
		<p><strong><?php esc_html_e( 'Capabilities for editor role', 'xili-dictionary' ); ?></strong></p>
		<p><?php esc_html_e( 'Here, as admin, set capabilities of the editor role:', 'xili-dictionary' ); ?></p>


			<select name="editor_caps" id="editor_caps" >
						<option value="no_caps" ><?php esc_html_e( 'No capability', 'xili-dictionary' ); ?></option>
						<option value="cap_edit" <?php selected( 'cap_edit', $this->xili_settings['editor_caps'] ); ?>><?php esc_html_e( 'Editor can edit MSGs', 'xili-dictionary' ); ?></option>
						<option value="cap_edit_save" <?php selected( 'cap_edit_save', $this->xili_settings['editor_caps'] ); ?>><?php esc_html_e( 'Can edit MSGs and save local-xx_XX.mo', 'xili-dictionary' ); ?></option>

				</select>
				<p class="submit">
				<input type="submit" id="setcapedit" name="setcapedit" value="<?php esc_html_e( 'Update Role…', 'xili-dictionary' ); ?>" />
			</p>

	<?php
	}

	/**
	 * email support form
	 *
	 *
	 * @since 2.3.2
	 */
	public function on_sidebox_mail_content( $data ) {
		extract( $data );

		global $wp_version;
		if ( '' != $emessage ) {
			?>
			<h4><?php esc_html_e( 'Note:', 'xili-dictionary' ); ?></h4>
			<p><strong><?php echo $emessage; ?></strong></p>
		<?php } ?>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo esc_html_e( 'Mail to dev.xiligroup', 'xili-dictionary' ); ?></legend>
		<label for="ccmail"><?php esc_html_e( 'Cc: (Reply to:)', 'xili-dictionary' ); ?>
		<input class="widefat" id="ccmail" name="ccmail" type="text" value="<?php bloginfo( 'admin_email' ); ?>" /></label><br /><br />
		<?php if ( false === strpos( get_bloginfo( 'url' ), 'local' ) ) { ?>
			<label for="urlenable">
				<input type="checkbox" id="urlenable" name="urlenable" value="enable"
				<?php
				if ( isset( $this->xili_settings['url'] ) && 'enable' == $this->xili_settings['url'] ) {
					echo ' checked="checked" ';
				}
				echo '/>&nbsp;' . bloginfo( 'url' );
				?>
			</label><br />
		<?php } else { ?>
			<input type="hidden" name="onlocalhost" id="onlocalhost" value="localhost" />
		<?php } ?>
		<br /><em><?php esc_html_e( 'When checking and giving detailled infos, support will be better !', 'xili-dictionary' ); ?></em><br />
		<label for="themeenable">
			<input type="checkbox" id="themeenable" name="themeenable" value="enable"
			<?php
			if ( isset( $this->xili_settings['theme'] ) && 'enable' == $this->xili_settings['theme'] ) {
				echo ' checked="checked" ';
			}
			echo '/>&nbsp;Theme name= ' . get_option( 'stylesheet' );
			?>
		</label><br />
		<?php if ( '' != $this->get_wplang() ) { ?>
		<label for="wplangenable">
			<input type="checkbox" id="wplangenable" name="wplangenable" value="enable"
			<?php
			if ( isset( $this->xili_settings['wplang'] ) && 'enable' == $this->xili_settings['wplang'] ) {
				echo ' checked="checked" ';
			}
			echo '/>&nbsp;WPLANG= ' . $this->get_wplang();
			?>
		</label><br />
		<?php } ?>
		<label for="versionenable">
			<input type="checkbox" id="versionenable" name="versionenable" value="enable"
			<?php
			if ( isset( $this->xili_settings['version-wp'] ) && 'enable' == $this->xili_settings['version-wp'] ) {
				echo ' checked="checked" ';
			}
			echo '/>&nbsp;WP version: ' . $wp_version;
			?>
		</label><br /><br />
		<?php
		$list = $this->check_other_xili_plugins();
		if ( '' != $list ) {
		?>
		<label for="xiliplugenable">
			<input type="checkbox" id="xiliplugenable" name="xiliplugenable" value="enable"
			<?php
			if ( isset( $this->xili_settings['xiliplug'] ) && 'enable' == $this->xili_settings['xiliplug'] ) {
				echo ' checked="checked" ';
			}
			echo '/>&nbsp;Other xili plugins = ' . $list;
			?>
		</label><br /><br />
		<?php } ?>
		<label for="webmestre"><?php esc_html_e( 'Type of webmaster:', 'xili-dictionary' ); ?>
		<select name="webmestre" id="webmestre" style="width:100%;">
		<?php
		if ( ! isset( $this->xili_settings['webmestre-level'] ) ) {
			$this->xili_settings['webmestre-level'] = '?';
		}
		?>
			<option value="?" <?php selected( $this->xili_settings['webmestre-level'], '?' ); ?>><?php esc_html_e( 'Define your experience as webmaster…', 'xili-dictionary' ); ?></option>
			<option value="newbie" <?php selected( $this->xili_settings['webmestre-level'], 'newbie' ); ?>><?php esc_html_e( 'Newbie in WP', 'xili-dictionary' ); ?></option>
			<option value="wp-php" <?php selected( $this->xili_settings['webmestre-level'], 'wp-php' ); ?>><?php esc_html_e( 'Good knowledge in WP and few in php', 'xili-dictionary' ); ?></option>
			<option value="wp-php-dev" <?php selected( $this->xili_settings['webmestre-level'], 'wp-php-dev' ); ?>><?php esc_html_e( 'Good knowledge in WP, CMS and good in php', 'xili-dictionary' ); ?></option>
			<option value="wp-plugin-theme" <?php selected( $this->xili_settings['webmestre-level'], 'wp-plugin-theme' ); ?>><?php esc_html_e( 'WP theme and /or plugin developper', 'xili-dictionary' ); ?></option>
		</select></label>
		<br /><br />
		<label for="subject"><?php esc_html_e( 'Subject:', 'xili-dictionary' ); ?>
		<input class="widefat" id="subject" name="subject" type="text" value="" /></label>
		<select name="thema" id="thema" style="width:100%;">
			<option value="" ><?php esc_html_e( 'Choose topic...', 'xili-dictionary' ); ?></option>
			<option value="Message" ><?php esc_html_e( 'Message', 'xili-dictionary' ); ?></option>
			<option value="Question" ><?php esc_html_e( 'Question', 'xili-dictionary' ); ?></option>
			<option value="Encouragement" ><?php esc_html_e( 'Encouragement', 'xili-dictionary' ); ?></option>
			<option value="Support need" ><?php esc_html_e( 'Support need', 'xili-dictionary' ); ?></option>
		</select>
		<textarea class="widefat" rows="5" cols="20" id="mailcontent" name="mailcontent"><?php esc_html_e( 'Your message here…', 'xili-dictionary' ); ?></textarea>
		</fieldset>
		<p>
		<?php esc_html_e( 'Before send the mail, check the infos to be sent and complete textarea. A copy (Cc:) is sent to webmaster email (modify it if needed) . ', 'xili-dictionary' ); ?>
		</p>
		<?php //wp_nonce_field( 'xili-postinpost-sendmail' ); ?>
		<div class='submit'>
		<input id='sendmail' name='sendmail' type='submit' tabindex='6' value="<?php esc_html_e( 'Send email', 'xili-dictionary' ); ?>" /></div>

		<div style="clear:both; height:1px"></div>
		<?php
	}

	/**
	 * display lines of files in special sidebox
	 * @since 1.0
	 */
	public function available_mo_files( $path, $filename ) {
		$langfolder = str_replace( $this->active_theme_directory, '', $path );
		if ( '' == $langfolder ) {
			$langfolder = '/';
		}
		$shortfilename = str_replace( '.mo', '', $filename );
		$alert = '<span style="color:red;">' . esc_html__( 'Uncommon filename', 'xili-dictionary' ) . '</span>';
		if ( 5 != strlen( $shortfilename ) && 2 != strlen( $shortfilename ) ) {
			if ( false === strpos( $shortfilename, 'local-' ) ) {
				$message = $alert;
			} else {
				$message = '<em>' . __( "Site's values", 'xili-dictionary' ) . '</em>';
			}
		} elseif ( false === strpos( $shortfilename, '_' ) && 5 == strlen( $shortfilename ) ) {
			$message = $alert;
		} else {
			$message = '';
		}

		echo $shortfilename . ' (' . $langfolder . ') ' . $message . '<br />';
	}

	/**
	 * Add a tool to import all terms of a taxonomy inside XD list - not called when Add New or Apply bulk action
	 *
	 * @since 2.3.3
	 *
	 */
	public function add_import_in_xd_button( $taxonomy ) {
		global $xili_language;
		$taxonomy_obj = get_taxonomy( $taxonomy );
		$result = '';
		$paged = ( isset( $_GET['paged'] ) ) ? '&paged=' . $_GET['paged'] : '';
		$quantities = array( 0, 0, array(), array() );

		if ( isset( $_GET['import-in-xd'] ) ) {
			if ( isset( $_GET['wpnonce'] ) && wp_verify_nonce( $_GET['wpnonce'], 'upload-xili-dictionary-' . $taxonomy ) ) {

				$quantities = $this->xili_read_catsterms_cpt( $taxonomy );
				/* translators: */
				$result = sprintf( esc_html__( 'xili-dictionary msgid list updated with %1$s terms - %2$s name(s) and %3$s description(s) - ', 'xili-dictionary' ), $taxonomy_obj->labels->singular_name, $quantities[0], $quantities[1] );
			} else {
				wp_die( esc_html__( 'Security check', 'xili-dictionary' ) );
			}
		}

		?>
		<br />
		<div class="updated" style="background: #f5f5f5; border:#dfdfdf 1px solid;">
		<fieldset style="margin:10px 0 2px; padding:10px 6px;" ><legend><strong><?php esc_html_e( 'Xili-dictionary tool to prepare translation', 'xili-dictionary' ); ?></strong></legend>
		<form action="" method="get">
			<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
			<input type="hidden" name="import-in-xd" value="true" />
			<input type="hidden" name="wpnonce" value="<?php echo wp_create_nonce( 'upload-xili-dictionary-' . $taxonomy ); ?>" />
		<?php
		if ( $result ) {
			echo '<div class="updated">';
			/* translators: */
			echo '<p><em>' . sprintf( esc_html__( 'Message : %s', 'xili-dictionary' ), $result ) . '</em></p>';

			if ( array() != $quantities[3] ) {
				/* translators: */
				echo '<p><strong>' . sprintf( __( '%3$s terms of %1$s were just imported, <a href="%2$s">display those terms</a> in msg list of xili-dictionary.', 'xili-dictionary' ), $taxonomy_obj->labels->name, 'edit.php?post_type=' . XDMSG . '&amp;only_' . XDMSG . '=' . implode( ',', $quantities[3] ), $quantities[0] + $quantities[1] ) . '</strong></p>';

			}
			if ( array() != $quantities[2] ) {
				/* translators: */
				echo '<p><strong>' . sprintf( __( 'All %1$s terms are checked , <a href="%2$s">display those terms</a> in xili-dictionary.', 'xili-dictionary' ), $taxonomy_obj->labels->name, 'edit.php?post_type=' . XDMSG . '&amp;only_' . XDMSG . '=' . implode( ',', $quantities[2] ) ) . '</strong></p>';
			}
			/* translators: */
			echo '<p style="text-align:right">' . sprintf( __( '<a href="%2$s">Refresh</a> %3$s column of %1$s table.', 'xili-dictionary' ), $taxonomy_obj->labels->name, admin_url() . 'edit-tags.php?taxonomy=' . $taxonomy, esc_html__( 'Language', 'xili-dictionary' ) ) . '</p></div>';
		}
		?>
		<p><?php /* translators: */ printf( esc_html__( '%1$s terms can be imported inside xili-dictionary msgid list', 'xili-dictionary' ), $taxonomy_obj->labels->name ); ?>
		<?php
		echo '&nbsp;&nbsp;';
		/* translators: */
		submit_button( sprintf( esc_html__( 'Import %1$s terms', 'xili-dictionary' ), $taxonomy_obj->labels->name ), 'xbutton', false, false, array( 'id' => 'xd-import' ) );
		?>
		</p>
		</form>
		<?php
		if ( array() != $this->taxlist ) {
		?>
		<hr />
		<form action="" method="get" >
			<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>" />
			<input type="hidden" name="see-in-xd" value="true" />
		<?php

		if ( array() != $this->tax_msgid_list ) { // build by rows

			//echo '<p>'.sprintf( esc_html__( 'To display the current above %1$s terms list, click this <a href="%2$s">link</a>', 'xili-dictionary' ), $taxonomy_obj->labels->name, 'edit.php?post_type='.XDMSG.'&amp;only_'.XDMSG."=".implode( ',', $msgid_list ) ) . '</p>';

			if ( isset( $_REQUEST['see-in-xd'] ) ) {
				$url_redir = admin_url() . 'edit.php?post_type=' . XDMSG . '&only_' . XDMSG . '=' . implode( ',', $this->tax_msgid_list );
				?>
<script type="text/javascript">
<!--
	window.location= <?php echo "'" . $url_redir . "'"; ?>;
//-->
</script>
<?php
			}
		}
		/* translators: */
		echo '<p>' . sprintf( esc_html__( 'In the term listed below, %d items are displayed (each has a name and a description) :', 'xili-dictionary' ), count( $this->taxlist ) ) . '&nbsp;';

		if ( array() != $this->tax_msgid_list ) {
			/* translators: */
			submit_button( sprintf( esc_html__( 'Display these %s terms in xili-dictionary', 'xili-dictionary' ), $taxonomy_obj->labels->name ), 'xbutton', false, false, array( 'id' => 'xd-display' ) );
			echo '</p>';

		} else {
			/* translators: */
			echo '</p><p>' . sprintf( esc_html__( 'None of these %d terms are available in the msgid list of xili-dictionary. Click button above to populate dictionary before you will translate.', 'xili-dictionary' ), count( $this->taxlist ) ) . '</p>';
		}

		?>
		</form>
		<?php } ?>

		</fieldset></div>
		<?php
	}

	/**
	 * to add a column in cat or tax list
	 * @param  [type] $content [description]
	 * @param  [type] $name    [description]
	 * @param  [type] $id      [description]
	 * @return [type]          [description]
	 */
	public function xili_manage_tax_column( $content, $name, $id ) {
		global $taxonomy;
		if ( TAXONAME != $name ) {
			return $content; // to have more than one added column 2.8.1
		}
		$this->taxlist[] = $id;
		$a = '';
		$ids = array();
		// check if in msgid
		$tax = get_term( (int) $id, $taxonomy );
		$result = $this->msgid_exists( $tax->name );
		if ( false != $result ) {
			// $msgid_name_id
			$this->tax_msgid_list[] = $result[0];
			if ( get_post_status( $result[0] ) != 'trash' ) {
				$ids[] = $result[0];
			}
		}
		$result = $this->msgid_exists( $tax->description );
		if ( false != $result ) {
			// $msgid_desc_id
			$this->tax_msgid_list[] = $result[0];
			if ( get_post_status( $result[0] ) != 'trash' ) {
				$ids[] = $result[0];
			}
		}
		if ( array() != $ids ) { // 2.11.2
			$a = sprintf(
				'<a title="%1$s" href="%3$s">%2$s</a>',
				esc_html__( 'To display name and description of this term in msgid list', 'xili-dictionary' ),
				esc_html__( 'Display in XD', 'xili-dictionary' ),
				admin_url() . 'edit.php?post_type=' . XDMSG . '&only_' . XDMSG . '=' . implode( ',', $ids )
			);
		}
		return $content . $a;
	}

}
