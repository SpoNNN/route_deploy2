<template>
  <div class="profile-page">
    <Header2
      :isLoggedIn="isLoggedIn"
      @navigateToWay="$router.push('/')"
      @navigateToFavorites="$router.push('/favorites')"
      @navigateToRegister="$router.push('/register')"
      @navigateToLogin="$router.push('/signin')"
      @logout="handleSignOut"
    />

    <div class="profile-container">
      <div v-if="isLoading" class="loading">Загрузка...</div>

      <div v-else class="profile-content">
        <div class="profile-main">
          <div class="tabs">
            <button 
              class="tab"
              :class="{ active: activeTab === 'favorites' }"
              @click="activeTab = 'favorites'"
            >
              Избранные<br>маршруты
            </button>
            <button 
              class="tab"
              :class="{ active: activeTab === 'saved' }"
              @click="activeTab = 'saved'"
            >
              Забронированные<br>маршруты
            </button>
            <button 
              class="tab"
              :class="{ active: activeTab === 'reviews' }"
              @click="activeTab = 'reviews'"
            >
              Отзывы
            </button>
            <button 
              class="tab"
              :class="{ active: activeTab === 'history' }"
              @click="activeTab = 'history'"
            >
              История посещений
            </button>
          </div>

          <div class="routes-list">
     
            <div v-if="activeTab === 'saved'">
              <div 
                v-for="booking in savedRoutes" 
                :key="booking.id"
                class="booking-card"
              >
                <div class="booking-header">
                  <div class="booking-badge">
                    Вы записаны на {{ booking.event.date }}
                  </div>
                  <div class="booking-seats">
                    Количество мест: {{ booking.seats }}
                  </div>
                </div>

                <div 
                  class="route-card"
                  :style="{ backgroundColor: colorMap[booking.route.mapColor] || colorMap.default }"
              
                >
                  <div class="route-info">
                    <h3>{{ booking.route.title }}</h3>
                    <p class="booking-time">Время: {{ booking.event.time }}</p>
                    <p class="booking-location">Место встречи: {{ booking.event.location }}</p>
                  </div>
                </div>

                <div class="booking-actions">
                  <button class="btn-secondary" @click.stop>
                    Перейти к маршруту
                  </button>
                  <button class="btn-cancel" @click.stop="cancelBooking(booking.id)">
                    Отменить бронь
                  </button>
                </div>
              </div>
            </div>

      
            <div v-else>
              <div 
                v-for="route in filteredRoutes" 
                :key="route.id"
                class="route-card"
                :class="{ 'non-clickable': activeTab === 'reviews', 'review-card': activeTab === 'reviews' }"
                :style="activeTab !== 'reviews' ? { backgroundColor: route.color } : {}"
              
              >
                <div class="route-info">
                  <h3>{{ route.title }}</h3>
                  <p v-if="activeTab !== 'reviews'">{{ route.subtitle }}</p>

                  <div v-if="activeTab === 'reviews'" class="review-info">
                    <p class="review-rating">⭐ {{ route.rating }}</p>
                    <p class="review-comment">{{ route.comment }}</p>
                    <p class="review-date">{{ formatDate(route.created_at) }}</p>
                  </div>
                </div>

                <button 
                  v-if="activeTab === 'favorites'"
                  class="favorite-btn"
                  :class="{ active: route.isFavorite }"
                  @click.stop="toggleFavorite(route.id)"
                >
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                  </svg>
                </button>
              </div>
            </div>

            <div v-if="filteredRoutes.length === 0 && activeTab !== 'saved'" class="empty-state">
              <p v-if="activeTab === 'favorites'">У вас пока нет избранных маршрутов</p>
              <p v-else-if="activeTab === 'reviews'">Вы ещё не оставили ни одного отзыва</p>
              <p v-else>Пока нет маршрутов в этой категории</p>
            </div>

            <div v-if="savedRoutes.length === 0 && activeTab === 'saved'" class="empty-state">
              <p>У вас пока нет забронированных маршрутов</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import axios from 'axios'
import Header2 from '../components/Header2.vue'

