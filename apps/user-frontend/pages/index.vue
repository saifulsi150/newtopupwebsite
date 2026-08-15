<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

type Product = {
  id: number;
  title: string;
  slug: string;
  image_url: string;
  price_from: number;
  category_id?: number;
  category_title?: string;
};

const { data, pending, error } = await useFetch<{ products: Product[] }>('/api/home', {
  key: 'home-products-v2'
});
const { data: categoriesData } = await useFetch<{ categories: Array<{ id: number; name: string }> }>('/api/categories', {
  key: 'home-categories-v1'
});
const { data: homeSettingsData } = await useFetch<any>('/api/settings/home', {
  key: 'home-settings-v1'
});

const products = computed(() => data.value?.products || []);
const categories = computed(() => categoriesData.value?.categories || []);
const homeSettings = computed(() => homeSettingsData.value?.home || {});

const sliderItems = computed(() => {
  const list = Array.isArray(homeSettings.value?.sliderItems) ? homeSettings.value.sliderItems : [];
  return list.filter((item: any) => Boolean(item?.enabled));
});

const topSupportButtons = computed(() => {
  const list = Array.isArray(homeSettings.value?.topSupportButtons) ? homeSettings.value.topSupportButtons : [];
  const filtered = list.filter((item: any) => Boolean(item?.enabled));
  if (filtered.length) return filtered;
  // Default 3 buttons if not configured
  return [
    { key: 'telegram', sub: 'SUPPORT', label: 'Telegram', url: 'https://t.me/rgbazer' },
    { key: 'group', sub: 'GROUP', label: 'Join Group', url: 'https://t.me/rgbazer' },
    { key: 'whatsapp', sub: 'CHAT', label: 'WhatsApp', url: 'https://wa.me/8801858039475' }
  ];
});

const noticeText = computed(() => {
  const raw = String(homeSettings.value?.notice || '').trim();
  return raw || 'বাবা-মা বিকাশ থেকে টাকা চুরি করে কেউ অর্ডার করবেন না ,, ১৮ বছরের নিচে কেউ অর্ডার করবেন না। যে কোন সমস্যায় WhatsApp 01858039475';
});

const noticeDismissed = ref(false);

// Slider state
const currentSlide = ref(0);
let sliderTimer: ReturnType<typeof setInterval> | null = null;

function nextSlide() {
  if (sliderItems.value.length <= 1) return;
  currentSlide.value = (currentSlide.value + 1) % sliderItems.value.length;
}

function startSliderTimer() {
  if (sliderTimer) clearInterval(sliderTimer);
  sliderTimer = setInterval(() => {
    nextSlide();
  }, 4000);
}

function stopSliderTimer() {
  if (sliderTimer) clearInterval(sliderTimer);
}

// Group products by category
const groupedCategories = computed(() => {
  const allProducts = products.value;
  if (!allProducts.length) return [];

  // Group by category_title
  const groups: { [key: string]: Product[] } = {};
  allProducts.forEach((p) => {
    const cat = p.category_title?.trim() || 'Special Offer';
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(p);
  });

  return Object.keys(groups).map((title) => ({
    title,
    products: groups[title]
  }));
});

// Recent Live Orders Data
const recentOrders = ref([
  { id: 1, name: 'Sojib Ahmed', avatarText: 'S', avatarBg: '#c2410c', desc: '115 Diamond 💎 - ৳76', status: 'Done', isDone: true },
  { id: 2, name: 'Sojib Ahmed', avatarText: 'S', avatarBg: '#c2410c', desc: '610 Diamond 💎 - ৳385', status: 'Done', isDone: true },
  { id: 3, name: 'FT Raib', avatarText: 'FT', avatarBg: '#7c3aed', desc: 'Monthly 💳 - ৳765', status: 'In Progress', isDone: false },
  { id: 4, name: 'Sakib Mian', avatarText: 'SM', avatarBg: '#0284c7', desc: 'Weekly 💳 - ৳154', status: 'Done', isDone: true },
  { id: 5, name: 'Md Igbal', avatarText: 'MI', avatarBg: '#059669', desc: '50 Diamond 💎 - ৳35', status: 'Done', isDone: true },
  { id: 6, name: 'Md Igbal', avatarText: 'MI', avatarBg: '#059669', desc: 'Weekly 💳 - ৳154', status: 'Done', isDone: true },
  { id: 7, name: 'Sakil Ahmed', avatarText: 'SA', avatarBg: '#db2777', desc: 'Weekly 💳 - ৳154', status: 'Done', isDone: true },
  { id: 8, name: 'Sakil Ahmed', avatarText: 'SA', avatarBg: '#db2777', desc: 'Monthly 💳 - ৳790', status: 'Done', isDone: true },
  { id: 9, name: 'Ariyan Safi11', avatarText: 'A', avatarBg: '#2563eb', desc: '1x Weekly Lite - ৳45', status: 'Done', isDone: true },
  { id: 10, name: 'MD Sanjad', avatarText: 'MS', avatarBg: '#ea580c', desc: 'Weekly 💳 - ৳158', status: 'Done', isDone: true },
]);

