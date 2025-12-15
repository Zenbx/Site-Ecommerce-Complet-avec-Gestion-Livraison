import { useState, useCallback } from 'react';
import { useDelivery } from '../context/DeliveryContext';
import { DELIVERY_STATUS } from '../constants/app';

export const useDeliveries = () => {
  const {
    deliveries,
    activeDelivery,
    loading,
    refreshing,
    fetchDeliveries,
    setActiveDelivery,
    updateDeliveryStatus,
    refresh,
  } = useDelivery();

  const [filter, setFilter] = useState<string>('all');

  const filteredDeliveries = useCallback(() => {
    if (filter === 'all') return deliveries;
    return deliveries.filter(d => d.status === filter);
  }, [deliveries, filter]);

  const getDeliveriesByStatus = useCallback((status: string) => {
    return deliveries.filter(d => d.status === status);
  }, [deliveries]);

  const getTodayStats = useCallback(() => {
    const today = new Date().toDateString();
    const todayDeliveries = deliveries.filter(
      d => new Date(d.created_at).toDateString() === today
    );

    return {
      total: todayDeliveries.length,
      assigned: todayDeliveries.filter(d => d.status === DELIVERY_STATUS.ASSIGNED).length,
      inProgress: todayDeliveries.filter(
        d => d.status === DELIVERY_STATUS.IN_TRANSIT || d.status === DELIVERY_STATUS.PICKED_UP
      ).length,
      completed: todayDeliveries.filter(d => d.status === DELIVERY_STATUS.DELIVERED).length,
      failed: todayDeliveries.filter(d => d.status === DELIVERY_STATUS.FAILED).length,
    };
  }, [deliveries]);

  return {
    deliveries: filteredDeliveries(),
    activeDelivery,
    loading,
    refreshing,
    filter,
    setFilter,
    fetchDeliveries,
    setActiveDelivery,
    updateDeliveryStatus,
    refresh,
    getDeliveriesByStatus,
    getTodayStats,
  };
};
