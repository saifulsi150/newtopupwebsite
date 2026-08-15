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

// Slider
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
function goToSlide(i: number) { currentSlide.value = i; startSlider(); }
onMounted(() => { if (sliderItems.value.length > 1) startSlider(); });
onUnmounted(() => { if (sliderTimer) clearInterval(sliderTimer); });

function onProductClick(id: number) { clickedProductId.value = id; }

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
  <div class="p-2">
    <!-- ===== NOTICE BOX ===== -->
    <div v-if="!noticeDismissed && noticeText" class="notice-container container m-auto">
      <div
        class="notice-style alert-dismissible position-relative"
        role="alert"
        :style="{ backgroundColor: noticeBgColor, color: noticeTextColor }"
      >
        <button class="btn-close-custom" type="button" aria-label="Close" @click="noticeDismissed = true">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
        </button>
        <div class="notice-heading">{{ noticeTitle }}</div>
        <div class="notice-text mb-0">{{ noticeText }}</div>
      </div>
    </div>

    <!-- ===== SLIDER ===== -->
    <section v-if="sliderItems.length" class="container m-auto">
      <section class="carousel my-4" dir="ltr" aria-label="Gallery" tabindex="0">
        <div class="carousel__viewport">
          <div
            class="carousel__track"
            :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
          >
            <div v-for="(item, i) in sliderItems" :key="i" class="carousel__slide">
              <div class="carousel__item">
                <a :href="item.link_url || '#'" :target="item.link_url ? '_blank' : '_self'" rel="noopener">
                  <img :src="item.image_url" :alt="item.title || 'Slider'" class="rounded-md w-full" @error="handleImageError" />
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Prev/Next buttons -->
        <button v-if="sliderItems.length > 1" type="button" class="carousel__prev" @click="goToSlide((currentSlide - 1 + sliderItems.length) % sliderItems.length)" aria-label="Previous">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6z"/></svg>
        </button>
        <button v-if="sliderItems.length > 1" type="button" class="carousel__next" @click="goToSlide((currentSlide + 1) % sliderItems.length)" aria-label="Next">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>
        </button>

        <!-- Pagination dots -->
        <ol class="carousel__pagination">
          <li v-for="(_, i) in sliderItems" :key="i" class="carousel__pagination-item">
            <button
              type="button"
              class="carousel__pagination-button"
              :class="{ 'carousel__pagination-button--active': currentSlide === i }"
              :aria-label="`Slide ${i + 1}`"
              @click="goToSlide(i)"
            />
          </li>
        </ol>
      </section>
    </section>

    <!-- ===== PRODUCTS BY CATEGORY ===== -->
    <template v-if="categoryGroups.length">
      <section v-for="cat in categoryGroups" :key="cat.title" class="my-2" id="topup">
        <div class="container mx-auto">
          <!-- Category Title -->
          <div class="text-center">
            <div class="flex items-center justify-center px-4 mt-0 md:mt-2 pb-4">
              <h3 class="text-2xl sm:text-3xl text-center font-bold mx-4" style="color:#17395c;">
                {{ cat.title }}
              </h3>
            </div>
          </div>

          <!-- Grid: grid-cols-3 gap-4 | md:grid-cols-6 md:gap-8 -->
          <div class="pb-1 md:pb-10">
            <div class="md:py-5 md:px-0 grid md:grid-cols-6 sm:grid-cols-4 grid-cols-3 md:gap-8 gap-4">
              <div
                v-for="item in cat.products"
                :key="item.id"
                class="single-game-product mb-2 md:mb-6"
              >
                <NuxtLink
                  :to="`/topup/${item.slug}`"
                  class="block"
                  style="text-decoration:none;"
                  @click="onProductClick(item.id)"
                >
                  <!-- Image with hover:scale-90 -->
                  <div class="cursor-pointer">
                    <div
                      class="inset-0 transform transition duration-300 product-img-container"
                      :class="clickedProductId === item.id ? 'scale-pressed' : ''"
                    >
                      <div class="h-full w-full text-center mx-auto">
                        <img
                          :src="item.image_url"
                          :alt="item.title"
                          class="rounded-md w-full"
                          style="aspect-ratio:1/1; object-fit:cover; display:block;"
                          loading="lazy"
                          decoding="async"
                          @error="handleImageError"
                        />
                      </div>
                    </div>
                  </div>
                  <!-- Title -->
                  <div>
                    <p class="capitalize text-xs text-center font-bold product-card-title">
                      {{ item.title }}
                    </p>
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
/* ===== NOTICE ===== */
.notice-style {
  padding: 8px 36px 8px 12px;
  border-radius: 0px;
  position: relative;
  margin-bottom: 8px;
}

.btn-close-custom {
  position: absolute;
  top: 8px;
  right: 10px;
  background: transparent;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.8;
}
.btn-close-custom:hover { opacity: 1; }

.notice-heading {
  font-size: 16px;
  font-weight: 700;
  padding-bottom: 3px;
  line-height: 1.2;
}

.notice-text {
  font-size: 12px;
  font-weight: 400;
  font-family: "Times New Roman", Times, serif;
  line-height: 1.5;
}

/* ===== SLIDER ===== */
.carousel {
  position: relative;
  overflow: hidden;
}

.carousel__viewport {
  overflow: hidden;
  border-radius: 8px;
}

.carousel__track {
  display: flex;
  transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
}

.carousel__slide {
  min-width: 100%;
  flex-shrink: 0;
}

.carousel__item img {
  display: block;
  width: 100%;
  height: auto;
}

.carousel__prev,
.carousel__next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.35);
  border: none;
  color: #fff;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 5;
  transition: background 0.2s;
}
.carousel__prev:hover, .carousel__next:hover { background: rgba(0,0,0,0.6); }
.carousel__prev { left: 8px; }
.carousel__next { right: 8px; }

.carousel__pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 5px;
  list-style: none;
  padding: 0;
  margin: 8px auto 4px;
}

.carousel__pagination-button {
  width: 16px;
  height: 4px;
  border-radius: 2px;
  background: #c0c8d8;
  border: none;
  cursor: pointer;
  padding: 0;
  transition: background 0.2s, width 0.2s;
}
.carousel__pagination-button--active {
  background: #17395c;
  width: 24px;
}

/* ===== PRODUCT CARD ===== */
.product-img-container {
  transform: scale(1);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
}

/* Hover: scale down */
.single-game-product:hover .product-img-container {
  transform: scale(0.90);
}

/* Clicked / pressed state */
.product-img-container.scale-pressed {
  transform: scale(0.90);
}

/* Title */
.product-card-title {
  color: #17395c;
  font-size: 11px;
  font-weight: 700;
  padding-top: 6px;
  line-height: 1.3;
  word-break: break-word;
}

@media (min-width: 768px) {
  .product-card-title {
    font-size: 12px;
  }
}
</style>