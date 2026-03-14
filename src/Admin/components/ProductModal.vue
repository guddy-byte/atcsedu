<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useCatalogStore, type ProductInput, type QuestionItem } from '../../stores/catalog'

const props = defineProps<{
  type: 'material' | 'cbt' | 'bulk'
  editProduct?: any
}>()

const emit = defineEmits(['close'])
const catalog = useCatalogStore()

const showAddCategory = ref(false)
const newCategoryName = ref('')

const saveCategory = () => {
  if (newCategoryName.value.trim()) {
    catalog.addCategory(newCategoryName.value)
    formData.category = newCategoryName.value.trim()
    newCategoryName.value = ''
    showAddCategory.value = false
  }
}

const removeCategory = (name: string) => {
  if (confirm(`Delete category "${name}" forever?`)) {
    catalog.deleteCategory(name)
    if (formData.category === name) {
      formData.category = catalog.categories[0] || ''
    }
  }
}

const formData = reactive<ProductInput>({
  type: props.type === 'bulk' ? 'material' : props.type,
  title: '',
  category: catalog.categories[0] || '',
  format: props.type === 'cbt' ? 'CBT Exam' : 'PDF Guide',
  price: 0,
  stock: 999,
  description: '',
  accessType: 'free',
  duration: '45 mins',
  limitAttempts: 2,
  questions: []
})

// Question Management
const addQuestion = () => {
  formData.questions?.push({
    id: `q-${Date.now()}`,
    type: 'objective',
    prompt: '',
    options: ['', '', ''],
    correctOption: ''
  })
}

const removeQuestion = (index: number) => {
  formData.questions?.splice(index, 1)
}

const addOption = (qIdx: number) => {
  formData.questions?.[qIdx].options?.push('')
}

const removeOption = (qIdx: number, oIdx: number) => {
  formData.questions?.[qIdx].options?.splice(oIdx, 1)
}

const handleFileUpload = (event: any) => {
  const files = event.target.files
  if (!files || files.length === 0) return

  if (props.type === 'bulk') {
    for (let i = 0; i < files.length; i++) {
        const file = files[i]
        // Create title from filename (remove extension)
        const title = file.name.replace(/\.[^/.]+$/, "")
        
        bulkItems.value.push({
            ...formData,
            title,
            downloadUrl: `/materials/${file.name}`,
            price: formData.price || bulkPrice.value,
            category: formData.category || bulkCategory.value,
            questions: []
        })
    }
  } else {
    const file = files[0]
    formData.downloadUrl = `/materials/${file.name}`
    if (!formData.title) {
        formData.title = file.name.replace(/\.[^/.]+$/, "")
    }
  }
}

const handleSubmit = () => {
  if (props.type === 'bulk') {
    catalog.addProductsBulk(bulkItems.value)
  } else {
    catalog.addProduct({ ...formData })
  }
  emit('close')
}

// Bulk state
const bulkItems = ref<ProductInput[]>([])
const bulkPrice = ref(0)
const bulkCategory = ref('')
const editingBulkIndex = ref<number | null>(null)

const resetForm = () => {
  formData.title = ''
  formData.description = ''
  formData.price = bulkPrice.value || 0
  formData.category = bulkCategory.value || catalog.categories[0] || ''
  formData.questions = []
  editingBulkIndex.value = null
}

const addToBulkList = () => {
  if (!formData.title) return
  
  const newItem = { 
    ...formData, 
    price: formData.price || bulkPrice.value,
    category: formData.category || bulkCategory.value
  }

  if (editingBulkIndex.value !== null) {
    bulkItems.value[editingBulkIndex.value] = newItem
  } else {
    bulkItems.value.push(newItem)
  }
  
  resetForm()
}

const editFromBulk = (index: number) => {
  const item = bulkItems.value[index]
  Object.assign(formData, item)
  editingBulkIndex.value = index
}

