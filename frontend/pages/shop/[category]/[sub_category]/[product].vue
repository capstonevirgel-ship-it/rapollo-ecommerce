<script setup lang="ts">
import Breadcrumbs from '@/components/navigation/Breadcrumbs.vue'
import Carousel from '@/components/Carousel.vue'
import ProductReview from '@/components/ProductReview.vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useProductStore } from '~/stores/product'
import { useSubcategoryStore } from '~/stores/subcategory'
import { useCartStore } from '~/stores/cart'
import { useAuthStore } from "~/stores/auth"
import { useAlert } from '~/composables/useAlert'
import { onMounted, ref } from 'vue'

import { getImageUrl } from '~/utils/imageHelper'

const route = useRoute()
const { category, sub_category: subSlug, product: productSlug } = route.params

// Stores
const productStore = useProductStore()
const subcategoryStore = useSubcategoryStore()
const cartStore = useCartStore()
const authStore = useAuthStore()
const { warning } = useAlert()

const { product, loading, error } = storeToRefs(productStore)

// Local state for data fetching
const isDataLoaded = ref(false)
const relatedProducts = ref<any[]>([])

// Fetch product data on component mount
onMounted(async () => {
  try {
    await (productStore as any).fetchProduct(productSlug as string)
    
    // Auto-select default color if available
    if ((product.value as any)?.default_color) {
      selectedColor.value = 'default'
    }
    
    // Related products from same subcategory
    if (product.value?.subcategory_id) {
      await (subcategoryStore as any).fetchSubcategoryById(product.value.subcategory_id)
      relatedProducts.value = subcategoryStore.subcategory?.products
        ?.filter(p => p.slug !== productSlug)
        .map((p, index) => ({ ...p, id: index })) || []
    }
    
    isDataLoaded.value = true
  } catch (error) {
    console.error('Failed to fetch product:', error)
  }
})

// Set page title with meta_title from backend
useHead(() => {
  if (product.value) {
    const title = product.value.meta_title || product.value.name
    return {
      title: `${title} | RAPOLLO`,
      meta: [
        { name: 'description', content: product.value.meta_description || product.value.description || '' },
        { name: 'keywords', content: product.value.meta_keywords || '' }
      ]
    }
  }
  return {
    title: 'Product | RAPOLLO'
  }
})

// Product selection state
const selectedColor = ref<string | null>(null)
const selectedSize = ref<number | null>(null)
const selectedQuantity = ref(1)
const isClient = ref(false)
const currentImageIndex = ref(0)

// Set client flag on mount and load guest cart
onMounted(() => {
  isClient.value = true
  
  // Load guest cart into store for display if not authenticated
  if (!authStore.isAuthenticated) {
    cartStore.loadGuestCartIntoStore()
  }
})

// Computed properties for variant management
const availableColors = computed(() => {
  // If product has no variants, return empty array
  if (!product.value?.variants || product.value.variants.length === 0) {
    return []
  }
  
  const colors: Array<{ id: string; name: string; hex: string; variant?: any; isDefault?: boolean }> = []
  
  // Add default/main product color if available
  if ((product.value as any)?.default_color) {
    colors.push({
      id: 'default',
      name: (product.value as any).default_color.name || 'Default',
      hex: (product.value as any).default_color.hex_code || '#000000',
      isDefault: true
    })
  }
  
  // Add variant colors
  const variantColors = new Map()
  product.value.variants.forEach(variant => {
    if (variant.color) {
      variantColors.set(variant.color.id, {
        id: variant.color.id.toString(),
        name: variant.color.name,
        hex: variant.color.hex_code,
        variant: variant
      })
    }
  })
  colors.push(...Array.from(variantColors.values()))
  
  return colors
})

