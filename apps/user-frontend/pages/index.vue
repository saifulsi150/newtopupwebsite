<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'

type Product = {
  id: number;
  title: string;
  slug: string;
  image_url: string;
  price_from: number;
  category_id?: number;
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

const noticeText = computed(() => {
  return String(homeSettings.value?.notice || '').trim();
});

const noticeDismissed = ref(false);

// Slider state
const currentSlide = ref(0);
let sliderTimer: ReturnType<typeof setInterval> | null = null;

function nextSlide() {
  if (sliderItems.value.length <= 1) return;
  currentSlide.value = (currentSlide.value + 1) % sliderItems.value.length;
}

function startSliderTimer() {
  if (sliderTimer) clearInterval(sliderTimer);
  if (sliderItems.value.length > 1) {
    sliderTimer = setInterval(() => {
      nextSlide();
    }, 4000);
  }
}

function stopSliderTimer() {
  if (sliderTimer) clearInterval(sliderTimer);
}

// Group products dynamically by Category title from database/admin
const categoryGroups = computed(() => {
  const allProducts = products.value;
  if (!allProducts.length) return [];

  // Group preserving database category order
  const groupMap = new Map<string, Product[]>();
  
  for (const p of allProducts) {
    const catTitle = (p.category_title && p.category_title.trim()) || 'Special Offer';
    if (!groupMap.has(catTitle)) {
      groupMap.set(catTitle, []);
    }
    groupMap.get(catTitle)!.push(p);
  }

  return Array.from(groupMap.entries()).map(([title, prods]) => ({
    title,
    products: prods
  }));
});

// Popups and Detect browser handlers (from admin settings)
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

watch(sliderItems, (items) => {
  currentSlide.value = 0;
  if (items.length > 1) startSliderTimer();
}, { immediate: true });

onMounted(() => {
  syncHomePopup();
  syncDetectGate();
  if (sliderItems.value.length > 1) startSliderTimer();
});

onBeforeUnmount(() => {
  stopSliderTimer();
});
</script>

<template>
  <div class="home-page">
    <!-- Notice Box -->
    <div v-if="!noticeDismissed && noticeText" class="notice-box">
      <button class="notice-close" type="button" aria-label="Close Notice" @click="noticeDismissed = true">
        <svg viewBox="0 0 24 24" class="close-icon"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2"/><line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2"/></svg>
      </button>
      <div class="notice-title">Notice:</div>
      <div class="notice-text">{{ noticeText }}</div>
    </div>

    <!-- Main Banner Slider -->
    <div v-if="homeSettings.showSlider !== false && sliderItems.length" class="slider-wrapper">
      <div class="slider-container">
        <div class="slider-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
          <a
            v-for="(item, index) in sliderItems"
            :key="index"
            :href="item.link_url || '#'"
            target="_blank"
            rel="noopener"
            class="slide-item"
          >
            <img :src="item.image_url" :alt="item.title || 'Banner'" @error="handleProductImageError" />
          </a>
        </div>
      </div>

      <!-- Single Dash Indicator -->
      <div class="slider-dash-indicator"></div>
    </div>

    <!-- State Handlers -->
    <div v-if="pending" class="status-msg">
      Loading top-up catalog...
    </div>
    <div v-else-if="error" class="status-msg error-msg">
      We could not load the catalog right now. Please try again.
    </div>

    <!-- Dynamic Product Categories & Grid -->
    <template v-else-if="homeSettings.showCategories !== false">
      <section v-for="cat in categoryGroups" :key="cat.title" class="category-block">
        <h2 class="category-title">{{ cat.title }}</h2>

        <div class="product-grid">
          <NuxtLink
            v-for="item in cat.products"
            :key="item.id"
            :to="`/topup/${item.slug}`"
            class="product-card"
          >
            <div class="product-img-wrap">
              <img :src="item.image_url" :alt="item.title" loading="lazy" decoding="async" @error="handleProductImageError" />
            </div>
            <p class="product-title">{{ item.title }}</p>
          </NuxtLink>
        </div>
      </section>
    </template>

    <!-- Admin Home Popup Modal -->
    <div v-if="homePopupVisible && homePopupActive" class="home-popup-overlay" role="dialog" aria-modal="true" @click="handlePopupCardClick">
      <div class="home-popup-card" @click.stop>
        <button type="button" class="home-popup-close-top" @click="dismissHomePopup">×</button>
        <div class="home-popup-image-wrap">
          <img v-if="homePopupActive.imageUrl" :src="homePopupActive.imageUrl" :alt="homePopupActive.title || 'Promotion'" class="home-popup-image">
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
            @click="dismissHomePopup"
          >
            {{ homePopupActive.buttonLabel || 'Click Here' }}
          </a>
        </div>
      </div>
    </div>

    <!-- In-App Browser Detect Gate -->
    <div v-if="detectGateVisible" class="detect-popup-overlay" role="dialog" aria-modal="true" @click="openCurrentPageInChrome">
      <div class="detect-popup-card" @click.stop>
        <h4>Detected In-App Browser</h4>
        <p>ভালো অভিজ্ঞতার জন্য Chrome browser এ খুলুন।</p>
        <div class="detect-popup-actions">
          <button type="button" class="detect-popup-open" @click="openCurrentPageInChrome">Open in Chrome</button>
          <button type="button" class="detect-popup-cancel" @click="dismissDetectGate">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home-page {
  padding-top: 10px;
  max-width: 1240px;
  margin: 0 auto;
  padding-bottom: 30px;
}

/* ===== NOTICE BOX ===== */
.notice-box {
  background: #0d682f;
  color: #ffffff;
  padding: 10px 14px;
  margin: 0 12px 12px;
  border-radius: 6px;
  position: relative;
  font-size: 13px;
  line-height: 1.45;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.notice-title {
  font-weight: 800;
  font-size: 15px;
  margin-bottom: 2px;
  color: #ffffff;
}

.notice-text {
  font-size: 12.5px;
  color: #ffffff;
  padding-right: 28px;
}

.notice-close {
  position: absolute;
  top: 8px;
  right: 10px;
  background: transparent;
  border: none;
  color: #ffffff;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-icon {
  width: 20px;
  height: 20px;
}

/* ===== SLIDER ===== */
.slider-wrapper {
  margin: 0 12px 14px;
}

.slider-container {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
  background: #0f172a;
}

.slider-track {
  display: flex;
  transition: transform 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.slide-item {
  min-width: 100%;
  display: block;
}

.slide-item img {
  width: 100%;
  aspect-ratio: 16/6.8;
  object-fit: cover;
  display: block;
}

@media (max-width: 640px) {
  .slide-item img {
    aspect-ratio: 16/7.5;
  }
}

.slider-dash-indicator {
  width: 20px;
  height: 5px;
  background: #000000;
  border-radius: 3px;
  margin: 8px auto 0;
}

/* ===== CATEGORIES & PRODUCTS ===== */
.category-block {
  margin-top: 24px;
  margin-bottom: 20px;
}

.category-title {
  text-align: center;
  font-size: 24px;
  font-weight: 800;
  color: #17395c;
  margin-bottom: 16px;
  letter-spacing: -0.2px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 0 12px;
}

@media (min-width: 640px) {
  .product-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }
}

@media (min-width: 900px) {
  .product-grid {
    grid-template-columns: repeat(6, 1fr);
    gap: 18px;
  }
}

.product-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
  outline: none;
}

.product-img-wrap {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 12px;
  overflow: hidden;
  background: #000000;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.12s ease-out;
}

/* Inward Press Animation as requested */
.product-card:active .product-img-wrap {
  transform: scale(0.92);
}

.product-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.product-title {
  color: #17395c;
  font-size: 11.5px;
  font-weight: 700;
  text-align: center;
  margin-top: 6px;
  line-height: 1.25;
  word-break: break-word;
}

.status-msg {
  text-align: center;
  padding: 30px;
  color: #64748b;
}

.error-msg {
  color: #e11d48;
}

/* ===== POPUPS ===== */
.home-popup-overlay,
.detect-popup-overlay {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(15, 23, 42, 0.56);
}

.home-popup-card,
.detect-popup-card {
  position: relative;
  width: min(600px, 100%);
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}

.home-popup-close-top {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #ffffff;
  border: none;
  font-size: 20px;
  font-weight: 800;
  cursor: pointer;
  z-index: 2;
}

.home-popup-image {
  width: 100%;
  max-height: 360px;
  object-fit: cover;
  display: block;
}

.home-popup-body,
.detect-popup-card {
  padding: 18px;
}

.home-popup-title {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
}

.home-popup-note {
  margin-top: 6px;
  font-size: 14px;
  color: #334155;
}

.home-popup-cta {
  display: inline-block;
  margin-top: 14px;
  padding: 10px 20px;
  background: #0d682f;
  color: #ffffff;
  border-radius: 8px;
  font-weight: 700;
  text-decoration: none;
}

.detect-popup-actions {
  display: flex;
  gap: 10px;
  margin-top: 14px;
}

.detect-popup-open {
  background: #0d682f;
  color: #ffffff;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
}

.detect-popup-cancel {
  background: #f1f5f9;
  color: #0f172a;
  border: 1px solid #cbd5e1;
  padding: 8px 16px;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
}
</style>