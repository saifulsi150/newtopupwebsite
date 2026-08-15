<script setup lang="ts">
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact', { server: false });
const loadingColor = computed(() => {
  const raw = String(contactSettingsData.value?.contact?.theme_color || '').trim();
  return /^#[0-9a-fA-F]{6}$/.test(raw) ? raw.toLowerCase() : '#0f7134';
});

// Initial page loading splash
const appReady = ref(false);
onMounted(() => {
  // Remove initial loading splash after a short delay
  requestAnimationFrame(() => {
    setTimeout(() => {
      appReady.value = true;
    }, 80);
  });
});

useHead(() => {
  const iconUrl = contactSettingsData.value?.contact?.site_icon_url;
  return {
    titleTemplate: (titleChunk) => {
      const siteName = contactSettingsData.value?.contact?.site_name || 'TAST Topup';
      return titleChunk ? `${titleChunk} - ${siteName}` : siteName;
    },
    link: iconUrl ? [{ rel: 'icon', href: iconUrl }] : []
  };
});
</script>

<template>
  <!-- Page transition loading bar -->
  <NuxtLoadingIndicator :color="loadingColor" :height="3" :throttle="0" />

  <!-- Initial app splash (white screen fade-out) -->
  <Transition name="splash-fade">
    <div v-if="!appReady" class="app-splash">
      <div class="splash-spinner" :style="{ borderTopColor: loadingColor }"></div>
    </div>
  </Transition>

  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>

<style>
/* Global reset */
*, *::before, *::after {
  box-sizing: border-box;
}

body {
  -webkit-tap-highlight-color: transparent;
}

/* Initial splash screen */
.app-splash {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.splash-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e8ecef;
  border-top-color: #0f7134;
  border-radius: 50%;
  animation: splash-spin 0.7s linear infinite;
}

@keyframes splash-spin {
  to { transform: rotate(360deg); }
}

.splash-fade-enter-active {
  transition: opacity 0.2s ease;
}
.splash-fade-leave-active {
  transition: opacity 0.35s ease;
}
.splash-fade-enter-from,
.splash-fade-leave-to {
  opacity: 0;
}

/* Smooth page transitions */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s ease;
}
.page-enter-from,
.page-leave-to {
  opacity: 0;
}
</style>