const availableSizes = computed(() => {
  // If product has no variants, check if product has direct sizes
  if (!product.value?.variants || product.value.variants.length === 0) {
    // Return sizes directly from product.sizes if available
    if (product.value?.sizes && product.value.sizes.length > 0) {
      return product.value.sizes.map(size => ({
        id: size.id,
        name: size.name,
        stock: product.value?.stock || 0, // Use product stock for all sizes
        variant: null // No variant for products without variants
      }))
    }
    return []
  }
  
  // If default color is selected or no color is selected, show all sizes
  if (!selectedColor.value || selectedColor.value === 'default') {
    const sizes = new Map()
    product.value.variants.forEach(variant => {
      if (variant.size) {
        sizes.set(variant.size.id, {
          id: variant.size.id,
          name: variant.size.name,
          stock: variant.stock,
          variant: variant
        })
      }
    })
    return Array.from(sizes.values())
  }
  
  // Filter sizes by selected variant color
  const sizes = new Map()
  product.value.variants
    .filter(variant => variant.color?.id.toString() === selectedColor.value)
    .forEach(variant => {
      if (variant.size) {
        sizes.set(variant.size.id, {
          id: variant.size.id,
          name: variant.size.name,
          stock: variant.stock,
          variant: variant
        })
      }
    })
  return Array.from(sizes.values())
})

const currentVariant = computed(() => {
  // If product has no variants, return null (will be handled by cart)
  if (!product.value?.variants || product.value.variants.length === 0) {
    return null
  }
  
  // If default color is selected
  if (selectedColor.value === 'default') {
    // If sizes are available, require size selection
    if (availableSizes.value.length > 0) {
      if (!selectedSize.value) {
        return null // No variant selected until size is chosen
      }
      // Find variant with the selected size (any color since default shows all sizes)
      return product.value.variants.find(variant => 
        variant.size?.id === selectedSize.value
      ) || null
    }
    // If no sizes available, return first variant
    return product.value.variants[0] || null
  }
  
  // If a specific variant color is selected
  if (selectedColor.value && selectedSize.value) {
    return product.value.variants.find(variant => 
      variant.color?.id.toString() === selectedColor.value &&
      variant.size?.id === selectedSize.value
    )
  } else if (selectedColor.value) {
    // No size selected but color is selected - only return variant if it has no size requirement
    const variantWithColor = product.value.variants.find(variant => 
      variant.color?.id.toString() === selectedColor.value && !variant.size
    )
    if (variantWithColor) {
      return variantWithColor
    }
    // If sizes are available for this color, require size selection
    const colorSizes = availableSizes.value.length > 0
    return colorSizes ? null : product.value.variants[0] || null
  }
  
  // No color selected - if sizes are available, require selection
  if (availableSizes.value.length > 0 && !selectedSize.value) {
    return null
  }
  
  return product.value.variants[0] || null
})

const currentImages = computed(() => {
  if (!product.value?.images) return []
  
  // If default color is selected, show ONLY main/default product images
  if (selectedColor.value === 'default') {
    const mainImages = product.value.images.filter(image => !image.variant_id)
    return mainImages.length > 0 ? mainImages : []
  }
  
  // If a specific variant color is selected, show ONLY variant images for that color
  if (selectedColor.value) {
    // Get all variant images for the selected color
    const variantImages = (product.value!.variants)
      ?.filter(variant => variant.color?.id.toString() === selectedColor.value)
      ?.flatMap(variant => variant.images || []) || []
    
    // Also check if any images in product.images belong to variants with the selected color
    const productImagesForColor = (product.value!.images).filter(image => {
      if (image.variant_id) {
        const variant = product.value!.variants?.find(v => v.id === image.variant_id)
        return variant?.color?.id.toString() === selectedColor.value
      }
      return false // Don't include main product images when color is selected
    })
    
    // Combine and deduplicate images
    const allColorImages = [...variantImages, ...productImagesForColor]
    const uniqueImages = allColorImages.filter((image, index, self) => 
      index === self.findIndex(img => img.id === image.id)
    )
    
    return uniqueImages.length > 0 ? uniqueImages : []
  }
  
  // If no color selected, show ONLY main/default product images (images without variant_id)
  const mainImages = product.value.images.filter(image => !image.variant_id)
  return mainImages.length > 0 ? mainImages : product.value.images || []
})

