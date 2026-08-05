<script setup lang="ts">
const { data, pending, refresh } = await useFetch('/api/dashboard', { server: false });
const stats = computed(() => data.value?.stats || { users: 0, orders: 0, transactions: 0, revenue: 0, pendingOrders: 0 });
const recentUsers = computed(() => data.value?.recentUsers || []);

const statCards = computed(() => [
  { label: 'Total Users', value: stats.value.users.toLocaleString(), icon: 'users', color: 'bg-blue-500', light: 'bg-blue-50 text-blue-700' },
  { label: 'Total Orders', value: stats.value.orders.toLocaleString(), icon: 'box', color: 'bg-green-500', light: 'bg-green-50 text-green-700' },
  { label: 'Transactions', value: stats.value.transactions.toLocaleString(), icon: 'card', color: 'bg-purple-500', light: 'bg-purple-50 text-purple-700' },
  { label: 'Revenue', value: 'Tk ' + Number(stats.value.revenue).toLocaleString(), icon: 'wallet', color: 'bg-yellow-500', light: 'bg-yellow-50 text-yellow-700' },
  { label: 'Pending Orders', value: stats.value.pendingOrders.toLocaleString(), icon: 'clock', color: 'bg-red-500', light: 'bg-red-50 text-red-700' }
]);
</script>

<template>
  <div>
    <!-- Stat Cards -->
    <div v-if="pending" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      <div v-for="i in 5" :key="i" class="admin-card h-24 animate-pulse bg-slate-200 sm:h-28" />
    </div>

    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      <div v-for="card in statCards" :key="card.label" class="admin-card p-3 sm:p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide sm:text-xs">{{ card.label }}</p>
            <p class="mt-1.5 text-xl font-black text-slate-900 sm:mt-2 sm:text-2xl">{{ card.value }}</p>
          </div>
          <div :class="card.light" class="flex h-8 w-8 items-center justify-center rounded-lg sm:h-10 sm:w-10 sm:rounded-xl">
            <svg v-if="card.icon === 'users'" viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0 2c-2.67 0-8 1.34-8 4v1h10v-1c0-1.15.53-2.13 1.4-2.95A12.2 12.2 0 0 0 8 13Zm8 0c-.29 0-.62.02-.97.05A4.96 4.96 0 0 1 18 17v1h6v-1c0-2.66-5.33-4-8-4Z"/></svg>
            <svg v-else-if="card.icon === 'box'" viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="m12 2 9 4.5v11L12 22 3 17.5v-11L12 2Zm0 2.2L6 7l6 2.8L18 7l-6-2.8ZM5 8.6v7.6l6 3v-7.6l-6-3Zm14 0-6 3v7.6l6-3V8.6Z"/></svg>
            <svg v-else-if="card.icon === 'card'" viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4H3V6h18v2Zm0 10H3v-6h18v6Z"/></svg>
            <svg v-else-if="card.icon === 'wallet'" viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M21 7H3a2 2 0 0 0-2 2v8a3 3 0 0 0 3 3h16a3 3 0 0 0 3-3V9a2 2 0 0 0-2-2ZM4 5h14v2H4V5Zm15 9a2 2 0 1 1-2-2 2 2 0 0 1 2 2Z"/></svg>
            <svg v-else viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M12 1a11 11 0 1 0 11 11A11 11 0 0 0 12 1Zm1 11.41V6h-2v7.24l4.2 2.52 1-1.68Z"/></svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="mt-6 admin-card sm:mt-8">
      <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-6 sm:py-4">
        <h2 class="text-sm font-bold text-slate-800 sm:text-base">Recent Users</h2>
        <NuxtLink to="/users" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">View All →</NuxtLink>
      </div>
      <div class="overflow-x-auto">
        <table class="admin-table min-w-full">
          <thead class="bg-slate-50">
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Balance</th>
              <th>Joined</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in recentUsers" :key="user.id">
              <td class="font-medium text-slate-800">{{ user.name }}</td>
              <td class="text-slate-500">{{ user.email }}</td>
              <td class="font-semibold text-green-600">Tk {{ Number(user.balance).toLocaleString() }}</td>
              <td class="text-slate-400 text-xs">{{ user.joined ? new Date(user.joined).toLocaleDateString('en-US') : '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick links -->
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
      <NuxtLink to="/users" class="admin-card group flex items-center gap-3 p-4 transition-all hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-8 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0 2c-2.67 0-8 1.34-8 4v1h10v-1c0-1.15.53-2.13 1.4-2.95A12.2 12.2 0 0 0 8 13Zm8 0c-.29 0-.62.02-.97.05A4.96 4.96 0 0 1 18 17v1h6v-1c0-2.66-5.33-4-8-4Z"/></svg></span>
        <div>
          <p class="text-sm font-bold text-slate-700 group-hover:text-indigo-600">Users</p>
          <p class="text-xs text-slate-400">Manage</p>
        </div>
      </NuxtLink>
      <NuxtLink to="/orders" class="admin-card group flex items-center gap-3 p-4 transition-all hover:-translate-y-0.5 hover:border-green-300 hover:shadow-md">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600"><svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="m12 2 9 4.5v11L12 22 3 17.5v-11L12 2Zm0 2.2L6 7l6 2.8L18 7l-6-2.8ZM5 8.6v7.6l6 3v-7.6l-6-3Zm14 0-6 3v7.6l6-3V8.6Z"/></svg></span>
        <div>
          <p class="text-sm font-bold text-slate-700 group-hover:text-green-600">Orders</p>
          <p class="text-xs text-slate-400">Process</p>
        </div>
      </NuxtLink>
      <NuxtLink to="/transactions" class="admin-card group flex items-center gap-3 p-4 transition-all hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600"><svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4H3V6h18v2Zm0 10H3v-6h18v6Z"/></svg></span>
        <div>
          <p class="text-sm font-bold text-slate-700 group-hover:text-purple-600">Transactions</p>
          <p class="text-xs text-slate-400">Payments</p>
        </div>
      </NuxtLink>
      <NuxtLink to="/settings" class="admin-card group flex items-center gap-3 p-4 transition-all hover:-translate-y-0.5 hover:border-yellow-300 hover:shadow-md">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><svg viewBox="0 0 24 24" class="h-5 w-5 fill-current"><path d="m19.14 12.94.86-1.49-1.7-2.94-1.71.07a6.91 6.91 0 0 0-1.03-.6l-.67-1.57h-3.4l-.67 1.57c-.35.16-.69.36-1.03.6l-1.71-.07-1.7 2.94.86 1.49a6.83 6.83 0 0 0 0 1.2l-.86 1.49 1.7 2.94 1.71-.07c.33.24.68.44 1.03.6l.67 1.57h3.4l.67-1.57c.35-.16.69-.36 1.03-.6l1.71.07 1.7-2.94-.86-1.49a6.83 6.83 0 0 0 0-1.2ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z"/></svg></span>
        <div>
          <p class="text-sm font-bold text-slate-700 group-hover:text-yellow-600">Settings</p>
          <p class="text-xs text-slate-400">Site Config</p>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>
