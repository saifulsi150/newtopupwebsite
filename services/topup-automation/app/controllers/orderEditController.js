const db = require("../config/database");
const logger = require("../utils/logger");
const websiteWebhookController = require("./websiteWebhookController");

const ALLOWED_STATUS = new Set(["pending", "processing", "locked", "completed", "failed", "cancelled"]);

async function ensureOrderLockTable() {
    await db.pool.execute(`
        CREATE TABLE IF NOT EXISTS order_locks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_row_id INT NOT NULL,
            source_order_id VARCHAR(100) NOT NULL,
            lock_amount_threshold DECIMAL(16,2) NOT NULL DEFAULT 0,
            detected_balance_amount DECIMAL(16,2) NOT NULL DEFAULT 0,
            detected_product_amount DECIMAL(16,2) NOT NULL DEFAULT 0,
            delay_seconds INT NOT NULL DEFAULT 0,
            auto_action VARCHAR(30) NOT NULL DEFAULT 'manual',
            delivery_message TEXT NULL,
            execute_after DATETIME NULL,
            processed TINYINT(1) NOT NULL DEFAULT 0,
            processed_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_source_order_lock (source_order_id),
            INDEX idx_lock_due (processed, execute_after),
            INDEX idx_lock_order_row (order_row_id)
        )
    `);
}

function normalizeStatus(status) {
    const raw = String(status || "").trim().toLowerCase();
    if (["cancel", "canceled", "cancelled", "refunded"].includes(raw)) return "cancelled";
    if (["complete", "completed", "success"].includes(raw)) return "completed";
    if (["process", "processing", "auto-processing"].includes(raw)) return "processing";
    if (raw === "pending") return "pending";
    if (["failed", "fail", "error"].includes(raw)) return "failed";
    return raw;
}

async function unlockAndResume(req, res) {
    try {
        const id = Number(req.params.id);
        if (!Number.isFinite(id) || id <= 0) {
            return res.status(400).json({ success: false, message: "Invalid order ID" });
        }

        const [rows] = await db.pool.execute("SELECT * FROM orders WHERE id = ?", [id]);
        if (!rows || rows.length === 0) {
            return res.status(404).json({ success: false, message: "Order not found" });
        }

        const order = rows[0];
        if (order.status !== "locked") {
            return res.status(400).json({ success: false, message: "Order is not in locked status" });
        }

        await ensureOrderLockTable();
        await db.pool.execute(
            "UPDATE order_locks SET processed = 1, processed_at = NOW() WHERE order_row_id = ? AND processed = 0",
            [id]
        );

        await db.pool.execute("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?", ["processing", id]);

        await db.pool.execute(
            "INSERT INTO logs (order_id, order_reference, message, type) VALUES (?, ?, ?, ?)",
            [id, order.order_id, "Order unlocked by admin. Automation resumed.", "success"]
        );

        try {
            const adminController = require("./adminController");
            if (adminController && typeof adminController.processNextPendingOrder === "function") {
                await adminController.processNextPendingOrder();
            }
        } catch (resumeError) {
            logger.error(`Unlock resume trigger error: ${resumeError.message}`);
        }

        return res.json({ success: true, message: "Order unlocked. Automation resumed." });
    } catch (error) {
        logger.error(`Error unlocking order: ${error.message}`);
        return res.status(500).json({ success: false, message: error.message || "Failed to unlock order" });
    }
}

async function updateOrder(req, res) {
    try {
        const id = Number(req.params.id);
        if (!Number.isFinite(id) || id <= 0) {
            return res.status(400).json({ success: false, message: "Invalid order ID" });
        }

        const incomingStatus = normalizeStatus(req.body?.status);
        const uid = String(req.body?.uid || "").trim();
        const deliveryMessage = String(req.body?.delivery_message || "").trim();

        if (!uid) {
            return res.status(400).json({ success: false, message: "UID is required" });
        }

        if (!ALLOWED_STATUS.has(incomingStatus)) {
            return res.status(400).json({ success: false, message: "Invalid status value" });
        }

        const [rows] = await db.pool.execute("SELECT * FROM orders WHERE id = ?", [id]);
        if (!rows || rows.length === 0) {
            return res.status(404).json({ success: false, message: "Order not found" });
        }

        const order = rows[0];
        const isUnlockResume = order.status === "locked" && incomingStatus === "processing";

        if (isUnlockResume) {
            await ensureOrderLockTable();
            await db.pool.execute(
                "UPDATE order_locks SET processed = 1, processed_at = NOW() WHERE order_row_id = ? AND processed = 0",
                [id]
            );
        }

        await db.pool.execute(
            "UPDATE orders SET uid = ?, status = ?, updated_at = NOW() WHERE id = ?",
            [uid, incomingStatus, id]
        );

        await db.pool.execute(
            "INSERT INTO logs (order_id, order_reference, message, type) VALUES (?, ?, ?, ?)",
            [
                id,
                order.order_id,
                isUnlockResume
                    ? `Locked order resumed by admin (status: ${incomingStatus}, uid: ${uid})`
                    : `Order updated from panel (status: ${incomingStatus}, uid: ${uid})`,
                "info",
            ]
        );

        if (isUnlockResume) {
            try {
                const adminController = require("./adminController");
                if (adminController && typeof adminController.processNextPendingOrder === "function") {
                    await adminController.processNextPendingOrder();
                }
            } catch (resumeError) {
                logger.error(`Resume processing trigger error: ${resumeError.message}`);
            }
        }

        if (!isUnlockResume && order.order_id && !String(order.order_id).startsWith("MND")) {
            const callbackStatus = incomingStatus === "cancelled" ? "Cancelled" :
                incomingStatus === "completed" ? "Completed" :
                incomingStatus === "failed" ? "Failed" :
                incomingStatus === "processing" ? "Processing" : "Pending";

            await websiteWebhookController.sendResultToWebsite(
                order.order_id,
                callbackStatus,
                0,
                null,
                {
                    uid,
                    delivered_uid: uid,
                    replacement_uid: uid,
                    delivery_message: deliveryMessage,
                    note: deliveryMessage,
                    message: deliveryMessage,
                }
            );
        }

        return res.json({
            success: true,
            message: isUnlockResume
                ? "Locked order unlocked. Automation resumed."
                : "Order updated and synced successfully",
        });
    } catch (error) {
        logger.error(`Error updating order from panel: ${error.message}`);
        return res.status(500).json({ success: false, message: error.message || "Failed to update order" });
    }
}

module.exports = {
    updateOrder,
    unlockAndResume,
};
