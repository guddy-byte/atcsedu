<script setup lang="ts">
import { computed, ref } from 'vue'

interface QuestionItem {
  id: string
  type: 'objective' | 'theory'
  prompt: string
  options?: string[]
  correctOption?: string
}

interface ExamItem {
  id: string
  title: string
  category: string
  duration: string
  priceLabel: string
  accessType: 'free' | 'paid'
  description: string
  questions: QuestionItem[]
}

interface AttemptRecord {
  examId: string
  examTitle: string
  scorePercent: number
  passed: boolean
}

const selectedExamId = ref('english-language-cbt')
const purchasedExamIds = ref<string[]>([])
const answers = ref<Record<string, string>>({})
const submissionResult = ref<{ scorePercent: number; passed: boolean } | null>(null)
const attempts = ref<AttemptRecord[]>([])

const passMark = 50

const freeExams: ExamItem[] = [
  {
    id: 'english-language-cbt',
    title: 'English Language CBT',
    category: 'Core subject',
    duration: '45 mins',
    priceLabel: 'Free access',
    accessType: 'free',
    description: 'Practice comprehension, grammar, and objective questions in a timed CBT format.',
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
    id: 'math-essentials-cbt',
    title: 'Math Essentials CBT',
    category: 'Core subject',
    duration: '50 mins',
    priceLabel: 'Free access',
    accessType: 'free',
    description: 'Warm up with arithmetic and algebra-based questions to build confidence.',
    questions: [
      {
        id: 'math-1',
        type: 'objective',
        prompt: 'Solve: 12 + 8 × 2',
        options: ['40', '28', '32'],
        correctOption: '28',
      },
      {
        id: 'math-2',
        type: 'objective',
        prompt: 'What is 15% of 200?',
        options: ['30', '15', '20'],
        correctOption: '30',
      },
      {
        id: 'math-3',
        type: 'theory',
        prompt: 'Briefly describe your approach to solving word problems in exams.',
      },
    ],
  },
]

const paidExams: ExamItem[] = [
  {
    id: 'waec-complete-mock',
    title: 'WAEC Complete Mock CBT',
    category: 'Premium mock',
    duration: '1 hr 20 mins',
    priceLabel: 'NGN 7,500',
    accessType: 'paid',
    description: 'Multi-section simulation exam with full scoring and progress feedback.',
    questions: [
      {
        id: 'waec-1',
        type: 'objective',
        prompt: 'Nigeria gained independence in which year?',
        options: ['1957', '1960', '1963'],
        correctOption: '1960',
      },
      {
        id: 'waec-2',
        type: 'objective',
        prompt: 'A noun is best described as:',
        options: ['Action word', 'Naming word', 'Describing word'],
        correctOption: 'Naming word',
      },
      {
        id: 'waec-3',
        type: 'theory',
        prompt: 'Write a short strategy for managing time in long-format exams.',
      },
    ],
  },
]

const allExams = [...freeExams, ...paidExams]

const selectedExam = computed(() => allExams.find((exam) => exam.id === selectedExamId.value) ?? allExams[0])
const hasAccessToSelectedExam = computed(
  () => selectedExam.value.accessType === 'free' || purchasedExamIds.value.includes(selectedExam.value.id),
)

const stats = computed(() => ({
  total: allExams.length,
  attempted: attempts.value.length,
  passed: attempts.value.filter((attempt) => attempt.passed).length,
  failed: attempts.value.filter((attempt) => !attempt.passed).length,
}))

const selectExam = (examId: string) => {
  selectedExamId.value = examId
  answers.value = {}
  submissionResult.value = null
}

const unlockPaidExam = () => {
  if (selectedExam.value.accessType !== 'paid') {
    return
  }

  if (!purchasedExamIds.value.includes(selectedExam.value.id)) {
    purchasedExamIds.value = [...purchasedExamIds.value, selectedExam.value.id]
  }
}

