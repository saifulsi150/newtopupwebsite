import { createError, defineEventHandler, readBody } from 'h3';
import { nextId, readStore, timestamp, writeStore } from '../../utils/mock-db';

export default defineEventHandler(async (event) => {
  const body = await readBody<{ user_id?: number; amount?: number; note?: string }>(event);
  const userId = Number(body?.user_id || 0);
  const amount = Number(body?.amount || 0);

  if (!userId) throw createError({ statusCode: 422, statusMessage: 'user_id is required.' });
  if (!Number.isFinite(amount) || amount <= 0) throw createError({ statusCode: 422, statusMessage: 'amount must be greater than 0.' });

  const store = readStore();
  const user = store.users.find((u) => u.id === userId);
  if (!user) throw createError({ statusCode: 404, statusMessage: 'User not found.' });

  user.balance = Number(user.balance || 0) + amount;

  store.transactions.unshift({
    id: nextId(store.transactions),
    user_id: user.id,
    user_name: user.name,
    user_email: user.email,
    amount,
    method: 'manual',
    type: String(body?.note || 'balance add').trim() || 'balance add',
    status: 'completed',
    invoice_id: `INV-${Date.now()}`,
    created_at: timestamp()
  });

  writeStore(store);
  return { success: true, user };
});
