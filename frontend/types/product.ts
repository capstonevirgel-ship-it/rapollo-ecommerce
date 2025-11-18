import type { Subcategory } from './subcategory'

export interface ProductVariantPayload {
  color_id?: number
  color_name?: string
  color_hex?: string
  available_sizes?: number[]
  size_stocks?: { [sizeId: number]: number }
  price: number
  stock: number
  sku: string
  images?: File[]
}

export interface ProductPayload {
  subcategory_id: number
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
  images?: File[]
  sizes?: number[]
  variants: ProductVariantPayload[]
}

export interface Product {
  price?: number
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
    color_id: number
    size_id: number
    price: number 
    stock: number
    sku: string
    created_at: string
    updated_at: string
    color: { id: number; name: string; hex_code: string }
    size: { id: number; name: string; description: string | null }
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
}
