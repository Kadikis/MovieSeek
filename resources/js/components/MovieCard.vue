<script setup lang="ts">
import type { PropType } from 'vue';
import { defineProps, ref } from 'vue';

interface Movie {
    title: string;
    year: string;
    imdb_id: string;
    type: string;
    poster: string;
}
defineProps({
    searchId: {
        type: Number,
        required: true,
    },
    movie: {
        type: Object as PropType<Movie>,
        required: true,
        default: () => ({
            title: '',
            year: '',
            imdb_id: '',
            type: '',
            poster: '',
        }),
    },
});

const showFallback = ref(false);
</script>

<template>
    <a :href="`/movie/${movie.imdb_id}?search_id=${searchId}`" class="block">
        <div
            class="group overflow-hidden rounded-lg border border-[#e3e3e0] bg-white shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition hover:shadow-[0_4px_16px_rgba(0,0,0,0.08)] dark:border-[#3E3E3A] dark:bg-[#161615]"
        >
            <div class="relative aspect-[2/3] w-full bg-[#fff2f2] dark:bg-[#1D0002]">
                <img
                    v-if="movie.poster && movie.poster !== 'N/A' && !showFallback"
                    :src="movie.poster"
                    :alt="movie.title"
                    class="h-full w-full object-cover"
                    @error="showFallback = true"
                />
                <div
                    v-if="showFallback || !movie.poster || movie.poster === 'N/A'"
                    class="absolute inset-0 flex items-center justify-center text-4xl"
                >
                    🎬
                </div>
            </div>
            <div class="space-y-1 p-3">
                <div class="flex items-center justify-between">
                    <p class="truncate text-sm font-medium">{{ movie.title }}</p>
                    <span class="rounded-sm bg-[#F8B803] px-1.5 py-0.5 text-xs text-black">{{ movie.year }}</span>
                </div>
                <p class="truncate text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ movie.type }} • {{ movie.imdb_id }}</p>
            </div>
        </div>
    </a>
</template>
