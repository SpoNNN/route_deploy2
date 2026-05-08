<template>
  <div>
    <div id="map" class="map" tabindex="0" style="min-height: 850px"></div>

    <!-- Popup -->
    <div
      v-if="popupVisible"
      :style="{ top: popupPosition.y + 'px', left: popupPosition.x + 'px' }"
      class="custom-popup"
    >
      <button class="close-btn" @click="hidePopup">✖</button>
      <h3 class="popup-title">{{ popupText }}</h3>
      <p class="popup-subtitle">Адрес</p>
      <p class="popup-content">{{ popupAddress }}</p>
      <div class="popup-actions">
        <div
          class="popup-images"
          v-if="currentPoint && currentPoint.images && currentPoint.images.length"
        ></div>
        <a :href="popupUrl" target="_blank" class="popup-btn" v-if="popupUrl">
          <span>🔗</span> Перейти на сайт
        </a>
        <button class="popup-btn" @click="navigateToPage"><span>ℹ️</span> Подробнее</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watchEffect } from 'vue'

import { ref, onMounted } from 'vue'
import Map from 'ol/Map'
import View from 'ol/View'
import { fromLonLat } from 'ol/proj'
import TileLayer from 'ol/layer/Tile'
import OSM from 'ol/source/OSM'
import VectorLayer from 'ol/layer/Vector'
import VectorSource from 'ol/source/Vector'
import { GeoJSON } from 'ol/format'
import { Style, Stroke, Fill, Circle as OlCircle } from 'ol/style'
import Feature from 'ol/Feature'
import Point from 'ol/geom/Point'
import type { Geometry } from 'ol/geom'
// import { MapBrowserEvent } from 'ol'

interface PointData {
  lon: number
  lat: number
  name: string
  address: string
  url: string
  images?: string[]
}

const props = defineProps<{
  points: PointData[]
  routeGeoJsonUrl: string | null
  // pointNameToPageMap: Record<string, string>
}>()

const emit = defineEmits(['navigate'])

// === Локальные переменные для popup и карты ===
const popupVisible = ref(false)
const popupText = ref('')
const popupAddress = ref('')
const popupUrl = ref('')
const popupPosition = ref({ x: 0, y: 0 })
const selectedFeature = ref<Feature<Geometry> | null>(null)
const currentPoint = ref<PointData | null>(null)
const currentImageIndex = ref(0)
const pointSource = ref<VectorSource<Feature<Geometry>> | null>(null)
const map = ref<Map | null>(null)

const defaultPointStyle = ref<Style | null>(null)

watchEffect(() => {
  const color =
    props.routeGeoJsonUrl === './tsiolkovsky.geojson' ? '#FF6B00' : '#FF6b00'

  defaultPointStyle.value = new Style({
    image: new OlCircle({
      radius: 7,
      fill: new Fill({ color }),
      stroke: new Stroke({ color: 'black', width: 1 }),
    }),
  })
})

// defaultPointStyle.value = new Style({
//   image: new OlCircle({
//     radius: 7,
//     fill: new Fill({ color: 'rgba(255,184,0,255)' }), // с "желто-красного"
//     stroke: new Stroke({ color: 'black', width: 1 }),
//   }),
// })

const selectedPointStyle = new Style({
  image: new OlCircle({
    radius: 9,
    fill: new Fill({ color: '#FF6B00' }),
    stroke: new Stroke({ color: 'black', width: 2 }),
  }),
})

onMounted(() => {
 

  let routeColor = '#ffb800'
  if (props.routeGeoJsonUrl === './tsiolkovsky.geojson') {
    routeColor = '#FF6B00'
  } else {
    routeColor = '#ffb800'
  }
  console.log('routeColor is ', routeColor)

  const mapElement = document.getElementById('map')
  if (!mapElement) return

  map.value = new Map({
    target: mapElement,
    layers: [
      new TileLayer({
        source: new OSM(),
      }),
    ],
    view: new View({
      center: fromLonLat([55.9721, 54.7388]),
      zoom: 13,
    }),
  })


  if (props.routeGeoJsonUrl && map.value) {
    const routeSource = new VectorSource()
    fetch(props.routeGeoJsonUrl)
      .then((response) => response.json())
      .then((data) => {
        const features = new GeoJSON().readFeatures(data, {
          featureProjection: 'EPSG:3857',
        })

        const routeStyle = new Style({
          stroke: new Stroke({
            // color: 'rgba(255,184,0,255)',
            // color: '#FF6B00',
            color: routeColor,
            width: 4,
          }),
        })

        // const routeStyle = new Style({
        //   stroke: new Stroke({
        //     color: 'rgba(255,184,0,255)',
        //     width: 4,
        //   }),
        // })
        features.forEach((f) => f.setStyle(routeStyle))
        routeSource.addFeatures(features)
      })
    map.value.addLayer(new VectorLayer({ source: routeSource, zIndex: 1 }))
  }


  pointSource.value = new VectorSource()
  props.points.forEach((point) => {

    const feature = new Feature({
      geometry: new Point(fromLonLat([point.lon, point.lat])),
    })

    feature.set('properties', point)
    feature.setStyle(defaultPointStyle.value)
    if (pointSource.value) {
      pointSource.value.addFeature(feature)
    }
  })

  if (map.value && pointSource.value) {
    map.value.addLayer(
      new VectorLayer({
        source: pointSource.value,
        zIndex: 2,
      }),
    )

    map.value.on('singleclick', (evt) => {
      if (!mapElement || !map.value) return

      map.value.forEachFeatureAtPixel(evt.pixel, (feature) => {
        if (feature instanceof Feature) {
          const properties = feature.get('properties')
          if (properties && properties.name) {
         
            if (selectedFeature.value) {
              selectedFeature.value.setStyle(defaultPointStyle.value)
            }
            feature.setStyle(selectedPointStyle)
            selectedFeature.value = feature

            const geometry = feature.getGeometry()
            if (geometry instanceof Point) {
              const coordinate = geometry.getCoordinates()
              const pixel = map.value.getPixelFromCoordinate(coordinate)
              const mapRect = mapElement.getBoundingClientRect()

              const mapAbsoluteLeft = mapRect.left + window.pageXOffset
              const mapAbsoluteTop = mapRect.top + window.pageYOffset

              let popupX = pixel[0] - 140
              let popupY = pixel[1] - 190

              if (popupX < 0) popupX = 10
              if (popupX + 280 > mapElement.offsetWidth) popupX = mapElement.offsetWidth - 290
              if (popupY < 0) popupY = 10

              popupPosition.value = {
                x: mapAbsoluteLeft + popupX,
                y: mapAbsoluteTop + popupY,
              }

              popupText.value = properties.name
              popupAddress.value = properties.address
              popupUrl.value = properties.url
              currentPoint.value = properties
              currentImageIndex.value = 0
              popupVisible.value = true
            }
          }
        }
      })
    })
  }
})


