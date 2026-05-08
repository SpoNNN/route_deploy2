<template>
  <div style="height: 60px; background-color: rgba(0, 0, 57, 255)">
    <div class="flex justify-between px-8 py-3 h-20 m-0" style="height: 60px">
      <div class="flex items-center">
        <img src="/ноц 1.svg" alt="Logo" class="w-30" />
      </div>
      <ul class="h-full flex space-x-10 items-center">
        
        <li>
          <span class="text-white cursor-pointer hover:underline" @click="$emit('navigateToWay')">
            Все маршруты
          </span>
        </li>


        <li v-if="isLoggedIn && !isProfilePage">
          <span class="text-white cursor-pointer hover:underline" @click="navigateToProfile">
            Профиль
          </span>
        </li>

        <li v-if="!isLoggedIn">
          <span class="text-white cursor-pointer hover:underline" @click="$emit('navigateToRegister')">
            Создать аккаунт
          </span>
        </li>
        <li v-if="!isLoggedIn">
          <span class="text-white cursor-pointer hover:underline" @click="$emit('navigateToLogin')">
            Войти
          </span>
        </li>
        <li v-if="isLoggedIn">
          <button style="color: white;" @click="handleLogout"
            class="text-white cursor-pointer hover:underline bg-transparent border-none">
            Выйти
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const props = defineProps(['isLoggedIn'])
console.log('props', props)
const emit = defineEmits(['logout'])
const router = useRouter()
const route = useRoute()


const isProfilePage = computed(() => {
  return route.path === '/profile'
})

const navigateToProfile = () => {
  router.push('/profile')
}

const handleLogout = async () => {
  emit('logout')

}
</script>

<style scoped>
button {
  padding: 0;
  font-size: inherit;
  font-family: inherit;
}

button:focus {
  outline: none;
}
</style>