<script setup lang="ts">
type PackageItem = {
  id: number;
  title: string;
  price: number;
};

type TopupPayload = {
  product: {
    id: number;
    title: string;
    slug: string;
    image_url: string;
    subtitle: string | null;
    input_label: string;
    uid_checker: number;
    uid_checker_api: string;
    dynamic_fields: Array<{ label: string; key: string }>;
  };
  packages: PackageItem[];
};

const route = useRoute();
const slug = computed(() => String(route.params.slug || ''));
const { data, pending, error } = await useFetch<TopupPayload>(() => `/api/topup/${slug.value}`, {
  key: () => `topup-${slug.value}`
});

const qty = ref(1);
const selectedPackageId = ref<number | null>(null);
const playerId = ref('');
const dynamicInputs = ref<Record<string, string>>({});
const paymentMethod = ref<'wallet' | 'instant'>('wallet');
const orderLoading = ref(false);
const orderError = ref('');
const uidCheckLoading = ref(false);
const uidCheckMessage = ref('');
const auth = useAuth();

async function syncAuthUser() {
  if (!auth.isLoggedIn.value) return;
  try {
    const res = await $fetch<{ user: any | null }>('/api/user/me', {
      method: 'GET',
      timeout: 8000,
      retry: 0
    });
    if (res?.user) {
      auth.setUser(res.user);
    }
  } catch {
    // Ignore sync failures
  }
}

onMounted(() => {
  syncAuthUser();
});

watch(
  () => data.value?.packages,
  (packages) => {
    if (packages && packages.length) {
      const normalized = packages.map((item) => Number((item as any).id ?? 0)).filter((id) => id > 0);
      if (normalized.length === 0) {
        selectedPackageId.value = null;
        return;
      }

      if (!selectedPackageId.value || !normalized.includes(selectedPackageId.value)) {
        selectedPackageId.value = normalized[0];
      }
    }
  },
  { immediate: true }
);

watch(
  () => data.value?.product?.dynamic_fields,
  (fields) => {
    const next: Record<string, string> = {};
    for (const field of fields || []) {
      const key = String(field?.key || '').trim();
      if (key) next[key] = '';
    }
    dynamicInputs.value = next;
  },
  { immediate: true }
);

const packageOptions = computed(() => {
  return (data.value?.packages || []).map((item) => ({
    id: Number((item as any).id ?? 0),
    title: String((item as any).title ?? ''),
    price: Number((item as any).price ?? 0)
  }));
});

const selected = computed(() => {
  return packageOptions.value.find((item) => item.id === selectedPackageId.value) || null;
});

const hasDynamicFields = computed(() => (data.value?.product?.dynamic_fields || []).length > 0);

const resolvedPlayerId = computed(() => {
  if (hasDynamicFields.value) {
    const dynamicPlayerId = String(dynamicInputs.value.player_id || '').trim();
    if (dynamicPlayerId) return dynamicPlayerId;
    const firstKey = String(data.value?.product?.dynamic_fields?.[0]?.key || '').trim();
    return String(firstKey ? dynamicInputs.value[firstKey] || '' : '').trim();
  }
  return String(playerId.value || '').trim();
});

const total = computed(() => Number(selected.value?.price || 0) * Number(qty.value || 1));
const walletBalance = computed(() => {
  if (!auth.isLoggedIn.value) return 0;
  const user = (auth.user as any)?.value ?? auth.user;
  return Number((user as any)?.wallet_balance || 0);
});

function normalizePackageText(title: string) {
  return title.replace(/\s+/g, ' ').trim();
}

