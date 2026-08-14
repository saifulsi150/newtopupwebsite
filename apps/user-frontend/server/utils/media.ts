const ADMIN_DOMAIN = String(process.env.ADMIN_DOMAIN || 'admin.ffuid.shop').trim().toLowerCase();

function resolveAdminAssetBase() {
  const explicit = String(process.env.NUXT_PUBLIC_ADMIN_ASSET_BASE || '').trim();
  if (explicit) {
    return explicit.replace(/\/+$/, '');
  }

  if (process.env.NODE_ENV === 'production') {
    return `https://${ADMIN_DOMAIN}`;
  }

  return 'http://127.0.0.1:8000';
}

const ADMIN_ASSET_BASE = resolveAdminAssetBase();

function rewriteLegacyAdminUploadUrl(raw: string): string {
  if (!/^https?:\/\//i.test(raw)) return raw;

  try {
    const parsed = new URL(raw);
    const host = String(parsed.hostname || '').toLowerCase();
    if (host === ADMIN_DOMAIN && parsed.pathname.startsWith('/uploads/')) {
      return `${ADMIN_ASSET_BASE}${parsed.pathname}`;
    }
  } catch {
    return raw;
  }

  return raw;
}

export function normalizePublicImageUrl(input: unknown): string {
  const raw = String(input || '').trim();
  if (!raw) return '';
  if (raw.includes('..')) return '';
  if (/^(javascript|data|vbscript):/i.test(raw)) {
    return '';
  }

  const config = useRuntimeConfig();
  let assetBase = String(config.public.adminAssetBase || '').trim().replace(/\/+$/, '');
  if (!assetBase) {
    assetBase = 'https://admin.vottopup.com';
  }

  // Helper to rewrite legacy/fallback URLs
  const rewriteUrls = (url: string) => {
    return url.replace(/https?:\/\/(admin\.ffuid\.shop|admin\.vottopup\.com|127\.0.0.1:8000)/gi, assetBase);
  };

  if (/^https?:\/\//i.test(raw)) {
    return rewriteUrls(raw);
  }
  if (raw.startsWith('//')) return `https:${raw}`;

  const safePath = raw.replace(/^\.?\//, '');
  if (raw.startsWith('/')) return `${assetBase}${raw}`;
  
  if (!safePath.startsWith('uploads/') && !safePath.startsWith('storage/')) {
    return `${assetBase}/storage/${safePath}`;
  }
  
  return `${assetBase}/${safePath}`;
}
