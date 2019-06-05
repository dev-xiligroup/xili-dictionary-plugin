<?php

/**
* XD Admin MSG List (edit.php)
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Msg_List {

	/**
	 * Add Origin selector in edit.php edit
	 *
	 * @since 2.0
	 *
	 */
	public function restrict_manage_origin_posts() {
		if ( isset( $_GET['post_type'] ) && XDMSG == $_GET['post_type'] ) {
			$listorigins = get_terms( 'origin', array( 'hide_empty' => false ) );
			if ( array() != $listorigins ) {
				$selected = '';
				if ( isset( $_GET['origin'] ) ) {
					$selected = $_GET['origin'];
				}
				$dropdown_options = array(
					'taxonomy' => 'origin',
					'show_option_all' => esc_html__( 'View all origins', 'xili-dictionary' ),
					'hide_empty' => 0,
					'hierarchical' => 0,
					'show_count' => 0,
					'orderby' => 'name',
					'name' => 'origin',
					'selected' => $selected,
				);
				wp_dropdown_categories( $dropdown_options );
			}
		}
	}


	/**
	 * new columns in cpt list
	 *
	 */
	public function xili_manage_column_name( $columns ) {
		// must be verified
		global $post_type; // from admin.php+edit.php
		if ( XDMSG == $post_type ) {
			$ends = array( 'author', 'date', 'rel', 'visible' );
			$end = array();
			foreach ( $columns as $k => $v ) {
				if ( in_array( $k, $ends ) ) {
					$end[ $k ] = $v;
					unset( $columns[ $k ] );
				}
			}
			$columns['msgcontent'] = esc_html__( 'Content', 'xili-dictionary' ); // ? sortable ?
			$columns['msgpostmeta'] = esc_html__( 'msg / relations', 'xili-dictionary' );
			$columns['msgposttype'] = esc_html__( 'Type / Origin', 'xili-dictionary' );
			if ( ! class_exists( 'xili_language' ) ) {
				$columns[ TAXONAME ] = esc_html__( 'Language', 'xili-dictionary' );
			}
			$columns = array_merge( $columns, $end );
		}
		return $columns;

	}

	public function xili_manage_column_row( $column, $id ) {
		global $post;
		$type = get_post_meta( $id, $this->msgtype_meta, true );

		if ( 'msgcontent' == $column && XDMSG == $post->post_type ) {
			echo htmlspecialchars( $post->post_content );
			$writers = wp_get_post_terms( $id, 'writer', array( 'fields' => 'names' ) );
			if ( 'msgid' != $type ) {
				if ( is_wp_error( $writers ) || ! $writers ) {
					echo '<br /><br /><small>( ' . esc_html__( 'No writer found', 'xili-dictionary' ) . ' )</small>';
				} else {
					$title_w = ( 1 == count( $writers ) ) ? esc_html__( 'Writer', 'xili-dictionary' ) : esc_html__( 'Writers', 'xili-dictionary' );
					/* translators: */
					echo '<br /><br />( <small>' . $title_w . sprintf( esc_html__( ': %s', 'xili-dictionary' ), implode( $writers, ', ' ) ) . '</small> )';
				}
			}
		}
		if ( 'msgpostmeta' == $column && XDMSG == $post->post_type ) {

			$this->msg_link_display( $id );
		}
		if ( 'msgposttype' == $column && XDMSG == $post->post_type ) {

			if ( 'msgid' == $type ) {
				$msgid_id = $id;
			} else {
				$msgid_id = get_post_meta( $id, $this->msgidlang_meta, true );
			}
			$extracted_comments = get_post_meta( $msgid_id, $this->msg_extracted_comments, true );
			if ( ( '' != $extracted_comments && false !== strpos( $extracted_comments, $this->local_tag . ' ' ) ) ) {
				esc_html_e( 'Local', 'xili-dictionary' );
			}

			//
			$origins = get_the_terms( $id, 'origin' );
			$names = array();
			if ( $origins ) {
				echo '<br />';
				foreach ( $origins as $origin ) {
					$names[] = $this->get_plugin_name( $origin->name, esc_html__( 'Plugin:', 'xili-dictionary' ) ); // if not: no prefix 2.6.0
				}
				/* translators: */
				printf( __( '<small>From:</small> %s', 'xili-dictionary' ), implode( ', ', $names ) );
			}
		}

		if ( 'language' == $column && XDMSG == $post->post_type ) {
			if ( ! class_exists( 'xili_language' ) ) {

				$lang = $this->cur_lang( $id );
				if ( isset( $lang->name ) ) {
					echo $lang->name;
				}
			}
		}

		return;

	}

	// Register the column as sortable
	public function msgcontent_column_register_sortable( $columns ) {
		$columns['msgcontent'] = 'msgcontent';
		$columns['msgpostmeta'] = 'msgpostmeta';
		return $columns;
	}

	public function msgcontent_column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'msgpostmeta' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => $this->msgtype_meta,
					'orderby' => 'meta_value',
				)
			);
		}

		return $vars;
	}

	/**
	 * Add Languages selector in edit.php edit after Category Selector (hook: restrict_manage_posts) only if no XL
	 *
	 * @since 2.0
	 * @updated 2.3.4 - only xdmsg if xl is not
	 */
	public function restrict_manage_languages_posts() {
		global $post_type; // from admin.php+edit.php
		if ( XDMSG == $post_type ) {
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
			?>
	<select name="lang" id="lang" class='postform'>
		<option value=""> <?php esc_html_e( 'View all languages', 'xili-dictionary' ); ?> </option>

		<?php
		foreach ( $listlanguages as $language ) {
			$selected = ( isset( $_GET[ QUETAG ] ) && $language->slug == $_GET[ QUETAG ] ) ? 'selected=selected' : '';
			echo '<option value="' . $language->slug . '" ' . $selected . ' >' . __( $language->description, 'xili-dictionary' ) . '</option>';
		}
		?>
	</select>
		<?php
		}
	}


}
