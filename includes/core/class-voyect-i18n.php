<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect_i18n {
    public function load_textdomain() {
        load_plugin_textdomain('voyect', false, dirname(plugin_basename(VOYECT_PATH . 'voyect.php')) . '/languages/');
    }
}
