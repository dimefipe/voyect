<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect_Activator {
    public static function activate() {
        // Aquí podrás registrar CPT y taxonomías si lo deseas, antes del flush.
        flush_rewrite_rules();
    }
}