// Leaderboard Data
const topLeaderboard = ref({
  rank1: { name: 'MD Siful', tier: 'Grand Master', pts: '250,091', avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120&h=120&fit=crop' },
  rank2: { name: 'MD Mizan', tier: 'Master', pts: '88,474', avatar: 'MD' },
  rank3: { name: 'Helal Hossen', tier: 'Master', pts: '50,831', avatar: 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=120&h=120&fit=crop' },
  others: [
    { rank: 4, name: 'Armaan Oooo', tier: 'Heroic', pts: '48,708', avatarText: 'AO' },
    { rank: 5, name: 'Md Saiful', tier: 'Heroic', pts: '47,964', avatarText: 'MS' },
    { rank: 6, name: 'Prince Tanvir', tier: 'Heroic', pts: '47,177', avatarText: 'P' },
    { rank: 7, name: 'MD ASHIK MIAH', tier: 'Heroic', pts: '44,818', avatarText: 'MA' },
    { rank: 8, name: 'RS Samiya Nushrat', tier: 'Heroic', pts: '43,850', avatarText: 'RS' },
    { rank: 9, name: 'MEHEDI HASAN', tier: 'Heroic', pts: '40,302', avatarText: 'MH' },
    { rank: 10, name: 'ATHAX 7X', tier: 'Heroic', pts: '40,025', avatarText: 'A' },
  ]
});

function handleProductImageError(event: Event) {
  const target = event.target as HTMLImageElement | null;
  if (!target) return;
  target.onerror = null;
  target.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
}

onMounted(() => {
  if (sliderItems.value.length > 1) startSliderTimer();
});

onBeforeUnmount(() => {
  stopSliderTimer();
});
</script>

<template>
  <div class="home-page">
    <!-- Notice Box -->
    <div v-if="!noticeDismissed && noticeText" class="notice-box">
      <div class="notice-body">
        <span class="notice-tag">Notice:</span>
        <span class="notice-text">{{ noticeText }}</span>
      </div>
      <button class="notice-close" type="button" aria-label="Close Notice" @click="noticeDismissed = true">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
      </button>
    </div>

    <!-- Main Banner Slider -->
    <div v-if="homeSettings.showSlider !== false" class="slider-wrapper">
      <div v-if="sliderItems.length" class="slider-container">
        <div class="slider-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
          <a
            v-for="(item, index) in sliderItems"
            :key="index"
            :href="item.link_url || '#'"
            target="_blank"
            rel="noopener"
            class="slide-item"
          >
            <img :src="item.image_url" :alt="item.title || 'Banner'" @error="handleProductImageError" />
          </a>
        </div>
      </div>
      <!-- Fallback Sample Slider Banner -->
      <div v-else class="sample-banner">
        <div class="sample-banner-content">
          <div class="sample-badge">RG BAZZER</div>
          <div class="sample-headline">নতুন নিয়মে ডায়মন্ড টপআপ করুন নিজে নিজেই</div>
          <div class="sample-sub">২০ সেকেন্ডে অটো ডেলিভারি</div>
        </div>
      </div>

      <!-- Single Dash Indicator -->
      <div class="slider-dash-indicator"></div>
    </div>

    <!-- Quick Action Support Buttons (3 in Grid) -->
    <div class="action-buttons-grid">
      <a
        v-for="btn in topSupportButtons"
        :key="btn.key"
        :href="btn.url"
        target="_blank"
        rel="noopener"
        class="action-card"
      >
        <div class="action-icon-circle">
          <svg v-if="btn.key === 'telegram'" viewBox="0 0 24 24" class="action-icon-svg"><path d="M21.4 4.6a1 1 0 0 0-1-.14L3.7 11.1a1 1 0 0 0 .06 1.86l4.3 1.6 1.6 4.3a1 1 0 0 0 1.86.06l6.64-16.7a1 1 0 0 0-.14-1Z"/></svg>
          <svg v-else-if="btn.key === 'group'" viewBox="0 0 24 24" class="action-icon-svg"><path d="M12 12a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-6 7a6 6 0 0 1 12 0Zm12 0a6 6 0 0 0-3.4-5.4A5 5 0 0 1 20 18.2V19ZM4 19v-.8a5 5 0 0 1 5.4-4.6A6 6 0 0 0 6 19Z"/></svg>
          <svg v-else viewBox="0 0 24 24" class="action-icon-svg"><path d="M12 2a10 10 0 0 0-8.66 15l-1.1 4 4.1-1.08A10 10 0 1 0 12 2Zm5.1 13.26c-.22.62-1.28 1.2-1.77 1.28s-1.12.11-1.81-.11a15 15 0 0 1-4.1-1.81 13.7 13.7 0 0 1-2.53-3.06 4 4 0 0 1-.84-2.16A2.33 2.33 0 0 1 6.8 7.3a.83.83 0 0 1 .6-.28h.43c.14 0 .34-.05.53.4s.66 1.62.72 1.74a.43.43 0 0 1 0 .42c-.06.12-.1.2-.2.3s-.2.22-.3.34-.2.2-.08.42a7.06 7.06 0 0 0 1.3 1.6A5.84 5.84 0 0 0 11.52 13c.23.12.36.1.5-.06s.58-.67.73-.9.3-.2.5-.12 1.29.6 1.51.7.38.16.44.24.06.44-.16 1.06Z"/></svg>
        </div>
        <div class="action-text-wrap">
          <span class="action-sub">{{ btn.sub || 'SUPPORT' }}</span>
          <span class="action-main">{{ btn.label || 'Support' }}</span>
        </div>
      </a>
    </div>

    <!-- Loading State -->
    <div v-if="pending" class="product-loading">
      <div v-for="i in 6" :key="i" class="skeleton-card">
        <div class="skeleton-thumb"></div>
        <div class="skeleton-line"></div>
      </div>
    </div>

    <!-- Product Sections Grouped by Category -->
    <template v-else-if="groupedCategories.length">
      <section v-for="catGroup in groupedCategories" :key="catGroup.title" class="category-section">
        <h2 class="section-title">{{ catGroup.title }}</h2>

        <div class="products-grid">
          <NuxtLink
            v-for="item in catGroup.products"
            :key="item.id"
            :to="`/topup/${item.slug}`"
            class="product-item"
          >
            <div class="product-thumb-wrap">
              <img :src="item.image_url" :alt="item.title" loading="lazy" decoding="async" @error="handleProductImageError" />
            </div>
            <p class="product-name">{{ item.title }}</p>
          </NuxtLink>
        </div>
      </section>
    </template>

    <!-- Fallback Product Grid if no grouped categories -->
    <section v-else-if="products.length" class="category-section">
      <h2 class="section-title">Special Offer</h2>
      <div class="products-grid">
        <NuxtLink
          v-for="item in products"
          :key="item.id"
          :to="`/topup/${item.slug}`"
          class="product-item"
        >
          <div class="product-thumb-wrap">
            <img :src="item.image_url" :alt="item.title" loading="lazy" decoding="async" @error="handleProductImageError" />
          </div>
          <p class="product-name">{{ item.title }}</p>
        </NuxtLink>
      </div>
    </section>

    <!-- ===== RECENT ORDERS SECTION ===== -->
    <section class="recent-orders-card">
      <div class="orders-header">
        <div class="orders-title-left">
          <div class="orders-icon-wrap">
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-[#e11d48]"><path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-1.99.9-1.99 2L3 20c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm0 10c-2.76 0-5-2.24-5-5h2c0 1.66 1.34 3 3 3s3-1.34 3-3h2c0 2.76-2.24 5-5 5z"/></svg>
          </div>
          <div>
            <h3 class="orders-title">Recent Orders</h3>
            <div class="orders-live-status">
              <span class="live-dot"></span>
              <span class="live-text">Live</span>
              <span class="live-pulse">〰️</span>
            </div>
          </div>
        </div>
        <button type="button" class="orders-refresh-btn" aria-label="Refresh Orders">
          <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        </button>
      </div>

      <div class="orders-items-list">
        <div v-for="order in recentOrders" :key="order.id" class="order-row">
          <div class="order-left">
            <div class="order-avatar" :style="{ backgroundColor: order.avatarBg }">
              {{ order.avatarText }}
            </div>
            <div class="order-info">
              <span class="order-user">{{ order.name }}</span>
              <span class="order-desc">{{ order.desc }}</span>
            </div>
          </div>
          <div class="order-right">
            <span :class="['order-badge', order.isDone ? 'badge-done' : 'badge-progress']">
              {{ order.isDone ? '✓ Done' : '⌛ In Progress' }}
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== TOP SPEND LEADERBOARD ===== -->
    <section class="leaderboard-section">
      <!-- Leaderboard Header Banner -->
      <div class="leaderboard-banner">
        <div class="banner-left">
          <div class="trophy-circle">🏆</div>
          <div>
            <div class="leaderboard-sub">L E A D E R B O A R D</div>
            <h3 class="leaderboard-title">Top Spend Leaderboard</h3>
            <p class="leaderboard-desc">Top 10 players ranked by lifetime spend and profile rank.</p>
          </div>
        </div>
        <NuxtLink to="/leaderboard" class="leaderboard-view-all">
          <span>View Full Leaderboard</span>
          <span class="ml-1">→</span>
        </NuxtLink>
      </div>

      <!-- Top 3 Podium Cards -->
      <div class="podium-grid">
        <!-- Rank 2: Silver -->
        <div class="podium-card podium-silver">
          <div class="podium-badge badge-silver">2</div>
          <div class="podium-avatar">MD</div>
          <div class="podium-name">{{ topLeaderboard.rank2.name }}</div>
          <div class="podium-tier">🛡️ {{ topLeaderboard.rank2.tier }}</div>
          <div class="podium-points">
            <span>⭐</span>
            <strong>{{ topLeaderboard.rank2.pts }} Pts</strong>
          </div>
        </div>

        <!-- Rank 1: Gold (Center Elevated) -->
        <div class="podium-card podium-gold">
          <div class="podium-top-crown">👑 TOP-1</div>
          <div class="podium-avatar avatar-gold">
            <img :src="topLeaderboard.rank1.avatar" alt="Rank 1" class="h-full w-full object-cover rounded-full" />
          </div>
          <div class="podium-name font-black">{{ topLeaderboard.rank1.name }}</div>
          <div class="podium-tier tier-gold">🛡️ {{ topLeaderboard.rank1.tier }}</div>
          <div class="podium-points points-gold">
            <span>⭐</span>
            <strong>{{ topLeaderboard.rank1.pts }} Pts</strong>
          </div>
        </div>

        <!-- Rank 3: Bronze -->
        <div class="podium-card podium-bronze">
          <div class="podium-badge badge-bronze">3</div>
          <div class="podium-avatar avatar-bronze">
            <img :src="topLeaderboard.rank3.avatar" alt="Rank 3" class="h-full w-full object-cover rounded-full" />
          </div>
          <div class="podium-name">{{ topLeaderboard.rank3.name }}</div>
          <div class="podium-tier">🛡️ {{ topLeaderboard.rank3.tier }}</div>
          <div class="podium-points">
            <span>⭐</span>
            <strong>{{ topLeaderboard.rank3.pts }} Pts</strong>
          </div>
        </div>
      </div>

      <!-- Rank 4 to 10 Table -->
      <div class="leaderboard-table-card">
        <div class="table-header">
          <span>RANK</span>
          <span>PLAYER</span>
          <span>TIER</span>
          <span>SPEND POINTS</span>
        </div>
        <div v-for="row in topLeaderboard.others" :key="row.rank" class="table-row">
          <div class="rank-shield">{{ row.rank }}</div>
          <div class="player-cell">
            <div class="player-avatar-small">{{ row.avatarText }}</div>
            <span class="player-name">{{ row.name }}</span>
          </div>
          <div class="tier-cell">
            <span>🛡️</span>
            <span>{{ row.tier }}</span>
          </div>
          <div class="points-cell">
            <span>⭐</span>
            <strong>{{ row.pts }} Pts</strong>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== PROMO ACTION BUTTONS (Download App & Join Telegram) ===== -->
    <div class="promo-buttons-grid">
      <!-- App Download Card -->
      <a href="#" class="promo-card">
        <div class="promo-icon-circle">
          <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3.609 1.814L13.792 12 3.61 22.186c-.198-.186-.319-.452-.319-.748V2.562c0-.296.121-.562.318-.748zm11.306 11.307l2.259 2.259-11.455 6.46 9.196-8.719zm0-2.242L5.719 2.16l11.455 6.46-2.259 2.259zm1.127 1.121l3.528 1.99c.732.414.732 1.09 0 1.504l-3.528 1.99-2.029-2.029 2.029-2.029z" fill="#00C1A6"/>
          </svg>
        </div>
        <div>
          <div class="promo-sub">Download Our Mobile App</div>
          <div class="promo-main text-slate-800">Click Here →</div>
        </div>
      </a>

      <!-- Telegram Promo Card -->
      <a :href="topSupportButtons[0]?.url || 'https://t.me/rgbazer'" target="_blank" rel="noopener" class="promo-card">
        <div class="promo-icon-circle bg-[#0088cc]">
          <svg class="h-6 w-6 fill-white" viewBox="0 0 24 24"><path d="M21.4 4.6a1 1 0 0 0-1-.14L3.7 11.1a1 1 0 0 0 .06 1.86l4.3 1.6 1.6 4.3a1 1 0 0 0 1.86.06l6.64-16.7a1 1 0 0 0-.14-1Z"/></svg>
        </div>
        <div>
          <div class="promo-sub">Giveaway & Offer Update</div>
          <div class="promo-main text-[#0088cc]">Join Telegram</div>
        </div>
      </a>
    </div>

    <!-- ===== PROMO BANNER AT BOTTOM ===== -->
    <div class="bottom-promo-banner">
      <div class="bottom-promo-content">
        <div class="bottom-promo-badge">RG BAZZER</div>
        <div class="bottom-promo-text">সবচেয়ে কম দামে ডায়মন্ড টপ আপ করুন</div>
        <div class="bottom-promo-payments">
          <span class="payment-chip">bKash</span>
          <span class="payment-chip">Nagad</span>
          <span class="payment-chip">Rocket</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home-page {
  padding-top: 10px;
  max-width: 1200px;
  margin: 0 auto;
  padding-bottom: 24px;
}

/* ===== NOTICE BOX ===== */
.notice-box {
  background: #0a6b2a;
  color: #ffffff;
  padding: 10px 14px;
  margin: 0 12px 12px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(10, 107, 42, 0.18);
}

.notice-body {
  font-size: 13px;
  line-height: 1.45;
}

.notice-tag {
  font-weight: 900;
  font-size: 14px;
  margin-right: 6px;
}

.notice-text {
  font-weight: 500;
}

.notice-close {
  background: transparent;
  border: none;
  color: #ffffff;
  cursor: pointer;
  padding: 2px;
  flex-shrink: 0;
  opacity: 0.85;
  transition: opacity 0.15s;
}

.notice-close:hover {
  opacity: 1;
}

/* ===== SLIDER WRAPPER ===== */
.slider-wrapper {
  margin: 0 12px 14px;
}

.slider-container {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
  background: #0f172a;
}

.slider-track {
  display: flex;
  transition: transform 0.45s ease-out;
}

.slide-item {
  min-width: 100%;
  display: block;
}

.slide-item img {
  width: 100%;
  aspect-ratio: 16/7;
  object-fit: cover;
  display: block;
}

.sample-banner {
  background: linear-gradient(135deg, #0a1128 0%, #1c1917 100%);
  border-radius: 10px;
  padding: 24px 20px;
  color: #ffffff;
  text-align: center;
  box-shadow: 0 4px 14px rgba(0,0,0,0.12);
}

.sample-badge {
  color: #e11d48;
  font-weight: 900;
  font-size: 18px;
  letter-spacing: 1px;
}

.sample-headline {
  font-size: 20px;
  font-weight: 900;
  margin-top: 6px;
  color: #facc15;
}

.sample-sub {
  margin-top: 8px;
  display: inline-block;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 4px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
}

.slider-dash-indicator {
  width: 24px;
  height: 5px;
  background: #0f172a;
  border-radius: 3px;
  margin: 8px auto 0;
}

/* ===== ACTION BUTTONS (3 IN GRID) ===== */
.action-buttons-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding: 0 12px;
  margin-top: 6px;
  margin-bottom: 18px;
}

.action-card {
  background: #0a6b2a;
  color: #ffffff;
  border-radius: 10px;
  padding: 8px 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  box-shadow: 0 3px 8px rgba(10, 107, 42, 0.2);
  transition: transform 0.12s ease;
}

.action-card:active {
  transform: scale(0.96);
}

.action-icon-circle {
  width: 32px;
  height: 32px;
  background: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-icon-svg {
  width: 16px;
  height: 16px;
  fill: #0a6b2a;
}

.action-text-wrap {
  display: flex;
  flex-direction: column;
}

.action-sub {
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
  opacity: 0.85;
  letter-spacing: 0.4px;
}

.action-main {
  font-size: 12px;
  font-weight: 800;
  line-height: 1.15;
}

/* ===== CATEGORY & PRODUCTS GRID ===== */
.category-section {
  margin-bottom: 24px;
}

.section-title {
  text-align: center;
  font-size: 20px;
  font-weight: 900;
  color: #132a4e;
  margin: 18px 0 12px;
  letter-spacing: -0.2px;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 0 12px;
}

@media (min-width: 640px) {
  .products-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
  }
}

@media (min-width: 900px) {
  .products-grid {
    grid-template-columns: repeat(6, 1fr);
    gap: 16px;
  }
}

.product-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
  outline: none;
}

.product-thumb-wrap {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 14px;
  overflow: hidden;
  background: #000000;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  transition: transform 0.12s ease-out;
}

/* Push Inward on Click (No outer bounce) */
.product-item:active .product-thumb-wrap {
  transform: scale(0.92);
}

.product-thumb-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.product-name {
  color: #1e293b;
  font-size: 11.5px;
  font-weight: 700;
  text-align: center;
  margin-top: 6px;
  line-height: 1.25;
  word-break: break-word;
}

/* SKELETON */
.product-loading {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  padding: 0 12px;
}

.skeleton-card {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.skeleton-thumb {
  width: 100%;
  aspect-ratio: 1/1;
  border-radius: 14px;
  background: #e2e8f0;
}

.skeleton-line {
  width: 60%;
  height: 12px;
  border-radius: 6px;
  background: #e2e8f0;
  margin-top: 6px;
}

/* ===== RECENT ORDERS CARD ===== */
.recent-orders-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 16px 14px;
  margin: 28px 12px 20px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
}

.orders-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 10px;
}

.orders-title-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.orders-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #ffe4e6;
  display: flex;
  align-items: center;
  justify-content: center;
}

