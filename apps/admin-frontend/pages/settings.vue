<script setup lang="ts">
const { data, pending } = await useFetch('/api/settings', { server: false });

const form = reactive({
  home_notice_text: '',
  slider_enabled: 1,
  category_enabled: 1,
  top_support_enabled: 1,
  top_support_telegram_enabled: 1,
  top_support_group_enabled: 1,
  top_support_whatsapp_enabled: 1,
  latest_orders_enabled: 1,
  top_support_telegram_url: '',
  top_support_group_url: '',
  top_support_whatsapp_url: '',
  top_support_telegram_label: 'Telegram',
  top_support_group_label: 'Join Group',
  top_support_whatsapp_label: 'WhatsApp',
  contact_whatsapp_enabled: 1,
  contact_telegram_enabled: 1,
  contact_email_enabled: 1,
  contact_phone_enabled: 1,
  detect_popup_enabled: 0,
  stay_connected_message: '',
  global_whatsapp_url: '',
  global_group_url: '',
  social_facebook_url: '',
  social_instagram_url: '',
  social_youtube_url: '',
  social_email: '',
  site_name: '',
  site_icon_url: '',
  logo_primary_url: '',
  logo_secondary_url: '',
  theme_color: '',
  pgw_app_enabled: 1,
  pgw_force_install_enabled: 0,
  contact_whatsapp_url: '',
  contact_telegram_url: '',
  contact_email: '',
  contact_phone: ''
});

const saving = ref(false);
const autoSaving = ref(false);
const saved = ref(false);
const restarting = ref(false);
const uploadingField = ref('');
const showEditModal = ref(false);
const saveNotice = ref('');

watch(
  data,
  (val) => {
    if (val?.settings) {
      Object.assign(form, val.settings);
    }
  },
  { immediate: true }
);

async function saveSettings(showSuccess = true) {
  if (saving.value || autoSaving.value) return;
  if (showSuccess) saving.value = true;
  else autoSaving.value = true;
  saved.value = false;
  try {
    await $fetch('/api/settings', { method: 'POST', body: { ...form } });
    saveNotice.value = 'Successfully updated';
    saved.value = true;
    setTimeout(() => {
      saved.value = false;
      saveNotice.value = '';
    }, 2200);
  } catch (e: any) {
    saveNotice.value = '';
    alert(e?.data?.statusMessage || 'Save failed');
  } finally {
    saving.value = false;
    autoSaving.value = false;
  }
}

function isEnabled(value: unknown) {
  return Number(value) === 1;
}

async function toggleAndSave(key: keyof typeof form) {
  form[key] = Number(form[key]) === 1 ? 0 : 1;
  await saveSettings(false);
}

async function closeEditModal() {
  showEditModal.value = false;
  await saveSettings(true);
}

const updating = ref(false)
const updateLogs = ref<string[]>([])
const updateResult = ref<'success' | 'error' | null>(null)
const updateJobId = ref('')
const activeSystemAction = ref<'update' | 'start-app'>('update')
let updatePollTimer: ReturnType<typeof setInterval> | null = null

function stopUpdatePolling() {
  if (updatePollTimer) {
    clearInterval(updatePollTimer)
    updatePollTimer = null
  }
}

async function pollUpdateStatus(jobId: string) {
  try {
    const status = await $fetch<{
      success: boolean
      jobId: string
      status: 'running' | 'completed' | 'failed'
      logs: string[]
    }>('/api/system/update-status', {
      method: 'GET',
      query: { jobId }
    })

    updateLogs.value = status.logs || []

    if (status.status === 'completed') {
      updateResult.value = 'success'
      updating.value = false
      stopUpdatePolling()
      alert(activeSystemAction.value === 'start-app' ? 'App started successfully!' : 'System updated successfully!')
    } else if (status.status === 'failed') {
      updateResult.value = 'error'
      updating.value = false
      stopUpdatePolling()
      alert(activeSystemAction.value === 'start-app' ? 'Start App failed. See logs for details.' : 'Update failed. See logs for details.')
    }
  } catch (e: any) {
    updateResult.value = 'error'
    updating.value = false
    stopUpdatePolling()
    updateLogs.value = [e?.data?.statusMessage || e?.message || 'Unable to read update status']
    alert('Update status check failed: ' + (e?.data?.statusMessage || e?.message || 'Unknown error'))
  }
}

async function triggerSystemJob(endpoint: '/api/system/update' | '/api/system/start-app', action: 'update' | 'start-app', confirmMessage: string) {
  if (!confirm(confirmMessage)) return

  activeSystemAction.value = action
  stopUpdatePolling()
  updating.value = true
  updateLogs.value = []
  updateResult.value = null

  try {
    const res = await $fetch<{ success: boolean; message: string; jobId: string; logs: string[] }>(endpoint, { method: 'POST' })
    updateJobId.value = String(res.jobId || '')
    updateLogs.value = res.logs || []

    if (!updateJobId.value) {
      throw new Error('Job id was not returned.')
    }

    updatePollTimer = setInterval(() => {
      pollUpdateStatus(updateJobId.value)
    }, 3000)
    await pollUpdateStatus(updateJobId.value)
  } catch (e: any) {
    updateResult.value = 'error'
    stopUpdatePolling()
    updateLogs.value = [e?.data?.statusMessage || e?.message || 'Unknown error']
    alert('Operation failed: ' + (e?.data?.statusMessage || e?.message || 'Check server logs.'))
  }
}

