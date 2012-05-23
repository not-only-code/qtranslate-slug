<?php
/*
Plugin Name: qTranslate slug
Plugin URI: http://wordpress.org/extend/plugins/qtranslate-slug/
Description: Allows to define a slug for each language and some qTranslate bug fixes
Version: 0.9
Author: Carlos Sanz Garcia
Author URI: http://codingsomethig.wordpress.com

this plugin is a complete fork of the original qTranslate slug developed by Cimatti Consulting http://www.cimatti.it

--------------------------------------------------------------------------------------------------------


version 0.9
+ some wordpress qTranslate bug fixes
+ adds a javascript solution for qTranslate Nav Menus

version 0.8
+ added support por Categories
+ added support por Tags
+ added support por Taxonomies
+ added support por Custom Post Types

version 0.7 enhanced by Zapo (http://www.qianqin.de/qtranslate/forum/viewtopic.php?f=4&t=1049&start=50#p7499)
+ added suport for qTranslate TLD domain mode (en: domain.com | fr: domain.fr) visit 

Version 0.5 and 0.6 enhanched by Marco Del Percio

--------------------------------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.


The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt

*/

/* Notes
 * fb() is a function defined by the WP-FirePHP plugin <http://wordpress.org/extend/plugins/wp-firephp/>, that allows debug using Firefox, Firebug and FirePHP.
 * 
 * TODO:
 
 * admin options page for setup the taxonomies names
 * change the database system ( post meta ), without installing tables 
 * generate translated slug automatically from the translated title
 * check if the slug is already used, and add a progressive number in this case
 * force the use of the translated slug if defined
 * force to show a page/post only at the correct URL
 * try to redirect from a wrong URL to a correct URL
 * keep track of renamed slugs, redirecting from old to new slugs 
 * translate categories and tags slugs.
 */
 
// Codes used in the database to indicate posts, pages, categories and tags.
$qts_post_types = array(
	'QTS_POST' => 1,
	'QTS_PAGE' => 2,
	'QTS_CAT' => 3,
	'QTS_TAG' => 4,
	'QTS_TAX' => 5
);
foreach ($qts_post_types as $type => $id)  define($type, $id);


/**
 * Variable used to override the language
 */
$qts_use_language = null;

/**
 * Array of translated versions of the current url
 */
$qts_self_url = array();

/**
 * Function invoked during the installation of the module. It creates or updates the tables
 */
 function qTranslateSlug_install(){
	 global $wpdb;

	 $table_name = $wpdb->prefix.'qtranslate_slug';
	 require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		
	 $collate = '';
	 if($wpdb->supports_collation()) {
		 if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET $wpdb->charset";
		 if(!empty($wpdb->collate)) $collate .= " COLLATE $wpdb->collate";
	 }	
  
	$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
	    qts_type TINYINT(3) UNSIGNED NOT NULL,
	    qts_id BIGINT(20) UNSIGNED NOT NULL,
	    qts_lang VARCHAR(6) NOT NULL,
	    qts_slug VARCHAR(200) NOT NULL,
	    PRIMARY KEY id_lang (qts_type, qts_id, qts_lang),
	    KEY post_name_lang (qts_slug, qts_type, qts_lang)
	);' ;
	$wpdb->query($sql);
}
register_activation_hook( __FILE__  , 'qTranslateSlug_install');




function qTranslateSlug_get_custom_taxonomy_slug($taxonomy) {
	global $taxonomies_slugs, $q_config, $qts_use_language;
	
	if(!function_exists('qTranslateSlug_term_link') || !isset($taxonomies_slugs)) return $taxonomy;
	
	$lang = ($qts_use_language === null) ? $q_config['language'] : $qts_use_language;
	
	if (is_array($taxonomies_slugs[$taxonomy])) {
		return $taxonomies_slugs[$taxonomy][$lang];
	} else {
		return $taxonomy;
	}
}



/**
 * Function called when query parameters are processed by Wordpress.
 * 
 * @param $q query parameters
 * @return array() $q processed
 */
function qTranslateSlug_filter_request($q){
	global $q_config, $wpdb, $qts_use_language, $qts_self_url, $qts_reset_self_url, $taxonomies_slugs ;

	if ($qts_use_language !== null){
		$lang = (string) $qts_use_language;
	} else {
		if (!isset($q_config['language'])){
			return $q;
		}
		$lang = (string) $q_config['language'];
	}

	$new_q = $q;
	
	// SEARCH TAXONOMIES
	$args=array(
		'public' => true,
		'_builtin' => false
		); 
	$output = 'object';
	$operator = 'and';
	$taxonomies = get_taxonomies($args, $output, $operator);
	
	foreach ($taxonomies as $taxonomy ) {
		if (isset($q[$taxonomy->name])) {
			$type = QTS_TAX;
			$slug = $q[$taxonomy->name];
			$param = 'term_id';
			$get_link = 'qTranslateSlug_term_link';
			$taxonomy_ = $taxonomy->name;
			//unset($new_q[$taxonomy->name]);
		}
	}
	
	if (isset($type) && $type == QTS_TAX) {
		// is taxonomy
	} else if (isset($q['post_type']) && !is_admin() ) {
		$id = qTranslateSlug_get_post_type_by_path($q['name'], $lang, $q['post_type']);
		if ($id) {
			$q = $new_q;
			$q['page_id']=$id;
			$get_link = 'get_post_permalink';
			//$post_ = get_post();
			
			//$q['name'] = qTranslateSlug_get_post_name($id);
			//$q['name'] = 'pinord-do-penedes/la-zona';
			/*
			print_r($post_);
			exit;
			*/
		}
	} else if (isset($q['name'])) {
		$type = QTS_POST;
		$slug = $q['name'];
		$param = 'p';
		$get_link = 'get_permalink';
		unset($new_q['name']);
	} else if (isset($q['pagename'])) {
		//$type = QTS_PAGE;
		//$slug = $q['pagename'];
		//$param = 'page_id';
		$id = qTranslateSlug_get_post_type_by_path($q['pagename'], $lang);
		if ($id) {
			unset($new_q['pagename']);
			$q = $new_q;
			$q['page_id']=$id;
			$get_link = 'get_page_link';
		}
	} else if (isset($q['category_name'])) {
		$type = QTS_CAT;
		$taxonomy_ = 'category';
		$slug = $q['category_name'];
		$param = 'cat';
		$get_link = 'get_category_link';
		unset($new_q['category_name']);
	} else if (isset($q['tag'])) {
		$type = QTS_TAG;
		$taxonomy_ = 'post_tag';
		$slug = $q['tag'];
		$param = 'tag_id';
		$get_link = 'get_tag_link';
		unset($new_q['tag']);
	}

	if (isset($type)){
		$slugs = explode('/',$slug);
		$slug = array_pop($slugs);

		$query = 'SELECT qts_id FROM '.$wpdb->prefix.'qtranslate_slug WHERE \''.$wpdb->escape($slug).'\' = qts_slug AND '.$type.' = qts_type AND \''.$wpdb->escape($lang).'\' = qts_lang';
		@ $id = $wpdb->get_var($query);

		if ($id) {
			$q = $new_q;
			
			if (($type == QTS_TAX || $type == QTS_CAT || $type == QTS_TAG )) {
				$term_ = &get_term($id, $taxonomy_);
				
				switch ($type) {
					case QTS_CAT:
						$q['category_name'] = $term_->slug;
						break;
					case QTS_TAG:
						$q['tag'] = $term_->slug;
						break;
					default:
						$q[$taxonomy_] = $term_->slug;
						break;
				}
			} else {
				$q[$param]=$id;
			}
		}
	}

	if (isset($get_link) && $id && (!$qts_self_url)){
		$old_qts_use_language = $qts_use_language;
		foreach($q_config['enabled_languages'] as $l) {
			global $qts_self_url;
			$qts_use_language = $l;
			if (isset($type) && $type == QTS_TAX) {
  				$link = rtrim(get_option('home'),"/"). '/'. qTranslateSlug_get_custom_taxonomy_slug($term_->taxonomy) . "/" . $term_->slug . "/";
				$qts_self_url[$l] = call_user_func($get_link, $link, $term_, $taxonomy_) . qTranslateSlug_putGetVars();
			} else if ( isset($type) && ( $type == QTS_TAX || $type == QTS_TAG ) ) {
				$qts_self_url[$l] = call_user_func($get_link, $term_) . qTranslateSlug_putGetVars();
			} else {
				$qts_self_url[$l] = call_user_func($get_link, $id) . qTranslateSlug_putGetVars();
			}
		}
		$qts_use_language = $old_qts_use_language;
	}
	
	return $q;
}
add_filter('request','qTranslateSlug_filter_request');

	

