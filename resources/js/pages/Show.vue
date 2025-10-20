<script setup lang="ts">
import type { PropType } from 'vue';
import { defineProps, ref } from 'vue';

interface Movie {
    title: string;
    year: string;
    rated: string;
    released: string;
    runtime: string;
    genre: string;
    director: string;
    writer: string;
    actors: string;
    plot: string;
    language: string;
    country: string;
    poster: string;
    imdb_rating: string;
    imdb_votes: string;
    imdb_id: string;
    type: string;
}

defineProps({
    searchId: {
        type: Number,
    },
    movie: {
        type: Object as PropType<Movie>,
        required: true,
        default: () => ({
            title: '',
            year: '',
            rated: '',
            released: '',
            runtime: '',
            genre: '',
            director: '',
            writer: '',
            actors: '',
            plot: '',
            language: '',
            country: '',
            poster: '',
            imdb_rating: '',
            imdb_votes: '',
            imdb_id: '',
            type: '',
        }),
    },
});

const showFallback = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <section class="px-6 py-8 lg:px-10 lg:py-12">
            <div class="mx-auto max-w-6xl">
                <div class="mb-6">
                    <a :href="`/${searchId ? `?search_id=${searchId}` : ''}`" class="text-2xl font-semibold tracking-tight">← MovieSeek</a>
                </div>
            </div>
        </section>

        <section class="px-6 pb-16 lg:px-10">
            <div class="mx-auto max-w-6xl">
                <div
                    class="rounded-xl bg-white p-8 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]"
                >
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <div class="lg:col-span-1">
                            <div class="relative mx-auto aspect-[2/3] w-full max-w-sm overflow-hidden rounded-lg bg-[#fff2f2] dark:bg-[#1D0002]">
                                <img
                                    v-if="movie.poster && movie.poster !== 'N/A' && !showFallback"
                                    :src="movie.poster"
                                    :alt="movie.title"
                                    class="h-full w-full object-cover"
                                    @error="showFallback = true"
                                />
                                <div
                                    v-if="showFallback || !movie.poster || movie.poster === 'N/A'"
                                    class="absolute inset-0 flex items-center justify-center text-6xl"
                                >
                                    🎬
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <div class="space-y-6">
                                <div>
                                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ movie.title }}</h1>
                                    <div class="mt-2 flex items-center gap-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        <span>{{ movie.year }}</span>
                                        <span v-if="movie.rated && movie.rated !== 'N/A'">• {{ movie.rated }}</span>
                                        <span v-if="movie.runtime && movie.runtime !== 'N/A'">• {{ movie.runtime }}</span>
                                        <span v-if="movie.type && movie.type !== 'N/A'">• {{ movie.type }}</span>
                                    </div>
                                </div>

                                <div v-if="movie.imdb_rating && movie.imdb_rating !== 'N/A'" class="flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        <span class="text-yellow-500">⭐</span>
                                        <span class="font-semibold">{{ movie.imdb_rating }}</span>
                                        <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">/10</span>
                                    </div>
                                    <span v-if="movie.imdb_votes && movie.imdb_votes !== 'N/A'" class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        ({{ movie.imdb_votes }} votes)
                                    </span>
                                </div>

                                <div v-if="movie.plot && movie.plot !== 'N/A'">
                                    <h2 class="mb-2 text-lg font-semibold">Plot</h2>
                                    <p class="leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">{{ movie.plot }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div v-if="movie.genre && movie.genre !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">Genre</h3>
                                        <p class="text-sm">{{ movie.genre }}</p>
                                    </div>

                                    <div v-if="movie.director && movie.director !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">Director</h3>
                                        <p class="text-sm">{{ movie.director }}</p>
                                    </div>

                                    <div v-if="movie.writer && movie.writer !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">Writer</h3>
                                        <p class="text-sm">{{ movie.writer }}</p>
                                    </div>

                                    <div v-if="movie.actors && movie.actors !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">Actors</h3>
                                        <p class="text-sm">{{ movie.actors }}</p>
                                    </div>

                                    <div v-if="movie.language && movie.language !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">Language</h3>
                                        <p class="text-sm">{{ movie.language }}</p>
                                    </div>

                                    <div v-if="movie.country && movie.country !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">Country</h3>
                                        <p class="text-sm">{{ movie.country }}</p>
                                    </div>

                                    <div v-if="movie.released && movie.released !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">Released</h3>
                                        <p class="text-sm">{{ movie.released }}</p>
                                    </div>

                                    <div v-if="movie.imdb_id && movie.imdb_id !== 'N/A'">
                                        <h3 class="mb-1 text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">IMDb</h3>
                                        <p class="text-sm">
                                            <a
                                                :href="`https://www.imdb.com/title/${movie.imdb_id}/`"
                                                target="_blank"
                                                rel="noopener"
                                                class="underline hover:opacity-80"
                                            >
                                                View on IMDb
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
