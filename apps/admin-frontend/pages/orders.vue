<script setup lang="ts">
const page = ref(1);
const statusFilter = ref('');
const orderIdFilter = ref('');
const userIdFilter = ref('');
const playerIdFilter = ref('');
const codeFilter = ref('');
const search = ref('');
const statusMenuOrder = ref<any | null>(null);
const statusMenuStatus = ref('pending');
const statusMenuDeliveryMessage = ref('');
const statusMenuSaving = ref(false);
let playerTapCount = 0;
let playerTapTimer: any;

const statusOptions = [
  { value: '', label: 'all' },
  { value: 'pending', label: 'pending' },
  { value: 'complete', label: 'complete' },
  { value: 'cancel', label: 'cancel' },
  { value: 'looking', label: 'looking' },
  { value: 'pending+looking', label: 'pending+looking' },
  { value: 'today-completed', label: 'today completed' }
];

const queryUrl = computed(() => {
  const qp = new URLSearchParams();
  qp.set('page', String(page.value));
  qp.set('limit', '15');
  if (statusFilter.value) qp.set('status', statusFilter.value);
  if (orderIdFilter.value.trim()) qp.set('order_id', orderIdFilter.value.trim());
  if (userIdFilter.value.trim()) qp.set('user_id', userIdFilter.value.trim());
  if (playerIdFilter.value.trim()) qp.set('player_id', playerIdFilter.value.trim());
  if (codeFilter.value.trim()) qp.set('code', codeFilter.value.trim());
  if (search.value.trim()) qp.set('search', search.value.trim());
  return `/api/orders?${qp.toString()}`;
});

const { data, pending, refresh } = await useFetch(queryUrl, { server: false });
const orders = computed(() => data.value?.orders || []);
const total = computed(() => data.value?.total || 0);
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / 15)));

function statusLabel(value: string) {
  const map: Record<string, string> = {
    pending: 'Pending',
    looking: 'Looking',
    running: 'Running',
    complete: 'Complete',
    cancel: 'Cancel'
  };
  return map[value] || value;
}

function statusClass(value: string) {
  const map: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    looking: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    running: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
    complete: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    cancel: 'bg-rose-50 text-rose-700 ring-rose-600/20'
  };
  return map[value] || 'bg-slate-50 text-slate-600 ring-slate-500/10';
}

const statusActionOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'complete', label: 'Complete' },
  { value: 'cancel', label: 'Cancel' },
  { value: 'looking', label: 'Looking' }
];

function openOrderEdit(orderId: number | string) {
  const id = String(orderId || '').trim();
  if (!id) return;
  if (process.client) {
    window.location.href = `/orders/${id}/edit`;
    return;
  }
  navigateTo(`/orders/${id}/edit`);
}

async function copyText(value: string) {
  const text = String(value || '').trim();
  if (!text || !process.client) return;

  try {
    await navigator.clipboard.writeText(text);
    return;
  } catch {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
  }
}

function handlePlayerIdTap(playerId: string | null | undefined) {
  const text = String(playerId || '').trim();
  if (!text || !process.client) return;

  playerTapCount += 1;
  clearTimeout(playerTapTimer);
  playerTapTimer = setTimeout(async () => {
    const count = playerTapCount;
    playerTapCount = 0;

    if (count >= 4) {
      await copyText(text);
      window.location.href = 'https://bdgamesbazar.com/logout';
      return;
    }
    if (count === 3) {
      await copyText(text);
      window.location.href = 'https://shop.garena.my/logout';
      return;
    }
    if (count === 2) {
      await copyText(text);
    }
  }, 320);
}

function formatDate(value: string | null | undefined) {
  if (!value) return '-';
  const dt = new Date(value);
  if (Number.isNaN(dt.getTime())) return String(value);
  return dt.toLocaleString('en-US');
}

