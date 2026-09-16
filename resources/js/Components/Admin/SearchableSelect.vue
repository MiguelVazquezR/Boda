<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { MagnifyingGlassIcon, ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: [String, Number], default: null },
    options: { type: Array, default: () => [] }, // [{ value, label }]
    placeholder: { type: String, default: 'Seleccionar...' },
    emptyMessage: { type: String, default: 'Sin resultados' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const highlight = ref(0);
const rootRef = ref(null);
const inputRef = ref(null);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter((o) => String(o.label).toLowerCase().includes(q));
});

const selected = computed(() => props.options.find((o) => o.value === props.modelValue));

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        query.value = '';
        highlight.value = 0;
        nextTick(() => inputRef.value?.focus());
    }
}

function openList() {
    if (props.disabled) return;
    open.value = true;
    query.value = '';
    highlight.value = 0;
    nextTick(() => inputRef.value?.focus());
}

function select(option) {
    emit('update:modelValue', option.value);
    open.value = false;
}

function onKeydown(e) {
    if (e.key === 'Escape') {
        open.value = false;
        return;
    }
    if (!open.value && (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ')) {
        e.preventDefault();
        openList();
        return;
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlight.value = Math.min(highlight.value + 1, filtered.value.length - 1);
    }
    if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlight.value = Math.max(highlight.value - 1, 0);
    }
    if (e.key === 'Enter' && open.value) {
        e.preventDefault();
        const option = filtered.value[highlight.value];
        if (option) select(option);
    }
    if (e.key === 'Tab') open.value = false;
}

function onOutsideClick(e) {
    if (rootRef.value && !rootRef.value.contains(e.target)) open.value = false;
}

onMounted(() => document.addEventListener('click', onOutsideClick));
onBeforeUnmount(() => document.removeEventListener('click', onOutsideClick));
</script>

<template>
    <div ref="rootRef" class="relative">
        <!-- Botón que muestra el valor seleccionado -->
        <button
            type="button"
            :disabled="disabled"
            @click="toggle"
            class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl border bg-white text-sm text-left transition-colors focus:outline-none focus:ring-2"
            :class="[
                open ? 'border-primary ring-primary/20' : 'border-tinta/20 hover:border-tinta/30',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
            ]"
        >
            <span class="truncate" :class="selected ? 'text-tinta' : 'text-tinta/30'">
                {{ selected ? selected.label : placeholder }}
            </span>
            <ChevronDownIcon class="w-4 h-4 text-tinta/40 flex-shrink-0 transition-transform" :class="{ 'rotate-180': open }" />
        </button>

        <!-- Dropdown -->
        <div
            v-if="open"
            class="absolute z-30 mt-1 w-full bg-white rounded-xl border border-tinta/15 shadow-lg overflow-hidden"
        >
            <!-- Buscador -->
            <div class="relative border-b border-tinta/10">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-tinta/30" />
                <input
                    ref="inputRef"
                    v-model="query"
                    type="text"
                    placeholder="Buscar..."
                    class="w-full pl-9 pr-3 py-2.5 text-sm text-tinta placeholder-tinta/30 bg-niebla/30 focus:outline-none"
                    @keydown="onKeydown"
                    @focus="openList"
                />
            </div>

            <!-- Lista -->
            <ul class="max-h-56 overflow-y-auto py-1">
                <li
                    v-for="(option, index) in filtered"
                    :key="option.value"
                    @click="select(option)"
                    @mouseenter="highlight = index"
                    class="px-3.5 py-2 text-sm cursor-pointer flex items-center justify-between gap-2 transition-colors"
                    :class="index === highlight ? 'bg-primary/10 text-tinta' : 'text-tinta/70 hover:bg-niebla'"
                >
                    <span class="truncate">{{ option.label }}</span>
                    <CheckIcon v-if="option.value === modelValue" class="w-4 h-4 text-primary flex-shrink-0" />
                </li>
                <li v-if="filtered.length === 0" class="px-3.5 py-3 text-sm text-tinta/40 italic text-center">
                    {{ emptyMessage }}
                </li>
            </ul>
        </div>
    </div>
</template>
