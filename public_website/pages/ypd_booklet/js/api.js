/**
 * api.js — thin wrapper around the /api_ypd/* REST endpoints.
 * INTEGRATED VERSION: Now uses AdminDash-integrated YPD API
 * No build step, no framework: plain fetch, used by booklet.js and any
 * future admin tooling. Kept separate from rendering logic so the API
 * surface is easy to reuse (e.g. from a future admin form).
 */
const YpdApi = (() => {
  // Determine API base URL based on current context
  let BASE = '../api_ypd';
  if (typeof YPD_API_BASE !== 'undefined') {
    BASE = YPD_API_BASE;
  }

  // Apache serves the endpoints as direct PHP files, so translate
  // REST-style paths: /resource[/{id}] -> /resource.php[?id={id}]
  function toEndpoint(path) {
    const [pathname, query] = path.split('?');
    const parts = pathname.split('/').filter(Boolean);
    let ep = `/${parts[0]}.php`;
    const qs = [];
    if (parts[1] !== undefined) qs.push(`id=${encodeURIComponent(parts[1])}`);
    if (query) qs.push(query);
    return ep + (qs.length ? `?${qs.join('&')}` : '');
  }

  async function request(path, options = {}) {
    // Add session cookie for authentication
    const fetchOptions = {
      credentials: 'same-origin', // Include cookies for authentication
      headers: options.body && !(options.body instanceof FormData)
        ? { 'Content-Type': 'application/json' }
        : undefined,
      ...options,
    };

    const res = await fetch(BASE + toEndpoint(path), fetchOptions);
    if (!res.ok) {
      let detail = res.statusText;
      try { const j = await res.json(); detail = j.error || detail; } catch (_) {}
      throw new Error(`API ${res.status}: ${detail}`);
    }
    if (res.status === 204) return null;
    return res.json();
  }

  const list = (resource, params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return request(`/${resource}${qs ? '?' + qs : ''}`);
  };
  const get = (resource, id) => request(`/${resource}/${id}`);
  const create = (resource, data) => request(`/${resource}`, { method: 'POST', body: JSON.stringify(data) });
  const update = (resource, id, data) => request(`/${resource}/${id}`, { method: 'PUT', body: JSON.stringify(data) });
  const remove = (resource, id) => request(`/${resource}/${id}`, { method: 'DELETE' });
  const upload = (file, meta = {}) => {
    const fd = new FormData();
    fd.append('file', file);
    Object.entries(meta).forEach(([k, v]) => fd.append(k, v));
    return request('/upload', { method: 'POST', body: fd });
  };
  const meta = {
    get: () => request('/meta'),
    update: (data) => request('/meta', { method: 'PUT', body: JSON.stringify(data) }),
  };

  return { list, get, create, update, remove, upload, meta };
})();
