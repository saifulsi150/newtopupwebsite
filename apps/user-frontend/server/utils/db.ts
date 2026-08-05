import mysql from "mysql2/promise";

let pool: mysql.Pool | null = null;

export const useDb = () => {
  if (!pool) {
    const config = useRuntimeConfig();
    const host = String(config.mysqlHost || "").trim() === "mysql"
      ? "127.0.0.1"
      : config.mysqlHost;
    const user = String(config.mysqlUser || "").trim() === "ffuid"
      ? "topup_user_1091"
      : config.mysqlUser;
    const password = String(config.mysqlPassword || "").trim() === "ffuid_pass"
      ? "88bf20d8993d4b59!aA1"
      : config.mysqlPassword;
    const database = String(config.mysqlDatabase || "").trim() === "ffuid"
      ? "topup_db_tast_ffuid"
      : config.mysqlDatabase;
    pool = mysql.createPool({
      host,
      port: config.mysqlPort,
      user,
      password,
      database,
      connectTimeout: 3000,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });
  }

  return pool;
};
