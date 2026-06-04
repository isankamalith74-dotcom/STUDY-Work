require('dotenv').config();
const express = require('express');
const rateLimit = require('express-rate-limit');
const chalk = require('chalk');
const app = express();

const PORT = process.env.PORT || 8000;
const path = process.cwd();

require('events').EventEmitter.defaultMaxListeners = 500;

app.use(rateLimit({
  windowMs: 60 * 1000,
  max: 100
}));

app.get('/', (req, res) => {
  res.sendFile(path + '/main.html');
});

app.get('/status', (req, res) => {
  res.json({
    bot: process.env.BOT_NAME || 'SULA X MD',
    status: 'online',
    uptime: process.uptime(),
    memory: process.memoryUsage()
  });
});

app.listen(PORT, () => {
  console.log(chalk.green(`
╔══════════════════════════════╗
║      SULA X MD ONLINE        ║
╚══════════════════════════════╝
Server running on http://localhost:${PORT}
  `));
});
