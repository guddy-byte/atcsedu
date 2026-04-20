<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { apiRequest, getApiBaseUrl, getApiToken } from '../lib/api'
import { useCatalogStore } from '../stores/catalog'
import { setPendingStudentPurchase } from '../utils/pendingStudentPurchase'
import { isStudentAuthenticated } from '../utils/studentAuth'

interface AttemptRecord {
  attemptId: string
  examId: string
  examTitle: string
  scorePercent: number
  passed: boolean
  status: string
}

interface DashboardStats {
  total_exams_on_platform: number
  exams_attempted: number
  exams_passed: number
  exams_failed: number
}

const catalog = useCatalogStore()
const route = useRoute()
const router = useRouter()

const isAuthenticated = computed(() => isStudentAuthenticated())
const viewMode = ref<'dashboard' | 'exam' | 'viewer'>('dashboard')
const currentTab = ref<'free' | 'paid'>('free')
const searchQuery = ref('')
const selectedExamId = ref('')
const selectedMaterialId = ref('')
const answers = ref<Record<string, string>>({})
const attempts = ref<AttemptRecord[]>([])
const stats = ref<DashboardStats | null>(null)
const errorMessage = ref('')
const isLoading = ref(false)
const isSubmitting = ref(false)
const purchasePendingId = ref('')
const examLoadingId = ref('')
const paidMaterialsLibraryRef = ref<HTMLElement | null>(null)
const showSubmitModal = ref(false)
const showResultModal = ref(false)
const isReviewing = ref(false)
const timeLeft = ref(0)
const timerInterval = ref<number | null>(null)
const resultDetails = ref<{ score: number; passed: boolean } | null>(null)

const filteredExams = computed(() =>
  catalog.products.filter((product) =>
    product.type === 'cbt' &&
    product.accessType === currentTab.value &&
    product.title.toLowerCase().includes(searchQuery.value.toLowerCase()),
  )
)

const purchasedMaterials = computed(() =>
  catalog.products.filter((product) =>
    product.type === 'material'
    && product.accessType === 'paid'
    && catalog.isProductPurchased(product.id, product.type)),
)

const selectedExam = computed(() =>
  catalog.products.find((product) => product.id === selectedExamId.value && product.type === 'cbt'),
)

const selectedMaterial = computed(() =>
  catalog.products.find((product) => product.id === selectedMaterialId.value && product.type === 'material'),
)

const totalQuestions = computed(() => selectedExam.value?.questions?.length ?? 0)
const totalAnswered = computed(() => Object.values(answers.value).filter((value) => value?.trim()).length)

const parseDuration = (duration?: string) => {
  if (!duration) return 45 * 60
  const parts = duration.toLowerCase().split(' ')
  let seconds = 0

  for (let index = 0; index < parts.length; index += 1) {
    const value = Number.parseInt(parts[index], 10)
    if (Number.isNaN(value)) continue
    if (parts[index + 1]?.startsWith('hr')) seconds += value * 3600
    if (parts[index + 1]?.startsWith('min')) seconds += value * 60
  }

  return seconds || 45 * 60
}

