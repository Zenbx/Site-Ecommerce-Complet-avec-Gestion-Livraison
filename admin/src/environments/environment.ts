// src/environments/environment.ts

export const environment = {
  production: false,
  apiUrl: 'http://192.168.1.103:8000/api/admin',
  apiAuthUrl: 'http://192.168.1.103:8000/api/auth/admin',
  wsUrl: 'ws://192.168.1.103:8000/api', // WebSocket URL
  mapboxToken: 'YOUR_MAPBOX_TOKEN', // Pour les cartes
  googleMapsApiKey: 'YOUR_GOOGLE_MAPS_KEY' // Alternative à Mapbox
};