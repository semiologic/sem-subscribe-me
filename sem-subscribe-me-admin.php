<?php

class subscribe_me_admin
{
	#
	# widget_control()
	#

	function widget_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number

		$options = subscribe_me::get_options();

		if ( !$updated && !empty($_POST['sidebar']) )
		{
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id )
			{
				if ( array('subscribe_me', 'display_widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
					)
				{
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "subscribe_me-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				
					subscribe_me::clear_cache();
				}
			}

			foreach ( (array) $_POST['widget-subscribe-me'] as $num => $opt ) {
				$title = stripslashes(wp_filter_post_kses(strip_tags($opt['title'])));
				$dropdown = isset($opt['dropdown']);
				$add_nofollow = isset($opt['add_nofollow']);
				
				$services = (array) $opt['services'];
				$services = array_map('strip_tags', $services);
				$services = array_map('stripslashes', $services);
				
				$options[$num] = compact( 'title', 'dropdown', 'add_nofollow', 'services' );
			}
			
			update_option('subscribe_me_widgets', $options);
			$updated = true;
		}

		if ( -1 == $number )
		{
			$ops = subscribe_me::default_options();
			$number = '%i%';
		}
		else
		{
			$ops = $options[$number];
		}
		
		extract($ops);
		
		
		$title = attribute_escape($title);


		echo '<input type="hidden"'
				. ' id="sem_subscribe_me_widget_update"'
				. ' name="sem_subscribe_me_widget_update"'
				. ' value="1"'
				. ' />'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. __('Title:')
				. '&nbsp;'
				. '<input style="width: 250px;"'
					. ' name="widget-subscribe-me[' . $number. '][title]"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. '<input'
					. ' name="widget-subscribe-me[' . $number. '][dropdown]"'
					. ( intval($dropdown)
						? ' checked="checked"'
						: ''
						)
					. ' type="checkbox" value="1" />'
				. '&nbsp;'
				. __('Show as a drop down button')
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. '<input'
					. ' name="widget-subscribe-me[' . $number. '][add_nofollow]"'
					. ( intval($add_nofollow)
						? ' checked="checked"'
						: ''
						)
					. ' type="checkbox" value="1" />'
				. '&nbsp;'
				. __('Add nofollow')
				. '</label>'
				. '</div>'
			;


		$args['site_path'] = trailingslashit(site_url());
		$args['img_path'] = trailingslashit(site_url()) . 'wp-content/plugins/sem-subscribe-me/img/';

		$o .= '<div style="width: 280px;">';

		foreach ( array_keys((array) subscribe_me::get_services()) as $service )
		{
			$details = subscribe_me::get_service($service);

			if ( $details )
			{
				switch( $service )
				{
				case 'local_feed':
				case 'help_link':
					$o .= '<div class="subscribe_service"'
						. ' style="float: left;'
							. ' margin: 2px 5px;'
							. ' width: 130px; height: 20px;'
							. '"'
						. '>'
						. '<label>'
						. '<input type="checkbox"'
							. ' name="widget-subscribe-me[' . $number. '][services][]"'
							. ' value="' . $service . '"'
							. ( in_array($service, (array) $services)
								? ' checked="checked"'
								: ''
								)
							. ' />'
						. '&nbsp;'
						. '<span style="background: url('
								. $args['img_path'] . $details['button']
								. ')'
								. ' center left no-repeat;'
								. ' padding-left: 18px;'
								. ' color: blue;'
								. ' text-decoration: underline;'
								. '"'
								. '>'
						. $details['name']
						. '</span>'
						. '</label>'
						. '</div>' . "\n";
					break;

				default:
					$o .= '<div class="subscribe_service"'
						. ' style="float: left;'
							. ' margin: 2px 5px;'
							. ' width: 130px; height: 20px;'
							. '"'
						. '>'
						. '<label>'
						. '<input type="checkbox"'
							. ' name="widget-subscribe-me[' . $number. '][services][]"'
							. ' value="' . $service . '"'
							. ( in_array($service, (array) $services)
								? ' checked="checked"'
								: ''
								)
							. ' />'
						. '&nbsp;'
						. '<img'
							. ' src="' . $args['img_path'] . $details['button'] . '"'
							. ' alt="' . str_replace('%feed%', $details['name'], __('Subscribe to %feed%')) . '"'
							. ' align="middle"'
							. ' />'
						. '</label>'
						. '</div>' . "\n";
					break;
				}
			}
		}

		$o .= '<div style="clear: both;"></div>'
			. '</div>'. "\n";

		echo $o;
	} # end widget_control()
} # subscribe_me_admin
?>