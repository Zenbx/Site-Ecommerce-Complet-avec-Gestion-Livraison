import { useState, useEffect, useRef } from 'react';
import * as Location from 'expo-location';
import { Alert } from 'react-native';
import ENV from '../config/env';

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
  const subscriptionRef = useRef<Location.LocationSubscription | null>(null);

  useEffect(() => {
    requestPermission();
    return () => stopTracking();
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
      return coords;
    } catch (err) {
      setError('Erreur obtention position');
      return null;
    }
  };

  const startTracking = async (callback: (coords: LocationCoords) => void) => {
    if (!permission) return;

    try {
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
          setLocation(coords);
          callback(coords);
        }
      );
    } catch (err) {
      setError('Erreur démarrage tracking');
    }
  };

  const stopTracking = () => {
    if (subscriptionRef.current) {
      subscriptionRef.current.remove();
      subscriptionRef.current = null;
    }
  };

  return {
    location,
    permission,
    error,
    getCurrentLocation,
    startTracking,
    stopTracking,
  };
};
