<?php

/**
 * QTS Rebuild Meta Slug
 *
 * @since 1.0
 */
class QTS_RebuildMetaSlug {

	/**
	 * Register ID of management page
	 *
	 * @var
	 * @since 1.2.0
	 */
	var $menu_id;

	// same as in qts main class
	private $meta_key = '_qts_slug_%s';

	/**
	 * User capability
	 *
	 * @access public
	 * @since 1.2.0
	 */
	public $capability;

	/**
	 * Plugin initialization
	 *
	 * @access public
	 * @since 1.2.0
	 */
	function __construct() {

		load_plugin_textdomain( 'qts-rebuild-meta-slug', false, '/qts-rebuild-meta-slug/localization' );

		add_action( 'admin_menu',                              array( &$this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts',                   array( &$this, 'admin_enqueues' ) );
		add_action( 'wp_ajax_rebuild_meta_slug',               array( &$this, 'ajax_process_image' ) );

		// Allow people to change what capability is required to use this plugin
		$this->capability = apply_filters( 'qts_rebuild_capability', 'manage_options' );
	}


	/**
	* getter: meta key
	*
	* modified from QTS main class
	*
	* @since 1.2.0
	*/
	public function get_meta_key( $lang ) {
		return sprintf( $this->meta_key, $lang ); // returns, eg: _qts_slug_en
	}

	/**
	 * Register the management page
	 *
	 * @access public
	 * @since 1.2.0
	 */
	public function add_admin_menu() {
		$this->menu_id = add_management_page( __( 'QTS Rebuild Meta Slug', 'qts-rebuild-meta-slug' ), __( 'QTS Rebuild Slug', 'qts-rebuild-meta-slug' ), $this->capability, 'qts-rebuild-meta-slug', array( &$this, 'rebuild_slug_interface' ) );
	}

	/**
	 * Enqueue Javascript
	 *
	 * @param string $hook_suffix
	 * @access public
	 * @since 1.2.0
	 */
	public function admin_enqueues( $hook_suffix ) {

		if ( $hook_suffix != $this->menu_id ) {
			return;
		}
		wp_enqueue_script( 'jquery-ui-progressbar', plugins_url( '/assets/js/jquery.ui.progressbar.min.js', dirname( __FILE__ ) ), array( 'jquery-ui-core' ), '1.7.2' );
	}


	/**
	 * The page interface and the processor for rebuilding meta slugs
	 *
	 * @access public
	 * @since 1.2.0
	 */
	public function rebuild_slug_interface() {

		global $wpdb;
		?>

	<div id="message" class="updated fade" style="display:none"></div>

	<div class="wrap qts-rebuildslugs">
		<h2><?php _e( 'QTS Rebuild Meta Slug', 'qts-rebuild-meta-slug' ); ?></h2>

		<?php

		// If the button was clicked

		if ( ! empty( $_POST['qts-rebuild-meta-slug'] ) || ! empty( $_REQUEST['ids'] ) ) {

			// Capability check
			if ( ! current_user_can( $this->capability ) ) {
				wp_die( __( 'Not Allowed', 'qts_rebuild_meta_slug' ) );
			}

			// Form nonce check
			check_admin_referer( 'qts-rebuild-meta-slug' );

			global $q_config;
			if ( ! isset( $q_config['enabled_languages'] ) || ! is_array( $q_config['enabled_languages'] ) ) {
				wp_die( __( 'Can\' find enabled languages', 'qts_rebuild_meta_slug' ) );
			}

			$posts_per_batch = ! empty( $_POST['qts_autob_settings']['posts_per_batch'] ) ? $_POST['qts_autob_settings']['posts_per_batch'] : '10';
			$pause_time = ! empty( $_POST['qts_autob_settings']['pause_time'] ) ? $_POST['qts_autob_settings']['pause_time'] : '300';

			$languages = $q_config['enabled_languages'];

			$test_run = isset( $_POST['qts_autob_settings']['qts_checkbox_field_0'] ) ? true : false;

			$exclude_default_post_types = array( 'attachment', 'revision','nav_menu_item','custom_css','customize_changeset' );
			$exclude_default_post_types = array_map( 'sanitize_title_for_query', $exclude_default_post_types );
			$exclude_default_post_types = "'" . implode( "','", $exclude_default_post_types ) . "'";

			$posts = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type NOT IN ($exclude_default_post_types) AND post_status != 'trash' ORDER BY ID ASC" ); // WPCS: unprepared SQL OK.

			// (array|object|null) return values
			if ( null === $posts ) {
				echo '	<p>' . sprintf( __( "Unable to find any post. Are you sure <a href='%s'>some exist</a>?", 'qts-rebuild-meta-slug' ), admin_url( 'wp-admin/edit.php' ) ) . '</p></div>';
				return;
			}

			$ids = '';
			foreach ( $posts as $post ) {
				$ids .= $post->ID . ',';
			}
			$count = count( $posts );

			$text_goback = sprintf(
				__( 'To go back to the previous page, <a href="%s">click here</a>.', 'qts-rebuild-meta-slug' ),'javascript:history.go(-1)'
			);

			$text_failures = sprintf(
				__( '%1$s post(s) slugs were successfully rebuilt in %2$s seconds and there were %3$s failure(s). To try rebuilding the everything again, <a href="%4$s">click here</a>. %5$s', 'qts-rebuild-meta-slug' ),
				"' + rt_successes + '",
				"' + rt_totaltime + '",
				"' + rt_errors + '",
				esc_url( wp_nonce_url( admin_url( 'tools.php?page=qts-rebuild-meta-slug&goback=1' ), 'qts-rebuild-meta-slug' ) . '&ids=' ) . "' + rt_failedlist + '",
				$text_goback
			);
			$text_nofailures = sprintf(
				__( 'All done! %1$s post(s) slugs were successfully rebuilt in %2$s seconds and there were 0 failures. %3$s', 'qts-rebuild-meta-slug' ),
				"' + rt_successes + '",
				"' + rt_totaltime + '",
				$text_goback
			);
	?>

	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'qts-rebuild-meta-slug' ) ?></em></p></noscript>

	<div id="qts-rebuild-bar" style="position:relative;height:25px;">
		<div id="qts-rebuild-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
	</div>

	<p><input type="button" class="button hide-if-no-js" name="qts-rebuild-stop" id="qts-rebuild-stop" value="<?php _e( 'Abort Process', 'qts-rebuild-meta-slug' ) ?>" /></p>

	<h3 class="title"><?php _e( 'Process Information', 'qts-rebuild-meta-slug' ); ?></h3>

	<p>
		<?php printf( __( 'Total: %s', 'qts-rebuild-meta-slug' ), $count ); ?><br />
		<?php printf( __( 'Success: %s', 'qts-rebuild-meta-slug' ), '<span id="qts-rebuild-debug-successcount">0</span>' ); ?><br />
		<?php printf( __( 'Failure: %s', 'qts-rebuild-meta-slug' ), '<span id="qts-rebuild-debug-failurecount">0</span>' ); ?><br />
		<?php printf( __( 'Position in batch: %s', 'qts-rebuild-meta-slug' ), '<span id="qts-rebuild-debug-position">0</span>' ); ?><br />
		<?php printf( __( 'Batch number: %s', 'qts-rebuild-meta-slug' ), '<span id="qts-rebuild-debug-batch">0</span>' ); ?><br />
	</p>

	<ol id="qts-rebuildlist">
		<li style="display:none"></li>
	</ol>

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function( $ ){
			var i;
			var rt_posts = [<?php echo $ids; ?>];
			var languages = <?php echo json_encode( $languages ); ?>;
			var test_run = <?php echo json_encode( $test_run ); ?>;
			var time_to_pause = false;
			var position = 1;
			var batch = 1;
			var posts_per_batch = <?php echo json_encode( $posts_per_batch ); ?>;
			var pause_time = <?php echo json_encode( $pause_time ); ?>;
			var rt_total = rt_posts.length;
			var rt_count = 1;
			var rt_successes = 0;
			var rt_errors = 0;
			var rt_failedlist = '';
			var rt_resulttext = '';
			var rt_timestart = new Date().getTime();
			var rt_timeend = 0;
			var rt_totaltime = 0;
			var rt_continue = true;

			// Create the progress bar
			$( "#qts-rebuild-bar" ).progressbar();
			$( "#qts-rebuild-bar-percent" ).html( "0%" );

			// Stop button
			$( "#qts-rebuild-stop" ).click(function() {
				rt_continue = false;
				$( '#qts-rebuild-stop' ).val( "<?php echo str_replace( '"', '\"', __( 'Stopping...', 'qts-rebuild-meta-slug' ) ); ?>" );
			});

			// Clear out the empty list element that's there for HTML validation purposes
			$( "#qts-rebuildlist li" ).remove();

			// Called after each resize. Updates debug information and the progress bar.
			function qts_rebuildUpdateStatus(id, success, response ) {
				$( "#qts-rebuild-bar" ).progressbar( "value", (rt_count / rt_total ) * 100);
				$( "#qts-rebuild-bar-percent" ).html(Math.round((rt_count / rt_total ) * 1000) / 10 + "%" );
				$( "#qts-rebuild-debug-position" ).html( position );
				$( "#qts-rebuild-debug-batch" ).html( batch );
				rt_count = rt_count + 1;

				if( position == posts_per_batch ){
					position = 1;
					batch += 1;
					time_to_pause = true;
				} else {
					position += 1;
				}


				if (success ) {
					rt_successes = rt_successes + 1;
					$( "#qts-rebuild-debug-successcount" ).html(rt_successes );
					$( "#qts-rebuildlist" ).append( "<li>" + response.success + "</li>" );
				}
				else {
					rt_errors = rt_errors + 1;
					rt_failedlist = rt_failedlist + ',' + id;
					$( "#qts-rebuild-debug-failurecount" ).html(rt_errors);
					$( "#qts-rebuildlist" ).append( "<li>" + response.error + "</li>" );
				}
			}

			// Called when all images have been processed. Shows the results and cleans up.
			function qts_rebuildFinishUp() {
				rt_timeend = new Date().getTime();
				rt_totaltime = Math.round((rt_timeend - rt_timestart) / 1000);

				$( '#qts-rebuild-stop' ).hide();

				if (rt_errors > 0) {
					rt_resulttext = '<?php echo $text_failures; ?>';
				} else {
					rt_resulttext = '<?php echo $text_nofailures; ?>';
				}

				$( "#message" ).html( "<p><strong>" + rt_resulttext + "</strong></p>" );
				$( "#message" ).show();
			}

			// rebuild each post
			function qts_rebuild(id) {

				$.ajax({
					type: 'POST',
					cache: false,
					url: ajaxurl,
					data: {
						action: "rebuild_meta_slug",
						id: id,
						testrun: test_run,
						languages: languages
					},
					success: function(response) {

						//Catch unknown error
						if (response === null) {
							response = {};
							response.success = false;
							response.error = 'Unknown error occured.';
						}

						if (response.success) {
							qts_rebuildUpdateStatus(id, true, response);
						} else {
							qts_rebuildUpdateStatus(id, false, response);
						}

						if (rt_posts.length && rt_continue) {
							if( time_to_pause ) {
								time_to_pause = false;
								setTimeout(function(){
									qts_rebuild(rt_posts.shift() );
								}, pause_time );
							} else {
								qts_rebuild(rt_posts.shift() );
							}
						} else {
							qts_rebuildFinishUp();
						}
					},
					error: function(response) {
						qts_rebuildUpdateStatus(id, false, response);

						if (rt_posts.length && rt_continue) {
							qts_rebuild(rt_posts.shift() );
						} else {
							qts_rebuildFinishUp();
						}
					}
				});
			}

			qts_rebuild(rt_posts.shift() );
		});
	// ]]>
	</script>
		<?php
		} else { // if no submition, display the form.
		?>
	<form method="post" action="">
		<?php wp_nonce_field( 'qts-rebuild-meta-slug' ) ?>

		<h3>All Thumbnails</h3>

		<p><?php _e( 'This process will run through every post, page and custom post types to rebuild the slugs for each enabled language.', 'qts-rebuild-meta-slug' ); ?></p>
		<?php printf(
			'<p class="qts_warning"><strong>%1$s</strong>%2$s</p>',
			'WARNING:',
			sprintf(
				__( 'This will replace existing slug meta data. All the previous QTS slugs will be rebuilt according to the rules specified in the <a href="%s">QTS settings page</a>.', 'qts' ),
				'/wp-admin/options-general.php?page=qtranslate-slug-settings'
			)
		); ?>

		<p>
			<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'qts-rebuild-meta-slug' ) ?></em></p></noscript>
			<?php
			if ( isset( $_POST['qts_checkbox_field_0'] ) ) {
				$checked = $_POST['qts_checkbox_field_0'];
			} else {
				$checked = true;
			} ?>
			<table class="form-table">
				<tbody>
					<tr><th scope="row">
						<label for="qts_checkbox_field_0">Test Run?</label></th><td><input type="checkbox" name="qts_autob_settings[qts_checkbox_field_0]" id="qts_checkbox_field_0" checked="checked" value="1">
						<p><em><?php _e( 'Test the automated process, without doing any changes. It will compare the existing slug the generated slug, for each enabled language.', 'qts-rebuild-meta-slug' ); ?></em></p>
						<p><em><?php _e( 'It will also mention if its a new slug, ie., if it was empty.' ); ?></em></p>
						<p><em><?php _e( 'Uncheck this to actually change the slugs.' ); ?></em></p>
					</td></tr>
					<tr><th scope="row">
						<label for="qts_posts_per_batch">Posts per batch</label></th><td><input type="text" name="qts_autob_settings[posts_per_batch]" id="qts_posts_per_batch" value="10"><br>
					</td></tr>
					<tr><th scope="row">
						<label for="qts_pause_time">Pause between batchs ( in miliseconds ) </label></th><td><input type="text" name="qts_autob_settings[pause_time]" id="qts_pause_time" value="300">
					</td></tr>
				</tbody>
			</table>
			<input type="submit" class="button-primary hide-if-no-js" name="qts-rebuild-meta-slug" id="qts-rebuild-meta-slug" value="<?php _e( 'Rebuild All Slugs', 'qts-rebuild-meta-slug' ) ?>" />
		</p>

		</br>
	</form>
	<?php
		} // End if button
	?>
</div>

<?php
	}


	/**
	 * Process a single image ID (this is an AJAX handler)
	 *
	 * @access public
	 * @since 1.2.0
	 */
	public function ajax_process_image() {

		// Don't break the JSON result
		error_reporting( 0 );
		$id = (int) $_REQUEST['id'];

		try {

			header( 'Content-type: application/json' );

			if ( ! isset( $_REQUEST['languages'] ) ) {
				throw new Exception( __( 'Missing enabled languages.', 'qts-rebuild-meta-slug' ) );
			}
			$languages = $_REQUEST['languages'];

			if ( ! current_user_can( $this->capability ) ) {
				throw new Exception( __( 'Your user account does not have permission to rebuild meta slugs.', 'qts-rebuild-meta-slug' ) );
			}

			$post = get_post( $id );
			if ( is_null( $post ) ) {
				throw new Exception( sprintf( __( 'Failed: %d is an invalid ID.', 'qts-rebuild-meta-slug' ), $id ) );
			}

			if ( 'attachment' == $image->post_type ) {
				throw new Exception( sprintf( __( 'Failed: %d is an attachment.', 'qts-rebuild-meta-slug' ), $id ) );
			}
			// worst case scenario, it runs as a test.
			$test_run = isset( $_REQUEST['testrun'] ) ? 'true' === $_REQUEST['testrun'] : true;

			// results
			$languages_success = array();
			$languages_failed = array();
			$languages_new = array();

			foreach ( $languages as $lang ) {

				$meta_name = $this->get_meta_key( $lang );
				$meta_value = apply_filters( 'qts_validate_post_slug', null, $post, $lang );

				if ( $test_run ) {
					// is a test, just compare:
					$existing_meta_value = get_post_meta( $id, $meta_name, true );
					if ( empty( $existing_meta_value ) ) {
						$update_status = true;
						$languages_new[] = $lang;
					} else {
						$update_status = $existing_meta_value == $meta_value;
					}
				} else {
					// update_post_meta will fail if the meta value is the same
					// so it needs to be deleted to return a positive value
					delete_post_meta( $id, $meta_name );
					$update_status = update_post_meta( $id, $meta_name, $meta_value );
				}

				// (int|bool) Meta ID if the key didn't exist, true on successful update, false on failure.
				if ( false === $update_status ) {
					$languages_failed[] = $lang;
				} else {
					$languages_success[] = $lang;
					$update_status = 'success';
				}
			}

			/**
			* Display results
			*/
			$message  = sprintf(
				'<b>&quot;%1$s&quot; %2$s </b>',
				esc_html( qtranxf_use( '', get_the_title( $id ) ) ),
				sprintf( __( '( ID %s )', 'qts-rebuild-meta-slug' ), $id )
			);

			$message .= '<br/>';
			//$message .= sprintf( __( '<code>Updated: %s</code><br />', 'qts-rebuild-meta-slug' ), $update_status );
			$languages_failed_count = count( $languages_failed );
			if ( count( $languages_success ) > 0 ) {
				$message .= sprintf( '<br />' . __( 'Success: %s', 'qts-rebuild-meta-slug' ), implode( ', ', $languages_success ) );
			}
			if ( count( $languages_new ) > 0 ) {
				$message .= sprintf( '<br />' . __( 'New: %s', 'qts-rebuild-meta-slug' ), implode( ', ', $languages_new ) );
			}
			if ( $languages_failed_count > 0 ) {
				$message .= sprintf(
					'<br /><b><span style="color: #DD3D36;">' . __( 'Failed: %s', 'qts-rebuild-meta-slug' ) . '</span></b>',
					implode( ', ', $languages_failed )
				);
			}
			if ( $languages_failed_count > 0 ) {
				$this->die_json( array( 'error' => sprintf( '<div id="message" class="error fade"><p>%s</p></div>', $message ) ) );
			} else {
				$this->die_json( array( 'success' => sprintf( '<div id="message" class="updated fade"><p>%s</p></div>', $message ) ) );
			}
		} catch ( Exception $e ) {
			$this->die_json(
				array(
					'error' => sprintf(
						'<b><span style="color: #DD3D36;">%1$s<br />%2$s</span></b>',
						sprintf(
							__( '(ID %1$s)', 'qts-rebuild-meta-slug' ),
							$id
						),
						$e->getMessage()
					),
				)
			);
		}
		exit;
	}

	/**
	 * Exit with json encoded fail message
	 *
	 * @param string #message
	 * @access public
	 * @since 1.2.0
	 */
	public function die_json( $message ) {
		die( json_encode( $message ) );
	}

}


/**
 * Start
 */
function qts_rebuild_meta_slug() {
	global $qts_rebuild_meta_slug;
	$qts_rebuild_meta_slug = new QTS_RebuildMetaSlug();
}
add_action( 'init', 'qts_rebuild_meta_slug' );


?>
