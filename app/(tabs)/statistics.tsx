import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  RefreshControl,
  SafeAreaView,
  StatusBar,
  Dimensions
} from 'react-native';
import * as deliveryService from '../../services/deliveryService';
import { formatCurrency } from '../../utils/helpers';

const { width } = Dimensions.get('window');

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
        <ActivityIndicator size="large" color="#0077ff" />
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.mainContainer}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Statistiques</Text>
      </View>
      
      <ScrollView
        style={styles.container}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor="#0077ff" />
        }
      >
        {/* Aujourd'hui - Main Highlight */}
        <View style={styles.todayCard}>
          <Text style={styles.todayLabel}>Gains du jour</Text>
          <Text style={styles.todayValue}>{formatCurrency(stats?.today?.earnings || 0)}</Text>
          <View style={styles.todayStatsRow}>
            <View style={styles.miniStat}>
              <Text style={styles.miniStatLabel}>Livrées</Text>
              <Text style={styles.miniStatValue}>{stats?.today?.deliveries.delivered || 0}</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.miniStat}>
              <Text style={styles.miniStatLabel}>Distance</Text>
              <Text style={styles.miniStatValue}>{stats?.today?.distance || 0} km</Text>
            </View>
          </View>
        </View>

        <Text style={styles.sectionTitle}>Cette semaine</Text>
        <View style={styles.grid}>
          <StatCard 
            label="Livraisons" 
            value={stats?.week?.deliveries.delivered || 0} 
            icon="📦"
            color="#E3F2FD"
          />
          <StatCard 
            label="Gains" 
            value={formatCurrency(stats?.week?.earnings || 0)} 
            icon="💰"
            color="#E8F5E9"
          />
          <StatCard 
            label="Distance" 
            value={`${stats?.week?.distance || 0} km`} 
            icon="🛣️"
            color="#FFF3E0"
          />
          <StatCard 
            label="Temps moyen" 
            value="24 min" 
            icon="⏱️"
            color="#F3E5F5"
          />
        </View>

        <Text style={styles.sectionTitle}>Ce mois</Text>
        <View style={styles.monthCard}>
          <View style={styles.monthRow}>
            <Text style={styles.monthLabel}>Total livraisons</Text>
            <Text style={styles.monthValue}>{stats?.month?.deliveries.delivered || 0}</Text>
          </View>
          <View style={styles.lineDivider} />
          <View style={styles.monthRow}>
            <Text style={styles.monthLabel}>Chiffre d'affaires</Text>
            <Text style={styles.monthValue}>{formatCurrency(stats?.month?.earnings || 0)}</Text>
          </View>
          <View style={styles.lineDivider} />
          <View style={styles.monthRow}>
            <Text style={styles.monthLabel}>Taux de réussite</Text>
            <View style={styles.performanceBadge}>
              <Text style={styles.performanceText}>{stats?.month?.performance.on_time_rate || 0}%</Text>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const StatCard = ({ label, value, icon, color }: { label: string; value: string | number; icon: string; color: string }) => (
  <View style={styles.statCard}>
    <View style={[styles.iconCircle, { backgroundColor: color }]}>
      <Text style={styles.iconText}>{icon}</Text>
    </View>
    <Text style={styles.cardValue}>{value}</Text>
    <Text style={styles.cardLabel}>{label}</Text>
  </View>
);

const styles = StyleSheet.create({
  mainContainer: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  header: {
    paddingHorizontal: 24,
    paddingVertical: 16,
  },
  headerTitle: {
    fontSize: 28,
    fontWeight: '800',
    color: '#1a1a1a',
    letterSpacing: -0.5,
  },
  container: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 24,
    paddingBottom: 40,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  todayCard: {
    backgroundColor: '#0077ff',
    borderRadius: 30,
    padding: 30,
    alignItems: 'center',
    marginBottom: 32,
    shadowColor: '#0077ff',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.2,
    shadowRadius: 20,
    elevation: 8,
  },
  todayLabel: {
    color: 'rgba(255,255,255,0.7)',
    fontSize: 14,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  todayValue: {
    color: '#ffffff',
    fontSize: 42,
    fontWeight: '800',
    marginVertical: 12,
  },
  todayStatsRow: {
    flexDirection: 'row',
    marginTop: 10,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderRadius: 20,
    padding: 16,
    width: '100%',
  },
  miniStat: {
    flex: 1,
    alignItems: 'center',
  },
  miniStatLabel: {
    color: 'rgba(255,255,255,0.6)',
    fontSize: 12,
    marginBottom: 4,
  },
  miniStatValue: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '700',
  },
  statDivider: {
    width: 1,
    backgroundColor: 'rgba(255,255,255,0.2)',
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1a1a1a',
    marginBottom: 16,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 32,
  },
  statCard: {
    backgroundColor: '#ffffff',
    width: (width - 64) / 2,
    borderRadius: 24,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  iconCircle: {
    width: 40,
    height: 40,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  iconText: {
    fontSize: 20,
  },
  cardValue: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1a1a1a',
    marginBottom: 4,
  },
  cardLabel: {
    fontSize: 13,
    color: '#94A3B8',
    fontWeight: '600',
  },
  monthCard: {
    backgroundColor: '#F8FAFC',
    borderRadius: 24,
    padding: 24,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  monthRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
  },
  monthLabel: {
    fontSize: 15,
    color: '#64748B',
    fontWeight: '500',
  },
  monthValue: {
    fontSize: 16,
    fontWeight: '700',
    color: '#1a1a1a',
  },
  lineDivider: {
    height: 1,
    backgroundColor: '#F1F5F9',
  },
  performanceBadge: {
    backgroundColor: '#E8F5E9',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 10,
  },
  performanceText: {
    color: '#2E7D32',
    fontWeight: '700',
    fontSize: 14,
  },
});