const router = useRouter()
const isLoggedIn = ref(true)
const isLoading = ref(false)
const user = ref<any>(null)
const activeTab = ref('favorites')

interface Route {
  id: number
  title: string
  subtitle: string
  color: string
  rating?: number
  comment?: string
  created_at?: string
  isFavorite: boolean
  isBooked: boolean
  hasReview: boolean
  visited: boolean
  slug?: string
}

interface BookedRoute {
  id: number
  bookingDate: string
  seats: number
  route: {
    id: number
    title: string
    description: string
    slug: string
    mapColor: string
  }
  event: {
    id: number
    date: string
    time: string
    location: string
    bookedSeats: number
    totalSeats: number
  }
}

const favoriteRoutes = ref<Route[]>([])
const reviewedRoutes = ref<Route[]>([])
const savedRoutes = ref<BookedRoute[]>([])
const historyRoutes = ref<Route[]>([])

const filteredRoutes = computed(() => {
  switch (activeTab.value) {
    case 'favorites': return favoriteRoutes.value
    case 'reviews': return reviewedRoutes.value
    case 'history': return historyRoutes.value
    default: return []
  }
})

const colorMap: Record<string, string> = {
  '1': '#F59E0B',
  '2': '#3B82F6',
  '3': '#F97316',
  '4': '#10B981',
  '5': '#8B5CF6',
  'default': '#4F46E5'
}

const fetchProfileData = async () => {
  try {
    isLoading.value = true

    const userResponse = await axios.get('/api/user')
    user.value = userResponse.data

    await fetchFavorites()
    await fetchUserReviews()
    await fetchBookedRoutes()

  } catch (error) {
    console.error('Ошибка загрузки профиля', error)

    if (axios.isAxiosError(error) && error.response?.status === 401) {
      router.push('/')
    }
  } finally {
    isLoading.value = false
  }
}

const fetchFavorites = async () => {
  try {
    const response = await axios.get('/api/favorites')

    favoriteRoutes.value = response.data.data.map((favorite: any) => {
      const r = favorite.route

      return {
        id: r.id,
        title: r.title,
        subtitle: r.description?.substring(0, 100) + '...',
        color: colorMap[r.mapColor] || colorMap.default,
        slug: r.slug,
        isFavorite: true,
        isBooked: false,
        hasReview: false,
        visited: false
      }
    })
  } catch (error) {
    console.error('Ошибка загрузки избранных', error)
  }
}

const fetchUserReviews = async () => {
  try {
    const response = await axios.get('/api/user/reviews')

    reviewedRoutes.value = response.data.data.map((review: any) => {
      const r = review.route
      return {
        id: r.id,
        title: r.title,
        subtitle: '',
        color: colorMap[r.mapColor] || colorMap.default,
        slug: r.slug,
        isFavorite: false,
        isBooked: false,
        hasReview: true,
        visited: false,
        rating: review.rating,
        comment: review.comment,
        created_at: review.created_at
      }
    })
  } catch (error) {
    console.error('Ошибка загрузки отзывов', error)
  }
}

const fetchBookedRoutes = async () => {
  try {
    if (!user.value?.id) {
      console.error('User ID not found')
      return
    }

    const response = await axios.get(`/api/user/${user.value.id}/bookings`)
    
    if (response.data.success) {
      savedRoutes.value = response.data.data
      console.log('Загружено бронирований:', savedRoutes.value.length)
    }
  } catch (error) {
    console.error('Ошибка загрузки бронирований', error)
  }
}

const toggleFavorite = async (routeId: number) => {
  try {
    await axios.delete(`/api/favorites/${routeId}`)
    favoriteRoutes.value = favoriteRoutes.value.filter(r => r.id !== routeId)
    message.success('Маршрут удален из избранного')
  } catch (error) {
    console.error('Ошибка при удалении избранного', error)
    message.error('Не удалось удалить маршрут')
  }
}

const cancelBooking = async (bookingId: number) => {
  try {
    const response = await axios.delete(`/api/bookings/${bookingId}`)
    
    if (response.data.success) {
      savedRoutes.value = savedRoutes.value.filter(b => b.id !== bookingId)
      message.success('Бронирование отменено')
    }
  } catch (error) {
    console.error('Ошибка отмены бронирования', error)
    message.error('Не удалось отменить бронирование')
  }
}

