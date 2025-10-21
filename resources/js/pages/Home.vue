<script setup lang="ts">
import LatestSearches from '@/components/LatestSearches.vue';
import MovieCard from '@/components/MovieCard.vue';
import type { PropType } from 'vue';
import { computed, defineProps, onMounted, onUnmounted, ref } from 'vue';

interface Result {
    id: number;
    query: string;
    movies: Movie[];
    total_results: number;
    total_pages: number;
    no_results: boolean;
    pages_loaded: number;
}

interface Movie {
    id: number;
    title: string;
    year: string;
    imdb_id: string;
    type: string;
    poster: string;
}

interface Search {
    id: number;
    query: string;
    movies_count: number;
}

const props = defineProps({
    errors: Object as PropType<{ query: string; search_id: string }>,
    query: {
        type: String,
        default: '',
    },
    latestSearches: {
        type: Array as PropType<Search[]>,
        required: true,
        default: () => [],
    },
    searchResult: {
        type: Object as PropType<Result>,
        required: true,
        default: () => ({
            id: 0,
            query: '',
            movies: [],
            total_results: 0,
            total_pages: 0,
            no_results: false,
            pages_loaded: 0,
        }),
    },
});
const search = ref<Search>({
    id: 0,
    query: '',
    movies_count: 0,
});

const handleKeyDown = (event: KeyboardEvent) => {
    // Check for Cmd+K (Mac) or Ctrl+K (Windows/Linux)
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        searchInput.value?.focus();
    }
};

onMounted(() => {
    search.value.query = props.query;

    document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown);
});

const searchForm = ref<HTMLFormElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const isLoadingMore = ref(false);

const canLoadMore = computed(() => {
    return props.searchResult?.total_pages > props.searchResult?.pages_loaded;
});

const handleSearch = async (s: Search) => {
    await new Promise<void>((resolve) => {
        search.value = s;
        resolve();
    }).then(() => {
        searchForm.value?.submit();
    });
};

const handleLoadMore = async () => {
    if (!props.searchResult?.id || isLoadingMore.value || !canLoadMore.value) return;

    isLoadingMore.value = true;

    try {
        const params = new URLSearchParams();
        params.append('search_id', props.searchResult.id.toString());
        params.append('load_more', '1');

        const response = await fetch(`/?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error loading more movies:', error);
    } finally {
        isLoadingMore.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <section class="px-6 py-12 lg:px-10 lg:py-20">
            <div class="mx-auto max-w-5xl">
                <div
                    class="rounded-xl bg-white p-8 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]"
                >
                    <div class="flex flex-col items-center gap-6 text-center">
                        <a href="/">
                            <h1 class="text-3xl leading-tight font-semibold tracking-tight sm:text-4xl lg:text-5xl">MovieSeek</h1>
                        </a>
                        <p class="max-w-2xl text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                            Looking for a movie? We've got you covered.
                        </p>

                        <form class="flex w-full max-w-2xl items-center gap-2" action="/" ref="searchForm">
                            <input v-if="search.id" type="hidden" name="search_id" :value="search.id" />
                            <input v-if="search.query" type="hidden" name="query" :value="search.query" />
                            <div class="relative flex-1">
                                <input
                                    ref="searchInput"
                                    v-model="search.query"
                                    type="text"
                                    placeholder="Search for a movie title"
                                    class="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-3 text-[14px] outline-none placeholder:text-[#9b9a96] focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/10 dark:border-[#3E3E3A] dark:bg-[#161615] dark:placeholder:text-[#777672] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]/15"
                                />
                                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-[#9b9a96] dark:text-[#777672]"
                                    >⌘K</span
                                >
                                <div v-if="errors?.query" class="text-sm text-red-500">{{ errors.query }}</div>
                            </div>
                            <button
                                type="submit"
                                class="rounded-md border border-black bg-[#1b1b18] px-4 py-3 text-[14px] font-medium text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                            >
                                Search
                            </button>
                        </form>
                        <LatestSearches :latest-searches="latestSearches" @search="handleSearch" />
                    </div>
                </div>
            </div>
        </section>

        <section class="px-6 pb-16 lg:px-10">
            <div class="mx-auto max-w-6xl">
                <div v-if="searchResult?.movies?.length > 0">
                    <h2 class="mb-3 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Movies Found</h2>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        <MovieCard v-for="movie in searchResult?.movies" :key="movie.id" :movie="movie" :search-id="searchResult.id" />
                    </div>

                    <div v-if="canLoadMore" class="mt-8 flex justify-center">
                        <button
                            @click="handleLoadMore"
                            :disabled="isLoadingMore"
                            class="rounded-md border border-[#e3e3e0] bg-white px-6 py-3 text-sm font-medium text-[#1b1b18] hover:bg-[#f8f8f7] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] dark:hover:bg-[#1a1a18]"
                        >
                            <span v-if="isLoadingMore">Loading...</span>
                            <span v-else>Load More Movies</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
