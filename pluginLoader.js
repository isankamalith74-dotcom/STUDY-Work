
const fs = require('fs');
const path = require('path');

function loadPlugins() {
  const plugins = [];
  const pluginDir = path.join(__dirname, '../plugins');

  if (!fs.existsSync(pluginDir)) return plugins;

  const files = fs.readdirSync(pluginDir);

  for (const file of files) {
    if (file.endsWith('.js')) {
      plugins.push(require(path.join(pluginDir, file)));
    }
  }

  return plugins;
}

module.exports = { loadPlugins };
