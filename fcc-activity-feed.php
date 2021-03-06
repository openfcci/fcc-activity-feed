<?php
/*
Plugin Name: FCC Activity Feed
Plugin URI: https://github.com/openfcci/fcc-activity-feed
Description: An activity feed for displaying global network posts.
Author: Forum Communications Company
Version: 1.16.05.22
Author URI: http://forumcomm.com/
*/

define( 'ACTFEED__PLUGIN_DIR',  plugin_dir_url( __FILE__ ) );

function activityfeed_add_lightbox() {
	if ( ! is_admin() && ! is_home() ) {
		$pagename = get_query_var( 'pagename' );
		if ( 'activity' == $pagename ) {
			wp_enqueue_style( 'wp-featherlight', ACTFEED__PLUGIN_DIR . 'includes/wp-featherlight.min.css' );
			wp_enqueue_script( 'wp-featherlight', ACTFEED__PLUGIN_DIR . 'includes/wpFeatherlight.pkgd.min.js' );
		}
	} else {
		// Don't add lightbox
	}
}
add_action( 'wp_head', 'activityfeed_add_lightbox' );

/*
Usage:
solr_display_recent_posts(NUMBER,TITLE_CHARACTERS,CONTENT_CHARACTERS,TITLE_CONTENT_DIVIDER,TITLE_BEFORE,TITLE_AFTER,GLOBAL_BEFORE,GLOBAL_AFTER,BEFORE,AFTER,TITLE_LINK,SHOW_AVATARS,AVATAR_SIZE,POSTTYPE, ECHO);

Ex:
solr_display_recent_posts(10,40,150,'<br />','<strong>','</strong>','<ul>','</ul>','<li>','</li>','yes','yes',16, 'post', true);
*/
class SolrActivityFeedShortcode {

	var $build = 1;

	var $db;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		if ( 1 == $this->db->blogid ) {
			// Only add the feed for the main site
			add_action( 'init', array( &$this, 'initialise_solractivityfeedshortcode' ) );
		}

