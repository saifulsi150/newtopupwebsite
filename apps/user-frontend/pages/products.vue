<script setup lang="ts">
const { data, pending } = await useFetch('/api/products');
const products = computed(() => data.value?.products || []);

function resolveTitle(item: any) {
  return item?.name || item?.title || 'Top-up Service';
}

function resolveDescription(item: any) {
  return item?.description || item?.subtitle || 'Fast, secure recharge for your favorite game.';
}

function resolvePrice(item: any) {
  return item?.price_from ?? item?.price ?? 0;
}
</script>

<template>
  <section class="page-shell">
    <div class="card-panel p-8 lg:p-10">
      <div class="mb-8">
        <div class="section-title">Top-Up Catalog</div>
        <h1 class="section-heading">Buy your favorite game credits</h1>
        <p class="mt-3 max-w-2xl muted-text">Choose a package and complete a secure payment with instant delivery.</p>
      </div>

      <div v-if="pending" class="card-soft p-10 text-center text-slate-500">Loading products...</div>
      <div v-else class="grid grid-cols-3 gap-x-2 gap-y-5 justify-items-center">
        <NuxtLink
          v-for="item in products"
          :key="item.id"
          :to="`/topup/${item.slug}`"
          class="product-press group block w-full max-w-[104px] text-center"
        >
          <img :src="item.image_url" :alt="resolveTitle(item)" class="product-thumb mx-auto" loading="lazy" decoding="async" />
          <h3 class="product-title">{{ resolveTitle(item) }}</h3>
          <div class="mt-1 text-theme text-[12px] font-bold">৳{{ resolvePrice(item) }}</div>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<style scoped>
.text-theme {
  color: var(--theme-color);
}
</style>
