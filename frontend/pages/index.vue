<script setup lang="ts">
import HeroCard from '@/components/HeroCard.vue';
import Carousel from '@/components/Carousel.vue'; 
import EventGallery from '@/components/EventGallery.vue';
import { useEventStore } from '~/stores/event';
import { useBrandStore } from '~/stores/brand';
import { getImageUrl } from '~/utils/imageHelper';
import { onMounted, reactive, computed } from 'vue';

const eventStore = useEventStore();
const brandStore = useBrandStore();

// Track image load errors for brands
const brandImageError = reactive<Record<number, boolean>>({});

// Duplicate brands array for seamless loop (like the React example)
const allBrands = computed(() => {
  const brands = brandStore.brands;
  return [...brands, ...brands, ...brands]; // Triple for better seamless effect
});

const newArrivals = [
  {
    id: 1,
    title: "Classic White T-Shirt",
    price: 29.99,
    image: "/t-shirt.jpg"
  },
  {
    id: 2,
    title: "Black Premium Tee",
    price: 34.99,
    image: "/t-shirt.jpg"
  },
  {
    id: 3,
    title: "Striped Casual Shirt",
    price: 39.99,
    image: "/t-shirt.jpg"
  },
  {
    id: 4,
    title: "Summer V-Neck",
    price: 27.99,
    image: "/t-shirt.jpg"
  },
  {
    id: 5,
    title: "Oversized Comfort Tee",
    price: 31.99,
    image: "/t-shirt.jpg"
  }
];

const handleProductClick = (product: any) => {
  console.log('Product clicked:', product);
};

// Fetch events and brands on component mount
onMounted(async () => {
  try {
    await Promise.all([
      eventStore.fetchEvents(),
      brandStore.fetchBrands()
    ]);
  } catch (error) {
    console.error('Failed to fetch data:', error);
  }
});
</script>

<template>
  <div class="bg-gray-50">
    <section class="py-[60px]" id="hero-section">
      <div class="mx-auto px-10 flex flex-col md:flex-row items-center justify-around gap-8 max-w-[1440px]">
        <div class="lg:w-1/2 py-6 w-full">
          <div>
            <h1 class="text-4xl font-extrabold text-zinc-900 mb-2">
              Hello World
            </h1>
            <h3 class="text-xl text-gray-700 mb-4">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit.
            </h3>
            <p class="text-gray-500 text-base">
              Lorem ipsum is simply dummy text of the printing and typesetting industry.
            </p>
          </div>
        </div>
        <div class="flex lg:w-1/2 w-full">
          <div class="flex w-full gap-4">
            <div class="w-1/2">
              <HeroCard
                image-url="https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=600&q=80"
                alt-text="Long Hero Image"
                height-class="h-[500px]"
              />
            </div>
            <div class="flex flex-col w-1/2 gap-4">
              <HeroCard
                image-url="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=300&q=80"
                alt-text="Short Hero Image 1"
                height-class="h-[242px]"
              />
              <HeroCard
                image-url="https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=300&q=80"
                alt-text="Short Hero Image 2"
                height-class="h-[242px]"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- New Arrivals Section -->
    <section class="py-[60px] bg-white" id="new-arrivals">
      <div class="mx-auto px-10 max-w-[1440px] flex flex-col items-center">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">New Arrivals</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Discover our latest collection of premium t-shirts and casual wear
          </p>
        </div>

        <!-- Carousel Component -->
        <Carousel
          :items="newArrivals"
          :autoplay="true"
          :autoplay-interval="5000"
          :show-arrows="true"
          :items-to-show="3"
          :items-to-scroll="1"
          @item-click="handleProductClick"
        >
          <template #item="{ item }">
            <div class="p-4 h-full">
              <div class="bg-gray-50 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow h-full flex flex-col justify-center">
                <div class="aspect-square overflow-hidden flex justify-center">
                  <img 
                    :src="item.image" 
                    :alt="item.title" 
                    class="object-cover w-3/4 h-3/4"
                  />
                </div>
                <div class="p-4 flex-grow">
                  <h3 class="font-medium text-gray-900 text-lg mb-1">{{ item.title }}</h3>
                  <p class="text-gray-600">${{ item.price.toFixed(2) }}</p>
                </div>
                <div class="p-4">
                  <button 
                    class="w-full bg-zinc-900 text-white py-2 rounded-md hover:bg-zinc-800 transition-colors cursor-pointer"
                    @click.stop="handleProductClick(item)"
                  >
                    View Details
                  </button>
                </div>
              </div>
            </div>
          </template>
        </Carousel>

        <RouterLink 
          to="#" 
          class="mt-8 inline-block text-center bg-zinc-900 text-white px-6 py-3 rounded-md hover:bg-zinc-800 transition-colors mx-auto"
        >
          View All Products
        </RouterLink>
      </div>
    </section>

    <!-- Brands Section -->
    <section class="py-[60px] bg-gray-50" id="brands">
      <div class="mx-auto px-10 max-w-[1440px]">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Brands</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Discover the premium brands we partner with to bring you the best products
          </p>
        </div>
        
        <!-- Brands Marquee -->
        <div class="relative overflow-hidden">
          <div class="flex gap-16 items-center animate-scroll">
            <div v-for="(brand, index) in allBrands" :key="index" class="flex-shrink-0 w-32 h-16 flex items-center justify-center grayscale hover:grayscale-0 transition-all duration-300 opacity-60 hover:opacity-100">
              <div class="w-24 h-12 bg-white rounded-lg flex items-center justify-center shadow-sm border">
                <img 
                  v-if="!brandImageError[brand.id]"
                  :src="getImageUrl(brand.logo_url, 'brand')"
                  :alt="brand.name"
                  class="w-16 h-8 object-contain"
                  @error="brandImageError[brand.id] = true"
                />
                <img 
                  v-else
                  :src="getImageUrl(null, 'brand')"
                  :alt="brand.name"
                  class="w-16 h-8 object-contain"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="py-[60px] bg-white" id="events">
      <div class="mx-auto px-10 max-w-[1440px] items-center">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Upcoming Events</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Don't miss out on our exciting rap battle events and competitions
          </p>
        </div>
        <EventGallery :events="eventStore.events" :loading="eventStore.loading" />
      </div>
    </section>
  </div>
</template>

<style scoped>
.animate-scroll {
  animation: scroll 45s linear infinite;
  width: max-content;
}

.animate-scroll:hover {
  animation-play-state: paused;
}

@keyframes scroll {
  0% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(-33.333%);
  }
}
</style>