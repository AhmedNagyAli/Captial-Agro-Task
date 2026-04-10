<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Our Products</h1>
        <p class="text-gray-600 mt-2">Choose a product to customize</p>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <input 
          v-model="search"
          type="text"
          placeholder="Search products..."
          class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
          @input="handleSearch"
        />
      </div>

      <!-- Products Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="product in products.data" 
          :key="product.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition"
        >
          <img 
            :src="product.image || '/placeholder.jpg'" 
            :alt="product.name"
            class="w-full h-48 object-cover"
          />
          <div class="p-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ product.name }}</h3>
            <p class="text-gray-600 text-sm mt-1 line-clamp-2">{{ product.description }}</p>
            <div class="mt-4 flex justify-between items-center">
              <div>
                <span class="text-2xl font-bold text-blue-600">${{ formatPrice(product.base_price) }}</span>
                <span v-if="product.sale_price" class="ml-2 text-sm text-gray-400 line-through">
                  ${{ formatPrice(product.sale_price) }}
                </span>
              </div>
              <Link 
                :href="route('products.configure', product.slug)"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition"
              >
                Customize
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="mt-8">
        <div class="flex justify-center">
          <Link 
            v-for="link in products.links"
            :key="link.label"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1 mx-1 border rounded hover:bg-gray-50"
            :class="{ 'bg-blue-600 text-white': link.active }"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  products: Object,
  filters: Object
})

const search = ref(props.filters.search || '')

let timeout = null
function handleSearch() {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    router.get('/products', { search: search.value }, {
      preserveState: true,
      replace: true
    })
  }, 300)
}

function formatPrice(price) {
  return parseFloat(price || 0).toFixed(2)
}
</script>