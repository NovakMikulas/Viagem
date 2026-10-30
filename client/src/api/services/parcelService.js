import api, { API_URL } from '../axios';

export const PARCEL_TILES_URL = API_URL + '/api/tiles/{z}/{x}/{y}.pbf';

export const MIN_PARCEL_ZOOM = 14;

export async function getParcel(id) {
  const response = await api.get('/api/parcels/' + id);
  return response.data;
}
