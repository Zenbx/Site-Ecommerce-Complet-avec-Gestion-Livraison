// src/environments/environment.ts

export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api', // URL de votre backend Laravel
  wsUrl: 'ws://localhost:8000', // WebSocket URL
  mapboxToken: 'YOUR_MAPBOX_TOKEN', // Pour les cartes (optionnel)
  googleMapsApiKey: 'YOUR_GOOGLE_MAPS_KEY' // Alternative à Mapbox
};