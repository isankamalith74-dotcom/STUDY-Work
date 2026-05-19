const fs = require('fs');
if (fs.existsSync('config.env')) require('dotenv').config({ path: './config.env' });

function convertToBool(text, fault = 'true') {
    return text === fault ? true : false;
}

module.exports = {
    SESSION_ID: process.env.SESSION_ID === undefined ? '𝙽𝙴𝙻𝚄𝙼𝙸-𝙼𝙳=put your session_id' : process.env.SESSION_ID,
    PREFIX: process.env.PREFIX || '.',
    MODE: process.env.MODE === undefined ? "public" : process.env.MODE,
    AUTO_READ_STATUS: process.env.AUTO_READ_STATUS === undefined ? "true" : process.env.AUTO_READ_STATUS,
    SUDO: process.env.SUDO === undefined ? '94718461889' : process.env.SUDO
};