const stockStatus = computed(() => {
  // If product has no variants, use product stock
  if (!product.value?.variants || product.value.variants.length === 0) {
    const stock = product.value?.stock || 0
    if (stock === 0) return { status: 'out', label: 'Out of Stock', class: 'text-red-600' }
    if (stock <= 5) return { status: 'low', label: `Only ${stock} left!`, class: 'text-orange-600' }
    return { status: 'in', label: `${stock} in stock`, class: 'text-green-600' }
  }
  
  if (!currentVariant.value) return { status: 'out', label: 'Select options', class: 'text-gray-600' }
  
  const stock = currentVariant.value.stock || 0
  if (stock === 0) return { status: 'out', label: 'Out of Stock', class: 'text-red-600' }
  if (stock <= 5) return { status: 'low', label: `Only ${stock} left!`, class: 'text-orange-600' }
  return { status: 'in', label: `${stock} in stock`, class: 'text-green-600' }
})

const isOutOfStock = computed(() => {
  // If product has no variants, check product stock
  if (!product.value?.variants || product.value.variants.length === 0) {
    return (product.value?.stock || 0) === 0
  }
  return !currentVariant.value || (currentVariant.value.stock || 0) === 0
})
const currentPrice = computed(() => product.value?.price || 0)

// Computed property for maximum available quantity considering cart
const maxAvailableQuantity = computed(() => {
  // If product has no variants, use product stock
  if (!product.value?.variants || product.value.variants.length === 0) {
    const availableStock = product.value?.stock || 0
    
    // Only check cart on client side to avoid hydration mismatch
    if (isClient.value) {
      // For products without variants, check cart by product_id and size_id if size is selected
      const existingCartItem = cartStore.cart.find((item: any) => {
        const matchesProduct = item.variant?.product_id === product.value?.id
        if (availableSizes.value.length > 0 && selectedSize.value) {
          // Product has sizes - check by size_id
          return matchesProduct && item.variant?.size_id === selectedSize.value && !item.variant?.color_id
        } else {
          // Product has no sizes - check for items without color_id and size_id
          return matchesProduct && !item.variant?.color_id && !item.variant?.size_id
        }
      })
      const currentCartQuantity = existingCartItem ? existingCartItem.quantity : 0
      return availableStock - currentCartQuantity
    }
    
    return availableStock
  }
  
  if (!currentVariant.value) return 0
  
  const availableStock = currentVariant.value.stock
  
  // Only check cart on client side to avoid hydration mismatch
  if (isClient.value) {
    const existingCartItem = cartStore.cart.find((item: any) => item.variant_id === currentVariant.value?.id)
    const currentCartQuantity = existingCartItem ? existingCartItem.quantity : 0
    return availableStock - currentCartQuantity
  }
  
  return availableStock
})

// Helper functions for selection
const selectColor = (colorId: string) => {
  selectedColor.value = colorId
  selectedSize.value = null // Reset size when color changes
  currentImageIndex.value = 0 // Reset to first image when color changes
}

const selectImage = (index: number) => {
  currentImageIndex.value = index
}

const selectSize = (sizeId: number) => {
  selectedSize.value = sizeId
}

const validateQuantity = () => {
  if (selectedQuantity.value > maxAvailableQuantity.value) {
    selectedQuantity.value = maxAvailableQuantity.value
    warning(
      "Quantity Adjusted", 
      `Quantity adjusted to maximum available (${maxAvailableQuantity.value}) considering items already in your cart.`
    )
  } else if (selectedQuantity.value < 1) {
    selectedQuantity.value = 1
  }
}

