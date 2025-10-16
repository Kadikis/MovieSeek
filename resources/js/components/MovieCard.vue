<template>
    <div
        class="group overflow-hidden rounded-lg border border-[#e3e3e0] bg-white shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition hover:shadow-[0_4px_16px_rgba(0,0,0,0.08)] dark:border-[#3E3E3A] dark:bg-[#161615]"
    >
        <div class="relative aspect-[2/3] w-full bg-[#fff2f2] dark:bg-[#1D0002]">
            <div class="absolute inset-0 flex items-center justify-center text-4xl">🎬</div>
        </div>
        <div class="space-y-1 p-3">
            <div class="flex items-center justify-between">
                <p class="truncate text-sm font-medium">Movie Title</p>
                <span class="rounded-sm bg-[#F8B803] px-1.5 py-0.5 text-xs text-black">Rating</span>
            </div>
            <p class="truncate text-xs text-[#706f6c] dark:text-[#A1A09A]">Genre • 2h 2m</p>
        </div>
    </div>
</template>
