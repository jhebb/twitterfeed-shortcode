<?php
/**
 * Plugin Name: Twitter Feed Shortcode
 * Description: A simple twitter feed shortcode using Twitter API 1.1.
 * Version: 0.1
 * Author: Justin Hebb
 * Author URI: http://jukah.com
 */

include_once('class-wp-twitter-api.php');
include_once('twitter-feed-settings.php');


/**
 * Display Tweets Shortcode
 *
 * Shortcode to display tweets using twitter api 1.1
 *
 * @param $account string required - name of twitter account.
 * @param $count int optional - number of tweets to display.
 * @param $exclude_replies optional boolean - show or hide replies.
 * @param $include_rts optional boolean - show or hide retweets.
 * @param $add_links optional boolean - add markup for hastags and names.
 * @param $cache optional boolean - length of time to chace results, in seconds .
 *
 * Usage: [twitterfeed account="twitteraccount" count="5" exclude_replies="false" include_rts="true" add_links=true cache=1800]
 */
function shortcode_twitter_feed($atts) {

	extract( shortcode_atts( array(
        'account'           => '',
        'count'             => 5,
        'exclude_replies'   => false,
        'include_rts'       => true,
        'add_links'         => true,
        'cache'             => 60 * 30
    ), $atts ) );

	// Fix SSL issues for WordPress (https://plus.google.com/107110219316412982437/posts/gTdK4MrnKUa)
	add_filter( 'https_ssl_verify', '__return_false' );
	add_filter( 'https_local_ssl_verify', '__return_false' );


	// Set access keys
	$twittersettings = get_option('twitterfeed_settings');

	$credentials = array(
	  'consumer_key' => $twittersettings['consumer_key'],
	  'consumer_secret' => $twittersettings['consumer_secret']
	);


	// Set query & arg variables
	$twitter_link = 'http://twitter.com/' . $account;

	// Let's instantiate Wp_Twitter_Api with your credentials
	if ( $credentials['consumer_key'] && $credentials['consumer_secret'] ) {
		$twitter_api = new Wp_Twitter_Api( $credentials );
	} else {
		return 'Please check your credentials!<br />';
	}

	// Set up query
	$query = 'count=' .$count. '&include_entities=true&exclude_replies=' . $exclude_replies . '&include_rts=' . $include_rts . '&screen_name=' .$account;

	// Add arguments
	$args = array (
	  'cache' => $cache
	);


	// Compile the results
	$tweets = $twitter_api->query( $query, $args );
	$result = '';

	// return $tweets;

	if ( !is_null($tweets) && is_array($tweets) ) {
		$tweet_display = '';
		foreach ( $tweets as $tweet ) {
		    /* Human friendly times  */
			$timerange = 60*60*24*7; // one week in seconds
			if ( ( current_time('timestamp') - strtotime( $tweet->created_at ) ) < $timerange ) {
				$tweet_time = 'about '.human_time_diff( strtotime( $tweet->created_at ), current_time('timestamp') ) . ' ago';
			} else {
				$tweet_time = date( "F jS, g:i a", strtotime( $tweet->created_at ) );
			}
			// Tweet permalink
		    $tweet_permalink = str_replace(
				array(
			        '%screen_name%',
			        '%id%',
			        '%created_at%'
			    ),
			    array(
			        $tweet->user->screen_name,
			        $tweet->id_str,
			        $tweet_time,
			    ),
			        '<span class="twitter-feed-tweet-time"><a href="https://twitter.com/%screen_name%/status/%id%" target="_blank">%created_at%</a></span>'
			);

			// Add links to hashtags & names & shortlinks
		    if ($add_links == 'true') {
			    // linkify @names
			    $tweet->text = preg_replace('!@([a-z0-9_]{1,15})!i', '<a class="twitter-screen-name" href="https://twitter.com/\\1" target="_blank">\\0</a>', $tweet->text );
			    // linkify #hashtags
			    $tweet->text = preg_replace('/(?<!&)#(\w+)/i', '<a class="twitter-hashtag" href="https://twitter.com/search?q=%23\\1&amp;src=hash" target="_blank">\\0</a>', $tweet->text );

			    // linkify t.co links
				foreach ($tweet->entities->urls as $url) {
			        $tweet->text = str_replace($url->url, '<a class="twitter-shortlink" href="' . $url->expanded_url . '" target="_blank">' . $url->url . '</a>', $tweet->text);
			    }
		    }

		    // Compile single tweet markup
	    	$tweet_display .= '<div class="twitter-feed-tweet">';
			$tweet_display .= $tweet->text;
			$tweet_display .= $tweet_permalink;
			$tweet_display .= '</div>';
		}


		// Compile feed markup
		$result .= '<div class="twitter-feed-wrap">';
		//$result .= '<a href="'. $twitter_link . '"><div class="twitter-feed-title">twitter</div></a>';
		$result .= '<div class="twitter-feed">' .$tweet_display. '</div>';
		$result .= '</div>';

	} else {

		$result .= 'No tweets available!<br />';
	}

	// Return markup and results
	return $result;
}
add_shortcode("twitterfeed", "shortcode_twitter_feed");
