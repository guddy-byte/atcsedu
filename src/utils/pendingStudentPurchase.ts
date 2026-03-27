export type PendingStudentPurchase = {
  productId: string
  productType: 'material' | 'cbt'
  createdAt: string
}

const PENDING_STUDENT_PURCHASE_KEY = 'atcsedu-pending-student-purchase'

const canUseStorage = typeof window !== 'undefined'

const isValidPendingPurchase = (value: unknown): value is PendingStudentPurchase => {
  if (!value || typeof value !== 'object') {
    return false
  }

  const candidate = value as Record<string, unknown>

  return typeof candidate.productId === 'string'
    && (candidate.productType === 'material' || candidate.productType === 'cbt')
    && typeof candidate.createdAt === 'string'
}

export const getPendingStudentPurchase = (): PendingStudentPurchase | null => {
  if (!canUseStorage) {
    return null
  }

  const rawValue = window.localStorage.getItem(PENDING_STUDENT_PURCHASE_KEY)

  if (!rawValue) {
    return null
  }

  try {
    const parsedValue = JSON.parse(rawValue)
    return isValidPendingPurchase(parsedValue) ? parsedValue : null
  } catch {
    return null
  }
}

export const setPendingStudentPurchase = (productId: string, productType: PendingStudentPurchase['productType']) => {
  if (!canUseStorage) {
    return
  }

  const payload: PendingStudentPurchase = {
    productId,
    productType,
    createdAt: new Date().toISOString(),
  }

  window.localStorage.setItem(PENDING_STUDENT_PURCHASE_KEY, JSON.stringify(payload))
}

export const clearPendingStudentPurchase = () => {
  if (!canUseStorage) {
    return
  }

  window.localStorage.removeItem(PENDING_STUDENT_PURCHASE_KEY)
}

export const consumePendingStudentPurchase = (): PendingStudentPurchase | null => {
  const pendingPurchase = getPendingStudentPurchase()

  if (pendingPurchase) {
    clearPendingStudentPurchase()
  }

  return pendingPurchase
}
