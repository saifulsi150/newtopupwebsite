<script setup lang="ts">
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact', { server: false });

const themeColor = computed(() => {
  const raw = String(contactSettingsData.value?.contact?.theme_color || '').trim();
  return /^#[0-9a-fA-F]{6}$/.test(raw) ? raw.toLowerCase() : '#0a6b2a';
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
  <!-- Page transition top progress bar with theme color -->
  <NuxtLoadingIndicator :color="themeColor" :height="3" :throttle="0" />

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
</style>
