<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, onMounted, computed, watch, shallowRef } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    groups: {
        type: Array,
        required: true,
        default: () => []
    }
})

// State Management
const configId = ref(null)
const selectedOptions = shallowRef({})
const totalPrice = ref(0)
const isLoading = ref(false)
const error = ref(null)
const isRestoring = ref(false)

// Storage keys
const STORAGE_KEY = 'pc_builder_selections'

// Helper Functions
const formatCurrency = (value) => {
    if (!value && value !== 0) return '$0.00'
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(parseFloat(value) || 0)
}

// Persistence Functions
const saveToStorage = () => {
    if (!configId.value || Object.keys(selectedOptions.value).length === 0) return
    
    const data = {
        configId: configId.value,
        selections: selectedOptions.value,
        total: totalPrice.value,
        lastUpdated: new Date().toISOString()
    }
    
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
        console.log('Saved to storage:', data)
    } catch (error) {
        console.error('Failed to save to localStorage:', error)
    }
}

const loadFromStorage = () => {
    try {
        const data = localStorage.getItem(STORAGE_KEY)
        if (!data) return null
        
        const parsed = JSON.parse(data)
        console.log('Loaded from storage:', parsed)
        return parsed
    } catch (error) {
        console.error('Failed to load from localStorage:', error)
        return null
    }
}

const clearStorage = () => {
    localStorage.removeItem(STORAGE_KEY)
    console.log('Storage cleared')
}

// Computed Properties
const hasSelections = computed(() => Object.keys(selectedOptions.value || {}).length > 0)

const formattedTotal = computed(() => formatCurrency(totalPrice.value))

const selectedCount = computed(() => Object.keys(selectedOptions.value || {}).length)

const totalGroupsCount = computed(() => props.groups?.length || 0)

const completionPercentage = computed(() => {
    if (totalGroupsCount.value === 0) return 0
    return Math.round((selectedCount.value / totalGroupsCount.value) * 100)
})

// Create a map for faster group name lookups
const groupNameMap = computed(() => {
    const map = new Map()
    if (props.groups) {
        props.groups.forEach(group => {
            map.set(group.id, group.name)
        })
    }
    return map
})

const getGroupName = (groupId) => {
    const id = parseInt(groupId)
    return groupNameMap.value.get(id) || 'Option'
}

// API Calls
const createConfiguration = async () => {
    try {
        const response = await axios.post(route('config.store'))
        configId.value = response.data.config_id
        error.value = null
        console.log('Created new configuration:', configId.value)
        return response.data.config_id
    } catch (err) {
        error.value = 'Failed to initialize builder. Please refresh the page.'
        console.error('Failed to create configuration:', err)
        throw err
    }
}

const selectOption = async (optionId, groupId, option) => {
    if (!configId.value || isLoading.value || isRestoring.value) return
    
    isLoading.value = true
    error.value = null
    
    try {
        const response = await axios.post(route('config.select'), {
            config_id: configId.value,
            option_id: optionId
        })
        
        // Update state
        selectedOptions.value = {
            ...selectedOptions.value,
            [groupId]: {
                id: optionId,
                name: option.name,
                price: option.price_value
            }
        }
        
        totalPrice.value = response.data.total
        
        // Save to localStorage
        saveToStorage()
        
    } catch (err) {
        error.value = 'Failed to select option. Please try again.'
        console.error('Failed to select option:', err)
    } finally {
        isLoading.value = false
    }
}

const submitOrder = async () => {
    if (!configId.value) return
    
    if (!hasSelections.value) {
        error.value = 'Please select at least one option before ordering'
        setTimeout(() => { error.value = null }, 3000)
        return
    }
    
    try {
        // Clear storage before redirecting
        clearStorage()
        
        router.post(route('orders.store'), {
            config_id: configId.value
        })
    } catch (err) {
        error.value = 'Failed to create order. Please try again.'
        console.error('Failed to create order:', err)
    }
}

const isSelected = (groupId, optionId) => {
    return selectedOptions.value[groupId]?.id === optionId
}

const clearSelections = async () => {
    if (!confirm('Clear all selections? This action cannot be undone.')) return
    
    isLoading.value = true
    try {
        // Clear local state
        selectedOptions.value = {}
        totalPrice.value = 0
        
        // Clear storage
        clearStorage()
        
        // Create new configuration
        const newConfigId = await createConfiguration()
        configId.value = newConfigId
        
    } catch (err) {
        error.value = 'Failed to clear selections'
        console.error('Failed to clear selections:', err)
    } finally {
        isLoading.value = false
    }
}