/**
 * Returns the link to the current page in the desired language
 * 
 * @param $lang the code of the desired language
 * @return the link for the current page in that language
 */
function qTranslateSlug_getSelfUrl($lang){
  //global $qts_self_url, $wp_query, $wpdb;
  global $q_config, $qts_self_url;

  if (!isset($qts_self_url[$lang])) {
  	$qts_self_url[$lang] = qTranslateSlug_convertURL(esc_url($q_config['url_info']['url']), $lang) . qTranslateSlug_putGetVars(); 
  }

 return $qts_self_url[$lang];
}




/**
 * Converts an url in another language.
 * 
 * This corresponds to qtrans_convertURL, but for now it doesn't check if the url contains translatable slugs.
 * If you need a translation of the current page, you should use qTranslateSlug_getSelfUrl($lang).
 * If you need the translation for a specific page or post id, you should use get_page_link($id) or get_permalink($id).
 * 
 * I extracted qTranslateSlug_urlAddLang() from this function if you just need to add language and home path to a clean relative url. 
 * 
 * @param $url
 * @param $lang
 * @param $forceadmin
 * @return unknown_type
 */
function qTranslateSlug_convertURL($url='', $lang='', $forceadmin = false) {
	if ( defined('WP_ADMIN') && !$forceadmin) return $url;
	global $q_config;
	
	if($lang=='') $lang = $q_config['language'];
	//if($url=='') $url = qTranslateSlug_getSelfUrl($lang);
	if($url=='') $url = esc_url($q_config['url_info']['url']);
	if(!qtrans_isEnabled($lang)) return "";
	
	// & workaround
	$url = str_replace('&amp;','&',$url);
	$url = str_replace('&#038;','&',$url);
	
	// check if it's an external link
	$urlinfo = qtrans_parseURL($url);
	$home = rtrim(get_option('home'),"/");
	if($urlinfo['host']!='') {
		// check for already existing pre-domain language information
		if($q_config['url_mode'] == QT_URL_DOMAIN && preg_match("#^([a-z]{2}).#i",$urlinfo['host'],$match)) {
			if(qtrans_isEnabled($match[1])) {
				// found language information, remove it
				$url = preg_replace("/".$match[1]."\./i","",$url, 1);
				// reparse url
				$urlinfo = qtrans_parseURL($url);
			}
		}
		
		if($q_config['url_mode'] == QT_URL_TLD && preg_match("/\.([a-z]{2,3})$/i",$urlinfo['host'],$match)) {
			
			//if(qtrans_isEnabled($match[1])) {
			
			    $tld = (!empty($q_config['language_tldcode'][$lang])) ? $q_config['language_tldcode'][$lang] : $lang;
			
				// found language information, replace it
				$url = preg_replace("/\.".$match[1]."/i",'.'.$tld, $url, 1);
				
				// reparse url
				$urlinfo = qtrans_parseURL($url);
				
			//}
		}
		
		
	    if(substr($url,0,strlen($home))!=$home) {
			return $url;
		}
		// strip home path
		$url = substr($url,strlen($home));
	} else {
		// relative url, strip home path
		$homeinfo = qtrans_parseURL($home);
		if($homeinfo['path']==substr($url,0,strlen($homeinfo['path']))) {
			$url = substr($url,strlen($homeinfo['path']));
		}
	}
	
	// check for query language information and remove if found
	if(preg_match("#(&|\?)lang=([^&\#]+)#i",$url,$match) && qtrans_isEnabled($match[2])) {
		$url = preg_replace("#(&|\?)lang=".$match[2]."&?#i","$1",$url);
	}
	
	// remove any slashes out front
	$url = ltrim($url,"/");
	
	// remove any useless trailing characters
	$url = rtrim($url,"?&");
	
	// reparse url without home path
	$urlinfo = qtrans_parseURL($url);
	
	// check if its a link to an ignored file type
	$ignore_file_types = preg_split('/\s*,\s*'.'/', strtolower($q_config['ignore_file_types']));
	$pathinfo = pathinfo($urlinfo['path']);
	if(isset($pathinfo['extension']) && in_array(strtolower($pathinfo['extension']), $ignore_file_types)) {
		return $home."/".$url;
	}
	
	return qTranslateSlug_urlAddLang($url, $lang, $urlinfo, $home);
}



/**
 * Adds home path and language to an already cleaned URL.
 * It doesn't reparse the url, and supposes $url is a clean relative url.
 * You may pass $urlinfo and $home if you have already them.
 * 
 * @param $url the relative url
 * @param $lang the desired language
 * @param $urlinfo result of qtrans_parseURL($url)
 * @param $home result of rtrim(get_option('home'),"/")
 * @return the absolute url with language informations
 */
