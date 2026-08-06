<script setup lang="ts">
const auth = useAdminAuth();
const route = useRoute();
const sidebarOpen = ref(false);
const theme = useState<'dark' | 'light'>('admin-theme', () => 'dark');
const profileMenuOpen = ref(false);
const { data: adminSettingsData, refresh: refreshAdminSettings } = await useFetch<any>('/api/settings', { server: false });
const popupEditorOpen = ref(false);
const popupSaving = ref(false);
const popupDraft = reactive({
  enabled: 0,
  limitPerDay: 5,
  items: [] as Array<{
    title: string;
    image_url: string;
    note: string;
    button_label: string;
    button_url: string;
    status: number;
  }>
});

const isLoginPage = computed(() => route.path === '/login');
const isDark = computed(() => theme.value === 'dark');

const navItems = [
  {
    path: '/',
    label: 'Dashboard',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
  },
  {
    path: '/users',
    label: 'Users',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
  },
  {
    path: '/orders',
    label: 'Orders',
    icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
  },
  {
    path: '/transactions',
    label: 'Transactions',
    icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'
  },
  {
    path: '/products',
    label: 'Products',
    icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5zM5 13a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 00-2-2H5z'
  },
  {
    path: '/categories',
    label: 'Category',
    icon: 'M3 7h18M3 12h18M3 17h18'
  },
  {
    path: '/packages',
    label: 'Packages',
    icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
  },
  {
    path: '/sliders',
    label: 'Sliders',
    icon: 'M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2zm1 4h8m-8 4h8m-8 4h5'
  },
  {
    path: '/api',
    label: 'API',
    icon: 'M4 7h16M4 12h10M4 17h7M18 12a2 2 0 100-4 2 2 0 000 4zm2 7a2 2 0 100-4 2 2 0 000 4z'
  }
];
const settingsNavItem = {
  path: '/settings',
  label: 'Settings',
  icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
};

function createPopupItem() {
  return {
    title: '',
    image_url: '',
    note: '',
    button_label: 'Click Here',
    button_url: '',
    status: 1
  };
}

function syncPopupDraft() {
  const settings = adminSettingsData.value?.settings || {};
  popupDraft.enabled = Number(settings.home_page_popup_enabled ?? 0);
  popupDraft.limitPerDay = Math.max(1, Number(settings.home_page_popup_limit_per_day || 5));
  popupDraft.items = Array.isArray(settings.home_page_popup_items)
    ? settings.home_page_popup_items.map((item: any) => ({
        title: String(item?.title || ''),
        image_url: String(item?.image_url || ''),
        note: String(item?.note || ''),
        button_label: String(item?.button_label || 'Click Here'),
        button_url: String(item?.button_url || ''),
        status: Number(item?.status ?? 1) === 1 ? 1 : 0
      }))
    : [];
}

watch(adminSettingsData, () => {
  syncPopupDraft();
}, { immediate: true, deep: true });

function applyTheme(value: 'dark' | 'light') {
  if (!process.client) return;
  document.documentElement.classList.toggle('dark', value === 'dark');
  document.documentElement.setAttribute('data-theme', value);
  localStorage.setItem('admin_theme', value);
}

onMounted(() => {
  const saved = localStorage.getItem('admin_theme');
  if (saved === 'dark' || saved === 'light') theme.value = saved;
  applyTheme(theme.value);
});

watch(theme, (value) => {
  applyTheme(value);
});

function toggleTheme() {
  theme.value = isDark.value ? 'light' : 'dark';
}

function toggleProfileMenu() {
  profileMenuOpen.value = !profileMenuOpen.value;
}

function closeProfileMenu() {
  profileMenuOpen.value = false;
}

function openProfilePage() {
  profileMenuOpen.value = false;
  navigateTo('/profile');
}

function openPopupEditor() {
  syncPopupDraft();
  popupEditorOpen.value = true;
}

function closePopupEditor() {
  popupEditorOpen.value = false;
}

function addPopupItem() {
  popupDraft.items.push(createPopupItem());
}

function removePopupItem(index: number) {
  popupDraft.items.splice(index, 1);
}

async function savePopupEditor() {
  if (popupSaving.value) return;
  popupSaving.value = true;
  try {
    const current = adminSettingsData.value?.settings || {};
    await $fetch('/api/settings', {
      method: 'POST',
      body: {
        ...current,
        home_page_popup_enabled: popupDraft.enabled,
        home_page_popup_limit_per_day: popupDraft.limitPerDay,
        home_page_popup_items: popupDraft.items
      }
    });
    await refreshAdminSettings();
    popupEditorOpen.value = false;
  } finally {
    popupSaving.value = false;
  }
}

