export interface BrandPayload {
  name: string;
  slug?: string;
  logo?: File | null; 
  meta_title?: string;
  meta_description?: string;
}

export interface Brand {
  id: number;
  name: string;
  slug: string;
  logo_url?: string;
  meta_title?: string;
  meta_description?: string;
  created_at?: string;
  updated_at?: string;
}