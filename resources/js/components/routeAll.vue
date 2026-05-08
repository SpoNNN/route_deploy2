<template>
  <div>
    <div id="map" class="map" tabindex="0" style="min-height: 850px"></div>

    <div v-if="popupVisible" :style="{ top: popupPosition.y + 'px', left: popupPosition.x + 'px' }"
      class="custom-popup">
      <button class="close-btn" @click="hidePopup">✖</button>
      <h3 class="popup-title">{{ popupText }}</h3>
      <p class="popup-subtitle">Адрес</p>
      <p class="popup-content">{{ popupAddress }}</p>
      <div class="popup-actions">
        <a :href="popupUrl" target="_blank" class="popup-btn" v-if="popupUrl">
          <span>🔗</span> Перейти на сайт
        </a>
        <button class="popup-btn" @click="navigateToPage">
          <span>ℹ️</span> Подробнее
        </button>
      </div>
    </div>

    <div class="legend" v-if="showAllRoutes">
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(0, 148, 255, 1)"></span>
        <span class="legend-label">Маршрут Пифагор</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(243, 211, 46, 1)"></span>
        <span class="legend-label">Маршрут Авиценна</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(150, 105, 200, 1)"></span>
        <span class="legend-label">Маршрут Вернадский</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(0, 168, 221, 1)"></span>
        <span class="legend-label">Маршрут Менделеев</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(255, 184, 0, 1)"></span>
        <span class="legend-label">Маршрут Сократ</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(255, 107, 0, 1)"></span>
        <span class="legend-label">Маршрут Циолковский</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(52, 215, 215, 1)"></span>
        <span class="legend-label">Маршрут Шень Ко</span>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, watch, ref } from 'vue';
import Map from 'ol/Map';
import OSM from 'ol/source/OSM';
import TileLayer from 'ol/layer/Tile';
import View from 'ol/View';
import { fromLonLat } from 'ol/proj';
import 'ol/ol.css';
import { GeoJSON } from 'ol/format';
import { Vector as VectorLayer } from 'ol/layer';
import { Vector as VectorSource } from 'ol/source';
import { Style, Stroke, Fill, Circle as CircleStyle } from 'ol/style';
import { Feature } from 'ol';
import { Point, LineString } from 'ol/geom';

