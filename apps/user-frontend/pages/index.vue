<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

type Product = {
  id: number;
  title: string;
  slug: string;
  image_url: string;
  price_from: number;
  category_id?: number;
  category_title?: string;
};

const { data } = await useFetch<{ products: Product[] }>('/api/home', { key: 'home-products-v2' });
const { data: homeSettingsData } = await useFetch<any>('/api/settings/home', { key: 'home-settings-v1' });

const products = computed(() => data.value?.products || []);
const homeSettings = computed(() => homeSettingsData.value?.home || {});

const sliderItems = computed(() => {
  const list = Array.isArray(homeSettings.value?.sliderItems) ? homeSettings.value.sliderItems : [];
  return list.filter((item: any) => Boolean(item?.enabled));
});

const noticeText = computed(() => String(homeSettings.value?.notice || '').trim());
const noticeTitle = computed(() => String(homeSettings.value?.notice_title || 'Notice:').trim());
const noticeBgColor = computed(() => {
  const raw = String(homeSettings.value?.notice_bg_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#0a6b2a';
});
const noticeTextColor = computed(() => {
  const raw = String(homeSettings.value?.notice_text_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#ffffff';
});

const noticeDismissed = ref(false);
const clickedProductId = ref<number | null>(null);

// Slider state
const currentSlide = ref(0);
let sliderTimer: ReturnType<typeof setInterval> | null = null;

function startSlider() {
  if (sliderTimer) clearInterval(sliderTimer);
  sliderTimer = setInterval(() => {
    if (sliderItems.value.length > 1) {
      currentSlide.value = (currentSlide.value + 1) % sliderItems.value.length;
    }
  }, 3500);
}

function goToSlide(i: number) {
  currentSlide.value = i;
  startSlider();
}

onMounted(() => { if (sliderItems.value.length > 1) startSlider(); });
onUnmounted(() => { if (sliderTimer) clearInterval(sliderTimer); });

function onProductClick(id: number) {
  clickedProductId.value = id;
}

const categoryGroups = computed(() => {
  const allProducts = products.value;
  if (!allProducts.length) return [];
  const groupMap = new Map<string, Product[]>();
  for (const p of allProducts) {
    const catTitle = (p.category_title?.trim()) || 'Special Offer';
    if (!groupMap.has(catTitle)) groupMap.set(catTitle, []);
    groupMap.get(catTitle)!.push(p);
  }
  return Array.from(groupMap.entries()).map(([title, prods]) => ({ title, products: prods }));
});

function handleImageError(event: Event) {
  const target = event.target as HTMLImageElement | null;
  if (!target) return;
  target.onerror = null;
  target.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
}
</script>

<template>
  <div class="home-page">

    <!-- ===================== NOTICE BAR ===================== -->
    <div
      v-if="!noticeDismissed && noticeText"
      class="notice-bar"
      :style="{ backgroundColor: noticeBgColor }"
    >
      <div class="notice-bar__inner">
        <div class="notice-bar__content">
          <span class="notice-bar__label" :style="{ color: noticeTextColor }">{{ noticeTitle }}</span>
          <span class="notice-bar__text" :style="{ color: noticeTextColor }">{{ noticeText }}</span>
        </div>
        <button class="notice-bar__close" type="button" @click="noticeDismissed = true" :style="{ color: noticeTextColor }">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- ===================== SLIDER ===================== -->
    <div v-if="sliderItems.length" class="slider-outer">
      <div class="slider-inner">
        <div class="slider-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
          <div v-for="(item, i) in sliderItems" :key="i" class="slide">
            <a :href="item.link_url || '#'" :target="item.link_url ? '_blank' : '_self'" rel="noopener">
              <img :src="item.image_url" :alt="item.title || 'Banner'" class="slide-img" @error="handleImageError" />
            </a>
          </div>
        </div>
      </div>
      <!-- Dots -->
      <div v-if="sliderItems.length > 1" class="slider-dots">
        <button
          v-for="(_, i) in sliderItems"
          :key="i"
          class="slider-dot"
          :class="{ active: currentSlide === i }"
          @click="goToSlide(i)"
          :aria-label="`Slide ${i + 1}`"
        />
      </div>
      <!-- Single dash for 1 slide -->
      <div v-else class="slider-single-dash"></div>
    </div>

    <!-- ===================== PRODUCT CATEGORIES ===================== -->
    <div class="categories-outer">
      <template v-if="categoryGroups.length">
        <section v-for="cat in categoryGroups" :key="cat.title" class="cat-section">
          <!-- Category Title -->
          <h2 class="cat-title">{{ cat.title }}</h2>

          <!-- Product Grid -->
          <div class="product-grid">
            <NuxtLink
              v-for="item in cat.products"
              :key="item.id"
              :to="`/topup/${item.slug}`"
              class="product-card"
              @click="onProductClick(item.id)"
            >
              <div class="product-img-wrap" :class="{ 'pressed': clickedProductId === item.id }">
                <img
                  :src="item.image_url"
                  :alt="item.title"
                  class="product-img"
                  loading="lazy"
                  decoding="async"
                  @error="handleImageError"
                />
              </div>
              <p class="product-title">{{ item.title }}</p>
            </NuxtLink>
          </div>
        </section>
      </template>
    </div>

  </div>
</template>

<style scoped>
/* ============================================================
   BASE
============================================================ */
.home-page {
  background: #f4f6f9;
  min-height: 100vh;
  padding-bottom: 32px;
}

/* ============================================================
   NOTICE BAR — Full-width, no rounding, no margin
============================================================ */
.notice-bar {
  width: 100%;
  background: #0a6b2a;
  padding: 7px 0 8px;
  margin: 0 0 8px 0;
}

.notice-bar__inner {
  max-width: 760px;
  margin: 0 auto;
  padding: 0 12px;
  display: flex;
  align-items: flex-start;
  gap: 6px;
}

.notice-bar__content {
  flex: 1;
  min-width: 0;
}

.notice-bar__label {
  display: block;
  font-size: 13.5px;
  font-weight: 800;
  color: #fff;
  line-height: 1.3;
  margin-bottom: 2px;
}

.notice-bar__text {
  display: block;
  font-size: 11.5px;
  font-weight: 400;
  color: #fff;
  line-height: 1.5;
}

.notice-bar__close {
  flex-shrink: 0;
  background: transparent;
  border: none;
  color: #fff;
  cursor: pointer;
  padding: 0;
  margin-top: 1px;
  display: flex;
  align-items: center;
  opacity: 0.85;
  transition: opacity 0.15s;
}
.notice-bar__close:hover { opacity: 1; }

/* ============================================================
   SLIDER
============================================================ */
.slider-outer {
  max-width: 760px;
  margin: 0 auto 0;
  padding: 0 12px;
}

.slider-inner {
  overflow: hidden;
  border-radius: 10px;
  background: #000;
  width: 100%;
}

.slider-track {
  display: flex;
  transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
  width: 100%;
}

.slide {
  min-width: 100%;
  flex-shrink: 0;
}

.slide-img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: cover;
}

/* Dots */
.slider-dots {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 5px;
  margin: 8px auto 14px;
}

.slider-dot {
  width: 16px;
  height: 4px;
  border-radius: 2px;
  background: #c0c8d8;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: background 0.2s, width 0.2s;
}

.slider-dot.active {
  background: #17395c;
  width: 24px;
}

.slider-single-dash {
  width: 16px;
  height: 4px;
  background: #17395c;
  border-radius: 2px;
  margin: 8px auto 14px;
}

/* ============================================================
   CATEGORY SECTIONS
============================================================ */
.categories-outer {
  max-width: 760px;
  margin: 0 auto;
  padding: 0 12px;
}

.cat-section {
  margin-bottom: 24px;
}

.cat-title {
  text-align: center;
  font-size: 20px;
  font-weight: 800;
  color: #17395c;
  margin: 0 0 12px;
  letter-spacing: -0.2px;
}

/* ============================================================
   PRODUCT GRID
   Mobile: 3 columns, gap-4 (16px)
   Desktop md: 6 columns, gap-8 (32px)
============================================================ */
.product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px 12px;
}

