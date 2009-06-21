<?php
/*
Plugin Name: Subscribe Me
Plugin URI: http://www.semiologic.com/software/subscribe-me/
Description: Widgets that let you display subscribe links to RSS readers such as Google Reader.
Version: 5.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: subscribe-me-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php

Fam Fam Fam silk icon (feed_add and information) are copyright Mark James (http://www.famfamfam.com/lab/icons/silk/), and CC-By licensed:

http://creativecommons.org/licenses/by/2.5/

Large feed icons are copyright feedicons.com.

Other icons are copyright their respective holders.
**/


load_plugin_textdomain('subscribe-me', null, dirname(__FILE__) . '/lang');


/**
 * subscribe_me
 *
 * @package Subscribe Me
 **/

add_action('widgets_init', array('subscribe_me', 'widgets_init'));

if ( !is_admin() ) {
	add_action('wp_print_scripts', array('subscribe_me', 'scripts'));
	add_action('wp_print_styles', array('subscribe_me', 'styles'));
}

foreach ( array(
		'generate_rewrite_rules',
		'switch_theme',
		'update_option_active_plugins',
		'update_option_sidebars_widgets',
		) as $hook) {
	add_action($hook, array('subscribe_me', 'flush_cache'));
}

register_activation_hook(__FILE__, array('subscribe_me', 'flush_cache'));
register_deactivation_hook(__FILE__, array('subscribe_me', 'flush_cache'));

