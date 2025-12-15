import React, { useEffect } from 'react';
import { useLocation } from '../hooks/useLocation';
import * as deliveryService from '../services/deliveryService';

interface Props {
  deliveryId: number;
  isActive: boolean;
}

export default function LocationTracker({ deliveryId, isActive }: Props) {
  const { startTracking, stopTracking } = useLocation();

  useEffect(() => {
    if (isActive) {
      startTracking(async (coords) => {
        try {
          await deliveryService.updateLocation(deliveryId, coords.latitude, coords.longitude, coords.speed, coords.heading);
        } catch (error) {
          console.error('Erreur update location:', error);
        }
      });
    } else {
      stopTracking();
    }

    return () => stopTracking();
  }, [isActive, deliveryId]);

  return null;
}
