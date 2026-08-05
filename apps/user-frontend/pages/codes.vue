<script setup lang="ts">
definePageMeta({ middleware: 'auth' });

const codes = ref<any[]>([]);
const pending = ref(true);
const loadError = ref('');

async function fetchCodes() {
  pending.value = true;
  loadError.value = '';

  try {
    const res = await $fetch<{ codes?: any[] }>('/api/codes', {
      method: 'GET',
      timeout: 10000,
      retry: 0
    });
    codes.value = Array.isArray(res?.codes) ? res.codes : [];
  } catch (error: any) {
    codes.value = [];
    loadError.value = String(error?.data?.message || error?.message || 'Failed to load codes.');
  } finally {
    pending.value = false;
  }
}

onMounted(fetchCodes);

function formatDate(value: string | null | undefined) {
  if (!value) return '-';
  const dt = new Date(value);
  if (Number.isNaN(dt.getTime())) return String(value);
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  }).format(dt);
}

function statusText(status: string | null | undefined) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'completed' || normalized === 'active') return 'complete';
  return normalized || 'pending';
}

function statusClass(status: string | null | undefined) {
  const normalized = String(status || '').toLowerCase();
  if (normalized === 'completed' || normalized === 'active' || normalized === 'complete') return 'text-theme';
  if (normalized === 'cancel') return 'text-rose-600';
  return 'text-amber-600';
}

function codeLines(raw: string | null | undefined) {
  const value = String(raw || '').trim();
  if (!value) return ['-'];
  return value.split(/\r?\n/).filter(Boolean);
}

// একদম অ্যানিমেশন ছাড়া সাইলেন্ট কপি ফাংশন
async function copyCode(code: string) {
  try { 
    await navigator.clipboard.writeText(code); 
  } catch {}
}
</script>

<template>
  <section class="bg-[#f1f6fc] px-0 py-3 md:px-6 md:py-6">
    <div class="mx-auto w-full max-w-5xl rounded-lg border border-slate-200 bg-white shadow-sm">
      
      <!-- Card Header -->
      <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 md:px-6">
        <div class="flex items-center gap-2.5">
          <svg class="h-5 w-5 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
          </svg>
          <h1 class="text-base font-bold text-slate-900 md:text-lg">My Codes</h1>
        </div>

        <!-- Redeem Code Button -->
        <button 
          type="button" 
          class="theme-btn rounded-md px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm md:text-sm"
        >
          Reedeem Code
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="pending" class="px-5 py-8 text-sm font-semibold text-slate-500">
        Loading codes...
      </div>

      <div v-else-if="loadError" class="px-5 py-8">
        <p class="text-sm font-semibold text-rose-600">{{ loadError }}</p>
        <button type="button" class="theme-btn mt-3 rounded px-3 py-1.5 text-xs font-semibold text-white" @click="fetchCodes">
          Retry
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="codes.length === 0" class="px-5 py-8 text-sm font-semibold text-slate-500">
        No codes found.
      </div>

      <!-- Codes List -->
      <div v-else class="divide-y divide-slate-200">
        <div 
          v-for="item in codes" 
          :key="item.id" 
          class="p-4 md:px-6 md:py-5 text-[15px] leading-relaxed text-slate-800 md:text-[16px]"
        >
          <div class="space-y-1 md:space-y-1.5">
            <p>
              <span class="font-bold text-slate-900">Serial NO:</span> {{ item.id }}
            </p>
            <p>
              <span class="font-bold text-slate-900">Date:</span> {{ formatDate(item.created_at) }}
            </p>
            <p>
              <span class="font-bold text-slate-900">Package:</span> {{ item.package }}
            </p>
            <p>
              <span class="font-bold text-slate-900">Price:</span> ৳ {{ Number(item.price || 0).toFixed(2) }}
            </p>
            <p class="flex flex-wrap items-center gap-1">
              <span class="font-bold text-slate-900">Status:</span>
              <span :class="statusClass(item.status)" class="font-bold">
                {{ statusText(item.status) }}
              </span>
              <span class="text-slate-700 text-[14px]">
                ( {{ formatDate(item.updated_at || item.created_at) }} )
              </span>
            </p>

            <div class="pt-1">
              <p class="mb-1 font-bold text-slate-900">Your Code:</p>
              <div class="space-y-1 break-all rounded-md bg-[#f2eff8] p-3 font-mono text-[13px] leading-relaxed text-slate-800 md:text-[14px]">
                <div v-for="(line, i) in codeLines(item.code)" :key="`${item.id}-code-${i}`">
                  {{ line }}
                </div>
              </div>

              <button
                type="button"
                class="mt-2.5 inline-flex select-none items-center gap-1.5 rounded border border-[#8f8f8f] bg-[#e3e3e3] px-2.5 py-1 text-xs font-semibold text-[#2b2b2b]"
                @click="copyCode(item.code)"
              >
                <svg class="h-4 w-4 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <rect x="9" y="9" width="13" height="13" rx="1.5" />
                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                </svg>
                <span>Copy Code</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<style scoped>
.theme-btn {
  background-color: var(--theme-color);
}
.theme-btn:hover {
  filter: brightness(0.9);
}
.text-theme {
  color: var(--theme-color);
}
</style>