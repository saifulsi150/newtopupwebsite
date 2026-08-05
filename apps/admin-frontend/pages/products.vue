<script setup lang="ts">
const page = ref(1);
const loadingEditor = ref(false);
const savingProduct = ref(false);
const showEditor = ref(false);
const showCreateModal = ref(false);
const uploadingCreateImage = ref(false);
const uploadingEditImage = ref(false);

type ProductDynamicField = {
  label: string;
  key: string;
};

const editorProduct = reactive<any>({
  id: 0,
  title: '',
  slug: '',
  slot: 0,
  status: 1,
  image: '',
  category_id: '',
  uid_checker: 0,
  uid_checker_api: '',
  dynamic_fields: [] as ProductDynamicField[]
});
const creatingProduct = ref(false);
const createForm = reactive({
  title: '',
  slug: '',
  image: '',
  category_id: '',
  slot: 0,
  status: 1
});

const { data, pending, refresh } = await useFetch(
  () => `/api/products?page=${page.value}&limit=20`,
  { server: false }
);
const { data: categoriesData } = await useFetch('/api/categories', { server: false });
const products = computed(() => data.value?.products || []);
const categories = computed(() => categoriesData.value?.categories || []);
const total = computed(() => data.value?.total || 0);
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / 20)));

function sanitizeFieldKey(input: string): string {
  return String(input || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9_]+/g, '_')
    .replace(/^_+|_+$/g, '');
}

function normalizeDynamicFields(input: any): ProductDynamicField[] {
  if (!Array.isArray(input)) return [];
  return input
    .map((row: any) => ({
      label: String(row?.label || '').trim(),
      key: sanitizeFieldKey(row?.key || '')
    }))
    .filter((row: ProductDynamicField) => row.label && row.key);
}

function addDynamicField() {
  editorProduct.dynamic_fields.push({ label: '', key: '' });
}

function removeDynamicField(index: number) {
  editorProduct.dynamic_fields.splice(index, 1);
}

async function openEditor(productId: number) {
  loadingEditor.value = true;
  showEditor.value = true;
  try {
    const res = await $fetch<any>(`/api/products/${productId}/packages`);
    const product = res?.product || {};
    editorProduct.id = Number(product.id || 0);
    editorProduct.title = String(product.title || '');
    editorProduct.slug = String(product.slug || '');
    editorProduct.slot = Number(product.slot || 0);
    editorProduct.status = Number(product.status || 1);
    editorProduct.image = String(product.image || '');
    editorProduct.category_id = String(product.category_id || '');
    editorProduct.uid_checker = Number(product.uid_checker || 0) === 1 ? 1 : 0;
    editorProduct.uid_checker_api = String(product.uid_checker_api || '');
    editorProduct.dynamic_fields = normalizeDynamicFields(product.dynamic_fields);
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to load product details');
    showEditor.value = false;
  } finally {
    loadingEditor.value = false;
  }
}

async function saveProduct() {
  if (!editorProduct.id) return;
  savingProduct.value = true;
  try {
    await $fetch(`/api/products/${editorProduct.id}`, {
      method: 'PATCH',
      body: {
        title: editorProduct.title,
        slug: editorProduct.slug,
        slot: editorProduct.slot,
        status: editorProduct.status,
        image: editorProduct.image,
        category_id: Number(editorProduct.category_id || 0),
        uid_checker: Number(editorProduct.uid_checker || 0) === 1 ? 1 : 0,
        uid_checker_api: String(editorProduct.uid_checker_api || '').trim(),
        dynamic_fields: normalizeDynamicFields(editorProduct.dynamic_fields)
      }
    });
    await refresh();
    alert('Product updated');
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to update product');
  } finally {
    savingProduct.value = false;
  }
}

async function uploadImage(event: Event, mode: 'create' | 'edit') {
  const input = event.target as HTMLInputElement;
  const file = input?.files?.[0];
  if (!file) return;
  try {
    if (mode === 'create') uploadingCreateImage.value = true;
    else uploadingEditImage.value = true;
    const payload = new FormData();
    payload.append('folder', 'products');
    payload.append('file', file, file.name);
    const res = await $fetch<{ url: string }>('/api/upload-image', {
      method: 'POST',
      body: payload
    });
    const imageUrl = String(res?.url || '');
    if (mode === 'create') {
      createForm.image = imageUrl;
    } else {
      editorProduct.image = imageUrl;
      if (editorProduct.id) {
        await $fetch(`/api/products/${editorProduct.id}`, {
          method: 'PATCH',
          body: { image: imageUrl }
        });
        await refresh();
      }
    }
  } catch {
    alert('Image upload failed');
  } finally {
    if (mode === 'create') uploadingCreateImage.value = false;
    else uploadingEditImage.value = false;
    if (input) input.value = '';
  }
}

