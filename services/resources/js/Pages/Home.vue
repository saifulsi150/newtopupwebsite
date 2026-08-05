<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SiteLayout from '../Layouts/SiteLayout.vue';

const props = defineProps({
  settings: { type: Object, required: true },
  auth: { type: Object, default: () => ({ user: null }) },
  categorys: { type: Array, default: () => [] },
  sliders: { type: Array, default: () => [] },
  productsByCategory: { type: Object, default: () => ({}) },
});

const imageUrl = (path) => `/uploads/${path}`;
</script>

<template>
  <Head title="Home" />
  <SiteLayout :settings="settings" :auth="auth">
    <div class="p-2">
      <div v-if="settings.enable_notice" class="notice-container container m-auto">
        <div class="alert alert-light notice-style alert-dismissible fade show position-relative" role="alert">
          <div class="notice-heading">{{ settings.notice_title }}</div>
          <div class="notice-text mb-0">{{ settings.notice_content }}</div>
        </div>
      </div>

      <section v-if="sliders.length" class="container m-auto my-4">
        <img :src="imageUrl(sliders[0].image_url)" class="rounded-md w-full" :alt="sliders[0].title || 'slider'" fetchpriority="high" decoding="async" />
      </section>

      <section v-for="category in categorys" :key="category.id" class="my-2" id="topup">
        <div class="container mx-auto">
          <div class="text-center">
            <div class="flex items-center justify-center px-4 mt-0 md:mt-2 section-contact-gap pb-4">
              <h3 class="text-2xl sm:text-3xl text-center font-primary font-bold mx-4 text-secondary-900">{{ category.title }}</h3>
            </div>
          </div>

          <div class="pb-1 md:pb-10">
            <div class="md:py-5 md:px-0 grid md:grid-cols-6 sm:grid-cols-4 grid-cols-2 md:gap-8 gap-4">
              <Link
                v-for="product in (productsByCategory[category.id] || [])"
                :key="product.id"
                :href="`/topup/${product.slug}`"
                class="single-game-product mb-2 md:mb-6 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg transition-shadow no-underline"
                prefetch
              >
                <div class="cursor-pointer p-2">
                  <div class="h-full w-full text-center mx-auto">
                    <img :src="imageUrl(product.image)" class="rounded-md w-full" :alt="product.title" loading="lazy" decoding="async" />
                  </div>
                </div>
                <div class="px-2 pb-3">
                  <h1 class="capitalize text-xs text-center pt-1 font-primary font-extralight text-secondary-500">{{ product.title }}</h1>
                </div>
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  </SiteLayout>
</template>
