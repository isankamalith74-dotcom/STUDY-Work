
module.exports = {
  name: "ping",

  async execute(sock, msg) {
    const start = Date.now();

    await sock.sendMessage(msg.key.remoteJid, {
      text: `🏓 Pong : ${Date.now() - start}ms`
    });
  }
}
