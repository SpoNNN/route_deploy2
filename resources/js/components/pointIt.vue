<template>
    <div>
      <div id="map" class="map" tabindex="0"></div>
    </div>
  </template>
  
<script>
    import { onMounted } from 'vue';
    import Map from 'ol/Map';
    import OSM from 'ol/source/OSM';
    import TileLayer from 'ol/layer/Tile';
    import View from 'ol/View';
    import { fromLonLat } from 'ol/proj';
    import 'ol/ol.css';
    import { Point } from 'ol/geom'; 
    import { Feature } from 'ol'; 
    import { Vector as VectorLayer } from 'ol/layer'; 
    import { Vector as VectorSource } from 'ol/source'; 
    import { Style, Fill, Stroke, Circle } from 'ol/style'; 
  
    export default {
      name: 'MapComponent',
      setup() {
        let map = null;
  
        const zoomIn = () => {
          const view = map.getView();
          const zoom = view.getZoom();
          view.setZoom(zoom + 1);
        };
  
        const zoomOut = () => {
          const view = map.getView();
          const zoom = view.getZoom();
          view.setZoom(zoom - 1);
        };
  
        onMounted(() => {
     
          const coordinates = [
            [55.936081, 54.720401],
            [55.940732, 54.724981],
            [55.942299, 54.729234],
            [55.947889, 54.726752],
            [55.960789, 54.729599],
            [55.983218, 54.726248],
            [56.007203, 54.716375],
            [55.989414, 54.741021],
          ];
  
       
          const vectorSource = new VectorSource();
  
    
          const pointStyle = new Style({
            image: new Circle({
              radius: 7, 
              fill: new Fill({
                color: 'rgba(0, 148, 255, 1)', 
              }),
              stroke: new Stroke({
                color: 'black', 
                width: 1, 
              }),
            }),
          });
  
      
          coordinates.forEach(coord => {
            const point = new Point(fromLonLat(coord));
            const pointFeature = new Feature({
              geometry: point,
            });
            pointFeature.setStyle(pointStyle);
            vectorSource.addFeature(pointFeature); 
          });
  
    
          const vectorLayer = new VectorLayer({
            source: vectorSource,
          });
  
      
          map = new Map({
            target: 'map',
            layers: [
              new TileLayer({
                source: new OSM(),
              }),
              vectorLayer, 
            ],
            view: new View({
              center: fromLonLat([55.9721, 54.7388]), // Центр карты (Уфа)
              zoom: 13, // Начальный зум
            }),
          });
        });
  
        return {
          zoomIn,
          zoomOut,
        };
      },
    };
</script>
  
<style>
    .map {
      width: 100%;
      height: 740px;
      border: 1px solid #ccc;
    }
    .controls {
      margin-top: 10px;
    }
</style>
  

