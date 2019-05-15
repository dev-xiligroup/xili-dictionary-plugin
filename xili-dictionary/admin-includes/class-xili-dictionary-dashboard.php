<?php
/**
* XD Admin class help and pointer functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

class Xili_Dictionary_Dashboard {
	public function __construct( &$xd ) {
		//error_log( '++++ +++ +' . $xili_dictionary->get_wplang() ) ;
		//$xili_dictionary->xd = $xd;
	}

	public static function on_sidebox_info_content() {
		global $xili_dictionary;
		echo '<p><em>' . esc_html__( 'xili-language-ms is active !', 'xili-dictionary' ) . '</em></p>';
		$template_directory = $xili_dictionary->active_theme_directory;

		$cur_theme_name = $xili_dictionary->get_option_theme_full_name( true );
		if ( $xili_dictionary->xililanguage_ms ) {
			echo '<p><em>' . esc_html__( 'xili-language-ms is active !', 'xili-dictionary' ) . '</em></p>';

		} else {
			switch ( $xili_dictionary->xililanguage ) {
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
				$langfolder = $xili_dictionary->xili_settings['langs_folder'];
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
						$xili_dictionary->find_files( $template_directory, '/.mo$/', array( 'Xili_Dictionary', 'available_mo_files' ) );
					}
				}

				?>
	</p>
</fieldset>


		<?php
	}

	public static function on_sidebox_settings_content() {
		global $xili_dictionary;
		?>
	<p> <?php esc_html_e( 'External file xd-style.css for dashboard (flags, customization)', 'xili-dictionary' ); ?></p>
		<?php
		if ( ! $xili_dictionary->exists_style_ext ) {

			echo '<p>' . esc_html__( 'There is no style for dashboard', 'xili-dictionary' ) . ' ( ' . $xili_dictionary->style_message . ' )</p>';

		} else {

			echo '<p>' . $xili_dictionary->style_message . '</p>';
		}

		if ( 'on' == $xili_dictionary->xili_settings['external_xd_style'] ) {

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
						<option value="cap_edit" <?php selected( 'cap_edit', $xili_dictionary->xili_settings['editor_caps'] ); ?>><?php esc_html_e( 'Editor can edit MSGs', 'xili-dictionary' ); ?></option>
						<option value="cap_edit_save" <?php selected( 'cap_edit_save', $xili_dictionary->xili_settings['editor_caps'] ); ?>><?php esc_html_e( 'Can edit MSGs and save local-xx_XX.mo', 'xili-dictionary' ); ?></option>

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
	public static function on_sidebox_mail_content( $data ) {
		extract( $data );
		global $xili_dictionary;
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
				if ( isset( $xili_dictionary->xili_settings['url'] ) && 'enable' == $xili_dictionary->xili_settings['url'] ) {
					echo ' checked="checked" />&nbsp;' . bloginfo( 'url' );
				}
				?>
			</label><br />
		<?php } else { ?>
			<input type="hidden" name="onlocalhost" id="onlocalhost" value="localhost" />
		<?php } ?>
		<br /><em><?php esc_html_e( 'When checking and giving detailled infos, support will be better !', 'xili-dictionary' ); ?></em><br />
		<label for="themeenable">
			<input type="checkbox" id="themeenable" name="themeenable" value="enable"
			<?php
			if ( isset( $xili_dictionary->xili_settings['theme'] ) && 'enable' == $xili_dictionary->xili_settings['theme'] ) {
				echo ' checked="checked" />&nbsp;';
				echo 'Theme name= ' . get_option( 'stylesheet' );
			}
			?>
		</label><br />
		<?php if ( '' != $xili_dictionary->get_wplang() ) { ?>
		<label for="wplangenable">
			<input type="checkbox" id="wplangenable" name="wplangenable" value="enable" <?php if ( isset( $xili_dictionary->xili_settings['wplang'] ) && 'enable' == $xili_dictionary->xili_settings['wplang'] ) echo 'checked="checked"' ?> />&nbsp;<?php echo 'WPLANG= ' . $xili_dictionary->get_wplang(); ?>
		</label><br />
		<?php } ?>
		<label for="versionenable">
			<input type="checkbox" id="versionenable" name="versionenable" value="enable" <?php if ( isset( $xili_dictionary->xili_settings['version-wp'] ) && 'enable' == $xili_dictionary->xili_settings['version-wp']) echo 'checked="checked"' ?> />&nbsp;<?php echo "WP version: " . $wp_version; ?>
		</label><br /><br />
		<?php
		$list = xd_check_other_xili_plugins();
		if ( '' != $list ) {
		?>
		<label for="xiliplugenable">
			<input type="checkbox" id="xiliplugenable" name="xiliplugenable" value="enable" <?php if ( isset( $xili_dictionary->xili_settings['xiliplug'] ) && 'enable' == $xili_dictionary->xili_settings['xiliplug'] ) echo 'checked="checked"' ?> />&nbsp;<?php echo "Other xili plugins = " . $list; ?>
		</label><br /><br />
		<?php } ?>
		<label for="webmestre"><?php esc_html_e( 'Type of webmaster:', 'xili-dictionary' ); ?>
		<select name="webmestre" id="webmestre" style="width:100%;">
		<?php
		if ( ! isset( $xili_dictionary->xili_settings['webmestre-level'] ) ) {
			$xili_dictionary->xili_settings['webmestre-level'] = '?';
		}
		?>
			<option value="?" <?php selected( $xili_dictionary->xili_settings['webmestre-level'], '?' ); ?>><?php esc_html_e( 'Define your experience as webmaster…', 'xili-dictionary' ); ?></option>
			<option value="newbie" <?php selected( $xili_dictionary->xili_settings['webmestre-level'], 'newbie' ); ?>><?php esc_html_e( 'Newbie in WP', 'xili-dictionary' ); ?></option>
			<option value="wp-php" <?php selected( $xili_dictionary->xili_settings['webmestre-level'], 'wp-php' ); ?>><?php esc_html_e( 'Good knowledge in WP and few in php', 'xili-dictionary' ); ?></option>
			<option value="wp-php-dev" <?php selected( $xili_dictionary->xili_settings['webmestre-level'], 'wp-php-dev' ); ?>><?php esc_html_e( 'Good knowledge in WP, CMS and good in php', 'xili-dictionary' ); ?></option>
			<option value="wp-plugin-theme" <?php selected( $xili_dictionary->xili_settings['webmestre-level'], 'wp-plugin-theme' ); ?>><?php esc_html_e( 'WP theme and /or plugin developper', 'xili-dictionary' ); ?></option>
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
}
