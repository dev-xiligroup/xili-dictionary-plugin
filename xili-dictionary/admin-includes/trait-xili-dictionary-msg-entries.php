<?php


/**
* XD Admin msg and entries (po mo)
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Msg_Entries {

	/**
	 * return count of msgid (local or theme domain)
	 * @since
	 */
	public function count_msgids( $curlang, $local = true, $theme_domain = '' ) {

		if ( $local ) {
			// msg id with lang
			$the_query = array(
				'post_type' => XDMSG,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => $this->msgtype_meta,
						'value' => 'msgid',
						'compare' => '=',
					),
					array(
						'key' => $this->msglang_meta,
						'value' => $curlang,
						'compare' => 'LIKE', // 2.1.2
					),
					array(
						'key' => $this->msg_extracted_comments,
						'value' => $this->local_tag,
						'compare' => 'LIKE',
					),
				),
			);

		} elseif ( '' == $theme_domain ) {
			$the_query = array(
				'post_type' => XDMSG,
				'tax_query' => array(
					array(
						'taxonomy' => TAXONAME,
						'field' => 'name',
						'terms' => $curlang,
					),
				),
			);

		} else {

			$the_query = array(
				'post_type' => XDMSG,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => $this->msgtype_meta,
						'value' => 'msgid',
						'compare' => '=',
					),
					array(
						'key' => $this->msglang_meta,
						'value' => $curlang,
						'compare' => 'LIKE', // 2.1.2
					),
				),
				'tax_query' => array(
					array(
						'taxonomy' => 'origin',
						'field' => 'slug',
						'terms' => array( $theme_domain ),
						'operator' => 'IN',
					),
				),
			);
		}

		$query_4_test = new WP_Query( $the_query );
		return $query_4_test->found_posts;

	}

	public function get_msg_in_entries( $msg, $type, $entries, $context ) {
		foreach ( $entries as $entry ) {
			$diff = 1;
			switch ( $type ) {
				case 'msgid':
					$diff = strcmp( $msg, $entry->singular );
					if ( '' != $context ) {
						if ( null != $entry->context ) {
							$diff += strcmp( $context, $entry->context );
						}
					}
					break;
				case 'msgid_plural':
					$diff = strcmp( $msg, $entry->plural );
					break;
				case 'msgstr':
					if ( isset( $entry->translations[0] ) ) {
						$diff = strcmp( $msg, $entry->translations[0] );
					}
					break;
				default:
					if ( false !== strpos( $type, 'msgstr_' ) ) {
						$indice = (int) substr( $type, -1 );
						if ( isset( $entry->translations[ $indice ] ) ) {
							$diff = strcmp( $msg, $entry->translations[ $indice ] );
						}
					}
			}

			//if ( $diff != 0) { echo $msg.' i= '.strlen( $msg); echo $entry->singular.' ) e= '.strlen( $entry->singular); }
			if ( 0 == $diff ) {
				if ( isset( $entry->translations[0] ) ) {
					return array(
						'msgid' => $entry->singular,
						'msgstr' => $entry->translations[0],
					);
				} else {
					return array();
				}
			}
		}
		return array();
	}

	/**
	 * return array of msgid objects
	 * @since 2.0
	 *
	 * @updated 2.1.2
	 */
	public function get_cpt_msgids( $curlang, $pomo = 'mo', $extract_array = array(), $not = false ) {
		global $wpdb, $wp_version;
		$like = ( true === $not ) ? 'NOT LIKE' : 'LIKE';
		if ( 'mo' == $pomo ) {

			if ( array() == $extract_array ) {

				return get_posts(
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
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key' => $this->msgtype_meta,
								'value' => 'msgid',
								'compare' => '=',
							),
							array(
								'key' => $this->msglang_meta,
								'value' => $curlang,
								'compare' => $like, // 2.1.2
							),
						),
					)
				);

			} elseif ( isset( $extract_array [ $this->msg_extracted_comments ] ) && isset( $extract_array [  'like-' . $this->msg_extracted_comments ] ) && true === $extract_array[ 'like-' . $this->msg_extracted_comments ] ) {
				$extract = $extract_array[ $this->msg_extracted_comments ];

					return get_posts(
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
							'meta_query' => array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '=',
								),
								array(
									'key' => $this->msglang_meta,
									'value' => $curlang,
									'compare' => 'LIKE',
								),
								array(
									'key' => $this->msg_extracted_comments,
									'value' => $extract,
									'compare' => 'LIKE',
								),
							),
						)
					);

			} elseif ( isset( $extract_array['origin'] ) ) {

				if ( ! is_array( $extract_array['origin'] ) ) {

					$array_tax = array(
						'taxonomy' => 'origin',
						'field' => 'slug',
						'terms' => $extract_array ['origin'],
					);

				} else {

					$array_tax = array(
						'taxonomy' => 'origin',
						'field' => 'slug',
						'terms' => $extract_array ['origin'],
						'operator' => 'IN',
					);
				}

				if ( isset( $extract_array[  'like-' . $this->msg_extracted_comments ] ) && false === $extract_array[ 'like-' . $this->msg_extracted_comments ] ) {

					if ( version_compare( $wp_version, '4.1-RC', '>=' ) ) {
						$meta_query = array(
							'relation' => 'OR', // better OR
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '=',
								),
								array(
									'key' => $this->msglang_meta,
									'value' => $curlang,
									'compare' => 'LIKE',
								),
								array(
									'key' => $this->msg_extracted_comments,
									'compare' => 'NOT EXISTS',
								),
							),
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '=',
								),
								array(
									'key' => $this->msglang_meta,
									'value' => $curlang,
									'compare' => 'LIKE',
								),
								array(
									'key' => $this->msg_extracted_comments,
									'value' => $extract_array [ $this->msg_extracted_comments ],
									'compare' => 'NOT LIKE',
								),
							),
						);

					} else {
						$meta_query = array(
							'relation' => 'AND',
							array(
								'key' => $this->msgtype_meta,
								'value' => 'msgid',
								'compare' => '=',
							),
							array(
								'key' => $this->msglang_meta,
								'value' => $curlang,
								'compare' => 'LIKE',
							),
							array(
								'key' => $this->msg_extracted_comments,
								'value' => $extract_array [ $this->msg_extracted_comments ],
								'compare' => 'NOT LIKE',
							),
						);
					}
				} else {
					$meta_query = array(
						'relation' => 'AND',
						array(
							'key' => $this->msgtype_meta,
							'value' => 'msgid',
							'compare' => '=',
						),
						array(
							'key' => $this->msglang_meta,
							'value' => $curlang,
							'compare' => 'LIKE',
						),
					);

				}

				if ( '' == $extract_array ['origin'] || array() == $extract_array ['origin'] ) {

					return get_posts(
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
							'meta_query' => $meta_query,
						)
					);

				} else {

					return get_posts(
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
							'meta_query' => $meta_query,
							'tax_query' => array(
								$array_tax,
							),
						)
					);
				}
			}
		} else {
			// po
			if ( array() == $extract_array ) {
				// to have also empty translation
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgid';
				return get_posts(
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
						'meta_query' => array(
							array(
								'meta_key' => $meta_key_val,
								'meta_value' => $meta_value_val,
							),
						),
					)
				);

			} elseif ( isset( $extract_array['origin'] ) ) {

				if ( ! is_array( $extract_array['origin'] ) ) {

					$array_tax = array(
						'taxonomy' => 'origin',
						'field' => 'slug',
						'terms' => $extract_array['origin'],
					);

				} else {

					$array_tax = array(
						'taxonomy' => 'origin',
						'field' => 'slug',
						'terms' => $extract_array['origin'],
						'operator' => 'IN',
					);
				}

				if ( isset( $extract_array [  'like-' . $this->msg_extracted_comments ] ) && false === $extract_array [ 'like-' . $this->msg_extracted_comments ] ) {

					if ( version_compare( $wp_version, '4.1-RC', '>=' ) ) {

						$meta_query = array(
							'relation' => 'OR', // better OR
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '=',
								),
								array(
									'key' => $this->msg_extracted_comments,
									'value' => $extract_array[ $this->msg_extracted_comments ],
									'compare' => 'NOT LIKE',
								),
							),
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '=',
								),
								array(
									'key' => $this->msg_extracted_comments,
									'compare' => 'NOT EXISTS',
								),
							),
						);

					} else {
						$meta_query = array(
							'relation' => 'AND',
							array(
								'key' => $this->msgtype_meta,
								'value' => 'msgid',
								'compare' => '=',
							),
							array(
								'key' => $this->msg_extracted_comments,
								'value' => $extract_array[ $this->msg_extracted_comments ],
								'compare' => 'NOT LIKE',
							),
						);
					}
				} else {
					$meta_query = array(
						array(
							'key' => $this->msgtype_meta,
							'value' => 'msgid',
							'compare' => '=',
						),
					);
				}

				return get_posts(
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
						'meta_query' => $meta_query,
						'tax_query' => array(
							$array_tax,
						),
					)
				);

			} else {

				$extract = $extract_array[ $this->msg_extracted_comments ];

				$like_or_not = ( true === $extract_array[ 'like-' . $this->msg_extracted_comments ] ) ? 'LIKE' : 'NOT LIKE';

				return get_posts(
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
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key' => $this->msgtype_meta,
								'value' => 'msgid',
								'compare' => '=',
							),
							array(
								'key' => $this->msg_extracted_comments,
								'value' => $extract,
								'compare' => $like_or_not,
							),
						),
					)
				);

			}
		}
	}


	/**
	 * return msgstr object (array translation)
	 * @since 2.0
	 */
	public function get_cpt_msgstr( $cur_msgid_id, $curlang, $plural = false ) {
		$res = get_post_meta( $cur_msgid_id, $this->msglang_meta, false );
		$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();

		if ( $plural ) {
			if ( isset( $thelangs['msgstrlangs'][ $curlang ]['msgstr_0'] ) ) {
				$cur_msgstr_id = $thelangs['msgstrlangs'][ $curlang ]['msgstr_0'];
				// get_parent (msgstr_0)
				$msgstr_array = array( get_post( $cur_msgstr_id ) );
				// get_children
				$args = array(
					'numberposts' => -1,
					'post_type' => XDMSG,
					'post_status' => 'publish',
					'post_parent' => $cur_msgstr_id,
				);
				$children = get_posts( $args );
				return array_merge( $msgstr_array, $children );
			} else {
				return false;
			}
		} else {
			if ( isset( $thelangs['msgstrlangs'][ $curlang ]['msgstr'] ) ) {
				$cur_msgstr_id = $thelangs['msgstrlangs'][ $curlang ]['msgstr'];
				// get_content
				return get_post( $cur_msgstr_id );
			} else {
				return false;
			}
		}
	}


	/**
	 * taxonomy's terms in array(name - description)
	 * by default taxonomy
	 *
	 */
	public function xili_read_catsterms_cpt( $taxonomy = 'category', $local_tag = '[local]' ) {
		$this->importing_mode = true;
		$msg_counters = array( 0, 0, array(), array() ); // q of term, description, list ids checked, imported
		$listcategories = get_terms( $taxonomy, array( 'hide_empty' => false ) );
		foreach ( $listcategories as $category ) {

			$result = $this->msgid_exists( $category->name );

			$t_entry = array();
			/* translators: */
			$t_entry['extracted_comments'] = sprintf( $local_tag . ' name from %1$s with slug %2$s', $taxonomy, $category->slug );
			$entry = (object) $t_entry;

			if ( false === $result ) {
				// create the msgid
				$msgid_post_id = $this->insert_one_cpt_and_meta( $category->name, null, 'msgid', 0, $entry );
				$msg_counters[0]++;
				if ( $msgid_post_id ) {
					$msg_counters[3][] = $msgid_post_id;
				}
			} else {
				$msgid_post_id = $result[0];
				// add comment in existing ?
			}

			if ( $msgid_post_id ) {
				$msg_counters[2][] = $msgid_post_id;
			}

			$result = $this->msgid_exists( $category->description );

			$t_entry = array();
			/* translators: */
			$t_entry['extracted_comments'] = sprintf( $this->local_tag . ' desc from %1$s with slug %2$s', $taxonomy, $category->slug );
			$entry = (object) $t_entry;

			if ( false === $result ) {
				// create the msgid
				$msgid_post_id = $this->insert_one_cpt_and_meta( $category->description, null, 'msgid', 0, $entry );
				$msg_counters[1]++;
				if ( $msgid_post_id ) {
					$msg_counters[3][] = $msgid_post_id;
				}
			} else {
				$msgid_post_id = $result[0];
				// add comment in existing ?
			}

			if ( $msgid_post_id ) {
				$msg_counters[2][] = $msgid_post_id;
			}
		}

		$this->importing_mode = false;
		return $msg_counters;
	}

	/**
	 * Insert entries previously extracted from files
	 *
	 * @since 2.8.1
	 *
	 */
	public function from_entries_to_xdmsg( $originals, $origin_theme = 'twentyfourteen-xili', $backup_pot = array(), $curlang = '' ) {

		$temp_po = new PO();
		$temp_po->entries = $originals->entries;
		$lines = 0;
		$this->importing_mode = true; // not manual
		foreach ( $temp_po->entries as $pomsgid => $pomsgstr ) {

			$this->pomo_entry_to_xdmsg(
				$pomsgid,
				$pomsgstr,
				$curlang,
				array(
					'importing_po_comments' => 'replace',
					'origin_theme' => $origin_theme,
				)
			);
			$lines++;
		}
		$this->importing_mode = false;
		if ( $backup_pot ) {

			$temp_po->set_header( 'Project-Id-Version', $backup_pot['title'] );
			$temp_po->set_header( 'Report-Msgid-Bugs-To', 'http://dev.xiligroup.com/' );
			$temp_po->set_header( 'POT-Creation-Date', gmdate( 'Y-m-d H:i:s+00:00' ) );
			$temp_po->set_header( 'MIME-Version', '1.0' );
			$temp_po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
			$temp_po->set_header( 'Content-Transfer-Encoding', '8bit' );
			$temp_po->set_header( 'PO-Revision-Date', gmdate( 'Y' ) . '-MO-DA HO:MI+ZONE' );
			$temp_po->set_header( 'Last-Translator', 'FULL NAME <EMAIL@ADDRESS>' );
			$temp_po->set_header( 'Language-Team', 'LANGUAGE <EMAIL@ADDRESS>' );

			// Write POT file
			$result = $temp_po->export_to_file( $backup_pot['file'] );

		}
		unset( $temp_po );
		return $lines;
	}

}
