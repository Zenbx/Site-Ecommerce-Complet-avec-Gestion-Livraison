import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import * as deliveryService from '../../services/deliveryService';
import { formatCurrency } from '../../utils/helpers';

export default function StatisticsScreen() {
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      const [today, week, month] = await Promise.all([
        deliveryService.getStatistics('today'),
        deliveryService.getStatistics('week'),
        deliveryService.getStatistics('month'),
      ]);
      setStats({ today, week, month });
    } catch (error) {
      console.error('Erreur stats:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadStats();
    setRefreshing(false);
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4169E1" />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>📅 Aujourd'hui</Text>
        <StatCard label="Livraisons complétées" value={stats?.today?.deliveries.delivered || 0} />
        <StatCard label="Gains" value={formatCurrency(stats?.today?.earnings || 0)} />
        <StatCard label="Distance parcourue" value={`${stats?.today?.distance || 0} km`} />
        <StatCard label="Temps moyen" value={`${stats?.today?.performance.average_delivery_time || 0} min`} />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>📊 Cette semaine</Text>
        <StatCard label="Livraisons complétées" value={stats?.week?.deliveries.delivered || 0} />
        <StatCard label="Gains" value={formatCurrency(stats?.week?.earnings || 0)} />
        <StatCard label="Distance parcourue" value={`${stats?.week?.distance || 0} km`} />
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>📈 Ce mois</Text>
        <StatCard label="Livraisons complétées" value={stats?.month?.deliveries.delivered || 0} />
        <StatCard label="Gains" value={formatCurrency(stats?.month?.earnings || 0)} />
        <StatCard label="Taux de réussite" value={`${stats?.month?.performance.on_time_rate || 0}%`} />
      </View>
    </ScrollView>
  );
}

const StatCard = ({ label, value }: { label: string; value: string | number }) => (
  <View style={styles.statCard}>
    <Text style={styles.statLabel}>{label}</Text>
    <Text style={styles.statValue}>{value}</Text>
  </View>
);

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5' },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  section: { backgroundColor: '#fff', marginTop: 12, padding: 16, borderTopWidth: 1, borderBottomWidth: 1, borderColor: '#eee' },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', color: '#333', marginBottom: 12 },
  statCard: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f0f0f0' },
  statLabel: { fontSize: 15, color: '#666' },
  statValue: { fontSize: 16, fontWeight: '600', color: '#333' },
});