function qTranslateSlug_urlAddLang($url, $lang='', $urlinfo='', $home='') {
	global $q_config;
	
	if($lang=='') $lang = $q_config['language'];
	if($urlinfo=='') $urlinfo = qtrans_parseURL($url);
	if($home=='') $home = rtrim(get_option('home'),"/");
	
	
  switch($q_config['url_mode']) {
		case QT_URL_PATH:	// pre url
			// might already have language information
			if(preg_match("#^([a-z]{2})/#i",$url,$match)) {
				if(qtrans_isEnabled($match[1])) {
					// found language information, remove it
					$url = substr($url, 3);
				}
			}
			//if(!$q_config['hide_default_language']||$lang!=$q_config['default_language']) $url = $lang."/".$url;
			//Check for older version of qtranslate without hide_default_language option
			if ( ($lang!=$q_config['default_language']) || (isset($q_config['hide_default_language']) && (!$q_config['hide_default_language']))) $url = $lang."/".$url;
			break;
		case QT_URL_DOMAIN:	// pre domain 
			//if(!$q_config['hide_default_language']||$lang!=$q_config['default_language']) $home = preg_replace("#//#","//".$lang.".",$home,1);
      //Check for older version of qtranslate without hide_default_language option
      if ( ($lang!=$q_config['default_language']) || (isset($q_config['hide_default_language']) && (!$q_config['hide_default_language']))) $home = preg_replace("#//#","//".$lang.".",$home,1);
			break;
		case QT_URL_TLD:	// tld domain 
		
			if($lang !=$q_config['default_language']) {
				
				$tld = (!empty($q_config['language_tldcode'][$lang])) ? $q_config['language_tldcode'][$lang] : $lang;
				
				//$home = preg_replace("#^(http://[^/.]+)(\.?)([a-z]{0,3})#", '$1.'.$tld, $home, 1);
				
				if (preg_match("/\.([a-z]{2,3})$/i",$home,$match)) {
					$home = str_replace($match[0], ".".$tld, $home);
				}
			}
			break;	
			

		default: // query
			//Check for older version of qtranslate without hide_default_language option
      if ( ($lang!=$q_config['default_language']) || (isset($q_config['hide_default_language']) && (!$q_config['hide_default_language']))) {
				if(strpos($url,'?')===false) {
					$url .= '?';
				} else {
					$url .= '&';
				}
				$url .= "lang=".$lang;
			}
	}
	
	// see if cookies are activated
	if(!$q_config['cookie_enabled'] && !$q_config['url_info']['internal_referer'] && $urlinfo['path'] == '' && $lang == $q_config['default_language'] && $q_config['language'] != $q_config['default_language'] && (isset($q_config['hide_default_language'])?$q_config['hide_default_language']:true)) {
		// :( now we have to make unpretty URLs
		$url = preg_replace("#(&|\?)lang=".$match[2]."&?#i","$1",$url);
		if(strpos($url,'?')===false) {
			$url .= '?';
		} else {
			$url .= '&';
		}
		$url .= "lang=".$lang;
	}
	
	// &amp; workaround
	$complete = str_replace('&','&amp;',$home."/".$url);
	
	return $complete;
}



/**
 * Hide auttomatically the wordpress slug blog in edit posts page
 */
function qTranslateSlug_remove_slug_box() {
	
	function qTranslateSlug_remove_slug_block() {
		echo '<!-- QTS remove slug box -->
<style type="text/css" media="screen">
#edit-slug-box { display: none !important}
</style>';
	}
	
	if (is_admin())
		add_action('admin_head', 'qTranslateSlug_remove_slug_block', 900);
	
}



/**
 * Activates filters defined by this module
 */
add_filter('post_type_link',				'qTranslateSlug_post_type_link', 0, 4);
add_filter('page_link',						'qTranslateSlug_page_link', 0, 2);
add_filter('post_link',						'qTranslateSlug_post_link', 0, 3);
add_filter('term_link',						'qTranslateSlug_term_link', 600 , 3);
add_action('admin_menu', 					'qTranslateSlug_remove_slug_box');


/**
 * Disables qtranslate filter for the link managed by this module
 */
function qTranslateSlug_remove_qtrans_filters() {
  remove_filter('page_link', 'qtrans_convertURL');
  remove_filter('post_link', 'qtrans_convertURL');
  remove_filter('category_link', 'qtrans_convertURL');
  remove_filter('tag_link', 'qtrans_convertURL');
}
add_action('plugins_loaded','qTranslateSlug_remove_qtrans_filters');

//TODO: Links that may have to be checked
//add_filter('category_feed_link',			'qtrans_convertURL');
//add_filter('the_permalink',					'qtrans_convertURL');
//add_filter('feed_link',						'qtrans_convertURL');
//add_filter('post_comments_feed_link',		'qtrans_convertURL');
//add_filter('tag_feed_link',					'qtrans_convertURL');
//add_filter('esc_url',						'qtrans_convertURL');



/**
 * Filter that translates the slug parts in a page link
 * 
 * @param $link the link for the page generated by Wordpress
 * @param $id the id of the page
 * @return the link translated
 */
function qTranslateSlug_page_link($link, $id){
  //$fb = function_exists('fb');
  //$fb && fb($link,'start $link') && fb($id, 'start $id');
  global $wpdb,$q_config, $qts_use_language;
  $lang = ($qts_use_language === null)?$q_config['language']:$qts_use_language;
  
  $home = rtrim(get_option('home'),"/");
  $link = substr($link,strlen($home));
  $link = ltrim($link,"/");
  $link = rtrim($link,"?&");

  $urlinfo = qtrans_parseURL($link);

  if ($urlinfo['query']){    
    return qTranslateSlug_urlAddLang($link, $lang, $urlinfo, $home);
  }
  
  $linkParts = explode('/', $link);
  $i = count($linkParts) - 1;
  
  if ($linkParts[$i] === ''){
    $i--;
  }
  
  do {
    $id = (int)$id; 
    $query = 'SELECT post_parent, qts_slug
					FROM '.$wpdb->posts.' p
	 					LEFT JOIN ( SELECT *
							FROM '.$wpdb->prefix.'qtranslate_slug
							WHERE '.QTS_PAGE.' = qts_type
								AND '.$id.' = qts_id
								AND \''.$wpdb->escape($lang).'\' = qts_lang
						) qts ON p.ID = qts.qts_id 
					WHERE '.$id.' = ID'; 
    @ $res = $wpdb->get_row($query,ARRAY_A);
    //$fb && fb($query, '$query') && fb($res, '$res');
    
    if (!$res) {
      break;
    }
    
    $parent = $res['post_parent'];
    $slug = $res['qts_slug'];
    
    if ($slug) {
      $linkParts[$i] = $slug;
    }
    
    if ((!$parent) || ($parent == $id)){
      break;
    }
    
    $id = $parent;
    $i--;
  } while ($i >= 0);
  
  $link = implode('/',$linkParts);
  
  $ret = qTranslateSlug_urlAddLang($link, $lang, $urlinfo, $home);
  //$fb && fb($link, 'end $link') && fb($id, 'end $id') && fb($ret,'return');
  
  return $ret;
}



