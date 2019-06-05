<?php

/**
* XD Admin class erase screen and functions
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Erase {

	/**
	 * delete lines of dictionary
	 *
	 *
	 */
	public function erase_dictionary( $selection = '' ) {

		if ( '' == $selection ) {
			// select all ids
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
				)
			);

		} else {
			// to improve soon

		}
		if ( $listdictiolines ) {
			// loop
			foreach ( $listdictiolines as $oneline ) {
				wp_delete_post( $oneline->ID, false );
			}
		}
	}

	/**
	 * Main settings section description for the settings page
	 *
	 * @since
	 */
	public function xd_erasing_setting_callback_main_section() {
		?>

		<p><?php _e( "Here it now possible to erase your dictionary (here in WP database) after creating the .mo files (and saving the .po files which is readable with any text editor). <strong>This erasing process don't delete the .mo or .po files.</strong>", 'xili-dictionary' ); ?></p>

		<?php
	}

	public function xd_sub_selection__setting_callback_row() {
		?>

		<?php

		$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
			?>
		<div class="sub-field">
			<label for="_xd_lang"><?php esc_html_e( 'Terms (msgid / msgstr) to erase', 'xili-dictionary' ); ?>:&nbsp;</label>
	<select name="_xd_lang" id="_xd_lang" class='postform'>
		<option value=""> <?php esc_html_e( 'Original (msgid) and translations or Select language...', 'xili-dictionary' ); ?> </option>
		<option value="all-lang"> <?php esc_html_e( 'Translations (msgstr) in all languages', 'xili-dictionary' ); ?> </option>
				<?php
				foreach ( $listlanguages as $language ) {
					$selected = ( isset( $_GET[ QUETAG ] ) && $language->name == $_GET[ QUETAG ] ) ? 'selected=selected' : '';
					/* translators: */
					echo '<option value="' . $language->name . '" ' . $selected . ' >' . sprintf( esc_html__( 'Translations (msgstr) in %s', 'xili-dictionary' ), __( $language->description, 'xili-dictionary' ) ) . '</option>';
				}
				?>
	</select>
	<p class="description"><?php esc_html_e( 'Language of the translated terms to be erased. Without selection, Original (msgid) AND translation (msgstr) will both be erased.', 'xili-dictionary' ); ?></p>
	</div>
	<div class="sub-field">
		<?php

		$cur_theme_name = $this->get_option_theme_name();

		$listterms = get_terms( 'origin', array( 'hide_empty' => false ) );
		echo '<div class="dialogorigin">';
		if ( $listterms ) {
			$checkline = esc_html__( 'Check Origin(s) to be erased', 'xili-dictionary' ) . ':<br />';
			$i = 0;
			echo '<table class="checktheme" ><tr>';
			foreach ( $listterms as $onetheme ) {
				$checked = ( $onetheme->name == $cur_theme_name ) ? 'checked="checked"' : '';
				$checkline .= '<td><input type="checkbox" ' . $checked . ' id="theme-' . $onetheme->term_id . '" name="theme-' . $onetheme->term_id . '" value="' . $onetheme->slug . '" />&nbsp;' . $onetheme->name . '</td>';
				$i++;
				if ( 0 == ( $i % 2 ) ) {
					$checkline .= '</tr><tr>';
				}
			}
			echo $checkline . '</tr></table>';
		}
		echo '</div>';
		?>
	<p class="description"><?php esc_html_e( 'Origins of the msgs to be erased. Without selection, msg without origin will be erased', 'xili-dictionary' ); ?></p>
	</div>
	<div class="sub-field">
		<input id="_xd_local" name="_xd_local" type="checkbox" id="_xd_local" value="local" />
		<label for="_xd_local"><?php esc_html_e( 'Locale msgs only', 'xili-dictionary' ); ?></label>
		<p class="description"><?php esc_html_e( 'Erase only locale msgs (as saved in local-xx_YY files)', 'xili-dictionary' ); ?></p>
	</div>

	<?php
	}

	public function xd_erasing_setting_callback_row() {
		?>

		<input name="_xd_looping_rows" type="text" id="_xd_looping_rows" value="50" class="small-text" />
		<label for="_xd_looping_rows"><?php esc_html_e( 'Number of entries in one step', 'xili-dictionary' ); ?></label>

		<?php
	}

	/**
	 * Edit Delay Time setting field
	 *
	 * @since
	 */
	public function xd_erasing_setting_callback_delay_time() {
		?>

	<input name="_xd_looping_delay_time" type="text" id="_xd_looping_delay_time" value=".5" class="small-text" />
	<label for="_xd_looping_delay_time"><?php esc_html_e( 'second(s) delay between each group of rows', 'xili-dictionary' ); ?></label>
	<p class="description"><?php esc_html_e( 'Keep this high to prevent too-many-connection issues.', 'xili-dictionary' ); ?></p>

		<?php
	}

	public function xd_erasing_init_settings() {
		add_settings_section( 'xd_erasing_main', esc_html__( 'Erasing the dictionary', 'xili-dictionary' ), array( &$this, 'xd_erasing_setting_callback_main_section' ), 'xd_erasing' );

		// erasing sub-selections
		add_settings_field( '_xd_sub_selection', esc_html__( 'What to erase ?', 'xili-dictionary' ), array( &$this, 'xd_sub_selection__setting_callback_row' ), 'xd_erasing', 'xd_erasing_main' );
		register_setting( 'xd_erasing_main', '_xd_sub_selection', '_xd_sub_selection_define' );

		// ajax section
		add_settings_section( 'xd_erasing_tech', esc_html__( 'Processing...', 'xili-dictionary' ), array( &$this, 'xd_setting_callback_process_section' ), 'xd_erasing' );

		// erasing rows step
		//add_settings_field( '_xd_looping_rows', esc_html__( 'Entries step', 'xili-dictionary' ), array( &$this, 'xd_erasing_setting_callback_row' ), 'xd_erasing', 'xd_erasing_tech' );
		register_setting( 'xd_erasing_tech', '_xd_looping_rows', 'sanitize_title' );

		// Delay Time
		//add_settings_field( '_xd_looping_delay_time', esc_html__( 'Delay Time', 'xili-dictionary' ), array( &$this, 'xd_erasing_setting_callback_delay_time' ), 'xd_erasing', 'xd_erasing_tech' );
		register_setting( 'xd_erasing_tech', '_xd_looping_delay_time', 'intval' );
	}

	public function xili_dictionary_erase() {
	?>
	<div class="wrap">



		<h2 class="nav-tab-wrapper"><?php esc_html_e( 'Erasing Dictionary lines', 'xili-dictionary' ); ?></h2>

		<form action="#" method="post" id="xd-looping-settings">

			<?php
			settings_fields( 'xd_erasing' );
			delete_option( '_xd_erasing_step' ); // echo get_option( '_xd_erasing_step', 1 );
			?>

			<?php do_settings_sections( 'xd_erasing' ); ?>
			<h4 class="link-back"><?php /* translators: */ printf( __( '<a href="%s">Back</a> to the list of msgs and tools', 'xili-dictionary' ), admin_url() . 'edit.php?post_type=' . XDMSG . '&page=dictionary_page' ); ?></h4>
			<p class="submit">
				<input type="button" name="submit" class="button-primary" id="xd-looping-start" value="<?php esc_html_e( 'Start erasing', 'xili-dictionary' ); ?>" onclick="xd_erasing_start()" />
				<input type="button" name="submit" class="button-primary" id="xd-looping-stop" value="<?php esc_html_e( 'Stop', 'xili-dictionary' ); ?>" onclick="xd_looping_stop()" />
				<img id="xd-looping-progress" src="<?php echo admin_url(); ?>images/wpspin_light.gif">
			</p>

			<div class="xd-looping-updated" id="xd-looping-message"></div>
		</form>
	</div>

	<?php
	}

	/**
	*
	*/
	public function erasing_process_callback() {

		check_ajax_referer( 'xd_erasing_process' );

		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
			ini_set( 'memory_limit', '256M' );
			ini_set( 'implicit_flush', '1' );
			ignore_user_abort( true );
		}

		// Save step and count so that it can be restarted.
		if ( ! get_option( '_xd_erasing_step' ) ) {
			update_option( '_xd_erasing_step', 1 );
			update_option( '_xd_erasing_start', 0 );
		}

		$step = (int) get_option( '_xd_erasing_step', 1 );
		$min = (int) get_option( '_xd_erasing_start', 0 );
		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;
		$max = ( $min + $count ) - 1;
		$start = $min;

		switch ( $step ) {

			// STEP 1. Prepare and count.
			case 1:
				$count_lines = $this->caching_msgs_to_erase();

				if ( $count_lines > 0 ) {
					update_option( '_xd_erasing_step', $step + 1 );
					update_option( '_xd_erasing_start', 0 );
					update_option( '_xd_erasing_count_lines', $count_lines );
					/* translators: */
					$this->looping_output( sprintf( esc_html__( 'Lines found ! ( %2$s lines)', 'xili-dictionary' ), '', $count_lines ) );

				} else {
					delete_option( '_xd_erasing_step' );
					delete_option( '_xd_erasing_start' );
					delete_option( '_xd_erasing_count_lines' );
					delete_option( '_xd_deletion_type' );
					delete_option( '_xd_cache_temp_array_IDs' );
					$this->looping_output( esc_html__( 'No msgs to erase', 'xili-dictionary' ), 'error' );

				}
				break;
			// STEP 2. Loop
			case 2:
				$count_lines = get_option( '_xd_erasing_count_lines', $max + 1 );

				$back = $this->erasing_msgs( $start );
				if ( in_array( $back, array( 'no-list', 'loop-over', 'loop-full' ) ) ) {
					update_option( '_xd_erasing_step', $step + 1 );
					update_option( '_xd_erasing_start', 0 );
					if ( empty( $start ) || 'no-list' == $back ) {

						if ( 'loop-full' == $back ) {
							$this->looping_output( sprintf( esc_html__( 'No more msgs to erase (%s)', 'xili-dictionary' ), $back ), 'loading' );
						} else {
							$this->looping_output( sprintf( esc_html__( 'No msgs to erase (%s)', 'xili-dictionary' ), $back ), 'error' );

						}
					}
				} else {

					update_option( '_xd_erasing_start', $max + 1 );

					$count_lines = get_option( '_xd_erasing_count_lines', $max + 1 );
					$end = ( $count_lines > $max ) ? $max + 1 : $count_lines;
					/* translators: */
					$this->looping_output( sprintf( esc_html__( 'Erasing msgs (%1$s - %2$s)', 'xili-dictionary' ), $min, $end ), 'loading' );

				}

				break;

			default:
				$count_lines = get_option( '_xd_erasing_count_lines', $max + 1 );
				delete_option( '_xd_erasing_step' );
				delete_option( '_xd_erasing_start' );
				delete_option( '_xd_erasing_count_lines' );
				delete_option( '_xd_deletion_type' );
				delete_option( '_xd_cache_temp_array_IDs' );
				/* translators: */
				$this->looping_output( sprintf( esc_html__( 'Erasing Complete (%1$s)', 'xili-dictionary' ), $count_lines ), 'success' );
				break;

		}
	}

	private static function looping_output( $output = '', $type = '' ) {

		switch ( $type ) {

			case 'success':
				$class = ' class="success"';
				break;
			case 'error':
				$class = ' class="error"';
				break;
			default:
				$class = ' class="loading"';
				break;
		}

		// Get the last query
		$before = "<p$class>";
		$after = '</p>';
		echo $before . $output . $after;
	}

	private static function erasing_msgs( $start ) {
		global $xili_dictionary;
		// $listdictiolines = $xili_dictionary->get_msgs_to_erase ( $start );
		$id_list = get_option( '_xd_cache_temp_array_IDs' );
		if ( $id_list ) { //$listdictiolines

			$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;

			$only_local = ( isset( $_POST['_xd_local'] ) ) ? true : false;

			$selected_lang = ( isset( $_POST['_xd_lang'] ) ) ? $_POST['_xd_lang'] : '';

			$deletion_type = get_option( '_xd_deletion_type' );

			// loop
			$count_lines = count( $id_list );

			$i = 0;
			foreach ( $id_list as $one_id ) {
				// to exit loop when only ids remain in loop
				//if ( in_array( $deletion_type , array( 'all_str', 'only_local' ) ) ) {
					$i++;
				if ( $i < $start ) {
					continue;
				}
				if ( $i > ( $start + $count ) - 1 ) {
					return 'loop';
				}
				if ( $i > $count_lines ) {
					return 'loop-over';
				}
				//}

				$res = get_post_meta( $one_id, $xili_dictionary->msglang_meta, false );
				$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();

				switch ( $deletion_type ) {

					case 'all':
						wp_delete_post( $one_id, false );
						break;

					case 'only_local':
						// id and all str or all str

						if ( isset( $thelangs['msgstrlangs'] ) ) {
							foreach ( $thelangs['msgstrlangs'] as $curlang => $msgtr ) {

								$res = get_post_meta( $one_id, $xili_dictionary->msgchild_meta, false );
								$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
								if ( isset( $thechilds['msgid']['plural'] ) ) {
									$msgstrs_arr = $xili_dictionary->get_cpt_msgstr( $one_id, $curlang, true );
									if ( $msgstrs_arr ) {
										foreach ( $msgstrs_arr as $msgstrs ) {
											if ( 'all-lang' == $selected_lang ) {
												wp_delete_post( $msgstrs->ID, false );
											}
										}
									}
								} else {

									$msgstrs = $xili_dictionary->get_cpt_msgstr( $one_id, $curlang, false ); // affiner plural

									// delete only msgstr of $oneline->ID

									if ( false != $msgstrs && 'all-lang' == $selected_lang ) {
										wp_delete_post( $msgstrs->ID, false );
									}
								}
							}
						}
						// clean msgid
						// msgid_post_links_delete
						// delete msgid - if $selected_lang == ""
						if ( '' == $selected_lang ) {
							wp_delete_post( $one_id, false );
						}
						break;

					case 'str_one_lang':
						$res = get_post_meta( $one_id, $xili_dictionary->msgchild_meta, false );
						$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
						if ( isset( $thechilds['msgid']['plural'] ) ) {
							$msgstrs_arr = $xili_dictionary->get_cpt_msgstr( $one_id, $selected_lang, true );
							if ( $msgstrs_arr ) {
								foreach ( $msgstrs_arr as $msgstrs ) {
									wp_delete_post( $msgstrs->ID, false );
								}
							}
						} else {

							// delete msgstr of this lang of $oneline->ID
							$msgstrs = $xili_dictionary->get_cpt_msgstr( $one_id, $selected_lang, false );
							// clean msgid (inside delete_post)

							// delete str
							if ( $msgstrs ) {
								wp_delete_post( $msgstrs->ID, false );
							}
						}
						break;

					case 'all_str':
						wp_delete_post( $one_id, false ); // id is cleaned by delete filter

						break;
				}
			}
			return 'loop-full';
		} else {
			return 'nolist';
		}

	}

	public function caching_msgs_to_erase() {

		$list = $this->get_msgs_to_erase( 0 );

		if ( $list ) {
			$id_list = array();
			foreach ( $list as $one ) {
				$id_list[] = $one->ID;
			}
			update_option( '_xd_cache_temp_array_IDs', $id_list );
			return count( $list );
		} else {
			return 0;
		}
	}

	private static function get_msgs_to_erase( $start ) {
		global $xili_dictionary;
		// sub-selection according msg origin (theme)
		$origins = array();
		$listterms = get_terms( 'origin', array( 'hide_empty' => false ) );
		foreach ( $listterms as $onetheme ) {
			if ( isset( $_POST[ 'theme-' . $onetheme->term_id ] ) ) {
				$origins[] = $onetheme->slug;
			}
		}

		$deletion_type = '';
		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;

		$only_local = ( isset( $_POST['_xd_local'] ) ) ? true : false;

		$selected_lang = ( isset( $_POST['_xd_lang'] ) ) ? $_POST['_xd_lang'] : '';

		// check origin checked

		if ( '' == $selected_lang && false == $only_local ) {
			$query = array(
				'numberposts' => -1,
				'offset' => 0,
				'category' => 0,
				'orderby' => 'ID',
				'order' => 'ASC',
				'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
			);
			$deletion_type = 'all';

		} elseif ( ( '' == $selected_lang || 'all-lang' == $selected_lang ) && true == $only_local ) {

			$query = array(
				'numberposts' => -1,
				'offset' => 0,
				'category' => 0,
				'orderby' => 'ID',
				'order' => 'ASC',
				'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => $xili_dictionary->msgtype_meta,
						'value' => 'msgid',
						'compare' => '=',
					),
					array(
						'key' => $xili_dictionary->msg_extracted_comments,
						'value' => $xili_dictionary->local_tag,
						'compare' => 'LIKE',
					),
				),
			);
			$deletion_type = 'only_local';

		} elseif ( 'all-lang' == $selected_lang && false == $only_local ) {

			$query = array(
				'numberposts' => -1,
				'offset' => 0,
				'category' => 0,
				'orderby' => 'ID',
				'order' => 'ASC',
				'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
					array(
						'key' => $xili_dictionary->msgtype_meta,
						'value' => array( 'msgstr', 'msgstr_0', 'msgstr_1' ),
						'compare' => 'IN',
					),
				),
			);

			$deletion_type = 'all_str';

		} elseif ( '' != $selected_lang && 'all-lang' != $selected_lang ) {

			// only msgstr
			$query = array(
				'numberposts' => -1,
				'offset' => 0,
				'category' => 0,
				'orderby' => 'ID',
				'order' => 'ASC',
				'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => $xili_dictionary->msgtype_meta,
						'value' => 'msgid',
						'compare' => '=',
					),
					array(
						'key' => $xili_dictionary->msglang_meta,
						'value' => $selected_lang,
						'compare' => 'LIKE',
					),
				),
			);

			if ( $only_local ) {

				$query['meta_query'][] = array(
					'key' => $xili_dictionary->msg_extracted_comments,
					'value' => $xili_dictionary->local_tag,
					'compare' => 'LIKE',
				);
			}
			$deletion_type = 'str_one_lang';
		}

		if ( array() != $origins ) {

			if ( 1 == count( $origins ) ) {

				$array_tax = array(
					'taxonomy' => 'origin',
					'field' => 'slug',
					'terms' => $origins[0],
				);

			} else {

				$array_tax = array(
					'taxonomy' => 'origin',
					'field' => 'slug',
					'terms' => $origins,
					'operator' => 'IN',
				);
			}
			$query['tax_query'] = array( $array_tax );

		}

		$listdictiolines = get_posts( $query );

		if ( $listdictiolines ) {
			update_option( '_xd_deletion_type', $deletion_type );
			return $listdictiolines;
		} else {
			return false;
		}
	}

}
