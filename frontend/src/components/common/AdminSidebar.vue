<!-- src/components/common/AdminSidebar.vue -->
<template>
  <aside class="sidebar" :class="{ collapsed }">

    <!-- Toggle -->
    <button class="toggle-btn" @click="collapsed = !collapsed"
      :aria-label="collapsed ? 'Buka sidebar' : 'Tutup sidebar'">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.5"
        stroke-linecap="round" stroke-linejoin="round"
        :style="{ transform: collapsed ? 'rotate(180deg)' : 'none', transition: 'transform 0.3s' }">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>

    <!-- Brand -->
    <div class="brand">
      <img src="@/assets/images/logo.png" alt="Logo SMA N 1 Mirit" class="brand-logo" />
      <Transition name="fade-text">
        <div v-if="!collapsed" class="brand-name">
          <span>SMA N 1 MIRIT</span>
          <span>KEBUMEN</span>
        </div>
      </Transition>
    </div>

    <!-- Nav -->
    <nav class="nav">
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="nav-item"
        active-class="nav-item--active"
        :title="collapsed ? item.label : undefined"
      >
        <span class="nav-icon" v-html="item.icon"></span>
        <Transition name="fade-text">
          <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
        </Transition>
      </router-link>
    </nav>

  </aside>
</template>

<script setup>
import { ref } from 'vue'

const collapsed = ref(false)

// Route sesuai routes.js: /admin/dashboard, /admin/users, /admin/classes, /admin/habits
const navItems = [
  {
    label: 'Dashboard',
    to: '/admin/dashboard',
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
      <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
    </svg>`,
  },
  {
    label: 'Manajemen Pengguna',
    to: '/admin/users',
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
      <circle cx="9" cy="7" r="4"/>
      <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>`,
  },
  {
    label: 'Manajemen Kelas',
    to: '/admin/classes',
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="18" height="18" rx="2"/>
      <path d="M3 9h18M9 21V9"/>
    </svg>`,
  },
  {
    label: 'Manajemen Habit',
    to: '/admin/habits',
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 11l3 3L22 4"/>
      <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
    </svg>`,
  },
]
</script>

<style scoped>
/* ─── Shell ─────────────────────────────────────────────── */
.sidebar {
  position: relative;
  display: flex;
  flex-direction: column;
  width: 240px;
  min-height: 100vh;
  background: #42b0f5;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  flex-shrink: 0;
}
.sidebar.collapsed { width: 68px; }

/* ─── Toggle ────────────────────────────────────────────── */
.toggle-btn {
  position: absolute;
  top: 18px;
  right: 12px;
  z-index: 10;
  background: rgba(255,255,255,0.2);
  border: none;
  border-radius: 8px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  cursor: pointer;
  transition: background 0.18s;
}
.toggle-btn:hover { background: rgba(255,255,255,0.32); }

/* ─── Brand ─────────────────────────────────────────────── */
.brand {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 16px 24px;
  gap: 12px;
  border-bottom: 1px solid rgba(255,255,255,0.2);
}
.brand-logo {
  width: 64px;
  height: 64px;
  object-fit: contain;
  flex-shrink: 0;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.15));
}
.sidebar.collapsed .brand  { padding: 18px 12px 16px; }
.sidebar.collapsed .brand-logo { width: 40px; height: 40px; }

.brand-name {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  white-space: nowrap;
}
.brand-name span {
  font-size: 13px;
  font-weight: 900;
  color: #fff;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  line-height: 1.5;
}

/* ─── Nav ───────────────────────────────────────────────── */
.nav {
  display: flex;
  flex-direction: column;
  padding: 14px 10px;
  gap: 4px;
  flex: 1;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 11px 13px;
  border-radius: 12px;
  text-decoration: none;
  color: rgba(255,255,255,0.88);
  transition: background 0.18s, color 0.18s;
  white-space: nowrap;
  overflow: hidden;
}
.nav-item:hover               { background: rgba(255,255,255,0.18); color: #fff; }
.nav-item--active             { background: #0f172a; color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.nav-item--active:hover       { background: #1e293b; }
.sidebar.collapsed .nav-item  { justify-content: center; padding: 11px; }

.nav-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 20px;
  height: 20px;
}
.nav-label {
  font-size: 14px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ─── Transitions ───────────────────────────────────────── */
.fade-text-enter-active { transition: opacity 0.2s ease 0.1s, transform 0.2s ease 0.1s; }
.fade-text-leave-active { transition: opacity 0.1s ease, transform 0.1s ease; }
.fade-text-enter-from,
.fade-text-leave-to     { opacity: 0; transform: translateX(-6px); }

/* ─── Mobile ────────────────────────────────────────────── */
@media (max-width: 768px) {
  .sidebar      { width: 68px; }
  .nav-item     { justify-content: center; padding: 11px; }
}
</style>