<script setup lang="ts">
import { useCatalogStore } from '../stores/catalog'

const catalog = useCatalogStore()

const updateItem = (productId: string, event: Event) => {
  const nextQuantity = Number((event.target as HTMLInputElement | null)?.value ?? 0)
  catalog.updateQuantity(productId, nextQuantity)
}
</script>

<template>
  <section class="section-header">
    <div>
      <p class="eyebrow">Checkout</p>
      <h1>Your cart</h1>
    </div>
    <RouterLink class="button secondary" to="/">Continue shopping</RouterLink>
  </section>

  <div class="cart-layout">
    <section class="card cart-items">
      <template v-if="catalog.cartItems.length">
        <article v-for="item in catalog.cartItems" :key="item.id" class="cart-row">
          <div>
            <h3>{{ item.title }}</h3>
            <p>{{ item.category }} • {{ item.format }}</p>
          </div>

          <label class="quantity-field">
            <span>Qty</span>
            <input
              type="number"
              min="0"
              :value="item.quantity"
              @input="updateItem(item.id, $event)"
            />
          </label>

          <strong>${{ item.lineTotal }}</strong>
        </article>
      </template>

      <div v-else class="empty-state">
        <h3>Your cart is empty.</h3>
        <p>Add products from the storefront to start building an order.</p>
      </div>
    </section>

    <aside class="card summary-card">
      <p class="eyebrow">Order summary</p>
      <div class="summary-line">
        <span>Items</span>
        <strong>{{ catalog.cartItemCount }}</strong>
      </div>
      <div class="summary-line total">
        <span>Total</span>
        <strong>${{ catalog.cartTotal }}</strong>
      </div>

      <button class="button primary wide">Proceed to checkout</button>
    </aside>
  </div>
</template>