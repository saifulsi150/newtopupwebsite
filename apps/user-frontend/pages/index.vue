<script setup lang="ts">
import { ref, computed } from 'vue'

type Product = {
  id: number;
  title: string;
  slug: string;
  image_url: string;
  price_from: number;
  category_title?: string;
};

const { data, pending, error } = await useFetch<{ products: Product[] }>('/api/home', {
  key: 'home-products-v2'
});
const { data: categoriesData } = await useFetch<{ categories: Array<{ id: number; name: string }> }>('/api/categories', {
  key: 'home-categories-v1'
});
const { data: homeSettingsData } = await useFetch<any>('/api/settings/home', {
  key: 'home-settings-v1'
});

const products = computed(() => data.value?.products || []);
const categories = computed(() => categoriesData.value?.categories || []);
const homeSettings = computed(() => homeSettingsData.value?.home || {});
const sliderItems = computed(() => {
  const list = Array.isArray(homeSettings.value?.sliderItems) ? homeSettings.value.sliderItems : [];
  return list.filter((item: any) => Boolean(item?.enabled));
});
const topSupportButtons = computed(() => {
  const list = Array.isArray(homeSettings.value?.topSupportButtons) ? homeSettings.value.topSupportButtons : [];
  return list.filter((item: any) => Boolean(item?.enabled));
});
const hasTopSupportButtons = computed(() => topSupportButtons.value.length > 0);
const noticeText = computed(() => {
  return String(homeSettings.value?.notice || '').trim();
});
const categoryHeading = computed(() => {
  const fromCategory = String(categories.value[0]?.name || '').trim();
  if (fromCategory) return fromCategory;
  return String(products.value[0]?.category_title || '').trim();
});
const noticeDismissed = ref(false);
const homePopupStorageKey = 'rgbazer-home-popup-state-v1';
const detectPopupDismissStorageKey = 'rgbazer-detect-popup-dismiss-v1';
const pwaInstalledStorageKey = 'rgbazer-pwa-installed-v1';
const homePopupVisible = ref(false);
const homePopupActive = ref<any | null>(null);
const detectGateVisible = ref(false);

const pagePopupEnabled = computed(() => Boolean(homeSettings.value?.pagePopupEnabled));
const pagePopupLimitPerDay = computed(() => Math.max(1, Number(homeSettings.value?.pagePopupLimitPerDay || 5)));
const detectPopupEnabled = computed(() => Boolean(homeSettings.value?.detectPopupEnabled));
const pagePopupItems = computed(() => {
  const list = Array.isArray(homeSettings.value?.pagePopupItems) ? homeSettings.value.pagePopupItems : [];
  return list
    .map((item: any) => ({
      title: String(item?.title || '').trim(),
      imageUrl: String(item?.imageUrl ?? item?.image_url ?? '').trim(),
      note: String(item?.note ?? item?.text ?? item?.description ?? '').trim(),
      buttonLabel: String(item?.buttonLabel ?? item?.button_label ?? 'Click Here').trim() || 'Click Here',
      buttonUrl: String(item?.buttonUrl ?? item?.button_url ?? '').trim(),
      closeLabel: String(item?.closeLabel ?? item?.close_label ?? 'CLOSE').trim() || 'CLOSE',
      closeImageUrl: String(item?.closeImageUrl ?? item?.close_image_url ?? '').trim(),
      enabled: Boolean(item?.enabled ?? item?.status ?? true)
    }))
    .filter((item: any) => item.enabled && (item.title || item.imageUrl || item.note || item.buttonUrl));
});

function dismissNotice() {
  noticeDismissed.value = true;
}

function getHomePopupDateKey() {
  return new Date().toISOString().slice(0, 10);
}

function readHomePopupState() {
  if (!process.client) return { dateKey: '', count: 0 };
  const raw = localStorage.getItem(homePopupStorageKey);
  if (!raw) return { dateKey: '', count: 0 };
  try {
    const parsed = JSON.parse(raw);
    return {
      dateKey: String(parsed?.dateKey || ''),
      count: Math.max(0, Number(parsed?.count || 0))
    };
  } catch {
    return { dateKey: '', count: 0 };
  }
}

function writeHomePopupState(state: { dateKey: string; count: number }) {
  if (!process.client) return;
  localStorage.setItem(homePopupStorageKey, JSON.stringify(state));
}

