<script setup lang="ts">
const auth = useAuth();
const route = useRoute();
const showProfileMenu = ref(false);
const defaultManualAvatar = 'https://rgbazer.com/default.png';
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact');

const isAuthPage = computed(() => route.path === '/login' || route.path === '/register');
const showProtectedUi = computed(() => auth.isLoggedIn.value && !isAuthPage.value);

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
    // Silent fail to keep existing UI responsive when network is unstable.
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

  // Manual login or fallback always uses fixed default image to avoid flicker/replacement.
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
const themeColor = computed(() => {
  const raw = String(supportSettings.value?.theme_color || '').trim();
  return /^#[0-9a-fA-F]{6}$/.test(raw) ? raw.toLowerCase() : '';
});
const showSupportWhatsapp = computed(() => Boolean(supportSettings.value?.show_whatsapp) && Boolean(globalWhatsappUrl.value));
const showSupportGroup = computed(() => Boolean(supportSettings.value?.show_telegram) && Boolean(globalGroupUrl.value));
const hasSupportCards = computed(() => showSupportWhatsapp.value || showSupportGroup.value);
const themeStyles = computed(() => (themeColor.value ? { '--theme-color': themeColor.value } : {}));
const stayConnectedMessage = computed(() => String(supportSettings.value?.stay_connected_message || '').trim());
const socialFacebookUrl = computed(() => String(supportSettings.value?.social_facebook_url || '').trim());
const socialInstagramUrl = computed(() => String(supportSettings.value?.social_instagram_url || '').trim());
const socialYoutubeUrl = computed(() => String(supportSettings.value?.social_youtube_url || '').trim());
const socialEmail = computed(() => String(supportSettings.value?.social_email || '').trim());
const globalWhatsappUrl = computed(() => String(supportSettings.value?.support_center_whatsapp_url || '').trim());
const globalGroupUrl = computed(() => String(supportSettings.value?.support_center_group_url || '').trim());
const pgwAppEnabled = computed(() => Number(supportSettings.value?.pgw_app_enabled ?? 1) === 1);
const pgwForceInstallEnabled = computed(() => Number(supportSettings.value?.pgw_force_install_enabled ?? 0) === 1);
const hasSocialLinks = computed(() =>
  Boolean(socialFacebookUrl.value || socialInstagramUrl.value || socialYoutubeUrl.value || socialEmail.value)
);
const resolvedThemeColor = computed(() => themeColor.value || '#0f7134');

type InstallPromptChoice = { outcome?: 'accepted' | 'dismissed'; platform?: string };
type InstallPromptEvent = Event & { prompt: () => Promise<void>; userChoice: Promise<InstallPromptChoice> };

const installPromptEvent = ref<InstallPromptEvent | null>(null);
const showInstallPrompt = ref(false);
const isMobileViewport = ref(false);
const isStandaloneApp = ref(false);
const installPromptInProgress = ref(false);
const pwaInstalledPersisted = ref(false);
const forceInstallArmed = ref(false);
const inAppBrowserDetected = ref(false);
const pwaInstalledStorageKey = 'rgbazer-pwa-installed-v1';
const hasInstallPromptEvent = computed(() => Boolean(installPromptEvent.value));
const forceInstallCanIntercept = computed(() =>
  pgwAppEnabled.value &&
  pgwForceInstallEnabled.value &&
  !isStandaloneApp.value &&
  !pwaInstalledPersisted.value &&
  (hasInstallPromptEvent.value || inAppBrowserDetected.value)
);
const showForceOverlay = computed(() => pgwAppEnabled.value && pgwForceInstallEnabled.value && inAppBrowserDetected.value && forceInstallArmed.value && !isStandaloneApp.value && !pwaInstalledPersisted.value);
const showRegularInstallCard = computed(() => pgwAppEnabled.value && !pgwForceInstallEnabled.value && showInstallPrompt.value && !isStandaloneApp.value && !pwaInstalledPersisted.value);
const showForceInstallButton = computed(() => pgwAppEnabled.value && pgwForceInstallEnabled.value && hasInstallPromptEvent.value && !isStandaloneApp.value && !pwaInstalledPersisted.value);

