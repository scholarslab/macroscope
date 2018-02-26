var TextList = function ($dom, $map) {
    'use strict';
    var rootDom = $('ul', $dom);
    // load texts from root
    // for each item, add a layer to the map
    // Bind the list item to the map control

    var texts = {};

    var style = new OpenLayers.StyleMap({
        'default' : {
            pointRadius: '${proportion}',
            fillColor: 'red',
            strokeColor: 'red',
            fillOpacity: 0.5
        },
        'select' : {
            pointRadius: '${proportion}',
            fillColor: 'blue',
            strokeColor: 'blue',
            fillOpacity: 0.5
        }
    });

    var format = new OpenLayers.Format.GeoJSON({
        externalProjection: 'EPSG:4326',
        internalProjection: 'EPSG:900913'
    });

    var _parseTextList = function ($data, $error) {

        var layers = [];

        for (var i = 0; i < $data.features.length; i++) {
            var feature = $data.features[i];
            var proportion = 3;

            if (feature.properties.lines.length > 150) {
                proportion = 3 + (feature.properties.lines.length / 1011) * 17
            }

            feature.properties.proportion = proportion;

        }

        var layer = new OpenLayers.Layer.Vector("Text", {
            strategies: [
                new OpenLayers.Strategy.Cluster()
            ],
            styleMap: style
        });

        layer.addFeatures(format.read($data));

        layer.events.on({
            featureselected: function ($e) {
                var popup = new OpenLayers.Popup('chicken', 
                    new OpenLayers.LonLat(
                        $e.feature.geometry.x,
                        $e.feature.geometry.y
                    ),
                    new OpenLayers.Size(300, 100),
                    '<ol><li>' + $e.feature.attributes.lines.join('</li><li>') + '</li></ol>',
                    true
                );
                $map.map.addPopup(popup);
            }, removelayer: function ($e) {
                alert('Layer removed!');
            }
        });

        $map.map.addLayer(layer);

        var selectFeature = new OpenLayers.Control.SelectFeature(layer, {
            clickout: true
        });

        $map.map.addControl(selectFeature);
        selectFeature.activate();

    };

    $.get('text_list_data/text.geojson', {}, _parseTextList, 'json');
};

