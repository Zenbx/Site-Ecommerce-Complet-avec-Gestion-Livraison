import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  SafeAreaView,
  StatusBar,
  Platform
} from 'react-native';
import { useRouter } from 'expo-router';
import { useDeliveries } from '../../hooks/useDeliveries';
import { formatCurrency } from '../../utils/helpers';
import { DELIVERY_STATUS_COLORS } from '../../constants/app';

export default function DeliveriesListScreen() {
  const router = useRouter();
  const { deliveries, loading, refreshing, filter, setFilter, fetchDeliveries, refresh } = useDeliveries();
  const [filters] = useState([
    { key: 'all', label: 'Toutes' },
    { key: 'ASSIGNED', label: 'Assignées' },
    { key: 'IN_TRANSIT', label: 'En cours' },
    { key: 'DELIVERED', label: 'Livrées' },
    { key: 'FAILED', label: 'Échouées' },
  ]);

  useEffect(() => {
    fetchDeliveries();
  }, []);

  const renderItem = ({ item }: any) => {
    // Mapping status color to a lighter background for the badge
    const statusColor = DELIVERY_STATUS_COLORS[item.status] || '#999';
    
    return (
      <TouchableOpacity 
        style={styles.card}
        activeOpacity={0.9}
        onPress={() => router.push(`/delivery/${item.id}`)}
      >
        <View style={styles.cardHeader}>
          <View style={styles.orderInfo}>
            <Text style={styles.orderNumber}>{item.order.order_number}</Text>
            <Text style={styles.trackingCode}>#{item.tracking_code}</Text>
          </View>
          <View style={[styles.badge, { backgroundColor: statusColor }]}>
            <Text style={styles.badgeText}>{item.status_label}</Text>
          </View>
        </View>

        <View style={styles.divider} />

        <View style={styles.cardBody}>
          <View style={styles.row}>
            <Text style={styles.labelIcon}>👤</Text>
            <Text style={styles.customer}>{item.order.customer.name}</Text>
          </View>
          <View style={styles.row}>
            <Text style={styles.labelIcon}>📍</Text>
            <Text style={styles.address} numberOfLines={2}>{item.delivery_address}</Text>
          </View>
        </View>

        <View style={styles.cardFooter}>
          <Text style={styles.itemsText}>
            📦 {item.order.items.length} article{item.order.items.length > 1 ? 's' : ''}
          </Text>
          <Text style={styles.amount}>{formatCurrency(parseFloat(item.order.total_amount))}</Text>
        </View>
      </TouchableOpacity>
    );
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#0077ff" />
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#F5F7FA" />
      
      <View style={styles.headerContainer}>
        <Text style={styles.headerTitle}>Livraisons</Text>
      </View>

      <View style={styles.filterContainer}>
        <FlatList
          horizontal
          data={filters}
          keyExtractor={item => item.key}
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.filterListContent}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={[styles.filterChip, filter === item.key && styles.filterChipActive]}
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
        showsVerticalScrollIndicator={false}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyEmoji}>📦</Text>
            <Text style={styles.emptyTitle}>Aucune livraison</Text>
            <Text style={styles.emptySubtitle}>Il n'y a pas de livraisons correspondant à votre recherche.</Text>
          </View>
        }
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F7FA',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F5F7FA',
  },
  headerContainer: {
    paddingHorizontal: 20,
    paddingTop: Platform.OS === 'android' ? 20 : 10,
    paddingBottom: 10,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: '800',
    color: '#1a1a1a',
    letterSpacing: -0.5,
  },
  filterContainer: {
    paddingVertical: 12,
  },
  filterListContent: {
    paddingHorizontal: 20,
  },
  filterChip: {
    paddingHorizontal: 20,
    paddingVertical: 10,
    marginRight: 10,
    borderRadius: 25,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.05)',
  },
  filterChipActive: {
    backgroundColor: '#0077ff',
    borderColor: '#0077ff',
    shadowColor: '#0077ff',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  filterText: {
    fontSize: 14,
    color: '#666',
    fontWeight: '600',
  },
  filterTextActive: {
    color: '#fff',
  },
  list: {
    paddingHorizontal: 20,
    paddingBottom: 20,
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 20,
    marginBottom: 16,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 3,
    borderWidth: 1,
    borderColor: '#f0f0f0',
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  orderInfo: {
    flex: 1,
  },
  orderNumber: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1a1a1a',
    marginBottom: 4,
  },
  trackingCode: {
    fontSize: 13,
    color: '#0077ff',
    fontWeight: '600',
    letterSpacing: 0.5,
  },
  badge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    marginLeft: 12,
  },
  badgeText: {
    color: '#fff',
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  divider: {
    height: 1,
    backgroundColor: '#f5f5f5',
    marginVertical: 16,
  },
  cardBody: {
    marginBottom: 16,
    gap: 12,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  labelIcon: {
    fontSize: 16,
    marginRight: 10,
    width: 20,
  },
  customer: {
    fontSize: 15,
    fontWeight: '600',
    color: '#333',
    flex: 1,
  },
  address: {
    fontSize: 14,
    color: '#666',
    lineHeight: 20,
    flex: 1,
  },
  cardFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    marginHorizontal: -20,
    marginBottom: -20,
    padding: 20,
    borderBottomLeftRadius: 20,
    borderBottomRightRadius: 20,
    borderTopWidth: 1,
    borderTopColor: '#f0f0f0',
  },
  itemsText: {
    fontSize: 14,
    color: '#64748B',
    fontWeight: '500',
  },
  amount: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1a1a1a',
  },
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: 60,
  },
  emptyEmoji: {
    fontSize: 48,
    marginBottom: 16,
    opacity: 0.5,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#333',
    marginBottom: 8,
  },
  emptySubtitle: {
    fontSize: 14,
    color: '#999',
    textAlign: 'center',
    paddingHorizontal: 40,
  },
});
