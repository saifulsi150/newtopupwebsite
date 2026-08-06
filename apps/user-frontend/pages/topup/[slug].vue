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

const hasProduct = computed(() => Boolean(data.value?.product?.id));
const hasPackages = computed(() => packageOptions.value.length > 0);

const unavailableMessage = computed(() => {
  if (error.value) {
    const status = Number((error.value as any)?.statusCode || (error.value as any)?.status || 0);
    if (status === 404) return 'Package not found.';
    return 'Unable to load this page right now.';
  }

  if (!data.value || !hasProduct.value) {
    return 'Product not found.';
  }

  if (!hasPackages.value) {
    return 'Package not found.';
  }

  return '';
});

const showUnavailable = computed(() => !pending.value && unavailableMessage.value.length > 0);

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

function packageSuffix(title: string) {
  return /weekly|monthly/i.test(title) ? '💳' : '💎';
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
      <div v-else-if="showUnavailable" class="card-panel mt-6 border-rose-200 bg-rose-50 p-10 text-center text-rose-600">
        {{ unavailableMessage }}
      </div>

      <div v-else class="mt-2 grid gap-3 lg:grid-cols-[1.12fr_0.88fr]">
        <div class="space-y-6">
          <div class="product-hero rounded-2xl border border-[#d5e1ee] bg-white p-2.5 sm:p-4">
            <div class="flex items-center gap-3 sm:gap-4">
              <img :src="data.product.image_url" :alt="data.product.title" class="h-[74px] w-[74px] rounded-xl border border-slate-200 object-cover sm:h-24 sm:w-24" loading="lazy" decoding="async" />
              <div class="min-w-0">
                <h1 class="product-title-main">{{ data.product.title }}</h1>
                <p class="mt-1 text-[12px] font-semibold tracking-wide text-slate-500">Game / Top up</p>
                <p class="hero-status mt-2 inline-flex max-w-full items-center gap-2 rounded-full px-3 py-1 text-[12px] font-bold">
                  <span>⚡</span>
                  <span>১ সেকেন্ডে টপআপ</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Section 1: Select Recharge -->
          <div class="package-shell rounded-2xl border border-slate-200 bg-white p-3 shadow-[0_6px_16px_rgba(15,23,42,0.04)] sm:p-4">
            <div class="mb-3 flex items-center gap-3 border-b border-slate-200 pb-3">
              <div class="theme-badge flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white">1</div>
              <div class="leading-tight">
                <h2 class="recharge-title">Select Recharge</h2>
                <p class="recharge-sub">Choose your package and continue checkout</p>
              </div>
            </div>

            <!-- Packages Grid -->
            <div class="mt-2 grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
              <button
                v-for="item in packageOptions"
                :key="item.id"
                type="button"
                class="package-card group relative overflow-hidden rounded-xl border px-3 py-3.5 text-left transition-all min-h-[72px] shadow-sm select-none"
                :class="selectedPackageId === item.id 
                  ? 'pkg-selected'
                  : 'border-[#d8deea] bg-white hover:border-[#b8c9de]'"
                @click="selectedPackageId = item.id"
              >
                <span v-if="selectedPackageId === item.id" class="pkg-corner">Selected</span>
                <div class="flex items-center justify-between gap-1.5">
                  <div class="flex min-w-0 flex-1 items-start gap-2.5 text-[12px] font-bold text-slate-800 leading-5 sm:text-[13px]">
                    
                    <!-- Selected Check Icon -->
                    <span 
                      v-if="selectedPackageId === item.id" 
                      class="theme-badge-sm flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-white"
                    >
                      <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    </span>
                    <span v-else class="h-5 w-5 shrink-0 rounded-full border border-slate-300 bg-slate-50" />
                    
                    <span class="package-title-text">{{ normalizePackageText(item.title) }} {{ packageSuffix(item.title) }}</span>
                  </div>
                  <div class="package-price shrink-0 whitespace-nowrap text-[14px] font-black leading-5 sm:text-[16px]">
                    {{ item.price }}<span class="ml-1 text-[11px] font-extrabold sm:text-[12px]">TK</span>
                  </div>
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
                <!-- After successful check: show name prominently like a result card -->
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
                <!-- Check button (shown when no result yet or re-check) -->
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
                <!-- Tick Badge for Payment Selection -->
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
                <!-- Tick Badge for Payment Selection -->
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

            <div class="mt-4 space-y-2 rounded-xl border border-slate-200/70 bg-slate-50/70 px-3 py-3">
              <div class="flex items-center justify-between gap-3 text-sm text-slate-700">
                <span class="font-semibold">Selected package</span>
                <span class="text-right font-semibold text-slate-900">{{ selected?.title || 'Choose one' }}</span>
              </div>
              <div class="flex items-center justify-between gap-3 text-sm text-slate-700">
                <span class="font-semibold">Total amount</span>
                <span class="text-theme text-xl font-black leading-none">৳{{ total }}</span>
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
.product-hero {
  background:
    radial-gradient(120% 180% at 100% 0%, rgba(16, 122, 60, 0.14), transparent 45%),
    linear-gradient(180deg, #ffffff 0%, #f9fcff 100%);
}
.product-title-main {
  font-size: clamp(1.15rem, 2.1vw, 1.9rem);
  line-height: 1.18;
  font-weight: 900;
  letter-spacing: -0.02em;
  color: #17243a;
  white-space: normal;
  overflow-wrap: anywhere;
}
.hero-status {
  color: #0f7134;
  background: linear-gradient(180deg, #f3fbf7 0%, #e7f8ef 100%);
  border: 1px solid #cbeedb;
}
.package-shell {
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(250, 253, 255, 0.98) 100%),
    radial-gradient(80% 110% at 100% 0%, rgba(62, 124, 190, 0.08), transparent 56%);
}
.recharge-title {
  font-size: clamp(1.2rem, 2vw, 1.85rem);
  line-height: 1.1;
  font-weight: 900;
  letter-spacing: -0.01em;
  color: #11253f;
}
.recharge-sub {
  margin-top: 0.2rem;
  font-size: 0.74rem;
  line-height: 1.1;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #5f7191;
}
.package-card {
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}
.package-title-text {
  white-space: normal;
  overflow-wrap: anywhere;
  display: block;
  line-height: 1.25;
}
.package-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}
.pkg-selected {
  border: 1px solid var(--theme-color);
  background: linear-gradient(180deg, #f4fbf7 0%, #eaf8f0 100%);
  box-shadow: 0 0 0 1px var(--theme-color) inset, 0 10px 24px rgba(15, 113, 52, 0.14);
}
.pkg-corner {
  position: absolute;
  right: 0;
  top: 0;
  padding: 0.16rem 0.45rem;
  font-size: 0.61rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  color: #ffffff;
  background: linear-gradient(180deg, #1d8a48 0%, #0f7134 100%);
  border-bottom-left-radius: 0.55rem;
}
.package-price {
  color: #ec5b14;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.55);
}
.pay-selected {
  border-color: var(--theme-color);
  box-shadow: 0 0 0 1px var(--theme-color) inset;
  background: #f8fafc;
}
</style>