const formatTime = (seconds: number) => {
  const minutes = Math.floor(seconds / 60)
  const remainingSeconds = seconds % 60
  return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const viewerUrl = ref<string | null>(null)
const isLoadingViewerBlob = ref(false)

const isGoogleDriveUrl = (url: string) =>
  /drive\.google\.com\/file\/d\//i.test(url)

const loadViewerBlob = async (material: { id: string; downloadUrl?: string | null }) => {
  // Google Drive files can't be proxied — open externally
  if (material.downloadUrl && isGoogleDriveUrl(material.downloadUrl)) {
    const match = material.downloadUrl.match(/drive\.google\.com\/file\/d\/([^/]+)/i)
    if (match) window.open(`https://drive.google.com/file/d/${match[1]}/preview`, '_blank', 'noreferrer')
    return
  }

  viewerUrl.value = null
  isLoadingViewerBlob.value = true
  try {
    const token = getApiToken()
    const res = await fetch(`${getApiBaseUrl()}/materials/${material.id}/view`, {
      headers: {
        Authorization: `Bearer ${token ?? ''}`,
        Accept: 'application/json',
      },
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.message ?? 'Could not load material.')
    viewerUrl.value = json.data.url
  } catch (err) {
    errorMessage.value = err instanceof Error ? err.message : 'Failed to load material.'
  } finally {
    isLoadingViewerBlob.value = false
  }
}
const recentlyPurchasedMaterialId = computed(() =>
  typeof route.query.purchased_material === 'string' ? route.query.purchased_material : '',
)
const shouldSpotlightPaidLibrary = computed(() =>
  route.query.library === 'paid-materials' || route.query.purchase === 'success',
)

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const scrollToPaidMaterialsLibrary = () => {
  if (typeof window === 'undefined') {
    return
  }

  window.setTimeout(() => {
    paidMaterialsLibraryRef.value?.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
    })
  }, 150)
}

const stopTimer = () => {
  if (!timerInterval.value) return
  window.clearInterval(timerInterval.value)
  timerInterval.value = null
}

const startTimer = () => {
  stopTimer()
  timerInterval.value = window.setInterval(() => {
    if (timeLeft.value <= 0) {
      stopTimer()
      void submitExam()
      return
    }

    timeLeft.value -= 1
  }, 1000)
}

const syncPaidExamAccess = async () => {
  if (!isAuthenticated.value) return

  const paidExams = catalog.products.filter((product) => product.type === 'cbt' && product.accessType === 'paid')
  const accessResponses = await Promise.allSettled(
    paidExams.map((exam) =>
      apiRequest<{ status: string; data: { has_access: boolean } }>(`/exams/${exam.id}/access`)),
  )

  accessResponses.forEach((result, index) => {
    if (result.status === 'fulfilled' && result.value.data.has_access) {
      catalog.buyProduct(paidExams[index].id, paidExams[index].type)
    }
  })
}

const syncPaidMaterialAccess = async () => {
  if (!isAuthenticated.value) return

  const paidMaterials = catalog.products.filter((product) => product.type === 'material' && product.accessType === 'paid')
  const detailResponses = await Promise.allSettled(
    paidMaterials.map((material) => catalog.fetchMaterialDetails(material.id)),
  )

  detailResponses.forEach((result, index) => {
    if (result.status === 'fulfilled' && result.value.downloadUrl) {
      catalog.buyProduct(paidMaterials[index].id, paidMaterials[index].type)
    }
  })
}

const loadStudentData = async () => {
  if (!isAuthenticated.value) return

  try {
    const [statsResponse, attemptsResponse] = await Promise.all([
      apiRequest<{ status: string; data: DashboardStats }>('/student/dashboard/stats'),
      apiRequest<{ status: string; data: { items: Array<{
        attempt_id: number
        exam_id: number
        exam_title: string
        score_percent: number
        passed: boolean
        status: string
      }> } }>('/student/attempts?per_page=10'),
    ])

    stats.value = statsResponse.data
    attempts.value = attemptsResponse.data.items.map((attempt) => ({
      attemptId: String(attempt.attempt_id),
      examId: String(attempt.exam_id),
      examTitle: attempt.exam_title,
      scorePercent: Number(attempt.score_percent),
      passed: attempt.passed,
      status: attempt.status,
    }))
    await syncPaidExamAccess()
    await syncPaidMaterialAccess()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to load student data.'
    scrollToTop()
  }
}

const startExam = async (examId: string) => {
  errorMessage.value = ''

  if (!isAuthenticated.value) {
    await router.push('/auth/login')
    return
  }

  examLoadingId.value = examId

  try {
    const exam = catalog.products.find((product) => product.id === examId && product.type === 'cbt')
    if (!exam) throw new Error('Exam not found.')
    if (exam.accessType === 'paid' && !catalog.isProductPurchased(exam.id, exam.type)) {
      throw new Error('Please unlock this exam before starting.')
    }

    const detailedExam = await catalog.fetchExamDetails(examId)
    selectedExamId.value = examId
    answers.value = {}
    isReviewing.value = false
    showResultModal.value = false
    timeLeft.value = parseDuration(detailedExam.duration)
    viewMode.value = 'exam'
    startTimer()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to start exam.'
    scrollToTop()
  } finally {
    examLoadingId.value = ''
  }
}

