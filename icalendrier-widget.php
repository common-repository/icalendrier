<?php
/*  Copyright 2014-2020 Baptiste Placé

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * Plugin Name: iCalendrier
 * Plugin URI: https://icalendrier.fr/widget/wordpress-plugin
 * Description: Un simple calendrier qui affiche des infos du jour, comme le numéro de semaine, la date, la fête du jour et la phase de lune.
 * Version: 1.83
 * Author: Baptiste Placé
 * Author URI: https://icalendrier.fr/
 * License: GNU General Public License, version 2
 */

defined('ABSPATH') or die("No script kiddies please!");

// Include lib
require_once(dirname(__FILE__) . '/lib/Icalendrier.php');

// Add localized strings
load_plugin_textdomain('icalendrier', false, basename(dirname(__FILE__)) . '/languages');

// Enqueue CSS
function icalendrier_wp_head()
{
    wp_register_style('icalendrier-default', plugins_url('/css/icalendrier.css', __FILE__), '', null, 'all');
    wp_register_style('icalendrier-alt-1', plugins_url('/css/themes/icalendrier-alt-1.css', __FILE__), array(), null, 'all');
    wp_enqueue_style('icalendrier-default');
    wp_enqueue_style('icalendrier-alt-1');
}

function icalendrier_wp_admin_head()
{
    wp_enqueue_style('icalendrier', plugins_url('/css/icalendrier-admin.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'icalendrier_wp_head');

add_action('admin_enqueue_scripts', 'icalendrier_wp_admin_head');


/**
 * Parse attributes + set theme or default CSS
 *
 * @param $atts
 *
 * @return array
 */
function getAttibutes($atts)
{
    $calType     = isset($atts['type']) ? $atts['type'] : 'comp175';
    $language    = isset($atts['language']) ? $atts['language'] : 'en';
    $timezone    = isset($atts['timezone']) ? $atts['timezone'] : 0;
    $showLink    = ((isset($atts['showLink']) and $atts['showLink'] == 'on') or ! isset($atts['showLink'])) ? 1 : 0;
    $bgColor     = isset($atts['bgColor']) ? $atts['bgColor'] : false;
    $style       = isset($atts['style']) ? $atts['style'] : 'default';
    $widgetTitle = isset($atts['widgetTitle']) ? $atts['widgetTitle'] : '';

    return [
        'calType'     => $calType,
        'language'    => $language,
        'timezone'    => $timezone,
        'showLink'    => $showLink,
        'bgColor'     => $bgColor,
        'style'       => $style,
        'widgetTitle' => $widgetTitle
    ];
}


// Shortcode
add_shortcode('icalendrier', 'icalendrier_shortcode');

// Not needed
//add_filter( 'no_texturize_shortcodes', 'icalendrier' );


function icalendrier_shortcode($atts)
{
    $attributes = getAttibutes($atts);

    $iCalendrier = new Icalendrier($attributes['language'], $attributes['timezone']);

    if ('comp175' === $attributes['calType']) {
        return $iCalendrier->iCalendrierComp($attributes);
    } elseif ('wide300' === $attributes['calType']) {
        return $iCalendrier->iCalendrierWide($attributes);
    }
}


/**
 * Class ICalendrier
 */
class ICalendrierWidget extends WP_Widget
{

    /**
     * ICalendrierWidget constructor.
     */
    public function __construct()
    {
        parent::__construct('icalendrier_widget', 'iCalendrier Widget', [
            'classname'   => 'calendar',
            'description' => 'Simple daily calendar'
        ]);
    }

    /**
     * @param $args
     * @param $instance
     */
    public function widget($args, $instance)
    {
        /**
         * @var $before_widget
         * @var $widgetTitle
         * @var $before_title
         * @var $after_title
         * @var $after_widget
         */
        extract($args);

        $attributes = getAttibutes($instance);

        if (empty($widgetTitle)) {
            $widgetTitle = isset($attributes['widgetTitle']) ? $attributes['widgetTitle'] : '';
        }

        echo $before_widget;

        if (isset($widgetTitle) && $widgetTitle != "") {
            if ( ! isset($before_title)) {
                $before_title = "";
            }
            if ( ! isset($after_title)) {
                $after_title = "";
            }
            echo $before_title . $widgetTitle . $after_title;
        }

        $iCalendrier = new Icalendrier($attributes['language'], $attributes['timezone']);

        if ('comp175' === $attributes['calType']) {
            echo $iCalendrier->iCalendrierComp($attributes);
        } elseif ('wide300' === $attributes['calType']) {
            echo $iCalendrier->iCalendrierWide($attributes);
        }

        echo $after_widget;
    }

    /**
     * @param $new_instance
     * @param $old_instance
     *
     * @return mixed
     */
    public function update($new_instance, $old_instance)
    {
        $instance                = $old_instance;
        $instance['type']        = wp_strip_all_tags($new_instance['type']);
        $instance['language']    = wp_strip_all_tags($new_instance['language']);
        $instance['timezone']    = wp_strip_all_tags($new_instance['timezone']);
        $instance['widgetTitle'] = wp_strip_all_tags($new_instance['widgetTitle']);
        $instance['bgColor']     = wp_strip_all_tags($new_instance['bgColor']);
        $instance['showLink']    = ((isset($instance['showLink']) and $instance['showLink'] == 'on') or ! isset($instance['showLink'])) ? 1 : 0;
        $instance['style']       = wp_strip_all_tags($new_instance['style']);

        return $instance;
    }

    /**
     * @param $instance
     */
    public function form($instance)
    {
        $type        = isset($instance['type']) ? esc_attr($instance['type']) : "comp175";
        $language    = isset($instance['language']) ? esc_attr($instance['language']) : "en";
        $timezone    = isset($instance['timezone']) ? esc_attr($instance['timezone']) : 5;
        $widgetTitle = isset($instance['widgetTitle']) ? esc_attr($instance['widgetTitle']) : "";
        $showLink    = ((isset($instance['showLink']) and $instance['showLink'] == 'on') or ! isset($instance['showLink'])) ? 1 : 0;
        $bgColor     = isset($instance['bgColor']) ? esc_attr($instance['bgColor']) : "";
        $style       = isset($instance['style']) ? esc_attr($instance['style']) : "default";
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('MOD_ICAL_CALTYPE_LABEL', 'icalendrier'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
                <option value="comp175"<?php if ($type == 'comp175') {
                    echo " selected=\"selected\"";
                } ?>><?php _e('MOD_ICAL_CALTYPE_COMP175', 'icalendrier'); ?></option>
                <option value="wide300"<?php if ($type == 'wide300') {
                    echo " selected=\"selected\"";
                } ?>><?php _e('MOD_ICAL_CALTYPE_COMP300', 'icalendrier'); ?></option>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('MOD_ICAL_STYLE_LABEL', 'icalendrier'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
                <option value="default"<?php if ($style == 'default') {
                    echo " selected=\"selected\"";
                } ?>><?php _e('MOD_ICAL_STYLE_DEFAULT', 'icalendrier'); ?></option>
                <option value="alt-1"<?php if ($style == 'alt-1') {
                    echo " selected=\"selected\"";
                } ?>><?php _e('MOD_ICAL_STYLE_ALT_1', 'icalendrier'); ?></option>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('language'); ?>"><?php _e('MOD_ICAL_LANGUAGE_LABEL', 'icalendrier'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('language'); ?>" name="<?php echo $this->get_field_name('language'); ?>">
                <option value="de"<?php if ($language == 'de') {
                    echo " selected=\"selected\"";
                } ?>>Deutsch
                </option>
                <option value="en"<?php if ($language == 'en') {
                    echo " selected=\"selected\"";
                } ?>>English
                </option>
                <option value="es"<?php if ($language == 'es') {
                    echo " selected=\"selected\"";
                } ?>>Español
                </option>
                <option value="fr"<?php if ($language == 'fr') {
                    echo " selected=\"selected\"";
                } ?>>Français
                </option>
                <option value="it"<?php if ($language == 'it') {
                    echo " selected=\"selected\"";
                } ?>>Italiano
                </option>
                <option value="ro"<?php if ($language == 'ro') {
                    echo " selected=\"selected\"";
                } ?>>Română
                </option>
                <option value="pt"<?php if ($language == 'pt') {
                    echo " selected=\"selected\"";
                } ?>>Português
                </option>
                <option value="pt-BR"<?php if ($language == 'pt-BR') {
                    echo " selected=\"selected\"";
                } ?>>Português (Brasil)
                </option>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('timezone'); ?>"><?php _e('MOD_ICAL_TIMEZONE_LABEL', 'icalendrier'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('timezone'); ?>" name="<?php echo $this->get_field_name('timezone'); ?>">
                <option value="0"<?php if ($timezone === 0) {
                    echo " selected=\"selected\"";
                } ?>><?php _e('MOD_ICAL_TIMEZONE_AUTO', 'icalendrier'); ?></option>

                <?php
                $timezone_identifiers = DateTimeZone::listIdentifiers();
                preg_match("#^(.+?)/#", $timezone_identifiers[count($timezone_identifiers) - 1], $m);
                $optGroup = $m[1];
                echo '<optgroup label="' . $optGroup . '">' . PHP_EOL;
                foreach ($timezone_identifiers as $timezone_identifier) {
                    preg_match("#^(.+?)/#", $timezone_identifier, $m);
                    if ($optGroup != $m[1]) {
                        echo '</optgroup>' . PHP_EOL;
                        $optGroup = $m[1];
                        echo '<optgroup label="' . $optGroup . '">' . PHP_EOL;
                    }
                    echo '<option value="' . $timezone_identifier . '" ' . ($timezone === $timezone_identifier ? 'selected="selected"' : '') . '>' . $timezone_identifier . '</option>';
                }
                echo '</optgroup>';
                ?>
                <option value="UTC"<?php if ($timezone == 'UTC') {
                    echo " selected=\"selected\"";
                } ?>>UTC
                </option>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('showLink'); ?>"><?php _e('MOD_ICAL_SHOWLINK_LABEL', 'icalendrier'); ?></label> &nbsp;
            <input id="<?php echo $this->get_field_id('showLink'); ?>" name="<?php echo $this->get_field_name('showLink'); ?>"
                   type="checkbox" <?php if ($showLink) {
                echo ' checked="checked"';
            } ?> />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('widgetTitle'); ?>"><?php _e('MOD_ICAL_WIDGET_TITLE_LABEL', 'icalendrier'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('widgetTitle'); ?>" name="<?php echo $this->get_field_name('widgetTitle'); ?>"
                   type="text"
                   value="<?php echo $widgetTitle; ?>"/>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('bgColor'); ?>"><?php _e('MOD_ICAL_BGCOLOR_LABEL', 'icalendrier'); ?></label><br/>
            <input class="widefat" id="<?php echo $this->get_field_id('bgColor'); ?>" name="<?php echo $this->get_field_name('bgColor'); ?>"
                   type="text"
                   value="<?php echo $bgColor; ?>"/>
        </p>

        <?php
    }
}

add_action('widgets_init', function () {
    register_widget('ICalendrierWidget');
});