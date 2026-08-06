<template>
  <DefaultLayout>
    <section class="relative pt-24 pb-16 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-surface-0 via-primary-50/20 to-surface-0 dark:from-surface-950 dark:via-primary-900/5 dark:to-surface-950" />
      <div class="relative max-w-6xl mx-auto px-6">
        <!-- Header -->
        <div class="text-center mb-16">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 mb-6">
            <BookOpen class="w-4 h-4 text-primary-500" />
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ $t('nav.docs') }}</span>
          </div>
          <h1 class="text-4xl sm:text-5xl font-bold text-surface-900 dark:text-white mb-4">Documentation NexusFTP</h1>
          <p class="text-lg text-surface-500 dark:text-surface-400 max-w-2xl mx-auto">Tout ce que vous devez savoir pour utiliser NexusFTP et gérer vos fichiers distants depuis votre navigateur.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
          <!-- Sidebar navigation -->
          <aside class="lg:w-64 flex-shrink-0">
            <div class="glass-panel rounded-2xl p-4 sticky top-24">
              <nav class="space-y-1">
                <a v-for="section in sections" :key="section.id" :href="'#' + section.id"
                  class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors cursor-pointer"
                  :class="activeSection === section.id ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 font-medium' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800'"
                  @click="activeSection = section.id">
                  <component :is="section.icon" class="w-4 h-4 flex-shrink-0" />
                  {{ section.label }}
                </a>
              </nav>
            </div>
          </aside>

          <!-- Main content -->
          <main class="flex-1 space-y-12">

            <!-- Démarrage rapide -->
            <div id="quickstart" class="glass-panel rounded-2xl p-8">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-primary-500/10 flex items-center justify-center">
                  <Zap class="w-5 h-5 text-primary-500" />
                </div>
                <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Démarrage Rapide</h2>
              </div>
              <p class="text-surface-600 dark:text-surface-400 mb-6">Pour commencer à utiliser NexusFTP, il vous suffit de disposer des identifiants de connexion à votre serveur FTP ou SFTP.</p>
              <div class="space-y-4">
                <div v-for="(step, i) in quickstartSteps" :key="i" class="flex gap-4">
                  <div class="w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold flex-shrink-0 mt-0.5">{{ i + 1 }}</div>
                  <div>
                    <h3 class="font-semibold text-surface-900 dark:text-white mb-1">{{ step.title }}</h3>
                    <p class="text-sm text-surface-500 dark:text-surface-400">{{ step.desc }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Protocoles -->
            <div id="protocols" class="glass-panel rounded-2xl p-8">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-violet-500/10 flex items-center justify-center">
                  <Globe class="w-5 h-5 text-violet-500" />
                </div>
                <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Protocoles supportés</h2>
              </div>
              <div class="space-y-6">
                <div v-for="proto in protocolDocs" :key="proto.name" class="border border-surface-200 dark:border-surface-700 rounded-xl p-5">
                  <div class="flex items-center gap-3 mb-3">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono" :class="proto.badgeClass">{{ proto.tag }}</span>
                    <h3 class="font-semibold text-surface-900 dark:text-white">{{ proto.name }}</h3>
                    <span class="ml-auto text-xs text-surface-500 font-mono bg-surface-100 dark:bg-surface-800 px-2 py-1 rounded-md">Port {{ proto.port }}</span>
                  </div>
                  <p class="text-sm text-surface-600 dark:text-surface-400 mb-3">{{ proto.desc }}</p>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="tag in proto.tags" :key="tag" class="text-xs px-2 py-1 rounded-full bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-400">{{ tag }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Transfert de fichiers -->
            <div id="transfers" class="glass-panel rounded-2xl p-8">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                  <Upload class="w-5 h-5 text-emerald-500" />
                </div>
                <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Transfert de Fichiers</h2>
              </div>
              <div class="space-y-6">
                <div>
                  <h3 class="font-semibold text-surface-900 dark:text-white mb-2">📤 Envoyer des fichiers (Upload)</h3>
                  <p class="text-sm text-surface-600 dark:text-surface-400 mb-3">Une fois connecté à un serveur, naviguez vers le dossier de destination et utilisez l'une des méthodes suivantes :</p>
                  <ul class="space-y-2 text-sm text-surface-600 dark:text-surface-400">
                    <li class="flex items-start gap-2"><ChevronRight class="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" /><span><strong>Glisser-déposer</strong> : Faites glisser vos fichiers ou dossiers depuis votre explorateur de fichiers directement sur l'interface.</span></li>
                    <li class="flex items-start gap-2"><ChevronRight class="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" /><span><strong>Bouton Envoyer</strong> : Cliquez sur le bouton "Envoyer" dans la barre d'outils pour ouvrir le sélecteur de fichiers.</span></li>
                    <li class="flex items-start gap-2"><ChevronRight class="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" /><span><strong>Dossiers entiers</strong> : Vous pouvez sélectionner un dossier entier, la structure de sous-dossiers sera préservée automatiquement.</span></li>
                  </ul>
                </div>
                <div>
                  <h3 class="font-semibold text-surface-900 dark:text-white mb-2">📥 Télécharger des fichiers (Download)</h3>
                  <p class="text-sm text-surface-600 dark:text-surface-400 mb-3">Sélectionnez un ou plusieurs fichiers dans la liste des fichiers distants et cliquez sur "Télécharger". Les dossiers seront automatiquement compressés en .zip avant le téléchargement.</p>
                </div>
                <div>
                  <h3 class="font-semibold text-surface-900 dark:text-white mb-2">🗑️ Supprimer</h3>
                  <p class="text-sm text-surface-600 dark:text-surface-400">Sélectionnez un ou plusieurs éléments (fichiers ou dossiers) et cliquez sur "Supprimer". La suppression de dossiers efface récursivement tout leur contenu.</p>
                </div>
              </div>
            </div>

            <!-- Connexions sauvegardées -->
            <div id="connections" class="glass-panel rounded-2xl p-8">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                  <Star class="w-5 h-5 text-amber-500" />
                </div>
                <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Connexions Sauvegardées</h2>
              </div>
              <p class="text-sm text-surface-600 dark:text-surface-400 mb-4">Pour ne pas avoir à ressaisir vos identifiants à chaque visite, NexusFTP vous permet de sauvegarder vos connexions. Lors de la création d'une connexion, cochez la case <strong>"Enregistrer cette connexion"</strong>.</p>
              <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                <div class="flex items-start gap-3">
                  <Shield class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                  <p class="text-sm text-amber-800 dark:text-amber-300"><strong>Sécurité :</strong> Vos mots de passe ne sont jamais stockés en clair. Ils sont chiffrés localement via AES avant d'être sauvegardés dans votre navigateur.</p>
                </div>
              </div>
            </div>

            <!-- Raccourcis clavier -->
            <div id="shortcuts" class="glass-panel rounded-2xl p-8">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center">
                  <Terminal class="w-5 h-5 text-rose-500" />
                </div>
                <h2 class="text-2xl font-bold text-surface-900 dark:text-white">Astuces & Raccourcis</h2>
              </div>
              <div class="space-y-3">
                <div v-for="tip in tips" :key="tip.key" class="flex items-center justify-between py-2.5 border-b border-surface-100 dark:border-surface-800 last:border-0">
                  <span class="text-sm text-surface-600 dark:text-surface-400">{{ tip.desc }}</span>
                  <kbd class="px-2.5 py-1 text-xs font-mono bg-surface-100 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-lg text-surface-700 dark:text-surface-300">{{ tip.key }}</kbd>
                </div>
              </div>
            </div>

          </main>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { BookOpen, Zap, Globe, Upload, Star, Shield, Terminal, ChevronRight } from 'lucide-vue-next'

export default {
  name: 'DocsPage',
  components: { DefaultLayout, BookOpen, Zap, Globe, Upload, Star, Shield, Terminal, ChevronRight },
  data() {
    return {
      activeSection: 'quickstart',
      sections: [
        { id: 'quickstart', label: 'Démarrage Rapide', icon: 'Zap' },
        { id: 'protocols', label: 'Protocoles', icon: 'Globe' },
        { id: 'transfers', label: 'Transferts', icon: 'Upload' },
        { id: 'connections', label: 'Connexions sauvegardées', icon: 'Star' },
        { id: 'shortcuts', label: 'Astuces', icon: 'Terminal' },
      ],
      quickstartSteps: [
        { title: 'Cliquez sur "Connexion"', desc: 'Depuis la page d\'accueil ou la barre de navigation, cliquez sur le bouton "Connexion" pour accéder au formulaire de connexion.' },
        { title: 'Saisissez vos identifiants', desc: 'Entrez l\'adresse de votre serveur (ex: ftp.monsite.com), votre nom d\'utilisateur, votre mot de passe et le port (21 pour FTP/FTPS, 22 pour SFTP).' },
        { title: 'Choisissez le protocole', desc: 'Sélectionnez FTP, FTPS (Implicite), FTPES (Explicite) ou SFTP selon ce que supporte votre hébergeur.' },
        { title: 'Connectez-vous !', desc: 'Cliquez sur "Connecter". En quelques secondes, vous accédez au gestionnaire de fichiers distants.' },
      ],
      protocolDocs: [
        { tag: 'FTP', name: 'FTP — File Transfer Protocol', port: '21', desc: 'Le protocole de transfert de fichiers standard. Rapide et universel, compatible avec tous les hébergeurs web. Les données ne sont pas chiffrées.', badgeClass: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400', tags: ['Non chiffré', 'Port 21', 'Universel'] },
        { tag: 'FTPS', name: 'FTPS — FTP over SSL/TLS (Implicite)', port: '990', desc: 'La connexion est immédiatement chiffrée dès le premier octet. Le serveur s\'attend à une connexion SSL dès le départ sur le port 990.', badgeClass: 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400', tags: ['Chiffré SSL/TLS', 'Port 990', 'Mode Implicite'] },
        { tag: 'FTPE', name: 'FTPES — FTP over SSL/TLS (Explicite)', port: '21', desc: 'La connexion démarre sur le port 21 standard (non chiffrée), puis envoie une commande AUTH TLS pour basculer en mode chiffré. Le plus utilisé par les hébergeurs modernes.', badgeClass: 'bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400', tags: ['Chiffré SSL/TLS', 'Port 21', 'Mode Explicite', 'Recommandé'] },
        { tag: 'SFTP', name: 'SFTP — SSH File Transfer Protocol', port: '22', desc: 'Protocole entièrement différent basé sur SSH. Le plus sécurisé des quatre. Chiffré de bout en bout et largement utilisé sur les serveurs Linux/VPS.', badgeClass: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400', tags: ['Chiffré SSH', 'Port 22', 'Linux/VPS', 'Le plus sécurisé'] },
      ],
      tips: [
        { key: 'Ctrl + A', desc: 'Sélectionner tous les fichiers' },
        { key: 'Suppr', desc: 'Supprimer les fichiers sélectionnés' },
        { key: 'F5', desc: 'Actualiser la liste des fichiers' },
        { key: 'Double-clic', desc: 'Ouvrir un dossier / Prévisualiser un fichier' },
        { key: 'Clic + Maj', desc: 'Sélection multiple en plage' },
        { key: 'Clic + Ctrl', desc: 'Ajouter un fichier à la sélection' },
      ]
    }
  }
}
</script>
