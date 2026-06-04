module.exports = {
  name: "menu",
  alias: ["allmenu", "help"],
  description: "Professional command menu",

  async execute(sock, msg, args) {

    const menu = `
╔═══════════════════╗
     🚀 SULA X MD 🚀
╚═══════════════════╝

👋 Welcome User

╭──〔 🤖 AI MENU 〕──⬣
│ • .ai
│ • .gpt
│ • .imagine
│ • .stickerai
╰──────────────⬣

╭──〔 🎵 DOWNLOAD MENU 〕──⬣
│ • .song
│ • .video
│ • .tiktok
│ • .spotify
│ • .instagram
╰──────────────⬣

╭──〔 👥 GROUP MENU 〕──⬣
│ • .tagall
│ • .hidetag
│ • .warn
│ • .kick
│ • .promote
│ • .demote
╰──────────────⬣

╭──〔 🎮 FUN MENU 〕──⬣
│ • .truth
│ • .dare
│ • .joke
│ • .quote
│ • .fact
╰──────────────⬣

╭──〔 ⚙️ OWNER MENU 〕──⬣
│ • .restart
│ • .shutdown
│ • .broadcast
│ • .ban
│ • .unban
╰──────────────⬣

╭──〔 💎 SYSTEM INFO 〕──⬣
│ • Fast Response
│ • Professional UI
│ • Anti Crash
│ • Multi Device
│ • Secure System
╰──────────────⬣

✨ Powered By SULA X MD
`;

    await sock.sendMessage(msg.key.remoteJid, {
      image: { url: 'https://files.catbox.moe/5j5k9w.jpg' },
      caption: menu
    });

  }
}
