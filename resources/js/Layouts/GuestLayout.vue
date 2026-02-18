<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-slate-950 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-blue-500 drop-shadow-[0_0_15px_rgba(59,130,246,0.5)]" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-slate-900/50 backdrop-blur-md border border-slate-800 px-8 py-8 shadow-2xl sm:max-w-md sm:rounded-[2.5rem]"
        >
            <slot />
        </div>
    </div>
</template>
