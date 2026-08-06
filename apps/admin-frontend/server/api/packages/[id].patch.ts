import { createError, defineEventHandler, getRouterParam, readBody } from 'h3';
import { timestamp, readStore, writeStore } from '../../utils/mock-db';

export default defineEventHandler(async (event) => {
  const id = Number(getRouterParam(event, 'id'));
  const body = await readBody<any>(event);

  const store = readStore();
  const idx = store.packages.findIndex((p) => p.id === id);
  if (idx === -1) throw createError({ statusCode: 404, statusMessage: 'Package not found.' });

  const current = store.packages[idx];

  if (body?.product_id !== undefined) {
    const productId = Number(body.product_id || 0);
    const product = store.products.find((p) => p.id === productId);
    if (!product) throw createError({ statusCode: 404, statusMessage: 'Product not found.' });
    current.product_id = product.id;
    current.product_title = product.title;
    current.product_slug = product.slug;
  }

  if (body?.name !== undefined) current.name = String(body.name || '').trim() || current.name;
  if (body?.buy_price !== undefined) {
    current.buy_price = body.buy_price === null || body.buy_price === '' ? null : Number(body.buy_price);
  }
  if (body?.sell_price !== undefined) current.sell_price = Number(body.sell_price || 0);
  if (body?.slot !== undefined) current.slot = Number(body.slot || 0);
  if (body?.is_active !== undefined) current.is_active = Number(body.is_active || 0) === 1 ? 1 : 0;
  if (body?.auto_forward_enabled !== undefined) current.auto_forward_enabled = Number(body.auto_forward_enabled || 0) === 1 ? 1 : 0;
  if (body?.auto_forward_api_name !== undefined) current.auto_forward_api_name = String(body.auto_forward_api_name || '');
  current.updated_at = timestamp();

  writeStore(store);
  return { success: true, package: current };
});
