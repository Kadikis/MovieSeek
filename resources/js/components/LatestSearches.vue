<script setup lang="ts">
import type { PropType } from 'vue';
import { defineProps } from 'vue';

interface Search {
    id: number;
    query: string;
    movies_count: number;
}
defineProps({
    latestSearches: {
        type: Array as PropType<Search[]>,
        required: true,
        default: () => [],
    },
});

const emit = defineEmits(['search']);
</script>

<template>
    <div v-if="latestSearches && latestSearches.length > 0" class="w-full max-w-2xl">
        <div class="flex flex-wrap gap-2">
            <button
                v-for="search in latestSearches"
                :key="search.id"
                @click="emit('search', search)"
                class="group relative rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-[12px] font-medium text-[#706f6c] transition-all hover:border-[#f53003] hover:bg-[#f53003]/5 hover:text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A] dark:hover:border-[#FF4433] dark:hover:bg-[#FF4433]/5 dark:hover:text-[#FF4433]"
            >
                {{ search.query }}
                <span class="ml-1 text-[10px] opacity-60">{{ search.movies_count }} results</span>
            </button>
        </div>
    </div>
</template>
