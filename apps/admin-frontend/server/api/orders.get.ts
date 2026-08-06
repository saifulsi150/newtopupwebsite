import { defineEventHandler, getQuery } from 'h3';
import { readStore } from '../utils/mock-db';

function isToday(value: string) {
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return false;
  const now = new Date();
  return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate();
}

export default defineEventHandler((event) => {
  const q = getQuery(event);
  const page = Math.max(1, Number(q.page || 1));
  const limit = Math.max(1, Number(q.limit || 15));

  const status = String(q.status || '').trim().toLowerCase();
  const orderId = String(q.order_id || '').trim();
  const userId = String(q.user_id || '').trim();
  const playerId = String(q.player_id || '').trim().toLowerCase();
  const code = String(q.code || '').trim().toLowerCase();
  const search = String(q.search || '').trim().toLowerCase();

  let orders = [...readStore().orders].sort((a, b) => b.id - a.id);

  if (status) {
    if (status === 'pending+looking') {
      orders = orders.filter((o) => o.status === 'pending' || o.status === 'looking');
    } else if (status === 'today-completed') {
      orders = orders.filter((o) => o.status === 'complete' && isToday(o.updated_at));
    } else {
      orders = orders.filter((o) => o.status === status);
    }
  }

  if (orderId) orders = orders.filter((o) => String(o.id) === orderId);
  if (userId) orders = orders.filter((o) => String(o.user_id) === userId);
  if (playerId) orders = orders.filter((o) => String(o.player_id || '').toLowerCase().includes(playerId));
  if (code) orders = orders.filter((o) => String(o.code || '').toLowerCase().includes(code));
  if (search) {
    orders = orders.filter((o) =>
      [o.user_name, o.user_email, o.player_id, o.code, o.package_title, o.product_title]
        .join(' ')
        .toLowerCase()
        .includes(search)
    );
  }

  const total = orders.length;
  const start = (page - 1) * limit;

  return {
    orders: orders.slice(start, start + limit),
    total
  };
});
