<script setup lang="ts">
import logoImage from '../images/logo.png'
</script>

<template>
  <footer class="mt-12 border-t border-rose-100 py-8">
    <div class="rounded-[1.9rem] border border-white/70 bg-white/88 px-5 py-7 shadow-[0_16px_54px_rgba(117,49,108,0.08)] sm:px-6 lg:px-8">
      <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-2xl">
          <img
            :src="logoImage"
            alt="ATCS Edu logo"
            class="h-14 w-auto object-contain"
          />

          <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-[15px]">
            ATCS EDU is built to deliver clean access to free and paid academic materials,
            student support resources, and a structured learning experience.
          </p>
        </div>

        <div class="flex flex-col items-start gap-4 lg:items-end">
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-secondary">
            Connect with us
          </p>

          <div class="flex items-center gap-3">
            <a
              href="https://wa.me/"
              target="_blank"
              rel="noreferrer"
              aria-label="WhatsApp"
              class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-rose-100 bg-rose-50/70 text-primary transition hover:-translate-y-0.5 hover:border-primary/30 hover:bg-rose-100"
            >
              <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
                <path d="M19.05 4.91A9.82 9.82 0 0 0 12.03 2C6.53 2 2.06 6.47 2.06 11.97c0 1.76.46 3.49 1.33 5.02L2 22l5.16-1.35a9.9 9.9 0 0 0 4.86 1.24h.01c5.5 0 9.97-4.47 9.97-9.97 0-2.66-1.04-5.16-2.95-7.01Zm-7.02 15.3h-.01a8.3 8.3 0 0 1-4.23-1.16l-.3-.18-3.06.8.82-2.98-.2-.31a8.26 8.26 0 0 1-1.27-4.41c0-4.57 3.72-8.29 8.3-8.29 2.21 0 4.29.86 5.85 2.43a8.23 8.23 0 0 1 2.43 5.86c0 4.57-3.72 8.28-8.28 8.28Zm4.54-6.2c-.25-.12-1.47-.72-1.7-.81-.23-.08-.4-.12-.56.12-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-2-1.25-.74-.66-1.23-1.48-1.37-1.73-.15-.25-.02-.39.11-.51.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.35-.77-1.85-.2-.48-.4-.41-.56-.42h-.48c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.89 2.4 1.02 2.57.12.17 1.74 2.66 4.21 3.73.59.25 1.05.4 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.07-.1-.23-.16-.48-.29Z" />
              </svg>
            </a>

            <a
              href="https://facebook.com/"
              target="_blank"
              rel="noreferrer"
              aria-label="Facebook"
              class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-rose-100 bg-rose-50/70 text-primary transition hover:-translate-y-0.5 hover:border-primary/30 hover:bg-rose-100"
            >
              <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
                <path d="M13.5 22v-8.2h2.76l.41-3.2H13.5V8.56c0-.92.26-1.55 1.58-1.55h1.69V4.15c-.29-.04-1.3-.12-2.47-.12-2.45 0-4.12 1.49-4.12 4.23v2.35H7.4v3.2h2.78V22h3.32Z" />
              </svg>
            </a>

            <a
              href="https://linkedin.com/"
              target="_blank"
              rel="noreferrer"
              aria-label="LinkedIn"
              class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-rose-100 bg-rose-50/70 text-primary transition hover:-translate-y-0.5 hover:border-primary/30 hover:bg-rose-100"
            >
              <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
                <path d="M6.94 8.5A1.94 1.94 0 1 1 7 4.62a1.94 1.94 0 0 1-.06 3.88ZM5.3 9.98h3.32V20.5H5.3V9.98Zm5.4 0h3.18v1.44h.05c.44-.84 1.52-1.72 3.12-1.72 3.34 0 3.95 2.2 3.95 5.05v5.75h-3.31v-5.09c0-1.21-.02-2.77-1.69-2.77-1.7 0-1.96 1.32-1.96 2.68v5.18H10.7V9.98Z" />
              </svg>
            </a>
          </div>
        </div>
      </div>

      <div class="mt-7 border-t border-rose-100 pt-4 text-sm text-slate-500">
        <p>Copyright @ ATCSEDU 2026</p>
      </div>
    </div>
  </footer>
</template>