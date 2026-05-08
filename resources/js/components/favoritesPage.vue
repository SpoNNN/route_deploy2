<script setup>
import { ref, onMounted } from 'vue';
import { doc, getDoc } from "firebase/firestore";
import { getAuth, onAuthStateChanged } from 'firebase/auth';
import { initializeApp } from "firebase/app";
import { getFirestore } from "firebase/firestore";

const firebaseConfig = {
  apiKey: "AIzaSyDnXXJ1R-lkoheA8LEJuHLzy2kjUvcC4-w",
  authDomain: "myproject-35bc3.firebaseapp.com",
  projectId: "myproject-35bc3",
  storageBucket: "myproject-35bc3.firebasestorage.app",
  messagingSenderId: "1064017138140",
  appId: "1:1064017138140:web:b294ccd4f2b9c9762abf19"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);


const props = defineProps({
  routes: Array 
});

const emit = defineEmits(['navigate']);

const navigateToRoute = (route) => {
  if (route.page) emit('navigate', route.page);
  if (route.route) emit('selectRoute', route.route);
};

const favorites = ref([]);

onMounted(async () => {
  const user = auth.currentUser;
  if (user) {
    const userDocRef = doc(db, "users", user.uid);
    const userDoc = await getDoc(userDocRef);

    if (userDoc.exists()) {
      const savedFavorites = userDoc.data().favorites || [];
    
      favorites.value = savedFavorites.filter(fav => fav?.title);
    }
  } else {
    const savedFavorites = JSON.parse(localStorage.getItem('favorites') || '[]');
  
    favorites.value = savedFavorites.filter(fav => fav?.title);
  }
});
</script>

<template>
  <div class="p-4 bg-indigo-800 text-white ">
    <h2 class="text-2xl mb-6 text-center">Избранные маршруты</h2>
    <div v-if="!favorites.length" class="text-center text-gray-300">
      Нет избранных маршрутов
    </div>
    <div v-else class="space-y-3 mx-auto" style="width: 420px;">
      <div 
        v-for="fav in favorites" 
        :key="fav.title"
      >
        <button 
          class="relative w-full py-2 px-4 border-2 border-green-500 text-green-500 rounded-md hover:bg-green-500
           hover:text-white transition overflow-visible text-center flex items-center justify-center"
          @click="navigateToRoute(fav)"
        >
          <span>{{ fav.title }}</span>
        </button>
        
      </div>
    </div>
  </div>
</template>
