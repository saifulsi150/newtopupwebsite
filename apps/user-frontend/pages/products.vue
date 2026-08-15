<script setup lang="ts">
const { data, pending } = await useFetch('/api/products');
const products = computed(() => data.value?.products || []);

const clickedProductId = ref<number | null>(null);

function onProductClick(id: number) {
  clickedProductId.value = id;
}

function resolveTitle(item: any) {
  return item?.name || item?.title || 'Top-up';
}

function handleProductImageError(event: Event) {
  const target = event.target as HTMLImageElement | null;
  if (!target) return;
  target.onerror = null;
  target.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
}
</script>

<template>
  <div class="products-page">
    <h1 class="page-title">TopUp Catalog</h1>

    <div v-if="pending" class="status-msg">
      Loading products...
    </div>

    <div v-else class="product-grid">
      <NuxtLink
        v-for="item in products"
        :key="item.id"
        :to="`/topup/${item.slug}`"
        class="product-card"
        @click="onProductClick(item.id)"
      >
        <div class="product-img-wrap" :class="{ 'is-pressed': clickedProductId === item.id }">
          <img :src="item.image_url" :alt="resolveTitle(item)" loading="lazy" decoding="async" @error="handleProductImageError" />
        </div>
        <p class="product-title">{{ resolveTitle(item) }}</p>
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped>
.products-page {
  padding-top: 12px;
  max-width: 1200px;
  margin: 0 auto;
  padding-bottom: 24px;
}

.page-title {
  text-align: center;
  font-size: 22px;
  font-weight: 800;
  color: #17395c;
  margin-bottom: 14px;
  letter-spacing: -0.2px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  column-gap: 12px;
  row-gap: 14px;
  padding: 0 14px;
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
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover & Active Scale 90 */
.product-card:hover .product-img-wrap,
.product-card:active .product-img-wrap,
.product-img-wrap.is-pressed {
  transform: scale(0.90);
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

.status-msg {
  text-align: center;
  padding: 30px;
  color: #64748b;
  font-size: 13px;
}
</style>