useHead(() => ({
  title: siteName.value || 'TOPUP',
  link: appIconUrl.value ? [{ rel: 'icon', href: appIconUrl.value }] : [],
  meta: [
    { name: 'theme-color', content: resolvedThemeColor.value },
    { name: 'msapplication-TileColor', content: resolvedThemeColor.value }
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

function updateInstallPromptVisibility() {
  if (!process.client) return;
  showInstallPrompt.value = Boolean(installPromptEvent.value) &&
    pgwAppEnabled.value &&
    !pgwForceInstallEnabled.value &&
    !isStandaloneApp.value &&
    !pwaInstalledPersisted.value;
}

function dismissInstallPrompt() {
  if (!process.client) return;
  showInstallPrompt.value = false;
}

function isInAppBrowser() {
  if (!process.client) return false;
  const ua = navigator.userAgent || '';
  const iosWebView = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(ua);
  return iosWebView || /FBAN|FBAV|FB_IAB|Instagram|TikTok|musical_ly|WhatsApp|Line|Messenger|WebView|; wv\)|\bwv\b/i.test(ua);
}

function openCurrentPageInChrome() {
  if (!process.client) return;
  const currentUrl = new URL(window.location.href);
  const chromeScheme = `${currentUrl.protocol === 'https:' ? 'googlechromes' : 'googlechrome'}://${currentUrl.host}${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}`;
  const androidIntent = `intent://${currentUrl.host}${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}#Intent;scheme=${currentUrl.protocol.replace(':', '')};package=com.android.chrome;end`;
  const ua = navigator.userAgent || '';
  const isAndroid = /Android/i.test(ua);
  const isIOS = /iPhone|iPad|iPod/i.test(ua);

  if (isAndroid) {
    window.location.href = androidIntent;
    setTimeout(() => {
      window.location.href = chromeScheme;
    }, 500);
    return;
  }

  if (isIOS) {
    window.location.href = chromeScheme;
    return;
  }

  window.open(currentUrl.toString(), '_blank', 'noopener');
}

async function triggerInstallPrompt() {
  if (!installPromptEvent.value || installPromptInProgress.value || pwaInstalledPersisted.value) return;
  installPromptInProgress.value = true;
  showInstallPrompt.value = false;
  forceInstallArmed.value = false;
  try {
    await installPromptEvent.value.prompt();
    const choice = await installPromptEvent.value.userChoice;
    if (choice?.outcome === 'accepted') {
      pwaInstalledPersisted.value = true;
      localStorage.setItem(pwaInstalledStorageKey, '1');
      showInstallPrompt.value = false;
    } else {
      if (pgwForceInstallEnabled.value) {
        forceInstallArmed.value = true;
      } else {
        showInstallPrompt.value = true;
      }
    }
  } catch {
    if (pgwForceInstallEnabled.value) {
      forceInstallArmed.value = true;
    } else {
      showInstallPrompt.value = false;
    }
  } finally {
    installPromptInProgress.value = false;
    installPromptEvent.value = null;
  }
}

function handlePgwForceClick(event: MouseEvent | PointerEvent) {
  if (!process.client) return;
  if (!forceInstallCanIntercept.value) return;
  const target = event.target as HTMLElement | null;
  if (target && target.closest('[data-pgw-install-safe="true"]')) return;
  event.preventDefault();
  event.stopPropagation();
  forceInstallArmed.value = true;
  if (installPromptEvent.value) {
    triggerInstallPrompt();
    return;
  }
  openCurrentPageInChrome();
}

function openForceInstallPrompt() {
  if (!process.client) return;
  if (!pgwAppEnabled.value || !pgwForceInstallEnabled.value || isStandaloneApp.value || pwaInstalledPersisted.value) return;
  forceInstallArmed.value = true;
  if (installPromptEvent.value) {
    triggerInstallPrompt();
    return;
  }
  if (inAppBrowserDetected.value) {
    openCurrentPageInChrome();
  }
}

function syncStandaloneState() {
  if (!process.client) return;
  const isStandaloneByDisplayMode = window.matchMedia('(display-mode: standalone)').matches;
  const isStandaloneByNavigator = Boolean((navigator as any).standalone);
  isStandaloneApp.value = isStandaloneByDisplayMode || isStandaloneByNavigator;
  if (isStandaloneApp.value) {
    showInstallPrompt.value = false;
    forceInstallArmed.value = false;
  }
}

function syncInstalledState() {
  if (!process.client) return;
  pwaInstalledPersisted.value = localStorage.getItem(pwaInstalledStorageKey) === '1';
  if (pwaInstalledPersisted.value) {
    showInstallPrompt.value = false;
    forceInstallArmed.value = false;
  }
}

onMounted(() => {
  if (!process.client) return;

  inAppBrowserDetected.value = isInAppBrowser();
  syncInstalledState();
  const mq = window.matchMedia('(max-width: 767px)');
  const syncDevice = () => {
    isMobileViewport.value = mq.matches;
  };
  syncDevice();
  syncStandaloneState();
  if (typeof mq.addEventListener === 'function') mq.addEventListener('change', syncDevice);
  else mq.addListener(syncDevice);

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => undefined);
  }

  document.addEventListener('click', handlePgwForceClick, true);

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPromptEvent.value = event as InstallPromptEvent;
    syncStandaloneState();
    updateInstallPromptVisibility();
    if (pgwForceInstallEnabled.value && forceInstallArmed.value && !pwaInstalledPersisted.value) {
      triggerInstallPrompt();
    }
  });

  window.addEventListener('appinstalled', () => {
    pwaInstalledPersisted.value = true;
    localStorage.setItem(pwaInstalledStorageKey, '1');
    showInstallPrompt.value = false;
    forceInstallArmed.value = false;
    installPromptEvent.value = null;
    syncStandaloneState();
  });

  document.addEventListener('visibilitychange', syncStandaloneState);
});

