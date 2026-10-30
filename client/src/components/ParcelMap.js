import { useState } from 'react';
import Map, { Layer, NavigationControl, ScaleControl, Source } from 'react-map-gl/maplibre';
import 'maplibre-gl/dist/maplibre-gl.css';
import { MIN_PARCEL_ZOOM, PARCEL_TILES_URL } from '../api/services/parcelService';
import { LAND_TYPE_COLORS, UNKNOWN_LAND_TYPE_COLOR } from '../constants/landTypes';

const INITIAL_VIEW = {
  longitude: 15.3615,
  latitude: 50.4372,
  zoom: 15,
};

const BASE_MAP_STYLE = {
  version: 8,
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

function ParcelMap({ selectedParcelId, onSelectParcel }) {
  const [zoom, setZoom] = useState(INITIAL_VIEW.zoom);
  const [cursor, setCursor] = useState('');

  function handleClick(event) {
    if (event.features && event.features.length > 0) {
      onSelectParcel(event.features[0].id);
    } else {
      onSelectParcel(null);
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
        interactiveLayerIds={['parcels-fill']}
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

        <NavigationControl position="top-right" />
        <ScaleControl position="bottom-left" />
      </Map>

      {zoom < MIN_PARCEL_ZOOM && <div className="zoom-hint">Přibližte mapu pro zobrazení parcel</div>}
    </div>
  );
}

export default ParcelMap;
