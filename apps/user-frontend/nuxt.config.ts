function resolveAllowedHosts() {
  const envHosts = String(process.env.NUXT_ALLOWED_HOSTS || "")
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);

  return Array.from(new Set([
    process.env.APP_DOMAIN || "tast.ffuid.shop",
    process.env.ADMIN_DOMAIN || "admin.ffuid.shop",
    "localhost",
    "127.0.0.1",
    ...envHosts
  ]));
}

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
      link: [
        { rel: "manifest", href: "/api/manifest.webmanifest" },
        { rel: "preconnect", href: `https://${process.env.ADMIN_DOMAIN || "admin.ffuid.shop"}` },
        { rel: "dns-prefetch", href: `//${process.env.ADMIN_DOMAIN || "admin.ffuid.shop"}` },
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
      adminAssetBase: process.env.NUXT_PUBLIC_ADMIN_ASSET_BASE || (process.env.NODE_ENV === 'production'
        ? `https://${process.env.ADMIN_DOMAIN || "admin.ffuid.shop"}`
        : 'http://127.0.0.1:3001')
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
      allowedHosts: resolveAllowedHosts()
    },
    build: {
      target: "es2022",
      cssCodeSplit: true
    }
  },
  routeRules: {
    "/api/**": { cors: true },
    "/_nuxt/**": {
      headers: {
        "cache-control": "public, max-age=31536000, immutable"
      }
    }
  }
});
