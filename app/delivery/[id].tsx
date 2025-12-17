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
      
      console.log('📦 Données reçues:', JSON.stringify(data, null, 2));
      
      // Vérifier si les coordonnées GPS existent
      const lat = data.latitude || data.lat || 0;
      const lng = data.longitude || data.lng || data.lon || 0;

      // Si pas de coordonnées, essayer de géocoder l'adresse
      if (!isValidCoordinates(lat, lng)) {
        console.log('⚠️ Coordonnées manquantes, tentative de géocodage...');
        
        const address = data.delivery_address || data.deliveryAddress || data.address;
        
        if (address) {
          setGeocoding(true);
          const geocoded = await geocodeAddressWithFallback(address);
          
          if (geocoded) {
            data.latitude = geocoded.latitude;
            data.longitude = geocoded.longitude;
            console.log('✅ Adresse géocodée:', geocoded);
          }
          setGeocoding(false);
        }
      }
      
      setDelivery(data);
    } catch (error) {
      console.error('❌ Erreur:', error);
      Alert.alert('Erreur', 'Impossible de charger les détails de la livraison');
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
      await loadDelivery();
      Alert.alert('Succès', 'Livraison acceptée');
    } catch (error) {
      console.error('❌ Erreur acceptation:', error);
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
      await loadDelivery();
      Alert.alert('Succès', 'Colis récupéré');
    } catch (error) {
      console.error('❌ Erreur pickup:', error);
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
      await loadDelivery();
      Alert.alert('Succès', 'Livraison démarrée');
    } catch (error) {
      console.error('❌ Erreur start:', error);
      Alert.alert('Erreur', 'Impossible de démarrer la livraison');
    } finally {
      setActionLoading(false);
    }
  };

  const openMaps = () => {
    const latitude = delivery?.latitude || delivery?.lat || 0;
    const longitude = delivery?.longitude || delivery?.lng || delivery?.lon || 0;
    const address = delivery?.delivery_address || delivery?.deliveryAddress || delivery?.address;

    if (!isValidCoordinates(latitude, longitude) && !address) {
      Alert.alert('Erreur', 'Coordonnées de livraison manquantes');
      return;
    }

    // URLs pour différentes apps de navigation
    const osmUrl = isValidCoordinates(latitude, longitude)
      ? `https://www.openstreetmap.org/directions?from=&to=${latitude},${longitude}`
      : `https://www.openstreetmap.org/search?query=${encodeURIComponent(address)}`;
    
    const googleUrl = isValidCoordinates(latitude, longitude)
      ? `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`
      : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
    
    const wazeUrl = isValidCoordinates(latitude, longitude)
      ? `https://waze.com/ul?ll=${latitude},${longitude}&navigate=yes`
      : null;

    if (Platform.OS === 'web') {
      window.open(osmUrl, '_blank');
    } else {
      const options = [
        { text: 'OpenStreetMap', onPress: () => Linking.openURL(osmUrl) },
        { text: 'Google Maps', onPress: () => Linking.openURL(googleUrl) },
      ];

      if (wazeUrl) {
        options.push({ text: 'Waze', onPress: () => Linking.openURL(wazeUrl) });
      }

      options.push({ text: 'Annuler', style: 'cancel' });

      Alert.alert('Navigation', 'Choisissez votre application de navigation', options);
    }
  };

  const viewMap = () => {
    const latitude = delivery?.latitude || delivery?.lat || 0;
    const longitude = delivery?.longitude || delivery?.lng || delivery?.lon || 0;

    if (!isValidCoordinates(latitude, longitude)) {
      Alert.alert('Erreur', 'Coordonnées de livraison manquantes');
      return;
    }
    setShowMap(true);
  };

  const callCustomer = () => {
    const phone = delivery?.customer_phone || delivery?.order?.customer?.phone;
    if (!phone) {
      Alert.alert('Erreur', 'Numéro de téléphone manquant');
      return;
    }
    Linking.openURL(`tel:${phone}`);
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4169E1" />
        <Text style={styles.loadingText}>Chargement des détails...</Text>
        {geocoding && (
          <Text style={styles.geocodingText}>🌍 Recherche des coordonnées GPS...</Text>
        )}
      </View>
    );
  }

  if (!delivery) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorText}>❌ Livraison introuvable</Text>
        <TouchableOpacity style={styles.backButton} onPress={() => router.back()}>
          <Text style={styles.backButtonText}>← Retour</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // Normalisation des données (support des deux structures)
  const customerName = delivery.customer_name || delivery.order?.customer?.name || 'Client inconnu';
  const customerPhone = delivery.customer_phone || delivery.order?.customer?.phone || 'N/A';
  const deliveryAddress = delivery.delivery_address || delivery.deliveryAddress || delivery.address || 'Adresse non spécifiée';
  const items = delivery.items || delivery.order?.items || delivery.order_items || [];
  const totalAmount = delivery.total_amount || delivery.order?.total_amount || delivery.totalAmount || delivery.amount || 0;
  const orderNumber = delivery.order_number || delivery.order?.order_number || delivery.orderNumber || delivery.order_id || id;
  const notes = delivery.notes || delivery.delivery_notes || '';
  const status = delivery.status || 'unknown';
  const trackingCode = delivery.tracking_code || delivery.trackingCode || `#${orderNumber}`;
  const estimatedDelivery = delivery.estimated_delivery || delivery.estimatedDelivery;
  
  const latitude = delivery.latitude || delivery.lat || 0;
  const longitude = delivery.longitude || delivery.lng || delivery.lon || 0;
  const hasValidCoordinates = isValidCoordinates(latitude, longitude);

  return (
    <>
      <Stack.Screen 
        options={{ 
          title: `Commande ${trackingCode}`, 
          headerShown: true 
        }} 
      />
      <ScrollView style={styles.container}>
        {/* Badge de statut */}
        <View style={[styles.statusBadge, { backgroundColor: DELIVERY_STATUS_COLORS[status] || '#666' }]}>
          <Text style={styles.statusText}>
            {DELIVERY_STATUS_LABELS[status] || delivery.status_label || 'Statut inconnu'}
          </Text>
        </View>

        {/* Code de suivi */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>📋 Code de suivi</Text>
          <Text style={styles.trackingCode}>{trackingCode}</Text>
        </View>

        {/* Informations client */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>👤 Client</Text>
          <Text style={styles.customerName}>{customerName}</Text>
          {customerPhone !== 'N/A' && (
            <TouchableOpacity onPress={callCustomer} style={styles.phoneButton}>
              <Text style={styles.phoneText}>📞 {customerPhone}</Text>
            </TouchableOpacity>
          )}
        </View>

        {/* Adresse de livraison */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>📍 Adresse de livraison</Text>
          <Text style={styles.address}>{deliveryAddress}</Text>
          
          {hasValidCoordinates ? (
            <View style={styles.mapActions}>
              <TouchableOpacity onPress={viewMap} style={styles.viewMapButton}>
                <Text style={styles.mapButtonText}>🗺️ Voir la carte</Text>
              </TouchableOpacity>
              
              <TouchableOpacity onPress={openMaps} style={styles.navigateButton}>
                <Text style={styles.mapButtonText}>🧭 Navigation</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <View style={styles.warningBox}>
              <Text style={styles.warningText}>⚠️ Coordonnées GPS non disponibles</Text>
              <Text style={styles.warningSubtext}>
                L'adresse n'a pas pu être géolocalisée automatiquement
              </Text>
              <TouchableOpacity onPress={openMaps} style={styles.searchAddressButton}>
                <Text style={styles.searchAddressText}>🔍 Rechercher l'adresse</Text>
              </TouchableOpacity>
            </View>
          )}
        </View>

        {/* Articles */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>📦 Articles ({items.length})</Text>
          {items.length > 0 ? (
            items.map((item: any, index: number) => (
              <View key={index} style={styles.item}>
                <Text style={styles.itemName}>
                  {item.name || item.product_name || 'Article'}
                </Text>
                <Text style={styles.itemQty}>x{item.quantity || item.qty || 1}</Text>
              </View>
            ))
          ) : (
            <Text style={styles.emptyText}>Aucun article dans cette commande</Text>
          )}
        </View>

        {/* Montant total */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>💰 Montant total</Text>
          <Text style={styles.amount}>
            {totalAmount > 0 ? formatCurrency(parseFloat(totalAmount)) : '0 FCFA'}
          </Text>
        </View>

        {/* Heure de livraison estimée */}
        {estimatedDelivery && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>⏰ Livraison estimée</Text>
            <Text style={styles.estimatedTime}>{formatDateTime(estimatedDelivery)}</Text>
          </View>
        )}

        {/* Notes */}
        {notes && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>📝 Notes de livraison</Text>
            <Text style={styles.notes}>{notes}</Text>
          </View>
        )}

        {/* Actions selon le statut */}
        <View style={styles.actions}>
          {status === DELIVERY_STATUS.ASSIGNED && (
            <TouchableOpacity
              style={[styles.actionButton, styles.acceptButton]}
              onPress={handleAccept}
              disabled={actionLoading}
            >
              {actionLoading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.actionButtonText}>✅ Accepter la livraison</Text>
              )}
            </TouchableOpacity>
          )}

          {status === DELIVERY_STATUS.ACCEPTED && (
            <TouchableOpacity
              style={[styles.actionButton, styles.pickupButton]}
              onPress={handlePickup}
              disabled={actionLoading}
            >
              {actionLoading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.actionButtonText}>📦 Marquer comme récupéré</Text>
              )}
            </TouchableOpacity>
          )}

          {status === DELIVERY_STATUS.PICKED_UP && (
            <TouchableOpacity
              style={[styles.actionButton, styles.startButton]}
              onPress={handleStart}
              disabled={actionLoading}
            >
              {actionLoading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text style={styles.actionButtonText}>🚚 Démarrer la livraison</Text>
              )}
            </TouchableOpacity>
          )}

          {status === DELIVERY_STATUS.IN_TRANSIT && (
            <>
              <TouchableOpacity
                style={[styles.actionButton, styles.scanButton]}
                onPress={() => router.push(`/delivery/qr-scanner?id=${id}`)}
              >
                <Text style={styles.actionButtonText}>📱 Scanner QR Code</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.actionButton, styles.issueButton]}
                onPress={() => router.push(`/delivery/report-issue?id=${id}`)}
              >
                <Text style={styles.actionButtonText}>⚠️ Signaler un problème</Text>
              </TouchableOpacity>
            </>
          )}
        </View>

        {/* Section debug en développement */}
        {__DEV__ && (
          <View style={styles.debugSection}>
            <Text style={styles.debugTitle}>🐛 DEBUG</Text>
            <Text style={styles.debugText}>ID: {id}</Text>
            <Text style={styles.debugText}>Statut: {status}</Text>
            <Text style={styles.debugText}>Lat/Lng: {latitude}, {longitude}</Text>
            <Text style={styles.debugText}>
              GPS valides: {hasValidCoordinates ? '✅ Oui' : '❌ Non'}
            </Text>
            <Text style={styles.debugText}>Items: {items.length}</Text>
            <Text style={styles.debugText}>Montant: {totalAmount}</Text>
            <Text style={styles.debugText}>Tracking: {trackingCode}</Text>
          </View>
        )}
      </ScrollView>

      {/* Modal de carte */}
      {hasValidCoordinates && (
        <Modal
          visible={showMap}
          animationType="slide"
          onRequestClose={() => setShowMap(false)}
        >
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>🗺️ Carte de livraison</Text>
              <TouchableOpacity onPress={() => setShowMap(false)} style={styles.closeButton}>
                <Text style={styles.closeButtonText}>✕ Fermer</Text>
              </TouchableOpacity>
            </View>
            <MapView
              latitude={latitude}
              longitude={longitude}
              deliveryAddress={deliveryAddress}
            />
          </View>
        </Modal>
      )}
    </>
  );
}

