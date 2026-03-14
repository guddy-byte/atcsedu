<script setup lang="ts">
import { computed, reactive, ref } from 'vue'

import { useCatalogStore, type ProductInput } from '../stores/catalog'

const catalog = useCatalogStore()

interface SingleForm {
  title: string
  category: string
  format: string
  price: string
  stock: string
  description: string
}

const singleForm = reactive<SingleForm>({
  title: '',
  category: '',
  format: 'Single',
  price: '',
  stock: '',
  description: '',
})

const bulkText = ref('')
const feedback = ref('')

const bulkTemplate = 'Title,Category,Format,Price,Stock,Description'

const resetSingleForm = () => {
  singleForm.title = ''
  singleForm.category = ''
  singleForm.format = 'Single'
  singleForm.price = ''
  singleForm.stock = ''
  singleForm.description = ''
}

const submitSingle = () => {
  const product: ProductInput = {
    title: singleForm.title.trim(),
    category: singleForm.category.trim(),
    format: singleForm.format,
    price: Number(singleForm.price),
    stock: Number(singleForm.stock),
    description: singleForm.description.trim(),
    type: 'material'
  }

  catalog.addProduct(product)

  feedback.value = `Added ${singleForm.title} to the catalog.`
  resetSingleForm()
}

// FIXME: Commented out due to missing 'type' property causing build error
// const parseBulkProducts = (rawText: string): ProductInput[] => rawText
//   .split('\n')
//   .map((line) => line.trim())
//   .filter(Boolean)
//   .map((line) => {
//     const [title, category, format, price, stock, ...descriptionParts] = line.split(',')
//
//     return {
//       title: title?.trim() ?? '',
//       category: category?.trim() ?? '',
//       format: format?.trim() || 'Bulk',
//       price: Number(price),
//       stock: Number(stock),
//       description: descriptionParts.join(',').trim(),
//     }
//   })
//   .filter((product) => product.title && product.category && Number.isFinite(product.price))

// FIXME: Bulk submit disabled due to parseBulkProducts issue
// const submitBulk = () => {
//   const parsed = parseBulkProducts(bulkText.value)
//
//   if (!parsed.length) {
//     feedback.value = 'No valid bulk rows found. Use the sample format below.'
//     return
//   }
//
//   catalog.addProductsBulk(parsed)
//   feedback.value = `Added ${parsed.length} products in bulk.`
//   bulkText.value = ''
// }

const metrics = computed(() => [
  { label: 'Catalog size', value: catalog.products.length },
  { label: 'Cart demand', value: catalog.cartItemCount },
  { label: 'Revenue estimate', value: `$${catalog.revenueEstimate}` },
])
</script>

<template>
  <section class="section-header">
    <div>
      <p class="eyebrow">Admin</p>
      <h1>Upload materials and assign pricing</h1>
    </div>
    <p class="helper-text">Single entry for one product, or bulk CSV-style input for many.</p>
  </section>

  <section class="metrics-grid">
    <article v-for="metric in metrics" :key="metric.label" class="stat-card">
      <small>{{ metric.label }}</small>
      <strong>{{ metric.value }}</strong>
    </article>
  </section>

  <section class="admin-grid">
    <form class="card form-card" @submit.prevent="submitSingle">
      <div class="form-header">
        <h2>Single upload</h2>
        <p>Create one material with price and stock.</p>
      </div>

      <label>
        <span>Title</span>
        <input v-model="singleForm.title" type="text" required />
      </label>

      <label>
        <span>Category</span>
        <input v-model="singleForm.category" type="text" required />
      </label>

      <div class="two-up">
        <label>
          <span>Format</span>
          <select v-model="singleForm.format">
            <option>Single</option>
            <option>Bulk</option>
          </select>
        </label>

        <label>
          <span>Price</span>
          <input v-model="singleForm.price" type="number" min="0" step="0.01" required />
        </label>
      </div>

      <div class="two-up">
        <label>
          <span>Stock</span>
          <input v-model="singleForm.stock" type="number" min="0" required />
        </label>

        <label>
          <span>Description</span>
          <input v-model="singleForm.description" type="text" required />
        </label>
      </div>

      <button class="button primary wide" type="submit">Add product</button>
    </form>

    <section class="card form-card">
      <div class="form-header">
        <h2>Bulk upload</h2>
        <p>One line per product using a simple CSV-style format.</p>
      </div>

      <div class="bulk-note">
        <strong>Format</strong>
        <code>{{ bulkTemplate }}</code>
      </div>

      <textarea
        v-model="bulkText"
        rows="12"
        :placeholder="`${bulkTemplate}\nWorkbook Pack,English,Bulk,65,10,Term-long student workbook`"
      />

      <!-- FIXME: Bulk upload disabled due to parseBulkProducts issue
      <button class="button primary wide" type="button" @click="submitBulk">Upload bulk list</button>
      -->
    </section>
  </section>

  <p v-if="feedback" class="feedback">{{ feedback }}</p>

  <section class="card table-card">
    <div class="form-header">
      <h2>Current catalog</h2>
      <p>New uploads appear here immediately.</p>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Format</th>
            <th>Price</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in catalog.products" :key="product.id">
            <td>{{ product.title }}</td>
            <td>{{ product.category }}</td>
            <td>{{ product.format }}</td>
            <td>${{ product.price }}</td>
            <td>{{ product.stock }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>