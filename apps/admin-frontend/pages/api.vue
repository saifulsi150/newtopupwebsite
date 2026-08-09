<script setup lang="ts">
const { data, pending } = await useFetch<any>('/api/settings', { server: false });

const form = reactive({
  website_api_url: 'https://ai-topup.lxtopup.com',
  website_api_key: '',
  automation_enabled: 0
});

const saving = ref(false);
const saved = ref(false);
const saveNotice = ref('');

function normalizeSettings(val: any) {
  return {
    website_api_url: String(val?.settings?.website_api_url || val?.settings?.auto_api_url || 'https://ai-topup.lxtopup.com'),
    website_api_key: String(val?.settings?.website_api_key || val?.settings?.auto_api_secret_key || ''),
    automation_enabled: Number(val?.settings?.automation_enabled ?? val?.settings?.auto_api_items?.[0]?.status ?? 0) === 1 ? 1 : 0
  };
}

watch(
  data,
  (val) => {
    Object.assign(form, normalizeSettings(val));
  },
  { immediate: true }
);

async function saveApiSettings() {
  if (saving.value) return;
  saving.value = true;
  saved.value = false;
  try {
    const payload = {
      website_api_url: String(form.website_api_url || '').trim() || 'https://ai-topup.lxtopup.com',
      website_api_key: String(form.website_api_key || '').trim(),
      automation_enabled: Number(form.automation_enabled || 0) === 1 ? 1 : 0,
      auto_api_name: 'Website API',
      auto_api_url: String(form.website_api_url || '').trim() || 'https://ai-topup.lxtopup.com',
      auto_api_secret_key: String(form.website_api_key || '').trim(),
      auto_api_items: [
        {
          name: 'Website API',
          url: String(form.website_api_url || '').trim() || 'https://ai-topup.lxtopup.com',
          secret_key: String(form.website_api_key || '').trim(),
          status: Number(form.automation_enabled || 0) === 1 ? 1 : 0
        }
      ]
    };

    await $fetch('/api/settings', {
      method: 'POST',
      body: payload
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
        <h2 class="admin-panel-title">Website API</h2>
        <p class="text-sm text-slate-500">Single connection for ai-topup.lxtopup.com/admin/website-settings</p>
      </div>
    </div>

    <div v-if="pending" class="admin-card p-8 text-center text-slate-400">Loading...</div>

    <template v-else>
      <div class="admin-card p-5 space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">Website API URL</label>
            <input v-model="form.website_api_url" type="text" class="admin-input" placeholder="https://ai-topup.lxtopup.com" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-semibold text-slate-500">Website API Key</label>
            <input v-model="form.website_api_key" type="text" class="admin-input" placeholder="API key" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Automation</label>
            <select v-model.number="form.automation_enabled" class="admin-input">
              <option :value="1">Enabled</option>
              <option :value="0">Disabled</option>
            </select>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button :disabled="saving" @click="saveApiSettings" class="admin-btn-primary px-7 py-2.5">
          {{ saving ? 'Saving...' : 'Update Website API' }}
        </button>
        <span v-if="saved" class="text-sm font-semibold text-green-600">{{ saveNotice }}</span>
      </div>
    </template>
  </div>
</template>
