import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  TextInput,
} from 'react-native';
import { useRouter } from 'expo-router';
import * as deliveryService from '../../services/deliveryService';
import { formatCurrency, formatDateTime } from '../../utils/helpers';
import { DELIVERY_STATUS_COLORS, DELIVERY_STATUS_LABELS } from '../../constants/app';

export default function HistoryScreen() {
  const router = useRouter();
  const [deliveries, setDeliveries] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [search, setSearch] = useState('');

  useEffect(() => {
    loadHistory();
  }, []);

  const loadHistory = async () => {
    try {
      setLoading(true);
      const data = await deliveryService.getHistory(1, 20);
      setDeliveries(data.data || []);
      setHasMore(data.current_page < data.last_page);
    } catch (error) {
      console.error('Erreur historique:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadMore = async () => {
    if (!hasMore || refreshing) return;
    
    try {
      setRefreshing(true);
      const nextPage = page + 1;
      const data = await deliveryService.getHistory(nextPage, 20);
      setDeliveries(prev => [...prev, ...(data.data || [])]);
      setPage(nextPage);
      setHasMore(data.current_page < data.last_page);
    } catch (error) {
      console.error('Erreur pagination:', error);
    } finally {
      setRefreshing(false);
    }
  };

  const onRefresh = async () => {
    setPage(1);
    setRefreshing(true);
    await loadHistory();
    setRefreshing(false);
  };

  const filteredData = deliveries.filter(d => 
    d.order_number.toLowerCase().includes(search.toLowerCase()) ||
    d.customer_name.toLowerCase().includes(search.toLowerCase())
  );

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
      <Text style={styles.date}>{formatDateTime(item.delivered_at || item.created_at)}</Text>
      <Text style={styles.amount}>{formatCurrency(item.total_amount)}</Text>
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
      <View style={styles.searchContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder="Rechercher..."
          value={search}
          onChangeText={setSearch}
        />
      </View>
      <FlatList
        data={filteredData}
        renderItem={renderItem}
        keyExtractor={item => item.id.toString()}
        onRefresh={onRefresh}
        refreshing={refreshing}
        onEndReached={loadMore}
        onEndReachedThreshold={0.5}
        ListEmptyComponent={<Text style={styles.empty}>Aucun historique</Text>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5' },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  searchContainer: { backgroundColor: '#fff', padding: 12, borderBottomWidth: 1, borderBottomColor: '#eee' },
  searchInput: { backgroundColor: '#f5f5f5', padding: 12, borderRadius: 8, fontSize: 16 },
  card: { backgroundColor: '#fff', padding: 16, marginHorizontal: 12, marginTop: 12, borderRadius: 8, borderWidth: 1, borderColor: '#eee' },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  orderNumber: { fontSize: 16, fontWeight: 'bold', color: '#333' },
  badge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 4 },
  badgeText: { color: '#fff', fontSize: 12, fontWeight: '600' },
  customer: { fontSize: 15, color: '#333', marginBottom: 4 },
  date: { fontSize: 13, color: '#666', marginBottom: 8 },
  amount: { fontSize: 16, fontWeight: 'bold', color: '#32CD32' },
  empty: { textAlign: 'center', marginTop: 40, fontSize: 16, color: '#999' },
});
