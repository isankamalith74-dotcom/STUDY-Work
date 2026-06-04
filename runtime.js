
module.exports = {
  name: "runtime",

  async execute(sock, msg) {

    const seconds = process.uptime();
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);

    await sock.sendMessage(msg.key.remoteJid, {
      text: `⏰ Runtime : ${h}h ${m}m ${s}s`
    });
  }
}
