import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { normalizePublicImageUrl } from './media';

type HomeProduct = {
  id: number;
  title: string;
  slug: string;
  image_url: string;
  price_from: number;
  category_id: number;
  category_title: string;
};

const ADMIN_DATA_DIR = join(process.cwd(), '..', 'admin-frontend', '.data');
const MOCK_DB_PATH = join(ADMIN_DATA_DIR, 'mock-db.json');
const CATEGORIES_PATH = join(ADMIN_DATA_DIR, 'categories.json');

function readJson<T>(path: string, fallback: T): T {
  if (!existsSync(path)) return fallback;
  try {
    const parsed = JSON.parse(readFileSync(path, 'utf8')) as T;
    return parsed;
  } catch {
    return fallback;
  }
}

export function readAdminMockProducts(): HomeProduct[] {
  const mock = readJson<any>(MOCK_DB_PATH, { products: [], packages: [] });
  const products = Array.isArray(mock?.products) ? mock.products : [];
  const packages = Array.isArray(mock?.packages) ? mock.packages : [];

  const sorted = products
    .filter((p: any) => Number(p?.status ?? 0) === 1)
    .map((p: any) => {
      const productId = Number(p?.id || 0);
      const packagePrices = packages
        .filter((pkg: any) => Number(pkg?.product_id || 0) === productId && Number(pkg?.is_active ?? 0) === 1)
        .map((pkg: any) => Number(pkg?.sell_price || 0))
        .filter((price: number) => Number.isFinite(price) && price > 0);

      return {
        id: productId,
        title: String(p?.title || ''),
        slug: String(p?.slug || ''),
        image_url: normalizePublicImageUrl(p?.image),
        price_from: packagePrices.length ? Math.min(...packagePrices) : 0,
        category_id: Number(p?.category_id || 0),
        category_title: String(p?.category_title || ''),
        slot: Number(p?.slot || 0)
      };
    })
    .filter((item: any) => Number(item.id || 0) > 0 && String(item.slug || '').trim())
    .sort((a: any, b: any) => Number(a?.slot || 0) - Number(b?.slot || 0) || b.id - a.id);

  return sorted.map(({ slot, ...rest }) => rest as HomeProduct);
}

export function readAdminMockCategories(): Array<{ id: number; name: string; slot: number }> {
  const list = readJson<any[]>(CATEGORIES_PATH, []);

  return list
    .map((item: any) => ({
      id: Number(item?.id || 0),
      name: String(item?.title || '').trim(),
      slot: Number(item?.slot || 0),
      status: Number(item?.status ?? 0)
    }))
    .filter((item: any) => item.id > 0 && item.name && item.status === 1)
    .sort((a, b) => a.slot - b.slot || b.id - a.id)
    .map(({ id, name, slot }) => ({ id, name, slot }));
}