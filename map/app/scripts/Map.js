var Map = function ($dom) {
    'use strict';
    var osm = new OpenLayers.Layer.OSM();
    this.map = new OpenLayers.Map('map', {
        layers: [osm]
    });

    var center = new OpenLayers.LonLat(116.45, 28.75).transform('EPSG:4326', 'EPSG:900913');

    this.map.setCenter(center, 5);
};

