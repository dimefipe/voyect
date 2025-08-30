<?php if ( ! defined('ABSPATH') ) exit; ?>
<div class="voyect-modal" id="voyect-create" aria-hidden="true">
  <div class="voyect-modal__dialog">
    <header>
      <h2><?php _e('Crear nuevo proyecto','voyect'); ?></h2>
      <button class="voyect-modal__close" data-close>&times;</button>
    </header>

    <div class="voyect-modal__body">
      <label class="voyect-field">
        <span><?php _e('Nombre de proyecto','voyect'); ?></span>
        <input type="text" id="create-title" placeholder="<?php esc_attr_e('Ingresa el nombre del proyecto','voyect'); ?>">
      </label>

      <label class="voyect-field">
        <span><?php _e('Slug (URL del proyecto)','voyect'); ?></span>
        <input type="text" id="create-slug" placeholder="<?php esc_attr_e('ingresa-la-url','voyect'); ?>">
      </label>

      <div class="voyect-chips" id="create-cats"><!-- chips por JS --></div>

      <!-- NEW: guardamos el ID del adjunto seleccionado -->
      <input type="hidden" id="create-thumb-id" value="">

      <!-- NEW: preview contenedor (se crea/actualiza por JS al seleccionar imagen) -->
      <div id="create-thumb-preview" class="voyect-thumb-preview" style="margin:8px 0;"></div>

      <button id="pick-thumb" class="button"><?php _e('Seleccionar imagen destacada','voyect'); ?></button>
    </div>

    <footer>
      <button class="button" data-close><?php _e('Cancelar','voyect'); ?></button>
      <button id="create-save" class="button button-primary"><?php _e('Crear proyecto','voyect'); ?></button>
    </footer>
  </div>
</div>
