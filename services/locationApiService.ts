// services/locationApiService.ts
import api from './api';

interface LocationUpdate {
  latitude: number;
  longitude: number;
  speed?: number | null;
  heading?: number | null;
}

export const sendLocationToBackend = async (location: LocationUpdate): Promise<void> => {
  try {
    const response = await api.post('/delivery-person/location/update', {
      latitude: location.latitude,
      longitude: location.longitude,
      speed: location.speed,
      heading: location.heading,
    });
    console.log('✅ Position envoyée au serveur:', response);
  } catch (error) {
    console.error('❌ Erreur envoi position:', error);
  }
};

export const setOnlineStatus = async (isOnline: boolean): Promise<void> => {
  try {
    await api.post('/delivery-person/location/status', { is_online: isOnline });
    console.log(`✅ Statut: ${isOnline ? 'EN LIGNE' : 'HORS LIGNE'}`);
  } catch (error) {
    console.error('❌ Erreur changement statut:', error);
  }
};