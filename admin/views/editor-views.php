<?php
/**
 * Represents the view for the `Shortcodes Templater`.
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

if ( ! file_exists( $this->target_dir_path ) ) {
	wp_die( '<p>' . __( 'Target directory does not exist.' ) . '</p>' );
}

if ( ! current_user_can( 'edit_themes' ) ) {
	wp_die( '<p>' . __( 'You do not have sufficient permissions to edit templates for this site.' ) . '</p>' );
}

$dirs = self::get_the_dirs();
$default_templates = self::get_the_files( $dirs );
$allowed_tags      = array_keys( $default_templates );

// Get uploads directory name.
$upload_name = sanitize_file_name( wp_basename( CHERRY_TEMPLATER_UPLOAD_DIR ) );

// Error flag.
$error = false;

// Prepare associative array (for add_query_arg).
$query_args = array(
	'file'     => '',
	'location' => '',
);

// Get a `file` value.
if ( isset( $_GET['file'] ) ) {
	$file       = sanitize_text_field( $_GET['file'] );
	$active_tag = wp_basename( dirname( $file ) );
} elseif ( isset( $_POST['relative-file'] ) ) {
	$file       = sanitize_text_field( $_POST['relative-file'] );
	$active_tag = wp_basename( dirname( $file ) );
} else {
	reset( $allowed_tags );
	$active_tag = current( $allowed_tags );
	$file       = Cherry_Shortcodes_Templater::$dir_name . $active_tag . '/default1.tmpl';
}

// Get a `location` value.
if ( isset( $_GET['location'] ) ) {
	$location = sanitize_text_field( $_GET['location'] );
} elseif ( isset( $_POST['location'] ) ) {
	$location = sanitize_text_field( $_POST['location'] );
} else {
	$location = wp_basename( reset( $dirs ) );
}

$query_args['file']     = $file;
$query_args['location'] = $upload_name;

// Set part of absolute path to the file.
if ( $location == $upload_name ) {
	$abs_file = CHERRY_TEMPLATER_UPLOAD_DIR;
} else {
	$abs_file = trailingslashit( WP_PLUGIN_DIR ) . $location;
}

$relative_file = $file;

if ( isset( $_POST['relative-file'] ) ) {
	$relative_file = sanitize_text_field( $_POST['relative-file'] );
}

$file = trailingslashit( $abs_file ) . $relative_file;

if ( ! is_file( $file ) ) {
	$error = true;
} else {
	// Get a file name.
	$file_name = wp_basename( $file );
}

$path_to_shortcode_templates = trailingslashit( $this->target_dir_path ) . $active_tag;

if ( isset( $_POST['save'] ) ) {
	$action = 'save';
} elseif ( isset( $_POST['delete'] ) ) {
	$action = 'delete';
} elseif ( isset( $_POST['rename'] ) ) {
	$action = 'rename';
} elseif ( isset( $_POST['copy'] ) || isset( $_GET['copy'] ) ) {
	$action = 'copy';
} else {
	$action = '';
}

switch ( $action ) {
	case 'save':
		$query_args['file'] = urlencode( $relative_file );

		$content = self::filesystem_write( $file );

		if ( $content && ! is_wp_error( $content ) ) {
			$query_args = array_merge( $query_args, array( $action => 'true' ) );
		}
		$loc = add_query_arg( $query_args, $this->form_url );

		/**
		 * Fires when template are saved.
		 *
		 * @since 1.0.0
		 * @param $active_tag Shortcode's tag.
		 */
		do_action( 'cherry_editor_template_save', $active_tag );

		wp_safe_redirect( $loc );
		exit;

	case 'copy':
		$query_args['file'] = urlencode( $relative_file );

		if ( false === wp_mkdir_p( $path_to_shortcode_templates ) ) {
			$loc = add_query_arg( $query_args, $this->form_url );
			wp_safe_redirect( $loc );
			exit;
		}

		$files = $this->scandir( $path_to_shortcode_templates, 'tmpl', -1 );

		if ( is_array( $files ) ) {
			$files_count = count( $files );
		} else {
			$files_count = 0;
		}

		$new_file = trailingslashit( $path_to_shortcode_templates ) . $active_tag;
		$new_file .= '_' . ++$files_count;
		$new_file .= '.tmpl';

		if ( file_exists( $new_file ) ) {
			$new_file = trailingslashit( $path_to_shortcode_templates ) . $active_tag;
			$new_file .= '_' . $files_count;
			$new_file .= '_' . uniqid();
			$new_file .= '.tmpl';
		}

		if ( false !== $this->copy( $file, $new_file ) ) {
			$_file = Cherry_Shortcodes_Templater::$dir_name . $active_tag;
			$_file = trailingslashit( $_file ) . wp_basename( $new_file );
			$query_args['file'] = urlencode( $_file );
			$query_args         = array_merge( $query_args, array( 'duplicate' => 'true' ) );
		}

		$loc = add_query_arg( $query_args, $this->form_url );

		/**
		 * Fires when template are copied.
		 *
		 * @since 1.0.0
		 * @param $active_tag Shortcode's tag.
		 */
		do_action( 'cherry_editor_template_copy', $active_tag );

		wp_safe_redirect( $loc );
		exit;

	case 'delete':

		if ( true === $this->delete( $file, false, 'f' ) ) {
			$query_args = array_merge( $query_args, array( $action => 'true' ) );
		}

		if ( isset( $default_templates[ $active_tag ][ $upload_name ] ) ) {
			foreach ( $default_templates[ $active_tag ][ $upload_name ] as $item => $data ) {
				if ( $file == $data['path'] ) {
					unset( $default_templates[ $active_tag ][ $upload_name ][ $item ] );
					break;
				}
			}
		}

		if ( empty( $default_templates[ $active_tag ][ $upload_name ] ) ) {
			unset( $query_args['location'] );
			unset( $query_args['file'] );
			$query_args = array_merge( $query_args, array( 'notemplate' => 'true' ) );
			$loc        = add_query_arg( $query_args, $this->form_url );

			/**
			 * Fires when all templates are deleted.
			 *
			 * @since 1.0.0
			 * @param $active_tag Shortcode's tag.
			 */
			do_action( 'cherry_editor_template_delete_all', $active_tag );

			wp_safe_redirect( $loc );
			exit;
		}

		$last_file          = end( $default_templates[ $active_tag ][ $upload_name ] );
		$query_args['file'] = urlencode( trailingslashit( $last_file['dir'] ) . wp_basename( $last_file['path'] ) );
		$loc                = add_query_arg( $query_args, $this->form_url );

		/**
		 * Fires when template are deleted.
		 *
		 * @since 1.0.0
		 * @param $active_tag Shortcode's tag.
		 */
		do_action( 'cherry_editor_template_delete', $active_tag );

		wp_safe_redirect( $loc );
		exit;

	case 'rename':

		$new_file = '';

		if ( isset( $_POST['new-file-name'] ) ) {
			$new_file = trailingslashit( $path_to_shortcode_templates ) . sanitize_file_name( $_POST['new-file-name'] );
		}

		if ( false === $this->rename( $file, $new_file ) ) {
			$query_args['file'] = urlencode( $relative_file );
			$query_args         = array_merge( $query_args, array( 'norename' => 'true' ) );
		} else {
			$_file = Cherry_Shortcodes_Templater::$dir_name . $active_tag;
			$_file = trailingslashit( $_file ) . wp_basename( $new_file );
			$query_args['file'] = urlencode( $_file );
			$query_args         = array_merge( $query_args, array( $action => 'true' ) );
		}

		$loc = add_query_arg( $query_args, $this->form_url );

		/**
		 * Fires when template are renamed.
		 *
		 * @since 1.0.0
		 * @param $active_tag Shortcode's tag.
		 */
		do_action( 'cherry_editor_template_rename', $active_tag );

		wp_safe_redirect( $loc );
		exit;

	default:

		$content = '';

		if ( ! $error ) {
			$_content = $this->filesystem_read( $file );

			if ( false === $_content ) {
				break;
			}

			$content = ( is_wp_error( $_content ) ) ? $content : $_content;
		} ?>

		<div class="wrap cherry-shortcodes-templater cherry-ui-core">

			<?php if ( isset( $_GET['save'] ) ) : ?>
				<div id="message_" class="updated_ templater-updated_"><?php _e( 'File edited successfully.', 'cherry-shortcodes-templater' ) ?></div>
			<?php endif;

			if ( isset( $_GET['duplicate'] ) ) : ?>
				<div id="message_" class="updated_ templater-updated_"><?php _e( 'File added successfully.', 'cherry-shortcodes-templater' ) ?></div>
			<?php endif;

			if ( isset( $_GET['delete'] ) ) : ?>
				<div id="message_" class="updated_ templater-updated_"><?php _e( 'File deleted successfully.', 'cherry-shortcodes-templater' ) ?></div>
			<?php endif;

			if ( isset( $_GET['rename'] ) ) : ?>
				<div id="message_" class="updated_ templater-updated_"><?php _e( 'File renamed successfully.', 'cherry-shortcodes-templater' ) ?></div>
			<?php endif;

			if ( isset( $_GET['norename'] ) ) : ?>
				<div id="message_" class="updated_ templater-updated_"><?php _e( 'File with the same name already exists.', 'cherry-shortcodes-templater' ) ?></div>
			<?php endif; ?>

			<div class="cherry-row">
				<div class="cherry-column-1">
					<div id="nav-container" class="box-primary_">
						<?php
							if ( class_exists( 'Cherry_Shortcodes_Data' ) ) {
								$registered_shortcodes = (array) Cherry_Shortcodes_Data::shortcodes();
							} else {
								$registered_shortcodes = apply_filters( 'cherry_templater/data/shortcodes', array() );
							}

							$_count = 0;
							foreach ( $default_templates as $tag => $locations ) :

								if ( ! isset( $registered_shortcodes[ $tag ] ) ) {
									continue;
								} ?>

								<h3><?php echo esc_html( $tag ); ?></h3>
								<div class="item item-<?php echo ++$_count; ?>">
									<ul class="template-list">

									<?php foreach ( $locations as $target => $items ) {

										if ( isset( $items['default'] ) ) {
											$_file = wp_basename( $items['default']['path'] );
											$query_args = array(
												'file'     => urlencode( $items['default']['dir'] . '/' . $_file ),
												'location' => $target,
												'noheader' => 'true',
												'copy'     => 'true',
											); ?>
											<li><a href="<?php echo wp_nonce_url( add_query_arg( $query_args, $this->form_url ), 'shortcode_templates_editor_admin', 'wp_nonce_field_editor' ); ?>" class="add-new"><?php _e( 'Add New', 'cherry-shortcodes-templater' ) ?></a></li>

										<?php unset( $locations[ $target ]['default'] );
										break;
										}
									} ?>

									<?php foreach ( $locations as $target => $items ) {

										foreach ( $items as $key => $value ) {
											$_file = wp_basename( $value['path'] );
											$query_args = array(
												'file'     => urlencode( $value['dir'] . '/' . $_file ),
												'location' => $target,
											); ?>
											<li><a href="<?php echo esc_url( add_query_arg( $query_args, $this->form_url ) ); ?>"><?php echo $_file; ?></a></li>

									<?php }
									} ?>

									</ul>

								</div>

						<?php endforeach; ?>
					</div><!--nav-container-->
				</div><!--.column-1-->

				<div class="cherry-column-2">

					<?php if ( isset( $_GET['notemplate'] ) ) { ?>
						<div class="main-title_">
							<h2><?php _e( 'You have not created any template', 'cherry-shortcodes-templater' ); ?></h2>
						</div>
						<?php break;
					}

					if ( ! isset( $_GET['location'] ) ) { ?>
						<div class="main-title_">
							<h2><?php printf( __( 'Welcome to the <strong>%s</strong>!', 'cherry-shortcodes-templater' ), esc_html( get_admin_page_title() ) ); ?></h2>
						</div>
						<?php break;
					} ?>

					<div class="main-title_">
						<h2 class="alignleft"><?php echo esc_html( $file_name ); ?></h2>
						<div class="clear"></div>
					</div>

					<?php if ( $error ) :
						echo '<div class="error"><p>' . __( 'Oops, such file does not exist! Double check the name and try again, merci.' ) . '</p></div>';
						wp_safe_redirect( $this->form_url );
					else : ?>

						<?php if ( ! empty( $file ) ) : ?>

							<ol class="breadcrumb_">

							<?php $templates_type = wp_basename( dirname( dirname( $file ) ) );

							switch ( $templates_type ) {
								case 'shortcodes':
									echo '<li>' . __( 'Shortcode Templates', 'cherry-shortcodes-templater' ) .'</li>';
									break;

								default:
									break;
							} ?>

								<li class="active"><?php echo esc_html( $active_tag ); ?></li>
							</ol><!--.breadcrumb_-->

							<form name="editor" id="editor" action="<?php echo esc_url( add_query_arg( array( 'noheader' => 'true' ), $this->form_url ) ); ?>" method="post">
								<input id="current-file" name="current-file" type="hidden" value="<?php echo $file_name; ?>">
								<input id="relative-file" name="relative-file" type="hidden" value="<?php echo $relative_file; ?>">
								<input id="location" name="location" type="hidden" value="<?php echo $location; ?>">
								<?php wp_nonce_field( 'shortcode_templates_editor_admin', 'wp_nonce_field_editor' );

									$args = array(
										'wpautop'       => false,
										'media_buttons' => false,
										'textarea_name' => 'shortcode-template',
										'textarea_rows' => 22,
										'tinymce'       => false,
										'quicktags'     => array(
											'buttons' => 'shcd_title,shcd_content,shcd_button,shcd_avatar',
										),
									);
									wp_editor( esc_textarea( $content ), 'shortcode-template', $args ); ?>

									<?php if ( $location == $upload_name ) { ?>

									<div class="action-dropdown_">
										<a href="#" id="edit-action" class="button_ button-default_"><span class="dashicons dashicons-welcome-write-blog"></span><?php _e( 'Edit', 'cherry-shortcodes-templater' ); ?></a>
										<ul id="action-dropdown-list_">
											<li><a href="#TB_inline?width=600&amp;height=170&amp;inlineId=rename-dialog" title="<?php _e( 'Rename dialog' , 'cherry-shortcodes-templater' ); ?>" class="thickbox"><?php _e( 'Rename', 'cherry-shortcodes-templater' ); ?></a></li>
											<li><input type="submit" name="copy" id="copy" value="<?php _e( 'Duplicate', 'cherry-shortcodes-templater' ); ?>"></li>
											<li><input type="submit" name="delete" id="delete" value="<?php _e( 'Delete', 'cherry-shortcodes-templater' ); ?>"></li>
										</ul>
									</div>

									<?php } ?>

							</form>

							<?php if ( $location == $upload_name ) {

								add_thickbox(); ?>

								<div id="rename-dialog">
									<div class="cherry-ui-core">
										<form action="<?php echo esc_url( add_query_arg( array( 'noheader' => 'true', 'location' => $upload_name ), $this->form_url ) ); ?>" method="post" id="rename-form">
											<?php wp_nonce_field( 'shortcode_templates_editor_admin', 'wp_nonce_field_dialog' ); ?>
											<input id="_relative-file" name="relative-file" type="hidden" value="<?php echo $relative_file; ?>">
											<label for="new-file-name"><?php _e( 'Enter a new file name', 'cherry-shortcodes-templater' ); ?></label>
											<input type="text" id="new-file-name" name="new-file-name" value="<?php echo $file_name; ?>" placeholder="<?php _e( 'Enter a new file name', 'cherry-shortcodes-templater' ); ?>">
											<span id="file-name-error"><?php _e( 'Please enter a valid filename.', 'cherry-shortcodes-templater' ); ?></span>
											<div class="rename-form-btns-wrap">
												<input type="submit" name="rename" id="rename" class="button_ button-primary_" value="<?php _e( 'Save', 'cherry-shortcodes-templater' ); ?>">
												<a href="#" class="button_ button-default_" id="TB_closeWindowButton"><?php _e( 'Cancel', 'cherry-shortcodes-templater' ); ?></a>
											</div>
										</form>
									</div><!--.cherry-ui-core-->
								</div><!--rename-dialog-->

							<?php } ?>

							<div class="clear"></div>

						<?php endif; ?>

					<?php endif; // $error ?>

				</div><!--.column-2-->
			</div><!--.row-fluid-->
		</div><!--.wrap-->

	<?php break;
}