watch([pgwAppEnabled, pgwForceInstallEnabled, pwaInstalledPersisted], () => {
  updateInstallPromptVisibility();
});
</script>

<template>
  <div class="themed-root relative flex min-h-screen flex-col justify-between bg-[#f1f6fc] text-slate-900" :style="themeStyles">
    <!-- HEADER -->
    <header class="fixed inset-x-0 top-0 z-50 h-[76px] border-b border-slate-200 bg-[#eef3fb]/95 backdrop-blur md:h-[84px]">
      <div class="mx-auto flex h-full max-w-6xl items-center justify-between px-3 py-2 lg:px-8 lg:py-3">
        <NuxtLink to="/" class="flex items-center gap-3" @click="closeMenu">
          <img v-if="headerLogoUrl" :src="headerLogoUrl" alt="Logo" class="h-9 w-auto sm:h-10 md:h-12" />
          <span v-else class="text-base font-black tracking-wide text-slate-900 sm:text-lg">{{ siteName || 'TOPUP' }}</span>
        </NuxtLink>

        <!-- Desktop Menu -->
        <nav class="hidden items-center gap-3 md:flex">
          <template v-if="showProtectedUi">
            <!-- Balance Chip -->
            <NuxtLink to="/account" class="themed-bg inline-flex items-center gap-2 rounded-full px-5 py-2 text-base font-semibold text-white shadow-[0_8px_24px_rgba(23,123,59,0.28)] transition">
              <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-white/50 text-[11px]">৳</span>
              <span>{{ walletBalance }}</span>
            </NuxtLink>

            <!-- Desktop Add Money Button -->
            <NuxtLink to="/add-money" class="themed-outline inline-flex items-center gap-1.5 rounded-full border bg-white px-4 py-2 text-sm font-bold shadow-sm transition">
              <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
              </svg>
              <span>Add Money</span>
            </NuxtLink>

            <!-- Profile Avatar Button -->
            <button type="button" class="themed-ring flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-white shadow-[0_4px_14px_rgba(15,23,42,0.12)] transition hover:ring-2" @click="toggleMenu">
              <img :src="userAvatarSrc" alt="User" class="pointer-events-none h-full w-full object-cover" referrerpolicy="no-referrer" />
            </button>
          </template>
          <NuxtLink v-else to="/login" class="themed-bg rounded-lg px-5 py-2 font-semibold text-white transition">Login</NuxtLink>
        </nav>

        <!-- Mobile Header Items -->
        <div class="flex items-center gap-2 md:hidden">
          <template v-if="showProtectedUi">
            <NuxtLink to="/account" class="themed-bg inline-flex items-center gap-2 rounded-full px-4 py-2 text-[14px] font-semibold text-white shadow-[0_8px_18px_rgba(23,123,59,0.22)]">
              <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-white/50 text-[11px]">৳</span>
              <span>{{ walletBalance }}</span>
            </NuxtLink>
            <button type="button" class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-white shadow-[0_4px_14px_rgba(15,23,42,0.12)]" @click="toggleMenu">
              <img :src="userAvatarSrc" alt="User" class="pointer-events-none h-full w-full object-cover" referrerpolicy="no-referrer" />
            </button>
          </template>
          <NuxtLink v-else to="/login" class="themed-bg rounded-md px-5 py-2 text-[14px] font-semibold text-white shadow-[0_8px_18px_rgba(23,123,59,0.22)]">
            Login
          </NuxtLink>
        </div>
      </div>

      <!-- Backdrop Overlay for closing profile menu -->
      <div v-if="showProfileMenu" class="fixed inset-0 z-40 bg-black/10" @click="closeMenu"></div>

      <!-- Dropdown Profile Menu -->
      <div v-if="showProfileMenu" class="absolute right-4 top-[80px] z-50 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-2xl md:right-8 md:top-[88px]">
        <div class="mb-3 border-b border-slate-200 pb-3">
          <div class="text-sm font-semibold text-slate-900">{{ (auth.user?.value || auth.user)?.name || 'Guest' }}</div>
          <div class="text-xs text-slate-500">{{ (auth.user?.value || auth.user)?.email || 'Welcome back' }}</div>
        </div>
        <div class="space-y-1 text-sm font-medium">
          <!-- Add Money Option inside Dropdown -->
          <NuxtLink to="/add-money" class="themed-soft-btn flex items-center gap-2 rounded-xl px-3 py-2 font-bold transition" @click="closeMenu">
            <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            <span>Add Money</span>
          </NuxtLink>
          <NuxtLink to="/account" class="block rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Account</NuxtLink>
          <NuxtLink to="/orders" class="block rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Orders</NuxtLink>
          <NuxtLink to="/codes" class="block rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Codes</NuxtLink>
          <NuxtLink to="/transactions" class="block rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">My Transaction</NuxtLink>
          <NuxtLink to="/contact" class="block rounded-xl px-3 py-2 text-slate-700 transition hover:bg-slate-100" @click="closeMenu">Contact Us</NuxtLink>
          <button type="button" class="block w-full rounded-xl px-3 py-2 text-left text-rose-500 transition hover:bg-slate-100" @click="logout">Logout</button>
        </div>
      </div>
    </header>

    <!-- PAGE MAIN CONTENT -->
    <main class="flex-1 pt-[76px] md:pt-[84px]">
      <slot />
    </main>

    <!-- FOOTER / SUPPORT CENTER -->
    <footer class="mt-8 bg-[#030a2f] pb-[90px] pt-8 text-white md:pb-8">
      <div class="mx-auto max-w-2xl space-y-8 px-5">
        
        <!-- STAY CONNECTED -->
        <div v-if="stayConnectedMessage || hasSocialLinks">
          <h3 class="text-lg font-black tracking-wide text-white uppercase">STAY CONNECTED</h3>
          <p v-if="stayConnectedMessage" class="mt-2 text-[12px] leading-relaxed text-slate-300">
            {{ stayConnectedMessage }}
          </p>
          <div v-if="hasSocialLinks" class="mt-4 flex items-center gap-3">
            <a v-if="socialFacebookUrl" :href="socialFacebookUrl" target="_blank" rel="noopener" class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="Facebook">
              <svg class="h-5 w-5 fill-white" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
            <a v-if="socialInstagramUrl" :href="socialInstagramUrl" target="_blank" rel="noopener" class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="Instagram">
              <svg class="h-5 w-5 fill-white" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </a>
            <a v-if="socialYoutubeUrl" :href="socialYoutubeUrl" target="_blank" rel="noopener" class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="YouTube">
              <svg class="h-5 w-5 fill-white" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </a>
            <a v-if="socialEmail" :href="`mailto:${socialEmail}`" class="flex h-11 w-11 items-center justify-center rounded-xl border border-white/20 bg-white/5 transition hover:bg-white/10" aria-label="Email">
              <svg class="h-5 w-5 fill-white" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </a>
          </div>
        </div>

        <!-- SUPPORT CENTER CARDS -->
        <div v-if="hasSupportCards">
          <h3 class="text-lg font-black tracking-wide text-white uppercase">SUPPORT CENTER</h3>
          <div class="mt-4 space-y-3">
            <!-- Whatsapp Card -->
            <a v-if="showSupportWhatsapp" :href="globalWhatsappUrl" target="_blank" rel="noopener" class="flex items-center gap-3.5 rounded-lg border border-white/25 bg-white/5 p-3 text-white transition hover:bg-white/10">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/50">
                <svg class="h-5 w-5 fill-white" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.66 15l-1.1 4 4.1-1.08A10 10 0 1 0 12 2Zm5.1 13.26c-.22.62-1.28 1.2-1.77 1.28s-1.12.11-1.81-.11a15 15 0 0 1-4.1-1.81 13.7 13.7 0 0 1-2.53-3.06 4 4 0 0 1-.84-2.16A2.33 2.33 0 0 1 6.8 7.3a.83.83 0 0 1 .6-.28h.43c.14 0 .34-.05.53.4s.66 1.62.72 1.74a.43.43 0 0 1 0 .42c-.06.12-.1.2-.2.3s-.2.22-.3.34-.2.2-.08.42a7.06 7.06 0 0 0 1.3 1.6A5.84 5.84 0 0 0 11.52 13c.23.12.36.1.5-.06s.58-.67.73-.9.3-.2.5-.12 1.29.6 1.51.7.38.16.44.24.06.44-.16 1.06Z"/></svg>
              </div>
              <div class="border-l border-white/20 pl-3">
                <div class="text-[12px] font-bold text-white">Help line [9AM-12PM]</div>
                <div class="text-[11px] text-slate-300">Whatsapp HelpLine</div>
              </div>
            </a>

            <!-- Telegram Card -->
            <a v-if="showSupportGroup" :href="globalGroupUrl" target="_blank" rel="noopener" class="flex items-center gap-3.5 rounded-lg border border-white/25 bg-white/5 p-3 text-white transition hover:bg-white/10">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-white/50">
                <svg class="h-5 w-5 fill-white" viewBox="0 0 24 24"><path d="M21.4 4.6a1 1 0 0 0-1-.14L3.7 11.1a1 1 0 0 0 .06 1.86l4.3 1.6 1.6 4.3a1 1 0 0 0 1.86.06l6.64-16.7a1 1 0 0 0-.14-1Z"/></svg>
              </div>
              <div class="border-l border-white/20 pl-3">
                <div class="text-[12px] font-bold text-white">Help line [9AM-12PM]</div>
                <div class="text-[11px] text-slate-300">টেলিগ্রামে সাপোর্ট</div>
              </div>
            </a>
          </div>
        </div>

        <!-- COPYRIGHT -->
        <div class="border-t border-white/10 pt-6 text-center text-[12px] text-slate-400">
          ©  2026 | All Rights Reserved | Developed by <span class="font-bold text-white"></span>
        </div>

      </div>
    </footer>

    <div
      v-if="showForceOverlay"
      class="install-force-overlay"
      role="dialog"
      aria-modal="true"
      aria-label="Install App"
      data-pgw-install-safe="true"
    >
      <div class="install-force-card">
        <div class="install-force-header">
          <img v-if="primaryLogoUrl || siteIconUrl" :src="primaryLogoUrl || siteIconUrl" alt="Logo" class="install-force-logo" />
          <div>
            <div class="install-force-title">Install App</div>
            <div class="install-force-brand">{{ siteName || 'GHOSTBAZAR' }}</div>
          </div>
        </div>
        <div class="install-sub">Install our app for a better experience</div>
        <button type="button" class="install-btn mt-4" data-pgw-install-safe="true" @click="openForceInstallPrompt">Install Now</button>
        <div class="install-support-grid">
          <a
            v-if="globalWhatsappUrl"
            :href="globalWhatsappUrl"
            target="_blank"
            rel="noopener"
            class="install-support-btn"
            data-pgw-install-safe="true"
          >
            WhatsApp Support
          </a>
          <a
            v-if="globalGroupUrl"
            :href="globalGroupUrl"
            target="_blank"
            rel="noopener"
            class="install-support-btn"
            data-pgw-install-safe="true"
          >
            Telegram Support
          </a>
        </div>
      </div>
    </div>

    <button
      v-if="showForceInstallButton"
      type="button"
      class="force-install-fab"
      data-pgw-install-safe="true"
      @click="openForceInstallPrompt"
    >
      PGW APP
    </button>

    <div
      v-else-if="showRegularInstallCard"
      :class="isMobileViewport ? 'install-app-mobile' : 'install-app-desktop'"
      role="dialog"
      aria-label="Install App"
      data-pgw-install-safe="true"
    >
      <button type="button" class="install-close-btn" aria-label="Close install prompt" data-pgw-install-safe="true" @click="dismissInstallPrompt">×</button>
      <div class="install-label">Install App</div>
      <div class="install-sub">Install our app for a better experience</div>
      <button type="button" class="install-btn" data-pgw-install-safe="true" @click="triggerInstallPrompt">Install Now</button>
    </div>

    <!-- BOTTOM MOBILE NAVIGATION BAR -->
    <nav class="fixed inset-x-0 bottom-0 z-40 h-[64px] border-t border-slate-200 bg-white shadow-[0_-4px_16px_rgba(0,0,0,0.06)] md:hidden">
      <div v-if="showProtectedUi" class="relative mx-auto grid h-full max-w-lg grid-cols-5 items-center text-center text-[10px] font-semibold">
        <NuxtLink to="/" class="themed-text flex flex-col items-center gap-0.5">
          <svg viewBox="0 0 24 24" class="themed-stroke h-5 w-5 fill-none stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1V9.5z"/>
          </svg>
          <span>Home</span>
        </NuxtLink>

        <NuxtLink to="/orders" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
          <span>My Orders</span>
        </NuxtLink>

        <NuxtLink to="/add-money" class="flex flex-col items-center justify-end text-slate-500">
          <div class="themed-bg absolute -top-5 flex h-12 w-12 items-center justify-center rounded-full border-[3px] border-white text-white shadow-md">
            <svg viewBox="0 0 24 24" class="h-6 w-6 fill-white">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
          </div>
          <span class="mt-6 text-[10px]">Add Money</span>
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

      <div v-else class="mx-auto grid h-full max-w-lg grid-cols-4 items-center text-center text-[10px] font-semibold">
        <NuxtLink to="/" class="themed-text flex flex-col items-center gap-0.5">
          <svg viewBox="0 0 24 24" class="themed-stroke h-5 w-5 fill-none stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1V9.5z"/>
          </svg>
          <span>Home</span>
        </NuxtLink>

        <NuxtLink to="/products" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
          </svg>
          <span>TopUp</span>
        </NuxtLink>

        <NuxtLink to="/contact" class="flex flex-col items-center gap-0.5 text-slate-500 hover:text-slate-800">
          <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <span>Contact</span>
        </NuxtLink>

        <NuxtLink to="/login" class="themed-text flex flex-col items-center gap-0.5">
          <svg viewBox="0 0 24 24" class="themed-stroke h-5 w-5 fill-none stroke-[2]" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
          </svg>
          <span>Login</span>
        </NuxtLink>
      </div>
    </nav>
  </div>
