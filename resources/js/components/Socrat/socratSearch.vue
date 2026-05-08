<script setup>
// Определение пропсов
defineProps({
  placeholderText: {
    type: String,
    default: 'найти объект', // Значение по умолчанию
  },
  modelValue: String, // Добавляем поддержку v-model
  showFilter: {
    type: Boolean,
    default: false, // По умолчанию фильтр скрыт
  },
})

defineEmits(['update:modelValue', 'click-filter']) // Эмитим изменения
</script>

<template>
  <div class="relative" style="width: 420px">
    <input
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      type="text"
      :placeholder="placeholderText"
      class="w-full p-3 rounded-lg bg-indigo-900 text-white placeholder-gray-400"
    />

    <button
      class="absolute top-1/2 transform -translate-y-1/2 text-white"
      :class="showFilter ? 'right-8' : 'right-1'"
    >
      <img src="/Group.svg" alt="lupa" />
    </button>

    <button
      v-if="showFilter"
      class="absolute top-1/2 right-1 transform -translate-y-1/2 text-white"
      @click="$emit('click-filter')"
    >
      <img src="/filter.svg" alt="filter" />
    </button>
  </div>
</template>
