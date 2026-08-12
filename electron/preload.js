const { contextBridge, ipcRenderer } = require('electron');

// Exposer des APIs sécurisées à l'application web
contextBridge.exposeInMainWorld('electronAPI', {
  getVersion: () => ipcRenderer.invoke('get-app-version'),
  getPlatform: () => ipcRenderer.invoke('get-platform'),
  isElectron: true
});
