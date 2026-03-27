import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'

import { apiRequest } from '../lib/api'

export interface QuestionOptionItem {
  id: string
  label?: string
  text: string
  isCorrect?: boolean
  position?: number
}

export interface QuestionItem {
  id: string
  type: 'objective' | 'theory'
  prompt: string
  options?: string[]
  optionRecords?: QuestionOptionItem[]
  correctOption?: string
  points?: number
  position?: number
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
  downloadUrl?: string | null
  questions?: QuestionItem[]
  duration?: string
  limitAttempts?: number
  slug?: string
  isPublished?: boolean
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
  id?: string
  slug?: string
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
  isPublished?: boolean
}

interface PaginatedResponse<T> {
  status: string
  data: {
    items: T[]
    meta?: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

interface MaterialApi {
  id: number
  title: string
  category: string
  access_type: 'free' | 'paid'
  price: number
  format: string | null
  description: string | null
  cover_url: string | null
  download_url: string | null
  is_published?: boolean
}

interface ExamSummaryApi {
  id: number
  title: string
  slug: string
  category: string | null
  access_type: 'free' | 'paid'
  price: number
  duration_minutes: number
  attempt_limit?: number
  description: string | null
  questions_count?: number
  is_published?: boolean
}

interface ExamDetailApi extends ExamSummaryApi {
  questions: Array<{
    id: number
    type: 'objective' | 'theory'
    prompt: string
    points: number
    position: number
    options: Array<{
      id: number
      label?: string | null
      option_text: string
      is_correct?: boolean
      position?: number
    }>
  }>
}

interface MaterialDetailResponse {
  status: string
  data: {
    material: MaterialApi
  }
}

interface ExamDetailResponse {
  status: string
  data: {
    exam: ExamDetailApi
  }
}

interface PaymentInitializeResponse {
  status: string
  data: {
    reference: string
    authorization_url: string | null
    access_code: string | null
    is_simulated?: boolean
  }
}

type ServerPurchaseItemType = 'exam' | 'material'

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
  'Core subject':
    'https://images.unsplash.com/photo-1513258496099-48168024aec0?auto=format&fit=crop&w=1200&q=80',
  'Premium mock':
    'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1200&q=80',
}

const getFallbackImage = (category: string) =>
  fallbackImageByCategory[category] ?? fallbackImageByCategory.General

const canUseStorage = typeof window !== 'undefined'
const PURCHASE_STORAGE_KEY = 'atcsedu-purchased'

const toPurchaseKey = (productId: string, productType: Product['type']) => `${productType}:${productId}`

const toProductType = (itemType: ServerPurchaseItemType): Product['type'] =>
  itemType === 'exam' ? 'cbt' : 'material'

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

const toDurationLabel = (minutes: number) => {
  if (minutes >= 60) {
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60

    if (remainingMinutes === 0) {
      return `${hours} hr`
    }

    return `${hours} hr ${remainingMinutes} mins`
  }

  return `${minutes} mins`
}

const toDurationMinutes = (duration?: string) => {
  if (!duration) {
    return 45
  }

  const parts = duration.toLowerCase().split(' ')
  let totalMinutes = 0

  for (let index = 0; index < parts.length; index += 1) {
    const value = Number.parseInt(parts[index], 10)

    if (Number.isNaN(value)) {
      continue
    }

    if (parts[index + 1]?.startsWith('hr')) {
      totalMinutes += value * 60
    }

    if (parts[index + 1]?.startsWith('min')) {
      totalMinutes += value
    }
  }

  return totalMinutes || 45
}

const mapMaterial = (material: MaterialApi): Product => ({
  id: String(material.id),
  type: 'material',
  title: material.title,
  category: material.category,
  format: material.format || 'PDF Guide',
  price: Number(material.price || 0),
  stock: 999,
  description: material.description || '',
  accessType: material.access_type,
  imageUrl: material.cover_url || getFallbackImage(material.category),
  downloadUrl: material.download_url,
  isPublished: material.is_published ?? true,
})

const mapExamQuestion = (question: ExamDetailApi['questions'][number]): QuestionItem => ({
  id: String(question.id),
  type: question.type,
  prompt: question.prompt,
  points: question.points,
  position: question.position,
  options: question.options.map((option) => option.option_text),
  optionRecords: question.options.map((option) => ({
    id: String(option.id),
    label: option.label ?? undefined,
    text: option.option_text,
    isCorrect: option.is_correct,
    position: option.position,
  })),
  correctOption: question.options.find((option) => option.is_correct)?.option_text,
})

const mapExam = (exam: ExamSummaryApi): Product => ({
  id: String(exam.id),
  type: 'cbt',
  title: exam.title,
  category: exam.category || 'General',
  format: 'CBT Exam',
  price: Number(exam.price || 0),
  stock: 999,
  description: exam.description || '',
  accessType: exam.access_type,
  imageUrl: getFallbackImage(exam.category || 'General'),
  duration: toDurationLabel(exam.duration_minutes),
  limitAttempts: exam.attempt_limit ?? 0,
  slug: exam.slug,
  isPublished: exam.is_published ?? true,
})

const mapExamDetail = (exam: ExamDetailApi): Product => ({
  ...mapExam(exam),
  questions: exam.questions.map(mapExamQuestion),
})

const uniqueCategories = (products: Product[], existing: string[]) => {
  const set = new Set<string>(existing)

  products.forEach((product) => {
    if (product.category) {
      set.add(product.category)
    }
  })

  return Array.from(set)
}

export const useCatalogStore = defineStore('catalog', () => {
  const products = ref<Product[]>([])
  const cart = ref<CartItem[]>(readStorage('atcsedu-cart', []))
  const purchasedIds = ref<string[]>(readStorage(PURCHASE_STORAGE_KEY, []))
  const categories = ref<string[]>([])
  const isLoading = ref(false)
  const hasLoaded = ref(false)
  const loadError = ref('')

  const normalizePurchasedKeys = (entries: string[], availableProducts: Product[]) => {
    const normalized = new Set<string>()

    entries.forEach((entry) => {
      if (entry.includes(':')) {
        if (availableProducts.some((product) => toPurchaseKey(product.id, product.type) === entry)) {
          normalized.add(entry)
        }

        return
      }

      const matches = availableProducts.filter((product) => product.id === entry)

      if (matches.length === 1) {
        normalized.add(toPurchaseKey(matches[0].id, matches[0].type))
      }
    })

    return Array.from(normalized)
  }

  const syncCategories = () => {
    categories.value = uniqueCategories(products.value, [])
  }

  const upsertProduct = (product: Product) => {
    const existingIndex = products.value.findIndex((entry) => entry.id === product.id && entry.type === product.type)

    if (existingIndex === -1) {
      products.value.unshift(product)
      syncCategories()
      return
    }

    products.value[existingIndex] = {
      ...products.value[existingIndex],
      ...product,
    }
    syncCategories()
  }

  const replaceProducts = (nextProducts: Product[]) => {
    products.value = nextProducts
    cart.value = cart.value.filter((item) => nextProducts.some((product) => product.id === item.productId))
    purchasedIds.value = normalizePurchasedKeys(purchasedIds.value, nextProducts)
    syncCategories()
  }

  const isProductPurchased = (productId: string, productType: Product['type']) =>
    purchasedIds.value.includes(toPurchaseKey(productId, productType))

  const initialize = async (force = false) => {
    if (hasLoaded.value && !force) {
      return
    }

    isLoading.value = true
    loadError.value = ''

    try {
      const [materialsResponse, examsResponse] = await Promise.all([
        apiRequest<PaginatedResponse<MaterialApi>>('/materials?per_page=100'),
        apiRequest<PaginatedResponse<ExamSummaryApi>>('/exams?per_page=100'),
      ])

      replaceProducts([
        ...examsResponse.data.items.map(mapExam),
        ...materialsResponse.data.items.map(mapMaterial),
      ])
      hasLoaded.value = true
    } catch (error) {
      loadError.value = error instanceof Error ? error.message : 'Unable to load catalog data right now.'
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const fetchExamDetails = async (productId: string) => {
    const response = await apiRequest<ExamDetailResponse>(`/exams/${productId}`)
    const product = mapExamDetail(response.data.exam)
    upsertProduct(product)
    return product
  }

  const fetchAdminExam = async (productId: string) => {
    const response = await apiRequest<ExamDetailResponse>(`/admin/exams/${productId}`)
    const product = mapExamDetail(response.data.exam)
    upsertProduct(product)
    return product
  }

  const fetchMaterialDetails = async (productId: string) => {
    const response = await apiRequest<MaterialDetailResponse>(`/materials/${productId}`)
    const product = mapMaterial(response.data.material)
    upsertProduct(product)
    return product
  }

  const applyExamReview = (productId: string, review: Array<{
    question_id: number
    correct_option_text?: string | null
  }>) => {
    const product = products.value.find((entry) => entry.id === productId && entry.type === 'cbt')

    if (!product?.questions) {
      return
    }

    const reviewMap = new Map(review.map((item) => [String(item.question_id), item.correct_option_text || undefined]))

    product.questions = product.questions.map((question) => ({
      ...question,
      correctOption: reviewMap.get(question.id) ?? question.correctOption,
    }))
  }

  const revenueEstimate = computed(() =>
    products.value.reduce((total, product) => (
      isProductPurchased(product.id, product.type) ? total + product.price : total
    ), 0),
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

  const buyProduct = (productId: string, productType: Product['type']) => {
    const purchaseKey = toPurchaseKey(productId, productType)

    if (!purchasedIds.value.includes(purchaseKey)) {
      purchasedIds.value.push(purchaseKey)
    }
  }

  const markServerPurchase = (itemType: ServerPurchaseItemType, itemId: number | string) => {
    buyProduct(String(itemId), toProductType(itemType))
  }

  const purchaseProduct = async (productId: string, productType: Product['type']) => {
    const product = products.value.find((entry) => entry.id === productId && entry.type === productType)

    if (!product) {
      throw new Error('The selected product could not be found.')
    }

    const response = await apiRequest<PaymentInitializeResponse>('/payments/paystack/initialize', {
      method: 'POST',
      body: {
        item_type: product.type === 'cbt' ? 'exam' : 'material',
        item_id: Number(product.id),
      },
    })

    if (response.data.is_simulated) {
      await apiRequest(`/payments/${response.data.reference}/verify?simulate=1`)
      buyProduct(product.id, product.type)
      return response.data
    }

    if (response.data.authorization_url && typeof window !== 'undefined') {
      window.location.href = response.data.authorization_url
    }

    return response.data
  }

  const toMaterialPayload = (product: ProductInput) => ({
    title: product.title,
    category: product.category,
    access_type: product.accessType ?? (product.price > 0 ? 'paid' : 'free'),
    price: product.price,
    format: product.format,
    description: product.description,
    cover_url: product.imageUrl,
    download_url: product.downloadUrl,
    is_published: product.isPublished ?? true,
  })

  const toExamPayload = (product: ProductInput) => ({
    title: product.title,
    slug: product.slug,
    category: product.category,
    access_type: product.accessType ?? (product.price > 0 ? 'paid' : 'free'),
    price: product.price,
    duration_minutes: toDurationMinutes(product.duration),
    attempt_limit: product.limitAttempts ?? 0,
    description: product.description,
    is_published: product.isPublished ?? true,
  })

  const toQuestionPayload = (question: QuestionItem, index: number) => ({
    type: question.type,
    prompt: question.prompt,
    points: question.points ?? (question.type === 'theory' ? 5 : 1),
    position: question.position ?? index + 1,
    options: question.type === 'objective'
      ? (question.options ?? []).map((option, optionIndex) => ({
          label: String.fromCharCode(65 + optionIndex),
          option_text: option,
          is_correct: question.correctOption === option,
          position: optionIndex + 1,
        }))
      : [],
  })

  const addProduct = async (product: ProductInput) => {
    if (product.type === 'material') {
      const response = await apiRequest<{ status: string; data: { material: MaterialApi } }>('/admin/materials', {
        method: 'POST',
        body: toMaterialPayload(product),
      })

      upsertProduct(mapMaterial(response.data.material))
      return
    }

    const examResponse = await apiRequest<{ status: string; data: { exam: ExamSummaryApi } }>('/admin/exams', {
      method: 'POST',
      body: toExamPayload(product),
    })

    const examId = String(examResponse.data.exam.id)

    for (const [index, question] of (product.questions ?? []).entries()) {
      await apiRequest(`/admin/exams/${examId}/questions`, {
        method: 'POST',
        body: toQuestionPayload(question, index),
      })
    }

    await fetchAdminExam(examId)
  }

  const addProductsBulk = async (newProducts: ProductInput[]) => {
    for (const product of newProducts) {
      await addProduct(product)
    }
  }

  const updateProduct = async (updated: ProductInput) => {
    if (!updated.id) {
      throw new Error('Missing product id for update.')
    }

    if (updated.type === 'material') {
      const response = await apiRequest<{ status: string; data: { material: MaterialApi } }>(`/admin/materials/${updated.id}`, {
        method: 'PUT',
        body: toMaterialPayload(updated),
      })

      upsertProduct(mapMaterial(response.data.material))
      return
    }

    await apiRequest(`/admin/exams/${updated.id}`, {
      method: 'PUT',
      body: toExamPayload(updated),
    })

    const existingExam = await fetchAdminExam(updated.id)

    for (const question of existingExam.questions ?? []) {
      await apiRequest(`/admin/questions/${question.id}`, {
        method: 'DELETE',
      })
    }

    for (const [index, question] of (updated.questions ?? []).entries()) {
      await apiRequest(`/admin/exams/${updated.id}/questions`, {
        method: 'POST',
        body: toQuestionPayload(question, index),
      })
    }

    await fetchAdminExam(updated.id)
  }

  const deleteProduct = async (id: string) => {
    const product = products.value.find((entry) => entry.id === id)

    if (!product) {
      return
    }

    const endpoint = product.type === 'material'
      ? `/admin/materials/${id}`
      : `/admin/exams/${id}`

    await apiRequest(endpoint, {
      method: 'DELETE',
    })

    products.value = products.value.filter((entry) => entry.id !== id)
  }

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
      persistStorage(PURCHASE_STORAGE_KEY, value)
    },
    { deep: true },
  )

  return {
    products,
    purchasedIds,
    isProductPurchased,
    categories,
    isLoading,
    hasLoaded,
    loadError,
    cartItems,
    cartItemCount,
    cartTotal,
    revenueEstimate,
    initialize,
    fetchExamDetails,
    fetchAdminExam,
    fetchMaterialDetails,
    applyExamReview,
    addToCart,
    updateQuantity,
    addProduct,
    addProductsBulk,
    updateProduct,
    deleteProduct,
    buyProduct,
    markServerPurchase,
    purchaseProduct,
    addCategory: (name: string) => {
      const trimmed = name.trim()
      if (trimmed && !categories.value.some((category) => category.toLowerCase() === trimmed.toLowerCase())) {
        categories.value.push(trimmed)
      }
    },
    deleteCategory: (name: string) => {
      const index = categories.value.findIndex((category) => category === name)
      if (index !== -1) {
        categories.value.splice(index, 1)
      }
    },
  }
})