class subscribe_me extends WP_Widget {
	var $option_name = 'subscribe_me';
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_script('subscribe_me', $folder . 'js/scripts.js', array('jquery'), '5.0');
	} # scripts()
	
	
	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_style('subscribe_me', $folder . 'css/styles.css', null, '5.0');
	} # styles()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('subscribe_me');
	} # widgets_init()
	
	
	/**
	 * subscribe_me()
	 *
	 * @return void
	 **/

	function subscribe_me() {
		$widget_ops = array(
			'classname' => 'subscribe_me',
			'description' => __("Subscribe links to RSS readers such as Google Reader", 'subscribe-me'),
			);
		$control_ops = array(
			'width' => 330,
			);
		
		$this->WP_Widget('subscribe_me', __('Subscribe Me', 'subscribe-me'), $widget_ops, $control_ops);
	} # subscribe_me()
	
	
	/**
	 * widget()
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( is_feed() || isset($_GET['action']) && $_GET['action'] == 'print' )
			return;
		
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, subscribe_me::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. ( $title
					? ( $before_title . $title . $after_title )
					: ''
					)
				. $after_widget;
			return;
		}
		
		if ( $o = wp_cache_get($widget_id, 'widget') ) {
			echo $o;
			return;
		}
		
		# check if the widget has a class
		if ( strpos($before_widget, 'subscribe_me') === false ) {
			if ( preg_match("/^(<[^>]+>)/", $before_widget, $tag) ) {
				if ( preg_match("/\bclass\s*=\s*(\"|')(.*?)\\1/", $tag[0], $class) ) {
					$tag[1] = str_replace($class[2], $class[2] . ' subscribe_me', $tag[1]);
				} else {
					$tag[1] = str_replace('>', ' class="subscribe_me"', $tag[1]);
				}
				$before_widget = preg_replace("/^$tag[0]/", $tag[1], $before_widget);
			} else {
				$before_widget = '<div class="subscribe_me">' . $before_widget;
				$after_widget = $after_widget . '</div>' . "\n";
			}
		}
		
		$site_url = user_trailingslashit(site_url());
		$feed_url = user_trailingslashit(apply_filters('bloginfo', get_feed_link('rss2'), 'rss2_url'));
		$icons_url = plugin_dir_url(__FILE__) . 'icons';

		ob_start();
		
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;

		echo '<div class="subscribe_me_services">' . "\n";
		
		echo '<div class="' . ( $text ? 'float' : 'center' ) . '_feed_button">'
			. '<a href="' . esc_url($feed_url) . '"'
				. ' title="' . esc_attr(__('RSS Feed', 'subscribe-me')) . '" class="no_icon">'
			. '<img src="'
				. esc_url($icons_url . '/feed-' . ( $text ? 'large' : 'giant' ) . '.gif') . '"'
				. ( $text
					? ' height="48" width="48"'
					: ' height="80" width="80"'
					)
				. ' alt="' . esc_attr(__('RSS feed', 'subscribe-me')) . '"'
				. ' />'
			. '</a>'
			. '</div>' . "\n";
		
		if ( $text ) {
			echo '<div class="subscribe_me_text">' . "\n"
				. apply_filters('widget_text', wpautop($text))
				. '</div>' . "\n";
		}
		
		echo '</div>' . "\n";

		echo '<div class="subscribe_me_spacer subscribe_me_ruler"></div>' . "\n";

		echo '<div class="subscribe_me_extra" style="display: none;">' . "\n";

		foreach ( subscribe_me::get_extra_services() as $service_id =>  $service ) {
			echo '<a href="' . esc_url($service['url'])  . '" class="' . $service_id . ' no_icon"'
				. ' title="' . esc_attr($service['name']) . '"'
				. ( $service_id == 'help' && ( strpos(get_option('home'), 'semiologic.com') !== false )
					? ''
					: ' rel="nofollow"'
					)
				. '>'
				. $service['name']
				. '</a>' . "\n";
		}

		echo '<div class="subscribe_me_spacer"></div>' . "\n";

		echo '</div>' . "\n";
	
		echo $after_widget;
		
		$o = ob_get_clean();
		
		$o = str_replace(
			array(
				'%enc_url%', '%enc_feed%',
				'%feed%',
				),
			array(
				urlencode($site_url), urlencode($feed_url),
				esc_url($feed_url),
				),
			$o);
		
		wp_cache_add($widget_id, $o, 'widget');
		
		echo $o;
	} # widget()
	
	
	/**
	 * get_extra_services()
	 *
	 * @return array $services
	 **/

	function get_extra_services() {
		return array(
			'rss_feed' => array(
				'name' => __('Desktop Reader', 'subscribe-me'),
				'url' => 'feed:%feed%',
				),
			'bloglines' => array(
				'name' => __('Bloglines', 'subscribe-me'),
				'url' => 'http://www.bloglines.com/sub/%feed%',
				),
			'google' => array(
				'name' => __('Google', 'subscribe-me'),
				'url' => 'http://fusion.google.com/add?feedurl=%enc_feed%',
				),
			'live' => array(
				'name' => __('Live', 'subscribe-me'),
				'url' => 'http://www.live.com/?add=%enc_url%',
				),
			'netvibes' => array(
				'name' => __('Netvibes', 'subscribe-me'),
				'url' => 'http://www.netvibes.com/subscribe.php?url=%enc_feed%',
				),
			'newsgator' => array(
				'name' => 'Newsgator',
				'button' => 'addnewsgator.gif',
				'url' => 'http://www.newsgator.com/ngs/subscriber/subext.aspx?url=%enc_feed%',
				),
			'yahoo' => array(
				'name' => __('Yahoo!', 'subscribe-me'),
				'url' => 'http://add.my.yahoo.com/rss?url=%enc_feed%',
				),
			'help' => array(
				'name' => __('What\'s This?', 'subscribe-me'),
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-feeds/'
				),
			);
	} # get_extra_services()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance['title'] = strip_tags($new_instance['title']);
		if ( current_user_can('unfiltered_html') ) {
			$instance['text'] = $new_instance['text'];
		} else {
			$instance['text'] = $old_instance['text'];
		}
		
		subscribe_me::flush_cache();
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance
	 * @return void
	 **/

	function form($instance) {
		extract($instance, EXTR_SKIP);
		
		echo '<p>'
			. '<label>'
			. __('Title:', 'subscribe-me')
			. '<br />'
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '" />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label for="' . $this->get_field_id('text') . '">'
			. __('Text:')
			. '</label>'
			. '<br />'
			. '<textarea class="widefat" cols="20" rows="6"'
				. ' name="' . $this->get_field_name('text') . '"'
				. ' id="' . $this->get_field_id('text') . '"'
				. ' >'
				. $text
			. '</textarea>';
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance
	 **/

	function defaults() {
		return array(
			'title' => __('Syndicate', 'subscribe-me'),
			'text' => __('Subscribe to this site\'s RSS feed.', 'subscribe-me'),
			);
	} # defaults()
	
	
	/**
	 * flush_cache()
	 *
	 * @return void
	 **/

	function flush_cache($in = null) {
		$o = get_option('subscribe_me');
		
		unset($o['_multiwidget']);
		
		if ( !$o )
			return $in;
		
		foreach ( array_keys($o) as $id ) {
			wp_cache_delete("subscribe_me-$id", 'widget');
		}
		
		return $in;
	} # flush_cache()
} # subscribe_me


/**
 * the_subscribe_links()
 *
 * @return void
 **/

function the_subscribe_links($instance = null, $args = '') {
	if ( is_string($instance) )
		$instance = array('title' => $instance);
	
	$args = wp_parse_args($args, array(
		'before_widget' => '<div class="subscribe_me">' . "\n",
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		));
	
	the_widget('subscribe_me', $instance, $args);
} # the_subscribe_links()
?>