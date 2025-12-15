import * as Location from 'expo-location';
import ENV from '../config/env';

export const requestPermissions = async (): Promise<boolean> => {
  const { status } = await Location.requestForegroundPermissionsAsync();
  return status === 'granted';
};

export const requestBackgroundPermissions = async (): Promise<boolean> => {
  const { status } = await Location.requestBackgroundPermissionsAsync();
  return status === 'granted';
};

export const getCurrentPosition = async (): Promise<Location.LocationObject | null> => {
  try {
    const hasPermission = await requestPermissions();
    if (!hasPermission) return null;

    const location = await Location.getCurrentPositionAsync({
      accuracy: Location.Accuracy.High,
    });
    return location;
  } catch (error) {
    console.error('Error getting location:', error);
    return null;
  }
};

export const startLocationTracking = async (
  callback: (location: Location.LocationObject) => void
): Promise<Location.LocationSubscription | null> => {
  try {
    const hasPermission = await requestPermissions();
    if (!hasPermission) return null;

    const subscription = await Location.watchPositionAsync(
      {
        accuracy: Location.Accuracy.High,
        timeInterval: ENV.GPS_UPDATE_INTERVAL,
        distanceInterval: ENV.GPS_DISTANCE_FILTER,
      },
      callback
    );
    return subscription;
  } catch (error) {
    console.error('Error starting tracking:', error);
    return null;
  }
};

export const stopLocationTracking = (subscription: Location.LocationSubscription) => {
  subscription.remove();
};