.orders-title {
  font-size: 16px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.2;
}

.orders-live-status {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  font-weight: 700;
  color: #16a34a;
}

.live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #16a34a;
}

.orders-refresh-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.orders-items-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.order-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  border-radius: 10px;
  transition: background 0.15s;
}

.order-row:hover {
  background: #f8fafc;
}

.order-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.order-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  color: #ffffff;
  font-size: 12px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.order-info {
  display: flex;
  flex-direction: column;
}

.order-user {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
}

.order-desc {
  font-size: 11.5px;
  color: #64748b;
  font-weight: 500;
}

.order-badge {
  font-size: 11px;
  font-weight: 800;
  padding: 3px 10px;
  border-radius: 20px;
}

.badge-done {
  background: #dcfce7;
  color: #16a34a;
}

.badge-progress {
  background: #ffedd5;
  color: #ea580c;
}

/* ===== LEADERBOARD SECTION ===== */
.leaderboard-section {
  margin: 24px 12px;
}

.leaderboard-banner {
  background: linear-gradient(135deg, #fef9c3 0%, #fffbeb 100%);
  border: 1px solid #fef08a;
  border-radius: 16px;
  padding: 16px 14px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  box-shadow: 0 2px 10px rgba(234, 179, 8, 0.1);
}

.banner-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.trophy-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #fef08a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.leaderboard-sub {
  font-size: 10px;
  font-weight: 900;
  color: #d97706;
  letter-spacing: 2px;
}

