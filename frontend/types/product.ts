import type { Subcategory } from './subcategory'

export interface ProductVariantPayload {
  id?: number // For updates
  color_id?: number
  color_name?: string
  color_hex?: string
  available_sizes?: number[]
  size_stocks?: { [sizeId: number]: number }
  stock: number
  sku: string
  images?: File[] // For create
  new_images?: File[] // For update
  existing_images?: number[] // Image IDs to keep
  images_to_delete?: number[] // Image IDs to delete
  primary_existing_image_id?: number | null // ID of existing primary image
  primary_new_image_index?: number | null // Index of new primary image
}

export interface ProductPayload {
  subcategory_id?: number // Optional for updates
  brand_id?: number
  brand_name?: string
  default_color_id?: number
  default_color_name?: string
  default_color_hex?: string
  name: string
  description?: string
  meta_title?: string
  meta_description?: string
  meta_keywords?: string
  canonical_url?: string
  robots?: string
  is_active?: boolean
  is_featured?: boolean
  is_hot?: boolean
  is_new?: boolean
  base_price?: number
  stock?: number
  size_stocks?: { [sizeId: number]: number } // Stock per size for products without color variants
  sku?: string
  images?: File[] // For create
  new_images?: File[] // For update
  existing_image_ids?: number[] // Image IDs to keep
  images_to_delete?: number[] // Image IDs to delete
  primary_existing_image_id?: number | null // ID of existing primary image
  primary_new_image_index?: number | null // Index of new primary image
  primary_image_index?: number | null // For create
  sizes?: number[]
  variants?: ProductVariantPayload[] // Optional for updates
}

export interface Product {
  price?: number
  base_price?: number
  stock?: number
  sku?: string | null
  id: number
  subcategory_id: number
  brand_id: number
  default_color_id?: number | null
  name: string
  slug: string
  description: string
  meta_title: string
  meta_description: string
  meta_keywords: string | null
  canonical_url: string | null
  robots: string
  is_active: number
  is_featured: number
  is_hot: number
  is_new: number
  created_at: string
  updated_at: string

  brand: {
    id: number
    name: string
    slug: string
    logo_url: string | null
    meta_title: string | null
    meta_description: string | null
    created_at: string
    updated_at: string
  }

  subcategory: Subcategory & {
    category: {
      id: number
      name: string
      slug: string
      meta_title: string
      meta_description: string
      created_at: string
      updated_at: string
    }

    products?: Product[]
  }

  variants: {
    id: number
    product_id: number
    color_id: number | null
    size_id: number | null
    stock: number
    sku: string | null
    created_at: string
    updated_at: string
    color: { id: number; name: string; hex_code: string } | null
    size: { id: number; name: string; description: string | null } | null
    images: {
      id: number
      product_id: number
      variant_id: number
      url: string
      is_primary: boolean
    }[]
  }[]

  images: {
    id: number
    product_id: number
    variant_id: number | null
    url: string
    is_primary: boolean
  }[]

  // Eager-loaded default color
  default_color?: { id: number; name: string; hex_code: string } | null

  // Eager-loaded sizes
  sizes?: { id: number; name: string; slug: string; description: string | null; sort_order: number | null; color_id?: number }[]
}
