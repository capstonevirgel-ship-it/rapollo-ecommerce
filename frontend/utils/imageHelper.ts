export function getImageUrl(path: string | null, type: 'product' | 'brand' | 'event' | 'default' = 'default'): string {
  // Check if path is null, undefined, empty string, or just whitespace
  if (!path || path.trim() === '') {
    // Return appropriate placeholder based on type
    switch (type) {
      case 'brand':
        return "/uploads/logo_placeholder.svg";
      case 'product':
        return "/uploads/product_placeholder.svg";
      case 'event':
        return "/uploads/event_placeholder.svg";
      default:
        return "/placeholder.png";
    }
  }

  // If it's already a full URL, return as is
  if (path.startsWith("http://") || path.startsWith("https://")) {
    return path;
  }

  // Ensure no leading slash
  const cleanPath = path.startsWith("/") ? path.slice(1) : path;

  const baseUrl = "http://localhost:8000/storage";

  return `${baseUrl}/${cleanPath}`;
}

/**
 * Get the primary image from an array of images
 * @param images Array of image objects with url and optional is_primary flag
 * @returns URL of primary image, or first image if no primary found, or null if no images
 */
export function getPrimaryImage(images: Array<{url: string, is_primary?: boolean}> | null | undefined): string | null {
  if (!images || images.length === 0) {
    return null;
  }

  // Find primary image first
  const primaryImage = images.find(img => img.is_primary === true);
  if (primaryImage?.url) {
    return primaryImage.url;
  }

  // Fallback to first image if no primary found
  return images[0]?.url || null;
}

/**
 * Get the primary variant image with fallback to product images
 * @param variant Variant object with images array
 * @param product Optional product object with images array
 * @returns URL of primary variant image, or primary product image, or first available image
 */
export function getPrimaryVariantImage(variant: any, product?: any): string | null {
  // Try variant images first
  if (variant?.images && Array.isArray(variant.images) && variant.images.length > 0) {
    const primaryVariant = variant.images.find((img: any) => img?.url && img.is_primary === true);
    if (primaryVariant?.url) {
      return primaryVariant.url;
    }
    // Fallback to first variant image
    if (variant.images[0]?.url) {
      return variant.images[0].url;
    }
  }

  // Fallback to product images
  if (product?.images && Array.isArray(product.images) && product.images.length > 0) {
    const primaryProduct = product.images.find((img: any) => img?.url && img.is_primary === true);
    if (primaryProduct?.url) {
      return primaryProduct.url;
    }
    // Fallback to first product image
    if (product.images[0]?.url) {
      return product.images[0].url;
    }
  }

  return null;
}