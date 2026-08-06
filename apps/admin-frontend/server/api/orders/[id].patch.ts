import { createError, defineEventHandler, getRouterParam, readBody } from 'h3';
import { timestamp, readStore, writeStore } from '../../utils/mock-db';

const allowed = new Set(['pending', 'looking', 'running', 'complete', 'cancel']);

export default defineEventHandler(async (event) => {
  const id = Number(getRouterParam(event, 'id'));
  const body = await readBody<{ status?: string; delivery_message?: string }>(event);

  const store = readStore();
  const idx = store.orders.findIndex((o) => o.id === id);
  if (idx === -1) throw createError({ statusCode: 404, statusMessage: 'Order not found.' });

  const order = store.orders[idx];
  if (body?.status !== undefined) {
    const status = String(body.status || '').trim().toLowerCase();
    if (status && allowed.has(status)) order.status = status;
  }
  if (body?.delivery_message !== undefined) {
    order.delivery_message = String(body.delivery_message || '');
  }
  order.updated_at = timestamp();

  writeStore(store);
  return { success: true, order };
});
