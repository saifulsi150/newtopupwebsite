<script setup lang="ts">
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact', { server: false });
const loadingColor = computed(() => {
  const raw = String(contactSettingsData.value?.contact?.theme_color || '').trim();
  return /^#[0-9a-fA-F]{6}$/.test(raw) ? raw.toLowerCase() : '#0f7134';
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
  <NuxtLoadingIndicator :color="loadingColor" :height="3" :throttle="240" />
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>
