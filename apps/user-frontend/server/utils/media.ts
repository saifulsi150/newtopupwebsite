const ADMIN_DOMAIN = String(process.env.ADMIN_DOMAIN || 'admin.ffuid.shop').trim().toLowerCase();

function resolveAdminAssetBase() {
  const explicit = String(process.env.NUXT_PUBLIC_ADMIN_ASSET_BASE || '').trim();
  if (explicit) {
    return explicit.replace(/\/+$/, '');
  }

  if (process.env.NODE_ENV === 'production') {
    return `https://${ADMIN_DOMAIN}`;
  }

  return 'http://127.0.0.1:3001';
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

  if (/^https?:\/\//i.test(raw)) return rewriteLegacyAdminUploadUrl(raw);
  if (raw.startsWith('//')) return `https:${raw}`;

  const safePath = raw.replace(/^\.?\//, '');
  if (raw.startsWith('/')) return `${ADMIN_ASSET_BASE}${raw}`;
  return `${ADMIN_ASSET_BASE}/${safePath}`;
}
