<script setup lang="ts">
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact', { server: false });

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
}
</style>
