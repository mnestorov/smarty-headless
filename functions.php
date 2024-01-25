<?php
 /**
  * Functions and definitions
  *
  * @link <https://developer.wordpress.org/themes/basics/theme-functions/>
  *
  * @package WordPress
  * @subpackage Smarty Headless
  * @since 1.0
  */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('smarty_theme_scripts')) {
	/**
	 * Enqueue theme scripts and styles.
	 * 
	 * @return void
	 */
	function smarty_theme_scripts() {
		wp_enqueue_style('style', get_stylesheet_uri());
		wp_enqueue_style('custom', get_template_directory_uri() . 'css/custom.css', array(), '1.0', 'all');
	}
	add_action('wp_enqueue_scripts', 'smarty_theme_scripts');
}

if (!function_exists('smarty_register_menus')) {
	/**
	 * Register theme menus.
	 * 
	 * @return void
	 */
	function smarty_register_menus() {
	  register_nav_menus(
		array(
		  'header-menu' => __('Header Menu'),
		  'social-menu' => __('Social Menu'),
		  'footer-menu' => __('Footer Menu'),
		 )
	  );
	}
	add_action('init', 'smarty_register_menus');
}

if (!function_exists('smarty_register_widget_areas')) {
	/**
	 * Register widget areas for the theme.
	 * 
	 * @return void
	 */
	function smarty_register_widget_areas() {
		register_sidebar(array(
			'name'          => __('Footer Widget Area', 'smarty'),
			'id'            => 'footer-widget-area',
			'description'   => __('Widget area for footer', 'smarty'),
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		));
	}
	add_action('widgets_init', 'smarty_register_widget_areas');
}

if (!function_exists('smarty_register_graphql_fields')) {
	/**
	 * Register a GraphQL field for the copyright info.
	 * 
	 * @return void
	 */
	function smarty_register_copyright_info_graphql_field() {
		register_graphql_fields('RootQuery', [
			'siteLogo' => [
				'type' => 'String',
				'description' => __('The site logo', 'smarty'),
				'resolve' => function() {
					return get_option('logo_url');
				}
			],
			'copyrightInfo' => [
				'type' => 'String',
				'description' => __('The copyright information', 'smarty'),
				'resolve' => function() {
					return get_option('copyright');
				}
			],
		]);
	}
	add_action('graphql_register_types', 'smarty_register_copyright_info_graphql_field');
}

if (!function_exists('smarty_add_theme_options_page')) {
	/**
	 * Adds the theme options page to the WordPress admin under 'Appearance'.
	 */
	function smarty_add_theme_options_page() {
		add_submenu_page(
			'themes.php',                // Parent slug (Appearance menu)
			'Theme Options',       		 // Page title
			'Theme Options',             // Menu title
			'manage_options',            // Capability required
			'smarty-theme-options',      // Menu slug
			'smarty_theme_options_page'  // Function that outputs the page content
		);
	}
	add_action('admin_menu', 'smarty_add_theme_options_page');
}

if (!function_exists('smarty_theme_options_page')) {
	/**
	 * Renders the content of the theme options page.
	 */
	function smarty_theme_options_page() {
		?>
		<div class="wrap">
			<h1>Theme Options</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('smarty-theme-options-group');
				do_settings_sections('smarty-theme-options-group');
				?>

				<style>
					.wp-editor-tools {
						width: 400px;  /* Adjust the width as needed */
					}
					.wp-editor-container {
						max-width: 400px; /* Adjust the width as needed */
					}
					.wp-editor-container iframe, 
					.wp-editor-container textarea {
						height: 150px; /* Adjust the height as needed */
					}
					p.submit {
    					margin-top: 0;
					}
				</style>

				<hr> <!-- Separator -->
				
				<!-- General Section -->
				<h2>General Settings</h2>
				<p>We need to use webhooks to trigger Gatsby rebuild when the theme options are updated.</p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Logo</th>
						<td>
							<input type='text' id='logo_url' name='logo_url' value='<?php echo esc_attr(get_option('logo_url')); ?>'>
							<input type='button' class='button-primary' value='Upload Image' id='upload_logo_button'><br>
							<img src='<?php echo esc_attr(get_option('logo_url')); ?>' id='logo_preview' height='100' style='max-height: 100px; width: auto;'>
							<script>
								jQuery(document).ready(function($) {
									$('#upload_logo_button').click(function(e) {
										e.preventDefault();
										var imageUploader = wp.media({
											'title': 'Upload Logo',
											'button': {
												'text': 'Use this image'
											},
											'multiple': false
										}).on('select', function() {
											var uploadedImage = imageUploader.state().get('selection').first();
											var imageUrl = uploadedImage.toJSON().url;
											$('#logo_url').val(imageUrl);
											$('#logo_preview').attr('src', imageUrl);
										})
										.open();
									});
								});
							</script>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Copyright Info</th>
						<td>
							<?php
							$content = get_option('copyright');
							$editor_id = 'copyright_editor';
							$settings = array('textarea_name' => 'copyright');

							wp_editor($content, $editor_id, $settings);
							?>
						</td>
					</tr>
				</table>
				
				<hr> <!-- Separator -->
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

if (!function_exists('smarty_register_theme_options')) {
	/**
	 * Registers theme options and their sanitization callbacks.
	 */
	function smarty_register_theme_options() {
		// General
		register_setting('smarty-theme-options-group', 'logo_url', 'esc_url_raw');
		register_setting('smarty-theme-options-group', 'copyright', 'smarty_sanitize_theme_option');
	}
}

if (!function_exists('smarty_sanitize_theme_option')) {
	/**
	 * Sanitizes theme option inputs.
	 *
	 * @param mixed $input The input to be sanitized.
	 * @return mixed The sanitized input.
	 */
	function smarty_sanitize_theme_option($input) {
		// Sanitize the input data for allowed HTML tags for post content
		$new_input = array();
		if (isset($input)) {
			$new_input = wp_kses_post($input);
		}

		return $new_input;
	}
	add_action('admin_init', 'smarty_register_theme_options');
}

if (!function_exists('smarty_url_to_trigger_gatsby_rebuild')) {
	/**
	 * Trigger a Gatsby rebuild when the theme options are updated.
	 * 
	 * @return mixed The response from the Gatsby server or a WP_Error object.
	 */
	function smarty_url_to_trigger_gatsby_rebuild($option_name, $old_value, $new_value) {
		$trigger_options = array('logo_url', 'copyright');
		
		if (in_array($option_name, $trigger_options)) {
			// URL to trigger Gatsby rebuild
			error_log('Triggering Gatsby rebuild.');
			$webhook_url = 'http://localhost:8000/__refresh';
			$response = wp_remote_post($webhook_url);
			
			if (is_wp_error($response)) {
				error_log('Error in posting to Gatsby: ' . $response->get_error_message());
			}
			
			return $response;
		}
	}
	add_action('updated_option', 'smarty_url_to_trigger_gatsby_rebuild', 10, 3);
}

// Additional filters
add_filter('show_admin_bar', '__return_false');