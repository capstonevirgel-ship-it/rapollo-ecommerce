export function getImageUrl(path: string | null, type: 'product' | 'brand' | 'event' | 'avatar' | 'default' = 'default'): string {
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
      case 'avatar':
        return "/uploads/avatar_placeholder.png";
      default:
        return "/uploads/event_placeholder.svg";
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
