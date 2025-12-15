// Configuration des variables d'environnement

const ENV = {
  // URL de l'API - à modifier selon l'environnement
  API_URL: 'http://192.168.1.188:9000',
  
  // Timeout des requêtes HTTP (en ms)
  API_TIMEOUT: 30000,
  
  // Intervalle de mise à jour GPS (en ms)
  GPS_UPDATE_INTERVAL: 30000, // 30 secondes
  
  // Timeout de session (en minutes)
  SESSION_TIMEOUT: 30,
  
  // Configuration des retry pour les requêtes échouées
  RETRY_ATTEMPTS: 3,
  RETRY_DELAYS: [5000, 10000, 20000], // 5s, 10s, 20s
  
  // Configuration GPS
  GPS_ACCURACY: 'high', // 'high' | 'balanced' | 'low'
  GPS_DISTANCE_FILTER: 10, // mètres
  
  // Compression d'images
  IMAGE_QUALITY: 0.7,
  IMAGE_MAX_WIDTH: 1024,
  IMAGE_MAX_HEIGHT: 1024,
  
  // Pusher/WebSocket (si utilisé)
  PUSHER_KEY: 'your_pusher_key',
  PUSHER_CLUSTER: 'eu',
  
  // Google Maps API Key
  GOOGLE_MAPS_API_KEY: 'your_google_maps_api_key',
  
  // Pagination
  ITEMS_PER_PAGE: 20,
};

export default ENV;
