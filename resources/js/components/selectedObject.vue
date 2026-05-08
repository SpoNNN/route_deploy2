<template>
  <div class="slider-container">
    <!-- <h1>selectedObject</h1> -->
    <!-- Кнопка назад -->
    <div class="back-button" @click="$emit('navigateBack')">
      <LeftOutlined class="icon-back" />
    </div>

    <h2 class="text-center text-white text-2xl font-bold mb-4">{{ pointData.name }}</h2>

    <div class="relative py-1">
      <a-image v-if="pointData.images && pointData.images.length > 0" :src="pointData.images[0]" alt="Preview"
        class="point-image main-image" @click="visible = true" :preview="{ visible: false }" />
    </div>

    <div class="border-b-2 border-gray-300 w-full my-4"></div>

    <div class="rounded-xl px-4 py-4 text-white text-sm" style="
    background-color: rgb(64, 84, 148);
    min-width: 350px !important;
    max-width: 470px !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
  ">
      <p style="word-wrap: break-word; overflow-wrap: break-word;">
        {{ pointData.description }}
      </p>
      <span style="word-wrap: break-word; overflow-wrap: break-word;">
        Адрес: {{ pointData.address }}
      </span>
      <div v-if="pointData.url" style="text-align: left; margin-top: 10px">
      <div style="display: flex;" class="content_site___info">
          <p class="mr-1">Ссылка на сайт: </p>
        <a :href="pointData.url" target="_blank">{{ pointData.url }}</a>
      </div>
      </div>
    </div>

    <div style="display: none">
      <a-image-preview-group :preview="{ visible, onVisibleChange: (vis) => (visible = vis) }">
        <a-image v-for="(image, index) in pointData.images" :key="index" :src="image" :alt="pointData.name" />
      </a-image-preview-group>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { LeftOutlined } from '@ant-design/icons-vue'
defineProps<{
  pointData: {
    name: string
    address: string
    description: string
    url?: string
    images?: string[]
  }
}>()
defineEmits<{
  (e: 'navigateBack'): void
}>()

const visible = ref(false)
</script>

<style scoped>
.slider-container {
  width: 100%;
  min-width: 450px;
  /* padding: 20px;
    color: white; */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  /* background-color: #2c3e50; */
  padding: 10px;
  border-radius: 10px;
  max-width: 600px;
  margin: 0 auto;
  position: relative;
}

.back-button {
  position: absolute;
  top: 10px;
  left: 10px;
  cursor: pointer;
  font-size: 24px;
  color: white;
}

.title {
  font-size: 1.5em;
  margin-bottom: 15px;
}

.address {
  margin-bottom: 15px;
}

.description {
  margin-bottom: 20px;
  line-height: 1.5;
}

.website {
  margin-bottom: 20px;
}

.website a {
  color: #4caf50;
  text-decoration: none;
}

.images {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.point-image {
  width: 100%;
  height: auto;
  border-radius: 8px;
  cursor: pointer;
}

.icon-back {
  font-size: 24px;
  color: white;
}

.text-center {
  text-shadow: 0px 0px 6px rgba(0, 0, 0, 0.8);
  /* Тень для текста */
  margin-bottom: 20px;
}
</style>