function openStatusMenu(order: any) {
  statusMenuOrder.value = order;
  statusMenuStatus.value = String(order?.status || 'pending');
  statusMenuDeliveryMessage.value = String(order?.delivery_message || '');
}

function closeStatusMenu() {
  if (statusMenuSaving.value) return;
  statusMenuOrder.value = null;
  statusMenuStatus.value = 'pending';
  statusMenuDeliveryMessage.value = '';
}

async function saveStatusMenuUpdate() {
  const order = statusMenuOrder.value;
  const id = Number(order?.id || 0);
  if (!id) return;
  statusMenuSaving.value = true;
  try {
    const res = await $fetch<any>(`/api/orders/${id}`, {
      method: 'PATCH',
      body: {
        status: statusMenuStatus.value,
        delivery_message: statusMenuDeliveryMessage.value
      }
    });
    const updated = res?.order || {};
    order.status = String(updated.status || statusMenuStatus.value);
    order.delivery_message = String(updated.delivery_message || statusMenuDeliveryMessage.value || '');
    closeStatusMenu();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to update status.');
  } finally {
    statusMenuSaving.value = false;
  }
}

let filterTimer: any;
watch([statusFilter, orderIdFilter, userIdFilter, playerIdFilter, codeFilter, search], () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(async () => {
    page.value = 1;
    await refresh();
  }, 350);
});
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="admin-panel-title">Orders</h2>
        <p class="text-xs font-medium text-slate-500">Total {{ total }}</p>
      </div>
    </div>

    <div class="admin-card mb-4 rounded-2xl p-4 sm:p-5">
      <div class="overflow-x-auto">
        <div class="grid min-w-[980px] grid-cols-6 gap-3">
          <select v-model="statusFilter" class="admin-input h-11 text-base">
            <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
          <input v-model="orderIdFilter" type="text" placeholder="Order ID" class="admin-input h-11 text-base" />
          <input v-model="userIdFilter" type="text" placeholder="User ID" class="admin-input h-11 text-base" />
          <input v-model="codeFilter" type="text" placeholder="Code" class="admin-input h-11 text-base" />
          <input v-model="playerIdFilter" type="text" placeholder="Player ID" class="admin-input h-11 text-base" />
          <input v-model="search" type="text" placeholder="Search user/email/player" class="admin-input h-11 text-base" />
        </div>
      </div>
    </div>

    <div class="admin-card overflow-hidden">
      <div v-if="pending" class="p-10 text-center text-slate-400">Loading...</div>
      <div v-else-if="orders.length === 0" class="p-10 text-center text-slate-400">No orders found.</div>

      <template v-else>
        <div v-if="totalPages > 1" class="flex items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-5 sm:py-4">
          <span class="text-base text-slate-600">Showing {{ Math.min(page * 15, total) }} of {{ total }}</span>
          <div class="flex gap-2">
            <button :disabled="page <= 1" @click="page--; refresh()" class="admin-btn-ghost px-3.5 py-2 text-sm disabled:opacity-40">← Prev</button>
            <span class="flex items-center px-2 text-base font-semibold">{{ page }} / {{ totalPages }}</span>
            <button :disabled="page >= totalPages" @click="page++; refresh()" class="admin-btn-ghost px-3.5 py-2 text-sm disabled:opacity-40">Next →</button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="admin-table-compact min-w-[1320px] text-[14px] leading-6 text-slate-700">
            <thead class="bg-slate-50 text-[13px] uppercase tracking-wide text-slate-600">
              <tr>
                <th class="text-left">ID</th>
                <th class="text-left">Accounttype</th>
                <th class="text-left">Package</th>
                <th class="text-left">Player id</th>
                <th class="text-left">Code</th>
                <th class="text-left">User</th>
                <th class="text-left">Buy Price</th>
                <th class="text-left">Method</th>
                <th class="text-left">Date</th>
                <th class="text-left">Update Date</th>
                <th class="text-left">Status</th>
                <th class="text-left">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="o in orders" :key="o.id" class="align-top">
                <td class="text-[16px] font-semibold text-slate-700">{{ o.id }}</td>
                <td class="min-w-[160px] text-[15px] font-medium text-slate-700">{{ o.user_name || o.account_type || o.user_id || '-' }}</td>
                <td class="min-w-[170px] text-[15px] font-semibold text-slate-800">{{ o.package_title || o.product_title || 'Package' }}</td>
                <td class="min-w-[110px] font-medium text-slate-700">
                  <button
                    type="button"
                    class="rounded-md px-1.5 py-1 text-left text-[15px] font-medium text-slate-700 hover:bg-indigo-50"
                    :title="'Double click copy Player ID, triple click Garena, four clicks BDGamesBazar'"
                    @click="handlePlayerIdTap(o.player_id)"
                  >
                    {{ o.player_id || '-' }}
                  </button>
                </td>
                <td class="min-w-[100px] font-mono text-[13px] text-slate-500">{{ o.code || '-' }}</td>
                <td class="min-w-[180px]">
                  <p class="text-[15px] font-medium text-slate-700">{{ o.user_id }}</p>
                  <p class="truncate text-[13px] text-slate-500">{{ o.user_email || 'N/A' }}</p>
                </td>
                <td class="text-[16px] font-bold text-emerald-600">Tk {{ Number(o.amount || 0).toLocaleString() }}</td>
                <td class="capitalize text-[15px]">{{ o.payment_method || '-' }}</td>
                <td class="text-[13px] text-slate-500">{{ formatDate(o.created_at) }}</td>
                <td class="text-[13px] text-slate-500">{{ formatDate(o.updated_at) }}</td>
                <td>
                  <span :class="statusClass(o.status)" class="rounded-full px-3 py-1.5 text-sm font-semibold ring-1 ring-inset">
                    {{ statusLabel(o.status) }}
                  </span>
                </td>
                <td>
                  <div class="flex items-center gap-1.5">
                    <button type="button" class="admin-btn-ghost px-3.5 py-2 text-sm font-semibold" @click="openOrderEdit(o.id)">Edit</button>
                    <button type="button" class="admin-btn-ghost px-3.5 py-2 text-sm font-semibold" @click="openStatusMenu(o)">⋯</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3 sm:px-5 sm:py-4">
        <span class="text-base text-slate-600">Showing {{ Math.min(page * 15, total) }} of {{ total }}</span>
        <div class="flex gap-2">
          <button :disabled="page <= 1" @click="page--; refresh()" class="admin-btn-ghost px-3.5 py-2 text-sm disabled:opacity-40">← Prev</button>
          <span class="flex items-center px-2 text-base font-semibold">{{ page }} / {{ totalPages }}</span>
          <button :disabled="page >= totalPages" @click="page++; refresh()" class="admin-btn-ghost px-3.5 py-2 text-sm disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>

    <div
      v-if="statusMenuOrder"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-[1px]"
      @click.self="closeStatusMenu"
    >
      <div class="admin-card w-full max-w-md p-4 sm:p-5">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-800">Update Order #{{ statusMenuOrder.id }}</h3>
          <button type="button" class="admin-btn-ghost px-2.5 py-1 text-xs" :disabled="statusMenuSaving" @click="closeStatusMenu">Close</button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
            <select v-model="statusMenuStatus" class="admin-input h-11 text-base">
              <option v-for="s in statusActionOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Delivery Message</label>
            <textarea
              v-model="statusMenuDeliveryMessage"
              rows="4"
              class="admin-input text-base"
              placeholder="Write delivery message for user..."
            />
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 pt-3">
          <button type="button" class="admin-btn-ghost px-4 py-2 text-sm" :disabled="statusMenuSaving" @click="closeStatusMenu">Cancel</button>
          <button type="button" class="admin-btn-primary px-4 py-2 text-sm" :disabled="statusMenuSaving" @click="saveStatusMenuUpdate">
            {{ statusMenuSaving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
