<script setup>
import { defineProps, defineEmits, ref, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  title: String,
  isLoggedIn: { type: Boolean, default: false },
  routeId: { type: Number, required: true }
})

const emit = defineEmits(['click', 'toggle-favorite'])

const isFavoriteLocal = ref(false)
const isLoading = ref(false)

// Проверяем, есть ли маршрут в избранном при монтировании
const loadFavoriteStatus = async () => {
  if (!props.isLoggedIn) return

  try {
    const res = await axios.get('/api/favorites')
    // res.data.data — твои объекты Favorite с полем route_id
    isFavoriteLocal.value = res.data.data.some(fav => fav.route_id === props.routeId)
  } catch (error) {
    console.error('Ошибка загрузки избранного:', error)
  }
}
watch(
  () => props.isLoggedIn,
  (newVal) => {
    if (!newVal) {
      isFavoriteLocal.value = false // сброс сердца
    } else {
      loadFavoriteStatus() // при входе заново проверяем избранное
    }
  }
)

onMounted(loadFavoriteStatus)

const toggleFavorite = async (event) => {
  event.stopPropagation()
  if (!props.isLoggedIn) {
    alert('Для добавления в избранное необходимо войти в аккаунт')
    return
  }
  if (!props.routeId || isLoading.value) return

  try {
    isLoading.value = true

    if (!isFavoriteLocal.value) {
      // Добавляем в избранное
      await axios.post('/api/favorites', { route_id: props.routeId })
      isFavoriteLocal.value = true
      console.log(`Добавлено в избранное: ${props.routeId}`)
    } else {
      // Удаляем из избранного
      await axios.delete(`/api/favorites/${props.routeId}`)
      isFavoriteLocal.value = false
      console.log(`Удалено из избранного: ${props.routeId}`)
    }

    emit('toggle-favorite', props.routeId, isFavoriteLocal.value)

  } catch (error) {
    console.error('Ошибка избранного:', error)
   
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="pb-1">
    <button
      class="relative w-full py-2 pr-8 border-2 border-green-500 text-green-500 rounded-md hover:bg-green-500 hover:text-white transition overflow-visible"
      @click="$emit('click')"
      :disabled="isLoading"
    >
      <div class="text-white">{{ title }}</div>

      <div 
        v-if="props.isLoggedIn"
        class="absolute top-1 right-1 cursor-pointer"
        @click.stop="toggleFavorite"
      >
        <div v-if="isLoading" class="w-5 h-5 flex items-center justify-center">
          <div class="w-4 h-4 border-2 border-green-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <img 
          v-else
          :src="isFavoriteLocal ? '/heart_fill_red.svg' : '/heart_empty.svg'"
          :alt="isFavoriteLocal ? 'В избранном' : 'Добавить в избранное'"
          class="w-5 h-5 transition-transform hover:scale-110"
        />
      </div>
    </button>
  </div>
</template>
