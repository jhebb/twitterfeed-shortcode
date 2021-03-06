<?php
/**
 * Plugin Name: Twitter Feed Shortcode
 * Description: A simple twitter feed shortcode using Twitter API 1.1.
 * Version: 0.1.7
 * Author: Justin Hebb
 * Author URI: http://jukah.com
 */

include_once( 'class-wp-twitter-api.php' );
include_once( 'twitter-feed-settings.php' );


/**
 * Display Tweets Shortcode
 *
 * Shortcode to display tweets using twitter api 1.1
 *
 * @param $account string required - name of twitter account.
 * @param $count int optional - number of tweets to display.
 * @param $exclude_replies boolean optional - show or hide retweets.
 * @param $add_links boolean optional - add markup for hastags and names.
 * @param $cache boolean optional - length of time to cache results, in seconds.
 *
 * Usage: [twitterfeed account="twitteraccount" count="5" exclude_replies="false" include_rts="true" add_links=true cache=1800]
 */
function shortcode_twitter_feed( $atts ) {

	$atts = shortcode_atts( array(
		'account'           => '',
		'count'             => 5,
		'exclude_replies'   => false,
		'include_rts'       => true,
		'add_links'         => true,
		'show_images'		=> true,
		'cache'             => 60 * 30,
	), $atts );

	// Fix SSL issues for WordPress (https://plus.google.com/107110219316412982437/posts/gTdK4MrnKUa)
	add_filter( 'https_ssl_verify', '__return_false' );
	add_filter( 'https_local_ssl_verify', '__return_false' );

	// Set access keys
	$twittersettings = get_option( 'twitterfeed_settings' );

	// Check credentials
	$credentials = array(
		'consumer_key' => $twittersettings['consumer_key'],
		'consumer_secret' => $twittersettings['consumer_secret'],
	);

	// Check twitter account
	if ( ! $atts['account'] ) {
		return 'Please check your account!<br />';
	}

	// Set query & arg variables
	$twitter_link = 'http://twitter.com/' . $atts['account'];

	// Let's instantiate Wp_Twitter_Api with your credentials
	if ( $credentials['consumer_key'] && $credentials['consumer_secret'] ) {
		$twitter_api = new Wp_Twitter_Api( $credentials );
	} else {
		return 'Please check your credentials!<br />';
	}

	// Set up query
	$query = 'count=' . $atts['count'] . '&include_entities=true&exclude_replies=' . $atts['exclude_replies'] . '&include_rts=' . $atts['include_rts'] . '&screen_name=' . $atts['account'];

	// Add arguments
	$args = array(
		'cache' => $atts['cache'],
	);

	// Get the results
	$tweets = $twitter_api->query( $query, $args );

	// Return the feed markup
	return twitter_feed_markup( $tweets, $atts['add_links'], $atts['show_images'] );
}
add_shortcode( 'twitterfeed', 'shortcode_twitter_feed' );



/**
 * Generate Tweets markup
 *
 * Generates and assembles markup for twitter feed.
 *
 * @param $tweets array required - an array containing the tweets.
 * @param $add_links boolean optional - add markup for hastags and names.
 * @param $show_images boolean optional - add markup to show embedded images.
 *
 */
function twitter_feed_markup( $tweets, $add_links = true, $show_images = true ) {

	if ( ! is_null( $tweets ) && is_array( $tweets ) ) {
		$tweet_display = '';
		foreach ( $tweets as $tweet ) {
			// Human friendly times
			$timerange = 60 * 60 * 24 * 7; // one week in seconds
			if ( ( current_time( 'timestamp' ) - strtotime( $tweet->created_at ) ) < $timerange ) {
				$tweet_time = 'about ' . human_time_diff( strtotime( $tweet->created_at ), current_time( 'timestamp' ) ) . ' ago';
			} else {
				$tweet_time = date( 'F jS, g:i a', strtotime( $tweet->created_at ) );
			}

			// Tweet permalink
			$tweet_permalink = str_replace(
				array(
					'%screen_name%',
					'%id%',
					'%created_at%',
				),
				array(
					$tweet->user->screen_name,
					$tweet->id_str,
					$tweet_time,
				),
				'<span class="twitter-feed-tweet-time"><a href="https://twitter.com/%screen_name%/status/%id%" target="_blank">%created_at%</a></span>'
			);

			// Add links to hashtags & names & shortlinks
			if ( 'true' == $add_links ) {
				// linkify @names
				$tweet->text = preg_replace( '!@([a-z0-9_]{1,15})!i', '<a class="twitter-screen-name" href="https://twitter.com/$1" target="_blank">$0</a>', $tweet->text );

				// linkify #hashtags
				$tweet->text = preg_replace( '/(?<!&)#(\w+)/i', '<a class="twitter-hashtag" href="https://twitter.com/search?q=%23$1&amp;src=hash" target="_blank">$0</a>', $tweet->text );

				// linkify t.co links
				foreach ( $tweet->entities->urls as $url ) {
					$tweet->text = str_replace( $url->url, '<a class="twitter-shortlink" href="' . $url->expanded_url . '" target="_blank">' . $url->url . '</a>', $tweet->text );
				}

				// linkify t.co links for media - check for the media entity first as it's not always set
				if ( isset( $tweet->entities->media ) ) {
					foreach ( $tweet->entities->media as $themedia ) {
						if ( $themedia->media_url && $show_images ) {
							$tweet->text = str_replace( $themedia->url, '<a class="twitter-shortlink" href="' . $themedia->expanded_url . '" target="_blank"><img class="twitter-feed-media" src="' . $themedia->media_url . '"/></a>', $tweet->text );
						} else {
							$tweet->text = str_replace( $themedia->url, '<a class="twitter-shortlink" href="' . $themedia->expanded_url . '" target="_blank">' . $themedia->url . '</a>', $tweet->text );
						}
					}
				}
			}

			// Compile single tweet markup
			$tweet_display .= '<div class="twitter-feed-tweet">';
			$tweet_display .= $tweet->text . ' '; // extra space between text and date
			$tweet_display .= $tweet_permalink;
			$tweet_display .= '</div>';
		}

		// Compile feed markup
		$result = '<div class="twitter-feed-wrap">';
		$result .= '<div class="twitter-feed">' . $tweet_display . '</div>';
		$result .= '</div>';

	} else {
		$result = 'No tweets available!<br />';
	}

	// Return markup and results
	return $result;
}
