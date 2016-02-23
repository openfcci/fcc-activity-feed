<?php
/*
Plugin Name: FCC Activity Feed
Plugin URI:
Description: Global Recent posts function and shortcode
Author: Forum Communications Company
Version: 0.16.02.18
Author URI: http://forumcomm.com/
*/

define( 'ACTFEED__PLUGIN_DIR',  plugin_dir_url( __FILE__ ) );

function fcc_add_thickbox() {
	if ( ! is_admin() ) {
		add_thickbox();
	} else {
		//Admin
	}
}
//add_action('wp_head', 'fcc_add_thickbox');

/* NOTES
* An alternative to adding the thickbox HTML around all links with the WordPress
* filter is to use jQuery to find all images on the page, step up to it's parent
* element which has to be an anchor tag, then we add a class of thickbox.
*/
//if( !function_exists('add_thickbox_classes_js') ){
//  function add_thickbox_classes_js() {
//		$fcc_custom_js = 'jQuery(document).ready(function(){jQuery("img").parent("a").addClass("thickbox")});';
//		echo '<script type="text/javascript">' . $fcc_custom_js . '</script>';
//  }
//}
//add_action( 'wp_footer', 'add_thickbox_classes_js' ); // add the additional script to footer area

/*
Usage:
display_recent_posts(NUMBER,TITLE_CHARACTERS,CONTENT_CHARACTERS,TITLE_CONTENT_DIVIDER,TITLE_BEFORE,TITLE_AFTER,GLOBAL_BEFORE,GLOBAL_AFTER,BEFORE,AFTER,TITLE_LINK,SHOW_AVATARS,AVATAR_SIZE,POSTTYPE, ECHO);

Ex:
display_recent_posts(10,40,150,'<br />','<strong>','</strong>','<ul>','</ul>','<li>','</li>','yes','yes',16, 'post', true);
*/
class recentpostsshortcode {

	var $build = 1;

	var $db;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		if($this->db->blogid == 1) {
			// Only add the feed for the main site
			add_action('init', array(&$this, 'initialise_recentpostsshortcode') );
		}

