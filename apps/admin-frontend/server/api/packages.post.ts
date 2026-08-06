import { createError, defineEventHandler, readBody } from 'h3';
import { nextId, readStore, timestamp, writeStore } from '../utils/mock-db';

export default defineEventHandler(async (event) => {
  const body = await readBody<any>(event);
  const productId = Number(body?.product_id || 0);
  const name = String(body?.name || '').trim();
  if (!productId) throw createError({ statusCode: 422, statusMessage: 'product_id is required.' });
  if (!name) throw createError({ statusCode: 422, statusMessage: 'name is required.' });

  const store = readStore();
  const product = store.products.find((p) => p.id === productId);
  if (!product) throw createError({ statusCode: 404, statusMessage: 'Product not found.' });

  const pkg = {
    id: nextId(store.packages),
    product_id: productId,
    product_title: product.title,
    product_slug: product.slug,
    name,
    buy_price: body?.buy_price === null || body?.buy_price === '' || body?.buy_price === undefined ? null : Number(body.buy_price),
    sell_price: Number(body?.sell_price || 0),
    slot: Number(body?.slot || store.packages.length + 1),
    is_active: Number(body?.is_active || 0) === 1 ? 1 : 0,
    auto_forward_enabled: Number(body?.auto_forward_enabled || 0) === 1 ? 1 : 0,
    auto_forward_api_name: String(body?.auto_forward_api_name || ''),
    updated_at: timestamp()
  };

  store.packages.push(pkg);
  writeStore(store);
  return { success: true, package: pkg };
});
