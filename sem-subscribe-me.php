<?php
/*
Plugin Name: Subscribe Me
Plugin URI: http://www.semiologic.com/software/subscribe-me/
Description: Adds widgets that let you display feed subscription buttons.
Version: 5.0 RC
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
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


load_plugin_textdomain('subscribe-me', null, plugin_dir_path(__FILE__) . '/lang');


/**
 * subscribe_me
 *
 * @package Subscribe Me
 **/

add_action('widgets_init', array('subscribe_me', 'widgetize'));

if ( !is_admin() ) {
	add_action('wp_print_scripts', array('subscribe_me', 'js'));
	add_action('wp_print_styles', array('subscribe_me', 'css'));
}

foreach ( array(
		'generate_rewrite_rules',
		'switch_theme',
		'update_option_active_plugins',
		'update_option_sidebars_widgets',
		) as $hook)
{
	add_action($hook, array('subscribe_me', 'clear_cache'));
}

register_activation_hook(__FILE__, array('subscribe_me', 'clear_cache'));
register_deactivation_hook(__FILE__, array('subscribe_me', 'clear_cache'));

class subscribe_me {
	/**
	 * js()
	 *
	 * @return void
	 **/

	function js() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_script('subscribe_me', $folder . 'js/scripts.js', array('jquery'), '5.0');
	} # js()
	
	
	/**
	 * css()
	 *
	 * @return void
	 **/

	function css() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_style('subscribe_me', $folder . 'css/styles.css', false, '5.0');
	} # css()
	
	
	/**
	 * widgetize()
	 *
	 * @return void
	 **/

	function widgetize() {
		$options = subscribe_me::get_options();
		
		$widget_options = array('classname' => 'subscribe_me', 'description' => __( "Feed subscription buttons for your site", 'subscribe-me') );
		$control_options = array('id_base' => 'subscribe_me', 'width' => 420);
		
		$id = false;
		
		# registered widgets
		foreach ( array_keys($options) as $o ) {
			if ( !is_numeric($o) ) continue;
			$id = "subscribe_me-$o";
			wp_register_sidebar_widget($id, __('Subscribe Me', 'subscribe-me'), array('subscribe_me', 'widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Subscribe Me', 'subscribe-me'), array('subscribe_me_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id ) {
			$id = "subscribe_me-1";
			wp_register_sidebar_widget($id, __('Subscribe Me', 'subscribe-me'), array('subscribe_me', 'widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Subscribe Me', 'subscribe-me'), array('subscribe_me_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	/**
	 * widget()
	 *
	 * @param array $args
	 * @param array $widget_args
	 * @return void
	 **/

	function widget($args, $widget_args = 1) {
		if ( is_feed() || isset($_GET['action']) && $_GET['action'] == 'print' )
			return;
		
		$options = subscribe_me::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract($widget_args, EXTR_SKIP);
		
		$args = array_merge((array) $options[$number], (array) $args);
		
		extract($args, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. $before_title . $title . $after_title
				. $after_widget;
			return;
		}
		
		if ( !( $o = wp_cache_get($widget_id, 'widget') ) ) {
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
				. '<a href="' . htmlspecialchars($feed_url) . '"'
					. ' title="' . __('RSS feed') . '" class="noicon">'
				. '<img src="'
					. htmlspecialchars($icons_url . '/feed-' . ( $text ? 'large' : 'giant' ) . '.gif') . '"'
					. ( $text
						? ' height="48" width="48"'
						: ' height="80" width="80"'
						)
					. ' alt="' . __('RSS feed', 'subscribe-me') . '"'
					. ' />'
				. '</a>'
				. '</div>' . "\n";
			
			if ( $text ) {
				echo '<div class="feed_text">' . "\n"
					. wpautop($text)
					. '</div>' . "\n";
			}
			
			echo '</div>' . "\n";

			echo '<div class="subscribe_me_spacer subscribe_me_ruler"></div>' . "\n";

			echo '<div class="subscribe_me_extra" style="display: none;">' . "\n";

			foreach ( subscribe_me::get_extra_services() as $service_id =>  $service ) {
				echo '<a href="' . htmlspecialchars($service['url'])  . '" class="' . $service_id . ' noicon"'
					. ' title="' . htmlspecialchars($service['name']) . '"'
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
					htmlspecialchars($feed_url),
					),
				$o);
			
			wp_cache_add($widget_id, $o, 'widget');
		}
		
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
	 * get_options()
	 *
	 * @return array $options
	 **/
	
	function get_options() {
		static $o;
		
		if ( isset($o) && !is_admin() )
			return $o;
		
		$o = get_option('subscribe_me');
		
		if ( $o === false ) {
			$o = subscribe_me::init_options();
		}
		
		return $o;
	} # get_options()
	
	
	/**
	 * init_options()
	 *
	 * @return array $options
	 **/

	function init_options() {
		if ( ( $o = get_option('subscribe_me_widgets') ) !== false ) {
			foreach ( $o as $k => $opt ) {
				if ( is_numeric($k) ) {
					$o[$k] = array('title' => $opt['title'], 'text' => '');
				}
			}
		} elseif ( ( $o = get_option('sem_subscribe_me_params') ) !== false ) {
			unset($o['before_widget']);
			unset($o['after_widget']);
			unset($o['before_title']);
			unset($o['after_title']);
			
			$o = array( 1 => $o );

			foreach ( array_keys( $sidebars = get_option('sidebars_widgets') ) as $k ) {
				if ( !is_array($sidebars[$k]) ) {
					continue;
				}

				if ( ( $key = array_search('subscribe-me', $sidebars[$k]) ) !== false ) {
					$sidebars[$k][$key] = 'subscribe_me-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				} elseif ( ( $key = array_search('Subscribe Me', $sidebars[$k]) ) !== false ) {
					$sidebars[$k][$key] = 'subscribe_me-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
			}
		} else {
			$o = array();
		}
		
		delete_option('sem_subscribe_me_services');
		delete_option('sem_subscribe_me_params');
		delete_option('subscribe_me_widgets');
		
		update_option('subscribe_me', $o);
		
		return $o;
	} # init_options()
	
	
	/**
	 * default_options()
	 *
	 * @return array $widget_options
	 **/

	function default_options() {
		return array(
			'title' => __('Syndicate', 'subscribe-me'),
			'text' => __('Subscribe to this site\'s RSS feed.', 'subscribe-me'),
			);
	} # default_options()
	
	
	/**
	 * new_widget()
	 *
	 * @param int $k arbitrary widget number
	 * @return string $widget_id
	 **/

	function new_widget($k = null) {
		$o = subscribe_me::get_options();
		
		if ( !( isset($k) && isset($o[$k]) ) ) {
			$k = time();
			while ( isset($o[$k]) ) $k++;
			$o[$k] = subscribe_me::default_options();
			
			update_option('subscribe_me', $o);
		}
		
		return 'subscribe_me-' . $k;
	} # new_widget()
	
	
	/**
	 * clear_cache()
	 *
	 * @return void
	 **/

	function clear_cache($in = null) {
		$o = subscribe_me::get_options();
		
		if ( !$o ) return $in;
		
		foreach ( array_keys($o) as $widget_id ) {
			wp_cache_delete($widget_id, 'widget');
		}
		
		return $in;
	} # clear_cache()
} # subscribe_me


/**
 * the_subscribe_links()
 *
 * @return void
 **/

function the_subscribe_links($args = null, $text = '') {
	if ( is_string($args) ) {
		$args = array('title' => $args, 'text' => $text);
	}
	
	$defaults = array(
		'before_widget' => '<div class="subscribe_me">' . "\n",
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		'title' => __('Syndicate', 'subscribe-me'),
		'text' => $text,
		);
	
	$args = array_merge($args, $defaults);
	
	subscribe_me::widget($args);
} # the_subscribe_links()


/**
 * subscribe_me_admin()
 *
 * @return void
 **/

function subscribe_me_admin() {
	include dirname(__FILE__) . '/sem-subscribe-me-admin.php';
} # subscribe_me_admin()

add_action('load-widgets.php', 'subscribe_me_admin');
?>