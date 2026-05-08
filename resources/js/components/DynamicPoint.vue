<!-- DynamicPoint.vue
<template>
  <RoutePointDetails :point-data="pointData" @navigateBack="$emit('navigateBack')" />
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import RoutePointDetails from './components/RoutePointDetails.vue'

interface Point {
  name: string
  address: string
  description: string   
  url?: string
  images?: string[]
}

const props = defineProps<{
  pointData: Point
}>()

const emit = defineEmits<{
  (e: 'navigateBack'): void
}>()
</script> -->
