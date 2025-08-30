<?php
if ( ! defined('ABSPATH') ) exit;

class Voyect_Loader {
    protected $actions = [];
    protected $filters = [];

    /**
     * Firmas soportadas:
     *  - add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
     *  - add_action($hook, $callback,  $priority = 10, $accepted_args = 1) // cuando el 2º parámetro ya es callable
     */
    public function add_action($hook, $component, $callback = null, $priority = 10, $accepted_args = 1) {
        // Modo corto: (hook, callback)
        if ($callback === null && is_callable($component)) {
            $callback  = $component;
            $component = null;
        }
        $this->actions[] = compact('hook','component','callback','priority','accepted_args');
    }

    /**
     * Firmas soportadas:
     *  - add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
     *  - add_filter($hook, $callback,  $priority = 10, $accepted_args = 1) // cuando el 2º parámetro ya es callable
     */
    public function add_filter($hook, $component, $callback = null, $priority = 10, $accepted_args = 1) {
        if ($callback === null && is_callable($component)) {
            $callback  = $component;
            $component = null;
        }
        $this->filters[] = compact('hook','component','callback','priority','accepted_args');
    }

    public function run() {
        foreach ($this->filters as $item) {
            add_filter(
                $item['hook'],
                $this->resolve_callback($item['component'], $item['callback']),
                $item['priority'],
                $item['accepted_args']
            );
        }
        foreach ($this->actions as $item) {
            add_action(
                $item['hook'],
                $this->resolve_callback($item['component'], $item['callback']),
                $item['priority'],
                $item['accepted_args']
            );
        }
    }

    /**
     * Normaliza el callback para WP:
     * - Da prioridad a [$component,'method'] si se pasó $component, para evitar
     *   confundirlo con una función global del mismo nombre (ej: load_textdomain).
     * - Acepta closures, funciones, [$obj,'metodo'] y ['Clase','metodo'].
     */
    protected function resolve_callback($component, $callback) {
        // 1) Si se pasó component + nombre de método, priorizarlo
        if ($component !== null && is_string($callback)) {
            // objeto + método
            if (is_object($component) && method_exists($component, $callback)) {
                return [$component, $callback];
            }
            // clase estática + método
            if (is_string($component) && is_callable([$component, $callback])) {
                return [$component, $callback];
            }
        }

        // 2) Si ya es callable (closure, array callable, nombre de función) úsalo
        if (is_callable($callback)) {
            return $callback;
        }

        // 3) Como último intento, si es nombre de función global válido
        if (is_string($callback) && function_exists($callback)) {
            return $callback;
        }

        // 4) Fallback: log y devolver algo seguro para evitar fatales
        error_log('[Voyect_Loader] Callback no válido: ' . print_r([$component, $callback], true));
        return '__return_null';
    }
}
