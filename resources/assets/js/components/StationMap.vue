<template>
  <div>
    <gmap-map
      ref="map"
      :center="{lat:parseFloat(center_lat), lng:parseFloat(center_lng)}"
      :zoom="14"
      style="width: 100%; height: 500px"
    >
      <gmap-marker
        :position="{lat:parseFloat(center_lat), lng:parseFloat(center_lng)}"
        :clickable="true"
        :icon="markerUrl"
        :draggable="false"
      ></gmap-marker>
        <gmap-marker
        :key="index"
        v-for="(m, index) in markers"
        :position="m.position"
        :draggable="false"
        @click="center=m.position"
      ></gmap-marker>
    </gmap-map>
    <div>
      <button class="btn btn-default" v-on:click="calculateHotSpots()">Identificar puntos de conflicto</button>
      <button class="btn btn-default" v-on:click="generateRoute()">Generar Ruta</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { loaded } from 'vue2-google-maps';
import clustersKmeans from '@turf/clusters-kmeans';
import distance from '@turf/distance';
import {featureCollection, point} from '@turf/turf';

const shuffleArray = (arr) => arr.sort(() => (Math.random() - 0.5));

export default {
  props: ['center_lat', 'center_lng', 'id'],
  data () {
    return {
      markers: [],
      markerUrl: `/markers/police.png`,
    }
  },
  async mounted () {
    await loaded;
    let vm = this;
    const vmt = await axios.get('/api/vmt');

    const incidents = await axios.get('/api/incidents');
    const station = await axios.get(`/api/stations/${this.id}`);
    const geoJson = JSON.parse(vmt.data.boundary);
    const json = this.$refs.map.$mapObject.data.addGeoJson(geoJson);

    const jurisdiction = new google.maps.Polygon({paths: json[0].getGeometry().getArray()})

    vm.points = _.filter(incidents.data, function(incindent) {
      const location = new google.maps.LatLng(incindent.lat, incindent.long);
      return google.maps.geometry.poly.containsLocation(location, jurisdiction)
    });

    let points = _.map(vm.points, function(incindent) {
      return new google.maps.LatLng(incindent.lat, incindent.long);
    });

    vm.heatmap = new google.maps.visualization.HeatmapLayer({
      data: points,
    });
    vm.heatmap.setMap(this.$refs.map.$mapObject);
  },
  methods: {
    calculateHotSpots () {
      let vm = this;
      let points = _.map(this.points, function(incindent) {
        return point([incindent.lat, incindent.long]);
      });
      let hotspots = [];
      let clusters = clustersKmeans(featureCollection(points), 120);
      _.each(clusters.features, (c) => {
        hotspots[c.properties.cluster] = c.properties.centroid;
      });
      _.each(hotspots, function(hotspot) {
        vm.markers.push({
          position: {lat: hotspot[0], lng: hotspot[1]}
        });
      });
    },
    async generateRoute () {

      let coords = this.markers.filter((m)=>{
        let from = point([parseFloat(this.center_lat), parseFloat(this.center_lng)]);
        let to = point([parseFloat(m.position.lat), parseFloat(m.position.lng)]);
        return distance(from, to) < 0.8;
      });
      coords = shuffleArray(coords);
      coords = coords.slice(0,6);

      let coordinates = _.reduce(coords, (string, m) => {
        return `${string};${m.position.lng},${m.position.lat}`;
      }, `${this.center_lng},${this.center_lat}`);
      const route = await axios.get(`https://api.mapbox.com/optimized-trips/v1/mapbox/driving/${coordinates}?roundtrip=true&access_token=${mapbox_key}`);
      var decodedPath = google.maps.geometry.encoding.decodePath(route.data.trips[0].geometry);

      const routePath = new google.maps.Polyline({
        path:decodedPath,
        strokeColor: '#FF0000',
        fillOpacity: 0.35,
      });

      routePath.setMap(this.$refs.map.$mapObject);
    }
  }

}
</script>
<style>
  .btn {
    margin-top: 10px;
  }
</style>