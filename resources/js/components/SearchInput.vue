<script setup lang="ts">
import { computed } from 'vue'

defineOptions({
  name: 'SearchInput',
})

interface Point {
  name: string
  lon: number
  lat: number
  address: string
  description: string
  url: string
  pointName: string
  images: string[]
}

// Определение пропсов
const props = defineProps({
  placeholderText: {
    type: String,
    default: 'найти объект',
  },
  modelValue: String,
  showFilter: {
    type: Boolean,
    default: false,
  },
  points: {
    type: Array as () => Point[],
    required: true,
  },
})

const emit = defineEmits(['update:modelValue', 'click-filter', 'focus', 'blur', 'select-point'])

// Фильтруем точки на основе поискового запроса
const filteredPoints = computed(() => {
  if (!props.modelValue) return []

  const searchQuery = props.modelValue.toLowerCase()
  return props.points.filter(
    (point) =>
      point.name.toLowerCase().includes(searchQuery) ||
      point.address.toLowerCase().includes(searchQuery),
  )
})

// Обработчик выбора точки
function selectPoint(point: Point) {
  emit('select-point', point)
}

// Обработчик ввода с типизацией
function handleInput(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<template>
  <div class="relative" style="width: 420px">
    <input
      :value="modelValue"
      @input="handleInput"
      @focus="$emit('focus')"
      @blur="$emit('blur')"
      type="text"
      :placeholder="placeholderText"
      class="w-full p-3 rounded-lg bg-indigo-900 text-white placeholder-gray-400"
      style="color: white;"
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

    <!-- Выпадающий список с результатами поиска -->
    <div
      v-if="modelValue && filteredPoints.length > 0"
      class="absolute w-full mt-1 bg-indigo-900 rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto"
    >
      <div
        v-for="point in filteredPoints"
        :key="point.name"
        class="p-3 hover:bg-indigo-800 cursor-pointer text-white"
        @click="selectPoint(point)"
      >
        <div class="font-medium">{{ point.name }}</div>
        <div class="text-sm text-gray-400">{{ point.address }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.timeline-text {
  color: white;
  font-size: 13px;
}
</style>
