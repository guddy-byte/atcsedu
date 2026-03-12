import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'

export interface Product {
  id: string
  title: string
  category: string
  format: string
  price: number
  stock: number
  description: string
  accessType: 'free' | 'paid'
  imageUrl: string
  downloadUrl?: string
}

export interface CartItem {
  productId: string
  quantity: number
}

export interface CartProduct extends Product {
  quantity: number
  lineTotal: number
}

export interface ProductInput {
  title: string
  category: string
  format: string
  price: number
  stock: number
  description: string
  accessType?: 'free' | 'paid'
  imageUrl?: string
  downloadUrl?: string
}

const fallbackImageByCategory: Record<string, string> = {
  Mathematics:
    'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1200&q=80',
  Science:
    'https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=1200&q=80',
  Revision:
    'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1200&q=80',
  'Exam Training':
    'https://images.unsplash.com/photo-1497486751825-1233686d5d80?auto=format&fit=crop&w=1200&q=80',
  Languages:
    'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
  General:
    'https://images.unsplash.com/photo-1513258496099-48168024aec0?auto=format&fit=crop&w=1200&q=80',
}

const getFallbackImage = (category: string) =>
  fallbackImageByCategory[category] ?? fallbackImageByCategory.General

const sampleFreeMaterialPdf = '/materials/atcsedu-free-material.pdf'

const catalogSeed: Product[] = [
  {
    id: 'waec-math-accelerator',
    title: 'WAEC Math Accelerator',
    category: 'Mathematics',
    format: 'Paid Pack',
    price: 45,
    stock: 40,
    description: 'Focused lesson notes, worked examples, and timed revision drills for exam candidates.',
    accessType: 'paid',
    imageUrl: getFallbackImage('Mathematics'),
  },
  {
    id: 'science-lab-bundle',
    title: 'Science Lab Bundle',
    category: 'Science',
    format: 'Paid Pack',
    price: 120,
    stock: 18,
    description: 'Grouped experiments, lesson notes, and downloadable lab materials.',
    accessType: 'paid',
    imageUrl: getFallbackImage('Science'),
  },
  {
    id: 'exam-revision-kit',
    title: 'Exam Revision Kit',
    category: 'Revision',
    format: 'Paid Pack',
    price: 49,
    stock: 25,
    description: 'High-yield revision material with quizzes and timed practice tests.',
    accessType: 'paid',
    imageUrl: getFallbackImage('Revision'),
  },
  {
    id: 'english-essay-starter',
    title: 'English Essay Starter',
    category: 'Languages',
    format: 'Free Material',
    price: 0,
    stock: 999,
    description: 'A concise writing guide with sample essay structures for senior secondary students.',
    accessType: 'free',
    imageUrl: getFallbackImage('Languages'),
    downloadUrl: sampleFreeMaterialPdf,
  },
  {
    id: 'exam-day-checklist',
    title: 'Exam Day Checklist',
    category: 'Exam Training',
    format: 'Free Material',
    price: 0,
    stock: 999,
    description: 'Printable checklist to help candidates prepare documents, timing, and focus before exams.',
    accessType: 'free',
    imageUrl: getFallbackImage('Exam Training'),
    downloadUrl: sampleFreeMaterialPdf,
  },
]

const catalogSeedById = new Map(catalogSeed.map((product) => [product.id, product]))

const canUseStorage = typeof window !== 'undefined'

const readStorage = <T>(key: string, fallback: T): T => {
  if (!canUseStorage) {
    return fallback
  }

  const saved = window.localStorage.getItem(key)

  if (!saved) {
    return fallback
  }

  try {
    return JSON.parse(saved) as T
  } catch {
    return fallback
  }
}

const persistStorage = <T>(key: string, value: T) => {
  if (!canUseStorage) {
    return
  }

  window.localStorage.setItem(key, JSON.stringify(value))
}

const createProductId = (title: string, suffix = '') => {
  const base = title.toLowerCase().replace(/[^a-z0-9]+/g, '-')
  return `${base}-${Date.now()}${suffix}`
}

const normalizeProduct = (product: Product): Product => {
  const seededProduct = catalogSeedById.get(product.id)
  const category = product.category || seededProduct?.category || 'General'
  const accessType = product.accessType ?? (product.price > 0 ? 'paid' : 'free')

  return {
    ...seededProduct,
    ...product,
    category,
    accessType,
    imageUrl: product.imageUrl || seededProduct?.imageUrl || getFallbackImage(category),
    downloadUrl: product.downloadUrl || seededProduct?.downloadUrl,
  }
}

export const useCatalogStore = defineStore('catalog', () => {
  const products = ref<Product[]>(
    readStorage('atcsedu-products', catalogSeed).map((product) => normalizeProduct(product)),
  )
  const cart = ref<CartItem[]>(readStorage('atcsedu-cart', []))

  const revenueEstimate = computed(() =>
    products.value.reduce((total, product) => total + product.price * product.stock, 0),
  )

  const cartItems = computed<CartProduct[]>(() =>
    cart.value.flatMap((item) => {
      const product = products.value.find((entry) => entry.id === item.productId)

      if (!product) {
        return []
      }

      return [
        {
          ...product,
          quantity: item.quantity,
          lineTotal: item.quantity * product.price,
        },
      ]
    }),
  )

  const cartItemCount = computed(() =>
    cart.value.reduce((total, item) => total + item.quantity, 0),
  )

  const cartTotal = computed(() =>
    cartItems.value.reduce((total, item) => total + item.lineTotal, 0),
  )

  const addToCart = (productId: string) => {
    const existing = cart.value.find((item) => item.productId === productId)

    if (existing) {
      existing.quantity += 1
      return
    }

    cart.value.push({ productId, quantity: 1 })
  }

  const updateQuantity = (productId: string, quantity: number) => {
    if (quantity <= 0) {
      cart.value = cart.value.filter((item) => item.productId !== productId)
      return
    }

    const existing = cart.value.find((item) => item.productId === productId)

    if (existing) {
      existing.quantity = quantity
    }
  }

  const addProduct = (product: ProductInput) => {
    const accessType = product.accessType ?? (product.price > 0 ? 'paid' : 'free')

    products.value.unshift({
      ...product,
      id: createProductId(product.title),
      accessType,
      imageUrl: product.imageUrl ?? getFallbackImage(product.category),
      downloadUrl: product.downloadUrl,
    })
  }

  const addProductsBulk = (newProducts: ProductInput[]) => {
    const stamped = newProducts.map((product, index) => ({
      ...product,
      id: createProductId(product.title, `-${index}`),
      accessType: product.accessType ?? (product.price > 0 ? 'paid' : 'free'),
      imageUrl: product.imageUrl ?? getFallbackImage(product.category),
      downloadUrl: product.downloadUrl,
    }))

    products.value.unshift(...stamped)
  }

  watch(
    products,
    (value) => {
      persistStorage('atcsedu-products', value)
    },
    { deep: true },
  )

  watch(
    cart,
    (value) => {
      persistStorage('atcsedu-cart', value)
    },
    { deep: true },
  )

  return {
    products,
    cartItems,
    cartItemCount,
    cartTotal,
    revenueEstimate,
    addToCart,
    updateQuantity,
    addProduct,
    addProductsBulk,
  }
})