async function uploadPopupImageForItem(index: number, event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input?.files?.[0];
  if (!file) return;
  try {
    const payload = new FormData();
    payload.append('folder', 'banners');
    payload.append('file', file, file.name);
    const res = await $fetch<{ url: string }>('/api/upload-image', {
      method: 'POST',
      body: payload
    });
    if (!popupDraft.items[index]) return;
    popupDraft.items[index].image_url = String(res?.url || '');
  } finally {
    if (input) input.value = '';
  }
}

function navLinkClass(path: string) {
  const active = route.path === path;
  if (isDark.value) {
    return active
      ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-600/25'
      : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200';
  }
  return active
    ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/25'
    : 'text-slate-600 hover:bg-indigo-50 hover:text-indigo-700';
}

function navIconClass(path: string) {
  const active = route.path === path;
  if (active) return 'text-white';
  return isDark.value ? 'text-slate-400 group-hover:text-indigo-400' : 'text-slate-500 group-hover:text-indigo-600';
}

async function handleLogout() {
  await $fetch('/api/auth/logout', { method: 'POST' });
  auth.logout();
  navigateTo('/login');
}

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value;
}

function closeSidebar() {
  sidebarOpen.value = false;
}
</script>

<template>
  <div :class="isDark ? 'min-h-screen bg-slate-950 font-sans text-slate-100 antialiased selection:bg-indigo-500 selection:text-white' : 'min-h-screen bg-slate-100 font-sans text-slate-800 antialiased selection:bg-indigo-200 selection:text-slate-900'">
    <slot v-if="isLoginPage" />

    <div v-else class="flex min-h-screen">
      <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="sidebarOpen"
          :class="isDark ? 'fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-md md:hidden' : 'fixed inset-0 z-40 bg-slate-900/35 backdrop-blur-sm md:hidden'"
          @click="closeSidebar"
        />
      </Transition>

      <aside
        :class="[
          isDark
            ? 'fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-800/80 bg-slate-900/90 backdrop-blur-xl transition-transform duration-300 ease-in-out md:translate-x-0'
            : 'fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white/95 backdrop-blur-xl transition-transform duration-300 ease-in-out md:translate-x-0',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
        :style="isDark ? '' : 'box-shadow: 0 20px 45px -30px rgba(15,23,42,.35);'"
      >
        <div :class="isDark ? 'flex h-20 shrink-0 items-center justify-between border-b border-slate-800/80 px-6' : 'flex h-20 shrink-0 items-center justify-between border-b border-slate-200 px-6'">
          <div class="flex items-center gap-3">
            <div class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 font-black text-white shadow-lg shadow-indigo-500/20">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <span :class="isDark ? 'block text-lg font-black tracking-wider text-white' : 'block text-lg font-black tracking-wider text-slate-800'">GHOST<span class="text-indigo-500">ADMIN</span></span>
              <span :class="isDark ? 'block text-[10px] font-bold uppercase tracking-widest text-slate-400' : 'block text-[10px] font-bold uppercase tracking-widest text-slate-500'">Dashboard v2.0</span>
            </div>
          </div>

          <button @click="closeSidebar" :class="isDark ? 'rounded-lg p-1 text-slate-400 hover:bg-slate-800 hover:text-white md:hidden' : 'rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-800 md:hidden'">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-4 pt-4 pb-5">
          <div :class="isDark ? 'mb-2 px-2 text-[10px] font-bold uppercase tracking-widest text-slate-500' : 'mb-2 px-2 text-[10px] font-bold uppercase tracking-widest text-slate-400'">Navigation</div>

          <NuxtLink
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="group relative flex items-center gap-3.5 rounded-xl px-3.5 py-3 text-xs font-semibold transition-all duration-200"
            :class="navLinkClass(item.path)"
            @click="closeSidebar"
          >
            <svg
              class="h-5 w-5 transition-transform duration-200 group-hover:scale-110"
              :class="navIconClass(item.path)"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="1.8"
            >
              <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
            </svg>

            <span>{{ item.label }}</span>

            <span
              v-if="route.path === item.path"
              class="ml-auto h-2 w-2 rounded-full bg-white shadow-sm"
            />
          </NuxtLink>

          <button
            type="button"
            class="mt-3 flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-xs font-semibold transition-all duration-200"
            :class="isDark ? 'bg-violet-600/15 text-violet-300 hover:bg-violet-600/25' : 'bg-violet-50 text-violet-700 hover:bg-violet-100'"
            @click="openPopupEditor"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 5h14a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Popup
          </button>

          <NuxtLink
            :to="settingsNavItem.path"
            class="mt-3 group relative flex items-center gap-3.5 rounded-xl px-3.5 py-3 text-xs font-semibold transition-all duration-200"
            :class="navLinkClass(settingsNavItem.path)"
            @click="closeSidebar"
          >
            <svg
              class="h-5 w-5 transition-transform duration-200 group-hover:scale-110"
              :class="navIconClass(settingsNavItem.path)"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="1.8"
            >
              <path stroke-linecap="round" stroke-linejoin="round" :d="settingsNavItem.icon" />
            </svg>
            <span>{{ settingsNavItem.label }}</span>
            <span
              v-if="route.path === settingsNavItem.path"
              class="ml-auto h-2 w-2 rounded-full bg-white shadow-sm"
            />
          </NuxtLink>
        </nav>

      </aside>

      <div class="flex min-w-0 flex-1 flex-col md:ml-72">
        <header :class="isDark ? 'sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-800/80 bg-slate-950/80 px-4 backdrop-blur-xl sm:px-8' : 'sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/85 px-4 backdrop-blur-xl sm:px-8'">
          <div class="flex items-center gap-4">
            <button
              type="button"
              :class="isDark ? 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-800 bg-slate-900 text-slate-300 hover:border-slate-700 hover:text-white md:hidden' : 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900 md:hidden'"
              @click="toggleSidebar"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>

            <div>
              <h1 :class="isDark ? 'text-lg font-bold text-white sm:text-xl' : 'text-lg font-bold text-slate-800 sm:text-xl'">
                {{ route.path === settingsNavItem.path ? settingsNavItem.label : (navItems.find(n => n.path === route.path)?.label || 'Dashboard') }}
              </h1>
              <p :class="isDark ? 'hidden text-[11px] text-slate-400 sm:block' : 'hidden text-[11px] text-slate-500 sm:block'">Welcome back to GhostBazar Control Panel</p>
            </div>
          </div>

          <div class="relative flex items-center gap-3">
            <button
              type="button"
              @click="toggleTheme"
              :class="isDark ? 'rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-indigo-500 hover:text-white' : 'rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-700'"
            >
              {{ isDark ? 'Light Theme' : 'Dark Theme' }}
            </button>
            <button
              type="button"
              :class="isDark ? 'inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-700 bg-slate-900/70 text-slate-300 transition hover:border-indigo-500 hover:text-white' : 'inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 transition hover:border-indigo-500 hover:text-indigo-700'"
            >
              <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0h6z" />
              </svg>
            </button>
            <button
              type="button"
              @click="toggleProfileMenu"
              :class="isDark ? 'inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900/80 p-1 pr-2 text-slate-200 transition hover:border-indigo-500 hover:text-white' : 'inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white p-1 pr-2 text-slate-700 transition hover:border-indigo-500 hover:text-indigo-700'"
            >
              <span :class="isDark ? 'flex h-7 w-7 items-center justify-center rounded-lg bg-slate-800 text-xs font-bold text-indigo-400' : 'flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-xs font-bold text-indigo-700'">
                {{ (auth.adminInfo.value?.name || 'A').charAt(0).toUpperCase() }}
              </span>
              <span class="hidden text-xs font-semibold sm:inline">Profile</span>
            </button>
            <div :class="isDark ? 'hidden items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/60 px-3 py-1.5 text-xs text-slate-400 sm:flex' : 'hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-500 sm:flex'">
              <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
              <span>System Live</span>
            </div>
            <button v-if="profileMenuOpen" type="button" class="fixed inset-0 z-40 cursor-default" @click="closeProfileMenu" />
            <div
              v-if="profileMenuOpen"
              :class="isDark ? 'absolute right-0 top-12 z-50 w-44 overflow-hidden rounded-xl border border-slate-700 bg-slate-900 shadow-2xl' : 'absolute right-0 top-12 z-50 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl'"
            >
              <button
                type="button"
                @click="openProfilePage"
                :class="isDark ? 'flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-slate-200 transition hover:bg-slate-800' : 'flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50'"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5.1 19a7 7 0 0113.8 0M12 12a4 4 0 100-8 4 4 0 000 8z" />
                </svg>
                Profile
              </button>
              <button
                type="button"
                @click="handleLogout"
                :class="isDark ? 'flex w-full items-center gap-2 border-t border-slate-800 px-4 py-3 text-left text-sm text-rose-300 transition hover:bg-slate-800' : 'flex w-full items-center gap-2 border-t border-slate-100 px-4 py-3 text-left text-sm text-rose-600 transition hover:bg-rose-50'"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                </svg>
                Logout
              </button>
            </div>
          </div>
        </header>

        <main :class="isDark ? 'flex-1 bg-slate-950 p-4 sm:p-6 lg:p-8' : 'flex-1 bg-transparent p-4 sm:p-6 lg:p-8'">
          <slot />
        </main>
        <footer :class="isDark ? 'border-t border-slate-800/70 bg-slate-950 px-4 py-3 text-center text-xs text-slate-400 sm:px-6' : 'border-t border-slate-200/80 bg-white/70 px-4 py-3 text-center text-xs text-slate-500 sm:px-6'">
          COPYRIGHT © 2019
          <a
            href="http://teammahal.info/"
            target="_blank"
            rel="noopener noreferrer"
            :class="isDark ? 'mx-1 font-semibold text-indigo-400 hover:text-indigo-300' : 'mx-1 font-semibold text-indigo-600 hover:text-indigo-700'"
          >
            Team Saiful
          </a>,
          All rights Reserved
        </footer>
      </div>

      <div v-if="popupEditorOpen" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4" @click.self="closePopupEditor">
        <div :class="isDark ? 'w-full max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl' : 'w-full max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl'">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <h3 :class="isDark ? 'text-lg font-bold text-white' : 'text-lg font-bold text-slate-800'">Homepage Popup</h3>
              <p :class="isDark ? 'text-xs text-slate-400' : 'text-xs text-slate-500'">Edit the homepage-only notice popup here.</p>
            </div>
            <button type="button" :class="isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-800'" @click="closePopupEditor">✕</button>
          </div>

          <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div class="rounded-xl border px-4 py-3" :class="isDark ? 'border-slate-700' : 'border-slate-200'">
                <div class="mb-2 text-xs font-bold uppercase tracking-widest" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Enable popup</div>
                <button
                  type="button"
                  class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                  :class="Number(popupDraft.enabled) === 1 ? 'bg-emerald-600' : 'bg-slate-400'"
                  @click="popupDraft.enabled = Number(popupDraft.enabled) === 1 ? 0 : 1"
                >
                  <span class="absolute left-2 text-[10px] font-bold text-white">{{ Number(popupDraft.enabled) === 1 ? 'ON' : 'OFF' }}</span>
                  <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="Number(popupDraft.enabled) === 1 ? 'translate-x-10' : 'translate-x-0'" />
                </button>
              </div>
              <div class="rounded-xl border px-4 py-3" :class="isDark ? 'border-slate-700' : 'border-slate-200'">
                <label class="mb-1 block text-xs font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Show count per day</label>
                <input v-model.number="popupDraft.limitPerDay" type="number" min="1" max="20" class="admin-input" />
              </div>
            </div>

            <div class="flex items-center justify-between">
              <p class="text-sm font-bold" :class="isDark ? 'text-slate-200' : 'text-slate-700'">Popup items</p>
              <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="addPopupItem">Add Popup</button>
            </div>

            <div v-for="(item, index) in popupDraft.items" :key="index" class="rounded-2xl border p-4" :class="isDark ? 'border-slate-700 bg-slate-950/60' : 'border-slate-200 bg-slate-50'">
              <div class="mb-3 flex items-center justify-between gap-2">
                <p class="text-xs font-bold uppercase tracking-widest" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Item {{ index + 1 }}</p>
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="relative inline-flex h-7 w-[66px] items-center rounded-full px-1 transition"
                    :class="Number(item.status) === 1 ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="item.status = Number(item.status) === 1 ? 0 : 1"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ Number(item.status) === 1 ? 'ON' : 'OFF' }}</span>
                    <span class="h-5 w-5 rounded-full bg-white shadow transition-transform" :class="Number(item.status) === 1 ? 'translate-x-8' : 'translate-x-0'" />
                  </button>
                  <button type="button" class="text-xs font-semibold text-rose-500" @click="removePopupItem(index)">Remove</button>
                </div>
              </div>
              <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Title</label>
                  <input v-model="item.title" type="text" class="admin-input" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Popup Image</label>
                  <div v-if="item.image_url" class="mb-2 overflow-hidden rounded-xl border" :class="isDark ? 'border-slate-700' : 'border-slate-200'">
                    <img :src="item.image_url" alt="Popup image" class="h-32 w-full object-cover" />
                  </div>
                  <input type="file" accept="image/*" class="admin-input" @change="uploadPopupImageForItem(index, $event)" />
                </div>
                <div class="md:col-span-2">
                  <label class="mb-1 block text-xs font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Note</label>
                  <textarea v-model="item.note" class="admin-input min-h-[84px]" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Button Label</label>
                  <input v-model="item.button_label" type="text" class="admin-input" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold" :class="isDark ? 'text-slate-400' : 'text-slate-500'">Button URL</label>
                  <input v-model="item.button_url" type="text" class="admin-input" />
                </div>
              </div>
            </div>
          </div>

          <div class="mt-5 flex justify-end gap-3 border-t pt-4" :class="isDark ? 'border-slate-800' : 'border-slate-200'">
            <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="closePopupEditor">Close</button>
            <button type="button" class="admin-btn-primary px-4 py-2 text-xs" :disabled="popupSaving" @click="savePopupEditor">
              {{ popupSaving ? 'Saving...' : 'Save Popup' }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