const styles = StyleSheet.create({
  container: { 
    flex: 1, 
    backgroundColor: '#f5f5f5' 
  },
  loadingContainer: { 
    flex: 1, 
    justifyContent: 'center', 
    alignItems: 'center',
    backgroundColor: '#f5f5f5'
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: '#666',
  },
  geocodingText: {
    marginTop: 10,
    fontSize: 14,
    color: '#4169E1',
    fontStyle: 'italic',
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  errorText: {
    fontSize: 18,
    color: '#DC143C',
    marginBottom: 20,
    textAlign: 'center',
  },
  backButton: {
    backgroundColor: '#4169E1',
    padding: 12,
    borderRadius: 8,
    paddingHorizontal: 24,
  },
  backButtonText: {
    color: 'white',
    fontWeight: '600',
    fontSize: 16,
  },
  statusBadge: { 
    padding: 16, 
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  statusText: { 
    color: '#fff', 
    fontWeight: 'bold', 
    fontSize: 16,
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  section: { 
    backgroundColor: '#fff', 
    padding: 16, 
    marginTop: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  sectionTitle: { 
    fontSize: 14, 
    color: '#666', 
    marginBottom: 8,
    fontWeight: '600',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  trackingCode: { 
    fontSize: 18, 
    fontWeight: '600', 
    color: '#4169E1',
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
  },
  customerName: { 
    fontSize: 20, 
    fontWeight: 'bold', 
    color: '#333',
    marginBottom: 4,
  },
  phoneButton: { 
    marginTop: 8, 
    padding: 12,
    backgroundColor: '#f0f8ff',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#4169E1',
  },
  phoneText: { 
    color: '#4169E1', 
    fontSize: 16,
    fontWeight: '600',
  },
  address: { 
    fontSize: 16, 
    color: '#333', 
    lineHeight: 24,
    marginBottom: 12,
  },
  mapActions: {
    flexDirection: 'row',
    gap: 8,
  },
  viewMapButton: { 
    flex: 1,
    padding: 14, 
    backgroundColor: '#4169E1', 
    borderRadius: 8,
    alignItems: 'center',
    shadowColor: '#4169E1',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 3,
  },
  navigateButton: { 
    flex: 1,
    padding: 14, 
    backgroundColor: '#32CD32', 
    borderRadius: 8,
    alignItems: 'center',
    shadowColor: '#32CD32',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.3,
    shadowRadius: 4,
    elevation: 3,
  },
  mapButtonText: { 
    color: '#fff', 
    fontWeight: '600',
    fontSize: 15,
  },
  warningBox: {
    backgroundColor: '#FFF3CD',
    padding: 12,
    borderRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: '#FFC107',
  },
  warningText: {
    color: '#856404',
    fontSize: 14,
    fontWeight: '600',
    marginBottom: 4,
  },
  warningSubtext: {
    color: '#856404',
    fontSize: 12,
    marginBottom: 8,
  },
  searchAddressButton: {
    backgroundColor: '#FFC107',
    padding: 10,
    borderRadius: 6,
    alignItems: 'center',
    marginTop: 8,
  },
  searchAddressText: {
    color: '#856404',
    fontWeight: '600',
    fontSize: 14,
  },
  item: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    paddingVertical: 12, 
    borderBottomWidth: 1, 
    borderBottomColor: '#f0f0f0',
    alignItems: 'center',
  },
  itemName: { 
    fontSize: 16, 
    color: '#333',
    flex: 1,
    fontWeight: '500',
  },
  itemQty: { 
    fontSize: 16, 
    color: '#666',
    fontWeight: 'bold',
    backgroundColor: '#f5f5f5',
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  emptyText: {
    fontSize: 14,
    color: '#999',
    fontStyle: 'italic',
    textAlign: 'center',
    paddingVertical: 20,
  },
  amount: { 
    fontSize: 28, 
    fontWeight: 'bold', 
    color: '#32CD32',
  },
  estimatedTime: { 
    fontSize: 16, 
    color: '#333',
    fontWeight: '500',
  },
  notes: { 
    fontSize: 14, 
    color: '#666', 
    fontStyle: 'italic',
    lineHeight: 20,
    backgroundColor: '#fffacd',
    padding: 12,
    borderRadius: 8,
    borderLeftWidth: 4,
    borderLeftColor: '#FFD700',
  },
  actions: { 
    padding: 16, 
    gap: 12, 
    marginBottom: 20 
  },
  actionButton: { 
    padding: 16, 
    borderRadius: 12, 
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
    elevation: 4,
  },
  actionButtonText: { 
    color: '#fff', 
    fontSize: 16, 
    fontWeight: '600' 
  },
  acceptButton: { backgroundColor: '#32CD32' },
  pickupButton: { backgroundColor: '#9370DB' },
  startButton: { backgroundColor: '#4169E1' },
  scanButton: { backgroundColor: '#FF8C00' },
  issueButton: { backgroundColor: '#DC143C' },
  
  modalContainer: {
    flex: 1,
    backgroundColor: '#fff',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#4169E1',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 5,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#fff',
  },
  closeButton: {
    padding: 8,
  },
  closeButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  
  debugSection: {
    backgroundColor: '#2C3E50',
    padding: 16,
    margin: 16,
    borderRadius: 8,
  },
  debugTitle: {
    color: '#ECF0F1',
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 8,
  },
  debugText: {
    color: '#BDC3C7',
    fontSize: 12,
    fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
    marginVertical: 2,
  },
});
