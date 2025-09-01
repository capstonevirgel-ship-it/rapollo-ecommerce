export function getImageUrl(path: string | null): string {
  if (!path) return '/placeholder.png'; // fallback if no image

  // If path already includes 'http' (full URL), return it as is
  if (path.startsWith('http')) return path;

  // Remove leading slash if exists
  const cleanPath = path.startsWith('/') ? path.slice(1) : path;

  // Base URL of your Laravel backend
  const baseUrl = 'http://localhost:8000/storage';

  return `${baseUrl}/${cleanPath}`;
}
