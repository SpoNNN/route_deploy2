<template>
  <div class="timeline-container">
    <div class="timeline-item" v-for="(item, index) in items" :key="index">
      <div class="timeline-number">{{ index + 1 }}</div>
      <div class="timeline-text">{{ item }}</div>
    </div>
  </div>
</template>

<script>
// работал тут
import { ref } from 'vue'

export default {
  setup() {
    const items = ref([
      'Кампус Евразийского НОЦ РБ',
      'Главный корпус УУНиТ',
      'Гуманитарный корпус УУНиТ',
      'УФИЦ РАН',
      'БГПУ им. Акмуллы',
      'ИЭС УГНТУ',
    ])

    return {
      items,
    }
  },
}
</script>

<style scoped>
.timeline-container {
  display: flex;
  flex-direction: column;
  position: relative;
  padding: 10px;
  background-color: rgb(64, 84, 148);
  border-radius: 10px;
  width: 435px;
}

.timeline-container::before {
  content: '';
  position: absolute;
  top: 10px;
  left: 25px; /* Линия центрируется относительно кружков */
  width: 2px;
  height: calc(100% - 40px);
  background-color: rgba(255, 184, 0, 255); /* Цвет линии */
}

.timeline-item {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
  position: relative;
}

.timeline-number {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: rgba(255, 184, 0, 255); /* Синий кружок */
  color: rgba(50, 54, 142, 255);
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 16px;
  font-weight: bold;
  margin-right: 20px;
  z-index: 1; /* Чтобы кружки перекрывали линию */
}

.timeline-text {
  color: white;
  font-size: 13px;
}
</style>
