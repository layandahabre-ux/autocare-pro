// assets/js/app.js — AutoCare Pro

const APP_URL = document.querySelector('meta[name=app-url]')?.content || '';

// ── סרגל צד (מובייל) ─────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar')?.classList.toggle('open');
  document.getElementById('sidebarOverlay')?.classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar')?.classList.remove('open');
  document.getElementById('sidebarOverlay')?.classList.remove('show');
}

// ── התראות (Toast) ───────────────────────────────────
function showToast(message, type = 'success', duration = 3500) {
  let cont = document.getElementById('toast-container');
  if (!cont) {
    cont = document.createElement('div');
    cont.id = 'toast-container';
    cont.className = 'toast-container';
    document.body.appendChild(cont);
  }
  const icons = { success: 'fa-circle-check', danger: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
  const toast = document.createElement('div');
  toast.className = `toast-item toast-${type}`;
  toast.innerHTML = `<i class="fa ${icons[type] || icons.success}"></i><span>${message}</span>`;
  cont.appendChild(toast);
  setTimeout(() => { toast.style.animation = 'fadeOut .25s forwards'; setTimeout(() => toast.remove(), 250); }, duration);
}

// ── קריאות API ───────────────────────────────────────
async function api(endpoint, options = {}) {
  const defaults = { headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
  const cfg = { ...defaults, ...options };
  if (cfg.body && typeof cfg.body === 'object') cfg.body = JSON.stringify(cfg.body);
  try {
    const res  = await fetch(APP_URL + '/api/' + endpoint, cfg);
    const data = await res.json();
    if (!res.ok || data.ok === false) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
  } catch (e) {
    showToast(e.message, 'danger');
    throw e;
  }
}

// ── טבלת נתונים גנרית עם חיפוש, מיון ועימוד ──────────
class DataTable {
  constructor(opts) {
    this.opts    = opts;
    this.data    = [];
    this.total   = 0;
    this.page    = 1;
    this.limit   = opts.limit || 15;
    this.search  = '';
    this.sortBy  = opts.defaultSort || 'id';
    this.sortDir = opts.defaultDir || 'DESC';
    this.filters = opts.filters || {};
    this.init();
  }
  async init() { this.bindSearch(); await this.load(); }
  bindSearch() {
    const el = document.getElementById(this.opts.searchId);
    if (!el) return;
    let t;
    el.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => { this.search = el.value; this.page = 1; this.load(); }, 320);
    });
  }
  setFilter(key, value) { this.filters[key] = value; this.page = 1; this.load(); }
  async load() {
    const params = new URLSearchParams({
      action: 'list', page: this.page, limit: this.limit,
      search: this.search, sort: this.sortBy, dir: this.sortDir, ...this.filters,
    });
    this.setLoading(true);
    try {
      const d = await api(this.opts.endpoint + '?' + params);
      this.data  = d.data.rows;
      this.total = d.data.total;
      this.render();
      this.renderPager();
    } finally {
      this.setLoading(false);
    }
  }
  render() {
    const el = document.getElementById(this.opts.tbodyId);
    if (!el) return;
    if (!this.data.length) {
      el.innerHTML = `<tr><td colspan="99" class="empty-row">
        <i class="fa fa-inbox"></i><div>אין נתונים להצגה</div></td></tr>`;
      return;
    }
    el.innerHTML = this.data.map(r => this.opts.rowTpl(r)).join('');
  }
  renderPager() {
    const pages = Math.ceil(this.total / this.limit) || 1;
    const from  = this.total ? (this.page - 1) * this.limit + 1 : 0;
    const to    = Math.min(this.page * this.limit, this.total);
    const info  = document.getElementById(this.opts.infoId);
    if (info) info.textContent = `מציג ${from}–${to} מתוך ${this.total.toLocaleString('he-IL')}`;
    const pager = document.getElementById(this.opts.pagerId);
    if (!pager) return;
    pager.innerHTML = '';
    const addBtn = (label, page, disabled, active) => {
      const b = document.createElement('button');
      b.className = `page-btn${active ? ' active' : ''}`;
      b.innerHTML = label;
      b.disabled  = disabled;
      if (!disabled && !active) b.onclick = () => { this.page = page; this.load(); };
      pager.appendChild(b);
    };
    addBtn('<i class="fa fa-angle-right"></i>', this.page - 1, this.page <= 1, false);
    for (let i = 1; i <= Math.min(pages, 10); i++) addBtn(i, i, false, i === this.page);
    addBtn('<i class="fa fa-angle-left"></i>', this.page + 1, this.page >= pages, false);
    window[`__dt_${this.opts.id}`] = this;
  }
  setLoading(v) {
    const el = document.getElementById(this.opts.tbodyId);
    if (el) el.style.opacity = v ? '.4' : '1';
  }
  sort(col) {
    if (this.sortBy === col) this.sortDir = this.sortDir === 'ASC' ? 'DESC' : 'ASC';
    else { this.sortBy = col; this.sortDir = 'ASC'; }
    this.load();
  }
  reload() { this.load(); }
}

// ── פונקציות עזר לעיצוב ───────────────────────────────
const fmt = {
  currency: n => '₪\u00A0' + Number(n || 0).toLocaleString('he-IL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
  date:     s => s ? new Date(s).toLocaleDateString('he-IL') : '—',
  datetime: s => s ? new Date(s).toLocaleString('he-IL', { dateStyle: 'short', timeStyle: 'short' }) : '—',
  number:   n => Number(n || 0).toLocaleString('he-IL'),
};

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
function confirmDelete(msg, cb) { if (confirm(msg || 'האם למחוק רשומה זו? פעולה זו אינה הפיכה.')) cb(); }
function openModal(id)  { new bootstrap.Modal(document.getElementById(id)).show(); }
function closeModal(id) { bootstrap.Modal.getInstance(document.getElementById(id))?.hide(); }
function serializeForm(id) {
  const d = {};
  new FormData(document.getElementById(id)).forEach((v, k) => d[k] = v);
  return d;
}

// אנימציית סגירת toast
const _style = document.createElement('style');
_style.textContent = '@keyframes fadeOut{to{opacity:0;transform:translateY(8px)}}';
document.head.appendChild(_style);
