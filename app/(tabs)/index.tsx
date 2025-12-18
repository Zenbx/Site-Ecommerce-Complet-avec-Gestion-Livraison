import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  StatusBar,
  Platform,
  SafeAreaView
} from 'react-native';
import { useRouter } from 'expo-router';
import { useDelivery } from '../../context/DeliveryContext';
import { useAuth } from '../../context/AuthContext';

export default function DashboardScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const { deliveries, fetchDeliveries, refresh, refreshing } = useDelivery();
  const [stats, setStats] = useState({
    total: 0,
    assigned: 0,
    in_transit: 0,
    delivered: 0,
    failed: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      await fetchDeliveries();
    } catch (error) {
      console.error('Erreur chargement:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const today = new Date().toDateString();
    const todayDeliveries = deliveries.filter(
      d => new Date(d.timeline.created_at).toDateString() === today
    );

    setStats({
      total: todayDeliveries.length,
      assigned: todayDeliveries.filter(d => d.status === 'ASSIGNED').length,
      in_transit: todayDeliveries.filter(d => d.status === 'IN_TRANSIT' || d.status === 'PICKED_UP').length,
      delivered: todayDeliveries.filter(d => d.status === 'DELIVERED').length,
      failed: todayDeliveries.filter(d => d.status === 'FAILED').length,
    });
  }, [deliveries]);

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#0077ff" />
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />
      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={refresh} tintColor="#0077ff" />
        }
      >
        {/* Header Épuré sans bannière bleue */}
        <View style={styles.header}>
          <View>
            <Text style={styles.greeting}>Bonjour, {user?.name}</Text>
            <Text style={styles.date}>
              {new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}
            </Text>
          </View>
          <View style={styles.avatarMini}>
            <Text style={styles.avatarText}>{user?.name?.charAt(0)}</Text>
          </View>
        </View>

        <View style={styles.content}>
          <Text style={styles.sectionLabel}>Activité du jour</Text>
          
          <View style={styles.statsGrid}>
            <View style={[styles.statCard, { borderColor: '#0077ff' }]}>
              <Text style={[styles.statValue, { color: '#0077ff' }]}>{stats.total}</Text>
              <Text style={styles.statLabel}>Total</Text>
            </View>

            <View style={[styles.statCard, { borderColor: '#FF9800' }]}>
              <Text style={[styles.statValue, { color: '#FF9800' }]}>{stats.assigned}</Text>
              <Text style={styles.statLabel}>Assignées</Text>
            </View>

            <View style={[styles.statCard, { borderColor: '#29B6F6' }]}>
              <Text style={[styles.statValue, { color: '#29B6F6' }]}>{stats.in_transit}</Text>
              <Text style={styles.statLabel}>En cours</Text>
            </View>

            <View style={[styles.statCard, { borderColor: '#4CAF50' }]}>
              <Text style={[styles.statValue, { color: '#4CAF50' }]}>{stats.delivered}</Text>
              <Text style={styles.statLabel}>Livrées</Text>
            </View>
          </View>

          <Text style={styles.sectionLabel}>Actions rapides</Text>
          <View style={styles.actions}>
            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => router.push('/(tabs)/deliveries')}
              activeOpacity={0.7}
            >
              <View style={[styles.actionIconBox, { backgroundColor: '#F0F7FF' }]}>
                <Text style={styles.actionEmoji}>📦</Text>
              </View>
              <View>
                <Text style={styles.actionTitle}>Livraisons</Text>
                <Text style={styles.actionSubtitle}>Gérer les courses</Text>
              </View>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => router.push('/(tabs)/history')}
              activeOpacity={0.7}
            >
              <View style={[styles.actionIconBox, { backgroundColor: '#F5F3FF' }]}>
                <Text style={styles.actionEmoji}>📋</Text>
              </View>
              <View>
                <Text style={styles.actionTitle}>Historique</Text>
                <Text style={styles.actionSubtitle}>Archives des trajets</Text>
              </View>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => router.push('/(tabs)/statistics')}
              activeOpacity={0.7}
            >
              <View style={[styles.actionIconBox, { backgroundColor: '#FFF5F5' }]}>
                <Text style={styles.actionEmoji}>📊</Text>
              </View>
              <View>
                <Text style={styles.actionTitle}>Statistiques</Text>
                <Text style={styles.actionSubtitle}>Performances et gains</Text>
              </View>
            </TouchableOpacity>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#ffffff',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingTop: Platform.OS === 'ios' ? 10 : 20,
    paddingBottom: 24,
    backgroundColor: '#ffffff',
  },
  greeting: {
    fontSize: 24,
    fontWeight: '800',
    color: '#1a1a1a',
    letterSpacing: -0.5,
  },
  date: {
    fontSize: 14,
    color: '#94A3B8',
    marginTop: 4,
    fontWeight: '500',
  },
  avatarMini: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#F1F5F9',
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#64748B',
  },
  content: {
    paddingHorizontal: 24,
  },
  sectionLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: '#CBD5E1',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: 16,
    marginTop: 8,
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 32,
  },
  statCard: {
    width: '48%',
    backgroundColor: '#ffffff', // Fond blanc
    padding: 20,
    borderRadius: 20,
    marginBottom: 16,
    borderWidth: 2, // Bordure colorée
    justifyContent: 'center',
  },
  statValue: {
    fontSize: 28,
    fontWeight: '800',
  },
  statLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#64748B',
    marginTop: 4,
  },
  actions: {
    gap: 12,
    paddingBottom: 40,
  },
  actionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    padding: 16,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  actionIconBox: {
    width: 48,
    height: 48,
    borderRadius: 14,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  actionEmoji: {
    fontSize: 22,
  },
  actionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#1a1a1a',
  },
  actionSubtitle: {
    fontSize: 13,
    color: '#94A3B8',
    marginTop: 2,
  },
});