</template>

<style scoped>
.themed-root {
  --theme-color: #0f7134;
}
.themed-bg {
  background-color: var(--theme-color);
}
.themed-text {
  color: var(--theme-color);
}
.themed-stroke {
  stroke: var(--theme-color);
}
.themed-outline {
  border-color: var(--theme-color);
  color: var(--theme-color);
}
.themed-outline:hover {
  background-color: var(--theme-color);
  color: #fff;
}
.themed-ring:hover {
  --tw-ring-color: var(--theme-color);
}
.themed-soft-btn {
  color: var(--theme-color);
  background-color: rgba(255, 255, 255, 0.9);
  border: 1px solid var(--theme-color);
}
.themed-soft-btn:hover {
  background-color: var(--theme-color);
  color: #fff;
}

.install-app-mobile {
  position: fixed;
  left: 10px;
  right: 10px;
  bottom: 72px;
  z-index: 60;
  border-radius: 12px;
  padding: 11px 13px 12px;
  background: var(--theme-color);
  color: #fff;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.26);
}

.install-app-desktop {
  position: fixed;
  right: 14px;
  bottom: 14px;
  z-index: 60;
  width: 265px;
  border-radius: 8px;
  padding: 11px 12px 12px;
  background: var(--theme-color);
  color: #fff;
  box-shadow: 0 12px 26px rgba(15, 23, 42, 0.3);
}

