<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import TextAlign from '@tiptap/extension-text-align'

interface Props {
  modelValue: string
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Start writing...'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const editor = useEditor({
  content: props.modelValue || '',
  extensions: [
    StarterKit,
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),
  ],
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none min-h-[200px] p-4',
    },
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (editor.value && editor.value.getHTML() !== newValue) {
    editor.value.commands.setContent(newValue || '')
  }
})

</script>

<template>
  <div class="rich-text-editor border border-gray-300 rounded-lg overflow-hidden bg-white">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 p-2 border-b border-gray-200 bg-gray-50">
      <!-- Bold -->
      <button
        type="button"
        @click="editor?.chain().focus().toggleBold().run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive('bold') ? 'bg-gray-300' : ''
        ]"
        title="Bold"
      >
        <Icon name="mdi:format-bold" class="w-5 h-5" />
      </button>

      <!-- Italic -->
      <button
        type="button"
        @click="editor?.chain().focus().toggleItalic().run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive('italic') ? 'bg-gray-300' : ''
        ]"
        title="Italic"
      >
        <Icon name="mdi:format-italic" class="w-5 h-5" />
      </button>

      <!-- Divider -->
      <div class="w-px h-6 bg-gray-300 mx-1"></div>

      <!-- Heading 1 -->
      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive('heading', { level: 1 }) ? 'bg-gray-300' : ''
        ]"
        title="Heading 1"
      >
        <Icon name="mdi:format-header-1" class="w-5 h-5" />
      </button>

      <!-- Heading 2 -->
      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive('heading', { level: 2 }) ? 'bg-gray-300' : ''
        ]"
        title="Heading 2"
      >
        <Icon name="mdi:format-header-2" class="w-5 h-5" />
      </button>

      <!-- Heading 3 -->
      <button
        type="button"
        @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive('heading', { level: 3 }) ? 'bg-gray-300' : ''
        ]"
        title="Heading 3"
      >
        <Icon name="mdi:format-header-3" class="w-5 h-5" />
      </button>

      <!-- Divider -->
      <div class="w-px h-6 bg-gray-300 mx-1"></div>

      <!-- Bullet List -->
      <button
        type="button"
        @click="editor?.chain().focus().toggleBulletList().run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive('bulletList') ? 'bg-gray-300' : ''
        ]"
        title="Bullet List"
      >
        <Icon name="mdi:format-list-bulleted" class="w-5 h-5" />
      </button>

      <!-- Ordered List -->
      <button
        type="button"
        @click="editor?.chain().focus().toggleOrderedList().run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive('orderedList') ? 'bg-gray-300' : ''
        ]"
        title="Numbered List"
      >
        <Icon name="mdi:format-list-numbered" class="w-5 h-5" />
      </button>

      <!-- Divider -->
      <div class="w-px h-6 bg-gray-300 mx-1"></div>

      <!-- Text Alignment -->
      <button
        type="button"
        @click="editor?.chain().focus().setTextAlign('left').run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive({ textAlign: 'left' }) ? 'bg-gray-300' : ''
        ]"
        title="Align Left"
      >
        <Icon name="mdi:format-align-left" class="w-5 h-5" />
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().setTextAlign('center').run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive({ textAlign: 'center' }) ? 'bg-gray-300' : ''
        ]"
        title="Align Center"
      >
        <Icon name="mdi:format-align-center" class="w-5 h-5" />
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().setTextAlign('right').run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive({ textAlign: 'right' }) ? 'bg-gray-300' : ''
        ]"
        title="Align Right"
      >
        <Icon name="mdi:format-align-right" class="w-5 h-5" />
      </button>

      <button
        type="button"
        @click="editor?.chain().focus().setTextAlign('justify').run()"
        :class="[
          'p-2 rounded hover:bg-gray-200 transition-colors',
          editor?.isActive({ textAlign: 'justify' }) ? 'bg-gray-300' : ''
        ]"
        title="Justify"
      >
        <Icon name="mdi:format-align-justify" class="w-5 h-5" />
      </button>
    </div>

    <!-- Editor Content -->
    <div class="min-h-[200px] max-h-[500px] overflow-y-auto relative">
      <EditorContent :editor="editor" />
    </div>
  </div>
</template>

<style>
.rich-text-editor .ProseMirror {
  outline: none;
  min-height: 200px;
}

.rich-text-editor .ProseMirror p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: #9ca3af;
  pointer-events: none;
  height: 0;
}

.rich-text-editor .ProseMirror h1 {
  font-size: 2em;
  font-weight: bold;
  margin-top: 0.67em;
  margin-bottom: 0.67em;
}

.rich-text-editor .ProseMirror h2 {
  font-size: 1.5em;
  font-weight: bold;
  margin-top: 0.83em;
  margin-bottom: 0.83em;
}

.rich-text-editor .ProseMirror h3 {
  font-size: 1.17em;
  font-weight: bold;
  margin-top: 1em;
  margin-bottom: 1em;
}

.rich-text-editor .ProseMirror ul,
.rich-text-editor .ProseMirror ol {
  padding-left: 1.5em;
  margin: 1em 0;
}

.rich-text-editor .ProseMirror ul {
  list-style-type: disc;
}

.rich-text-editor .ProseMirror ol {
  list-style-type: decimal;
}

.rich-text-editor .ProseMirror li {
  margin: 0.5em 0;
}

.rich-text-editor .ProseMirror strong {
  font-weight: bold;
}

.rich-text-editor .ProseMirror em {
  font-style: italic;
}

.rich-text-editor .ProseMirror [style*="text-align: left"] {
  text-align: left;
}

.rich-text-editor .ProseMirror [style*="text-align: center"] {
  text-align: center;
}

.rich-text-editor .ProseMirror [style*="text-align: right"] {
  text-align: right;
}

.rich-text-editor .ProseMirror [style*="text-align: justify"] {
  text-align: justify;
}
</style>