// Auto-restore saved build
const autoRestoreBuild = async () => {
    const saved = loadFromStorage()
    
    if (!saved || !saved.selections || Object.keys(saved.selections).length === 0) {
        console.log('No saved build found, starting fresh')
        await createConfiguration()
        return
    }
    
    console.log('Found saved build, attempting to restore...')
    isRestoring.value = true
    isLoading.value = true
    
    try {
        // First, validate that saved options still exist
        const validSelections = {}
        let hasValidSelections = false
        
        for (const [groupId, option] of Object.entries(saved.selections)) {
            const group = props.groups?.find(g => g.id == groupId)
            if (group) {
                const optionExists = group.options?.some(o => o.id == option.id)
                if (optionExists) {
                    validSelections[groupId] = option
                    hasValidSelections = true
                } else {
                    console.warn(`Option ${option.id} no longer exists in group ${groupId}`)
                }
            } else {
                console.warn(`Group ${groupId} no longer exists`)
            }
        }
        
        if (!hasValidSelections) {
            console.log('No valid selections found, starting fresh')
            clearStorage()
            await createConfiguration()
            return
        }
        
        // Create new configuration
        const newConfigId = await createConfiguration()
        configId.value = newConfigId
        
        // Restore each valid selection
        for (const [groupId, option] of Object.entries(validSelections)) {
            try {
                await axios.post(route('config.select'), {
                    config_id: newConfigId,
                    option_id: option.id
                })
                
                // Update local state
                selectedOptions.value = {
                    ...selectedOptions.value,
                    [groupId]: option
                }
                
            } catch (err) {
                console.error(`Failed to restore option ${option.id}:`, err)
            }
        }
        
        // Update total price
        totalPrice.value = saved.total
        
        // Save restored build to storage
        saveToStorage()
        
        console.log('Build restored successfully!')
        
    } catch (err) {
        console.error('Failed to restore build:', err)
        error.value = 'Could not restore your previous build. Starting fresh.'
        await createConfiguration()
    } finally {
        isLoading.value = false
        isRestoring.value = false
    }
}

// Watch for changes and save to storage
watch([selectedOptions, totalPrice], () => {
    if (configId.value && hasSelections.value && !isRestoring.value) {
        saveToStorage()
    }
}, { deep: true })

// Lifecycle
onMounted(async () => {
    console.log('Dashboard mounted, checking for saved build...')
    console.log('Groups available:', props.groups?.length)
    
    // Auto-restore or create new
    await autoRestoreBuild()
})
</script>

<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                
                <!-- Loading Indicator during restore -->
                <div v-if="isRestoring" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                    <p class="text-gray-600">Restoring your saved build...</p>
                </div>
                
                <!-- Error Alert -->
                <div v-if="error" class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Main Content - Only show when not restoring -->
                <div v-if="!isRestoring">
                    <!-- Auto-save Indicator -->
                    <div v-if="hasSelections" class="mb-4 text-right">
                        <div class="inline-flex items-center gap-2 text-xs text-green-600 bg-green-50 px-3 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Auto-saved</span>
                        </div>
                    </div>
                    
                    <!-- Current Selections Summary -->
                    <div v-if="hasSelections" class="mb-8 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-lg font-semibold text-gray-800">Current Build</h3>
                            <div class="flex items-center gap-3">
                                <div class="text-sm text-gray-600">
                                    Progress: {{ completionPercentage }}%
                                </div>
                                <button 
                                    @click="clearSelections"
                                    class="text-sm text-red-600 hover:text-red-700"
                                    :disabled="isLoading"
                                >
                                    Clear all
                                </button>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mb-4">
                            <div 
                                class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                                :style="{ width: `${completionPercentage}%` }"
                            ></div>
                        </div>
                        
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <div 
                                v-for="(option, groupId) in selectedOptions" 
                                :key="groupId" 
                                class="flex justify-between items-center py-1"
                            >
                                <div>
                                    <span class="text-sm text-gray-600">{{ getGroupName(groupId) }}:</span>
                                    <span class="font-medium ml-2">{{ option.name }}</span>
                                </div>
                                <span class="text-gray-700">{{ formatCurrency(option.price) }}</span>
                            </div>
                        </div>
                        
                        <div class="border-t pt-3 mt-3">
                            <div class="flex justify-between items-center font-bold text-lg">
                                <span>Total:</span>
                                <span class="text-blue-600">{{ formattedTotal }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Option Groups -->
                    <div 
                        v-for="group in groups" 
                        :key="group.id" 
                        class="mb-8"
                    >
                        <h2 class="text-xl font-bold mb-3 flex items-center gap-2">
                            {{ group.name }}
                            <span 
                                v-if="selectedOptions[group.id]"
                                class="text-sm bg-green-100 text-green-700 px-2 py-0.5 rounded-full"
                            >
                                Selected
                            </span>
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <button
                                v-for="option in group.options"
                                :key="option.id"
                                @click="selectOption(option.id, group.id, option)"
                                :disabled="isLoading"
                                :class="[
                                    'border rounded-lg px-4 py-3 text-left transition-all duration-200',
                                    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                    isSelected(group.id, option.id) 
                                        ? 'bg-blue-600 text-white border-blue-600 shadow-md transform scale-[1.02]' 
                                        : 'hover:bg-gray-50 border-gray-300 hover:shadow-sm',
                                    isLoading && 'opacity-50 cursor-not-allowed'
                                ]"
                            >
                                <div class="font-medium">{{ option.name }}</div>
                                <div :class="isSelected(group.id, option.id) ? 'text-blue-100' : 'text-gray-600'">
                                    {{ formatCurrency(option.price_value) }}
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Order Button -->
                    <div class="mt-8 pt-6 border-t sticky bottom-4 bg-white bg-opacity-95 backdrop-blur-sm rounded-lg p-4 shadow-lg">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm text-gray-600">
                                    {{ selectedCount }} of {{ totalGroupsCount }} groups selected
                                </div>
                                <div v-if="hasSelections" class="text-2xl font-bold text-blue-600">
                                    {{ formattedTotal }}
                                </div>
                            </div>
                            <button 
                                @click="submitOrder" 
                                :disabled="!hasSelections || isLoading"
                                class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 shadow-md"
                            >
                                <span v-if="isLoading">Processing...</span>
                                <span v-else>Create Order →</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #CBD5E0 #EDF2F7;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #EDF2F7;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #CBD5E0;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #A0AEC0;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>