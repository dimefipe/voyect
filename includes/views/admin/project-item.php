<?php if ( ! defined('ABSPATH') ) exit; ?>
<script type="text/template" id="voyect-item-tpl">
<li class="voyect-item" data-id="{{id}}">
  <div class="voyect-handle" title="<?php esc_attr_e('Arrastrar para ordenar','voyect'); ?>">
    <span class="ri-drag-move-2-line"></span>
  </div>

  <div class="voyect-thumb">
    <img src="{{thumb}}" alt="">
  </div>

  <div class="voyect-meta">
    <h4 class="voyect-title">{{title}}</h4>
    <div class="voyect-cats">{{cats}}</div>
  </div>

  <div class="voyect-actions">
    <button class="button button-small vo-view" title="<?php esc_attr_e('Vista previa','voyect'); ?>"><span class="ri-eye-line"></span></button>
    <button class="button button-small vo-edit" title="<?php esc_attr_e('Editar','voyect'); ?>"><span class="ri-edit-2-line"></span></button>
    <button class="button button-small vo-quick" title="<?php esc_attr_e('Edición rápida','voyect'); ?>"><span class="ri-flashlight-line"></span></button>
    <button class="button button-small vo-dup"  title="<?php esc_attr_e('Duplicar','voyect'); ?>"><span class="ri-file-copy-2-line"></span></button>
    <button class="button button-small vo-del"  title="<?php esc_attr_e('Eliminar','voyect'); ?>"><span class="ri-delete-bin-6-line"></span></button>
  </div>
</li>
</script>
