import api from '../axios';

export async function getCadastralUnits() {
  const response = await api.get('/api/cadastral-units');
  return response.data;
}
