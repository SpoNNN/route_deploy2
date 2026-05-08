<template>

  <Header2
    :isLoggedIn="isAuthenticated"

    @navigateToWay="
      () => {
        currentPage = 'way'
        selectedRouteId = null
        isPathObjectOpened = false
      }
    "
    @navigateToFavorites="currentPage = 'favorites'"
    @navigateToRegister="currentPage = 'register'"
    @navigateToLogin="currentPage = 'signIn'"
    @logout="handleSignOut"
  />

  <div v-if="isLoading" class="loading-overlay">
    <div class="loading-spinner"></div>
    <p>Загрузка данных...</p>
  </div>

  <div v-else-if="error" class="error-message">
    {{ error }}
    <button @click="fetchRoutes">Повторить попытку</button>
  </div>

  <div v-else class="app-container">
    <div class="sidebar">
      <way
        v-if="currentPage === 'way'"
        ref="wayRef"
        :routes="Object.values(routesData)"
        @navigate="handleNavigate"
        @selectRoute="handleSelectRoute"
      />

      <RouteDetails 
        v-if="currentPage === 'RouteDetails.vue' && selectedRouteId !== null" 
        :route-id="selectedRouteId"  
        :isAdmin="isAdmin"
        @select-object="(point) => updateClick(point)"
        @navigate="(point) => {
          isPathObjectOpened = true
          currentPointData = point
          currentPage = 'Details Opened'
        }"
        @create-object="handleCreateObject"
        @back="() => {
          currentPage = 'way'
          selectedRouteId = null
        }"
      />

      <register 
        v-if="currentPage === 'register'" 
        @registered="handleRegistered"
        @switchToSignIn="currentPage = 'signIn'"
      />
      
      <signIn 
        v-if="currentPage === 'signIn'" 
        @loggedIn="handleLoggedIn"
        @switchToRegister="currentPage = 'register'"
      />

      <favoritesPage
        v-if="currentPage === 'favorites'"
        :routes="Object.values(routesData)"
        @navigate="handleNavigate"
        @selectRoute="handleSelectRoute"
      />

      <ReviewsComponent 
        v-if="currentPage === 'RouteDetails.vue' && selectedRouteId !== null" 
        :route-id="selectedRouteId"
        :is-logged-in="isAuthenticated"   
      />

      <selectedObject
        v-if="isPathObjectOpened && currentPointData"
        :point-data="currentPointData"
        @navigateBack="handleNavigateBack"
      />
    </div>

    <div class="map-container">
      <!-- Карта с маршрутом и точками -->
      <routeAll 
        v-if="selectedRouteId !== null"
        :route-id="selectedRouteId"
        :points="currentRoutePoints"
        :route-color="currentRouteData.mapColor || '#FFB800'"
        ref="mapRef"
        @navigate="(point) => {
          isPathObjectOpened = true
          currentPointData = point
          currentPage = 'Details Opened'
        }"
      />
      
      <!-- Карта по умолчанию (без маршрута) -->
      <routeAll 
        v-else
        :points="[]"
      />
    </div>
  </div>
   
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import axios from 'axios'

import Header2 from './components/Header2.vue'
import selectedObject from './components/selectedObject.vue'
import way from './components/way.vue'
import routeAll from './components/routeAll.vue'
import register from './views/register.vue'
import signIn from './views/signIn.vue'
import RouteDetails from './components/RouteDetails.vue'
import favoritesPage from './components/favoritesPage.vue'
import ReviewsComponent from './components/ReviewsComponent.vue'
import { useAuthCheck } from './utils/useAuthCheck'

// Используем composable правильно
const { 
  isAuthenticated, 
  checkToken, 
  logout,
  checkAuth 
} = useAuthCheck()
const API_BASE_URL = '/api'
const routesData = ref({})
const currentPage = ref('way')
const isLoading = ref(true)
const error = ref(null)
const currentPointData = ref(null)
const isPathObjectOpened = ref(false)
const mapRef = ref(null)
const wayRef = ref(null)
const selectedRouteId = ref(null)

