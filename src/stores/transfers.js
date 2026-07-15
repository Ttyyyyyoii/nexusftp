import { defineStore } from 'pinia'

export const useTransfersStore = defineStore('transfers', {
  state: () => ({
    transfers: [],
    queue: [],
    activeCount: 0,
    maxSimultaneous: 3,
    completedTransfers: JSON.parse(localStorage.getItem('completedTransfers') || '[]')
  }),

  getters: {
    activeTransfers: (state) => state.transfers.filter(t => t.status === 'active'),
    completedList: (state) => state.transfers.filter(t => t.status === 'completed'),
    failedTransfers: (state) => state.transfers.filter(t => t.status === 'failed')
  },

  actions: {
    addTransfer(file, type, remotePath = '/') {
      const transfer = {
        id: Date.now() + Math.random(), fileName: file.name, fileSize: file.size,
        type, status: 'pending', progress: 0, speed: 0, transferred: 0,
        remotePath, startTime: null, endTime: null, error: null, retries: 0
      }
      if (this.activeCount < this.maxSimultaneous) {
        transfer.status = 'active'
        transfer.startTime = Date.now()
        this.activeCount++
      } else {
        this.queue.push(transfer)
      }
      this.transfers.unshift(transfer)
      return transfer.id
    },
    updateProgress(id, progress, speed, transferred) {
      const transfer = this.transfers.find(t => t.id === id)
      if (transfer) {
        transfer.progress = progress
        transfer.speed = speed
        transfer.transferred = transferred || (transfer.fileSize * progress / 100)
      }
    },
    completeTransfer(id) {
      const transfer = this.transfers.find(t => t.id === id)
      if (transfer) {
        transfer.status = 'completed'
        transfer.progress = 100
        transfer.endTime = Date.now()
        this.activeCount--
        this.completedTransfers.unshift({
          fileName: transfer.fileName, type: transfer.type, fileSize: transfer.fileSize,
          completedAt: new Date().toISOString(), duration: transfer.endTime - transfer.startTime
        })
        if (this.completedTransfers.length > 100) this.completedTransfers = this.completedTransfers.slice(0, 100)
        this.processQueue()
        localStorage.setItem('completedTransfers', JSON.stringify(this.completedTransfers))
      }
    },
    failTransfer(id, error) {
      const transfer = this.transfers.find(t => t.id === id)
      if (transfer) {
        transfer.status = 'failed'
        transfer.error = error
        transfer.endTime = Date.now()
        this.activeCount--
        this.processQueue()
      }
    },
    cancelTransfer(id) {
      const idx = this.transfers.findIndex(t => t.id === id)
      if (idx > -1) {
        if (this.transfers[idx].status === 'active') {
          this.activeCount--
          this.processQueue()
        }
        this.transfers.splice(idx, 1)
      }
    },
    processQueue() {
      while (this.activeCount < this.maxSimultaneous && this.queue.length > 0) {
        const next = this.queue.shift()
        const transfer = this.transfers.find(t => t.id === next.id)
        if (transfer) {
          transfer.status = 'active'
          transfer.startTime = Date.now()
          this.activeCount++
        }
      }
    },
    clearCompleted() {
      this.transfers = this.transfers.filter(t => t.status !== 'completed')
    }
  }
})
