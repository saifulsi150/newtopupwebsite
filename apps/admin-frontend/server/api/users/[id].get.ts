import { createError, defineEventHandler, getRouterParam } from 'h3';
import { readStore } from '../../utils/mock-db';

export default defineEventHandler((event) => {
  const id = Number(getRouterParam(event, 'id'));
  const store = readStore();
  const user = store.users.find((u) => u.id === id);
  if (!user) throw createError({ statusCode: 404, statusMessage: 'User not found.' });

  const transactions = store.transactions
    .filter((tx) => Number(tx.user_id) === id)
    .sort((a, b) => b.id - a.id);

  return { user, transactions };
});
