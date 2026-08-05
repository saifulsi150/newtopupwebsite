<script setup lang="ts">
const page = ref(1);
const limit = 20;
const search = ref('');
const productFilter = ref('');
const statusFilter = ref('');
const rowSaving = reactive<Record<number, boolean>>({});
const rowDeleting = reactive<Record<number, boolean>>({});

const showEditModal = ref(false);
const showCreateModal = ref(false);
const modalSaving = ref(false);
const creatingPackage = ref(false);
const createForm = reactive({
  product_id: '',
  name: '',
  buy_price: '',
  sell_price: '',
  slot: '',
  is_active: 1,
  auto_forward_enabled: 0,
  auto_forward_api_name: ''
});
const editForm = reactive({
  id: 0,
  product_id: '',
  name: '',
  buy_price: '',
  sell_price: '',
  slot: '',
  is_active: 1,
  auto_forward_enabled: 0,
  auto_forward_api_name: ''
});

const queryUrl = computed(() => {
  const qp = new URLSearchParams();
  qp.set('page', String(page.value));
  qp.set('limit', String(limit));
  if (search.value.trim()) qp.set('search', search.value.trim());
  if (productFilter.value.trim()) qp.set('product_id', productFilter.value.trim());
  if (statusFilter.value.trim()) qp.set('status', statusFilter.value.trim());
  return `/api/packages?${qp.toString()}`;
});

const { data, pending, refresh } = await useFetch(queryUrl, { server: false });
const { data: productsData } = await useFetch('/api/products?page=1&limit=100', { server: false });
const { data: settingsData } = await useFetch('/api/settings', { server: false });
const rows = computed(() => data.value?.packages || []);
const total = computed(() => data.value?.total || 0);
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / limit)));
const productOptions = computed(() => (productsData.value?.products || []).map((p: any) => ({
  id: String(p.id || ''),
  label: String(p.title || `Product #${p.id}`)
})));
const autoApiOptions = computed(() =>
  (settingsData.value?.settings?.auto_api_items || [])
    .filter((item: any) => Number(item?.status ?? 1) === 1 && String(item?.name || '').trim())
    .map((item: any) => String(item.name))
);

function productLabel(productId: number | string, fallback = '') {
  const id = String(productId || '');
  const found = productOptions.value.find((p: any) => p.id === id);
  return found?.label || fallback || `Product #${id}`;
}

function openEditModal(pkg: any) {
  editForm.id = Number(pkg.id || 0);
  editForm.product_id = String(pkg.product_id || '');
  editForm.name = String(pkg.name || '');
  editForm.buy_price = pkg.buy_price === null || pkg.buy_price === undefined ? '' : String(pkg.buy_price);
  editForm.sell_price = String(pkg.sell_price ?? 0);
  editForm.slot = String(pkg.slot ?? 0);
  editForm.is_active = Number(pkg.is_active || 0);
  editForm.auto_forward_enabled = Number(pkg.auto_forward_enabled || 0);
  editForm.auto_forward_api_name = String(pkg.auto_forward_api_name || '');
  showEditModal.value = true;
}

function closeEditModal() {
  showEditModal.value = false;
}

async function createPackage() {
  if (!String(createForm.product_id || '').trim()) {
    alert('Product is required');
    return;
  }
  if (!String(createForm.name || '').trim()) {
    alert('Package name is required');
    return;
  }
  const sellPrice = Number(createForm.sell_price || 0);
  if (!Number.isFinite(sellPrice) || sellPrice < 0) {
    alert('Valid sell price is required');
    return;
  }

  creatingPackage.value = true;
  try {
    await $fetch('/api/packages', {
      method: 'POST',
      body: {
        product_id: Number(createForm.product_id || 0),
        name: String(createForm.name || ''),
        buy_price: createForm.buy_price === '' ? null : Number(createForm.buy_price),
        sell_price: sellPrice,
        slot: createForm.slot === '' ? undefined : Number(createForm.slot),
        is_active: Number(createForm.is_active || 0),
        auto_forward_enabled: Number(createForm.auto_forward_enabled || 0),
        auto_forward_api_name: String(createForm.auto_forward_api_name || '')
      }
    });
    createForm.product_id = '';
    createForm.name = '';
    createForm.buy_price = '';
    createForm.sell_price = '';
    createForm.slot = '';
    createForm.is_active = 1;
    createForm.auto_forward_enabled = 0;
    createForm.auto_forward_api_name = '';
    await refresh();
    showCreateModal.value = false;
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to create package');
  } finally {
    creatingPackage.value = false;
  }
}

