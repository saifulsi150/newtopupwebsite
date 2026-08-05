<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  settings: { type: Object, required: true },
  auth: { type: Object, default: () => ({ user: null }) },
  compact: { type: Boolean, default: false },
});

const isLoggedIn = computed(() => !!props.auth?.user);
const whatsappUrl = computed(() => {
  const number = props.settings?.whatsapp_number || '';
  return number.startsWith('http') ? number : `https://wa.me/+88${number}`;
});
</script>

<template>
  <div class="body-bg min-h-screen">
    <header class="header bg-white/95 backdrop-blur-sm border-b border-slate-200">
      <div class="container m-auto p-2 py-3 md:py-5 md:px-0">
        <nav class="flex items-center justify-between gap-4">
          <Link href="/" class="shrink-0">
            <img :src="`/uploads/${settings.logo}`" :alt="settings.site_name" class="w-28 md:w-48 logo" fetchpriority="high" decoding="async" />
          </Link>
          <div class="flex items-center gap-3 ml-auto">
            <nav class="hidden md:block">
              <div class="text-sm flex items-center gap-4 font-semibold text-slate-700">
                <Link href="/#topup" class="hover:text-[var(--theme-color)]">Topup</Link>
                <Link href="/contactus" class="hover:text-[var(--theme-color)]">Contact Us</Link>
              </div>
            </nav>
            <Link
              v-if="isLoggedIn"
              href="/account"
              class="flex items-center text-md px-4 py-2 shadow-md hover:shadow-2xl border rounded-full text-white font-primary"
              :style="{ backgroundColor: 'var(--theme-color)' }"
            >
              <span class="ml-1">{{ auth.user?.balance ?? 0 }}৳</span>
            </Link>
            <Link v-else href="/login" class="btn-pro btn-register rounded ml-2 border-2 border-pink-500 bg-pink-500 text-white px-4 py-2">
              Login
            </Link>
          </div>
        </nav>
      </div>
    </header>

    <main>
      <slot />
    </main>

    <footer class="mb-16 md:mb-0 text-gray-200 border-t-2 footer-bg">
      <section class="container mx-auto pb-8">
        <div class="w-full md:w-4/6 m-auto flex flex-wrap my-0">
          <div class="w-full md:w-1/2 px-5 md:px-0">
            <div class="text-lg fb mt-10 uppercase text-white font-normal tracking-wider footer-title">STAY CONNECTED</div>
            <div class="mt-2 text-sm text-white/90 leading-6">কোন সমস্যায় পড়লে telegram এ যোগাযোগ করবেন। তাহলে দ্রুত সমাধান পেয়ে যাবেন।</div>
            <div class="mt-4 flex flex-wrap gap-4">
              <a :href="settings.facebook_link" target="_blank" rel="noopener" class="text-white">Facebook</a>
              <a :href="settings.messenger_link" target="_blank" rel="noopener" class="text-white">Messenger</a>
              <a :href="settings.youtube_link" target="_blank" rel="noopener" class="text-white">YouTube</a>
              <a :href="`mailto:${settings.email_address}`" class="text-white">Email</a>
            </div>
          </div>
          <div class="w-full md:w-2/6 pt-5 px-5 md:px-0 md:ml-auto">
            <div class="text-lg fb mt-1 uppercase text-white font-normal tracking-wider pb-3 footer-title">SUPPORT CENTER</div>
            <a :href="whatsappUrl" target="_blank" class="block rounded-md p-3 mt-2 flex footer-contact-icon1 border text-white no-underline">
              <div class="ml-2 pl-2" style="border-left:2px solid #b1b1b1;">
                <span class="number">Whatsapp HelpLine</span>
              </div>
            </a>
            <a :href="settings.telegram_link" target="_blank" class="block rounded-md p-3 mt-2 flex footer-contact-icon1 border text-white no-underline">
              <div class="ml-2 pl-2" style="border-left:2px solid #b1b1b1;">
                <span class="number">টেলিগ্রামে সাপোর্ট</span>
              </div>
            </a>
          </div>
        </div>
      </section>
      <div :style="{ borderTop: '2px solid #c1bcbc1c', backgroundColor: settings.footer_color || '#0b1150' }">
        <div class="pb-5 px-5 m-auto pt-5 text-sm flex flex-col items-center justify-center text-white text-center">© Copyright 2025. All Rights Reserved. Developer</div>
      </div>
    </footer>
  </div>
</template>
