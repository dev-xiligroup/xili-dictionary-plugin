<?php
/**
* XD Admin styles
*
* @package Xili-Dictionary
* @subpackage admin
* @since 2.14
*/

trait Xili_Dictionary_Admin_Styles {

	/**
	 * add styles in edit msg screen
	 *
	 */
	public function print_styles_xdmsg_edit() {
		global $post;
		if ( XDMSG == get_post_type( $post->ID ) ) {
			echo '<!---- xd css ----->' . "\n";
			echo '<style type="text/css" media="screen">' . "\n";

			echo '#msg-states { width:69%; float:left; border:0px solid red; padding-bottom: 10px;}' . "\n";
			echo '#msg-states-comments { width:27%; margin-left: 70%; border-left:0px #666 solid; padding:10px 10px 0; }' . "\n";
			echo '#msg-states-actions { background:#ffffff; clear:left; padding: 8px 5px; margin-top:5px; }' . "\n";
			echo '.xdversion { font-size:80%; text-align:right; }' . "\n";
			echo '.msg-states-actions-left { float:left; padding: 8px 0px; overflow:hidden; width:50% }' . "\n";
			echo '.msg-states-actions-right { float:left; padding: 8px 0px; width:50% }' . "\n";
			echo '.alert { color:red;}' . "\n";
			echo '.editing { color:blue; background:yellow;}' . "\n";
			echo '.msgidstyle { line-height:200% !important; font-size:105%; padding:4px 10px 6px;}' . "\n";
			echo '.msgstrstyle { line-height:180% !important; font-size:105%; }' . "\n";
			echo '.msg-saved { background:#ffffff !important; border:1px dotted #999; padding:5px; margin-bottom:5px;}' . "\n";
			echo '.column-msgtrans {width: 20%;}' . "\n";
			echo 'mark { background-color:#e0e0e0;}' . "\n";
			// buttons
			echo '.action-button {text-decoration:none; text-align:center; display:block; width:70%; margin:0px 1px 1px 30px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;}' . "\n";
			echo '.blue-button {border:1px #33f solid;}' . "\n";
			echo '.grey-button {border:1px #ccc solid;}' . "\n";

			echo '</style>' . "\n";

			$this->insert_flags_style_css();
		}
	}

	/**
	 * add style for flags (coming from xili-language if active)
	 * @since 2.10.3
	 *
	 */
	public function insert_flags_style_css() {
		global $xili_language, $xili_language_admin;
		if ( 'isactive' == $this->xililanguage && 'on' == $this->xili_settings['external_xd_style'] ) {
			echo '<!---- xd css flags ----->' . "\n";
			echo '<style type="text/css" media="screen">' . "\n";
			echo 'div#msg-states tr[class|="lang"] th>span {display:inline-block; }' . "\n";
			echo 'div#msg-states-comments a[class|="lang"] {display:inline-block; text-indent:-9999px; width:20px; }' . "\n";
			$listlanguages = $xili_language->get_listlanguages();
			$folder_url = $xili_language->plugin_url . '/xili-css/flags/';
			foreach ( $listlanguages as $language ) {
				$ok = false;
				$flag_id = $xili_language->get_flag_series( $language->slug, 'admin' );
				if ( 0 != $flag_id ) {
					$flag_uri = wp_get_attachment_url( $flag_id );
					$ok = true;
				} else {
					// test the default in xili-language here
					$ok = file_exists( $xili_language_admin->style_flag_folder_path . $language->slug . '.png' );
					$flag_uri = $folder_url . $language->slug . '.png';
					if ( ! $ok ) {
						// test the default in plugin here
						$ok = file_exists( $this->plugin_path . '/xili-css/flags/' . $language->slug . '.png' );
						$flag_uri = $this->plugin_url . '/xili-css/flags/' . $language->slug . '.png';
					}
				}
				if ( $ok ) {
					echo 'tr.lang-' . $language->slug . ' th, div#msg-states-comments a.lang-' . $language->slug . ' { background: transparent url( ' . $flag_uri . ' ) no-repeat 60% center; }' . "\n";
				}
			}
			echo '</style>' . "\n";
		} else {
			if ( $this->exists_style_ext && 'on' == $this->xili_settings['external_xd_style'] ) {
				wp_enqueue_style( 'xili_dictionary_stylesheet' );
			}
		}

	}

	/**
	 * add styles in edit tags screen
	 *
	 */
	public function print_styles_edit_tags() {

		echo '<!---- xd css tags ----->' . "\n";
		echo '<style type="text/css" media="screen">' . "\n";
		echo '.displaybbt { margin: 10px 0 0 200px !important }' . "\n";
		echo '.displaybbt a:link { text-decoration:none; }' . "\n";
		echo '.taxinmos { border:1px solid #999; width:500px; padding: 10px 20px }' . "\n";
		echo '.taxinmoslist { border-bottom:1px solid #eee; padding: 5px 0; margin-bottom: 5px; }' . "\n"; // in xl
		echo '</style>' . "\n";
		$this->insert_flags_style_css();
	}

