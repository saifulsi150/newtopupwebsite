<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

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

// Slider state
const currentSlide = ref(0);
let sliderTimer: ReturnType<typeof setInterval> | null = null;

function nextSlide() {
  if (sliderItems.value.length <= 1) return;
  currentSlide.value = (currentSlide.value + 1) % sliderItems.value.length;
}
function prevSlide() {
  if (sliderItems.value.length <= 1) return;
  currentSlide.value = (currentSlide.value - 1 + sliderItems.value.length) % sliderItems.value.length;
}
function goToSlide(index: number) {
  currentSlide.value = index;
}
function startSliderTimer() {
  if (sliderTimer) clearInterval(sliderTimer);
  sliderTimer = setInterval(() => {
    nextSlide();
  }, 4000);
}
function stopSliderTimer() {
  if (sliderTimer) clearInterval(sliderTimer);
}

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
    <!-- Notice Banner -->
    <transition name="notice-slide">
      <div v-if="!noticeDismissed && noticeText" class="notice-box">
        <div class="notice-icon">📢</div>
        <div class="notice-content">
          <span class="notice-title">Notice:</span>
          {{ noticeText }}
        </div>
        <button class="notice-close" type="button" aria-label="Close notice" @click="dismissNotice">×</button>
      </div>
    </transition>

    <!-- Popup Modal -->
    <transition name="popup-fade">
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
    </transition>

    <!-- In-App Browser Gate -->
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

    <!-- ===== AUTO SLIDER ===== -->
    <div v-if="homeSettings.showSlider && sliderItems.length" class="slider-section" @mouseenter="stopSliderTimer" @mouseleave="startSliderTimer">
      <div class="slider-track-wrap">
        <!-- Slides -->
        <div class="slider-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
          <a
            v-for="(item, index) in sliderItems"
            :key="`slide-${index}`"
            :href="item.link_url || '#'"
            target="_blank"
            rel="noopener"
            class="slide-item"
          >
            <img v-if="item.image_url" :src="item.image_url" :alt="item.title || 'Banner'" @error="handleProductImageError">
            <div v-else class="slide-placeholder">
              <strong>{{ item.title || 'Slider' }}</strong>
            </div>
          </a>
        </div>

        <!-- Arrows (only show if more than 1 slide) -->
        <template v-if="sliderItems.length > 1">
          <button class="slider-arrow slider-arrow-left" type="button" aria-label="Previous slide" @click.prevent="prevSlide">‹</button>
          <button class="slider-arrow slider-arrow-right" type="button" aria-label="Next slide" @click.prevent="nextSlide">›</button>
        </template>
      </div>

      <!-- Dots -->
      <div v-if="sliderItems.length > 1" class="slider-dots">
        <button
          v-for="(_, i) in sliderItems"
          :key="i"
          :class="['slider-dot', { active: i === currentSlide }]"
          type="button"
          :aria-label="`Go to slide ${i + 1}`"
          @click="goToSlide(i)"
        />
      </div>
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
    <h3 v-if="homeSettings.showCategories && categoryHeading" class="section-title">
      <span class="section-title-line"></span>
      {{ categoryHeading }}
      <span class="section-title-line"></span>
    </h3>

    <!-- Skeleton Loading State -->
    <div v-if="pending" class="product-grid">
      <div v-for="i in 9" :key="i" class="product-skeleton">
        <div class="skeleton-img"></div>
        <div class="skeleton-text"></div>
      </div>
    </div>

    <div v-else-if="error" class="status-msg error-msg">
      We could not load the catalog right now. Please try again.
    </div>

    <!-- Product Grid -->
    <div v-else-if="homeSettings.showCategories" class="product-grid">
      <NuxtLink
        v-for="item in products"
        :key="item.id"
        :to="`/topup/${item.slug}`"
        class="product-card"
      >
        <div class="img-wrapper">
          <img :src="item.image_url" :alt="item.title" loading="lazy" decoding="async" @error="handleProductImageError" />
          <div class="img-shine"></div>
        </div>
        <p class="product-title">{{ item.title }}</p>
      </NuxtLink>
    </div>

    <div v-if="!homeSettings.showCategories && !pending" class="status-msg">
      Categories section is currently hidden by admin.
    </div>

    <!-- Latest Orders Section -->
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
/* ===== PAGE ===== */
.home-page {
  padding-top: 10px;
  max-width: 1260px;
  margin: 0 auto;
  padding-bottom: 20px;
}

