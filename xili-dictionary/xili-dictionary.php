<?php
/*
Plugin Name: xili-dictionary
Plugin URI: http://dev.xiligroup.com/xili-dictionary/
Description: A tool using WordPress CPT and taxonomy for localized themes or multilingual themes managed by xili-language - a powerful tool to create .mo file(s) on the fly in the theme's folder and more... - ONLY for >= WP 4.6.1 -
Author: dev.xiligroup - MS
Version: 2.14.03
Author URI: http://dev.xiligroup.com
License: GPLv2
Text Domain: xili-dictionary
Domain Path: /languages/
*/

# 2.14.0 - 190513 - WP Standards PHP Code Sniffer rewriting

# 2.13.0 - 170609 - xili-language-term integration

# 2.12.5 - 160729 - wp_get_theme integration
# 2.12.4 - 160216 - pot generation: now bbp addon integrated in xl (glotpress)

# 2.12.3 - 151021 - fixes menu when just activated, cleaning code lines
# 2.12.2 - 150926 - better compatibility w polylang before xl install - import polylang_mo custom posts, categories,... - improves msgid_exists
# 2.12.1 - 150704 - datatables js updated to 1.10.7 (for jQuery 1.11.3 WP 4.3)
# 2.12.0 - 150628 - fixes, better labels in Writers and Origins, able to import parent sources if child theme active, writers displayed in list, compatible with Polylang taxonomy
# 2.11.2 - 150527 - link title added, more terms from post-template, core import process improved
# 2.11.1 - 150514 - fixes import terms from comment-template.php
# 2.11.0 - 150422 - add way to create only POT w/o import msgid in dictionary
# 2.10.3 - 150322 - improves adding context after draft state only, import get_the_archive_title msgid (since WP 4.1) - new datatables JS & css
# 2.10.2 - 150312 - fixes no context msgid creation after creation with context
# 2.10.1 - 150228 - pre-test with WP4.2 alpha - manages plugin language files in WP_LANG_DIR

# 2.10.0 - 141218 - ready for WP4.1 - add comment terms (from wp-includes/comment-template.php) - better query for WP 4.1
# 2.9.2 - 141216 - fixes WPLANG (obsolete in WP4.0) and export local-xx_yy.po
# 2.9.1 - 140701 - fixes js for file state of local- files in multisite install. Better import from local- mo file
# 2.9.0 - 140628 - better message when creating - scan and import from source files (current theme, plugins) - fixe uppercase content (BINARY)
# 2.8.0 - 140605 - can now create pot file - improvement in export, UI and texts
# 2.7.2 - 140602 - disable media button, subtitle of msg, formatted msg title (id)
# 2.7.1 - 140526 - clean code - fixes issue in download - improve detection of existing msgid with context
# 2.7.0 - 140516 - able to save local-xx_XX (po or mo) in WP_LANG_DIR/themes
# 2.6.2 - 140414 - fixes issues (warning) when importing plural form on origin assiging
# 2.6.1 - 140308 - fixes issues with pot file name and verify no msgstr when importing pot file.
# 2.6.0 - 140303 - possible to manage plugin language files, more help, clean source
# 2.5.0 - 140228 - possible to download po / mo / pot files to computer
# 2.4.0 - 140201 - WP3.7 improvements, WP3.8 - fixes noun comment import, detect context when detecting duplicate - new icons
#
# 2.3.9 - 130822 - WP3.6 final - fixes Strict Standards message (__construct) - fixe js when importing pot
# 2.3.8 - 130512 - tests 3.6 - add parent theme origin when import/export if child theme - clean obsolete code lines
# 2.3.7 - 130508 - tests 3.6 - clean $wp_roles on deactivating, able to import/export from parent languages subfolder
# 2.3.6 - 130426 - improve import title for context of languages (no context for languages list), fixes origin in menu
# 2.3.5 - 130415 - import titles of xili_language_list - the_category
# 2.3.4 - 130223 - add infos and links in cat (removed from xl) - import from sources : detects esc_html and esc_attr functions (I10n.php) and more
# 2.3.3 - 130211 - fixes nonce, add editor size option - add import taxonomies in dictionary in bottom of edit list - sortby content (add postmeta)
# 2.3.2 - 130203 - add support, add capabilities for editor, add import from subfolder wp-content/languages/themes/ // Else, load textdomain from the Language directory (I10n.php #470
# 2.3.1 - 130127 - tests wp351 and XL 2.8.4, fixes and few improvements in UI
#
# 2.3.0 - 121118 - add ajax functions for import and erase functions
# 2.2.0 - 120922 - fixes issues with .mo and .po inserts - better messages and warning
# 2.1.3 - 120728 - fixes
# 2.1.2 - 120715 - list in msg edit - new query - new metabox - new pointers - ...
# 2.1.1 - 120710 - fixes - new icons
# 2.1.0 - 120507 - options to save on new local-xx_XX.mo and more... needs XL 2.6
# 2.0.0 - 120417 - repository as current
# beta 2.0.0-rc4 - 120415 - fixes
# beta 2.0.0-rc3 - 120406 - pre-tests WP3.4: fixes metaboxes columns, conditional edit link in list
# beta 2.0.0-rc2 - 120402 - latest fixes (writers)
# beta 2.0.0-rc1 - 120318 - before svn
# beta 2.0.0 - 120219 - new way of saving lines in CPT
#
# now msg lines full commented as in .po
# now translated lines (msgstr) attached to same taxonomy as xili-language
# before upgrading from 1.4.4 to 2.0, export all the dictionary in .po files and empty the dictionary.
#
# beta 1.4.4 - 111221 - fixes
# between 0.9.3 and 1.4.4 see version 1.4.4 - 20120219
# beta 0.9.3 - first published - 090131 MS


// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'XILIDICTIONARY_VER', '2.14.03' );
define( 'XILIDICTIONARY_DEBUG', false ); // WP_DEBUG must be also true !
define( 'XILIDICTIONARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// the class
/********************* the CLASS **********************/
require_once XILIDICTIONARY_PLUGIN_DIR . 'class-xili-dictionary.php';
require_once XILIDICTIONARY_PLUGIN_DIR . 'admin-includes/functions-xd-admin-help.php';
require_once XILIDICTIONARY_PLUGIN_DIR . 'admin-includes/functions-xd-various.php';
require_once XILIDICTIONARY_PLUGIN_DIR . 'admin-includes/class-xili-dictionary-dashboard.php';
require_once XILIDICTIONARY_PLUGIN_DIR . 'admin-includes/class-xili-dictionary-xml-pll.php';

/**
 * filter wp_upload_dir (/wp-includes/functions.php)
 *
 * @since 1.0.5
 */
function xili_upload_dir() {
	add_filter( 'upload_dir', 'xili_change_upload_subdir' );
	$uploads = wp_upload_dir();
	remove_filter( 'upload_dir', 'xili_change_upload_subdir' );
	return $uploads;
}

function xili_change_upload_subdir( $pre_uploads = array() ) {
	$pre_uploads['path'] = $pre_uploads['basedir'] . '/languages'; /* normally easy to create this subfolder */
	return $pre_uploads;
}

/**
 * instantiation when xili-language is loaded
 */
function xili_dictionary_start() {
	global $xili_dictionary; // for barmenu
	$xili_dictionary = new Xili_Dictionary();
	if ( is_admin() ) {
		$plugin_path = dirname( __FILE__ );
		require_once $plugin_path . '/includes/class-extractor.php';
		require_once ABSPATH . WPINC . '/pomo/po.php'; /* not included in wp-settings - here 2.12.3 */
		$xili_dictionary_dashboard = new Xili_Dictionary_Dashboard( $xili_dictionary );
		$xili_dictionary_xml_pll = new Xili_Dictionary_Xml_Pll( $xili_dictionary );
	}
}
add_action( 'plugins_loaded', 'xili_dictionary_start', 20 ); // 20 = after xili-language and xili-dictionary

/**
 * @since 2.8.1 - XILIDICTIONARY_DEBUG on top
 */
function xili_xd_error_log( $content = '' ) {

	if ( defined( 'XILIDICTIONARY_DEBUG' ) && true == XILIDICTIONARY_DEBUG && defined( 'WP_DEBUG' ) && true == WP_DEBUG && '' != $content ) {
		error_log( 'XD' . $content );
	}
}
/* © xiligroup dev */
