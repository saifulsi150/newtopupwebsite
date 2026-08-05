<script setup lang="ts">
const page = ref(1);
const search = ref('');

const { data, pending, refresh } = await useFetch(
  () => `/api/transactions?page=${page.value}&limit=20${search.value ? '&search=' + encodeURIComponent(search.value) : ''}`,
  { server: false }
);
const transactions = computed(() => data.value?.transactions || []);
const total = computed(() => data.value?.total || 0);
const totalAmount = computed(() => data.value?.totalAmount || 0);
const totalPages = computed(() => Math.ceil(total.value / 20));

function statusClass(s: string) {
  const map: Record<string, string> = {
    completed: 'badge-green', paid: 'badge-green',
    pending: 'badge-yellow', failed: 'badge-red', cancelled: 'badge-red'
  };
  return map[s] || 'badge-slate';
}

function formatDate(value: string | null | undefined) {
  if (!value) return '';
  const dt = new Date(value);
  if (Number.isNaN(dt.getTime())) return String(value);
  return dt.toLocaleString('en-US');
}

let t: any;
watch([search], () => {
  clearTimeout(t);
  t = setTimeout(() => { page.value = 1; refresh(); }, 400);
});
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="admin-panel-title">Transactions</h2>
        <p class="text-sm text-slate-500">Total {{ total }} · Sum <span class="font-bold text-green-600">Tk {{ Number(totalAmount).toLocaleString() }}</span></p>
        <p class="text-xs text-slate-400">Showing completed transactions only</p>
      </div>
      <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:gap-3">
        <input v-model="search" type="text" placeholder="Search by name or email..." class="admin-input w-full sm:w-56" />
      </div>
    </div>

    <div class="admin-card overflow-hidden">
      <div v-if="pending" class="p-10 text-center text-slate-400">Loading...</div>
      <div v-else-if="transactions.length === 0" class="p-10 text-center text-slate-400">No transactions found.</div>
      <div v-else>
        <div class="space-y-3 p-3 sm:p-4 lg:hidden">
          <div
            v-for="tx in transactions"
            :key="tx.id"
            class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs"
          >
            <div class="mb-3 flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
              <div>
                <p class="text-[11px] font-semibold text-slate-400">Transaction #{{ tx.id }}</p>
                <h3 class="text-sm font-bold text-slate-800">{{ tx.user_name || 'Unknown user' }}</h3>
                <p class="text-[11px] text-slate-500">{{ tx.user_email || '—' }}</p>
              </div>
              <span :class="statusClass(tx.status)">{{ tx.status }}</span>
            </div>

            <div class="grid grid-cols-2 gap-2.5 text-xs">
              <div class="rounded-xl bg-slate-50/80 p-2.5">
                <p class="text-[11px] font-medium text-slate-400">Amount</p>
                <p class="font-bold text-emerald-600">Tk {{ Number(tx.amount || 0).toLocaleString() }}</p>
              </div>
              <div class="rounded-xl bg-slate-50/80 p-2.5">
                <p class="text-[11px] font-medium text-slate-400">Method</p>
                <p class="font-semibold capitalize text-slate-700">{{ tx.method || '—' }}</p>
              </div>
              <div class="rounded-xl bg-slate-50/80 p-2.5">
                <p class="text-[11px] font-medium text-slate-400">Type</p>
                <p class="font-semibold text-slate-700">{{ tx.type || '—' }}</p>
              </div>
              <div class="rounded-xl bg-slate-50/80 p-2.5">
                <p class="text-[11px] font-medium text-slate-400">Date</p>
                <p class="font-semibold text-slate-700">{{ formatDate(tx.created_at) || '—' }}</p>
              </div>
            </div>

            <div class="mt-2.5 rounded-xl border border-slate-200/70 bg-slate-50/50 p-2.5">
              <p class="text-[11px] font-medium text-slate-400">Invoice</p>
              <p class="mt-0.5 break-all font-mono text-xs text-slate-600">{{ tx.invoice_id || '—' }}</p>
            </div>
          </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
          <table class="admin-table min-w-full">
            <thead class="bg-slate-50">
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Type</th>
                <th>Status</th>
                <th>Invoice</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="tx in transactions" :key="tx.id">
                <td class="font-mono text-xs text-slate-400">#{{ tx.id }}</td>
                <td>
                  <div class="text-slate-800 text-xs font-medium">{{ tx.user_name }}</div>
                  <div class="text-slate-400 text-xs">{{ tx.user_email }}</div>
                </td>
                <td class="font-bold text-green-600">Tk {{ Number(tx.amount).toLocaleString() }}</td>
                <td class="capitalize text-slate-500 text-xs">{{ tx.method || '—' }}</td>
                <td class="text-slate-500 text-xs">{{ tx.type || '—' }}</td>
                <td><span :class="statusClass(tx.status)">{{ tx.status }}</span></td>
                <td class="font-mono text-xs text-slate-400 max-w-[100px] truncate" :title="tx.invoice_id">{{ tx.invoice_id || '—' }}</td>
                <td class="text-xs text-slate-400">{{ tx.created_at ? new Date(tx.created_at).toLocaleDateString('en-US') : '' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-5 py-4">
        <span class="text-sm text-slate-500">Showing {{ Math.min(page * 20, total) }} of {{ total }}</span>
        <div class="flex gap-2">
          <button :disabled="page <= 1" @click="page--; refresh()" class="admin-btn-ghost px-3 py-1.5 text-xs disabled:opacity-40">← Prev</button>
          <span class="flex items-center px-3 text-sm">{{ page }} / {{ totalPages }}</span>
          <button :disabled="page >= totalPages" @click="page++; refresh()" class="admin-btn-ghost px-3 py-1.5 text-xs disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>
  </div>
</template>