/* ===== NOTICE BOX ===== */
.notice-box {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: linear-gradient(135deg, var(--theme-color) 0%, color-mix(in srgb, var(--theme-color) 80%, #000) 100%);
  color: #ffffff;
  padding: 12px 40px 12px 14px;
  margin: 0 12px 12px 12px;
  border-radius: 10px;
  position: relative;
  font-size: 13px;
  line-height: 1.55;
  box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

.notice-icon {
  font-size: 18px;
  flex-shrink: 0;
  margin-top: 1px;
}

.notice-content {
  flex: 1;
}

.notice-title {
  font-weight: 900;
  font-size: 14px;
  margin-right: 5px;
}

.notice-close {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,0.2);
  border: 1px solid rgba(255,255,255,0.5);
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
  transition: background 0.2s;
  flex-shrink: 0;
}

.notice-close:hover {
  background: rgba(255,255,255,0.35);
}

.notice-slide-enter-active { transition: all 0.3s ease; }
.notice-slide-leave-active { transition: all 0.25s ease; }
.notice-slide-enter-from { opacity: 0; transform: translateY(-8px); }
.notice-slide-leave-to { opacity: 0; transform: translateY(-8px); }

/* ===== SLIDER ===== */
.slider-section {
  margin: 0 12px 14px 12px;
  position: relative;
}

.slider-track-wrap {
  border-radius: 12px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 4px 18px rgba(0,0,0,0.13);
  background: #1a1a2e;
}

.slider-track {
  display: flex;
  transition: transform 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  will-change: transform;
}

.slide-item {
  min-width: 100%;
  display: block;
  position: relative;
}

.slide-item img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
  aspect-ratio: 16/6;
}

@media (max-width: 480px) {
  .slide-item img {
    aspect-ratio: 16/7;
  }
}

.slide-placeholder {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  padding: 20px;
  text-align: center;
  background: linear-gradient(135deg, var(--theme-color), #0a0a1a);
}

.slider-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.4);
  border: none;
  color: #fff;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, transform 0.2s;
  z-index: 2;
  backdrop-filter: blur(4px);
}

.slider-arrow:hover {
  background: rgba(0,0,0,0.65);
  transform: translateY(-50%) scale(1.08);
}

.slider-arrow-left { left: 8px; }
.slider-arrow-right { right: 8px; }

.slider-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 10px;
}

.slider-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #c5cdd6;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: all 0.25s;
}

.slider-dot.active {
  background: var(--theme-color);
  width: 22px;
  border-radius: 4px;
}

/* ===== ACTION BUTTONS ===== */
.action-buttons {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding: 0 12px;
  margin-top: 4px;
  margin-bottom: 6px;
}

.action-btn {
  background: var(--theme-color);
  color: white;
  padding: 9px 7px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 7px;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(15, 104, 56, 0.22);
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.action-btn:active {
  transform: scale(0.96);
  box-shadow: 0 2px 6px rgba(15, 104, 56, 0.15);
}

.icon-circle {
  width: 30px;
  height: 30px;
  background: rgba(255,255,255,0.92);
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
  opacity: 0.82;
  letter-spacing: 0.4px;
}

.btn-text .main {
  font-size: 11px;
  font-weight: 700;
  line-height: 1.15;
}

/* ===== SECTION TITLE ===== */
.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: center;
  color: #1e293b;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.5px;
  margin: 18px 12px 12px 12px;
  text-transform: uppercase;
}

