<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

interface CarouselItem {
  id: number
  [key: string]: any
}

const props = defineProps({
  items: {
    type: Array as () => CarouselItem[],
    required: true
  },
  breakpoints: {
    type: Object,
    default: () => ({
      sm: 640,
      md: 768,
      lg: 1024,
      xl: 1280
    })
  },
  showArrows: {
    type: Boolean,
    default: true
  },
  autoplay: {
    type: Boolean,
    default: false
  },
  autoplayInterval: {
    type: Number,
    default: 3000
  },
  itemsToShow: {
    type: Number,
    default: 1
  },
  itemsToScroll: {
    type: Number,
    default: 1
  },
  transitionDuration: {
    type: Number,
    default: 500
  }
})

const emit = defineEmits(['item-click'])

const currentIndex = ref(0)
const isDragging = ref(false)
const startPos = ref(0)
const currentTranslate = ref(0)
const prevTranslate = ref(0)
const isHovered = ref(false)
const animationFrame = ref<number | null>(null)
const lastAnimationTime = ref(0)
const autoplayTimer = ref<NodeJS.Timeout | null>(null)

const carouselWrapperRef = ref<HTMLElement | null>(null)
const carouselTrackRef = ref<HTMLElement | null>(null)

const windowWidth = ref(0)

// Only clone items if we have more items than what we're showing (for infinite scroll)
// Otherwise, just use the items as-is to avoid duplicates
const clonedItems = computed(() => {
  if (props.items.length <= responsiveItemsToShow.value) {
    // Not enough items for infinite scroll, return items as-is
    return props.items
  }
  // Enough items for infinite scroll, clone for seamless looping
  return [...props.items, ...props.items, ...props.items]
})

const actualIndex = computed(() => currentIndex.value % props.items.length)

const responsiveItemsToShow = computed(() => {
  const width = windowWidth.value
  if (width >= props.breakpoints.xl) return props.itemsToShow
  if (width >= props.breakpoints.lg) return Math.min(props.itemsToShow, 3)
  if (width >= props.breakpoints.md) return Math.min(props.itemsToShow, 2)
  return 1
})

const itemWidth = computed(() => carouselWrapperRef.value?.clientWidth! / responsiveItemsToShow.value)

const trackWidth = computed(() => itemWidth.value * clonedItems.value.length)

const trackTransform = computed(() => `translateX(${-currentIndex.value * itemWidth.value + currentTranslate.value}px)`)

const transitionStyle = computed(() => 
  `transform ${props.transitionDuration}ms cubic-bezier(0.25, 0.46, 0.45, 0.94)`
)

const handleResize = () => {
  windowWidth.value = window.innerWidth
}

const startAutoplay = () => {
  if (!props.autoplay || isHovered.value) return
  
  stopAutoplay()
  
  const play = (timestamp: number) => {
    if (!lastAnimationTime.value) {
      lastAnimationTime.value = timestamp
    }
    
    const elapsed = timestamp - lastAnimationTime.value
    
    if (elapsed >= props.autoplayInterval) {
      next()
      lastAnimationTime.value = timestamp
    }
    
    animationFrame.value = requestAnimationFrame(play)
  }
  
  animationFrame.value = requestAnimationFrame(play)
}

const stopAutoplay = () => {
  if (animationFrame.value) {
    cancelAnimationFrame(animationFrame.value)
    animationFrame.value = null
  }
  if (autoplayTimer.value) {
    clearInterval(autoplayTimer.value)
    autoplayTimer.value = null
  }
  lastAnimationTime.value = 0
}

const updateTransform = () => {
  if (carouselTrackRef.value) {
    carouselTrackRef.value.style.transform = `translateX(${-currentIndex.value * itemWidth.value}px)`
  }
}

const next = () => {
  if (isDragging.value) return
  
  // If not enough items for infinite scroll, just loop normally
  if (props.items.length <= responsiveItemsToShow.value) {
    currentIndex.value = (currentIndex.value + props.itemsToScroll) % props.items.length
    updateTransform()
    return
  }
  
  const animate = () => {
    currentIndex.value += props.itemsToScroll
    updateTransform()

    if (currentIndex.value >= props.items.length * 2) {
      setTimeout(() => {
        if (!carouselTrackRef.value) return
        carouselTrackRef.value.style.transition = 'none'
        currentIndex.value = props.items.length
        updateTransform()
        // Force reflow before restoring transition
        void carouselTrackRef.value.offsetHeight
        carouselTrackRef.value.style.transition = transitionStyle.value
      }, props.transitionDuration)
    }
  }
  
  requestAnimationFrame(animate)
}

