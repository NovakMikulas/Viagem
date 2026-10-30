export const LAND_TYPE_COLORS = {
  ArableGround: '#e9d98b',
  Hopgarden: '#9cbf5b',
  Vineyard: '#b58ccc',
  Garden: '#b5dc8f',
  Orchard: '#86c06c',
  Grassland: '#cfe8a9',
  Forest: '#4f9150',
  WaterArea: '#86bde8',
  BuiltUpArea: '#e3907f',
  OtherArea: '#cfc9bd',
};

export const UNKNOWN_LAND_TYPE_COLOR = '#bbbbbb';

export function getLandTypeColor(code) {
  return LAND_TYPE_COLORS[code] || UNKNOWN_LAND_TYPE_COLOR;
}
