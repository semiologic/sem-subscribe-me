<?php
/*
Plugin Name: Subscribe Me
Plugin URI: http://www.semiologic.com/software/widgets/subscribe-me/
Description: Adds widgets that let you display feed subscription buttons.
Author: Denis de Bernardy
Version: 4.3.2 RC
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php


Hat tips
--------

	* James Huff <http://www.macmanx.com>
	* Duke Thor <http://blog.dukethor.info>
	* Mike Koepke <http://www.mikekoepke.com>
**/


load_plugin_textdomain('sem-subscribe-me');

class subscribe_me
{
	#
	# init()
	#
	
	function init()
	{
		add_action('wp_head', array('subscribe_me', 'css'));
		if ( !is_admin() )
		{
			add_action('wp_print_scripts', array('subscribe_me', 'js'));
		}
		
		add_action('widgets_init', array('subscribe_me', 'widgetize'));
		
		foreach ( array(
				'generate_rewrite_rules',
				'switch_theme',
				'update_option_active_plugins',
				'update_option_show_on_front',
				'update_option_page_on_front',
				'update_option_page_for_posts',
				) as $hook)
		{
			add_action($hook, array('subscribe_me', 'clear_cache'));
		}
		
		register_activation_hook(__FILE__, array('subscribe_me', 'clear_cache'));
		register_deactivation_hook(__FILE__, array('subscribe_me', 'clear_cache'));
	} # init()
	
	
	#
	# get_services()
	#

	function get_services()
	{
		return array(
			'local_feed' => array(
				'name' => __('RSS Feed'),
				'button' => 'rss.png',
				'url' => apply_filters('bloginfo', get_feed_link('rss2'), 'rss2_url'),
				),
			'google' => array(
				'name' => 'Google',
				'button' => 'addgoogle.gif',
				'url' => 'http://fusion.google.com/add?feedurl=%feed_url%',
				),
			'msn' => array(
				'name' => 'MyMSN',
				'button' => 'addmymsn.gif',
				'url' => 'http://my.msn.com/addtomymsn.armx?id=rss&amp;ut=%feed_url%&amp;ru=%site_url%',
				),
			'yahoo' => array(
				'name' => 'MyYahoo!',
				'button' => 'addmyyahoo.gif',
				'url' => 'http://add.my.yahoo.com/rss?url=%feed_url%',
				),
			'aol' => array(
				'name' => 'MyAOL',
				'button' => 'addmyaol.gif',
				'url' => 'http://feeds.my.aol.com/add.jsp?url=%feed_url%',
				),
			'bloglines' => array(
				'name' => 'Bloglines',
				'button' => 'addbloglines.gif',
				'url' => 'http://www.bloglines.com/sub/%feed_url%',
				),
			'live' => array(
				'name' => 'Windows Live',
				'button' => 'addwindowslive.gif',
				'url' => 'http://www.live.com/?add=%feed_url%',
				),
			'netvibes' => array(
				'name' => 'Netvibes',
				'button' => 'addnetvibes.gif',
				'url' => 'http://www.netvibes.com/subscribe.php?url=%feed_url%',
				),
			'newsgator' => array(
				'name' => 'Newsgator',
				'button' => 'addnewsgator.gif',
				'url' => 'http://www.newsgator.com/ngs/subscriber/subext.aspx?url=%feed_url%',
				),
			'pageflakes' => array(
				'name' => 'Pageflakes',
				'button' => 'addpageflakes.gif',
				'url' => 'http://www.pageflakes.com/subscribe.aspx?url=%feed_url%',
				),
			'help_link' => array(
				'name' => __('Help'),
				'button' =>'help.gif',
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-feeds/'
				),
			);
	} # get_services()


	#
	# default_services()
	#

	function default_services()
	{
		return array(
			'local_feed',
			'bloglines',
			'help_link'
			);
	} # default_services()


	#
	# get_service()
	#

	function get_service($key)
	{
		$services = subscribe_me::get_services();

		return $services[$key];
	} # get_service()


	#
	# display()
	#