async function saveRow(pkg: any) {
  const id = Number(pkg?.id || 0);
  if (!id) return;
  rowSaving[id] = true;
  try {
    const res = await $fetch<any>(`/api/packages/${id}`, {
      method: 'PATCH',
      body: {
        product_id: Number(pkg.product_id || 0),
        name: String(pkg.name || ''),
        buy_price: pkg.buy_price === '' || pkg.buy_price === null ? null : Number(pkg.buy_price),
        sell_price: Number(pkg.sell_price || 0),
        slot: Number(pkg.slot || 0),
        is_active: Number(pkg.is_active || 0),
        auto_forward_enabled: Number(pkg.auto_forward_enabled || 0),
        auto_forward_api_name: String(pkg.auto_forward_api_name || '')
      }
    });
    if (res?.package) {
      pkg.product_id = Number(res.package.product_id || pkg.product_id);
      pkg.product_title = String(res.package.product_title || productLabel(pkg.product_id, pkg.product_title || ''));
      pkg.product_slug = String(res.package.product_slug || pkg.product_slug || '');
      pkg.name = String(res.package.name || pkg.name);
      pkg.buy_price = res.package.buy_price !== null ? Number(res.package.buy_price) : null;
      pkg.sell_price = res.package.sell_price !== null ? Number(res.package.sell_price) : Number(pkg.sell_price || 0);
      pkg.slot = Number(res.package.slot || pkg.slot || 0);
      pkg.is_active = Number(res.package.is_active || 0);
      pkg.auto_forward_enabled = Number(res.package.auto_forward_enabled || 0);
      pkg.auto_forward_api_name = String(res.package.auto_forward_api_name || '');
    }
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to save package');
  } finally {
    rowSaving[id] = false;
  }
}

async function saveModal() {
  const id = Number(editForm.id || 0);
  if (!id) return;
  modalSaving.value = true;
  try {
    const res = await $fetch<any>(`/api/packages/${id}`, {
      method: 'PATCH',
      body: {
        product_id: Number(editForm.product_id || 0),
        name: String(editForm.name || ''),
        buy_price: editForm.buy_price === '' ? null : Number(editForm.buy_price),
        sell_price: Number(editForm.sell_price || 0),
        slot: Number(editForm.slot || 0),
        is_active: Number(editForm.is_active || 0),
        auto_forward_enabled: Number(editForm.auto_forward_enabled || 0),
        auto_forward_api_name: String(editForm.auto_forward_api_name || '')
      }
    });

    if (res?.package) {
      const idx = rows.value.findIndex((item: any) => Number(item.id) === id);
      if (idx !== -1) {
        const target = rows.value[idx];
        target.product_id = Number(res.package.product_id || target.product_id);
        target.product_title = String(res.package.product_title || productLabel(target.product_id, target.product_title || ''));
        target.product_slug = String(res.package.product_slug || target.product_slug || '');
        target.name = String(res.package.name || target.name);
        target.buy_price = res.package.buy_price !== null ? Number(res.package.buy_price) : null;
        target.sell_price = res.package.sell_price !== null ? Number(res.package.sell_price) : Number(target.sell_price || 0);
        target.slot = Number(res.package.slot || target.slot || 0);
        target.is_active = Number(res.package.is_active || 0);
        target.auto_forward_enabled = Number(res.package.auto_forward_enabled || 0);
        target.auto_forward_api_name = String(res.package.auto_forward_api_name || '');
      }
    }
    showEditModal.value = false;
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to save package');
  } finally {
    modalSaving.value = false;
  }
}

async function deleteRow(pkg: any) {
  const id = Number(pkg?.id || 0);
  if (!id) return;
  if (!confirm(`Delete package #${id}?`)) return;

  rowDeleting[id] = true;
  try {
    await $fetch(`/api/packages/${id}`, { method: 'DELETE' });
    await refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to delete package');
  } finally {
    rowDeleting[id] = false;
  }
}