const prev = () => {
  if (isDragging.value) return
  
  // If not enough items for infinite scroll, just loop normally
  if (props.items.length <= responsiveItemsToShow.value) {
    currentIndex.value = (currentIndex.value - props.itemsToScroll + props.items.length) % props.items.length
    updateTransform()
    return
  }
  
  const animate = () => {
    currentIndex.value -= props.itemsToScroll
    updateTransform()

    if (currentIndex.value < props.items.length) {
      setTimeout(() => {
        if (!carouselTrackRef.value) return
        carouselTrackRef.value.style.transition = 'none'
        currentIndex.value = props.items.length * 2 - 1
        updateTransform()
        // Force reflow before restoring transition
        void carouselTrackRef.value.offsetHeight
        carouselTrackRef.value.style.transition = transitionStyle.value
      }, props.transitionDuration)
    }
  }
  
  requestAnimationFrame(animate)
}

const goTo = (index: number) => {
  if (isDragging.value) return
  currentIndex.value = index + props.items.length
  updateTransform()
}

const getPositionX = (e: TouchEvent | MouseEvent): number =>
  e.type.includes('touch')
    ? (e as TouchEvent).touches[0].clientX
    : (e as MouseEvent).clientX

const touchStart = (e: TouchEvent | MouseEvent) => {
  stopAutoplay()
  isDragging.value = true
  startPos.value = getPositionX(e)
  prevTranslate.value = -currentIndex.value * itemWidth.value
  currentTranslate.value = 0

  // Prevent default for mouse events to avoid text selection
  if (e.type === 'mousedown') {
    e.preventDefault()
  }

  if (carouselTrackRef.value) {
    carouselTrackRef.value.style.cursor = 'grabbing'
    carouselTrackRef.value.style.transition = 'none'
    carouselTrackRef.value.style.willChange = 'transform'
  }
}

const touchMove = (e: TouchEvent | MouseEvent) => {
  if (!isDragging.value) return
  
  // Prevent default to stop scrolling during drag
  e.preventDefault()
  
  const currentPosition = getPositionX(e)
  currentTranslate.value = currentPosition - startPos.value
  if (carouselTrackRef.value) {
    carouselTrackRef.value.style.transform = `translateX(${-currentIndex.value * itemWidth.value + currentTranslate.value}px)`
  }
}

const touchEnd = () => {
  if (!isDragging.value) return
  isDragging.value = false
  
  if (carouselTrackRef.value) {
    carouselTrackRef.value.style.transition = transitionStyle.value
    carouselTrackRef.value.style.cursor = 'grab'
    carouselTrackRef.value.style.willChange = 'auto'
  }

  const movedBy = currentTranslate.value
  const threshold = 50
  
  if (Math.abs(movedBy) > threshold) {
    if (movedBy < -threshold) {
      // Swiped left - go to next
      if (props.items.length <= responsiveItemsToShow.value) {
        currentIndex.value = (currentIndex.value + props.itemsToScroll) % props.items.length
      } else {
        currentIndex.value += props.itemsToScroll
      }
    } else if (movedBy > threshold) {
      // Swiped right - go to previous
      if (props.items.length <= responsiveItemsToShow.value) {
        currentIndex.value = (currentIndex.value - props.itemsToScroll + props.items.length) % props.items.length
      } else {
        currentIndex.value -= props.itemsToScroll
      }
    }
    
    // Handle infinite scroll wrap-around (only if we have enough items)
    if (props.items.length > responsiveItemsToShow.value) {
      if (currentIndex.value >= props.items.length * 2) {
        setTimeout(() => {
          if (!carouselTrackRef.value) return
          carouselTrackRef.value.style.transition = 'none'
          currentIndex.value = props.items.length
          updateTransform()
          void carouselTrackRef.value.offsetHeight
          carouselTrackRef.value.style.transition = transitionStyle.value
        }, props.transitionDuration)
      } else if (currentIndex.value < props.items.length) {
        setTimeout(() => {
          if (!carouselTrackRef.value) return
          carouselTrackRef.value.style.transition = 'none'
          currentIndex.value = props.items.length * 2 - 1
          updateTransform()
          void carouselTrackRef.value.offsetHeight
          carouselTrackRef.value.style.transition = transitionStyle.value
        }, props.transitionDuration)
      }
    }
    
    updateTransform()
  }

  currentTranslate.value = 0
  updateTransform()
  startAutoplay()
}

