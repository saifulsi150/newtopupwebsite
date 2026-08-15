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
  <div class="p-2">
    <section class="my-2" id="topup">
      <div class="container mx-auto">
        <div class="text-center">
          <div class="flex items-center justify-center px-4 mt-0 md:mt-2 pb-4">
            <h3 class="text-2xl sm:text-3xl text-center font-primary font-bold mx-4 text-secondary-900">
              TopUp Catalog
            </h3>
          </div>
        </div>

        <div v-if="pending" class="text-center py-10 text-slate-500 font-primary">
          Loading products...
        </div>

        <div v-else class="pb-1 md:pb-10">
          <div class="md:py-5 md:px-0 grid md:grid-cols-6 sm:grid-cols-4 grid-cols-3 md:gap-8 gap-4">
            <div
              v-for="product in products"
              :key="product.id"
              class="single-game-product mb-2 md:mb-6"
            >
              <NuxtLink
                :to="`/topup/${product.slug}`"
                class="triangle block text-decoration-none"
                @click="onProductClick(product.id)"
              >
                <div class="cursor-pointer">
                  <div
                    class="inset-0 transform transition duration-300"
                    :class="clickedProductId === product.id ? 'scale-90' : 'hover:scale-90'"
                  >
                    <div class="h-full w-full text-center mx-auto">
                      <img
                        :src="product.image_url"
                        :alt="resolveTitle(product)"
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
                    {{ resolveTitle(product) }}
                  </h1>
                </div>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.text-secondary-900 {
  color: #17395c;
}

.text-secondary-500 {
  color: #17395c;
}

.font-primary {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.scale-90 {
  transform: scale(0.90);
}
</style>
