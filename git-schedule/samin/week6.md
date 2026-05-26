# Samin — Week 6 (4–10 May 2026)
**Branch:** `feature/vue-setup`  
**Modules owned:** M2 Jobs · M3 Applications  
**This week's focus:** frontend foundation (Vite, Vue 3, Pinia, Tailwind). Module CRUD starts in Week 7.

---

## Day 1 — Mon 4 May
Vite + Vue 3 + Pinia scaffolding.

**Files touched:**
- `frontend/package.json` — `vue@^3`, `vue-router@^4`, `pinia`, `axios`, `vite`, dev tooling.
- `frontend/vite.config.js` — `@vitejs/plugin-vue`, dev `proxy` `/api` → `http://localhost:8000`.
- `frontend/src/main.js` — create app, register Pinia + router placeholder.
- `frontend/src/App.vue` — minimal `<RouterView />` shell.
- `frontend/index.html` — root `<div id="app">`.

```bash
git checkout -b feature/vue-setup
git add frontend/package.json frontend/vite.config.js frontend/src/main.js frontend/src/App.vue frontend/index.html
git commit -m "chore(frontend): scaffold Vite + Vue 3 + Pinia"
git push -u origin feature/vue-setup
```

---

## Day 2 — Wed 6 May
Tailwind CSS + base styles.

**Files touched:**
- `frontend/tailwind.config.js` — content paths, brand color tokens.
- `frontend/postcss.config.js`
- `frontend/src/assets/main.css` — Tailwind directives + CSS resets + base typography.

```bash
git add frontend/tailwind.config.js frontend/postcss.config.js frontend/src/assets/main.css
git commit -m "chore(frontend): Tailwind CSS + base styles"
git push origin feature/vue-setup
```

---

## Day 3 — Fri 8 May
Axios instance ready for Areeb's auth interceptors in Week 7.

**Files touched:**
- `frontend/src/services/api.js` — `axios.create({ baseURL: '/api', timeout: 10000 })`; placeholder for request/response interceptors (filled by Areeb in Wk7 of his branch).

```bash
git add frontend/src/services/api.js
git commit -m "chore(frontend): base Axios instance"
git push origin feature/vue-setup
```

> **Open PR:** `feature/vue-setup` → `main`. Title: *"Frontend scaffolding: Vue 3 + Pinia + Tailwind + Axios"*.