.section-title-line {
  flex: 1;
  height: 1.5px;
  background: linear-gradient(90deg, transparent, #cbd5e1, transparent);
}

/* ===== SKELETON LOADING ===== */
.product-skeleton {
  background: #ffffff;
  border-radius: 12px;
  padding: 8px 8px 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  overflow: hidden;
}

.skeleton-img {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 8px;
  background: linear-gradient(90deg, #e8ecef 25%, #f4f6f8 50%, #e8ecef 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite linear;
}

.skeleton-text {
  height: 12px;
  border-radius: 6px;
  margin-top: 10px;
  background: linear-gradient(90deg, #e8ecef 25%, #f4f6f8 50%, #e8ecef 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite linear;
  width: 70%;
  margin-left: 15%;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ===== PRODUCT GRID ===== */
.product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  padding: 0 12px;
}

@media (min-width: 640px) {
  .product-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
  }
}

@media (min-width: 900px) {
  .product-grid {
    grid-template-columns: repeat(5, 1fr);
  }
}

@media (min-width: 1100px) {
  .product-grid {
    grid-template-columns: repeat(6, 1fr);
  }
}

.product-card {
  background: #ffffff;
  border-radius: 12px;
  text-align: center;
  padding: 8px 8px 10px 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.18s ease;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.product-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
}

.product-card:active {
  transform: scale(0.94);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  transition: transform 0.1s ease, box-shadow 0.1s ease;
}

.img-wrapper {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 9px;
  overflow: hidden;
  background: #f1f5f9;
  position: relative;
}

.img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s ease;
}

.product-card:hover .img-wrapper img {
  transform: scale(1.05);
}

.img-shine {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 60%);
  pointer-events: none;
}

.product-title {
  color: #1e293b;
  font-size: 11.5px;
  font-weight: 700;
  margin-top: 7px;
  line-height: 1.25;
  word-break: break-word;
  width: 100%;
}

/* ===== STATUS MESSAGE ===== */
.status-msg {
  text-align: center;
  padding: 30px 15px;
  color: #64748b;
  font-size: 14px;
}

.error-msg {
  color: #e11d48;
}

/* ===== ORDERS SECTION ===== */
.orders-section {
  margin: 24px 12px 0 12px;
  background: #ffffff;
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.orders-header {
  text-align: center;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
}

.orders-header h4 {
  font-size: 16px;
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
  padding: 9px 12px;
  background: #f8fafc;
  border-radius: 8px;
  font-size: 12.5px;
}

.order-name {
  color: #334155;
  font-weight: 500;
}

.order-status {
  color: #16a34a;
  font-weight: 700;
  font-size: 11px;
  background: #dcfce7;
  padding: 2px 8px;
  border-radius: 20px;
}

/* ===== POPUPS ===== */
.popup-fade-enter-active { transition: all 0.25s ease; }
.popup-fade-leave-active { transition: all 0.2s ease; }
.popup-fade-enter-from, .popup-fade-leave-to { opacity: 0; }

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
  cursor: pointer;
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
  font-size: 20px;
  font-weight: 900;
  color: #0f172a;
  margin-bottom: 8px;
}

.home-popup-note {
  font-size: 15px;
  line-height: 1.55;
  color: #1e293b;
  margin-bottom: 16px;
}

.home-popup-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 0 18px;
  border-radius: 10px;
  background: var(--theme-color);
  color: #fff;
  font-size: 14px;
  font-weight: 800;
  text-decoration: none;
}

.home-popup-cancel {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-left: 10px;
  padding: 0 16px;
  min-height: 44px;
  border-radius: 10px;
  border: 0;
  background: var(--theme-color);
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
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
  font-size: 20px;
  font-weight: 900;
  color: #0f172a;
}

.detect-popup-card p {
  margin-top: 8px;
  font-size: 14px;
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
  font-size: 13px;
  cursor: pointer;
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
</style>