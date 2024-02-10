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

// Theme support
add_theme_support('post-thumbnails');

if (!function_exists('smarty_theme_scripts')) {
	/**
	 * Enqueue theme scripts and styles.
	 * 
	 * @return void
	 */
	function smarty_theme_scripts() {
		wp_enqueue_style('style', get_stylesheet_uri());
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

if (!function_exists('smarty_remove_editor_from_homepage')) {
    /**
     * Disabling Gutenberg editor and hiding the content editor for the home page.
     * 
     * @param bool    $use_block_editor Whether the post can be edited or not.
     * @param WP_Post $post             The post being edited.
     * @return bool
     */
    function smarty_remove_editor_from_homepage($use_block_editor, $post) {
        $home_page_id = get_option('page_on_front');

        // Check if the current post is the home page
        if ($post->ID == $home_page_id) {
            remove_post_type_support('page', 'editor'); // Hide content editor
            return false; // Disable Gutenberg
        }

        return $use_block_editor; // For all other posts/pages, return the default editor choice
    }
    add_filter('use_block_editor_for_post', 'smarty_remove_editor_from_homepage', 10, 2);

    // Additional action to ensure the content editor is hidden in classic editor
    function smarty_hide_editor_from_homepage() {
        global $pagenow;
        // Check if we are on the post edit screen
        if (in_array($pagenow, array('post.php', 'post-new.php'))) {
            $home_page_id = get_option('page_on_front');

            // Check if the current page is the home page
            if (isset($_GET['post']) && $_GET['post'] == $home_page_id) {
                remove_post_type_support('page', 'editor');
            }
        }
    }
    add_action('admin_head', 'smarty_hide_editor_from_homepage');
}

if (!function_exists('smarty_register_graphql_fields')) {
	/**
	 * Register a GraphQL field for the copyright info.
	 * 
	 * @return void
	 */
	function smarty_register_graphql_fields() {
		register_graphql_fields('RootQuery', [
			// Register siteLogo field
			'siteLogo' => [
				'type' => 'String',
				'description' => __('The site logo', 'smarty'),
				'resolve' => function() {
					return get_option('logo_url');
				}
			],
			
			// Register copyrightInfo field
			'copyrightInfo' => [
				'type' => 'String',
				'description' => __('The copyright information', 'smarty'),
				'resolve' => function() {
					return get_option('copyright');
				}
			],
			
			// Register postsOnHomePage field
			'postsOnHomePage' => [
				'type' => 'Int',
				'description' => __('Number of posts on home page', 'smarty'),
				'resolve' => function() {
					return get_option('posts_on_home_page', 4); // Default to 4 if not set
				}
			],
			
			// Register postsPerPage field
			'postsPerPage' => [
				'type' => 'Int',
				'description' => __('Number of posts per page', 'smarty'),
				'resolve' => function() {
					return get_option('posts_per_page', 6); // Default to 6 if not set
				}
			],

			// Register categoriesPerPage field
			'categoriesPerPage'=> [
				'type' => 'Int',
				'description' => __('Number of categories per page', 'smarty'),
				'resolve' => function() {
					return get_option('categories_per_page', 6); // Default to 6 if not set
				}
			],

			// Register tagsPerPage field
			'tagsPerPage' => [
				'type' => 'Int',
				'description' => __('Number of tags per page', 'smarty'),
				'resolve' => function() {
					return get_option('tags_per_page', 6); // Default to 6 if not set
				}
			],
		]);
	}
	add_action('graphql_register_types', 'smarty_register_graphql_fields');
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
		// Check if settings were updated
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            ?>
            <div id="message" class="updated notice">
                <p><strong><?php _e('Settings saved.', 'smarty'); ?></strong></p>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
					setTimeout(function(){
						$("#message").fadeOut("slow");
					}, 4000);
				});
            </script>
            <?php
        }
		?>
		<div class="wrap">
			<h1><?php echo __('Theme Options', 'smarty'); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('smarty-theme-options-group');
				do_settings_sections('smarty-theme-options-group');
				?>

				<style>
					.wp-editor-tools {
						width: 550px;  /* Adjust the width as needed */
					}
					.wp-editor-container {
						max-width: 550px; /* Adjust the width as needed */
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
				<h2><?php echo __('General Settings', 'smarty'); ?></h2>
				<p><?php echo __('We need to use webhooks to trigger Gatsby rebuild when the theme options are updated.', 'smarty'); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php echo __('Logo', 'smarty'); ?></th>
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
						<th scope="row"><?php echo __('Copyright Info', 'smarty'); ?></th>
						<td>
							<?php
							$content = get_option('copyright');
							$editor_id = 'copyright_editor';
							
							// Use wp_editor to add a WYSIWYG editor for new comments
							$editor_settings = array(
								'textarea_name' => 'copyright',
								'textarea_rows' => 5,
								'teeny' => true,
								'media_buttons' => false,
							);
							wp_editor($content, $editor_id, $editor_settings);
							?>
						</td>
					</tr>
				</table>
				
				<hr> <!-- Separator -->
				
				<h2><?php echo __('Home Settings', 'smarty'); ?></h2>
				<p><?php echo __('Configure the number of posts displayed on the home page.', 'smarty'); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php echo __('Posts on home page', 'smarty'); ?></th>
						<td>
							<select id="posts_on_home_page" name="posts_on_home_page">
								<option value="2" <?php selected(get_option('posts_on_home_page'), 2); ?>>2</option>
    							<option value="4" <?php selected(get_option('posts_on_home_page'), 4); ?>>4</option>
    							<option value="6" <?php selected(get_option('posts_on_home_page'), 6); ?>>6</option>
							</select>
							<p class="description"><?php echo __('Select how many posts to display on home page.', 'smarty'); ?></p>
						</td>
					</tr>
				</table>
				
				<hr> <!-- Separator -->
				
				<h2><?php echo __('Pagination Settings', 'smarty'); ?></h2>
				<p><?php echo __('Configure the number of items displayed per page for posts, categories, and tags.', 'smarty'); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php echo __('Posts per Page', 'smarty'); ?></th>
						<td>
							<select id="posts_per_page" name="posts_per_page">
								<option value="7" <?php selected(get_option('posts_per_page'), 7); ?>>7</option>
    							<option value="10" <?php selected(get_option('posts_per_page'), 10); ?>>10</option>
    							<option value="13" <?php selected(get_option('posts_per_page'), 13); ?>>13</option>
    							<option value="16" <?php selected(get_option('posts_per_page'), 16); ?>>16</option>
							</select>
							<p class="description"><?php echo __('Select how many posts to display on each page.', 'smarty'); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php echo __('Categories per Page', 'smarty'); ?></th>
						<td>
							<select id="categories_per_page" name="categories_per_page">
								<option value="6" <?php selected(get_option('categories_per_page'), 6); ?>>6</option>
								<option value="9" <?php selected(get_option('categories_per_page'), 9); ?>>9</option>
								<option value="12" <?php selected(get_option('categories_per_page'), 12); ?>>12</option>
								<option value="15"<?php selected(get_option('categories_per_page'), 15); ?>>15</option>
							</select>
							<p class="description"><?php echo __('Select how many categories to display on each page.', 'smarty'); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php echo __('Tags per Page', 'smarty'); ?></th>
						<td>
							<select id="tags_per_page" name="tags_per_page">
								<option value="6" <?php selected(get_option('tags_per_page'), 6); ?>>6</option>
								<option value="9" <?php selected(get_option('tags_per_page'), 9); ?>>9</option>
								<option value="12" <?php selected(get_option('tags_per_page'), 12); ?>>12</option>
								<option value="15" <?php selected(get_option('tags_per_page'), 15); ?>>15</option>
							</select>
							<p class="description"><?php echo __('Select how many tags to display on each page.', 'smarty'); ?></p>
						</td>
					</tr>
				</table>
				
				<hr> <!-- Separator -->
		
				<h2><?php echo __('Webhook Settings', 'smarty'); ?></h2>
				<p><?php echo __('Configure the webhook URL and secret token for triggering Gatsby rebuilds.', 'smarty'); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php echo __('Webhook URL', 'smarty'); ?></th>
						<td>
							<input type="url" id="gatsby_webhook_url" name="gatsby_webhook_url" value="<?php echo esc_attr(get_option('gatsby_webhook_url')); ?>" class="regular-text">
							<p class="description"><?php echo __('Enter the Webhook URL to trigger Gatsby rebuild.', 'smarty'); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php echo __('Secret Token', 'smarty'); ?></th>
						<td>
							<input type="text" id="gatsby_secret_token" name="gatsby_secret_token" value="<?php echo esc_attr(get_option('gatsby_secret_token')); ?>" class="regular-text">
							<button type="button" class="button" id="generate_token_button"><?php echo __('Generate Token', 'smarty'); ?></button>
							<p class="description"><?php echo __('Secret token for secure webhook calls. Click "Generate Token" to create a new one.', 'smarty'); ?></p>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row"><?php echo __('Webhook Receiver Script', 'smarty'); ?></th>
						<td>
							<textarea id="webhook_receiver_code" name="webhook_receiver_code" rows="15" style="width: 100%;" readonly>
								<?php
								$gatsby_secret_token = esc_attr(get_option('gatsby_secret_token')); // Assuming 'gatsby_secret_token' is the name of the option where you store the token
								$webhookReceiverPhpCode = <<<EOD

								<?php
								// webhook-receiver.php

								\$SECRET_TOKEN = '{$gatsby_secret_token}';

								// Check for the secret token to validate the request
								if (!isset(\$_GET['token']) || \$_GET['token'] !== \$SECRET_TOKEN) {
									http_response_code(403);
									die('Unauthorized');
								}

								// The command to trigger the Gatsby build
								\$buildCommand = 'cd /path/to/your/gatsby/site && gatsby build';

								// Execute the build command
								exec(\$buildCommand, \$output, \$return);

								// Check if the build was successful
								if (\$return === 0) {
									echo 'Gatsby build triggered successfully.';
								} else {
									http_response_code(500);
									echo 'Gatsby build failed.';
								}
								EOD;
								echo htmlspecialchars($webhookReceiverPhpCode); // Print the code in the textarea
								?>
							</textarea>
							<p><?php echo __('Copy the following PHP code into a new file named <code>webhook-receiver.php</code> on your Gatsby server. This script will listen for webhook requests to trigger the Gatsby build process.', 'smarty'); ?></p>
							<p><?php echo __('Make sure to replace <code>/path/to/your/gatsby/site</code> with the actual path to your Gatsby site on the server.', 'smarty'); ?></p>
						</td>
					</tr>
				</table>
				
				<script>
					jQuery(document).ready(function($) {
						$("#generate_token_button").click(function(e) {
							e.preventDefault();
							let randomToken = [...Array(35)] // Increase the length to 35 characters or any desired length
                          		.map(() => Math.random().toString(36)[2] || 0)
                          		.join('') + 
                          		new Date().getTime().toString(36); // Adding current time in base36 to ensure uniqueness
        					$("#gatsby_secret_token").val(randomToken);
						});
					});
				</script>
					
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
		// General Settings
		register_setting('smarty-theme-options-group', 'logo_url', 'esc_url_raw');
		register_setting('smarty-theme-options-group', 'copyright', 'smarty_sanitize_theme_option');
		
		// Home Settings
		register_setting('smarty-theme-options-group', 'posts_on_home_page', 'intval');
		
		// Pagination Settings
		register_setting('smarty-theme-options-group', 'posts_per_page', 'intval');
		register_setting('smarty-theme-options-group', 'categories_per_page', 'intval');
		register_setting('smarty-theme-options-group', 'tags_per_page', 'intval');
		
		// Webhook Settings
		register_setting('smarty-theme-options-group', 'gatsby_webhook_url', 'esc_url_raw');
		register_setting('smarty-theme-options-group', 'gatsby_secret_token', 'sanitize_text_field');
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
		$trigger_options = array('logo_url', 'copyright', 'posts_on_home_page', 'posts_per_page', 'categories_per_page', 'tags_per_page', 'gatsby_webhook_url', 'gatsby_secret_token');

		if (in_array($option_name, $trigger_options)) {
			$webhook_url = get_option('gatsby_webhook_url'); // for development: http://localhost:8000/__refresh
			if (!empty($webhook_url)) {
				$secret_token = get_option('gatsby_secret_token');
				$response = wp_remote_post($webhook_url, array(
					'body' => json_encode(array('secret_token' => $secret_token)),
					'headers' => array('Content-Type' => 'application/json'),
				));

				if (is_wp_error($response)) {
					error_log('Error in posting to Gatsby: ' . $response->get_error_message());
				} else {
					error_log('Gatsby rebuild triggered.');
				}

				return $response;
			}
		}
	}
	add_action('updated_option', 'smarty_url_to_trigger_gatsby_rebuild', 10, 3);
}

// Additional filters
add_filter('show_admin_bar', '__return_false');
