export const useAuth = () => {
  const cookie = useCookie('rgb_user', {
    sameSite: 'lax',
    maxAge: 60 * 60 * 24 * 30
  });

  const parseUserCookie = (raw: unknown) => {
    if (!raw) return null;
    if (typeof raw === 'object') return raw;
    if (typeof raw !== 'string') return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  };

  const user = useState<any>('auth-user', () => parseUserCookie(cookie.value));

  const isLoggedIn = computed(() => {
    if (!user.value || typeof user.value !== 'object') return false;
    const numericId = Number(user.value.id ?? 0);
    const email = String(user.value.email ?? '').trim();
    const hasValidId = Number.isFinite(numericId) && numericId > 0;
    const hasValidEmail = email.length > 3 && email.includes('@');
    return hasValidId || hasValidEmail;
  });

  function setUser(payload: any) {
    user.value = payload;
    cookie.value = payload ? JSON.stringify(payload) : null;
  }

  function login(payload: any) {
    setUser(payload);
  }

  function logout() {
    setUser(null);
  }

  return {
    user,
    isLoggedIn,
    login,
    logout,
    setUser
  };
};
