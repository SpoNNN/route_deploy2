<template>
  <div class="route-item">{{ path.title }}</div>
</template>

<script>
import { defineProps } from 'vue'

defineProps({
  path: { type: Object, required: true },
})
</script>