export default {
  name: 'MapWithPoints',
  props: {
    routeSlug: {
      type: String,
      default: null
    },
    points: {
      type: Array,
      default: () => []
    },

    routeColor: {
      type: String,
      default: '#FFB800'
    }
  },
  emits: ['navigate'],
  setup(props, { emit }) {
    let map = null;
    let vectorLayers = [];
    let pointsLayer = null;
    let routePointsLayer = null;
    let pointsRouteLayer = null;
    let selectedFeature = null;

    const showAllRoutes = ref(true);
    const popupVisible = ref(false);
    const popupText = ref('');
    const popupAddress = ref('');
    const popupUrl = ref('');
    const popupPosition = ref({ x: 0, y: 0 });
    const currentPoint = ref(null);

    const routesConfig = [
      { slug: 'pifagor', file: '/ors__v2_directions_{profile}_geojson_post_1733420735317.geojson', color: 'rgba(0, 148, 255, 1)', name: 'Пифагор' },
      { slug: 'avicena', file: '/avicena.geojson', color: 'rgba(243, 211, 46, 1)', name: 'Авиценна' },
      { slug: 'vernadsky', file: '/vernadsky.geojson', color: 'rgba(150, 105, 200, 1)', name: 'Вернадский' },
      { slug: 'mendeleev', file: '/mendeleev.geojson', color: 'rgba(0, 168, 221, 1)', name: 'Менделеев' },
      { slug: 'sokrat', file: '/sokrat.geojson', color: 'rgba(255, 184, 0, 1)', name: 'Сократ' },
      { slug: 'tsiolkovsky', file: '/tsiolkovsky.geojson', color: 'rgba(255, 107, 0, 1)', name: 'Циолковский' },
      { slug: 'shen_ko', file: '/shen_ko.geojson', color: 'rgba(52, 215, 215, 1)', name: 'Шень Ко' },
    ];

    const defaultPointStyle = new Style({
      image: new CircleStyle({
        radius: 7,
        fill: new Fill({ color: '#' + props.routeColor || '#FFB800', }),
        stroke: new Stroke({ color: '#fff', width: 2 }),
      }),
    });

    const selectedPointStyle = new Style({
      image: new CircleStyle({
        radius: 9,
        fill: new Fill({ color: '#FF6B00' }),
        stroke: new Stroke({ color: '#fff', width: 3 }),
      }),
    });

    const clearLayers = () => {
      vectorLayers.forEach(layer => map.removeLayer(layer));
      vectorLayers = [];

      if (pointsLayer) {
        map.removeLayer(pointsLayer);
        pointsLayer = null;
      }

      if (routePointsLayer) {
        map.removeLayer(routePointsLayer);
        routePointsLayer = null;
      }

      if (pointsRouteLayer) {
        map.removeLayer(pointsRouteLayer);
        pointsRouteLayer = null;
      }
    };

    const buildRouteForPoints = async (points) => {
      if (!points || points.length < 2) {
        console.log('Недостаточно точек для построения маршрута');
        return;
      }

      const coordinates = points.map(p => `${p.lon},${p.lat}`).join(';');

      try {
        const response = await fetch(
          `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson`,
          {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json'
            }
          }
        );

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.code !== 'Ok' || !data.routes || data.routes.length === 0) {
          throw new Error('Маршрут не найден');
        }

        const routeCoordinates = data.routes[0].geometry.coordinates;

        const projectedCoordinates = routeCoordinates.map(coord => fromLonLat(coord));

        const lineFeature = new Feature({
          geometry: new LineString(projectedCoordinates)
        });

        const routeStyle = new Style({
          stroke: new Stroke({
            color: '#' + props.routeColor || '#FFB800',
            width: 5,
          }),
        });

        lineFeature.setStyle(routeStyle);

        const vectorSource = new VectorSource({
          features: [lineFeature]
        });

        pointsRouteLayer = new VectorLayer({
          source: vectorSource,
          zIndex: 2
        });

        map.addLayer(pointsRouteLayer);

        const distance = (data.routes[0].distance / 1000).toFixed(2);
        const duration = Math.round(data.routes[0].duration / 60);
        console.log(`Маршрут построен: ${distance} км, ${duration} мин`);

      } catch (error) {
        console.error('Ошибка построения маршрута через OSRM:', error);
        await buildRouteViaORS(points);
      }
    };

    const buildRouteViaORS = async (points) => {
      try {
        const coordinates = points.map(p => [p.lon, p.lat]);

        const body = {
          coordinates: coordinates
        };

        const response = await fetch('https://api.openrouteservice.org/v2/directions/driving-car/geojson', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': '5b3ce3597851110001cf6248YOUR_API_KEY_HERE'
          },
          body: JSON.stringify(body)
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        const geojsonFormat = new GeoJSON();
        const features = geojsonFormat.readFeatures(data, {
          featureProjection: 'EPSG:3857',
        });

        const routeStyle = new Style({
          stroke: new Stroke({
            color: 'rgba(255, 0, 0, 0.8)',
            width: 5,
          }),
        });

        features.forEach((feature) => {
          feature.setStyle(routeStyle);
        });

        const vectorSource = new VectorSource({ features });
        pointsRouteLayer = new VectorLayer({
          source: vectorSource,
          zIndex: 2
        });

        map.addLayer(pointsRouteLayer);
        console.log('Маршрут построен через OpenRouteService');

      } catch (error) {
        console.error('Ошибка построения маршрута через ORS:', error);
      }
    };

    const extractPointsFromGeoJSON = (geoJSONData, routeConfig) => {
      const points = [];

      try {
        if (geoJSONData.features && geoJSONData.features.length > 0) {
          geoJSONData.features.forEach((feature, featureIndex) => {
            if (feature.geometry && feature.geometry.type === 'LineString') {
              const coordinates = feature.geometry.coordinates;

              const importantPoints = [
                coordinates[0],
                coordinates[Math.floor(coordinates.length / 4)],
                coordinates[Math.floor(coordinates.length / 2)],
                coordinates[Math.floor(coordinates.length * 3 / 4)],
                coordinates[coordinates.length - 1]
              ];

              const uniquePoints = Array.from(new Set(importantPoints.map(JSON.stringify))).map(JSON.parse);

              uniquePoints.forEach((coord, index) => {
                const pointTypes = ['Начало', 'Промежуточная', 'Середина', 'Промежуточная', 'Конец'];
                points.push({
                  lon: coord[0],
                  lat: coord[1],
                  name: `${routeConfig.name} - ${pointTypes[index]} ${index + 1}`,
                  address: `Маршрут ${routeConfig.name}`,
                  type: 'route_point',
                  route: routeConfig.slug
                });
              });
            }
          });
        }

        if (geoJSONData.features && geoJSONData.features[0]?.properties?.way_points) {
          const wayPoints = geoJSONData.features[0].properties.way_points;
          const coordinates = geoJSONData.features[0].geometry.coordinates;

          wayPoints.forEach((wayPointIndex, index) => {
            if (coordinates[wayPointIndex]) {
              points.push({
                lon: coordinates[wayPointIndex][0],
                lat: coordinates[wayPointIndex][1],
                name: `${routeConfig.name} - Остановка ${index + 1}`,
                address: `Ключевая точка маршрута ${routeConfig.name}`,
                type: 'way_point',
                route: routeConfig.slug
              });
            }
          });
        }
      } catch (error) {
        console.error('Ошибка при извлечении точек из GeoJSON:', error);
      }

      return points;
    };

    const loadRoute = (routeConfig, shouldLoadPoints = false) => {
      return fetch(routeConfig.file)
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          console.log(`Маршрут ${routeConfig.slug} загружен успешно`);

          const geojsonFormat = new GeoJSON();
          const features = geojsonFormat.readFeatures(data, {
            featureProjection: 'EPSG:3857',
          });

          const routeStyle = new Style({
            stroke: new Stroke({
              color: routeConfig.color,
              width: 4,
            }),
          });

          features.forEach((feature) => {
            feature.setStyle(routeStyle);
          });

          const vectorSource = new VectorSource({ features });
          const vectorLayer = new VectorLayer({
            source: vectorSource,
            zIndex: 1
          });

          map.addLayer(vectorLayer);
          vectorLayers.push(vectorLayer);

          if (shouldLoadPoints) {
            const routePoints = extractPointsFromGeoJSON(data, routeConfig);
            addRoutePointsToMap(routePoints, routeConfig.color);
          }
        })
        .catch((error) => {
          console.error(`Ошибка загрузки ${routeConfig.slug} из ${routeConfig.file}:`, error);
        });
    };

    const addRoutePointsToMap = (points, color) => {
      if (!points || points.length === 0) return;

      const features = points.map((point) => {
        const feature = new Feature({
          geometry: new Point(fromLonLat([point.lon, point.lat])),
        });

        feature.set('properties', point);

        const pointStyle = new Style({
          image: new CircleStyle({
            radius: 6,
            fill: new Fill({ color: color }),
            stroke: new Stroke({ color: '#fff', width: 2 }),
          }),
        });

        feature.setStyle(pointStyle);
        return feature;
      });

      if (!routePointsLayer) {
        const vectorSource = new VectorSource({ features });
        routePointsLayer = new VectorLayer({
          source: vectorSource,
          zIndex: 3,
        });
        map.addLayer(routePointsLayer);
      } else {
        routePointsLayer.getSource().addFeatures(features);
      }
    };

    const loadPoints = () => {
      if (!props.points || props.points.length === 0) {
        return;
      }

      const features = props.points.map((point, index) => {
        console.log(`  Координаты: lon=${point.lon}, lat=${point.lat}`);

        const feature = new Feature({
          geometry: new Point(fromLonLat([point.lon, point.lat])),
        });

        feature.set('properties', point);
        feature.setStyle(defaultPointStyle);

        return feature;
      });

      const vectorSource = new VectorSource({ features });
      pointsLayer = new VectorLayer({
        source: vectorSource,
        zIndex: 4,
      });

      map.addLayer(pointsLayer);
      console.log('Слоев на карте всего:', map.getLayers().getLength());

      buildRouteForPoints(props.points);
    };

    const loadMap = () => {
      clearLayers();

   
      if (props.points && props.points.length > 0) {
        showAllRoutes.value = false;
        loadPoints();
        return;
      }

     
      if (props.routeSlug) {
        showAllRoutes.value = false;
        const routeConfig = routesConfig.find(r => r.slug === props.routeSlug);
        if (routeConfig) {
          loadRoute(routeConfig, true);
        }
      } else {
        showAllRoutes.value = true;
        const loadRoutesSequentially = async () => {
          for (const routeConfig of routesConfig) {
            await loadRoute(routeConfig, false);
          }
        };
        loadRoutesSequentially();
      }
    };

    const hidePopup = () => {
      popupVisible.value = false;
      if (selectedFeature) {
        const pointType = selectedFeature.get('properties')?.type;
        if (pointType === 'route_point' || pointType === 'way_point') {
          const routeConfig = routesConfig.find(r => r.slug === selectedFeature.get('properties')?.route);
          if (routeConfig) {
            const routePointStyle = new Style({
              image: new CircleStyle({
                radius: 6,
                fill: new Fill({ color: routeConfig.color }),
                stroke: new Stroke({ color: '#fff', width: 2 }),
              }),
            });
            selectedFeature.setStyle(routePointStyle);
          }
        } else {
          selectedFeature.setStyle(defaultPointStyle);
        }
        selectedFeature = null;
      }
      currentPoint.value = null;
    };

    const navigateToPage = () => {
      const point = currentPoint.value;
      hidePopup();
      setTimeout(() => {
        emit('navigate', point);
      }, 300);
    };

    const openPopupByName = (name) => {
      if (!pointsLayer && !routePointsLayer) return;

      let feature = null;

      if (pointsLayer) {
        const features = pointsLayer.getSource().getFeatures();
        feature = features.find(f => f.get('properties').name === name);
      }

      if (!feature && routePointsLayer) {
        const features = routePointsLayer.getSource().getFeatures();
        feature = features.find(f => f.get('properties').name === name);
      }

      if (feature) {
        if (selectedFeature) {
          const prevPointType = selectedFeature.get('properties')?.type;
          if (prevPointType === 'route_point' || prevPointType === 'way_point') {
            const routeConfig = routesConfig.find(r => r.slug === selectedFeature.get('properties')?.route);
            if (routeConfig) {
              const routePointStyle = new Style({
                image: new CircleStyle({
                  radius: 6,
                  fill: new Fill({ color: routeConfig.color }),
                  stroke: new Stroke({ color: '#fff', width: 2 }),
                }),
              });
              selectedFeature.setStyle(routePointStyle);
            }
          } else {
            selectedFeature.setStyle(defaultPointStyle);
          }
        }

        feature.setStyle(selectedPointStyle);
        selectedFeature = feature;

        const geometry = feature.getGeometry();
        if (geometry instanceof Point) {
          const mapElement = document.getElementById('map');
          if (!mapElement) return;

          const coordinate = geometry.getCoordinates();
          const pixel = map.getPixelFromCoordinate(coordinate);
          const mapRect = mapElement.getBoundingClientRect();

          popupPosition.value = {
            x: mapRect.left + window.pageXOffset + pixel[0] - 140,
            y: mapRect.top + window.pageYOffset + pixel[1] - 190,
          };

          const properties = feature.get('properties');
          popupText.value = properties.name;
          popupAddress.value = properties.address;
          popupUrl.value = properties.url;
          currentPoint.value = properties;
          popupVisible.value = true;
        }
      }
    };

    onMounted(() => {
      map = new Map({
        target: 'map',
        layers: [
          new TileLayer({
            source: new OSM(),
          }),
        ],
        view: new View({
          center: fromLonLat([55.9721, 54.7388]),
          zoom: 13,
        }),
      });

      map.on('singleclick', (evt) => {
        const mapElement = document.getElementById('map');
        if (!mapElement) return;

        map.forEachFeatureAtPixel(evt.pixel, (feature) => {
          const properties = feature.get('properties');
          if (properties && properties.name) {
            if (selectedFeature) {
              const prevPointType = selectedFeature.get('properties')?.type;
              if (prevPointType === 'route_point' || prevPointType === 'way_point') {
                const routeConfig = routesConfig.find(r => r.slug === selectedFeature.get('properties')?.route);
                if (routeConfig) {
                  const routePointStyle = new Style({
                    image: new CircleStyle({
                      radius: 6,
                      fill: new Fill({ color: routeConfig.color }),
                      stroke: new Stroke({ color: '#fff', width: 2 }),
                    }),
                  });
                  selectedFeature.setStyle(routePointStyle);
                }
              } else {
                selectedFeature.setStyle(defaultPointStyle);
              }
            }

            feature.setStyle(selectedPointStyle);
            selectedFeature = feature;

            const geometry = feature.getGeometry();
            if (geometry instanceof Point) {
              const coordinate = geometry.getCoordinates();
              const pixel = map.getPixelFromCoordinate(coordinate);
              const mapRect = mapElement.getBoundingClientRect();

              popupPosition.value = {
                x: mapRect.left + window.pageXOffset + pixel[0] - 140,
                y: mapRect.top + window.pageYOffset + pixel[1] - 190,
              };

              popupText.value = properties.name;
              popupAddress.value = properties.address;
              popupUrl.value = properties.url;
              currentPoint.value = properties;
              popupVisible.value = true;
            }
          }
        });
      });

      loadMap();
    });

    watch(() => [props.routeSlug, props.points], () => {
      if (map) {
        loadMap();
      }
    }, { deep: true });

    return {
      showAllRoutes,
      popupVisible,
      popupText,
      popupAddress,
      popupUrl,
      popupPosition,
      hidePopup,
      navigateToPage,
      openPopupByName,
    };
  },
};
</script>

<style scoped>
.map {
  width: 100%;
  height: 850px;
  border: 1px solid #ccc;
  position: relative;
}

.custom-popup {
  position: fixed;
  background: white;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #ccc;
  box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
  width: 280px;
  z-index: 1000;
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
  margin-bottom: 8px;
}

.popup-subtitle {
  font-size: 12px;
  color: gray;
  margin-bottom: 4px;
}

.popup-content {
  font-size: 14px;
  margin-bottom: 12px;
}

.popup-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
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
  text-decoration: none;
  color: inherit;
}

.popup-btn:hover {
  background: #e0e0e0;
}

.legend {
  position: absolute;
  top: 60px;
  right: 10px;
  background-color: rgba(255, 255, 255, 0.9);
  padding: 10px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  z-index: 1000;
}

.legend-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
}

.legend-item:last-child {
  margin-bottom: 0;
}

.legend-line {
  width: 40px;
  height: 4px;
  display: inline-block;
  margin-right: 10px;
  border-radius: 2px;
}

.legend-label {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}
</style>