import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Linking,
  Alert,
  ActivityIndicator,
  Modal,
  Platform,
  SafeAreaView,
  StatusBar
} from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { useDelivery } from '../../context/DeliveryContext';
import * as deliveryService from '../../services/deliveryService';
import { useLocation } from '../../hooks/useLocation';
import { DELIVERY_STATUS, DELIVERY_STATUS_COLORS, DELIVERY_STATUS_LABELS } from '../../constants/app';
import { formatCurrency, formatDateTime } from '../../utils/helpers';
import MapView from '../../components/MapView';
import { geocodeAddressWithFallback, isValidCoordinates } from '../../services/geocodingservice';

export default function DeliveryDetailScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { fetchDeliveryDetails, updateDeliveryStatus } = useDelivery();
  const { getCurrentLocation } = useLocation();
  const [delivery, setDelivery] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);
  const [showMap, setShowMap] = useState(false);
  const [geocoding, setGeocoding] = useState(false);

  useEffect(() => {
    loadDelivery();
  }, [id]);

  const loadDelivery = async () => {
    try {
      setLoading(true);
      const data = await fetchDeliveryDetails(Number(id));
      
      const lat = data.latitude || data.lat || 0;
      const lng = data.longitude || data.lng || data.lon || 0;

      if (!isValidCoordinates(lat, lng)) {
        const address = data.delivery_address || data.deliveryAddress || data.address;
        if (address) {
          setGeocoding(true);
          const geocoded = await geocodeAddressWithFallback(address);
          if (geocoded) {
            data.latitude = geocoded.latitude;
            data.longitude = geocoded.longitude;
          }
          setGeocoding(false);
        }
      }
      setDelivery(data);
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de charger les détails');
      router.back();
    } finally {
      setLoading(false);
    }
  };

  const handleStatusUpdate = async (action: 'accept' | 'pickup' | 'start') => {
    try {
      setActionLoading(true);
      const location = action !== 'accept' ? await getCurrentLocation() : null;
      
      if (action !== 'accept' && !location) {
        throw new Error('Position GPS requise');
      }

      let nextStatus;
      if (action === 'accept') {
        await deliveryService.acceptDelivery(Number(id));
        nextStatus = DELIVERY_STATUS.ACCEPTED;
      } else if (action === 'pickup') {
        await deliveryService.pickupDelivery(Number(id), location!.latitude, location!.longitude);
        nextStatus = DELIVERY_STATUS.PICKED_UP;
      } else {
        await deliveryService.startDelivery(Number(id), location!.latitude, location!.longitude);
        nextStatus = DELIVERY_STATUS.IN_TRANSIT;
      }

      updateDeliveryStatus(Number(id), nextStatus);
      await loadDelivery();
      Alert.alert('Succès', 'Statut mis à jour avec succès');
    } catch (error) {
      Alert.alert('Erreur', 'L\'opération a échoué');
    } finally {
      setActionLoading(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#0077ff" />
        <Text style={styles.loadingText}>Préparation de la course...</Text>
      </View>
    );
  }

  const status = delivery?.status || 'unknown';
  const statusColor = DELIVERY_STATUS_COLORS[status] || '#64748B';

  return (
    <SafeAreaView style={styles.mainContainer}>
      <StatusBar barStyle="dark-content" />
      <Stack.Screen options={{ title: 'Détails de la course', headerShadowVisible: false }} />
      
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Status Header */}
        <View style={[styles.statusBanner, { backgroundColor: statusColor + '15' }]}>
          <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
          <Text style={[styles.statusLabel, { color: statusColor }]}>
            {DELIVERY_STATUS_LABELS[status] || 'Statut inconnu'}
          </Text>
        </View>

        {/* Info Card */}
        <View style={styles.card}>
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>CLIENT & COMMANDE</Text>
            <View style={styles.customerRow}>
              <View style={styles.customerInfo}>
                <Text style={styles.customerName}>{delivery.customer_name || delivery.order?.customer?.name}</Text>
                <Text style={styles.orderId}>Commande {delivery.tracking_code || `#${id}`}</Text>
              </View>
              <TouchableOpacity 
                style={styles.callButton} 
                onPress={() => Linking.openURL(`tel:${delivery.customer_phone || delivery.order?.customer?.phone}`)}
              >
                <Text style={styles.callIcon}>📞</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.section}>
            <Text style={styles.sectionTitle}>ADRESSE DE LIVRAISON</Text>
            <Text style={styles.addressText}>{delivery.delivery_address || delivery.address}</Text>
            <View style={styles.mapActions}>
              <TouchableOpacity style={styles.secondaryAction} onPress={() => setShowMap(true)}>
                <Text style={styles.secondaryActionText}>🗺️ Voir sur la carte</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.secondaryAction, { borderColor: '#34C759' }]}
                onPress={() => {
                  const lat = delivery.latitude || 0;
                  const lng = delivery.longitude || 0;
                  const url = Platform.select({
                    ios: `maps://app?daddr=${lat},${lng}`,
                    android: `google.navigation:q=${lat},${lng}`
                  });
                  Linking.openURL(url || '');
                }}
              >
                <Text style={[styles.secondaryActionText, { color: '#34C759' }]}>🧭 Lancer le GPS</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>

        {/* Items Card */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>CONTENU DU COLIS</Text>
          {(delivery.items || delivery.order?.items || []).map((item: any, index: number) => (
            <View key={index} style={styles.itemRow}>
              <View style={styles.qtyBadge}>
                <Text style={styles.qtyText}>{item.quantity || 1}</Text>
              </View>
              <Text style={styles.itemName}>{item.name || item.product_name}</Text>
            </View>
          ))}
          <View style={styles.totalRow}>
            <Text style={styles.totalLabel}>Total à encaisser</Text>
            <Text style={styles.totalValue}>
              {formatCurrency(parseFloat(delivery.total_amount || delivery.order?.total_amount || 0))}
            </Text>
          </View>
        </View>

        {/* Notes Section */}
        {delivery.notes && (
          <View style={styles.notesBox}>
            <Text style={styles.notesTitle}>📝 Notes importantes</Text>
            <Text style={styles.notesText}>{delivery.notes}</Text>
          </View>
        )}

        <View style={{ height: 120 }} />
      </ScrollView>

      {/* Floating Action Buttons */}
      <View style={styles.footerActions}>
        {status === DELIVERY_STATUS.ASSIGNED && (
          <TouchableOpacity 
            style={[styles.mainButton, { backgroundColor: '#34C759' }]}
            onPress={() => handleStatusUpdate('accept')}
            disabled={actionLoading}
          >
            {actionLoading ? <ActivityIndicator color="#fff" /> : <Text style={styles.mainButtonText}>Accepter la course</Text>}
          </TouchableOpacity>
        )}

        {status === DELIVERY_STATUS.ACCEPTED && (
          <TouchableOpacity 
            style={[styles.mainButton, { backgroundColor: '#5856D6' }]}
            onPress={() => handleStatusUpdate('pickup')}
            disabled={actionLoading}
          >
            {actionLoading ? <ActivityIndicator color="#fff" /> : <Text style={styles.mainButtonText}>Confirmer la récupération</Text>}
          </TouchableOpacity>
        )}

        {status === DELIVERY_STATUS.PICKED_UP && (
          <TouchableOpacity 
            style={[styles.mainButton, { backgroundColor: '#0077ff' }]}
            onPress={() => handleStatusUpdate('start')}
            disabled={actionLoading}
          >
            {actionLoading ? <ActivityIndicator color="#fff" /> : <Text style={styles.mainButtonText}>Démarrer la livraison</Text>}
          </TouchableOpacity>
        )}

        {status === DELIVERY_STATUS.IN_TRANSIT && (
          <View style={styles.doubleButtonRow}>
            <TouchableOpacity 
              style={[styles.mainButton, { backgroundColor: '#FF9500', flex: 1 }]}
              onPress={() => router.push(`/delivery/qr-scanner?id=${id}`)}
            >
              <Text style={styles.mainButtonText}>Scanner QR</Text>
            </TouchableOpacity>
            <TouchableOpacity 
              style={[styles.mainButton, { backgroundColor: '#FF3B30', width: 60 }]}
              onPress={() => router.push(`/delivery/report-issue?id=${id}`)}
            >
              <Text style={styles.mainButtonText}>⚠️</Text>
            </TouchableOpacity>
          </View>
        )}
      </View>

      <Modal visible={showMap} animationType="slide">
        <SafeAreaView style={{ flex: 1 }}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Localisation</Text>
            <TouchableOpacity onPress={() => setShowMap(false)}>
              <Text style={styles.closeText}>Fermer</Text>
            </TouchableOpacity>
          </View>
          <MapView latitude={delivery?.latitude} longitude={delivery?.longitude} deliveryAddress={delivery?.delivery_address} />
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  mainContainer: { flex: 1, backgroundColor: '#F8FAFC' },
  container: { flex: 1, padding: 20 },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 12, color: '#64748B', fontWeight: '500' },
  statusBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 12,
    marginBottom: 20,
    alignSelf: 'flex-start'
  },
  statusDot: { width: 8, height: 8, borderRadius: 4, marginRight: 8 },
  statusLabel: { fontSize: 13, fontWeight: '700', textTransform: 'uppercase' },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 20,
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 15,
    elevation: 2,
  },
  section: { paddingVertical: 4 },
  sectionTitle: { fontSize: 11, fontWeight: '800', color: '#94A3B8', letterSpacing: 1, marginBottom: 12 },
  customerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  customerName: { fontSize: 20, fontWeight: '800', color: '#1E293B' },
  orderId: { fontSize: 14, color: '#64748B', marginTop: 2 },
  callButton: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center' },
  callIcon: { fontSize: 18 },
  divider: { height: 1, backgroundColor: '#F1F5F9', marginVertical: 16 },
  addressText: { fontSize: 16, color: '#1E293B', lineHeight: 24, fontWeight: '500' },
  mapActions: { flexDirection: 'row', gap: 12, marginTop: 16 },
  secondaryAction: { flex: 1, paddingVertical: 12, borderRadius: 12, borderWidth: 1.5, borderColor: '#0077ff', alignItems: 'center' },
  secondaryActionText: { color: '#0077ff', fontWeight: '700', fontSize: 13 },
  itemRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  qtyBadge: { backgroundColor: '#F1F5F9', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8, marginRight: 12 },
  qtyText: { fontSize: 14, fontWeight: '700', color: '#475569' },
  itemName: { fontSize: 15, color: '#1E293B', fontWeight: '500' },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 12, paddingTop: 16, borderTopWidth: 1, borderTopColor: '#F1F5F9' },
  totalLabel: { fontSize: 15, color: '#64748B', fontWeight: '600' },
  totalValue: { fontSize: 22, fontWeight: '800', color: '#34C759' },
  notesBox: { backgroundColor: '#FFFBEB', padding: 16, borderRadius: 16, borderLeftWidth: 4, borderLeftColor: '#F59E0B' },
  notesTitle: { fontSize: 14, fontWeight: '700', color: '#92400E', marginBottom: 4 },
  notesText: { fontSize: 14, color: '#B45309', lineHeight: 20 },
  footerActions: { position: 'absolute', bottom: 0, left: 0, right: 0, padding: 20, backgroundColor: 'rgba(248, 250, 252, 0.9)' },
  mainButton: { height: 60, borderRadius: 20, justifyContent: 'center', alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.1, shadowRadius: 10, elevation: 4 },
  mainButtonText: { color: '#FFF', fontSize: 17, fontWeight: '800' },
  doubleButtonRow: { flexDirection: 'row', gap: 12 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', padding: 20, alignItems: 'center', borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  modalTitle: { fontSize: 18, fontWeight: '800' },
  closeText: { color: '#0077ff', fontWeight: '700' }
});