/**
 * Filter that translates the slug parts in a page link
 * 
 * @param $link the link for the page generated by Wordpress
 * @param $id the id of the page
 * @return the link translated
 */

//$post_link, $post, $leavename, $sample
function qTranslateSlug_post_type_link($link, $post, $leavename, $sample){
	 global $wpdb,$q_config, $qts_use_language, $custom_post_types_slugs;
	
	 $id = $post->ID;
  $lang = ($qts_use_language === null) ? $q_config['language'] : $qts_use_language;
  
  $home = rtrim(get_option('home'),"/");
  $link = substr($link,strlen($home));
  $link = ltrim($link,"/");
  $link = rtrim($link,"?&");
  $urlinfo = qtrans_parseURL($link);
  
  if ($urlinfo['query']){    
    return qTranslateSlug_urlAddLang($link, $lang, $urlinfo, $home);
  }
  
  $linkParts = explode('/', $link);

  if ($custom_post_types_slugs !== null && !is_admin()) {
 
	  foreach ($linkParts as $key => $part) {
		if (in_array($part, $custom_post_types_slugs[$post->post_type])) {
			$linkParts[$key] = $custom_post_types_slugs[$post->post_type][$lang];
		}
	  }
  }

  $i = count($linkParts) - 1;
  
  if ($linkParts[$i] === '') {
    $i--;
  }
  
  do {
    $id = (int)$id; 
    $query = 'SELECT post_parent, qts_slug
					FROM '.$wpdb->posts.' p
	 					LEFT JOIN ( SELECT *
							FROM '.$wpdb->prefix.'qtranslate_slug
							WHERE '.QTS_PAGE.' = qts_type
								AND '.$id.' = qts_id
								AND \''.$wpdb->escape($lang).'\' = qts_lang
						) qts ON p.ID = qts.qts_id 
					WHERE '.$id.' = ID'; 
    @ $res = $wpdb->get_row($query,ARRAY_A);
    //$fb && fb($query, '$query') && fb($res, '$res');
    
    if (!$res) {
      break;
    }
    
    $parent = $res['post_parent'];
    $slug = $res['qts_slug'];
    
    if ($slug) {
      $linkParts[$i] = $slug;
    }
    
    if ((!$parent) || ($parent == $id)){
      break;
    }
    
    $id = $parent;
    $i--;
  } while ($i >= 0);
  
  $link = implode('/',$linkParts);

  $ret = qTranslateSlug_urlAddLang($link, $lang, $urlinfo, $home);
  //$fb && fb($link, 'end $link') && fb($id, 'end $id') && fb($ret,'return');
  
  return $ret;
}



/**
 * Filter that translates the slug in a post link
 * 
 * @param $link the link generated by wordpress
 * @param $post the post data
 * @param $leavename parameter used by get_permalink. Whether to keep post name or page name. 
 * @return the link translated
 */
function qTranslateSlug_post_link($link, $post, $leavename){
   global $wpdb, $q_config, $qts_use_language;
    
   //$fb = function_exists('fb');
   //$fb && fb($link, 'initial $link') && fb($post, '$post');
  
   $lang = ($qts_use_language === null)?$q_config['language']:$qts_use_language;
	
	$permalink = get_option('permalink_structure');
	
	$home = rtrim(get_option('home'),"/");
	
	$trans_slug = null;
	
	if ((!empty($post->ID)) && ('' != $permalink) && !in_array($post->post_status, array('draft', 'pending')) ) {
	  $query = $wpdb->prepare(
"		SELECT qts_slug
			FROM {$wpdb->prefix}qtranslate_slug
			WHERE %d = qts_type
				AND %d = qts_id
				AND %s = qts_lang",
	    QTS_POST, $post->ID, $lang);
	  @$trans_slug = $wpdb->get_var($query);
	  //$fb && fb($query,'$query') && fb($trans_slug,'$trans_slug');
	}
	
	if ($trans_slug !== null) {
		$unixtime = strtotime($post->post_date);
		
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
		
    	//TODO: translate category slug
		$category = '';
		if ( strpos($permalink, '%category%') !== false ) {
			$cats = get_the_category($post->ID);
			if ( $cats ) {
				usort($cats, '_usort_terms_by_ID'); // order by ID
				$category = $cats[0]->slug;
				if ( $parent = $cats[0]->parent )
					$category = get_category_parents($parent, false, '/', true) . $category;
			}
			// show default category in permalinks, without
			// having to assign it explicitly
			if ( empty($category) ) {
				$default_category = get_category( get_option( 'default_category' ) );
				$category = is_wp_error( $default_category ) ? '' : $default_category->slug;
			}
		}

		$author = '';
		if ( strpos($permalink, '%author%') !== false ) {
			$authordata = get_userdata($post->post_author);
			$author = $authordata->user_nicename;
		}

		$date = explode(" ",date('Y m d H i s', $unixtime));
		$rewritereplace = array(
			$date[0],
			$date[1],
			$date[2],
			$date[3],
			$date[4],
			$date[5],
			$trans_slug,
			$post->ID,
			$category,
			$author,
			$trans_slug,
		);
		$link = user_trailingslashit(str_replace($rewritecode, $rewritereplace, $permalink), 'single');
	} else {
	  $link = substr($link, strlen($home));
	}
	$link = ltrim($link, '/');
	$link = qTranslateSlug_urlAddLang($link, $lang,'', $home);
	//$fb && fb($link,'new $link');
	return $link;
}

function getParentTermsSlugTranslation($link, $term, $lang, $taxonomy) {
  global $wpdb;

    $cat_id = $term->term_id;
    $mycategory = $term;

    $term_type = qTranslateSlug_get_term_type($taxonomy);

	$category_parent_id = $mycategory->parent;
	$category_parent_id = (int)$category_parent_id;
	if($category_parent_id != 0) {
      $parentCategory = &get_term($category_parent_id, $taxonomy);
      $parentCategorySlug = $parentCategory->slug;
      if ($parentCategorySlug != '') {
      	  $query = $wpdb->prepare(
            "SELECT qts_slug
      			FROM {$wpdb->prefix}qtranslate_slug
      			WHERE %d = qts_type
      				AND %d = qts_id
      				AND %s = qts_lang",
      	    $term_type, $category_parent_id, $lang);
      	  @$trans_slug = $wpdb->get_var($query);
      	  if ($trans_slug !== null) {   
             $link = str_replace($parentCategorySlug, $trans_slug, $link);
          }
          
          $parentParentCatId = (int)$parentCategory->parent;
          if($parentParentCatId != 0) {
              return getParentTermsSlugTranslation($link, $parentCategory, $lang, $taxonomy);
          }
          else {
              return $link;
          }
      }
      else {
          return $link;
      }
  }
  else {
      return $link;
  }
}

