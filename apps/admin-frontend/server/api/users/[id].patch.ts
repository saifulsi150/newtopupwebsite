import { createError, defineEventHandler, getRouterParam, readBody } from 'h3';
import { readStore, writeStore } from '../../utils/mock-db';

export default defineEventHandler(async (event) => {
  const id = Number(getRouterParam(event, 'id'));
  const body = await readBody<{ balance?: number; status?: number }>(event);

  const store = readStore();
  const idx = store.users.findIndex((u) => u.id === id);
  if (idx === -1) throw createError({ statusCode: 404, statusMessage: 'User not found.' });

  if (body?.balance !== undefined) store.users[idx].balance = Number(body.balance || 0);
  if (body?.status !== undefined) store.users[idx].status = Number(body.status || 0) === 1 ? 1 : 0;

  writeStore(store);
  return { success: true, user: store.users[idx] };
});