const unlockPaidProduct = async (productId: string) => {
  errorMessage.value = ''

  if (!isAuthenticated.value) {
    setPendingStudentPurchase(productId, 'cbt')
    await router.push('/auth/signup')
    return
  }

  purchasePendingId.value = productId

  try {
    await catalog.purchaseProduct(productId, 'cbt')
    await loadStudentData()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to unlock product.'
    scrollToTop()
  } finally {
    purchasePendingId.value = ''
  }
}

const viewMaterial = async (materialId: string) => {
  try {
    const material = await catalog.fetchMaterialDetails(materialId)

    if (!material.downloadUrl) {
      throw new Error('This material is not ready for viewing yet. Please contact support if payment has already been completed.')
    }

    selectedMaterialId.value = materialId
    viewMode.value = 'viewer'
    await loadViewerBlob(material)
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to open material.'
  }
}

const openMaterialFromRoute = async () => {
  if (!isAuthenticated.value) {
    return
  }

  const shouldOpenViewer = route.query.view === 'viewer'
  const materialId = typeof route.query.material === 'string' ? route.query.material : ''

  if (!shouldOpenViewer || !materialId) {
    return
  }

  if (selectedMaterialId.value === materialId && viewMode.value === 'viewer') {
    return
  }

  await viewMaterial(materialId)
}

const closeMaterialViewer = async () => {
  viewerUrl.value = null
  selectedMaterialId.value = ''
  viewMode.value = 'dashboard'
  await router.push('/exam-training')
}

const submitExam = async () => {
  if (!selectedExam.value) return

  const payloadAnswers: Array<{ question_id: number; selected_option_id?: number; theory_answer?: string }> = []

  for (const question of selectedExam.value.questions ?? []) {
    const answer = answers.value[question.id]?.trim()
    if (!answer) continue

    if (question.type === 'objective') {
      const option = question.optionRecords?.find((item) => item.text === answer)
      if (!option) continue

      payloadAnswers.push({ question_id: Number(question.id), selected_option_id: Number(option.id) })
      continue
    }

    payloadAnswers.push({ question_id: Number(question.id), theory_answer: answer })
  }

  if (!payloadAnswers.length) {
    errorMessage.value = 'Answer at least one question before submitting.'
    showSubmitModal.value = false
    return
  }

  isSubmitting.value = true

  try {
    const response = await apiRequest<{
      status: string
      data: {
        attempt: { id: number; status: string; score_percent: number; passed: boolean }
        review: Array<{ question_id: number; correct_option_text?: string | null }>
      }
    }>(`/exams/${selectedExam.value.id}/attempts`, {
      method: 'POST',
      body: { answers: payloadAnswers },
    })

    stopTimer()
    showSubmitModal.value = false
    catalog.applyExamReview(selectedExam.value.id, response.data.review)
    resultDetails.value = {
      score: Math.round(response.data.attempt.score_percent),
      passed: response.data.attempt.passed,
    }
    showResultModal.value = true
    await loadStudentData()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to submit exam.'
  } finally {
    isSubmitting.value = false
  }
}

const closeExam = () => {
  stopTimer()
  viewMode.value = 'dashboard'
  selectedExamId.value = ''
  answers.value = {}
  isReviewing.value = false
  showResultModal.value = false
  showSubmitModal.value = false
}

onMounted(async () => {
  isLoading.value = true
  try {
    await catalog.initialize()
    await loadStudentData()
    await openMaterialFromRoute()
    if (shouldSpotlightPaidLibrary.value) {
      scrollToPaidMaterialsLibrary()
    }
  } finally {
    isLoading.value = false
  }
})

watch(() => isAuthenticated.value, (nextValue) => {
  if (nextValue) {
    void loadStudentData()
  }
})

