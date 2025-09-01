import type { Category } from './category';
import type { Product } from './product';

export interface Subcategory {
  id: number;
  category_id: number;
  name: string;
  slug: string;
  meta_title?: string;
  meta_description?: string;
  created_at?: string;
  updated_at?: string;

  // Optional relation
  category?: Category;
  products?: Product[];
}
