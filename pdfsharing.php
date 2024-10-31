<?php
/*
Plugin Name: pdfsharing
Plugin URI: http://pdfsharing.com
Description: With this plugin you can use the service from pdfsharing.com in your site. Please lookup pdfsharing.com for more informations.
Author: Andreas van Loock, pdfsharing
Version: 1.0
Author URI: http://pdfsharing.com

Demo-Template:

<aside id="text-5" class="widget widget_text">
    <h3 class="widget-title"><span><!--TITLE--></span></h3>
    <div style="float:left;width: 20%;">
        <a href="<!--LINK-->" target="_blank">
            <img src="<!--IMAGE-->" border="0" width="85"/>
        </a>
    </div>
    <div style="float:left;width: 75%;margin-left: 5%;" class="textwidget">
        <a href="<!--LINK-->" target="_blank">
            <!--DESCRIPTION-->
        </a>
    </div>
</aside>
*/

/**
 * Init the widget
 */
add_action(
    'widgets_init',
    'pdfsharing_widgets_init'
);

/**
 * Function to register the widgets.
 */
function pdfsharing_widgets_init() {
    register_widget('pdfsharing_widget');
	register_widget('pdfsharing_list_widget');
}

/**
 * Class pdfsharing_widget
 */
class pdfsharing_widget extends WP_Widget {

    //
    // Use const for name pdfsharing
    //
    const PDF_SHARING = 'pdfsharing';

    /**
     * Constructor
     */
    function __construct() {
        $widget_ops = array(
            'classname' => self::PDF_SHARING,
            'description' => __(
                'Display PDF-Preview/Download created by pdfsharing.com',
                'pdfsharing: item'
            )
        );

        $control_ops = array(
            'width' => 400,
            'height' => 400
        );

		parent::__construct(
			false,
			$name = self::PDF_SHARING.': item',
			$widget_ops,
			$control_ops
		);
    }

    /**
     * Show the widget
     *
     * @param $args
     * @param $instance
     */
    function widget($args, $instance) {
        extract($args);

        //
        // Get content from cache by widget id
        //
        $transient = get_transient($this->id);

        //
        // If widget was cached output them
        //
        if(!empty($transient)) {
            echo $transient;
        }
        //
        // Create output and cache them
        //
        else {
            $url = apply_filters(
                'widget_url',
                empty($instance['url']) ? '' : $instance['url'],
                $instance,
                $this->id_base
            );

            $template = apply_filters(
                'widget_template',
                empty($instance['template']) ? '' : $instance['template'],
                $instance
            );

            $word_count = apply_filters(
                'widget_word_count',
                empty($instance['word_count']) ? '' : $instance['word_count'],
                $instance
            );

            $text_after_word_count = apply_filters(
                'widget_text_after_word_count',
                empty($instance['text_after_word_count']) ? '' : $instance['text_after_word_count'],
                $instance
            );

            $share_informations = file_get_contents(
                $url,
                false,
                stream_context_create(
                    array(
                        'http'=>array(
                            'user_agent' => 'pdfsharing-wordpress-plugin 1.0',
                            'method' => 'GET'
                        )
                    )
                )
            );

            //
            // Match metadata from pdfsharing
            //
            preg_match_all(
                "|<meta[^>]+property=\"([^\"]*)\"[^>]"."+content=\"([^\"]*)\"[^>]+>|i",
                $share_informations,
                $meta_tags,
                PREG_PATTERN_ORDER
            );

            //
            // Create normally map/array
            //
            foreach($meta_tags[1] as $key => $value) {
                $tags[$value] = $meta_tags[2][$key];
            }

            //
            // Trim the text if need...
            //
            if ($word_count > 0) {
                $tags['og:description'] = preg_replace('/[^ ]*$/', '', substr($tags['og:description'], 0, $word_count));
                $tags['og:description'] .= $text_after_word_count;
            }

            //
            // Replace the content in the template
            //
            $template = preg_replace('/<!--LINK-->/', $tags['og:url'], $template);
            $template = preg_replace('/<!--IMAGE-->/', $tags['og:image'], $template);
            $template = preg_replace('/<!--TITLE-->/', utf8_encode($tags['og:title']), $template);
            $template = preg_replace('/<!--DESCRIPTION-->/', utf8_encode($tags['og:description']), $template);

            $out = '';
            $out .= $template;

            //
            // Cache the output
            //
            set_transient(
                $this->id,
                $out,
                DAY_IN_SECONDS
            );

            echo $out;
        }
    }

