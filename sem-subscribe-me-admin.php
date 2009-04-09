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
				
					wp_cache_delete($_widget_id, 'widget');
				}
			}

			foreach ( (array) $_POST['widget-subscribe-me'] as $num => $opt ) {
				$title = strip_tags(stripslashes($opt['title']));
				$text = stripslashes($opt['text']);
				
				if ( !current_user_can('unfiltered_html') ) {
					$text = stripslashes(wp_filter_post_kses($text));
				}
				
				$options[$num] = compact('title', 'text');
			}
			
			update_option('subscribe_me', $options);
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
		$text = format_to_edit($text);

		echo '<input type="hidden"'
				. ' id="sem_subscribe_me_widget_update"'
				. ' name="sem_subscribe_me_widget_update"'
				. ' value="1"'
				. ' />'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. __('Title:')
				. '&nbsp;'
				. '<input class="widefat"'
					. ' name="widget-subscribe-me[' . $number. '][title]"'
					. ' type="text" value="' . $title . '" />'
				. '</label>'
				. '</div>'
			. '<div style="margin-bottom: .2em;">'
			. '<label>'
				. __('Text:') . '<br />'
				. '<textarea class="widefat" cols="20" rows="6"'
					. ' name="widget-subscribe-me[' . $number. '][text]"'
					. ' >'
					. $text
				. '</textarea>'
				. '</label>'
				. '</div>'
			;

		echo $o;
	} # end widget_control()
} # subscribe_me_admin
?>