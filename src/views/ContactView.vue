<script setup lang="ts">
import { computed, ref } from 'vue'

interface ContactSubmission {
  name: string
  email: string
  phone: string
  subject: string
  message: string
  createdAt: string
}

interface FeedbackEntry {
  name: string
  role: string
  message: string
  rating: number
  createdAt: string
}

const CONTACT_STORAGE_KEY = 'atcsedu-contact-submissions'
const FEEDBACK_STORAGE_KEY = 'atcsedu-feedback-entries'

const canUseStorage = typeof window !== 'undefined'

const readStorage = <T>(key: string, fallback: T): T => {
  if (!canUseStorage) {
    return fallback
  }

  const savedValue = window.localStorage.getItem(key)

  if (!savedValue) {
    return fallback
  }

  try {
    return JSON.parse(savedValue) as T
  } catch {
    return fallback
  }
}

const writeStorage = (key: string, value: unknown) => {
  if (!canUseStorage) {
    return
  }

  window.localStorage.setItem(key, JSON.stringify(value))
}

const socialLinks = [
  {
    label: 'WhatsApp',
    href: 'https://wa.me/',
    icon: 'whatsapp',
  },
  {
    label: 'Facebook',
    href: 'https://facebook.com/',
    icon: 'facebook',
  },
  {
    label: 'LinkedIn',
    href: 'https://linkedin.com/',
    icon: 'linkedin',
  },
] as const

const seededFeedback: FeedbackEntry[] = [
  {
    name: 'Amina Yusuf',
    role: 'Student applicant',
    message: 'The platform made it easier to locate the right exam materials and reach support quickly.',
    rating: 5,
    createdAt: '2026-03-01T09:00:00.000Z',
  },
  {
    name: 'David Okon',
    role: 'Parent',
    message: 'The contact process is clear and the free resources helped us get started before buying packs.',
    rating: 4,
    createdAt: '2026-03-04T12:15:00.000Z',
  },
]

const contactForm = ref({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
})

const feedbackForm = ref({
  name: '',
  role: '',
  message: '',
  rating: 5,
})

const contactError = ref('')
const feedbackError = ref('')
const contactSuccess = ref('')
const feedbackSuccess = ref('')

const submittedContacts = ref<ContactSubmission[]>(
  readStorage<ContactSubmission[]>(CONTACT_STORAGE_KEY, []),
)

const storedFeedback = ref<FeedbackEntry[]>(
  readStorage<FeedbackEntry[]>(FEEDBACK_STORAGE_KEY, []),
)

const visibleFeedback = computed(() =>
  [...storedFeedback.value, ...seededFeedback].sort(
    (left, right) => new Date(right.createdAt).getTime() - new Date(left.createdAt).getTime(),
  ),
)

const averageRating = computed(() => {
  if (!visibleFeedback.value.length) {
    return 0
  }

  const total = visibleFeedback.value.reduce((sum, entry) => sum + entry.rating, 0)
  return (total / visibleFeedback.value.length).toFixed(1)
})

const submitContactForm = () => {
  contactError.value = ''
  contactSuccess.value = ''

  const name = contactForm.value.name.trim()
  const email = contactForm.value.email.trim()
  const message = contactForm.value.message.trim()

  if (!name || !email || !message) {
    contactError.value = 'Name, email, and message are required before sending your request.'
    return
  }

  submittedContacts.value.unshift({
    name,
    email,
    phone: contactForm.value.phone.trim(),
    subject: contactForm.value.subject.trim(),
    message,
    createdAt: new Date().toISOString(),
  })

  writeStorage(CONTACT_STORAGE_KEY, submittedContacts.value)

  contactForm.value = {
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
  }

  contactSuccess.value = 'Your request has been received. The ATCS EDU team can now follow up from this message.'
}

