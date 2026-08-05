<script setup lang="ts">
const reason = useRoute().query.reason;
</script>

<template>
  <section class="bg-[#f1f6fc] px-4 py-6 font-sans">
    <div class="w-full max-w-md mx-auto">
      <div class="rounded-2xl bg-white border border-rose-200 shadow-sm p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100">
          <svg class="h-9 w-9 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="mt-4 text-xl font-black text-slate-900">Payment Cancelled</h1>
        <p class="mt-2 text-slate-500 text-sm">
          আপনি payment cancel করেছেন। কোনো টাকা কাটা হয়নি।
        </p>
        <div class="mt-6 flex flex-col gap-2">
          <NuxtLink to="/add-money" class="block w-full rounded-lg bg-[#0b6e35] py-2.5 text-sm font-bold text-white text-center hover:bg-[#085a2b]">
            আবার চেষ্টা করুন
          </NuxtLink>
          <NuxtLink to="/" class="block w-full rounded-lg border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-700 text-center hover:bg-slate-50">
            Home-এ ফিরুন
          </NuxtLink>
        </div>
      </div>
    </div>
  </section>
</template>
