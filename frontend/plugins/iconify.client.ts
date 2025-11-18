import { addCollection } from '@iconify/vue'
import mdi from '@iconify-json/mdi/icons.json'

export default defineNuxtPlugin(() => {
  addCollection(mdi)
})