.leaderboard-title {
  font-size: 20px;
  font-weight: 900;
  color: #b45309;
  line-height: 1.2;
}

.leaderboard-desc {
  font-size: 11.5px;
  color: #78350f;
  margin-top: 2px;
}

.leaderboard-view-all {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  color: #0f172a;
  text-decoration: none;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}

/* Podium Grid */
.podium-grid {
  display: grid;
  grid-template-columns: 1fr 1.15fr 1fr;
  gap: 10px;
  margin-top: 14px;
  align-items: flex-end;
}

.podium-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 16px 8px;
  text-align: center;
  box-shadow: 0 4px 16px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
}

.podium-gold {
  background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
  border: 1.5px solid #fef08a;
  padding-top: 22px;
  box-shadow: 0 8px 24px rgba(234, 179, 8, 0.15);
}

.podium-silver {
  background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%);
  border: 1px solid #e0f2fe;
}

.podium-bronze {
  background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
  border: 1px solid #ffedd5;
}

.podium-top-crown {
  position: absolute;
  top: -10px;
  background: #f59e0b;
  color: #ffffff;
  font-size: 10px;
  font-weight: 900;
  padding: 2px 8px;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);
}

.podium-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  color: #fff;
  font-size: 11px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
}

.badge-silver { background: #64748b; }
.badge-bronze { background: #b45309; }

.podium-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #0f172a;
  font-size: 14px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #cbd5e1;
}

.avatar-gold {
  width: 56px;
  height: 56px;
  border-color: #f59e0b;
}

.podium-name {
  font-size: 13px;
  font-weight: 800;
  color: #0f172a;
  margin-top: 6px;
}

.podium-tier {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
  margin-top: 2px;
}

.podium-points {
  margin-top: 8px;
  background: #f8fafc;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  color: #0f172a;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.points-gold {
  background: #fef08a;
  color: #92400e;
}

/* Leaderboard Table */
.leaderboard-table-card {
  background: #ffffff;
  border-radius: 14px;
  padding: 12px 14px;
  margin-top: 12px;
  box-shadow: 0 4px 14px rgba(0,0,0,0.04);
  border: 1px solid #f1f5f9;
}

.table-header {
  display: grid;
  grid-template-columns: 40px 1.5fr 1fr 1fr;
  font-size: 10.5px;
  font-weight: 800;
  color: #94a3b8;
  padding-bottom: 8px;
  border-bottom: 1px solid #f1f5f9;
  letter-spacing: 0.5px;
}

.table-row {
  display: grid;
  grid-template-columns: 40px 1.5fr 1fr 1fr;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f8fafc;
  font-size: 12px;
}

.rank-shield {
  width: 24px;
  height: 24px;
  background: #0f172a;
  color: #ffffff;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 900;
}

.player-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.player-avatar-small {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #3b82f6;
  color: #ffffff;
  font-size: 10px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

.player-name {
  font-weight: 700;
  color: #0f172a;
}

.tier-cell {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #64748b;
  font-weight: 600;
}

.points-cell {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #b45309;
}

/* ===== PROMO BUTTONS GRID ===== */
.promo-buttons-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  padding: 0 12px;
  margin-top: 20px;
}

.promo-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  transition: transform 0.12s;
}

.promo-card:active {
  transform: scale(0.97);
}

.promo-icon-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.promo-sub {
  font-size: 9.5px;
  font-weight: 700;
  color: #64748b;
}

.promo-main {
  font-size: 12px;
  font-weight: 800;
}

/* ===== BOTTOM PROMO BANNER ===== */
.bottom-promo-banner {
  margin: 18px 12px 0;
  border-radius: 12px;
  background: linear-gradient(135deg, #111827 0%, #1e1b4b 100%);
  padding: 24px 16px;
  text-align: center;
  color: #ffffff;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.bottom-promo-badge {
  color: #e11d48;
  font-weight: 900;
  font-size: 16px;
}

.bottom-promo-text {
  font-size: 20px;
  font-weight: 900;
  color: #ffffff;
  margin-top: 4px;
}

.bottom-promo-payments {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 12px;
}

.payment-chip {
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.25);
  padding: 2px 10px;
  border-radius: 14px;
  font-size: 11px;
  font-weight: 700;
}
</style>