import mysql from 'mysql2/promise';

let pool: mysql.Pool | null = null;

export const useDb = () => {
  if (!pool) {
    const config = useRuntimeConfig();
    pool = mysql.createPool({
      host: String(config.mysqlHost || '127.0.0.1'),
      port: Number(config.mysqlPort || 3306),
      user: String(config.mysqlUser || ''),
      password: String(config.mysqlPassword || ''),
      database: String(config.mysqlDatabase || ''),
      connectTimeout: 5000,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });
  }
  return pool;
};