const submitExam = () => {
  if (!hasAccessToSelectedExam.value) {
    return
  }

  const objectiveQuestions = selectedExam.value.questions.filter((question) => question.type === 'objective')
  const correctCount = objectiveQuestions.filter(
    (question) => answers.value[question.id] && answers.value[question.id] === question.correctOption,
  ).length

  const scorePercent = objectiveQuestions.length
    ? Math.round((correctCount / objectiveQuestions.length) * 100)
    : 0

  const passed = scorePercent >= passMark

  submissionResult.value = {
    scorePercent,
    passed,
  }

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
</script>

<template>
  <section class="grid gap-6">
    <div class="rounded-[2rem] border border-white/80 bg-white p-7 shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-secondary">Student dashboard</p>
      <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
        Exam Training Portal
      </h2>
      <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">
        Attempt objective and theory past questions directly on the platform, get scored instantly for objective
        sections, and track your pass/fail trend over time.
      </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <article class="rounded-2xl border border-rose-100 bg-white p-4 shadow-[0_10px_24px_rgba(117,49,108,0.06)]">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Exams</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ stats.total }}</p>
      </article>
      <article class="rounded-2xl border border-rose-100 bg-white p-4 shadow-[0_10px_24px_rgba(117,49,108,0.06)]">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Exams Attempted</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ stats.attempted }}</p>
      </article>
      <article class="rounded-2xl border border-emerald-100 bg-white p-4 shadow-[0_10px_24px_rgba(16,185,129,0.08)]">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Exams Passed</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-emerald-600">{{ stats.passed }}</p>
      </article>
      <article class="rounded-2xl border border-rose-100 bg-white p-4 shadow-[0_10px_24px_rgba(239,68,68,0.08)]">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Exams Failed</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-rose-600">{{ stats.failed }}</p>
      </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(390px,1.05fr)]">
      <div class="grid gap-4 rounded-[1.6rem] border border-rose-100 bg-white p-4 shadow-[0_14px_30px_rgba(117,49,108,0.06)] sm:p-5">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">Available exams</h3>
          <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Free + paid</p>
        </div>

        <article
          v-for="exam in allExams"
          :key="exam.id"
          class="rounded-2xl border p-4 transition"
          :class="selectedExamId === exam.id ? 'border-primary bg-rose-50/60' : 'border-rose-100 bg-white'"
        >
          <button type="button" class="w-full text-left" @click="selectExam(exam.id)">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm font-bold text-slate-900">{{ exam.title }}</p>
              <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em]" :class="exam.accessType === 'free' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                {{ exam.accessType }}
              </span>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ exam.category }} • {{ exam.duration }}</p>
            <p class="mt-2 text-xs leading-5 text-slate-600">{{ exam.description }}</p>
          </button>
        </article>
      </div>

      <div class="grid gap-4 rounded-[1.6rem] border border-rose-100 bg-white p-4 shadow-[0_14px_30px_rgba(117,49,108,0.06)] sm:p-5">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Current exam</p>
          <h3 class="mt-1 text-xl font-extrabold text-slate-900">{{ selectedExam.title }}</h3>
          <p class="mt-1 text-xs text-slate-500">{{ selectedExam.duration }} • {{ selectedExam.priceLabel }}</p>
        </div>

        <div v-if="!hasAccessToSelectedExam" class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
          <p class="text-sm font-semibold text-amber-700">This is a paid exam.</p>
          <p class="mt-1 text-xs leading-5 text-amber-700/90">
            Click below to simulate payment unlock (replace with Paystack API callback in backend integration).
          </p>
          <button
            type="button"
            class="mt-3 inline-flex items-center justify-center rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white"
            @click="unlockPaidExam"
          >
            Pay & unlock exam
          </button>
        </div>

        <div v-else class="grid gap-4">
          <div
            v-for="question in selectedExam.questions"
            :key="question.id"
            class="rounded-2xl border border-rose-100 bg-rose-50/40 p-4"
          >
            <p class="text-sm font-semibold text-slate-800">{{ question.prompt }}</p>

            <div v-if="question.type === 'objective'" class="mt-3 grid gap-2">
              <label
                v-for="option in question.options"
                :key="option"
                class="flex cursor-pointer items-center gap-2 rounded-lg border border-rose-100 bg-white px-3 py-2 text-sm text-slate-700"
              >
                <input v-model="answers[question.id]" :value="option" type="radio" />
                <span>{{ option }}</span>
              </label>
            </div>

            <textarea
              v-else
              v-model="answers[question.id]"
              rows="4"
              placeholder="Write your theory answer here"
              class="mt-3 w-full rounded-lg border border-rose-100 bg-white px-3 py-2 text-sm outline-none"
            />
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white"
            @click="submitExam"
          >
            Submit exam attempt
          </button>

          <div v-if="submissionResult" class="rounded-2xl border px-4 py-3" :class="submissionResult.passed ? 'border-emerald-100 bg-emerald-50' : 'border-rose-100 bg-rose-50'">
            <p class="text-sm font-semibold" :class="submissionResult.passed ? 'text-emerald-700' : 'text-rose-600'">
              Score: {{ submissionResult.scorePercent }}% — {{ submissionResult.passed ? 'Passed' : 'Failed' }}
            </p>
            <p class="mt-1 text-xs text-slate-600">Objective questions are auto-scored. Theory answers should be reviewed by admin.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-[1.6rem] border border-rose-100 bg-white p-5 shadow-[0_14px_30px_rgba(117,49,108,0.06)]">
      <h3 class="text-lg font-bold text-slate-900">Recent attempts</h3>
      <p class="mt-1 text-xs text-slate-500">Your latest attempts and outcomes are listed below.</p>
      <ul v-if="attempts.length" class="mt-4 grid gap-2">
        <li v-for="(attempt, index) in attempts.slice(0, 5)" :key="`${attempt.examId}-${index}`" class="flex items-center justify-between rounded-xl border border-rose-100 px-3 py-2 text-sm">
          <span class="font-medium text-slate-700">{{ attempt.examTitle }}</span>
          <span :class="attempt.passed ? 'text-emerald-600' : 'text-rose-600'">{{ attempt.scorePercent }}% • {{ attempt.passed ? 'Passed' : 'Failed' }}</span>
        </li>
      </ul>
      <p v-else class="mt-3 text-sm text-slate-500">No exam attempts yet.</p>
    </div>
  </section>
</template>
