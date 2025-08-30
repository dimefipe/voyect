<?php if ( ! defined('ABSPATH') ) exit; ?>
<?php
  // Meses localizados para los selects de fecha
  $months = array();
  for ($m = 1; $m <= 12; $m++) {
    $months[$m] = date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) );
  }
  $current_year = (int) date_i18n('Y');
?>
<div class="voyect-modal" id="voyect-edit" aria-hidden="true" aria-labelledby="voyect-edit-title" role="dialog">
  <div class="voyect-modal__dialog voyect-modal--lg" role="document">
    <header class="voyect-modal__header">
      <h2 id="voyect-edit-title" class="voyect-modal__title">
        <?php esc_html_e('Edición rápida','voyect'); ?>
      </h2>
      <button class="voyect-modal__close" data-close aria-label="<?php esc_attr_e('Cerrar','voyect'); ?>">×</button>
    </header>

    <div class="voyect-modal__body">
      <!-- Logs de depuración al abrir el modal -->
      <script>
        (function(){ try{
          console.debug('[Voyect][modal-edit] view cargado');
        }catch(e){} })();

        // Clamp numérico simple con logs (re-usable para día/hora/min)
        // Se define aquí para no depender de otros bundles.
        window.voyectClampNumber = function(el){
          try{
            if(!el) return;
            // Permite borrado temporal mientras escribe
            if (el.value === '' || el.value === '-' ) return;
            var v = parseInt(el.value, 10);
            if (isNaN(v)) { el.value = ''; return; }
            var min = (el.min !== '') ? parseInt(el.min,10) : null;
            var max = (el.max !== '') ? parseInt(el.max,10) : null;
            if (min !== null && v < min) v = min;
            if (max !== null && v > max) v = max;
            // Normaliza a step si procede
            var step = (el.step && el.step !== 'any') ? parseInt(el.step,10) : 1;
            if (!isNaN(step) && step > 0) {
              // no forzamos múltiplos exactos, solo redondeamos si es extraño
              v = Math.round(v / step) * step;
            }
            var old = el.value;
            el.value = String(v);
            if (old !== el.value) {
              console.debug('[Voyect][modal-edit] clamp', {name: el.id, from: old, to: el.value, min, max, step});
            }
          }catch(e){ console.warn('[Voyect][modal-edit] clamp error', e); }
        };
      </script>

      <input type="hidden" id="edit-id" value="0" />

      <!-- Estado -->
      <label class="voyect-field">
        <span><?php esc_html_e('Estado','voyect'); ?></span>
        <select id="edit-status">
          <option value="publish"><?php esc_html_e('Publicado','voyect'); ?></option>
          <option value="draft"><?php esc_html_e('Borrador','voyect'); ?></option>
          <option value="pending"><?php esc_html_e('En revisión','voyect'); ?></option>
          <option value="private"><?php esc_html_e('Privado','voyect'); ?></option>
        </select>
      </label>

      <!-- Título -->
      <label class="voyect-field">
        <span><?php esc_html_e('Nombre de proyecto','voyect'); ?></span>
        <input type="text" id="edit-title" placeholder="<?php esc_attr_e('Ingrese el nombre del proyecto','voyect'); ?>" />
      </label>

      <!-- Slug + ayuda/validación -->
      <label class="voyect-field">
        <span><?php esc_html_e('Slug (URL del proyecto)','voyect'); ?></span>
        <input type="text" id="edit-slug" placeholder="<?php esc_attr_e('ingrese-el-slug','voyect'); ?>" aria-describedby="edit-slug-help" />
        <small id="edit-slug-help" class="voyect-help" aria-live="polite"></small>
      </label>

      <!-- Categorías (chips) -->
      <div class="voyect-field">
        <span><?php esc_html_e('Categorías','voyect'); ?></span>
        <div id="edit-cats" class="voyect-chips" aria-live="polite" aria-relevant="additions removals">
          <!-- chips inyectadas por JS -->
        </div>
        <small class="voyect-help" id="edit-cats-help"></small>
      </div>

      <!-- Imagen destacada -->
      <div class="voyect-field">
        <span><?php esc_html_e('Imagen destacada','voyect'); ?></span>
        <div class="voyect-thumb-picker" style="display:flex;align-items:center;gap:10px;">
          <div id="edit-thumb-preview" class="voyect-thumb" style="width:96px;height:64px;border-radius:8px;overflow:hidden;background:#f3f4f6;border:1px solid #e6e9ef;display:flex;align-items:center;justify-content:center;">
            <span style="opacity:.55;font-size:12px;"><?php esc_html_e('Sin imagen','voyect'); ?></span>
          </div>
          <div class="voyect-thumb-actions" style="display:flex;gap:8px;">
            <button type="button" id="edit-pick-thumb" class="button"><?php esc_html_e('Cambiar','voyect'); ?></button>
            <button type="button" id="edit-remove-thumb" class="button"><?php esc_html_e('Quitar','voyect'); ?></button>
          </div>
        </div>
        <input type="hidden" id="edit-thumb-id" value="0" />
      </div>

      <!-- Fecha / Hora (como Quick Edit de WP) -->
      <fieldset class="voyect-field">
        <legend style="font-weight:600;"><?php esc_html_e('Fecha y hora de publicación','voyect'); ?></legend>

        <div class="voyect-date-grid" style="display:grid;grid-template-columns:80px 1fr 90px 80px 80px;gap:8px;align-items:center;">
          <label style="display:grid;gap:4px;">
            <span style="font-size:12px;color:#6b7280;"><?php esc_html_e('Día','voyect'); ?></span>
            <input
              type="number"
              id="edit-day"
              inputmode="numeric"
              pattern="[0-9]*"
              min="1" max="31" step="1"
              value="1"
              oninput="voyectClampNumber(this)"
              title="<?php esc_attr_e('Día del mes (1–31)','voyect'); ?>"
            />
          </label>

          <label style="display:grid;gap:4px;">
            <span style="font-size:12px;color:#6b7280;"><?php esc_html_e('Mes','voyect'); ?></span>
            <select id="edit-month" title="<?php esc_attr_e('Mes','voyect'); ?>">
              <?php foreach ($months as $num => $label): ?>
                <option value="<?php echo esc_attr($num); ?>"><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label style="display:grid;gap:4px;">
            <span style="font-size:12px;color:#6b7280;"><?php esc_html_e('Año','voyect'); ?></span>
            <input
              type="number"
              id="edit-year"
              inputmode="numeric"
              pattern="[0-9]*"
              min="<?php echo esc_attr($current_year-10); ?>"
              max="<?php echo esc_attr($current_year+10); ?>"
              step="1"
              value="<?php echo esc_attr($current_year); ?>"
              oninput="voyectClampNumber(this)"
              title="<?php esc_attr_e('Año','voyect'); ?>"
            />
          </label>

          <label style="display:grid;gap:4px;">
            <span style="font-size:12px;color:#6b7280;"><?php esc_html_e('Hora','voyect'); ?></span>
            <input
              type="number"
              id="edit-hour"
              inputmode="numeric"
              pattern="[0-9]*"
              min="0" max="23" step="1"
              value="0"
              oninput="voyectClampNumber(this)"
              title="<?php esc_attr_e('Hora en formato 24h (0–23)','voyect'); ?>"
            />
          </label>

          <label style="display:grid;gap:4px;">
            <span style="font-size:12px;color:#6b7280;"><?php esc_html_e('Min','voyect'); ?></span>
            <input
              type="number"
              id="edit-min"
              inputmode="numeric"
              pattern="[0-9]*"
              min="0" max="59" step="1"
              value="0"
              oninput="voyectClampNumber(this)"
              title="<?php esc_attr_e('Minutos (0–59)','voyect'); ?>"
            />
          </label>
        </div>

        <small class="voyect-help"><?php esc_html_e('Si no cambias la fecha/hora se conservará la actual.','voyect'); ?></small>
      </fieldset>
    </div>

    <footer class="voyect-modal__footer" style="display:flex;justify-content:space-between;gap:10px;">
      <button class="button" data-close><?php esc_html_e('Cancelar','voyect'); ?></button>
      <div style="display:flex;gap:8px;align-items:center;">
        <span id="edit-saving-hint" style="font-size:12px;color:#6b7280;display:none;"><?php esc_html_e('Guardando…','voyect'); ?></span>
        <button id="edit-save" class="button button-primary">
          <?php esc_html_e('Guardar cambios','voyect'); ?>
        </button>
      </div>
    </footer>
  </div>
</div>