const removeFromBulk = (index: number) => {
  bulkItems.value.splice(index, 1)
  if (editingBulkIndex.value === index) {
    resetForm()
  }
}

const clearBulkQueue = () => {
  if (confirm('Clear all materials from the queue?')) {
    bulkItems.value = []
    resetForm()
  }
}

const isFormValid = computed(() => {
  const isPricingValid = formData.accessType === 'free' || (formData.accessType === 'paid' && formData.price > 0)
  
  if (props.type === 'bulk') {
    return bulkItems.value.length > 0
  }

  const baseValid = !!formData.title && !!formData.category && isPricingValid

  if (props.type === 'material') {
    return baseValid && !!formData.downloadUrl
  }

  if (props.type === 'cbt') {
    const hasQuestions = (formData.questions?.length || 0) > 0
    const questionsValid = formData.questions?.every(q => 
      !!q.prompt && 
      !!q.correctOption && 
      q.options?.every(opt => !!opt.trim()) &&
      (q.options?.length || 0) >= 2
    )
    return baseValid && !!formData.duration && hasQuestions && questionsValid
  }

  return false
})

const canAddToBulk = computed(() => {
  const isPricingValid = formData.accessType === 'free' || (formData.accessType === 'paid' && formData.price > 0)
  return !!formData.title && !!formData.category && isPricingValid && !!formData.downloadUrl
})

const questionEntryMode = ref<'manual' | 'import'>('manual')

const handleCsvUpload = (event: any) => {
  const file = event.target.files[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    const text = e.target?.result as string
    const lines = text.split('\n')
    const questions: QuestionItem[] = []

    // Skip header: Prompt,Option A,Option B,Option C,Option D,Correct Answer,Type
    for (let i = 1; i < lines.length; i++) {
      const line = lines[i].trim()
      if (!line) continue
      
      const parts = line.split(',').map(p => p.trim())
      if (parts.length < 6) continue

      questions.push({
        id: `q-csv-${Date.now()}-${i}`,
        prompt: parts[0],
        options: [parts[1], parts[2], parts[3], parts[4]].filter(Boolean),
        correctOption: parts[5],
        type: (parts[6]?.toLowerCase() as any) || 'objective'
      })
    }
    
    formData.questions = questions
    questionEntryMode.value = 'manual' // Switch back to see result
  }
  reader.readAsText(file)
}

const downloadTemplate = () => {
  const csvContent = "Prompt,Option A,Option B,Option C,Option D,Correct Answer,Type (objective/theory)\nWhat is 2+2?,2,4,6,8,4,objective"
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'cbt_template.csv'
  a.click()
}
</script>

