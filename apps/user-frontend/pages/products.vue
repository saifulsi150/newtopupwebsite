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
  font-size: 20px;
  font-weight: 800;
  color: #17395c;
  margin-bottom: 14px;
  letter-spacing: -0.2px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding: 0 10px;
}

@media (min-width: 640px) {
  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(105px, 120px));
    gap: 12px;
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
  max-width: 115px;
  aspect-ratio: 1/1;
  border-radius: 8px;
  overflow: hidden;
  background: #f1f6fc;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  transition: transform 0.08s ease-out;
}

/* Firm Inward Press */
.product-card:active .product-img-wrap,
.product-img-wrap.is-pressed {
  transform: scale(0.91);
}

.product-img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.product-title {
  color: #17395c;
  font-size: 10.5px;
  font-weight: 700;
  text-align: center;
  margin-top: 4px;
  line-height: 1.2;
  word-break: break-word;
  max-width: 115px;
}

.status-msg {
  text-align: center;
  padding: 30px;
  color: #64748b;
  font-size: 13px;
}
</style>
