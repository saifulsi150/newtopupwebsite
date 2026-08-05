<script setup lang="ts">
const { data, pending } = await useFetch<any>('/api/settings', { server: false });

type ApiItem = {
  name: string;
  url: string;
  secret_key: string;
  status: number;
};

const items = ref<ApiItem[]>([]);
const saving = ref(false);
const saved = ref(false);
const saveNotice = ref('');

function createApiItem(): ApiItem {
  return {
    name: '',
    url: '',
    secret_key: '',
    status: 1
  };
}

watch(
  data,
  (val) => {
    const source = Array.isArray(val?.settings?.auto_api_items)
      ? val.settings.auto_api_items
      : [];
    if (source.length) {
      items.value = source.map((item: any) => ({
        name: String(item?.name || ''),
        url: String(item?.url || ''),
        secret_key: String(item?.secret_key || ''),
        status: Number(item?.status ?? 1) === 1 ? 1 : 0
      }));
      return;
    }
    const legacyName = String(val?.settings?.auto_api_name || '');
    const legacyUrl = String(val?.settings?.auto_api_url || '');
    const legacySecret = String(val?.settings?.auto_api_secret_key || '');
    if (legacyName || legacyUrl || legacySecret) {
      items.value = [{
        name: legacyName,
        url: legacyUrl,
        secret_key: legacySecret,
        status: 1
      }];
    } else {
      items.value = [createApiItem()];
    }
  },
  { immediate: true }
);

function addApiItem() {
  items.value.push(createApiItem());
}

function removeApiItem(index: number) {
  if (items.value.length <= 1) {
    items.value[0] = createApiItem();
    return;
  }
  items.value.splice(index, 1);
}

async function saveApiSettings() {
  if (saving.value) return;
  saving.value = true;
  saved.value = false;
  try {
    const cleanItems = items.value
      .map((item) => ({
        name: String(item.name || '').trim(),
        url: String(item.url || '').trim(),
        secret_key: String(item.secret_key || '').trim(),
        status: Number(item.status || 0) === 1 ? 1 : 0
      }))
      .filter((item) => item.name || item.url || item.secret_key);
    const primary = cleanItems[0] || { name: '', url: '', secret_key: '' };
    await $fetch('/api/settings', {
      method: 'POST',
      body: {
        auto_api_items: cleanItems,
        auto_api_name: primary.name,
        auto_api_url: primary.url,
        auto_api_secret_key: primary.secret_key
      }
    });
    saveNotice.value = 'Successfully updated';
    saved.value = true;
    setTimeout(() => {
      saved.value = false;
      saveNotice.value = '';
    }, 2200);
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Save failed');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h2 class="admin-panel-title">API</h2>
        <p class="text-sm text-slate-500">Manage Auto Api providers</p>
      </div>
      <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="addApiItem">Add API</button>
    </div>

    <div v-if="pending" class="admin-card p-8 text-center text-slate-400">Loading...</div>

    <template v-else>
      <div class="space-y-3">
        <div v-for="(item, index) in items" :key="index" class="admin-card p-5">
          <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-bold text-slate-700">API {{ index + 1 }}</p>
            <button type="button" class="admin-btn-danger px-3 py-1 text-[11px]" @click="removeApiItem(index)">Remove</button>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-500">API Name</label>
              <input v-model="item.name" type="text" class="admin-input" placeholder="Provider name" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
              <select v-model.number="item.status" class="admin-input">
                <option :value="1">Active</option>
                <option :value="0">Disabled</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label class="mb-1 block text-xs font-semibold text-slate-500">API URL</label>
              <input v-model="item.url" type="text" class="admin-input" placeholder="https://example.com/webhook/website/order" />
            </div>
            <div class="md:col-span-2">
              <label class="mb-1 block text-xs font-semibold text-slate-500">API Secret Key</label>
              <input v-model="item.secret_key" type="text" class="admin-input" placeholder="Secret key" />
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button :disabled="saving" @click="saveApiSettings" class="admin-btn-primary px-7 py-2.5">
          {{ saving ? 'Saving...' : 'Update API' }}
        </button>
        <span v-if="saved" class="text-sm font-semibold text-green-600">{{ saveNotice }}</span>
      </div>
    </template>
  </div>
</template>
