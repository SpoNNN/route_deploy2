import { ref, computed } from 'vue'
import axios from 'axios'

const isAuthenticated = ref(false)
const isChecking = ref(false)
const isAdmin = ref(false)
const currentUser = ref<{
  id: number
  name: string
  email: string
  is_admin: boolean
} | null>(null)

export function useAuthCheck() {
  const checkAuth = async () => {
    if (typeof window === 'undefined') {
      isAuthenticated.value = false
      currentUser.value = null
      return false
    }
    
    const token = localStorage.getItem('auth_token')
    console.log('useAuthCheck - checkAuth - token:', token ? 'ЕСТЬ' : 'НЕТ')
    
    if (!token) {
      console.log('useAuthCheck - токен отсутствует')
      isAuthenticated.value = false
      currentUser.value = null
      return false
    }

    try {
      isChecking.value = true
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      
      console.log('useAuthCheck - запрос к /api/user...')
      const response = await axios.get('/api/user')
      console.log('useAuthCheck - ответ получен:', response.data)
      
      // Сохраняем данные пользователя
      currentUser.value = {
        id: response.data.id,
        name: response.data.name,
        email: response.data.email,
        is_admin: response.data.is_admin
      }
      console.log('test', currentUser.value.is_admin)
      // Также сохраняем в localStorage
      localStorage.setItem('user', JSON.stringify(currentUser.value))
      
      isAuthenticated.value = true
          isAdmin.value = currentUser.value.is_admin
      console.log('useAuthCheck - ✅ авторизован, userId:', currentUser.value.id, isAdmin.value)
      return true
      
    } catch (error: any) {
      console.error('useAuthCheck - ❌ ошибка:', error.response?.status)
      isAuthenticated.value = false
      currentUser.value = null
      
      if (axios.isAxiosError(error) && error.response?.status === 401) {
        console.log('useAuthCheck - токен невалидный, очищаем')
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
        delete axios.defaults.headers.common['Authorization']
      }
      
      return false
    } finally {
      isChecking.value = false
    }
  }

  const checkToken = () => {
    if (typeof window === 'undefined') {
      isAuthenticated.value = false
      currentUser.value = null
      return false
    }
    
    const token = localStorage.getItem('auth_token')
    const hasToken = !!token
    
    console.log('useAuthCheck - checkToken:', hasToken ? 'ЕСТЬ' : 'НЕТ')
    isAuthenticated.value = hasToken
    
    if (hasToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      
      // Пытаемся загрузить пользователя из localStorage
      const savedUser = localStorage.getItem('user')
      if (savedUser) {
        try {
          currentUser.value = JSON.parse(savedUser)
           
          console.log('useAuthCheck - пользователь загружен из localStorage, userId:', currentUser.value?.id)
          
        } catch (e) {
          console.error('useAuthCheck - ошибка парсинга user из localStorage')
        }
      }
    } else {
      currentUser.value = null
    }
    
    return hasToken
  }

  const setAuthenticated = (value: boolean) => {
    console.log('useAuthCheck - setAuthenticated:', value)
    isAuthenticated.value = value
    
    if (!value) {
      currentUser.value = null
      localStorage.removeItem('user')
    }
  }

  const logout = () => {
    console.log('useAuthCheck - logout')
    isAuthenticated.value = false
    currentUser.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    delete axios.defaults.headers.common['Authorization']
  }

  return {
    isAuthenticated: computed(() => isAuthenticated.value),
    isChecking: computed(() => isChecking.value),
    currentUser: computed(() => currentUser.value),
    userId: computed(() => currentUser.value?.id || 0),
      isAdmin: computed(() => isAdmin.value),
    checkAuth,
    checkToken,
    setAuthenticated,
    logout
    
  }
}