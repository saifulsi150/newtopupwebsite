import { defineEventHandler, getQuery } from 'h3';
import { readStore } from '../utils/mock-db';

export default defineEventHandler((event) => {
  const query = getQuery(event);
  const page = Math.max(1, Number(query.page || 1));
  const limit = Math.max(1, Number(query.limit || 20));
  const search = String(query.search || '').trim().toLowerCase();

  let list = [...readStore().transactions].sort((a, b) => b.id - a.id);
  if (search) {
    list = list.filter((tx) =>
      [tx.user_name, tx.user_email, tx.invoice_id, tx.method, tx.type]
        .join(' ')
        .toLowerCase()
        .includes(search)
    );
  }

  const total = list.length;
  const start = (page - 1) * limit;
  const transactions = list.slice(start, start + limit);
  const totalAmount = list.reduce((sum, tx) => sum + Number(tx.amount || 0), 0);

  return { transactions, total, totalAmount };
});
