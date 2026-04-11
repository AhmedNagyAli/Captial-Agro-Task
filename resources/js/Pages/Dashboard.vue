<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, onMounted, computed, shallowRef } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { watch } from 'vue'

const props = defineProps({
    groups: { type: Array, required: true }
})

const groups = ref([])

watch(
    () => props.groups,
    (val) => {
        groups.value = val || []
    },
    { immediate: true }
)

const isSidebarOpen = ref(false)
const configId = ref(null)
const selectedOptions = shallowRef({})
const totalPrice = ref(0)
const isLoading = ref(false)
const error = ref(null)
const isRestoring = ref(false)

const STORAGE_KEY = 'pc_builder_selections'
const VISITED_KEY = 'pc_builder_visited'

const formatCurrency = (val) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(parseFloat(val) || 0)

const saveToStorage = () => {
    if (!configId.value) return

    if (Object.keys(selectedOptions.value).length === 0) {
        clearStorage()
        return
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        configId: configId.value,
        selections: selectedOptions.value,
        total: totalPrice.value,
        lastUpdated: new Date().toISOString()
    }))
}

const loadFromStorage = () => JSON.parse(localStorage.getItem(STORAGE_KEY))
const clearStorage = () => localStorage.removeItem(STORAGE_KEY)

const hasSelections = computed(() => Object.keys(selectedOptions.value).length > 0)
const formattedTotal = computed(() => formatCurrency(totalPrice.value))
const completionPercentage = computed(() => {
    if (!groups.value.length) return 0
    return Math.round((Object.keys(selectedOptions.value).length / props.groups.length) * 100)
})

const createConfiguration = async () => {
    const { data } = await axios.post(route('config.store'))
    configId.value = data.config_id
    return data.config_id
}

const selectOption = async (optionId, groupId, option) => {
    if (!configId.value || isLoading.value) return
    isLoading.value = true

    if (!isSidebarOpen.value) isSidebarOpen.value = true

    try {
        const { data } = await axios.post(route('config.select'), {
            config_id: configId.value,
            option_id: optionId
        })

        selectedOptions.value = {
            ...selectedOptions.value,
            [groupId]: {
                id: optionId,
                name: option.name,
                price: option.price_value
            }
        }

        totalPrice.value = data.total
        saveToStorage()
    } finally {
        isLoading.value = false
    }
}

const removeItem = (groupId) => {
    delete selectedOptions.value[groupId]
    selectedOptions.value = { ...selectedOptions.value }

    if (Object.keys(selectedOptions.value).length === 0) {
        clearStorage()
        totalPrice.value = 0
        return
    }

    saveToStorage()
}

const submitOrder = async () => {
    if (!configId.value || !hasSelections.value) return
    clearStorage()
    router.post(route('orders.store'), { config_id: configId.value })
}

const clearSelections = async () => {
    const result = await Swal.fire({
        title: 'Reset Build?',
        text: 'This will clear all chosen components.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0f172a',
        confirmButtonText: 'Clear Build'
    })

    if (result.isConfirmed) {
        selectedOptions.value = {}
        totalPrice.value = 0
        clearStorage()
        await createConfiguration()
    }
}

