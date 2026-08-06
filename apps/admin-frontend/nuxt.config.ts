export default defineNuxtConfig({
  ssr: true,
  compatibilityDate: '2026-07-29',
  telemetry: false,
  devtools: { enabled: false },
  modules: ['@nuxtjs/tailwindcss'],
  css: ['~/assets/css/main.css'],
  app: {
    head: {
      title: 'Admin Panel',
      meta: [{ name: 'robots', content: 'noindex, nofollow' }]
    }
  },
  runtimeConfig: {
    mysqlHost: process.env.MYSQL_HOST || '127.0.0.1',
    mysqlPort: Number(process.env.MYSQL_PORT || 3306),
    mysqlUser: process.env.MYSQL_USER || 'ffuid',
    mysqlPassword: process.env.MYSQL_PASSWORD || 'ffuid_pass',
    mysqlDatabase: process.env.MYSQL_DATABASE || 'topup_db_tast_ffuid',
    adminSecret: process.env.ADMIN_SECRET || 'change_this_secret_123',
    deployAgentUrl: process.env.DEPLOY_AGENT_URL || 'http://deploy-agent:8099',
    deployWebhookToken: process.env.DEPLOY_WEBHOOK_TOKEN || '',
    adminEmail: process.env.ADMIN_EMAIL || 'admin@ghostbazar.online',
    adminPassword: process.env.ADMIN_PASSWORD || 'Admin@12345',
    adminName: process.env.ADMIN_NAME || 'GhostBazar Admin',
    public: {
      siteName: process.env.NUXT_PUBLIC_SITE_NAME || 'GhostBazar Admin'
    }
  },
  nitro: {
    preset: 'node-server',
    compressPublicAssets: true
  },
  devServer: {
    host: '0.0.0.0',
    port: 3001
  },
  routeRules: {
    '/api/**': { cors: false }
  }
});