function qTranslateSlug_term_link($link, $term, $taxonomy) {
	
  global $wpdb, $q_config, $qts_use_language, $wp_query, $taxonomies_slugs;

  $id = $term->term_id;

  $lang = ($qts_use_language === null)?$q_config['language']:$qts_use_language;
  
  $home = rtrim(get_option('home'),"/");

  $term_type = qTranslateSlug_get_term_type($taxonomy);
  
  //Marco  INIZIO
  $trans_slug = null;
	$permalink = get_option('permalink_structure');
	
	$category_id = '';
	$category_slug = '';
 
	$mycategory = $term;
	$category_slug = $term->slug;
	$category_id = $id;

	if (($category_id != '') && ('' != $permalink) && ($category_slug != '')) {
	  $query = $wpdb->prepare(
"		SELECT qts_slug
			FROM {$wpdb->prefix}qtranslate_slug
			WHERE %d = qts_type
				AND %d = qts_id
				AND %s = qts_lang",
	    $term_type, $category_id, $lang);
	  @$trans_slug = $wpdb->get_var($query);
	}

  $link = ltrim(substr($link, strlen($home)), '/');
  
  if ($trans_slug !== null) {   
     $link = str_replace($category_slug, $trans_slug, $link);
  }
  
  $link = getParentTermsSlugTranslation($link, $term, $lang, $taxonomy);

  //print_r(qTranslateSlug_urlAddLang($link, $lang, '', $home));

  return qTranslateSlug_urlAddLang($link, $lang, '', $home);
}

function qTranslateSlug_putGetVars() {
	//global $current_user;
	$vars_ = array();
	
	if (isset($_GET) && count($_GET) > 0) {
		
		foreach ($_GET as $name => $value) {
			if (is_array($value)) {
				foreach ($value as $value_) {
					$vars_[] = $name. '%5B%5D=' . $value_;
				}
			} else {
				if ($name != 'q' && $name != 's') {
					$vars_[] = $name . '=' . $value;
				}
			}
		}
	}
	
	$vars_ = apply_filters('qtranslateslug_putgetvars', $vars_);
	
	if (!empty($vars_)) {
		$ret = (isset($_GET['s'])) ? '&amp;' : '?'; 
		return $ret . implode('&amp;', $vars_);
	} else {
		return '';
	}
}

/**
 * Returns the id of the page with the specified path. 
 * 
 * @param $page_path the path
 * @param $lang optional, the desired language
 * @return id of the page
 */
function qTranslateSlug_get_post_type_by_path($page_path, $lang = '', $post_type = 'page') {
	global $wpdb, $q_config, $qts_use_language, $custom_post_types_slugs;
	//$fb = function_exists('fb');
	if ($lang == ''){
	  $lang = ($qts_use_language === null)?$q_config['language']:$qts_use_language;
	}
	$page_path = rawurlencode(urldecode($page_path));
	$page_path = str_replace('%2F', '/', $page_path);
	$page_path = str_replace('%20', ' ', $page_path);
	$page_paths = '/' . trim($page_path, '/');
	$page_paths = explode('/', $page_paths);
	$spage_paths = array();
	$full_path = '';
	foreach( (array) $page_paths as $pathdir){
	    $pathdir = sanitize_title($pathdir);
	    if ($pathdir !== ''){
	      $spage_paths[] = $pathdir;
	      $full_path .= '/'.$pathdir;
	    }
	}
	$leaf_path = array_pop($spage_paths);
	
	/* This makes invalid the default slug, if it was defined a slug in the desired language.
	 * However, without other modifications, the default slug is found anyway by wordpress, so we can find it here.
	$query = $wpdb->prepare(
"		(SELECT ID, qts_slug AS post_name, post_parent
			FROM {$wpdb->posts}, {$wpdb->prefix}qtranslate_slug
			WHERE %s = qts_slug
				AND %d = qts_type
				AND %s = qts_lang
				AND qts_id = ID)
	  	UNION (SELECT ID, post_name, post_parent 
			FROM $wpdb->posts
			WHERE post_name = %s
				AND (post_type = 'page' OR post_type = 'attachment')
				AND (SELECT qts_id
						FROM {$wpdb->prefix}qtranslate_slug
							WHERE %d = qts_type
								AND ID = qts_id
								AND %s = qts_lang
						LIMIT 1) IS NULL)",
	  $leaf_path, QTS_PAGE, $lang, $leaf_path, QTS_PAGE, $lang ); */
	
	$query = $wpdb->prepare(
"		(SELECT ID, qts_slug AS post_name, post_parent
			FROM {$wpdb->posts}, {$wpdb->prefix}qtranslate_slug
			WHERE %s = qts_slug
				AND %d = qts_type
				AND %s = qts_lang
				AND qts_id = ID)
	  	UNION (SELECT ID, post_name, post_parent 
			FROM $wpdb->posts
			WHERE post_name = %s AND (post_type = '$post_type' OR post_type = 'attachment'))",
	  $leaf_path, QTS_PAGE, $lang, $leaf_path );
	$pages = $wpdb->get_results($query);
	
	//$fb && fb($query, 'pages query') && fb($pages, '$pages');

	foreach ($pages as $page) {
		$path = '/' . $leaf_path;
		$level = count($spage_paths);
		$curpage = $page;
		while ($curpage->post_parent != 0) {
		    $level--;
		    if ($level < 0) continue 2;
			$curpage = $wpdb->get_row( $wpdb->prepare( 
				"SELECT ID, post_name, post_parent, qts_slug 
					FROM $wpdb->posts p
						LEFT JOIN ( SELECT *
							FROM {$wpdb->prefix}qtranslate_slug
							WHERE %d = qts_type
								AND %d = qts_id
								AND %s = qts_lang
						) qts on p.ID = qts.qts_id
					WHERE ID = %d
					and post_type='$post_type'",
			QTS_PAGE, $curpage->post_parent, $lang, $curpage->post_parent ));
			/* This makes invalid the default slug, if it was defined a slug in the desired language.
	 		* However, without other modifications, the default slug is found anyway by wordpress, so we can find it here.
			if ($curpage->qts_slug){
			    if ($curpage->qts_slug === $spage_paths[$level]){
			      $path = '/' . $curpage->qts_slug . $path;
			    } else {
			      continue 2;
			    }
			}*/
			
			
			if ( isset($curpage) && $curpage->qts_slug === $spage_paths[$level]){
			  $path = '/' . $curpage->qts_slug . $path;
			} else if (isset($curpage) && $curpage->post_name === $spage_paths[$level]) {
			  $path = '/' . $curpage->post_name . $path;
			} else {
			  continue 2;
			}
		}

		if ( $path === $full_path ) {
		    //$fb && fb($level, 'final $level') && fb ($page->ID, '$page->ID');
			return $page->ID;
		}
	}

	return null;
}

