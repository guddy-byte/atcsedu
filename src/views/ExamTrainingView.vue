<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import { getStudentSession, logoutStudent } from '../utils/studentAuth'

interface ExamItem {
  id: string
  title: string
  category: string
  duration: string
  questions: number
  priceLabel: string
  accessType: 'free' | 'paid'
  description: string
}

const router = useRouter()
const selectedExamId = ref('english-language-cbt')
const studentSession = computed(() => getStudentSession())

const freeExams: ExamItem[] = [
  {
    id: 'english-language-cbt',
    title: 'English Language CBT',
    category: 'Core subject',
    duration: '45 mins',
    questions: 40,
    priceLabel: 'Free access',
    accessType: 'free',
    description: 'Practice comprehension, grammar, and objective questions in a timed online CBT environment.',
  },
  {
    id: 'math-essentials-cbt',
    title: 'Math Essentials CBT',
    category: 'Core subject',
    duration: '50 mins',
    questions: 35,
    priceLabel: 'Free access',
    accessType: 'free',
    description: 'Warm up with arithmetic, algebra, and interpretation questions for baseline readiness.',
  },
  {
    id: 'general-knowledge-cbt',
    title: 'General Knowledge CBT',
    category: 'Aptitude',
    duration: '30 mins',
    questions: 25,
    priceLabel: 'Free access',
    accessType: 'free',
    description: 'Use this short attempt set to build speed and confidence before full paid mock sessions.',
  },
]

const paidExams: ExamItem[] = [
  {
    id: 'waec-complete-mock',
    title: 'WAEC Complete Mock CBT',
    category: 'Premium mock',
    duration: '1 hr 20 mins',
    questions: 80,
    priceLabel: 'NGN 7,500',
    accessType: 'paid',
    description: 'A broader exam simulation with multiple sections, timing pressure, and performance review prompts.',
  },
  {
    id: 'jamb-elite-simulation',
    title: 'JAMB Elite Simulation',
    category: 'Premium mock',
    duration: '1 hr 30 mins',
    questions: 100,
    priceLabel: 'NGN 12,000',
    accessType: 'paid',
    description: 'Full mock environment for candidates preparing for competitive online examinations and screening tests.',
  },
  {
    id: 'science-intensive-cbt',
    title: 'Science Intensive CBT',
    category: 'Specialized pack',
    duration: '1 hr',
    questions: 60,
    priceLabel: 'NGN 9,500',
    accessType: 'paid',
    description: 'Focused paid practice for science-heavy candidates who need more challenging timed question banks.',
  },
]

const allExams = [...freeExams, ...paidExams]

const selectedExam = computed(
  () => allExams.find((exam) => exam.id === selectedExamId.value) ?? allExams[0],
)

const selectExam = (examId: string) => {
  selectedExamId.value = examId
}

const logoutAndReturn = () => {
  logoutStudent()
  router.push('/auth/login')
}
</script>

