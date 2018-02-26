import random
import json

cities = [
    ('Los Angeles', [34.052234,-118.243685,],),
    ('San Diego', [32.715738 , -117.161084,],),
    ('Modesto', [37.639097 , -120.996878,],),
    ('Bakersfield', [35.373292 , -119.018712,],),
    ('Santa Monica', [34.019454 , -118.491191,],),
]

texts = {'texts' : []}

for index in xrange(1,20):
    book = {
        "type" : "FeatureCollection",
        "properties" : {
            'title' : "Book {0}".format(index)
        },
        "features" : [],
    }

    for point_counter in xrange(random.randrange(5, 30)):
        name, center = random.choice(cities)
        book['features'].append({
            'type' : 'Feature',
            'properties' : {
                'page' : random.randrange(1, 400),
                'text' : 'and then we went to <b>{0}</b> and ...'.format(name),
            },
            'geometry' : {
                'type' : 'Point',
                'coordinates' : [center,],
            }
        })
    out_file = open('sample_data/{0}.geojson'.format(index), 'w')
    json.dump(book, out_file)
    texts['texts'].append({'title' : book['properties']['title']})

root_file = open('sample_data/root.json', 'w')
json.dump(texts, root_file)
