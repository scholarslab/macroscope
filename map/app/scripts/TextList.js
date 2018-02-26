var TextList = function ($dom, $map) {
    'use strict';
    var rootDom = $('ul', $dom);
    // load texts from root
    // for each item, add a layer to the map
    // Bind the list item to the map control

    var texts = {};

    var style = new OpenLayers.StyleMap({
        'default' : {
            pointRadius: '5',
            fillColor: 'red',
            strokeColor: 'red',
            fillOpacity: 0.5
        }
    });

    var format = new OpenLayers.Format.GeoJSON({
        externalProjection: 'EPSG:4326',
        internalProjection: 'EPSG:900913'
    });

    var _parseTextList = function ($data, $error) {

        var layers = [];

        $data.features.forEach(function ($text) {
            var newDom = $('#book-template').clone();
            newDom.attr('id', $text.properties.title);
            newDom.text($text.properties.title);

            rootDom.append(newDom);

            var layer = new OpenLayers.Layer.Vector($text.properties.title, {
//                strategy: OpenLayers.Strategy.Cluster(),
                styleMap: style
            });
            texts[$text.properties.title] = layer;
            
            layer.addFeatures(format.read($text));
            layers.push(layer);
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
                    alert('Layer removed!')
                }
            });
            $map.map.addLayer(layer);

            newDom.click(function ($event) {
                var target = $($event.target),
                    id     = target.attr('id'),
                    layer  = texts[id]
                    ;
                
                layer.setVisibility(!layer.getVisibility());
                target.toggleClass('inactive');
                return;
                if (layer.map) {
//                    layer.map.removeLayer(layer);
                    target.addClass('inactive');
                } else {
                    $map.map.addLayer(layer);
                    target.removeClass('inactive');
                }
            })
        });        

        var selectFeature = new OpenLayers.Control.SelectFeature(layers, {
            clickout: true
        });

        $map.map.addControl(selectFeature);
        selectFeature.activate();
    };

    $.get('text_list_data/text.geojson', {}, _parseTextList, 'json');
};