async function handleSystemUpdate() {
  await triggerSystemJob(
    '/api/system/update',
    'update',
    'Are you sure? This will pull latest code from GitHub and run database migrations on the live server.'
  )
}

async function handleStartApp() {
  await triggerSystemJob(
    '/api/system/start-app',
    'start-app',
    'Start all apps now? This will (re)build and run services, user frontend, and admin frontend.'
  )
}

onBeforeUnmount(() => {
  stopUpdatePolling()
})

async function restartAdminApp() {
  try {
    const res = await $fetch<any>('/api/settings/restart', { method: 'POST' });
    alert(res?.message || 'Restart started. Please refresh after a short wait.');
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Restart failed');
  } finally {
    restarting.value = false;
  }
}

async function uploadImageForField(event: Event, field: 'logo_primary_url' | 'logo_secondary_url' | 'site_icon_url') {
  const input = event.target as HTMLInputElement;
  const file = input?.files?.[0];
  if (!file) return;
  try {
    uploadingField.value = field;
    const payload = new FormData();
    payload.append('folder', field === 'site_icon_url' ? 'icons' : 'logos');
    payload.append('file', file, file.name);
    const res = await $fetch<{ url: string }>('/api/upload-image', {
      method: 'POST',
      body: payload
    });
    (form as any)[field] = String(res?.url || '');
    await saveSettings(false);
  } catch {
    alert('Image upload failed');
  } finally {
    uploadingField.value = '';
    if (input) input.value = '';
  }
}

</script>