	/**
	 * add styles in list of msgs screen icon32-posts-xdmsg
	 *
	 */
	public function print_styles_xdmsg_list() {

		if ( isset( $_GET['post_type'] ) && XDMSG == $_GET['post_type'] ) {

			echo '<!---- xd css ----->' . "\n";
			echo '<style type="text/css" media="screen">' . "\n";

			echo 'mark { background-color:#e0e0e0;}' . "\n";
			echo '.alert { color:red;}' . "\n";
			echo '.column-language, .column-msgposttype { width: 80px; }' . "\n";
			echo '.column-date { width: 80px !important; }' . "\n";
			echo '.column-msgcontent { width: auto; }' . "\n";
			echo '.column-msgpostmeta { width: 150px; }' . "\n";
			echo '.column-msgposttype { width: 150px; }' . "\n";
			echo '.column-author { width: 80px !important; }' . "\n";
			echo '.column-title { width: 160px !important; }' . "\n";
			echo '.column-date { width: 100px !important; }' . "\n";

			echo '#icon-edit.icon32-posts-xdmsg { background:transparent url( ' . XILIDICTIONARY_PLUGIN_URL . '/images/XD-full-logo-32.png ) no-repeat !important; }' . "\n";
			echo '</style>' . "\n";
			$this->insert_flags_style_css();
		}
	}

	/**
	 * add styles in tool screen
	 *
	 */
	public function print_styles_xdmsg_tool() {
		echo '<!---- xd css ----->' . "\n";
		echo '<style type="text/css" media="screen">' . "\n";

		echo '.metabox-content { background:transparent;}' . "\n";
		echo '.dialoglang { float:left; width:18%; border:0px solid grey; margin: 5px; }' . "\n";
		echo '.dialogfile { float:left; width:42%; min-height:80px; border-left:1px solid #ddd; padding: 10px 5px 10px 5px; } ' . "\n";
		echo '.dialogorigin { float:left; width:32%; border-left:1px solid #ddd; padding: 10px 5px 10px 10px; min-height:180px} ' . "\n";
		echo '#xd-file-state p {font-size:10px; margin-left:20%;}' . "\n";
		echo '#xd-file-exists {font-size:11px; margin-left: 20%; padding-top:5px;}' . "\n";
		echo '.dialogbbt {clear:left; text-align:right; padding:12px 2px 5px; }' . "\n";
		echo 'table.checktheme { width:95%; margin-left:10px;}' . "\n";
		echo 'table.checktheme>tr>td { width:45% }' . "\n";

		echo 'mark { background-color:#e0e0e0;}' . "\n";
		echo '.dialogcontainer { margin-top:14px !important; padding:12px 10px; overflow:hidden; background:#f0f0f0;}' . "\n";

		// buttons
		echo '.action-button {text-decoration:none; text-align:center; display:block; width:70%; margin:0px 1px 1px 30px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;}' . "\n";
		echo '.small-action-button {text-decoration:none; text-align:center; display:inline-block; width:16%; margin:0px 1px 1px 10px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; border:1px #ccc solid;}' . "\n";
		echo '.blue-button {border:1px #33f solid;}' . "\n";
		echo '.grey-button {border:1px #ccc solid;}' . "\n";

		echo '</style>' . "\n";
		// $this->insert_flags_style_css();
	}

	/**
	 * add styles in new screens (import / erase)
	 *
	 */
	public function print_styles_new_ui() {

		echo '<!---- xd css ----->' . "\n";
		echo '<style type="text/css" media="screen">' . "\n";
		echo 'form#xd-looping-settings > p { width:600px; padding:10px 20px 10px 0; font-size:110%; }' . "\n";
		echo '.sub-field {border:1px #ccc solid; width:600px; padding:10px 20px; margin:5px 0;}' . "\n";
		echo '.link-back > a { border:1px #ccc solid; width:80px; padding:4px 10px; border-radius: 3px; margin-right:4px;}' . "\n";
		echo '.xd-looping-updated{ width:870px !important; }' . "\n";
		echo '#xd-file-state p {font-size:10px;}' . "\n";
		echo '</style>' . "\n";

	}

	/**
	 * style for new dashboard
	 * @since 2.1
	 *
	 */
	public function ext_style_init() {
				// test successively style file in theme, plugins, current plugin subfolder
		if ( file_exists( get_stylesheet_directory() . '/xili-css/xd-style.css' ) ) { // in child theme
				$this->exists_style_ext = true;
				$this->style_folder = get_stylesheet_directory_uri();
				$this->style_flag_folder_path = get_stylesheet_directory() . '/images/flags/';
				$this->style_message = esc_html__( 'xd-style.css is in sub-folder xili-css of current theme folder', 'xili-dictionary' );
		} elseif ( file_exists( WP_PLUGIN_DIR . $this->xilidev_folder . '/xili-css/xd-style.css' ) ) { // in plugin xilidev-libraries
				$this->exists_style_ext = true;
				$this->style_folder = plugins_url() . $this->xilidev_folder;
				$this->style_flag_folder_path = WP_PLUGIN_DIR . $this->xilidev_folder . '/xili-css/flags/';
				/* translators: */
				$this->style_message = sprintf( esc_html__( 'xd-style.css is in sub-folder xili-css of %s folder', 'xili-dictionary' ), $this->style_folder );
		} elseif ( file_exists( $this->plugin_path . '/xili-css/xd-style.css' ) ) { // in current plugin
				$this->exists_style_ext = true;
				$this->style_folder = $this->plugin_url;
				$this->style_flag_folder_path = $this->plugin_path . '/xili-css/flags/';
				$this->style_message = esc_html__( 'xd-style.css is in sub-folder xili-css of xili-dictionary plugin folder (example)', 'xili-dictionary' );
		} else {
				$this->style_message = esc_html__( 'no xd-style.css', 'xili-dictionary' );
		}
		if ( $this->exists_style_ext ) {
			wp_register_style( 'xili_dictionary_stylesheet', $this->style_folder . '/xili-css/xd-style.css', XILIDICTIONARY_VER );
		}
	}

}
