<script setup lang="ts">
const route = useRoute();
const invoiceId = computed(() => String(route.query.invoice_id || '').trim());
const status = ref<'loading' | 'success' | 'pending' | 'error'>('loading');
const paymentInfo = ref<any>(null);
const message = ref('');
const auth = useAuth();

onMounted(async () => {
  if (!invoiceId.value) {
    status.value = 'error';
    message.value = 'No invoice ID found.';
    return;
  }

  try {
    const res = await $fetch<any>('/api/payment/uddoktapay/verify', {
      method: 'POST',
      body: { invoice_id: invoiceId.value }
    });

    paymentInfo.value = res;

    if (res?.verified && res?.status === 'COMPLETED') {
      status.value = 'success';
      const meta = res?.metadata || {};

      // Refresh user balance from server
      try {
        const meRes = await $fetch<any>('/api/user/me');
        if (meRes?.user) {
          auth.setUser(meRes.user);
        }
      } catch {}

      if (String(meta.type) === 'add_money') {
        message.value = `৳${res.amount} সফলভাবে Wallet-এ যোগ হয়েছে!`;
      } else if (String(meta.type) === 'order') {
        message.value = `Payment সম্পন্ন হয়েছে! Order #${meta.order_id} processing হচ্ছে।`;
      } else {
        message.value = 'Payment সম্পন্ন হয়েছে!';
      }
    } else {
      status.value = 'pending';
      message.value = 'Payment এখনো confirm হয়নি। কিছুক্ষণ পরে আবার চেক করুন।';
    }
  } catch (e: any) {
    status.value = 'error';
    message.value = e?.data?.message || 'Payment verify করতে সমস্যা হয়েছে।';
  }
});
</script>



<template>
  <section class="bg-[#f1f6fc] px-4 py-6 font-sans">
    <div class="w-full max-w-md mx-auto">

      <!-- Loading -->
      <div v-if="status === 'loading'" class="rounded-2xl bg-white border border-slate-200 shadow-sm p-8 text-center">
        <svg class="mx-auto h-12 w-12 animate-spin text-[#18823f]" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        <p class="mt-4 text-slate-600 font-medium">Payment verify করা হচ্ছে...</p>
      </div>

      <!-- Success -->
      <div v-else-if="status === 'success'" class="rounded-2xl bg-white border border-emerald-200 shadow-sm p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
          <svg class="h-9 w-9 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="mt-4 text-2xl font-black text-slate-900">Payment Successful!</h1>
        <p class="mt-2 text-slate-600 text-sm">{{ message }}</p>

        <div v-if="paymentInfo" class="mt-5 rounded-xl bg-slate-50 border border-slate-100 p-4 text-left space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500">Amount</span>
            <span class="font-bold text-slate-800">৳{{ paymentInfo.amount }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Method</span>
            <span class="font-bold text-slate-800">{{ paymentInfo.payment_method || 'Mobile Banking' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Transaction</span>
            <span class="font-mono text-xs text-slate-700">{{ paymentInfo.transaction_id || invoiceId }}</span>
          </div>
        </div>

        <div class="mt-6 flex flex-col gap-2">
          <NuxtLink to="/orders" class="block w-full rounded-lg bg-[#0b6e35] py-2.5 text-sm font-bold text-white text-center hover:bg-[#085a2b]">
            My Orders দেখুন
          </NuxtLink>
          <NuxtLink to="/account" class="block w-full rounded-lg border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-700 text-center hover:bg-slate-50">
            Account-এ যান
          </NuxtLink>
        </div>
      </div>

      <!-- Pending -->
      <div v-else-if="status === 'pending'" class="rounded-2xl bg-white border border-yellow-200 shadow-sm p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100">
          <svg class="h-9 w-9 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h1 class="mt-4 text-xl font-black text-slate-900">Payment Pending</h1>
        <p class="mt-2 text-slate-600 text-sm">{{ message }}</p>
        <NuxtLink to="/orders" class="mt-6 block w-full rounded-lg bg-[#0b6e35] py-2.5 text-sm font-bold text-white text-center hover:bg-[#085a2b]">
          Orders দেখুন
        </NuxtLink>
      </div>

      <!-- Error -->
      <div v-else class="rounded-2xl bg-white border border-rose-200 shadow-sm p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100">
          <svg class="h-9 w-9 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="mt-4 text-xl font-black text-slate-900">Something went wrong</h1>
        <p class="mt-2 text-slate-600 text-sm">{{ message }}</p>
        <NuxtLink to="/" class="mt-6 block w-full rounded-lg bg-slate-800 py-2.5 text-sm font-bold text-white text-center hover:bg-slate-700">
          Home-এ ফিরুন
        </NuxtLink>
      </div>

    </div>
  </section>
</template>