const addToCart = async () => {
  // Prevent admins from adding to cart
  if (authStore.isAdmin) {
    warning(
      "Admin Restriction",
      "Administrators cannot add items to cart. Please use a customer account to make purchases."
    )
    return
  }

  if (!product.value) {
    return
  }

  // Handle products without variants
  const hasNoVariants = !product.value.variants || product.value.variants.length === 0
  
  if (hasNoVariants) {
    // Check if size is required but not selected
    if (availableSizes.value.length > 0 && !selectedSize.value) {
      warning(
        "Size Required",
        "Please select a size before adding to cart."
      )
      return
    }

    // Check product stock
    if ((product.value.stock || 0) === 0) {
      warning("Out of Stock", "This product is currently out of stock.")
      return
    }

    // Check if adding this quantity would exceed available stock
    const availableStock = product.value.stock || 0
    const requestedQuantity = selectedQuantity.value
    
    // Get current cart quantity for this product (client-side only)
    // For products with sizes but no variants, check by product_id and size_id
    let currentCartQuantity = 0
    if (isClient.value) {
      const existingCartItem = cartStore.cart.find((item: any) => {
        const matchesProduct = item.variant?.product_id === product.value?.id
        const matchesSize = availableSizes.value.length > 0 
          ? (item.variant?.size_id === selectedSize.value)
          : (!item.variant?.color_id && !item.variant?.size_id)
        return matchesProduct && matchesSize
      })
      currentCartQuantity = existingCartItem ? existingCartItem.quantity : 0
    }
    const totalQuantity = currentCartQuantity + requestedQuantity

    // Check if total quantity would exceed stock
    if (totalQuantity > availableStock) {
      warning(
        "Stock Exceeded", 
        `You already have ${currentCartQuantity} of this item in your cart. Adding ${requestedQuantity} more would exceed the available stock of ${availableStock}. Please adjust your quantity.`
      )
      return
    }

    try {
      if (authStore.isAuthenticated) {
        // Logged in: use store method with product_id and size_id if size is selected
        if (availableSizes.value.length > 0 && selectedSize.value) {
          await cartStore.store({ product_id: product.value.id, size_id: selectedSize.value, quantity: selectedQuantity.value })
        } else {
          await cartStore.store({ product_id: product.value.id, quantity: selectedQuantity.value })
        }
      } else {
        // Guest: create proper guest item with virtual variant
        const selectedSizeObj = availableSizes.value.length > 0 && selectedSize.value
          ? availableSizes.value.find(s => s.id === selectedSize.value)
          : null
        
        const guestItem = {
          id: 0,
          user_id: 0,
          variant_id: 0, // Will be set after variant is created
          quantity: selectedQuantity.value,
          created_at: '',
          updated_at: '',
          variant: {
            id: 0,
            product_id: product.value.id,
            color_id: null,
            size_id: selectedSize.value || null,
            price: product.value?.price || 0,
            stock: product.value.stock || 0,
            sku: product.value.sku || '',
            created_at: '',
            updated_at: '',
            product: product.value,
            color: null,
            size: selectedSizeObj ? { id: selectedSizeObj.id, name: selectedSizeObj.name, description: null } : null,
            images: product.value.images?.filter(img => !img.variant_id) || []
          }
        }
        
        if (availableSizes.value.length > 0 && selectedSize.value) {
          await cartStore.addToCart({ product_id: product.value.id, size_id: selectedSize.value, quantity: selectedQuantity.value }, false, guestItem as any)
        } else {
          await cartStore.addToCart({ product_id: product.value.id, quantity: selectedQuantity.value }, false, guestItem as any)
        }
      }
    } catch (error: any) {
      console.error('Failed to add to cart:', error)
      if (error.data?.message) {
        alert(error.data.message)
      }
    }
    return
  }

  // Handle products with variants
  if (!currentVariant.value) {
    // Show warning if size selection is required
    if (availableSizes.value.length > 0 && !selectedSize.value) {
      warning(
        "Size Required",
        "Please select a size before adding to cart."
      )
    }
    return
  }

  // Check stock before adding
  if (currentVariant.value.stock === 0) {
    console.error('Product is out of stock')
    return
  }

  // Check if adding this quantity would exceed available stock
  const availableStock = currentVariant.value.stock
  const requestedQuantity = selectedQuantity.value
  
  // Get current cart quantity for this variant (client-side only)
  let currentCartQuantity = 0
  if (isClient.value) {
    const existingCartItem = cartStore.cart.find((item: any) => item.variant_id === currentVariant.value?.id)
    currentCartQuantity = existingCartItem ? existingCartItem.quantity : 0
  }
  const totalQuantity = currentCartQuantity + requestedQuantity

  // Check if total quantity would exceed stock
  if (totalQuantity > availableStock) {
    warning(
      "Stock Exceeded", 
      `You already have ${currentCartQuantity} of this item in your cart. Adding ${requestedQuantity} more would exceed the available stock of ${availableStock}. Please adjust your quantity.`
    )
    return
  }

  // Compute color-matched images if the variant has none
  const getColorMatchedImages = (prod: any, variant: any) => {
    if (!prod?.images?.length || !prod?.variants?.length) return []
    const variantColorId = variant?.color_id ?? variant?.color?.id
    if (!variantColorId) return []
    const sameColorVariantIds = prod.variants
      .filter((v: any) => (v?.color_id ?? v?.color?.id) === variantColorId)
      .map((v: any) => v.id)
    return (prod.images || []).filter((img: any) => img?.variant_id && sameColorVariantIds.includes(img.variant_id))
  }

  try {
    if (authStore.isAuthenticated) {
      // Logged in: use store method directly
      await cartStore.store({ variant_id: currentVariant.value.id, quantity: selectedQuantity.value })
    } else {
      // Guest: create proper guest item and use addToCart
      const images = (currentVariant.value.images && currentVariant.value.images.length > 0)
        ? currentVariant.value.images
        : getColorMatchedImages(product.value, currentVariant.value)
      const guestItem = {
        id: 0,
        user_id: 0,
        variant_id: currentVariant.value.id,
        quantity: selectedQuantity.value,
        created_at: '',
        updated_at: '',
        variant: {
          id: currentVariant.value.id,
          product_id: product.value.id,
          color_id: currentVariant.value.color_id,
          size_id: currentVariant.value.size_id,
          price: product.value?.price || 0,
          stock: currentVariant.value.stock,
          sku: currentVariant.value.sku,
          created_at: '',
          updated_at: '',
          product: product.value,
          color: currentVariant.value.color,
          size: currentVariant.value.size,
          images
        }
      }
      await cartStore.addToCart({ variant_id: currentVariant.value.id, quantity: selectedQuantity.value }, false, guestItem as any)
    }
  } catch (error: any) {
    console.error('Failed to add to cart:', error)
    if (error.data?.message) {
      alert(error.data.message)
    }
  }
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <Breadcrumbs />

    <!-- Loading skeleton -->
    <div v-if="!isDataLoaded || loading" class="space-y-4 animate-pulse">
      <div class="h-64 bg-gray-200 rounded"></div>
      <div class="h-6 bg-gray-200 rounded w-1/3"></div>
      <div class="h-4 bg-gray-200 rounded w-2/3"></div>
      <div class="h-10 bg-gray-300 rounded w-1/4"></div>
    </div>

    <!-- Error -->
    <p v-else-if="error" class="text-red-500">{{ error }}</p>

    <!-- Product detail -->
    <div v-else-if="product && isDataLoaded" class="space-y-8">
      <div class="flex flex-col md:flex-row gap-6">
        <!-- Product Images -->
        <div class="w-full md:w-1/2">
          <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden mb-4">
            <img
              :src="getImageUrl(currentImages[currentImageIndex]?.url)"
              :alt="product.name"
              class="w-full h-full object-cover"
            />
          </div>
          
          <!-- Image Thumbnails -->
          <div v-if="currentImages.length > 1" class="flex gap-2 overflow-x-auto py-1">
            <div
              v-for="(image, index) in currentImages"
              :key="index"
              :class="[
                'w-20 h-20 rounded overflow-hidden cursor-pointer border-2 flex-shrink-0 transition-all duration-200',
                currentImageIndex === index 
                  ? 'border-gray-800 scale-105' 
                  : 'border-transparent hover:border-gray-300'
              ]"
              @click="selectImage(index)"
            >
              <img
                :src="getImageUrl(image.url)"
                :alt="product.name"
                class="w-full h-full object-cover block"
              />
            </div>
          </div>
        </div>

        <!-- Product Details -->
        <div class="md:w-1/2">
          <h1 class="text-3xl font-bold mb-2">{{ product.name }}</h1>
          <p class="text-primary-600 font-semibold mb-3">
            ₱{{ currentPrice.toFixed(2) }}
          </p>

          <!-- Stock Indicator -->
          <div class="mb-4">
            <p :class="['text-sm font-medium', stockStatus.class]">
              <span v-if="stockStatus.status === 'out'" class="inline-block mr-1">❌</span>
              <span v-else-if="stockStatus.status === 'low'" class="inline-block mr-1">⚠️</span>
              <span v-else class="inline-block mr-1">✅</span>
              {{ stockStatus.label }}
            </p>
          </div>

          <p class="text-gray-700 leading-relaxed mb-6">{{ product.description }}</p>

          <!-- Color Selection (only show if product has variants) -->
          <div v-if="product.variants && product.variants.length > 0 && availableColors.length > 0" class="mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Color</h3>
            <div class="flex gap-2">
              <button
                v-for="color in availableColors"
                :key="color.id"
                @click="selectColor(color.id.toString())"
                :class="[
                  'w-10 h-10 rounded-full border-2 transition-all duration-200',
                  selectedColor === color.id.toString()
                    ? 'border-gray-800 scale-110'
                    : 'border-gray-300 hover:border-gray-500'
                ]"
                :style="{ backgroundColor: color.hex }"
                :title="color.name"
              />
            </div>
          </div>

          <!-- Size Selection (show if product has sizes, either from variants or direct sizes) -->
          <div v-if="availableSizes.length > 0" class="mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Size</h3>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="size in availableSizes"
                :key="size.id"
                @click="selectSize(size.id)"
                :class="[
                  'px-3 py-2 border rounded-lg text-sm font-medium transition-all duration-200',
                  selectedSize === size.id
                    ? 'border-zinc-800 bg-zinc-800 text-white'
                    : size.stock === 0
                    ? 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-500'
                ]"
                :disabled="size.stock === 0"
              >
                {{ size.name }}
                <span v-if="size.stock > 0" class="text-xs ml-1">({{ size.stock }})</span>
                <span v-else class="text-xs ml-1">(0)</span>
              </button>
            </div>
          </div>

          <!-- Quantity Selection -->
          <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Quantity</h3>
            <div class="flex items-center gap-2">
              <button
                @click="selectedQuantity = Math.max(1, selectedQuantity - 1)"
                :disabled="selectedQuantity <= 1"
                class="w-8 h-8 border border-gray-300 rounded flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="text-lg font-bold">−</span>
              </button>
              <input
                v-model.number="selectedQuantity"
                type="number"
                min="1"
                :max="isClient ? maxAvailableQuantity : (product.variants && product.variants.length > 0 ? currentVariant?.stock : product.stock) || 1"
                class="w-24 text-center border border-gray-300 rounded px-2 py-1 appearance-none"
                @input="validateQuantity"
              />
              <button
                @click="selectedQuantity = Math.min(isClient ? maxAvailableQuantity : (product.variants && product.variants.length > 0 ? currentVariant?.stock : product.stock) || 1, selectedQuantity + 1)"
                :disabled="isClient ? selectedQuantity >= maxAvailableQuantity : selectedQuantity >= ((product.variants && product.variants.length > 0 ? currentVariant?.stock : product.stock) || 1)"
                class="w-8 h-8 border border-gray-300 rounded flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span class="text-lg font-bold">+</span>
              </button>
            </div>
            <ClientOnly>
              <p v-if="product.variants && product.variants.length > 0 && maxAvailableQuantity < (currentVariant?.stock || 0)" class="text-xs text-orange-600 mt-1">
                {{ cartStore.cart.find((item: any) => item.variant_id === currentVariant?.id)?.quantity || 0 }} already in cart
              </p>
              <p v-else-if="(!product.variants || product.variants.length === 0) && maxAvailableQuantity < (product.stock || 0)" class="text-xs text-orange-600 mt-1">
                {{ cartStore.cart.find((item: any) => {
                  const matchesProduct = item.variant?.product_id === product.id
                  if (availableSizes.length > 0 && selectedSize) {
                    return matchesProduct && item.variant?.size_id === selectedSize && !item.variant?.color_id
                  }
                  return matchesProduct && !item.variant?.color_id && !item.variant?.size_id
                })?.quantity || 0 }} already in cart
              </p>
            </ClientOnly>
          </div>

          <!-- Add to Cart Button -->
          <button
            v-if="!authStore.isAdmin"
            :disabled="isOutOfStock || (isClient && maxAvailableQuantity === 0)"
            :class="[
              'w-full px-6 py-3 font-medium rounded transition cursor-pointer',
              (isOutOfStock || (isClient && maxAvailableQuantity === 0))
                ? 'bg-gray-400 text-gray-200 cursor-not-allowed' 
                : 'bg-zinc-800 text-white hover:bg-zinc-700'
            ]"
            @click="addToCart"
          >
            {{ isOutOfStock ? 'Out of Stock' : (isClient && maxAvailableQuantity === 0) ? 'Already in Cart' : 'Add to Cart' }}
          </button>
          <div v-else class="w-full px-6 py-3 bg-gray-200 text-gray-600 rounded text-center font-medium">
            Administrators cannot add items to cart
          </div>
        </div>
      </div>

      <!-- Reviews Section -->
      <div v-if="product.variants?.[0]?.id" class="mt-12">
        <ProductReview 
          :variant-id="product.variants[0].id" 
          :product-name="product.name" 
        />
      </div>

      <!-- Related Products -->
      <div v-if="relatedProducts.length" class="mt-12">
        <h2 class="text-2xl font-semibold mb-4">Related Products</h2>
        <Carousel
          :items="relatedProducts"
          :items-to-show="3"
          :autoplay="false"
          :show-arrows="true"
        >
          <template #item="{ item }">
            <div class="block rounded shadow transition">
              <NuxtLink
                :to="`/shop/${category}/${subSlug}/${item.slug}`"
              >
                <img
                  :src="getImageUrl(item.images?.[0]?.url)"
                  :alt="item.name"
                  class="w-full h-48 object-cover rounded-t mb-3"
                />
              </NuxtLink>
                <div class="p-4">
                  <h3 class="font-medium text-gray-900">{{ item.name }}</h3>
                  <p class="text-primary-600 font-semibold">
                    ₱{{ item.price?.toFixed(2) ?? 0 }}
                  </p>
                </div>
            </div>
          </template>
        </Carousel>
      </div>
    </div>

    <!-- Not found -->
    <p v-else class="text-red-500">Product not found.</p>
  </div>
</template>

<style>
/* Hide number input spinners (Chrome, Safari, Edge) */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  appearance: none;
  margin: 0;
}

/* Hide number input spinners (Firefox) */
input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
