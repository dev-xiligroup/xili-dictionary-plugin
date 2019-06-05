<?php

/**
* XD Admin class download screen and functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Download {

	/**
	* download file to computer
	* need function in init
	* @since 2.5
	*
	* 2014-02-14
	*/
	public function xili_dictionary_download() {
		$themessages = array(
			'ok' => esc_html__( 'File is ready for download:', 'xili-dictionary' ),
			'error' => esc_html__( 'Impossible to download file', 'xili-dictionary' ),
			'error_lang' => esc_html__( 'Please specify language', 'xili-dictionary' ),
		);
		$msg = $this->msg_settings;

		?>
		<div class="wrap">

			<h2 class="nav-tab-wrapper"><?php esc_html_e( 'Download files (.po, .pot, .mo) to your computer', 'xili-dictionary' ); ?></h2>
		<?php if ( '' != $msg ) : ?>

			<?php
			$class_error = ( 'ok' != $msg ) ? ' error-message error' : '';
			echo '<div id="message" class="updated fade' . $class_error . '"><p>';
			echo $themessages[ $msg ];
			if ( 'error' == $msg ) {
				$sub_type = ( 'local' == $_POST['download_sub_type_file'] ) ? 'local-': '';
				if ( 'pot' == $_POST['download_type_file'] ) {
					$sub_type = $this->theme_domain();
				}
				echo ' “' . $sub_type . $_POST['language_file'] . '.' . $_POST['download_type_file'];

				$place = ( $_POST['download_place_file'] == 'parenttheme' ) ? esc_html__( 'Parent theme', 'xili-dictionary' ): esc_html__( 'Child theme', 'xili-dictionary' );
				echo '”&nbsp;' . esc_html__( 'from', 'xili-dictionary' ) . '&nbsp;' . $place;
			}

			if ( $this->download_uri ) {
				$basename = basename( $this->download_uri );
				echo ' <a href="' . $this->download_uri . '" /><strong>' . sprintf( esc_html__( 'Click here to download file %s', 'xili-dictionary' ), $basename ) . '</strong></a>';
			}
			?>
				</p></div>
<?php endif; ?>
			<form enctype="multipart/form-data" action="edit.php?post_type=xdmsg&amp;page=download_dictionary_page" method="post" id="xd-download-settings">

				<?php
				wp_nonce_field( 'xili_dictionary_download', 'xd_download' );
				settings_fields( 'xd_download_main' );
				?>

				<?php do_settings_sections( 'xd_downloadd' ); ?>
				<h4 class="link-back"><?php /* translators: */ printf( __( '<a href="%s">Back</a> to the list of msgs and tools', 'xili-dictionary' ), admin_url() . 'edit.php?post_type=' . XDMSG . '&page=dictionary_page' ); ?></h4>
				<p class="submit">
					<input type="submit" name="submit" class="button-primary" id="submit" value="<?php esc_html_e( 'Prepare download', 'xili-dictionary' ); ?>" />
				</p>
			</form>

		</div>
	<?php
	}

	public function xd_download_init_settings() {
		//
		register_setting( 'xd_download_main', 'xd_downloadd', array( &$this, '_xd_download_type_define' ) );
		add_settings_section( 'xd_download_mainn', esc_html__( 'Download the files', 'xili-dictionary' ), array( &$this, 'xd_download_setting_callback_main_section' ), 'xd_downloadd' );

		//
		add_settings_field( '_xd_download_type', esc_html__( 'Define the file to download', 'xili-dictionary' ), array( &$this, 'xd_file_download_setting_callback_row' ), 'xd_downloadd', 'xd_download_mainn' );

	}

	public function xd_download_setting_callback_main_section() {
		echo '<p>' . esc_html__( 'Here it is possible to choose a language file of the current theme to download to your computer for further works or archives.', 'xili-dictionary' ) . '</p>';
	}

	public function xd_file_download_setting_callback_row() {
		// pop up to build
		$extend = 'mo';
		?>


		<div class="sub-field">
			<label for="download_type_file"><?php esc_html_e( 'Type', 'xili-dictionary' ); ?>:&nbsp;&nbsp;<strong>.</strong></label>
			<select name="download_type_file" id="download_type_file" class='postform'>
				<option value="" <?php selected( '', $extend ); ?>> <?php esc_html_e( 'Select type...', 'xili-dictionary' ); ?> </option>
				<option value="po" <?php selected( 'po', $extend ); ?>> <?php esc_html_e( 'PO file', 'xili-dictionary' ); ?> </option>
				<option value="mo" <?php selected( 'mo', $extend ); ?>> <?php esc_html_e( 'MO file', 'xili-dictionary' ); ?> </option>
				<option value="pot" <?php selected( 'pot', $extend ); ?>> <?php esc_html_e( 'POT file', 'xili-dictionary' ); ?> </option>
			</select>
			<p class="description"><?php esc_html_e( 'Type of the file (.mo, .po or .pot)', 'xili-dictionary' ); ?></p>
		</div><br />
		<div class="sub-field">
			<label for="download_sub_type_file">&nbsp;<?php esc_html_e( 'Subtype of file', 'xili-dictionary' ); ?>:&nbsp;
			<select name="download_sub_type_file" id="download_sub_type_file">
				<option value="" ><?php esc_html_e( 'xx_YY', 'xili-dictionary' ); ?></option>
				<option value="local" ><?php esc_html_e( 'local-xx_YY', 'xili-dictionary' ); ?></option>
			</select>
			</label>
				<p class="description"><?php esc_html_e( 'Subtype of the file (theme or local ?)', 'xili-dictionary' ); ?></p>
		</div><br />
		<div class="sub-field">
			<label for="language_file"><?php esc_html_e( 'Language', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="language_file" id="language_file" class='postform'>
				<option value=""> <?php esc_html_e( 'Select language', 'xili-dictionary' ); ?> </option>

			<?php
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );
			foreach ( $listlanguages as $language ) {
				$selected = ( isset( $_GET[ QUETAG ] ) && $language->name == $_GET[ QUETAG ] ) ? 'selected=selected' : '';
				echo '<option value="' . $language->name . '" ' . $selected . ' >' . __( $language->description, 'xili-dictionary' ) . '</option>';
			}?>
				<option value="pot-file"> <?php esc_html_e( 'no language (pot)', 'xili-dictionary' ); ?> </option>
			</select>
			<p class="description"><?php esc_html_e( 'Language of the file.', 'xili-dictionary' ); ?></p>
		</div><br />
		<div class="sub-field">
				<label for="download_place_file">&nbsp;<?php esc_html_e( 'Place of file', 'xili-dictionary' ); ?>:&nbsp;
				<select name="download_place_file" id="download_place_file">
					<?php if ( is_child_theme() ) { ?>
					<option value="theme" ><?php esc_html_e( 'Child theme', 'xili-dictionary' ); ?></option>
					<?php } ?>
					<option value="parenttheme" ><?php esc_html_e( 'Parent theme', 'xili-dictionary' ); ?></option>
				</select>
				</label>
				<p class="description"><?php esc_html_e( 'Place of the file (parent or child theme)', 'xili-dictionary' ); ?></p>
		</div><br />
		<?php $extend = 'zip'; ?>
		<div class="sub-field">
				<label for="download_compress_file">&nbsp;<?php esc_html_e( 'Compression of file', 'xili-dictionary' ); ?>:&nbsp;
				<select name="download_compress_file" id="download_compress_file">
					<option value="" ><?php esc_html_e( 'No compression', 'xili-dictionary' ); ?></option>
					<option value="zip" <?php selected( 'zip', $extend ); ?>><?php esc_html_e( 'Zip', 'xili-dictionary' ); ?></option>
					<option value="gz" ><?php esc_html_e( 'Gz', 'xili-dictionary' ); ?></option>

				</select>
				</label>
				<p class="description"><?php esc_html_e( 'Type of compression...', 'xili-dictionary' ); ?></p>
		</div><br /><hr />
		<div id="xd-file-exists"><p><?php esc_html_e( 'The wanted file do not exist ! Try another selection (language ?) !', 'xili-dictionary' ); ?></p></div>
		<div id="xd-file-state"></div>
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
		?>

		var plugin = '';

		function update_ui_download_state() {

			var place = jQuery( '#download_place_file' ).val();

			var la = jQuery( '#language_file' ).val();
			var place = jQuery( '#download_place_file' ).val();

			var subtype = jQuery( '#download_sub_type_file' ).val();
			var local = '';
			if ( subtype == 'local' ) local = 'local';

			var x = jQuery( '#download_type_file' ).val();

			if ( x == 'pot' ) la = 'pot-file';
			var a = from_file_exists( 'files', place, curthemename, plugin, la, x, local); //alert ( a + ' <> ' + place + curthemename + plugin + la + x );
			show_file_states( 'files', place, curthemename, plugin, la, x, local );
			if ( a == "exists" ) {
				jQuery( '#xd-file-exists' ).hide();
				jQuery( '#xd-file-state' ).show();
				jQuery( '#submit' ).attr( 'disabled', false );
			} else {
				jQuery( '#xd-file-exists' ).show();
				jQuery( '#xd-file-state' ).hide();
				jQuery( '#submit' ).attr( 'disabled', true );
			}
		}
		jQuery(document).ready( function() {
			update_ui_download_state();
		});

		jQuery( '#language_file, #download_place_file, #download_sub_type_file, #download_type_file' ).change(function() {
			update_ui_download_state();
		});

		//]]>
		</script>

	<?php
	}

	public function _xd_download_type_define( $input ) {
		add_settings_error( 'my-settings', 'invalid-email', 'You have entered an invalid e-mail address.' );
		return $input;
	}

	// call by init filter
	public function download_file_if() {

		if ( isset( $_POST['language_file'] ) && isset( $_POST['download_type_file'] ) ) {
			check_admin_referer( 'xili_dictionary_download', 'xd_download' );
			$actiontype = 'add';
			$selectlang = $_POST['language_file'];
			$type_file = $_POST['download_type_file'];

			if ( '' != $selectlang || 'pot' == $type_file ) {

				$sub_type_file = $_POST['download_sub_type_file'];
				$place_file = $_POST['download_place_file'];
				$compress_file = $_POST['download_compress_file'];

				$result = $this->deliver_file( $selectlang, $type_file, $sub_type_file, $place_file, $compress_file );

				if ( $result ) {
					$this->msg_settings = 'ok';
				} else {
					$this->msg_settings = 'error';
				}
			} else {

				$this->msg_settings = 'error_lang';
			}
		}

	}

	public function deliver_file( $selectlang, $type_file, $sub_type_file, $place_file, $compress_file = '' ) {
		//
		$filename = $selectlang . '.' . $type_file;
		if ( $sub_type_file ) {
			$filename = 'local-' . $filename;
		}
		if ( 'pot' == $type_file ) {
			$filename = $this->theme_domain() . '.pot';
		}

		if ( is_multisite() && $multilocal ) {
			if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
				$path = $uploads['basedir'] . '/languages/';
			}
		} else {
			if ( ! is_child_theme() && 'languages' != $place_file ) {
				$path = $this->active_theme_directory . $this->langfolder;
			} else {
				if ( 'languages' == $place_file ) {
					$path = WP_LANG_DIR . '/themes/' . $this->theme_domain() . '-';
				} elseif ( 'parenttheme' == $place_file ) {
					$path = get_template_directory() . $this->parentlangfolder;
				} else {
					$path = get_stylesheet_directory() . $this->langfolder;
				}
			}
		}

		$diskfile = $path . $filename;

		$path_file = $this->transfer_file( $diskfile, $filename, $compress_file );

		if ( $path_file ) {
			$this->download_uri = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path_file );
			return true;
		}

		return false;
	}

	/**
	* inspirated form wp-db-backup
	*/
	public function transfer_file( $diskfile = '', $filename = '', $compress_file = '' ) {
		if ( '' == $diskfile ) {
			return 'nofile-error';
		}
		$gz_diskfile = "{$diskfile}.gz";

		$success = 'error';
		/**
		 * Try upping the memory limit before gzipping
		 */
		if ( function_exists( 'memory_get_usage' ) && ( (int) @ini_get( 'memory_limit' ) < 64 ) ) {
			@ini_set( 'memory_limit', '64M' );
		}

		if ( file_exists( $diskfile && 'gz' == $compress_file ) ) {
			/**
			 * Try gzipping with an external application
			 */
			if ( file_exists( $diskfile ) && ! file_exists( $gz_diskfile ) ) {
				@exec( "gzip $diskfile" );
			}

			if ( file_exists( $gz_diskfile ) ) {
				if ( file_exists( $diskfile ) ) {
					//unlink( $diskfile);
				}
				$diskfile = $gz_diskfile;
				$filename = "{$filename}.gz";

				/**
				 * Try to compress to gzip, if available
				 */
			} else {
				if ( function_exists( 'gzencode' ) ) {
					if ( function_exists( 'file_get_contents' ) ) {
						$text = file_get_contents( $diskfile );
					} else {
						$text = implode( '', file( $diskfile ) );
					}
					$gz_text = gzencode( $text, 9 );
					$fp = fopen( $gz_diskfile, 'w' );
					fwrite( $fp, $gz_text );
					if ( fclose( $fp ) ) {
						//unlink( $diskfile);
						$diskfile = $gz_diskfile;
						$filename = "{$filename}.gz";
					}
				}
			}
		} elseif ( file_exists( $diskfile ) && 'zip' == $compress_file ) {
			//
			$zip_diskfile = "{$diskfile}.zip";
			$filename = "{$filename}.zip";
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
			//$zip = new ZipArchive();
			$zip = new PclZip( $zip_diskfile );
			//if ( $zip->open( $zip_diskfile, ZIPARCHIVE::CREATE)===TRUE) {
			//	$zip->addFile( $diskfile);
			//	$zip->close();
			//}
			$zip->create( $diskfile, PCLZIP_OPT_REMOVE_PATH, dirname( $diskfile . '/' ) );
			$diskfile = $zip_diskfile;

		}

		if ( file_exists( $diskfile ) ) {
			/*
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Length: ' . filesize( $diskfile) );
			header("Content-Disposition: attachment; filename=$filename");

			flush();

			$r = readfile( $diskfile);

			flush();
			if ( $r == true ) {
				$success = 'ok';
				$this->msg_settings = 'ok';
			} else {
				$this->msg_settings = 'error';
			}
			*/
			return $diskfile;
		}

		return false;
	}


}