async function createProduct() {
  if (!createForm.title.trim()) {
    alert('Title is required');
    return;
  }
  if (!String(createForm.category_id || '').trim()) {
    alert('Category is required');
    return;
  }
  creatingProduct.value = true;
  try {
    await $fetch('/api/products', {
      method: 'POST',
      body: {
        title: createForm.title,
        slug: createForm.slug,
        image: createForm.image,
        slot: createForm.slot,
        status: createForm.status,
        category_id: Number(createForm.category_id || 0)
      }
    });
    createForm.title = '';
    createForm.slug = '';
    createForm.image = '';
    createForm.slot = 0;
    createForm.status = 1;
    createForm.category_id = '';
    await refresh();
    showCreateModal.value = false;
    alert('Product created');
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to create product');
  } finally {
    creatingProduct.value = false;
  }
}

async function quickToggleStatus(product: any) {
  const newStatus = Number(product.status) === 1 ? 0 : 1;
  try {
    await $fetch(`/api/products/${product.id}`, {
      method: 'PATCH',
      body: { status: newStatus }
    });
    product.status = newStatus;
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed');
  }
}

async function deleteProduct(product: any) {
  const id = Number(product?.id || 0);
  if (!id) return;
  if (!confirm(`Delete product #${id} and all its packages?`)) return;
  try {
    await $fetch(`/api/products/${id}`, { method: 'DELETE' });
    await refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to delete product');
  }
}

