export interface Rating {
  id: number
  user_id: number
  variant_id: number
  purchase_id: number
  stars: number
  comment?: string
  created_at: string
  updated_at: string
  user?: {
    id: number
    user_name: string
  }
  variant?: {
    id: number
    product_id: number
    size_id?: number
    color_id?: number
    product?: {
      id: number
      name: string
      slug: string
    }
  }
}

export interface RatingStatistics {
  total_ratings: number
  average_rating: number
  five_star: number
  four_star: number
  three_star: number
  two_star: number
  one_star: number
}

export interface ReviewableProduct {
  purchase_id: number
  variant_id: number
  product: {
    id: number
    name: string
    slug: string
  }
  variant: {
    id: number
    product_id: number
    size_id?: number
    color_id?: number
  }
  quantity: number
  purchased_at: string
  has_rated: boolean
}

export interface RatingPayload {
  variant_id: number
  stars: number
  comment?: string
}