    /**
     * Update the widget-content
     *
     * @param $new_instance
     * @param $old_instance
     * @return mixed
     */
    function update(
        $new_instance,
        $old_instance
    ) {

        $instance = $old_instance;
        $instance['url'] = strip_tags($new_instance['url']);
        $instance['word_count'] = strip_tags($new_instance['word_count']);
        $instance['text_after_word_count'] = strip_tags($new_instance['text_after_word_count']);
        $instance['template'] = $new_instance['template'];

        //
        // Delete content from cache by widget id
        //
        delete_transient($this->id);

        return $instance;
    }

    /**
     * Create the widgetform
     *
     * @param $instance
     */
    function form(
        $instance
    ) {

        $instance = wp_parse_args(
            (array) $instance,
            array(
                'url' => '',
                'word_count' => 25,
                'text_after_word_count' => '...',
                'template' => '
                    <aside id="text-5" class="widget widget_text">
                        <h3 class="widget-title"><span><!--TITLE--></span></h3>
                        <div style="float:left;width: 20%;">
                            <a href="<!--LINK-->" target="_blank">
                                <img src="<!--IMAGE-->" border="0" width="85"/>
                            </a>
                        </div>
                        <div style="float:left;width: 75%;margin-left: 5%;" class="textwidget">
                            <a href="<!--LINK-->" target="_blank">
                                <!--DESCRIPTION-->
                            </a>
                        </div>
                    </aside>'
            )
        );

        $url = strip_tags($instance['url']);
        $word_count = strip_tags($instance['word_count']);
        $text_after_word_count = strip_tags($instance['text_after_word_count']);
        $template = $instance['template'];

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('url'); ?>">URL</label>
            <input class="widefat" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>" type="text" value="<?php echo esc_attr($url); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('word_count'); ?>">Anzahl Zeichen für die Beschreibung</label>
            <input class="widefat" id="<?php echo $this->get_field_id('word_count'); ?>" name="<?php echo $this->get_field_name('word_count'); ?>" type="text" value="<?php echo esc_attr($word_count); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('text_after_word_count'); ?>">Zeichenfolge für nach dem Text (z.B. ...)</label>
            <input class="widefat" id="<?php echo $this->get_field_id('text_after_word_count'); ?>" name="<?php echo $this->get_field_name('text_after_word_count'); ?>" type="text" value="<?php echo esc_attr($text_after_word_count); ?>" />
        </p>
        Template
        <textarea class="widefat" rows="12" cols="20" id="<?php echo $this->get_field_id('template'); ?>" name="<?php echo $this->get_field_name('template'); ?>"><?php echo htmlentities($template); ?></textarea>
    <?php
    }
}

/**
 * Class pdfsharing_widget
 */
class pdfsharing_list_widget extends WP_Widget {

	//
	// Use const for name pdfsharing
	//
	const PDF_SHARING = 'pdfsharing';

	/**
	 * Constructor
	 */
	function __construct() {
		$widget_ops = array(
			'classname' => self::PDF_SHARING,
			'description' => __(
				'Display PDF-Preview/Download as list created by pdfsharing.com',
				'pdfsharing'
			)
		);

		$control_ops = array(
			'width' => 400,
			'height' => 400
		);

		parent::__construct(
			false,
			$name = self::PDF_SHARING.': list',
			$widget_ops,
			$control_ops
		);
	}

