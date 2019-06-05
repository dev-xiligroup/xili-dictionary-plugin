<?php

/**
* XD Admin importing screens and ajax
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Imports {

	/** importing new UI
	*
	*/
	public function xili_dictionary_import() {
		?>

		<div class="wrap">

			<h2 class="nav-tab-wrapper">
			<?php
			if ( isset( $_GET['scan'] ) && 'sources' == $_GET['scan'] ) {
				$scanning = true;
				esc_html_e( 'Scanning source files of current theme or plugins', 'xili-dictionary' );
				$submit_start = esc_html__( 'Start scanning', 'xili-dictionary' );
				$submit_start_js = 'xd_importing_start()';
			} else {
				esc_html_e( 'Importing files (.po, .pot, .mo)', 'xili-dictionary' );
				$scanning = false;
				$submit_start = esc_html__( 'Start importing', 'xili-dictionary' );
				$submit_start_js = 'xd_importing_start()';
			}

			?>
			</h2>

			<form action="#" method="post" id="xd-looping-settings">

				<?php
				if ( $scanning ) {
					settings_fields( 'xd_scanning' );
					delete_option( '_xd_importing_step' );
					do_settings_sections( 'xd_scanning' );
					echo '<input type="hidden" id="from-what" name="from-what" value="sources" >';
				} else {
					settings_fields( 'xd_importing' );
					delete_option( '_xd_importing_step' );
					do_settings_sections( 'xd_importing' );
					echo '<input type="hidden" id="from-what" name="from-what" value="files" >';
				}
				?>

				<h4 class="link-back"><?php /* translators: */ printf( __( '<a href="%s">Back</a> to the list of msgs and tools', 'xili-dictionary' ), admin_url() . 'edit.php?post_type=' . XDMSG . '&page=dictionary_page' ); ?></h4>
				<p class="submit">
					<input type="button" name="submit" class="button-primary" id="xd-looping-start" value="<?php echo $submit_start; ?>" onclick="<?php echo $submit_start_js; ?>" />
					<input type="button" name="submit" class="button-primary" id="xd-looping-stop" value="<?php esc_html_e( 'Stop', 'xili-dictionary' ); ?>" onclick="xd_importing_stop()" />
					<img id="xd-looping-progress" src="<?php echo admin_url(); ?>images/wpspin_light.gif">
				</p>
				<div class="xd-looping-updated" id="xd-looping-message"></div>

			</form>
		</div>
		<script type="text/javascript">
		//<![CDATA[
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

		function bbtvalue( pot ) {
		var fromwhat = jQuery( '#from-what' ).val();
		var x = jQuery( '#_xd_file_extend' ).val();
		var lo = jQuery( '#_xd_local' ).val();
		var place = jQuery( '#_xd_place' ).val();
		var la = jQuery( '#_xd_lang' ).val();
		var multiupload = 'blog-dir';
		if ( jQuery( '#_xd_multi_local' ).length  ) multiupload = jQuery( '#_xd_multi_local' ).val(); //multisite and not super_admin

		if ( fromwhat == 'sources' ) {
			var t = '<?php echo esc_js( __( 'Start scanning', 'xili-dictionary' ) ); ?>';
			var sources = { 'theme' : '<?php echo esc_js( __( 'this theme', 'xili-dictionary' ) ); ?>', 'parenttheme' : '<?php echo esc_js( __( 'parent of this theme', 'xili-dictionary' ) ); ?>', 'plugin' : '<?php echo esc_js( __( 'this plugin', 'xili-dictionary' ) ); ?>'}
		} else {
			var t = '<?php echo esc_js( __( 'Start importing', 'xili-dictionary' ) ); ?>';
			if ( place == 'parenttheme' ) {
				t = '<?php echo esc_js( __( 'Start importing from parent theme', 'xili-dictionary' ) ); ?>';
			}
		}
		var plugin = jQuery( '#_xd_plugin' ).val();
		var themedomain = '<?php echo $this->theme_domain(); ?>';
		var local = '';
		if ( place == 'local' ) {
			local = 'local';
			submitval = t+" : "+place+"-"+la+'.'+x;
			jQuery("#_origin_theme").val( curthemename );
			jQuery(".title_origin_theme").html( curthemename );

		} else if ( place == 'theme' || place == 'parenttheme' ) {
			if ( fromwhat == 'sources' ) {
				submitval = t+' '+sources[place]+' : '+ curthemename;
			} else {
				submitval = t+' : '+la+'.'+x+pot;
				if ( place == 'parenttheme' ) {
					jQuery("#_origin_theme").val( parentthemename );
					jQuery(".title_origin_theme").html( parentthemename );
				} else {
					jQuery("#_origin_theme").val( curthemename );
					jQuery(".title_origin_theme").html( curthemename );
				}
			}

		} else if ( place == 'plugin' ) {

			if ( fromwhat == 'sources' ) {
				if ( 'string' == typeof (plugin) && plugin != '' ) {
					submitval = t+' '+sources[place]+' : '+ plugindatas[plugin]['domain'];
				} else {
					submitval = ' ? ';
				}
				if ( 'string' == typeof (plugin) && plugin != '' ) {
					jQuery( '#backup_pot_label' ).html( '( '+ plugindatas[plugin]['domain'] + '.pot)' );
				} else {
					jQuery( '#backup_pot_label' ).html( ' ' );
				}
			} else {
				if ( la == pluginpotfile ) {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '.pot';
				} else {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '-'+la+'.'+x;
				}
			}
			jQuery("#_origin_theme").val( plugin );
			if ( 'string' == typeof (plugin) && plugin != '' ) {
				jQuery(".title_origin_theme").html( plugindatas[plugin]['domain'] );
			} else {
				jQuery(".title_origin_theme").html( ' ' );
			}
		} else if ( place == 'pluginwplang' ) {
			if ( la == pluginpotfile ) {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '.pot';
				} else {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '-'+la+'.'+x;
				}

			jQuery("#_origin_theme").val( plugin );
			if ( 'string' == typeof (plugin) && plugin != '' ) {
				jQuery(".title_origin_theme").html( plugindatas[plugin]['domain'] );
			} else {
				jQuery(".title_origin_theme").html( ' ' );
			}
		} else {
			if ( fromwhat == 'sources' ) {
				submitval = t+' : ? ';
			} else {
				submitval = t+' : '+themedomain+'-'+la+'.'+x;
				jQuery("#_origin_theme").val( curthemename );
				jQuery(".title_origin_theme").html( curthemename );
			}
		}
		show_file_states( fromwhat, place, curthemename, plugin, la, x, local, multiupload );
		jQuery("#xd-looping-start").val( submitval );
		if ( fromwhat == 'files' ) {
			var a = from_file_exists( fromwhat, place, curthemename, plugin, la, x, local, multiupload );
			if ( a == 'exists' ) {
				jQuery("#xd-looping-start").attr( 'disabled', false );
			} else {
				jQuery("#xd-looping-start").attr( 'disabled', true ); // error or not exists
			}
		}

	if ( fromwhat == 'sources' ) {
		if ( jQuery( '#backup_pot' ).val() == 'onlypot' || jQuery( '#backup_pot' ).val() == 'both' ) {
			jQuery( '#xd-file-state' ).show();
		} else {
					jQuery( '#xd-file-state' ).hide();
				}
			} else {
				jQuery( '#xd-file-state' ).show();
			}
		}

		jQuery( '#_xd_file_extend' ).change(function() {
			var x = jQuery(this).val();
			if ( x == 'po' ) {
				jQuery( '#po-comment-option' ).show();
				jQuery("#_xd_lang").append( new Option( potfile+'.pot', potfile) );
			} else {
				jQuery( '#po-comment-option' ).hide();
				jQuery("#_xd_lang").find("option[value='"+potfile+"']").remove();
				jQuery("#_xd_lang").find("option[value='" + pluginpotfile + "']").remove();
			}
			bbtvalue ( '' );
		});

		jQuery( '#backup_pot, #_xd_local, #_xd_multi_local' ).change(function() {
			bbtvalue ( '' );
		});

		jQuery( '#_xd_place' ).change(function() {
			var x = jQuery(this).val();
			var ext = jQuery( '#_xd_file_extend' ).val();
			var fromwhat = jQuery( '#from-what' ).val();
			jQuery( '#plugins-option' ).hide();
			if ( x == 'languages' || x == 'local' ) {
				jQuery("#_xd_lang").find("option[value='" + potfile + "']").remove();
				jQuery("#_xd_lang").find("option[value='" + pluginpotfile + "']").remove();
			} else if ( x == 'theme' && ext == 'po' ) {
				jQuery("#_xd_lang").append( new Option( potfile+'.pot', potfile) );
			} else if ( (x == 'theme' || x == 'parenttheme' ) && fromwhat == 'sources' ) {
				if (x == 'theme' ) {
					jQuery(".title_origin_theme").html( curthemename );
				} else {
					jQuery(".title_origin_theme").html( parentthemename );
				}
				jQuery( '#backup_pot_label' ).html( '( '+ potfile + '.pot)' );
			} else if ( x == 'plugin' || x == 'pluginwplang' ) {
				jQuery( '#plugins-option' ).show();
				jQuery("#_xd_lang").find("option[value='" + potfile + "']").remove();
				jQuery("#_xd_lang").append( new Option( pluginpotfile , pluginpotfile) );
				if ( fromwhat == 'sources' ) {
					var plugin = jQuery( '#_xd_plugin' ).val();
					jQuery( '#backup_pot_label' ).html( ' ' );
					if ( 'string' == typeof (plugin) && plugin != '' ) {
						jQuery(".title_origin_theme").html( plugindatas[plugin]['domain'] );
					} else {
						jQuery(".title_origin_theme").html( ' ' );
					}
				}
			}
			bbtvalue ( '' );
		});

		jQuery( '#_xd_plugin' ).change(function() {
			var x = jQuery(this).val();

			bbtvalue ( '' );
		});

		jQuery( '#_xd_lang' ).change(function() {
			var x = jQuery(this).val();
			t = '';
			if ( potfile == x ) {
				t = 't';
				jQuery( '#_xd_local' ).attr( 'checked',false);
				jQuery( '#_xd_local' ).prop( 'disabled',true);
			} else {
				jQuery( '#_xd_local' ).prop( 'disabled',false);
			};
			bbtvalue ( t );
		});


		var x = jQuery( '#_xd_file_extend' ).val();
		if ( x == 'po' ) {
			jQuery( '#po-comment-option' ).show();
			jQuery("#_xd_lang").append( new Option( potfile+'.pot', potfile ) );
		} else {
			jQuery( '#po-comment-option' ).hide();
			jQuery("#_xd_lang").find("option[value='"+potfile+"']").remove();
		}

		bbtvalue ( '' );

	});
		//]]>
		</script>
	<?php
	}

	public function xd_importing_init_settings() {
		add_settings_section( 'xd_importing_main', esc_html__( 'Importing the files', 'xili-dictionary' ), array( &$this, 'xd_importing_setting_callback_main_section' ), 'xd_importing' );

		//
		add_settings_field( '_xd_importing_type', esc_html__( 'Define the file to import', 'xili-dictionary' ), array( &$this, 'xd_file_importing_setting_callback_row' ), 'xd_importing', 'xd_importing_main' );
		register_setting( 'xd_importing_main', '_xd_importing_type', '_xd_importing_type_define' );

		// ajax section
		add_settings_section( 'xd_importing_tech', esc_html__( 'Processing...', 'xili-dictionary' ), array( &$this, 'xd_setting_callback_process_section' ), 'xd_importing' );

		// erasing rows step
		//add_settings_field( '_xd_looping_rows', esc_html__( 'Entries step', 'xili-dictionary' ), array( &$this, 'xd_erasing_setting_callback_row' ), 'xd_importing', 'xd_importing_tech' );
		register_setting( 'xd_importing_tech', '_xd_looping_rows', 'sanitize_title' );

		// Delay Time
		//add_settings_field( '_xd_looping_delay_time', esc_html__( 'Delay Time', 'xili-dictionary' ), array( &$this, 'xd_erasing_setting_callback_delay_time' ), 'xd_importing', 'xd_importing_tech' );
		register_setting( 'xd_importing_tech', '_xd_looping_delay_time', 'intval' );

		/** scanning elements XD 2.9 **/

		add_settings_section( 'xd_scanning_main', esc_html__( 'Scanning the files', 'xili-dictionary' ), array( &$this, 'xd_importing_setting_callback_main_section' ), 'xd_scanning' );

		add_settings_field( '_xd_scanning_type', esc_html__( 'Define the sources', 'xili-dictionary' ), array( &$this, 'xd_file_scanning_setting_callback_row' ), 'xd_scanning', 'xd_scanning_main' );

		add_settings_section( 'xd_scanning_tech', esc_html__( 'Processing...', 'xili-dictionary' ), array( &$this, 'xd_setting_callback_process_section' ), 'xd_scanning' );

	}

	public function xd_setting_callback_tech_section() {
		?>
		<p><?php esc_html_e( "These settings below are reserved for future uses, leave values 'as is'.", 'xili-dictionary' ); ?></p>
		<?php
	}

	public function echo_js_plugins_datas() {

		echo 'var plugindatas = {';
		$list_plugins = get_plugins();

		foreach ( $list_plugins as $plugin_path => $one_plugin ) {
			$plugin_folder = str_replace( basename( $plugin_path ), '', $plugin_path );

			if ( '' == $one_plugin ['TextDomain'] ) {
				if ( '' != $plugin_folder ) {
					$domain_path = $this->detect_plugin_language_sub_folder( $plugin_path );
					if ( '' == $this->plugin_text_domain ) {
						$plugin_text_domain = $this->detect_plugin_textdomain_sub_folder( $plugin_path );
					} else {
						$plugin_text_domain = $this->plugin_text_domain;
					}
				} else {
					$plugin_text_domain = 'no_plugin_folder';
				}
			} else {
				$plugin_text_domain = $one_plugin ['TextDomain'];
			}

			echo '"' . $plugin_path . '" : { "domain" : "' . $plugin_text_domain . '", "name" : "' . $one_plugin ['Name'] . '"}, ';
		}
		echo '};';

	}


	/**
	 * plugin domain catalog ( hook plugin_locale )
	 */
	public function get_plugin_domain_array( $locale, $domain ) {

		if ( ! isset( $this->xili_settings['domains'][ $domain ] ) ) {
			$this->xili_settings['domains'][ $domain ] = 'disable';
			if ( is_admin() ) {
				update_option( 'xili_dictionary_settings', $this->xili_settings );
			}
		}

		return $locale;
	}

	/**
	 * Main settings section description for the settings page
	 *
	 * @since
	 */
	public function xd_importing_setting_callback_main_section() {

		if ( isset( $_GET['scan'] ) && 'sources' == $_GET['scan'] ) {
			echo '<p>' . esc_html__( 'Here it is possible to scan the source files and import translatable terms inside the dictionary.', 'xili-dictionary' ) . '</p>';
			/* translators: */
			echo '<p><em>' . sprintf( esc_html__( 'Before importing terms, verify that the %1$strash%2$s is empty !', 'xili-dictionary' ), '<a href="edit.php?post_type=' . XDMSG . '">', '</a>' ) . '</em></p>';
		} else {
			$extend = ( isset( $_GET['extend'] ) ) ? $_GET['extend'] : 'po';
			?>
			<p><?php /* translators: */ printf( esc_html__( 'Here it is possible to import the .%s file inside the dictionary.', 'xili-dictionary' ), '<strong>' . $extend . '</strong>' ); ?></p>
			<?php
		}
	}

	public function xd_file_importing_setting_callback_row() {
		$extend = ( isset( $_GET['extend'] ) ) ? $_GET['extend'] : 'po';
		$place = 'theme';
		// pop up to build
		?>
	<div class="sub-field">
	<label for="_xd_file_extend"><?php esc_html_e( 'Type', 'xili-dictionary' ); ?>:&nbsp;&nbsp;<strong>.</strong></label>
	<select name="_xd_file_extend" id="_xd_file_extend" class='postform'>
		<option value="" <?php selected( '', $extend ); ?>> <?php esc_html_e( 'Select type...', 'xili-dictionary' ); ?> </option>
		<option value="po" <?php selected( 'po', $extend ); ?>> <?php esc_html_e( 'PO file', 'xili-dictionary' ); ?> </option>
		<option value="mo" <?php selected( 'mo', $extend ); ?>> <?php esc_html_e( 'MO file', 'xili-dictionary' ); ?> </option>
	</select>

	<p class="description"><?php esc_html_e( 'Type of file: .mo or .po', 'xili-dictionary' ); ?></p>
	</div>
	<?php
	// _xd_multi_local
	if ( is_multisite() && is_super_admin() && ! $this->xililanguage_ms ) {
		?>
	<div class="sub-field">
	<label for="_xd_multi_local"><?php esc_html_e( 'Folder', 'xili-dictionary' ); ?>:&nbsp;</label>
	<select name="_xd_multi_local" id="_xd_multi_local" class='postform'>
		<option value="blog-dir"> <?php esc_html_e( 'Site file (blogs.dir)', 'xili-dictionary' ); ?> </option>
		<option value="theme-dir"> <?php esc_html_e( 'Original theme file', 'xili-dictionary' ); ?> </option>
	</select>

	<p class="description"><?php esc_html_e( 'As superadmin, define origin of file', 'xili-dictionary' ); ?></p>
	</div>
		<?php
	}
	// local file
	// languages directory //$mofile = WP_LANG_DIR . "/themes/{$domain}-{$locale}.mo";
?>
	<div class="sub-field">
	<label for="_xd_place"><?php esc_html_e( 'Place of msgs file', 'xili-dictionary' ); ?>:&nbsp;</label>
	<select name="_xd_place" id="_xd_place" class='postform'>
		<option value="theme" <?php selected( 'theme', $place ); ?>> <?php esc_html_e( 'xx_XX file in theme', 'xili-dictionary' ); ?> </option>
		<option value="local" <?php selected( 'local', $place ); ?>> <?php esc_html_e( 'local-xx_XX file in theme', 'xili-dictionary' ); ?> </option>

		<?php if ( is_child_theme() ) { ?>
			<option value="parenttheme" <?php selected( 'parenttheme', $place ); ?>> <?php esc_html_e( 'xx_XX file in parent theme', 'xili-dictionary' ); ?> </option>
		<?php } ?>

		<option value="languages" <?php selected( 'languages', $place ); ?>> <?php /* translators: */ printf( esc_html__( '%1$s-xx_XX in %2$s', 'xili-dictionary' ), $this->theme_domain(), str_replace( WP_CONTENT_DIR, '', WP_LANG_DIR . '/themes/' ) ); ?> </option>

		<option value="plugin" <?php selected( 'plugin', $place ); ?>> <?php esc_html_e( 'language file from a plugin', 'xili-dictionary' ); ?> </option>
		<option value="pluginwplang" <?php selected( 'pluginwplang', $place ); ?>> <?php /* translators: */ printf( esc_html__( 'language file from a plugin in %1$s', 'xili-dictionary' ), str_replace( WP_CONTENT_DIR, '', WP_LANG_DIR . '/plugins/') ); ?> </option>
	</select>


	<p class="description"><?php esc_html_e( 'Define folder where is file.', 'xili-dictionary' ); ?></p>
	</div>

	<div id="plugins-option" class="sub-field" style="display:none;" >
		<?php $this->echo_div_plugins_option(); ?>
		<p class="description"><?php esc_html_e( 'Select the plugin from where to import language file.', 'xili-dictionary' ); ?><br /><?php esc_html_e( 'Each line includes Text Domain (if available in plugin sources header) and state of plugin.', 'xili-dictionary' ); ?></p>
	</div>

	<?php
	$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
		?>
		<div class="sub-field">
			<label for="_xd_lang"><?php esc_html_e( 'Language', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="_xd_lang" id="_xd_lang" class='postform'>
				<option value=""> <?php esc_html_e( 'Select language', 'xili-dictionary' ); ?> </option>

						<?php
						foreach ( $listlanguages as $language ) {
							$selected = ( isset( $_GET[ QUETAG ] ) && $language->name == $_GET[ QUETAG ] ) ? 'selected=selected' : '';
							echo '<option value="' . $language->name . '" ' . $selected . ' >' . __( $language->description, 'xili-dictionary' ) . '</option>';
						}

						?>
			</select>
			<p class="description"><?php esc_html_e( "Language of the file or POT type if available in theme's folder when importing .po file. Submit button is active if file exists!", 'xili-dictionary' ); ?></p>
		</div>
	<?php
		$this->echo_div_source_comments();

	}

	/**
	 * @since 2.9
	 */
	public function echo_div_plugins_option() {

			$list = array_keys( $this->xili_settings['domains'] ); // detected plugins by domain in load_plugin_textdomain

			?>
			<label for="_xd_plugin"><?php esc_html_e( 'Plugin', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="_xd_plugin" id="_xd_plugin" class='postform'>
			<option value=""> <?php esc_html_e( 'Select the plugin', 'xili-dictionary' ); ?> </option>
			<?php
			// list of plugins
			$list_plugins = get_plugins();

			foreach ( $list_plugins as $key_plugin => $one_plugin ) {
				if ( '' != $one_plugin['TextDomain'] ) {
					$text_domain = $one_plugin['TextDomain'];
				} else {
					// test with detected
					$plugin_folder = WP_PLUGIN_DIR . '/' . dirname( $key_plugin );
					$text_domain = $this->plugin_textdomain_search( $key_plugin, esc_html__( 'n. a.', 'xili-dictionary' ) );
				}
				$pb = ( in_array( $text_domain, $list ) && is_plugin_active( $key_plugin ) ) ? '' : '*'; // domain not detected‡
				$active = ( is_plugin_active( $key_plugin ) ) ? ' ( ' . esc_html__( 'active', 'xili-dictionary' ) . ' )' : '';
				echo '<option value="' . $key_plugin . '"> ' . $one_plugin['Name'] . ' {' . $text_domain . ' ' . $pb . '}' . $active . '</option>';
			}
			?>
			</select>

	<?php
	}

	/**
	 * @since 2.9
	 */
	public function echo_div_source_comments() {
		?>
		<div class="sub-field">
			<label for="_origin_theme"><?php esc_html_e( 'Name of current source', 'xili-dictionary' ); ?>&nbsp;: <span class="title_origin_theme"><?php echo $this->get_option_theme_name(); ?></span>
			<input id="_origin_theme" name="_origin_theme" type="hidden" id="_origin_theme" value="<?php echo $this->get_option_theme_name(); ?>" /></label>
			<p class="description"><?php esc_html_e( 'Used to assign origin taxonomy. Below the state of the wanted file:', 'xili-dictionary' ); ?></p>
			<div id="xd-file-state"></div>
		</div>

		<div id="po-comment-option" class="sub-field" style="display:none;" >
		<label for="_importing_po_comments">&nbsp;<?php esc_html_e( 'What about comments', 'xili-dictionary' ); ?>:&nbsp;
		<select name="_importing_po_comments" id="_importing_po_comments">
			<option value="" ><?php esc_html_e( 'No change', 'xili-dictionary' ); ?></option>
			<option value="replace" ><?php esc_html_e( 'Imported comments replace those in list', 'xili-dictionary' ); ?></option>
			<option value="append" ><?php esc_html_e( 'Imported comments be appended...', 'xili-dictionary' ); ?></option>
		</select>
		</label>
		</div>
		<?php
	}

	/**
	 * @since 2.9
	 */
	public function xd_file_scanning_setting_callback_row() {
		?>
		<div class="sub-field">
			<label for="_xd_place"><?php esc_html_e( 'Place of source files', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="_xd_place" id="_xd_place" class='postform'>
				<option value="theme" > <?php esc_html_e( 'from the current theme', 'xili-dictionary' ); ?> </option>
				<?php
				if ( is_child_theme() ) {
					$parent_theme_obj = wp_get_theme( get_option( 'template' ) );
					?>
				<option value="parenttheme" > <?php /* translators: */ printf( esc_html__( 'from the current parent theme (%s)', 'xili-dictionary' ), $parent_theme_obj->get( 'Name' ) ); ?> </option>
				<?php } ?>
				<option value="plugin" > <?php esc_html_e( 'from a selected plugin', 'xili-dictionary' ); ?> </option>
			</select>
			<p class="description"><?php esc_html_e( 'Define where to scan files.', 'xili-dictionary' ); ?></p>
		</div>

		<div id="plugins-option" class="sub-field" style="display:none;" >
			<?php $this->echo_div_plugins_option(); ?>
			<p class="description"><?php esc_html_e( 'Select the plugin from where to scan translatable term.', 'xili-dictionary' ); ?><br /><?php esc_html_e( 'Each line includes Text Domain (if available in plugin sources header) and state of plugin.', 'xili-dictionary' ); ?></p>
		</div>
		<?php
		$this->echo_div_source_comments();
		echo '<br />' . esc_html__( 'What about found msgid ?', 'xili-dictionary' );
		echo ' <span id="backup_pot_label"></span> ';
		echo '<select name="backup_pot" id="backup_pot" class="postform">';
			echo '<option value="onlydic" >' . esc_html__( 'only import in dictionary', 'xili-dictionary' ) . '</option>';
			echo '<option value="onlypot" >' . esc_html__( 'only import in a POT file', 'xili-dictionary' ) . '</option>';
			echo '<option value="both" >' . esc_html__( 'import and backup in POT', 'xili-dictionary' ) . '</option>';
		echo '</select>'; //2.11
	}

	public function _xd_importing_type_define( $input ) {
		return $input;
	}

	public function importing_process_callback() {
		check_ajax_referer( 'xd_importing_process' );

		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
			ini_set( 'memory_limit', '256M' );
			ini_set( 'implicit_flush', '1' );
			ignore_user_abort( true );
		}

		// Save step and count so that it can be restarted.
		if ( ! get_option( '_xd_importing_step' ) ) {
			update_option( '_xd_importing_step', 1 );
			update_option( '_xd_importing_start', 0 );
		}

		$step = (int) get_option( '_xd_importing_step', 1 );
		$min = (int) get_option( '_xd_importing_start', 0 );
		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;
		$max = ( $min + $count ) - 1;
		$start = $min;

		$from_what = $_POST['from-what'];

		$pomofile = ''; // future use

		$lang = ( ! empty( $_POST['_xd_lang'] ) ) ? $_POST['_xd_lang'] : 'en_US';
		if ( 'sources' == $from_what ) {
			$lang = '';
			$create_pot_file = ( in_array( $_POST['backup_pot'], array( 'onlypot', 'both' ) ) ) ? true : false;
		}
		$type = ( ! empty( $_POST['_xd_file_extend'] ) ) ? $_POST['_xd_file_extend'] : 'po';

		$local_file = $_POST['_xd_place'];
		$local_file2 = ( 'local' == $local_file ) ? $local_file . '-' : ( ( 'languages' == $local_file ) ? $this->theme_domain() . '-' : '' );

		if ( 'plugin' == $local_file || 'pluginwplang' == $local_file ) {
			$plugin_path = ( ! empty( $_POST['_xd_plugin'] ) ) ? $_POST['_xd_plugin'] : 'undefined';
			$pomofile = $this->path_plugin_file( $plugin_path, $lang, $type, ( 'pluginwplang' == $_POST['_xd_place'] ) );
		} else {
			$plugin_path = '';
		}

		$multilocal = ( isset( $_POST['_xd_multi_local'] ) && 'blog-dir' == $_POST['_xd_multi_local'] ) ? true : false; // find in original theme in multisite

		switch ( $step ) {

			// STEP 1. Clean all tables.
			case 1:
				if ( 'files' == $from_what ) {
					$count_entries = $this->caching_file( $type, $lang, $local_file, $pomofile, $multilocal );
				} else {
					add_filter( 'xd-pot-scanning-project', array( &$this, 'xd_pot_scanning_xili_project' ), 10, 2 );
					$result_array = $this->caching_pot_obj( $local_file, $plugin_path, $create_pot_file );
					if ( $result_array ) {
						$this->looping_output( $result_array['infos'] );
					}
					$count_entries = ( $result_array ) ? $result_array['count'] : false;
				}

				if ( false != $count_entries ) {

					update_option( '_xd_importing_step', $step + 1 );
					update_option( '_xd_importing_start', 0 );
					update_option( '_xd_importing_count_entries', $count_entries );
					if ( 'files' == $from_what ) {
						/* translators: */
						$this->looping_output( sprintf( esc_html__( 'File %3$s%2$s.%1$s found ! (%4$s entries)', 'xili-dictionary' ), $type, $lang, $local_file2, $count_entries ) );
					} else {
						/* translators: */
						$this->looping_output( sprintf( esc_html__( 'Found %1$s entries in sources of %2$s', 'xili-dictionary' ), $count_entries, $local_file ) );
					}
				} else {

					delete_option( '_xd_importing_step' );
					delete_option( '_xd_importing_start' );
					delete_option( '_xd_cache_pomo_file' );
					delete_option( '_xd_importing_count_entries' );

					if ( 'files' == $from_what ) {
						if ( false === strpos( $lang, '_' ) && 'po' == $type ) {
							$type = 'pot';
						}
						// special case
						/* translators: */
						$this->looping_output( sprintf( esc_html__( 'Impossible to find file %3$s%2$s.%1$s (%4$s)', 'xili-dictionary' ), $type, $lang, $local_file2, $pomofile ), 'error' );

					} else {
						/* translators: */
						$this->looping_output( sprintf( esc_html__( 'Impossible to find entries in sources of %1$s', 'xili-dictionary' ), $local_file ) );
					}
				}
				break;

			// STEP 2. Loop
			case 2:
				if ( isset( $_POST['backup_pot'] ) && 'onlypot' == $_POST['backup_pot'] ) {
					update_option( '_xd_importing_step', $step + 1 );
					update_option( '_xd_importing_start', 0 );
					$this->looping_output( esc_html__( 'msgs only in pot file', 'xili-dictionary' ), 'error' );

				} else {
					//2.11
					if ( $this->importing_msgs( $start, $lang ) ) {
						update_option( '_xd_importing_step', $step + 1 );
						update_option( '_xd_importing_start', 0 );

						if ( empty( $start ) ) {

							$this->looping_output( esc_html__( 'No msgs to import', 'xili-dictionary' ), 'error' );
						}
					} else {
						update_option( '_xd_importing_start', $max + 1 );

						$count_entries = get_option( '_xd_importing_count_entries', $max + 1 );
						$end = ( $count_entries > $max ) ? $max + 1 : $count_entries;
						/* translators: */
						$this->looping_output( sprintf( esc_html__( 'Importing msgs (%1$s - %2$s)', 'xili-dictionary' ), $min, $end ) );

					}
				}
				break;

			default:
				delete_option( '_xd_importing_step' );
				delete_option( '_xd_importing_start' );
				delete_option( '_xd_cache_pomo_file' );
				delete_option( '_xd_importing_count_entries' );

				$this->looping_output( esc_html__( 'Importation Complete', 'xili-dictionary' ), 'success' );

				// if not exists, update description of origin - here to avoid hanging loop
				$origin_theme = $_POST['_origin_theme'];
				$origin = get_term_by( 'name', $origin_theme, 'origin' );
				if ( '' == $origin->description ) {
					$list_plugins = get_plugins();
					if ( in_array( $origin_theme, array_keys( $list_plugins ) ) ) {
						/* translators: */
						$description = sprintf( esc_html__( 'from plugin: %s', 'xili-dictionary' ), $this->get_plugin_name( $origin_theme, '' ) );
					} else {
						/* translators: */
						$description = sprintf( esc_html__( 'from theme: %s', 'xili-dictionary' ), $origin_theme );
					}
					wp_update_term( (int) $origin->term_id, 'origin', array( 'description' => $description ) );
				}
				break;
		}

	}

	public function get_plugin_name( $plugin_path, $from = '' ) {
		$list_plugins = get_plugins();
		if ( isset( $list_plugins[ $plugin_path ] ) ) {
			$cur_plugin = $list_plugins[ $plugin_path ];
			return ( $from . $cur_plugin ['Name'] );
		}
		return $plugin_path;
	}

	public function path_plugin_file( $plugin_path, $lang, $type = 'po', $wp_lang_dir = false ) {
		// search language path
		$list_plugins = get_plugins();
		$cur_plugin = $list_plugins[ $plugin_path ];
		$plugin_folder = str_replace( basename( $plugin_path ), '', $plugin_path );

		if ( '' == $cur_plugin ['DomainPath'] && '' != $plugin_folder ) {
			$domain_path = $this->detect_plugin_language_sub_folder( $plugin_path );
		} else {
			$domain_path = $cur_plugin ['DomainPath'];
		}

		if ( '' == $cur_plugin ['TextDomain'] && '' != $plugin_folder ) {
			if ( '' == $this->plugin_text_domain ) {
				$plugin_text_domain = $this->detect_plugin_textdomain_sub_folder( $plugin_path );
			} else {
				$plugin_text_domain = $this->plugin_text_domain;
			}
		} else {
			$plugin_text_domain = $cur_plugin ['TextDomain'];
		}

		if ( 'plugin pot file' == $lang ) {
			// no pot in WP_LANG_DIR
			$full_path = str_replace( '//', '/', WP_PLUGIN_DIR . '/' . str_replace( basename( $plugin_path ), '', $plugin_path ) . $domain_path . '/' . $plugin_text_domain . '.pot' );
		} else {
			if ( $wp_lang_dir ) {
				$full_path = str_replace( '//', '/', WP_LANG_DIR . '/plugins/' . $plugin_text_domain . '-' . $lang . '.' . $type );
			} else {
				$full_path = str_replace( '//', '/', WP_PLUGIN_DIR . '/' . str_replace( basename( $plugin_path ), '', $plugin_path ) . $domain_path . '/' . $plugin_text_domain . '-' . $lang . '.' . $type );
			}
		}

		return $full_path;
	}

	public function detect_plugin_language_sub_folder( $plugin_path ) {
		//
		$this->plugin_text_domain = '';
		$this->plugin_domain_path = '';
		$plugin_folder = str_replace( basename( $plugin_path ), '', $plugin_path );
		if ( '' != $plugin_folder ) {
			$full_path = str_replace( '//', '/', WP_PLUGIN_DIR . '/' . $plugin_folder );
			$this->find_files( $full_path, '/^.*\.(mo|po|pot)$/', array( &$this, 'plugin_language_folder' ) );
			return str_replace( $full_path, '', $this->plugin_domain_path );
		} else {
			return '';
		}

	}

	public function plugin_language_folder( $path, $filename ) {
		$file_part = explode( '.', $filename );
		if ( '' == $this->plugin_text_domain && 'pot' != $file_part[1] ) {
			// case -xx_YY.
			if ( preg_match( '/(^.*)-(\S\S_\S\S)\.(\So)$/', $filename, $matches ) ) {
				$this->plugin_text_domain = $matches[1];
			} elseif ( preg_match( '/(^.*)-(\S\S)\.(\So)$/', $filename, $matches ) ) {
				// case -ja.
				$this->plugin_text_domain = $matches[1];
			}
		}
		$this->plugin_domain_path = str_replace( '//', '/', $path );
	}

	public function detect_plugin_textdomain_sub_folder( $plugin_path, $suffix = 'pot' ) {
		//
		$this->plugin_text_domain = "no{$suffix}file";
		$full_path = str_replace( '//', '/', WP_PLUGIN_DIR . '/' . str_replace( basename( $plugin_path ), '', $plugin_path ) );
		$this->find_files( $full_path, '/^.*\.( ' . $suffix . ' )$/', array( &$this, 'plugin_textdomain_folder' ), $suffix );
		return $this->plugin_text_domain;
	}

	public function plugin_textdomain_search( $key_plugin, $not_found = 'notfound' ) {
		if ( '.' != dirname( $key_plugin ) ) {
			// seach mo, po or pot
			$text_domain = $this->detect_plugin_textdomain_sub_folder( $key_plugin, 'pot' );
			if ( 'nopotfile' == $text_domain ) {
				$text_domain = $this->detect_plugin_textdomain_sub_folder( $key_plugin, 'mo' );
			}
			if ( 'nomofile' == $text_domain ) {
				$text_domain = $this->detect_plugin_textdomain_sub_folder( $key_plugin, 'po' );
			}
			if ( 'nopofile' == $text_domain ) {
				$text_domain = 'nolangfiles';
			}
		} else {
			$text_domain = $not_found;
		}
		return $text_domain;
	}

	public function plugin_textdomain_folder( $path, $filename, $suffix = 'pot' ) {
		if ( 'pot' == $suffix ) {
			$this->plugin_text_domain = str_replace( '.' . $suffix, '', $filename );
			return;
		} else {
			// try to find core w/o lang
			if ( preg_match( '/(^.*)-(\S\S_\S\S)\.(\So)$/', $filename, $matches ) ) {
				$this->plugin_text_domain = $matches[1];
			} elseif ( preg_match( '/(^.*)-(\S\S)\.(\So)$/', $filename, $matches ) ) {
				// case -ja.
				$this->plugin_text_domain = $matches[1];
			}
			return;
		}
	}

	public function get_origin_plugin_paths( $empty = false ) {
		$plugin_paths = array();
		// get origins
		$origins = get_terms( 'origin' );
		if ( ! is_wp_error( $origins ) && array() != $origins ) {
			// if in list plugins
			$list_plugins_keys = array_keys( get_plugins() );
			foreach ( $origins as $one_origin ) {
				// if not empty
				if ( in_array( $one_origin->name, $list_plugins_keys ) ) {
					$plugin_paths[] = $one_origin->name;

				}
			}
		}

		return $plugin_paths;
	}

	/**
	 * to limit scanning for each plugin inside - can be used for other plugins
	 *
	 * filter : xd-pot-scanning-project
	 *
	 * @since 2.9
	 */
	public function xd_pot_scanning_xili_project( $the_project, $plugin_key ) {

		switch ( $plugin_key ) {

			case 'xili-language/xili-language.php':
				$the_project['excludes'] = array( 'xili-includes/locales.php' ); // now bbp addon integrated xl 2.21.2
				break;
			case 'xili-language/xili-xl-bbp-addon.php':
				$the_project['includes'] = array( 'xili-xl-bbp-addon.php' );
				break;
		}

		return $the_project;
	}

	private static function caching_pot_obj( $place, $plugin_key, $backup_pot = false ) {
		global $xili_dictionary; // called by ajax

		$entries = false;

		if ( 'plugin' == $place && '' != $plugin_key ) {

			$project_key = dirname( $plugin_key ); // w/o php file
			$list_plugins = get_plugins();
			if ( isset( $list_plugins[ $plugin_key ] ) && '.' != $project_key ) {
				$cur_plugin = $list_plugins[ $plugin_key ];

				$cur_plugin_textdomain = $cur_plugin['TextDomain'];

				if ( '' == $cur_plugin_textdomain ) {
					$cur_plugin_textdomain = $xili_dictionary->plugin_textdomain_search( $plugin_key );
				}

				$the_plugin_project = array(
					'title'    => sprintf( '%s Version %s generated by ©xili-dictionary', $cur_plugin['Name'], $cur_plugin['Version'] ),
					'file'     => str_replace( '//', '/', WP_PLUGIN_DIR . '/' . $project_key . '/' . $cur_plugin['DomainPath'] . '/' . $cur_plugin_textdomain . '.pot' ),
					'excludes' => array(),
					'includes' => array(),
					'working_path' => WP_PLUGIN_DIR . '/' . $project_key,
				);

				$projects = array( $project_key => apply_filters( 'xd-pot-scanning-project', $the_plugin_project, $plugin_key ) ); // to adapt excludes and includes

				$xd_extractor = new XD_extractor( $projects );

				$entries = $xd_extractor->generate_entries( $project_key );

				$params = ( in_array( $_POST['backup_pot'], array( 'onlypot', 'both' ) ) ) ? $projects[ $project_key ] : array();

				unset( $xd_extractor );

			}
		} elseif ( 'theme' == $place || 'parenttheme' == $place ) {

			$origin_theme = $xili_dictionary->get_option_theme_name( false );

			if ( function_exists( 'the_theme_domain' ) ) { // in new xili-language
				$theme_text_domain = the_theme_domain();
			} else {
				$theme_text_domain = $xili_dictionary->get_option_theme_name( false ); // need analysis as in xl
			}

			$project_title = ( 'theme' == $place ) ? $origin_theme : get_option( 'template' );

			$folder = ( 'theme' == $place ) ? $xili_dictionary->langfolder : $xili_dictionary->parentlangfolder;
			$active_theme_directory = ( 'theme' == $place ) ? get_stylesheet_directory() : get_template_directory();
			$file = $active_theme_directory . $folder . $theme_text_domain . '.pot';

			$the_theme_project = array(
				'title'    => $project_title . ' generated by xili-dictionary',
				'file'     => $file,
				'excludes' => array(),
				'includes' => array(),
				'working_path' => $active_theme_directory,
			);

			$projects = array( $origin_theme => apply_filters( 'xd-pot-scanning-project', $the_theme_project, $plugin_key ) ); // to adapt excludes and includes
			$xd_extractor = new XD_extractor( $projects );

			$entries = $xd_extractor->generate_entries( $origin_theme );
			unset( $xd_extractor );

		}
		if ( false !== $entries ) {
			// cache file
			update_option( '_xd_cache_pomo_file', $entries );
			$cur_key = ( 'plugin' == $place ) ? $project_key : $origin_theme;
			$result = false;
			if ( $backup_pot && is_writable( dirname( $projects[ $cur_key ]['file'] ) ) ) { // ok if file do not exists 2.9.2 - 17:00
				$temp_po = new PO();
				$temp_po->entries = $entries->entries;

				$temp_po->set_header( 'Project-Id-Version', $projects[ $cur_key ]['title'] );
				$temp_po->set_header( 'Report-Msgid-Bugs-To', 'http://dev.xiligroup.com/' );
				$temp_po->set_header( 'POT-Creation-Date', gmdate( 'Y-m-d H:i:s+00:00' ) );
				$temp_po->set_header( 'MIME-Version', '1.0' );
				$temp_po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
				$temp_po->set_header( 'Content-Transfer-Encoding', '8bit' );
				$temp_po->set_header( 'PO-Revision-Date', gmdate( 'Y' ) . '-MO-DA HO:MI+ZONE' );
				$temp_po->set_header( 'Last-Translator', 'FULL NAME <EMAIL@ADDRESS>' );
				$temp_po->set_header( 'Language-Team', 'LANGUAGE <EMAIL@ADDRESS>' );

				// Write POT file
				$result = $temp_po->export_to_file( $projects[ $cur_key ]['file'] );
				unset( $temp_po );
			}
			$c = count( $entries->entries );
			unset( $entries );
			$file_pot_title = ( $backup_pot && $result ) ? esc_html__( 'POT file updated', 'xili-dictionary' ) : esc_html__( 'POT file infos', 'xili-dictionary' );
			$infos = $file_pot_title . ':' . $xili_dictionary->display_file_states( $xili_dictionary->state_of_file( $projects[ $cur_key ]['file'] ) );
			return array(
				'count' => $c,
				'infos' => $infos,
			);

		} else {
			unset( $entries );
			return false;
		}
	}


	private static function caching_file( $type, $lang, $local_file = '', $pomofile = '', $multilocal = true ) {
		global $xili_dictionary; // called by ajax

		// search file
			$temp_po = $xili_dictionary->import_pomo( $type, $lang, $local_file, $pomofile, $multilocal );
		if ( false !== $temp_po ) {
			// cache file
			update_option( '_xd_cache_pomo_file', $temp_po );

			return count( $temp_po->entries );
		} else {
			return false;
		}
	}

	private static function importing_msgs( $start, $lang ) {
		global $xili_dictionary; // called by ajax

		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;
		$importing_po_comments = ( isset( $_POST['_importing_po_comments'] ) ) ? $_POST['_importing_po_comments'] : '';
		$origin_theme = $_POST['_origin_theme'];

		$xili_dictionary->get_list_languages();
		$temp_po = get_option( '_xd_cache_pomo_file', false );
		$count_entries = count( $temp_po->entries );
		if ( false !== $temp_po && ( $start < $count_entries ) ) {
			$i = 0;
			foreach ( $temp_po->entries as $pomsgid => $pomsgstr ) {
				$i++;
				if ( $i < $start ) {
					continue;
				}
				if ( $i > ( $start + $count ) - 1 ) {
					break;
				}
				if ( $i > $count_entries ) {
					break;
				}
				$xili_dictionary->importing_mode = true; // not manual
				// add local_tag if mo local file imported
				if ( 'local' == $_POST['_xd_place'] && 'mo' == $_POST['_xd_file_extend'] ) {
					$pomsgstr->extracted_comments = $xili_dictionary->local_tag . ' (imported from mo) ' . $pomsgstr->extracted_comments;
				}
				$lines = $xili_dictionary->pomo_entry_to_xdmsg(
					$pomsgid,
					$pomsgstr,
					$lang,
					array(
						'importing_po_comments' => $importing_po_comments,
						'origin_theme' => $origin_theme,
					)
				);
				$xili_dictionary->importing_mode = false;
			}
			return false;
		}
		return true;
	}

	/**
	 * hidden values now
	 *
	 * @since 2.9
	 */
	public function xd_setting_callback_process_section() {
		echo '<input name="_xd_looping_rows" type="hidden" id="_xd_looping_rows" value="50">';
		echo '<input name="_xd_looping_delay_time" type="hidden" id="_xd_looping_delay_time" value=".5">';
	}

	// used by new ajax
	public function pomo_entry_to_xdmsg( $pomsgid, $pomsgstr, $curlang = 'en_US', $args = array( 'importing_po_comments' => '', 'origin_theme' => '' ) ) {
		$nblines = array( 0, 0 ); // id, str count
		// test if msgid exists
		$result = $this->msgid_exists( $pomsgstr->singular, $pomsgstr->context );

		if ( false == $result ) {
			// create the msgid
			$type = 'msgid';
			$msgid_post_id = $this->insert_one_cpt_and_meta( $pomsgstr->singular, $pomsgstr->context, $type, 0, $pomsgstr );
			$nblines[0]++;
		} else {
			$msgid_post_id = $result[0];
			if ( '' != $args['importing_po_comments'] ) {
				$this->insert_comments( $msgid_post_id, $pomsgstr, $args['importing_po_comments'] );
			}
		}

		// add origin taxonomy
		if ( '' != $args['origin_theme'] ) {
			wp_set_object_terms( $msgid_post_id, $args['origin_theme'], 'origin', true ); // true to append to existing
		}

		if ( null != $pomsgstr->is_plural ) {
			// create msgid plural (child of msgid)
			// $pomsgstr->plural, $msgid_post_id
			$result = $this->msgid_exists( $pomsgstr->plural );
			if ( false === $result ) {
				$msgid_post_id_plural = $this->insert_one_cpt_and_meta( $pomsgstr->plural, null, 'msgid_plural', $msgid_post_id, $pomsgstr );
				// add origin taxonomy
				if ( '' != $args['origin_theme'] ) {
					wp_set_object_terms( $msgid_post_id_plural, $args['origin_theme'], 'origin', true ); // true to append to existing
				}
			}
		}

		// create msgstr - taxonomy - if lang exists
		if ( '' != $curlang ) {
			if ( null == $pomsgstr->is_plural || 0 == $pomsgstr->is_plural ) {

				$msgstr_content = ( isset( $pomsgstr->translations[0] ) ) ? $pomsgstr->translations[0] : '';

				if ( $this->is_importing_pot( $curlang ) ) {
					$msgstr_content = '';
				}

				if ( '' != $msgstr_content ) {
					// test exists with taxo before
					$result = $this->msgstr_exists( $msgstr_content, $msgid_post_id, $curlang );
					if ( false === $result ) {
						$msgstr_post_id = $this->insert_one_cpt_and_meta( $msgstr_content, null, 'msgstr', 0, $pomsgstr );
						wp_set_object_terms( $msgstr_post_id, $this->target_lang( $curlang ), TAXONAME );
						$nblines[1]++;
					} else {
						$msgstr_post_id = $result[0];
					}

					// create link according lang

					$res = get_post_meta( $msgid_post_id, $this->msglang_meta, false );
					$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
					$thelangs['msgstrlangs'][ $curlang ]['msgstr'] = $msgstr_post_id;
					update_post_meta( $msgid_post_id, $this->msglang_meta, $thelangs );
					update_post_meta( $msgstr_post_id, $this->msgidlang_meta, $msgid_post_id );

					// add origin taxonomy
					if ( '' != $args['origin_theme'] ) {
						wp_set_object_terms( $msgstr_post_id, $args['origin_theme'], 'origin', true ); // true to append to existing
					}
				}
			} else {
				// $pomsgstr->translations
				$i = 0;
				$parentplural = 0;
				foreach ( $pomsgstr->translations as $onetranslation ) {
					$msgstr_plural = 'msgstr_' . $i;
					$parent = ( 0 == $i ) ? 0 : $parentplural;
					if ( $this->is_importing_pot( $curlang ) ) {
						$onetranslation = ''; // 2.6.1
					}

					if ( '' != $onetranslation ) {
						// test exists with taxo before
						$result = $this->msgstr_exists( $onetranslation, $msgid_post_id, $curlang );
						if ( false === $result ) {
							$msgstr_post_id_plural = $this->insert_one_cpt_and_meta( $onetranslation, null, $msgstr_plural, $parent, $pomsgstr );
							wp_set_object_terms( $msgstr_post_id_plural, $this->target_lang( $curlang ), TAXONAME );
							$nblines[1]++;
						} else {
							$msgstr_post_id_plural = $result[0];
						}
						update_post_meta( $msgstr_post_id_plural, $this->msgidlang_meta, $msgid_post_id );
						// add origin taxonomy
						if ( '' != $args['origin_theme'] ) {
							wp_set_object_terms( $msgstr_post_id_plural, $args['origin_theme'], 'origin', true ); // true to append to existing - fixes 2.6.2
						}
					}

					if ( 0 == $i ) {
						$parentplural = $msgstr_post_id_plural;

						// create link according lang in msgid
						$res = get_post_meta( $msgid_post_id, $this->msglang_meta, false );
						$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
						$thelangs['msgstrlangs'][ $curlang ][ $msgstr_plural ] = $msgstr_post_id_plural;
						update_post_meta( $msgid_post_id, $this->msglang_meta, $thelangs );

					} // only first str

					$i++;
				}
			}
		}
		return $nblines;
	}

	/**
	 * insert comments of msgid / msgstr
	 *
	 * called by pomo_entry_to_xdmsg
	 *
	 */
	public function insert_comments( $post_id, $entry, $import_comment_mode = 'replace' ) {

		$references = ( ! empty( $entry->references ) ) ? implode( ' #: ', $entry->references ) : '';
		$flags = ( ! empty( $entry->flags ) ) ? implode( ', ', $entry->flags ) : '';
		$extracted_comments = ( ! empty( $entry->extracted_comments ) ) ? $entry->extracted_comments : '';
		$translator_comments = ( ! empty( $entry->translator_comments ) ) ? $entry->translator_comments : '';

		if ( 'replace' == $import_comment_mode ) {
			// update references in excerpt
			$postarr = get_post( $post_id, ARRAY_A );

			$postarr['post_excerpt'] = $references;
			$postarr['post_content'] = wp_slash( $postarr['post_content'] ); // 2.12.2

			wp_insert_post( $postarr );

			// update comments in meta
			if ( '' != $extracted_comments ) {
				update_post_meta( $post_id, $this->msg_extracted_comments, $extracted_comments );
			}
			if ( '' != $translator_comments ) {
				update_post_meta( $post_id, $this->msg_translator_comments, $translator_comments );
			}
			if ( '' != $flags ) {
				update_post_meta( $post_id, $this->msg_flags, $flags );
			}
			$this->update_msgid_flags( $post_id, $postarr['post_content'] ); // 2.9

		} elseif ( 'append' == $import_comment_mode ) {
			// don't erase existing comments - can be risked

			$dummy = 1;
		}

	}

	/**
	 * Import POMO file in respective class PO or MO
	 *
	 *
	 * @since 2.3
	 */
	public function import_pomo( $extend = 'po', $lang = 'en_US', $local = '', $pomofile = '', $multilocal = true ) {

		if ( in_array( $extend, array( 'po', 'mo' ) ) ) {

			if ( 'po' == $extend ) {
				$pomo = new PO();

				if ( $lang == $this->theme_domain() ) {
					$extend = 'pot';
				}
			} else {
				$pomo = new MO();
			}
			$path = '';

			if ( is_multisite() && $multilocal ) {
				if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
					$path = $uploads['basedir'] . '/languages/';
				}
			} else {
				if ( ! is_child_theme() ) {
					$path = $this->active_theme_directory . $this->langfolder;
				} else {
					if ( 'parenttheme' == $local ) {
						$path = get_template_directory() . $this->parentlangfolder;
					} else {
						$path = get_stylesheet_directory() . $this->langfolder;
					}
				}
			}

			if ( '' == $pomofile && 'local' == $local ) {

				$pomofile = $path . $local . '-' . $lang . '.' . $extend;

			} elseif ( 'theme' == $local || 'parenttheme' == $local ) {

				$pomofile = $path . $lang . '.' . $extend;

			} elseif ( 'languages' == $local ) {

				$pomofile = WP_LANG_DIR . '/themes/' . $this->theme_domain() . '-' . $lang . '.' . $extend;

			}

			if ( file_exists( $pomofile ) ) {
				if ( ! $pomo->import_from_file( $pomofile ) ) {
					return false;
				} else {
					return $pomo;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

}
