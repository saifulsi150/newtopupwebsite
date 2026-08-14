import { defineEventHandler } from 'h3';
import { useDb } from '../utils/db';
import { readAdminMockCategories } from '../utils/admin-mock';

export default defineEventHandler(async () => {
  try {
    const db = useDb();
    const [rows] = await db.query(
      `SELECT id, title AS name
       FROM categories
       WHERE COALESCE(status, 1) = 1
       ORDER BY CAST(COALESCE(slot, '0') AS DECIMAL(10,2)) ASC, id DESC`
    );

    const categories = (rows as any[]).map((item: any) => ({
      id: Number(item.id || 0),
      name: String(item.name || '').trim()
    })).filter((item) => item.id > 0 && item.name);

    return { categories };
  } catch {
    return { categories: [] };
  }
});