	/**
	 * Show the widget
	 *
	 * @param $args
	 * @param $instance
	 */
	function widget($args, $instance) {
		extract($args);

		//
		// Get content from cache by widget id
		//
		$transient = get_transient($this->id);

		//
		// If widget was cached output them
		//
		if(!empty($transient)) {
			echo $transient;
		}
		//
		// Create output and cache them
		//
		else {
			$url_list = apply_filters(
				'widget_url',
				empty($instance['url']) ? '' : $instance['url'],
				$instance,
				$this->id_base
			);

			$template = apply_filters(
				'widget_template',
				empty($instance['template']) ? '' : $instance['template'],
				$instance
			);

			$word_count = apply_filters(
				'widget_word_count',
				empty($instance['word_count']) ? '' : $instance['word_count'],
				$instance
			);

			$text_after_word_count = apply_filters(
				'widget_text_after_word_count',
				empty($instance['text_after_word_count']) ? '' : $instance['text_after_word_count'],
				$instance
			);

			$url_list = explode("\n", $url_list);
			$out = $template_tmp = '';
			foreach ($url_list as $url) {

				$template_tmp = $template;

				$share_informations = file_get_contents(
					trim($url),
					false,
					stream_context_create(
						array(
							'http'=>array(
								'user_agent' => 'pdfsharing-wordpress-plugin 1.0',
								'method' => 'GET'
							)
						)
					)
				);

				//
				// Match metadata from pdfsharing
				//
				preg_match_all(
					"|<meta[^>]+property=\"([^\"]*)\"[^>]"."+content=\"([^\"]*)\"[^>]+>|i",
					$share_informations,
					$meta_tags,
					PREG_PATTERN_ORDER
				);

				//
				// Create normally map/array
				//
				foreach($meta_tags[1] as $key => $value) {
					$tags[$value] = $meta_tags[2][$key];
				}

				//
				// Trim the text if need...
				//
				if ($word_count > 0) {
					$tags['og:description'] = preg_replace('/[^ ]*$/', '', substr($tags['og:description'], 0, $word_count));
					$tags['og:description'] .= $text_after_word_count;
				}

				//
				// Replace the content in the template
				//
				$template_tmp = preg_replace('/<!--LINK-->/', $tags['og:url'], $template_tmp);
				$template_tmp = preg_replace('/<!--IMAGE-->/', $tags['og:image'], $template_tmp);
				$template_tmp = preg_replace('/<!--TITLE-->/', utf8_encode($tags['og:title']), $template_tmp);
				$template_tmp = preg_replace('/<!--DESCRIPTION-->/', utf8_encode($tags['og:description']), $template_tmp);

				$out .= $template_tmp;
			}

			//
			// Cache the output
			//
			set_transient(
				$this->id,
				$out,
				DAY_IN_SECONDS
			);

			echo $out;
		}
	}

	/**
	 * Update the widget-content
	 *
	 * @param $new_instance
	 * @param $old_instance
	 * @return mixed
	 */
	function update(
		$new_instance,
		$old_instance
	) {

		$instance = $old_instance;
		$instance['url'] = strip_tags($new_instance['url']);
		$instance['word_count'] = strip_tags($new_instance['word_count']);
		$instance['text_after_word_count'] = strip_tags($new_instance['text_after_word_count']);
		$instance['template'] = $new_instance['template'];

		//
		// Delete content from cache by widget id
		//
		delete_transient($this->id);

		return $instance;
	}

	/**
	 * Create the widgetform
	 *
	 * @param $instance
	 */
	function form(
		$instance
	) {

		$instance = wp_parse_args(
			(array) $instance,
			array(
				'url' => '',
				'word_count' => 25,
				'text_after_word_count' => '...',
				'template' => '
                    <aside id="text-5" class="widget widget_text">
                        <h3 class="widget-title"><span><!--TITLE--></span></h3>
                        <div style="float:left;width: 20%;">
                            <a href="<!--LINK-->" target="_blank">
                                <img src="<!--IMAGE-->" border="0" width="85"/>
                            </a>
                        </div>
                        <div style="float:left;width: 75%;margin-left: 5%;" class="textwidget">
                            <a href="<!--LINK-->" target="_blank">
                                <!--DESCRIPTION-->
                            </a>
                        </div>
                    </aside>'
			)
		);

		$url = strip_tags($instance['url']);
		$word_count = strip_tags($instance['word_count']);
		$text_after_word_count = strip_tags($instance['text_after_word_count']);
		$template = $instance['template'];

		?>
		<p>
			<label for="<?php echo $this->get_field_id('url'); ?>">URL-List</label>
			<textarea class="widefat" rows="12" cols="20" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>"><?php echo esc_attr($url); ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('word_count'); ?>">Anzahl Zeichen für die Beschreibung</label>
			<input class="widefat" id="<?php echo $this->get_field_id('word_count'); ?>" name="<?php echo $this->get_field_name('word_count'); ?>" type="text" value="<?php echo esc_attr($word_count); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('text_after_word_count'); ?>">Zeichenfolge für nach dem Text (z.B. ...)</label>
			<input class="widefat" id="<?php echo $this->get_field_id('text_after_word_count'); ?>" name="<?php echo $this->get_field_name('text_after_word_count'); ?>" type="text" value="<?php echo esc_attr($text_after_word_count); ?>" />
		</p>
		Template
		<textarea class="widefat" rows="12" cols="20" id="<?php echo $this->get_field_id('template'); ?>" name="<?php echo $this->get_field_name('template'); ?>"><?php echo htmlentities($template); ?></textarea>
	<?php
	}
}