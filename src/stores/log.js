import { defineStore } from 'pinia'

export const useLogStore = defineStore('log', {
  state: () => ({
    entries: [],
    filter: 'all',
    maxEntries: 1000
  }),

  getters: {
    filteredEntries: (state) => {
      if (state.filter === 'all') return state.entries
      return state.entries.filter(e => e.level === state.filter)
    },
    errorCount: (state) => state.entries.filter(e => e.level === 'error').length,
    warningCount: (state) => state.entries.filter(e => e.level === 'warning').length
  },

  actions: {
    addEntry(level, message, details = '') {
      const entry = {
        id: Date.now() + Math.random(),
        timestamp: new Date().toISOString(),
        level, message, details
      }
      this.entries.unshift(entry)
      if (this.entries.length > this.maxEntries) this.entries = this.entries.slice(0, this.maxEntries)
      this.persist()
    },
    logConnection(message, details = '') { this.addEntry('connection', message, details) },
    logCommand(message, details = '') { this.addEntry('command', message, details) },
    logInfo(message, details = '') { this.addEntry('info', message, details) },
    logWarning(message, details = '') { this.addEntry('warning', message, details) },
    logError(message, details = '') { this.addEntry('error', message, details) },
    setFilter(filter) { this.filter = filter },
    clear() { this.entries = []; this.persist() },
    persist() { localStorage.setItem('log-store', JSON.stringify(this.entries)) },
    restore() {
      const stored = localStorage.getItem('log-store')
      if (stored) this.entries = JSON.parse(stored)
    }
  }
})
