<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useCatalogStore, type ProductInput, type QuestionItem } from '../../stores/catalog'
import { getApiBaseUrl, getApiToken } from '../../lib/api'

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

// Pre-fill formData if editing
if (props.editProduct) {
  Object.assign(formData, props.editProduct)
}

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

const isUploading = ref(false)
const uploadError = ref('')

const handleFileUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  isUploading.value = true
  uploadError.value = ''

  const body = new FormData()
  body.append('file', file)

  try {
    const res = await fetch(`${getApiBaseUrl()}/admin/materials/upload-file`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${getApiToken() ?? ''}` },
      body,
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json.message ?? 'Upload failed')
    formData.downloadUrl = json.data.url
    if (!formData.format) formData.format = json.data.format
    if (!formData.title) formData.title = file.name.replace(/\.[^/.]+$/, '')
  } catch (err) {
    uploadError.value = err instanceof Error ? err.message : 'Upload failed'
  } finally {
    isUploading.value = false
    input.value = ''
  }
}

// ZIP bulk upload state
const isDragOver = ref(false)
const isExtracting = ref(false)
const extractError = ref('')
const samePriceInput = ref<number | null>(null)
const showSamePriceInput = ref(false)

const handleZipDrop = async (event: DragEvent) => {
  isDragOver.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) await processZipFile(file)
}

const handleZipInput = async (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) await processZipFile(file)
}

const processZipFile = async (file: File) => {
  if (!file.name.toLowerCase().endsWith('.zip')) {
    extractError.value = 'Please upload a .zip file. Supported file types inside: PDF, DOCX, PPT, XLSX, TXT, PNG, JPG.'
    return
  }
  extractError.value = ''
  isExtracting.value = true
  try {
    const files = await catalog.extractZip(file)
    if (files.length === 0) {
      extractError.value = 'No PDF files were found inside the ZIP.'
      return
    }
    files.forEach(({ title, url, format }) => {
      bulkItems.value.push({
        ...formData,
        title,
        downloadUrl: url,
        format: format || formData.format,
        price: 0,
        questions: [],
      })
    })
  } catch (err) {
    extractError.value = err instanceof Error ? err.message : 'ZIP upload failed.'
  } finally {
    isExtracting.value = false
  }
}

const applySamePrice = () => {
  if (samePriceInput.value === null || samePriceInput.value < 0) return
  bulkItems.value = bulkItems.value.map(item => ({
    ...item,
    price: samePriceInput.value!,
    accessType: 'paid' as const,
  }))
  showSamePriceInput.value = false
  samePriceInput.value = null
}

const showSuccess = ref(false)
const submitError = ref('')
const isSaving = ref(false)
const handleSubmit = async () => {
  submitError.value = ''
  isSaving.value = true

  try {
    if (props.type === 'bulk') {
      const itemsToSave = bulkItems.value.map(item => ({
        ...item,
        accessType: item.accessType || formData.accessType,
        price: (item.accessType || formData.accessType) === 'free' ? 0 : (item.price || 0),
      }))
      await catalog.addProductsBulk(itemsToSave)
      emit('close')
      return
    }

    if (props.editProduct) {
      await catalog.updateProduct({ ...formData, id: props.editProduct.id })
      showSuccess.value = true
      setTimeout(() => {
        showSuccess.value = false
        emit('close')
      }, 1200)
      return
    }

    await catalog.addProduct({ ...formData })
    emit('close')
  } catch (error) {
    submitError.value = error instanceof Error ? error.message : 'Unable to save this resource.'
  } finally {
    isSaving.value = false
  }
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
    if (bulkItems.value.length === 0) return false
    return bulkItems.value.every(item =>
      item.accessType === 'free' || (item.accessType === 'paid' && (item.price ?? 0) > 0)
    )
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
          <p class="text-sm font-medium text-slate-500">
            {{ type === 'bulk' ? 'Upload a ZIP of PDFs — set access type, then configure pricing per file.' : 'Fill in the details for the new portal resource.' }}
          </p>
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
              <label v-if="formData.accessType === 'paid' && type !== 'bulk'" class="block animate-in zoom-in-95 duration-200">
                <span class="text-sm font-bold text-slate-700">Price (₦)</span>
                <input v-model.number="formData.price" type="number" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm focus:ring-4 focus:ring-primary/5 shadow-inner" placeholder="Enter amount" />
              </label>
              <div v-if="type === 'bulk' && formData.accessType === 'paid'" class="rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 text-[10px] font-bold text-amber-700 animate-in fade-in duration-200">
                Prices are set individually per file below
              </div>
            </div>

            <label v-if="type !== 'bulk'" class="block animate-in slide-in-from-top-2 duration-300">
              <span class="text-sm font-bold text-slate-700">Material Title</span>
              <input v-model="formData.title" type="text" class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm focus:ring-4 focus:ring-primary/5" placeholder="e.g. Mathematics Part 1" />
            </label>

            <!-- ZIP Bulk Upload Zone -->
            <div v-if="type === 'bulk'" class="rounded-2xl bg-primary/5 p-5 border border-primary/10 space-y-3">
              <div class="flex items-center justify-between">
                <p class="text-xs font-black uppercase tracking-wider text-primary">Upload ZIP File</p>
                <span v-if="bulkItems.length > 0" class="rounded-full bg-primary/10 px-2.5 py-1 text-[10px] font-black text-primary">
                  {{ bulkItems.length }} file{{ bulkItems.length !== 1 ? 's' : '' }} ready
                </span>
              </div>
              <label
                class="relative flex flex-col items-center justify-center gap-4 rounded-3xl border-2 border-dashed p-10 cursor-pointer transition-all duration-200"
                :class="isDragOver
                  ? 'border-primary bg-primary/10 scale-[1.01]'
                  : 'border-primary/20 bg-white hover:bg-primary/5 hover:border-primary/40'"
                @dragover.prevent="isDragOver = true"
                @dragleave.prevent="isDragOver = false"
                @drop.prevent="handleZipDrop"
              >
                <div v-if="isExtracting" class="flex flex-col items-center gap-3 text-primary">
                  <svg class="h-9 w-9 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                  </svg>
                  <p class="text-sm font-black">Extracting PDFs…</p>
                  <p class="text-[10px] font-bold text-slate-400">This may take a moment</p>
                </div>
                <template v-else>
                  <div
                    class="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center text-primary transition-transform duration-300"
                    :class="isDragOver ? 'scale-110' : ''"
                  >
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/>
                    </svg>
                  </div>
                  <div class="text-center">
                    <p class="text-sm font-black text-slate-800">{{ isDragOver ? 'Release to upload' : 'Drop your ZIP file here' }}</p>
                    <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-wide">Or click to browse — ZIP containing PDF, DOCX, PPT, XLSX…</p>
                  </div>
                  <input type="file" accept=".zip" @change="handleZipInput" class="absolute inset-0 opacity-0 cursor-pointer" />
                </template>
              </label>
              <p v-if="extractError" class="flex items-center gap-1.5 rounded-xl bg-rose-50 border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ extractError }}
              </p>
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
              <span class="text-sm font-bold text-slate-700">Protected Material URL</span>
              <input
                v-model="formData.downloadUrl"
                type="url"
                class="mt-1 w-full rounded-2xl border border-slate-200 p-3 text-sm"
                placeholder="Paste a PDF, Google Drive preview link, or hosted embeddable URL"
              />
              <p class="mt-2 text-xs leading-5 text-slate-500">
                Students will view this material inside their dashboard after payment. Use a hosted URL that can be embedded in an iframe.
              </p>
            </label>

            <div class="block">
              <span class="text-sm font-bold text-slate-700">Upload file to server</span>
              <input
                type="file"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt"
                :disabled="isUploading"
                class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-primary/10 file:px-4 file:py-1.5 file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20 disabled:opacity-50"
                @change="handleFileUpload"
              />
              <p v-if="isUploading" class="mt-1 text-xs text-slate-500">Uploading file to server…</p>
              <p v-if="uploadError" class="mt-1 text-xs text-rose-600">{{ uploadError }}</p>
              <p class="mt-1 text-xs text-slate-400">
                Uploading a file stores it on the server and auto-fills the URL above. Alternatively, paste a hosted URL directly.
              </p>
            </div>
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
              <!-- Header -->
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-black text-slate-800">Extracted Files</h4>
                  <p class="text-[10px] font-bold text-slate-400">{{ bulkItems.length }} item{{ bulkItems.length !== 1 ? 's' : '' }} ready to publish</p>
                </div>
                <button v-if="bulkItems.length > 0" @click="clearBulkQueue" class="text-[10px] font-black uppercase text-rose-500 hover:text-rose-600 transition-colors px-2 py-1 rounded-lg hover:bg-rose-50">
                  Clear All
                </button>
              </div>

              <!-- Add Same Price to All — only shown for paid access with items -->
              <div v-if="formData.accessType === 'paid' && bulkItems.length > 0" class="rounded-2xl bg-amber-50 border border-amber-200 p-4 space-y-3 animate-in fade-in duration-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs font-black text-amber-800">Bulk Pricing</p>
                    <p class="text-[10px] text-amber-600 font-medium">Apply one price to all files at once</p>
                  </div>
                  <button
                    @click="showSamePriceInput = !showSamePriceInput"
                    class="rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-wide transition-all active:scale-95"
                    :class="showSamePriceInput ? 'bg-slate-200 text-slate-700' : 'bg-amber-500 text-white hover:bg-amber-600 shadow-md shadow-amber-200'"
                  >
                    {{ showSamePriceInput ? 'Cancel' : 'Add Same Price to All' }}
                  </button>
                </div>
                <div v-if="showSamePriceInput" class="flex gap-2 animate-in slide-in-from-top-1 fade-in duration-150">
                  <div class="relative flex-1">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-black text-amber-600">₦</span>
                    <input
                      v-model.number="samePriceInput"
                      type="number"
                      min="0"
                      placeholder="Enter price"
                      class="w-full rounded-xl border border-amber-300 bg-white pl-7 pr-3 py-2.5 text-sm font-bold outline-none focus:ring-2 focus:ring-amber-300"
                    />
                  </div>
                  <button
                    @click="applySamePrice"
                    :disabled="samePriceInput === null || samePriceInput < 0"
                    class="rounded-xl bg-slate-900 text-white px-5 text-xs font-black hover:bg-primary transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                  >
                    Apply to All
                  </button>
                </div>
              </div>

              <!-- Item list -->
              <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                <div
                  v-for="(item, idx) in bulkItems"
                  :key="idx"
                  class="rounded-2xl bg-white border border-slate-100 p-4 shadow-sm space-y-3 transition hover:border-primary/20 hover:shadow-md"
                >
                  <!-- Title + remove -->
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                      <div class="h-7 w-7 flex-shrink-0 rounded-lg bg-rose-50 flex items-center justify-center">
                        <svg class="h-3.5 w-3.5 text-rose-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                      </div>
                      <div class="min-w-0">
                        <p class="text-sm font-black text-slate-800 truncate">{{ item.title }}</p>
                        <span v-if="item.format" class="inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide bg-slate-100 text-slate-500">{{ item.format }}</span>
                      </div>
                    </div>
                    <button
                      @click="removeFromBulk(idx)"
                      class="flex-shrink-0 rounded-lg p-1.5 text-slate-300 hover:bg-rose-50 hover:text-rose-500 transition-colors"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>

                  <!-- Category dropdown per item -->
                  <select
                    v-model="item.category"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/30"
                  >
                    <option v-for="cat in catalog.categories" :key="cat" :value="cat">{{ cat }}</option>
                  </select>

                  <!-- Per-item price for paid access -->
                  <div v-if="formData.accessType === 'paid'" class="animate-in fade-in duration-200">
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">₦</span>
                      <input
                        v-model.number="item.price"
                        type="number"
                        min="0"
                        placeholder="Set price"
                        class="w-full rounded-xl border pl-7 pr-3 py-2.5 text-sm font-bold outline-none focus:ring-2 transition"
                        :class="(!item.price || item.price <= 0)
                          ? 'border-rose-300 bg-rose-50/30 focus:ring-rose-200'
                          : 'border-emerald-300 bg-emerald-50/20 focus:ring-emerald-200'"
                      />
                    </div>
                    <p v-if="!item.price || item.price <= 0" class="mt-1 text-[10px] font-bold text-rose-500 flex items-center gap-1">
                      <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                      Price required
                    </p>
                  </div>

                  <!-- Free badge -->
                  <div v-else class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1">
                    <svg class="h-3 w-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    <span class="text-[10px] font-black uppercase text-emerald-700">Free Access</span>
                  </div>
                </div>
              </div>

              <!-- Empty state -->
              <div v-if="bulkItems.length === 0 && !isExtracting" class="flex flex-col items-center justify-center py-12 text-center text-slate-400">
                <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                  <svg class="h-7 w-7 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V7"/>
                  </svg>
                </div>
                <p class="text-sm font-black text-slate-500">No files yet</p>
                <p class="text-[10px] font-bold text-slate-400 mt-1">Upload a ZIP file to get started</p>
              </div>
            </div>
          </template>
        </section>
      </div>

      <transition name="slide-fade">
        <div v-if="showSuccess" class="fixed top-6 right-6 z-[100] flex items-center gap-3 rounded-2xl bg-emerald-500 px-6 py-4 text-white shadow-2xl animate-in slide-in-from-right-2 fade-in">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
          <span class="font-black text-sm">Material updated successfully!</span>
        </div>
      </transition>
      <p v-if="submitError" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
        {{ submitError }}
      </p>
      <div class="mt-10 flex border-t border-slate-100 pt-8 justify-end gap-3">
        <button @click="$emit('close')" class="rounded-2xl border border-slate-200 px-8 py-3 text-sm font-bold text-slate-500">Cancel</button>
        <button 
          @click="void handleSubmit()" 
          :disabled="!isFormValid || isSaving"
          class="rounded-2xl px-8 py-3 text-sm font-black text-white shadow-xl transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none"
          :class="isFormValid ? 'bg-secondary shadow-rose-200' : 'bg-slate-300'"
        >
          {{ isSaving ? 'Saving...' : (type === 'bulk' ? 'Execute Bulk Upload' : (editProduct ? 'Update' : 'Upload Item')) }}
        </button>
      </div>
    </div>
  </div>
</template>
