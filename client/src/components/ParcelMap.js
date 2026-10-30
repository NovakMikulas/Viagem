import { useEffect, useState } from 'react';
import Map, { Layer, NavigationControl, ScaleControl, Source } from 'react-map-gl/maplibre';
import 'maplibre-gl/dist/maplibre-gl.css';
import { getCadastralUnits } from '../api/services/cadastralUnitService';
import { MIN_PARCEL_ZOOM, PARCEL_TILES_URL } from '../api/services/parcelService';
import { LAND_TYPE_COLORS, UNKNOWN_LAND_TYPE_COLOR } from '../constants/landTypes';

// Celý okres Jičín
const INITIAL_VIEW = {
  longitude: 15.38,
  latitude: 50.43,
  zoom: 10,
};

const BASE_MAP_STYLE = {
  version: 8,
  glyphs: 'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf',
  sources: {
    osm: {
      type: 'raster',
      tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
      tileSize: 256,
      maxzoom: 19,
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> přispěvatelé',
    },
  },
  layers: [{ id: 'osm', type: 'raster', source: 'osm' }],
};

function landTypeColorExpression() {
  const expression = ['match', ['get', 'land_type']];
  for (const code in LAND_TYPE_COLORS) {
    expression.push(code, LAND_TYPE_COLORS[code]);
  }
  expression.push(UNKNOWN_LAND_TYPE_COLOR);
  return expression;
}

const fillLayer = {
  id: 'parcels-fill',
  type: 'fill',
  'source-layer': 'parcels',
  paint: {
    'fill-color': landTypeColorExpression(),
    'fill-opacity': 0.5,
  },
};

const outlineLayer = {
  id: 'parcels-outline',
  type: 'line',
  'source-layer': 'parcels',
  paint: {
    'line-color': '#5a3e2b',
    'line-width': ['interpolate', ['linear'], ['zoom'], 14, 0.3, 18, 1],
  },
};

const selectedOutlineLayer = {
  id: 'parcels-selected',
  type: 'line',
  'source-layer': 'parcels',
  paint: {
    'line-color': '#d7191c',
    'line-width': 3,
  },
};

// Hranice k.ú. jsou vidět vždy, výplň a popisky jen při oddálení, kdy se parcely nezobrazují
const cadastralUnitFillLayer = {
  id: 'cadastral-units-fill',
  type: 'fill',
  maxzoom: MIN_PARCEL_ZOOM,
  filter: ['!=', ['geometry-type'], 'Point'],
  paint: {
    'fill-color': '#3b6ea5',
    'fill-opacity': 0.1,
  },
};

const cadastralUnitOutlineLayer = {
  id: 'cadastral-units-outline',
  type: 'line',
  filter: ['!=', ['geometry-type'], 'Point'],
  paint: {
    'line-color': '#3b6ea5',
    'line-width': ['interpolate', ['linear'], ['zoom'], 9, 1, 14, 2.5],
  },
};

const cadastralUnitLabelLayer = {
  id: 'cadastral-units-label',
  type: 'symbol',
  maxzoom: MIN_PARCEL_ZOOM,
  filter: ['==', ['geometry-type'], 'Point'],
  layout: {
    'text-field': ['get', 'name'],
    'text-font': ['Open Sans Semibold'],
    'text-size': ['interpolate', ['linear'], ['zoom'], 9, 10, 13, 14],
  },
  paint: {
    'text-color': '#1f3d5c',
    'text-halo-color': '#fff',
    'text-halo-width': 1.5,
  },
};

function ParcelMap({ selectedParcelId, onSelectParcel }) {
  const [zoom, setZoom] = useState(INITIAL_VIEW.zoom);
  const [cursor, setCursor] = useState('');
  const [cadastralUnits, setCadastralUnits] = useState(null);

  useEffect(() => {
    getCadastralUnits()
      .then(setCadastralUnits)
      .catch((err) => console.error('Hranice k.ú. se nepodařilo načíst', err));
  }, []);

  function handleClick(event) {
    const feature = event.features && event.features[0];

    if (!feature) {
      onSelectParcel(null);
    } else if (feature.layer.id === 'cadastral-units-fill') {
      zoomToCadastralUnit(event.target, feature);
    } else {
      onSelectParcel(feature.id);
    }
  }

  let selectedFilter = ['==', ['id'], -1];
  if (selectedParcelId !== null) {
    selectedFilter = ['==', ['id'], selectedParcelId];
  }

  return (
    <div className="parcel-map">
      <Map
        initialViewState={INITIAL_VIEW}
        mapStyle={BASE_MAP_STYLE}
        interactiveLayerIds={['parcels-fill', 'cadastral-units-fill']}
        cursor={cursor}
        onClick={handleClick}
        onMouseEnter={() => setCursor('pointer')}
        onMouseLeave={() => setCursor('')}
        onZoomEnd={(event) => setZoom(event.viewState.zoom)}
      >
        <Source
          id="parcels"
          type="vector"
          tiles={[PARCEL_TILES_URL]}
          minzoom={MIN_PARCEL_ZOOM}
          maxzoom={18}
          attribution='Parcely © <a href="https://cuzk.gov.cz">ČÚZK</a>'
        >
          <Layer {...fillLayer} />
          <Layer {...outlineLayer} />
          <Layer {...selectedOutlineLayer} filter={selectedFilter} />
        </Source>

        {cadastralUnits && (
          <Source id="cadastral-units" type="geojson" data={cadastralUnits}>
            <Layer {...cadastralUnitFillLayer} />
            <Layer {...cadastralUnitOutlineLayer} />
            <Layer {...cadastralUnitLabelLayer} />
          </Source>
        )}

        <NavigationControl position="top-right" />
        <ScaleControl position="bottom-left" />
      </Map>

      {zoom < MIN_PARCEL_ZOOM && (
        <div className="zoom-hint">Klikněte na katastrální území nebo přibližte mapu pro zobrazení parcel</div>
      )}
    </div>
  );
}

// Přiblíží na celé k.ú., ale nejméně na zoom, od kterého jsou vidět parcely
function zoomToCadastralUnit(map, feature) {
  // Vnořené vlastnosti GeoJSON prvků vrací MapLibre při kliknutí jako JSON řetězec
  let bbox = feature.properties.bbox;
  if (typeof bbox === 'string') {
    bbox = JSON.parse(bbox);
  }
  const camera = map.cameraForBounds(bbox, { padding: 40 });

  map.flyTo({
    center: camera.center,
    zoom: Math.max(camera.zoom, MIN_PARCEL_ZOOM),
  });
}

export default ParcelMap;
