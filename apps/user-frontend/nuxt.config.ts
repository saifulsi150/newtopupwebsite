const cleanAdminDomain = (process.env.ADMIN_DOMAIN || "admin.ffuid.shop").trim().toLowerCase().replace(/^https?:\/\//, "").replace(/\/$/, "");
const cleanAppDomain = (process.env.APP_DOMAIN || "tast.ffuid.shop").trim().toLowerCase().replace(/^https?:\/\//, "").replace(/\/$/, "");

function resolveAllowedHosts() {
  const toDomain = (value: string) => value.trim().toLowerCase().replace(/^https?:\/\//, "").replace(/:\d+$/, "").replace(/\/$/, "");
  const toApex = (value: string) => {
    const domain = toDomain(value);
    const parts = domain.split(".").filter(Boolean);
    if (parts.length < 2) return "";
    return `${parts[parts.length - 2]}.${parts[parts.length - 1]}`;
  };

  const envHosts = String(process.env.NUXT_ALLOWED_HOSTS || "")
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);

  const appApex = toApex(cleanAppDomain);
  const adminApex = toApex(cleanAdminDomain);
 
  return Array.from(new Set([
    cleanAppDomain,
    cleanAdminDomain,
    appApex,
    adminApex,
    appApex ? `.${appApex}` : "",
    adminApex ? `.${adminApex}` : "",
    "ffuid.shop",
    ".ffuid.shop",
    "localhost",
    "127.0.0.1",
    ...envHosts.map((item) => toDomain(item))
  ].filter(Boolean)));
}

const localhostServiceWorkerResetScript = `(function(){
  try {
    var host = window.location.hostname;
    var isLocal = host === 'localhost' || host === '127.0.0.1';
    var isProdApp = host === 'ffuid.shop' || host === 'www.ffuid.shop';
    var shouldReset = isLocal || isProdApp;
    var resetKey = 'ffuid-sw-reset-v3';
    if (!shouldReset || !('serviceWorker' in navigator)) return;
    if (window.localStorage && localStorage.getItem(resetKey) === 'done') return;
    navigator.serviceWorker.getRegistrations()
      .then(function(registrations){
        return Promise.all(registrations.map(function(registration){ return registration.unregister(); }));
      })
      .catch(function(){ return undefined; });
    if ('caches' in window) {
      caches.keys()
        .then(function(keys){ return Promise.all(keys.map(function(key){ return caches.delete(key); })); })
        .catch(function(){ return undefined; });
    }
    if (window.localStorage) {
      localStorage.setItem(resetKey, 'done');
    }
  } catch (_) {}
})();`;

export default defineNuxtConfig({
  ssr: true,
  compatibilityDate: "2026-07-29",
  telemetry: false,
  devtools: { enabled: false },
  modules: ["@nuxtjs/tailwindcss"],
  css: ["~/assets/css/tailwind.css"],
  app: {
    baseURL: process.env.NUXT_APP_BASE_URL || "/",
    pageTransition: false,
    layoutTransition: false,
    head: {
      meta: [
        { name: "mobile-web-app-capable", content: "yes" },
        { name: "apple-mobile-web-app-capable", content: "yes" }
      ],
      script: [
        {
          key: "localhost-sw-reset",
          innerHTML: localhostServiceWorkerResetScript
        }
      ],
      link: [
        { rel: "manifest", href: "/api/manifest.webmanifest" },
        { rel: "preconnect", href: `https://${cleanAdminDomain}` },
        { rel: "dns-prefetch", href: `//${cleanAdminDomain}` },
        { rel: "preconnect", href: "https://wa.me" },
        { rel: "dns-prefetch", href: "//wa.me" }
      ]
    }
  },
  runtimeConfig: {
    mysqlHost: process.env.MYSQL_HOST || "127.0.0.1",
    mysqlPort: Number(process.env.MYSQL_PORT || 3306),
    mysqlUser: process.env.MYSQL_USER || "topup_user_1091",
    mysqlPassword: process.env.MYSQL_PASSWORD || "88bf20d8993d4b59!aA1",
    mysqlDatabase: process.env.MYSQL_DATABASE || "topup_db_tast_ffuid",
    redisUrl: process.env.REDIS_URL || "redis://127.0.0.1:6379",
    googleClientId: process.env.GOOGLE_CLIENT_ID || "",
    googleClientSecret: process.env.GOOGLE_CLIENT_SECRET || "",
    googleRedirectUri: process.env.GOOGLE_REDIRECT_URI || "",
    uddoktapayApiKey: process.env.UDDOKTAPAY_API_KEY || "",
    uddoktapayBaseUrl: process.env.UDDOKTAPAY_BASE_URL || "",
    public: {
      siteName: process.env.NUXT_PUBLIC_SITE_NAME || "FFUID",
      supportUrl: process.env.NUXT_PUBLIC_SUPPORT_URL || "https://t.me/admimapp",
      googleClientId: process.env.NUXT_PUBLIC_GOOGLE_CLIENT_ID || "",
      googleRedirectUri: process.env.NUXT_PUBLIC_GOOGLE_REDIRECT_URI || "/api/auth/google/callback",
      adminAssetBase: process.env.NUXT_PUBLIC_ADMIN_ASSET_BASE || `https://${cleanAdminDomain}`
    }
  },
  nitro: {
    compressPublicAssets: true
  },
  devServer: {
    host: "0.0.0.0",
    port: 3000
  },
  experimental: {
    payloadExtraction: true,
    renderJsonPayloads: true,
    viewTransition: false,
    restoreState: false
  },
  vite: {
    server: {
      allowedHosts: true
    },
    build: {
      target: "es2022",
      cssCodeSplit: true
    }
  },
  routeRules: {
    "/api/login": { proxy: `https://${cleanAdminDomain}/api/gamevault/auth/login` },
    "/api/register": { proxy: `https://${cleanAdminDomain}/api/gamevault/auth/register` },
    "/api/**": { cors: true },
    "/_nuxt/**": {
      headers: {
        "cache-control": "public, max-age=31536000, immutable"
      }
    }
  }
});
