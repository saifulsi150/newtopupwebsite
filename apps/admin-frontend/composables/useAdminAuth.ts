export const useAdminAuth = () => {
  const cookie = useCookie<string | null>('admin_token', {
    sameSite: 'lax',
    maxAge: 60 * 60 * 8, // 8 hours
    httpOnly: false
  });

  const isLoggedIn = computed(() => {
    if (!cookie.value || typeof cookie.value !== 'string') return false;
    try {
      const data = JSON.parse(atob(cookie.value));
      return !!data?.admin && !!data?.email;
    } catch {
      return false;
    }
  });

  const adminInfo = computed(() => {
    if (!cookie.value) return null;
    try {
      return JSON.parse(atob(cookie.value));
    } catch {
      return null;
    }
  });

  function setAdmin(payload: { email: string; name: string; id: number }) {
    cookie.value = btoa(JSON.stringify({ admin: true, ...payload }));
  }

  function logout() {
    cookie.value = null;
  }

  return { isLoggedIn, adminInfo, setAdmin, logout };
};
