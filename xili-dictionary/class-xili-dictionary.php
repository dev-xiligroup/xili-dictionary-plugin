<?php

/**
 * Main Admin Class
 *
 * @package Xili-Dictionary
 * @subpackage core
 * @since 2.14
 */
class Xili_Dictionary {


	use Xili_Dictionary_Dashboard;
	use Xili_Dictionary_Settings;
	use Xili_Dictionary_Various;
	use Xili_Dictionary_Imports;
	use Xili_Dictionary_Download;
	use Xili_Dictionary_Erase;
	use Xili_Dictionary_Javascripts;
	use Xili_Dictionary_Msg_Metabox;
	use Xili_Dictionary_Msg_Entries;
	use Xili_Dictionary_Msg_Import;
	use Xili_Dictionary_Msg_List;

	use Xili_Dictionary_Admin_Styles;
	use Xili_Dictionary_Import_Test_Pomo;
	use Xili_Dictionary_Export_Pomo;

	use Xili_Dictionary_Help;

	use Xili_Dictionary_Xml_Pll;

	public $plugin_url = ''; // Url to this plugin - see construct
	public $plugin_path = ''; // The path to this plugin - see construct

	public $subselect = ''; /* used to subselect by msgid or languages*/
	public $searchtranslated = ''; /* used to search untranslated - 2.1.2 */
	public $languages_key_slug = array(); // used for slug to other items
	public $languages_names = array(); // 2.6.1
	public $msg_action_message = '';
	public $xililanguage = ''; /* neveractive isactive wasactive */
	public $xililanguage_ms = false; // xlms
	public $tempoutput = '';

	public $langfolder = ''; /* where po or mo files */
	public $parentlangfolder = ''; /* where po or mo files of parent */

	public $xili_settings; /* saved in options */
	public $ossep = '/'; /* for recursive file search in xamp */

	// 2.0 new vars
	public $xdmsg = 'xdmsg';
	public $xd_settings_page = 'edit.php?post_type=xdmsg&amp;page=dictionary_page'; // now in CPT menu

	// post meta
	public $ctxt_meta = '_xdmsg_ctxt'; // to change to xdctxt
	public $msgtype_meta = '_xdmsg_msgtype'; // to hidden
	public $msgchild_meta = '_xdmsg_msgchild';
	public $msglang_meta = '_xdmsg_msglangs';
	public $msgidlang_meta = '_xdmsg_msgid_id'; // origin of the msgstr
	public $msg_extracted_comments = '_xdmsg_extracted_comments';
	public $msg_translator_comments = '_xdmsg_translator_comments';
	public $msg_flags = '_xdmsg_flags';
	public $msg_sort_slug = '_xdmsg_sort_slug'; // 2.3.3 for content sort

	public $origin_theme = ''; // used when importing
	public $local_tag = '[local]';
	public $exists_style_ext = false; // wp_enqueue_style( 'xili_dictionary_stylesheet' );
	public $style_message = '';
	public $xilidev_folder = '/xilidev-libraries'; //must be in plugins

	public $theme_mos = array(); // $this->get_pomo_from_theme();
	public $plugin_mos = array(); // 2.6.1 - mo from plugin
	public $local_mos = array(); // $this->get_pomo_from_theme( true ); // 2.1
	//	is_multisite
	public $file_site_mos = array(); // $this->get_pomo_from_site(); // since 1.2.0 - mo of site
	public $file_site_local_mos = array(); // $this->get_pomo_from_site( true );

	public $default_langs_array = array(); // default languages
	public $internal_list = false; // created by xl // true by xd or pll

	public $importing_mode = false; // for new action by hand ( action save when new )
	public $msg_str_labels = array(
		'msgid' => 'msgid',
		'msgid_plural' => 'msgid_plural',
		'msgstr' => 'msgstr',
		'msgstr_0' => 'msgstr[0]',
		'msgstr_1' => 'msgstr[1]',
		'msgstr_2' => 'msgstr[2]',
		'msgstr_3' => 'msgstr[3]',
		'msgstr_4' => 'msgstr[4]',
	);
	public $importing_po_comments = ''; // mode replace or append 2.0-rc2
	public $create_line_lang = ''; // line between box

	public $langs_group_id; /* group ID and Term Taxo ID */
	public $langs_group_tt_id;

	// temp mo/po object
	public $temp_po;

	public $taxlist = array(); // list of current tax in edit-tags table
	public $tax_msgid_list = array(); // list of current tax visible in dictionary list

	public $active_theme_directory = ''; // current template (or stylesheet) directory

	public $wikilink = 'http://wiki.xiligroup.org';

	public $msg_settings = ''; // used by download process 2.5
	public $download_uri = ''; // URI used by download process 2.5
	public $plugin_domain_path = '';
	public $plugin_text_domain = '';

	public $news_id = 0; //for multi pointers
	public $news_case = array();

	public $import_message = ''; // core import process

	public $examples_list = array(); // try to use GP class (jetpack)
	public $multilanguage_plugin_active = ''; //2.12
	public $iso_to_term_id = array();