.install-close-btn {
  position: absolute;
  top: 6px;
  right: 8px;
  width: 22px;
  height: 22px;
  border-radius: 9999px;
  border: none;
  background: transparent;
  color: rgba(255, 255, 255, 0.85);
  font-size: 18px;
  line-height: 1;
  cursor: pointer;
}

.install-label {
  font-size: 14px;
  font-weight: 800;
}

.install-sub {
  margin-top: 3px;
  font-size: 12px;
  opacity: 0.92;
  padding-right: 18px;
}

.install-btn {
  margin-top: 9px;
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 8px;
  padding: 8px 10px;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.install-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.install-force-overlay {
  position: fixed;
  inset: 0;
  z-index: 70;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(2, 6, 23, 0.72);
  backdrop-filter: blur(10px);
  padding: 20px;
}

.install-force-card {
  width: min(420px, 100%);
  border-radius: 18px;
  background: var(--theme-color);
  color: #fff;
  padding: 18px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32);
}

.install-force-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.install-force-logo {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  object-fit: cover;
  background: rgba(255, 255, 255, 0.16);
}

.install-force-title {
  font-size: 18px;
  font-weight: 900;
  line-height: 1.1;
}

.install-force-brand {
  margin-top: 2px;
  font-size: 12px;
  opacity: 0.92;
}

.install-support-grid {
  margin-top: 12px;
  display: grid;
  gap: 8px;
}

.install-support-btn {
  display: block;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.44);
  padding: 10px 12px;
  text-align: center;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.12);
}

.install-support-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.force-install-fab {
  position: fixed;
  right: 14px;
  bottom: 92px;
  z-index: 65;
  border: 0;
  border-radius: 9999px;
  padding: 10px 14px;
  background: var(--theme-color);
  color: #fff;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: 0.08em;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.24);
}

.force-install-fab:hover {
  filter: brightness(0.95);
}
</style>
