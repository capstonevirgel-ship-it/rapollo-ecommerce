import type { Product } from './product'

export interface CartPayload {
  variant_id?: number
  product_id?: number
  size_id?: number
  quantity: number
}

export interface Cart {
  id: number
  user_id: number
  variant_id: number
  quantity: number
  created_at: string
  updated_at: string

  variant: {
    id: number
    product_id: number
    color_id: number | null
    size_id: number | null
    price: number
    stock: number
    sku: string | null
    created_at: string
    updated_at: string

    product: Product
    color: {
      id: number
      name: string
      hex_code: string
    } | null
    size: {
      id: number
      name: string
      description: string | null
    } | null
    images: {
      id: number
      product_id: number
      variant_id: number
      url: string
      is_primary: boolean
    }[]
  }
}
