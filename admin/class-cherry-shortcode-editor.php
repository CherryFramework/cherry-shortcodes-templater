<?php
/**
 * Cherry Shortcodes Templater.
 *
 * @package   Cherry_Shortcodes_Templater_Admin
 * @author    Cherry Team
 * @license   GPL-3.0+
 * @copyright 2012 - 2015, Cherry Team
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Cherry_Shortcode_Editor' ) ) {

	/**
	 * Shortcode Editor.
	 *
	 * @since 1.0.0
	 */
	class Cherry_Shortcode_Editor {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Slug of the page screen.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		private $page_screen_hook_suffix = null;

		/**
		 * The URL to which the form should be submitted.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		private $form_url = null;

		/**
		 * The target folder path.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		public $target_dir_path = null;

		/**
		 * Sets up needed actions/filters for the class to initialize.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$plugin                = Cherry_Shortcodes_Templater::get_instance();
			$this->plugin_slug     = $plugin->get_plugin_slug();
			$this->target_dir_path = CHERRY_TEMPLATER_UPLOAD_DIR . Cherry_Shortcodes_Templater::$dir_name;

			if ( false == Cherry_Shortcodes_Templater::template_dir() ) {
				add_action( 'admin_notices', array( $this, 'admin_notice' ) );
				return;
			}

			/**
			 * Default templates.
			 *
			 * Dafault templates structure.
			 *
			 * @since 1.0.0
			 *
			 * array(
			 * 	'shortcode_name_1' => array(
			 *  	'location_name' => array(
			 *  		'item-*' => array(
			 *  			'dir'  => '',
			 *  			'path' => '',
			 *  		)
			 *  	)
			 * 	),
			 * 	'shortcode_name_2' => array(...),
			 * )
			 */

			// Enqueue admin-specific stylesheet.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 99 );

			// Add the options page and menu item.
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 11 );
		}

		/**
		 * Show notice if a target directory is not writable.
		 *
		 * @since 1.0.0
		 */
		public function admin_notice() {
			echo '<div class="updated"><p>' . sprintf( __( 'Sorry, but target directory (<strong>%1$s</strong>) is not created. Maybe <strong>%2$s</strong> the directory is not writable.', 'cherry-shortcodes-templater' ), $this->target_dir_path, 'uploads' ) . '</p></div>';
		}

		/**
		 * Retrieve the target directories.
		 *
		 * @since  1.0.0
		 * @return array
		 */
		public static function get_the_dirs() {
			return apply_filters( 'cherry_templater_target_dirs', array( CHERRY_TEMPLATER_UPLOAD_DIR ) );
		}

		/**
		 * Enqueue admin-specific stylesheet.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_admin_scripts() {

			if ( ! isset( $this->page_screen_hook_suffix ) ) {
				return;
			}

			$screen = get_current_screen();

			if ( $this->page_screen_hook_suffix != $screen->id ) {
				return;
			}

			$default_templates  = self::get_the_files( self::get_the_dirs() );
			$allowed_shortcodes = array_keys( $default_templates );

			foreach ( $allowed_shortcodes as $k => $tag ) {

				if ( ! shortcode_exists( $tag ) ) {
					if ( ! shortcode_exists( 'cherry_' . $tag ) ) {
						unset( $allowed_shortcodes[ $k ] );
					}
				}
			}

			// Rebase array keys after unsetting elements.
			$allowed_shortcodes = array_values( $allowed_shortcodes );

			if ( isset( $_GET['file'] ) ) {
				$file      = sanitize_text_field( $_GET['file'] );
				$shortcode = wp_basename( dirname( $file ) );
			} else {
				reset( $allowed_shortcodes );
				$shortcode = current( $allowed_shortcodes );
			}

			$active = array_search( $shortcode, $allowed_shortcodes );

			if ( false === $active ) {
				$active = 0;
			}

			wp_dequeue_style( 'jquery-ui' );
			wp_register_style( 'cherry-ui-elements', plugins_url( 'assets/css/cherry-ui-elements.css', __FILE__ ) );
			wp_register_style( $this->plugin_slug . '-admin-style', plugins_url( 'assets/css/editor.css', __FILE__ ), array( 'cherry-ui-elements' ), CHERRY_TEMPLATER_VERSION );
			wp_register_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/editor.min.js', __FILE__ ), array( 'jquery', 'jquery-ui-accordion', 'jquery-ui-tooltip', 'quicktags' ), CHERRY_TEMPLATER_VERSION, true );

			wp_enqueue_style( $this->plugin_slug . '-admin-style' );
			wp_enqueue_script( $this->plugin_slug . '-admin-script' );

			/**
			 * Filters a `macros buttons` array.
			 *
			 * @since 1.0.0
			 * @param array  $macros_buttons
			 * @param string $shortcode      Shortcode name.
			 */
			$macros_buttons = apply_filters( 'cherry_templater_macros_buttons', array(
					'title' => array(
						'id'    => 'cherry_title',
						'value' => __( 'Title', 'cherry-shortcodes-templater' ),
						'open'  => '%%TITLE%%',
						'close' => '',
					),
					'image' => array(
						'id'    => 'cherry_image',
						'value' => __( 'Image', 'cherry-shortcodes-templater' ),
						'open'  => '%%IMAGE%%',
						'close' => '',
					),
					'content' => array(
						'id'    => 'cherry_content',
						'value' => __( 'Content', 'cherry-shortcodes-templater' ),
						'open'  => '%%CONTENT%%',
						'close' => '',
					),
					'button' => array(
						'id'    => 'cherry_button',
						'value' => __( 'Button', 'cherry-shortcodes-templater' ),
						'open'  => '%%BUTTON="btn btn-default"%%',
						'close' => '',
					),
					'permalink' => array(
						'id'    => 'cherry_permalink',
						'value' => __( 'Permalink', 'cherry-shortcodes-templater' ),
						'open'  => '%%PERMALINK%%',
						'close' => '',
					),
				), $shortcode );

			wp_localize_script( $this->plugin_slug . '-admin-script', 'macrosButtons', $macros_buttons );
			wp_localize_script( $this->plugin_slug . '-admin-script', 'activeAcc', (string) $active );
		}

		/**
		 * Register the administration menu into the WordPress Dashboard menu.
		 *
		 * @since 1.0.0
		 */
		public function add_admin_menu() {

			if ( class_exists( 'Cherry_Framework' ) ) {
				$parent_slug    = 'cherry';
				$this->form_url = 'admin.php?page=' . $this->plugin_slug;
			} else {
				$parent_slug    = 'themes.php';
				$this->form_url = 'themes.php?page=' . $this->plugin_slug;
			}

			$this->page_screen_hook_suffix = add_submenu_page(
				$parent_slug,
				__( 'Shortcodes Templater', 'cherry-shortcodes-templater' ),
				__( 'Shortcodes Templater', 'cherry-shortcodes-templater' ),
				'edit_theme_options',
				$this->plugin_slug,
				array( $this, '_display_admin_page' )
			);
		}

		/**
		 * Render the editor page.
		 *
		 * @since 1.0.0
		 */
		public function _display_admin_page() {
			add_filter( 'the_editor', array( $this, 'add_save_button' ) );
			include_once( 'views/editor-views.php' );
		}

		/**
		 * Add a `save` button.
		 *
		 * @since  1.0.0
		 * @param  string $editor_html Editor's HTML markup.
		 * @return string
		 */
		public function add_save_button( $editor_html ) {

			if ( ! is_writable( CHERRY_TEMPLATER_UPLOAD_DIR ) ) {

				$text = '<p><em>' . __( 'You need to make this file writable to save your changes. See <a href="http://codex.wordpress.org/Changing_File_Permissions">the Codex</a> for more information.', 'cherry-shortcodes-templater' ) . '</em></p>';

			} else {

				// Get a `location` value.
				if ( isset( $_GET['location'] ) ) {
					$location = sanitize_text_field( $_GET['location'] );
				} elseif ( isset( $_POST['location'] ) ) {
					$location = sanitize_text_field( $_POST['location'] );
				} else {
					$location = '';
				}

				// Get uploads directory name.
				$upload_name = sanitize_file_name( wp_basename( CHERRY_TEMPLATER_UPLOAD_DIR ) );

				if ( $upload_name === $location ) {
					$text = '<input type="submit" name="save" id="save" class="button_ button-primary_" value="' . __( 'Save', 'cherry-shortcodes-templater' ) .'">';
				} else {
					$text = '<input type="submit" name="copy" id="copy" class="button_ button-primary_" value="' . __( 'Duplicate', 'cherry-shortcodes-templater' ) .'">';
				}
			}

			$editor_html = str_replace( '</textarea>', '</textarea>' . $text, $editor_html );

			return $editor_html;
		}

		/**
		 * Return files in the target's directories.
		 *
		 * @since  1.0.0
		 * @param  array|string $targer_dirs A target directories.
		 * @param  array|string $types       Optional. Array of extensions to return. Defaults to *.tmpl files.
		 * @param  int          $depth       Optional. How deep to search for files. Defaults to -1 depth is infinite.
		 * @return array                     Array of files
		 */
		public function get_the_files( $targer_dirs, $types = 'tmpl', $depth = -1 ) {
			$files = array();

			foreach ( (array) $targer_dirs as $dir ) {
				$_files = (array) $this->scandir( $dir, $types, $depth );

				if ( ! empty( $_files ) ) {
					$f     = $this->set_allowed_data( $_files, basename( $dir ) );
					$files = array_merge_recursive( $files, $f );
				}
			}

			return $files;
		}

		/**
		 * Scans a directory for files of a certain extension.
		 *
		 * @since  1.0.0
		 * @param  string $path          Absolute path to search.
		 * @param  mixed  $types         Array of extensions to find, string of a single extension, or null for all extensions.
		 * @param  int    $depth         How deep to search for files. Optional, defaults to -1 depth is infinite.
		 * @param  string $relative_path The basename of the absolute path. Used to control the returned path for the found files, particularly when this function recurses to lower depths.
		 * @return array
		 */
		public function scandir( $path, $types, $depth, $relative_path = '' ) {
			if ( ! is_dir( $path ) ) {
				return false;
			}

			$path = untrailingslashit( $path );

			if ( $types ) {
				$types  = (array) $types;
				$_types = implode( '|', $types );
			}

			$relative_path = trailingslashit( $relative_path );

			if ( '/' == $relative_path ) {
				$relative_path = '';
			}

			$results = scandir( $path );
			$files   = array();

			foreach ( $results as $result ) :

				if ( '.' == $result[0] ) {
					continue;
				}

				if ( is_dir( $path . '/' . $result ) ) {

					if ( ! $depth || 'CVS' == $result ) {
						continue;
					}

					$found = $this->scandir( $path . '/' . $result, $types, $depth - 1 , $relative_path . $result );
					$files = array_merge_recursive( $files, $found );

				} elseif ( ! $types || preg_match( '~\.(' . $_types . ')$~', $result ) ) {

					$files[ $relative_path . $result ] = $path . '/' . $result;

				}

			endforeach;

			return $files;
		}

		/**
		 * To prepare allowed data.
		 *
		 * @since  1.0.0
		 * @param  array  $files Set of files data.
		 * @param  string $dir   Directory name.
		 * @return array
		 */
		public function set_allowed_data( $files, $dir ) {
			$count = 0;
			$_new = array();

			foreach ( $files as $tag => $path ) {

				$shortcode_tag = wp_basename( dirname( $tag ) );
				$template      = wp_basename( $tag );
				$key           = 'item-' . $count;

				if ( 'default.tmpl' == $template ) {
					$key = 'default';
				}

				$_new = array_merge_recursive( $_new, array(
					$shortcode_tag => array(
						$dir => array(
							$key => array(
								'dir'  => dirname( $tag ),
								'path' => $path,
							),
						),
					),
				) );

				$count++;
			}

			return $_new;
		}

		/**
		 * Initialize Filesystem object.
		 *
		 * @since  1.0.0
		 * @param  string $form_url URL to POST the form to.
		 * @param  array  $fields   Form fields.
		 * @return bool|str         false on failure, stored text on success
		 */
		public function filesystem_init( $form_url, $fields = null ) {
			global $wp_filesystem;

			// First attempt to get credentials.
			if ( false === ( $creds = request_filesystem_credentials( $form_url, '', false, $this->target_dir_path, $fields ) ) ) {
				/**
				 * If we comes here - we don't have credentials
				 * so the request for them is displaying
				 * no need for further processing.
				 */
				return false;
			}

			// Now we got some credentials - try to use them.
			if ( ! WP_Filesystem( $creds ) ) {

				// Incorrect connection data - ask for credentials again, now with error message.
				request_filesystem_credentials( $form_url, '', true, $this->target_dir_path );

				return false;
			}

			$this->target_dir_path = $wp_filesystem->find_folder( $this->target_dir_path );

			return true; // Filesystem object successfully initiated.
		}

		/**
		 * Perform writing into template.
		 *
		 * @since  1.0.0
		 * @return bool|str - false on failure, stored text on success.
		 */
		public function filesystem_write( $template ) {
			global $wp_filesystem;

			check_admin_referer( 'shortcode_templates_editor_admin', 'wp_nonce_field_editor' );

			$form_url    = $this->form_url;
			$form_url    = wp_nonce_url( $form_url, 'shortcode_templates_editor_admin' );

			// Fields that should be preserved across screens.
			$form_fields = array( 'shortcode-template' );

			if ( ! $this->filesystem_init( $form_url, $form_fields ) ) {
				return false;
			}

			// Sanitize the input.
			$content = wp_unslash( $_POST['shortcode-template'] );

			// Write into file.
			if ( ! $wp_filesystem->put_contents( $template, $content, FS_CHMOD_FILE ) ) {

				// Return error object.
				return new WP_Error( 'writing_error', 'Error when writing file' );
			}

			return $template;
		}

		/**
		 * Read template.
		 *
		 * @since  1.0.0
		 * @return bool|str - false on failure, stored text on success.
		 */
		public function filesystem_read( $template ) {
			global $wp_filesystem;

			$form_url = $this->form_url;
			$form_url = wp_nonce_url( $form_url, 'shortcode_templates_editor_admin' );

			if ( ! $this->filesystem_init( $form_url ) ) {
				return false;
			}

			$content = '';

			// Read the file.
			if ( $wp_filesystem->exists( $template ) ) :

				$content = $wp_filesystem->get_contents( $template );

				if ( ! $content ) {

					// Return error object.
					return new WP_Error( 'reading_error', 'Error when reading file' );
				}

			endif;

			return $content;
		}

		/**
		 * Read template (static).
		 *
		 * @since  1.0.0
		 * @return bool|WP_Error|string - false on failure, stored text on success.
		 */
		public static function get_contents( $template ) {

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				include_once( ABSPATH . '/wp-admin/includes/file.php' );
			}

			WP_Filesystem();
			global $wp_filesystem;

			if ( ! $wp_filesystem->exists( $template ) ) {
				return false;
			}

			// Read the file.
			$content = $wp_filesystem->get_contents( $template );

			if ( ! $content ) {

				// Return error object.
				return new WP_Error( 'reading_error', 'Error when reading file' );
			}

			return $content;
		}

		/**
		 * Get files in a directory.
		 *
		 * @since  1.0.0
		 * @param  string $shortcode Shortcode tag name.
		 * @return array|bool Array of files. False if unable to list directory contents.
		 */
		public static function dirlist( $shortcode ) {

			if ( ! function_exists( 'WP_Filesystem' ) ) {
				include_once( ABSPATH . '/wp-admin/includes/file.php' );
			}

			WP_Filesystem();
			global $wp_filesystem;

			// Get all target directories.
			$dirs = self::get_the_dirs();

			// Flag necessary template.
			$necessary_template = false;

			foreach ( $dirs as $d ) :
				if ( file_exists( trailingslashit( $d ) . Cherry_Shortcodes_Templater::$dir_name . $shortcode ) ) {
					$necessary_template = true;
				}
			endforeach;

			if ( ! $necessary_template ) {
				return false;
			}

			// Prepare arrays.
			$dirlist = array();

			foreach ( $dirs as $d ) :

				$dir = trailingslashit( $d ) . Cherry_Shortcodes_Templater::$dir_name . $shortcode;

				if ( ! file_exists( $dir ) ) {
					continue;
				}

				// Get details for files in a directory.
				$list = $wp_filesystem->dirlist( $dir );

				if ( ! $list ) {
					continue;
				}

				// Pluck a certain field out of each object in a list.
				$list = wp_list_pluck( $list, 'name' );

				foreach ( $list as $file_name => $data ) {
					$list[ $file_name ] = $data;
				}

				$dirlist = array_merge_recursive( $dirlist, $list );

			endforeach;

			if ( empty( $dirlist ) ) {
				return false;
			}

			return $dirlist;
		}

		/**
		 * The function for make a copy template.
		 *
		 * @since 1.0.0
		 */
		public function copy( $source, $destination ) {
			global $wp_filesystem;

			check_admin_referer( 'shortcode_templates_editor_admin', 'wp_nonce_field_editor' );

			$copied   = false;
			$form_url = wp_nonce_url( $this->form_url, 'shortcode_templates_editor_admin' );

			if ( ! $this->filesystem_init( $form_url ) ) {
				return false;
			}

			$copied = $wp_filesystem->copy( $source, $destination );

			return $copied;
		}

		/**
		 * Delete a file or directory.
		 *
		 * @since 1.0.0
		 * @param string $file      Path to the file.
		 * @param bool   $recursive Optional. If set True changes file group recursively. Defaults to False.
		 *                          Default false.
		 * @param bool   $type      Type of resource. 'f' for file, 'd' for directory.
		 *                          Default false.
		 * @return bool             True if the file or directory was deleted, false on failure.
		 */
		public function delete( $file, $recursive = false, $type = false ) {
			global $wp_filesystem;

			check_admin_referer( 'shortcode_templates_editor_admin', 'wp_nonce_field_editor' );

			$deleted  = false;
			$form_url = wp_nonce_url( $this->form_url, 'shortcode_templates_editor_admin' );

			if ( ! $this->filesystem_init( $form_url ) ) {
				return false;
			}

			$deleted = $wp_filesystem->delete( $file, $recursive, $type );

			return $deleted;
		}

		/**
		 * Rename a file.
		 *
		 * @since  1.0.0
		 * @param  string $file     Path to the file.
		 * @param  string $new_file Path to the new file.
		 * @return bool             True if the file was renamed, false on failure.
		 */
		public function rename( $file, $new_file ) {
			global $wp_filesystem;

			check_admin_referer( 'shortcode_templates_editor_admin', 'wp_nonce_field_dialog' );

			$form_url = wp_nonce_url( $this->form_url, 'shortcode_templates_editor_admin' );

			if ( ! $this->filesystem_init( $form_url ) ) {
				return false;
			}

			if ( false === $wp_filesystem->copy( $file, $new_file ) ) {
				return false;
			}

			if ( false === $wp_filesystem->delete( $file, false, 'f' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}
	}

	Cherry_Shortcode_Editor::get_instance();
}
