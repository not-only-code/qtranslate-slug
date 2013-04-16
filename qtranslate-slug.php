<?php
/*
Plugin Name: qTranslate slug
Plugin URI: http://not-only-code.github.com/qtranslate-slug/
Description: Allows to define a slug for each language and some qTranslate bug fixes
Version: 1.1.5
Author: Carlos Sanz Garcia
Author URI: http://github.com/not-only-code
*/


////////////////////////////////////////////////////////////////////////////////////////

if ( !function_exists('_debug') ):
function _debug( $message ) {
	   
	if ( WP_DEBUG === true ):
		 
		if ( is_array( $message ) || is_object( $message ) ) {
			
			error_log( print_r( $message, true ) );
			
		} else {
			
			error_log( $message );
		}
			 
	 endif;
}
endif;
////////////////////////////////////////////////////////////////////////////////////////

// testing push from cloned repo


/**
 * QtranslateSlugWidget class
 *
 * @since 1.0
 */
class QtranslateSlugWidget extends WP_Widget {
	 
	function QtranslateSlugWidget() {
		$widget_ops = array('classname' => 'qts_widget', 'description' => __('Allows your visitors to choose a Language.','qtranslate') );
		$this->WP_Widget('qtranslateslug', __('Language selector', 'qts'), $widget_ops);
	}
	
 	function widget($args, $instance) {
 		extract($args);
		
 		echo $before_widget;
 		$title = empty($instance['title']) ? __('Language', 'qtranslate') : apply_filters('widget_title', $instance['title']);
 		$hide_title = empty($instance['hide-title']) ? false : 'on';
 		$type = $instance['type'];
		$short_text = ($instance['short_text'] == 'on') ? true : false ;
		
 		if( $type!='text' && $type!='image' && $type!='both' && $type!='dropdown' ) $type='text';

 		if( $hide_title!='on')
			echo $before_title . $title . $after_title;
		
 		qts_language_menu($type, array( 'id' => $this->id, 'short' => $short_text ) );
		
 		echo $after_widget;
 	}
	
 	function update($new_instance, $old_instance) {
 		$instance = $old_instance;
 		$instance['title'] = $new_instance['title'];
 		$instance['hide-title'] = $new_instance['hide-title'];
 		$instance['type'] = $new_instance['type'];
		$instance['short_text'] = $new_instance['short_text'];

 		return $instance;
 	}
	
 	function form($instance) {
 		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'hide-title' => false, 'type' => 'text' ) );
 		$title = $instance['title'];
 		$hide_title = $instance['hide-title'];
 		$type = $instance['type'];
		$short_text = $instance['short_text'];
 ?>
 		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'qtranslate'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
 		<p><label for="<?php echo $this->get_field_id('hide-title'); ?>"><?php _e('Hide Title:', 'qtranslate'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('hide-title'); ?>" name="<?php echo $this->get_field_name('hide-title'); ?>" <?php echo ($hide_title=='on')?'checked="checked"':''; ?>/></label></p>
 		<p><?php _e('Display:', 'qtranslate'); ?></p>
 		<p><label for="<?php echo $this->get_field_id('type'); ?>1"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>1" value="text"<?php echo ($type=='text')?' checked="checked"':'' ?>/> <?php _e('Text only', 'qtranslate'); ?></label></p>
 		<p><label for="<?php echo $this->get_field_id('type'); ?>2"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>2" value="image"<?php echo ($type=='image')?' checked="checked"':'' ?>/> <?php _e('Image only', 'qtranslate'); ?></label></p>
 		<p><label for="<?php echo $this->get_field_id('type'); ?>3"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>3" value="both"<?php echo ($type=='both')?' checked="checked"':'' ?>/> <?php _e('Text and Image', 'qtranslate'); ?></label></p>
 		<p><label for="<?php echo $this->get_field_id('type'); ?>4"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>4" value="dropdown"<?php echo ($type=='dropdown')?' checked="checked"':'' ?>/> <?php _e('Dropdown Box', 'qtranslate'); ?></label></p>
 		<p><label for="<?php echo $this->get_field_id('short_text'); ?>"><?php _e('Show short name (en):', 'qts'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('short_text'); ?>" name="<?php echo $this->get_field_name('short_text'); ?>" <?php checked($short_text, 'on')  ?>/></label></p>
 		<p><?php _e('Display:', 'qtranslate'); ?></p>
		
 <?php
 	}
}
////////////////////////////////////////////////////////////////////////////////////////



/**
 * QtranslateSlug class
 *
 * @since 1.0
 */
class QtranslateSlug {
	
	
	/**
	 * array with old data system
	 *
	 * @var bool
	 *
	 * @since 1.0
	 */
	private $old_data = null;
	
	
	
	/**
	 * stores permalink_structure option, for save queries to db
	 *
	 * @var string
	 *
	 * @since 1.0
	 */
	private $permalink_structure;
	
	
	
	/**
	 * Stores options slugs from database
	 *
	 * @var array
	 *
	 * @since 1.0
	 */
	protected $options;
	
	
	
	/**
	 * Variable used to override the language
	 *
	 * @var string
	 *
	 * @since 1.0
	 */
	private $lang = false;
	
	
	
	/**
	 * slug in meta_key name in meta tables
	 *
	 * @var string
	 *
	 * @since 1.0
	 */
	private $meta_key = "_qts_slug_%s";
	
	
	
	/**
	 * Array of translated versions of the current url
	 *
	 * @var array
	 *
	 * @since 1.0
	 */
	private $current_url = array();
	
	
	
	/**
	 * return the current / temp language
	 *
     * @since 1.0
	 */
	private function get_lang() {
		global $q_config;
		
		return ($this->lang) ? $this->lang : $q_config['language'];
	}
	
	
	
	/**
	 * getter: options
	 *
 	 * @since 1.0
	 */
	public function get_options() {
		$this->set_options();
		return $this->options;
	}
	
	
	
	/**
	 * setter: options | permalink_structure
	 *
 	 * @since 1.0
	 */
	public function set_options() {
		if (empty($this->options))
			$this->options = get_option(QTS_OPTIONS_NAME);
		
		if (!$this->options)
			add_option(QTS_OPTIONS_NAME, array());
		
		if (is_null($this->permalink_structure))
			$this->permalink_structure = get_option('permalink_structure');
	}
	
	
	
	/**
	 * setter: options | permalink_structure
	 *
 	 * @since 1.0
	 */
	public function save_options($new_options = false) {
		if (!$new_options || empty($new_options)) return;
		
		if (count($this->options) != count($new_options)) return;
		
		update_option(QTS_OPTIONS_NAME, $new_options);
		$this->options = $new_options;
	}
	
	
	
	/**
	 * getter: meta key
	 *
	 * @since 1.0
	 */
	public function get_meta_key( $force_lang = false ) {
		global $q_config;
		
		$lang = $this->get_lang();
		
		if ($force_lang) $lang = $force_lang;
	   
		return sprintf($this->meta_key, $lang); // returns: _qts_slug_en
	}
	
	
	
	/**
	 * check dependences for activation
	 *
 	 * @since 1.0
	 */
	static function block_activate() {
		global $wp_version;
		
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
		
		return ( version_compare($wp_version, "3.3", "<" ) || !is_plugin_active('qtranslate/qtranslate.php') );
	}
	
	
	