/**
 * Actions used to insert and edit the slug translations
 */
// post / pages
add_action('admin_menu', 'qTranslateSlug_add_custom_box');
add_action('save_post', 'qTranslateSlug_save_postdata', 605, 2);

// Categories / Tags /Taxonomies
add_action ('edit_category_form_fields', 'qTranslateSlug_tag_fields');
add_action ('edit_tag_form_fields', 'qTranslateSlug_tag_fields');
add_action ('edited_term', 'save_qTranslateSlug_term_fields', 605, 3);

function qTranslateSlug_add_custom_box() {
	if ( function_exists( 'add_meta_box' ) ) {
		add_meta_box( 'qts_sectionid', 'Slug', 'qTranslateSlug_custom_box', 'post', 'side', 'high');
		add_meta_box( 'qts_sectionid', 'Slug', 'qTranslateSlug_custom_box', 'page', 'side', 'high' );
		foreach ( get_post_types( array('_builtin' => false ) ) as $ptype ) {
			add_meta_box( 'qts_sectionid', 'Slug', 'qTranslateSlug_custom_box', $ptype, 'side', 'high' );
		}
	}
}

/**
 * Shows the fields where insert the translated slugs in the post and page edit form.
 */
function qTranslateSlug_custom_box() {
  global $post, $wpdb, $q_config;
  
  $post_types = get_post_types(array('_builtin' => false ));

  if ( 'page' == $post->post_type || in_array($post->post_type, $post_types) ) {
    $post_type = QTS_PAGE;
  } else if ($post->post_type == 'post') {
    $post_type = QTS_POST;
  } else {
    return;
  } 
  
  $query = $wpdb->prepare(
		"SELECT qts_lang, qts_slug
  			FROM {$wpdb->prefix}qtranslate_slug
			WHERE %d = qts_type
				AND %d = qts_id"
    , $post_type, $post->ID);
  $results = $wpdb->get_results($query);
  $slugs = array();
  foreach ($results as $res) {
    $slugs[$res->qts_lang] = $res->qts_slug;
  }
  
  // Use nonce for verification
  echo '<table style="width:100%">';
  echo '<input type="hidden" name="qts_nonce" id="qts_nonce" value="' . wp_create_nonce( 'qts_nonce' ) . '" />';
  
  foreach($q_config['enabled_languages'] as $lang) {
	echo '<tr>';
    echo "<th style=\"text-align:left; width:10%; color:#555\"><label for='qts_{$lang}_slug'>".__($q_config['language_name'][$lang], 'qtranslate')."</label></th>";
    $value = isset($slugs[$lang])?htmlspecialchars($slugs[$lang],ENT_QUOTES):'';
    echo "<td><input type='text' id='qts_{$lang}_slug' name='qts_{$lang}_slug' value='$value' style='width:90%; margin-left:10%; color:#777' /></td>\n";
	echo '</tr>';
  }
  echo '</table>';
}


function qTranslateSlug_get_term_type($str) {
	$tag_type = QTS_TAX;
	if ($str == 'category') {
		$tag_type = QTS_CAT;
	} elseif ($str == 'post_tag') {
		$tag_type = QTS_TAG;
	}
	return $tag_type;
}


function qTranslateSlug_tag_fields( $tag ) {    //check for existing featured ID
    $t_id = $tag->term_id;
    //$cat_meta = get_option( "category_$t_id");
	
	if ($_GET['taxonomy'] == 'newspaper') return $t_id;
    
	$tag_type = qTranslateSlug_get_term_type($tag->taxonomy);
	
     global $post, $wpdb, $q_config;
    $query = $wpdb->prepare(
		"SELECT qts_lang, qts_slug
  			FROM {$wpdb->prefix}qtranslate_slug
			WHERE %d = qts_type
				AND %d = qts_id"
    , $tag_type, $t_id);
  $results = $wpdb->get_results($query);
  $slugs = array();
  foreach ($results as $res) {
    $slugs[$res->qts_lang] = $res->qts_slug;
  }
  
  echo '<table class="form-table">';
  echo '<input type="hidden" name="qts_nonce" id="qts_nonce" value="' .  wp_create_nonce( 'qts_nonce' ) . '" />';
  
  foreach($q_config['enabled_languages'] as $lang) {
    echo "<tr class='form-field form-required'><th scope='row' valig='top'><label for='qts_{$lang}_slug'>Slug (".__($q_config['language_name'][$lang], 'qtranslate').")</label></th>";
    $value = isset($slugs[$lang])?htmlspecialchars($slugs[$lang],ENT_QUOTES):'';
    echo "<td><input type='text' name='qts_{$lang}_slug' value='$value' /></td></tr>\n";
  }
  echo '</table>';
}


function save_qTranslateSlug_term_fields( $term_id, $tt_id, $taxonomy ) {
     global $wpdb, $q_config, $post_type_object;
	
     
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	|| (!isset($_POST['tag_ID']) || $term_id != $_POST['tag_ID'])
	|| (!current_user_can('edit_posts'))) {
		return $term_id;
	}
		
	 if ( !wp_verify_nonce( $_POST['qts_nonce'], 'qts_nonce' )) {
       return;
     }
     
     $tag_type = qTranslateSlug_get_term_type($taxonomy);
	
     
     foreach($q_config['enabled_languages'] as $lang) {
        if (isset($_POST["qts_{$lang}_slug"])){
          $slug = sanitize_title($_POST["qts_{$lang}_slug"]);
          if ($slug === ''){
            $remove[] = $wpdb->prepare('%s',$lang);
          } else {
            $update[] = $wpdb->prepare("(%d,%d,%s,%s)",$tag_type,$term_id,$lang,$slug);
          }
        }
     }
  
     if (isset($remove)){
        $sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}qtranslate_slug
        			WHERE qts_type = %d
        			  AND qts_id = %d
        			  AND qts_lang in (",
                  $tag_type, $term_id) . implode(',',$remove) . ')';
        $res = $wpdb->query($sql);
        //$fb && fb($sql, 'remove query') && fb($res, 'remove results');
     }
     if (isset($update)){
        $sql = "INSERT INTO {$wpdb->prefix}qtranslate_slug (qts_type,qts_id,qts_lang,qts_slug) VALUES "
          . implode(',',$update)
          . " ON DUPLICATE KEY UPDATE qts_slug=VALUES(qts_slug)";
        $res = $wpdb->query($sql);
        //$fb && fb($sql, 'update query') && fb($res, 'update results');
     }
}



