export interface Subcategory {
  id: number;
  category_id: number;
  name: string;
  slug: string;
  meta_title?: string;
  meta_description?: string;
  created_at: string;
  updated_at: string;
}

export interface CategoryWithSubcategories {
  id: number;
  name: string;
  slug: string;
  meta_title?: string;
  meta_description?: string;
  created_at: string;
  updated_at: string;
  subcategories: Subcategory[];
}