<template>
  <div class="space-y-4">
    <div>
      <h2 class="admin-panel-title">Settings</h2>
      <p class="text-sm text-slate-500">Compact control panel</p>
    </div>

    <div v-if="pending" class="admin-card p-8 text-center text-slate-400">Loading...</div>

    <template v-else>
      <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="admin-table min-w-[1540px]">
            <thead>
              <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Top Support</th>
                <th>Latest Orders</th>
                <th>PGW APP</th>
                <th>PGW FORCE</th>
                <th>Detect PopUp</th>
                <th>WhatsApp</th>
                <th>Telegram</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Edit</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="font-semibold text-slate-800">RG BAZZER</td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.category_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('category_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.category_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.category_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.top_support_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('top_support_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.top_support_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.top_support_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.latest_orders_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('latest_orders_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.latest_orders_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.latest_orders_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.pgw_app_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('pgw_app_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.pgw_app_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.pgw_app_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.pgw_force_install_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('pgw_force_install_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.pgw_force_install_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.pgw_force_install_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.detect_popup_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('detect_popup_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.detect_popup_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.detect_popup_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.contact_whatsapp_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('contact_whatsapp_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.contact_whatsapp_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.contact_whatsapp_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.contact_telegram_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('contact_telegram_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.contact_telegram_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.contact_telegram_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.contact_email_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('contact_email_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.contact_email_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.contact_email_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button
                    type="button"
                    class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
                    :class="isEnabled(form.contact_phone_enabled) ? 'bg-emerald-600' : 'bg-slate-400'"
                    @click="toggleAndSave('contact_phone_enabled')"
                  >
                    <span class="absolute left-2 text-[10px] font-bold text-white">{{ isEnabled(form.contact_phone_enabled) ? 'ON' : 'OFF' }}</span>
                    <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="isEnabled(form.contact_phone_enabled) ? 'translate-x-10' : 'translate-x-0'" />
                  </button>
                </td>
                <td>
                  <button type="button" class="admin-btn-primary px-4 py-1.5 text-xs" @click="showEditModal = true">Edit</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-card p-4">
        <p class="mb-3 text-sm font-bold text-slate-700">Logo & Icon Settings</p>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Website Name</label>
            <input v-model="form.site_name" type="text" class="admin-input" placeholder="Your website name" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Website Icon URL</label>
            <input v-model="form.site_icon_url" type="text" class="admin-input" placeholder="https://.../favicon.png" />
            <div class="mt-2">
              <input type="file" accept="image/*" @change="uploadImageForField($event, 'site_icon_url')" />
            </div>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Primary Logo URL</label>
            <input v-model="form.logo_primary_url" type="text" class="admin-input" />
            <div class="mt-2">
              <input type="file" accept="image/*" @change="uploadImageForField($event, 'logo_primary_url')" />
            </div>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Secondary Logo URL</label>
            <input v-model="form.logo_secondary_url" type="text" class="admin-input" />
            <div class="mt-2">
              <input type="file" accept="image/*" @change="uploadImageForField($event, 'logo_secondary_url')" />
            </div>
          </div>
        </div>
        <p v-if="uploadingField" class="mt-2 text-xs text-slate-500">Uploading image...</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button :disabled="saving || autoSaving" @click="saveSettings(true)" class="admin-btn-primary px-7 py-2.5">
          {{ saving ? 'Saving...' : 'Update' }}
        </button>
        <button :disabled="restarting" @click="restartAdminApp" class="admin-btn-ghost px-6 py-2.5">
          {{ restarting ? 'Restarting...' : 'Update & Restart' }}
        </button>
        <span v-if="autoSaving" class="text-xs font-semibold text-slate-500">Auto saving...</span>
        <span v-if="saved" class="text-sm font-semibold text-green-600">{{ saveNotice }}</span>
      </div>

      <!-- System Update Card -->
      <div class="admin-card p-4 border-l-4 border-blue-500">
        <div class="mb-2 flex items-center gap-2">
          <span class="text-base">🚀</span>
          <p class="text-sm font-bold text-slate-700">System Update</p>
        </div>
        <p class="mb-3 text-xs text-slate-500">Pulls latest code + migrations, and also lets you start all three app layers from one place.</p>
        <div class="flex flex-wrap items-center gap-2">
          <button
            :disabled="updating"
            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-60 transition"
            @click="handleSystemUpdate"
          >
            <span v-if="updating && activeSystemAction === 'update'">⏳ Updating... Please wait</span>
            <span v-else>🚀 Check &amp; Apply System Update</span>
          </button>
          <button
            :disabled="updating"
            class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-60 transition"
            @click="handleStartApp"
          >
            <span v-if="updating && activeSystemAction === 'start-app'">⏳ Starting App... Please wait</span>
            <span v-else>▶ Start App</span>
          </button>
        </div>
        <div v-if="updateLogs.length" class="mt-3 rounded-lg bg-slate-900 p-3 font-mono text-xs text-green-300 max-h-40 overflow-y-auto">
          <p v-for="(log, i) in updateLogs" :key="i" class="leading-relaxed">{{ log }}</p>
        </div>
        <p v-if="updateResult === 'success'" class="mt-2 text-xs font-semibold text-green-600">✅ Update completed successfully.</p>
        <p v-if="updateResult === 'error'" class="mt-2 text-xs font-semibold text-red-600">❌ Update failed. See logs above.</p>
      </div>
    </template>

    <div v-if="showEditModal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/45 p-4">
      <div class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-800">Edit Settings</h3>
          <button type="button" class="text-slate-400 hover:text-slate-700" @click="showEditModal = false">✕</button>
        </div>

        <div class="space-y-4 overflow-y-auto pr-1">
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Homepage Notice</label>
            <textarea v-model="form.home_notice_text" class="admin-input min-h-[96px]" />
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <p class="mb-3 text-sm font-bold text-slate-700">Global Links (One link updates all same icons)</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Global WhatsApp URL</label>
                <input v-model="form.global_whatsapp_url" type="text" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Global Group URL</label>
                <input v-model="form.global_group_url" type="text" class="admin-input" />
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <p class="mb-3 text-sm font-bold text-slate-700">Stay Connected Content</p>
            <div class="space-y-3">
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Stay Connected Message</label>
                <textarea v-model="form.stay_connected_message" class="admin-input min-h-[90px]" />
              </div>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                  <label class="mb-1 block text-xs font-semibold text-slate-500">Facebook URL</label>
                  <input v-model="form.social_facebook_url" type="text" class="admin-input" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold text-slate-500">Instagram URL</label>
                  <input v-model="form.social_instagram_url" type="text" class="admin-input" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold text-slate-500">YouTube URL</label>
                  <input v-model="form.social_youtube_url" type="text" class="admin-input" />
                </div>
                <div>
                  <label class="mb-1 block text-xs font-semibold text-slate-500">Social Email</label>
                  <input v-model="form.social_email" type="text" class="admin-input" />
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <p class="mb-3 text-sm font-bold text-slate-700">Contact Values</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Email</label>
                <input v-model="form.contact_email" type="text" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Phone</label>
                <input v-model="form.contact_phone" type="text" class="admin-input" />
              </div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <p class="mb-3 text-sm font-bold text-slate-700">Site Branding</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Primary Logo URL</label>
                <input v-model="form.logo_primary_url" type="text" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Secondary Logo URL</label>
                <input v-model="form.logo_secondary_url" type="text" class="admin-input" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-slate-500">Theme Color</label>
                <input v-model.trim="form.theme_color" type="text" class="admin-input" placeholder="#RRGGBB (leave empty to clear)" />
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 flex justify-end gap-2 border-t border-slate-200 bg-white pt-3">
          <button type="button" class="admin-btn-ghost px-4 py-2 text-xs" @click="showEditModal = false">Close</button>
          <button type="button" class="admin-btn-primary px-4 py-2 text-xs" @click="closeEditModal">Save</button>
        </div>
      </div>
    </div>
  </div>
</template>