const showWelcomeAlert = () => {
    const hasVisited = localStorage.getItem(VISITED_KEY)

    if (!hasVisited) {
        Swal.fire({
            html: `
                <div style="text-align:left; font-family:system-ui, -apple-system, sans-serif;">
                    
                    <h2 style="font-size:20px; font-weight:800; color:#0f172a; margin-bottom:14px; letter-spacing:-0.01em;">
                        Demonstration Interface
                    </h2>

                    <p style="font-size:14.5px; font-weight:600; color:#475569; line-height:1.7; margin-bottom:20px;">
                        This interface represents a modular configuration system. 
                        While the current example displays PC components, the underlying structure 
                        is designed for broad application across any configurable product or service.
                    </p>

                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:6px; margin-bottom:10px;">
                        <p style="font-size:15px; font-weight:800; color:#0f172a; margin-bottom:10px; text-transform:uppercase; letter-spacing:0.05em;">
                            Applicable Use Cases
                        </p>

                        <ul style="margin:0; padding-left:18px; font-size:13.5px; font-weight:500; color:#334155; line-height:1.8;">
                            <li>Electronics configuration systems</li>
                            <li>Food customization workflows (multi-option ordering)</li>
                            <li>Service bundles and packaged offerings</li>
                            <li>General-purpose product configurators</li>
                        </ul>
                    </div>

                    <div style="border-top:1px solid #e2e8f0; margin:8px 0;"></div>

                    <p style="font-size:12.5px; font-weight:800; color:#334155; line-height:1.6;">
                        The focus is structural flexibility, not the displayed category.
                    </p>

                </div>
            `,
            confirmButtonColor: '#0f172a',
            confirmButtonText: 'Continue',
            background: '#ffffff',
            allowOutsideClick: false,
            customClass: {
                popup: 'rounded-2xl shadow-xl max-w-xl',
                htmlContainer: 'p-0',
                confirmButton: 'px-7 py-2.5 rounded-lg font-semibold text-sm tracking-wide'
            }
        })

        localStorage.setItem(VISITED_KEY, 'true')
    }
}

const autoRestoreBuild = async () => {
    const saved = loadFromStorage()
    
    // Always restore if there's a saved build - no confirmation
    if (saved && Object.keys(saved.selections).length > 0) {
        isRestoring.value = true
        try {
            configId.value = await createConfiguration()
            for (const [groupId, option] of Object.entries(saved.selections)) {
                await axios.post(route('config.select'), {
                    config_id: configId.value,
                    option_id: option.id
                })
                selectedOptions.value = { ...selectedOptions.value, [groupId]: option }
            }
            totalPrice.value = saved.total
        } finally {
            isRestoring.value = false
        }
    } else {
        // No saved build, just create fresh config
        await createConfiguration()
    }
}

onMounted(async () => {
    await autoRestoreBuild()
    showWelcomeAlert()
})
</script>