	function display($args = null)
	{
		# default args

		$defaults = array(
			'title' => __('Syndicate'),
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2>',
			'after_title' => '</h2>'
			);

		$default_options = subscribe_me::default_options();

		$args = array_merge($defaults, (array) $default_options, (array) $args);

		$args['site_path'] = trailingslashit(get_option('siteurl'));
		$args['feed_url'] = apply_filters('bloginfo', get_feed_link('rss2'), 'rss2_url');
		$args['img_path'] = trailingslashit(get_option('siteurl')) . 'wp-content/plugins/sem-subscribe-me/img/';

		$hash = md5(uniqid(rand()));

		$cache_id = md5(serialize($args));
		
		$cache = get_option('subscribe_me_cache');

		# return cache if relevant

		if ( $o = $cache[$cache_id] )
		{
			$o = str_replace('{$hash}', $hash, $o);

			return $o;
		}


		# process output

		$as_dropdown = intval($args['dropdown']);
		$home_url = get_option('home');
		$o = '';

		$o .= $args['before_widget'] . "\n"
			. ( $args['title']
				? ( $args['before_title'] . $args['title'] . $args['after_title'] . "\n" )
				: ''
				);

		$o .= '<div'
				. ( $as_dropdown
					? ( ' onmouseover="fade_subscribe_buttons_in(\'subscribe_me_{$hash}\');"'
						. ' onmouseout="fade_subscribe_buttons_out(\'subscribe_me_{$hash}\');"'
						)
					: ''
					)
				. '>' . "\n";

		if ( $as_dropdown )
		{
			$o .= '<div class="subscribe_service">'
				. '<a href="'
					. $args['feed_url']
					. '">'
				. '<img'
					. ' src="' . $args['img_path'] . 'subscribe.gif"'
					. ' alt="' . __('RSS Feed') . '"'
					. ' />'
				. '</a>'
				. '</div>' . "\n";
		}

		$o .= '<div'
			. ' class="subscribe_services' . ( $as_dropdown ? ' subscribe_dropdown' : '' ) . '"'
			. ' id="subscribe_me_{$hash}"'
			. '>';

		if ( $as_dropdown ) $o .= '<div style="clear: both;"></div>' . "\n";

		foreach ( (array) $args['services'] as $service )
		{
			$details = subscribe_me::get_service($service);

			if ( $details )
			{
				switch( $service )
				{
				case 'local_feed':
				case 'help_link':
					$o .= '<div class="subscribe_service">'
						. '<a'
							. ' href="' . $details['url'] . '"'
							. ' style="background: url('
								. $args['img_path'] . $details['button']
								. ')'
								. ' center left no-repeat;'
								. ' padding: 2px 0px 2px 18px;"'
							. ( ( $args['add_nofollow'] && $service != 'local_feed'
									&& strpos($details['url'], $home_url) !== 0 )
								? ' rel="nofollow"'
								: ''
								)
							. '>'
						. $details['name']
						. '</a>'
						. '</div>' . "\n";
					break;
				default:
					$o .= '<div class="subscribe_service">'
						. '<a'
							. ' href="'
								. str_replace(
									'%site_url%',
									urlencode($args['site_path']),
									str_replace(
										'%feed_url%',
										( strpos($details['url'], '?') !== false
											? urlencode($args['feed_url'])
											: $args['feed_url']
											),
										$details['url']
										)
									) . '"'
							. ( $args['add_nofollow']
								? ' rel="nofollow"'
								: ''
								)
							. '>'
						. '<img'
							. ' src="' . $args['img_path'] . $details['button'] . '"'
							. ' alt="' . str_replace('%feed%', $details['name'], __('Subscribe to %feed%')) . '"'
							. ' />'
						. '</a>'
						. '</div>' . "\n";
					break;
				}
			}
		}

		if ( $as_dropdown ) $o .= '<div style="clear: both;"></div>' . "\n";

		$o .= '</div>' . "\n";

		$o .= '</div>' . "\n"
			. $args['after_widget'] . "\n";


		# store output

		$cache[$cache_id] = $o;
	
		update_option('subscribe_me_cache', $cache);


		# return output

		$o = str_replace('{$hash}', $hash, $o);

		return $o;
	} # display()


	#
	# css()
	#

