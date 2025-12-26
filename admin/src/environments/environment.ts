// src/environments/environment.ts

export const environment = {
  production: false,
  apiUrl: 'http://127.0.0.1:8000/api',
  wsUrl: 'ws://127.0.0.1:8000/api', // WebSocket URL
  mapboxToken: 'YOUR_MAPBOX_TOKEN', // Pour les cartes
  googleMapsApiKey: 'YOUR_GOOGLE_MAPS_KEY' // Alternative à Mapbox
};