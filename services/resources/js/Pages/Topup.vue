<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SiteLayout from '../Layouts/SiteLayout.vue';

const props = defineProps({
  settings: { type: Object, required: true },
  auth: { type: Object, default: () => ({ user: null }) },
  product: { type: Object, required: true },
});

const selectedVariationId = ref(props.product.variations?.[0]?.id ?? null);
const quantity = ref(1);

const selectedVariation = computed(() => {
  return props.product.variations?.find((variation) => variation.id === selectedVariationId.value) || null;
});

const imageUrl = (path) => `/uploads/${path}`;
</script>

<template>
  <Head :title="product.title" />
  <SiteLayout :settings="settings" :auth="auth">
    <div class="p-2 container m-auto checkout_page">
      <div class="bg-white border rounded-md p-3">
        <div class="flex items-center gap-4">
          <img class="rounded-3xl w-24 h-24 object-cover" :src="imageUrl(product.image)" :alt="product.title" fetchpriority="high" decoding="async">
          <div>
            <h2 class="text-lg capitalize">{{ product.title }}</h2>
            <div class="text-gray-400 text-sm text-left">
              <span>{{ product.type }}</span>
            </div>
          </div>
        </div>
      </div>

      <form method="POST" action="/topup/buynow" class="md:flex gap-2 mt-2">
        <input type="hidden" name="variation_id" :value="selectedVariationId">
        <input type="hidden" name="variation_price" :value="selectedVariation?.price || 0">
        <input type="hidden" name="payment_method" :value="settings.wallet ? 'wallet' : 'payment_gateway'">

        <section class="w-full md:w-2/3 mt-2">
          <div class="bg-white border rounded-md">
            <div class="text-left px-3 flex items-center">
              <div class="_order_header_step_circle mr-2">1</div>
              <h2 class="text-lg text-black py-2 font-normal fb"> Select Recharge </h2>
            </div>
            <hr>
            <div class="p-1 md:p-4 inline-grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-2 package-item-outer w-full">
              <button
                v-for="variation in product.variations"
                :key="variation.id"
                type="button"
                class="sm-device-package mb-0 w-full drop-shadow-2xl list-group-item flex content-between p-2 active:order-0"
                :class="{ 'ring-2 ring-[var(--theme-color)]': selectedVariationId === variation.id }"
                style="font-size: 11px; position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center; height: 50px;"
                @click="selectedVariationId = variation.id"
              >
                <div class="w-full flex flex-wrap items-center">
                  <span class="text-xs font-primary">{{ variation.title }}</span>
                </div>
                <div class="font-bold fb-normal" style="color: var(--theme-color); min-width: 46px; float: right; text-align: right;">{{ variation.price }}</div>
              </button>
            </div>
          </div>
        </section>

        <div class="w-full md:w-1/3 mt-2">
          <section>
            <div class="border bg-white rounded-md">
              <div class="text-left px-3 flex items-center">
                <div class="_order_header_step_circle mr-2">2</div>
                <h2 class="text-lg text-black py-2 font-bold fb-normal"> Account Info </h2>
              </div>
              <hr>
              <div class="p-3">
                <div class="relative">
                  <label class="label-title">Player ID</label>
                  <input name="account_info[player_id]" type="text" placeholder="Player ID" class="form-input relative block w-full border-0 rounded-md text-sm px-2.5 py-2.5 shadow-sm bg-transparent text-gray-900 ring-1 ring-inset focus:ring-2 focus:ring-black-900">
                </div>
              </div>
            </div>
          </section>

          <section class="mt-2">
            <div class="bg-white border rounded-md p-3">
              <div class="flex items-center justify-between">
                <span class="font-primary">Quantity</span>
                <div class="flex items-center border-2 border-gray-200 rounded-full px-2">
                  <button type="button" class="px-2" @click="quantity = Math.max(1, quantity - 1)">-</button>
                  <input v-model="quantity" type="number" min="1" class="h-10 w-16 border-transparent text-center bg-white">
                  <button type="button" class="px-2" @click="quantity = quantity + 1">+</button>
                </div>
              </div>
            </div>
          </section>

          <section class="mt-2">
            <div class="bg-white border rounded-md p-3">
              <div class="flex gap-2">
                <button type="submit" class="align-middle bg-pink-500 hover:bg-pink-400 text-center px-4 py-2 text-white text-sm font-semibold rounded inline-block shadow-lg w-full">Buy Now</button>
              </div>
              <div class="mt-3 text-xs text-gray-500">
                <Link href="/" class="text-pink-500">Back to home</Link>
              </div>
            </div>
          </section>
        </div>
      </form>
    </div>
  </SiteLayout>
</template>
