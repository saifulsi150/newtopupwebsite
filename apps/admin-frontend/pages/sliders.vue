<script setup lang="ts">
type SliderItem = {
  title: string;
  image_url: string;
  link_url: string;
  status: number;
};

const saving = ref(false);
const uploadingIndex = ref(-1);
const payload = reactive<any>({
  slider_enabled: 1,
  slider_items: [] as SliderItem[]
});

const { data, pending, refresh } = await useFetch('/api/settings', { server: false });

function normalizeSliderItems(input: any): SliderItem[] {
  if (!Array.isArray(input)) return [];
  return input.map((item: any) => ({
    title: String(item?.title || ''),
    image_url: String(item?.image_url || ''),
    link_url: String(item?.link_url || ''),
    status: Number(item?.status ?? 1) === 1 ? 1 : 0
  }));
}

watch(data, (val) => {
  if (!val?.settings) return;
  Object.assign(payload, val.settings);
  payload.slider_items = normalizeSliderItems(val.settings.slider_items);
}, { immediate: true });

function addSlide() {
  payload.slider_items.push({ title: '', image_url: '', link_url: '', status: 1 });
}

function removeSlide(index: number) {
  payload.slider_items.splice(index, 1);
}

function moveSlide(index: number, direction: 'up' | 'down') {
  const target = direction === 'up' ? index - 1 : index + 1;
  if (target < 0 || target >= payload.slider_items.length) return;
  const current = payload.slider_items[index];
  payload.slider_items[index] = payload.slider_items[target];
  payload.slider_items[target] = current;
}

async function uploadSliderImage(event: Event, index: number) {
  const input = event.target as HTMLInputElement;
  const file = input?.files?.[0];
  if (!file) return;
  try {
    uploadingIndex.value = index;
    const form = new FormData();
    form.append('folder', 'banners');
    form.append('file', file, file.name);
    const res = await $fetch<{ url: string }>('/api/upload-image', { method: 'POST', body: form });
    payload.slider_items[index].image_url = String(res?.url || '');
  } catch {
    alert('Image upload failed');
  } finally {
    uploadingIndex.value = -1;
    if (input) input.value = '';
  }
}

async function saveSliderSettings() {
  saving.value = true;
  try {
    await $fetch('/api/settings', { method: 'POST', body: { ...payload } });
    await refresh();
    alert('Slider updated');
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to save slider');
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h2 class="admin-panel-title">Homepage Slider</h2>
        <p class="text-sm text-slate-500">Manage slider from dedicated menu</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-xs font-semibold text-slate-600">Slider Status</span>
        <button
          type="button"
          class="relative inline-flex h-8 w-[74px] items-center rounded-full px-1 transition"
          :class="Number(payload.slider_enabled) === 1 ? 'bg-emerald-600' : 'bg-slate-400'"
          @click="payload.slider_enabled = Number(payload.slider_enabled) === 1 ? 0 : 1"
        >
          <span class="absolute left-2 text-[10px] font-bold text-white">{{ Number(payload.slider_enabled) === 1 ? 'ON' : 'OFF' }}</span>
          <span class="h-6 w-6 rounded-full bg-white shadow transition-transform" :class="Number(payload.slider_enabled) === 1 ? 'translate-x-10' : 'translate-x-0'" />
        </button>
      </div>
    </div>

    <div v-if="pending" class="admin-card p-10 text-center text-slate-400">Loading...</div>

    <div v-else class="admin-card p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">Slides</h3>
        <button type="button" class="admin-btn-primary px-3 py-1.5 text-xs" @click="addSlide">Add Slide</button>
      </div>

      <div v-if="payload.slider_items.length === 0" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
        No slides added yet. You can save slide without image.
      </div>

      <div v-else class="space-y-3">
        <div v-for="(item, index) in payload.slider_items" :key="index" class="rounded-xl border border-slate-200 p-3">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-500">Title</label>
              <input v-model="item.title" type="text" class="admin-input" placeholder="Optional title" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-500">Link URL</label>
              <input v-model="item.link_url" type="text" class="admin-input" placeholder="https://..." />
            </div>
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-semibold text-slate-500">Image URL (Optional)</label>
              <input v-model="item.image_url" type="text" class="admin-input" placeholder="https://.../banner.jpg" />
              <div class="mt-2">
                <input type="file" accept="image/*" @change="uploadSliderImage($event, index)" />
                <p v-if="uploadingIndex === index" class="mt-1 text-xs text-slate-500">Uploading...</p>
              </div>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold text-slate-500">Status</label>
              <select v-model.number="item.status" class="admin-input">
                <option :value="1">Active</option>
                <option :value="0">Hidden</option>
              </select>
            </div>
          </div>
          <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" class="admin-btn-ghost px-3 py-1.5 text-xs" @click="moveSlide(index, 'up')">↑ Up</button>
            <button type="button" class="admin-btn-ghost px-3 py-1.5 text-xs" @click="moveSlide(index, 'down')">↓ Down</button>
            <button type="button" class="admin-btn-danger px-3 py-1.5 text-xs" @click="removeSlide(index)">Delete</button>
          </div>
        </div>
      </div>

      <div class="mt-4 border-t border-slate-200 pt-3">
        <button type="button" class="admin-btn-primary px-5 py-2 text-sm" :disabled="saving" @click="saveSliderSettings">
          {{ saving ? 'Saving...' : 'Save Slider' }}
        </button>
      </div>
    </div>
  </div>
</template>
