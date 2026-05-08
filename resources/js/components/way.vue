<script setup lang="ts">
import { ref, computed, onMounted, defineProps, defineEmits } from 'vue'
import axios from 'axios'
import { useAuthCheck } from '../utils/useAuthCheck'
import SearchInput from './SearchInput.vue'
import buttons from './buttons.vue'
import filterPop from './filterPop.vue'

interface Route {
  id: number
  title: string
  slug: string | null
  distance: string | null
  participants: number | null
  audience: string | null
  duration: string,
  description: String,
  isFavorite: boolean
}

interface Filters {
  minDistance: number | null
  maxParticipants: number | null
  selectedAudience: string
  minDuration: number
  maxDuration: number
}


const props = defineProps<{
  isLoggedIn?: boolean
}>()

const emit = defineEmits<{
  selectRoute: [id: number]
  navigate: [page: string]
}>()


const { isAuthenticated, checkAuth } = useAuthCheck()

const routes = ref<Route[]>([])
const searchQuery = ref('')
const showFilterPopup = ref(false)
const favoritesLoaded = ref(false)
const isLoading = ref(false)
const error = ref<string | null>(null)
const selectedRouteId = ref<number | null>(null)

const filters = ref({
  minDistance: null as number | null,
  maxParticipants: null as number | null,
  selectedAudience: '',
  minDuration: 0,
  maxDuration: 300
})

const userIsLoggedIn = computed(() => {
  console.log('userIsLoggedIn computed - isAuthenticated:', isAuthenticated.value)
  return isAuthenticated.value
})

const hasToken = computed(() => {
  if (typeof window === 'undefined') return false

  const token = localStorage.getItem('auth_token')
  console.log('hasToken computed - token:', token ? 'ЕСТЬ' : 'НЕТ')
  return !!token
})

const loadRoutes = async () => {
  try {
    isLoading.value = true
    error.value = null

    const response = await axios.get('/api/routes')
    const dbRoutes = response.data.data || []

    routes.value = dbRoutes.map((dbRoute: any) => ({
      id: dbRoute.id,
      title: dbRoute.title,
      slug: dbRoute.slug,
      distance: dbRoute.distance || null,
      participants: dbRoute.participants || null,
      audience: dbRoute.audience || null,
      duration: dbRoute.duration || 0,
      description: dbRoute.description || '',
      isFavorite: false
    }))

    await loadFavorites()

  } catch (err) {
    console.error('Failed to load routes:', err)
    error.value = 'Не удалось загрузить маршруты'
  } finally {
    isLoading.value = false
    favoritesLoaded.value = true
  }
}

const loadFavorites = async () => {
  try {
    console.log('loadFavorites - userIsLoggedIn:', userIsLoggedIn.value)

    if (!userIsLoggedIn.value) {
      console.log('Пользователь не авторизован')
      routes.value.forEach(route => {
        route.isFavorite = false
      })
      return
    }

    const response = await axios.get('/favorites')
    const favorites = response.data.data || []
    console.log('Loaded favorites from Laravel:', favorites.length)

    routes.value.forEach(route => {
      route.isFavorite = favorites.some((favorite: any) =>
        favorite.route_id === route.id || favorite.route?.id === route.id
      )
    })

  } catch (err) {
    console.error('Failed to load favorites:', err)
    routes.value.forEach(route => {
      route.isFavorite = false
    })
  }
}

const toggleFavorite = async (route: Route) => {
  try {
    if (!userIsLoggedIn.value) {
      alert('Для добавления в избранное необходимо войти в аккаунт')
      return
    }


    const wasFavorite = route.isFavorite
    route.isFavorite = !wasFavorite

    if (route.isFavorite) {

      await axios.post('/favorites', {
        route_id: route.id
      })
      console.log(`Маршрут ${route.id} добавлен в избранное`)
    } else {

      await axios.delete(`/favorites/${route.id}`)
      console.log(`Маршрут ${route.id} удален из избранного`)
    }

  } catch (err) {
    console.error('Failed to toggle favorite:', err)

    route.isFavorite = !route.isFavorite

    if (axios.isAxiosError(err)) {
      if (err.response?.status === 401) {
        alert('Сессия истекла. Пожалуйста, войдите снова.')
      } else {

      }
    }
  }
}

