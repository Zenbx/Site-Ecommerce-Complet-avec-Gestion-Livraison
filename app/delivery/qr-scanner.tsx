import React, { useState } from 'react';
import { View, Text, StyleSheet, Alert, TouchableOpacity } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { useRouter, Stack, useLocalSearchParams } from 'expo-router';
import * as deliveryService from '../../services/deliveryService';
import { parseQRCodeData } from '../../utils/helpers';

export default function QRScannerScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);

  if (!permission) return <View style={styles.container} />;

  if (!permission.granted) {
    return (
      <View style={styles.permissionContainer}>
        <Text style={styles.permissionText}>Permission caméra requise</Text>
        <TouchableOpacity style={styles.button} onPress={requestPermission}>
          <Text style={styles.buttonText}>Autoriser</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const handleBarCodeScanned = async ({ data }: { data: string }) => {
    if (scanned) return;
    setScanned(true);

    try {
      const qrData = parseQRCodeData(data);
      await deliveryService.scanQRCode(Number(id), data);
      
      Alert.alert('✅ QR Code valide', 'Vous pouvez procéder à la livraison', [
        { text: 'Continuer', onPress: () => router.push(`/delivery/proof?id=${id}`) }
      ]);
    } catch (error: any) {
      Alert.alert('❌ QR Code invalide', error.response?.data?.message || 'Ce code ne correspond pas à cette livraison', [
        { text: 'Réessayer', onPress: () => setScanned(false) },
        { text: 'Annuler', onPress: () => router.back() }
      ]);
    }
  };

  return (
    <>
      <Stack.Screen options={{ title: 'Scanner QR Code' }} />
      <View style={styles.container}>
        <CameraView
          style={styles.camera}
          facing="back"
          onBarcodeScanned={scanned ? undefined : handleBarCodeScanned}
          barcodeScannerSettings={{
            barcodeTypes: ['qr'],
          }}
        />
        <View style={styles.overlay}>
          <View style={styles.frame} />
          <Text style={styles.instruction}>Centrez le QR code dans le cadre</Text>
        </View>
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#000' },
  permissionContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 20 },
  permissionText: { fontSize: 16, marginBottom: 20, textAlign: 'center' },
  button: { backgroundColor: '#4169E1', padding: 12, borderRadius: 8 },
  buttonText: { color: '#fff', fontWeight: '600' },
  camera: { flex: 1 },
  overlay: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, justifyContent: 'center', alignItems: 'center' },
  frame: { width: 250, height: 250, borderWidth: 3, borderColor: '#fff', borderRadius: 12, backgroundColor: 'transparent' },
  instruction: { color: '#fff', fontSize: 16, marginTop: 30, textAlign: 'center', backgroundColor: 'rgba(0,0,0,0.6)', padding: 12, borderRadius: 8 },
});