function syncHomePopup() {
  if (!process.client) return;
  homePopupVisible.value = false;
  homePopupActive.value = null;

  const items = pagePopupItems.value;
  if (!pagePopupEnabled.value || !items.length) return;

  const todayKey = getHomePopupDateKey();
  const stored = readHomePopupState();
  const state = stored.dateKey === todayKey
    ? stored
    : { dateKey: todayKey, count: 0 };

  if (state.count >= pagePopupLimitPerDay.value) {
    writeHomePopupState(state);
    return;
  }

  const index = state.count % items.length;
  homePopupActive.value = items[index];
  homePopupVisible.value = true;
  writeHomePopupState(state);
}

function markHomePopupSeen() {
  if (!process.client || !homePopupActive.value) return;
  const todayKey = getHomePopupDateKey();
  const stored = readHomePopupState();
  const state = stored.dateKey === todayKey
    ? stored
    : { dateKey: todayKey, count: 0 };
  state.count += 1;
  writeHomePopupState(state);
  homePopupVisible.value = false;
  homePopupActive.value = null;
}

function dismissHomePopup() {
  markHomePopupSeen();
}

function handleHomePopupAction() {
  markHomePopupSeen();
}

function isInAppBrowser() {
  if (!process.client) return false;
  const ua = navigator.userAgent || '';
  return /FBAN|FBAV|Instagram|TikTok|musical_ly|WhatsApp|Line|wv/i.test(ua);
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

function handlePopupCardClick() {
  if (detectPopupEnabled.value) {
    openCurrentPageInChrome();
  }
}

function readDetectDismissedToday() {
  if (!process.client) return false;
  const today = getHomePopupDateKey();
  return localStorage.getItem(detectPopupDismissStorageKey) === today;
}

function isPwaInstalledFlagged() {
  if (!process.client) return false;
  return localStorage.getItem(pwaInstalledStorageKey) === '1';
}

function writeDetectDismissedToday() {
  if (!process.client) return;
  localStorage.setItem(detectPopupDismissStorageKey, getHomePopupDateKey());
}

function syncDetectGate() {
  if (!process.client) return;
  const shouldShow = detectPopupEnabled.value && isInAppBrowser() && !isPwaInstalledFlagged() && !readDetectDismissedToday();
  detectGateVisible.value = shouldShow;
  if (shouldShow) {
    homePopupVisible.value = false;
  }
}

function dismissDetectGate() {
  writeDetectDismissedToday();
  detectGateVisible.value = false;
}

function handleProductImageError(event: Event) {
  const target = event.target as HTMLImageElement | null;
  if (!target) return;
  target.onerror = null;
  target.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
}

watch(homeSettingsData, () => {
  syncHomePopup();
  syncDetectGate();
}, { immediate: true, deep: true });

onMounted(() => {
  syncHomePopup();
  syncDetectGate();
});
</script>

<template>
  <div class="home-page">
    <!-- Notice Banner -->
    <div v-if="!noticeDismissed && noticeText" class="notice-box">
      <button class="notice-close" type="button" @click="dismissNotice">&times;</button>
      <div class="notice-title">Notice:</div>
      {{ noticeText }}
    </div>

    <div v-if="homePopupVisible && homePopupActive" class="home-popup-overlay" role="dialog" aria-modal="true" aria-label="Homepage notice">
      <div class="home-popup-card" @click="handlePopupCardClick">
        <button type="button" class="home-popup-close-top" @click.stop="dismissHomePopup">
          <span>×</span>
        </button>

        <div class="home-popup-image-wrap">
          <img v-if="homePopupActive.imageUrl" :src="homePopupActive.imageUrl" :alt="homePopupActive.title || 'Homepage promotion'" class="home-popup-image">
          <div v-else class="home-popup-image-placeholder">
            <strong>{{ homePopupActive.title || 'Promotion' }}</strong>
          </div>
        </div>

        <div class="home-popup-body">
          <h4 v-if="homePopupActive.title" class="home-popup-title">{{ homePopupActive.title }}</h4>
          <p v-if="homePopupActive.note" class="home-popup-note">{{ homePopupActive.note }}</p>
          <a
            v-if="homePopupActive.buttonUrl"
            :href="homePopupActive.buttonUrl"
            target="_blank"
            rel="noopener"
            class="home-popup-cta"
            @click.prevent.stop="detectPopupEnabled ? handlePopupCardClick() : handleHomePopupAction()"
          >
            {{ homePopupActive.buttonLabel || 'Click Here' }}
          </a>
          <button type="button" class="home-popup-cancel" @click.stop="dismissHomePopup">
            <span>× CLOSE</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="detectGateVisible" class="detect-popup-overlay" role="dialog" aria-modal="true" aria-label="Open in Chrome" data-pgw-install-safe="true" @click="openCurrentPageInChrome">
      <div class="detect-popup-card" data-pgw-install-safe="true" @click.stop>
        <h4>Detected In-App Browser</h4>
        <p>ভালো অভিজ্ঞতার জন্য Chrome browser এ খুলুন।</p>
        <div class="detect-popup-actions">
          <button type="button" class="detect-popup-open" data-pgw-install-safe="true" @click="openCurrentPageInChrome">Open in Chrome</button>
          <button type="button" class="detect-popup-cancel" data-pgw-install-safe="true" @click="dismissDetectGate">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Main Banner Slide -->
    <div v-if="homeSettings.showSlider" class="space-y-2">
      <div v-if="sliderItems.length" class="slider-list">
        <a
          v-for="(item, index) in sliderItems"
          :key="`${item.title}-${index}`"
          :href="item.link_url || '#'"
          target="_blank"
          rel="noopener"
          class="main-banner"
        >
          <img v-if="item.image_url" :src="item.image_url" :alt="item.title || 'Banner'" @error="handleProductImageError">
          <div v-else class="slider-no-image">
            <strong>{{ item.title || 'Slider' }}</strong>
            <span>{{ item.link_url || 'No image added' }}</span>
          </div>
        </a>
      </div>
      <div class="slider-indicator" aria-hidden="true">—</div>
    </div>

    <!-- Quick Action Support Buttons -->
    <div v-if="homeSettings.showTopSupport && hasTopSupportButtons" class="action-buttons">
      <a
        v-for="btn in topSupportButtons"
        :key="btn.key"
        :href="btn.url"
        target="_blank"
        rel="noopener"
        class="action-btn"
      >
        <div class="icon-circle">
          <svg v-if="btn.key === 'telegram'" viewBox="0 0 24 24" class="icon-svg"><path d="M21.4 4.6a1 1 0 0 0-1-.14L3.7 11.1a1 1 0 0 0 .06 1.86l4.3 1.6 1.6 4.3a1 1 0 0 0 1.86.06l6.64-16.7a1 1 0 0 0-.14-1Z"/></svg>
          <svg v-else-if="btn.key === 'group'" viewBox="0 0 24 24" class="icon-svg"><path d="M12 12a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-6 7a6 6 0 0 1 12 0Zm12 0a6 6 0 0 0-3.4-5.4A5 5 0 0 1 20 18.2V19ZM4 19v-.8a5 5 0 0 1 5.4-4.6A6 6 0 0 0 6 19Z"/></svg>
          <svg v-else viewBox="0 0 24 24" class="icon-svg"><path d="M12 2a10 10 0 0 0-8.66 15l-1.1 4 4.1-1.08A10 10 0 1 0 12 2Zm5.1 13.26c-.22.62-1.28 1.2-1.77 1.28s-1.12.11-1.81-.11a15 15 0 0 1-4.1-1.81 13.7 13.7 0 0 1-2.53-3.06 4 4 0 0 1-.84-2.16A2.33 2.33 0 0 1 6.8 7.3a.83.83 0 0 1 .6-.28h.43c.14 0 .34-.05.53.4s.66 1.62.72 1.74a.43.43 0 0 1 0 .42c-.06.12-.1.2-.2.3s-.2.22-.3.34-.2.2-.08.42a7.06 7.06 0 0 0 1.3 1.6A5.84 5.84 0 0 0 11.52 13c.23.12.36.1.5-.06s.58-.67.73-.9.3-.2.5-.12 1.29.6 1.51.7.38.16.44.24.06.44-.16 1.06Z"/></svg>
        </div>
        <div class="btn-text">
          <span class="sub">{{ btn.sub || 'LINK' }}</span>
          <span class="main">{{ btn.label || 'Support' }}</span>
        </div>
      </a>
    </div>

    <!-- Section Title -->
    <h3 v-if="homeSettings.showCategories && categoryHeading" class="section-title">{{ categoryHeading }}</h3>

    <!-- State Handlers -->
    <div v-if="pending" class="status-msg">
      Loading top-up catalog...
    </div>
    <div v-else-if="error" class="status-msg error-msg">
      We could not load the catalog right now. Please try again.
    </div>

    <!-- Dynamic Product Grid from API -->
    <div v-else-if="homeSettings.showCategories" class="product-grid">
      <NuxtLink
        v-for="item in products"
        :key="item.id"
        :to="`/topup/${item.slug}`"
        class="product-card"
      >
        <div class="img-wrapper">
          <img :src="item.image_url" :alt="item.title" loading="lazy" decoding="async" @error="handleProductImageError" />
        </div>
        <p class="product-title">{{ item.title }}</p>
      </NuxtLink>
    </div>

    <!-- Latest Orders Section -->
    <div v-if="!homeSettings.showCategories" class="status-msg">
      Categories section is currently hidden by admin.
    </div>

    <div v-if="homeSettings.showLatestOrders" class="orders-section">
      <div class="orders-header">
        <h4>Latest Orders</h4>
        <p>সবচেয়ে সাম্প্রতিক ১০টি অর্ডার এক নজরে</p>
      </div>
      <div class="orders-list">
        <div class="order-item">
          <span class="order-name">Munnu Khan • Weekly</span>
          <span class="order-status">Completed</span>
        </div>
        <div class="order-item">
          <span class="order-name">Rahat Ahmed • Weekly</span>
          <span class="order-status">Completed</span>
        </div>
        <div class="order-item">
          <span class="order-name">YOUR PRONOY • Weekly</span>
          <span class="order-status">Completed</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home-page {
  padding-top: 10px;
  max-width: 1260px;
  margin: 0 auto;
}

/* Notice Box */
.notice-box {
  background: var(--theme-color);
  color: #ffffff;
  padding: 12px 14px;
  margin: 0 12px 10px 12px;
  border-radius: 6px;
  position: relative;
  font-size: 13px;
  line-height: 1.5;
  box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}

.notice-title {
  font-weight: 900;
  font-size: 16px;
  margin-bottom: 2px;
}

.notice-close {
  position: absolute;
  right: 10px;
  top: 8px;
  background: transparent;
  border: 1px solid rgba(255,255,255,0.7);
  color: white;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
}

.home-popup-overlay {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(15, 23, 42, 0.56);
  backdrop-filter: blur(3px);
}

.home-popup-card {
  position: relative;
  width: min(780px, 100%);
  overflow: hidden;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
}

.home-popup-close-top {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 2;
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.95);
  color: #0f172a;
  font-size: 24px;
  font-weight: 800;
  line-height: 1;
  display: grid;
  place-items: center;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.18);
}

