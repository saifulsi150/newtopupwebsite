<script setup lang="ts">
const auth = useAuth();
const route = useRoute();
const showProfileMenu = ref(false);
const defaultManualAvatar = 'https://rgbazer.com/default.png';
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact');

const isAuthPage = computed(() => route.path === '/login' || route.path === '/register');
const showProtectedUi = computed(() => auth.isLoggedIn.value);

const walletBalance = computed(() => {
  if (!auth.isLoggedIn.value) return 0;
  const user = auth.user?.value || auth.user;
  return Number(user?.wallet_balance || 0);
});

async function syncAuthUser() {
  if (!auth.isLoggedIn.value) return;
  try {
    const res = await $fetch<{ user: any | null }>('/api/user/me', {
      method: 'GET',
      timeout: 8000,
      retry: 0
    });
    if (res?.user && typeof auth.setUser === 'function') {
      auth.setUser(res.user);
    }
  } catch {
    // Silent fail
  }
}

onMounted(() => {
  syncAuthUser();
});

const userAvatarSrc = computed(() => {
  if (!auth.isLoggedIn.value) return defaultManualAvatar;

  const user = auth.user?.value || auth.user;
  const provider = String(user?.login_provider || '').toLowerCase();
  const authAvatar = String(user?.avatar || user?.picture || '').trim();

  if (provider === 'google' && authAvatar && authAvatar.startsWith('http')) {
    return authAvatar;
  }
  return defaultManualAvatar;
});

const supportSettings = computed(() => contactSettingsData.value?.contact || {});
const siteName = computed(() => String(supportSettings.value?.site_name || '').trim());
const siteIconUrl = computed(() => String(supportSettings.value?.site_icon_url || '').trim());
const primaryLogoUrl = computed(() =>
  String(supportSettings.value?.logo_primary_url || '').trim()
);
const secondaryLogoUrl = computed(() =>
  String(supportSettings.value?.logo_secondary_url || '').trim()
);
const headerLogoUrl = computed(() => primaryLogoUrl.value || secondaryLogoUrl.value || siteIconUrl.value);
const appIconUrl = computed(() => headerLogoUrl.value || siteIconUrl.value);

