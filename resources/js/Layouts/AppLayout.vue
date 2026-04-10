<template>
    <div class="min-h-screen bg-gray-50">
        <Header />

        <main>
            <slot />
        </main>

        <footer class="bg-gray-250 border-t border-gray-200 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <p class="text-center text-sm text-gray-500">
                    CAPITAL AGRO TASK
                </p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import Header from '@/Components/Header.vue'
</script>