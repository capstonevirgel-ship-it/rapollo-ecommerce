export function getImageUrl(path: string | null): string {
  if (!path) return "/placeholder.png";

  // Ensure no leading slash
  const cleanPath = path.startsWith("/") ? path.slice(1) : path;

  const baseUrl = "http://localhost:8000/storage";

  return `${baseUrl}/${cleanPath}`;
}
