<script setup lang="ts">
definePageMeta({ middleware: 'auth' });
const amount = ref<number | null>(null);
const message = ref('');
const loading = ref(false);

const messageClass = computed(() => {
  const text = String(message.value || '').toLowerCase();
  return text.includes('failed') || text.includes('minimum') || text.includes('could not') || text.includes('error')
    ? 'text-rose-600'
    : 'text-theme';
});

async function submit() {
  if (!Number.isFinite(Number(amount.value)) || Number(amount.value) < 10) {
    message.value = 'Minimum amount is 10 BDT';
    return;
  }
  loading.value = true;
  message.value = '';
  try {
    const res = await $fetch<any>('/api/wallet/add-money', { method: 'POST', body: { amount: amount.value } });
    if (res?.redirect_url) {
      // Redirect to UddoktaPay payment page
      window.location.href = res.redirect_url;
    } else {
      message.value = 'Could not create payment';
    }
  } catch (e: any) {
    message.value = e?.data?.message || 'Add money failed';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="bg-[#f1f6fc] px-0 py-3 md:px-6 md:py-6 font-sans">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-4 md:gap-5">

      <!-- Top Form Card -->
      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 md:px-5">
          <h1 class="font-serif text-[22px] font-bold text-slate-900 md:text-xl">Add Money</h1>
        </div>

        <div class="p-4 md:p-5 space-y-3.5">
          <label class="block">
            <span class="font-serif text-[16px] font-bold text-slate-800 md:text-sm">Enter the amount</span>
            <input
              v-model.number="amount"
              type="number"
              min="10"
              inputmode="numeric"
              placeholder="Amount"
              class="theme-input mt-1.5 w-full rounded-md border border-slate-300 px-3.5 py-2 text-[14px] text-slate-800 outline-none transition placeholder:text-slate-400 md:text-sm"
            />
          </label>

          <!-- Payment logos -->
          <div class="flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 border border-slate-100 px-3 py-2.5">
            <span class="text-xs text-slate-500 font-medium mr-1">Pay via:</span>
            <img src="https://cdn.uddoktapay.com/assets/imgs/bkash.svg" alt="bKash" class="h-6 w-auto" onerror="this.style.display='none'" />
            <img src="https://cdn.uddoktapay.com/assets/imgs/nagad.svg" alt="Nagad" class="h-6 w-auto" onerror="this.style.display='none'" />
            <img src="https://cdn.uddoktapay.com/assets/imgs/rocket.svg" alt="Rocket" class="h-6 w-auto" onerror="this.style.display='none'" />
            <span class="text-[11px] font-semibold text-slate-600 bg-pink-50 border border-pink-100 rounded px-1.5 py-0.5">bKash</span>
            <span class="text-[11px] font-semibold text-slate-600 bg-orange-50 border border-orange-100 rounded px-1.5 py-0.5">Nagad</span>
            <span class="text-[11px] font-semibold text-slate-600 bg-purple-50 border border-purple-100 rounded px-1.5 py-0.5">Rocket</span>
          </div>

          <button
            type="button"
            :disabled="loading"
            class="theme-btn w-full rounded py-2.5 text-[13px] font-bold text-white transition active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 md:text-sm flex items-center justify-center gap-2"
            @click="submit"
          >
            <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            {{ loading ? 'Redirecting to payment...' : 'Click Here To Add Money' }}
          </button>

          <p v-if="message" class="text-xs md:text-sm font-semibold pt-1" :class="messageClass">
            {{ message }}
          </p>
        </div>
      </div>

      <!-- Bottom Tutorial/Video Card -->
      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 md:px-5">
          <svg class="h-5 w-5 text-[#86efac]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <rect x="2" y="3" width="20" height="14" rx="2" />
            <path d="M8 21h8" />
            <path d="M12 17v4" />
          </svg>
          <h2 class="font-serif text-[18px] font-bold text-slate-900 md:text-lg">How to add money</h2>
        </div>
        <div class="p-3 md:p-4">
          <div class="flex h-[240px] w-full items-center justify-center rounded bg-[#e3e3e3] md:h-[320px]">
            <svg class="h-14 w-14 text-[#9aa0a6]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              <circle cx="10" cy="14" r="0.75" fill="currentColor" />
              <circle cx="14" cy="14" r="0.75" fill="currentColor" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 18c.8-1 2.2-1 3 0" />
            </svg>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<style scoped>
.theme-btn {
  background-color: var(--theme-color);
}
.theme-btn:hover {
  filter: brightness(0.9);
}
.theme-input:focus {
  border-color: var(--theme-color);
}
.text-theme {
  color: var(--theme-color);
}
</style>