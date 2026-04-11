<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
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
            router.reload({ only: ['order'] })
            
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
        ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white'
        : 'bg-gradient-to-r from-blue-500 to-blue-600 text-white'
}

const getDiscountAmount = computed(() => {
    return props.order.discount || 0
})

const getTotalAfterDiscount = computed(() => {
    return props.order.total || props.order.subtotal
})
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/20 to-gray-50 py-8 px-4">
            <div class="max-w-4xl mx-auto">
                <!-- Header with Order Summary -->
                <div class="mb-6">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-slate-900/5 rounded-full mb-3">
                                <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span class="text-[11px] font-bold text-slate-900 uppercase tracking-wider">Order Details</span>
                            </div>
                            <h1 class="text-3xl font-black tracking-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">
                                Order Summary
                            </h1>
                            <p class="text-slate-500 mt-1">Review your custom configuration</p>
                        </div>
                        <div class="bg-white rounded-xl px-4 py-2 shadow-sm border border-slate-200">
                            <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Order ID</span>
                            <p class="text-lg font-bold text-slate-900">#{{ order.id }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Main Order Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Build Components Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                            <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 7h14M5 7a2 2 0 00-2 2v8a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2M5 7a2 2 0 012-2h10a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                    <h2 class="text-lg font-bold text-white">Your Custom Build</h2>
                                </div>
                            </div>
                            
                            <div class="p-6 space-y-6">
                                <div v-for="(items, groupName) in groupedItems" :key="groupName" class="border-b border-slate-300 last:border-0 pb-4 last:pb-0">
                                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">{{ groupName }}</h3>
                                    <div class="space-y-2">
                                        <div v-for="item in items" :key="item.id" class="flex justify-between items-center py-2 hover:bg-slate-50 rounded-lg px-2 transition-colors">
                                            <div class="flex items-center gap-3">
                                                <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                                                <div>
                                                    <span class="text-slate-700 font-medium">{{ item.option_name }}</span>
                                                    <span v-if="item.quantity > 1" class="text-xs text-slate-400 ml-2">
                                                        ×{{ item.quantity }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="font-semibold text-slate-900">{{ formatCurrency(item.price) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between flex-wrap gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Current Status</p>
                                        <p :class="[
                                            'text-lg font-bold',
                                            order.status === 'pending' ? 'text-yellow-600' :
                                            order.status === 'completed' ? 'text-emerald-600' :
                                            'text-red-600'
                                        ]">
                                            {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>Updated just now</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 sticky top-6 hover:shadow-md transition-shadow">
                            <!-- Price Breakdown -->
                            <div class="p-6 border-b border-slate-200">
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Price Breakdown</h3>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between text-slate-600">
                                        <span>Subtotal</span>
                                        <span class="font-medium">{{ formatCurrency(order.subtotal) }}</span>
                                    </div>
                                    
                                    <div v-if="order.coupon" class="flex justify-between text-emerald-600">
                                        <div class="flex items-center gap-2">
                                            <span>Discount</span>
                                            <span :class="['px-2 py-0.5 rounded-lg text-xs font-bold', getCouponBadgeClass()]">
                                                {{ order.coupon.code }}
                                            </span>
                                        </div>
                                        <span class="font-semibold">-{{ formatCurrency(getDiscountAmount) }}</span>
                                    </div>
                                    
                                    <div class="border-t border-slate-200 pt-3 mt-3">
                                        <div class="flex justify-between text-lg font-black text-slate-900">
                                            <span>Total</span>
                                            <span class="text-2xl">{{ formatCurrency(getTotalAfterDiscount) }}</span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">Including all taxes & fees</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Coupon Section -->
                            <div class="p-6">
                                <div v-if="!order.coupon">
                                    <label class="block text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">
                                        Have a coupon?
                                    </label>
                                    
                                    <div class="space-y-3">
                                        <div class="flex gap-2">
                                            <input 
                                                v-model="couponCode"
                                                type="text"
                                                placeholder="Enter coupon code"
                                                :disabled="applyingCoupon"
                                                class="flex-1 rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-slate-900 transition-all px-3 py-2 text-sm"
                                                @keyup.enter="applyCoupon"
                                            />
                                            <button 
                                                @click="applyCoupon"
                                                :disabled="applyingCoupon || !couponCode.trim()"
                                                class="px-4 py-2 bg-slate-900 text-white rounded-xl hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed transition-all font-medium text-sm"
                                            >
                                                {{ applyingCoupon ? '...' : 'Apply' }}
                                            </button>
                                        </div>
                                        
                                        <!-- Available Coupons -->
                                        <div v-if="availableCoupons && availableCoupons.length > 0" class="space-y-2">
                                            <p class="text-xs font-medium text-slate-500">Try these coupons:</p>
                                            <div class="flex flex-wrap gap-2">
                                                <button 
                                                    v-for="coupon in availableCoupons" 
                                                    :key="coupon.code"
                                                    @click="couponCode = coupon.code"
                                                    class="text-xs px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors font-mono font-semibold text-slate-700"
                                                >
                                                    {{ coupon.code }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Messages -->
                                    <div v-if="couponError" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl">
                                        <p class="text-sm text-red-600">{{ couponError }}</p>
                                    </div>
                                    <div v-if="couponSuccess" class="mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                                        <p class="text-sm text-emerald-600">{{ couponSuccess }}</p>
                                    </div>
                                </div>
                                
                                <div v-else class="bg-gradient-to-r from-emerald-50 to-emerald-100/50 rounded-xl p-4 border border-emerald-200">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Applied Coupon</p>
                                            <p class="text-lg font-black text-emerald-800 mt-1">{{ order.coupon.code }}</p>
                                            <p class="text-xs text-emerald-600 mt-0.5">
                                                {{ order.coupon.type === 'percentage' ? `${order.coupon.value}% OFF` : `${formatCurrency(order.coupon.value)} OFF` }}
                                            </p>
                                        </div>
                                        <button 
                                            @click="removeCoupon"
                                            :disabled="applyingCoupon"
                                            class="text-xs text-red-600 hover:text-red-700 font-medium disabled:opacity-50 hover:scale-105 transition-transform"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <div class="p-6 border-t border-slate-200 bg-slate-50/50 rounded-b-2xl">
                                <button 
                                    @click="router.post(route('orders.checkout', { order: order.id }))"
                                    class="w-full py-3.5 bg-gradient-to-r from-slate-900 to-slate-800 hover:from-black hover:to-slate-900 text-white font-bold rounded-xl transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg"
                                >
                                    Proceed to Checkout →
                                </button>
                                <p class="text-xs text-center text-slate-400 mt-3">
                                    Secure payment • 30-day guarantee
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Smooth transitions */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>