	/**
	 * check if exists qtranslate and do the installation, support multisite
	 *
 	 * @since 1.0
	 */
	public function install() {
		global $wpdb;
		
		if ( self::block_activate() ) return;
		
		if ( function_exists('is_multisite') && is_multisite() ) {

			if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
				
				$old_blog = $wpdb->blogid;
				$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					$this->activate();
				}
				switch_to_blog($old_blog);
				return;
			}
		}
		
		$this->activate();
	}
	
	
	
	/**
	 * activates and do the installation
	 *
 	 * @since 1.0
	 */
	private function activate() {
		global $wp_rewrite;

		$this->set_options();
		
		$qts_version = get_option('qts_version');
		
		// checks version and do the installation
		if ( !$qts_version || $qts_version != QTS_VERSION ) {
			
			// install termmeta table using functions from Simple-Term-Meta ( http://wordpress.org/extend/plugins/simple-term-meta/ )
			install_term_meta_table();
			
			// update installed option	
			update_option('qts_version', QTS_VERSION);
		}
		
		// regenerate rewrite rules in db
		add_action( 'generate_rewrite_rules', array($this, 'modify_rewrite_rules') );
		flush_rewrite_rules();
	}
	
	
	
	/**
	 * actions when deactivating the plugin
	 *
 	 * @since 1.0
	 */
	public function deactivate() {
		global $wp_rewrite;
		
		// regenerate rewrite rules in db
		remove_action( 'generate_rewrite_rules', array($this, 'modify_rewrite_rules') );
		$wp_rewrite->flush_rules();
	}
	
	
	
	/**
	 * admin notice: update your old data 
	 *
 	 * @since 1.0
	 */
	function notice_update(){
		global $current_screen;
		
		if ($current_screen->id != 'settings_page_qtranslate-slug-settings'):
		
	    echo "<div class=\"updated\">" . PHP_EOL;
		echo "<p><strong>Qtranslate Slug:</strong></p>" . PHP_EOL;
		printf("<p>%s <a href=\"%s\" class=\"button\">%s</a></p>", __('Please update your old data to the new system.', 'qts'), add_query_arg(array('page' => 'qtranslate-slug-settings'), 'options-general.php'), __('upgrade now', 'qts')) . PHP_EOL;
	    echo "</div>" . PHP_EOL;
		
		endif;
	}
	
	
	
	/**
	 * admin notice: update your old data 
	 *
 	 * @since 1.0
	 */
	function notice_dependences(){
		global $current_screen;
		
	    echo "<div class=\"error\">" . PHP_EOL;
		echo "<p><strong>Qtranslate Slug:</strong></p>" . PHP_EOL;
		echo "<p>" . __('This plugin requires at least <strong>Wordpress 3.3</strong> and <strong>Qtranslate(2.5.8 or newer)</strong>', 'qts') . "</p>" . PHP_EOL;
	    echo "</div>" . PHP_EOL;
	}
	
	
	
	/**
	 * checks if old table 'qtranslate_slug' exists and is not empty
	 * 
	 * @return object | false
	 *
 	 * @since 1.0
	 */
	public function check_old_data() {
		global $wpdb;
		
		if ($this->old_data === false) return false;
					
		$table_name = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}qtranslate_slug'");
			
		if (!empty($table_name))
			$this->old_data = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}qtranslate_slug");
		
		if ( empty($table_name) || empty($this->old_data) )
			$this->old_data = false;
		
		return $this->old_data;
	}
	
	
	
	/**
	 * actions when deactivating the plugin
	 *
 	 * @since 1.0
	 */
	private function check_old_versions() {
		
		if ( $this->check_old_data() )
			add_action('admin_notices', array($this, 'notice_update'));
	}
	
	
	
	/**
	 * Initialise the Class with all hooks
	 *
	 * @since 1.0
	 */
	function init() {
		
		load_plugin_textdomain( 'qts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		
		// checking plugin activate
		if ( self::block_activate() ) {
			if (is_admin()) 
				add_action('admin_notices', array($this, 'notice_dependences'));
			return;
		}
		
		// caching qts options
		$this->set_options();
		
		if ( is_admin() ) {
			
			$this->check_old_versions();
			
			// add filters
			add_filter( 'qts_validate_post_slug', array($this, 'validate_post_slug'), 0, 3 );
			add_filter( 'qts_validate_post_slug', array($this, 'unique_post_slug'), 1, 3 );
			add_filter( 'qts_validate_term_slug', array($this, 'validate_term_slug'), 0, 3 );
			add_filter( 'qts_validate_term_slug', array($this, 'unique_term_slug'), 1, 3 );
			
			// admin actions
			add_action( 'admin_menu', array($this, 'add_slug_meta_box') );
			add_action( 'save_post', array($this, 'save_postdata'), 605, 2 );
			add_action( 'delete_term', array($this, 'delete_term'), 0, 3);
			add_action( 'created_term', array($this, 'save_term'), 605, 3);
			add_action( 'edited_term', array($this, 'save_term'), 605, 3 );
			add_action( 'admin_head', array($this, 'hide_slug_box'), 900 );
			
			add_action( 'init', array($this, 'taxonomies_hooks'), 805 );
			
			add_action( 'wp_dashboard_setup', array($this, 'remove_dashboard_widgets') );
			add_action( 'admin_head', array($this, 'hide_quick_edit'), 600 );
			add_action( 'admin_init', array($this, 'fix_nav_menu') );
			
		} else {
			
			add_filter( 'request', array($this, 'filter_request') );
		}
		
		add_filter( 'query_vars', array($this, 'query_vars'));
		add_action( 'generate_rewrite_rules', array($this, 'modify_rewrite_rules') );
			
		// remove some Qtranslate filters
	    remove_filter( 'page_link', 	'qtrans_convertURL' );
	    remove_filter( 'post_link', 	'qtrans_convertURL' );
	    remove_filter( 'category_link', 'qtrans_convertURL' );
	    remove_filter( 'tag_link', 		'qtrans_convertURL' );
		
		add_filter( 'qts_permastruct' , array($this, 'get_extra_permastruct'), 0, 2);
		add_filter( 'qts_url_args',		array($this, 'parse_url_args'), 0, 1);
		add_filter( 'home_url',			array($this, 'home_url'), 10, 4);	
		add_filter( 'post_type_link',	array($this, 'post_type_link'), 600, 4 );
		add_filter( 'post_link',		array($this, 'post_link'), 0, 3 );
		add_filter( '_get_page_link',	array($this, '_get_page_link'), 0, 2 );
		add_filter( 'term_link',		array($this, 'term_link'), 600, 3 );
		
		add_filter( 'single_term_title', 'qtrans_useTermLib', 805 );
		add_filter( 'get_blogs_of_user', array($this, 'blog_names'), 1  );
		add_action( 'widgets_init', array($this, 'widget_init'), 100 );
	}
	
	
	
	/**
	 * Adds news rules to translate the URL bases, this function must be called on flush_rewrite or 'flush_rewrite_rules' 
	 * 
	 * @param object $wp_rewrite
	 *
	 * @since 1.0
	 */	
 	public function modify_rewrite_rules() {
 		global $wp_rewrite;
		
 		// post types rules
 		$post_types = get_post_types( array('_builtin' => false ), 'objects');
 		foreach ( $post_types as $post_type )
 			$this->generate_extra_rules( $post_type->name );
		
 		// taxonomies rules
 		$taxonomies = $this->get_public_taxonomies();
 		foreach ( $taxonomies as $taxonomy )
 			$this->generate_extra_rules( $taxonomy->name );
 	}
	
	
	
	/**
	 * Helper: news rules to translate the URL bases
	 * 
	 * @param string $name name of extra permastruct
	 * @param string $type 'post_type' or 'taxonomy'
	 *
	 * @since 1.0
	 */
	private function generate_extra_rules( $name = false ) {
		global $wp_rewrite, $q_config;
		
		foreach ($q_config['enabled_languages'] as $lang):
			
			if ( $base = $this->get_base_slug( $name, $lang) ):
				
				$struct = $wp_rewrite->extra_permastructs[$name];
				
				if ( is_array( $struct ) ) {
					if ( count( $struct ) == 2 )
						$rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%", $struct[1] );
					else
						$rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%", $struct['ep_mask'], $struct['paged'], $struct['feed'], $struct['forcomments'], $struct['walk_dirs'], $struct['endpoints'] );
				} else {
					$rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%" );
				}
				
				$wp_rewrite->rules = array_merge($rules, $wp_rewrite->rules);
			
			endif;
			
		endforeach;
	}
	
	
	
	/**
	 * Helper that gets a base slug stored in options
	 * 
	 * @param string $name of extra permastruct
	 * @return string base slug for 'post_type' and 'language' or false
	 *
	 * @since 1.0
	 */
	public function get_base_slug($name = false, $lang = false) {
		
		if ( !$name || !$lang ) return false;
		
		if ( taxonomy_exists($name) ) {
			$type = 'taxonomy';
		} else if ( post_type_exists($name) ) {
			$type = 'post_type';
		} else {
			return false;
		}
		
		$qts_options = $this->get_options();
		
		$option_name = QTS_PREFIX . $type . '_' . $name;
		
		if ( !isset($qts_options[$option_name]) || empty($qts_options[$option_name]) ) return false;
		
		$option = $qts_options[$option_name][$lang];
		
		if (isset($option))  return $option;
		
		return false;
	}
	
	
	
	/**
	 * Helper: returns public built-in and not built-in taxonomies
	 * 
	 * @return array of public taxonomies objects
	 *
	 * @since 1.0
	 */
	private function get_public_taxonomies() {
		
		$builtin = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => true ), 'object'); 
		$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'object' ); 
		
		return array_merge( $builtin, $taxonomies );
	}
	
	
	
	/**
	 * parse and adds $_GET args passed to an url
	 * 
	 * @param string $url parameters
	 * @param string $lang processed
	 * @return string converted url
	 *
	 * @since 1.0
	 */
	public function parse_url_args( $url ) {
		global $q_config;
		
		if (is_admin()) return $url;
		
		$url = preg_replace('/&amp;/', '&', $url);
		
		// if no permalink structure ads ?lang=en
		$base_query = parse_url($q_config['url_info']['original_url']);
		$base_args = isset($base_query['query']) ? wp_parse_args($base_query['query']) : array();
		
		if ( empty($this->permalink_structure) || $q_config['url_mode'] == 1 ) 
			$base_args['lang'] = $this->get_lang();

		// rebulid query with all args
		$url = add_query_arg($base_args, $url);

		$url = str_replace('/?', '?', $url); // hack: improve this code
		$url = str_replace('?', '/?', $url); // hack: improve this code

		return $url;
	}
	
	
	
	/**
	 * Fix get_page_by_path when querying vars
	 * 
	 * @param $query_vars objec query vars founded
	 * @return object $query_vars processed
	 *
	 * @since 1.0
	 */
	public function query_vars( $query_vars ) {
		global $wp, $wp_rewrite;

		$wp->query_vars = array();
		$post_type_query_vars = array();

		// Fetch the rewrite rules.
		$rewrite = $wp_rewrite->wp_rewrite_rules();

		if ( ! empty($rewrite) ) {
			// If we match a rewrite rule, this will be cleared.
			$error = '404';
			$wp->did_permalink = true;

			if ( isset($_SERVER['PATH_INFO']) )
				$pathinfo = $_SERVER['PATH_INFO'];
			else
				$pathinfo = '';
			$pathinfo_array = explode('?', $pathinfo);
			$pathinfo = str_replace("%", "%25", $pathinfo_array[0]);
			$req_uri = $_SERVER['REQUEST_URI'];
			$req_uri_array = explode('?', $req_uri);
			$req_uri = $req_uri_array[0];
			$self = $_SERVER['PHP_SELF'];
			$home_path = parse_url(home_url());
			
			if ( isset($home_path['path']) )
				$home_path = $home_path['path'];
			else
				$home_path = '';
			$home_path = trim($home_path, '/');

			// Trim path info from the end and the leading home path from the
			// front. For path info requests, this leaves us with the requesting
			// filename, if any. For 404 requests, this leaves us with the
			// requested permalink.
			$req_uri = str_replace($pathinfo, '', $req_uri);
			$req_uri = trim($req_uri, '/');
			$req_uri = preg_replace("|^$home_path|", '', $req_uri);
			$req_uri = trim($req_uri, '/');
			$pathinfo = trim($pathinfo, '/');
			$pathinfo = preg_replace("|^$home_path|", '', $pathinfo);
			$pathinfo = trim($pathinfo, '/');
			$self = trim($self, '/');
			$self = preg_replace("|^$home_path|", '', $self);
			$self = trim($self, '/');

			// The requested permalink is in $pathinfo for path info requests and
			//  $req_uri for other requests.
			if ( ! empty($pathinfo) && !preg_match('|^.*' . $wp_rewrite->index . '$|', $pathinfo) ) {
				$request = $pathinfo;
			} else {
				// If the request uri is the index, blank it out so that we don't try to match it against a rule.
				if ( $req_uri == $wp_rewrite->index )
					$req_uri = '';
				$request = $req_uri;
			}
			
			$wp->request = $request;

			// Look for matches.
			$request_match = $request;
			if ( empty( $request_match ) ) {
				// An empty request could only match against ^$ regex
				if ( isset( $rewrite['$'] ) ) {
					$wp->matched_rule = '$';
					$query = $rewrite['$'];
					$matches = array('');
				}
			} else if ( $req_uri != 'wp-app.php' ) {
				foreach ( (array) $rewrite as $match => $query ) {
					// If the requesting file is the anchor of the match, prepend it to the path info.
					if ( ! empty($req_uri) && strpos($match, $req_uri) === 0 && $req_uri != $request )
						$request_match = $req_uri . '/' . $request;

					if ( preg_match("#^$match#", $request_match, $matches) ||
						preg_match("#^$match#", urldecode($request_match), $matches) ) {

						if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
							// this is a verbose page match, lets check to be sure about it
							if ( ! $page_foundid = $this->get_page_by_path( $matches[ $varmatch[1] ] ) ) {
								continue;
							} else {
								wp_cache_set('qts_page_request', $page_foundid); // caching query :)
							}		
						}

						// Got a match.
						$wp->matched_rule = $match;
						break;
					}
				}
			}

			if ( isset( $wp->matched_rule ) ) {
				// Trim the query of everything up to the '?'.
				$query = preg_replace("!^.+\?!", '', $query);

				// Substitute the substring matches into the query.
				$query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

				$wp->matched_query = $query;

				// Parse the query.
				parse_str($query, $perma_query_vars);

				// If we're processing a 404 request, clear the error var
				// since we found something.
				unset( $_GET['error'] );
				unset( $error );
			}

			// If req_uri is empty or if it is a request for ourself, unset error.
			if ( empty($request) || $req_uri == $self || strpos($_SERVER['PHP_SELF'], 'wp-admin/') !== false ) {
				unset( $_GET['error'] );
				unset( $error );

				if ( isset($perma_query_vars) && strpos($_SERVER['PHP_SELF'], 'wp-admin/') !== false )
					unset( $perma_query_vars );

				$wp->did_permalink = false;
			}
		}

		return count(array_diff($query_vars, $wp->public_query_vars)) > 0 ? $query_vars : $wp->public_query_vars;
	}
	
	
	
	/**
	 * Function called when query parameters are processed by Wordpress.
	 * 
	 * @param $query query parameters
	 * @return array() $query processed
	 *
	 * @since 1.0
	 */
	function filter_request( $query ) {
		global $q_config, $wp_query, $wp;
		
		if (isset($wp->matched_query))
			$query = wp_parse_args($wp->matched_query);
		
		foreach (get_post_types() as $post_type) 
			if ( array_key_exists($post_type, $query) && !in_array($post_type, array('post', 'page')) ) $query['post_type'] = $post_type;
		
		$page_foundit = false;
		
		// -> page
		if ( isset($query['pagename']) || isset($query['page_id']) ):
			
			$page = wp_cache_get('qts_page_request');
			if (!$page) 
				$page = isset($query['page_id']) ? get_page($query['page_id']) : $this->get_page_by_path($query['pagename']);
			
			if (!$page) return $query;
			$id = $page->ID;
			$cache_array = array($page);
			update_post_caches($cache_array, 'page'); // caching query :)
			wp_cache_delete('qts_page_request');
			$query['pagename'] = get_page_uri($page);
			$function = 'get_page_link';
		
		// -> custom post type
		elseif ( isset($query['post_type']) ):
			
			$page_slug = ( isset($query['name']) && !empty($query['name']) ) ? $query['name'] : $query[$query['post_type']];
			$page = $this->get_page_by_path($page_slug, OBJECT, $query['post_type']);
			if (!$page) return $query;
			$id = $page->ID;
			$cache_array = array($page);
			update_post_caches($cache_array, $query['post_type']); // caching query :)
			$query['name'] = $query[$query['post_type']] = get_page_uri($page); 
			$function = 'get_post_permalink';
		
		// -> post
		elseif ( isset($query['name']) || isset($query['p']) ):
			
			$post = isset($query['p']) ? get_post($query['p']) : $this->get_page_by_path($query['name'], OBJECT, 'post');
			if (!$post) return $query;
			$query['name'] = $post->post_name;
			$id = $post->ID;
			$cache_array = array($post);
			update_post_caches($cache_array);
			$function = 'get_permalink';
			
		// -> category
		elseif ( ( isset($query['category_name']) || isset($query['cat'])) ):
			if ( isset($query['category_name']) ) 
				$term_slug = $this->get_last_slash( $query['category_name'] );
			$term = isset($query['cat']) ? get_term($query['cat'], 'category') : $this->get_term_by('slug', $term_slug, 'category');
			if (!$term) return $query;
			$cache_array = array($term);
			update_term_cache($cache_array, 'category'); // caching query :)
			$id = $term->term_id;
			$query['category_name'] = $term->slug; // uri
			$function = 'get_category_link';
		
		// -> tag
		elseif ( isset($query['tag']) ):
			
			$term = $this->get_term_by('slug', $query['tag'], 'post_tag');
			if (!$term) return $query;
			$cache_array = array($term);
			update_term_cache($cache_array, 'post_tag'); // caching query :)
			$id = $term->term_id;
			$query['tag'] = $term->slug;
			$function = 'get_tag_link';
		
		endif;
		
		// -> taxonomy
		$taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false )  );
		foreach ($taxonomies as $term_name):
		if ( isset($query[$term_name]) ) {
			
			$term_slug = $this->get_last_slash( $query[$term_name] );
			$term = $this->get_term_by('slug', $term_slug, $term_name);
			if (!$term) return $query;
			$cache_array = array($term);
			update_term_cache($cache_array, $term_name); // caching query :)
			$id = $term;
			$query[$term_name] = $term->slug;
			$function = 'get_term_link';
			
		}
		endforeach;
		
		// -> home url
		if ( empty($query) ):
			
			$function = 'home_url';
			$id = '';
			
		endif;
		
		if ( isset($function) ):
				
		// parse all languages links
		foreach( $q_config['enabled_languages'] as $lang ) {
			
			$this->lang = $lang;
			$this->current_url[$lang] = apply_filters('qts_url_args', call_user_func($function, $id));
			
		}
		$this->lang = false;
		
		endif;
		
		return $query;
	}
	
	
	
	/**
	 * Parse a hierarquical name and extract the last one
	 *
	 * @param string $lang Page path
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_current_url( $lang = false ) {
		global $q_config;
		
		if (!$lang) $lang = $this->get_lang();
		
		if (isset($this->current_url[$lang]) && !empty($this->current_url[$lang]))
			return $this->current_url[$lang];
		
		return '';
	}
	
	
	
	/**
	 * Parse a hierarquical name and extract the last one 
	 *
	 * @param string $slug Page path
	 * @return string
	 *
	 * @since 1.0
	 */
	private function get_last_slash($slug) {
		
		$slug = rawurlencode( urldecode( $slug ) );
		$slug = str_replace('%2F', '/', $slug);
		$slug = str_replace('%20', ' ', $slug);
			
		return array_pop( explode('/', $slug) );
	}
	
	
	
	/**
	 * Retrieves a page id given its path.
	 *
	 * @param string $page_path Page path
	 * @param string $output Optional. Output type. OBJECT, ARRAY_N, or ARRAY_A. Default OBJECT.
	 * @param string $post_type Optional. Post type. Default page.
	 * @return mixed Null when complete.
	 *
	 * @since 1.0
	 */
	private function get_page_id_by_path($page_path, $output = OBJECT, $post_type = 'page') {
		global $wpdb;

		$page_path = rawurlencode(urldecode($page_path));
		$page_path = str_replace('%2F', '/', $page_path);
		$page_path = str_replace('%20', ' ', $page_path);
		$parts = explode( '/', trim( $page_path, '/' ) );
		$parts = array_map( 'esc_sql', $parts );
		$parts = array_map( 'sanitize_title_for_query', $parts );
		$in_string = "'". implode( "','", $parts ) . "'";
		$meta_key = $this->get_meta_key();
		$post_type_sql = $post_type;
		$wpdb->escape_by_ref( $post_type_sql );
		
		$pages = $wpdb->get_results( "SELECT $wpdb->posts.ID, $wpdb->posts.post_parent, $wpdb->postmeta.meta_value FROM $wpdb->posts,$wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' AND $wpdb->postmeta.meta_value IN ($in_string) AND ($wpdb->posts.post_type = '$post_type_sql' OR $wpdb->posts.post_type = 'attachment')", OBJECT_K );

		$revparts = array_reverse( $parts );

		$foundid = 0;
		foreach ( (array) $pages as $page ) {
			if ( $page->meta_value == $revparts[0] ) {
				$count = 0;
				$p = $page;
				while ( $p->post_parent != 0 && isset( $pages[ $p->post_parent ] ) ) {
					$count++;
					$parent = $pages[ $p->post_parent ];
					if ( ! isset( $revparts[ $count ] ) || $parent->meta_value != $revparts[ $count ] )
						break;
					$p = $parent;
				}

				if ( $p->post_parent == 0 && $count+1 == count( $revparts ) && $p->meta_value == $revparts[ $count ] ) {
					$foundid = $page->ID;
					break;
				}
			}
		}
		
		if ( $foundid ) {
			return $foundid;
			
		} else {
			
			$last_part = array_pop($parts);
			$page_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '$last_part' AND (post_type = '$post_type_sql' OR post_type = 'attachment')" );
			
			if ( $page_id )
				return $page_id;
		}

		return null;
	}
	
	
	
	
	/**
	 * Retrieves a page given its path.
	 *
	 * @param string $page_path Page path
	 * @param string $output Optional. Output type. OBJECT, ARRAY_N, or ARRAY_A. Default OBJECT.
	 * @param string $post_type Optional. Post type. Default page.
	 * @return mixed Null when complete.
	 *
	 * @since 1.0
	 */
	private function get_page_by_path($page_path, $output = OBJECT, $post_type = 'page') {
		
		$foundid = $this->get_page_id_by_path($page_path, $output, $post_type);
		if ( $foundid )
			return get_page( $foundid, $output );
		
		return null;
	}
	
	
	
	/**
	 * Ignores if the mod_rewrite func is the caller
	 *
	 * @return boolean
	 *
	 * @since 1.0
	 */
	private function ignore_rewrite_caller() {
		
		$backtrace = debug_backtrace();
		
		$ignore_functions = array('mod_rewrite_rules', 'save_mod_rewrite_rules', 'flush_rules', 'rewrite_rules', 'wp_rewrite_rules', 'query_vars');
		
		if ( isset($backtrace['function']) ) {
			if (in_array($backtrace['function'], $ignore_functions)) return true;
		} else {
			foreach ($backtrace as $trace) if ( isset($trace['function']) && in_array($trace['function'], $ignore_functions) ) return true;
		}
		
		return false;
	}
	
	
	/**
	 * Retrieve the home url for a given site.
	 *
	 * @param  int $blog_id   (optional) Blog ID. Defaults to current blog.
	 * @param  string $path   (optional) Path relative to the home url.
	 * @param  string $scheme (optional) Scheme to give the home url context. Currently 'http', 'https'.
	 * @return string Home url link with optional path appended.
	 *
	 * @since 1.0
	 */
	public function home_url($url, $path, $scheme, $blog_id) {

		if ( !in_array( $scheme, array( 'http', 'https' ) ) )
			$scheme = is_ssl() && !is_admin() ? 'https' : 'http';

		if ( empty( $blog_id ) || !is_multisite() )
			$url = get_option( 'home' );
		else
			$url = get_blog_option( $blog_id, 'home' );

		if ( 'http' != $scheme )
			$url = str_replace( 'http://', "$scheme://", $url );
		
		$ignore_caller = $this->ignore_rewrite_caller();
		
		if ( !empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false )
			$url .= '/' . ltrim( $path, '/' );
			
		if ( !$ignore_caller ) 
			$url = qtrans_convertURL($url, $this->get_lang(), true);
		
		return $url;
	}
	
	
	
	/**
	 * Filter that changes the permastruct depending
	 * 
	 * @param string $permastruct default permastruct given b wp_rewrite
	 * @param string $name the name of the extra permastruct
	 * @return string processed permastruct
	 *
	 * @since 1.0
	 */
	public function get_extra_permastruct( $permastruct = false, $name = false ) {
		
		if ( !$name || !$permastruct ) return '';
		
		if ( $base = $this->get_base_slug($name, $this->get_lang()) )
			return "/$base/%$name%";
		
		return $permastruct;
	}
		
	
	
	/**
	 * Filter that translates the slug parts in a page link
	 * 
	 * @param $link the link for the page generated by Wordpress
	 * @param $id the id of the page
	 * @return the link translated
	 *
	 * @since 1.0
	 */
	public function post_type_link( $link, $post, $leavename, $sample ) {
		global $wp_rewrite;

		if ( is_wp_error( $post ) )
			return $post;

		$post_link = apply_filters( 'qts_permastruct', $wp_rewrite->get_extra_permastruct($post->post_type), $post->post_type);
		
		$slug = get_post_meta( $post->ID, $this->get_meta_key(), true );
		if (!$slug) $slug =  $post->post_name;

		$draft_or_pending = isset($post->post_status) && in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );

		$post_type = get_post_type_object($post->post_type);

		if ( !empty($post_link) && ( !$draft_or_pending || $sample ) ) {
			if ( ! $leavename ) {
				if ( $post_type->hierarchical )
					$slug = $this->get_page_uri($post->ID);
				$post_link = str_replace("%$post->post_type%", $slug, $post_link);
			}
			
			$post_link = home_url( user_trailingslashit($post_link) );
			
		} else {
			
			if ( $post_type->query_var && ( isset($post->post_status) && !$draft_or_pending ) )
				$post_link = add_query_arg($post_type->query_var, $slug, '');
			else
				$post_link = add_query_arg(array('post_type' => $post->post_type, 'p' => $post->ID), '');
			
			$post_link = home_url($post_link);
		}

		return $post_link;
	}
	
	
	
	/**
	 * Filter that translates the slug in a post link
	 * 
	 * @param $link the link generated by wordpress
	 * @param $post the post data
	 * @param $leavename parameter used by get_permalink. Whether to keep post name or page name. 
	 * @return the link translated
	 *
	 * @since 1.0
	 */
	public function post_link( $link, $post, $leavename ) {
		global $q_config;
		
		$rewritecode = array(
			'%year%',
			'%monthnum%',
			'%day%',
			'%hour%',
			'%minute%',
			'%second%',
			$leavename? '' : '%postname%',
			'%post_id%',
			'%category%',
			'%author%',
			$leavename? '' : '%pagename%',
		);

		if ( empty($post->ID) )
			return false;

		$permalink = $this->permalink_structure;

		if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft')) ) {
			$unixtime = strtotime($post->post_date);
			
			$category = '';
			if ( strpos($permalink, '%category%') !== false ) {
				$cats = get_the_category($post->ID);
				if ( $cats ) {
					usort($cats, '_usort_terms_by_ID'); // order by ID
					
					$category = get_term_meta($cats[0]->term_id, $this->get_meta_key(), true );
					if (!$category) $category = $cats[0]->slug;
					
					if ( $parent = $cats[0]->parent )
						$category = $this->get_category_parents($parent, false, '/', true) . $category;
				}
				// show default category in permalinks, without
				// having to assign it explicitly
				if ( empty($category) ) {
					$default_category = get_category( get_option( 'default_category' ) );
					
					$default_category_slug = get_term_meta($default_category->term_id, $this->get_meta_key(), true );
					if (!$default_category_slug) $default_category_slug = $default_category->slug;
					
					$category = is_wp_error( $default_category ) ? '' : $default_category_slug;
				}
			}

			$author = '';
			if ( strpos($permalink, '%author%') !== false ) {
				$authordata = get_userdata($post->post_author);
				$author = $authordata->user_nicename;
			}

			$date = explode(" ",date('Y m d H i s', $unixtime));
			
			$post_slug = get_post_meta($post->ID, $this->get_meta_key(), true );
			if(!$post_slug) $post_slug = $post->post_name;
			
			$rewritereplace =
			array(
				$date[0],
				$date[1],
				$date[2],
				$date[3],
				$date[4],
				$date[5],
				$post_slug,
				$post->ID,
				$category,
				$author,
				$post_slug,
			);
			$permalink = home_url( str_replace($rewritecode, $rewritereplace, $permalink));
			if ($q_config['url_mode'] != 1)
				$permalink = user_trailingslashit($permalink, 'single');
		} else { // if they're not using the fancy permalink option
			$permalink = home_url('?p=' . $post->ID);
		}
		
		return $permalink;
	}
	
	
	
	/**
	 * Retrieve category parents with separator.
	 *
	 * @param int $id Category ID.
	 * @param bool $link Optional, default is false. Whether to format with link.
	 * @param string $separator Optional, default is '/'. How to separate categories.
	 * @param bool $nicename Optional, default is false. Whether to use nice name for display.
	 * @param array $visited Optional. Already linked to categories to prevent duplicates.
	 * @return string
	 *
	 * @since 1.0
	 */
	private function get_category_parents( $id, $link = false, $separator = '/', $nicename = false, $visited = array() ) {
		
		$chain = '';
		$parent = &get_category( $id );
		if ( is_wp_error( $parent ) )
			return $parent;

		if ( $nicename ) {

			$name = get_term_meta($parent->term_id, $this->get_meta_key(), true );
			if (!$name) $name = $parent->slug;
		} else {
			$name = $parent->name;
		}

		if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
			$visited[] = $parent->parent;
			$chain .= $this->get_category_parents( $parent->parent, $link, $separator, $nicename, $visited );
		}
		
		if ( $link )
			$chain .= '<a href="' . get_category_link( $parent->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
		else
			$chain .= $name.$separator;
		
		return $chain;
	}
	

	
	
	
	/**
	 * Filter that translates the slug parts in a page link
	 * 
	 * @param $link the link for the page generated by Wordpress
	 * @param $id the id of the page
	 * @return the link translated
	 *
	 * @since 1.0
	 */
	public function _get_page_link( $link, $id ) {
		global $post, $wp_rewrite, $q_config;

		$current_post = $post;

		if ( !$id )
			$id = (int) $post->ID;
		else
			$current_post = &get_post($id);

		$draft_or_pending = in_array( $current_post->post_status, array( 'draft', 'pending', 'auto-draft' ) );

		$link = $wp_rewrite->get_page_permastruct();

		if ( !empty($link) && ( isset($current_post->post_status) && !$draft_or_pending ) ) {
			
			$link = str_replace('%pagename%', $this->get_page_uri($id), $link);
			
			$link = trim($link, '/'); // hack
			$link = home_url("/$link/"); // hack
			
			if ($q_config['url_mode'] != 1)
				$link = user_trailingslashit($link, 'page');
			
		} else {
			
			$link = home_url("?page_id=$id");
		}

		return $link;
	}
	
	
	
	/**
	 * Builds URI for a page.
	 *
	 * Sub pages will be in the "directory" under the parent page post name.
	 *
	 * @param mixed $page Page object or page ID.
	 * @return string Page URI.
	 *
	 * @since 1.0
	 */
	private function get_page_uri($page) {
		
		if ( ! is_object($page) )
			$page = get_page($page);
		
		$uri = get_post_meta( $page->ID, $this->get_meta_key(), true );
		if (!$uri) $uri =  $page->post_name;

		// A page cannot be it's own parent.
		if ( $page->post_parent == $page->ID )
			return $uri;

		while ($page->post_parent != 0) {
			$page = get_page($page->post_parent);
			
			$page_name = get_post_meta( $page->ID, $this->get_meta_key(), true );
			if (!$page_name) $page_name = $page->post_name;
			
			$uri = $page_name . "/" . $uri;
		}

		return $uri;
	}
	
	
	
	/**
	 * Filter that translates the slug parts in a term link
	 * 
	 * @param $link the link for the page generated by Wordpress
	 * @param $term object
	 * @param $taxonomy object
	 * @return the link translated
	 *
	 * @since 1.0
	 */
	public function term_link( $link, $term, $taxonomy ) {
		global $wp_rewrite;
		
		// parse normal term names for ?tag=tagname
		if (empty($this->permalink_structure)) return $link;

		if ( !is_object($term) ) {
			if ( is_int($term) ) {
				$term = &get_term($term, $taxonomy);
			} else {
				$term = $this->get_term_by('slug', $term, $taxonomy);
			}
		}

		if ( !is_object($term) )
			$term = new WP_Error('invalid_term', __('Empty Term'));

		if ( is_wp_error( $term ) )
			return $term;

		$taxonomy = $term->taxonomy;

		$termlink = apply_filters( 'qts_permastruct', $wp_rewrite->get_extra_permastruct($taxonomy), $taxonomy);
		
		$slug = get_term_meta( $term->term_id, $this->get_meta_key(), true );
		if (!$slug) $slug = $term->slug;
		
		$t = get_taxonomy($taxonomy);

		if ( empty($termlink) ) {
			if ( 'category' == $taxonomy )
				$termlink = '?cat=' . $term->term_id;
			elseif ( $t->query_var )
				$termlink = "?$t->query_var=$slug";
			else
				$termlink = "?taxonomy=$taxonomy&term=$slug";
			$termlink = home_url($termlink);
		} else {
			if ( $t->rewrite['hierarchical'] ) {
				$hierarchical_slugs = array();
				$ancestors = get_ancestors($term->term_id, $taxonomy);
				foreach ( (array)$ancestors as $ancestor ) {
					$ancestor_term = get_term($ancestor, $taxonomy);
					
					$ancestor_slug = get_term_meta( $ancestor_term->term_id, $this->get_meta_key(), true );
					if (!$ancestor_slug) $ancestor_slug = $ancestor_term->slug;
					
					$hierarchical_slugs[] = $ancestor_slug;
				}
				$hierarchical_slugs = array_reverse($hierarchical_slugs);
				$hierarchical_slugs[] = $slug;
				$termlink = str_replace("%$taxonomy%", implode('/', $hierarchical_slugs), $termlink);
			} else {
				$termlink = str_replace("%$taxonomy%", $slug, $termlink);
			}
			$termlink = home_url( user_trailingslashit($termlink, 'category') );
		}
		return $termlink;
	}
	
	
	
	/**
	 * Get all Term data from database by Term field and data.
	 *
	 * @param string $field Either 'slug', 'name', or 'id'
	 * @param string|int $value Search for this term value
	 * @param string $taxonomy Taxonomy Name
	 * @param string $output Constant OBJECT, ARRAY_A, or ARRAY_N
	 * @param string $filter Optional, default is raw or no WordPress defined filter will applied.
	 * @return mixed Term Row from database. Will return false if $taxonomy does not exist or $term was not found.
	 *
	 * @since 1.0
	 */
	private function get_term_by($field, $value, $taxonomy, $output = OBJECT, $filter = 'raw') {
		global $wpdb;
		
		$original_field = $field;

		if ( ! taxonomy_exists($taxonomy) )
			return false;

		if ( 'slug' == $field ) {
			$field = 'm.meta_key = \''.$this->get_meta_key().'\' AND m.meta_value';
			$value = sanitize_title($value);
			if ( empty($value) )
				return false;
		} else if ( 'name' == $field ) {
			// Assume already escaped
			$value = stripslashes($value);
			$field = 't.name';
		} else {
			$term = get_term( (int) $value, $taxonomy, $output, $filter);
			if ( is_wp_error( $term ) )
				$term = false;
			return $term;
		}
		
		$term = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt, $wpdb->termmeta AS m WHERE t.term_id = tt.term_id AND tt.term_id = m.term_id AND tt.taxonomy = %s AND $field = %s LIMIT 1", $taxonomy, $value) );

		if ( !$term && 'slug' == $original_field ) {
			$field = 't.slug';
			$term = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND $field = %s LIMIT 1", $taxonomy, $value) );
		}
		
		if ( !$term )
			return false;

		wp_cache_add($term->term_id, $term, $taxonomy);

		$term = apply_filters('get_term', $term, $taxonomy);
		$term = apply_filters("get_$taxonomy", $term, $taxonomy);
		$term = sanitize_term($term, $taxonomy, $filter);

		if ( $output == OBJECT ) {
			return $term;
		} elseif ( $output == ARRAY_A ) {
			return get_object_vars($term);
		} elseif ( $output == ARRAY_N ) {
			return array_values(get_object_vars($term));
		} else {
			return $term;
		}
	}
	
	
	
	/**
	 * hide quickedit button ( functionality not supported by qTranslate )
	 * 
	 * @since 1.0
	 */
	public function hide_quick_edit() {
		echo "<!-- QTS remove quick edit box -->" . PHP_EOL;
		echo "<style type=\"text/css\" media=\"screen\">" . PHP_EOL;
		echo "	.row-actions .inline.inline.hide-if-no-js { display: none !important }" . PHP_EOL;
		echo "</style>" . PHP_EOL;
	}
	
	
	
	/**
	 * Hide auttomatically the wordpress slug blog in edit posts page
	 *
	 * @since 1.0
	 */
	public function hide_slug_box() {
		global $pagenow;
		
		switch ( $pagenow ):
			case 'edit-tags.php':
			
				echo "<!-- QTS remove slug box -->" . PHP_EOL;
				echo "<script type=\"text/javascript\" charset=\"utf-8\">" . PHP_EOL;
				echo "	jQuery(document).ready(function($){" . PHP_EOL;
				echo "		$(\"#tag-slug\").parent().hide();" . PHP_EOL;
				echo "		$(\".form-field td #slug\").parent().parent().hide();" . PHP_EOL;
				echo "	});" . PHP_EOL;
				echo "</script>" . PHP_EOL;
				break;
			
			case 'post.php':
			
				echo "<!-- QTS remove slug box -->" . PHP_EOL;
				echo "<style type=\"text/css\" media=\"screen\">" . PHP_EOL;
				echo "	#edit-slug-box { display: none !important}" . PHP_EOL;
				echo "</style>" . PHP_EOL;
				break;
		endswitch;
	}
	
	
		
	/**
	 * Creates a metabox for every post, page and post type avaiable
	 *
	 * @since 1.0
	 */
	public function add_slug_meta_box() {
		
		if ( function_exists( 'add_meta_box' ) ) {
			
			add_meta_box( 'qts_sectionid', __('Slug', 'qts'), array($this, 'draw_meta_box'), 'post', 'side', 'high');
			add_meta_box( 'qts_sectionid', __('Slug', 'qts'), array($this, 'draw_meta_box'), 'page', 'side', 'high' );
			
			foreach ( get_post_types( array('_builtin' => false ) ) as $ptype )
				add_meta_box( 'qts_sectionid', __('Slug', 'qts'), array($this, 'draw_meta_box'), $ptype, 'side', 'high' );
		}
	}
	
	
	
	/**
	 * Shows the fields where insert the translated slugs in the post and page edit form.
	 *
	 * @param $post (object) current post object
	 *
	 * @since 1.0
	 */
	public function draw_meta_box( $post ) {
		global $q_config;
	  
		// Use nonce for verification
		echo "<table style=\"width:100%\">" . PHP_EOL;
		echo "<input type=\"hidden\" name=\"qts_nonce\" id=\"qts_nonce\" value=\"" . wp_create_nonce( 'qts_nonce' ) . "\" />" . PHP_EOL;
  
		foreach ($q_config['enabled_languages'] as $lang):
			
			$slug = get_post_meta( $post->ID, $this->get_meta_key($lang), true);
			
			$value = ( $slug ) ? htmlspecialchars( $slug , ENT_QUOTES ) : '';
			
			echo "<tr>" . PHP_EOL;
			echo "<th style=\"text-align:left; width:10%; color:#555 \"><label for=\"qts_{$lang}_slug\">".__($q_config['language_name'][$lang], 'qtranslate')."</label></th>" . PHP_EOL;
			echo "<td><input type=\"text\" id=\"qts_{$lang}_slug\" name=\"qts_{$lang}_slug\" value=\"$value\" style=\"width:90%; margin-left:10%; color:#777\" /></td>" . PHP_EOL;
			echo "</tr>" . PHP_EOL;
			
		endforeach;
		
		echo '</table>' . PHP_EOL;
	}
	
	
	
	/**
	 * Sanitize title as slug, if empty slug
	 * 
	 * @param $post (object) the post object
	 * @param $slug (string) the slug name
	 * @param $lang (string) the language
	 * @return the slug validated
	 *
	 * @since 1.0
	 */
	public function validate_post_slug( $slug, $post, $lang ) {	
			
		$post_title = trim(qtrans_use($lang, $_POST['post_title']));
		$post_name = get_post_meta($post->ID, $this->get_meta_key($lang), true);
		if (!$post_name) $post_name = $post->post_name;
		
		$name = ( $post_title == '' || strlen($post_title) == 0 ) ? $post_name : $post_title;
		
		$slug = trim($slug);
			
		$slug = (empty($slug)) ? sanitize_title($name) : sanitize_title($slug);
		
		
		
		return htmlspecialchars( $slug , ENT_QUOTES );
	}
	
	
	
	/**
	 * Validates post slug against repetitions per language
	 * 
	 * @param $post (object) the post object
	 * @param $slug (string) the slug name
	 * @param $lang (string) the language
	 * @return the slug validated
	 *
	 * @since 1.0
	 */
	public function unique_post_slug( $slug, $post, $lang ) {
		
		$original_status = $post->post_status;
		
		if ( in_array($post->post_status, array('draft', 'pending')) )
			$post->post_status = 'publish';
		
		$slug = $this->wp_unique_post_slug( $slug, $post->ID, $post->post_status, $post->post_type, $post->post_parent, $lang );
		
		$post->post_status = $original_status;
		
		return $slug;
	}
	
	
	
	/**
	 * Computes a unique slug for the post and language, when given the desired slug and some post details.
	 *
	 * @param string $slug the desired slug (post_name)
	 * @param integer $post_ID
	 * @param string $post_status no uniqueness checks are made if the post is still draft or pending
	 * @param string $post_type
	 * @param integer $post_parent
	 * @return string unique slug for the post, based on language meta_value (with a -1, -2, etc. suffix)
	 *
	 * @since 1.0
	 */
	public function wp_unique_post_slug( $slug, $post_ID, $post_status, $post_type, $post_parent, $lang ) {
		if ( in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) ) )
			return $slug;

		global $wpdb, $wp_rewrite;

		$feeds = $wp_rewrite->feeds;
		if ( ! is_array( $feeds ) )
			$feeds = array();
		
		$meta_key = $this->get_meta_key($lang);
		if ( 'attachment' == $post_type ) {
			// Attachment slugs must be unique across all types.
			$check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
			$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_ID ) );

			if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_attachment_slug', false, $slug ) ) {
				$suffix = 2;
				do {
					$alt_post_name = substr ($slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
					$post_name_check = $wpdb->get_var( $wpdb->prepare($check_sql, $alt_post_name, $post_ID ) );
					$suffix++;
				} while ( $post_name_check );
				$slug = $alt_post_name;
			}
		} else {
			// Post slugs must be unique across all posts.
			$check_sql = "SELECT $wpdb->postmeta.meta_value FROM $wpdb->posts,$wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '%s' AND $wpdb->postmeta.meta_value = '%s' AND $wpdb->posts.post_type = %s AND ID != %d LIMIT 1";
			$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $meta_key, $slug, $post_type, $post_ID ) );

			if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type ) ) {
				$suffix = 2;
				do {
					$alt_post_name = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
					$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $meta_key, $alt_post_name, $post_type, $post_ID ) );
					$suffix++;
				} while ( $post_name_check );
				$slug = $alt_post_name;
			}
		}

		return $slug;
	}
	
	
	
	
	/**
	 * Saves the translated slug when the page is saved
	 * 
	 * @param $post_id the post id
	 * @param $post the post object
	 *
	 * @since 1.0
	 */
	public function save_postdata( $post_id, $post ) {
		global $q_config;
		
		$post_type_object = get_post_type_object( $post->post_type);
		
		
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)						// check autosave
		|| (!isset($_POST['post_ID']) || $post_id != $_POST['post_ID'])			// check revision
		|| (!wp_verify_nonce( $_POST['qts_nonce'], 'qts_nonce'))				// verify nonce
		|| (!current_user_can($post_type_object->cap->edit_post, $post_id))) {	// check permission
			return $post_id;
		}
		
		foreach ($q_config['enabled_languages'] as $lang):
			
			$meta_name = $this->get_meta_key($lang);
			$meta_value = apply_filters( 'qts_validate_post_slug', $_POST["qts_{$lang}_slug"], $post, $lang);
			
			delete_post_meta($post_id, $meta_name);
			update_post_meta($post_id, $meta_name, $meta_value);
			
		endforeach;
	}
	
	
	
	/**
	 * Display multiple input fields, one per language
	 * 
	 * @param $term the term object
	 *
	 * @since 1.0
	 */
	public function show_term_fields( $term ) {
		global $q_config;
		
		// prints the fields in edit page
		if (isset($_GET['action']) && $_GET['action'] == 'edit' ):
  
			echo "<table class=\"form-table\">" . PHP_EOL;
			echo "<input type=\"hidden\" name=\"qts_nonce\" id=\"qts_nonce\" value=\"" .  wp_create_nonce( 'qts_nonce' ) . "\" />" . PHP_EOL;
  
			foreach( $q_config['enabled_languages'] as $lang ):
			
				$slug = (is_object($term)) ? get_term_meta( $term->term_id, $this->get_meta_key($lang), true ) : '';
			
				$value = ( $slug ) ? htmlspecialchars( $slug , ENT_QUOTES ) : '';
			
				echo "<tr class=\"form-field form-required\">" . PHP_EOL;
				echo "<th scope=\"row\" valig=\"top\"><label for=\"qts_{$lang}_slug\">Slug (".__($q_config['language_name'][$lang], 'qtranslate').")</label></th>" . PHP_EOL;
		    	echo "<td><input type=\"text\" name=\"qts_{$lang}_slug\" value=\"$value\" /></td></tr>" . PHP_EOL;
			
			endforeach;
		
			echo '</table>';
		
		// prints the fields in new page
		else:
			echo "<input type=\"hidden\" name=\"qts_nonce\" id=\"qts_nonce\" value=\"" .  wp_create_nonce( 'qts_nonce' ) . "\" />" . PHP_EOL;
  
			foreach( $q_config['enabled_languages'] as $lang ):
				
				echo "<div class=\"form-field\">" . PHP_EOL;
			
				$slug = (is_object($term)) ? get_term_meta( $term->term_id, $this->get_meta_key($lang), true ) : '';
			
				$value = ( $slug ) ? htmlspecialchars( $slug , ENT_QUOTES ) : '';
			

				echo "<label for=\"qts_{$lang}_slug\">Slug (".__($q_config['language_name'][$lang], 'qtranslate').")</label>" . PHP_EOL;
		    	echo "<input type=\"text\" name=\"qts_{$lang}_slug\" value=\"$value\" aria-required=\"true\">" . PHP_EOL;
				
				echo '</div>';
			
			endforeach;

		endif;
	}
	
	
	
	/**
	 * Sanitize title as slug, if empty slug
	 * 
	 * @param $term (object) the term object
	 * @param $slug (string) the slug name
	 * @param $lang (string) the language
	 * @return the slug validated
	 *
	 * @since 1.0
	 */
	public function validate_term_slug( $slug, $term, $lang ) {
		global $q_config;
		
		$lang_name = $q_config['term_name'][$term->name][$lang];
		
		$ajax_name = 'new' . $term->taxonomy;
		
		$post_name = isset($_POST['name']) ? $_POST['name'] : '';
		
		$term_name = isset($_POST[$ajax_name]) ? trim($_POST[$ajax_name]) : $post_name;
		
		if (empty($term_name)) return $slug;
		
		$name = ( $lang_name == '' || strlen($lang_name) == 0 ) ? $term_name : $lang_name;
		
		$slug = trim($slug);
			
		$slug = (empty($slug)) ? sanitize_title($name) : sanitize_title($slug);
		
		return htmlspecialchars( $slug , ENT_QUOTES );
	}
	
	
	
	/**
	 * Will make slug unique per language, if it isn't already.
	 *
	 * @param string $slug The string that will be tried for a unique slug
	 * @param object $term The term object that the $slug will belong too
	 * @param object $lang The language reference 
	 * @return string Will return a true unique slug.
	 *
	 * @since 1.0
	 */
	public function unique_term_slug($slug, $term, $lang) {
		global $wpdb;
		
        $meta_key_name = $this->get_meta_key($lang);
        $query = $wpdb->prepare("SELECT term_id FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d ",
                       $meta_key_name,
                       $slug,
                       $term->term_id);
        $exists_slug = $wpdb->get_results($query);

        if ( empty($exists_slug) )
            return $slug;

        // If we didn't get a unique slug, try appending a number to make it unique.
        $query = $wpdb->prepare(
                    "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d",
                    $meta_key_name,
                    $slug,
                    $term->term_id);

        if ( $wpdb->get_var( $query ) ) {
            $num = 2;
            do {
                $alt_slug = $slug . "-$num";
                $num++;
                $slug_check = $wpdb->get_var(
                                 $wpdb->prepare(
                                "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s'",
                                $meta_key_name,
                                $alt_slug) );
            } while ( $slug_check );
            $slug = $alt_slug;
        }

        return $slug;
	}
	
	
	
	/**
	 * Display multiple input fields, one per language
	 * 
	 * @param $term_id the term id
	 * @param $tt_id the term taxonomy id
	 * @param $taxonomy the term object
	 *
	 * @since 1.0
	 */
	public function save_term( $term_id, $tt_id, $taxonomy ) {
        global $q_config;
	     
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )				// check autosave
		|| ( !current_user_can('edit_posts') ) ) {                      // check permission
			return $term_id;
		}
		
		$term = get_term( $term_id, $taxonomy);
	
		foreach( $q_config['enabled_languages'] as $lang ):
			
			$meta_name = $this->get_meta_key($lang);
			$meta_value = apply_filters( 'qts_validate_term_slug', $_POST["qts_{$lang}_slug"], $term, $lang);
			
			delete_term_meta($term_id, $meta_name);
			update_term_meta($term_id, $meta_name, $meta_value);

	   endforeach;
	}
	
	
	
	/**
	 * deletes termmeta rows associated with the term
	 * 
	 * @since 1.0
	 */	
	public function delete_term($term_id, $tt_id, $taxonomy) {
		global $wpdb;
		
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->termmeta WHERE term_id = %d", $term_id ) );
	}
	
	
	
	/**
	 * adds support for qtranslate in taxonomies
	 *
	 * @since 1.0
	 */
	public function taxonomies_hooks() {
	
		$taxonomies = $this->get_public_taxonomies();

		if ($taxonomies) {
			foreach ($taxonomies  as $taxonomy ) {
				add_action( $taxonomy->name.'_add_form', 'qtrans_modifyTermFormFor');
				add_action( $taxonomy->name.'_edit_form', 'qtrans_modifyTermFormFor');
				add_action( $taxonomy->name.'_add_form',  array($this, 'show_term_fields'));
				add_action( $taxonomy->name.'_edit_form_fields', array($this, 'show_term_fields') );
				add_filter('manage_edit-'.$taxonomy->name.'_columns', array($this, 'taxonomy_columns'));
				add_filter('manage_'.$taxonomy->name.'_custom_column', array($this, 'taxonomy_custom_column'), 0, 3);
			}
		}
	}
	
	
	
	/*
	 * Bug fix for slug column in taxonomies
	 * 
	 * @since 1.0
	 */
	public function taxonomy_columns($columns) {
		unset($columns['slug']);
		unset($columns['posts']);
		
		$columns['qts-slug'] = __('Slug', 'qts');
		$columns['posts'] = __('Posts');
		
		return $columns;
	}
	
	
	
	/*
	 * Bug fix for slug column in taxonomies
	 * 
	 * @since 1.0
	 */
	public function taxonomy_custom_column($str, $column_name, $term_id) {
		
		switch ($column_name) {
			case 'qts-slug':
				echo get_term_meta($term_id, $this->get_meta_key(), true);
				break;
		}
		return false;
	}
	
	
	
	
	/**
	 * Bug fix for multisite blog names
	 * 
	 * @since 1.0
	 */
	public function blog_names($blogs) {
		
		foreach ($blogs as $blog)
			$blog->blogname = __($blog->blogname);
		
		return $blogs;
	}
	
	
	
	/**
	 * Initialise the Language Widget selector
	 * 
	 * @since 1.0
	 */
	public function widget_init() {
		
		if (class_exists('qTranslateWidget'))
			unregister_widget('qTranslateWidget');
		
		register_widget('QtranslateSlugWidget');
	}
	
	
	
	/**
	 * remove some default dashboard Widgets on Desktop
	 *
	 * @since 1.0
	 */
	function remove_dashboard_widgets() {
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	} 
	
	
	
	/**
	 * adds support for qtranslate nav menus
	 * 
	 * @since 1.0
	 */
	public function fix_nav_menu() {
		global $pagenow;
		
		if( $pagenow != 'nav-menus.php' ) return;
	
		wp_enqueue_script( 'nav-menu-query',  plugins_url( 'assets/js/qts-nav-menu-min.js' , __FILE__ ) , 'nav-menu', '1.0' );
		add_meta_box( 'qt-languages', __('Languages'), array($this, 'nav_menu_meta_box'), 'nav-menus', 'side', 'default' );
	}
	
	
	
	/**
	 * draws meta box for select language
	 * 
	 * @since 1.0
	 */
	public function nav_menu_meta_box() {
		global $q_config;
		echo '<p>';
		foreach($q_config['enabled_languages'] as $id => $language) {
			$checked = ($language == $q_config['language']) ? ' checked="checked"' : '';
			echo '<p style="margin:0 0 5px 0"><input type="radio" style="margin-right:5px" name="wa_qt_lang" value="' . $language . '" id="wa_gt_lang_' . $id . '" ' . $checked . '/>';
			echo '<label for="wa_gt_lang_' . $id . '">';
			echo '<img src="' . trailingslashit(WP_CONTENT_URL).$q_config['flag_location'].$q_config['flag'][$language] . '"/>&nbsp;';
			echo __($q_config['language_name'][$language], 'qtranslate');
			echo '</label></p>';
		}
		echo '</p>';
	}
	
	
	
	/**
	 * Language select function for templating
	 *
	 * @param $type (string) choose the type of menu: 'text', 'image', 'both', 'dropdown' 
	 * @param $args (array) some args for draw the menu: array( 'id', 'class', 'short' );
	 * 
	 * @since 1.0
	 */
	public function language_menu( $type = "text", $args = array() ) {
		global $q_config;
		
		// default arguments
		$defaults = array(
			'id' => "qts-lang-menu",
			'class' => "qts-lang-menu",
			'short' => false
		);
		$args = wp_parse_args( $args, $defaults );
		
		$languages = qtrans_getSortedLanguages();
		
		// every type
		switch ( $type ) {
			
			case 'image':
			case 'text':
			case 'both':
			
				echo "<ul id=\"{$args['id']}\" class=\"{$args['class']}\">". PHP_EOL;
				
				foreach( $languages as $index => $lang ):
					
					$url = $this->get_current_url($lang);
					
					$item_class = array();
					if ( (string)$q_config['language'] == (string)$lang ) $item_class[] = 'current-menu-item';
					if ( $index == (count($languages) - 1) ) $item_class[] = 'last-child';
					
					$item_class = empty($item_class) ? '' : ' class="' . implode(' ', $item_class) . '"';
					
					$language_name = ($args['short']) ? $lang : $q_config['language_name'][$lang];
					
					if ( $type == 'image' ) {
						$link_class = " class=\"qtrans_flag qtrans_flag_$lang\"";
						$link_content = "<span style=\"display:none\">$language_name</span>";
					} else if ( $type == 'both' ) {
						$link_class = " class=\"qtrans_flag qtrans_flag_$lang\" style=\"padding-left:25px\"";
						$link_content = "$language_name";
						
					} else {
						$link_class = '';
						$link_content = $language_name;
					}
					
					echo "<li$item_class><a href=\"$url\" hreflang=\"$lang\"$link_class>$link_content</a></li>" . PHP_EOL;
					
				endforeach;
				
				echo "</ul>". PHP_EOL;
				
				break;
				
			case 'dropdown':
			
				echo "<select id=\"{$args['id']}\" class=\"{$args['class']}\" onchange=\"window.location.href=this.options[this.selectedIndex].value\">". PHP_EOL;
				
				foreach( $languages as $index => $lang ):
					
					$url = $this->get_current_url($lang);
					
					$item_class = '';
					if ( (string)$q_config['language'] == (string)$lang ) $item_class = 'selected="selected"';
					
					$language_name = ($args['short']) ? $lang : $q_config['language_name'][$lang];
					
					echo "<option value=\"$url\" $item_class>$language_name</option>" . PHP_EOL;
					
				endforeach;
				
				echo "</select>". PHP_EOL;

				break;
		}
		
	}
}
////////////////////////////////////////////////////////////////////////////////////////



