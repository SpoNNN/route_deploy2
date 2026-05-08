<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <h1 class="text-white text-2xl font-bold mb-6">Войти в аккаунт</h1>
        
        <form @submit.prevent="sign">
          <!-- Email -->
          <div class="form-group">
            <input 
              class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm 
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-300 
                     transition duration-300 ease-in-out" 
              type="email" 
              placeholder="Email" 
              v-model="email"
              required
            />
          </div>

          <!-- Пароль -->
          <div class="form-group relative">
            <input 
              class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 pr-12 shadow-sm 
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-300 
                     transition duration-300 ease-in-out" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="Пароль" 
              v-model="password"
              required
            />
            <button 
              type="button"
              class="absolute inset-y-0 right-3 flex items-center text-gray-600 hover:text-gray-800"
              @click="togglePassword"
            >
              <img v-if="showPassword" src="/open_eye.svg" alt="Скрыть" class="h-5 w-5">
              <img v-else src="/hide_eye.svg" alt="Показать" class="h-5 w-5">
            </button>
          </div>

          <!-- Ошибки -->
          <div v-if="errmsg" class="error-message">
            {{ errmsg }}
          </div>

          <!-- Кнопка отправки -->
          <button 
            type="submit" style="color: white;"
            :disabled="isLoading"
            class="w-full bg-blue-500 text-white font-semibold px-4 py-3 rounded-lg 
                   hover:bg-blue-600 transition duration-300 ease-in-out 
                   shadow-md hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isLoading ? 'Вход...' : 'Войти' }}
          </button>
        </form>

        <!-- Ссылка на регистрацию -->
        <p class="mt-4 pt-4 text-center text-white">
          Нет аккаунта? 
          <a href="#" @click.prevent="$emit('switchToRegister')" class="text-blue-300 hover:text-blue-100 underline">
        Войти
      </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter() // Добавьте эту строку

const email = ref('')
const password = ref('')
const errmsg = ref('')
const isLoading = ref(false)
const showPassword = ref(false)
const emit = defineEmits(['registered', 'switchToRegister'])

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const sign = async () => {
  errmsg.value = ''
  
  try {
    isLoading.value = true
    
    const response = await axios.post('/api/login', {
      email: email.value,
      password: password.value
    })

    // Сохраняем токен и пользователя
    localStorage.setItem('auth_token', response.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
    
    emit('loggedIn', response.data.user)
    console.log('Successfully signed in', response.data.user)
    
    // router.push('/profile') // Убрать эту строку, если используете emit
    // Просто эмитим событие, а главный компонент App.vue обработает навигацию
    
  } catch (error) {
    console.error('Login error:', error)
    
    if (error.response?.status === 422) {
      if (error.response.data?.errors?.email) {
        errmsg.value = error.response.data.errors.email[0]
      } else {
        errmsg.value = 'Неверный email или пароль'
      }
    } else if (error.response?.data?.message) {
      errmsg.value = error.response.data.message
    } else {
      errmsg.value = 'Ошибка входа. Попробуйте позже'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;

  padding: 20px;
}

.auth-container {
  width: 100%;
  max-width: 450px;
}

.auth-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);

  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 20px;
}

.error-message {
  background: rgba(239, 68, 68, 0.9);
  color: white;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
  text-align: center;
}
</style>