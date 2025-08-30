(function ($) {
  // Helpers
  function zi(n) { n = parseInt(n, 10); return isNaN(n) ? 0 : n; }
  function getChipValues(root) {
    return $(root)
      .find('[data-value].is-active')
      .map((_, el) => $(el).data('value'))
      .get();
  }

  // ========= CREACIÓN =========
  // (Se mantiene simple porque ya funciona correctamente)
  $('#create-save').on('click', async () => {
    try {
      const title = $('#create-title').val();
      const slug  = $('#create-slug').val();
      const cats  = getChipValues('#create-cats');
      const thumb = zi($('#create-thumb-id').val());

      console.debug('[Voyect][CREATE] payload', { title, slug, cats, thumb });

      const resp = await window.VoyectAPI.createProject({ title, slug, cats, thumb });
      console.debug('[Voyect][CREATE] response', resp);

      // cerrar modal
      $('[data-close]').first().click();
      // refrescar (si tu listado usa un fetch separado, invócalo aquí)
      if (window.VoyectAdmin && typeof window.VoyectAdmin.refreshList === 'function') {
        window.VoyectAdmin.refreshList();
      }
    } catch (e) {
      console.error('[Voyect][CREATE] error', e);
      alert('Error al crear proyecto. Revisa la consola.');
    }
  });

  // ========= EDICIÓN RÁPIDA =========
  $('#edit-save').on('click', async () => {
    try {
      const id    = zi($('#edit-id').val());
      const title = $('#edit-title').val();
      const slug  = $('#edit-slug').val();
      const cats  = getChipValues('#edit-cats');
      const thumb = zi($('#edit-thumb-id').val());
      const status = $('#edit-status').val() || null;

      // Fecha desde inputs del modal
      const day   = zi($('#edit-day').val());
      const month = zi($('#edit-month').val());   // 1–12 (select)
      const year  = zi($('#edit-year').val());
      const hour  = zi($('#edit-hour').val());
      const min   = zi($('#edit-min').val());

      // Construimos dateParts solo si tenemos Y/M/D válidos
      let dateParts = null;
      if (year > 0 && month > 0 && day > 0) {
        dateParts = { year, month, day, hour, min };
      }

      console.debug('[Voyect][EDIT] payload base', { id, title, slug, cats, thumb, status, dateParts });

      const resp = await window.VoyectAPI.updateProject({
        id,
        title,
        slug,
        cats,
        thumb,
        status,
        // Enviamos dateParts; si no corresponde, la API lo ignora y no cambia post_date
        dateParts
      });

      console.debug('[Voyect][EDIT] response', resp);

      // cerrar modal
      $('[data-close]').first().click();

      // refrescar lista
      if (window.VoyectAdmin && typeof window.VoyectAdmin.refreshList === 'function') {
        window.VoyectAdmin.refreshList();
      } else {
        // fallback suave: recargar la página si no hay refresco programático
        // location.reload();
      }
    } catch (e) {
      console.error('[Voyect][EDIT] error', e);
      alert('Error al actualizar el proyecto. Revisa la consola.');
    }
  });

  // Ping opcional para validar que la API esté lista
  try {
    if (window.VoyectAPI && typeof window.VoyectAPI.ping === 'function') {
      window.VoyectAPI.ping();
    } else {
      console.warn('[Voyect][modals] VoyectAPI no disponible aún.');
    }
  } catch (e) {
    console.warn('[Voyect][modals] ping error', e);
  }
})(jQuery);
