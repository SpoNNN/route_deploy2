<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <h1 class="text-white text-2xl font-bold mb-6">Создать аккаунт</h1>
        
        <form @submit.prevent="register">
          <!-- Имя -->
          <div class="form-group">
            <input 
              class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm 
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-300 
                     transition duration-300 ease-in-out" 
              type="text" 
              placeholder="Имя" 
              v-model="name"
              required
            />
          </div>

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
              placeholder="Пароль (минимум 8 символов)" 
              v-model="password"
              minlength="8"
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

          <!-- Подтверждение пароля -->
          <div class="form-group relative">
            <input 
              class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 pr-12 shadow-sm 
                     focus:border-blue-500 focus:ring-2 focus:ring-blue-300 
                     transition duration-300 ease-in-out" 
              :type="showConfirmPassword ? 'text' : 'password'" 
              placeholder="Подтвердите пароль" 
              v-model="password_confirmation"
              required
            />
            <button 
              type="button"
              class="absolute inset-y-0 right-3 flex items-center text-gray-600 hover:text-gray-800"
              @click="toggleConfirmPassword"
            >
              <img v-if="showConfirmPassword" src="/open_eye.svg" alt="Скрыть" class="h-5 w-5">
              <img v-else src="/hide_eye.svg" alt="Показать" class="h-5 w-5">
            </button>
          </div>

          <!-- Ошибки -->
          <div v-if="errorMsg" class="error-message">
            {{ errorMsg }}
          </div>

          <!-- Кнопка отправки -->
          <button style="color: white;"
            type="submit"
            :disabled="isLoading"
            class="w-full bg-blue-500 text-white font-semibold px-4 py-3 rounded-lg 
                   hover:bg-blue-600 transition duration-300 ease-in-out 
                   shadow-md hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isLoading ? 'Создание...' : 'Создать' }}
          </button>
        </form>

        <!-- Ссылка на вход -->
        <p class="mt-4 pt-4 text-center text-white">
          Уже есть аккаунт? 
          <a href="#" @click.prevent="$emit('switchToSignIn')" class="text-blue-300 hover:text-blue-100 underline">
        Войти
      </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

import axios from 'axios'
const emit = defineEmits(['registered', 'switchToSignIn'])


const name = ref('')
const email = ref('')
const password = ref('')
const password_confirmation = ref('')
const errorMsg = ref('')
const isLoading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmPassword = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}

const register = async () => {
  errorMsg.value = ''
  
  // Валидация на фронте
  if (password.value !== password_confirmation.value) {
    errorMsg.value = 'Пароли не совпадают'
    return
  }

  if (password.value.length < 8) {
    errorMsg.value = 'Пароль должен содержать минимум 8 символов'
    return
  }

  try {
    isLoading.value = true
    
    const response = await axios.post('/api/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value
    })

    // Сохраняем токен и пользователя
    localStorage.setItem('auth_token', response.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
    emit('registered', response.data.user)
    console.log('Successfully registered!', response.data.user)
    router.push('/profile')
    
  } catch (error) {
    console.error('Registration error:', error)
    
    if (error.response?.data?.errors) {
      // Laravel validation errors
      const errors = error.response.data.errors
      errorMsg.value = Object.values(errors).flat().join(', ')
    } else if (error.response?.data?.message) {
      errorMsg.value = error.response.data.message
    } else {
      errorMsg.value = 'Ошибка регистрации. Попробуйте позже'
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