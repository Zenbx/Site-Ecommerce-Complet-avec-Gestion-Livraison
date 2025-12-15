import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useDeliveries } from '../../hooks/useDeliveries';
import { formatCurrency, formatDistanceKm, calculateDistance } from '../../utils/helpers';
import { useLocation } from '../../hooks/useLocation';
import { DELIVERY_STATUS, DELIVERY_STATUS_COLORS, DELIVERY_STATUS_LABELS } from '../../constants/app';

export default function DeliveriesListScreen() {
  const router = useRouter();
  const { deliveries, loading, refreshing, filter, setFilter, fetchDeliveries, refresh } = useDeliveries();
  const { location } = useLocation();
  const [filters] = useState([
    { key: 'all', label: 'Toutes' },
    { key: DELIVERY_STATUS.ASSIGNED, label: 'Assignées' },
    { key: DELIVERY_STATUS.IN_TRANSIT, label: 'En cours' },
    { key: DELIVERY_STATUS.DELIVERED, label: 'Livrées' },
    { key: DELIVERY_STATUS.FAILED, label: 'Échouées' },
  ]);

  useEffect(() => {
    fetchDeliveries();
  }, []);

  const getDistance = (delivery: any) => {
    if (!location) return null;
    const dist = calculateDistance(location.latitude, location.longitude, delivery.latitude, delivery.longitude);
    return formatDistanceKm(dist);
  };

  const renderItem = ({ item }: any) => (
    <TouchableOpacity 
      style={styles.card}
      onPress={() => router.push(`/delivery/${item.id}`)}
    >
      <View style={styles.cardHeader}>
        <Text style={styles.orderNumber}>{item.order_number}</Text>
        <View style={[styles.badge, { backgroundColor: DELIVERY_STATUS_COLORS[item.status] }]}>
          <Text style={styles.badgeText}>{DELIVERY_STATUS_LABELS[item.status]}</Text>
        </View>
      </View>
      <Text style={styles.customer}>{item.customer_name}</Text>
      <Text style={styles.address} numberOfLines={2}>{item.delivery_address}</Text>
      <View style={styles.footer}>
        <Text style={styles.amount}>{formatCurrency(item.total_amount)}</Text>
        {location && <Text style={styles.distance}>{getDistance(item)}</Text>}
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4169E1" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.filterContainer}>
        <FlatList
          horizontal
          data={filters}
          keyExtractor={item => item.key}
          showsHorizontalScrollIndicator={false}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={[styles.filterButton, filter === item.key && styles.filterButtonActive]}
              onPress={() => setFilter(item.key)}
            >
              <Text style={[styles.filterText, filter === item.key && styles.filterTextActive]}>
                {item.label}
              </Text>
            </TouchableOpacity>
          )}
        />
      </View>
      <FlatList
        data={deliveries}
        renderItem={renderItem}
        keyExtractor={item => item.id.toString()}
        onRefresh={refresh}
        refreshing={refreshing}
        contentContainerStyle={styles.list}
        ListEmptyComponent={<Text style={styles.empty}>Aucune livraison</Text>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5' },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  filterContainer: { backgroundColor: '#fff', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#eee' },
  filterButton: { paddingHorizontal: 16, paddingVertical: 8, marginHorizontal: 4, borderRadius: 20, backgroundColor: '#f0f0f0' },
  filterButtonActive: { backgroundColor: '#4169E1' },
  filterText: { fontSize: 14, color: '#666' },
  filterTextActive: { color: '#fff', fontWeight: '600' },
  list: { padding: 12 },
  card: { backgroundColor: '#fff', padding: 16, marginBottom: 12, borderRadius: 8, borderWidth: 1, borderColor: '#eee' },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  orderNumber: { fontSize: 16, fontWeight: 'bold', color: '#333' },
  badge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 4 },
  badgeText: { color: '#fff', fontSize: 12, fontWeight: '600' },
  customer: { fontSize: 15, fontWeight: '600', color: '#333', marginBottom: 4 },
  address: { fontSize: 14, color: '#666', marginBottom: 8 },
  footer: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  amount: { fontSize: 16, fontWeight: 'bold', color: '#32CD32' },
  distance: { fontSize: 14, color: '#4169E1' },
  empty: { textAlign: 'center', marginTop: 40, fontSize: 16, color: '#999' },
});
