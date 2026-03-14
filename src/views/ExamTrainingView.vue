<script setup lang="ts">
import { computed, ref, onUnmounted, watch } from 'vue'
import logoImage from '../images/logo.png'
import { useCatalogStore, type Product } from '../stores/catalog'
import { isStudentAuthenticated } from '../utils/studentAuth'
import { useRouter } from 'vue-router'

const catalog = useCatalogStore()
const router = useRouter()
const isAuthenticated = computed(() => isStudentAuthenticated())

interface AttemptRecord {
  examId: string
  examTitle: string
  scorePercent: number
  passed: boolean
}

// UI State
const viewMode = ref<'dashboard' | 'exam' | 'viewer'>('dashboard')
const currentTab = ref<'free' | 'paid'>('free')
const selectedExamId = ref('')
const selectedMaterialId = ref('')
const answers = ref<Record<string, string>>({})
const attempts = ref<AttemptRecord[]>([])
const purchaseAttemptCount = ref<Record<string, number>>({})

// Confirmation state
const showSubmitModal = ref(false)
const showResultModal = ref(false)
const isReviewing = ref(false)

const resultDetails = ref<{
  score: number;
  passed: boolean;
  examTitle: string;
  examId: string;
  totalQuestions: number;
} | null>(null)

// Table Controls State
const searchQuery = ref('')
const categoryFilter = ref('')
const currentPage = ref(1)
const itemsPerPage = 5

// Timer State
const timeLeft = ref(0)
const timerInterval = ref<any>(null)

const passMark = 50

// Computed Results
const availableCategories = computed(() => {
  const cats = new Set(catalog.products.map(e => e.category))
  return Array.from(cats)
})

const filteredExams = computed(() => {
  let list = catalog.products.filter(exam => exam.accessType === currentTab.value && exam.type === 'cbt')
  
  if (categoryFilter.value) {
    list = list.filter(exam => exam.category === categoryFilter.value)
  }
  
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(exam => 
      exam.title.toLowerCase().includes(q) || 
      exam.description.toLowerCase().includes(q)
    )
  }
  
  return list
})

const myMaterials = computed(() => {
  return catalog.products.filter(p => p.type === 'material' && (p.accessType === 'free' || catalog.purchasedIds.includes(p.id)))
})

const totalPages = computed(() => Math.ceil(filteredExams.value.length / itemsPerPage))

const paginatedExams = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredExams.value.slice(start, start + itemsPerPage)
})

// Listeners for UI state resets
watch([currentTab, categoryFilter, searchQuery], () => {
  currentPage.value = 1
})

const selectedExam = computed(() => catalog.products.find((exam) => exam.id === selectedExamId.value))
const selectedMaterial = computed(() => catalog.products.find((m) => m.id === selectedMaterialId.value))

const getRemainingAttempts = (examId: string) => {
  const product = catalog.products.find(p => p.id === examId)
  if (!product || product.limitAttempts === 0) return 'Unlimited'
  const count = purchaseAttemptCount.value[examId] || 0
  return Math.max(0, product.limitAttempts! - count)
}

const hasAccessToSelectedExam = (exam: Product) => {
  if (exam.accessType === 'free') return true
  const isPurchased = catalog.purchasedIds.includes(exam.id)
  const rem = getRemainingAttempts(exam.id)
  return isPurchased && (rem === 'Unlimited' || Number(rem) > 0)
}

const stats = computed(() => ({
  total: catalog.products.filter(p => p.type === 'cbt').length,
  attempted: attempts.value.length,
  passed: attempts.value.filter((attempt) => attempt.passed).length,
  failed: attempts.value.filter((attempt) => !attempt.passed).length,
}))

