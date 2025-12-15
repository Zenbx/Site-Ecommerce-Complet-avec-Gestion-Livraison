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
} from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { useDelivery } from '../../context/DeliveryContext';
import * as deliveryService from '../../services/deliveryService';
import { useLocation } from '../../hooks/useLocation';
import { DELIVERY_STATUS, DELIVERY_STATUS_COLORS, DELIVERY_STATUS_LABELS } from '../../constants/app';
import { formatCurrency, formatDateTime } from '../../utils/helpers';

export default function DeliveryDetailScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { fetchDeliveryDetails, updateDeliveryStatus } = useDelivery();
  const { getCurrentLocation } = useLocation();
  const [delivery, setDelivery] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    loadDelivery();
  }, [id]);

  const loadDelivery = async () => {
    try {
      setLoading(true);
      const data = await fetchDeliveryDetails(Number(id));
      setDelivery(data);
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de charger les détails');
      router.back();
    } finally {
      setLoading(false);
    }
  };

  const handleAccept = async () => {
    try {
      setActionLoading(true);
      await deliveryService.acceptDelivery(Number(id));
      updateDeliveryStatus(Number(id), DELIVERY_STATUS.ACCEPTED);
      setDelivery({ ...delivery, status: DELIVERY_STATUS.ACCEPTED });
      Alert.alert('Succès', 'Livraison acceptée');
    } catch (error) {
      Alert.alert('Erreur', 'Impossible d\'accepter la livraison');
    } finally {
      setActionLoading(false);
    }
  };

  const handlePickup = async () => {
    try {
      setActionLoading(true);
      const location = await getCurrentLocation();
      if (!location) throw new Error('Position non disponible');

      await deliveryService.pickupDelivery(Number(id), location.latitude, location.longitude);
      updateDeliveryStatus(Number(id), DELIVERY_STATUS.PICKED_UP);
      setDelivery({ ...delivery, status: DELIVERY_STATUS.PICKED_UP });
      Alert.alert('Succès', 'Colis récupéré');
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de marquer comme récupéré');
    } finally {
      setActionLoading(false);
    }
  };

  const handleStart = async () => {
    try {
      setActionLoading(true);
      const location = await getCurrentLocation();
      if (!location) throw new Error('Position non disponible');

      await deliveryService.startDelivery(Number(id), location.latitude, location.longitude);
      updateDeliveryStatus(Number(id), DELIVERY_STATUS.IN_TRANSIT);
      setDelivery({ ...delivery, status: DELIVERY_STATUS.IN_TRANSIT });
      Alert.alert('Succès', 'Livraison démarrée');
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de démarrer la livraison');
    } finally {
      setActionLoading(false);
    }
  };

  const openMaps = () => {
    const url = `https://www.google.com/maps/dir/?api=1&destination=${delivery.latitude},${delivery.longitude}`;
    Linking.openURL(url);
  };

  const callCustomer = () => {
    Linking.openURL(`tel:${delivery.customer_phone}`);
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4169E1" />
      </View>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: `Commande ${delivery.order_number}`, headerShown: true }} />
      <ScrollView style={styles.container}>
        <View style={[styles.statusBadge, { backgroundColor: DELIVERY_STATUS_COLORS[delivery.status] }]}>
          <Text style={styles.statusText}>{DELIVERY_STATUS_LABELS[delivery.status]}</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Client</Text>
          <Text style={styles.customerName}>{delivery.customer_name}</Text>
          <TouchableOpacity onPress={callCustomer} style={styles.phoneButton}>
            <Text style={styles.phoneText}>📞 {delivery.customer_phone}</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Adresse de livraison</Text>
          <Text style={styles.address}>{delivery.delivery_address}</Text>
          <TouchableOpacity onPress={openMaps} style={styles.mapButton}>
            <Text style={styles.mapButtonText}>📍 Ouvrir dans Maps</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Articles ({delivery.items?.length || 0})</Text>
          {delivery.items?.map((item: any, index: number) => (
            <View key={index} style={styles.item}>
              <Text style={styles.itemName}>{item.name}</Text>
              <Text style={styles.itemQty}>x{item.quantity}</Text>
            </View>
          ))}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Montant</Text>
          <Text style={styles.amount}>{formatCurrency(delivery.total_amount)}</Text>
        </View>

        {delivery.notes && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Notes</Text>
            <Text style={styles.notes}>{delivery.notes}</Text>
          </View>
        )}

        <View style={styles.actions}>
          {delivery.status === DELIVERY_STATUS.ASSIGNED && (
            <TouchableOpacity
              style={[styles.actionButton, styles.acceptButton]}
              onPress={handleAccept}
              disabled={actionLoading}
            >
              <Text style={styles.actionButtonText}>Accepter</Text>
            </TouchableOpacity>
          )}

          {delivery.status === DELIVERY_STATUS.ACCEPTED && (
            <TouchableOpacity
              style={[styles.actionButton, styles.pickupButton]}
              onPress={handlePickup}
              disabled={actionLoading}
            >
              <Text style={styles.actionButtonText}>Marquer comme récupéré</Text>
            </TouchableOpacity>
          )}

          {delivery.status === DELIVERY_STATUS.PICKED_UP && (
            <TouchableOpacity
              style={[styles.actionButton, styles.startButton]}
              onPress={handleStart}
              disabled={actionLoading}
            >
              <Text style={styles.actionButtonText}>Démarrer la livraison</Text>
            </TouchableOpacity>
          )}

          {delivery.status === DELIVERY_STATUS.IN_TRANSIT && (
            <>
              <TouchableOpacity
                style={[styles.actionButton, styles.scanButton]}
                onPress={() => router.push('/delivery/qr-scanner')}
              >
                <Text style={styles.actionButtonText}>Scanner QR Code</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.actionButton, styles.issueButton]}
                onPress={() => router.push(`/delivery/report-issue?id=${id}`)}
              >
                <Text style={styles.actionButtonText}>Signaler un problème</Text>
              </TouchableOpacity>
            </>
          )}
        </View>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5' },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  statusBadge: { padding: 12, alignItems: 'center' },
  statusText: { color: '#fff', fontWeight: 'bold', fontSize: 16 },
  section: { backgroundColor: '#fff', padding: 16, marginTop: 8 },
  sectionTitle: { fontSize: 14, color: '#666', marginBottom: 8 },
  customerName: { fontSize: 18, fontWeight: 'bold', color: '#333' },
  phoneButton: { marginTop: 8, padding: 8 },
  phoneText: { color: '#4169E1', fontSize: 16 },
  address: { fontSize: 16, color: '#333', lineHeight: 24 },
  mapButton: { marginTop: 12, padding: 12, backgroundColor: '#4169E1', borderRadius: 8 },
  mapButtonText: { color: '#fff', textAlign: 'center', fontWeight: '600' },
  item: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#eee' },
  itemName: { fontSize: 16, color: '#333' },
  itemQty: { fontSize: 16, color: '#666' },
  amount: { fontSize: 24, fontWeight: 'bold', color: '#32CD32' },
  notes: { fontSize: 14, color: '#666', fontStyle: 'italic' },
  actions: { padding: 16, gap: 12, marginBottom: 20 },
  actionButton: { padding: 16, borderRadius: 8, alignItems: 'center' },
  actionButtonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  acceptButton: { backgroundColor: '#32CD32' },
  pickupButton: { backgroundColor: '#9370DB' },
  startButton: { backgroundColor: '#4169E1' },
  scanButton: { backgroundColor: '#FF8C00' },
  issueButton: { backgroundColor: '#DC143C' },
});
