<script setup lang="ts">
definePageMeta({ middleware: 'auth' });
const { data, pending } = await useFetch('/api/transactions', { server: false });
const transactions = computed(() => data.value?.transactions || []);
const total = computed(() => data.value?.total || 0);

function statusBadge(status: string) {
  if (status === 'completed' || status === 'paid') return 'bg-green-100 text-green-700';
  if (status === 'pending') return 'bg-yellow-100 text-yellow-700';
  if (status === 'failed' || status === 'cancelled') return 'bg-red-100 text-red-700';
  return 'bg-slate-100 text-slate-600';
}

function statusLabel(status: string) {
  const map: Record<string, string> = {
    completed: 'সম্পন্ন', paid: 'সম্পন্ন', pending: 'অপেক্ষমাণ',
    failed: 'ব্যর্থ', cancelled: 'বাতিল', add: 'যোগ'
  };
  return map[status] || status;
}

function typeLabel(type: string) {
  const map: Record<string, string> = {
    wallet: 'ওয়ালেট', topup: 'টপ-আপ', order: 'অর্ডার',
    add_money: 'ব্যালেন্স যোগ', deposit: 'ডিপোজিট'
  };
  return map[type] || type;
}
</script>

<template>
  <section class="page-shell">
    <div class="card-panel p-5 lg:p-8">
      <div class="section-title">My Transaction</div>
      <h1 class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">History of your payments</h1>

      <div class="mt-5 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-700">
        মোট লেনদেন: <span class="ml-1 font-black text-[#18823f]">৳{{ total.toLocaleString() }}</span>
      </div>

      <div v-if="pending" class="mt-8 text-center text-slate-500">লোড হচ্ছে...</div>

      <div v-else-if="transactions.length === 0" class="mt-8 rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-500">
        কোনো লেনদেন নেই।
      </div>

      <div v-else class="mt-5 overflow-x-auto rounded-2xl border border-slate-200">
        <table class="min-w-full text-left text-sm text-slate-700">
          <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">পরিমাণ</th>
              <th class="px-4 py-3">ধরন</th>
              <th class="px-4 py-3">পদ্ধতি</th>
              <th class="px-4 py-3">স্ট্যাটাস</th>
              <th class="px-4 py-3">তারিখ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(item, idx) in transactions" :key="item.id" class="bg-white hover:bg-slate-50/60 transition-colors">
              <td class="px-4 py-3 text-slate-400 font-medium">{{ idx + 1 }}</td>
              <td class="px-4 py-3 font-bold text-[#18823f]">৳{{ Number(item.amount).toLocaleString() }}</td>
              <td class="px-4 py-3">{{ typeLabel(item.type) }}</td>
              <td class="px-4 py-3 capitalize">{{ item.method || '—' }}</td>
              <td class="px-4 py-3">
                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusBadge(item.status)">
                  {{ statusLabel(item.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-500">{{ item.date || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
