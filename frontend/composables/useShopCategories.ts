export interface Product {
  name: string
  slug: string
  image: string
  price: number
  description: string
}

export interface Subcategory {
  name: string
  slug: string
  products: Product[]
}

export interface ShopCategory {
  name: string
  slug: string
  subcategories: Subcategory[]
}

export const useShopCategories = (): ShopCategory[] => {
  return [
    {
      name: 'Tops',
      slug: 'tops',
      subcategories: [
        {
          name: 'T-Shirts',
          slug: 't-shirts',
          products: [
            {
              name: 'Classic White Tee',
              slug: 'classic-white-tee',
              image: '/test_images/classic_white_tee.png',
              price: 29.99,
              description: 'A classic white T-shirt made from 100% cotton.'
            },
            {
              name: 'Graphic Tee',
              slug: 'graphic-tee',
              image: '/test_images/graphic_tee.png',
              price: 34.99,
              description: 'Bold graphic print on a soft cotton base.'
            }
          ]
        },
        {
          name: 'Shirts',
          slug: 'shirts',
          products: [
            {
              name: 'Linen Shirt',
              slug: 'linen-shirt',
              image: '/test_images/linen_shirt.png',
              price: 49.99,
              description: 'Lightweight linen shirt, perfect for summer.'
            }
          ]
        }
      ]
    },
    {
      name: 'Bottoms',
      slug: 'bottoms',
      subcategories: [
        {
          name: 'Jeans',
          slug: 'jeans',
          products: [
            {
              name: 'Slim Fit Jeans',
              slug: 'slim-fit-jeans',
              image: '/test_images/slim_fit_jeans.png',
              price: 59.99,
              description: 'Slim fit denim jeans with stretch material.'
            }
          ]
        }
      ]
    },
    {
      name: 'Headwear',
      slug: 'headwear',
      subcategories: [
        {
          name: 'Caps',
          slug: 'caps',
          products: [
            {
              name: 'Snapback Cap',
              slug: 'snapback-cap',
              image: '/test_images/snapback_cap.png',
              price: 19.99,
              description: 'Adjustable snapback with embroidered logo.'
            }
          ]
        }
      ]
    },
    {
      name: 'Accessories',
      slug: 'accessories',
      subcategories: [
        {
          name: 'Watches',
          slug: 'watches',
          products: [
            {
              name: 'Sport Watch',
              slug: 'sport-watch',
              image: '/test_images/sports_watch.png',
              price: 99.99,
              description: 'Water-resistant sport watch with rubber strap.'
            }
          ]
        }
      ]
    }
  ]
}
