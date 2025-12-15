import React, { createContext, useState, useContext, useCallback, ReactNode } from 'react';
import * as deliveryService from '../services/deliveryService';
import { DELIVERY_STATUS } from '../constants/app';

interface Delivery {
  id: number;
  order_number: string;
  status: string;
  customer_name: string;
  customer_phone: string;
  delivery_address: string;
  latitude: number;
  longitude: number;
  total_amount: number;
  items: any[];
  notes?: string;
  created_at: string;
  scheduled_at?: string;
}

interface DeliveryContextData {
  deliveries: Delivery[];
  activeDelivery: Delivery | null;
  loading: boolean;
  refreshing: boolean;
  fetchDeliveries: (status?: string) => Promise<void>;
  fetchDeliveryDetails: (id: number) => Promise<Delivery>;
  setActiveDelivery: (delivery: Delivery | null) => void;
  updateDeliveryStatus: (id: number, status: string) => void;
  refresh: () => Promise<void>;
}

const DeliveryContext = createContext<DeliveryContextData>({} as DeliveryContextData);

export const DeliveryProvider = ({ children }: { children: ReactNode }) => {
  const [deliveries, setDeliveries] = useState<Delivery[]>([]);
  const [activeDelivery, setActiveDelivery] = useState<Delivery | null>(null);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  const fetchDeliveries = useCallback(async (status?: string) => {
    try {
      setLoading(true);
      const data = await deliveryService.getDeliveries(status);
      setDeliveries(data);
    } catch (error) {
      console.error('Erreur fetch deliveries:', error);
      throw error;
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchDeliveryDetails = useCallback(async (id: number) => {
    try {
      const delivery = await deliveryService.getDeliveryDetails(id);
      return delivery;
    } catch (error) {
      console.error('Erreur fetch delivery details:', error);
      throw error;
    }
  }, []);

  const updateDeliveryStatus = useCallback((id: number, status: string) => {
    setDeliveries(prev =>
      prev.map(delivery =>
        delivery.id === id ? { ...delivery, status } : delivery
      )
    );

    if (activeDelivery?.id === id) {
      setActiveDelivery(prev => prev ? { ...prev, status } : null);
    }
  }, [activeDelivery]);

  const refresh = useCallback(async () => {
    try {
      setRefreshing(true);
      await fetchDeliveries();
    } finally {
      setRefreshing(false);
    }
  }, [fetchDeliveries]);

  return (
    <DeliveryContext.Provider
      value={{
        deliveries,
        activeDelivery,
        loading,
        refreshing,
        fetchDeliveries,
        fetchDeliveryDetails,
        setActiveDelivery,
        updateDeliveryStatus,
        refresh,
      }}
    >
      {children}
    </DeliveryContext.Provider>
  );
};

export const useDelivery = () => {
  const context = useContext(DeliveryContext);
  if (!context) {
    throw new Error('useDelivery doit être utilisé dans DeliveryProvider');
  }
  return context;
};
