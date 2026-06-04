
module.exports = {
  name: "alive",

  async execute(sock, msg) {

    await sock.sendMessage(msg.key.remoteJid, {
      text: `
╔══════════════╗
   ✅ BOT ONLINE
╚══════════════╝

🚀 SULA X MD Running Successfully
⚡ Speed Optimized
🛡️ Anti Crash Enabled
💎 Professional Edition
`
    });
  }
}
