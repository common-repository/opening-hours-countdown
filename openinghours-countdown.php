<?php
/*
Plugin Name: Opening Hours Countdown
Plugin URI: 
Description: A plugin that countdown the hours until your shop/restaurant etc. opening and closing.
Version: 1.0
Author: Kosolapov Oleg
Author URI: 
License: GPLv2 or later
Text Domain: Opening Hours Countdown
*/

require_once 'widget.php';

class StoreCountdownPlugin {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action( 'admin_init', array($this, 'register_settings') );
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('widgets_init', array($this, 'widgets_init'));

        add_shortcode('openinghours_countdown', array($this, 'shortcode'));
    }

    function widgets_init()
    {
        register_widget('StoreCountdownWidget');
    }

    function init()
    {
        wp_enqueue_style('countdown', plugins_url('css/style.css', __FILE__));
        wp_enqueue_script('countdown', plugins_url('js/script.js', __FILE__));
    }

    static function get_clock_html($minutes)
    {
        $minutes = min($minutes, 23 * 60 + 59);
        $h = floor($minutes / 60);
        $m = $minutes % 60;

        return '<div class="clock">'.StoreCountdownPlugin::pad($h).':'.StoreCountdownPlugin::pad($m).'</div>';
    }

    static function pad($v)
    {
        while (strlen($v) < 2)
            $v = '0'.$v;
        return $v;
    }

    static $js_var_declared = false;

    static function get_html()
    {
        $html = '';
        $dtz = date_default_timezone_get();

        $open_time_h = unserialize(get_option('cd_open_time_h'));
        $open_time_m = unserialize(get_option('cd_open_time_m'));
        $close_time_h = unserialize(get_option('cd_close_time_h'));
        $close_time_m = unserialize(get_option('cd_close_time_m'));
        $is_open = unserialize(get_option('cd_is_open'));
        $tz = get_option('cd_timezone');
        date_default_timezone_set($tz);

        $weekday = intval(date('w'));
        if ($weekday == 0)
            $weekday = 7;
        $weekday--;

        $minutes = intval(date('H')) * 60 + intval(date('i'));

        for ($di = 0; $di < 8; $di++)
        {
            $wd = ($weekday + $di) % 7;
            if ($is_open[$wd] == 1)
            {
                $open_minutes = $di * 24 * 60 + intval($open_time_h[$wd]) * 60 + intval($open_time_m[$wd]);
                $close_minutes = $di * 24 * 60 + intval($close_time_h[$wd]) * 60 + intval($close_time_m[$wd]);
                if ($close_minutes > $minutes)
                    break;
            }

        }




        $html .= '<div class="countdown-wrap">';
        //$html .= '<div class="countdown-title">OPENING HOURS</div>';

        $left = -1;

        if ($minutes < $open_minutes)
        {
            $left = $open_minutes - $minutes;
            $html .= '<div class="countdown-msg">Time until we\'re opening</div>';
            $html .= StoreCountdownPlugin::get_clock_html($open_minutes - $minutes);

        } elseif ($minutes < $close_minutes) {
            $left = $close_minutes - $minutes;
            $html .= '<div class="countdown-msg">Time until we\'re closing</div>';
            $html .= StoreCountdownPlugin::get_clock_html($close_minutes - $minutes);
        }

        


        if (!StoreCountdownPlugin::$js_var_declared)
        {
            StoreCountdownPlugin::$js_var_declared = true;
            $html .= '<script type="text/javascript">';
            $html .= ' var countdown_minutes = '.$left.';';
            $html .= ' jQuery(document).ready(function() { setInterval(function() { console.log("tick"); if (countdown_minutes > 0) { countdown_minutes--; jQuery(".clock").html(countdownMinutesFormat(countdown_minutes)); } }, 60000); }); ';
            $html .= '</script>';
        }



        date_default_timezone_set($dtz);
        return $html;
    }

    function shortcode($atts)
    {
        $html = $this->get_html();
        return $html;
    }

    function register_settings()
    {
        register_setting( 'countdown-settings-group', 'cd_open_time_h' );
        register_setting( 'countdown-settings-group', 'cd_open_time_m' );
        register_setting( 'countdown-settings-group', 'cd_close_time_h' );
        register_setting( 'countdown-settings-group', 'cd_close_time_m' );
        register_setting( 'countdown-settings-group', 'cd_timezone' );
        register_setting( 'countdown-settings-group', 'cd_is_open' );
    }

    function admin_menu()
    {
        add_options_page('Countdown', 'Countdown', 'administrator', 'countdown', array($this, 'settings_page'));
        //add_submenu_page('options-general', 'Countdown', 'Countdown', 'administrator', 'countdown', array($this, 'settings_page'));
    }

    function settings_page()
    {
        $zones = timezone_identifiers_list();

        if (count($_POST))
        {
            update_option('cd_timezone', $_POST['cd_timezone']);
            update_option('cd_is_open', serialize($_POST['is_opened']));
            update_option('cd_open_time_h', serialize($_POST['cd_open_time_h']));
            update_option('cd_open_time_m', serialize($_POST['cd_open_time_m']));
            update_option('cd_close_time_h', serialize($_POST['cd_close_time_h']));
            update_option('cd_close_time_m', serialize($_POST['cd_close_time_m']));
        }

        $zn = get_option('cd_timezone');
        $open_time_h = unserialize(get_option('cd_open_time_h'));
        $open_time_m = unserialize(get_option('cd_open_time_m'));
        $close_time_h = unserialize(get_option('cd_close_time_h'));
        $close_time_m = unserialize(get_option('cd_close_time_m'));
        $is_open = unserialize(get_option('cd_is_open'));
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('.countdown-settings input[name*="time_h"]').blur(function() {
                    this.value = Math.min(this.value, 23);
                    this.value = Math.max(this.value, 0);
                });
                jQuery('.countdown-settings input[name*="time_m"]').blur(function() {
                    this.value = Math.min(this.value, 59);
                    this.value = Math.max(this.value, 0);
                });
            });
        </script>
        <div class="wrap countdown-settings">
            <h2>Opening Hours Countdown</h2>
            <form method="post" action="">
                <table>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                        <th>Sunday</th>
                    </tr>
                    <tr>
                        <th>Opening Time</th>
                        <?php for ($i = 0; $i < 7; $i++) { ?>
                        <td style="text-align: center; "><input type="checkbox" name="is_opened[<?=$i?>]" value="1" <?php if ($is_open[$i] == 1) echo 'checked="checked"'; ?> /></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <th style="text-align: left; ">
                            Opening Time
                        </th>
                        <?php for ($i = 0; $i < 7; $i++) { ?>
                        <td style="padding: 0 10px;"><input type="number" style="width:60px;" name="cd_open_time_h[]" min="0" max="23" value="<?=$open_time_h[$i]?>" /> : <input type="number" style="width:60px;" name="cd_open_time_m[]" min="0" max="59" value="<?=$open_time_m[$i]?>" /></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <th style="text-align: left">
                            Closing Time
                        </th>
                        <?php for ($i = 0; $i < 7; $i++) { ?>
                        <td style="padding: 0 10px;"><input type="number" style="width:60px;" name="cd_close_time_h[]" min="0" max="23" value="<?=$close_time_h[$i]?>" /> : <input type="number" style="width:60px;" name="cd_close_time_m[]" min="0" max="59" value="<?=$close_time_m[$i]?>" /></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <th style="text-align: left">Timezone</th>
                        <td colspan="7" style="padding: 0 10px;">
                            <select name="cd_timezone">
                                <?php
                                foreach ($zones as $zone) {
                                    echo '<option value="'.$zone.'"';
                                    if ($zone == $zn)
                                        echo ' selected="selected"';
                                    echo '>'.$zone.'</option>';
                                }

                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <button class="button button-primary" type="submit"><?=_e('Save Changes')?></button>
            </form>
        </div>
    <?php

    }
}

new StoreCountdownPlugin();