<script setup lang="ts">
import HeroCard from '@/components/HeroCard.vue';
import Carousel from '@/components/Carousel.vue'; 
import EventGallery from '@/components/EventGallery.vue';

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
                    class="w-full bg-black text-white py-2 rounded-md hover:bg-gray-800 transition-colors cursor-pointer"
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
          class="mt-8 inline-block text-center bg-black text-white px-6 py-3 rounded-md hover:bg-gray-800 transition-colors mx-auto"
        >
          View All Products
        </RouterLink>
      </div>
    </section>

    <section class="py-[60px]" id="events">
      <div class="mx-auto px-10 max-w-[1440px] items-center">
        <h2 class="text-3xl font-bold text-gray-900 text-center">Events</h2>
        <EventGallery />
      </div>
    </section>
  </div>
</template>