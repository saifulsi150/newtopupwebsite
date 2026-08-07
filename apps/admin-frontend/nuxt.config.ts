function resolveAllowedHosts() {
  const toDomain = (value: string) => value.trim().toLowerCase().replace(/^https?:\/\//, '').replace(/:\d+$/, '').replace(/\/$/, '');
  const toApex = (value: string) => {
    const domain = toDomain(value);
    const parts = domain.split('.').filter(Boolean);
    if (parts.length < 2) return '';
    return `${parts[parts.length - 2]}.${parts[parts.length - 1]}`;
  };

  const envHosts = String(process.env.NUXT_ALLOWED_HOSTS || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);

  const appDomain = process.env.APP_DOMAIN || 'tast.ffuid.shop';
  const adminDomain = process.env.ADMIN_DOMAIN || 'admin.ffuid.shop';
  const appApex = toApex(appDomain);
  const adminApex = toApex(adminDomain);

  return Array.from(new Set([
    toDomain(appDomain),
    toDomain(adminDomain),
    appApex,
    adminApex,
    appApex ? `.${appApex}` : '',
    adminApex ? `.${adminApex}` : '',
    'ffuid.shop',
    '.ffuid.shop',
    'localhost',
    '127.0.0.1',
    ...envHosts.map((item) => toDomain(item)),
  ].filter(Boolean)));
}

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
  vite: {
    server: {
      allowedHosts: resolveAllowedHosts()
    }
  },
  routeRules: {
    '/api/**': { cors: false }
  }
});
