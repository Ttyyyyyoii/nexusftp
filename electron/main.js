import { app, BrowserWindow, Menu, shell, ipcMain } from 'electron';
import { fileURLToPath } from 'url';
import path from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

let mainWindow;

function createWindow() {
  // Créer la fenêtre principale
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 820,
    minWidth: 900,
    minHeight: 600,
    title: 'NexusFTP',
    icon: path.join(__dirname, '../public/icon.png'),
    backgroundColor: '#0f0f23',
    show: false, // Ne pas afficher avant que la page soit chargée
    titleBarStyle: process.platform === 'darwin' ? 'hiddenInset' : 'default',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      sandbox: true,
    },
  });

  // Charger l'application
  const viteDevServerUrl = process.env.VITE_DEV_SERVER_URL;
  if (viteDevServerUrl) {
    mainWindow.loadURL(viteDevServerUrl);
    mainWindow.webContents.openDevTools();
  } else {
    // En production, charger le fichier index.html buildé
    mainWindow.loadFile(path.join(__dirname, '../dist/index.html'));
  }

  // Afficher la fenêtre quand elle est prête (évite le flash blanc)
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
    mainWindow.focus();
  });

  // Ouvrir les liens externes dans le navigateur par défaut, pas dans Electron
  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: 'deny' };
  });

  // Empêcher la navigation vers des URLs externes
  mainWindow.webContents.on('will-navigate', (event, url) => {
    const appUrl = viteDevServerUrl || `file://${path.join(__dirname, '../dist/index.html')}`;
    if (!url.startsWith(appUrl) && !url.startsWith('file://') && !url.startsWith('https://nexusftp.onrender.com')) {
      event.preventDefault();
      shell.openExternal(url);
    }
  });
}

// Configurer le menu natif de l'application
function setupMenu() {
  const isMac = process.platform === 'darwin';

  const template = [
    // Menu Apple (macOS seulement)
    ...(isMac ? [{
      label: app.name,
      submenu: [
        { role: 'about' },
        { type: 'separator' },
        { role: 'services' },
        { type: 'separator' },
        { role: 'hide' },
        { role: 'hideOthers' },
        { role: 'unhide' },
        { type: 'separator' },
        { role: 'quit' }
      ]
    }] : []),
    // Fichier
    {
      label: 'Fichier',
      submenu: [
        {
          label: 'Nouvelle connexion',
          accelerator: 'CmdOrCtrl+N',
          click: () => {
            mainWindow?.webContents.executeJavaScript(`window.__router?.push('/connect')`);
          }
        },
        { type: 'separator' },
        isMac ? { role: 'close' } : { role: 'quit', label: 'Quitter' }
      ]
    },
    // Édition
    {
      label: 'Édition',
      submenu: [
        { role: 'undo', label: 'Annuler' },
        { role: 'redo', label: 'Rétablir' },
        { type: 'separator' },
        { role: 'cut', label: 'Couper' },
        { role: 'copy', label: 'Copier' },
        { role: 'paste', label: 'Coller' },
        { role: 'selectAll', label: 'Tout sélectionner' }
      ]
    },
    // Affichage
    {
      label: 'Affichage',
      submenu: [
        { role: 'reload', label: 'Actualiser' },
        { role: 'togglefullscreen', label: 'Plein écran' },
        { type: 'separator' },
        { role: 'zoomIn', label: 'Zoom +' },
        { role: 'zoomOut', label: 'Zoom -' },
        { role: 'resetZoom', label: 'Zoom normal' }
      ]
    },
    // Aide
    {
      label: 'Aide',
      submenu: [
        {
          label: 'Documentation',
          click: () => shell.openExternal('https://nexusftp.onrender.com/docs')
        },
        {
          label: 'Signaler un bug',
          click: () => shell.openExternal('https://github.com/Ttyyyyyoii/nexusftp/issues')
        },
        { type: 'separator' },
        {
          label: 'À propos de NexusFTP',
          click: () => {
            mainWindow?.webContents.executeJavaScript(`window.__router?.push('/')`);
          }
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

// IPC pour exposer des infos à l'app web
ipcMain.handle('get-app-version', () => app.getVersion());
ipcMain.handle('get-platform', () => process.platform);

app.whenReady().then(() => {
  setupMenu();
  createWindow();

  app.on('activate', function () {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', function () {
  if (process.platform !== 'darwin') app.quit();
});
