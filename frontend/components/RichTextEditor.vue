<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
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
    Image.extend({
      addAttributes() {
        return {
          ...this.parent?.(),
          width: {
            default: null,
            renderHTML: attributes => {
              if (!attributes.width) {
                return {}
              }
              return {
                width: attributes.width,
              }
            },
            parseHTML: element => element.getAttribute('width'),
          },
          height: {
            default: null,
            renderHTML: attributes => {
              if (!attributes.height) {
                return {}
              }
              return {
                height: attributes.height,
              }
            },
            parseHTML: element => element.getAttribute('height'),
          },
        }
      },
      addNodeView() {
        return ({ node, HTMLAttributes, getPos, editor }) => {
          const dom = document.createElement('div')
          dom.className = 'image-wrapper'
          dom.style.cssText = 'position: relative; display: inline-block; margin: 1rem 0;'
          
          const img = document.createElement('img')
          img.src = node.attrs.src
          img.alt = node.attrs.alt || ''
          img.draggable = false
          img.style.cssText = 'max-width: 100%; height: auto; display: block; vertical-align: middle;'
          
          if (node.attrs.width) {
            img.style.width = `${node.attrs.width}px`
          }
          if (node.attrs.height) {
            img.style.height = `${node.attrs.height}px`
          }
          
          // Create resize handle container
          const handle = document.createElement('div')
          handle.className = 'resize-handle'
          handle.setAttribute('contenteditable', 'false')
          handle.style.cssText = `
            position: absolute;
            bottom: 10px;
            right: -10px;
            width: 16px;
            height: 16px;
            background: #3b82f6;
            border: 2px solid white;
            border-radius: 3px 0 0 0;
            cursor: nwse-resize;
            z-index: 1000;
            box-shadow: -2px -2px 4px rgba(0, 0, 0, 0.3);
            pointer-events: auto;
            user-select: none;
          `
          
          // Add hover effect via style
          handle.addEventListener('mouseenter', () => {
            handle.style.background = '#2563eb'
            handle.style.transform = 'scale(1.1)'
          })
          handle.addEventListener('mouseleave', () => {
            handle.style.background = '#3b82f6'
            handle.style.transform = 'scale(1)'
          })
          
          let isResizing = false
          let startX = 0
          let startWidth = 0
          let aspectRatio = 0
          
          const handleMouseDown = (e: MouseEvent) => {
            e.preventDefault()
            e.stopPropagation()
            isResizing = true
            startX = e.clientX
            startWidth = img.offsetWidth
            aspectRatio = img.offsetWidth / img.offsetHeight
            
            const handleMouseMove = (e: MouseEvent) => {
              if (!isResizing) return
              e.preventDefault()
              const deltaX = e.clientX - startX
              const maxWidth = dom.parentElement?.clientWidth || 800
              const newWidth = Math.max(50, Math.min(startWidth + deltaX, maxWidth - 20))
              const newHeight = newWidth / aspectRatio
              
              img.style.width = `${newWidth}px`
              img.style.height = `${newHeight}px`
            }
            
            const handleMouseUp = () => {
              if (!isResizing) return
              isResizing = false
              
              const pos = getPos()
              if (typeof pos === 'number' && editor) {
                editor.commands.updateAttributes('image', {
                  width: img.offsetWidth,
                  height: img.offsetHeight,
                })
              }
              
              document.removeEventListener('mousemove', handleMouseMove)
              document.removeEventListener('mouseup', handleMouseUp)
            }
            
            document.addEventListener('mousemove', handleMouseMove)
            document.addEventListener('mouseup', handleMouseUp, { once: true })
          }
          
          handle.addEventListener('mousedown', handleMouseDown)
          
          dom.appendChild(img)
          dom.appendChild(handle)
          
          return {
            dom,
            update: (updatedNode) => {
              if (updatedNode.type.name !== 'image') return false
              img.src = updatedNode.attrs.src
              if (updatedNode.attrs.width) {
                img.style.width = `${updatedNode.attrs.width}px`
              } else {
                img.style.width = ''
              }
              if (updatedNode.attrs.height) {
                img.style.height = `${updatedNode.attrs.height}px`
              } else {
                img.style.height = ''
              }
              return true
            },
          }
        }
      },
    }).configure({
      inline: false,
      allowBase64: false,
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

// Image upload handler
const handleImageUpload = async (file: File) => {
  if (!file) return

  const formData = new FormData()
  formData.append('image', file)

  try {
    const response = await $fetch<{ success: boolean; url: string }>('/api/upload/event-content', {
      method: 'POST',
      body: formData,
    })

    if (response.success && response.url && editor.value) {
      editor.value.chain().focus().setImage({ src: response.url }).run()
    }
  } catch (error) {
    console.error('Error uploading image:', error)
    // You might want to show an error notification here
  }
}

// Handle image paste/drop
const handleImageInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    handleImageUpload(target.files[0])
    target.value = '' // Reset input
  }
}

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

      <!-- Divider -->
      <div class="w-px h-6 bg-gray-300 mx-1"></div>

      <!-- Image Upload -->
      <label class="p-2 rounded hover:bg-gray-200 transition-colors cursor-pointer" title="Insert Image">
        <Icon name="mdi:image-outline" class="w-5 h-5" />
        <input
          type="file"
          accept="image/*"
          class="hidden"
          @change="handleImageInput"
        />
      </label>
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

.rich-text-editor .ProseMirror .image-wrapper {
  position: relative;
  display: inline-block;
  margin: 1rem 0;
  max-width: 100%;
}

.rich-text-editor .ProseMirror .image-wrapper:hover {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
  border-radius: 0.375rem;
}

.rich-text-editor .ProseMirror .image-wrapper img {
  max-width: 100%;
  height: auto;
  border-radius: 0.375rem;
  display: block;
  user-select: none;
  pointer-events: none;
}

.rich-text-editor .ProseMirror .image-wrapper .resize-handle {
  position: absolute;
  bottom: 10px;
  right: -10px;
  width: 16px;
  height: 16px;
  background: #3b82f6;
  border: 2px solid white;
  border-radius: 3px 0 0 0;
  cursor: nwse-resize;
  z-index: 1000;
  box-shadow: -2px -2px 4px rgba(0, 0, 0, 0.3);
  pointer-events: auto;
  user-select: none;
  transition: background 0.2s, transform 0.2s;
}

.rich-text-editor .ProseMirror .image-wrapper .resize-handle:hover {
  background: #2563eb;
  transform: scale(1.1);
}

.rich-text-editor .ProseMirror .image-wrapper .resize-handle:active {
  background: #1d4ed8;
  transform: scale(0.95);
}

.rich-text-editor .ProseMirror img {
  max-width: 100%;
  height: auto;
  border-radius: 0.375rem;
  margin: 1rem 0;
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

