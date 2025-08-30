/* Voyect – Admin AJAX Operations
   Provee funciones para consultar/crear/editar/eliminar proyectos, guardar el orden
   y validar/auto-sugerir slugs únicos.
   Requiere: axios (CDN) y ajaxurl global de WordPress o VoyectVars.ajaxurl.
*/

(function (window) {
  if (!window.axios) {
    console.error('[Voyect] axios no está cargado.');
    return;
  }

  // Preferimos VoyectVars.ajaxurl si está disponible (inyectado vía wp_localize_script)
  const WP_AJAX =
    (window.VoyectVars && window.VoyectVars.ajaxurl) ? window.VoyectVars.ajaxurl :
    (typeof ajaxurl !== 'undefined' ? ajaxurl : null);

  if (!WP_AJAX) {
    console.error('[Voyect] ajaxurl no está definido por WordPress.');
    return;
  }

  console.debug('[Voyect][API] WP_AJAX =', WP_AJAX);

  // Helpers
  const toParams = (obj) => {
    const p = new URLSearchParams();
    Object.entries(obj || {}).forEach(([k, v]) => {
      if (v === undefined) return; // evitamos "undefined" en la query
      p.append(k, v);
    });
    return p;
  };

  // === Helper de fecha ===
  // Recibe números (YYYY, M, D, HH, mm) y retorna "Y-m-d H:i:s" con zero-pad.
  // *No* aplica zonas horarias: el backend interpretará en la zona local de WP.
  function zero(n) { n = parseInt(n, 10); return (n < 10 ? '0' : '') + n; }
  function formatToWpDateTime({ year, month, day, hour = 0, min = 0 }) {
    if (!year || !month || !day) return '';
    const y = String(year);
    const m = zero(month);
    const d = zero(day);
    const h = zero(hour);
    const i = zero(min);
    const result = `${y}-${m}-${d} ${h}:${i}:00`;
    console.debug('[Voyect][API] formatToWpDateTime →', result, { year, month, day, hour, min });
    return result;
  }

  // Obtiene nonce desde el listado (o desde VoyectVars), para endpoints protegidos (admin)
  function getNonce() {
    const el = document.querySelector('#voyect-list');
    const fromList = el ? el.getAttribute('data-nonce') : '';
    const fromVars = (window.VoyectVars && window.VoyectVars.nonce) ? window.VoyectVars.nonce : '';
    const nonce = fromList || fromVars || '';
    console.debug('[Voyect][API] nonce:', { fromList, fromVars, nonce });
    return nonce;
  }

  // Helper centralizado que adjunta _wpnonce y maneja errores de forma consistente
  async function request(method, paramsOrBody) {
    const _wpnonce = getNonce();
    const payload = (paramsOrBody instanceof URLSearchParams) ? paramsOrBody : toParams(paramsOrBody);
    if (_wpnonce) payload.set('_wpnonce', _wpnonce);

    // Para trazar qué action estamos enviando
    const actionForLog = payload.get ? payload.get('action') : undefined;
    console.debug(`[Voyect][API] → ${method} action=${actionForLog}`, Object.fromEntries(payload));

    try {
      if (method === 'GET') {
        const { data } = await axios.get(WP_AJAX, { params: Object.fromEntries(payload) });
        console.debug(`[Voyect][API] ← ${method} action=${actionForLog}`, data);
        return data;
      } else {
        const { data } = await axios.post(WP_AJAX, payload);
        console.debug(`[Voyect][API] ← ${method} action=${actionForLog}`, data);
        return data;
      }
    } catch (err) {
      const status = err?.response?.status;
      const msg = err?.response?.data?.message || err.message || 'Error';
      console.error(`[Voyect][API] ${method} ${status || ''}: ${msg}`, err?.response?.data || err);
      throw err;
    }
  }

  // =============== READ ===============
  async function getProjects({ page = 1, per_page = 10, search = '', term = 0, orderby = 'menu_order', order = 'asc' } = {}) {
    return await request('GET', {
      action: 'voyect_get_projects',
      page, per_page, search, term, orderby, order,
    });
  }

  async function getCategories() {
    return await request('GET', { action: 'voyect_get_cats' });
  }

  // =============== CREATE / UPDATE / DELETE / DUPLICATE ===============
  async function createProject({ title, slug = '', cats = [], thumb = 0 }) {
    // Si slug === '' el servidor puede dejar que WP genere uno único desde el título.
    const body = toParams({ action: 'voyect_create_project', title, slug, thumb });
    (cats || []).forEach((c) => body.append('cats[]', c));
    return await request('POST', body);
  }

  // 🔁 AHORA ACEPTA 'date' (string "Y-m-d H:i:s") o 'dateParts' ({year, month, day, hour, min})
  async function updateProject({ id, title = null, slug = null, cats = null, thumb = null, status = null, date = null, dateParts = null }) {
    const body = toParams({ action: 'voyect_update_project', id });
    if (title !== null) body.append('title', title);
    if (slug  !== null)  body.append('slug', slug);
    if (status!== null)  body.append('status', status);
    if (cats  !== null)  (cats || []).forEach((c) => body.append('cats[]', c));
    if (thumb !== null)  body.append('thumb', thumb);

    // Soporte fecha:
    let dateToSend = date;
    if (!dateToSend && dateParts && (dateParts.year && dateParts.month && dateParts.day)) {
      dateToSend = formatToWpDateTime(dateParts);
    }
    if (dateToSend) {
      body.append('date', dateToSend);
      console.debug('[Voyect][API] updateProject enviando fecha:', dateToSend);
    } else {
      console.debug('[Voyect][API] updateProject SIN fecha (no se modifica post_date)');
    }

    return await request('POST', body);
  }

  async function deleteProject({ id, force = false }) {
    const body = toParams({ action: 'voyect_delete_project', id, force: force ? 1 : 0 });
    return await request('POST', body);
  }

  async function duplicateProject({ id }) {
    const body = toParams({ action: 'voyect_duplicate_project', id });
    return await request('POST', body);
  }

  async function saveOrder({ orderIds = [] }) {
    const body = toParams({ action: 'voyect_save_order', order: JSON.stringify(orderIds) });
    return await request('POST', body);
  }

  // =============== SLUG (único) ===============
  // Alineado con PHP -> action: 'voyect_check_slug_unique'
  // Parámetros esperados por el servidor:
  //  - slug: el slug tipeado (puede venir vacío)
  //  - title: título (para que el server derive un slug cuando no haya slug)
  //  - exclude: ID a excluir (0 al crear)
  async function checkSlugUnique({ slug = '', title = '', exclude = 0 }) {
    const params = toParams({
      action: 'voyect_check_slug_unique',
      slug,
      title,
      exclude
    });
    return await request('GET', params);
  }

  // Utilidad rápida para probar desde la consola si el módulo cargó
  function ping() {
    console.debug('[Voyect][API] ping OK', { WP_AJAX, nonce: getNonce() });
    return true;
  }

  // Exponer API global
  window.VoyectAPI = {
    WP_AJAX,
    getProjects,
    getCategories,
    createProject,
    updateProject,
    deleteProject,
    duplicateProject,
    saveOrder,
    checkSlugUnique,
    formatToWpDateTime, // ← por si quieres usarlo desde el UI de modales
    ping,
  };
})(window);
