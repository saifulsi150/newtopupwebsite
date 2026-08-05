<script setup lang="ts">
const search = ref('');
const page = ref(1);
const selectedUser = ref<any>(null);
const showModal = ref(false);
const editBalance = ref(0);
const editStatus = ref(1);
const saving = ref(false);
const addBalanceAmount = ref(0);
const addBalanceNote = ref('');

const { data, pending, refresh } = await useFetch(() => `/api/users?page=${page.value}&limit=20&search=${search.value}`, { server: false });
const users = computed(() => data.value?.users || []);
const total = computed(() => data.value?.total || 0);
const totalPages = computed(() => Math.ceil(total.value / 20));

async function openUser(userId: number) {
  const res = await $fetch<any>(`/api/users/${userId}`);
  selectedUser.value = res;
  editBalance.value = Number(res.user?.balance || 0);
  editStatus.value = Number(res.user?.status || 1);
  addBalanceAmount.value = 0;
  addBalanceNote.value = '';
  showModal.value = true;
}

async function saveUser() {
  if (!selectedUser.value?.user) return;
  saving.value = true;
  try {
    await $fetch(`/api/users/${selectedUser.value.user.id}`, {
      method: 'PATCH',
      body: { balance: editBalance.value, status: editStatus.value }
    });
    showModal.value = false;
    refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Update failed');
  } finally {
    saving.value = false;
  }
}

async function addBalance() {
  if (!addBalanceAmount.value || addBalanceAmount.value <= 0) {
    alert('Amount must be > 0');
    return;
  }
  saving.value = true;
  try {
    await $fetch('/api/users/add-balance', {
      method: 'POST',
      body: { user_id: selectedUser.value.user.id, amount: addBalanceAmount.value, note: addBalanceNote.value }
    });
    alert('Balance added!');
    openUser(selectedUser.value.user.id);
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed');
  } finally {
    saving.value = false;
  }
}

function statusBadge(s: number) {
  return s === 1 ? 'badge-green' : 'badge-red';
}
function statusLabel(s: number) {
  return s === 1 ? 'Active' : 'Banned';
}

