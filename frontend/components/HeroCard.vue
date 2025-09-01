<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps({
  imageUrl: {
    type: String,
    required: true
  },
  altText: {
    type: String,
    default: 'Product image'
  },
  heightClass: {
    type: String,
    default: 'h-full'
  }
});

const isHovered = ref(false);
</script>

<template>
  <div 
    class="relative rounded-xl shadow-lg overflow-hidden group w-full"
    :class="heightClass"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <img
      :src="imageUrl"
      :alt="altText"
      class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
    />
    
    <!-- Slide-up overlay with button -->
    <div 
      class="absolute inset-0 bg-black/20 opacity-0 transition-opacity duration-300 flex items-end justify-center"
      :class="{'opacity-100': isHovered}"
    >
      <div 
        class="w-full p-4 transform transition-transform duration-300"
        :class="isHovered ? 'translate-y-0' : 'translate-y-full'"
      >
        <button class="w-full py-3 bg-white text-gray-900 font-medium rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
          View product
        </button>
      </div>
    </div>
  </div>
</template>