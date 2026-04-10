<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    order: Object,
    availableCoupons: Array
})

const couponCode = ref('')
const applyingCoupon = ref(false)
const couponError = ref('')
const couponSuccess = ref('')

const groupedItems = computed(() => {
    const groups = {}
    props.order.items.forEach(item => {
        if (!groups[item.option_group_name]) {
            groups[item.option_group_name] = []
        }
        groups[item.option_group_name].push(item)
    })
    return groups
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value)
}

const applyCoupon = async () => {
    if (!couponCode.value.trim()) {
        couponError.value = 'Please enter a coupon code'
        return
    }

    applyingCoupon.value = true
    couponError.value = ''
    couponSuccess.value = ''

    try {
        const response = await axios.post(
            route('orders.coupon.apply', { order: props.order.id }),
            { code: couponCode.value }
        )
        
        if (response.data.success) {
            couponSuccess.value = response.data.message
            couponCode.value = ''
            
            // Refresh the order data
            router.reload({ only: ['order'] })
            
            // Clear success message after 3 seconds
            setTimeout(() => {
                couponSuccess.value = ''
            }, 3000)
        }
    } catch (error) {
        if (error.response?.data?.error) {
            couponError.value = error.response.data.error
        } else {
            couponError.value = 'Failed to apply coupon. Please try again.'
        }
        
        // Clear error after 3 seconds
        setTimeout(() => {
            couponError.value = ''
        }, 3000)
    } finally {
        applyingCoupon.value = false
    }
}

const removeCoupon = async () => {
    applyingCoupon.value = true
    
    try {
        const response = await axios.delete(
            route('orders.coupon.remove', { order: props.order.id })
        )
        
        if (response.data.success) {
            couponSuccess.value = 'Coupon removed successfully'
            
            // Refresh the order data
            router.reload({ only: ['order'] })
            
            setTimeout(() => {
                couponSuccess.value = ''
            }, 3000)
        }
    } catch (error) {
        if (error.response?.data?.error) {
            couponError.value = error.response.data.error
        } else {
            couponError.value = 'Failed to remove coupon'
        }
        
        setTimeout(() => {
            couponError.value = ''
        }, 3000)
    } finally {
        applyingCoupon.value = false
    }
}

const getCouponBadgeClass = () => {
    if (!props.order.coupon) return ''
    
    return props.order.coupon.type === 'percentage' 
        ? 'bg-purple-100 text-purple-800'
        : 'bg-blue-100 text-blue-800'
}

const getDiscountAmount = computed(() => {
    return props.order.discount || 0
})

const getTotalAfterDiscount = computed(() => {
    return props.order.total || props.order.subtotal
})
</script>

<template>
    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h1 class="text-2xl font-bold text-white">Your Custom PC Build</h1>
                <p class="text-blue-100 mt-1">Order #{{ order.id }}</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Build Items -->
                <div v-for="(items, groupName) in groupedItems" :key="groupName" class="border-b pb-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">{{ groupName }}</h2>
                    <div v-for="item in items" :key="item.id" class="flex justify-between items-center">
                        <div>
                            <span class="text-gray-600">{{ item.option_name }}</span>
                            <span v-if="item.quantity > 1" class="text-sm text-gray-500 ml-2">
                                x{{ item.quantity }}
                            </span>
                        </div>
                        <div class="font-medium">{{ formatCurrency(item.price) }}</div>
                    </div>
                </div>

                <!-- Pricing Summary -->
                <div class="pt-4 space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal:</span>
                        <span>{{ formatCurrency(order.subtotal) }}</span>
                    </div>
                    
                    <!-- Applied Coupon Display -->
                    <div v-if="order.coupon" class="flex justify-between text-green-600">
                        <div class="flex items-center gap-2">
                            <span>Discount ({{ order.coupon.code }}):</span>
                            <span :class="['px-2 py-0.5 rounded-full text-xs font-semibold', getCouponBadgeClass()]">
                                {{ order.coupon.type === 'percentage' ? `${order.coupon.value}% OFF` : `FIXED ${formatCurrency(order.coupon.value)}` }}
                            </span>
                        </div>
                        <span>-{{ formatCurrency(getDiscountAmount) }}</span>
                    </div>
                    
                    <div class="border-t pt-2 mt-2">
                        <div class="flex justify-between text-xl font-bold">
                            <span>Total:</span>
                            <span>{{ formatCurrency(getTotalAfterDiscount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Coupon Section -->
                <div class="bg-gray-50 rounded-lg p-4 mt-4">
                    <div v-if="!order.coupon">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Have a coupon code?
                        </label>
                        <div class="flex gap-2">
                            <input 
                                v-model="couponCode"
                                type="text"
                                placeholder="Enter coupon code"
                                :disabled="applyingCoupon"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @keyup.enter="applyCoupon"
                            />
                            <button 
                                @click="applyCoupon"
                                :disabled="applyingCoupon || !couponCode.trim()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                {{ applyingCoupon ? 'Applying...' : 'Apply' }}
                            </button>
                        </div>
                        
                        <!-- Available Coupons Suggestions -->
                        <div v-if="availableCoupons && availableCoupons.length > 0" class="mt-3">
                            <p class="text-xs text-gray-500 mb-2">Available coupons:</p>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="coupon in availableCoupons" 
                                    :key="coupon.code"
                                    @click="couponCode = coupon.code"
                                    class="text-xs px-2 py-1 bg-gray-200 rounded hover:bg-gray-300 transition-colors"
                                >
                                    {{ coupon.code }}
                                    <span class="text-gray-600 ml-1">
                                        ({{ coupon.type === 'percentage' ? `${coupon.value}%` : formatCurrency(coupon.value) }}
                                        <span v-if="coupon.min_total"> / min {{ formatCurrency(coupon.min_total) }}</span>)
                                    </span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <div v-if="couponError" class="mt-2 text-sm text-red-600">
                            {{ couponError }}
                        </div>
                        <div v-if="couponSuccess" class="mt-2 text-sm text-green-600">
                            {{ couponSuccess }}
                        </div>
                    </div>
                    
                    <div v-else class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600">Applied Coupon:</p>
                            <p class="font-semibold text-green-600">
                                {{ order.coupon.code }}
                                <span class="text-sm font-normal text-gray-500">
                                    ({{ order.coupon.type === 'percentage' ? `${order.coupon.value}% off` : `${formatCurrency(order.coupon.value)} off` }})
                                </span>
                            </p>
                        </div>
                        <button 
                            @click="removeCoupon"
                            :disabled="applyingCoupon"
                            class="text-sm text-red-600 hover:text-red-700 disabled:opacity-50"
                        >
                            Remove
                        </button>
                    </div>
                </div>

                <!-- Order Status -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Order Status:</span>
                        <span :class="[
                            'px-3 py-1 rounded-full text-sm font-semibold',
                            order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                            order.status === 'completed' ? 'bg-green-100 text-green-800' :
                            'bg-red-100 text-red-800'
                        ]">
                            {{ order.status.toUpperCase() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>