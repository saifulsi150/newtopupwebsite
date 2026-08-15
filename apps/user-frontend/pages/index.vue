<script setup lang="ts">
import { ref, computed } from 'vue'

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

const noticeBgColor = computed(() => {
  const raw = String(homeSettings.value?.notice_bg_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#0d682f';
});

const noticeTextColor = computed(() => {
  const raw = String(homeSettings.value?.notice_text_color || '').trim();
  return /^#[0-9a-fA-F]{3,8}$/.test(raw) ? raw : '#ffffff';
});

const noticeDismissed = ref(false);

// Firm click lock state
const clickedProductId = ref<number | null>(null);

function onProductClick(id: number) {
  clickedProductId.value = id;
}

// Group products dynamically by Category title from database/admin
const categoryGroups = computed(() => {
  const allProducts = products.value;
  if (!allProducts.length) return [];

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

function handleProductImageError(event: Event) {
  const target = event.target as HTMLImageElement | null;
  if (!target) return;
  target.onerror = null;
  target.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
}
</script>

<template>
  <div class="home-page">
    <!-- Notice Box: Flush Full-Width Sharp Box matching rgbazer.com -->
    <div
      v-if="!noticeDismissed && noticeText"
      class="notice-box"
      :style="{ backgroundColor: noticeBgColor, color: noticeTextColor }"
    >
      <button class="notice-close" type="button" aria-label="Close Notice" @click="noticeDismissed = true">
        <svg viewBox="0 0 24 24" class="close-icon"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2.2" fill="none"/><line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2.2"/><line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2.2"/></svg>
      </button>
      <div class="notice-title" :style="{ color: noticeTextColor }">Notice:</div>
      <div class="notice-text" :style="{ color: noticeTextColor }">{{ noticeText }}</div>
    </div>

    <!-- Main Banner Slider -->
    <div v-if="homeSettings.showSlider !== false && sliderItems.length" class="slider-wrapper">
      <div class="slider-container">
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

      <!-- Single Dash Indicator -->
      <div class="slider-dash-indicator"></div>
    </div>

    <!-- Dynamic Product Categories & Grid -->
    <template v-if="homeSettings.showCategories !== false && categoryGroups.length">
      <section v-for="cat in categoryGroups" :key="cat.title" class="category-block">
        <h2 class="category-title">{{ cat.title }}</h2>

        <div class="product-grid">
          <NuxtLink
            v-for="item in cat.products"
            :key="item.id"
            :to="`/topup/${item.slug}`"
            class="product-card"
            @click="onProductClick(item.id)"
          >
            <div class="product-img-wrap" :class="{ 'is-pressed': clickedProductId === item.id }">
              <img :src="item.image_url" :alt="item.title" loading="lazy" decoding="async" @error="handleProductImageError" />
            </div>
            <p class="product-title">{{ item.title }}</p>
          </NuxtLink>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.home-page {
  padding-top: 0px;
  max-width: 1200px;
  margin: 0 auto;
  padding-bottom: 24px;
}

/* ===== NOTICE BOX: FLUSH & FULL WIDTH (1:1 with rgbazer.com) ===== */
.notice-box {
  background: #0d682f;
  color: #ffffff;
  padding: 6px 12px 7px 12px;
  margin: 0 0 8px 0;
  width: 100%;
  border-radius: 0px;
  position: relative;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}

.notice-title {
  font-weight: 800;
  font-size: 13.5px;
  margin-bottom: 2px;
  color: #ffffff;
  line-height: 1.2;
}

.notice-text {
  font-size: 11.5px;
  color: #ffffff;
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
  color: #ffffff;
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

/* ===== SLIDER ===== */
.slider-wrapper {
  margin: 0 12px 4px 12px;
}

.slider-container {
  border-radius: 0px;
  overflow: hidden;
  background: transparent;
}

.slide-item {
  width: 100%;
  display: block;
}

.slide-item img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: contain;
}

.slider-dash-indicator {
  width: 16px;
  height: 4px;
  background: #000000;
  border-radius: 2px;
  margin: 8px auto 14px;
}

/* ===== CATEGORIES & SPACING ===== */
.category-block {
  margin-top: 0;
  margin-bottom: 16px;
  padding: 0 14px;
}

.category-title {
  text-align: center;
  font-size: 22px;
  font-weight: 800;
  color: #17395c;
  margin-bottom: 12px;
  letter-spacing: -0.2px;
}

/* Product Grid: 3 columns with 14px row-gap and 12px column-gap */
.product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  column-gap: 12px;
  row-gap: 14px;
}

@media (min-width: 640px) {
  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(105px, 120px));
    gap: 16px 14px;
    justify-content: start;
  }
}

.product-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
  outline: none;
  cursor: pointer;
}

.product-img-wrap {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 12px;
  overflow: hidden;
  background: #f1f6fc;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  transition: transform 0.1s ease-in-out;
}

/* Firm Inward Press */
.product-card:active .product-img-wrap,
.product-img-wrap.is-pressed {
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
  font-size: 11px;
  font-weight: 700;
  text-align: center;
  margin-top: 6px;
  line-height: 1.25;
  word-break: break-word;
}
</style>