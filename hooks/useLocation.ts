import { useState, useEffect, useRef } from 'react';
import * as Location from 'expo-location';
import { Alert } from 'react-native';
import ENV from '../config/env';
import { sendLocationToBackend, setOnlineStatus } from '../services/locationApiService';

interface LocationCoords {
  latitude: number;
  longitude: number;
  speed: number | null;
  heading: number | null;
}

export const useLocation = () => {
  const [location, setLocation] = useState<LocationCoords | null>(null);
  const [permission, setPermission] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [isTracking, setIsTracking] = useState<boolean>(false);
  
  const subscriptionRef = useRef<Location.LocationSubscription | null>(null);
  const sendIntervalRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const currentLocationRef = useRef<LocationCoords | null>(null); // FIX: Ref pour position actuelle
  const lastSentLocationRef = useRef<LocationCoords | null>(null);

  useEffect(() => {
    requestPermission();
    
    // FIX: Cleanup correct
    return () => {
      if (subscriptionRef.current) {
        subscriptionRef.current.remove();
      }
      if (sendIntervalRef.current) {
        clearInterval(sendIntervalRef.current);
      }
    };
  }, []);

  const requestPermission = async () => {
    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      setPermission(status === 'granted');
      if (status !== 'granted') {
        setError('Permission de localisation refusée');
        Alert.alert('Permission requise', 'La localisation est nécessaire pour les livraisons');
      }
    } catch (err) {
      setError('Erreur demande permission');
    }
  };

  const getCurrentLocation = async (): Promise<LocationCoords | null> => {
    if (!permission) {
      await requestPermission();
      if (!permission) return null;
    }

    try {
      const loc = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.High,
      });
      const coords: LocationCoords = {
        latitude: loc.coords.latitude,
        longitude: loc.coords.longitude,
        speed: loc.coords.speed,
        heading: loc.coords.heading,
      };
      setLocation(coords);
      currentLocationRef.current = coords; // FIX: Mettre à jour la ref
      return coords;
    } catch (err) {
      setError('Erreur obtention position');
      return null;
    }
  };

  // Calculer distance entre 2 points (en mètres)
  const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371e3;
    const φ1 = (lat1 * Math.PI) / 180;
    const φ2 = (lat2 * Math.PI) / 180;
    const Δφ = ((lat2 - lat1) * Math.PI) / 180;
    const Δλ = ((lon2 - lon1) * Math.PI) / 180;
    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  };

  // FIX: Fonction pour envoyer la position au backend
  const sendCurrentLocation = async (coords: LocationCoords) => {
    // Ne pas envoyer si la position n'a pas bougé de plus de 5 mètres
    if (lastSentLocationRef.current) {
      const distance = calculateDistance(
        lastSentLocationRef.current.latitude,
        lastSentLocationRef.current.longitude,
        coords.latitude,
        coords.longitude
      );
      if (distance < 5) {
        console.log('⏭️ Position n\'a pas assez bougé, skip');
        return;
      }
    }

    await sendLocationToBackend(coords);
    lastSentLocationRef.current = coords;
  };

  const startTracking = async (callback?: (coords: LocationCoords) => void) => {
    if (!permission) {
      Alert.alert('Erreur', 'Permission de localisation requise');
      return;
    }

    if (isTracking) {
      console.log('⚠️ Tracking déjà actif');
      return;
    }

    try {
      console.log('🚀 Démarrage du tracking...');
      
      // Mettre le statut en ligne
      await setOnlineStatus(true);
      setIsTracking(true);

      // Démarrer le tracking GPS
      subscriptionRef.current = await Location.watchPositionAsync(
        {
          accuracy: Location.Accuracy.High,
          timeInterval: ENV.GPS_UPDATE_INTERVAL,
          distanceInterval: ENV.GPS_DISTANCE_FILTER,
        },
        (loc) => {
          const coords: LocationCoords = {
            latitude: loc.coords.latitude,
            longitude: loc.coords.longitude,
            speed: loc.coords.speed,
            heading: loc.coords.heading,
          };
          
          console.log('📍 Position mise à jour:', coords.latitude.toFixed(6), coords.longitude.toFixed(6));
          
          // FIX: Mettre à jour à la fois l'état et la ref
          setLocation(coords);
          currentLocationRef.current = coords;
          
          if (callback) callback(coords);
        }
      );

      // FIX: Envoyer la position au serveur toutes les 10-15 secondes
      sendIntervalRef.current = setInterval(() => {
        const currentCoords = currentLocationRef.current; // FIX: Utiliser la ref, pas l'état
        if (currentCoords) {
          console.log('📤 Envoi position au serveur...', currentCoords);
          sendCurrentLocation(currentCoords);
        } else {
          console.log('⚠️ Pas de position disponible pour envoi');
        }
      }, ENV.GPS_UPDATE_INTERVAL);

      console.log('✅ Tracking activé avec succès');
    } catch (err) {
      console.error('❌ Erreur démarrage tracking:', err);
      setError('Erreur démarrage tracking');
      setIsTracking(false);
    }
  };

  const stopTracking = async () => {
    console.log('⏹️ Arrêt du tracking...');
    
    // Mettre le statut hors ligne
    await setOnlineStatus(false);
    setIsTracking(false);

    // Arrêter le GPS
    if (subscriptionRef.current) {
      subscriptionRef.current.remove();
      subscriptionRef.current = null;
    }

    // Arrêter l'envoi périodique
    if (sendIntervalRef.current) {
      clearInterval(sendIntervalRef.current);
      sendIntervalRef.current = null;
    }

    console.log('✅ Tracking arrêté');
  };

  return {
    location,
    permission,
    error,
    isTracking,
    getCurrentLocation,
    startTracking,
    stopTracking,
  };
};