async function moveProduct(product: any, direction: 'up' | 'down') {
  try {
    await $fetch(`/api/products/${product.id}/move`, {
      method: 'POST',
      body: { direction }
    });
    await refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to reorder product');
  }
}
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h2 class="admin-panel-title">Products</h2>
      <div class="flex items-center gap-3">
        <p class="text-sm text-slate-500">Total {{ total }}</p>
        <button type="button" class="admin-btn-primary px-4 py-2 text-xs" @click="showCreateModal = true">Add New Product</button>
        <NuxtLink to="/packages" class="admin-btn-primary px-4 py-2 text-xs">Edit Packages</NuxtLink>
      </div>
    </div>

    <div class="admin-card overflow-hidden">
      <div v-if="pending" class="p-10 text-center text-slate-400">Loading...</div>
      <div v-else-if="products.length === 0" class="p-10 text-center text-slate-400">No products found.</div>
      <div v-else class="overflow-x-auto">
        <table class="admin-table admin-table-compact min-w-[980px]">
          <thead class="bg-slate-50">
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Slug</th>
              <th>Category</th>
              <th>Packages</th>
              <th>Slot</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in products" :key="p.id">
              <td>
                <img v-if="p.image" :src="p.image" :alt="p.title" class="h-8 w-8 rounded-md border border-slate-200 object-cover" />
                <div v-else class="flex h-8 w-8 items-center justify-center rounded-md bg-slate-100 text-sm text-slate-400">🎮</div>
              </td>
              <td class="font-medium text-slate-800">{{ p.title }}</td>
              <td class="font-mono text-xs text-slate-400">{{ p.slug }}</td>
              <td class="text-xs font-medium text-slate-600">{{ p.category_title || '-' }}</td>
              <td><span class="badge-blue">{{ p.package_count }}</span></td>
              <td>{{ p.slot || '—' }}</td>
              <td>
                <span :class="Number(p.status) === 1 ? 'badge-green' : 'badge-red'">{{ Number(p.status) === 1 ? 'Active' : 'Hidden' }}</span>
              </td>
              <td class="space-x-1">
                <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="moveProduct(p, 'up')">↑</button>
                <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="moveProduct(p, 'down')">↓</button>
                <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="openEditor(Number(p.id))">Edit</button>
                <button
                  type="button"
                  :class="Number(p.status) === 1 ? 'admin-btn-danger' : 'admin-btn-primary'"
                  class="px-2.5 py-1 text-[11px]"
                  @click="quickToggleStatus(p)"
                >
                  {{ Number(p.status) === 1 ? 'Hide' : 'Show' }}
                </button>
                <button type="button" class="admin-btn-danger px-2.5 py-1 text-[11px]" @click="deleteProduct(p)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
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

    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-6">
      <div class="w-full max-w-3xl rounded-2xl border border-slate-200/80 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-6">
          <h3 class="text-base font-black text-slate-800 sm:text-lg">Add New Product</h3>
          <button type="button" class="text-xl text-slate-400 hover:text-slate-700" @click="showCreateModal = false">✕</button>
        </div>
        <div class="p-4 sm:p-6">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <input v-model="createForm.title" type="text" class="admin-input" placeholder="Product title" />
            <input v-model="createForm.slug" type="text" class="admin-input" placeholder="Slug (optional)" />
            <select v-model="createForm.category_id" class="admin-input">
              <option value="">Select category</option>
              <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.title }}</option>
            </select>
            <input v-model.number="createForm.slot" type="number" class="admin-input" placeholder="Slot" />
            <select v-model.number="createForm.status" class="admin-input">
              <option :value="1">Active</option>
              <option :value="0">Hidden</option>
            </select>
            <input v-model="createForm.image" type="text" class="admin-input" placeholder="Image URL" />
          </div>
          <div class="mt-3">
            <input type="file" accept="image/*" @change="uploadImage($event, 'create')" />
            <p v-if="uploadingCreateImage" class="mt-1 text-xs text-slate-500">Uploading image...</p>
          </div>
          <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 pt-3">
            <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="showCreateModal = false">Close</button>
            <button type="button" class="admin-btn-primary px-4 py-2 text-xs" :disabled="creatingProduct" @click="createProduct">
              {{ creatingProduct ? 'Creating...' : 'Create Product' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showEditor" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-6">
      <div class="w-full max-w-5xl rounded-2xl border border-slate-200/80 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-6">
          <h3 class="text-base font-black text-slate-800 sm:text-lg">Edit Product</h3>
          <button type="button" class="text-xl text-slate-400 hover:text-slate-700" @click="showEditor = false">✕</button>
        </div>

        <div v-if="loadingEditor" class="p-10 text-center text-slate-400">Loading editor...</div>
        <div v-else class="p-4 sm:p-6">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
            <h4 class="mb-3 text-sm font-bold text-slate-700">Product Details</h4>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
              <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Title</label>
                <input v-model="editorProduct.title" type="text" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Slug</label>
                <input v-model="editorProduct.slug" type="text" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Slot</label>
                <input v-model.number="editorProduct.slot" type="number" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Category</label>
                <select v-model="editorProduct.category_id" class="admin-input">
                  <option value="" disabled>Select category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.title }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
                <select v-model.number="editorProduct.status" class="admin-input">
                  <option :value="1">Active</option>
                  <option :value="0">Hidden</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">UID Checker</label>
                <select v-model.number="editorProduct.uid_checker" class="admin-input">
                  <option :value="0">Off</option>
                  <option :value="1">On</option>
                </select>
                <p v-if="Number(editorProduct.uid_checker) === 1" class="mt-1 text-[11px] text-slate-500">User side check hint will be shown.</p>
              </div>
              <div v-if="Number(editorProduct.uid_checker) === 1">
                <label class="mb-1 block text-xs font-semibold text-slate-500">UID Checker API URL</label>
                <input
                  v-model="editorProduct.uid_checker_api"
                  type="text"
                  class="admin-input font-mono text-xs"
                  placeholder="https://example.com/api/checkuid?uid="
                />
                <p class="mt-1 text-[11px] text-slate-500">The entered UID will be appended to this URL. Example: <code>https://vnbazer.com/api/checkuid?uid=</code></p>
              </div>
            </div>
            <div class="mt-3">
              <label class="mb-1 block text-xs font-semibold text-slate-500">Image URL</label>
              <input v-model="editorProduct.image" type="text" class="admin-input" />
              <div class="mt-2">
                <input type="file" accept="image/*" @change="uploadImage($event, 'edit')" />
                <p v-if="uploadingEditImage" class="mt-1 text-xs text-slate-500">Uploading image...</p>
              </div>
            </div>
            <div class="mt-3">
              <button type="button" class="admin-btn-primary px-4 py-2 text-sm" :disabled="savingProduct" @click="saveProduct">
                {{ savingProduct ? 'Saving...' : 'Save Product' }}
              </button>
            </div>
          </div>

          <div class="mt-4 rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
              <h4 class="text-sm font-bold text-slate-700">Account Info Fields</h4>
              <button type="button" class="admin-btn-primary px-3 py-1.5 text-xs" @click="addDynamicField">Add Field</button>
            </div>

            <p class="mb-3 text-xs text-slate-500">
              Add unlimited fields. <strong>User Label</strong> is shown to user, <strong>DB Key</strong> is saved in order account info (example: <code>player_id</code>).
            </p>

            <div v-if="editorProduct.dynamic_fields.length === 0" class="rounded-lg border border-dashed border-slate-300 p-3 text-xs text-slate-500">
              No fields added yet. Click <strong>Add Field</strong>.
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="(field, index) in editorProduct.dynamic_fields"
                :key="index"
                class="grid grid-cols-1 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 sm:grid-cols-[1fr_1fr_auto]"
              >
                <input
                  v-model="field.label"
                  type="text"
                  class="admin-input"
                  placeholder="User Label (e.g. এখানে গেমের আইডি কোড দিন)"
                />
                <input
                  v-model="field.key"
                  type="text"
                  class="admin-input"
                  placeholder="DB Key (e.g. player_id)"
                  @blur="field.key = sanitizeFieldKey(field.key)"
                />
                <button type="button" class="admin-btn-danger px-3 py-2 text-xs" @click="removeDynamicField(index)">Delete</button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>