.home-popup-close-image {
  max-width: 20px;
  max-height: 20px;
  object-fit: contain;
}

.home-popup-image-wrap {
  background: #0f172a;
}

.home-popup-image {
  width: 100%;
  display: block;
  max-height: 420px;
  object-fit: cover;
}

.home-popup-image-placeholder {
  min-height: 220px;
  display: grid;
  place-items: center;
  color: #ffffff;
  padding: 24px;
  text-align: center;
  background: linear-gradient(135deg, var(--theme-color) 0%, #0f172a 100%);
}

.home-popup-body {
  padding: 18px 18px 20px;
}

.home-popup-title {
  font-size: 22px;
  font-weight: 900;
  color: #0f172a;
  margin-bottom: 8px;
}

.home-popup-note {
  font-size: 16px;
  line-height: 1.55;
  color: #1e293b;
  margin-bottom: 16px;
}

.home-popup-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 46px;
  padding: 0 18px;
  border-radius: 10px;
  background: var(--theme-color);
  color: #fff;
  font-size: 15px;
  font-weight: 800;
  text-decoration: none;
}

.home-popup-cancel {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-left: 10px;
  margin-top: 12px;
  padding: 0 16px;
  min-height: 46px;
  border-radius: 10px;
  border: 0;
  background: var(--theme-color);
  color: #ffffff;
  font-size: 14px;
  font-weight: 800;
}

