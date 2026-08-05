<script setup lang="ts">
const auth = useAdminAuth();
const loading = ref(true);
const saving = ref(false);

const form = reactive({
  name: '',
  email: '',
  current_password: '',
  new_password: '',
  confirm_password: ''
});

async function loadProfile() {
  loading.value = true;
  try {
    const res = await $fetch<any>('/api/auth/profile');
    form.name = String(res?.profile?.name || '');
    form.email = String(res?.profile?.email || '');
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to load profile');
  } finally {
    loading.value = false;
  }
}

async function saveProfile() {
  if (!form.name.trim() || !form.email.trim()) {
    alert('Name and email are required.');
    return;
  }
  if (form.new_password || form.confirm_password) {
    if (!form.current_password) {
      alert('Current password is required to change password.');
      return;
    }
    if (form.new_password.length < 6) {
      alert('New password must be at least 6 characters.');
      return;
    }
    if (form.new_password !== form.confirm_password) {
      alert('New password and confirm password do not match.');
      return;
    }
  }

  saving.value = true;
  try {
    const res = await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      body: {
        name: form.name,
        email: form.email,
        current_password: form.current_password,
        new_password: form.new_password
      }
    });
    if (res?.admin) {
      auth.setAdmin({
        id: Number(res.admin.id || 0),
        name: String(res.admin.name || form.name),
        email: String(res.admin.email || form.email)
      });
    }
    form.current_password = '';
    form.new_password = '';
    form.confirm_password = '';
    alert('Profile updated successfully.');
  } catch (e: any) {
    alert(e?.data?.statusMessage || 'Failed to update profile');
  } finally {
    saving.value = false;
  }
}

onMounted(loadProfile);
</script>

<template>
  <div class="max-w-3xl space-y-5">
    <div>
      <h2 class="admin-panel-title">Profile</h2>
      <p class="text-sm text-slate-500">Update your account details and password.</p>
    </div>

    <div v-if="loading" class="admin-card p-10 text-center text-slate-400">Loading...</div>

    <div v-else class="admin-card p-5 sm:p-6">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-semibold text-slate-500">Name</label>
          <input v-model="form.name" type="text" class="admin-input" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold text-slate-500">Email</label>
          <input v-model="form.email" type="email" class="admin-input" />
        </div>
      </div>

      <div class="mt-6 rounded-xl border border-slate-200 p-4">
        <h3 class="mb-3 text-sm font-bold text-slate-700">Change Password</h3>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Current Password</label>
            <input v-model="form.current_password" type="password" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">New Password</label>
            <input v-model="form.new_password" type="password" class="admin-input" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold text-slate-500">Confirm Password</label>
            <input v-model="form.confirm_password" type="password" class="admin-input" />
          </div>
        </div>
      </div>

      <div class="mt-5">
        <button type="button" class="admin-btn-primary px-6 py-2.5" :disabled="saving" @click="saveProfile">
          {{ saving ? 'Saving...' : 'Update Profile' }}
        </button>
      </div>
    </div>
  </div>
</template>
