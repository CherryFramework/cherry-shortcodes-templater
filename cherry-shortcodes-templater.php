<?php
/**
 * Plugin Name: Cherry Shortcodes Templater
 * Plugin URI:  http://www.cherryframework.com/
 * Description: Extends a Cherry Shortcodes plugin.
 * Version:     1.0.2
 * Author:      Cherry Team
 * Author URI:  http://www.cherryframework.com/
 * Text Domain: cherry-shortcodes-templater
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 *
 * @package  Cherry Testimonials
 * @category Core
 * @author   Cherry Team
 * @license  GPL-3.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Cherry_Shortcodes_Templater' ) ) {

	/**
	 * Sets up and initializes the Cherry Shortcodes Templater plugin.
	 *
	 * @since 1.0.0
	 */
	class Cherry_Shortcodes_Templater {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Unique identifier.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		private $plugin_slug = 'cherry-shortcodes-templater';

		/**
		 * The target folder name.
		 *
		 * @since 1.0.0
		 * @var   string
		 */
		public static $dir_name = 'templates/shortcodes/';

		/**
		 * Sets up needed actions/filters for the plugin to initialize.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Set the constants needed by the plugin.
			$this->constants();

			// Internationalize the text strings used.
			add_action( 'plugins_loaded', array( $this, 'lang' ), 2 );

			// Load the includes.
			add_action( 'plugins_loaded', array( $this, 'includes' ), 3 );

			// Load the admin files.
			add_action( 'plugins_loaded', array( $this, 'admin' ), 4 );

			// Register activation and deactivation hook.
			register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
			register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
		}

		/**
		 * Defines constants for the plugin.
		 *
		 * @since 1.0.0
		 */
		public function constants() {

			/**
			 * Set the version number of the plugin.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_TEMPLATER_VERSION', '1.0.2' );

			/**
			 * Set the slug of the plugin.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_TEMPLATER_SLUG', basename( dirname( __FILE__ ) ) );

			/**
			 * Set constant path to the plugin directory.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_TEMPLATER_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );

			/**
			 * Set constant path to the plugin URI.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_TEMPLATER_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

			// Gets a uploads directory.
			$upload_dir = wp_upload_dir();

			/**
			 * Set constant path to the uploads directory.
			 *
			 * @since 1.0.0
			 */
			define( 'CHERRY_TEMPLATER_UPLOAD_DIR', trailingslashit( $upload_dir['basedir'] ) );
		}

		/**
		 * Loads the translation files.
		 *
		 * @since 1.0.0
		 */
		public function lang() {
			$domain = $this->plugin_slug;
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

			load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $domain, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
		}

		/**
		 * Loads includes.
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			require_once( CHERRY_TEMPLATER_DIR . 'includes/class-cherry-shortcode-templates.php' );
		}

		/**
		 * Loads admin files.
		 *
		 * @since 1.0.0
		 */
		public function admin() {
			if ( is_admin() ) {
				require_once( CHERRY_TEMPLATER_DIR . 'admin/class-cherry-shortcode-editor.php' );
				require_once( CHERRY_TEMPLATER_DIR . 'admin/includes/class-cherry-update/class-cherry-plugin-update.php' );

				$Cherry_Plugin_Update = new Cherry_Plugin_Update();
				$Cherry_Plugin_Update->init( array(
					'version'         => CHERRY_TEMPLATER_VERSION,
					'slug'            => CHERRY_TEMPLATER_SLUG,
					'repository_name' => CHERRY_TEMPLATER_SLUG,
				) );
			}
		}

		/**
		 * On plugin activation.
		 *
		 * @since 1.0.0
		 */
		public static function activate() {
			self::template_dir();

			do_action( 'cherry_templater_activate' );
		}

		/**
		 * On plugin deactivation.
		 *
		 * @since 1.0.0
		 */
		public static function deactivate() {
			do_action( 'cherry_templater_deactivate' );
		}

		/**
		 * Recursive directory creation based on full path.
		 *
		 * @since 1.0.0
		 * @return bool Whether the path was created or not. True if path already exists.
		 */
		public static function template_dir() {
			$upload_dir = wp_upload_dir();
			$path       = trailingslashit( path_join( $upload_dir['basedir'], self::$dir_name ) );

			return wp_mkdir_p( $path );
		}

		/**
		 * Return the plugin slug.
		 *
		 * @since  1.0.0
		 * @return Plugin slug variable.
		 */
		public function get_plugin_slug() {
			return $this->plugin_slug;
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

	Cherry_Shortcodes_Templater::get_instance();
}