/**
 * Define Constants
 *
 * @since 1.0
 */
if (!defined("QTS_VERSION")) 		define("QTS_VERSION", '1.0');
if (!defined("QTS_PREFIX")) 		define("QTS_PREFIX", '_qts_');
if (!defined("QTS_PAGE_BASENAME")) 	define('QTS_PAGE_BASENAME', 'qtranslate-slug-settings');
if (!defined("QTS_OPTIONS_NAME")) 	define("QTS_OPTIONS_NAME", 'qts_options');
if (!defined("PHP_EOL")) 			define("PHP_EOL", "\r\n");

////////////////////////////////////////////////////////////////////////////////////////



/**
 * Includes
 *
 * @since 1.0
 */
if ( is_admin() && !QtranslateSlug::block_activate() )  // setting options page
	include_once('qtranslate-slug-settings.php'); 

include_once('termmeta-core.php'); // termmeta install and core functions

////////////////////////////////////////////////////////////////////////////////////////


	
/**
 * Init the plugin
 *
 * @since 1.0
 */	
global $qtranslate_slug;

$qtranslate_slug = new QtranslateSlug();

// plugin activation
register_activation_hook( __FILE__, array($qtranslate_slug, 'install') );

// plugin deactivation
register_deactivation_hook( __FILE__, array($qtranslate_slug, 'deactivate') );

