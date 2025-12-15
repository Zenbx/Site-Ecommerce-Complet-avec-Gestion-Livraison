import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Image,
  TextInput,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as deliveryService from '../../services/deliveryService';
import { useLocation } from '../../hooks/useLocation';
import { PROOF_TYPES } from '../../constants/app';

export default function DeliveryProofScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { getCurrentLocation } = useLocation();
  const [permission, requestPermission] = useCameraPermissions();
  const [proofType, setProofType] = useState<'photo' | 'signature' | null>(null);
  const [photo, setPhoto] = useState<string | null>(null);
  const [recipientName, setRecipientName] = useState('');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);
  const [cameraRef, setCameraRef] = useState<any>(null);

  if (!permission) {
    return <View style={styles.container}><ActivityIndicator /></View>;
  }

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

  const takePhoto = async () => {
    if (cameraRef) {
      const photo = await cameraRef.takePictureAsync();
      setPhoto(photo.uri);
    }
  };

  const retakePhoto = () => {
    setPhoto(null);
  };

  const submitProof = async () => {
    if (!photo || !recipientName.trim()) {
      Alert.alert('Erreur', 'Photo et nom du destinataire requis');
      return;
    }

    try {
      setLoading(true);
      const location = await getCurrentLocation();
      if (!location) throw new Error('Position non disponible');

      const formData = new FormData();
      formData.append('proof_type', PROOF_TYPES.PHOTO);
      formData.append('proof_file', {
        uri: photo,
        type: 'image/jpeg',
        name: 'proof.jpg',
      } as any);
      formData.append('recipient_name', recipientName);
      formData.append('latitude', location.latitude.toString());
      formData.append('longitude', location.longitude.toString());
      formData.append('notes', notes);
      formData.append('timestamp', new Date().toISOString());

      const response = await deliveryService.submitProof(Number(id), formData);
      
      Alert.alert('Succès', 'Preuve envoyée', [
        { text: 'OK', onPress: () => router.back() }
      ]);
    } catch (error) {
      Alert.alert('Erreur', 'Impossible d\'envoyer la preuve');
    } finally {
      setLoading(false);
    }
  };

  if (!proofType) {
    return (
      <>
        <Stack.Screen options={{ title: 'Type de preuve' }} />
        <View style={styles.container}>
          <Text style={styles.title}>Choisir le type de preuve</Text>
          <TouchableOpacity
            style={styles.typeButton}
            onPress={() => setProofType('photo')}
          >
            <Text style={styles.typeButtonText}>📷 Photo du colis</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.typeButton}
            onPress={() => router.push('/delivery/signature')}
          >
            <Text style={styles.typeButtonText}>✍️ Signature client</Text>
          </TouchableOpacity>
        </View>
      </>
    );
  }

  if (!photo) {
    return (
      <>
        <Stack.Screen options={{ title: 'Capturer photo' }} />
        <View style={styles.cameraContainer}>
          <CameraView style={styles.camera} ref={setCameraRef} facing="back" />
          <View style={styles.cameraControls}>
            <TouchableOpacity style={styles.captureButton} onPress={takePhoto}>
              <View style={styles.captureButtonInner} />
            </TouchableOpacity>
          </View>
        </View>
      </>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Confirmer preuve' }} />
      <View style={styles.container}>
        <Image source={{ uri: photo }} style={styles.preview} />
        
        <TouchableOpacity style={styles.retakeButton} onPress={retakePhoto}>
          <Text style={styles.retakeText}>Reprendre</Text>
        </TouchableOpacity>

        <TextInput
          style={styles.input}
          placeholder="Nom du destinataire *"
          value={recipientName}
          onChangeText={setRecipientName}
        />

        <TextInput
          style={[styles.input, styles.textArea]}
          placeholder="Notes (optionnel)"
          value={notes}
          onChangeText={setNotes}
          multiline
          numberOfLines={3}
        />

        <TouchableOpacity
          style={[styles.submitButton, loading && styles.buttonDisabled]}
          onPress={submitProof}
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.submitButtonText}>Envoyer la preuve</Text>
          )}
        </TouchableOpacity>
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 20, backgroundColor: '#f5f5f5' },
  permissionContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 20 },
  permissionText: { fontSize: 16, marginBottom: 20, textAlign: 'center' },
  title: { fontSize: 20, fontWeight: 'bold', marginBottom: 20, textAlign: 'center' },
  typeButton: { backgroundColor: '#4169E1', padding: 20, borderRadius: 8, marginBottom: 12 },
  typeButtonText: { color: '#fff', fontSize: 18, textAlign: 'center', fontWeight: '600' },
  cameraContainer: { flex: 1 },
  camera: { flex: 1 },
  cameraControls: { position: 'absolute', bottom: 40, alignSelf: 'center' },
  captureButton: { width: 70, height: 70, borderRadius: 35, backgroundColor: 'rgba(255,255,255,0.3)', justifyContent: 'center', alignItems: 'center' },
  captureButtonInner: { width: 60, height: 60, borderRadius: 30, backgroundColor: '#fff' },
  preview: { width: '100%', height: 300, borderRadius: 8, marginBottom: 12 },
  retakeButton: { padding: 12, backgroundColor: '#666', borderRadius: 8, marginBottom: 20 },
  retakeText: { color: '#fff', textAlign: 'center', fontWeight: '600' },
  input: { backgroundColor: '#fff', padding: 12, borderRadius: 8, borderWidth: 1, borderColor: '#ddd', marginBottom: 12, fontSize: 16 },
  textArea: { height: 80, textAlignVertical: 'top' },
  submitButton: { backgroundColor: '#32CD32', padding: 16, borderRadius: 8, marginTop: 12 },
  submitButtonText: { color: '#fff', textAlign: 'center', fontSize: 16, fontWeight: '600' },
  button: { backgroundColor: '#4169E1', padding: 12, borderRadius: 8 },
  buttonText: { color: '#fff', fontWeight: '600' },
  buttonDisabled: { backgroundColor: '#a0a0a0' },
});
