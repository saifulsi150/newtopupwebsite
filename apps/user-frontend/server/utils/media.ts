const ADMIN_ASSET_BASE = (
  process.env.NUXT_PUBLIC_ADMIN_ASSET_BASE ||
  (process.env.NODE_ENV === 'production'
    ? `https://${process.env.ADMIN_DOMAIN || 'admin.ffuid.shop'}`
    : 'http://127.0.0.1:3001')
).replace(/\/+$/, '');

export function normalizePublicImageUrl(input: unknown): string {
  const raw = String(input || '').trim();
  if (!raw) return '';
  if (raw.includes('..')) return '';
  if (/^(javascript|data|vbscript):/i.test(raw)) {
    return '';
  }

  if (/^https?:\/\//i.test(raw)) return raw;
  if (raw.startsWith('//')) return `https:${raw}`;

  const safePath = raw.replace(/^\.?\//, '');
  if (raw.startsWith('/')) return `${ADMIN_ASSET_BASE}${raw}`;
  return `${ADMIN_ASSET_BASE}/${safePath}`;
}
