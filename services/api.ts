import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { STORAGE_KEYS, ERROR_MESSAGES } from '../constants/app';
import ENV from '../config/env';

const api = axios.create({
  baseURL: ENV.API_URL,
  timeout: ENV.API_TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Intercepteur de requête - ajoute le token JWT
api.interceptors.request.use(
  async (config) => {
    const token = await AsyncStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Intercepteur de réponse - gestion des erreurs
api.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    if (!error.response) {
      throw new Error(ERROR_MESSAGES.NETWORK_ERROR);
    }

    const { status } = error.response;

    // Token expiré ou invalide
    if (status === 401) {
      await AsyncStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
      await AsyncStorage.removeItem(STORAGE_KEYS.USER_DATA);
      throw new Error(ERROR_MESSAGES.UNAUTHORIZED);
    }

    // Erreur serveur
    if (status >= 500) {
      throw new Error(ERROR_MESSAGES.SERVER_ERROR);
    }

    throw error;
  }
);

export default api;