	function css()
	{
		echo '<link rel="stylesheet" type="text/css"'
			. ' href="'
				. trailingslashit(get_option('siteurl'))
				. 'wp-content/plugins/sem-subscribe-me/sem-subscribe-me.css?ver=4.2'
				. '"'
			. ' />' . "\n";
	} # css()


	#
	# js()
	#

	function js()
	{
		$plugin_path = plugin_basename(__FILE__);
		$plugin_path = preg_replace("/[^\/]+$/", '', $plugin_path);
		$plugin_path = '/wp-content/plugins/' . $plugin_path;
		
		wp_enqueue_script( 'subscribe_me', $plugin_path . 'sem-subscribe-me.js', false, '20080416' );
	} # js()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = subscribe_me::get_options();

		$widget_options = array('classname' => 'subscribe_me', 'description' => __( "Feed subscription buttons") );
		$control_options = array('width' => 300, 'id_base' => 'subscribe_me');

		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "subscribe_me-$o";
			wp_register_sidebar_widget($id, __('Subscribe Me'), array('subscribe_me', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Subscribe Me'), array('subscribe_me_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}

		# default widget if none were registered
		if ( !$id )
		{
			$id = "subscribe_me-1";
			wp_register_sidebar_widget($id, __('Subscribe Me'), array('subscribe_me', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Subscribe Me'), array('subscribe_me_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()


	#
	# display_widget()
	#

	function display_widget($args, $widget_args = 1)
	{
		$options = subscribe_me::get_options();
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$args = array_merge((array) $options[$number], (array) $args);
		
		if ( is_admin() )
		{
			echo $args['before_widget']
				. $args['before_title']
				. $args['title']
				. $args['after_title']
				. $args['after_widget'];
			
			return;
		}
		
		echo subscribe_me::display($args);
	} # display_widget()


	#
	# get_options()
	#

	function get_options()
	{
		if ( ( $o = get_option('subscribe_me_widgets') ) === false )
		{
			if ( ( $o = get_option('sem_subscribe_me_params') ) !== false )
			{
				unset($o['before_widget']);
				unset($o['after_widget']);
				unset($o['before_title']);
				unset($o['after_title']);

				$o['services'] = get_option('sem_subscribe_me_services');

				if ( !$o['services'] )
				{
					$defaults = subscribe_me::default_options();
					$o['services'] = $defaults['services'];
				}
				
				$o = array( 1 => $o );

				foreach ( array_keys( $sidebars = get_option('sidebars_widgets') ) as $k )
				{
					if ( !is_array($sidebars[$k]) )
					{
						continue;
					}

					if ( ( $key = array_search('subscribe-me', $sidebars[$k]) ) !== false )
					{
						$sidebars[$k][$key] = 'subscribe_me-1';
						update_option('sidebars_widgets', $sidebars);
						break;
					}
					elseif ( ( $key = array_search('Subscribe Me', $sidebars[$k]) ) !== false )
					{
						$sidebars[$k][$key] = 'subscribe_me-1';
						update_option('sidebars_widgets', $sidebars);
						break;
					}
				}
			}
			else
			{
				$o = array();
			}

			update_option('subscribe_me_widgets', $o);
		}

		return $o;
	} # get_options()
	
	
	#
	# new_widget()
	#
	
	function new_widget()
	{
		$o = subscribe_me::get_options();
		$k = time();
		do $k++; while ( isset($o[$k]) );
		$o[$k] = subscribe_me::default_options();
		
		update_option('subscribe_me_widgets', $o);
		
		return 'subscribe_me-' . $k;
	} # new_widget()


	#
	# default_options()
	#

	function default_options()
	{
		return array(
			'title' => __('Syndicate'),
			'dropdown' => false,
			'add_nofollow' => false,
			'services' => array(
				'local_feed',
				'bloglines',
				'help_link'
				),
			);
	} # default_options()


	#
	# clear_cache()
	#

	function clear_cache($in = null)
	{
		update_option('subscribe_me_cache', array());
		
		return $in;
	} # clear_cache()
} # subscribe_me

subscribe_me::init();


#
# the_subscribe_links()
#

function the_subscribe_links()
{
	echo 'Obsolete call to the_subscribe_links() detected';
} # the_subscribe_links()


if ( is_admin() )
{
	include dirname(__FILE__) . '/sem-subscribe-me-admin.php';
}
?>