watch(
  () => [route.query.view, route.query.material, isAuthenticated.value] as const,
  ([nextView, nextMaterial, isLoggedIn]) => {
    if (!isLoggedIn) {
      return
    }

    if (nextView === 'viewer' && typeof nextMaterial === 'string') {
      void viewMaterial(nextMaterial)
      return
    }

    if (viewMode.value === 'viewer') {
      viewerUrl.value = null
      selectedMaterialId.value = ''
      viewMode.value = 'dashboard'
    }
  },
)

watch(
  () => [route.query.library, route.query.purchase, purchasedMaterials.value.length] as const,
  ([library, purchase]) => {
    if (library === 'paid-materials' || purchase === 'success') {
      scrollToPaidMaterialsLibrary()
    }
  },
)

onUnmounted(() => stopTimer())
</script>

<template>
  <div class="space-y-6">
    <section v-if="errorMessage || catalog.loadError" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
      {{ errorMessage || catalog.loadError }}
    </section>

    <section v-if="viewMode === 'dashboard'" class="grid gap-6">
      <div class="rounded-[2rem] border border-white/80 bg-white p-8 shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-secondary">Student Dashboard</p>
            <h2 class="mt-2 text-4xl font-black tracking-tight text-slate-900">Exam Training</h2>
          </div>
          <div class="flex gap-3">
            <div class="rounded-2xl bg-slate-100 px-5 py-4 text-center">
              <p class="text-[10px] font-black uppercase text-slate-400">Exams</p>
              <p class="mt-1 text-2xl font-black text-slate-900">{{ stats?.total_exams_on_platform ?? filteredExams.length }}</p>
            </div>
            <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-center">
              <p class="text-[10px] font-black uppercase text-emerald-600">Passed</p>
              <p class="mt-1 text-2xl font-black text-emerald-700">{{ stats?.exams_passed ?? 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="rounded-[2rem] border border-slate-100 bg-white p-8 text-sm font-semibold text-slate-500">
        Loading your dashboard...
      </div>

      <div v-else class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
        <section class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
          <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="inline-flex rounded-full border border-slate-100 bg-slate-50 p-1">
              <button @click="currentTab = 'free'" class="rounded-full px-4 py-2 text-xs font-black" :class="currentTab === 'free' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Free Practice</button>
              <button @click="currentTab = 'paid'" class="rounded-full px-4 py-2 text-xs font-black" :class="currentTab === 'paid' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Premium Mock</button>
            </div>
            <input v-model="searchQuery" type="text" placeholder="Search exams" class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm outline-none" />
          </div>

          <div class="space-y-4">
            <article v-for="exam in filteredExams" :key="exam.id" class="rounded-2xl border border-slate-100 p-5">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p class="text-lg font-black text-slate-900">{{ exam.title }}</p>
                  <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ exam.category }} | {{ exam.duration }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold text-slate-900">{{ exam.accessType === 'free' ? 'Free' : `NGN ${exam.price.toLocaleString()}` }}</p>
                  <p v-if="exam.accessType === 'paid'" class="text-[11px] text-slate-400">{{ catalog.isProductPurchased(exam.id, exam.type) ? 'Unlocked' : 'Locked' }}</p>
                </div>
              </div>
              <p class="mt-3 text-sm text-slate-600">{{ exam.description }}</p>
              <div class="mt-4 flex flex-wrap gap-3">
                <button
                  v-if="exam.accessType === 'free' || catalog.isProductPurchased(exam.id, exam.type)"
                  class="rounded-full bg-slate-900 px-5 py-2.5 text-xs font-black text-white disabled:opacity-70"
                  :disabled="examLoadingId === exam.id"
                  @click="void startExam(exam.id)"
                >
                  {{ examLoadingId === exam.id ? 'Loading...' : 'Take Exam' }}
                </button>
                <button
                  v-else
                  class="rounded-full bg-primary px-5 py-2.5 text-xs font-black text-white disabled:opacity-70"
                  :disabled="purchasePendingId === exam.id"
                  @click="void unlockPaidProduct(exam.id)"
                >
                  {{ purchasePendingId === exam.id ? 'Processing...' : 'Unlock Exam' }}
                </button>
              </div>
            </article>
          </div>
        </section>

        <aside class="space-y-6">
          <section ref="paidMaterialsLibraryRef" class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
            <h3 class="text-lg font-black text-slate-900">Recent Attempts</h3>
            <div v-if="attempts.length" class="mt-5 space-y-4">
              <article v-for="attempt in attempts.slice(0, 5)" :key="attempt.attemptId" class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ attempt.examTitle }}</p>
                  <p class="text-xs text-slate-500">Score: {{ attempt.scorePercent }}%</p>
                </div>
                <span class="text-xs font-black" :class="attempt.passed ? 'text-emerald-600' : 'text-rose-600'">{{ attempt.passed ? 'PASS' : 'FAIL' }}</span>
              </article>
            </div>
            <p v-else class="mt-5 text-sm text-slate-500">No attempts yet.</p>
          </section>

          <section class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-lg font-black text-slate-900">Paid Materials Library</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                  Purchased materials stay here for view-only access inside your dashboard. Free downloads are meant to live on the student's own device.
                </p>
              </div>
              <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center">
                <p class="text-[10px] font-black uppercase text-slate-400">Purchased</p>
                <p class="mt-1 text-xl font-black text-slate-900">{{ purchasedMaterials.length }}</p>
              </div>
            </div>

            <div
              v-if="route.query.purchase === 'success'"
              class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-4 text-sm font-medium text-emerald-700"
            >
              Payment successful. Your material has been added to the Paid Materials Library below.
            </div>

            <div v-if="purchasedMaterials.length" class="mt-5 space-y-3">
              <button
                v-for="material in purchasedMaterials"
                :key="material.id"
                class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left transition"
                :class="
                  material.id === recentlyPurchasedMaterialId
                    ? 'border border-emerald-200 bg-emerald-50 ring-2 ring-emerald-100'
                    : 'bg-slate-50 hover:bg-slate-100'
                "
                @click="void router.push({ path: '/exam-training', query: { view: 'viewer', material: material.id } })"
              >
                <span>
                  <span class="block text-sm font-bold text-slate-900">{{ material.title }}</span>
                  <span class="mt-1 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                    {{ material.category }} · {{ material.format }}
                  </span>
                </span>
                <span class="text-xs font-semibold" :class="material.id === recentlyPurchasedMaterialId ? 'text-emerald-700' : 'text-primary'">
                  {{ material.id === recentlyPurchasedMaterialId ? 'Ready now' : 'View only' }}
                </span>
              </button>
            </div>

            <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
              Paid materials you unlock will appear here automatically after payment.
            </div>
          </section>
        </aside>
      </div>
    </section>

    <section v-else-if="viewMode === 'viewer' && selectedMaterial" class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h3 class="text-2xl font-black text-slate-900">{{ selectedMaterial.title }}</h3>
          <p class="mt-1 text-sm text-slate-500">{{ selectedMaterial.category }}</p>
        </div>
        <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700" @click="void closeMaterialViewer()">Close Viewer</button>
      </div>

      <div class="mt-6 rounded-[1.5rem] border border-sky-100 bg-sky-50 px-5 py-4 text-sm font-medium text-sky-700">
        This protected material is view-only inside your dashboard. Download controls are not provided in this interface.
      </div>

      <div class="mt-8 overflow-hidden rounded-[2rem] border border-slate-100 bg-slate-950">
        <div v-if="isLoadingViewerBlob" class="flex h-[78vh] items-center justify-center">
          <div class="flex flex-col items-center gap-4 text-slate-400">
            <svg class="h-10 w-10 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <p class="text-sm font-bold text-slate-300">Loading material…</p>
          </div>
        </div>
        <div v-else-if="viewerUrl" class="relative h-[78vh]">
          <iframe
            :src="viewerUrl"
            class="h-full w-full border-0 bg-white"
            referrerpolicy="no-referrer"
          />
          <div class="absolute inset-0 select-none" style="pointer-events: none;" @contextmenu.prevent />
        </div>
        <div v-else class="px-6 py-10 text-center text-white">
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-300">Protected material</p>
          <p class="mt-4 text-base font-medium text-slate-100">
            The material viewer is not available yet for this item. Ask the admin to provide an embeddable material URL.
          </p>
        </div>
      </div>
    </section>

    <section v-else-if="viewMode === 'exam' && selectedExam" class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h3 class="text-2xl font-black text-slate-900">{{ selectedExam.title }}</h3>
          <p class="mt-1 text-sm text-slate-500">{{ selectedExam.category }}</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center">
            <p class="text-[10px] font-black uppercase text-slate-400">Time</p>
            <p class="text-lg font-black text-slate-900">{{ formatTime(timeLeft) }}</p>
          </div>
          <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700" @click="closeExam">Exit</button>
        </div>
      </div>

      <div class="mt-8 space-y-6">
        <article v-for="(question, index) in selectedExam.questions" :key="question.id" class="rounded-2xl border border-slate-100 p-6">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-primary">Question {{ index + 1 }}</p>
          <p class="mt-3 text-lg font-bold text-slate-900">{{ question.prompt }}</p>

          <div v-if="question.type === 'objective'" class="mt-5 grid gap-3 sm:grid-cols-2">
            <label v-for="option in question.options" :key="option" class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3" :class="isReviewing && option === question.correctOption ? 'border-emerald-300 bg-emerald-50' : ''">
              <input v-model="answers[question.id]" :value="option" type="radio" class="accent-primary" :disabled="isReviewing" />
              <span class="text-sm font-semibold text-slate-700">{{ option }}</span>
            </label>
          </div>

          <textarea v-else v-model="answers[question.id]" rows="4" class="mt-5 w-full rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm outline-none" :disabled="isReviewing" />
        </article>
      </div>

      <div class="mt-8 flex items-center justify-between">
        <p class="text-sm font-semibold text-slate-500">{{ totalAnswered }} of {{ totalQuestions }} answered</p>
        <div class="flex gap-3">
          <button v-if="isReviewing" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700" @click="closeExam">Back Home</button>
          <button v-if="!isReviewing" class="rounded-full bg-primary px-6 py-3 text-sm font-black text-white" @click="showSubmitModal = true">Submit Attempt</button>
        </div>
      </div>
    </section>

    <div v-if="showSubmitModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
      <div class="w-full max-w-md rounded-[2rem] bg-white p-8 text-center shadow-2xl">
        <h3 class="text-2xl font-black text-slate-900">Submit Exam?</h3>
        <p class="mt-3 text-sm text-slate-500">You have answered {{ totalAnswered }} of {{ totalQuestions }} questions.</p>
        <div class="mt-6 flex justify-center gap-3">
          <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700" @click="showSubmitModal = false">Cancel</button>
          <button class="rounded-full bg-primary px-6 py-2 text-sm font-black text-white disabled:opacity-70" :disabled="isSubmitting" @click="void submitExam()">{{ isSubmitting ? 'Submitting...' : 'Submit Now' }}</button>
        </div>
      </div>
    </div>

    <div v-if="showResultModal && resultDetails" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
      <div class="w-full max-w-md rounded-[2rem] bg-white p-8 text-center shadow-2xl">
        <p class="text-sm font-semibold uppercase tracking-[0.18em]" :class="resultDetails.passed ? 'text-emerald-600' : 'text-rose-600'">{{ resultDetails.passed ? 'Passed' : 'Needs Review' }}</p>
        <h3 class="mt-3 text-4xl font-black text-slate-900">{{ resultDetails.score }}%</h3>
        <div class="mt-6 flex justify-center gap-3">
          <button class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700" @click="showResultModal = false; isReviewing = true">Review</button>
          <button class="rounded-full bg-slate-900 px-6 py-2 text-sm font-black text-white" @click="closeExam">Back Home</button>
        </div>
      </div>
    </div>
  </div>
</template>
