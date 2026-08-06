<template>
  <DefaultLayout>
    <section class="relative pt-24 pb-16 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-surface-0 via-violet-50/20 to-surface-0 dark:from-surface-950 dark:via-violet-900/5 dark:to-surface-950" />
      <div class="relative max-w-6xl mx-auto px-6">

        <!-- Header -->
        <div class="text-center mb-16">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 mb-6">
            <Code class="w-4 h-4 text-violet-500" />
            <span class="text-sm font-medium text-violet-700 dark:text-violet-300">{{ $t('nav.api') }} Reference</span>
          </div>
          <h1 class="text-4xl sm:text-5xl font-bold text-surface-900 dark:text-white mb-4">API Reference</h1>
          <p class="text-lg text-surface-500 dark:text-surface-400 max-w-2xl mx-auto">Documentation technique des endpoints du backend PHP de NexusFTP. Utilisez cette API pour intégrer NexusFTP dans vos propres applications.</p>
          <div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
            <div class="w-2 h-2 rounded-full bg-emerald-500" />
            <span class="text-sm text-emerald-700 dark:text-emerald-400 font-mono">Base URL: <strong>https://nexusftp.onrender.com/api</strong></span>
          </div>
        </div>

        <!-- Authentification -->
        <div class="glass-panel rounded-2xl p-8 mb-8">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
              <Lock class="w-5 h-5 text-amber-500" />
            </div>
            <h2 class="text-xl font-bold text-surface-900 dark:text-white">Authentification par Session</h2>
          </div>
          <p class="text-sm text-surface-600 dark:text-surface-400 mb-4">Toutes les requêtes (sauf <code class="bg-surface-100 dark:bg-surface-800 px-1.5 py-0.5 rounded text-xs font-mono">/connect.php</code>) nécessitent un <strong>sessionId</strong> valide obtenu lors de la connexion. Ce sessionId expire après <strong>1 heure d'inactivité</strong>.</p>
          <div class="bg-surface-900 rounded-xl p-4 font-mono text-sm">
            <span class="text-surface-500">// Inclure dans chaque requête POST :</span><br />
            <span class="text-violet-400">"sessionId"</span><span class="text-surface-300">: </span><span class="text-emerald-400">"abc123def456..."</span>
          </div>
        </div>

        <!-- Endpoints -->
        <div class="space-y-8">
          <div v-for="endpoint in endpoints" :key="endpoint.path" class="glass-panel rounded-2xl p-8">
            <div class="flex flex-wrap items-start gap-3 mb-5">
              <span class="px-3 py-1 rounded-lg text-xs font-bold font-mono" :class="endpoint.method === 'POST' ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'">{{ endpoint.method }}</span>
              <code class="text-surface-900 dark:text-white font-mono font-semibold text-base">/api/{{ endpoint.path }}</code>
              <span class="ml-auto text-sm text-surface-500 dark:text-surface-400">{{ endpoint.desc }}</span>
            </div>

            <!-- Paramètres -->
            <div v-if="endpoint.params.length" class="mb-5">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-surface-500 mb-3">Paramètres</h4>
              <div class="border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                  <thead class="bg-surface-50 dark:bg-surface-800">
                    <tr>
                      <th class="px-4 py-2.5 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Nom</th>
                      <th class="px-4 py-2.5 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Type</th>
                      <th class="px-4 py-2.5 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Requis</th>
                      <th class="px-4 py-2.5 text-left text-xs font-semibold text-surface-500 uppercase tracking-wider">Description</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="param in endpoint.params" :key="param.name" class="border-t border-surface-100 dark:border-surface-800">
                      <td class="px-4 py-2.5 font-mono text-xs text-violet-600 dark:text-violet-400">{{ param.name }}</td>
                      <td class="px-4 py-2.5 font-mono text-xs text-amber-600 dark:text-amber-400">{{ param.type }}</td>
                      <td class="px-4 py-2.5">
                        <span class="text-xs px-1.5 py-0.5 rounded-full" :class="param.required ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400' : 'bg-surface-100 dark:bg-surface-800 text-surface-500'">
                          {{ param.required ? 'Oui' : 'Non' }}
                        </span>
                      </td>
                      <td class="px-4 py-2.5 text-xs text-surface-600 dark:text-surface-400">{{ param.desc }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Exemple de réponse -->
            <div>
              <h4 class="text-xs font-semibold uppercase tracking-wider text-surface-500 mb-3">Réponse (exemple)</h4>
              <div class="bg-surface-900 rounded-xl p-4 font-mono text-xs text-surface-300 overflow-x-auto">
                <pre>{{ endpoint.response }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { Code, Lock } from 'lucide-vue-next'

export default {
  name: 'ApiPage',
  components: { DefaultLayout, Code, Lock },
  data() {
    return {
      endpoints: [
        {
          method: 'POST', path: 'connect.php', desc: 'Établir une connexion à un serveur FTP/SFTP',
          params: [
            { name: 'host', type: 'string', required: true, desc: 'Adresse du serveur (ex: ftp.example.com)' },
            { name: 'port', type: 'integer', required: true, desc: 'Port de connexion (21, 22, 990...)' },
            { name: 'username', type: 'string', required: true, desc: 'Nom d\'utilisateur' },
            { name: 'password', type: 'string', required: true, desc: 'Mot de passe' },
            { name: 'type', type: 'string', required: true, desc: 'Protocole : ftp | ftps | ftpse | sftp' },
            { name: 'passive', type: 'boolean', required: false, desc: 'Mode passif FTP (défaut : true)' },
          ],
          response: `{\n  "success": true,\n  "sessionId": "abc123def456...",\n  "timestamp": "2026-08-06T00:00:00+00:00"\n}`
        },
        {
          method: 'POST', path: 'list.php', desc: 'Lister le contenu d\'un dossier distant',
          params: [
            { name: 'sessionId', type: 'string', required: true, desc: 'Session de connexion active' },
            { name: 'path', type: 'string', required: true, desc: 'Chemin du dossier à lister (ex: /public_html)' },
          ],
          response: `{\n  "success": true,\n  "files": [\n    { "name": "index.php", "size": 2048, "isDirectory": false, "modified": "...", "permissions": "rw-r--r--" },\n    { "name": "images", "size": 0, "isDirectory": true, "modified": "..." }\n  ]\n}`
        },
        {
          method: 'POST', path: 'upload.php', desc: 'Envoyer un fichier sur le serveur distant',
          params: [
            { name: 'sessionId', type: 'string', required: true, desc: 'Session de connexion active' },
            { name: 'remotePath', type: 'string', required: true, desc: 'Dossier de destination sur le serveur' },
            { name: 'remoteName', type: 'string', required: false, desc: 'Nom du fichier sur le serveur (défaut : nom original)' },
            { name: 'file', type: 'file', required: true, desc: 'Le fichier à envoyer (multipart/form-data)' },
          ],
          response: `{\n  "success": true,\n  "file": "mon-fichier.zip",\n  "size": 1048576\n}`
        },
        {
          method: 'POST', path: 'delete.php', desc: 'Supprimer un ou plusieurs fichiers/dossiers',
          params: [
            { name: 'sessionId', type: 'string', required: true, desc: 'Session de connexion active' },
            { name: 'path', type: 'string', required: true, desc: 'Dossier parent contenant les éléments' },
            { name: 'items', type: 'array', required: true, desc: 'Tableau d\'objets [{name, isDirectory}] à supprimer' },
            { name: 'stream', type: 'boolean', required: false, desc: 'Si true, renvoie un flux SSE de progression' },
          ],
          response: `{\n  "success": true,\n  "message": "Items deleted",\n  "timestamp": "2026-08-06T00:00:00+00:00"\n}`
        },
        {
          method: 'POST', path: 'download.php', desc: 'Télécharger un fichier ou dossier distant',
          params: [
            { name: 'sessionId', type: 'string', required: true, desc: 'Session de connexion active' },
            { name: 'remotePath', type: 'string', required: true, desc: 'Chemin du dossier parent' },
            { name: 'remoteName', type: 'string', required: true, desc: 'Nom du fichier ou dossier à télécharger' },
            { name: 'isDirectory', type: 'boolean', required: false, desc: 'Si true, compresse le dossier en .zip avant l\'envoi' },
          ],
          response: `// Réponse binaire (application/octet-stream ou application/zip)\n// Le contenu du fichier est renvoyé directement en flux binaire.`
        },
        {
          method: 'POST', path: 'rename.php', desc: 'Renommer un fichier ou dossier',
          params: [
            { name: 'sessionId', type: 'string', required: true, desc: 'Session de connexion active' },
            { name: 'path', type: 'string', required: true, desc: 'Chemin du dossier parent' },
            { name: 'oldName', type: 'string', required: true, desc: 'Nom actuel de l\'élément' },
            { name: 'newName', type: 'string', required: true, desc: 'Nouveau nom souhaité' },
          ],
          response: `{\n  "success": true,\n  "message": "Renamed successfully"\n}`
        },
        {
          method: 'POST', path: 'disconnect.php', desc: 'Fermer et supprimer la session active',
          params: [
            { name: 'sessionId', type: 'string', required: true, desc: 'Session à fermer' },
          ],
          response: `{\n  "success": true,\n  "message": "Disconnected"\n}`
        },
      ]
    }
  }
}
</script>
