<script setup lang="ts">
definePageMeta({ middleware: 'auth' });

const orders = ref<any[]>([]);
const pending = ref(true);
const loadError = ref('');

async function fetchOrders() {
  pending.value = true;
  loadError.value = '';

  try {
    const res = await $fetch<{ orders?: any[] }>('/api/orders', {
      method: 'GET',
      timeout: 10000,
      retry: 0
    });
    orders.value = Array.isArray(res?.orders) ? res.orders : [];
  } catch (error: any) {
    orders.value = [];
    loadError.value = String(error?.data?.message || error?.message || 'Failed to load orders.');
  } finally {
    pending.value = false;
  }
}

onMounted(fetchOrders);

function formatDate(value: string | null | undefined) {
  if (!value) return '-';
  const dt = new Date(value);
  if (Number.isNaN(dt.getTime())) return String(value);
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  }).format(dt);
}

function statusText(status: string | null | undefined) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'completed' || normalized === 'complete') return 'complete';
  if (normalized === 'cancelled' || normalized === 'canceled' || normalized === 'cancel') return 'cancel';
  if (normalized === 'processing' || normalized === 'running') return 'running';
  if (normalized === 'auto-processing' || normalized === 'autoprocessing' || normalized === 'looking') return 'looking';
  return 'pending';
}

function statusClass(status: string | null | undefined) {
  const normalized = statusText(status);
  if (normalized === 'complete') return 'text-theme font-bold';
  if (normalized === 'cancel') return 'text-rose-600 font-bold';
  if (normalized === 'running') return 'text-sky-600 font-bold';
  if (normalized === 'looking') return 'text-indigo-600 font-bold';
  return 'text-amber-600 font-bold';
}

function statusTime(order: any) {
  return formatDate(order?.completed_at || order?.updated_at || order?.created_at);
}
</script>

<template>
  <section class="bg-[#f1f6fc] px-0 py-3 md:px-6 md:py-6">
    <div class="mx-auto w-full max-w-5xl rounded-lg border border-slate-200 bg-white shadow-sm">
      
      <!-- Card Header -->
      <div class="flex items-center gap-2.5 border-b border-slate-200 px-4 py-3 md:px-6">
        <svg class="h-5 w-5 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
        <h1 class="text-base font-bold text-slate-900 md:text-lg">My Orders</h1>
      </div>

      <!-- Loading State -->
      <div v-if="pending" class="px-5 py-8 text-sm font-semibold text-slate-500">
        Loading orders...
      </div>

      <div v-else-if="loadError" class="px-5 py-8">
        <p class="text-sm font-semibold text-rose-600">{{ loadError }}</p>
        <button type="button" class="theme-btn mt-3 rounded px-3 py-1.5 text-xs font-semibold text-white" @click="fetchOrders">
          Retry
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="orders.length === 0" class="px-5 py-8 text-sm font-semibold text-slate-500">
        No orders found.
      </div>

      <!-- Orders List -->
      <div v-else class="divide-y divide-slate-200">
        <div 
          v-for="order in orders" 
          :key="order.id" 
          class="p-4 md:px-6 md:py-5 text-[15px] leading-relaxed text-slate-800 md:text-[16px]"
        >
          <div class="space-y-1 md:space-y-1.5">
            
            <p>
              <span class="font-bold text-slate-900">Serial NO:</span> {{ order.id }}
            </p>
            <p>
              <span class="font-bold text-slate-900">Date:</span> {{ formatDate(order.created_at) }}
            </p>
            <p>
              <span class="font-bold text-slate-900">Package:</span> {{ order.package }}
            </p>
            <p>
              <span class="font-bold text-slate-900">Player ID:</span> {{ order.player_id }}
            </p>
            <p>
              <span class="font-bold text-slate-900">Price:</span> ৳ {{ Number(order.price || 0).toFixed(2) }}
            </p>
            <p class="flex flex-wrap items-center gap-1">
              <span class="font-bold text-slate-900">Status:</span>
              <span :class="statusClass(order.status)" class="font-bold">
                {{ statusText(order.status) }}
              </span>
              <span class="text-slate-700 text-[14px]">
                ( {{ statusTime(order) }} )
              </span>
            </p>
            <p v-if="order.note || order.admin_note" class="pt-0.5 text-[13px] text-slate-600">
              <span class="inline-block">ⓘ {{ order.note || order.admin_note }}</span>
            </p>
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
.text-theme {
  color: var(--theme-color);
}
</style>