const handleItemClick = (item: CarouselItem, index: number) => {
  if (Math.abs(currentTranslate.value) > 10) return
  emit('item-click', { item, index: index % props.items.length })
}

const onMouseEnter = () => {
  isHovered.value = true
  stopAutoplay()
}

const onMouseLeave = () => {
  isHovered.value = false
  startAutoplay()
}

onMounted(() => {
  windowWidth.value = window.innerWidth
  window.addEventListener('resize', handleResize)
  // Only set to middle if we have enough items for infinite scroll
  if (props.items.length > responsiveItemsToShow.value) {
    currentIndex.value = props.items.length
  } else {
    currentIndex.value = 0
  }
  
  // Use nextTick to ensure refs are ready
  nextTick(() => {
    updateTransform()
    startAutoplay()

    if (carouselWrapperRef.value) {
      carouselWrapperRef.value.addEventListener('mouseenter', onMouseEnter)
      carouselWrapperRef.value.addEventListener('mouseleave', onMouseLeave)
    }

    if (carouselTrackRef.value) {
      // Touch events - passive: false to allow preventDefault
      carouselTrackRef.value.addEventListener('touchstart', touchStart, { passive: false })
      carouselTrackRef.value.addEventListener('touchmove', touchMove, { passive: false })
      carouselTrackRef.value.addEventListener('touchend', touchEnd)
      
      // Mouse events - need preventDefault
      carouselTrackRef.value.addEventListener('mousedown', touchStart)
      carouselTrackRef.value.addEventListener('mousemove', touchMove)
      carouselTrackRef.value.addEventListener('mouseup', touchEnd)
      carouselTrackRef.value.addEventListener('mouseleave', touchEnd)
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  stopAutoplay()
  
  if (carouselWrapperRef.value) {
    carouselWrapperRef.value.removeEventListener('mouseenter', onMouseEnter)
    carouselWrapperRef.value.removeEventListener('mouseleave', onMouseLeave)
  }

  if (carouselTrackRef.value) {
    carouselTrackRef.value.removeEventListener('touchstart', touchStart)
    carouselTrackRef.value.removeEventListener('mousedown', touchStart)
    carouselTrackRef.value.removeEventListener('touchmove', touchMove)
    carouselTrackRef.value.removeEventListener('mousemove', touchMove)
    carouselTrackRef.value.removeEventListener('touchend', touchEnd)
    carouselTrackRef.value.removeEventListener('mouseup', touchEnd)
    carouselTrackRef.value.removeEventListener('mouseleave', touchEnd)
  }
})
</script>

<template>
  <div 
    ref="carouselWrapperRef" 
    class="relative w-full overflow-hidden group"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <div
      ref="carouselTrackRef"
      class="flex touch-pan-y cursor-grab"
      :style="{
        width: `${trackWidth}px`,
        transform: trackTransform,
        transition: isDragging ? 'none' : transitionStyle,
        willChange: isDragging ? 'transform' : 'auto'
      }"
    >
      <div
        v-for="(item, index) in clonedItems"
        :key="`${item.id}-${index}`"
        class="flex-shrink-0 px-2"
        :style="{ width: `${itemWidth}px` }"
        @click="handleItemClick(item, index)"
      >
        <slot name="item" :item="item" :index="index % items.length">
          <div class="h-full bg-gray-100 rounded-lg flex items-center justify-center">
            {{ item }}
          </div>
        </slot>
      </div>
    </div>

    <template v-if="showArrows && items.length > responsiveItemsToShow">
      <button
        class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 rounded-full p-2 shadow-md transition-opacity opacity-0 group-hover:opacity-100 z-10"
        @click="prev"
        aria-label="Previous slide"
      >
        <Icon name="mdi:chevron-left" class="w-6 h-6" />
      </button>
      <button
        class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-gray-800 rounded-full p-2 shadow-md transition-opacity opacity-0 group-hover:opacity-100 z-10"
        @click="next"
        aria-label="Next slide"
      >
        <Icon name="mdi:chevron-right" class="w-6 h-6" />
      </button>
    </template>

    <div v-if="items.length > 1" class="flex justify-center mt-4 space-x-2">
      <button
        v-for="(item, index) in items"
        :key="`indicator-${item.id}`"
        class="w-3 h-3 rounded-full transition-all"
        :class="{
          'bg-gray-800 w-6': actualIndex === index,
          'bg-gray-300': actualIndex !== index
        }"
        @click="goTo(index)"
        :aria-label="`Go to slide ${index + 1}`"
        :aria-current="actualIndex === index ? 'true' : 'false'"
      />
    </div>
  </div>
</template>