/**
 * Saves the translated slug when the page is saved
 * 
 * @param $post_id the post id
 * @param $post the post object
 */
function qTranslateSlug_save_postdata( $post_id, $post ) {
  static $last_done = '';
  
  global $wpdb, $q_config;
  //$fb = function_exists('fb');
  //$fb && fb($_POST,'$_POST') && fb($last_done,'$last_done');

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if (!isset($_POST['qts_nonce'])) return;
  
  if (!wp_verify_nonce( $_POST['qts_nonce'], 'qts_nonce' )) return;
  
  $post_types = get_post_types(array('_builtin' => false ));

  if ( 'page' == $post->post_type || in_array($post->post_type, $post_types) ) {
    $type = QTS_PAGE;
    if ( !current_user_can( 'edit_page', $post_id ))
      return;
  } else if ( 'post' == $post->post_type ) {
    $type = QTS_POST;
    if ( !current_user_can( 'edit_post', $post_id ))
      return;
  } else {
    return;
  }

  // OK, we're authenticated: we need to find and save the data
  if ($last_done === $post_id) {
    return;
  } else {
    $last_done = $post_id;
  }
  
  $update = array();
  $remove = array();
  
  ### DO AUTO-TITLE SLUG HERE!!!! $_POST['post_name']
  
  foreach($q_config['enabled_languages'] as $lang) {
    if (isset($_POST["qts_{$lang}_slug"])){
      $slug = sanitize_title($_POST["qts_{$lang}_slug"]);
      if ($slug === ''){
        $remove[] = $wpdb->prepare('%s',$lang);
      } else {
        $update[] = $wpdb->prepare("(%d,%d,%s,%s)",$type,$post_id,$lang,$slug);
      }
    }
  }
  
  if ($remove){
    $sql = $wpdb->prepare("DELETE FROM {$wpdb->prefix}qtranslate_slug
    			WHERE qts_type = %d
    			  AND qts_id = %d
    			  AND qts_lang in (",
              $type, $post_id) . implode(',',$remove) . ')';
    $res = $wpdb->query($sql);
    //$fb && fb($sql, 'remove query') && fb($res, 'remove results');
  }
  if ($update){
    $sql = "INSERT INTO {$wpdb->prefix}qtranslate_slug (qts_type,qts_id,qts_lang,qts_slug) VALUES "
      . implode(',',$update)
      . " ON DUPLICATE KEY UPDATE qts_slug=VALUES(qts_slug)";
    $res = $wpdb->query($sql);
    //$fb && fb($sql, 'update query') && fb($res, 'update results');
  }
}

// Language Select Code for non-Widget users
function qTranslateSlug_generateLanguageSelectCode($style='', $id='qtrans_language_chooser') {
	
	global $q_config;
	
	if($style=='') $style='text';
	if(is_bool($style)&&$style) $style='image';
	switch($style) {
		case 'image':
		case 'text':
		case 'dropdown':
		echo '<ul class="qtrans_language_chooser" id="'.$id.'">';
		$sorted_languages = qtrans_getSortedLanguages();
		$counter_ = 0;
		$counter_total_ = count($sorted_languages);
		foreach($sorted_languages as $language) {
			$counter_ ++;
			$last_class = ' ';
			if($counter_ == $counter_total_) $last_class = ' last-child';
			echo '<li';
			if((string)$language == (string)$q_config['language']):
			echo ' class="active'.$last_class.'"';
			else:
			echo ' class="'.$last_class.'"';
			endif;
			echo '><a href="'.qTranslateSlug_getSelfUrl($language).'"';
			// set hreflang
			echo ' hreflang="'.$language.'"';
			if($style=='image')
				echo ' class="qtrans_flag qtrans_flag_'.$language.'"';
			echo '><span';
			if($style=='image')
				echo ' style="display:none"';
			echo '>'.$language.'</span></a></li>';
		}
		echo "</ul><div class=\"qtrans_widget_end\"></div>";
		if($style=='dropdown') {
			echo "<script type=\"text/javascript\">\n// <![CDATA[\r\n";
			echo "var lc = document.getElementById('".$id."');\n";
			echo "var s = document.createElement('select');\n";
			echo "s.id = 'qtrans_select_".$id."';\n";
			echo "lc.parentNode.insertBefore(s,lc);";
			// create dropdown fields for each language
			foreach(qtrans_getSortedLanguages() as $language) {
				echo qtrans_insertDropDownElement($language, qTranslateSlug_getSelfUrl($language), $id);
			}
			// hide html language chooser text
			echo "s.onchange = function() { document.location.href = this.value;}\n";
			echo "lc.style.display='none';\n";
			echo "// ]]>\n</script>\n";
		}
		break;
		case 'both':
		echo '<ul class="qtrans_language_chooser" id="'.$id.'">';
		$sorted_languages = qtrans_getSortedLanguages();
		$counter_ = 0;
		$counter_total_ = count($sorted_languages);
		foreach($sorted_languages as $language) {
			$counter_ ++;
			$last_class = ' ';
			if($counter_ == $counter_total_) $last_class = ' last-child';
			echo '<li';
			if($language == $q_config['language']):
			echo ' class="active'.$last_class.'"';
			else:
			echo ' class="'.$last_class.'"';
			endif;
			echo '><a href="'.qTranslateSlug_getSelfUrl($language).'"';
			echo ' class="qtrans_flag_'.$language.' qtrans_flag_and_text"';
			echo '><span>'.$q_config['language_name'][$language].'</span></a></li>';
		}
		echo "</ul><div class=\"qtrans_widget_end\"></div>";
		break;
	}
}

class qTranslateslugWidget extends WP_Widget {
	function qTranslateslugWidget() {
		$widget_ops = array('classname' => 'widget_qtranslateslug', 'description' => __('Allows your visitors to choose a Language.','qtranslate') );
		$this->WP_Widget('qtranslateslug', 'qTranslate slug widget', $widget_ops);
	}
	
	function widget($args, $instance) {
		extract($args);
		
		echo $before_widget;
		$title = empty($instance['title']) ? __('Language', 'qtranslate') : apply_filters('widget_title', $instance['title']);
		$hide_title = empty($instance['hide-title']) ? false : 'on';
		$type = $instance['type'];
		if($type!='text'&&$type!='image'&&$type!='both'&&$type!='dropdown') $type='text';

		if($hide_title!='on') { echo $before_title . $title . $after_title; };
		qTranslateSlug_generateLanguageSelectCode($type, $this->id);
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['hide-title'] = $new_instance['hide-title'];
		$instance['type'] = $new_instance['type'];

		return $instance;
	}
	
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'hide-title' => false, 'type' => 'text' ) );
		$title = $instance['title'];
		$hide_title = $instance['hide-title'];
		$type = $instance['type'];
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'qtranslate'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id('hide-title'); ?>"><?php _e('Hide Title:', 'qtranslate'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('hide-title'); ?>" name="<?php echo $this->get_field_name('hide-title'); ?>" <?php echo ($hide_title=='on')?'checked="checked"':''; ?>/></label></p>
		<p><?php _e('Display:', 'qtranslate'); ?></p>
		<p><label for="<?php echo $this->get_field_id('type'); ?>1"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>1" value="text"<?php echo ($type=='text')?' checked="checked"':'' ?>/> <?php _e('Text only', 'qtranslate'); ?></label></p>
		<p><label for="<?php echo $this->get_field_id('type'); ?>2"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>2" value="image"<?php echo ($type=='image')?' checked="checked"':'' ?>/> <?php _e('Image only', 'qtranslate'); ?></label></p>
		<p><label for="<?php echo $this->get_field_id('type'); ?>3"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>3" value="both"<?php echo ($type=='both')?' checked="checked"':'' ?>/> <?php _e('Text and Image', 'qtranslate'); ?></label></p>
		<p><label for="<?php echo $this->get_field_id('type'); ?>4"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>4" value="dropdown"<?php echo ($type=='dropdown')?' checked="checked"':'' ?>/> <?php _e('Dropdown Box', 'qtranslate'); ?></label></p>