const applyFilters = (filteredRoutes: Route[]) => {
  routes.value = filteredRoutes
  showFilterPopup.value = false
}

const resetFilters = () => {
  searchQuery.value = ''
  filters.value = {
    minDistance: null,
    maxParticipants: null,
    selectedAudience: '',
    minDuration: 0,
    maxDuration: 300
  }
  localStorage.removeItem('filters')
  loadRoutes()
}

const filteredRoutes = computed(() => {
  return routes.value.filter(route => {
    const matchesSearch = route.title.toLowerCase()
      .includes(searchQuery.value.toLowerCase())

    const matchesDistance = filters.value.minDistance === null ||
      (route.distance !== null && route.distance <= filters.value.minDistance)

    const matchesParticipants = filters.value.maxParticipants === null ||
      (route.participants !== null && route.participants <= filters.value.maxParticipants)

    const matchesAudience = filters.value.selectedAudience === '' ||
      (route.audience && route.audience === filters.value.selectedAudience)

    const matchesDuration = route.duration >= filters.value.minDuration &&
      route.duration <= filters.value.maxDuration

    return matchesSearch && matchesDistance &&
      matchesParticipants && matchesAudience &&
      matchesDuration
  })
})
const modalVisible = ref(false)
const isEdit = ref(false)

const form = ref({
  id: null,
  title: '',
  map_color: '',
  description: ''
})

const openCreateModal = () => {
  isEdit.value = false
  form.value = {
    id: null,
    title: '',
    map_color: '',
    description: ''
  }
  modalVisible.value = true
}

const openEditModal = (route: any) => {
  isEdit.value = true
  form.value = {
    id: route.id,
    title: route.title,
    map_color: route.map_color || '',
    description: route.description || ''
  }
  modalVisible.value = true
}

const saveRoute = async () => {
  if (isEdit.value) {
    await axios.put(`/api/routes/${form.value.id}`, form.value)
  } else {
    await axios.post(`/api/routes`, form.value)
  }

  modalVisible.value = false
  await loadRoutes()
}

const deleteRoute = async (id: number) => {
  if (!confirm('Удалить маршрут?')) return
  await axios.delete(`/api/routes/${id}`)
  await loadRoutes()
}
onMounted(async () => {
  console.log('way.vue mounted')

  await checkAuth()
  console.log('checkAuth result:', isAuthenticated.value)
  console.log('localStorage token:', !!localStorage.getItem('auth_token'))

  const savedFilters = JSON.parse(localStorage.getItem('filters') || '{}')
  if (savedFilters) {
    filters.value = { ...filters.value, ...savedFilters }
  }


  const token = localStorage.getItem('auth_token')
  if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }

  loadRoutes()
})
</script>

<template>
  
  <div class="text-center mb-6">
    <span class="text-white text-3xl text-center">Научно-образовательные маршруты по Уфе</span>
  </div>

  <SearchInput v-model="searchQuery" placeholderText="найти маршрут" :showFilter="true" :points="[]"
    @click-filter="showFilterPopup = true" />

  <div v-if="isLoading" class="text-white text-center">Загрузка маршрутов...</div>

  <div v-else-if="error" class="text-red-500 text-center">
    {{ error }}
    <button @click="loadRoutes" class="text-white">Повторить</button>
  </div>

  <div v-else-if="favoritesLoaded" class="space-y-3 mt-4" style="width: 420px">


    <buttons v-for="route in filteredRoutes" :key="route.id" :title="route.title" :route-id="route.id"
      :is-favorite="route.isFavorite" :is-logged-in="userIsLoggedIn" @toggle-favorite="() => toggleFavorite(route)"
      @click="
        () => {
          selectedRouteId = route.id
          emit('selectRoute', Number(route.id))
          console.log('Мы перешли в роут с айди', route.id)
        }
      " />

  </div>

  <div class="mt-4 flex justify-center">
    <button @click="resetFilters" class="bg-red-500 text-white px-4 py-2 mt-4 rounded">
      <div class="text-white">Сбросить фильтры</div>
    </button>
  </div>

  <filterPop v-if="showFilterPopup" @filtered="applyFilters" @close="showFilterPopup = false" />
</template>