		add_shortcode( 'solrrecentposts', array( &$this, 'solr_display_recent_posts_shortcode' ) );

	}

	function solractivityfeedshortcode() {
		$this->__construct();
	}

	function initialise_solractivityfeedshortcode() {
		// In case we need it in future :)
	}

	function solr_display_recent_posts( $tmp_number, $tmp_title_characters = 0, $tmp_content_characters = 0, $tmp_title_content_divider = '<br />', $tmp_title_before, $tmp_title_after, $tmp_global_before, $tmp_global_after, $tmp_before, $tmp_after, $tmp_title_link = 'no', $tmp_show_avatars = 'yes', $tmp_avatar_size = 16, $posttype = 'post', $output = true ) {

		$html = '';

		/***  JSON Loop Starts Here ***/
		$default_avatar = get_option( 'default_avatar' );

		#JSON
		$response = file_get_contents( 'http://avsearch.fccinteractive.com:8080/solr/select?indent=on&version=2.2&q=*%3A*&fq=-blogid%3A1&fq=publishtime%3A%5BNOW-7DAY+TO+NOW%5D&fq=type%3Apost&start=0&rows=' . $tmp_number .'&fl=id%2C+permalink%2C+blogid%2C+title%2C+author%2C+type%2C+publishtime%2C+categories&qt=&wt=json&sort=publishtime+desc&omitHeader=true' );
		$response = json_decode( $response, true );
		$docs = $response['response']['docs'];
		$returnedposts = count( $docs );
		$totalposts = $response['response']['numFound'];

		$html .= '<h3 class="foot-head">Latest Posts</h3><div class="relative" style="text-align: left;">' . 'Showing ' . $returnedposts . ' of ' . $totalposts .' posts published since ' . date( 'm/d/Y', strtotime( '-7 days' ) ) . '.</div>';
		$html .= $tmp_global_before;

		for ( $i = 0; $i < count( $docs ); $i++ ) {

			$obj = $docs[ $i ];

			$html .= $tmp_before;
			if ( $tmp_title_characters > 0 ) {
				$html .= $tmp_title_before;

				# Article Title
				$feed_title = $obj['title'];
				# Article ID
				$feed_id = preg_replace( '/[^0-9]/','',$obj['id'] );
				# Article Blog ID
				if ( '0' === $obj['blogid'] ) {
					# Set SayAnythingBlog to correct blog_id
					$feed_blogid = '67725';
				} else {
					$feed_blogid = $obj['blogid'];
				}
				# Article Permalink
				$feed_permalink = $obj['permalink'];
				# Article Author
				$feed_author = $obj['author'];
				# Article Type
				$feed_type = $obj['type'];
				# Article Publish Time
				$feed_publishtime = $obj['publishtime'];
				# Article Category
				$feed_category = $obj['categories'][0];
				# Article Time
				$post_time = gmdate( 'm/d/Y g:i a', strtotime( $feed_publishtime ) );

				# Feed Site
				//$feed_site = get_blog_details( $feed_id )->path;
				$feed_site = preg_replace( '/www\./','',preg_replace( '/\/()\d+/','',$obj['id'] ) );

				# Placeholder Image
				$placeholder_image_url = ACTFEED__PLUGIN_DIR . 'placeholder.jpeg';
				$placeholder_image = '<a href="' .$placeholder_image_url . '?TB_iframe=true" class="thickbox">' . '<img src="' . $placeholder_image_url . '">' . '</a>';

				# Featured Image
				$featured_image = null;
				if ( '0' === $obj['blogid'] ) {
					switch ( $feed_site ) {
					    case 'sayanythingblog.com':
					        $featured_image = 'https://i0.wp.com/www.sayanythingblog.com/files/2016/04/sab-activityfeed.png?zoom=2&fit=95%2C35';
					        break;
					    case 'northlandoutdoors.com':
					        $featured_image = 'https://i2.wp.com/www.northlandoutdoors.com/files/2015/12/cropped-Northland-Outdoors-Site-Icon.png?resize=95%2C35';
					        break;
					    case 'bakkentoday.com':
					        $featured_image = 'https://i0.wp.com/bakkentoday.areavoices.com/wp-content/blogs.dir/67724/files/2016/03/cropped-Bakken-Today-Site-Icon.png?resize=95%2C35';
					        break;
					    default:
					        $featured_image = $placeholder_image_url;
					}

					$post_thumbnail_url = $featured_image;
					$featured_image_with_url = '<a href="' . $post_thumbnail_url . '">' . '<img src="' . $featured_image . '">' . '</a>';
				} else {
					switch_to_blog( $feed_blogid );
					$featured_image = get_the_post_thumbnail( $feed_id, 'small-thumb' );
					$post_thumbnail_url = wp_get_attachment_url( get_post_thumbnail_id( $feed_id ) );
					$featured_image_with_url = '<a href="' . $post_thumbnail_url . '?TB_iframe=true" class="thickbox">' . $featured_image . '</a>';
					restore_current_blog();
				}

				# Display Featured Image Thumbnail if available
				if ( $featured_image ) {
					$html .= '<div class="sidebar-list-img left relative">' . $featured_image_with_url . '</div>';
				} else {
					$html .= '<div class="sidebar-list-img left relative">' . $placeholder_image . '</div>';
				}

				# Title
				if ( 'no' == $tmp_title_link  ) {
					$html .= substr( $feed_title,0,$tmp_title_characters );
				} else {
					if ( ! $featured_image ) {
						$html .= '<div class="sidebar-list-text left relative">';
						$html .= '<a href="' . $feed_permalink . '" target="_blank" style="color: #888;">' . substr( $feed_title,0,$tmp_title_characters ) . '</a>';
					} else {
						$html .= '<div class="sidebar-list-text left relative">';
						$html .= '<a href="' . $feed_permalink . '" target="_blank">' . substr( $feed_title,0,$tmp_title_characters ) . '</a>';
					}
				}

				# Category & Post Time
				$html .= '<div class="widget-post-info left">';
				$html .= '<span class="widget-post-cat">' . $feed_category . '</span>';
				$html .= '<span class="widget-post-date">' . $post_time . '</span>';
				$html .= '<span class="widget-post-date">' . '<strong>' .  $feed_author . '</strong></span>';
				$html .= '<span class="widget-post-date">' .  $feed_site . '</span>';
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
		}
		$html .= $tmp_global_after;

		if ( $output ) {
			echo $html;
		} else {
			return $html;
		}

	}

	function solr_display_recent_posts_shortcode( $atts, $content = null, $code = '' ) {

		$defaults = array(
			'number' => 5,
			'title_characters' => 250,
			'content_characters' => 0,
			'title_content_divider' => '<br />',
			'title_before'	=> '',
			'title_after'	=> '',
			'global_before'	=> '<ul class="sidebar-list-tabs left relative">',
			'global_after'	=> '</ul>',
			'before' => '<li>',
			'after'	=> '</li>',
			'title_link' => 'yes',
			'show_avatars' => 'no',
			'avatar_size' => 16,
			'posttype' => 'post',
		);

		extract( shortcode_atts( $defaults, $atts ) );

		$html = '';

		$html .= $this->solr_display_recent_posts( $number, $title_characters, $content_characters, $title_content_divider, $title_before, $title_after, $global_before, $global_after, $before, $after, $title_link, $show_avatars, $avatar_size, $posttype, false );

		return $html;

	}
}

function solr_display_recent_posts( $tmp_number, $tmp_title_characters = 0, $tmp_content_characters = 0, $tmp_title_content_divider = '<br />', $tmp_title_before, $tmp_title_after, $tmp_global_before, $tmp_global_after, $tmp_before, $tmp_after, $tmp_title_link = 'no', $tmp_show_avatars = 'yes', $tmp_avatar_size = 16, $posttype = 'post', $output = true ) {
	global $solractivityfeedshortcode;

	$solractivityfeedshortcode->solr_display_recent_posts( $tmp_number, $tmp_title_characters, $tmp_content_characters, $tmp_title_content_divider, $tmp_title_before, $tmp_title_after, $tmp_global_before, $tmp_global_after, $tmp_before, $tmp_after, $tmp_title_link, $tmp_show_avatars, $tmp_avatar_size, $posttype, $output );
}

$solractivityfeedshortcode = new SolrActivityFeedShortcode();