<?php
	}
}



/**
 * adds support for qtranslate nav menus
 * @package Qtranslate Slug
 * @version 0.9
**/
function wa_admin_init() {
	global $pagenow;
	if( $pagenow != 'nav-menus.php' ) return;
	
	wp_enqueue_script('nav-menu-query',  plugins_url( 'js/qt-nav-menu-min.js' , __FILE__ ) , 'nav-menu', '1.0');
	add_meta_box( 'qt-languages', __('Languages'), 'qt_languages_meta_box', 'nav-menus', 'side', 'default' );
}

function qt_languages_meta_box() {
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
add_action('admin_init', 'wa_admin_init');


/**
 * adds support for qtranslate in taxonomies
 * @package Qtranslate Slug
 * @version 0.8
**/
function qtranslate_edit_taxonomies(){
	
	$has_titlte = array('category', 'post_tag');
	//$has_slug = array( 'families' );
	
	$args = array( 'public' => true, 'show_ui' => true, '_builtin' => true 	); 
	$categories = get_taxonomies($args, 'object'); 
	$args = array( 'public' => true, '_builtin' => false ); 
	$taxonomies = get_taxonomies($args, 'object' ); 
	
	$taxonomies = array_merge( $categories, $taxonomies );

	if  ($taxonomies) {
		foreach ($taxonomies  as $taxonomy ) {
			if (in_array($taxonomy->name, $has_titlte) ) {
				add_action( $taxonomy->name.'_add_form', 'qtrans_modifyTermFormFor');
				add_action( $taxonomy->name.'_edit_form', 'qtrans_modifyTermFormFor');
			}
		}
	}

}
add_action('admin_menu', 'qtranslate_edit_taxonomies', 805);
add_filter('single_term_title', 'qtrans_useTermLib', 805);



/**
 * adds support for qtranslateSlug in taxonomies
 * @package Qtranslate Slug
 * @version 0.8
**/
/*
function qtranslateSlug_filter_taxonomies(){
	
	if ( !isset($_GET['action']) || $_GET['action'] != 'edit' || !isset($_GET['taxonomy']) || !isset($_GET['tag_ID']) ) return false;
	
	$has_slug = array( 'category', 'post_tag');
	
	if (!in_array($_GET['taxonomy'], $has_slug) ) {
		remove_action('edit_category_form_fields', 'qTranslateSlug_tag_fields');
		remove_action('edit_tag_form_fields', 'qTranslateSlug_tag_fields');
		remove_action('edited_term', 'save_qTranslateSlug_term_fields', 10, 3);
	}
}
add_action('admin_init', 'qtranslateSlug_filter_taxonomies', 800);
*/

/**
 * hide quickedit button (functionality not supported by qTranslate)
 * @package Qtranslate Slug
 * @version 0.8
**/
function hide_quick_edit_script() {
	global $pagenow;
	
	if (is_admin())
		echo"		
		<script type=\"text/javascript\">
			jQuery(document).ready(function($) {
				// Removing Quick Edit button
				var editinline = $('a.editinline');
				if(editinline.length > 0) editinline.parent().remove();
			});
		</script>";
}
add_action('admin_footer','hide_quick_edit_script', 600);



/**
 * get traduction slug for post type
 * @package Qtranslate Slug
 * @version 0.8
**/
function get_custom_post_type_slug($post_type) {
	global $custom_post_types_slugs, $q_config, $qts_use_language;
	
	if(!function_exists('qTranslateSlug_post_type_link') || !isset($custom_post_types_slugs) ) return $post_type;
	
	$lang = ($qts_use_language === null) ? $q_config['language'] : $qts_use_language;
	
	if (is_array($custom_post_types_slugs[$post_type])) {
		return $custom_post_types_slugs[$post_type][$lang];
	} else {
		return $post_type;
	}
}

function get_custom_taxonomy_slug($taxonomy) {
	global $taxonomies_slugs, $q_config, $qts_use_language;
	
	if(!function_exists('qTranslateSlug_term_link') || !isset($taxonomies_slugs) ) return $taxonomy;
	
	$lang = ($qts_use_language === null) ? $q_config['language'] : $qts_use_language;
	
	if (is_array($taxonomies_slugs[$taxonomy])) {
		return $taxonomies_slugs[$taxonomy][$lang];
	} else {
		return $taxonomy;
	}
}


function qtranslateSlug_blog_names($blogs) {
	
	foreach ($blogs as $blog) {
		$blog->blogname = __($blog->blogname);
	}
	return $blogs;
}

add_filter( 'get_blogs_of_user', 'qtranslateSlug_blog_names', 1 );


function qtranslug_widget_init() {
		register_widget('qTranslateslugWidget');
}
add_action('widgets_init', 'qtranslug_widget_init');