// plugin uninstall
register_uninstall_hook( __FILE__, 'qts_uninstall' );

// plugin init
add_action('plugins_loaded', array($qtranslate_slug, 'init') );

////////////////////////////////////////////////////////////////////////////////////////



/**
 * Language Selector Code for templating
 *
 * @package Qtranslate Slug
 * @subpackage Core 
 * @since 1.0
 */
function qts_language_menu ($type = "text", $args = array()) {
	global $qtranslate_slug;
	
	$qtranslate_slug->language_menu($type, $args);
}



/**
 * Adds support for old plugin function
 * 
 * @package Qtranslate Slug
 * @subpackage Core
 * @since 1.1.5
 */
function qTranslateSlug_getSelfUrl ($lang = false) {
	global $qtranslate_slug;

	return $qtranslate_slug->get_current_url($lang);
}



/**
 * Add a "Settings" link to the plugins.php page for Qtranslate Slug
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0 
 *
 * @return calls qts_show_msg()
 */
function qts_add_settings_link( $links, $file ) {
	
	if (QtranslateSlug::block_activate()) return $links;
	
	$this_plugin = plugin_basename( __FILE__ );
	if( $file == $this_plugin ) {
		$settings_link = "<a href=\"options-general.php?page=" . QTS_PAGE_BASENAME . "\">" . __( 'Settings' ) . '</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter( 'plugin_action_links', 'qts_add_settings_link', 10, 2 );



/**
 * Delete plugin stored data ( options, termmeta table and postmeta data ) ################################################ test this function
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0 
 *
 */
function qts_uninstall() {
	global $q_config, $wpdb;
	
	// options
	delete_option(QTS_OPTIONS_NAME);
	delete_option('qts_version');
	
	// delete termmeta table
	$wpdb->query("DROP TABLE IF EXISTS $wpdb->termmeta");
	
	// delete postmeta data
	$meta_keys = array();
	foreach ($q_config['enabled_languages'] as $lang) $meta_keys[] = sprintf("_qts_slug_%s", $lang);
	$meta_keys = "'". implode( "','", $meta_keys ) . "'";
	$wpdb->query("DELETE from $wpdb->postmeta WHERE meta_key IN ($meta_keys)");	
}



if (is_admin()) {
	
	/**
	 * Fix for:
	 * - Taxonomy & custom taxonomy names in Post Manage page
	 * - List of tags already added to the post in Post 
	 *   Edit page (but have issues when saving)
	 *   -> temporarily disabled
	 */	
	function get_object_terms_qtranslate($terms, $obj_id, $taxonomy, $args) {
		
		global $pagenow;
		
		// Although in post edit page the tags are translated,
		// but when saving/updating the post Wordpess considers
		// the translated tags as new tags. Due to this
		// issue I limit this 'hack' to the post manage
		// page only.
		if ( $pagenow == 'edit.php' ) {
			
			// $taxonomy output seems to be wrapped
			// in single quotes, thus remove them to
			// make the output valid
			$tax = str_replace("'", "", $taxonomy);
			
			$meta = get_option('qtranslate_term_name');
			$lang = qtrans_getLanguage();
			
			if ( !empty( $terms ) ) {
				foreach ($terms as $term) {
					$term->name = $meta[$term->name][$lang];
				};
			};
		
		}
		return $terms;
	}
	add_filter( 'wp_get_object_terms', 'get_object_terms_qtranslate', 0, 4 );
	
	
	/**
	 * Fix for:
	 * - Taxonomy names in Taxonomy Manage page
	 * - 'Popular Tags' in Taxonomy (Tags) Manage page
	 * - Category filter dropdown menu in Post Manage page
	 * - Category list in Post Edit page
	 * - 'Most Used' tags list in Post Edit page
	 *   (but have issues when saving)
	 *   -> temporarily disabled
	 */	
	function get_terms_qtranslate($terms, $taxonomy) {
		
		global $pagenow;
		
		// Although in post edit page the tags in 'most
		// used' list are translated, but when saving the
		// post Wordpess considers the translated tags as
		// new tags. Due to this issue I skip this 'hack'
		// for tags in post edit page.
		if ( $pagenow != 'admin-ajax.php' ) {
			
			$meta		= get_option('qtranslate_term_name');
			$lang		= qtrans_getLanguage();
			
			if ( !empty( $terms ) ) {
				foreach ($terms as $term) {
					if ($meta[$term->name][$lang]) {
						$term->name = $meta[$term->name][$lang];
					}
				};
			};
		
		}
	
		return $terms;
	}
	add_filter( 'get_terms', 'get_terms_qtranslate', 0, 3 );
	
}
