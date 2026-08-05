import { useDb } from "../utils/db";
import { normalizePublicImageUrl } from "../utils/media";

type Product = {
  id: number;
  title: string;
  slug: string;
  image_url: string;
  price_from: number;
  category_id: number;
  category_title: string;
};

async function withTimeout<T>(promise: Promise<T>, timeoutMs: number): Promise<T> {
  return await Promise.race([
    promise,
    new Promise<T>((_, reject) => setTimeout(() => reject(new Error("db-timeout")), timeoutMs))
  ]);
}

export default defineEventHandler(async () => {
  try {
    const db = useDb();
    const [rows] = await withTimeout(db.query(
     `SELECT
        p.id,
        p.title,
        p.slug,
        p.image,
        p.categorie_id,
        c.title AS category_title,
        COALESCE(MIN(CASE WHEN pp.sell_price IS NOT NULL THEN pp.sell_price ELSE pp.price END), 0) AS price_from
      FROM products p
      LEFT JOIN categories c ON c.id = p.categorie_id
      LEFT JOIN product_packages pp ON pp.product_id = p.id AND COALESCE(pp.is_active, 1) = 1
      WHERE COALESCE(p.status, 1) = 1
      GROUP BY p.id
      ORDER BY CAST(COALESCE(p.slot, '0') AS DECIMAL(10,2)) ASC, p.id DESC`,
     ), 320);

    const dbRows = (rows as any[]).map((item: any) => ({
     id: Number(item.id || 0),
     title: String(item.title || ''),
     slug: String(item.slug || ''),
     image_url: normalizePublicImageUrl(item.image),
     price_from: Number(item.price_from || 0),
     category_id: Number(item.categorie_id || 0),
     category_title: String(item.category_title || '')
    })).filter((item: Product) => item.id > 0 && item.slug);

    const payload = { products: dbRows };
    return payload;
  } catch {
    return {
      products: []
    };
  }
});
