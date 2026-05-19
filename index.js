const {
    default: makeWASocket,
    getAggregateVotesInPollMessage, 
    useMultiFileAuthState,
    DisconnectReason,
    getDevice,
    fetchLatestBaileysVersion,
    jidNormalizedUser,
    getContentType,
    Browsers,
    makeInMemoryStore,
    makeCacheableSignalKeyStore,
    downloadContentFromMessage,
    generateForwardMessageContent,
    generateWAMessageFromContent,
    prepareWAMessageMedia,
    proto
} = require('@whiskeysockets/baileys')
const { 
  getBuffer, 
  getGroupAdmins, 
  getRandom, 
  h2k, 
  isUrl, 
  Json, 
  runtime, 
  sleep, 
  fetchJson 
} = require('./functions') // ෆෝල්ඩර් නැති නිසා ./lib/ අයින් කළා
const fs = require('fs')
const P = require('pino')
const FileType = require('file-type')
const l = console.log
var config = require('./settings')
const qrcode = require('qrcode-terminal')
const NodeCache = require('node-cache')
const util = require('util')
const { 
  sms,
  downloadMediaMessage 
} = require('./msg') // ෆෝල්ඩර් නැති නිසා ./lib/ අයින් කළා
const axios = require('axios')
const { File } = require('megajs')
const { exec } = require('child_process');
const { tmpdir } = require('os')
const Crypto = require('crypto')
const Jimp = require('jimp')

var prefix = config.PREFIX
var prefixRegex = config.PREFIX === "false" || config.PREFIX === "null" ? "^" : new RegExp('^[' + config.PREFIX + ']');

function genMsgId() {
  const prefix = "3EB";
  const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
  let randomText = prefix;
  for (let i = prefix.length; i < 22; i++) {
    const randomIndex = Math.floor(Math.random() * characters.length);
    randomText += characters.charAt(randomIndex);
  }   
  return randomText;
}    

const path = require('path')
const msgRetryCounterCache = new NodeCache()
const ownerNumber = ['94718461889']

//================== SESSION ==================
if (!fs.existsSync(__dirname + '/session/creds.json')) {
    if (!config.SESSION_ID) {
        console.log("Please Add SESSION_ID ➾");
        process.exit(1);
    }
    const sessdata = config.SESSION_ID.split("𝙽𝙴𝙻𝚄𝙼𝙸-𝙼𝙳=")[1] || config.SESSION_ID;
    const filer = File.fromURL(`https://mega.nz/file/${sessdata}`)
    filer.download((err, data) => {
        if (err) throw err
        fs.writeFileSync(__dirname + '/session/creds.json', data);
        console.log("Session download completed !!")
    })
}

//================== PORTS ==================
const express = require("express");
const app = express();
const port = process.env.PORT || 9000;

app.get("/", (req, res) => {
    res.send("QUEEN-NELUMI-MD CONNECTED SUCCESSFUL💔🪄");
});
app.listen(port, () => console.log(`QUEEN-NELUMI-MD Server listening on port ${port}`));

