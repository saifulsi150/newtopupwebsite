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

const noticeTitle = computed(() => {
  return String(homeSettings.value?.notice_title || 'Notice:').trim();
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
  <div class="p-2">
    <!-- Notice Container (Exact Blade Structure) -->
    <div v-if="!noticeDismissed && noticeText" class="notice-container container m-auto mb-2">
      <div
        class="alert alert-light notice-style alert-dismissible fade show position-relative"
        role="alert"
        :style="{ backgroundColor: noticeBgColor, color: noticeTextColor }"
      >
        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          @click="noticeDismissed = true"
        >
          ✕
        </button>
        <div class="notice-heading" :style="{ color: noticeTextColor }">{{ noticeTitle }}</div>
        <div class="notice-text mb-0" :style="{ color: noticeTextColor }">{{ noticeText }}</div>
      </div>
    </div>

    <!-- Slider Section (Exact Blade Structure) -->
    <section v-if="homeSettings.showSlider !== false && sliderItems.length" class="container m-auto">
      <section class="carousel my-4" dir="ltr" aria-label="Gallery" tabindex="0" style="margin-bottom: 10px !important;">
        <div class="carousel__viewport">
          <ol class="carousel__track">
            <li v-for="(slider, index) in sliderItems" :key="index" class="carousel__slide">
              <div class="carousel__item">
                <a v-if="slider.link_url" :href="slider.link_url" target="_blank" rel="noopener">
                  <img :src="slider.image_url" :alt="slider.title || 'Slider'" class="rounded-md w-full" @error="handleProductImageError" />
                </a>
                <img v-else :src="slider.image_url" :alt="slider.title || 'Slider'" class="rounded-md w-full" @error="handleProductImageError" />
              </div>
            </li>
          </ol>
        </div>

        <!-- Single Dash Pagination Indicator -->
        <div class="carousel__dash_indicator"></div>
      </section>
    </section>

    <!-- Products By Category (Exact Blade Structure) -->
    <template v-if="homeSettings.showCategories !== false && categoryGroups.length">
      <section v-for="category in categoryGroups" :key="category.title" class="my-2" id="topup">
        <div class="container mx-auto">
          <!-- Category Title -->
          <div class="text-center">
            <div class="flex items-center justify-center px-4 mt-0 md:mt-2 section-contact-gap pb-4">
              <h3 class="text-2xl sm:text-3xl text-center font-primary font-bold mx-4 text-secondary-900">
                {{ category.title }}
              </h3>
            </div>
          </div>

          <!-- Product Grid: grid-cols-3 gap-4 on mobile, md:grid-cols-6 sm:grid-cols-4 md:gap-8 on desktop -->
          <div class="pb-1 md:pb-10">
            <div class="md:py-5 md:px-0 grid md:grid-cols-6 sm:grid-cols-4 grid-cols-3 md:gap-8 gap-4">
              <div
                v-for="product in category.products"
                :key="product.id"
                class="single-game-product mb-2 md:mb-6"
              >
                <NuxtLink
                  :to="`/topup/${product.slug}`"
                  class="triangle block text-decoration-none"
                  @click="onProductClick(product.id)"
                >
                  <div class="cursor-pointer">
                    <!-- Exact Scale 90 Transition on Click / Hover -->
                    <div
                      class="inset-0 transform transition duration-300"
                      :class="clickedProductId === product.id ? 'scale-90' : 'hover:scale-90'"
                    >
                      <div class="h-full w-full text-center mx-auto">
                        <img
                          :src="product.image_url"
                          :alt="product.title"
                          class="rounded-md w-full aspect-square object-cover"
                          loading="lazy"
                          decoding="async"
                          @error="handleProductImageError"
                        />
                      </div>
                    </div>
                  </div>
                  <div>
                    <h1 class="capitalize text-xs text-center pt-3 font-primary font-extralight text-secondary-500">
                      {{ product.title }}
                    </h1>
                  </div>
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
/* ===== EXACT BLADE STYLES ===== */
.notice-style {
  padding: 10px 14px;
  border-radius: 4px;
  position: relative;
}

.notice-style .btn-close {
  position: absolute;
  top: 8px;
  right: 10px;
  background: transparent;
  border: none;
  font-size: 14px;
  color: inherit;
  cursor: pointer;
  padding: 0;
  line-height: 1;
}

.notice-style .notice-heading {
  font-size: 16px;
  font-weight: 700;
  padding-bottom: 3px;
}

.notice-text {
  font-size: 12px;
  font-weight: 400;
  font-family: "Times New Roman", Times, serif;
  line-height: 1.45;
}

/* Slider */
.carousel__viewport {
  overflow: hidden;
}

.carousel__track {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
}

.carousel__slide {
  width: 100%;
  flex-shrink: 0;
}

.carousel__item img {
  display: block;
  width: 100%;
  height: auto;
}

.carousel__dash_indicator {
  width: 16px;
  height: 4px;
  background: #000000;
  border-radius: 2px;
  margin: 8px auto 0;
}

/* Category Title Color */
.text-secondary-900 {
  color: #17395c;
}

/* Product Title Color */
.text-secondary-500 {
  color: #17395c;
}

.font-primary {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* Product card hover & click scale-90 matching blade */
.scale-90 {
  transform: scale(0.90);
}
</style>