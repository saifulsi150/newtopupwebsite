<script setup lang="ts">
const { data: homeSettingsData } = await useFetch<any>('/api/settings/home');
const { data: contactSettingsData } = await useFetch<any>('/api/settings/contact');

const homeSettings = computed(() => homeSettingsData.value?.home || {});
const contactSettings = computed(() => contactSettingsData.value?.contact || {});

const videoUrl = computed(() => {
  return homeSettings.value?.tutorialVideoLink || 'https://www.youtube.com';
});
</script>

<template>
  <div class="tutorial-page">
    <h1 class="page-title">Tutorial & Guide</h1>
    
    <div class="tutorial-card">
      <p class="tutorial-text">
        কিভাবে ওয়েবসাইটে অর্ডার করবেন এবং টপআপ নিবেন তার নিয়মাবলী নিচে দেওয়া হলো:
      </p>
      
      <div class="video-container">
        <iframe
          v-if="videoUrl && videoUrl.includes('youtube')"
          :src="videoUrl.replace('watch?v=', 'embed/')"
          title="Tutorial Video"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        ></iframe>
        <div v-else class="video-placeholder">
          <p>টিউটোরিয়াল ভিডিও শীঘ্রই যোগ করা হবে।</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tutorial-page {
  max-width: 800px;
  margin: 0 auto;
  padding: 16px 14px 24px;
}

.page-title {
  text-align: center;
  font-size: 22px;
  font-weight: 800;
  color: #17395c;
  margin-bottom: 16px;
}

.tutorial-card {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 16px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.tutorial-text {
  font-size: 13.5px;
  color: #334155;
  margin-bottom: 14px;
  line-height: 1.5;
}

.video-container {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%;
  height: 0;
  overflow: hidden;
  border-radius: 8px;
  background: #0f172a;
}

.video-container iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.video-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 14px;
}
</style>