async function submitOrder() {
  orderError.value = '';

  if (!auth.isLoggedIn.value) {
    await navigateTo('/login');
    return;
  }

  if (!selected.value) {
    orderError.value = 'Please select a package.';
    return;
  }

  if (hasDynamicFields.value) {
    const missing = (data.value?.product?.dynamic_fields || []).find((field) => {
      const key = String(field?.key || '').trim();
      return !key || !String(dynamicInputs.value[key] || '').trim();
    });
    if (missing) {
      orderError.value = 'Please fill all account info fields.';
      if (process.client) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
      return;
    }
  } else if (!playerId.value.trim()) {
    orderError.value = 'Please enter your player ID.';
    if (process.client) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    return;
  }

  orderLoading.value = true;

  try {
    const res = await $fetch<{ success: boolean; order?: { redirect_url?: string } }>('/api/orders', {
      method: 'POST',
      body: {
        package_id: selected.value.id,
        package_title: selected.value.title,
        amount: selected.value.price,
        quantity: Number(qty.value || 1),
        player_id: resolvedPlayerId.value,
        dynamic_fields: dynamicInputs.value,
        payment_method: paymentMethod.value
      }
    });

    await syncAuthUser();

    if (paymentMethod.value === 'instant' && res.order?.redirect_url) {
      // UddoktaPay external redirect
      window.location.href = res.order.redirect_url;
    } else {
      await navigateTo(res.order?.redirect_url || '/orders');
    }
  } catch (e: any) {
    orderError.value = e?.data?.message || 'Unable to place order right now.';
  } finally {
    orderLoading.value = false;
  }
}

async function checkPlayerName() {
  uidCheckMessage.value = '';
  const pid = resolvedPlayerId.value;
  if (!pid) {
    uidCheckMessage.value = 'Please enter Player ID first.';
    return;
  }
  uidCheckLoading.value = true;
  try {
    const res = await $fetch<{ valid: boolean; nickname?: string; message?: string }>(`/api/products/${slug.value}/verify-player`, {
      method: 'POST',
      body: { player_id: pid }
    });
    uidCheckMessage.value = res?.valid ? String(res.nickname || 'Player found') : String(res?.message || 'Player not found');
  } catch (e: any) {
    uidCheckMessage.value = e?.data?.statusMessage || 'Unable to verify player right now.';
  } finally {
    uidCheckLoading.value = false;
  }
}
</script>