		add_shortcode( 'globalrecentposts', array( &$this, 'display_recent_posts_shortcode') );

	}

	function recentpostsshortcode() {
		$this->__construct();
	}

	function initialise_recentpostsshortcode() {
		// In case we need it in future :)
	}

	function display_recent_posts($tmp_number,$tmp_title_characters = 0,$tmp_content_characters = 0,$tmp_title_content_divider = '<br />',$tmp_title_before,$tmp_title_after,$tmp_global_before,$tmp_global_after,$tmp_before,$tmp_after,$tmp_title_link = 'no',$tmp_show_avatars = 'yes', $tmp_avatar_size = 16, $posttype = 'post', $output = true) {

		$html = '';

		/***  JSON Loop Starts Here ***/
			$html .= $tmp_global_before; # Keep
			$default_avatar = get_option('default_avatar');
			#JSON
			$response = file_get_contents('http://avsearch.fccinteractive.com:8080/solr/select?indent=on&version=2.2&q=*%3A*&fq=blogid%3A%5B2+TO+*%5D&fq=publishtime%3A%5BNOW-7DAY+TO+NOW%5D&start=0&rows=1000&fl=id%2C+permalink%2C+blogid%2C+title%2C+author%2C+type%2C+publishtime%2C+categories&qt=&wt=json&sort=publishtime+desc&omitHeader=true');
			$response = json_decode($response, true);
			$docs = $response['response']['docs'];

			for($i = 0; $i < count($docs); $i++){

				$obj = $docs[$i];

				$html .= $tmp_before; # Keep
				if ( $tmp_title_characters > 0 ) {
					$html .= $tmp_title_before; # Keep

					#Article Title
					$feed_title =$obj['title'];
					#Article ID
					$feed_id = preg_replace('/[^0-9]/','',$obj['id']);
					#Article Blog ID
					$feed_blogid = $obj['blogid'];
					#Article Permalink
					$feed_permalink = $obj['permalink'];
					#Article Author
					$feed_author = $obj['author'];
					#Article Type
					$feed_type = $obj['type'];
					#Article Publish Time
					$feed_publishtime = $obj['publishtime'];
					#Article Category
					$feed_category = $obj['categories'][0];
					#Article Time
					$post_time = gmdate( 'm/d/Y g:i a', $feed_publishtime);

					switch_to_blog( $feed_blogid );
					$featured_image = get_the_post_thumbnail( $feed_id, 'small-thumb' );
					restore_current_blog();
					// $featured_image = ACTFEED__PLUGIN_DIR . 'placeholder.jpeg';
					$featured_image_url = '<img src="' . $featured_image . '">';


					# Display Featured Image Thumbnail if available
					if ( $featured_image ) {
						$html .= '<div class="sidebar-list-img left relative">' . $featured_image_url . '</div>';
					}

					# Title & Author
					if ( $tmp_title_link == 'no' ) {
						$html .= substr($feed_title,0,$tmp_title_characters);
					}
					else {
						$html .= '<div class="sidebar-list-text left relative">';
						$html .= '<a href="' . $permalink . '" target="_blank">' . substr($feed_title,0,$tmp_title_characters) . '</a>';
					}

					# Category & Post Time
					$html .= '<div class="widget-post-info left">';
					$html .= '<span class="widget-post-cat">' . $feed_category . '</span>';
					$html .= '<span class="widget-post-date">' . $post_time . '</span>';
					$html .= '<span class="widget-post-date">' . '<strong>' .  $feed_author . '</strong></span>';
					$html .= '</div></div><br>';

					$html .= $tmp_title_after;
				}
				$html .= $tmp_title_content_divider;

				# The Content (Not Needed Currently)
				//if ( $tmp_content_characters > 0 ) {
					//$the_content = network_get_the_content();
					//$html .= substr(strip_tags($the_content),0,$tmp_content_characters) . '...';
				//}

				$html .= $tmp_title_content_divider;

				$html .= $tmp_after;
			$html .= $tmp_global_after;
		}

		if($output) {
			echo $html;
		} else {
			return $html;
		}

	}

	function display_recent_posts_shortcode($atts, $content = null, $code = "") {

		$defaults = array(	'number'	=>	5,
							'title_characters' => 250,
							'content_characters' => 0,
							'title_content_divider' => '<br />',
							'title_before'	=>	'',
							'title_after'	=>	'',
							'global_before'	=>	'<ul class="sidebar-list-tabs left relative">',
							'global_after'	=>	'</ul>',
							'before'	=>	'<li>',
							'after'	=>	'</li>',
							'title_link' => 'yes',
							'show_avatars' => 'no',
							'avatar_size' => 16,
							'posttype' => 'post'
						);

		extract(shortcode_atts($defaults, $atts));

		$html = '';

		$html .= $this->display_recent_posts( $number, $title_characters, $content_characters, $title_content_divider, $title_before, $title_after, $global_before, $global_after, $before, $after, $title_link, $show_avatars, $avatar_size, $posttype, false);

		return $html;

	}

}

function display_recent_posts($tmp_number,$tmp_title_characters = 0,$tmp_content_characters = 0,$tmp_title_content_divider = '<br />',$tmp_title_before,$tmp_title_after,$tmp_global_before,$tmp_global_after,$tmp_before,$tmp_after,$tmp_title_link = 'no',$tmp_show_avatars = 'yes', $tmp_avatar_size = 16, $posttype = 'post', $output = true) {
	global $recentpostsshortcode;

	$recentpostsshortcode->display_recent_posts( $tmp_number, $tmp_title_characters, $tmp_content_characters, $tmp_title_content_divider, $tmp_title_before, $tmp_title_after, $tmp_global_before, $tmp_global_after, $tmp_before, $tmp_after, $tmp_title_link, $tmp_show_avatars, $tmp_avatar_size, $posttype, $output );
}

$recentpostsshortcode = new recentpostsshortcode();