	public function __construct( $langsfolder = '/' ) {

		global $wp_version;
		/* activated when first activation of plug */
		// 2.0
		define( 'XDMSG', $this->xdmsg ); // CPT to change from msg to xdmsg (less generic) 20120217

		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		register_activation_hook( __FILE__, array( &$this, 'xili_dictionary_activation' ) );
		register_deactivation_hook( __FILE__, array( &$this, 'remove_capabilities' ) ); //2.3.7

		$this->ossep = strtoupper( 'WIN' == substr( PHP_OS, 0, 3 ) ) ? '\\' : '/'; /* for rare xamp servers*/

		/* get current settings - name of taxonomy - name of query-tag */
		$this->xililanguage_state();
		$this->xili_settings = get_option( 'xili_dictionary_settings' );
		if ( empty( $this->xili_settings ) || 'dictionary' != $this->xili_settings['taxonomy'] ) { // to fix
			$this->xili_dictionary_activation();
			$this->xili_settings = get_option( 'xili_dictionary_settings' );
		}

		/* test if version changed */
		$version = $this->xili_settings['version'];
		if ( $version <= '0.2' ) {
				/* update relationships for grouping existing dictionary terms */
			$this->update_terms_langs_grouping();
			$this->xili_settings['version'] = '1.0';
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
		if ( $version == '1.0' ) {
			$this->xili_settings['external_xd_style'] = 'off';
			$this->xili_settings['version'] = '2.0';
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
		if ( $version == '2.0' ) {
			$this->update_postmeta_msgid();
			$this->xili_settings['version'] = '2.1';
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
		if ( $version == '2.1' ) {
			$this->xili_settings['editor_caps'] = 'no_caps'; // saved value of capabilities of editor role 2.3.2
			$this->xili_settings['version'] = '2.2';
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
		if ( $version == '2.2' ) {
			if ( ! isset( $this->xili_settings['meta_keys'] ) || ( isset( $this->xili_settings['meta_keys'] ) && 'updated' != $this->xili_settings['meta_keys'] ) ) {
				$this->xili_settings['meta_keys'] = $this->updated_sort_meta_keys(); // 2.3.3
			}
			$this->xili_settings['version'] = '2.3';
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
		if ( $version == '2.3' ) {
			$this->xili_settings['parent_langs_folder'] = '';
			$this->xili_settings['version'] = '2.4';
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}

		$this->fill_default_languages_list();
		/* Actions */

		xili_xd_error_log( '# ' . __LINE__ . ' admin_menu_add ------------' );
		add_action( 'admin_menu', array( &$this, 'dictionary_menus_pages' ) );
		add_action( 'admin_menu', array( &$this, 'admin_sub_menus_hide' ) );

		/* admin */

		add_action( 'admin_init', array( &$this, 'admin_init' ) ); // 1.3.0
		add_action( 'admin_init', array( &$this, 'ext_style_init' ) ); // 2.1
		add_action( 'admin_init', array( &$this, 'xd_erasing_init_settings' ) ); // 2.3
		add_action( 'admin_init', array( &$this, 'xd_importing_init_settings' ) ); // 2.3
		add_action( 'admin_init', array( &$this, 'xd_download_init_settings' ) ); // 2.5
		add_action( 'admin_init', array( &$this, 'download_file_if' ) ); // 2.5

		// Attach to the admin head with our ajax requests cycle and css
		add_action( 'admin_head', array( &$this, 'admin_head' ) );
		add_action( 'admin_head', array( &$this, 'check_post_type_and_remove_media_buttons' ) );

		// Attach to the admin ajax request to process cycles
		add_action( 'wp_ajax_xd_erasing_process', array( &$this, 'erasing_process_callback' ) ); // 2.3
		add_action( 'wp_ajax_xd_importing_process', array( &$this, 'importing_process_callback' ) ); // 2.3
		add_action( 'wp_ajax_xd_live_state_file', array( &$this, 'xd_live_state_file' ) ); // 2.9
		add_action( 'wp_ajax_xd_from_file_exists', array( &$this, 'xd_from_file_exists' ) );

		add_action( 'add_meta_boxes', array( &$this, 'add_custom_box_in_post_msg' ) ); // 2.1.2

		add_action( 'init', array( &$this, 'set_roles_capabilities' ), 9 );
		add_action( 'init', array( &$this, 'post_type_msg' ), 9 );
		add_action( 'init', array( &$this, 'xili_dictionary_register_taxonomies' ) );

		add_filter( 'plugin_locale', array( &$this, 'get_plugin_domain_array' ), 10, 2 );

		if ( is_admin() ) {
			add_filter( 'manage_posts_columns', array( &$this, 'xili_manage_column_name' ), 9, 1 );
			add_filter( 'manage_pages_custom_column', array( &$this, 'xili_manage_column_row' ), 9, 2 ); // hierarchic
			add_filter( 'manage_edit-' . XDMSG . '_sortable_columns', array( &$this, 'msgcontent_column_register_sortable' ) );
			add_filter( 'request', array( &$this, 'msgcontent_column_orderby' ) );

			if ( ! class_exists( 'xili_language' ) ) {
				add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_languages_posts' ) );
			}

			if ( ! class_exists( 'xili_language_ms' ) ) {
				add_action( 'category_add_form', array( &$this, 'add_content_in_taxonomy_edit_form' ) );
				add_filter( 'manage_category_custom_column', array( &$this, 'xili_manage_tax_column' ), 10, 3 ); // 2.3.3
				add_action( 'after-category-table', array( &$this, 'add_import_in_xd_button' ) );
				add_action( 'parse_query', array( &$this, 'show_imported_msgs_in_xdmg_list' ) );
				add_filter( 'query_vars', array( &$this, 'keywords_addquery_var' ) );
			}

			add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_writer_posts' ), 11 );
			add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_origin_posts' ), 10 );
			add_action( 'pre_get_posts', array( &$this, 'wpse6066_pre_get_posts' ) );

			if ( class_exists( 'xili_language' ) ) {
				// not visible if pll 2.12.2
				add_action( 'category_edit_form_fields', array( &$this, 'show_translation_msgstr' ), 10, 2 );
			}
			add_action( 'wp_print_scripts', array( &$this, 'auto_save_unsetting' ), 2 ); // before other

			add_filter( 'user_can_richedit', array( &$this, 'disable_richedit_for_cpt' ) );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG != true ) {
				add_filter( 'page_row_actions', array( &$this, 'remove_quick_edit' ), 10, 1 ); // before to solve metas column
			}
			add_action( 'save_post', array( &$this, 'custom_post_type_title' ), 11, 2 ); //
			add_action( 'save_post', array( &$this, 'msgid_post_new_create' ), 12, 2 );
			add_action( 'save_post', array( &$this, 'update_msg_comments' ), 13, 2 ); // comments and contexts
			add_filter( 'post_updated_messages', array( &$this, 'msg_post_messages' ) );

			add_action( 'before_delete_post', array( &$this, 'msgid_post_links_delete' ) );

			add_action( 'admin_print_styles-post.php', array( &$this, 'print_styles_xdmsg_edit' ) );
			add_action( 'admin_print_styles-post-new.php', array( &$this, 'print_styles_xdmsg_edit' ) );

			add_action( 'admin_print_styles-post.php', array( &$this, 'admin_enqueue_styles' ) );
			add_action( 'admin_print_scripts-post.php', array( &$this, 'admin_enqueue_scripts' ) );

			add_action( 'admin_print_styles-edit.php', array( &$this, 'print_styles_xdmsg_list' ) ); // list of msgs
			add_action( 'admin_print_styles-edit-tags.php', array( &$this, 'print_styles_edit_tags' ) ); //

			add_action( 'admin_print_styles-xdmsg_page_dictionary_page', array( &$this, 'print_styles_xdmsg_tool' ) );
			add_action( 'admin_print_styles-xdmsg_page_erase_dictionary_page', array( &$this, 'print_styles_new_ui' ) );
			add_action( 'admin_print_styles-xdmsg_page_import_dictionary_page', array( &$this, 'print_styles_new_ui' ) );

			add_action( 'add_meta_boxes_' . XDMSG, array( &$this, 'msg_update_action' ) ); // to locally update files from editing...
		}

		add_filter( 'plugin_action_links', array( &$this, 'xilidict_filter_plugin_actions' ), 10, 2 );

		/* special to detect theme changing since 1.1.9 */
		add_action( 'switch_theme', array( &$this, 'xd_theme_switched' ) );

		// Test about import frontend terms of plugin.
		if ( ! is_admin() && get_option( 'xd_test_importation', false ) ) {
			add_filter( 'gettext', array( &$this, 'detect_plugin_frontent_msg' ), 5, 3 ); // front-end limited !
		}

		if ( ! is_admin() && get_option( 'xd_test_importation', false ) ) {
			add_action( 'wp', array( &$this, 'start_detect_plugin_msg' ), 100 );
		}
		if ( ! is_admin() && get_option( 'xd_test_importation', false ) ) {
			add_action( 'shutdown', array( &$this, 'end_detect_plugin_msg' ) );
		}

		add_action( 'export_filters', array( &$this, 'message_export_limited' ) ); // 2.7

		// add_action( 'contextual_help', array( &$this, 'add_help_text' ), 10, 3 ); /* 1.2.2 - 2.14 */ !
		add_action( 'admin_head', array( &$this, 'add_help_text' ) ); // 2021-04 !
		if ( class_exists( 'xili_language_ms' ) ) {
			$this->xililanguage_ms = true; // 1.3.4
		}

		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ( '' != $langfolderset ) ? $langfolderset . '/' : '/';
		// doublon
		$this->langfolder = str_replace( '//', '/', $this->langfolder ); // upgrading... 2.0 and sub folder sub

		$langfolderset = $this->xili_settings['parent_langs_folder']; // 2.3.7
		$this->parentlangfolder = ( '' != $langfolderset ) ? $langfolderset . '/' : '/';
		// doublon
		$this->parentlangfolder = str_replace( '//', '/', $this->parentlangfolder );

	}
	// end construct

	/**
	 * used when updating 2.3.3
	 */
	public function updated_sort_meta_keys() {
		// select all msgs
		global $wpdb;
		$all_posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_content FROM $wpdb->posts WHERE post_type = %s ", XDMSG ) );

		if ( $all_posts ) {
			if ( is_wp_error( $all_posts ) ) {
				return 'error';
			}

			foreach ( $all_posts as $all_post ) {
				update_post_meta( $all_post->ID, $this->msg_sort_slug, sanitize_title( $all_post->post_content ) );
			}
		}
		return 'updated'; // empty or ...
	}


	public function set_roles_capabilities() {
		global $wp_roles;
		$add_cap = false;
		$wp_roles->remove_cap( 'editor', 'xili_dictionary_admin' ); // reset
		$wp_roles->remove_cap( 'editor', 'xili_dictionary_edit' );
		$wp_roles->remove_cap( 'editor', 'xili_dictionary_edit_save' );

		if ( current_user_can( 'activate_plugins' ) ) {

			$wp_roles->add_cap( 'administrator', 'xili_dictionary_admin' );
			$wp_roles->add_cap( 'administrator', 'xili_dictionary_edit' );
			$wp_roles->add_cap( 'administrator', 'xili_dictionary_edit_save' );
			$add_cap = true;

		} elseif ( current_user_can( 'edit_others_pages' ) ) {
			if ( 'cap_edit' == $this->xili_settings['editor_caps'] ) {
				$wp_roles->add_cap( 'editor', 'xili_dictionary_edit' );
				$add_cap = true;
			} elseif ( 'cap_edit_save' == $this->xili_settings['editor_caps'] ) {
				$wp_roles->add_cap( 'editor', 'xili_dictionary_edit' );
				$wp_roles->add_cap( 'editor', 'xili_dictionary_edit_save' );
				$add_cap = true;
			}
		}
		if ( $add_cap && current_user_can( 'activate_plugins' ) || current_user_can( 'edit_others_pages' ) ) {

			$user = wp_get_current_user();
			$user->add_cap( 'xili_dictionary_edit' ); // the current user must be updated to be checked for taxonomy menu -
			if ( current_user_can( 'activate_plugins' ) ) {
				$user->add_cap( 'xili_dictionary_admin' );
			}
		}
	}

	// when desactivating - 2.3.7
	public function remove_capabilities() {
		global $wp_roles;

		$wp_roles->remove_cap( 'administrator', 'xili_dictionary_admin' );
		$wp_roles->remove_cap( 'administrator', 'xili_dictionary_edit' );
		$wp_roles->remove_cap( 'administrator', 'xili_dictionary_edit_save' );

		$wp_roles->remove_cap( 'editor', 'xili_dictionary_admin' ); // reset
		$wp_roles->remove_cap( 'editor', 'xili_dictionary_edit' );
		$wp_roles->remove_cap( 'editor', 'xili_dictionary_edit_save' );

	}
	/* wp 4.0 -2.9.1 */
	public function get_wplang() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.0', '<' ) ) {
			if ( defined( 'WPLANG' ) ) {
				return WPLANG;
			} else {
				return '';
			}
		} else {
			return get_option( 'WPLANG', '' );
		}
	}

	/* wp 3.0 WP-net */
	public function xili_dictionary_register_taxonomies() {

		if ( is_child_theme() ) { // move here from init 1.4.1
			if ( isset( $this->xili_settings['langs_in_root_theme'] ) && 'root' == $this->xili_settings['langs_in_root_theme'] ) {
				// for future uses
				$this->active_theme_directory = get_template_directory();
			} else {
				$this->active_theme_directory = get_stylesheet_directory();
			}
		} else {
			$this->active_theme_directory = get_template_directory();
		}

		$this->init_textdomain(); // plugin

		// new method for languages 2.0
		$this->internal_list = $this->default_language_taxonomy();

		if ( $this->internal_list ) { // xl is not active
			$listlanguages = get_terms( TAXONAME, array( 'hide_empty' => false ) );

			$this->other_multilingual_compat( $listlanguages ); // what from pll ?

			if ( array() == $listlanguages ) {
				$this->create_default_languages();
			}
		}

		//,'slug' => 'the-langs-group'
		$thegroup = get_terms( TAXOLANGSGROUP, array( 'hide_empty' => false ) );
		if ( ! is_wp_error( $thegroup ) && array() != $thegroup ) { // notice on first start
			$this->langs_group_id = $thegroup[0]->term_id;
			$this->langs_group_tt_id = $thegroup[0]->term_taxonomy_id;
		}
	}

	public function xili_dictionary_activation() {
		$this->xili_settings = get_option( 'xili_dictionary_settings' );
		if ( empty( $this->xili_settings ) || 'dictionary' != $this->xili_settings['taxonomy'] ) { // to fix
			$submitted_settings = array(
				'taxonomy' => 'dictionary',
				'langs_folder' => '',
				'external_xd_style' => 'off',
				'editor_caps' => 'no_caps', // 2.3.2
				'parent_langs_folder' => '', // 2.3.7
				'version' => '2.4',
			);
			update_option( 'xili_dictionary_settings', $submitted_settings );
		}
		xili_xd_error_log( '# ' . __LINE__ . ' ----------- xili_dictionary_activation ------------' );
	}

	public function post_type_msg() {
		load_plugin_textdomain( 'xili-dictionary', false, 'xili-dictionary/languages' ); // 2.9
		$labels = array(
			'name' => _x( 'xili-dictionary©', 'post type general name', 'xili-dictionary' ),
			'singular_name' => _x( 'Msg', 'post type singular name', 'xili-dictionary' ),
			'add_new' => esc_html__( 'New Msgid', 'xili-dictionary' ),
			'add_new_item' => esc_html__( 'Add New Msgid', 'xili-dictionary' ),
			'edit_item' => esc_html__( 'Edit Msg', 'xili-dictionary' ),
			'new_item' => esc_html__( 'New Msg', 'xili-dictionary' ),
			'view_item' => esc_html__( 'View Msg', 'xili-dictionary' ),
			'search_items' => esc_html__( 'Search Msg', 'xili-dictionary' ),
			'not_found' => esc_html__( 'No Msg found', 'xili-dictionary' ),
			'not_found_in_trash' => esc_html__( 'No Msg found in Trash', 'xili-dictionary' ),
			'parent_item_colon' => '',
		);

		// impossible to see in front-end (no display in edit list)
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'_edit_link' => 'post.php?post=%d',
			'_builtin' => false,
			'show_ui' => true,
			'query_var' => XDMSG, // add 2.3.3
			'rewrite' => false,
			'capability_type' => 'post',
			'show_in_menu' => current_user_can( 'xili_dictionary_edit' ), // ?? if not admin true, // xili_dictionary_edit
			'hierarchical' => true,
			'menu_position' => null,
			'supports' => array( 'author', 'editor', 'excerpt' ), // ,'page-attributes', 'custom-fields' => (parent for plural), not ready
			'taxonomies' => array( 'origin', 'writer' ), // 'appearance',
			'rewrite' => array(
				'slug' => XDMSG,
				'with_front' => false,
			),
			'menu_icon' => XILIDICTIONARY_PLUGIN_URL . '/images/XD-logo-16.png' , // 16px16
		);
		register_post_type( XDMSG, $args );

		register_taxonomy(
			'origin',
			array( XDMSG ),
			array(
				'hierarchical' => false,
				'label' => esc_html__( 'Origins', 'xili-dictionary' ),
				'query_var' => 'origin',
				'public' => false, // fixed for menus 2.3.6
				'show_ui' => true,
				//'show_in_nav_menus' => false, // not necessary CPT XDMSG not in Nav Menu WP 4.3
				'rewrite' => array( 'slug' => 'origin' ),
				'labels' => array(
					'name' => esc_html__( 'Origins', 'xili-dictionary' ),
					'singular_name' => esc_html__( 'Origin', 'xili-dictionary' ),
					'search_items' => esc_html__( 'Search Origins', 'xili-dictionary' ),
					'popular_items' => esc_html__( 'Popular Origins', 'xili-dictionary' ),
					'all_items' => esc_html__( 'All Origins', 'xili-dictionary' ),
					'parent_item' => esc_html__( 'Parent Origin', 'xili-dictionary' ),
					'parent_item_colon' => esc_html__( 'Parent Origin:', 'xili-dictionary' ),
					'edit_item' => esc_html__( 'Edit Origin', 'xili-dictionary' ),
					'update_item' => esc_html__( 'Update Origin', 'xili-dictionary' ),
					'add_new_item' => esc_html__( 'Add New Origin', 'xili-dictionary' ),
					'new_item_name' => esc_html__( 'New Origin Name ', 'xili-dictionary' ),
					'separate_items_with_commas' => esc_html__( 'Separate origins with commas', 'xili-dictionary' ),
					'add_or_remove_items' => esc_html__( 'Add or remove origins', 'xili-dictionary' ),
					'choose_from_most_used' => esc_html__( 'Choose from the most used origins', 'xili-dictionary' ),
					'not_found' => esc_html__( 'No origin found', 'xili-dictionary' ),
				),
			)
		);

		register_taxonomy(
			'writer',
			array( XDMSG ),
			array(
				'hierarchical' => true,
				'label' => esc_html__( 'Writers', 'xili-dictionary' ),
				'rewrite' => true,
				'query_var' => 'writer_name',
				'public' => false,
				'show_ui' => true,
				'labels' => array(
					'name' => esc_html__( 'Writers', 'xili-dictionary' ),
					'singular_name' => esc_html__( 'Writer', 'xili-dictionary' ),
					'search_items' => esc_html__( 'Search Writers', 'xili-dictionary' ),
					'popular_items' => esc_html__( 'Popular Writers', 'xili-dictionary' ),
					'all_items' => esc_html__( 'All Writers', 'xili-dictionary' ),
					'parent_item' => esc_html__( 'Parent Writer', 'xili-dictionary' ),
					'parent_item_colon' => esc_html__( 'Parent Writer:', 'xili-dictionary' ),
					'edit_item' => esc_html__( 'Edit Writer', 'xili-dictionary' ),
					'update_item' => esc_html__( 'Update Writer', 'xili-dictionary' ),
					'add_new_item' => esc_html__( 'Add New Writer', 'xili-dictionary' ),
					'new_item_name' => esc_html__( 'New Writer Name ', 'xili-dictionary' ),
					'separate_items_with_commas' => esc_html__( 'Separate writers with commas', 'xili-dictionary' ),
					'add_or_remove_items' => esc_html__( 'Add or remove writers', 'xili-dictionary' ),
					'choose_from_most_used' => esc_html__( 'Choose from the most used writers', 'xili-dictionary' ),
					'not_found' => esc_html__( 'No writer found', 'xili-dictionary' ),
				),
			)
		);
	}

	// 2.7.2
	public function check_post_type_and_remove_media_buttons() {
		$screen = get_current_screen();
		if ( XDMSG == $screen->id ) {
			remove_action( 'media_buttons', 'media_buttons' );
			add_action( 'edit_form_top', array( &$this, 'msg_subtitle' ) );
		}
	}

	/**
	 * subtitle inserted to describe msg type
	 *
	 * @since 2.7.2
	 */
	public function msg_subtitle( $post ) {
		$singular_name = esc_html__( 'series', 'xili-dictionary' );
		$type = get_post_meta( $post->ID, $this->msgtype_meta, true );
		if ( false === strpos( $type, 'str' ) ) { // msgid
			/* translators: */
			echo '<h3>' . sprintf( _x( '%s', 'form', 'xili-dictionary' ), $type ) . '</h3>';
			echo '<p>' . esc_html__( 'Reference', 'xili-dictionary' ) . '<br />';
			echo '<em>' . __( 'This text (msgid) is the Reference and must modified <strong>ONLY IF</strong> you are sure that it will be the same as in source code or text.', 'xili-dictionary' ) . '</em><br />';
			/* translators: */
			echo '<em>' . sprintf(
				esc_html__( 'To create or edit translations (msgstr), see %1$s in the table of box %2$s.', 'xili-dictionary' ),
				'<a style="background:yellow;" href="#msg_state" >' . esc_html__( 'links', 'xili-dictionary' ) . '</a>',
				'</em>' . sprintf( esc_html__( 'msg %s', 'xili-dictionary' ), $singular_name )
			) . '</p>';
		} else { // msgstr
			if ( false === strpos( $type, '_' ) ) {
				/* translators: */
				echo '<h3>' . sprintf( _x( '%s', 'form', 'xili-dictionary' ), $type ) . '</h3>';
				$plural = '';
			} else { // plural translation
				$indices = explode( '_', $type );
				$indice = $indices[1];
				/* translators: */
				echo '<h3>' . sprintf( _x( 'msgstr[%d]', 'form', 'xili-dictionary' ), $indice ) . '</h3>';
				if ( false !== strpos( $type, '_0' ) ) {
					$plural = '';
				} else {
					$plural = esc_html__( '&nbsp;(plural)', 'xili-dictionary' );
				}
			}
			echo '<p><em>' . sprintf( esc_html__( 'Translation%s', 'xili-dictionary' ), $plural ) . '</em></p>';
		}
	}

	/**
	 * register language taxonomy if no xili_language - 'update_count_callback' => array( &$this, '_update_post_lang_count' ),
	 *
	 *
	 */
	public function default_language_taxonomy() {

		if ( ! defined( 'TAXONAME' ) ) {
			// XL is not active
			if ( ! defined( 'QUETAG' ) ) {
				define( 'QUETAG', 'lang' );
			}

			define( 'TAXONAME', 'language' );
			register_taxonomy(
				TAXONAME,
				'post',
				array(
					'hierarchical' => false,
					'label' => false,
					'rewrite' => false,
					'show_ui' => false,
					'_builtin' => false,
					'show_in_nav_menus' => false,
					'query_var' => QUETAG,
				)
			);

			define( 'TAXOLANGSGROUP', 'languages_group' );
			register_taxonomy(
				TAXOLANGSGROUP,
				'term',
				array(
					'hierarchical' => false,
					'update_count_callback' => '',
					'show_ui' => false,
					'label' => false,
					'rewrite' => false,
					'show_in_nav_menus' => false, //
					'_builtin' => false,
				)
			);
			$thegroup = get_terms(
				TAXOLANGSGROUP,
				array(
					'hide_empty' => false,
					'slug' => 'the-langs-group',
				)
			);
			if ( array() == $thegroup ) {
				$args = array(
					'alias_of' => '',
					'description' => 'the group of languages',
					'parent' => 0,
					'slug' => 'the-langs-group',
				);
				wp_insert_term( 'the-langs-group', TAXOLANGSGROUP, $args ); /* create and link to existing langs */
			}
			return true;
		} else {
			return false;
		}
	}


	/**
	 * create default languages if no xili_language
	 *
	 * @since
	 */
	public function create_default_languages() {

		$this->default_langs_array = array(
			'en_us' => array( 'en_US', 'english' ),
			'fr_fr' => array( 'fr_FR', 'french' ),
			'de_de' => array( 'de_DE', 'german' ),
			'es_es' => array( 'es_ES', 'spanish' ),
			'it_it' => array( 'it_IT', 'italian' ),
			'pt_pt' => array( 'pt_PT', 'portuguese' ),
			'ru_ru' => array( 'ru_RU', 'russian' ),
			'zh_cn' => array( 'zh_CN', 'chinese' ),
			'ja' => array( 'ja', 'japanese' ),
			'ar_ar' => array( 'ar_AR', 'arabic' ),
		);

		$term = 'en_US';
		$args = array(
			'alias_of' => '',
			'description' => 'english',
			'parent' => 0,
			'slug' => 'en_us',
		);
		$theids = $this->safe_lang_term_creation( $term, $args );
		if ( ! is_wp_error( $theids ) ) {
			wp_set_object_terms( $theids['term_id'], 'the-langs-group', TAXOLANGSGROUP );
		}

		/* default value detected in config */
		if ( '' != $this->get_wplang() && ( 5 == strlen( $this->get_wplang() ) || 2 == strlen( $this->get_wplang() ) ) ) : // for japanese
			$this->default_lang = $this->get_wplang();
		else :
			$this->default_lang = 'en_US';
		endif;

		$term = $this->default_lang;
		$desc = $this->default_lang;
		$slug = strtolower( $this->default_lang ); // 2.3.1
		if ( 'en_US' == $this->default_lang || '' == $this->default_lang ) {
			$term = 'fr_FR';
			$desc = 'French';
			$slug = 'fr_fr';
		}
		$args = array(
			'alias_of' => '',
			'description' => $desc,
			'parent' => 0,
			'slug' => $slug,
		);

		$theids = $this->safe_lang_term_creation( $term, $args );
		if ( ! is_wp_error( $theids ) ) {
			wp_set_object_terms( $theids['term_id'], 'the-langs-group', TAXOLANGSGROUP );
		}

	}

	/**
	 * Safe language term creation (if XL inactive)
	 *
	 * @since 2.0 (from XL 2.4.1)
	 */
	public function safe_lang_term_creation( $term, $args ) {
		global $wpdb;
		// test if exists with other slug or name
		if ( $term_id = term_exists( $term ) ) {
			$existing_term = $wpdb->get_row( $wpdb->prepare( "SELECT name, slug FROM $wpdb->terms WHERE term_id = %d", $term_id), ARRAY_A );
			if ( $existing_term['slug'] != $args['slug'] ) {
				$res = wp_insert_term( $term . 'xl', TAXONAME, $args ); // temp insert with temp other name
				$args['name'] = $term;
				$res = wp_update_term( $res['term_id'], TAXONAME, $args );
			} else {
				return new WP_Error( 'term_exists', esc_html__( 'A term with the name provided already exists.' ), $term_id );
			}
		} else {
			$res = wp_insert_term( $term, TAXONAME, $args );
		}
		/*
		if ( is_wp_error( $res ) ) {
			return $res;
		} else {
			$theids = $res;
		}*/
		return $res;
	}

	/**
	 * call from filter disable_richedit
	 *
	 * disable rich editor in msg cpt
	 *
	 * @since 2.0
	 *
	 */
	public function disable_richedit_for_cpt( $default ) {
		global $post;
		if ( XDMSG == get_post_type( $post ) ) {
			return false;
		}
		return $default;
	}
	public function remove_quick_edit( $actions ) {
		if ( isset( $_GET['post_type'] ) && XDMSG == $_GET['post_type'] ) {
			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * call from filter save_post
	 *
	 * save content in title - fixes empty msgid
	 *
	 * @since 2.0
	 *
	 */
	public function custom_post_type_title( $post_id, $post ) {
		global $wpdb;
		if ( XDMSG == get_post_type( $post_id ) ) {
			$where = array( 'ID' => $post_id );
			$what = array();

			if ( false === strpos( $post->post_title, 'MSG:' ) ) {
				$format_id = substr( 10000000 + (int) $post_id, - 7 ); // since 2.7.2
				$title = 'MSG:' . $format_id;
				$what['post_title'] = $title;
			}

			if ( '' == $post->post_content ) {
				$what['post_content'] = 'XD say: do not save empty ' . $post_id;
			}
			if ( array() != $what ) {
				$wpdb->update( $wpdb->posts, $what, $where );
			}
		}
	}

	/**
	 * clean msgid postmeta before deleting
	 */
	public function msgid_post_links_delete( $post_id ) {
		// type of msg
		if ( XDMSG == get_post_type( $post_id ) ) {
			$type = get_post_meta( $post_id, $this->msgtype_meta, true );

			if ( 'msgid_plural' == $type ) {

				$parent = get_post( $post_id )->post_parent;
				$res = get_post_meta( $parent, $this->msgchild_meta, false );
				$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
				if ( '' != $res ) {
					unset( $thechilds['msgid']['plural'] );
					update_post_meta( $parent, $this->msgchild_meta, $thechilds );
				}
			} elseif ( 'msgid' != $type ) {
				$langs = get_the_terms( $post_id, TAXONAME );
				$target_lang = $langs[0]->name;
				// id of msg id or parent
				if ( 'msgstr' == $type && '' != $target_lang ) {
					$msgid_id = get_post_meta( $post_id, $this->msgidlang_meta, true );
					$res = get_post_meta( $msgid_id, $this->msglang_meta, false );
					$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
					if ( '' != $res && is_array( $thelangs ) ) {

						unset( $thelangs['msgstrlangs'][ $target_lang ]['msgstr'] );
						if ( isset( $thelangs['msgstrlangs'][ $target_lang ] ) && array() == $thelangs['msgstrlangs'][ $target_lang ] ) {
							unset( $thelangs['msgstrlangs'][ $target_lang ] ); // 2.3
						}
						if ( isset( $thelangs['msgstrlangs'] ) && array() == $thelangs['msgstrlangs'] ) {
							unset( $thelangs['msgstrlangs'] ); // 2.3
						}
						if ( array() != $thelangs ) {
							update_post_meta( $msgid_id, $this->msglang_meta, $thelangs ); // update id post_meta
						} else {
							delete_post_meta( $msgid_id, $this->msglang_meta );
						}
					}
				} elseif ( false !== strpos( $type, 'msgstr_' ) && '' != $target_lang ) {
					$indices = explode( '_', $type );
					$msgid_id = get_post_meta( $post_id, $this->msgidlang_meta, true );
					if ( 0 == $indices[1] ) {
						$res = get_post_meta( $msgid_id, $this->msglang_meta, false );
						$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
						if ( '' != $res && is_array( $thelangs ) ) {
							unset( $thelangs['msgstrlangs'][ $target_lang ]['msgstr_0'] );
							if ( isset( $thelangs['msgstrlangs'][ $target_lang ] ) && $thelangs['msgstrlangs'][ $target_lang ] == array( ) ) {
								unset( $thelangs['msgstrlangs'][ $target_lang ] ); // 2.3
							}
							if ( isset( $thelangs['msgstrlangs'] ) && array() == $thelangs['msgstrlangs'] ) {
								unset( $thelangs['msgstrlangs'] ); // 2.3
							}

							if ( array() != $thelangs ) {
								update_post_meta( $msgid_id, $this->msglang_meta, $thelangs ); // update id post_meta
							} else {
								delete_post_meta( $msgid_id, $this->msglang_meta ); // 2.9
							}
						}
					} else {
						$res = get_post_meta( $msgid_id, $this->msglang_meta, false );
						$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
						if ( '' != $res && is_array( $thelangs ) ) {
							if ( isset( $thelangs['msgstrlangs'][ $target_lang ]['msgstr_0'] ) ) {
								$parent = $thelangs['msgstrlangs'][ $target_lang ]['msgstr_0'];
								$res = get_post_meta( $parent, $this->msgchild_meta, false );
								$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
								if ( '' != $res ) {
									unset( $thechilds['msgstr']['plural'][ $indices[1] ] );
									update_post_meta( $parent, $this->msgchild_meta, $thechilds );
								}
							}
						}
					} // indice > 0
				} // str plural
			} // msgstr
		} // XDMSG
	}

	/**
	 * a new msgid is created/edited manually
	 */
	public function msgid_post_new_create( $post_id, $post ) {
		global $wpdb;
		if ( isset( $_POST['_inline_edit'] ) ) {
			return;
		}
		if ( isset( $_GET['bulk_edit'] ) ) {
			return;
		}
		if ( XDMSG == get_post_type( $post_id ) ) {
			if ( ! wp_is_post_revision( $post_id ) && true !== $this->importing_mode ) {

				//$temp_post = $this->temp_get_post( $post_id );
				$type = get_post_meta( $post_id, $this->msgtype_meta, true );
				if ( '' == $type ) {
					$type = 'msgid';
					update_post_meta( $post_id, $this->msgtype_meta, $type );
					update_post_meta( $post_id, $this->msglang_meta, array() );
					update_post_meta( $post_id, $this->msg_extracted_comments, $this->local_tag . ' ' ); // 2.2.0 local by default if hand created
					update_post_meta( $post_id, $this->msg_sort_slug, sanitize_title( $post->post_content ) );
				}
				if ( isset( $_POST['add_ctxt'] ) ) {
					$the_context = $_POST['add_ctxt']; // 2.7.2 - fixed if very new msgid
				} else {
					$the_context = get_post_meta( $post_id, $this->ctxt_meta, true );
				}

				$result = $this->msgid_exists( $post->post_content, $the_context );

				if ( empty( $result ) || ( in_array( $post_id, $result ) && 1 == count( $result ) ) ) {
					return;
				} elseif ( 'msgid' == $type ) { // only msgid tested
					$found_others = array();
					// erase current
					foreach ( $result as $one ) {
						if ( $one != $post_id ) {
							$found_others[] = $one;
						}
					}
					if ( $the_context ) {
						// only one  with this context - impossible to have 2 msgid with same context
						$found_context = get_post_meta( $found_others[0], $this->ctxt_meta, true ); //2.10.2
						if ( $found_context == $the_context ) {
							/* translators: */
							$context = ' ' . sprintf( esc_html__( 'and context %s', 'xili-dictionary' ), $the_context ); // 2.7.1
							/* translators: */
							$newcontent = sprintf( esc_html__( 'msgid exists as %1$d with content: %2$s%3$s', 'xili-dictionary' ), $result[0], $post->post_content, $context );
							$where = array( 'ID' => $post_id );
							$wpdb->update( $wpdb->posts, array( 'post_content' => $newcontent ), $where );
						}
					} else {
							$context = ' ' . esc_html__( 'without context', 'xili-dictionary' );
							/* translators: */
							$newcontent = sprintf( esc_html__( 'msgid exists as %1$d with content: %2$s%3$s', 'xili-dictionary' ), $result[0], $post->post_content, $context );
							$where = array( 'ID' => $post_id );
							$wpdb->update( $wpdb->posts, array( 'post_content' => $newcontent ), $where );
					}
				}
			}
		}
	}


	/**
	 * test unique content for msgid + context
	 *
	 * @since 2.0
	 * @return ID is true
	 */
	public function msgid_exists( $content = '', $ctxt = null ) {
		global $wpdb;
		if ( '' != $content ) {
			if ( null == $ctxt ) {
				$posts_query = $wpdb->prepare(
					"SELECT ID FROM $wpdb->posts
					INNER JOIN $wpdb->postmeta as mt1 ON ( $wpdb->posts.ID = mt1.post_id)
					WHERE BINARY post_content = %s
					AND post_type = %s
					AND mt1.meta_key= '{$this->msgtype_meta}'
					AND mt1.meta_value = %s "
					, $content,
					XDMSG,
					'msgid'
				);
			} else {
				$posts_query = $wpdb->prepare(
					"SELECT ID FROM $wpdb->posts
					INNER JOIN $wpdb->postmeta as mt1 ON ( $wpdb->posts.ID = mt1.post_id)
					WHERE BINARY post_content = %s
					AND post_type = %s AND mt1.meta_key= '{$this->ctxt_meta}'
					AND mt1.meta_value = %s ",
					$content,
					XDMSG,
					$ctxt
				);
			}
			// 2.2.0

			$found_posts = $wpdb->get_col( $posts_query );
			if ( empty( $found_posts ) ) {

				return false;
			} else {

				return $found_posts;
			}
		}

	}

	/**
	 * test unique content for msgstr + msgid + language
	 *
	 * @since 2.0
	 * @return ID is true
	 */
	public function msgstr_exists( $content = '', $msgid, $curlang ) {
		global $wpdb;
		if ( '' != $content ) {
			$posts_query = $wpdb->prepare(
				"SELECT ID FROM $wpdb->posts
				INNER JOIN $wpdb->postmeta as mt1 ON ( $wpdb->posts.ID = mt1.post_id)
				WHERE BINARY post_content = %s AND post_type = %s
				AND mt1.meta_key='{$this->msgidlang_meta}' AND mt1.meta_value = %s ",
				$content,
				XDMSG,
				$msgid
			);

			$found_posts = $wpdb->get_col( $posts_query );
			if ( empty( $found_posts ) ) {
				return false;
			} else {

				if ( in_array( $curlang, wp_get_object_terms( $found_posts, TAXONAME, array( 'fields' => 'names' ) ) ) ) {
					// select only this with $curlang

					return $found_posts;

				} else {
					return false;
				}
			}
		}
		return false;
	}


	/**
	 * Test if importing POT file
	 *
	 * @since 2.6.1
	 *
	 * @return true or false (true = pot import)
	 */
	public function is_importing_pot( $curlang ) {

		if ( ! in_array( $curlang, $this->languages_names ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * import a msg line
	 *
	 * @since 2.0
	 *
	 * @updated 2.1.2
	 *
	 * @return ID
	 */
	public function insert_one_cpt_and_meta( $content, $context = null, $type, $parent = 0, $entry = null ) {
		global $user_ID;
		/* if (! empty( $entry->translator_comments) ) $po[] = PO::comment_block( $entry->translator_comments);
				if (! empty( $entry->extracted_comments) ) $po[] = PO::comment_block( $entry->extracted_comments, '.' );
				if (! empty( $entry->references) ) $po[] = PO::comment_block(implode( ' ', $entry->references), ':' );
				if (! empty( $entry->flags) ) $po[] = PO::comment_block(implode(", ", $entry->flags), ',' );
			*/
		if ( null != $entry ) {
			$references = ( ! empty( $entry->references ) ) ? implode( ' #: ', $entry->references ) : '';
			$flags = ( ! empty( $entry->flags ) ) ? implode( ', ', $entry->flags ) : '';
			$extracted_comments = ( ! empty( $entry->extracted_comments ) ) ? $entry->extracted_comments : '';
			$translator_comments = ( ! empty( $entry->translator_comments ) ) ? $entry->translator_comments : '';
		} else {
			$references = '';
			$flags = '';
			$extracted_comments = '';
			$translator_comments = '';

		}

		$params = array(
			'post_status' => 'publish',
			'post_type' => XDMSG,
			'post_author' => $user_ID,
			'ping_status' => get_option( 'default_ping_status' ),
			'post_parent' => $parent,
			'menu_order' => 0,
			'to_ping' => '',
			'pinged' => '',
			'post_password' => '',
			'guid' => '',
			'post_content_filtered' => '',
			'post_excerpt' => $references,
			'import_id' => 0,
			'post_content' => wp_slash( $content ),
			'post_title' => '',
		);

		$post_id = wp_insert_post( $params );

		if ( 0 != $post_id ) {
			if ( null != $context ) {
				update_post_meta( $post_id, $this->ctxt_meta, $context );
			}

			// type postmeta

			update_post_meta( $post_id, $this->msgtype_meta, $type );

			if ( 'msgid' == $type ) {
				if ( '' != $extracted_comments ) {
					update_post_meta( $post_id, $this->msg_extracted_comments, $extracted_comments );
				}
				if ( '' != $translator_comments ) {
					update_post_meta( $post_id, $this->msg_translator_comments, $translator_comments );
				}
				if ( '' != $flags ) {
					update_post_meta( $post_id, $this->msg_flags, $flags );
				}
				$this->update_msgid_flags( $post_id, $params['post_content'] ); // 2.9
				update_post_meta( $post_id, $this->msglang_meta, array() ); // 2.1.2
			}

			if ( 'msgstr' == $type || 'msgstr_0' == $type ) {

				if ( '' != $translator_comments ) {
					update_post_meta( $post_id, $this->msg_translator_comments, $translator_comments );
				}
			}
			update_post_meta( $post_id, $this->msg_sort_slug, sanitize_title( $content ) );

			// update postmeta children
			// create array
			if ( 0 != $parent ) {

				$res = get_post_meta( $parent, $this->msgchild_meta, false );
				$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
				if ( 'msgid_plural' == $type ) {
					$thechilds['msgid']['plural'] = $post_id;

				} elseif ( 'msgstr' != $type ) {
					$indices = explode( '_', $type );
					$thechilds['msgstr']['plural'][ $indices[1] ] = $post_id;
				}

				update_post_meta( $parent, $this->msgchild_meta, $thechilds );

			}
		}
		return $post_id;
	}


	/**
	 * Modify query to display only recent imported msgs array or adapt order_by
	 *
	 * @since 2.3.3
	 *
	 */
	public function show_imported_msgs_in_xdmg_list( $args = array() ) {
		global $wp_query;

		$query = $args->query;
		if ( is_array( $query ) ) {
			$r = $query;
		} else {
			parse_str( $query, $r );
		}

		if ( XDMSG == $r['post_type'] ) {

			if ( isset( $r[ 'only_' . XDMSG ] ) ) {

				$wp_query->query_vars['post__in'] = explode( ',', $r[ 'only_' . XDMSG ] );
				$wp_query->query_vars['meta_key'] = $this->msg_sort_slug;
				$wp_query->query_vars['orderby'] = 'meta_value';
				if ( ! isset( $wp_query->query_vars['order'] ) ) {
					$wp_query->query_vars['order'] = 'asc';
				}
			} elseif ( isset( $r['orderby'] ) && 'msgcontent' == $r['orderby'] ) { // sort by content via meta msg_sort_slug
				$wp_query->query_vars['meta_key'] = $this->msg_sort_slug;
				$wp_query->query_vars['orderby'] = 'meta_value';

			}
		}

	}

	public function keywords_addquery_var( $vars ) {
		$vars[] = 'only_' . XDMSG;

		return $vars;
	}

	public function add_content_in_taxonomy_edit_form( $taxonomy ) {
		?>
		<?php
		// future features under Add New button
	}

	/**
	 * Complete taxonomy edit screen (just under description)
	 *
	 * @since 2.0
	 *
	 */
	public function show_translation_msgstr( $tag, $taxonomy ) {

		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="description"><?php esc_html_e( 'Translated in', 'xili-dictionary' ); ?></label></th>
			<td>
			<?php
			echo '<fieldset class="taxinmos" ><legend><em>' . esc_html__( 'Name' ) . '</em> = ' . $tag->name . '</legend>';
			$a = $this->is_msg_saved_in_localmos( $tag->name, 'msgid', '', 'single' );
			echo $a[0];
			$ids = array();
			if ( current_user_can( 'xili_dictionary_edit' ) ) {

				$result = $this->msgid_exists( $tag->name );
				if ( false != $result ) {
					// $msgid_desc_id
					$this->tax_msgid_list[] = $result[0];
					if ( get_post_status( $result[0] ) != 'trash' ) {
						$ids[] = $result[0];
					}
				}
			}

			echo '</fieldset><br /><fieldset class="taxinmos" ><legend><em>' . esc_html__( 'Description' ) . '</em> = ' . $tag->description . '</legend>';
			$a = $this->is_msg_saved_in_localmos( $tag->description, 'msgid', '', 'single' );
			echo $a[0];
			if ( current_user_can( 'xili_dictionary_edit' ) ) {

				$result = $this->msgid_exists( $tag->description );
				if ( false != $result ) {
					// $msgid_desc_id
					$this->tax_msgid_list[] = $result[0];
					if ( get_post_status( $result[0] ) != 'trash' ) {
						$ids[] = $result[0];
					}
				}
			}
			echo '</fieldset>';
			if ( array() != $ids ) {
				echo '<p><span class="button displaybbt">';
				/* translators: */
				printf( __( '<a href="%s">Display term(s) in XD</a>', 'xili-dictionary' ), admin_url() . 'edit.php?post_type=' . XDMSG . '&only_' . XDMSG . '=' . implode( ',', $ids ) );
				echo '</span></p>';
			}

			?>
			<p><em><?php esc_html_e( 'This list above gathers the translations of name and description saved in current local-xx_XX.mo files of the current theme.', 'xili-dictionary' ); ?></em></p>
			</td>
		</tr>

		<?php
	}

	/**
	 * Add writer selector in edit.php edit
	 *
	 * @since 2.0
	 *
	 */
	public function restrict_manage_writer_posts() {
		if ( isset( $_GET['post_type'] ) && XDMSG == $_GET['post_type'] ) {
			$listwriters = get_terms( 'writer', array( 'hide_empty' => false ) );
			if ( array() != $listwriters ) {
				$selected = '';
				if ( isset( $_GET['writer_name'] ) ) {
					$selected = $_GET['writer_name'];
				}
				$dropdown_options = array(
					'taxonomy' => 'writer',
					'show_option_all' => esc_html__( 'View all writers', 'xili-dictionary' ),
					'hide_empty' => 0,
					'hierarchical' => 1,
					'show_count' => 0,
					'orderby' => 'name',
					'name' => 'writer_name',
					'selected' => $selected,
				);
				wp_dropdown_categories( $dropdown_options );
			}
		}
	}

	/**
	 * to fixes wp_dropdown_categories id value in option
	 * thanks to http://wordpress.stackexchange.com/questions/6066/query-custom-taxonomy-by-term-id
	 */
	public function wpse6066_pre_get_posts( &$wp_query ) {

		if ( $wp_query->is_tax ) {
			if ( is_numeric( $wp_query->get( 'writer_name' ) ) ) {
				// Convert numberic terms to term slugs for dropdown
				$term = get_term_by( 'term_id', $wp_query->get( 'writer_name' ), 'writer' );

				if ( $term ) {
					$wp_query->set( 'writer_name', $term->slug );
				}
			}

			if ( is_numeric( $wp_query->get( 'origin' ) ) ) {

				// Convert numberic terms to term slugs for dropdown

				$term = get_term_by( 'term_id', $wp_query->get( 'origin' ), 'origin' );

				if ( $term ) {
					$wp_query->set( 'origin', $term->slug );
				}
			}
		}
	}

	/**
	 * @since
	 * call after saved xdmsg
	 *
	 * updated to insert flags according msgid content
	 *
	 */
	public function update_msg_comments( $post_id ) {
		if ( XDMSG == get_post_type( $post_id ) ) {
			// only visible if msgstr
			$translator_comments = ( isset( $_POST['add_translator_comments'] ) ) ? $_POST['add_translator_comments'] : '';
			if ( '' != $translator_comments ) {
				update_post_meta( $post_id, $this->msg_translator_comments, $translator_comments );
			}
			// add_ctxt

			if ( isset( $_POST['add_ctxt'] ) ) {
				$ctxt = $_POST['add_ctxt'];
				if ( '' != $ctxt ) {
					update_post_meta( $post_id, $this->ctxt_meta, $ctxt );
				} else {
					delete_post_meta( $post_id, $this->ctxt_meta );
				}
			}
			$thepost = $this->temp_get_post( $post_id );
			update_post_meta( $post_id, $this->msg_sort_slug, sanitize_title( $thepost->post_content ) ); // 2.3.3

			$type = get_post_meta( $post_id, $this->msgtype_meta, true );
			if ( 'msgid' == $type ) {
				$this->update_msgid_flags( $post_id, $thepost->post_content );
			}
		}
	}

	public function update_msgid_flags( $post_id, $post_content ) {
		// analyse $thepost->post_content %d %s %2$s
		if ( preg_match( '/(%d|%s|%\S\$s)*/', $post_content, $matches ) ) {

			$flags = get_post_meta( $post_id, $this->msg_flags, true );
			if ( $flags ) {
				$flags = explode( ',', $flags );
				if ( ! in_array( 'php-format', $flags ) ) {
					$flags[] = 'php-format';
				}
				$flags = implode( ',', $flags );
			} else {
				$flags = 'php-format';
			}
			update_post_meta( $post_id, $this->msg_flags, $flags );
		} else {
			$flags = get_post_meta( $post_id, $this->msg_flags, true );
			if ( $flags ) {
				$flags = explode( ',', $flags );
				if ( in_array( 'php-format', $flags ) ) {
					$key = array_search( 'php-format', $flags );
					unset( $flags [ $key ] );
					$flags = implode( ',', $flags );
					update_post_meta( $post_id, $this->msg_flags, $flags );
				}
			}
		}
	}


	/**
	 * display msg series linked together
	 *
	 * @param post ID, display (true for single edit)
	 *
	 */
	public function msg_link_display( $id, $display = false, $thepost = null ) {

		if ( null != $thepost ) {
			$post = $thepost;
		} else {
			global $post;
		}

		$spanred = '<span class="alert">';
		$spanend = '</span>';
		// type
		$type = get_post_meta( $id, $this->msgtype_meta, true );

		$res = get_post_meta( $id, $this->msgchild_meta, false );
		$thechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();

		$res = get_post_meta( $id, $this->msglang_meta, false );
		$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();

		if ( 'msgid' == $type ) {
			$ctxt = get_post_meta( $id, $this->ctxt_meta, true );
			if ( 'trash' == $post->post_status ) {
				echo $spanred;
			}
			if ( $display ) {
				echo '<div class="msg-saved" >';
				printf( esc_html__( 'msgid saved as: <em>%s</em>', 'xili-dictionary' ), $post->post_content );
				echo '</div>';
			} else {
				echo '<mark><strong>msgid</strong></mark>';
			}
			if ( 'trash' == $post->post_status ) {
				echo $spanend;
			}
			echo '<br />';
			if ( '' != $ctxt && ! $display ) {
				printf( 'ctxt: %s <br />', $ctxt );
			}

			if ( isset( $thechilds['msgid']['plural'] ) ) {
				$post_status = get_post_status( $thechilds['msgid']['plural'] );
				if ( ! $display ) {
					if ( 'trash' == $post_status || false === $post_status ) {
						echo $spanred;
					}
					/* translators: */
					printf( __( 'has plural: <a href="%1$s" >%2$d</a><br />', 'xili-dictionary' ), 'post.php?post=' . $thechilds['msgid']['plural'] . '&action=edit', $thechilds['msgid']['plural'] );
					if ( 'trash' == $post_status || false === $post_status ) {
						echo $spanend;
					}
				} else {
					if ( 'trash' == $post_status || false === $post_status ) {
						echo $spanred;
					}
					esc_html_e( 'has plural:', 'xili-dictionary' );
					echo '&nbsp;';
					if ( 'trash' == $post_status || false === $post_status ) {
						echo $spanend;
					}
					$temp_post = $this->temp_get_post( $thechilds['msgid']['plural'] );
					$content_plural = htmlspecialchars( $temp_post->post_content );
					echo '<strong>' . $content_plural . '</strong> ';
					printf( __( '( <a href="%1$s" title="link to:%2$d" >%3$s</a> )<br />', 'xili-dictionary' ), 'post.php?post=' . $thechilds['msgid']['plural'] . '&action=edit', $thechilds['msgid']['plural'], esc_html__( 'Edit' ) );
				}
			} else {
				if ( $display && ! isset( $thelangs['msgstrlangs'] ) && ! isset( $thechilds['msgid']['plural'] ) ) { // not yet translated
					/* translators: */
					printf( __( '&nbsp;<a href="%s" >Create msgid_plural</a>', 'xili-dictionary' ), 'post.php?post=' . $id . '&action=edit&msgaction=msgid_plural' );
					echo '<br />';
				}
			}
			$res = get_post_meta( $id, $this->msglang_meta, false );
			$thelangs = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
			// action to create child and default line - single or plural...
			if ( isset( $_GET['msgaction'] ) && isset( $_GET['langstr'] ) && $display ) {
				$target_lang = $_GET['langstr'];
				if ( 'msgstr' == $_GET['msgaction'] && ! isset( $thelangs['msgstrlangs'][ $target_lang ] ) ) {
					// create post
					if ( ! isset( $thechilds['msgid']['plural'] ) ) {
						/* translators: */
						$msgstr_post_id = $this->insert_one_cpt_and_meta( sprintf( esc_html__( 'XD say to translate in %s: ', 'xili-dictionary' ), $target_lang ) . ' ' . $post->post_content, null, 'msgstr', 0 );
						wp_set_object_terms( $msgstr_post_id, $this->target_lang( $target_lang ), TAXONAME );
						$thelangs['msgstrlangs'][ $target_lang ]['msgstr'] = $msgstr_post_id;
						update_post_meta( $id, $this->msglang_meta, $thelangs );
						update_post_meta( $msgstr_post_id, $this->msgidlang_meta, $id );

						sprintf( 'msgstr created in %s <br/>', $target_lang );

					} else {
						// create msgstr_0
						/* translators: */
						$msgstr_post_id = $this->insert_one_cpt_and_meta( sprintf( esc_html__( 'XD say to translate in %s (msgstr[0] ): ', 'xili-dictionary' ), $target_lang ) . ' ' . $post->post_content, null, 'msgstr_0', 0 );
						wp_set_object_terms( $msgstr_post_id, $this->target_lang( $target_lang ), TAXONAME );
						$thelangs['msgstrlangs'][ $target_lang ]['msgstr_0'] = $msgstr_post_id;
						update_post_meta( $id, $this->msglang_meta, $thelangs );
						update_post_meta( $msgstr_post_id, $this->msgidlang_meta, $id );

						sprintf( 'msgstr[0] created in %s <br/>', $target_lang );

						// create msgstr_1
						/* translators: */
						$msgstr_1_post_id = $this->insert_one_cpt_and_meta( sprintf( esc_html__( 'XD say to translate in %s (msgstr[1] ): ', 'xili-dictionary' ), $target_lang ) . ' ' . $content_plural, null, 'msgstr_1',  $msgstr_post_id );
						wp_set_object_terms( $msgstr_1_post_id, $this->target_lang( $target_lang ), TAXONAME );
						$thelangs['msgstrlangs'][ $target_lang ]['plural'][1] = $msgstr_1_post_id;
						update_post_meta( $id, $this->msglang_meta, $thelangs );
						update_post_meta( $msgstr_1_post_id, $this->msgidlang_meta, $msgid_id );

						sprintf( 'msgstr[1] created in %s <br/>', $target_lang );
					}
				} elseif ( 'msgid_plural' == $_GET['msgaction'] && ! isset( $thelangs['msgstrlangs'][ $target_lang ] ) ) {

					$msgid_plural_post_id = $this->insert_one_cpt_and_meta( esc_html__( 'XD say id to plural: ', 'xili-dictionary' ) . $post->post_content, null, 'msgid_plural', $id );

				}
			}
			$listlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
			if ( isset( $thelangs['msgstrlangs'] ) ) {
				//$thelangs['msgstrlangs'][ $curlang]['msgstr'] = $msgstr_post_id;

				$translated_langs = array();

				foreach ( $thelangs['msgstrlangs'] as $curlang => $msgtr ) {

					$strid = 0;
					if ( isset( $msgtr['msgstr'] ) ) {
						$strid = $msgtr['msgstr'];
						$str_plural = false;

					} elseif ( isset( $msgtr['msgstr_0'] ) ) {
						$strid = $msgtr['msgstr_0'];
						$str_plural = true;

					}

					if ( 0 != $strid ) {
						if ( ! $display ) {
							// get strid status
							$post_status = get_post_status( $strid );
							$temp_post = $this->temp_get_post( $strid );
							if ( $temp_post ) { // 2.12.2
								$translated_langs[] = $curlang;
								echo ( esc_html__( 'translated in', 'xili-dictionary' ) . ':<br />' );
								if ( 'trash' == $post_status || false === $post_status ) {
									echo $spanred;
								}
								printf( '- %s : <a href="%s" >%d</a><br />', $curlang, 'post.php?post=' . $strid . '&action=edit', $strid );
								if ( 'trash' == $post_status || false === $post_status ) {
									echo $spanend;
								}
							}
						} else {
							echo '<br /><table class="widefat"><thead><tr><th class="column-msgtrans">';
							esc_html_e( 'translated in', 'xili-dictionary' );
							echo '</th><th>msgstr</th></tr></thead><tbody>';
							echo '<tr><th>';
							printf( '%s : ', $curlang );
							echo '</th><td>';
							$temp_post = $this->temp_get_post( $strid );
							$translated_langs[] = $curlang; // detect content empty
							$content = htmlspecialchars( $temp_post->post_content );

							if ( $str_plural ) {
								echo '[0] ';
							}

							echo '<strong>' . $content . '</strong>';
							$post_status = get_post_status( $strid );
							if ( 'trash' == $post_status || false === $post_status ) {
								echo $spanred;
							}
							printf( ' ( <a href="%1$s" title="link to:%2$d">%s</a> )<br />', 'post.php?post=' . $strid . '&action=edit', $strid, esc_html__( 'Edit' ) );
							if ( 'trash' == $post_status || false === $post_status ) {
								echo $spanend;
							}

							if ( $str_plural ) {
								$res = get_post_meta( $strid, $this->msgchild_meta, false );
								$strthechilds = ( is_array( $res ) && array() != $res ) ? $res[0] : array();
								foreach ( $strthechilds['msgstr']['plural'] as $key => $strchildid ) {
									$temp_post = $this->temp_get_post( $strchildid );
									$content = htmlspecialchars( $temp_post->post_content );
									$post_status = get_post_status( $strchildid );
									if ( 'trash' == $post_status || false === $post_status ) {
										echo $spanred;
									}
									printf( '[%s] ', $key );
									if ( 'trash' == $post_status || false === $post_status ) {
										echo $spanend;
									}
									printf( '<strong>%s</strong> ( %s )<br />', $content, '<a href="post.php?post=' . $strchildid.'&action=edit" title="link to:' . $strchildid . '">' . esc_html__( 'Edit' ) . '</a>' );

								}
								// if possible against current lang add links - compare to count of $strthechilds['msgstr']['plural']

							}
							echo '</td></tr>';
						}
					}
				} // end foreach
				if ( ! count( $translated_langs ) && ! $display ) {
					esc_html_e( 'not yet translated.', 'xili-dictionary' );
				}
				if ( $display ) {
					echo '</tbody></table>';
				}

				$this->create_line_lang = '';
				if ( $display && ( count( $translated_langs ) != count( $listlanguages ) ) ) {
					//echo '<br />';
					$this->create_line_lang = esc_html__( 'Create msgstr in: ', 'xili-dictionary' );
					foreach ( $listlanguages as $tolang ) {
						if ( ! in_array( $tolang->name, $translated_langs ) ) {
							$nonce_url = wp_nonce_url( 'post.php?post=' . $id . '&action=edit&msgaction=msgstr&langstr=' . $tolang->name, 'xd-langstr' );
							$this->create_line_lang .= sprintf( '&nbsp;<a href="%s" >' . $tolang->name . '</a>', $nonce_url );
						}
					}
				}
			} else { // no translation
				if ( ! isset( $_POST['msgaction'] ) || ( isset( $_GET['msgaction'] ) && 'msgid_plural' == $_GET['msgaction'] ) ) {
					esc_html_e( 'not yet translated.', 'xili-dictionary' );
					echo '&nbsp';
					if ( $display ) {
						esc_html_e( 'Create msgstr in: ', 'xili-dictionary' );

						foreach ( $listlanguages as $tolang ) {
							$nonce_url = wp_nonce_url( 'post.php?post=' . $id . '&action=edit&msgaction=msgstr&langstr=' . $tolang->name, 'xd-langstr' );
							printf( '&nbsp;<a href="%s" >' . $tolang->name . '</a>', $nonce_url );
						}
					}
				}
			}
		} elseif ( '' != $type ) {

			$msgid_id = get_post_meta( $id, $this->msgidlang_meta, true );

			if ( $display && ( 'msgid_plural' == $type || ( false !== strpos( $type, 'msgstr_' ) && '0' != substr( $type, -1 ) ) ) ) {
				$temp_post = $this->temp_get_post( $post->post_parent );
				$content = htmlspecialchars( $temp_post->post_content );
				$target_lang = implode( ' ', wp_get_object_terms( $id, TAXONAME, array( 'fields' => 'names' ) ) );
				$is_plural = true;
			} elseif ( $display ) {
				$temp_post = $this->temp_get_post( $msgid_id );
				$content = htmlspecialchars( $temp_post->post_content );
				$target_lang = implode( ' ', wp_get_object_terms( $id, TAXONAME, array( 'fields' => 'names' ) ) );
				$is_plural = false;
			}

			$span_msgid = ( 'trash' == get_post_status( $msgid_id ) || false === get_post_status( $msgid_id ) );
			$span_parent = ( 'trash' == get_post_status( $post->post_parent ) || false === get_post_status( $post->post_parent ) );

			if ( $display ) {
				echo '<div class="msg-saved" >';
				/* translators: */
				printf( esc_html__( '%1$s saved as: <em>%2$s</em>', 'xili-dictionary' ), $this->msg_str_labels[ $type ], $post->post_content );
				echo '</div>';
			}

			switch ( $type ) {
				case 'msgid_plural':
					if ( $span_parent ) {
						echo $spanred;
					}
					if ( $display ) {
						/* translators: */
						printf( __( 'msgid plural of: <strong>%s</strong> ( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary' ), $content, 'post.php?post=' . $post->post_parent . '&action=edit', $post->post_parent, esc_html__( 'Edit' ) );
					} else {
						/* translators: */
						printf( __( 'msgid plural of: <a href="%s" >%d</a><br />', 'xili-dictionary' ),'post.php?post=' . $post->post_parent . '&action=edit', $post->post_parent );
					}
					if ( $span_parent ) {
						echo $spanend;
					}

					break;
				case 'msgstr':
					if ( $display ) {
						echo '<strong>' . $target_lang . '</strong> translation of: <strong>' . $content . '</strong> ';
					}
					if ( $span_msgid ) {
						echo $spanred;
					}
					if ( $display ) {
						printf( __( '( <a href="%1$s" title = "link of:%2$d">%3$s</a> )<br />', 'xili-dictionary' ), 'post.php?post=' . $msgid_id . '&action=edit', $msgid_id, esc_html__( 'Edit' ) );
					} else {
						/* translators: */
						printf( __( 'msgstr of: <a href="%1$s" >%2$d</a><br />', 'xili-dictionary' ), 'post.php?post=' . $msgid_id . '&action=edit', $msgid_id );
					}
					if ( $span_msgid ) {
						echo $spanend;
					}

					break;

				default:
					if ( false !== strpos( $type, 'msgstr_' ) ) {
						$indices = explode( '_', $type );
						$indice = $indices[1];
						$edit_id = ( 0 == $indice ) ? $msgid_id : $post->post_parent;

						if ( $display ) {
							if ( $is_plural ) {
								/* translators: */
								printf( __( '<strong>%1$s</strong> plural of: <strong>%2$s</strong>( <a href="%3$s" title="link to:%4$d">%5$s</a> )<br />', '' ), $target_lang, $content, 'post.php?post=' . $edit_id.'&action=edit', $edit_id, esc_html__( 'Edit' ) );
							} else {
								/* translators: */
								printf( __( '<strong>%1$s</strong> translation of: <strong>%2$s</strong>( <a href="%3$s" title="link to:%4$d">%5$s</a> )<br />', '' ), $target_lang, $content, 'post.php?post=' . $edit_id.'&action=edit', $edit_id, esc_html__( 'Edit' ) );
							}
						} else {
							if ( 0 == $indice ) {
								if ( $span_msgid ) {
									echo $spanred;
								}
								/* translators: */
								printf( __( 'msgstr of: <a href="%1$s" >%2$d</a><br />', 'xili-dictionary' ), 'post.php?post=' . $msgid_id . '&action=edit', $msgid_id );
								if ( $span_msgid ) {
									echo $spanend;
								}
							} else {
								if ( $span_parent ) {
									echo $spanred;
								}
								/* translators: */
								printf( __( 'msgstr[%1$d] plural of: <a href="%2$s" >%3$d</a><br />', 'xili-dictionary' ), $indice, 'post.php?post=' . $post->post_parent . '&action=edit', $post->post_parent );
								if ( $span_parent ) {
									echo $spanend;
								}
							}
						}
						if ( $display && $indice > 0 ) {
							/* translators: */
							printf( __( 'go to <a href="%1$s" title="link to:%2$d">msgid</a>', 'xili-dictionary' ), 'post.php?post=' . $msgid_id . '&action=edit', $msgid_id );
						}
					}
			}
		}
		return $type;
	}

	public function temp_get_post( $post_id ) {
		global $wpdb;
		$res = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post_id ) );
		if ( $res && ! is_wp_error( $res ) ) {
			return $res;
		} else {
			return false;
		}
	}

	/**
	 * unset autosave for msg
	 * @since 2.0
	 */
	public function auto_save_unsetting() {
		global $hook_suffix, $post;
		$type = '';
		if ( isset( $_GET['post_type'] ) ) {
			$type = $_GET['post_type'];
		}

		if ( ( 'post-new.php' == $hook_suffix && XDMSG == $type ) || ( 'post.php' == $hook_suffix && XDMSG == $post->post_type ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Reset values when theme was changed... updated by previous function
	 * @since 1.0.5
	 */
	public function xd_theme_switched( $theme ) {
		$this->xili_settings['langs_folder'] = 'unknown';
		/* to force future search in new theme */
		update_option( 'xili_dictionary_settings', $this->xili_settings );
	}

	/**
	 * @since 1.3.0 for js in tools list
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'datatables-v10' );
	}

	public function admin_enqueue_styles() {
		wp_enqueue_style( 'table_style-v10' ); // style of js table
	}

	public function admin_init() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		/* Register our script. Same as in XTT loaded before if active */
		wp_register_script( 'datatables-v10', XILIDICTIONARY_PLUGIN_URL . '/js/jquery.dataTables' . $suffix . '.js', array( 'jquery' ), XILIDICTIONARY_VER, true );

		wp_register_style( 'table_style-v10', XILIDICTIONARY_PLUGIN_URL . '/css/jquery.dataTables' . $suffix . '.css', array(), XILIDICTIONARY_VER, 'screen' );
	}

	/**
	 * add admin menu and associated pages
	 */
	public function dictionary_menus_pages() {

		$this->admin_menus(); // moved here from add_action
		$this->thehook = add_submenu_page( 'edit.php?post_type=' . XDMSG, esc_html__( 'Xili Dictionary', 'xili-dictionary' ), esc_html__( 'Tools, Files po mo', 'xili-dictionary' ), 'xili_dictionary_admin', 'dictionary_page', array( &$this, 'xili_dictionary_settings' ) );
		add_action( 'admin_head-' . $this->thehook, array( &$this, 'modify_menu_highlight' ) );
		add_action( 'load-' . $this->thehook, array( &$this, 'on_load_page' ) );

		add_action( 'admin_print_scripts-' . $this->thehook, array( &$this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_print_styles-' . $this->thehook, array( &$this, 'admin_enqueue_styles' ) );

		// Add to end of admin_menu action function
		global $submenu;
		$submenu[ 'edit.php?post_type=' . XDMSG ][5][0] = esc_html__( 'Msg list', 'xili-dictionary' ); // sub menu
		$submenu[ 'edit.php?post_type=' . XDMSG ][5][1] = 'edit_posts'; // sub menu
		$submenu[ 'edit.php?post_type=' . XDMSG ][5][2] = 'edit.php?post_type=xdmsg'; // added for first activation
		$post_type_object = get_post_type_object( XDMSG );
		$post_type_object->labels->name = esc_html__( 'XD Msg list', 'xili-dictionary' ); // title list screen

		$this->insert_news_pointer( 'xd_new_version' ); // pointer in menu for updated version
		add_action( 'admin_print_footer_scripts', array( &$this, 'print_the_pointers_js' ) );

	}

	public function on_load_page() {
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );

		add_meta_box( 'xili-dictionary-sidebox-style', esc_html__( 'XD settings', 'xili-dictionary' ), array( &$this, 'on_sidebox_settings_content' ), $this->thehook, 'side', 'low' ); // low to be at end 2.3.1
		add_meta_box( 'xili-dictionary-sidebox-info', esc_html__( 'Info', 'xili-dictionary' ), array( &$this, 'on_sidebox_info_content' ), $this->thehook, 'side', 'low' );
		add_meta_box( 'xili-dictionary-sidebox-mail', esc_html__( 'Mail & Support', 'xili-dictionary' ), array( &$this, 'on_sidebox_mail_content' ), $this->thehook, 'normal', 'low' );

	}

	/**
	 * Add action link(s) to plugins page
	 *
	 * @since 0.9.3
	 * @author MS
	 * @copyright Dion Hulse, http://dd32.id.au/wordpress-plugins/?configure-link and scripts@schloebe.de
	 */
	public function xilidict_filter_plugin_actions( $links, $file ) {
		static $this_plugin;

		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="' . $this->xd_settings_page . '">' . esc_html__( 'Settings' ) . '</a>';
			$links = array_merge( array( $settings_link ), $links ); // before other links
		}
		return $links;
	}

	public function init_textdomain() {
		/*multilingual for admin pages and menu*/

		if ( class_exists( 'xili_language' ) ) {
			global $xili_language;
			$langs_folder = $xili_language->xili_settings['langs_folder']; // set by override_load_textdomain filter
			if ( $this->xili_settings['langs_folder'] != $langs_folder ) {
				$this->xili_settings['langs_folder'] = $langs_folder;
				update_option( 'xili_dictionary_settings', $this->xili_settings );
			}
			if ( is_child_theme() ) {
				$parent_langs_folder = $xili_language->xili_settings['parent_langs_folder']; // set by override_load_textdomain filter
				if ( $this->xili_settings['parent_langs_folder'] != $parent_langs_folder ) {
					$this->xili_settings['parent_langs_folder'] = $parent_langs_folder;
					$this->parent_langs_folder = $parent_langs_folder;
					update_option( 'xili_dictionary_settings', $this->xili_settings );
				}
			}
		} else {
			if ( file_exists( $this->active_theme_directory ) ) {
				$this->find_files( $this->active_theme_directory, '/^.*\.(mo|po|pot)$/', array( &$this, 'searchpath' ) );
			}
			if ( is_child_theme() ) {
				if ( file_exists( get_template_directory() ) ) {
					$this->find_files( get_template_directory(), '/^.*\.(mo|po|pot)$/', array( &$this, 'searchpathparent' ) );
				}
			}
		}
	}

	/* call by findfiles */
	public function searchpath( $path, $filename ) {
		$langs_folder = str_replace( $this->active_theme_directory, '', $path ); // updated 1.2.0
		if ( $this->xili_settings['langs_folder'] != $langs_folder ) {
			$this->xili_settings['langs_folder'] = $langs_folder;
			$this->langs_folder = $langs_folder;
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
	}

	/* call by findfiles */
	public function searchpathparent( $path, $filename ) {
		$parent_langs_folder = str_replace( get_template_directory(), '', $path ); // updated 1.2.0
		if ( $this->xili_settings['parent_langs_folder'] != $parent_langs_folder ) {
			$this->xili_settings['parent_langs_folder'] = $parent_langs_folder;
			$this->parent_langs_folder = $parent_langs_folder;
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
	}

	public function xililanguage_state() {
		/* test if xili-language is present or was present */
		if ( class_exists( 'xili_language' ) ) {

			$this->xililanguage = 'isactive';

		} else {
			/* test if language taxonomy relationships are present */
			$xl_settings = get_option( 'xili_language_settings', false );
			if ( $xl_settings ) {
				$this->xililanguage = 'wasactive';
			} else {
				$this->xililanguage = 'neveractive';
			}
		}
	}

	/**
	 * @since 1.02
	 */
	public function fill_default_languages_list() {
		if ( 'neveractive' == $this->xililanguage || 'wasactive' == $this->xililanguage ) {

			if ( ! isset( $this->xili_settings['xl-dictionary-langs'] ) ) {

				$default_langs_array = array(
					'en_us' => array( 'en_US', 'english' ),
					'fr_fr' => array( 'fr_FR', 'french' ),
					'de_de' => array( 'de_DE', 'german' ),
					'es_es' => array( 'es_ES', 'spanish' ),
					'it_it' => array( 'it_IT', 'italian' ),
					'pt_pt' => array( 'pt_PT', 'portuguese' ),
					'ru_ru' => array( 'ru_RU', 'russian' ),
					'zh_cn' => array( 'zh_CN', 'chinese' ),
					'ja' => array( 'ja', 'japanese' ),
					'ar_ar' => array( 'ar_AR', 'arabic' ),
				);
				/* add wp admin lang */
				if ( $this->get_wplang() ) {
					$lkey = $this->get_wplang();
					if ( ! array_key_exists( $lkey, $default_langs_array ) ) {
						$default_langs_array[ $lkey ] = array( $this->get_wplang(), $this->get_wplang() );
					}
				}
				$this->xili_settings['xl-dictionary-langs'] = $default_langs_array;
				update_option( 'xili_dictionary_settings', $this->xili_settings );
			}
		}
	}

	/**
	 * for slug with 5 (_fr_fr) or 2 letters (as japanese)
	 *
	 */
	public function extract_extend( $line_slug ) {
		$end = substr( $line_slug, -6 );
		if ( '_' == substr( $end, 0, 1 ) && '_' == substr( $end, 3, 1 ) ) {
			return substr( $line_slug, -5 );
		} else {
			return substr( $line_slug, -2 ); // as ja
		}
	}

	/**
	 * 2.3.2
	 *
	 */
	public function theme_domain() {

		if ( function_exists( 'the_theme_domain' ) ) { // xili-language
			return the_theme_domain();
		} else {
			return get_option( 'template' ); // child same as parent domain
		}
	}


	public function get_option_theme_full_name( $child_of = false ) {
		$current_theme_obj = wp_get_theme();
		if ( is_child_theme() ) { // 1.8.1 and WP 3.0
			$parent_theme_obj = wp_get_theme( get_option( 'template' ) );
			$theme_name = $current_theme_obj->get( 'Name' );
			if ( $child_of ) {
				$theme_name = $current_theme_obj->get( 'Name' ) . ' ' . esc_html__( 'child of', 'xili-dictionary' ) . ' ' . $parent_theme_obj->get( 'Name' ); //replace slug of theme
			}
		} else {
			$theme_name = $current_theme_obj->get( 'Name' ); // get_option("current_theme"); // name of theme
		}
		return $theme_name;
	}


	/**
	 * @since 2.1
	 * built checked themes array
	 *
	 */
	public function checked_themes_array() {
		$checked_themes = array();
		$listterms = get_terms( 'origin', array( 'hide_empty' => false ) );
		if ( $listterms ) {

			foreach ( $listterms as $onetheme ) {
				$idcheck = 'theme-' . $onetheme->term_id;
				if ( isset( $_POST[ $idcheck ] ) ) {
					$checked_themes[] = $onetheme->name;
				}
			}
		}
		return $checked_themes;
	}




	/**
	 * build the list of group of languages for dictionary
	 *
	 * @updated 1.0.2
	 *
	 */
	public function build_grouplist( $left_line = '' ) {

		$listdictlanguages = $this->get_terms_of_groups_lite( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array( 'hide_empty' => false) );
		$optionlist = '';
		$lefttext = '';
		if ( 'nottransin_' == $left_line ) {
			$lefttext = esc_html__( 'not translated in', 'xili-dictionary' ) . ' ';
		}
		foreach ( $listdictlanguages as $dictlanguage ) {
			$checked = ( $this->subselect == $left_line . $dictlanguage->slug ) ? 'selected="selected"' : '';
		$optionlist .= '<option value="' . $left_line . $dictlanguage->slug . '" ' . $checked . ' >' . $lefttext . $dictlanguage->name . ' ( ' . $dictlanguage->description . ' )</option>';
		}
		return $optionlist;
	}


	/**
	 * ? obsolete ?
	 * @return full folder
	 */
	public function get_langfolder() {
		$xili_settings = get_option( 'xili_dictionary_settings' );
		$langfolderset = $xili_settings['langs_folder'];
		$full_folder = ( '' != $langfolderset ) ? $langfolderset . '/' : '/';
		return $full_folder;
	}


	/**
	 * Recursive search of files in a path
	 * @since 1.0
	 *
	 */
	public function find_files( $path, $pattern, $callback, $param = null ) {
		//$path = rtrim(str_replace("\\", '/', $path), '/' ) . '/';
		$matches = array();
		$entries = array();
		$dir = dir( $path );
		while ( false !== ( $entry = $dir->read() ) ) {
			$entries[] = $entry;
		}
		$dir->close();
		foreach ( $entries as $entry ) {
			$fullname = $path . $this->ossep . $entry;
			if ( '.' != $entry && '..' != $entry && is_dir( $fullname ) ) {
				$this->find_files( $fullname, $pattern, $callback, $param );
			} elseif ( is_file( $fullname ) && preg_match( $pattern, $entry ) ) {
				if ( $param ) {
					call_user_func( $callback, $path, $entry, $param );
				} else {
					call_user_func( $callback, $path, $entry );
				}
			}
		}
	}


	// with xili-language, it is now possible to export/import xml with language
	public function message_export_limited() {
		echo '<div class="error"><p>' . esc_html__( 'WARNING: xili-dictionary datas are not ready to be imported from XML generated here. <br />So, it is fully recommended to <strong>desactivate temporarily xili-dictionary</strong> during export and so, avoid clutter of the file.', 'xili-dictionary' ) . '</p>'
		. '<p>' . esc_html__( 'To export/import/backup dictionary datas, use .po files built by the plugin.', 'xili-dictionary' ) . '</p></div>';
	}



	/**** Functions that improve taxinomy.php - avoid conflict if xl absent ****/
	public function get_terms_of_groups_lite( $group_ids, $taxonomy, $taxonomy_child, $order = '' ) {
		global $wpdb;
		if ( ! is_array( $group_ids ) ) {
			$group_ids = array( $group_ids );
		}
		$group_ids = array_map( 'intval', $group_ids );
		$group_ids = implode( ', ', $group_ids );
		$theorderby = '';

		// lite release
		if ( 'ASC' == $order || 'DESC' == $order ) {
			$theorderby = ' ORDER BY tr.term_order ' . $order;
		}

		$query = "SELECT t.*, tt2.term_taxonomy_id, tt2.description,tt2.parent, tt2.count, tt2.taxonomy, tr.term_order FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ( '" . $taxonomy . "' ) AND tt2.taxonomy = '" . $taxonomy_child . "' AND tt.term_id IN (" . $group_ids . ") ". $theorderby;

		$listterms = $wpdb->get_results( $query );
		if ( ! $listterms ) {
			return array();
		}

		return apply_filters( 'other_multilingual_plugin_filter_terms', $listterms );
	}

	// used onetime from v2 to v2.1
	public function update_postmeta_msgid() {
		global $wpdb;
		// scan msgid
		$query = sprintf(
			"SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_type = '%s'
			AND $wpdb->posts.ID NOT IN
			( SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '%s' )
			ORDER BY $wpdb->posts.ID ASC",
			XDMSG,
			$this->msglang_meta
		);
		$listposts = $wpdb->get_results( $query, ARRAY_A );
		// test if no postmeta lang
		$i = 0;
		if ( $listposts ) {
			foreach ( $listposts as $onepost ) {
				if ( 'msgid' == get_post_meta( $onepost['ID'], $this->msgtype_meta, true ) ) {
					// update
					update_post_meta( $onepost['ID'], $this->msglang_meta, array() );
					++$i;
				}
			}
		}
		xili_xd_error_log( '# ' . __LINE__ . ' XD MSG - Updated 2.0->2.1 ' . count( $i ) );
	}

	/****************** NEW ADMIN UI *******************/
	/**
	* @since 2.3
	*/
	public function admin_menus() {

		$hooks = array();

		$hooks[] = add_submenu_page(
			'edit.php?post_type=' . XDMSG,
			esc_html__( 'Erasing', 'xili-dictionary' ),
			esc_html__( 'Erase', 'xili-dictionary' ),
			'xili_dictionary_admin',
			'erase_dictionary_page',
			array( &$this, 'xili_dictionary_erase' )
		);
		$hooks[] = add_submenu_page(
			'edit.php?post_type=' . XDMSG,
			esc_html__( 'Importing files', 'xili-dictionary' ),
			esc_html__( 'Import', 'xili-dictionary' ),
			'xili_dictionary_admin',
			'import_dictionary_page',
			array( &$this, 'xili_dictionary_import' )
		);
		$hooks[] = add_submenu_page(
			'edit.php?post_type=' . XDMSG,
			esc_html__( 'Downloading files to your computer', 'xili-dictionary' ),
			esc_html__( 'Download', 'xili-dictionary' ),
			'xili_dictionary_admin',
			'download_dictionary_page',
			array( &$this, 'xili_dictionary_download' )
		);

		// Fudge the highlighted subnav item when on a XD admin page
		foreach ( $hooks as $hook ) {
			add_action( "admin_head-$hook", array( &$this, 'modify_menu_highlight' ) );
		}

	}
	public function admin_sub_menus_hide() {

		remove_submenu_page( 'edit.php?post_type=' . XDMSG, 'erase_dictionary_page' );
		remove_submenu_page( 'edit.php?post_type=' . XDMSG, 'import_dictionary_page' );
		remove_submenu_page( 'edit.php?post_type=' . XDMSG, 'download_dictionary_page' );

	}

	public function modify_menu_highlight() {
		global $plugin_page, $submenu_file;

		// This tweaks the Tools subnav menu to only show one XD menu item
		if ( in_array( $plugin_page, array( 'erase_dictionary_page', 'import_dictionary_page', 'download_dictionary_page' ) ) ) {
			$submenu_file = 'dictionary_page';
		}
	}


} /* end of class */
