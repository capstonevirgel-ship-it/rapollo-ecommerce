<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface EventItem {
  image: string
  title: string
  description: string
}

const events: EventItem[] = [
  {
    image: 'https://plus.unsplash.com/premium_photo-1708589337293-283eca955f45?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
    title: 'Battle Rap Championship',
    description: 'Watch the fiercest wordsmiths go bar-for-bar in a lyrical warzone.',
  },
  {
    image: 'https://images.unsplash.com/photo-1671126923413-ee24aace145d?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
    title: 'Mic Masters Arena',
    description: 'Top-tier emcees showcase their bars in a heated microphone showdown.',
  },
  {
    image: 'https://plus.unsplash.com/premium_photo-1708589337293-283eca955f45?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
    title: 'Lyrical Warfare',
    description: 'Where metaphors, punches, and delivery clash for dominance.',
  },
  {
    image: 'https://images.unsplash.com/photo-1671126923413-ee24aace145d?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D',
    title: 'The Final Cypher',
    description: 'Only legends remain. Who will walk out undefeated?',
  },
]

const currentIndex = ref(0)
const isHovered = ref(false)

let intervalId: ReturnType<typeof setInterval>

const setImage = (index: number) => {
  currentIndex.value = index
}

onMounted(() => {
  intervalId = setInterval(() => {
    currentIndex.value = (currentIndex.value + 1) % events.length
  }, 3000)
})

onUnmounted(() => {
  clearInterval(intervalId)
})
</script>

<template>
  <div class="max-w-4xl mx-auto pt-12 pb-8 relative">
    <div class="flex flex-col md:flex-row gap-6 items-start">
    <!-- Main Image Area with Overlay -->
    <div
        class="relative w-full h-[344px] rounded-2xl overflow-hidden shadow-md group"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
    >
        <img
            :src="events[currentIndex].image"
            :alt="events[currentIndex].title"
            class="w-full h-full object-cover transition-opacity duration-700 ease-in-out"
        />

        <!-- Overlay Content -->
        <div
            class="absolute inset-0 bg-black/50 opacity-0 transition-opacity duration-300 flex items-end justify-center"
            :class="{ 'opacity-100': isHovered }"
        >
            <div
            class="w-full p-6 text-white text-center transform transition-transform duration-300"
            :class="isHovered ? 'translate-y-0' : 'translate-y-full'"
            >
            <h3 class="text-xl font-semibold mb-1">{{ events[currentIndex].title }}</h3>
            <p class="text-sm text-gray-200 mb-4">{{ events[currentIndex].description }}</p>
            <button class="w-full py-2 bg-white text-gray-900 font-medium rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                View Event
            </button>
            </div>
        </div>
    </div>

        <!-- Thumbnails -->
      <div class="flex flex-row md:flex-col gap-2 w-full md:w-[100px] justify-center items-center">
        <template v-for="(event, idx) in events" :key="idx">
          <img
            :src="event.image"
            @click="setImage(idx)"
            :class="[
              'object-cover rounded-lg cursor-pointer transition-all duration-300',
              currentIndex === idx ? 'ring-2 ring-zinc-500 scale-105' : 'opacity-70 hover:opacity-100',
              'w-[calc(25%-6px)] h-[60px] sm:w-[70px] sm:h-[70px] md:w-full md:h-[80px]'
            ]"
            :alt="event.title"
          />
        </template>
      </div>
    </div>
  </div>
</template>


