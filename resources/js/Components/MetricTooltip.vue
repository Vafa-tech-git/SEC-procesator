<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { computePosition, flip, shift, offset, arrow } from '@floating-ui/dom';

const props = defineProps({
    content: {
        type: String,
        required: true
    },
    title: {
        type: String,
        default: 'Definition'
    }
});

const reference = ref(null);
const floating = ref(null);
const arrowElement = ref(null);
const isVisible = ref(false);

const updatePosition = async () => {
    if (!reference.value || !floating.value) return;

    const { x, y, placement, middlewareData } = await computePosition(reference.value, floating.value, {
        placement: 'top',
        strategy: 'fixed', // Essential for Teleported elements
        middleware: [
            offset(14), 
            flip(), 
            shift({ padding: 10 }),
            arrow({ element: arrowElement.value })
        ],
    });

    Object.assign(floating.value.style, {
        left: `${x}px`,
        top: `${y}px`,
    });

    // Handle Arrow Position
    if (middlewareData.arrow) {
        const { x: arrowX, y: arrowY } = middlewareData.arrow;
        const staticSide = {
            top: 'bottom',
            right: 'left',
            bottom: 'top',
            left: 'right',
        }[placement.split('-')[0]];

        Object.assign(arrowElement.value.style, {
            left: arrowX != null ? `${arrowX}px` : '',
            top: arrowY != null ? `${arrowY}px` : '',
            right: '',
            bottom: '',
            [staticSide]: '-4px',
        });
    }
};

const show = () => {
    isVisible.value = true;
    nextTick(() => {
        updatePosition();
    });
};

const hide = () => {
    isVisible.value = false;
};

// Global listeners for responsive behavior
onMounted(() => {
    window.addEventListener('resize', () => isVisible.value && updatePosition());
    window.addEventListener('scroll', () => isVisible.value && updatePosition(), true);
});

onUnmounted(() => {
    window.removeEventListener('resize', updatePosition);
    window.removeEventListener('scroll', updatePosition, true);
});
</script>

<template>
    <div class="w-full h-full">
        <!-- TRIGGER -->
        <div 
            ref="reference" 
            @mouseenter="show" 
            @mouseleave="hide"
            class="w-full h-full"
        >
            <slot />
        </div>

        <!-- FLOATING TOOLTIP (Teleported to root for maximum visibility) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 translate-y-2"
            >
                <div 
                    v-if="isVisible"
                    ref="floating"
                    class="fixed z-[9999] w-72 bg-slate-900/95 backdrop-blur-2xl border border-slate-700/50 text-slate-200 text-xs p-5 rounded-[2rem] shadow-[0_25px_50px_-12px_rgba(0,0,0,0.7)] pointer-events-none"
                >
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                        <div class="font-black uppercase tracking-widest text-[9px] text-blue-400">{{ title }}</div>
                    </div>
                    <p class="leading-relaxed text-slate-300 font-medium">
                        {{ content }}
                    </p>
                    
                    <!-- ARROW -->
                    <div ref="arrowElement" class="absolute w-2 h-2 bg-slate-900 border-b border-r border-slate-700/50 rotate-45"></div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
