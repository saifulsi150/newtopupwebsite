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

const noticeDismissed = ref(false);

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
    <!-- Notice Box (Exact as screenshot: sharp corners, solid dark green, Notice: on line 1, text on line 2, close (X) circle on right) -->
    <div v-if="!noticeDismissed && noticeText" class="notice-box">
      <button class="notice-close" type="button" aria-label="Close Notice" @click="noticeDismissed = true">
        <svg viewBox="0 0 24 24" class="close-icon"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2"/><line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2"/></svg>
      </button>
      <div class="notice-title">Notice:</div>
      <div class="notice-text">{{ noticeText }}</div>
    </div>

    <!-- Main Banner Slider (Exact as screenshot: full image visibility, sharp corners, no crop, no sliding movement) -->
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

    <!-- State Handlers -->
    <div v-if="pending" class="status-msg">
      Loading...
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
  </div>
</template>

<style scoped>
.home-page {
  padding-top: 10px;
  max-width: 1260px;
  margin: 0 auto;
  padding-bottom: 30px;
}

/* ===== NOTICE BOX ===== */
.notice-box {
  background: #0d682f;
  color: #ffffff;
  padding: 10px 14px;
  margin: 0 12px 12px;
  border-radius: 0px;
  position: relative;
  font-size: 13px;
  line-height: 1.45;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.06);
}

.notice-title {
  font-weight: 800;
  font-size: 15px;
  margin-bottom: 2px;
  color: #ffffff;
}

.notice-text {
  font-size: 12px;
  color: #ffffff;
  padding-right: 28px;
  line-height: 1.5;
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

/* ===== SLIDER (NO ROUNDED CORNERS, FULL IMAGE VISIBLE, NO SLIDING ANIMATION) ===== */
.slider-wrapper {
  margin: 0 12px 14px;
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
  transition: transform 0.1s ease-out;
}

/* Inward Press Animation */
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
</style>