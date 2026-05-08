<template>
    <div class="slider-container">
      <!-- Заголовок -->
      <div class="text-center text-white text-2xl font-bold mb-4">
        АО «Уфанет»
      </div>
  
      <!-- Карусель -->
      <a-carousel arrows>
        <!-- Настройка кнопок -->
        <template #prevArrow>
          <div class="custom-slick-arrow" style="left: 10px; z-index: 1">
            <left-circle-outlined />
          </div>
        </template>
        <template #nextArrow>
          <div class="custom-slick-arrow" style="right: 10px">
            <right-circle-outlined />
          </div>
        </template>
  
        <!-- Слайд 1: Изображение -->
        <div>
          <div class="relative py-1">
            <a-image
              :preview="{ visible: false }"
              :width="400"
              src="/medium.jpg"
              class="main-image"
              @click="visible = true"
            />
          </div>
        </div>
  
        <!-- Слайд 2: Описание -->
        <div>
          <div class="h-50 rounded-xl px-4 py-4 text-white text-sm" style="background-color: rgb(64,84,148);">
            <p>
              Знакомство с крупнейшим провайдером интернета в Башкостостане и других регионах. Компания занимает лидирующую позицию, оказывая широкий спектр услуг: интернет, телефония, телевидение, информационные и медийные проекты, IT-сопровождение, системы безопасности, умные домофоны и дома, системная интеграция, мобильная связь, разработка собственных приложений для удобства клиентов.
            </p>
            <span>Адрес: пр. Октября, 4/3</span>
          </div>
        </div>
  
        <!-- Слайд 3: Дополнительный контент -->
        <div>
          <h3 class="text-white">Другие услуги компании</h3>
        </div>
      </a-carousel>
  
      <!-- Скрытые изображения для предпросмотра -->
      <div style="display: none">
        <a-image-preview-group :preview="{ visible, onVisibleChange: (vis: boolean) => (visible = vis) }">
          <a-image src="/medium.jpg" />
          <a-image src="/1606136590_ufa-0.jpg" />
          <a-image src="street.jpg" />
        </a-image-preview-group>
      </div>
    </div>
</template>
  
<script lang="ts" setup>
  import { ref } from 'vue';
  import { LeftCircleOutlined, RightCircleOutlined } from '@ant-design/icons-vue';
  
  const visible = ref(false);
</script>
  
<style scoped>
  /* Центрирование основного контейнера */
  .slider-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border-radius: 10px;
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
  }
  
  /* Настройки текста */
  .text-center {
    color: white;
    font-family: Arial, sans-serif;
  }
  
  /* Основное изображение */
  .main-image {
    border-radius: 10px;
    cursor: pointer;
  }
  
  /* Кастомные кнопки карусели */
  :deep(.slick-arrow.custom-slick-arrow) {
    width: 25px;
    height: 25px;
    font-size: 25px;
    color: #fff;
    background-color: rgba(31, 45, 61, 0.11);
    transition: ease all 0.3s;
    opacity: 0.3;
    z-index: 1;
  }
  :deep(.slick-arrow.custom-slick-arrow:hover) {
    color: #fff;
    opacity: 0.5;
  }
  
  /* Стили для текста в слайдах */
  :deep(.slick-slide h3) {
    text-align: center;
    color: #fff;
  }
</style>
  