// Timer Helpers
const formatTime = (seconds: number) => {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  return h > 0 
    ? `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
    : `${m}:${s.toString().padStart(2, '0')}`
}

const parseDuration = (durationStr: string) => {
  const parts = durationStr.toLowerCase().split(' ')
  let totalSeconds = 0
  for (let i = 0; i < parts.length; i++) {
    const val = parseInt(parts[i])
    if (isNaN(val)) continue
    if (parts[i+1]?.startsWith('hr')) totalSeconds += val * 3600
    if (parts[i+1]?.startsWith('min')) totalSeconds += val * 60
  }
  return totalSeconds || 1800
}

const startTimer = () => {
  if (timerInterval.value) clearInterval(timerInterval.value)
  timerInterval.value = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--
    } else {
      stopTimer()
      submitExam()
    }
  }, 1000)
}

const stopTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
}

onUnmounted(() => stopTimer())

const submissionResult = ref<{ scorePercent: number; passed: boolean } | null>(null)

// Actions
const handleAccess = (product: Product) => {
  if (!isAuthenticated.value) {
    router.push('/auth/signup')
    return
  }

  if (product.type === 'cbt') {
    startExam(product.id)
  } else {
    viewMaterial(product.id)
  }
}

const startExam = (examId: string) => {
  const exam = catalog.products.find(e => e.id === examId)
  if (!exam) return
  
  if (!hasAccessToSelectedExam(exam)) {
    selectedExamId.value = examId
    return
  }

  selectedExamId.value = examId
  answers.value = {}
  submissionResult.value = null
  isReviewing.value = false
  showResultModal.value = false
  timeLeft.value = parseDuration(exam.duration || '45 mins')
  viewMode.value = 'exam'
  startTimer()
}

const viewMaterial = (id: string) => {
  selectedMaterialId.value = id
  viewMode.value = 'viewer'
}

const retakeExam = () => {
  if (selectedExam.value) {
    startExam(selectedExam.value.id)
  }
}

const exitExam = () => {
  const msg = isReviewing.value 
    ? 'Close review and return to dashboard?' 
    : 'Are you sure you want to exit? Your progress will not be saved.'

  if (confirm(msg)) {
    stopTimer()
    viewMode.value = 'dashboard'
    selectedExamId.value = ''
    answers.value = {}
    isReviewing.value = false
  }
}

const unlockPaidProduct = (productId: string) => {
  if (!isAuthenticated.value) {
    router.push('/auth/signup')
    return
  }
  catalog.buyProduct(productId)
  purchaseAttemptCount.value[productId] = 0
}

const submitExam = () => {
  if (!selectedExam.value) return
  
  const objectiveQuestions = selectedExam.value.questions?.filter((q) => q.type === 'objective') || []
  const correctCount = objectiveQuestions.filter(
    (q) => answers.value[q.id] && answers.value[q.id] === q.correctOption,
  ).length

  const scorePercent = objectiveQuestions.length
    ? Math.round((correctCount / objectiveQuestions.length) * 100)
    : 0

  const passed = scorePercent >= passMark

  stopTimer()
  showSubmitModal.value = false
  
  if (selectedExam.value.accessType === 'paid') {
    purchaseAttemptCount.value[selectedExam.value.id] = (purchaseAttemptCount.value[selectedExam.value.id] || 0) + 1
  }

  resultDetails.value = {
    score: scorePercent,
    passed,
    examTitle: selectedExam.value.title,
    examId: selectedExam.value.id,
    totalQuestions: selectedExam.value.questions?.length || 0
  }
  
  showResultModal.value = true

  attempts.value = [
    {
      examId: selectedExam.value.id,
      examTitle: selectedExam.value.title,
      scorePercent,
      passed,
    },
    ...attempts.value,
  ]
}

const closeResultsAndExit = () => {
  showResultModal.value = false
  viewMode.value = 'dashboard'
  selectedExamId.value = ''
  isReviewing.value = false
}

const startReview = () => {
  showResultModal.value = false
  isReviewing.value = true
}

const totalQuestions = computed(() => selectedExam.value?.questions?.length ?? 0)
const totalAnswered = computed(() => Object.keys(answers.value).filter(id => answers.value[id]?.trim() !== '').length)
const yetToAnswer = computed(() => totalQuestions.value - totalAnswered.value)
</script>

<template>
  <div class="space-y-6">
    <!-- DASHBOARD VIEW -->
    <section v-if="viewMode === 'dashboard'" class="grid gap-6">
      <div class="rounded-[2.5rem] border border-white/80 bg-white p-8 shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-secondary">Learning HUB</p>
            <h2 class="mt-2 text-4xl font-black tracking-tight text-slate-900 sm:text-5xl">
              Knowledge Repository
            </h2>
            <p class="mt-4 max-w-2xl text-base leading-8 text-slate-500">
              Access your personalized study vault. Manage exam sessions, review materials, and track your scholarly progress.
            </p>
          </div>
          <div class="flex gap-3">
             <div class="rounded-3xl bg-slate-100 p-6 text-center min-w-[120px]">
                <p class="text-[10px] font-black uppercase text-slate-400">Total Exams</p>
                <p class="mt-1 text-3xl font-black text-slate-900">{{ stats.total }}</p>
             </div>
             <div class="rounded-3xl bg-emerald-50 p-6 text-center min-w-[120px]">
                <p class="text-[10px] font-black uppercase text-emerald-600">My Study</p>
                <p class="mt-1 text-3xl font-black text-emerald-700">{{ myMaterials.length }}</p>
             </div>
          </div>
        </div>
      </div>

      <!-- Content Sections -->
      <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <div class="space-y-8">
           <!-- My Materials Safe Spot -->
          <section v-if="myMaterials.length" class="rounded-[2.5rem] bg-slate-900 p-8 text-white shadow-2xl">
             <div class="mb-6 flex items-center justify-between">
                <div>
                   <h3 class="text-2xl font-black">My Study Vault</h3>
                   <p class="text-sm font-medium text-slate-400">Secured materials available for instant review.</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-white/10 flex items-center justify-center">📚</div>
             </div>
             <div class="grid gap-4 sm:grid-cols-2">
                <article v-for="m in myMaterials" :key="m.id" @click="viewMaterial(m.id)" class="group cursor-pointer rounded-2xl bg-white/5 border border-white/10 p-5 transition-all hover:bg-white/10 hover:border-white/20">
                   <div class="flex items-center gap-4">
                      <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-500/20 text-orange-400 font-bold">PDF</div>
                      <div>
                         <p class="text-sm font-black group-hover:text-primary transition-colors">{{ m.title }}</p>
                         <p class="text-[10px] uppercase font-bold text-slate-500">{{ m.category }}</p>
                      </div>
                   </div>
                </article>
             </div>
          </section>

          <!-- Exam Catalog -->
          <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between mb-8">
               <div class="inline-flex items-center gap-1 rounded-2xl bg-slate-50 p-1.5 border border-slate-100">
                  <button @click="currentTab = 'free'" class="rounded-xl px-6 py-2.5 text-xs font-black transition-all" :class="currentTab === 'free' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600'">Free Practice</button>
                  <button @click="currentTab = 'paid'" class="rounded-xl px-6 py-2.5 text-xs font-black transition-all" :class="currentTab === 'paid' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600'">Premium Mock</button>
               </div>
               
               <div class="flex flex-col sm:flex-row gap-3">
                  <input v-model="searchQuery" type="text" placeholder="Search exams..." class="rounded-2xl border border-slate-100 bg-slate-50 px-5 py-2.5 text-xs font-bold outline-none focus:ring-4 focus:ring-primary/5 min-w-[200px]" />
                  <select v-model="categoryFilter" class="rounded-2xl border border-slate-100 bg-slate-50 px-5 py-2.5 text-xs font-bold text-slate-600 outline-none">
                     <option value="">All Subjects</option>
                     <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ cat }}</option>
                  </select>
               </div>
            </div>

            <div class="overflow-x-auto">
               <table class="w-full text-left">
                  <thead>
                     <tr class="border-b border-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <th class="px-4 py-4">Title & Details</th>
                        <th class="px-4 py-4">Access</th>
                        <th class="px-4 py-4 text-right">Action</th>
                     </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-50">
                     <tr v-for="exam in paginatedExams" :key="exam.id" class="group hover:bg-slate-50/50">
                        <td class="px-4 py-6">
                           <div class="flex gap-4">
                              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-50 border border-slate-100">
                                 <img :src="logoImage" class="h-8 w-auto opacity-80" />
                              </div>
                              <div>
                                 <p class="text-sm font-black text-slate-800">{{ exam.title }}</p>
                                 <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ exam.category }} • {{ exam.duration }}</p>
                              </div>
                           </div>
                        </td>
                        <td class="px-4 py-6">
                           <span v-if="exam.accessType === 'free'" class="rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-emerald-600">Free</span>
                           <span v-else class="text-sm font-black text-slate-900">₦{{ exam.price?.toLocaleString() }}</span>
                        </td>
                        <td class="px-4 py-6 text-right">
                           <template v-if="exam.accessType === 'free' || catalog.purchasedIds.includes(exam.id)">
                              <div class="flex flex-col items-end gap-1">
                                 <button @click="startExam(exam.id)" class="rounded-full bg-slate-900 px-6 py-2.5 text-xs font-black text-white shadow-xl transition hover:bg-primary active:scale-95">Take Exam</button>
                                 <p v-if="exam.accessType === 'paid'" class="text-[9px] font-bold text-slate-400 mr-2">{{ getRemainingAttempts(exam.id) }} left</p>
                              </div>
                           </template>
                           <button v-else @click="unlockPaidProduct(exam.id)" class="rounded-full bg-primary px-6 py-2.5 text-xs font-black text-white shadow-xl shadow-rose-100 transition hover:bg-rose-600 active:scale-95">Enroll Now</button>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-6">
           <div class="rounded-[2.5rem] bg-emerald-600 p-8 text-white shadow-xl shadow-emerald-100">
              <h4 class="text-xl font-black">Performance</h4>
              <p class="mt-2 text-sm font-medium text-emerald-50">Quick snapshot of your scores.</p>
              <div class="mt-6 flex items-center justify-between">
                 <div>
                    <p class="text-4xl font-black">{{ stats.passed }}</p>
                    <p class="text-[10px] uppercase font-bold opacity-70">Passed</p>
                 </div>
                 <div class="h-12 w-px bg-white/20" />
                 <div>
                    <p class="text-4xl font-black">{{ stats.failed }}</p>
                    <p class="text-[10px] uppercase font-bold opacity-70">Failed</p>
                 </div>
              </div>
           </div>

           <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm">
              <h4 class="text-lg font-black text-slate-900">Recent Logs</h4>
              <div v-if="attempts.length" class="mt-6 space-y-4">
                 <div v-for="(a, i) in attempts.slice(0, 3)" :key="i" class="flex items-center justify-between border-b border-slate-50 pb-4">
                    <div>
                       <p class="text-xs font-black text-slate-800">{{ a.examTitle }}</p>
                       <p class="text-[10px] font-bold text-slate-400">Score: {{ a.scorePercent }}%</p>
                    </div>
                    <span class="h-2 w-2 rounded-full" :class="a.passed ? 'bg-emerald-500' : 'bg-rose-500'" />
                 </div>
              </div>
              <p v-else class="mt-6 text-xs text-slate-400 font-bold text-center">No recent sessions.</p>
           </div>
        </aside>
      </div>
    </section>

    <!-- VIEWER MODE -->
     <section v-else-if="viewMode === 'viewer' && selectedMaterial" class="fixed inset-0 z-50 bg-slate-950 flex flex-col">
        <header class="flex items-center justify-between px-8 py-4 bg-slate-900 text-white">
           <div>
              <h4 class="text-lg font-black">{{ selectedMaterial.title }}</h4>
              <p class="text-xs text-slate-400 font-bold">{{ selectedMaterial.category }} • Secure internal view</p>
           </div>
           <button @click="viewMode = 'dashboard'" class="h-10 px-6 rounded-full bg-white/10 text-sm font-black hover:bg-white/20">Close Viewer</button>
        </header>
        <div class="flex-1 bg-slate-800 p-4 sm:p-8">
           <div class="h-full w-full rounded-2xl bg-white shadow-2xl overflow-hidden flex items-center justify-center">
              <!-- Mock PDF Viewer -->
              <div class="text-center p-12">
                 <div class="mx-auto h-24 w-24 rounded-[2rem] bg-rose-50 flex items-center justify-center text-primary mb-6">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                 </div>
                 <h3 class="text-3xl font-black text-slate-900">Secure Content Protected</h3>
                 <p class="mt-4 max-w-md mx-auto text-slate-500 font-bold leading-relaxed">
                    This material is locked within the portal environment to prevent unauthorized downloads and sharing. 
                    <br><br>
                    <span class="text-primary tracking-widest uppercase text-xs">Viewing: {{ selectedMaterial.downloadUrl }}</span>
                 </p>
                 <div class="mt-10 animate-pulse text-xs font-black text-slate-400">Loading document securely...</div>
              </div>
           </div>
        </div>
     </section>

    <!-- EXAM FOCUS MODE -->
    <section v-else-if="viewMode === 'exam' && selectedExam" class="fixed inset-0 z-50 overflow-hidden bg-slate-50 flex flex-col">
      <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
          <!-- Sticky Header -->
          <header class="sticky top-0 z-20 mb-8 rounded-[2rem] border border-white/60 bg-white/80 p-5 shadow-2xl backdrop-blur-2xl ring-1 ring-black/5 sm:p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <h3 v-if="isReviewing" class="text-2xl font-black text-primary leading-none">Review Mode</h3>
                <h3 v-else class="text-2xl font-black text-slate-900 leading-none">{{ selectedExam.title }}</h3>
                <p class="mt-2 text-xs font-bold uppercase tracking-[0.2em] text-primary/70">{{ selectedExam.category }}</p>
              </div>
              <div class="flex items-center gap-3">
                 <div v-if="!isReviewing" class="flex items-center gap-4 bg-slate-900/5 p-2 rounded-2xl">
                    <div class="text-center px-2">
                       <p class="text-[9px] font-black uppercase text-slate-400">Time</p>
                       <p class="text-lg font-black tabular-nums" :class="timeLeft < 300 ? 'text-rose-600' : 'text-slate-900'">{{ formatTime(timeLeft) }}</p>
                    </div>
                    <div class="h-8 w-px bg-slate-200" />
                    <div class="text-center px-2">
                       <p class="text-[9px] font-black uppercase text-slate-400">Answered</p>
                       <p class="text-lg font-black text-emerald-600">{{ totalAnswered }}/{{ totalQuestions }}</p>
                    </div>
                 </div>
                 <button @click="exitExam" class="h-12 w-12 rounded-full border border-rose-100 bg-white flex items-center justify-center text-rose-500 hover:bg-rose-50 shadow-sm">×</button>
              </div>
            </div>
          </header>

          <!-- Questions List -->
          <div class="grid gap-10">
            <div v-for="(question, index) in selectedExam.questions" :key="question.id" class="rounded-[2.5rem] border border-white bg-white/80 p-8 shadow-xl">
               <span class="inline-block px-3 py-1 rounded-lg bg-slate-900 text-[10px] font-black text-white mb-6">QUESTION {{ index + 1 }}</span>
               <p class="text-xl font-extrabold text-slate-900 leading-relaxed">{{ question.prompt }}</p>
               
               <div v-if="question.type === 'objective'" class="mt-8 grid gap-4 sm:grid-cols-2">
                  <label v-for="(option, oIndex) in question.options" :key="oIndex" class="relative group flex items-center gap-4 p-5 rounded-[1.5rem] border-2 cursor-pointer transition-all"
                    :class="[
                      isReviewing 
                        ? (option === question.correctOption ? 'border-emerald-500 bg-emerald-50' : (answers[question.id] === option ? 'border-rose-500 bg-rose-50' : 'border-slate-50'))
                        : (answers[question.id] === option ? 'border-primary bg-rose-50/20' : 'border-slate-50 hover:border-slate-200 hover:bg-white')
                    ]"
                  >
                     <div class="h-10 w-10 shrink-0 flex items-center justify-center rounded-xl font-black text-sm"
                        :class="answers[question.id] === option ? 'bg-primary text-white' : 'bg-slate-100 text-slate-500'"
                     >{{ String.fromCharCode(65 + oIndex) }}</div>
                     <input v-model="answers[question.id]" :value="option" type="radio" class="sr-only" :disabled="isReviewing" />
                     <span class="text-base font-bold text-slate-700">{{ option }}</span>
                  </label>
               </div>
               <textarea v-else v-model="answers[question.id]" rows="5" class="mt-8 w-full rounded-3xl border-2 border-slate-50 bg-slate-50/50 p-6 text-sm font-bold outline-none focus:bg-white focus:border-primary/20" :disabled="isReviewing" placeholder="Type your response..."></textarea>
            </div>
          </div>

          <footer class="mt-16 flex flex-col items-center pb-32">
             <button v-if="!isReviewing" @click="showSubmitModal = true" class="rounded-full bg-slate-900 px-12 py-5 text-base font-black text-white shadow-2xl hover:bg-primary transition-all">Submit Attempt</button>
             <div v-else class="flex gap-4">
                <button @click="closeResultsAndExit" class="rounded-full bg-slate-100 px-10 py-4 text-sm font-black text-slate-600">Back Home</button>
                <button v-if="hasAccessToSelectedExam(selectedExam)" @click="retakeExam" class="rounded-full bg-primary px-10 py-4 text-sm font-black text-white shadow-xl">Retake Now</button>
             </div>
          </footer>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <div v-if="showSubmitModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md">
         <div class="w-full max-w-sm rounded-[2.5rem] bg-white p-10 text-center shadow-2xl">
            <h4 class="text-2xl font-black text-slate-900">Final Submission?</h4>
            <p class="mt-4 text-sm font-bold text-slate-500 leading-relaxed">
               You've answered {{ totalAnswered }} of {{ totalQuestions }} questions. Results are final once submitted.
            </p>
            <div class="mt-8 grid gap-3">
               <button @click="submitExam" class="rounded-2xl bg-primary py-4 text-sm font-black text-white hover:bg-rose-600 transition-all">Yes, Submit Now</button>
               <button @click="showSubmitModal = false" class="rounded-2xl bg-slate-100 py-4 text-sm font-black text-slate-600">Cancel</button>
            </div>
         </div>
      </div>

      <!-- Result Modal -->
      <div v-if="showResultModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xl">
         <div class="w-full max-w-md rounded-[3rem] bg-white overflow-hidden shadow-2xl text-center">
            <div class="h-3 w-full" :class="resultDetails?.passed ? 'bg-emerald-400' : 'bg-rose-400'" />
            <div class="p-10">
               <div class="mx-auto h-24 w-24 rounded-full flex items-center justify-center text-white text-3xl font-black mb-6 shadow-xl" :class="resultDetails?.passed ? 'bg-emerald-500' : 'bg-rose-500'">
                  {{ resultDetails?.passed ? '✔' : '✘' }}
               </div>
               <h2 class="text-3xl font-black text-slate-900">{{ resultDetails?.passed ? 'Perfect!' : 'Keep Going!' }}</h2>
               <p class="mt-4 text-base font-bold text-slate-500">{{ resultDetails?.passed ? 'You did a great job today.' : 'You can do better next time!' }}</p>
               
               <div class="mt-8 rounded-[2rem] bg-slate-50 border border-slate-100 p-8">
                  <p class="text-[10px] font-black uppercase text-slate-400 mb-2">Score</p>
                  <div class="flex items-baseline justify-center gap-1">
                     <span class="text-6xl font-black text-slate-900">{{ resultDetails?.score }}</span>
                     <span class="text-xl font-bold text-slate-400">%</span>
                  </div>
               </div>

               <div class="mt-10 grid grid-cols-2 gap-4">
                  <button @click="startReview" class="rounded-2xl bg-slate-100 py-4 text-xs font-black text-slate-700">Review</button>
                  <button v-if="hasAccessToSelectedExam(selectedExam!)" @click="retakeExam" class="rounded-2xl bg-slate-900 py-4 text-xs font-black text-white shadow-xl">Retake</button>
                  <button v-else @click="closeResultsAndExit" class="rounded-2xl bg-slate-900 py-4 text-xs font-black text-white shadow-xl col-span-2">Back Home</button>
               </div>
            </div>
         </div>
      </div>
    </section>
  </div>
</template>