let searchTimeout: any;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => { page.value = 1; refresh(); }, 400);
});
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="admin-panel-title">Users</h2>
        <p class="text-sm text-slate-500">Total {{ total }} users</p>
      </div>
      <input v-model="search" type="text" placeholder="Search by name or email..." class="admin-input w-full sm:w-64" />
    </div>

    <!-- Table -->
    <div class="admin-card overflow-hidden">
      <div v-if="pending" class="p-10 text-center text-slate-400">Loading...</div>
      <div v-else-if="users.length === 0" class="p-10 text-center text-slate-400">No users found.</div>
      <div v-else class="overflow-x-auto">
        <table class="admin-table min-w-full">
          <thead class="bg-slate-50">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Balance</th>
              <th>Orders</th>
              <th>Type</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td class="font-mono text-xs text-slate-400">#{{ u.id }}</td>
              <td class="font-medium text-slate-800">{{ u.name }}</td>
              <td class="text-slate-500">{{ u.email }}</td>
              <td class="text-slate-500">{{ u.phone || '—' }}</td>
              <td class="font-bold text-green-600">Tk {{ Number(u.balance).toLocaleString() }}</td>
              <td>{{ u.total_order }}</td>
              <td>
                <span :class="u.user_type === 'admin' ? 'badge-blue' : 'badge-slate'">{{ u.user_type }}</span>
              </td>
              <td>
                <span :class="statusBadge(u.status)">{{ statusLabel(u.status) }}</span>
              </td>
              <td>
                <button @click="openUser(u.id)" class="admin-btn-ghost text-xs px-3 py-1.5">Details</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-5 py-4">
        <span class="text-sm text-slate-500">Showing {{ Math.min(page * 20, total) }} of {{ total }}</span>
        <div class="flex gap-2">
          <button :disabled="page <= 1" @click="page--; refresh()" class="admin-btn-ghost px-3 py-1.5 text-xs disabled:opacity-40">← Prev</button>
          <span class="flex items-center px-3 text-sm text-slate-600">{{ page }} / {{ totalPages }}</span>
          <button :disabled="page >= totalPages" @click="page++; refresh()" class="admin-btn-ghost px-3 py-1.5 text-xs disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>

    <!-- User Detail Modal -->
    <div v-if="showModal && selectedUser" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-10">
      <div class="w-full max-w-2xl rounded-2xl border border-slate-200/80 bg-white shadow-2xl">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <h3 class="text-lg font-black text-slate-800">{{ selectedUser.user?.name }}</h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
        </div>

        <div class="p-6 space-y-6">
          <!-- User Info -->
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-slate-400">Email:</span> <span class="font-medium">{{ selectedUser.user?.email }}</span></div>
            <div><span class="text-slate-400">Phone:</span> <span class="font-medium">{{ selectedUser.user?.phone || '—' }}</span></div>
            <div><span class="text-slate-400">Balance:</span> <span class="font-bold text-green-600">Tk {{ Number(selectedUser.user?.balance).toLocaleString() }}</span></div>
            <div><span class="text-slate-400">Total Orders:</span> <span class="font-medium">{{ selectedUser.user?.total_order }}</span></div>
            <div><span class="text-slate-400">Type:</span> <span class="font-medium capitalize">{{ selectedUser.user?.user_type }}</span></div>
            <div><span class="text-slate-400">Joined:</span> <span class="font-medium">{{ selectedUser.user?.created_at ? new Date(selectedUser.user.created_at).toLocaleDateString('en-US') : '—' }}</span></div>
          </div>

          <!-- Edit section -->
          <div class="rounded-xl border border-slate-200 p-4 space-y-4">
            <h4 class="font-bold text-slate-700">Balance / Status Edit</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Balance (Set Directly)</label>
                <input v-model.number="editBalance" type="number" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
                <select v-model.number="editStatus" class="admin-input">
                  <option :value="1">Active</option>
                  <option :value="0">Banned</option>
                </select>
              </div>
            </div>
            <button :disabled="saving" @click="saveUser" class="admin-btn-primary w-full justify-center py-2.5">{{ saving ? 'Saving...' : 'Update User' }}</button>
          </div>

          <!-- Add balance -->
          <div class="rounded-xl border border-green-100 bg-green-50/50 p-4 space-y-3">
            <h4 class="font-bold text-green-800">Add Balance</h4>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Amount (Tk)</label>
                <input v-model.number="addBalanceAmount" type="number" min="1" class="admin-input" placeholder="100" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Note</label>
                <input v-model="addBalanceNote" type="text" class="admin-input" placeholder="Reason" />
              </div>
            </div>
            <button :disabled="saving" @click="addBalance" class="admin-btn-success w-full justify-center py-2.5">{{ saving ? '...' : '+ Add Balance' }}</button>
          </div>

          <!-- Recent transactions -->
          <div v-if="selectedUser.transactions?.length">
            <h4 class="mb-3 font-bold text-slate-700">Recent Transactions</h4>
            <div class="overflow-x-auto rounded-xl border border-slate-100">
              <table class="admin-table min-w-full text-xs">
                <thead class="bg-slate-50">
                  <tr><th>Amount</th><th>Type</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                  <tr v-for="tx in selectedUser.transactions.slice(0,5)" :key="tx.id">
                    <td class="font-bold text-green-600">Tk {{ tx.amount }}</td>
                    <td>{{ tx.type }}</td>
                    <td><span :class="tx.status === 'completed' ? 'badge-green' : 'badge-yellow'">{{ tx.status }}</span></td>
                    <td class="text-slate-400">{{ tx.created_at ? new Date(tx.created_at).toLocaleDateString() : '' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
