import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'

export interface QuestionItem {
  id: string
  type: 'objective' | 'theory'
  prompt: string
  options?: string[]
  correctOption?: string
}

export interface Product {
  id: string
  type: 'material' | 'cbt'
  title: string
  category: string
  format: string
  price: number
  stock: number
  description: string
  accessType: 'free' | 'paid'
  imageUrl: string
  downloadUrl?: string
  // CBT specific
  questions?: QuestionItem[]
  duration?: string
  limitAttempts?: number
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
  type: 'material' | 'cbt'
  title: string
  category: string
  format: string
  price: number
  stock: number
  description: string
  accessType?: 'free' | 'paid'
  imageUrl?: string
  downloadUrl?: string
  questions?: QuestionItem[]
  duration?: string
  limitAttempts?: number
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
    id: 'english-language-cbt-demo',
    type: 'cbt',
    title: 'English Language CBT',
    category: 'Core subject',
    format: 'CBT Exam',
    price: 0,
    stock: 999,
    description: 'Practice comprehension, grammar, and objective questions in a timed CBT format.',
    accessType: 'free',
    imageUrl: getFallbackImage('Languages'),
    duration: '45 mins',
    limitAttempts: 0,
    questions: [
      {
        id: 'eng-1',
        type: 'objective',
        prompt: 'Choose the correctly punctuated sentence.',
        options: ['lets eat grandma', "Let's eat, Grandma.", 'Lets, eat grandma.'],
        correctOption: "Let's eat, Grandma.",
      },
      {
        id: 'eng-2',
        type: 'objective',
        prompt: 'The antonym of "scarce" is:',
        options: ['Rare', 'Plenty', 'Little'],
        correctOption: 'Plenty',
      },
      {
        id: 'eng-3',
        type: 'theory',
        prompt: 'In 2-3 sentences, explain why reading comprehension matters in exam preparation.',
      },
    ],
  },
  {
    id: 'waec-complete-mock-demo',
    type: 'cbt',
    title: 'WAEC Complete Mock CBT',
    category: 'Premium mock',
    format: 'CBT Exam',
    price: 7500,
    stock: 100,
    description: 'Multi-section simulation exam with full scoring and progress feedback.',
    accessType: 'paid',
    imageUrl: getFallbackImage('Revision'),
    duration: '1 hr 20 mins',
    limitAttempts: 2,
    questions: [
      {
        id: 'waec-1',
        type: 'objective',
        prompt: 'Nigeria gained independence in which year?',
        options: ['1957', '1960', '1963'],
        correctOption: '1960',
      },
    ],
  },
  {
    id: 'english-essay-starter',
    type: 'material',
    title: 'English Essay Starter',
    category: 'Languages',
    format: 'PDF Guide',
    price: 0,
    stock: 999,
    description: 'A concise writing guide with sample essay structures for senior secondary students.',
    accessType: 'free',
    imageUrl: getFallbackImage('Languages'),
    downloadUrl: sampleFreeMaterialPdf,
  },
  {
    id: 'waec-math-accelerator',
    type: 'material',
    title: 'WAEC Math Accelerator',
    category: 'Mathematics',
    format: 'PDF Guide',
    price: 4500,
    stock: 40,
    description: 'Focused lesson notes, worked examples, and timed revision drills for exam candidates.',
    accessType: 'paid',
    imageUrl: getFallbackImage('Mathematics'),
    downloadUrl: sampleFreeMaterialPdf,
  },
]

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

export const useCatalogStore = defineStore('catalog', () => {
  const products = ref<Product[]>(readStorage('atcsedu-products', catalogSeed))
  const cart = ref<CartItem[]>(readStorage('atcsedu-cart', []))
  const purchasedIds = ref<string[]>(readStorage('atcsedu-purchased', []))
  const categories = ref<string[]>(readStorage('atcsedu-categories', ['Mathematics', 'Science', 'Languages', 'Revision', 'Core subject', 'Premium mock']))

  const revenueEstimate = computed(() =>
    // Simulated: count all items in purchasedIds that are paid
    purchasedIds.value.reduce((total, id) => {
      const p = products.value.find((prod) => prod.id === id)
      return total + (p?.price || 0)
    }, 0),
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

  const updateProduct = (updated: ProductInput) => {
    const idx = products.value.findIndex(p => p.id === updated.id)
    if (idx !== -1) {
      products.value[idx] = {
        ...products.value[idx],
        ...updated,
        imageUrl: updated.imageUrl ?? getFallbackImage(updated.category),
        downloadUrl: updated.downloadUrl,
      }
    }
  }

  const deleteProduct = (id: string) => {
    products.value = products.value.filter((p) => p.id !== id)
  }

  const buyProduct = (id: string) => {
    if (!purchasedIds.value.includes(id)) {
      purchasedIds.value.push(id)
    }
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

  watch(
    purchasedIds,
    (value) => {
      persistStorage('atcsedu-purchased', value)
    },
    { deep: true },
  )

  watch(
    categories,
    (value) => {
      persistStorage('atcsedu-categories', value)
    },
    { deep: true },
  )

  return {
    products,
    purchasedIds,
    categories,
    cartItems,
    cartItemCount,
    cartTotal,
    revenueEstimate,
    addToCart,
    updateQuantity,
    addProduct,
    addProductsBulk,
    deleteProduct,
    buyProduct,
    addCategory: (name: string) => {
      const trimmed = name.trim()
      if (trimmed && !categories.value.some(c => c.toLowerCase() === trimmed.toLowerCase())) {
        categories.value.push(trimmed)
      }
    },
    deleteCategory: (name: string) => {
      const index = categories.value.findIndex(c => c === name)
      if (index !== -1) {
        categories.value.splice(index, 1)
      }
    },
  }
})