// Dynamic Colors
const themeColor = computed(() => {
  const raw = String(supportSettings.value?.theme_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#0d682f';
});
const bgColor = computed(() => {
  const raw = String(supportSettings.value?.background_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#f1f6fc';
});
const headerBgColor = computed(() => {
  const raw = String(supportSettings.value?.navigation_background_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#ffffff';
});
const footerBgColor = computed(() => {
  const raw = String(supportSettings.value?.footer_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#030d36';
});
const footerTextColor = computed(() => {
  const raw = String(supportSettings.value?.footer_font_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#ffffff';
});

const themeStyles = computed(() => ({
  '--theme-color': themeColor.value,
  '--body-bg': bgColor.value,
  '--header-bg': headerBgColor.value,
  '--footer-bg': footerBgColor.value,
  '--footer-text': footerTextColor.value,
  backgroundColor: bgColor.value
}));

const showSupportWhatsapp = computed(() => Boolean(supportSettings.value?.show_whatsapp) && Boolean(globalWhatsappUrl.value));
const showSupportGroup = computed(() => Boolean(supportSettings.value?.show_telegram) && Boolean(globalGroupUrl.value));
const hasSupportCards = computed(() => showSupportWhatsapp.value || showSupportGroup.value);
const stayConnectedMessage = computed(() => String(supportSettings.value?.stay_connected_message || '').trim());
const socialFacebookUrl = computed(() => String(supportSettings.value?.social_facebook_url || '').trim());
const socialInstagramUrl = computed(() => String(supportSettings.value?.social_instagram_url || '').trim());
const socialYoutubeUrl = computed(() => String(supportSettings.value?.social_youtube_url || '').trim());
const socialEmail = computed(() => String(supportSettings.value?.social_email || '').trim());
const globalWhatsappUrl = computed(() => String(supportSettings.value?.support_center_whatsapp_url || '').trim());
const globalGroupUrl = computed(() => String(supportSettings.value?.support_center_group_url || '').trim());
const hasSocialLinks = computed(() =>
  Boolean(socialFacebookUrl.value || socialInstagramUrl.value || socialYoutubeUrl.value || socialEmail.value)
);

useHead(() => ({
  title: siteName.value || 'RG BAZZER',
  link: appIconUrl.value ? [{ rel: 'icon', href: appIconUrl.value }] : [],
  meta: [
    { name: 'theme-color', content: themeColor.value },
    { name: 'msapplication-TileColor', content: themeColor.value }
  ]
}));

function toggleMenu() {
  showProfileMenu.value = !showProfileMenu.value;
}

function closeMenu() {
  showProfileMenu.value = false;
}

async function logout() {
  try {
    await $fetch('/api/logout', { method: 'POST' });
  } catch {}
  if (typeof auth.logout === 'function') {
    auth.logout();
  }
  closeMenu();
  await navigateTo('/login');
}
</script>

<template>
  <div class="themed-root relative flex min-h-screen flex-col justify-between text-slate-900" :style="themeStyles">
    <!-- HEADER -->
    <header class="fixed inset-x-0 top-0 z-50 h-[62px] border-b border-slate-100 shadow-[0_1px_4px_rgba(0,0,0,0.03)] md:h-[68px]" :style="{ backgroundColor: headerBgColor }">
      <div class="mx-auto flex h-full max-w-6xl items-center justify-between px-3.5 lg:px-8">
        <!-- Logo -->
        <NuxtLink to="/" class="flex items-center gap-2" @click="closeMenu">
          <img v-if="headerLogoUrl" :src="headerLogoUrl" alt="Logo" class="h-8 w-auto md:h-10 object-contain" />
          <div v-else class="flex items-center gap-1.5">
            <svg class="h-6 w-6 text-[#e11d48]" viewBox="0 0 24 24" fill="currentColor">
              <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
            <span class="text-lg font-black tracking-tight text-[#e11d48]">{{ siteName || 'RG BAZZER' }}</span>
          </div>
        </NuxtLink>

        <!-- Right Nav: Desktop -->
        <nav class="hidden md:flex items-center gap-4">
          <NuxtLink to="/products" class="text-sm font-semibold text-slate-700 hover:text-slate-900 transition">
            Topup
          </NuxtLink>
          <NuxtLink to="/contact" class="text-sm font-semibold text-slate-700 hover:text-slate-900 transition">
            Contact Us
          </NuxtLink>

          <!-- If Logged In -->
          <template v-if="showProtectedUi">
            <NuxtLink to="/account" class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition" :style="{ backgroundColor: themeColor }">
              <span>💳</span>
              <span>{{ walletBalance }}৳</span>
            </NuxtLink>

            <button type="button" class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-slate-100 ring-2 ring-slate-200 transition" @click="toggleMenu">
              <img :src="userAvatarSrc" alt="User" class="h-full w-full object-cover" referrerpolicy="no-referrer" />
            </button>
          </template>

          <!-- If Guest -->
          <template v-else>
            <NuxtLink to="/login" class="inline-flex items-center justify-center rounded-none px-4 py-1.5 text-[13.5px] font-bold text-white transition hover:opacity-90 shadow-sm" :style="{ backgroundColor: themeColor }">
              Login
            </NuxtLink>
          </template>
        </nav>

        <!-- Right Nav: Mobile -->
        <div class="flex md:hidden items-center gap-2">
          <template v-if="showProtectedUi">
            <NuxtLink to="/account" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold text-white shadow-sm" :style="{ backgroundColor: themeColor }">
              <span>💳</span>
              <span>{{ walletBalance }}৳</span>
            </NuxtLink>

            <button type="button" class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-200" @click="toggleMenu">
              <img :src="userAvatarSrc" alt="User" class="h-full w-full object-cover" referrerpolicy="no-referrer" />
            </button>
          </template>

          <template v-else>
            <NuxtLink to="/login" class="inline-flex items-center justify-center rounded-none px-3.5 py-1 text-[13px] font-bold text-white shadow-sm" :style="{ backgroundColor: themeColor }">
              Login
            </NuxtLink>
          </template>
        </div>
      </div>

      <!-- Profile Menu Dropdown -->
      <div v-if="showProfileMenu" class="fixed inset-0 z-40 bg-black/10" @click="closeMenu"></div>
      <div v-if="showProfileMenu" class="absolute right-4 top-[65px] z-50 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-2xl md:right-8">
        <div class="mb-3 border-b border-slate-100 pb-3">
          <div class="text-sm font-bold text-slate-900">{{ (auth.user?.value || auth.user)?.name || 'User' }}</div>
          <div class="text-xs text-slate-500">{{ (auth.user?.value || auth.user)?.email || '' }}</div>
        </div>
        <div class="space-y-1 text-sm font-medium">
          <NuxtLink to="/add-money" class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 font-bold text-[#0d682f] transition hover:bg-emerald-100" @click="closeMenu">
            <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            <span>Add Money</span>
          </NuxtLink>
          <NuxtLink to="/account" class="block rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Account</NuxtLink>
          <NuxtLink to="/orders" class="block rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Orders</NuxtLink>
          <NuxtLink to="/codes" class="block rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Codes</NuxtLink>
          <NuxtLink to="/transactions" class="block rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Transaction</NuxtLink>
          <NuxtLink to="/contact" class="block rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">Contact Us</NuxtLink>
          <button type="button" class="block w-full rounded-lg px-3 py-2 text-left text-rose-500 transition hover:bg-rose-50" @click="logout">Logout</button>
        </div>
      </div>
    </header>

    <!-- PAGE CONTENT -->
    <main class="flex-1 pt-[62px] md:pt-[68px] pb-[64px] md:pb-0">
      <slot />
    </main>

    <!-- FOOTER -->
    <footer class="pb-20 pt-8 md:pb-8" :style="{ backgroundColor: footerBgColor, color: footerTextColor }">
      <div class="mx-auto max-w-6xl px-5 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
          
          <!-- Column 1: STAY CONNECTED -->
          <div v-if="stayConnectedMessage || hasSocialLinks">
            <h4 class="text-sm font-black tracking-wider uppercase" :style="{ color: footerTextColor }">STAY CONNECTED</h4>
            <p v-if="stayConnectedMessage" class="mt-2 text-xs leading-relaxed opacity-80">
              {{ stayConnectedMessage }}
            </p>
            <div v-if="hasSocialLinks" class="mt-3 flex items-center gap-2.5">
              <a v-if="socialFacebookUrl" :href="socialFacebookUrl" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="Facebook">
                <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              <a v-if="socialInstagramUrl" :href="socialInstagramUrl" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="Instagram">
                <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
              </a>
              <a v-if="socialYoutubeUrl" :href="socialYoutubeUrl" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="YouTube">
                <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
              </a>
              <a v-if="socialEmail" :href="`mailto:${socialEmail}`" class="flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="Email">
                <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
              </a>
            </div>
          </div>

          <!-- Column 2: SUPPORT CENTER -->
          <div v-if="hasSupportCards">
            <h4 class="text-sm font-black tracking-wider uppercase" :style="{ color: footerTextColor }">SUPPORT CENTER</h4>
            <div class="mt-3 space-y-2.5">
              <a v-if="showSupportWhatsapp" :href="globalWhatsappUrl" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-white/15 bg-white/5 p-2.5 text-white transition hover:bg-white/10">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/40">
                  <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.66 15l-1.1 4 4.1-1.08A10 10 0 1 0 12 2Zm5.1 13.26c-.22.62-1.28 1.2-1.77 1.28s-1.12.11-1.81-.11a15 15 0 0 1-4.1-1.81 13.7 13.7 0 0 1-2.53-3.06 4 4 0 0 1-.84-2.16A2.33 2.33 0 0 1 6.8 7.3a.83.83 0 0 1 .6-.28h.43c.14 0 .34-.05.53.4s.66 1.62.72 1.74a.43.43 0 0 1 0 .42c-.06.12-.1.2-.2.3s-.2.22-.3.34-.2.2-.08.42a7.06 7.06 0 0 0 1.3 1.6A5.84 5.84 0 0 0 11.52 13c.23.12.36.1.5-.06s.58-.67.73-.9.3-.2.5-.12 1.29.6 1.51.7.38.16.44.24.06.44-.16 1.06Z"/></svg>
                </div>
                <div class="border-l border-white/20 pl-2.5">
                  <div class="text-[11px] font-bold text-white">Help line [9AM-12PM]</div>
                  <div class="text-[10px] text-slate-300">Whatsapp HelpLine</div>
                </div>
              </a>

              <a v-if="showSupportGroup" :href="globalGroupUrl" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-white/15 bg-white/5 p-2.5 text-white transition hover:bg-white/10">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-white/40">
                  <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24"><path d="M21.4 4.6a1 1 0 0 0-1-.14L3.7 11.1a1 1 0 0 0 .06 1.86l4.3 1.6 1.6 4.3a1 1 0 0 0 1.86.06l6.64-16.7a1 1 0 0 0-.14-1Z"/></svg>
                </div>
                <div class="border-l border-white/20 pl-2.5">
                  <div class="text-[11px] font-bold text-white">Help line [9AM-12PM]</div>
                  <div class="text-[10px] text-slate-300">টেলিগ্রামে সাপোর্ট</div>
                </div>
              </a>
            </div>
          </div>

        </div>

        <!-- Copyright -->
        <div class="mt-8 border-t border-white/10 pt-6 text-center text-[11.5px] opacity-70">
          © {{ siteName || 'RG BAZZER' }} 2026 | All Rights Reserved | Developed by <span class="font-bold">Team Mahal</span>
        </div>
      </div>
    </footer>

    <!-- BOTTOM MOBILE NAVIGATION (matching rgbazer.com) -->
    <nav class="fixed inset-x-0 bottom-0 z-40 h-[58px] border-t border-slate-200 bg-white shadow-[0_-2px_10px_rgba(0,0,0,0.03)] md:hidden">
      <!-- If Logged In: 5 Items -->
      <div v-if="showProtectedUi" class="relative mx-auto grid h-full max-w-lg grid-cols-5 items-center text-center text-[10px] font-semibold">
        <NuxtLink to="/" class="flex flex-col items-center gap-0.5" :style="{ color: themeColor }">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1V9.5z"/>
          </svg>
          <span class="font-bold">Home</span>
        </NuxtLink>

        <NuxtLink to="/orders" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          <span>My Orders</span>
        </NuxtLink>

        <!-- Elevated Add Money Button -->
        <NuxtLink to="/add-money" class="flex flex-col items-center justify-end text-slate-600">
          <div class="absolute -top-4 flex h-12 w-12 items-center justify-center rounded-full text-white shadow-md border-[3px] border-white" :style="{ backgroundColor: themeColor }">
            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-white">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
          </div>
          <span class="mt-6 text-[10px] font-semibold">Add Money</span>
        </NuxtLink>

        <NuxtLink to="/codes" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
          </svg>
          <span>My Codes</span>
        </NuxtLink>

        <NuxtLink to="/account" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          <span>My Account</span>
        </NuxtLink>
      </div>

      <!-- If Guest: 4 Items -->
      <div v-else class="mx-auto grid h-full max-w-lg grid-cols-4 items-center text-center text-[10px] font-semibold">
        <NuxtLink to="/" class="flex flex-col items-center gap-0.5" :style="{ color: themeColor }">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1V9.5z"/>
          </svg>
          <span class="font-bold">Home</span>
        </NuxtLink>

        <NuxtLink to="/tutorial" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="15" rx="2"/><polygon points="10 8 16 11.5 10 15 10 8" fill="currentColor"/>
          </svg>
          <span>Tutorial</span>
        </NuxtLink>

        <NuxtLink to="/products" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
          </svg>
          <span>TopUp</span>
        </NuxtLink>

        <NuxtLink to="/contact" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
          </svg>
          <span>Contact Us</span>
        </NuxtLink>
      </div>
    </nav>
  </div>
</template>

<style scoped>
.themed-root {
  --theme-color: #0d682f;
  --body-bg: #f1f6fc;
}
</style>
