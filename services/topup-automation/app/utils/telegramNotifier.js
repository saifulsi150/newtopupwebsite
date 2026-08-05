const axios = require("axios");

function asText(value) {
    return value === undefined || value === null ? "" : String(value);
}

async function sendTelegramNotification(settings, title, details = {}, options = {}) {
    const enabled = settings.order_lock_telegram_enabled === true || settings.order_lock_telegram_enabled === "true";
    const botToken = asText(settings.order_lock_telegram_bot_token).trim();
    const chatId = asText(settings.order_lock_telegram_chat_id).trim();
    const force = options.force === true;

    if ((!enabled && !force) || !botToken || !chatId) {
        return { sent: false, reason: "telegram-disabled-or-missing-config" };
    }

    const lines = [];
    lines.push("[MS2BD Telegram Alert]");
    lines.push(asText(title) || "Event");

    if (details && typeof details === "object") {
        const orderedKeys = ["event", "orderId", "status", "uid", "amount", "endpoint", "message"];
        for (const key of orderedKeys) {
            if (details[key] !== undefined && details[key] !== null && asText(details[key]).trim() !== "") {
                lines.push(`${key}: ${asText(details[key])}`);
            }
        }

        for (const [key, value] of Object.entries(details)) {
            if (orderedKeys.includes(key)) {
                continue;
            }
            if (value === undefined || value === null) {
                continue;
            }
            const text = asText(value).trim();
            if (!text) {
                continue;
            }
            lines.push(`${key}: ${text}`);
        }
    }

    if (options.timestamp !== false) {
        lines.push(`time: ${new Date().toISOString()}`);
    }

    await axios.post(`https://api.telegram.org/bot${botToken}/sendMessage`, {
        chat_id: chatId,
        text: lines.join("\n"),
        disable_web_page_preview: true,
    }, {
        timeout: 10000,
    });

    return { sent: true };
}

module.exports = {
    sendTelegramNotification,
};
