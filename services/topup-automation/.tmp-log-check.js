const fs = require('fs');
const dotenv = require('dotenv');
dotenv.config();
if (fs.existsSync('.env.local')) {
  const local = dotenv.parse(fs.readFileSync('.env.local'));
  for (const k in local) process.env[k] = local[k];
}
const db = require('./app/config/database');
(async () => {
  try {
    const [rows] = await db.pool.execute(
      "SELECT id, type, message, order_reference, created_at FROM logs WHERE message LIKE ? OR message LIKE ? OR message LIKE ? OR message LIKE ? ORDER BY id DESC LIMIT 80",
      ['%result to website%', '%completion result%', '%Failed to send result to website%', '%Cancel webhook sent%']
    );
    for (const r of rows) {
      console.log(`${r.id}\t${r.type}\t${r.order_reference || '-'}\t${r.created_at}\t${r.message}`);
    }
    process.exit(0);
  } catch (e) {
    console.error('ERR', e.message);
    process.exit(1);
  }
})();
