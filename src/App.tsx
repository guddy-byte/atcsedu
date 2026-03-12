import { defineComponent } from 'vue'
import { RouterView } from 'vue-router'

import FooterSection from './components/FooterSection.vue'
import Header from './components/Header.vue'

export default defineComponent({
  name: 'App',
  setup: () => () => (
    <div class="min-h-screen bg-[linear-gradient(180deg,#fff8fb_0%,#fff1f6_45%,#ffffff_100%)] text-slate-900">
      <div class="pointer-events-none fixed inset-x-0 top-0 h-80 bg-[radial-gradient(circle_at_top_left,rgba(237,69,97,0.18),transparent_42%),radial-gradient(circle_at_top_right,rgba(117,49,108,0.14),transparent_34%)]" />

      <div class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
        <Header />
        <main class="flex-1 py-8 sm:py-10">
          <RouterView />
        </main>
        <FooterSection />
      </div>
    </div>
  ),
})