const axios = require("axios");

function asText(value) {
    return value === undefined || value === null ? "" : String(value);
}

async function sendDiscordNotification(settings, title, details = {}, options = {}) {
    const enabled = settings.order_lock_discord_enabled === true || settings.order_lock_discord_enabled === "true";
    const webhookUrl = asText(settings.order_lock_discord_webhook_url).trim();
    const force = options.force === true;

    if ((!enabled && !force) || !webhookUrl) {
        return { sent: false, reason: "discord-disabled-or-missing-config" };
    }

    const lines = [];
    lines.push("[MS2BD Discord Alert]");
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

    await axios.post(webhookUrl, {
        content: lines.join("\n"),
        username: "MS2BD Alert Bot",
    }, {
        timeout: 10000,
    });

    return { sent: true };
}

module.exports = {
    sendDiscordNotification,
};
