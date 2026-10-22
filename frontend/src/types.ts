export interface Category {
  id: number
  name: string
}

export interface Product {
  id: number
  sku: string
  name: string
  price: number
  stock: number
  category: Category
}

export interface Paginated<T> {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