const navigateToRoute = (routeId: number) => {
  const all = [...favoriteRoutes.value, ...reviewedRoutes.value]
  const route = all.find(r => r.id === routeId)

  if (route?.slug) {
    router.push(`/route/${route.slug}`)
  } else {
   
    const booking = savedRoutes.value.find(b => b.route.id === routeId)
    if (booking?.route.slug) {
      router.push(`/route/${booking.route.slug}`)
    }
  }
}

const handleSignOut = async () => {
  try {
    await axios.post('/logout')
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    delete axios.defaults.headers.common['Authorization']
    router.push('/')
  }
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('ru-RU')
}

onMounted(async () => {
  const token = localStorage.getItem('auth_token')

  if (!token) {
    router.push('/')
    return
  }

  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

  await fetchProfileData()
})
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: #E5E7EB;
}

.profile-container {
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
  font-size: 18px;
  color: #666;
}

.profile-content {
  display: flex;
  gap: 30px;
  max-width: 1400px;
  margin: 0 auto;
}

.profile-main {
  flex: 1;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.tabs {
  display: flex;
  border-bottom: 2px solid #E5E7EB;
  background: #F9FAFB;
}

.tab {
  flex: 1;
  padding: 16px 12px;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #6B7280;
  transition: all 0.3s;
  text-align: center;
  line-height: 1.4;
}

.tab:hover {
  background: #F3F4F6;
  color: #374151;
}

.tab.active {
  background: white;
  border-bottom-color: #4F46E5;
  color: #1F2937;
  font-weight: 600;
}

.routes-list {
  padding: 20px;
  max-height: calc(100vh - 250px);
  overflow-y: auto;
}

.booking-card {
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #E5E7EB;
}

.booking-badge {
  background: #FF8C42;
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
}

.booking-seats {
  background: #F3F4F6;
  color: #374151;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
}

.route-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-radius: 12px;
  margin-bottom: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.route-card.clickable {
  cursor: pointer;
  margin-bottom: 0;
}

.route-card.clickable:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.review-card {
  background-color: #4F46E5;
  border: 2px solid #4F46E5;
  box-shadow: none;
}

.route-info {
  flex: 1;
}

.route-info h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
  color: white;
  line-height: 1.4;
}

.route-info p {
  margin: 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.4;
}

.booking-time,
.booking-location {
  margin: 4px 0;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.95);
}

.review-info {
  color: white;
  margin-top: 6px;
  font-size: 14px;
}

.review-rating {
  font-size: 16px;
  font-weight: 600;
}

.review-comment {
  margin-top: 4px;
}

.booking-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #E5E7EB;
}

.btn-secondary,
.btn-cancel {
  flex: 1;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-secondary {
  background: #4F46E5;
  color: white;
}

.btn-secondary:hover {
  background: #4338CA;
}

.btn-cancel {
  background: #FEE2E2;
  color: #DC2626;
}

.btn-cancel:hover {
  background: #FCA5A5;
}

.favorite-btn {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-left: 16px;
}

.favorite-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.1);
}

.favorite-btn.active {
  background: rgba(255, 255, 255, 0.3);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6B7280;
}

.empty-state p {
  font-size: 16px;
  margin: 0;
}

.routes-list::-webkit-scrollbar {
  width: 8px;
}

.routes-list::-webkit-scrollbar-track {
  background: #F3F4F6;
  border-radius: 4px;
}

.routes-list::-webkit-scrollbar-thumb {
  background: #D1D5DB;
  border-radius: 4px;
}

.routes-list::-webkit-scrollbar-thumb:hover {
  background: #9CA3AF;
}

@media (max-width: 768px) {
  .profile-content {
    flex-direction: column;
  }

  .tabs {
    overflow-x: auto;
  }

  .tab {
    white-space: nowrap;
    min-width: 120px;
  }

  .booking-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .booking-actions {
    flex-direction: column;
  }

  .route-card {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .favorite-btn {
    margin-left: 0;
  }
}
</style>