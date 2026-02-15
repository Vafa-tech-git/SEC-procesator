<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-300 shadow-sm transition duration-150 ease-in-out hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-slate-900 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