.detect-popup-overlay {
  position: fixed;
  inset: 0;
  z-index: 95;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(2, 6, 23, 0.68);
}

.detect-popup-card {
  width: min(520px, 100%);
  border-radius: 14px;
  background: #ffffff;
  padding: 20px;
  box-shadow: 0 18px 40px rgba(2, 6, 23, 0.35);
}

.detect-popup-card h4 {
  font-size: 22px;
  font-weight: 900;
  color: #0f172a;
}

.detect-popup-card p {
  margin-top: 8px;
  font-size: 15px;
  color: #334155;
  line-height: 1.55;
}

.detect-popup-actions {
  margin-top: 16px;
  display: flex;
  gap: 10px;
}

.detect-popup-open,
.detect-popup-cancel {
  min-height: 44px;
  border-radius: 10px;
  padding: 0 14px;
  font-weight: 800;
  font-size: 14px;
}

.detect-popup-open {
  border: 0;
  background: var(--theme-color);
  color: #ffffff;
}

.detect-popup-cancel {
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  color: #0f172a;
}

/* Banner */
.main-banner {
  margin: 12px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  background-color: #194f2f;
}

.main-banner img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
}

.slider-list {
  display: grid;
  gap: 8px;
}

.slider-indicator {
  text-align: center;
  margin-top: -2px;
  color: #0f172a;
  font-size: 28px;
  font-weight: 900;
  line-height: 1;
}