const fetchRoutes = async () => {
  try {
    isLoading.value = true
    const response = await axios.get(`${API_BASE_URL}/routes`)
    
    const transformedData = {}
    response.data.data.forEach((route) => {
      const routeKey = `route${route.id}`
      
      const infoItems = Array.isArray(route.route_infos) 
        ? route.route_infos.reduce((acc, info) => {
            acc[info.key] = {
              id: info.id,
              key: info.key,
              label: info.label,
              value: info.value
            }
            return acc
          }, {}) 
        : {}

      let points = []
      if (Array.isArray(route.points)) {
        points = route.points.map((point) => ({
          id: point.id,
          lon: parseFloat(point.lon),
          lat: parseFloat(point.lat),
          name: point.name,
          address: point.address,
          url: point.url,
          pointName: point.point_name || '',
          description: point.description || '',
          images: point.images ? (typeof point.images === 'string' ? JSON.parse(point.images) : point.images) : []
        }))
      } else if (route.point && Array.isArray(route.point)) {
        points = route.point.map((point) => ({
          id: point.id,
          lon: parseFloat(point.lon),
          lat: parseFloat(point.lat),
          name: point.name,
          address: point.address,
          url: point.url,
          pointName: point.point_name || '',
          description: point.description || '',
          images: point.images ? (typeof point.images === 'string' ? JSON.parse(point.images) : point.images) : []
        }))
      }
      
      transformedData[routeKey] = {
        id: route.id,
        title: route.title,
        mapColor: route.map_color,
        description: route.description,
        slug: route.slug,
        infoItems,
        points
      }
    })
    
    routesData.value = transformedData
    error.value = null
  } catch (err) {
    console.error('Ошибка загрузки маршрутов:', err)
    error.value = 'Не удалось загрузить данные маршрутов'
  } finally {
    isLoading.value = false
  }
}

const handleSelectRoute = (routeId) => {
  console.log('handleSelectRoute вызван, routeId:', routeId)
  selectedRouteId.value = routeId
  currentPage.value = 'RouteDetails.vue'
}

const handleCreateObject = async (object) => {
  console.log('Создан объект:', object)
  await fetchRoutes()
}

const updateClick = (point) => {
  nextTick(() => {
    if (mapRef.value) {
      mapRef.value.openPopupByName(point.name)
    }
  })
}

const handleNavigate = (page) => {
  if (page === 'way') {
    selectedRouteId.value = null
  }
  currentPage.value = page
}

const handleNavigateBack = () => {
  currentPage.value = 'RouteDetails.vue'
  isPathObjectOpened.value = false
}

const handleSignOut = async () => {
  try {
    await axios.post('/api/logout')
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    logout() // Используем logout из useAuthCheck
    currentPage.value = 'way'
  }
}

const handleLoggedIn = async (user) => {
  // После успешного входа проверяем авторизацию заново
  await checkAuth()
  currentPage.value = 'way'
}

const handleRegistered = async (user) => {
  // После успешной регистрации проверяем авторизацию заново
  await checkAuth()
  currentPage.value = 'way'
}

const currentRouteData = computed(() => {
  if (selectedRouteId.value === null) {
    return {
      id: 0,
      title: '',
      description: '',
      points: [],
      infoItems: {},
      mapColor: '',
      slug: ''
    }
  }
  
  const routeKey = `route${selectedRouteId.value}`
  return routesData.value[routeKey] || {
    id: 0,
    title: '',
    description: '',
    points: [],
    infoItems: {},
    mapColor: '',
    slug: ''
  }
})

const currentRoutePoints = computed(() => {
  return currentRouteData.value?.points || []
})

onMounted(async () => {
  await fetchRoutes()
  
  // Проверяем токен и загружаем данные пользователя
  const hasToken = checkToken()
  
  if (hasToken) {
    // Проверяем валидность токена и загружаем данные пользователя
    await checkAuth()
  }
})
</script>

<style scoped>
.app-container {
  display: flex;
  min-height: calc(100vh - 60px);
}

.sidebar {
  width: 500px;
  background-color: #3730a3;
  padding: 20px;
  overflow-y: auto;
}

.map-container {
  flex-grow: 1;
  background-color: white;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-spinner {
  border: 5px solid #f3f3f3;
  border-top: 5px solid #3498db;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  padding: 20px;
  background: #ffebee;
  color: #c62828;
  text-align: center;
  margin: 20px;
  border-radius: 4px;
}

.error-message button {
  margin-top: 10px;
  padding: 8px 16px;
  background: #c62828;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>