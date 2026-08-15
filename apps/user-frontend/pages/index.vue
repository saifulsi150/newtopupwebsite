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
  if (sliderItems.value.length > 1) {
    sliderTimer = setInterval(() => {
      currentSlide.value = (currentSlide.value + 1) % sliderItems.value.length;
    }, 4000);
  }
}

function goToSlide(i: number) {
  currentSlide.value = i;
  startSlider();
}

onMounted(() => {
  if (sliderItems.value.length > 1) startSlider();
});

onUnmounted(() => {
  if (sliderTimer) clearInterval(sliderTimer);
});

function onProductClick(id: number) {
  clickedProductId.value = id;
}

const categoryGroups = computed(() => {
  const allProducts = products.value;
  if (!allProducts.length) return [];
  const groupMap = new Map<string, Product[]>();
  for (const p of allProducts) {
    const catTitle = p.category_title?.trim() || 'Special Offer';
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
  <div class="home-wrapper">
    <!-- ===== 1. NOTICE BAR: Edge-to-Edge flush ===== -->
    <div
      v-if="!noticeDismissed && noticeText"
      class="notice-box"
      :style="{ backgroundColor: noticeBgColor, color: noticeTextColor }"
    >
      <button class="notice-close" type="button" aria-label="Close Notice" @click="noticeDismissed = true">
        <svg viewBox="0 0 24 24" class="close-icon"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.2" fill="none"/><line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2.2"/><line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2.2"/></svg>
      </button>
      <div class="notice-title" :style="{ color: noticeTextColor }">{{ noticeTitle }}</div>
      <div class="notice-text" :style="{ color: noticeTextColor }">{{ noticeText }}</div>
    </div>

    <!-- ===== 2. SLIDER: Exact 1:1 Banner Fitting ===== -->
    <div v-if="sliderItems.length" class="slider-section">
      <div class="slider-viewport">
        <div
          class="slider-track"
          :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
        >
          <div
            v-for="(item, i) in sliderItems"
            :key="i"
            class="slider-slide"
          >
            <a :href="item.link_url || '#'" :target="item.link_url ? '_blank' : '_self'" rel="noopener" class="slide-anchor">
              <img
                :src="item.image_url"
                :alt="item.title || 'Banner'"
                class="banner-img"
                @error="handleImageError"
              />
            </a>
          </div>
        </div>
      </div>

      <!-- Dash Pagination Indicator (matching rgbazer.com) -->
      <div v-if="sliderItems.length > 1" class="slider-dots">
        <button
          v-for="(_, i) in sliderItems"
          :key="i"
          class="slider-dot-btn"
          :class="{ active: currentSlide === i }"
          :aria-label="`Slide ${i + 1}`"
          @click="goToSlide(i)"
        />
      </div>
      <div v-else class="slider-single-dash"></div>
    </div>

    <!-- ===== 3. CATEGORIES & PRODUCT GRID ===== -->
    <div class="main-container">
      <template v-if="categoryGroups.length">
        <section v-for="cat in categoryGroups" :key="cat.title" class="category-section">
          <!-- Category Title -->
          <h2 class="category-heading">{{ cat.title }}</h2>

          <!-- Product Grid: 3 cols on mobile, 6 cols on desktop -->
          <div class="products-grid">
            <div
              v-for="item in cat.products"
              :key="item.id"
              class="product-item"
            >
              <NuxtLink
                :to="`/topup/${item.slug}`"
                class="product-link"
                @click="onProductClick(item.id)"
              >
                <!-- Image Container with smooth scale-90 effect -->
                <div
                  class="product-thumb-wrap"
                  :class="{ 'is-clicked': clickedProductId === item.id }"
                >
                  <img
                    :src="item.image_url"
                    :alt="item.title"
                    class="product-thumb-img"
                    loading="lazy"
                    decoding="async"
                    @error="handleImageError"
                  />
                </div>
                <!-- Title -->
                <p class="product-name">{{ item.title }}</p>
              </NuxtLink>
            </div>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<style scoped>
.home-wrapper {
  width: 100%;
  padding-bottom: 24px;
}

/* ===== 1. NOTICE BOX ===== */
.notice-box {
  background: #0a6b2a;
  color: #ffffff;
  padding: 7px 12px 8px 12px;
  margin: 0 0 10px 0;
  width: 100%;
  position: relative;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.notice-title {
  font-weight: 800;
  font-size: 13.5px;
  margin-bottom: 2px;
  line-height: 1.2;
}

.notice-text {
  font-size: 11.5px;
  padding-right: 24px;
  line-height: 1.45;
  font-weight: 400;
}

.notice-close {
  position: absolute;
  top: 7px;
  right: 8px;
  background: transparent;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-icon {
  width: 18px;
  height: 18px;
}

/* ===== 2. SLIDER (Fixed 100% width fitting) ===== */
.slider-section {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 10px;
  box-sizing: border-box;
}

.slider-viewport {
  width: 100%;
  overflow: hidden;
  border-radius: 6px;
  position: relative;
}

.slider-track {
  display: flex;
  width: 100%;
  transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
}

.slider-slide {
  flex: 0 0 100%;
  width: 100%;
  max-width: 100%;
  min-width: 100%;
  box-sizing: border-box;
}

.slide-anchor {
  display: block;
  width: 100%;
  line-height: 0;
}

.banner-img {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 6px;
  object-fit: cover;
}

/* Dash Indicators */
.slider-dots {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 5px;
  margin: 8px auto 14px;
}

.slider-dot-btn {
  width: 16px;
  height: 4px;
  border-radius: 2px;
  background: #c0c8d8;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: background 0.2s, width 0.2s;
}

.slider-dot-btn.active {
  background: #000000;
  width: 20px;
}

.slider-single-dash {
  width: 16px;
  height: 4px;
  background: #000000;
  border-radius: 2px;
  margin: 8px auto 14px;
}

/* ===== 3. CATEGORIES & GRID ===== */
.main-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 10px;
  box-sizing: border-box;
}

.category-section {
  margin-bottom: 20px;
}

.category-heading {
  text-align: center;
  font-size: 22px;
  font-weight: 800;
  color: #17395c;
  margin: 0 0 12px 0;
  letter-spacing: -0.2px;
}

/* Responsive Grid: 3 cols on mobile, 4 on tablet, 6 on desktop */
.products-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  column-gap: 10px;
  row-gap: 14px;
}

@media (min-width: 640px) {
  .products-grid {
    grid-template-columns: repeat(4, 1fr);
    column-gap: 14px;
    row-gap: 18px;
  }
}

@media (min-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(6, 1fr);
    column-gap: 20px;
    row-gap: 24px;
  }
}

.product-item {
  width: 100%;
}

.product-link {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
  outline: none;
  cursor: pointer;
}

/* Image Wrap */
.product-thumb-wrap {
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 10px;
  overflow: hidden;
  background: #f1f6fc;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  transform: scale(1);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
}

/* Hover on desktop */
.product-link:hover .product-thumb-wrap {
  transform: scale(0.90);
}

/* Active / Clicked lock */
.product-link:active .product-thumb-wrap,
.product-thumb-wrap.is-clicked {
  transform: scale(0.90);
}

.product-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Product Title */
.product-name {
  color: #17395c;
  font-size: 11px;
  font-weight: 700;
  text-align: center;
  margin-top: 6px;
  line-height: 1.25;
  word-break: break-word;
}

@media (min-width: 768px) {
  .product-name {
    font-size: 12px;
  }
}
</style>