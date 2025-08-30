<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Respuestas JSON consistentes para AJAX.
 */
class Voyect_Ajax_Handler {

    public static function success( $data = [], $status = 200 ) {
        wp_send_json( [
            'success' => true,
            'status'  => $status,
            'data'    => $data
        ], $status );
    }

    public static function error( $message = 'Error', $status = 400, $extra = [] ) {
        wp_send_json( array_merge([
            'success' => false,
            'status'  => $status,
            'message' => $message
        ], $extra), $status );
    }

    /** Verificación de nonce y capacidad */
    public static function verify( $capability = 'manage_options', $nonce_action = 'voyect_admin_nonce', $nonce_field = '_wpnonce' ) {
        if ( ! isset($_REQUEST[$nonce_field]) || ! wp_verify_nonce( $_REQUEST[$nonce_field], $nonce_action ) ) {
            self::error( __('Nonce inválido','voyect'), 403 );
        }
        if ( ! current_user_can( $capability ) ) {
            self::error( __('Permisos insuficientes','voyect'), 403 );
        }
    }
}
