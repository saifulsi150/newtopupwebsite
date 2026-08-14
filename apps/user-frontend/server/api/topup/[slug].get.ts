import { existsSync, readFileSync } from "node:fs";
import { join } from "node:path";
import { createError } from "h3";
import { useDb } from "../../utils/db";
import { normalizePublicImageUrl } from "../../utils/media";

type ProductRow = {
  id: number;
  title: string;
  slug: string;
  image_url: string;
  subtitle: string | null;
  input_label: string;
  uid_checker: number;
  uid_checker_api: string;
  dynamic_fields: Array<{ label: string; key: string }>;
};

type PackageRow = {
  id: number;
  title: string;
  price: number;
};

type TopupPayload = {
  product: ProductRow;
  packages: PackageRow[];
};

const ADMIN_DATA_DIR = join(process.cwd(), "..", "admin-frontend", ".data");
const MOCK_DB_PATH = join(ADMIN_DATA_DIR, "mock-db.json");

function normalizeFields(input: any): Array<{ label: string; key: string }> {
  const list = Array.isArray(input) ? input : [];
  return list
    .map((item: any) => ({
      label: String(item?.label || "").trim(),
      key: String(item?.key || "").trim()
    }))
    .filter((item: { label: string; key: string }) => item.label && item.key);
}

function normalizeProduct(item: any): ProductRow {
  return {
    id: Number(item?.id || 0),
    title: String(item?.title || item?.name || ""),
    slug: String(item?.slug || ""),
    image_url: normalizePublicImageUrl(item?.image || item?.image_url),
    subtitle: item?.subtitle ? String(item.subtitle) : null,
    input_label: String(item?.input_label || item?.input || "Player ID"),
    uid_checker: Number(item?.uid_checker || 0) === 1 ? 1 : 0,
    uid_checker_api: String(item?.uid_checker_api || ""),
    dynamic_fields: normalizeFields(item?.dynamic_fields)
  };
}

function normalizePackage(item: any): PackageRow {
  const fallbackPrice = Number(item?.price || 0);
  const sellPrice = Number(item?.sell_price || 0);

  return {
    id: Number(item?.id || 0),
    title: String(item?.name || item?.title || "").trim(),
    price: Number.isFinite(sellPrice) && sellPrice > 0 ? sellPrice : fallbackPrice
  };
}

async function withTimeout<T>(promise: Promise<T>, timeoutMs: number): Promise<T> {
  return await Promise.race([
    promise,
    new Promise<T>((_, reject) => setTimeout(() => reject(new Error("db-timeout")), timeoutMs))
  ]);
}

function readMockPayload(slugOrId: string): TopupPayload | null {
  if (!existsSync(MOCK_DB_PATH)) return null;

  try {
    const parsed = JSON.parse(readFileSync(MOCK_DB_PATH, "utf8")) as any;
    const products = Array.isArray(parsed?.products) ? parsed.products : [];
    const packages = Array.isArray(parsed?.packages) ? parsed.packages : [];

    const numericId = Number(slugOrId);
    const mockProduct = products.find((item: any) => {
      const slug = String(item?.slug || "").trim();
      const id = Number(item?.id || 0);
      const status = Number(item?.status ?? 1);
      if (status !== 1) return false;
      if (slug && slug === slugOrId) return true;
      if (Number.isFinite(numericId) && numericId > 0 && id === numericId) return true;
      return false;
    });

    if (!mockProduct) return null;

    const productId = Number(mockProduct?.id || 0);
    const mockPackages = packages
      .filter((item: any) => Number(item?.product_id || 0) === productId && Number(item?.is_active ?? 1) === 1)
      .map((item: any) => normalizePackage(item))
      .filter((item: PackageRow) => item.id > 0 && item.title && item.price > 0)
      .sort((a: PackageRow, b: PackageRow) => a.id - b.id);

    return {
      product: normalizeProduct(mockProduct),
      packages: mockPackages
    };
  } catch {
    return null;
  }
}

export default defineEventHandler(async (event) => {
  const rawSlug = String(getRouterParam(event, "slug") || "").trim();
  if (!rawSlug) {
    throw createError({ statusCode: 400, statusMessage: "Invalid slug" });
  }

  const db = useDb();

  try {
    const [productRows] = await withTimeout(db.query(
      `SELECT
        p.id,
        p.title,
        p.slug,
        p.image,
        COALESCE(p.content, '') AS subtitle,
        COALESCE(p.input, 'Player ID') AS input_label,
        COALESCE(p.uid_checker, 0) AS uid_checker,
        '' AS uid_checker_api,
        '[]' AS dynamic_fields
      FROM products p
      WHERE (
        (p.slug IS NOT NULL AND p.slug = ?)
        OR (? REGEXP '^[0-9]+$' AND p.id = CAST(? AS UNSIGNED))
      )
      AND COALESCE(p.status, 1) = 1
      ORDER BY p.id DESC
      LIMIT 1`,
      [rawSlug, rawSlug, rawSlug]
    ), 1000);

    const dbProduct = Array.isArray(productRows) && productRows.length > 0 ? productRows[0] : null;

    if (!dbProduct) {
      throw createError({ statusCode: 404, statusMessage: "Product not found" });
    }

    const product = normalizeProduct(dbProduct);
    const [packageRows] = await withTimeout(db.query(
      `SELECT
        pp.id,
        pp.name AS title,
        COALESCE(pp.price, 0) AS price,
        0 AS slot
      FROM product_packages pp
      WHERE pp.product_id = ?
      AND COALESCE(pp.is_active, 1) = 1
      ORDER BY pp.id ASC`,
      [product.id]
    ), 1000);

    const packages = (Array.isArray(packageRows) ? packageRows : [])
      .map((item: any) => normalizePackage(item))
      .filter((item: PackageRow) => item.id > 0 && item.title && item.price > 0);

    if (!packages.length) {
      throw createError({ statusCode: 404, statusMessage: "Package not found" });
    }

    return {
      product,
      packages
    };
  } catch (error: any) {
    console.error('Error loading topup data:', error);
    if (Number(error?.statusCode || 0) >= 400) {
      throw error;
    }

    throw createError({ statusCode: 500, statusMessage: "Unable to load topup data" });
  }
});
