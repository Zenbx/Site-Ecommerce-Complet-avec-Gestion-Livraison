import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  Image,
  ScrollView,
} from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as deliveryService from '../../services/deliveryService';
import { useLocation } from '../../hooks/useLocation';
import { ISSUE_TYPES, ISSUE_TYPE_LABELS } from '../../constants/app';

export default function ReportIssueScreen() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { getCurrentLocation } = useLocation();
  const [permission, requestPermission] = useCameraPermissions();
  const [issueType, setIssueType] = useState('');
  const [description, setDescription] = useState('');
  const [photo, setPhoto] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [showCamera, setShowCamera] = useState(false);
  const [cameraRef, setCameraRef] = useState<any>(null);

  const takePhoto = async () => {
    if (cameraRef) {
      const photo = await cameraRef.takePictureAsync();
      setPhoto(photo.uri);
      setShowCamera(false);
    }
  };

  const submitIssue = async () => {
    if (!issueType || !description.trim()) {
      Alert.alert('Erreur', 'Type et description requis');
      return;
    }

    try {
      setLoading(true);
      const location = await getCurrentLocation();
      if (!location) throw new Error('Position non disponible');

      const formData = new FormData();
      formData.append('issue_type', issueType);
      formData.append('description', description);
      formData.append('latitude', location.latitude.toString());
      formData.append('longitude', location.longitude.toString());
      formData.append('attempted_at', new Date().toISOString());
      
      if (photo) {
        formData.append('photo', {
          uri: photo,
          type: 'image/jpeg',
          name: 'issue.jpg',
        } as any);
      }

      await deliveryService.reportIssue(Number(id), formData);
      
      Alert.alert('Succès', 'Problème signalé', [
        { text: 'OK', onPress: () => router.back() }
      ]);
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de signaler le problème');
    } finally {
      setLoading(false);
    }
  };

  if (showCamera) {
    return (
      <>
        <Stack.Screen options={{ title: 'Prendre photo' }} />
        <View style={styles.cameraContainer}>
          <CameraView style={styles.camera} ref={setCameraRef} facing="back" />
          <View style={styles.cameraControls}>
            <TouchableOpacity style={styles.captureButton} onPress={takePhoto}>
              <View style={styles.captureButtonInner} />
            </TouchableOpacity>
            <TouchableOpacity style={styles.cancelButton} onPress={() => setShowCamera(false)}>
              <Text style={styles.cancelText}>Annuler</Text>
            </TouchableOpacity>
          </View>
        </View>
      </>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: 'Signaler un problème' }} />
      <ScrollView style={styles.container}>
        <Text style={styles.label}>Type de problème *</Text>
        {Object.entries(ISSUE_TYPE_LABELS).map(([key, label]) => (
          <TouchableOpacity
            key={key}
            style={[styles.option, issueType === key && styles.optionSelected]}
            onPress={() => setIssueType(key)}
          >
            <Text style={[styles.optionText, issueType === key && styles.optionTextSelected]}>
              {label}
            </Text>
          </TouchableOpacity>
        ))}

        <Text style={styles.label}>Description *</Text>
        <TextInput
          style={styles.textArea}
          placeholder="Décrivez le problème rencontré..."
          value={description}
          onChangeText={setDescription}
          multiline
          numberOfLines={4}
        />

        <Text style={styles.label}>Photo (optionnel)</Text>
        {photo ? (
          <View>
            <Image source={{ uri: photo }} style={styles.preview} />
            <TouchableOpacity style={styles.retakeButton} onPress={() => setPhoto(null)}>
              <Text style={styles.retakeText}>Supprimer</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <TouchableOpacity style={styles.photoButton} onPress={() => setShowCamera(true)}>
            <Text style={styles.photoButtonText}>📷 Ajouter une photo</Text>
          </TouchableOpacity>
        )}

        <TouchableOpacity
          style={[styles.submitButton, loading && styles.buttonDisabled]}
          onPress={submitIssue}
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.submitButtonText}>Signaler le problème</Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 20, backgroundColor: '#f5f5f5' },
  label: { fontSize: 16, fontWeight: '600', color: '#333', marginBottom: 8, marginTop: 12 },
  option: { backgroundColor: '#fff', padding: 14, borderRadius: 8, marginBottom: 8, borderWidth: 1, borderColor: '#ddd' },
  optionSelected: { backgroundColor: '#4169E1', borderColor: '#4169E1' },
  optionText: { fontSize: 15, color: '#333' },
  optionTextSelected: { color: '#fff', fontWeight: '600' },
  textArea: { backgroundColor: '#fff', padding: 12, borderRadius: 8, borderWidth: 1, borderColor: '#ddd', fontSize: 16, minHeight: 100, textAlignVertical: 'top' },
  photoButton: { backgroundColor: '#fff', padding: 16, borderRadius: 8, borderWidth: 1, borderColor: '#ddd', alignItems: 'center' },
  photoButtonText: { fontSize: 16, color: '#666' },
  preview: { width: '100%', height: 200, borderRadius: 8, marginBottom: 8 },
  retakeButton: { padding: 12, backgroundColor: '#DC143C', borderRadius: 8 },
  retakeText: { color: '#fff', textAlign: 'center', fontWeight: '600' },
  submitButton: { backgroundColor: '#DC143C', padding: 16, borderRadius: 8, marginTop: 20, marginBottom: 30 },
  submitButtonText: { color: '#fff', textAlign: 'center', fontSize: 16, fontWeight: '600' },
  buttonDisabled: { backgroundColor: '#a0a0a0' },
  cameraContainer: { flex: 1 },
  camera: { flex: 1 },
  cameraControls: { position: 'absolute', bottom: 40, alignSelf: 'center', alignItems: 'center' },
  captureButton: { width: 70, height: 70, borderRadius: 35, backgroundColor: 'rgba(255,255,255,0.3)', justifyContent: 'center', alignItems: 'center' },
  captureButtonInner: { width: 60, height: 60, borderRadius: 30, backgroundColor: '#fff' },
  cancelButton: { marginTop: 12, padding: 12, backgroundColor: 'rgba(0,0,0,0.5)', borderRadius: 8 },
  cancelText: { color: '#fff', fontWeight: '600' },
});