<template>
    <AppLayout>
        <div class="relative flex min-h-screen bg-gray-250">
            
            <main class="flex-1 px-4 py-8 md:px-10 transition-all duration-500 ease-in-out" :class="{'mr-80': isSidebarOpen}">
                <div class="max-w-5xl mx-auto">

                    <header class="mb-12">
                        <h1 class="text-3xl font-black tracking-tight text-slate-900 uppercase">BUILD YOUR PC</h1>
                        <p class="mt-2 text-slate-500">Configure your dream machine with precision parts.</p>
                        
                        <div class="mt-4 flex items-center gap-4 bg-white/80 backdrop-blur p-4 rounded-xl border border-slate-200 shadow-sm">
                            <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-slate-800 to-black transition-all duration-500"
                                     :style="{ width: `${completionPercentage}%` }"></div>
                            </div>
                            <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                {{ completionPercentage }}% Complete
                            </span>
                        </div>
                    </header>

                    <div v-for="group in props.groups" :key="group.id" class="mb-6 p-6 rounded-2xl bg-gray-300 border border-slate-200 shadow-xl backdrop-blur-sm">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="h-8 w-1 bg-slate-900/80 rounded-full"></div>
                            <h2 class="text-lg font-bold text-slate-900 uppercase tracking-wide">
                                {{ group.name }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <button
                                v-for="option in group.options"
                                :key="option.id"
                                @click="selectOption(option.id, group.id, option)"
                                class="group relative flex flex-col p-5 text-left transition-all border rounded-xl overflow-hidden"
                                :class="[
                                    selectedOptions[group.id]?.id === option.id 
                                    ? 'border-slate-900 bg-white shadow-lg ring-1 ring-slate-900 scale-[1.02]' 
                                    : 'border-slate-200 bg-white/90 hover:bg-white hover:border-slate-300 shadow-sm hover:shadow-md hover:-translate-y-0.5'
                                ]"
                            >
                                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition bg-gradient-to-br from-white/40 via-slate-50/40 to-transparent"></div>

                                <div class="relative flex justify-between items-start mb-2">
                                    <span class="font-bold text-slate-900">{{ option.name }}</span>

                                    <div v-if="selectedOptions[group.id]?.id === option.id"
                                         class="bg-slate-900 text-white rounded-full p-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>

                                <span class="relative text-sm font-medium text-slate-500">
                                    {{ formatCurrency(option.price_value) }}
                                </span>
                            </button>
                        </div>
                    </div>

                </div>
            </main>

            <aside 
                class="fixed right-0 top-0 h-full bg-white/95 backdrop-blur border-l border-slate-200 transition-transform duration-500 ease-in-out z-40 shadow-2xl"
                :class="[isSidebarOpen ? 'translate-x-0 w-80' : 'translate-x-full w-80']"
            >
                <div class="flex flex-col h-full">

                    <div class="p-6 border-b border-slate-200 flex justify-between items-center bg-slate-50/70">
                        <h3 class="font-black text-slate-900 uppercase tracking-widest text-sm">
                            Your Selections
                        </h3>
                        <button @click="isSidebarOpen = false"
                                class="p-2 hover:bg-slate-200 rounded-lg transition-colors">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-6">

                        <div v-if="!hasSelections" class="text-center py-12">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-slate-400 font-medium">No parts selected yet.</p>
                            <p class="text-xs text-slate-300 mt-1">Click on any component to start building</p>
                        </div>

                        <div 
                            v-for="(option, groupId) in selectedOptions" 
                            :key="groupId" 
                            class="group relative bg-gray-300 p-4 rounded-xl border border-slate-200 hover:border-slate-300 transition-all hover:shadow-sm"
                        >
                            <button 
                                @click="removeItem(groupId)"
                                class="absolute -top-2 -right-2 bg-white border border-slate-200 rounded-full p-1 opacity-0 group-hover:opacity-100 hover:text-red-500"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <div class="flex justify-between items-start gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                        {{ groups.find(g => g.id == groupId)?.name }}
                                    </p>
                                    <p class="text-sm font-bold text-slate-800 leading-tight">
                                        {{ option.name }}
                                    </p>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ formatCurrency(option.price) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-white border-t border-slate-200">
                        <div class="flex justify-between items-end mb-6">
                            <span class="text-xs font-bold text-slate-400 uppercase">Subtotal</span>
                            <span class="text-2xl font-black text-slate-900 tracking-tight">
                                {{ formattedTotal }}
                            </span>
                        </div>
                        
                        <button 
                            @click="submitOrder"
                            :disabled="!hasSelections || isLoading"
                            class="w-full py-4 bg-slate-900 hover:bg-black text-white font-bold rounded-xl transition-all active:scale-95 disabled:opacity-30 disabled:pointer-events-none shadow-lg hover:shadow-xl"
                        >
                            {{ isLoading ? 'Processing...' : 'Complete Purchase' }}
                        </button>

                        <button 
                            @click="clearSelections" 
                            class="w-full mt-4 py-2 text-xs font-bold text-slate-400 uppercase hover:text-red-500 transition-colors"
                        >
                            Reset Components
                        </button>
                    </div>

                </div>
            </aside>

            <button 
                v-if="!isSidebarOpen"
                @click="isSidebarOpen = true"
                class="fixed right-6 bottom-6 w-14 h-14 bg-slate-900 text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 transition-all z-50 animate-bounce-subtle"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>

                <span 
                    v-if="hasSelections" 
                    class="absolute -top-1 -right-1 bg-red-500 text-[10px] w-5 h-5 flex items-center justify-center rounded-full font-bold border-2 border-white"
                >
                    {{ Object.keys(selectedOptions).length }}
                </span>
            </button>

        </div>
    </AppLayout>
</template>

<style>
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
.animate-bounce-subtle {
    animation: bounce-subtle 2s infinite;
}
</style>