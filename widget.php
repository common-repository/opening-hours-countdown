<?php

class StoreCountdownWidget extends WP_Widget
{
    function __construct()
    {
        parent::__construct(false, 'Countdown');
    }

    function widget($args, $instance)
    {
        //echo '<aside class="widget widget_countdown">';
        echo $args['before_widget'];
        if (isset($instance['title']))
            echo $args['before_title'].$instance['title'].$args['after_title'];
        //var_dump($args, $instance);
        echo StoreCountdownPlugin::get_html();
        echo $args['after_widget'];
        //echo '</aside>';
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    function form($instance) {
        $title = esc_attr($instance['title']);
        ?>
         <p>
             <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
             <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
         </p>
        <?php
    }
}