const submitFeedbackForm = () => {
  feedbackError.value = ''
  feedbackSuccess.value = ''

  const name = feedbackForm.value.name.trim()
  const role = feedbackForm.value.role.trim()
  const message = feedbackForm.value.message.trim()

  if (!name || !role || !message) {
    feedbackError.value = 'Name, role, and feedback message are required.'
    return
  }

  storedFeedback.value.unshift({
    name,
    role,
    message,
    rating: feedbackForm.value.rating,
    createdAt: new Date().toISOString(),
  })

  writeStorage(FEEDBACK_STORAGE_KEY, storedFeedback.value)

  feedbackForm.value = {
    name: '',
    role: '',
    message: '',
    rating: 5,
  }

  feedbackSuccess.value = 'Your feedback has been added to the page.'
}

const renderStars = (rating: number) => '★'.repeat(rating) + '☆'.repeat(5 - rating)
</script>

<template>
  <section class="grid gap-6">
    <div class="rounded-[2rem] border border-white/75 bg-white/88 p-8 shadow-[0_20px_70px_rgba(117,49,108,0.08)]">
      <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">Contact Us</p>
      <h2 class="mt-3 max-w-4xl text-4xl font-extrabold tracking-tight text-slate-950">
        Reach the ATCS EDU team for support, onboarding, materials, and exam preparation help.
      </h2>
      <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
        Students, parents, and school partners can use this page to find our location, contact the team directly, and submit requests or feedback from the platform.
      </p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
      <div class="grid gap-6">
        <div class="rounded-[1.8rem] border border-rose-100 bg-white p-5 shadow-[0_14px_48px_rgba(117,49,108,0.08)]">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Office location</p>
              <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950">Lagos, Nigeria</h3>
              <p class="mt-3 max-w-xl text-sm leading-7 text-slate-600">
                Visit the ATCS EDU support point in Lagos for platform guidance, application support, and exam preparation enquiries.
              </p>
            </div>

            <a
              href="https://www.google.com/maps/search/?api=1&query=Lagos%2C+Nigeria"
              target="_blank"
              rel="noreferrer"
              class="inline-flex items-center justify-center rounded-full border border-rose-100 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-primary transition hover:bg-rose-100"
            >
              Open in Maps
            </a>
          </div>

          <div class="mt-5 overflow-hidden rounded-[1.4rem] border border-rose-100 bg-rose-50/60">
            <iframe
              title="ATCS EDU location map"
              src="https://www.openstreetmap.org/export/embed.html?bbox=3.1815%2C6.4281%2C3.6015%2C6.7081&layer=mapnik&marker=6.5244%2C3.3792"
              class="h-[320px] w-full border-0"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            />
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="rounded-[1.6rem] border border-rose-100 bg-white p-5 shadow-[0_14px_40px_rgba(117,49,108,0.06)]">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Call us</p>
            <a href="tel:+2348000000000" class="mt-3 block text-xl font-extrabold tracking-tight text-slate-950">
              +234 800 000 0000
            </a>
            <p class="mt-2 text-sm leading-7 text-slate-600">Speak with the support team for urgent student or platform questions.</p>
          </div>

          <div class="rounded-[1.6rem] border border-rose-100 bg-white p-5 shadow-[0_14px_40px_rgba(117,49,108,0.06)]">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Email us</p>
            <a href="mailto:support@atcsedu.com" class="mt-3 block text-xl font-extrabold tracking-tight text-slate-950">
              support@atcsedu.com
            </a>
            <p class="mt-2 text-sm leading-7 text-slate-600">Send materials, onboarding, exam, or account-related enquiries at any time.</p>
          </div>
        </div>

        <div class="rounded-[1.6rem] border border-rose-100 bg-white p-5 shadow-[0_14px_40px_rgba(117,49,108,0.06)]">
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Social channels</p>
          <div class="mt-4 flex flex-wrap gap-3">
            <a
              v-for="item in socialLinks"
              :key="item.label"
              :href="item.href"
              target="_blank"
              rel="noreferrer"
              class="inline-flex items-center gap-3 rounded-full border border-rose-100 bg-rose-50/70 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-primary/30 hover:text-primary"
            >
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-primary shadow-[0_10px_22px_rgba(232,49,52,0.12)]">
                <svg v-if="item.icon === 'whatsapp'" viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                  <path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.53 2 2.06 6.47 2.06 11.97c0 1.76.46 3.49 1.33 5.02L2 22l5.16-1.35a9.9 9.9 0 0 0 4.86 1.24h.01c5.5 0 9.97-4.47 9.97-9.97 0-2.66-1.04-5.16-2.95-7.01Zm-7.02 15.3h-.01a8.3 8.3 0 0 1-4.23-1.16l-.3-.18-3.06.8.82-2.98-.2-.31a8.26 8.26 0 0 1-1.27-4.41c0-4.57 3.72-8.29 8.3-8.29 2.21 0 4.29.86 5.85 2.43a8.23 8.23 0 0 1 2.43 5.86c0 4.57-3.72 8.28-8.28 8.28Zm4.54-6.2c-.25-.12-1.47-.72-1.7-.81-.23-.08-.4-.12-.56.12-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-2-1.25-.74-.66-1.23-1.48-1.37-1.73-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42h-.48c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.89 2.4 1.02 2.57.12.17 1.74 2.66 4.21 3.73.59.25 1.05.4 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.07-.1-.23-.16-.48-.29Z" />
                </svg>
                <svg v-else-if="item.icon === 'facebook'" viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                  <path d="M13.5 22v-8.2h2.76l.41-3.2H13.5V8.56c0-.92.26-1.55 1.58-1.55h1.69V4.15c-.29-.04-1.3-.12-2.47-.12-2.45 0-4.12 1.49-4.12 4.23v2.35H7.4v3.2h2.78V22h3.32Z" />
                </svg>
                <svg v-else viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                  <path d="M6.94 8.5A1.94 1.94 0 1 1 7 4.62a1.94 1.94 0 0 1-.06 3.88ZM5.3 9.98h3.32V20.5H5.3V9.98Zm5.4 0h3.18v1.44h.05c.44-.84 1.52-1.72 3.12-1.72 3.34 0 3.95 2.2 3.95 5.05v5.75h-3.31v-5.09c0-1.21-.02-2.77-1.69-2.77-1.7 0-1.96 1.32-1.96 2.68v5.18H10.7V9.98Z" />
                </svg>
              </span>
              {{ item.label }}
            </a>
          </div>
        </div>
      </div>

      <div class="rounded-[1.8rem] border border-rose-100 bg-white p-6 shadow-[0_14px_48px_rgba(117,49,108,0.08)]">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Send a request</p>
          <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950">Tell us what you need from the platform.</h3>
          <p class="mt-3 text-sm leading-7 text-slate-600">
            Use this form for support, paid material enquiries, onboarding questions, technical issues, or partnership requests.
          </p>
        </div>

        <form class="mt-6 grid gap-4" @submit.prevent="submitContactForm">
          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-2">
              <span class="text-sm font-semibold text-slate-700">Full name</span>
              <input v-model="contactForm.name" type="text" placeholder="Enter your full name" class="rounded-[1rem] border border-rose-100 bg-rose-50/40 px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10" />
            </label>

            <label class="grid gap-2">
              <span class="text-sm font-semibold text-slate-700">Email address</span>
              <input v-model="contactForm.email" type="email" placeholder="name@example.com" class="rounded-[1rem] border border-rose-100 bg-rose-50/40 px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10" />
            </label>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-2">
              <span class="text-sm font-semibold text-slate-700">Phone number</span>
              <input v-model="contactForm.phone" type="tel" placeholder="+234 800 000 0000" class="rounded-[1rem] border border-rose-100 bg-rose-50/40 px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10" />
            </label>

            <label class="grid gap-2">
              <span class="text-sm font-semibold text-slate-700">Request type</span>
              <select v-model="contactForm.subject" class="rounded-[1rem] border border-rose-100 bg-rose-50/40 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-primary/10">
                <option value="">Select a subject</option>
                <option value="Material support">Material support</option>
                <option value="Exam training access">Exam training access</option>
                <option value="Account issue">Account issue</option>
                <option value="School partnership">School partnership</option>
                <option value="General enquiry">General enquiry</option>
              </select>
            </label>
          </div>

          <label class="grid gap-2">
            <span class="text-sm font-semibold text-slate-700">Message</span>
            <textarea v-model="contactForm.message" rows="6" placeholder="Describe what you need from ATCS EDU" class="resize-none rounded-[1rem] border border-rose-100 bg-rose-50/40 px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10" />
          </label>

          <p v-if="contactError" class="text-sm font-semibold text-primary">{{ contactError }}</p>
          <p v-if="contactSuccess" class="rounded-[1rem] bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ contactSuccess }}</p>

          <button type="submit" class="inline-flex items-center justify-center rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-[0_16px_26px_rgba(232,49,52,0.22)] transition hover:-translate-y-0.5 hover:bg-secondary">
            Submit request
          </button>
        </form>
      </div>
    </div>

    <section class="grid gap-6 rounded-[1.9rem] border border-white/75 bg-white/88 p-6 shadow-[0_20px_70px_rgba(117,49,108,0.08)] lg:p-8">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">Feedback</p>
          <h3 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">What users are saying</h3>
          <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
            Collect feedback from users directly on the platform and keep a visible pulse on their experience.
          </p>
        </div>

        <div class="rounded-[1.2rem] bg-rose-50 px-5 py-4 text-center">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-secondary">Average rating</p>
          <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ averageRating }}/5</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <form class="grid gap-4 rounded-[1.7rem] border border-rose-100 bg-rose-50/50 p-5" @submit.prevent="submitFeedbackForm">
          <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-2">
              <span class="text-sm font-semibold text-slate-700">Your name</span>
              <input v-model="feedbackForm.name" type="text" placeholder="Enter your name" class="rounded-[1rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10" />
            </label>

            <label class="grid gap-2">
              <span class="text-sm font-semibold text-slate-700">Role</span>
              <input v-model="feedbackForm.role" type="text" placeholder="Student, parent, school admin" class="rounded-[1rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10" />
            </label>
          </div>

          <label class="grid gap-2">
            <span class="text-sm font-semibold text-slate-700">Rating</span>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="rating in [1, 2, 3, 4, 5]"
                :key="rating"
                type="button"
                class="inline-flex h-11 min-w-11 items-center justify-center rounded-full border px-4 text-sm font-semibold transition"
                :class="feedbackForm.rating === rating ? 'border-primary bg-primary text-white' : 'border-rose-100 bg-white text-slate-600 hover:border-primary/30 hover:text-primary'"
                @click="feedbackForm.rating = rating"
              >
                {{ rating }}
              </button>
            </div>
          </label>

          <label class="grid gap-2">
            <span class="text-sm font-semibold text-slate-700">Your feedback</span>
            <textarea v-model="feedbackForm.message" rows="5" placeholder="Tell us how the platform can improve" class="resize-none rounded-[1rem] border border-rose-100 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary/40 focus:ring-2 focus:ring-primary/10" />
          </label>

          <p v-if="feedbackError" class="text-sm font-semibold text-primary">{{ feedbackError }}</p>
          <p v-if="feedbackSuccess" class="rounded-[1rem] bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ feedbackSuccess }}</p>

          <button type="submit" class="inline-flex items-center justify-center rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-[0_16px_26px_rgba(232,49,52,0.22)] transition hover:-translate-y-0.5 hover:bg-secondary">
            Send feedback
          </button>
        </form>

        <div class="grid gap-4">
          <article
            v-for="entry in visibleFeedback.slice(0, 4)"
            :key="`${entry.name}-${entry.createdAt}`"
            class="rounded-[1.5rem] border border-rose-100 bg-white p-5 shadow-[0_12px_34px_rgba(117,49,108,0.06)]"
          >
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <h4 class="text-lg font-bold tracking-tight text-slate-950">{{ entry.name }}</h4>
                <p class="text-sm text-slate-500">{{ entry.role }}</p>
              </div>
              <p class="text-sm font-semibold tracking-[0.12em] text-primary">{{ renderStars(entry.rating) }}</p>
            </div>
            <p class="mt-4 text-sm leading-7 text-slate-600">{{ entry.message }}</p>
          </article>
        </div>
      </div>
    </section>
  </section>
</template>