<template>
  <section class="grid gap-6">
    <div class="rounded-[2rem] border border-white/75 bg-white/88 p-8 shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">Exam Training</p>
          <h2 class="mt-3 max-w-4xl text-4xl font-extrabold tracking-tight text-slate-950">
            Your CBT dashboard for free and paid online past-question practice.
          </h2>
          <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
            Once a student logs in, they can review free warm-up CBTs, browse paid premium simulations, and launch an attempt whenever they are ready.
          </p>
        </div>

        <div class="rounded-[1.4rem] border border-rose-100 bg-rose-50/70 px-5 py-4 text-sm text-slate-700 shadow-[0_12px_24px_rgba(117,49,108,0.06)]">
          <p class="font-semibold text-slate-950">Logged in as</p>
          <p class="mt-1 break-all font-medium text-primary">{{ studentSession?.email }}</p>
          <button
            type="button"
            class="mt-4 inline-flex items-center justify-center rounded-full border border-rose-100 bg-white px-4 py-2 text-sm font-semibold text-secondary transition hover:border-primary/30 hover:text-primary"
            @click="logoutAndReturn"
          >
            Log out
          </button>
        </div>
      </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)]">
      <div class="grid gap-6">
        <section class="rounded-[1.8rem] border border-rose-100 bg-white p-6 shadow-[0_14px_48px_rgba(117,49,108,0.08)]">
          <div class="flex items-end justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Free CBT exams</p>
              <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950">Start with quick practice attempts.</h3>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">
              Open after login
            </span>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
              v-for="exam in freeExams"
              :key="exam.id"
              class="grid gap-4 rounded-[1.4rem] border border-rose-100 bg-rose-50/40 p-4 shadow-[0_12px_30px_rgba(117,49,108,0.06)]"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-secondary">{{ exam.category }}</p>
                  <h4 class="mt-2 text-lg font-bold tracking-tight text-slate-950">{{ exam.title }}</h4>
                </div>
                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                  {{ exam.priceLabel }}
                </span>
              </div>

              <p class="text-sm leading-7 text-slate-600">{{ exam.description }}</p>

              <div class="flex flex-wrap gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                <span class="rounded-full bg-white px-3 py-1">{{ exam.duration }}</span>
                <span class="rounded-full bg-white px-3 py-1">{{ exam.questions }} questions</span>
              </div>

              <button
                type="button"
                class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-secondary"
                @click="selectExam(exam.id)"
              >
                Attempt now
              </button>
            </article>
          </div>
        </section>

        <section class="rounded-[1.8rem] border border-rose-100 bg-white p-6 shadow-[0_14px_48px_rgba(117,49,108,0.08)]">
          <div class="flex items-end justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Paid CBT exams</p>
              <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950">Premium full-length mock sessions.</h3>
            </div>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">
              Premium access
            </span>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
              v-for="exam in paidExams"
              :key="exam.id"
              class="grid gap-4 rounded-[1.4rem] border border-rose-100 bg-white p-4 shadow-[0_12px_30px_rgba(117,49,108,0.06)]"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-secondary">{{ exam.category }}</p>
                  <h4 class="mt-2 text-lg font-bold tracking-tight text-slate-950">{{ exam.title }}</h4>
                </div>
                <span class="rounded-full bg-rose-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">
                  {{ exam.priceLabel }}
                </span>
              </div>

              <p class="text-sm leading-7 text-slate-600">{{ exam.description }}</p>

              <div class="flex flex-wrap gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                <span class="rounded-full bg-rose-50 px-3 py-1">{{ exam.duration }}</span>
                <span class="rounded-full bg-rose-50 px-3 py-1">{{ exam.questions }} questions</span>
              </div>

              <button
                type="button"
                class="inline-flex items-center justify-center rounded-full border border-primary bg-white px-5 py-3 text-sm font-semibold text-primary transition hover:-translate-y-0.5 hover:bg-rose-50"
                @click="selectExam(exam.id)"
              >
                View details
              </button>
            </article>
          </div>
        </section>
      </div>

      <aside class="rounded-[1.8rem] border border-rose-100 bg-white p-6 shadow-[0_14px_48px_rgba(117,49,108,0.08)]">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Selected CBT</p>
        <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950">{{ selectedExam.title }}</h3>
        <p class="mt-4 text-sm leading-7 text-slate-600">{{ selectedExam.description }}</p>

        <div class="mt-6 grid gap-3 rounded-[1.4rem] bg-rose-50/60 p-4">
          <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
            <span>Access</span>
            <span class="font-semibold text-slate-950">{{ selectedExam.priceLabel }}</span>
          </div>
          <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
            <span>Duration</span>
            <span class="font-semibold text-slate-950">{{ selectedExam.duration }}</span>
          </div>
          <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
            <span>Questions</span>
            <span class="font-semibold text-slate-950">{{ selectedExam.questions }}</span>
          </div>
          <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
            <span>Category</span>
            <span class="font-semibold text-slate-950">{{ selectedExam.category }}</span>
          </div>
        </div>

        <div class="mt-6 grid gap-3">
          <button
            v-if="selectedExam.accessType === 'free'"
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-secondary"
          >
            Launch free CBT
          </button>

          <button
            v-else
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-secondary"
          >
            Pay and unlock CBT
          </button>

          <p class="rounded-[1.2rem] border border-rose-100 bg-white px-4 py-3 text-sm leading-7 text-slate-600">
            This dashboard is now protected behind student login. You can connect these CTA buttons to a real CBT engine and payment flow next.
          </p>
        </div>
      </aside>
    </div>
  </section>
</template>