import { defineStore } from 'pinia'
import { useConnectionStore } from './connection'

export const useUploadQueueStore = defineStore('uploadQueue', {
  state: () => ({
    // Liste des lots (batches)
    batches: [],
    // Indique si on est en train de traiter un lot
    isProcessing: false,
    // Callback de refresh (injecté par FilesPage)
    onAllDoneCallback: null,
  }),

  getters: {
    // Lot en cours de traitement (premier lot non encore terminé)
    activeBatch: (state) => state.batches.find(b => b.status === 'processing'),
    // Lots en attente
    pendingBatches: (state) => state.batches.filter(b => b.status === 'pending'),
    // Lots terminés
    doneBatches: (state) => state.batches.filter(b => b.status === 'done' || b.status === 'done_with_errors'),
    // Nombre total de fichiers actifs (en cours d'envoi)
    totalActiveFiles: (state) => {
      const active = state.batches.find(b => b.status === 'processing')
      if (!active) return 0
      return active.files.filter(f => f.status === 'uploading').length
    },
    // Y a-t-il des lots non terminés ?
    hasPendingWork: (state) => state.batches.some(b => b.status === 'pending' || b.status === 'processing'),
  },

  actions: {
    /**
     * Enregistre le callback à appeler quand tout est terminé.
     */
    onAllDone(cb) {
      this.onAllDoneCallback = cb
    },

    /**
     * Ajoute un nouveau lot de fichiers à la file d'attente.
     * @param {File[]} files - les fichiers à envoyer
     * @param {string} remotePath - le chemin distant cible
     * @returns {string} - l'id du lot créé
     */
    addBatch(files, remotePath) {
      const batchId = `batch_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`
      const batch = {
        id: batchId,
        remotePath,
        status: 'pending', // 'pending' | 'processing' | 'done' | 'done_with_errors'
        createdAt: Date.now(),
        files: files.map((file, index) => ({
          id: `${batchId}_f${index}`,
          file,                   // Objet File réel, nécessaire pour l'upload
          name: file.name,
          size: file.size,
          relativePath: file.webkitRelativePath || file.customPath || '',
          status: 'pending',      // 'pending' | 'uploading' | 'done' | 'error'
          progress: 0,
          error: null,
        }))
      }
      this.batches.push(batch)

      // Lance le traitement si rien n'est en cours
      if (!this.isProcessing) {
        this._processNext()
      }

      return batchId
    },

    /**
     * Démarre le traitement du prochain lot en attente.
     */
    async _processNext() {
      const nextBatch = this.batches.find(b => b.status === 'pending')
      if (!nextBatch) {
        this.isProcessing = false
        // Tout est terminé : déclencher le refresh
        if (this.onAllDoneCallback) {
          this.onAllDoneCallback()
        }
        return
      }

      this.isProcessing = true
      nextBatch.status = 'processing'

      const connectionStore = useConnectionStore()

      // Récupérer la limite de simultanéité depuis les settings
      let maxSimultaneous = 3 // Remis à 3 pour la rapidité, combiné à la logique de retry
      try {
        const { useSettingsStore } = await import('./settings')
        const planLimit = useSettingsStore().planLimits?.maxSimultaneous
        if (planLimit && planLimit < 3) maxSimultaneous = 3
      } catch(e) { /* fallback */ }

      let sessionExpired = false
      const total = nextBatch.files.length
      let batchFinished = false;

      while (!batchFinished && !sessionExpired) {
        let activeUploads = 0
        let currentIndex = 0
        let hasErrorsInPass = false

        await new Promise((resolve) => {
          const launchNext = () => {
            // Trouver le prochain fichier 'pending'
            let nextPendingIdx = -1
            for (let i = currentIndex; i < total; i++) {
              if (nextBatch.files[i].status === 'pending') {
                nextPendingIdx = i
                break
              }
            }

            // Tous les fichiers restants sont traités ou en cours
            if (nextPendingIdx === -1) {
              if (activeUploads === 0) resolve()
              return
            }

            while (activeUploads < maxSimultaneous && nextPendingIdx !== -1 && !sessionExpired) {
              currentIndex = nextPendingIdx + 1
              const fileEntry = nextBatch.files[nextPendingIdx]
              
              activeUploads++
              fileEntry.status = 'uploading'

              // Calculer le chemin distant de ce fichier
              let targetPath = nextBatch.remotePath
              if (fileEntry.relativePath && fileEntry.relativePath.includes('/')) {
                const parts = fileEntry.relativePath.split('/')
                parts.pop()
                const relDir = parts.join('/')
                const base = targetPath === '/' ? '' : targetPath.replace(/\/$/, '')
                targetPath = base + '/' + relDir
              }

              connectionStore.uploadFile(fileEntry.file, targetPath)
                .then(() => {
                  fileEntry.status = 'done'
                  fileEntry.progress = 100
                })
                .catch((err) => {
                  const isSessionError = err.message?.toLowerCase?.().match(/(session|connect|login|authentification|401|unauthorized)/)
                  if (isSessionError && !sessionExpired) {
                    sessionExpired = true
                    // Marquer tous les fichiers restants comme annulés
                    for (let i = 0; i < total; i++) {
                      if (nextBatch.files[i].status === 'pending' || nextBatch.files[i].status === 'uploading') {
                        nextBatch.files[i].status = 'error'
                        nextBatch.files[i].error = 'Session expirée'
                      }
                    }
                    // Marquer les lots en attente
                    this.batches.forEach(b => {
                      if (b.status === 'pending') {
                        b.status = 'done_with_errors'
                        b.files.forEach(f => { f.status = 'error'; f.error = 'Session expirée' })
                      }
                    })
                    // Déclencher le modal de session expirée
                    window.dispatchEvent(new CustomEvent('upload-session-expired'))
                  } else if (!sessionExpired) {
                    fileEntry.status = 'error'
                    fileEntry.error = err.message
                    hasErrorsInPass = true
                  }
                })
                .finally(() => {
                  activeUploads--
                  launchNext()
                })
                
              // Chercher le prochain pending pour la boucle while
              nextPendingIdx = -1
              for (let i = currentIndex; i < total; i++) {
                if (nextBatch.files[i].status === 'pending') {
                  nextPendingIdx = i
                  break
                }
              }
            }
          }
          launchNext()
        })

        // Fin d'une passe sur les fichiers. Vérifier si on doit faire un retry.
        const retryableFiles = nextBatch.files.filter(f => f.status === 'error' && (f.retries || 0) < 2 && f.error !== 'Session expirée')
        if (retryableFiles.length > 0 && !sessionExpired) {
          // Relancer les fichiers qui ont échoué
          retryableFiles.forEach(f => {
            f.status = 'pending'
            f.error = null
            f.progress = 0
            f.retries = (f.retries || 0) + 1
          })
          // La boucle externe continue
        } else {
          batchFinished = true
        }
      }

      // Marquer le lot comme terminé définitivement
      const finalErrors = nextBatch.files.some(f => f.status === 'error')
      nextBatch.status = finalErrors ? 'done_with_errors' : 'done'

      // Si session expirée, on arrête tout
      if (sessionExpired) {
        this.isProcessing = false
        return
      }

      // Traiter le lot suivant
      await this._processNext()
    },

    /**
     * Supprime les lots terminés de la liste.
     */
    clearDone() {
      this.batches = this.batches.filter(b => b.status === 'pending' || b.status === 'processing')
    },

    /**
     * Annule un fichier en attente d'un lot.
     */
    cancelFile(batchId, fileId) {
      const batch = this.batches.find(b => b.id === batchId)
      if (!batch || batch.status === 'processing') return // On ne peut pas annuler un lot en cours
      const fileIdx = batch.files.findIndex(f => f.id === fileId)
      if (fileIdx > -1) batch.files.splice(fileIdx, 1)
      // Si le lot est vide, le supprimer
      if (batch.files.length === 0) {
        this.batches = this.batches.filter(b => b.id !== batchId)
      }
    },

    /**
     * Supprime un lot entier (seulement si en attente).
     */
    cancelBatch(batchId) {
      const batch = this.batches.find(b => b.id === batchId)
      if (!batch || batch.status === 'processing') return
      this.batches = this.batches.filter(b => b.id !== batchId)
    }
  }
})
