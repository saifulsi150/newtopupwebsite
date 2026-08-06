<script setup lang="ts">
definePageMeta({ middleware: 'auth' });

type OrderDetails = {
  id: number;
  package: string;
  player_id: string;
  quantity: number;
  payment_method: string;
  price: number;
  status: string;
  created_at: string;
  updated_at: string;
  note?: string;
  admin_note?: string;
  delivery_message?: string;
};

const route = useRoute();
const orderId = computed(() => Number(route.params.id || 0));

const { data, pending, error } = await useFetch<{ order?: OrderDetails }>(
  () => `/api/orders/${orderId.value}`,
  { key: () => `order-success-${orderId.value}` }
);

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
  if (normalized === 'complete') return 'text-theme';
  if (normalized === 'cancel') return 'text-rose-600';
  if (normalized === 'running') return 'text-sky-600';
  if (normalized === 'looking') return 'text-indigo-600';
  return 'text-amber-600';
}

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
</script>

<template>
  <section class="bg-[#f1f6fc] px-3 py-5 md:px-6 md:py-8">
    <div class="mx-auto w-full max-w-xl rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-5 py-4 text-center">
        <h1 class="text-theme text-xl font-bold">Order Successful</h1>
        <p class="mt-1 text-sm text-slate-500">Your order has been placed successfully.</p>
      </div>

      <div v-if="pending" class="px-5 py-8 text-center text-sm text-slate-500">Loading order details...</div>

      <div v-else-if="error || !data?.order" class="px-5 py-8 text-center">
        <p class="text-sm font-semibold text-rose-600">Order তথ্য পাওয়া যায়নি।</p>
        <NuxtLink to="/orders" class="theme-btn mt-4 inline-block rounded px-4 py-2 text-sm font-semibold text-white">
          Go to Orders
        </NuxtLink>
      </div>

      <div v-else class="px-5 py-5">
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-center">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order ID</p>
          <p class="text-2xl font-bold text-slate-900">#{{ data.order.id }}</p>
        </div>

        <div class="space-y-2 text-sm">
          <div class="flex items-center justify-between border-b border-dashed border-slate-200 py-2">
            <span class="font-semibold text-slate-600">Package</span>
            <span class="font-semibold text-slate-900">{{ data.order.package }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-dashed border-slate-200 py-2">
            <span class="font-semibold text-slate-600">Player ID</span>
            <span class="font-semibold text-slate-900">{{ data.order.player_id }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-dashed border-slate-200 py-2">
            <span class="font-semibold text-slate-600">Quantity</span>
            <span class="font-semibold text-slate-900">{{ data.order.quantity }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-dashed border-slate-200 py-2">
            <span class="font-semibold text-slate-600">Amount</span>
            <span class="text-theme font-semibold">৳ {{ Number(data.order.price || 0).toFixed(2) }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-dashed border-slate-200 py-2">
            <span class="font-semibold text-slate-600">Payment</span>
            <span class="font-semibold text-slate-900">{{ data.order.payment_method }}</span>
          </div>
          <div class="flex items-center justify-between border-b border-dashed border-slate-200 py-2">
            <span class="font-semibold text-slate-600">Status</span>
            <span :class="statusClass(data.order.status)" class="font-bold uppercase">{{ statusText(data.order.status) }}</span>
          </div>
          <div class="flex items-center justify-between py-2">
            <span class="font-semibold text-slate-600">Date</span>
            <span class="font-semibold text-slate-900">{{ formatDate(data.order.created_at) }}</span>
          </div>
        </div>

        <p v-if="data.order.note || data.order.admin_note || data.order.delivery_message" class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
          {{ data.order.admin_note || data.order.note || data.order.delivery_message }}
        </p>

        <div class="mt-5 grid gap-2 sm:grid-cols-2">
          <NuxtLink to="/orders" class="theme-btn rounded px-4 py-2 text-center text-sm font-semibold text-white">My Orders</NuxtLink>
          <NuxtLink to="/" class="rounded border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700">Back to Home</NuxtLink>
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
