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
  const directAssetPattern = /^\/?(products|banners|logos|icons)\/[a-zA-Z0-9._-]+\.(jpg|jpeg|png|webp|gif)$/i;
  const uploadAssetPattern = /^\/?uploads\/(products|banners|logos|icons)\/[a-zA-Z0-9._-]+\.(jpg|jpeg|png|webp|gif)$/i;
  if (directAssetPattern.test(raw) === false && uploadAssetPattern.test(raw) === false && /^https?:\/\//i.test(raw) === false) {
    return '';
  }
  if (/^https?:\/\//i.test(raw)) return raw;
  if (raw.startsWith('/')) return `${ADMIN_ASSET_BASE}${raw}`;
  return `${ADMIN_ASSET_BASE}/${raw}`;
}
