<script setup lang="ts">
const saving = ref(false);
const showCreateModal = ref(false);
const form = reactive({
  title: '',
  slot: 0,
  status: 1
});

const { data, pending, refresh } = await useFetch('/api/categories?all=1', { server: false });
const categories = computed(() => Array.isArray(data.value?.categories) ? data.value.categories : []);

async function createCategory() {
  if (!form.title.trim()) {
    alert('Category title is required');
    return;
  }
  saving.value = true;
  try {
    await $fetch('/api/categories', { method: 'POST', body: { ...form } });
    form.title = '';
    form.slot = 0;
    form.status = 1;
    await refresh();
    showCreateModal.value = false;
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to create category');
  } finally {
    saving.value = false;
  }
}

async function updateCategory(item: any) {
  try {
    await $fetch(`/api/categories/${item.id}`, {
      method: 'PATCH',
      body: {
        title: item.title,
        slot: Number(item.slot || 0),
        status: Number(item.status || 0) === 1 ? 1 : 0
      }
    });
    await refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to update category');
  }
}

async function deleteCategory(item: any) {
  if (!confirm(`Delete category "${item.title}"?`)) return;
  try {
    await $fetch(`/api/categories/${item.id}`, { method: 'DELETE' });
    await refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to delete category');
  }
}

async function moveCategory(item: any, direction: 'up' | 'down') {
  try {
    await $fetch(`/api/categories/${item.id}/move`, { method: 'POST', body: { direction } });
    await refresh();
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to reorder category');
  }
}
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between gap-3">
      <div>
        <h2 class="admin-panel-title">Categories</h2>
        <p class="text-sm text-slate-500">Manage product categories</p>
      </div>
      <button type="button" class="admin-btn-primary px-4 py-2 text-xs" @click="showCreateModal = true">Add New Category</button>
    </div>

    <div class="admin-card overflow-hidden">
      <div v-if="pending" class="p-10 text-center text-slate-400">Loading...</div>
      <div v-else-if="categories.length === 0" class="p-10 text-center text-slate-400">No categories found.</div>
      <div v-else class="overflow-x-auto">
        <table class="admin-table admin-table-compact min-w-[760px]">
          <thead class="bg-slate-50">
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Slot</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in categories" :key="item.id">
              <td class="font-mono text-[11px] text-slate-500">#{{ item.id }}</td>
              <td><input v-model="item.title" type="text" class="admin-input h-8 min-w-[150px] text-xs" /></td>
              <td><input v-model.number="item.slot" type="number" class="admin-input h-8 w-20 text-xs" /></td>
              <td>
                <select v-model.number="item.status" class="admin-input h-8 w-24 text-xs">
                  <option :value="1">Active</option>
                  <option :value="0">Hidden</option>
                </select>
              </td>
              <td class="space-x-1">
                <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="moveCategory(item, 'up')">↑</button>
                <button type="button" class="admin-btn-ghost px-2.5 py-1 text-[11px]" @click="moveCategory(item, 'down')">↓</button>
                <button type="button" class="admin-btn-primary px-2.5 py-1 text-[11px]" @click="updateCategory(item)">Save</button>
                <button type="button" class="admin-btn-danger px-2.5 py-1 text-[11px]" @click="deleteCategory(item)">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 z-[95] flex items-center justify-center bg-black/45 p-4">
      <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-800">Add New Category</h3>
          <button type="button" class="text-slate-400 hover:text-slate-700" @click="showCreateModal = false">✕</button>
        </div>
        <div class="grid grid-cols-1 gap-3">
          <input v-model="form.title" type="text" class="admin-input" placeholder="Category title" />
          <input v-model.number="form.slot" type="number" class="admin-input" placeholder="Slot" />
          <select v-model.number="form.status" class="admin-input">
            <option :value="1">Active</option>
            <option :value="0">Hidden</option>
          </select>
        </div>
        <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 pt-3">
          <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="showCreateModal = false">Close</button>
          <button type="button" class="admin-btn-primary px-4 py-2 text-xs" :disabled="saving" @click="createCategory">
            {{ saving ? 'Adding...' : 'Add Category' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