function hidePopup() {
  popupVisible.value = false
  if (selectedFeature.value) {
    selectedFeature.value.setStyle(defaultPointStyle.value)
    selectedFeature.value = null
  }
  currentPoint.value = null
  currentImageIndex.value = 0
}


function navigateToPage() {

  const newSelectedPoint = currentPoint.value
  // if (page) {
  // }
  hidePopup()
  setTimeout(() => {
    emit('navigate', newSelectedPoint)
  }, 300)
}

const openPopupByName = (name: string) => {
  console.log('openPopupByName это наш нейм в openPopupByName', name)
  if (!pointSource.value || !map.value) return

  const features = pointSource.value.getFeatures()
  const feature = features.find((f) => {
    const props = f.get('properties')
    return props.name === name
  })

  if (feature) {
    // Сброс стилей предыдущей точки
    if (selectedFeature.value) {
      selectedFeature.value.setStyle(defaultPointStyle.value)
    }
    feature.setStyle(selectedPointStyle)
    selectedFeature.value = feature

    const geometry = feature.getGeometry()
    if (geometry instanceof Point) {
      const mapElement = document.getElementById('map')
      if (!mapElement || !map.value) return

      const coordinate = geometry.getCoordinates()
      const pixel = map.value.getPixelFromCoordinate(coordinate)
      const mapRect = mapElement.getBoundingClientRect()
      const mapAbsoluteLeft = mapRect.left + window.pageXOffset
      const mapAbsoluteTop = mapRect.top + window.pageYOffset

      let popupX = pixel[0] - 140
      let popupY = pixel[1] - 190

      if (popupX < 0) popupX = 10
      if (popupX + 280 > mapElement.offsetWidth) popupX = mapElement.offsetWidth - 290
      if (popupY < 0) popupY = 10

      popupPosition.value = {
        x: mapAbsoluteLeft + popupX,
        y: mapAbsoluteTop + popupY,
      }

      const properties = feature.get('properties')
      popupText.value = properties.name
      popupAddress.value = properties.address
      popupUrl.value = properties.url
      currentPoint.value = properties
      currentImageIndex.value = 0
      popupVisible.value = true
    }
  }
}

// Добавляем в expose, чтобы можно было вызвать извне
defineExpose({ openPopupByName })
</script>

<style scoped>

.map {
  width: 100%;
  min-height: 1200px;
  border: 1px solid #ccc;
}

/* Стили для popup */
.custom-popup {
  position: absolute;
  background: white;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #ccc;
  box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
  width: 280px;
  font-family: Arial, sans-serif;
  opacity: 1;
  transform: scale(1);
  transition:
    opacity 0.3s ease,
    transform 0.3s ease;
}

.close-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
}

.popup-title {
  font-size: 16px;
  font-weight: bold;
}

.popup-subtitle {
  font-size: 12px;
  color: gray;
  margin-bottom: 2px; /* Уменьшает расстояние до popupText но окно чуть выше от точки*/
}

.popup-content {
  font-size: 14px;
}

.popup-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
}

.popup-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  padding: 6px 10px;
  border: none;
  border-radius: 6px;
  background: #f3f3f3;
  cursor: pointer;
}

.custom-popup.hidden {
  opacity: 0;
  transform: scale(0.9);
}

.popup-btn:hover {
  background: #e0e0e0;
}

.popup-images {
  margin-bottom: 10px;
  text-align: center;
}

.popup-image {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

.image-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-top: 5px;
}

.image-controls button {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  padding: 0 5px;
}

.image-controls button:hover {
  color: #666;
}
</style>