@media (min-width: 768px) {
  .cat-title {
    font-size: 22px;
  }
  .product-grid {
    grid-template-columns: repeat(6, 1fr);
    gap: 20px 16px;
  }
}

/* ============================================================
   PRODUCT CARD
============================================================ */
.product-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  outline: none;
  -webkit-tap-highlight-color: transparent;
  cursor: pointer;
}

.product-img-wrap {
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 12px;
  overflow: hidden;
  background: #e8eff8;
  box-shadow: 0 2px 8px rgba(23, 57, 92, 0.10);
  transform: scale(1);
  transition: transform 0.30s cubic-bezier(0.4, 0, 0.2, 1),
              box-shadow 0.30s ease;
  will-change: transform;
}

/* Hover: desktop */
.product-card:hover .product-img-wrap {
  transform: scale(0.92);
  box-shadow: 0 1px 4px rgba(23, 57, 92, 0.06);
}

/* Active / clicked: both mobile & desktop */
.product-card:active .product-img-wrap,
.product-img-wrap.pressed {
  transform: scale(0.90);
  box-shadow: none;
}

.product-img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-title {
  font-size: 11px;
  font-weight: 700;
  color: #17395c;
  text-align: center;
  margin: 6px 0 0;
  line-height: 1.3;
  word-break: break-word;
}

/* Desktop larger title text */
@media (min-width: 768px) {
  .product-title {
    font-size: 12px;
  }
}
</style>