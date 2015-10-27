<?php
/**
 * Cherry Shortcodes Templater.
 *
 * @package   Cherry_Shortcodes_Templater
 * @author    Cherry Team
 * @license   GPL-3.0+
 * @copyright 2012 - 2015, Cherry Team
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Cherry_Shortcode_Templates' ) ) {

	/**
	 * Shortcode Templates.
	 *
	 * @since 1.0.0
	 */
	class Cherry_Shortcode_Templates {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since 1.0.0
		 * @var   object
		 */
		private static $instance = null;

		/**
		 * Sets up needed actions/filters for the class to initialize.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			// Filters a shortcode's data.
			add_filter( 'cherry_shortcodes/data/shortcodes', array( $this, 'add_template_view' ), 20 );
		}

		/**
		 * Adds a `Template` view.
		 *
		 * @since 1.0.0
		 */
		public function add_template_view( $shortcodes ) {
			$shortcode = ( ! empty( $_REQUEST['shortcode'] ) ) ? sanitize_key( $_REQUEST['shortcode'] ) : '';

			if ( empty( $shortcode ) ) {
				return $shortcodes;
			}

			if ( ! isset( $shortcodes[ $shortcode ]['atts']['template'] ) ) {
				return $shortcodes;
			}

			// Get templates.
			$templates = Cherry_Shortcode_Editor::dirlist( $shortcode );

			if ( ! $templates || empty( $templates ) ) {
				return $shortcodes;
			}

			// Add new atts - template.
			$shortcodes[ $shortcode ]['atts']['template'] = array(
				'type'    => 'select',
				'values'  => $templates,
				'default' => 'default.tmpl',
				'name'    => __( 'Template', 'cherry-shortcodes-templater' ),
				'desc'    => __( 'Shortcode template', 'cherry-shortcodes-templater' ),
			);

			return $shortcodes;
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

	Cherry_Shortcode_Templates::get_instance();
}
