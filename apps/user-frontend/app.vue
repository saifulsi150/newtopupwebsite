<script setup lang="ts">
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact', { server: false });

const themeColor = computed(() => {
  const raw = String(contactSettingsData.value?.contact?.theme_color || '').trim();
  return /^#[0-9a-fA-F]{6}$/.test(raw) ? raw.toLowerCase() : '#0a6b2a';
});

// Full page clean white curtain on initial mount
const pageReady = ref(false);

onMounted(() => {
  if (process.client) {
    setTimeout(() => {
      pageReady.value = true;
    }, 200);
  }
});

useHead(() => {
  const iconUrl = contactSettingsData.value?.contact?.site_icon_url;
  return {
    titleTemplate: (titleChunk) => {
      const siteName = contactSettingsData.value?.contact?.site_name || 'RG BAZZER';
      return titleChunk ? `${titleChunk} - ${siteName}` : siteName;
    },
    link: iconUrl ? [{ rel: 'icon', href: iconUrl }] : []
  };
});
</script>

<template>
  <!-- Top loading progress bar during background page fetching -->
  <NuxtLoadingIndicator :color="themeColor" :height="3" :duration="2500" :throttle="0" />

  <!-- Initial clean white curtain to prevent flash during hard refresh -->
  <Transition name="fade-curtain">
    <div v-if="!pageReady" class="page-white-curtain"></div>
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
  margin: 0;
  padding: 0;
  -webkit-tap-highlight-color: transparent;
  background-color: #f1f6fc;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.page-white-curtain {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background-color: #ffffff;
}

.fade-curtain-leave-active {
  transition: opacity 0.2s ease-out;
}
.fade-curtain-leave-to {
  opacity: 0;
}

/* Smooth background-fetch page transitions like rgbazer.com */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.22s ease-in-out;
}
.page-enter-from,
.page-leave-to {
  opacity: 0;
}
</style>
