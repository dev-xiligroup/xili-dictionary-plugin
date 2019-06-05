<?php

/**
* XD Admin class javascript functions and containers
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Javascripts {

	public function build_file_full_path( $request ) {
		$file = ' ? ';
		if ( function_exists( 'the_theme_domain' ) ) { // in new xili-language
			$theme_text_domain = the_theme_domain();
		} else {
			$theme_text_domain = $this->get_option_theme_name( false ); // need analysis as in xl
		}

		if ( 'sources' == $request['fromwhat'] ) {
			if ( 'theme' == $request['place'] || 'parenttheme' == $request['place'] ) {
				//$origin_theme = $this->get_option_theme_name( false );

				$folder = ( 'theme' == $request['place'] ) ? $this->langfolder : $this->parentlangfolder;
				$active_theme_directory = ( 'theme' == $request['place'] ) ? get_stylesheet_directory() : get_template_directory();
				$file = $active_theme_directory . $folder . $theme_text_domain . '.pot';

			} else {
				$plugin_key = $request['plugin'];
				$project_key = dirname( $request['plugin'] ); // w/o php file
				$list_plugins = get_plugins();
				if ( isset( $list_plugins[ $plugin_key ] ) && '.' != $project_key ) {
					$cur_plugin = $list_plugins[ $plugin_key ];

					$cur_plugin_textdomain = $cur_plugin['TextDomain'];

					if ( '' == $cur_plugin_textdomain ) {
						$cur_plugin_textdomain = $this->plugin_textdomain_search( $plugin_key );
					}
					$file = str_replace( '//', '/', WP_PLUGIN_DIR . '/' . $project_key . '/' . $cur_plugin['DomainPath'] . '/' . $cur_plugin_textdomain . '.pot' );
				}
			}
		} else {  // files (import / export)

			switch ( $request['local'] ) {
				case ( 'local' ):
					if ( in_array( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' ) ) ) {
						$local = 'local';
					} else {
						$local = 'local-';
					}
					$base = '';
					if ( 'local' == $request['place'] ) {
						$request['place'] = 'theme'; //mixed in import only from theme
					}
					break;
				default:
					$base = '';
					$local = '';
					break;
			}

			switch ( $request['place'] ) {

				case ( 'theme' ):
					$path = get_stylesheet_directory() . $this->langfolder;
					$base = $local;
					if ( in_array( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' ) ) ) {
						$base = $theme_text_domain;
					}

					if ( is_multisite() && is_super_admin() && isset( $request['multiupload'] ) && 'blog-dir' == $request['multiupload'] ) {
						if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error']  ) {
							$path = $uploads['basedir'] . '/languages/';
						}
					}
					break;

				case ( 'parenttheme' ):
					$path = get_template_directory() . $this->parentlangfolder;
					if ( in_array( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' ) ) ) {
						$base = $theme_text_domain;
					} else {
						$base = $local;
					}
					break;

				case ( 'languages' ):
					$path = WP_LANG_DIR . '/themes/';
					if ( in_array( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' ) ) ) {
						$base = $theme_text_domain;
					} else {
						$base = $theme_text_domain . '-' . $local;
					}

					break;

				case ( 'plugin' ):
					if ( isset( $request['plugin'] ) ) {
						$plugin_key = $request['plugin'];
						$project_key = dirname( $request['plugin'] ); // w/o php file
						$list_plugins = get_plugins();
						if ( isset( $list_plugins[ $plugin_key ] ) && '.' != $project_key ) {
							$cur_plugin = $list_plugins[ $plugin_key ];

							$cur_plugin_textdomain = $cur_plugin['TextDomain'];

							if ( '' == $cur_plugin_textdomain ) {
								$cur_plugin_textdomain = $this->plugin_textdomain_search( $plugin_key );
							}
							$path = WP_PLUGIN_DIR . '/' . $project_key . '/';
							$base = $cur_plugin['DomainPath'] . '/' . $cur_plugin_textdomain;
							$base = ( in_array( $request['lang'], array( 'plugin pot file', 'pot-file' ) ) ) ? $base : $base . '-';
						} else {
							$path = WP_PLUGIN_DIR . '/';
							$base = 'undefined-';
						}
					} else {
						$path = WP_PLUGIN_DIR . '/';
						$base = 'undefined-';
					}
					break;

				case ( 'pluginwplang' ):
					$path = WP_LANG_DIR . '/plugins/';

					if ( isset( $request['plugin'] ) ) {
						$plugin_key = $request['plugin'];
						$project_key = dirname( $request['plugin'] ); // w/o php file
						$list_plugins = get_plugins();
						if ( isset( $list_plugins[ $plugin_key ] ) && '.' != $project_key ) {
							$cur_plugin = $list_plugins[ $plugin_key ];

							$cur_plugin_textdomain = $cur_plugin['TextDomain'];

							if ( '' == $cur_plugin_textdomain ) {
								$cur_plugin_textdomain = $this->plugin_textdomain_search( $plugin_key );
							}

							$base = $cur_plugin['DomainPath'] . '/' . $cur_plugin_textdomain;
							$base = ( in_array( $request['lang'], array( 'plugin pot file', 'pot-file' ) ) ) ? $base : $base . '-';
						} else {

							$base = 'undefined-';
						}
					} else {

						$base = 'undefined-';
					}
					break;

				default:
					$path = get_stylesheet_directory() . $this->langfolder;
					break;
			}
			$suffix = ( in_array( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' ) ) ) ? 'pot' : $request['suffix'];
			$lang = ( in_array( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' ) ) ) ? '' : str_replace( '-', '_', $request['lang'] );
			$base = $base . $lang;
			$file = str_replace( '//', '/', $path . $base . '.' . $suffix );
		}
		return $file;
	}

	/**
	 * called by ajax to display state of file when importing or scanning
	 *
	 * @since 2.9
	 *
	 */
	public function xd_live_state_file( $args ) {

		check_ajax_referer( 'xd-live-state-file' );

		$html = '<p>';

		$file = $this->build_file_full_path( $_REQUEST );

		$html .= $this->display_file_states( $this->state_of_file( $file ) );
		wp_send_json_success( $html . '</p>' );
	}

	/**
	 * called by ajax to display state of file when importing or scanning
	 *
	 * @since 2.9
	 *
	 */
	public function xd_from_file_exists( $args ) {
		check_ajax_referer( 'xd-from-file-exists' );

		$file = $this->build_file_full_path( $_REQUEST );
		$state = $this->state_of_file( $file );
		$return = ( $state['file_exists'] ) ? 'exists' : 'not exists';
		wp_die( $return );
	}

	public function admin_head() {
		$screen = get_current_screen();

		if ( in_array( $screen->base, array( 'xdmsg_page_import_dictionary_page', 'xdmsg_page_dictionary_page', 'xdmsg_page_download_dictionary_page' ) ) ) : // 2.9.0
		?>
			<script type="text/javascript">
			//<![CDATA[
			function show_file_states( fromwhat, place, curthemename, plugin, la, x, local, multiupload ) {

				var post = {
						fromwhat: fromwhat,
						place: place,
						theme: curthemename,
						plugin: plugin,
						lang: la,
						suffix: x,
						local: local,
						multiupload: multiupload,
						action: 'xd_live_state_file',
						_ajax_nonce : "<?php echo wp_create_nonce( 'xd-live-state-file' ); ?>"
					};

				jQuery.ajax( ajaxurl, {
					type: 'POST',
					data: post,
					dataType: 'json'
				}).done( function( x ) {
					if ( ! x.success ) {
						jQuery( '#xd-file-state' ).text( 'error' );
					}
					jQuery( '#xd-file-state' ).html( x.data );
				}).fail( function() {
					jQuery( '#xd-file-state' ).text( 'error' );
				});
			}

			function from_file_exists( fromwhat, place, curthemename, plugin, la, x, local, multiupload) {
				var output; // required for output !
				var post = {
					fromwhat: fromwhat,
					place: place,
					theme: curthemename,
					plugin: plugin,
					lang: la,
					suffix: x,
					local: local,
					multiupload: multiupload,
					action: 'xd_from_file_exists',
					_ajax_nonce : "<?php echo wp_create_nonce( 'xd-from-file-exists' ); ?>"
				};

				jQuery.ajax( ajaxurl, {
					type: 'POST',
					data: post,
					async :false, // required for output !
					dataType: 'text'
				}).done( function( x ) {
					output = x;
				}).fail( function() {
					output = 'error';
				});

				return output;
			}
			//]]>
			</script>
		<?php
		endif;
		if ( in_array( $screen->base, array( 'xdmsg_page_import_dictionary_page', 'xdmsg_page_erase_dictionary_page' ) ) ) : // 2.5.0
		?>
			<script type="text/javascript">
			//<![CDATA[

				var xd_looping_is_running = false;
				var xd_looping_run_timer;
				var xd_looping_delay_time = 0;

				function xd_importing_grab_data() {
					var values = {};
					jQuery.each(jQuery('#xd-looping-settings').serializeArray(), function(i, field) {
						values[field.name] = field.value;
					});

					if ( values['_xd_looping_delay_time'] ) {
						xd_looping_delay_time = values['_xd_looping_delay_time'] * 1000;
					}

					values['action'] = 'xd_importing_process';
					values['_ajax_nonce'] = '<?php echo wp_create_nonce( 'xd_importing_process' ); ?>';

					return values;
				}

				function xd_importing_start() {
					if ( false == xd_looping_is_running ) {
						xd_looping_is_running = true;
						jQuery( '#xd-looping-start' ).hide();
						jQuery( '#xd-looping-stop' ).show();
						jQuery( '#xd-looping-progress' ).show();
						xd_looping_log( '<p class="loading"><?php echo esc_js( __( 'Starting Importing', 'xili-dictionary' ) ); ?></p>' );
						xd_importing_run();
					}
				}

				function xd_importing_run() {
					jQuery.post(ajaxurl, xd_importing_grab_data(), function(response) {
						var response_length = response.length - 1;
						response = response.substring(0,response_length);
						xd_importing_success(response);
					});
				}

				function xd_importing_success(response) {
					xd_looping_log(response);
					var possuccess = response.indexOf ("success",0);
					var poserror = response.indexOf ("error",0);
					if ( possuccess != -1 || poserror != -1 || response.indexOf( 'error' ) > -1 ) {
						xd_looping_log( '<p><?php echo esc_js( __( 'Go to the list of msgs:', 'xili-dictionary' ) ); ?> <a href="<?php echo admin_url(); ?>edit.php?post_type=<?php echo XDMSG; ?>&page=dictionary_page"><?php echo esc_js( __( 'Continue', 'xili-dictionary' ) ); ?></a></p>' );

						xd_looping_stop();
						jQuery( '#xd-looping-start' ).hide();
					} else if ( xd_looping_is_running ) { // keep going
						jQuery( '#xd-looping-progress' ).show();
						clearTimeout( xd_looping_run_timer );
						xd_looping_run_timer = setTimeout( 'xd_importing_run()', xd_looping_delay_time );
					} else {

						xd_looping_stop();
					}
				}


				function xd_erasing_grab_data() {
					var values = {};
					jQuery.each(jQuery( '#xd-looping-settings' ).serializeArray(), function(i, field) {
						values[field.name] = field.value;
					});

					if ( values['_xd_looping_delay_time'] ) {
						xd_looping_delay_time = values['_xd_looping_delay_time'] * 1000;
					}

					values['action'] = 'xd_erasing_process';
					values['_ajax_nonce'] = '<?php echo wp_create_nonce( 'xd_erasing_process' ); ?>';

					return values;
				}



				function xd_erasing_start() {
					if ( false == xd_looping_is_running ) {
						xd_looping_is_running = true;
						jQuery( '#xd-looping-start' ).hide();
						jQuery( '#xd-looping-stop' ).show();
						jQuery( '#xd-looping-progress' ).show();
						xd_looping_log( '<p class="loading"><?php echo esc_js( __( 'Starting Erasing', 'xili-dictionary' ) ); ?></p>' );
						xd_erasing_run();
					}
				}

				function xd_erasing_run() {
					jQuery.post(ajaxurl, xd_erasing_grab_data(), function(response) {
						var response_length = response.length - 1;
						response = response.substring(0,response_length);
						xd_erasing_success(response);
					});
				}

				function xd_erasing_success(response) {
					xd_looping_log(response);
					var possuccess = response.indexOf ("success",0);
					var poserror = response.indexOf ("error",0);
					if ( -1 != possuccess || -1 != poserror || response.indexOf( 'error' ) > -1 ) {
						xd_looping_log( '<p><?php echo esc_js( __( 'Go to the list of msgs:', 'xili-dictionary' ) ); ?> <a href="<?php echo admin_url(); ?>edit.php?post_type=<?php echo XDMSG; ?>&page=dictionary_page"><?php echo esc_js( __( 'Continue', 'xili-dictionary' ) ); ?></a></p>' );
						xd_looping_stop();
						jQuery( '#xd-looping-start' ).hide();
					} else if ( xd_looping_is_running ) {
						// keep going
						jQuery( '#xd-looping-progress' ).show();
						clearTimeout( xd_looping_run_timer );
						xd_looping_run_timer = setTimeout( 'xd_erasing_run()', xd_looping_delay_time );
					} else {
						xd_looping_stop();
					}
				}

				function xd_looping_stop() {
					jQuery( '#xd-looping-start' ).show();
					jQuery( '#xd-looping-stop' ).hide();
					jQuery( '#xd-looping-progress' ).hide();
					jQuery( '#xd-looping-message p' ).removeClass( 'loading' );
					xd_looping_is_running = false;
					clearTimeout( xd_looping_run_timer );
				}

				function xd_looping_log(text) {
					if ( jQuery( '#xd-looping-message' ).css( 'display' ) == 'none' ) {
						jQuery( '#xd-looping-message' ).show();
					}
					if ( text ) {
						jQuery( '#xd-looping-message p' ).removeClass( 'loading' );
						jQuery( '#xd-looping-message' ).prepend( text );
					}
				}
				//]]>
			</script>
			<style type="text/css" media="screen">
				/*<![CDATA[*/

				div.xd-looping-updated,
				div.xd-looping-warning {
					border-radius: 3px 3px 3px 3px;
					border-style: solid;
					border-width: 1px;
					padding: 5px 5px 5px 5px;
				}

				div.xd-looping-updated {
					height: 300px;
					overflow: auto;
					display: none;
					background-color: #FFFFE0;
					border-color: #E6DB55;
					font-family: monospace;
					font-weight: bold;
				}

				div.xd-looping-updated p {
					margin: 0.5em 0;
					padding: 2px;
					float: left;
					clear: left;
				}

				div.xd-looping-updated p.loading {
					padding: 2px 20px 2px 2px;
					background-image: url( '<?php echo admin_url(); ?>images/wpspin_light.gif' );
					background-repeat: no-repeat;
					background-position: center right;
				}

				#xd-looping-stop {
					display:none;
				}

				#xd-looping-progress {
					display:none;
				}

				/*]]>*/
			</style>

		<?php
		endif;
	}

	public function display_file_states( $file_state ) {
		$return = '';
		if ( $file_state['file_exists'] ) {
			/* translators: */
			$return .= sprintf( ' <strong>%-40s</strong> [%sreadable] [%swritable]<br />', basename( $file_state['file'] ), $file_state['is_readable'] ? '' : 'not ', $file_state['is_writable'] ? '' : 'not ' );

		} else {
			/* translators: */
			$return .= sprintf( ' %-40s  [do not exists]<br />', basename( $file_state['file'] ) );
			// test dirname
			/* translators: */
			$return .= sprintf( ' <strong>%-40s</strong> [%swritable]<br />', basename( $file_state['file'] ), is_writable( dirname( $file_state['file'] ) ) ? '' : 'not ' );
		}
		/* translators: */
		$return .= sprintf( '   * Path: %s<br />', dirname( $file_state['file'] ) . '/' );
		/* translators: */
		$return .= sprintf( '   * Size: %s<br />', $file_state['file_exists'] ? number_format( $file_state['filesize'], 0 ) : '--' );
		/* translators: */
		$return .= sprintf( '   * Last updated: %s<br />', $file_state['filemtime'] ? @date( 'F jS Y H:i:s', $file_state['filemtime'] ) : '--' );
		return $return;
	}

	/**
	 * return array with states of a file - thanks to Geert
	 */
	public function state_of_file( $file ) {
		return array(
			'file'        => $file,
			'file_exists' => file_exists( $file ),
			'is_readable' => is_readable( $file ),
			'is_writable' => is_writable( $file ),
			'filemtime'   => ( is_readable( $file ) ) ? filemtime( $file ) : false,
			'filesize'    => ( is_readable( $file ) ) ? filesize( $file ) : false,
		);
	}

}
