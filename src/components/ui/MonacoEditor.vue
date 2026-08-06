<template>
  <div ref="container" class="w-full h-full" style="min-height: 300px;"></div>
</template>

<script>
import loader from '@monaco-editor/loader'

export default {
  name: 'MonacoEditor',
  props: {
    modelValue: { type: String, default: '' },
    language: { type: String, default: 'plaintext' },
    theme: { type: String, default: 'vs-dark' },
    readOnly: { type: Boolean, default: false }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      editor: null,
      monaco: null
    }
  },
  mounted() {
    this.initEditor()
  },
  beforeUnmount() {
    if (this.editor) {
      this.editor.dispose()
    }
  },
  watch: {
    modelValue(newVal) {
      if (this.editor && this.editor.getValue() !== newVal) {
        this.editor.setValue(newVal)
      }
    },
    language(newLang) {
      if (this.editor && this.monaco) {
        this.monaco.editor.setModelLanguage(this.editor.getModel(), newLang)
      }
    }
  },
  methods: {
    async initEditor() {
      try {
        this.monaco = await loader.init()
        const isDark = document.documentElement.classList.contains('dark')
        this.editor = this.monaco.editor.create(this.$refs.container, {
          value: this.modelValue,
          language: this.language,
          theme: isDark ? 'vs-dark' : 'vs',
          readOnly: this.readOnly,
          automaticLayout: true,
          minimap: { enabled: false },
          fontSize: 14,
          lineNumbers: 'on',
          scrollBeyondLastLine: false,
          folding: true,
          wordWrap: 'on',
          tabSize: 2,
          padding: { top: 12, bottom: 12 },
          scrollbar: { verticalScrollbarSize: 6, horizontalScrollbarSize: 6 },
          renderLineHighlight: 'gutter',
          smoothScrolling: true,
          cursorBlinking: 'smooth',
          fontFamily: "'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace",
          fontLigatures: true
        })
        this.editor.onDidChangeModelContent(() => {
          this.$emit('update:modelValue', this.editor.getValue())
        })
      } catch (e) {
        console.error('Monaco failed to load:', e)
      }
    }
  }
}
</script>
