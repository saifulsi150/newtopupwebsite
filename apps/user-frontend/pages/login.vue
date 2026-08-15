<script setup lang="ts">
definePageMeta({
  middleware: 'guest'
});

const auth = useAuth();
const route = useRoute();
const config = useRuntimeConfig();
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);
const showPassword = ref(false);
const requiredNotice = computed(() => route.query.required === '1');

const googleAuthUrl = computed(() => {
  const clientId = String(config.public.googleClientId || '').trim();
  const redirectUri = String(config.public.googleRedirectUri || '').trim();
  if (!clientId || !redirectUri) return '';
  const url = new URL('https://accounts.google.com/o/oauth2/v2/auth');
  url.searchParams.set('client_id', clientId);
  url.searchParams.set('redirect_uri', redirectUri);
  url.searchParams.set('response_type', 'code');
  url.searchParams.set('scope', 'openid email profile');
  url.searchParams.set('prompt', 'select_account');
  url.searchParams.set('access_type', 'offline');
  return url.toString();
});

async function submit() {
  if (!email.value.trim() || !password.value) {
    error.value = 'Email and password are required';
    return;
  }

  loading.value = true;
  error.value = '';
  try {
    const res = await $fetch<any>('/api/login', {
      method: 'POST',
      body: { email: email.value, password: password.value },
      timeout: 6000
    });

    if (res && res.uid) {
      auth.login(res);
      await navigateTo('/account');
    } else {
      error.value = 'Invalid response from server';
    }
  } catch (e: any) {
    error.value = e?.data?.error || e?.data?.message || 'Login failed. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <section class="login-shell">
    <div class="login-card">
      <!-- Header -->
      <div class="login-header">
        <div class="login-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <h1 class="login-title">Welcome Back</h1>
        <p class="login-subtitle">Sign in to your account</p>
      </div>

      <div class="login-body">
        <!-- Required Notice -->
        <div v-if="requiredNotice" class="auth-notice auth-notice--warn">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          এই পেজ দেখতে আগে লগইন করুন
        </div>

        <!-- Google Login -->
        <a v-if="googleAuthUrl" :href="googleAuthUrl" class="btn-google">
          <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Continue with Google
        </a>

        <!-- Divider -->
        <div class="divider">
          <span class="divider-line"></span>
          <span class="divider-text">or sign in with email</span>
          <span class="divider-line"></span>
        </div>

        <!-- Email Field -->
        <div class="field-group">
          <label class="field-label" for="login-email">Email Address</label>
          <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
            </svg>
            <input
              id="login-email"
              v-model="email"
              type="email"
              class="field-input"
              placeholder="your@email.com"
              autocomplete="email"
              @keyup.enter="submit"
            />
          </div>
        </div>

        <!-- Password Field -->
        <div class="field-group">
          <label class="field-label" for="login-password">Password</label>
          <div class="input-wrap">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input
              id="login-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              class="field-input"
              placeholder="Your password"
              autocomplete="current-password"
              @keyup.enter="submit"
            />
            <button type="button" class="input-eye" :aria-label="showPassword ? 'Hide password' : 'Show password'" @click="showPassword = !showPassword">
              <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="auth-notice auth-notice--error">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          {{ error }}
        </div>

        <!-- Submit Button -->
        <button
          type="button"
          class="btn-login"
          :disabled="loading"
          @click="submit"
        >
          <svg v-if="loading" class="btn-spinner" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,0.3)" stroke-width="3"/>
            <path d="M12 2a10 10 0 0 1 10 10" stroke="white" stroke-width="3" stroke-linecap="round"/>
          </svg>
          <span>{{ loading ? 'Signing in...' : 'Login' }}</span>
        </button>

        <!-- Register Link -->
        <div class="register-link">
          Don't have an account?
          <NuxtLink to="/register" class="register-link-a">Register Now</NuxtLink>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.login-shell {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px 40px;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(15, 23, 42, 0.12);
  overflow: hidden;
}

.login-header {
  background: linear-gradient(135deg, var(--theme-color, #0f7134) 0%, color-mix(in srgb, var(--theme-color, #0f7134) 70%, #000) 100%);
  padding: 32px 24px 28px;
  text-align: center;
  color: white;
}

.login-icon {
  width: 58px;
  height: 58px;
  margin: 0 auto 14px;
  background: rgba(255,255,255,0.18);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid rgba(255,255,255,0.35);
}

.login-icon svg {
  width: 28px;
  height: 28px;
  color: white;
}

.login-title {
  font-size: 24px;
  font-weight: 900;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.3px;
}

.login-subtitle {
  margin-top: 4px;
  font-size: 13.5px;
  color: rgba(255,255,255,0.8);
}

.login-body {
  padding: 28px 24px 32px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Notices */
.auth-notice {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
}

.auth-notice svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.auth-notice--warn {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
}

.auth-notice--error {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  color: #be123c;
}

/* Google Button */
.btn-google {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 14px 18px;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  background: #ffffff;
  color: #1e293b;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.btn-google:hover {
  background: #f8fafc;
  border-color: #c7d2dd;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.btn-google:active {
  transform: scale(0.98);
}

.google-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

/* Divider */
.divider {
  display: flex;
  align-items: center;
  gap: 12px;
}

.divider-line {
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.divider-text {
  font-size: 12px;
  color: #94a3b8;
  white-space: nowrap;
  font-weight: 500;
}

/* Fields */
.field-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field-label {
  font-size: 13px;
  font-weight: 700;
  color: #374151;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 12px;
  width: 17px;
  height: 17px;
  color: #94a3b8;
  pointer-events: none;
}

.field-input {
  width: 100%;
  height: 50px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 0 42px 0 40px;
  font-size: 14px;
  color: #1e293b;
  background: #f8fafc;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
  outline: none;
}

.field-input:focus {
  border-color: var(--theme-color, #0f7134);
  background: #ffffff;
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-color, #0f7134) 15%, transparent);
}

.field-input::placeholder {
  color: #b0bec5;
}

.input-eye {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.15s;
}

.input-eye:hover {
  color: #475569;
}

.input-eye svg {
  width: 18px;
  height: 18px;
}

/* Login Button */
.btn-login {
  width: 100%;
  height: 52px;
  border: none;
  border-radius: 12px;
  background: var(--theme-color, #0f7134);
  color: #ffffff;
  font-size: 16px;
  font-weight: 800;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow: 0 6px 20px color-mix(in srgb, var(--theme-color, #0f7134) 45%, transparent);
  margin-top: 4px;
  letter-spacing: 0.3px;
}

.btn-login:hover:not(:disabled) {
  opacity: 0.92;
  box-shadow: 0 10px 28px color-mix(in srgb, var(--theme-color, #0f7134) 50%, transparent);
}

.btn-login:active:not(:disabled) {
  transform: scale(0.97);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-spinner {
  width: 20px;
  height: 20px;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Register Link */
.register-link {
  text-align: center;
  font-size: 13.5px;
  color: #64748b;
}

.register-link-a {
  color: var(--theme-color, #0f7134);
  font-weight: 700;
  text-decoration: none;
  margin-left: 4px;
}

.register-link-a:hover {
  text-decoration: underline;
}
</style>
