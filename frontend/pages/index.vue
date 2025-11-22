<script setup lang="ts">
import HeroCard from '@/components/HeroCard.vue';
import Carousel from '@/components/Carousel.vue'; 
import EventGallery from '@/components/EventGallery.vue';
import ProductGallery from '@/components/ProductGallery.vue';
import HeroSkeleton from '@/components/skeleton/HeroSkeleton.vue';
import NewArrivalSkeleton from '@/components/skeleton/NewArrivalSkeleton.vue';
import { useEventStore } from '~/stores/event';
import { useBrandStore } from '~/stores/brand';
import { useProductStore } from '~/stores/product';
import { useSettingsStore } from '~/stores/settings';
import { useAuthStore } from '~/stores/auth';
import { getImageUrl, getPrimaryImage } from '~/utils/imageHelper';
import { onMounted, reactive, computed, ref } from 'vue';

const eventStore = useEventStore();
const brandStore = useBrandStore();
const productStore = useProductStore();
const settingsStore = useSettingsStore();
const authStore = useAuthStore();

// Track image load errors for brands
const brandImageError = reactive<Record<number, boolean>>({});

// Duplicate brands array for seamless loop (like the React example)
const allBrands = computed(() => {
  const brands = brandStore.brands;
  return [...brands, ...brands, ...brands]; // Triple for better seamless effect
});

// New arrivals are now stored in the ref above

const handleProductClick = (product: any) => {
  console.log('Product clicked:', product);
};

// Separate data for featured, hot, and new products
const featuredProducts = ref<any[]>([]);
const hotProducts = ref<any[]>([]);
const newArrivals = ref<any[]>([]);

// Loading states for each section
const isLoadingHero = ref(true);
const isLoadingNewArrivals = ref(true);

const brandActions = brandStore as unknown as {
  fetchBrands: () => Promise<unknown>
}

// Fetch events, brands, featured products, hot products, new arrivals, and settings on component mount
onMounted(async () => {
  try {
    // Only fetch data on client side
    if (process.client) {
      // Fetch all data in parallel for better performance
      const promises = [
        eventStore.fetchEvents(),
        brandActions.fetchBrands(),
        settingsStore.fetchSettings(),
        productStore.fetchFeaturedProducts(),
        productStore.fetchTrendingProducts()
      ];
      
      // Wait for all critical data to load
      const results = await Promise.allSettled(promises);
      
      // Set featured products (from optimized endpoint)
      const featuredResult = results[3];
      if (featuredResult.status === 'fulfilled') {
        featuredProducts.value = featuredResult.value;
      }
      isLoadingHero.value = false;
      
      // Set hot/trending products (from optimized endpoint)
      const trendingResult = results[4];
      if (trendingResult.status === 'fulfilled') {
        hotProducts.value = trendingResult.value;
      }
      
      // Fetch new products (below the fold, can load separately)
      isLoadingNewArrivals.value = true;
      productStore.fetchNewArrivals()
        .then((data) => {
          newArrivals.value = data;
          isLoadingNewArrivals.value = false;
        })
        .catch(() => {
          isLoadingNewArrivals.value = false;
        });
    } else {
      // On server side, just set loading to false
      isLoadingHero.value = false;
      isLoadingNewArrivals.value = false;
    }
  } catch (error) {
    console.error('Failed to fetch data:', error);
    isLoadingHero.value = false;
    isLoadingNewArrivals.value = false;
  }
});

// Featured products are now stored in the ref above

// Set page title dynamically
useHead({
  title: computed(() => `${settingsStore.siteName || 'RAPOLLO'} | Rap Battle Merchandise & Event Tickets`),
  meta: [
    { name: 'description', content: computed(() => settingsStore.siteAbout || 'Welcome to our e-commerce store. We offer quality products at affordable prices.') },
    { name: 'keywords', content: 'rap battle, merchandise, event tickets, Philippines, rap culture, clothing, apparel' }
  ]
})
</script>