.slider-no-image {
  min-height: 90px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  color: #ffffff;
}

.slider-no-image strong {
  font-size: 14px;
}

.slider-no-image span {
  font-size: 12px;
  opacity: 0.92;
  margin-top: 4px;
}

/* Action Social Buttons */
.action-buttons {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding: 0 12px;
  margin-top: 15px;
}

.action-btn {
  background: var(--theme-color);
  color: white;
  padding: 8px 6px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  box-shadow: 0 4px 10px rgba(15, 104, 56, 0.2);
}

.icon-circle {
  width: 28px;
  height: 28px;
  background: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-svg {
  width: 15px;
  height: 15px;
  fill: var(--theme-color);
}

.btn-text {
  display: flex;
  flex-direction: column;
}

.btn-text .sub {
  font-size: 9px;
  text-transform: uppercase;
  opacity: 0.85;
  letter-spacing: 0.3px;
}

.btn-text .main {
  font-size: 11px;
  font-weight: 700;
  line-height: 1.1;
}

/* Section Title */
.section-title {
  text-align: center;
  color: #1e293b;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 1px;
  margin: 24px 0 14px 0;
}

/* Product Grid */
.product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 0 12px;
}

.product-card {
  background: #ffffff;
  border-radius: 12px;
  text-align: center;
  padding: 8px 8px 12px 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.15s ease;
}

.product-card:active {
  transform: scale(0.97);
}

.img-wrapper {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 8px;
  overflow: hidden;
  background: #f1f5f9;
}

.img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-title {
  color: #1e293b;
  font-size: 12px;
  font-weight: 700;
  margin-top: 8px;
  line-height: 1.2;
  word-break: break-word;
}

/* Status Message */
.status-msg {
  text-align: center;
  padding: 30px 15px;
  color: #64748b;
  font-size: 14px;
}
.error-msg {
  color: #e11d48;
}

/* Orders Section */
.orders-section {
  margin: 30px 12px 15px 12px;
  background: #ffffff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.orders-header {
  text-align: center;
  margin-bottom: 12px;
}

.orders-header h4 {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
}

.orders-header p {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #f8fafc;
}

.order-name {
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}

.order-status {
  font-size: 11px;
  font-weight: 700;
  color: var(--theme-color);
}

@media (min-width: 1024px) {
  .home-page {
    padding-top: 12px;
  }

  .notice-box {
    margin: 0 14px 12px 14px;
    border-radius: 4px;
    font-size: 12px;
    line-height: 1.45;
  }

  .notice-title {
    font-size: 22px;
    margin-bottom: 4px;
  }

  .notice-close {
    right: 9px;
    top: 8px;
    width: 21px;
    height: 21px;
  }

  .main-banner {
    margin: 8px 14px;
    border-radius: 2px;
  }

  .main-banner img {
    max-height: 350px;
    object-fit: cover;
  }

  .action-buttons {
    max-width: 760px;
    margin: 16px auto 0;
    gap: 12px;
    padding: 0;
  }

  .section-title {
    margin: 24px 0 14px;
    font-size: 44px;
    line-height: 1.06;
    letter-spacing: 0;
  }

  .product-grid {
    display: flex;
    overflow-x: auto;
    gap: 14px;
    padding: 0 14px 8px;
    scrollbar-width: thin;
    scroll-snap-type: x proximity;
  }

  .product-card {
    flex: 0 0 110px;
    border-radius: 8px;
    padding: 5px 5px 10px;
    scroll-snap-align: start;
  }

  .img-wrapper {
    border-radius: 6px;
  }

  .product-title {
    margin-top: 6px;
    font-size: 10px;
    line-height: 1.25;
  }
}
</style>