import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE = '/api'

export const useConnectionStore = defineStore('connection', {
  state: () => ({
    isConnecting: false,
    sessions: [],
    activeSessionId: null,
    savedConnections: JSON.parse(localStorage.getItem('savedConnections') || '[]'),
    recentConnections: JSON.parse(localStorage.getItem('recentConnections') || '[]'),
    favorites: JSON.parse(localStorage.getItem('favoriteConnections') || '[]'),
    error: null,
    searchQuery: ''
  }),

  getters: {
    activeSession: (state) => state.sessions.find(s => s.sessionId === state.activeSessionId),
    isConnected: (state) => !!state.activeSessionId,
    connectionInfo: (state) => state.activeSession ? state.activeSession.connectionInfo : null,
    sessionId: (state) => state.activeSessionId,
    currentPath: (state) => state.activeSession ? state.activeSession.currentPath : '/',
    remoteFiles: (state) => state.activeSession ? state.activeSession.remoteFiles : [],
    connectionLabel: (state) => {
      const info = state.activeSession ? state.activeSession.connectionInfo : null
      if (!info) return ''
      return `${info.username}@${info.host}`
    },
    filteredRemoteFiles: (state) => {
      const files = state.activeSession ? state.activeSession.remoteFiles : []
      if (!state.searchQuery) return files
      const q = state.searchQuery.toLowerCase()
      return files.filter(f => f.name.toLowerCase().includes(q))
    }
  },

  actions: {
    switchSession(id) {
      if (this.sessions.some(s => s.sessionId === id)) {
        this.activeSessionId = id
        this.persist()
      }
    },

    async connect(config) {
      this.isConnecting = true
      this.error = null

      try {
        const response = await axios.post(`${API_BASE}/connect.php`, {
          host: config.host,
          port: config.port || this.getDefaultPort(config.type),
          username: config.username,
          password: config.password,
          type: config.type,
          passive: config.passive !== false
        })

        if (response.data.success) {
          const newSessionId = response.data.sessionId
          const connectionInfo = {
            host: config.host,
            port: config.port || this.getDefaultPort(config.type),
            username: config.username,
            type: config.type
          }

          this.sessions.push({
            sessionId: newSessionId,
            connectionInfo,
            currentPath: '/',
            remoteFiles: []
          })
          this.activeSessionId = newSessionId

          if (config.saveConnection) {
            this.saveConnection(config)
          }
          this.addRecentConnection(config)
          this.persist()
          return { success: true }
        } else {
          throw new Error(response.data.message || 'Connection failed')
        }
      } catch (err) {
        this.error = err.message || 'Failed to connect'
        return { success: false, error: this.error }
      } finally {
        this.isConnecting = false
      }
    },

    async disconnect(id = null) {
      const sessionIdToDisconnect = id || this.activeSessionId
      if (!sessionIdToDisconnect) return

      try {
        await axios.post(`${API_BASE}/disconnect.php`, { sessionId: sessionIdToDisconnect })
      } catch (err) {
        console.warn('Disconnect error:', err)
      }

      this.sessions = this.sessions.filter(s => s.sessionId !== sessionIdToDisconnect)
      if (this.activeSessionId === sessionIdToDisconnect) {
        this.activeSessionId = this.sessions.length > 0 ? this.sessions[this.sessions.length - 1].sessionId : null
      }
      this.persist()
    },

    async listRemotePath(path = '/') {
      const activeSession = this.activeSession
      if (!activeSession) return []
      try {
        const response = await axios.post(`${API_BASE}/list.php`, {
          sessionId: activeSession.sessionId,
          path: path
        })
        if (response.data.success) {
          const sessionInState = this.sessions.find(s => s.sessionId === activeSession.sessionId)
          if (sessionInState) {
            sessionInState.remoteFiles = response.data.files || []
            sessionInState.currentPath = path
          }
          return response.data.files || []
        }
      } catch (err) {
        console.error('List error:', err)
        this.error = err.message
      }
      return []
    },

    async uploadFile(file, remotePath = '/') {
      if (!this.activeSessionId) throw new Error('Not connected')
      const formData = new FormData()
      formData.append('file', file)
      formData.append('sessionId', this.activeSessionId)
      formData.append('remotePath', remotePath)
      formData.append('remoteName', file.name)
      const response = await axios.post(`${API_BASE}/upload.php`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      if (!response.data.success) throw new Error(response.data.message || 'Upload failed')
      return response.data
    },

    async downloadFile(remoteFile) {
      if (!this.activeSessionId) throw new Error('Not connected')
      const response = await axios.post(`${API_BASE}/download.php`, {
        sessionId: this.activeSessionId,
        remotePath: this.currentPath,
        remoteName: remoteFile.name,
        isDirectory: remoteFile.isDirectory
      }, { responseType: 'blob' })
      return response.data
    },

    async createRemoteFolder(name, path = '/') {
      if (!this.activeSessionId) throw new Error('Not connected')
      try {
        const response = await axios.post(`${API_BASE}/mkdir.php`, {
          sessionId: this.activeSessionId, path: path, name: name
        })
        if (!response.data.success) throw new Error(response.data.message || 'Failed to create folder')
        return response.data
      } catch (err) {
        if (err.response && err.response.data && err.response.data.message) throw new Error(err.response.data.message)
        throw err
      }
    },

    async createRemoteFile(name, path = '/') {
      if (!this.activeSessionId) throw new Error('Not connected')
      const response = await axios.post(`${API_BASE}/create_file.php`, {
        sessionId: this.activeSessionId, path: path, name: name
      })
      if (!response.data.success) throw new Error(response.data.message || 'Failed to create file')
      return response.data
    },

    async deleteRemoteItems(items, path = '/', onProgress = null) {
      if (!this.activeSessionId) throw new Error('Not connected')
      try {
        if (onProgress) {
          const response = await fetch(`${API_BASE}/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              sessionId: this.activeSessionId, path: path, items: items, stream: true
            })
          });
          const reader = response.body.getReader();
          const decoder = new TextDecoder();
          let done = false;
          let finalData = null;
          let buffer = '';
          
          while (!done) {
            const { value, done: readerDone } = await reader.read();
            done = readerDone;
            if (value) {
              buffer += decoder.decode(value, { stream: !done });
              const lines = buffer.split('\n');
              buffer = lines.pop(); // keep incomplete line in buffer
              
              for (let line of lines) {
                line = line.trim();
                if (line.startsWith('data: ')) {
                  try {
                    const data = JSON.parse(line.substring(6));
                    if (data.status === 'progress') {
                      onProgress(data.message);
                    } else if (data.success === false) {
                      throw new Error(data.message || 'Failed to delete');
                    } else if (data.success === true) {
                      finalData = data;
                    }
                  } catch (e) {
                    // Ignore parse errors on incomplete chunks if any
                  }
                }
              }
            }
          }
          return finalData || { success: true };
        } else {
          const response = await axios.post(`${API_BASE}/delete.php`, {
            sessionId: this.activeSessionId, path: path, items: items
          })
          if (!response.data.success) throw new Error(response.data.message || 'Failed to delete')
          return response.data
        }
      } catch (err) {
        if (err.response && err.response.data && err.response.data.message) throw new Error(err.response.data.message)
        throw err
      }
    },

    async renameRemoteItem(oldName, newName, path = '/') {
      if (!this.activeSessionId) throw new Error('Not connected')
      const response = await axios.post(`${API_BASE}/rename.php`, {
        sessionId: this.activeSessionId, path: path, oldName: oldName, newName: newName
      })
      if (!response.data.success) throw new Error(response.data.message || 'Failed to rename')
      return response.data
    },

    saveConnection(config) {
      const exists = this.savedConnections.find(c => c.host === config.host && c.username === config.username)
      if (!exists) {
        this.savedConnections.push({
          id: Date.now(), host: config.host, port: config.port,
          username: config.username, type: config.type,
          label: config.label || `${config.username}@${config.host}`
        })
        this.persistSaved()
      }
    },

    removeSavedConnection(id) {
      this.savedConnections = this.savedConnections.filter(c => c.id !== id)
      this.persistSaved()
    },

    addRecentConnection(config) {
      this.recentConnections = this.recentConnections.filter(
        c => !(c.host === config.host && c.username === config.username)
      )
      this.recentConnections.unshift({
        host: config.host, port: config.port, username: config.username,
        type: config.type, date: new Date().toISOString()
      })
      if (this.recentConnections.length > 10) this.recentConnections = this.recentConnections.slice(0, 10)
      this.persistRecent()
    },

    getDefaultPort(type) {
      const ports = { ftp: 21, ftps: 990, ftpse: 21, sftp: 22 }
      return ports[type] || 21
    },

    persist() {
      localStorage.setItem('connection-store', JSON.stringify({
        sessions: this.sessions,
        activeSessionId: this.activeSessionId
      }))
    },

    persistSaved() {
      localStorage.setItem('savedConnections', JSON.stringify(this.savedConnections))
    },

    persistRecent() {
      localStorage.setItem('recentConnections', JSON.stringify(this.recentConnections))
    },

    restoreSession() {
      const stored = localStorage.getItem('connection-store')
      if (stored) {
        const data = JSON.parse(stored)
        if (data.isConnected && data.sessionId && !data.sessions) {
          this.sessions = [{
            sessionId: data.sessionId,
            connectionInfo: data.connectionInfo,
            currentPath: data.currentPath || '/',
            remoteFiles: []
          }]
          this.activeSessionId = data.sessionId
        } else if (data.sessions) {
          this.sessions = data.sessions.map(s => ({
            ...s,
            remoteFiles: []
          }))
          this.activeSessionId = data.activeSessionId
          if (this.activeSessionId && !this.sessions.some(s => s.sessionId === this.activeSessionId)) {
            this.activeSessionId = this.sessions.length > 0 ? this.sessions[0].sessionId : null
          }
        }
      }
    }
  }
})
