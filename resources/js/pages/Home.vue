<script setup lang="ts">
import MovieCard from '@/components/MovieCard.vue';
</script>

<template>
    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <section class="px-6 py-12 lg:px-10 lg:py-20">
            <div class="mx-auto max-w-5xl">
                <div
                    class="rounded-xl bg-white p-8 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]"
                >
                    <div class="flex flex-col items-center gap-6 text-center">
                        <h1 class="text-3xl leading-tight font-semibold tracking-tight sm:text-4xl lg:text-5xl">MovieSeek</h1>
                        <p class="max-w-2xl text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                            Looking for a movie? We've got you covered.
                        </p>

                        <form class="flex w-full max-w-2xl items-center gap-2">
                            <div class="relative flex-1">
                                <input
                                    type="text"
                                    placeholder="Search for a movie title"
                                    class="w-full rounded-md border border-[#e3e3e0] bg-white px-4 py-3 text-[14px] outline-none placeholder:text-[#9b9a96] focus:border-[#f53003] focus:ring-2 focus:ring-[#f53003]/10 dark:border-[#3E3E3A] dark:bg-[#161615] dark:placeholder:text-[#777672] dark:focus:border-[#FF4433] dark:focus:ring-[#FF4433]/15"
                                />
                                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-[#9b9a96] dark:text-[#777672]"
                                    >⌘K</span
                                >
                            </div>
                            <button
                                type="button"
                                class="rounded-md border border-black bg-[#1b1b18] px-4 py-3 text-[14px] font-medium text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                            >
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-6 pb-16 lg:px-10">
            <div class="mx-auto max-w-6xl">
                <h2 class="mb-4 text-lg font-medium">Latest Searches</h2>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <MovieCard v-for="i in 10" :key="i" />
                </div>
            </div>
        </section>
    </div>
</template>
