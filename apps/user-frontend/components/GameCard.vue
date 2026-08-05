<script setup lang="ts">
const props = defineProps<{
  item: {
    id: number;
    title: string;
    slug: string;
    image_url: string;
    price_from: number;
  };
}>();
</script>

<template>
  <NuxtLink
    :to="`/topup/${props.item.slug}`"
    class="group overflow-hidden rounded-[10px] border border-slate-300 bg-white transition hover:-translate-y-0.5"
  >
    <img :src="props.item.image_url" :alt="props.item.title" class="h-28 w-full object-cover" loading="lazy" decoding="async" />
    <div class="p-3 text-center">
      <h3 class="text-[15px] font-black leading-5 text-[#1c3f63]">{{ props.item.title }}</h3>
      <div class="mt-2 text-sm font-semibold text-[#18823f]">৳{{ props.item.price_from }}</div>
      <div class="mt-1 text-xs text-slate-500">Instant top-up</div>
      <div class="mt-3 flex items-center justify-center">
        <span class="rounded-md bg-[#18823f] px-3 py-1 text-xs font-semibold text-white">Buy Now</span>
      </div>
    </div>
  </NuxtLink>
</template>
