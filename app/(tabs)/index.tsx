import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { useDeliveries } from '../../hooks/useDeliveries';
import { useAuth } from '../../context/AuthContext';
import { formatCurrency } from '../../utils/helpers';
import { DELIVERY_STATUS } from '../../constants/app';

export default function DashboardScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const { fetchDeliveries, getTodayStats, refresh, refreshing } = useDeliveries();
  const [stats, setStats] = useState({
    total: 0,
    assigned: 0,
    inProgress: 0,
    completed: 0,
    failed: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      await fetchDeliveries();
      setStats(getTodayStats());
    } catch (error) {
      console.error('Erreur chargement:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    await refresh();
    setStats(getTodayStats());
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
      <View style={styles.header}>
        <Text style={styles.greeting}>Bonjour, {user?.name}</Text>
        <Text style={styles.date}>{new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}</Text>
      </View>

      <View style={styles.statsGrid}>
        <View style={[styles.statCard, { backgroundColor: '#4169E1' }]}>
          <Text style={styles.statValue}>{stats.total}</Text>
          <Text style={styles.statLabel}>Total aujourd'hui</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#FFA500' }]}>
          <Text style={styles.statValue}>{stats.assigned}</Text>
          <Text style={styles.statLabel}>Assignées</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#1E90FF' }]}>
          <Text style={styles.statValue}>{stats.inProgress}</Text>
          <Text style={styles.statLabel}>En cours</Text>
        </View>
        <View style={[styles.statCard, { backgroundColor: '#32CD32' }]}>
          <Text style={styles.statValue}>{stats.completed}</Text>
          <Text style={styles.statLabel}>Complétées</Text>
        </View>
      </View>

      <View style={styles.actions}>
        <TouchableOpacity
          style={styles.actionButton}
          onPress={() => router.push('/(tabs)/deliveries')}
        >
          <Text style={styles.actionButtonText}>📦 Mes livraisons</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.actionButton}
          onPress={() => router.push('/(tabs)/history')}
        >
          <Text style={styles.actionButtonText}>📋 Historique</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={styles.actionButton}
          onPress={() => router.push('/(tabs)/statistics')}
        >
          <Text style={styles.actionButtonText}>📊 Statistiques</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5' },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { backgroundColor: '#fff', padding: 20, borderBottomWidth: 1, borderBottomColor: '#eee' },
  greeting: { fontSize: 24, fontWeight: 'bold', color: '#333' },
  date: { fontSize: 14, color: '#666', marginTop: 4 },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', padding: 12, gap: 12 },
  statCard: { flex: 1, minWidth: '45%', padding: 20, borderRadius: 12, alignItems: 'center' },
  statValue: { fontSize: 32, fontWeight: 'bold', color: '#fff' },
  statLabel: { fontSize: 14, color: '#fff', marginTop: 4, textAlign: 'center' },
  actions: { padding: 16, gap: 12 },
  actionButton: { backgroundColor: '#fff', padding: 20, borderRadius: 12, borderWidth: 1, borderColor: '#eee' },
  actionButtonText: { fontSize: 16, fontWeight: '600', color: '#333', textAlign: 'center' },
});