async function connectToWA() {
    console.log("Connecting QUEEN-NELUMI-MD 💗");
    const { version, isLatest } = await fetchLatestBaileysVersion()
    console.log(`using WA v${version.join('.')}, isLatest: ${isLatest}`)
    
    const { state, saveCreds } = await useMultiFileAuthState(__dirname + '/session/')
    
    const conn = makeWASocket({
        logger: P({ level: "fatal" }),
        printQRInTerminal: true,
        generateHighQualityLinkPreview: true,
        auth: state,
        msgRetryCounterCache
    })

    conn.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect } = update
        if (connection === 'close') {
            if (lastDisconnect && lastDisconnect.error && lastDisconnect.error.output && lastDisconnect.error.output.statusCode !== DisconnectReason.loggedOut) {
                connectToWA()
            } else {
                console.log("Connection closed. Logged out?");
            }
        } else if (connection === 'open') {
            console.log('Installing plugins 😌... ')
            
            // කෙලින්ම එළියේ තියෙන alive.js සහ dog.js රන් කරන්න සකස් කළා
            const filesToLoad = ["./alive.js", "./dog.js"];
            filesToLoad.forEach((file) => {
                if (fs.existsSync(file)) {
                    require(file);
                }
            });

            console.log('QUEEN-NELUMI-MD Plugins installed 🤭💗')
            console.log(' Bot connected ✅')

            let up = "QUEEN-NELUMI-MD BOT CONNECTED SUCCESSFULL\n\nPrefix :-" + config.PREFIX + "\nMode :- " + config.MODE + "\nStatus Read :-" + config.AUTO_READ_STATUS + "\n\n> ᴘᴀᴡᴇʀᴇᴅ ʙʏ ꜱᴜᴘᴜɴ ᴍᴅ";
            conn.sendMessage(conn.user.id, { 
                text: up, 
                contextInfo: {
                    isForwarded: true,
                    forwardedNewsletterMessageInfo: {
                        newsletterJid: '120363421132465520@newsletter',
                        newsletterName: "QUEEN-NELUMI-MD",
                        serverMessageId: 999
                    },
                    externalAdReply: { 
                        title: 'QUEEN-NELUMI-MD',
                        body: 'QUEEN-NELUMI-MD',
                        mediaType: 1,
                        thumbnailUrl: "https://i.ibb.co/6RPYc2rF/4681.jpg",
                        renderLargerThumbnail: true,
                        showAdAttribution: true
                    }
                } 
            })
        }
    })

    conn.ev.on('creds.update', saveCreds)  

    conn.ev.on('messages.upsert', async (mek) => {
        try {
            mek = mek.messages[0]
            if (!mek.message) return
            mek.message = (getContentType(mek.message) === 'ephemeralMessage') ? mek.message.ephemeralMessage.message : mek.message

            if (mek.key && mek.key.remoteJid === 'status@broadcast' && config.AUTO_READ_STATUS === "true"){
                await conn.readMessages([mek.key])  
                const mnyako = await jidNormalizedUser(conn.user.id)
                await conn.sendMessage(mek.key.remoteJid, { react: { key: mek.key, text: 'බලලම එපා වෙනෝ 🤧'}}, { statusJidList: [mek.key.participant, mnyako] })
            }          
            if (mek.key && mek.key.remoteJid === 'status@broadcast') return
            
            const m = sms(conn, mek)
            const type = getContentType(mek.message)
            const from = mek.key.remoteJid

            const quoted = type == 'extendedTextMessage' && mek.message.extendedTextMessage.contextInfo != null ? mek.message.extendedTextMessage.contextInfo.quotedMessage || [] : []

            try {
                const metadata = await conn.newsletterMetadata("jid", "120363421132465520@newsletter");
                if (metadata && metadata.viewer_metadata === null) {
                    await conn.newsletterFollow("120363421132465520@newsletter");
                    console.log("QUEEN-NELUMI-MD CHANNEL FOLLOW ✅");
                }
            } catch (e) {}

            const body = (type === 'conversation') ? mek.message.conversation : (type === 'extendedTextMessage') ? mek.message.extendedTextMessage.text :(type == 'interactiveResponseMessage' ) ? mek.message.interactiveResponseMessage && mek.message.interactiveResponseMessage.nativeFlowResponseMessage && JSON.parse(mek.message.interactiveResponseMessage.nativeFlowResponseMessage.paramsJson) && JSON.parse(mek.message.interactiveResponseMessage.nativeFlowResponseMessage.paramsJson).id :(type == 'templateButtonReplyMessage' )? mek.message.templateButtonReplyMessage && mek.message.templateButtonReplyMessage.selectedId : (type == 'imageMessage') && mek.message.imageMessage.caption ? mek.message.imageMessage.caption : (type == 'videoMessage') && mek.message.videoMessage.caption ? mek.message.videoMessage.caption : ''

            const isCmd = body.startsWith(prefix)     
            const command = isCmd ? body.slice(prefix.length).trim().split(' ').shift().toLowerCase() : ''
            const args = body.trim().split(/ +/).slice(1)
            const q = args.join(' ')
            const isGroup = from.endsWith('@g.us')
            const sender = mek.key.fromMe ? (conn.user.id.split(':')[0] + '@s.whatsapp.net' || conn.user.id) : (mek.key.participant || mek.key.remoteJid)
            const senderNumber = sender.split('@')[0]
            const botNumber = conn.user.id.split(':')[0]
            const pushname = mek.pushName || 'SUPUN MD'
            const ownbot = config.SUDO
            const isownbot = ownbot?.includes(senderNumber)
            const developers = '94718461889'
            const isbot = botNumber.includes(senderNumber)
            const isdev = developers.includes(senderNumber)     
            const botNumber2 = await jidNormalizedUser(conn.user.id)
            const isMe = isbot ? isbot : isdev
            const isOwner = ownerNumber.includes(senderNumber) || isMe
            const groupMetadata = isGroup ? await conn.groupMetadata(from).catch(e => {}) : ''
            const groupName = isGroup ? groupMetadata.subject : ''
            const participants = isGroup ? groupMetadata.participants : ''
            const groupAdmins = isGroup ? getGroupAdmins(participants) : ''
            const isBotAdmins = isGroup ? groupAdmins.includes(botNumber2) : false
            const isAdmins = isGroup ? groupAdmins.includes(sender) : false

            const reply = async(teks) => {
                return await conn.sendMessage(from, { text: teks }, { quoted: mek })
            }

            if(!isOwner && config.MODE === "private") return 
            if(!isOwner && isGroup && config.MODE === "inbox") return 
            if(!isOwner && !isGroup && config.MODE === "groups") return 

            const events = require('./command') // මෙතනත් ./lib/ එක අයින් කළා
            const cmdName = isCmd ? command : false;
            if (isCmd) {
                const cmd = events.commands.find((cmd) => cmd.pattern === (cmdName)) || events.commands.find((cmd) => cmd.alias && cmd.alias.includes(cmdName))
                if (cmd) {
                    if (cmd.react) conn.sendMessage(from, { react: { text: cmd.react, key: mek.key } })
                    try {
                        cmd.function(conn, mek, m, { from, prefix, l, quoted, body, isCmd, command, args, q, isGroup, sender, senderNumber, botNumber2, botNumber, pushname, isMe, isOwner, groupMetadata, groupName, participants, groupAdmins, isBotAdmins, isAdmins, reply });
                    } catch (e) {
                        console.error("[PLUGIN ERROR] ", e);
                    }
                }
            }

            switch (command) {
                case 'jid':
                    reply(from)
                    break
                default:                
                    if (isOwner && body.startsWith('$')) {
                        let bodyy = body.split('$')[1]
                        let code2 = bodyy.replace("°", ".toString()");
                        try {
                            let resultTest = await eval(code2);
                            reply(util.format(resultTest));
                        } catch (err) {
                            reply(util.format(err));
                        }
                    }
            }
        } catch (e) {
            console.log(e)
        }
    })
}

setTimeout(() => {
    connectToWA()
}, 5000);