<template>
  <div>
    <section class="py-[60px] bg-white" id="hero-section">
      <div class="mx-auto px-10 max-w-[1440px]">
        <!-- Hero Skeleton -->
        <HeroSkeleton v-if="isLoadingHero" />
        
        <!-- Hero Content -->
        <div v-else class="flex flex-col md:flex-row items-center justify-around gap-8">
          <div class="lg:w-1/2 py-6 w-full">
            <div>
              <h1 class="text-6xl font-extrabold text-zinc-900 mb-2 font-winner-extra-bold">
                {{ settingsStore.siteName || 'Rapollo E-Commerce' }}
              </h1>
              <h3 class="text-xl text-gray-700 mb-4">
                Rap battle in the Philippines - Buy tickets and merchandises
              </h3>
              <p class="text-gray-500 text-base">
                Experience the ultimate rap battle culture with exclusive merchandise and event tickets
              </p>
              <div class="mt-6">
                <NuxtLink 
                  to="/shop" 
                  class="inline-flex items-center justify-center px-6 py-3 bg-zinc-900 text-white font-semibold rounded-lg hover:bg-zinc-800 transition-colors"
                >
                  View Products
                  <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </NuxtLink>
              </div>
            </div>
          </div>
          <div class="flex lg:w-1/2 w-full">
            <div class="flex w-full gap-4">
            <!-- Featured Product 1 (Large) -->
            <div class="w-1/2" v-if="featuredProducts[0]">
              <div 
                class="relative h-[500px] rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow cursor-pointer group"
                @click="() => navigateTo(`/shop/${featuredProducts[0].category_slug}/${featuredProducts[0].subcategory_slug}/${featuredProducts[0].slug}`)"
              >
                <img 
                  :src="getImageUrl(featuredProducts[0].image || null)" 
                  :alt="featuredProducts[0].name"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-300 flex items-end">
                  <div class="p-6 w-full transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                    <button class="w-full bg-white text-zinc-900 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                      View Product
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Fallback for no featured products -->
            <div class="w-1/2" v-else>
              <div class="relative h-[500px] rounded-lg overflow-hidden shadow-lg bg-gray-200 flex items-center justify-center">
                <div class="text-center text-gray-500">
                  <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  <p class="text-lg font-medium">No featured products yet</p>
                </div>
              </div>
            </div>
            
            <!-- Featured Products 2 & 3 (Small) -->
            <div class="flex flex-col w-1/2 gap-4">
              <div 
                v-if="featuredProducts[1]"
                class="relative h-[242px] rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow cursor-pointer group"
                @click="() => navigateTo(`/shop/${featuredProducts[1].category_slug}/${featuredProducts[1].subcategory_slug}/${featuredProducts[1].slug}`)"
              >
                <img 
                  :src="getImageUrl(featuredProducts[1].image || null)" 
                  :alt="featuredProducts[1].name"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-300 flex items-end">
                  <div class="p-4 w-full transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                    <button class="w-full bg-white text-zinc-900 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors text-sm">
                      View Product
                    </button>
                  </div>
                </div>
              </div>
              <!-- Fallback for second product -->
              <div v-else class="relative h-[242px] rounded-lg overflow-hidden shadow-lg bg-gray-200 flex items-center justify-center">
                <div class="text-center text-gray-500">
                  <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  <p class="text-sm">Coming Soon</p>
                </div>
              </div>
              
              <div 
                v-if="featuredProducts[2]"
                class="relative h-[242px] rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow cursor-pointer group"
                @click="() => navigateTo(`/shop/${featuredProducts[2].category_slug}/${featuredProducts[2].subcategory_slug}/${featuredProducts[2].slug}`)"
              >
                <img 
                  :src="getImageUrl(featuredProducts[2].image || null)" 
                  :alt="featuredProducts[2].name"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-300 flex items-end">
                  <div class="p-4 w-full transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 opacity-0 group-hover:opacity-100">
                    <button class="w-full bg-white text-zinc-900 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors text-sm">
                      View Product
                    </button>
                  </div>
                </div>
              </div>
              <!-- Fallback for third product -->
              <div v-else class="relative h-[242px] rounded-lg overflow-hidden shadow-lg bg-gray-200 flex items-center justify-center">
                <div class="text-center text-gray-500">
                  <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  <p class="text-sm">Coming Soon</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </section>

    <!-- Hot Products Section -->
    <section class="py-[60px] bg-gray-100" id="hot-products">
      <div class="mx-auto px-10 max-w-[1440px] items-center">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4 font-winner-extra-bold">Trending Products</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Check out our most popular items right now
          </p>
        </div>
        <ProductGallery :products="hotProducts" :loading="productStore.loading" />
      </div>
    </section>

    <!-- New Arrivals Section -->
    <section class="py-[60px] bg-white" id="new-arrivals">
      <div class="mx-auto px-10 max-w-[1440px] flex flex-col items-center">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4 font-winner-extra-bold">New Arrivals</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Discover our latest collection of premium t-shirts and casual wear
          </p>
        </div>

        <!-- New Arrivals Skeleton -->
        <NewArrivalSkeleton v-if="isLoadingNewArrivals" />

        <!-- Carousel Component -->
        <template v-else>
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
                  <div class="aspect-square overflow-hidden">
                    <img 
                      :src="getImageUrl(item.image || getPrimaryImage(item.images) || null)" 
                      :alt="item.name" 
                      class="object-cover w-full h-full"
                    />
                  </div>
                  <div class="p-4 flex-grow">
                    <h3 class="font-medium text-gray-900 text-lg mb-1">{{ item.name }}</h3>
                    <p class="text-gray-600">₱{{ (item.price || 0).toFixed(2) }}</p>
                  </div>
                  <div class="p-4">
                    <button 
                      class="w-full bg-zinc-900 text-white py-2 rounded-md hover:bg-zinc-800 transition-colors cursor-pointer"
                      @click.stop="() => navigateTo(`/shop/${item.category_slug || item.subcategory?.category?.slug}/${item.subcategory_slug || item.subcategory?.slug}/${item.slug}`)"
                    >
                      View Details
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </Carousel>

          <NuxtLink
            to="/shop?is_new=true"
            class="mt-8 inline-flex items-center px-6 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors"
          >
            View All New Arrivals
            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </NuxtLink>
        </template>
      </div>
    </section>

    <!-- Brands Section -->
    <section class="py-[60px] bg-gray-100" id="brands">
      <div class="mx-auto px-10 max-w-[1440px]">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4 font-winner-extra-bold">Our Brands</h2>
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
                  :src="getImageUrl(brand.logo_url || null, 'brand')"
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
          <h2 class="text-3xl font-bold text-gray-900 mb-4 font-winner-extra-bold">Upcoming Events</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Don't miss out on our exciting rap battle events and competitions
          </p>
        </div>
        <EventGallery :events="eventStore.events.slice(0, 5)" :loading="eventStore.loading" />
      </div>
    </section>

    <!-- CTA Section -->
    <CTA />
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