<template>
  <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div @click="$emit('close')" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm" />
    
    <div class="relative max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-[2.5rem] bg-white p-8 shadow-2xl">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h3 class="text-2xl font-black text-slate-900">
            {{ type === 'bulk' ? 'Bulk Upload Materials' : (type === 'cbt' ? 'Configure CBT Exam' : 'Upload Study Material') }}
          </h3>
          <p class="text-sm font-medium text-slate-500">Fill in the details for the new portal resource.</p>
        </div>
        <button @click="$emit('close')" class="h-10 w-10 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200">×</button>
      </div>

      <div class="grid gap-8" :class="type !== 'material' ? 'lg:grid-cols-2' : ''">
        <!-- Form Section -->
        <section class="space-y-6">
          <div class="grid gap-4">
            <div class="grid grid-cols-2 gap-4">
              <label class="block">
                <span class="text-sm font-bold text-slate-700">Access Type</span>
                <select v-model="formData.accessType" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm focus:ring-4 focus:ring-primary/5">
                  <option value="free">Free Access</option>
                  <option value="paid">Paid Access</option>
                </select>
              </label>
              <label v-if="formData.accessType === 'paid'" class="block animate-in zoom-in-95 duration-200">
                <span class="text-sm font-bold text-slate-700">Price (₦)</span>
                <input v-model.number="formData.price" type="number" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm focus:ring-4 focus:ring-primary/5 shadow-inner" placeholder="Enter amount" />
              </label>
            </div>

            <label v-if="type !== 'bulk'" class="block animate-in slide-in-from-top-2 duration-300">
              <span class="text-sm font-bold text-slate-700">Material Title</span>
              <input v-model="formData.title" type="text" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm focus:ring-4 focus:ring-primary/5" placeholder="e.g. Mathematics Part 1" />
            </label>

            <!-- Material Selection (Bulk) -->
            <div v-if="type === 'bulk'" class="rounded-2xl bg-primary/5 p-6 border border-primary/10">
                <p class="text-xs font-black uppercase tracking-wider text-primary mb-3">Bulk Asset Selection</p>
                <label class="relative flex flex-col items-center justify-center gap-4 rounded-3xl border-2 border-dashed border-primary/20 bg-white p-10 cursor-pointer hover:bg-primary/5 transition-all group">
                    <div class="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center text-primary group-hover:scale-110 duration-300 transition-transform">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-black text-slate-800">Select Multiple Files</p>
                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">PDF, DOC, PNG, etc.</p>
                    </div>
                    <input type="file" multiple @change="handleFileUpload" class="absolute inset-0 opacity-0 cursor-pointer" />
                </label>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-slate-700">{{ showAddCategory ? 'Manage Categories' : 'Category Selection' }}</span>
                <button 
                  type="button"
                  @click="showAddCategory = !showAddCategory" 
                  class="rounded-lg bg-primary/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-primary transition hover:bg-primary/20"
                >
                  {{ showAddCategory ? 'Back to Select' : 'Manage / Create' }}
                </button>
              </div>
              
              <div v-if="showAddCategory" class="space-y-4 animate-in fade-in slide-in-from-top-1 duration-300">
                <div class="flex gap-2 p-1 bg-rose-50/20 rounded-2xl border border-primary/10">
                  <input 
                    v-model="newCategoryName" 
                    type="text" 
                    class="flex-1 bg-transparent p-3 text-sm font-bold outline-none" 
                    placeholder="New category name..." 
                    @keyup.enter="saveCategory" 
                  />
                  <button @click="saveCategory" class="rounded-xl bg-primary px-6 text-xs font-black text-white shadow-lg transition hover:scale-105 active:scale-95">Add</button>
                </div>

                <div class="grid grid-cols-2 gap-2 max-h-[160px] overflow-y-auto pr-1">
                  <div v-for="cat in catalog.categories" :key="cat" class="flex items-center justify-between gap-2 rounded-xl border border-slate-100 bg-white p-2.5 shadow-sm">
                    <span class="text-[11px] font-bold text-slate-600 truncate">{{ cat }}</span>
                    <button 
                      type="button"
                      @click.stop="removeCategory(cat)" 
                      class="text-rose-400 hover:text-rose-600 transition-colors p-0.5"
                    >
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                  </div>
                </div>
              </div>
              
              <select 
                v-else 
                v-model="formData.category" 
                class="w-full rounded-2xl border border-slate-200 bg-white p-3.5 text-sm font-bold text-slate-700 outline-none ring-primary/5 transition focus:border-primary/30 focus:ring-4"
              >
                <option disabled value="">Choose a category</option>
                <option v-for="cat in catalog.categories" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>
          </div>

          <!-- Material Specific (Single) -->
          <template v-if="type === 'material'">
            <label class="block">
              <span class="text-sm font-bold text-slate-700">Attach File (PDF, DOC, etc)</span>
              <input type="file" @change="handleFileUpload" class="mt-1 w-full rounded-2xl border border-dashed border-slate-300 p-8 text-sm text-slate-500" />
            </label>
          </template>

          <template v-if="type === 'cbt'">
            <div class="grid grid-cols-2 gap-4">
              <label class="block">
                <span class="text-sm font-bold text-slate-700">Time Limit (mins)</span>
                <input v-model="formData.duration" type="text" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm" />
              </label>
              <label class="block">
                <span class="text-sm font-bold text-slate-700">Retry Limit (Attempts)</span>
                <input v-model.number="formData.limitAttempts" type="number" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm" />
              </label>
            </div>
          </template>

          <label class="block">
            <span class="text-sm font-bold text-slate-700">Description (Optional)</span>
            <textarea v-model="formData.description" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm" rows="3"></textarea>
          </label>
        </section>

        <!-- Dynamic Content Section (Only for CBT/Bulk) -->
        <section v-if="type !== 'material'" class="rounded-[2rem] bg-slate-50 p-6">
          <template v-if="type === 'cbt'">
            <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4">
               <div>
                  <h4 class="font-black text-slate-800">CBT Questions</h4>
                  <p class="text-[10px] font-bold text-slate-400">Total: {{ formData.questions?.length }}</p>
               </div>
               <div class="flex gap-2 rounded-xl bg-slate-200/50 p-1">
                  <button 
                    @click="questionEntryMode = 'manual'" 
                    class="rounded-lg px-3 py-1.5 text-[10px] font-black transition-all"
                    :class="questionEntryMode === 'manual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                  >Manual</button>
                  <button 
                    @click="questionEntryMode = 'import'" 
                    class="rounded-lg px-3 py-1.5 text-[10px] font-black transition-all"
                    :class="questionEntryMode === 'import' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                  >CSV Import</button>
               </div>
            </div>

            <div v-if="questionEntryMode === 'manual'" class="space-y-4">
              <div class="flex justify-end">
                 <button @click="addQuestion" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-primary">+ Add Question</button>
              </div>
              <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                <div v-for="(q, idx) in formData.questions" :key="q.id" class="rounded-2xl border border-slate-200 bg-white p-4">
                  <div class="flex justify-between">
                    <span class="text-xs font-black text-primary uppercase tracking-widest">Question {{ idx + 1 }}</span>
                    <button @click="removeQuestion(idx)" class="text-[10px] font-black text-rose-500 hover:bg-rose-50 px-2 py-1 rounded-lg">Remove</button>
                  </div>
                  <input v-model="q.prompt" class="mt-2 w-full border-b border-slate-100 py-2 text-sm font-bold outline-none focus:border-primary/30" placeholder="Type question prompt..." />
                  
                  <div class="mt-4 grid gap-3">
                    <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="flex items-center gap-3 rounded-xl border border-slate-50 bg-slate-50/30 p-2 group">
                      <input type="radio" :name="`correct-${q.id}`" v-model="q.correctOption" :value="q.options![oIdx]" class="accent-primary" />
                      <input v-model="q.options![oIdx]" class="w-full bg-transparent text-xs font-medium outline-none" :placeholder="`Option ${String.fromCharCode(65+oIdx)}`" />
                      <button v-if="q.options!.length > 2" @click="removeOption(idx, oIdx)" class="opacity-0 group-hover:opacity-100 text-rose-400 hover:text-rose-600 font-black px-1" title="Remove Option">×</button>
                    </div>
                    <button @click="addOption(idx)" class="mt-1 w-full rounded-xl border border-dashed border-slate-200 py-2 text-[10px] font-black uppercase text-slate-400 hover:border-primary/40 hover:text-primary transition-all">
                      + Add Option
                    </button>
                  </div>
                  <div class="mt-4 flex items-center justify-between border-t border-slate-50 pt-3">
                     <p class="text-[10px] font-bold text-slate-400">Marked Answer: <span class="text-primary">{{ q.correctOption || 'None' }}</span></p>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="questionEntryMode === 'import'" class="flex flex-col items-center justify-center p-8 text-center bg-white rounded-3xl border border-dashed border-slate-200">
               <div class="mb-4 h-16 w-16 rounded-3xl bg-primary/5 flex items-center justify-center text-primary">
                  <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
               </div>
               <h5 class="text-lg font-black text-slate-800">CSV Bulk Upload</h5>
               <p class="mt-2 text-xs font-medium text-slate-500 leading-relaxed max-w-[240px]">
                  Upload questions in bulk using our structured format.
               </p>
               <button @click="downloadTemplate" class="mt-4 text-[10px] font-black uppercase text-primary tracking-widest hover:underline">Download Format Guide</button>
               
               <label class="mt-8 flex w-full cursor-pointer flex-col items-center gap-2 rounded-2xl bg-slate-900 py-4 font-black text-white hover:bg-slate-800 transition-all">
                  <span class="text-sm">Choose CSV File</span>
                  <input type="file" @change="handleCsvUpload" accept=".csv" class="hidden" />
               </label>
            </div>
          </template>

          <template v-if="type === 'bulk'">
             <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="font-black text-slate-800">Bulk Queue</h4>
                    <p class="text-[10px] font-bold text-slate-400">Total: {{ bulkItems.length }} items</p>
                  </div>
                  <button v-if="bulkItems.length > 0" @click="clearBulkQueue" class="text-[10px] font-black uppercase text-rose-500 hover:text-rose-600 transition-colors">
                    Clear All
                  </button>
                </div>
                
                <div class="flex flex-col gap-2">
                  <button 
                    @click="addToBulkList" 
                    :disabled="!canAddToBulk"
                    class="w-full rounded-xl py-3.5 text-xs font-black text-white shadow-xl transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-400"
                    :class="canAddToBulk ? 'bg-slate-900 hover:bg-primary' : 'bg-slate-400'"
                  >
                    {{ editingBulkIndex !== null ? '✓ Update Queued Item' : '+ Add to Bulk Queue' }}
                  </button>
                  <button v-if="editingBulkIndex !== null" @click="resetForm" class="w-full rounded-xl border border-slate-200 py-2.5 text-[10px] font-black uppercase text-slate-500 hover:bg-slate-50">
                    Cancel Editing
                  </button>
                </div>

                <div class="mt-4 space-y-2 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                  <div v-for="(item, idx) in bulkItems" :key="idx" class="group relative flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm border border-slate-100 transition hover:border-primary/20" :class="editingBulkIndex === idx ? 'ring-2 ring-primary/20 border-primary/30' : ''">
                    <div class="overflow-hidden">
                      <p class="text-sm font-black truncate text-slate-800">{{ item.title }}</p>
                      <p class="text-[10px] font-bold text-slate-400 mt-0.5 uppercase tracking-tighter">{{ item.accessType }} • ₦{{ item.price?.toLocaleString() }} • {{ item.category }}</p>
                    </div>
                    <div class="flex items-center gap-1">
                      <button @click="editFromBulk(idx)" class="rounded-lg p-2 text-slate-400 hover:bg-slate-50 hover:text-primary transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                      </button>
                      <button @click="removeFromBulk(idx)" class="rounded-lg p-2 text-slate-300 hover:bg-rose-50 hover:text-rose-500 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
          </template>
        </section>
      </div>

      <div class="mt-10 flex border-t border-slate-100 pt-8 justify-end gap-3">
        <button @click="$emit('close')" class="rounded-2xl border border-slate-200 px-8 py-3 text-sm font-bold text-slate-500">Cancel</button>
        <button 
          @click="handleSubmit" 
          :disabled="!isFormValid"
          class="rounded-2xl px-8 py-3 text-sm font-black text-white shadow-xl transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none"
          :class="isFormValid ? 'bg-secondary shadow-rose-200' : 'bg-slate-300'"
        >
          {{ type === 'bulk' ? 'Execute Bulk Upload' : 'Upload Item' }}
        </button>
      </div>
    </div>
  </div>
</template>