async function movePackage(pkg: any, direction: 'up' | 'down') {
  const id = Number(pkg?.id || 0);
  if (!id) return;
  try {
    await $fetch(`/api/packages/${id}/move`, { method: 'POST', body: { direction } });
    await refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to reorder package');
  }
}

let filterTimer: any;
watch([search, productFilter, statusFilter], () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(async () => {
    page.value = 1;
    await refresh();
  }, 300);
});
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h2 class="admin-panel-title">Packages</h2>
        <p class="text-sm text-slate-500">Total {{ total }}</p>
      </div>
      <button type="button" class="admin-btn-primary px-4 py-2 text-xs" @click="showCreateModal = true">Add New Package</button>
    </div>

    <div class="admin-card p-3 sm:p-4">
      <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        <input v-model="search" type="text" placeholder="Search package / product..." class="admin-input" />
        <select v-model="productFilter" class="admin-input">
          <option value="">All Products</option>
          <option v-for="opt in productOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
        </select>
        <select v-model="statusFilter" class="admin-input">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="hidden">Hidden</option>
        </select>
      </div>
    </div>

    <div class="admin-card overflow-hidden">
      <div v-if="pending" class="p-10 text-center text-slate-400">Loading packages...</div>
      <div v-else-if="rows.length === 0" class="p-10 text-center text-slate-400">No packages found.</div>

      <div v-else class="overflow-x-auto">
        <table class="admin-table admin-table-compact min-w-[1260px]">
          <thead class="bg-slate-50">
            <tr>
              <th>ID</th>
              <th>Product</th>
              <th>Package Name</th>
              <th>Buy Price</th>
              <th>Sell Price</th>
              <th>Slot</th>
              <th>Status</th>
              <th>Auto</th>
              <th>API</th>
              <th>Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="pkg in rows" :key="pkg.id">
              <td class="font-mono text-[11px] text-slate-500">#{{ pkg.id }}</td>
              <td class="min-w-[160px]">
               <select v-model="pkg.product_id" class="admin-input h-8 text-xs">
                  <option v-for="opt in productOptions" :key="opt.id" :value="Number(opt.id)">{{ opt.label }}</option>
                </select>
                <p class="mt-1 text-[11px] text-slate-400">{{ pkg.product_slug || '-' }}</p>
              </td>
              <td>
               <input v-model="pkg.name" type="text" class="admin-input h-8 min-w-[140px] text-xs" />
              </td>
              <td>
               <input v-model.number="pkg.buy_price" type="number" step="0.01" min="0" class="admin-input h-8 w-24 text-xs" />
              </td>
              <td>
               <input v-model.number="pkg.sell_price" type="number" step="0.01" min="0" class="admin-input h-8 w-24 text-xs" />
              </td>
              <td>
               <input v-model.number="pkg.slot" type="number" step="1" class="admin-input h-8 w-20 text-xs" />
              </td>
              <td>
               <select v-model.number="pkg.is_active" class="admin-input h-8 w-24 text-xs">
                  <option :value="1">Active</option>
                  <option :value="0">Hidden</option>
                </select>
              </td>
              <td>
                <select v-model.number="pkg.auto_forward_enabled" class="admin-input h-8 w-20 text-xs">
                  <option :value="1">ON</option>
                  <option :value="0">OFF</option>
                </select>
              </td>
              <td class="min-w-[150px]">
                <select v-model="pkg.auto_forward_api_name" class="admin-input h-8 text-xs" :disabled="Number(pkg.auto_forward_enabled || 0) !== 1">
                  <option value="">Select API</option>
                  <option v-for="name in autoApiOptions" :key="name" :value="name">{{ name }}</option>
                </select>
              </td>
              <td class="text-[11px] text-slate-400">
                {{ pkg.updated_at ? new Date(pkg.updated_at).toLocaleString('en-US') : '-' }}
              </td>
              <td>
                <div class="flex items-center gap-1.5">
                 <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="movePackage(pkg, 'up')">↑</button>
                 <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="movePackage(pkg, 'down')">↓</button>
                 <button type="button" class="admin-btn-primary px-2.5 py-1 text-[11px]" :disabled="rowSaving[pkg.id]" @click="saveRow(pkg)">
                    {{ rowSaving[pkg.id] ? 'Saving...' : 'Save' }}
                  </button>
                 <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="openEditModal(pkg)">
                    Edit
                  </button>
                 <button type="button" class="admin-btn-danger px-2.5 py-1 text-[11px]" :disabled="rowDeleting[pkg.id]" @click="deleteRow(pkg)">
                    {{ rowDeleting[pkg.id] ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3 sm:px-5 sm:py-4">
        <span class="text-sm text-slate-500">Showing {{ Math.min(page * limit, total) }} of {{ total }}</span>
        <div class="flex gap-2">
          <button :disabled="page <= 1" @click="page--; refresh()" class="admin-btn-ghost px-3 py-1.5 text-xs disabled:opacity-40">← Prev</button>
          <span class="flex items-center px-3 text-sm">{{ page }} / {{ totalPages }}</span>
          <button :disabled="page >= totalPages" @click="page++; refresh()" class="admin-btn-ghost px-3 py-1.5 text-xs disabled:opacity-40">Next →</button>
        </div>
      </div>
    </div>

    <div v-if="showEditModal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/45 p-4">
      <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-800">Edit Package #{{ editForm.id }}</h3>
          <button type="button" class="text-slate-400 hover:text-slate-700" @click="closeEditModal">✕</button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">Product</label>
            <select v-model="editForm.product_id" class="admin-input">
              <option value="" disabled>Select product</option>
              <option v-for="opt in productOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">Package Name</label>
            <input v-model="editForm.name" type="text" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
            <select v-model.number="editForm.is_active" class="admin-input">
              <option :value="1">Active</option>
              <option :value="0">Hidden</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Buy Price</label>
            <input v-model="editForm.buy_price" type="number" step="0.01" min="0" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Sell Price</label>
            <input v-model="editForm.sell_price" type="number" step="0.01" min="0" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Slot</label>
            <input v-model="editForm.slot" type="number" step="1" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Auto</label>
            <select v-model.number="editForm.auto_forward_enabled" class="admin-input">
              <option :value="1">ON</option>
              <option :value="0">OFF</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">API Provider</label>
            <select v-model="editForm.auto_forward_api_name" class="admin-input" :disabled="Number(editForm.auto_forward_enabled || 0) !== 1">
              <option value="">Select API</option>
              <option v-for="name in autoApiOptions" :key="name" :value="name">{{ name }}</option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 pt-3">
          <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="closeEditModal">Close</button>
          <button type="button" class="admin-btn-primary px-4 py-2 text-xs" :disabled="modalSaving" @click="saveModal">
            {{ modalSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 z-[95] flex items-center justify-center bg-black/45 p-4">
      <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-800">Add New Package</h3>
          <button type="button" class="text-slate-400 hover:text-slate-700" @click="showCreateModal = false">✕</button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">Product</label>
            <select v-model="createForm.product_id" class="admin-input">
              <option value="">Select Product</option>
              <option v-for="opt in productOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">Package Name</label>
            <input v-model="createForm.name" type="text" placeholder="Package name" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Buy Price</label>
            <input v-model="createForm.buy_price" type="number" step="0.01" min="0" placeholder="Buy price (optional)" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Sell Price</label>
            <input v-model="createForm.sell_price" type="number" step="0.01" min="0" placeholder="Sell price" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Slot</label>
            <input v-model="createForm.slot" type="number" step="1" placeholder="Sort slot (optional)" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
            <select v-model.number="createForm.is_active" class="admin-input">
              <option :value="1">Active</option>
              <option :value="0">Hidden</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Auto</label>
            <select v-model.number="createForm.auto_forward_enabled" class="admin-input">
              <option :value="1">ON</option>
              <option :value="0">OFF</option>
            </select>
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">API Provider</label>
            <select v-model="createForm.auto_forward_api_name" class="admin-input" :disabled="Number(createForm.auto_forward_enabled || 0) !== 1">
              <option value="">Select API</option>
              <option v-for="name in autoApiOptions" :key="name" :value="name">{{ name }}</option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 pt-3">
          <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="showCreateModal = false">Close</button>
          <button type="button" class="admin-btn-primary px-4 py-2 text-xs" :disabled="creatingPackage" @click="createPackage">
            {{ creatingPackage ? 'Adding...' : 'Add Package' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