<template>
  <main class="min-h-screen text-slate-900">

    <section class="page-shell pb-24 md:pb-0">
      <div v-if="pending" class="card-panel mt-6 p-10 text-center text-slate-600">
        Preparing your checkout...
      </div>
      <div v-else-if="error || !data" class="card-panel mt-6 border-rose-200 bg-rose-50 p-10 text-center text-rose-600">
        This product is not available right now.
      </div>

      <div v-else class="mt-2 grid gap-3 lg:grid-cols-[1.12fr_0.88fr]">
        <div class="space-y-6">
          <!-- Product Info Header -->
          <div class="rounded-lg border border-slate-200 bg-white p-2.5 sm:p-4">
            <div class="flex items-center gap-3 sm:gap-4">
              <img :src="data.product.image_url" :alt="data.product.title" class="h-[74px] w-[74px] rounded-md border border-slate-200 object-cover sm:h-24 sm:w-24" loading="lazy" decoding="async" />
              <div>
                <h1 class="text-[14px] font-medium text-slate-900 sm:text-2xl">{{ data.product.title }}</h1>
                <p class="mt-1 text-[12px] text-slate-500">Game / Top up</p>
              </div>
            </div>
          </div>

          <!-- Section 1: Select Recharge -->
          <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-[0_2px_10px_rgba(15,23,42,0.03)] sm:p-4">
            <div class="mb-3 flex items-center gap-3 border-b border-slate-200 pb-3">
              <div class="theme-badge flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white">1</div>
              <h2 class="text-[18px] font-black leading-none text-slate-900">Select Recharge</h2>
            </div>

            <!-- Packages Grid -->
            <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2 sm:gap-2.5">
              <button
                v-for="item in packageOptions"
                :key="item.id"
                type="button"
                class="relative h-[50px] w-full rounded-md border px-2.5 text-left transition-all shadow-sm select-none flex items-center justify-between"
                :class="selectedPackageId === item.id 
                  ? 'pkg-selected'
                  : 'border-[#d5d9e1] bg-white hover:border-slate-300'"
                @click="selectedPackageId = item.id"
              >
                <!-- Title and Selection Check Box -->
                <div class="flex min-w-0 flex-1 items-center gap-1.5 text-[11px] font-semibold text-slate-800 leading-tight">
                  <span 
                    v-if="selectedPackageId === item.id" 
                    class="theme-badge-sm flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full text-white"
                  >
                    <svg class="h-2 w-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>
                  <span v-else class="h-3.5 w-3.5 shrink-0 rounded-full border border-slate-300 bg-slate-50" />
                  
                  <span class="truncate whitespace-nowrap text-[11px] text-slate-800">{{ normalizePackageText(item.title) }}</span>
                </div>

                <!-- Price -->
                <div class="shrink-0 whitespace-nowrap min-w-[46px] text-right text-[11px] font-bold text-theme">
                  {{ item.price }} TK
                </div>
              </button>
            </div>

            <button type="button" class="mt-3 inline-flex items-center gap-2 text-[15px] font-semibold text-theme">
              <span>কিভাবে অর্ডার করবেন?</span>
              <span class="text-[#d9493f]">➜</span>
            </button>
          </div>
        </div>

        <aside class="space-y-6">
          <!-- Section 2: Account Info -->
          <div class="rounded-lg border border-slate-200 bg-white p-4 sm:p-5">
            <div class="mb-4 flex items-center gap-3">
              <div class="theme-badge flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white">2</div>
              <h2 class="text-xl font-black text-slate-900">Account Info</h2>
            </div>
            <div class="mt-4 space-y-4">
              <template v-if="hasDynamicFields">
                <label v-for="field in data.product.dynamic_fields" :key="field.key" class="block text-sm font-semibold text-slate-700">
                  {{ field.label }}
                  <input
                    v-model="dynamicInputs[field.key]"
                    type="text"
                    class="input-shell mt-2"
                    :placeholder="field.label"
                  />
                </label>
              </template>
              <label v-else class="block text-sm font-semibold text-slate-700">
                {{ data.product.input_label || 'এখানে গেমের আইডি কোড দিন' }}
                <input
                  v-model="playerId"
                  type="text"
                  class="input-shell mt-2"
                  :placeholder="data.product.input_label || 'এখানে গেমের আইডি কোড দিন'"
                />
              </label>

              <template v-if="Number(data.product.uid_checker) === 1">
                <div
                  v-if="uidCheckMessage && !uidCheckLoading"
                  class="uid-result-card w-full cursor-pointer rounded-lg px-4 py-3 text-center text-lg font-bold transition"
                  :class="uidCheckMessage.startsWith('Player not found') || uidCheckMessage.startsWith('Unable') || uidCheckMessage.startsWith('UID') || uidCheckMessage.startsWith('Please')
                    ? 'bg-rose-50 border border-rose-300 text-rose-600'
                    : 'theme-btn text-white'"
                  @click="checkPlayerName"
                >
                  {{ uidCheckMessage }}
                </div>
                <button
                  v-else
                  type="button"
                  class="theme-btn w-full rounded-lg px-4 py-3 text-lg font-semibold text-white transition"
                  :disabled="uidCheckLoading"
                  @click="checkPlayerName"
                >
                  {{ uidCheckLoading ? 'Checking...' : 'আপনার গেম আইডির নাম চেক করুন' }}
                </button>
              </template>
            </div>
          </div>

          <!-- Section 3: Select Payment Method -->
          <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-[0_4px_14px_rgba(15,23,42,0.04)] sm:p-5">
            <div class="mt-2 flex items-center gap-3">
              <div class="theme-badge flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white">3</div>
              <h2 class="text-xl font-black text-slate-900">Select one option</h2>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
              <!-- Wallet Pay Button -->
              <button 
                type="button" 
                class="relative overflow-hidden rounded-md border text-left transition-all select-none shadow-sm"
                :class="paymentMethod === 'wallet' ? 'pay-selected' : 'border-slate-300 bg-white hover:border-slate-400'" 
                @click="paymentMethod = 'wallet'"
              >
                <div v-if="paymentMethod === 'wallet'" class="theme-badge absolute right-0 top-0 flex h-5 w-5 items-center justify-center rounded-bl-md text-white">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>

                <div class="flex min-h-[90px] items-center justify-center p-2.5">
                  <img src="https://admin.rgbazer.com/notices/1754121013_walletlogo.png" alt="wallet" class="mx-auto h-12 w-auto object-contain sm:h-14" />
                </div>
                <div class="bg-slate-200/80 px-3 py-2 text-center text-xs font-bold text-slate-800 sm:text-sm">
                  Wallet Pay
                </div>
              </button>

              <!-- Instant Pay Button -->
              <button 
                type="button" 
                class="relative overflow-hidden rounded-md border text-left transition-all select-none shadow-sm"
                :class="paymentMethod === 'instant' ? 'pay-selected' : 'border-slate-300 bg-white hover:border-slate-400'" 
                @click="paymentMethod = 'instant'"
              >
                <div v-if="paymentMethod === 'instant'" class="theme-badge absolute right-0 top-0 flex h-5 w-5 items-center justify-center rounded-bl-md text-white">
                  <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>

                <div class="flex min-h-[90px] items-center justify-center p-2.5">
                  <img src="https://admin.rgbazer.com/notices/1754121013_autopay.png" alt="auto payment" class="mx-auto h-12 w-auto object-contain sm:h-14" />
                </div>
                <div class="bg-slate-200/80 px-3 py-2 text-center text-xs font-bold text-slate-800 sm:text-sm">
                  Instant Pay
                </div>
              </button>
            </div>

            <div class="mt-5 space-y-3 text-sm text-slate-600">
              <div class="flex items-center justify-between gap-3">
                <span>আপনার অ্যাকাউন্ট ব্যালেন্স</span>
                <span class="text-theme font-semibold">৳ {{ walletBalance.toFixed(2) }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span>প্রোডাক্ট কিনতে আপনার প্রয়োজন</span>
                <span class="text-theme font-semibold">৳ {{ total }}</span>
              </div>
            </div>

            <NuxtLink v-if="!auth.isLoggedIn" to="/login" class="theme-btn mt-5 block rounded-lg px-4 py-3 text-center text-xl font-semibold text-white transition">
              Buy Now
            </NuxtLink>
            <button v-else type="button" class="theme-btn mt-5 w-full rounded-lg px-4 py-3 text-xl font-semibold text-white transition" :disabled="orderLoading" @click="submitOrder">
              {{ orderLoading ? 'Placing Order...' : 'Buy Now' }}
            </button>

            <p v-if="orderError" class="mt-3 text-sm font-semibold text-rose-500">{{ orderError }}</p>
          </div>

          <div class="rounded-lg border border-slate-200 bg-white p-4 sm:p-5">
            <h2 class="text-xl font-black text-slate-900">Rules & Conditions</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
              <li>⦿ শুধুমাত্র Bangladesh সার্ভারে ID Code দিয়ে টপ আপ হবে।</li>
              <li>⦿ Player ID ভুল হলে TopUp কর্তৃপক্ষ দায়ী নয়।</li>
              <li>⦿ Order status দেখে সঠিক তথ্য দিয়ে পুনরায় অর্ডার করুন।</li>
            </ul>
          </div>
        </aside>
      </div>
    </section>
  </main>
</template>

<style scoped>
.theme-btn,
.theme-badge,
.theme-badge-sm {
  background-color: var(--theme-color);
}
.theme-btn:hover {
  filter: brightness(0.9);
}
.text-theme {
  color: var(--theme-color);
}
.pkg-selected {
  border: 1px solid var(--theme-color);
  background: #f8fafc;
  box-shadow: 0 0 0 1px var(--theme-color) inset;
}
.pay-selected {
  border-color: var(--theme-color);
  box-shadow: 0 0 0 1px var(--theme-color) inset;
  background: #f8fafc;
}
</style>