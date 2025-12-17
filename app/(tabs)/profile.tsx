import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Switch,
  Alert,
  Platform
} from 'react-native';
import { useAuth } from '../../context/AuthContext';
import { useRouter } from 'expo-router';
import * as deliveryService from '../../services/deliveryService';

export default function ProfileScreen() {
  const { user, signOut, updateUser } = useAuth();
  const router = useRouter();
  const [isAvailable, setIsAvailable] = useState(user?.is_available || false);
  const [loading, setLoading] = useState(false);

  const toggleAvailability = async (value: boolean) => {
    try {
      setLoading(true);
      await deliveryService.updateAvailability(value);
      setIsAvailable(value);
      updateUser({ is_available: value });
    } catch (error) {
      Alert.alert('Erreur', 'Impossible de mettre à jour la disponibilité');
      setIsAvailable(!value);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
  	console.log("Bouton déconnexion pressé");
    if (Platform.OS === 'web') {
      const confirmLogout = window.confirm('Voulez-vous vraiment vous déconnecter ?');
      if (confirmLogout) signOut();
    } else {
      Alert.alert(
        'Déconnexion',
        'Voulez-vous vraiment vous déconnecter ?',
        [
          { text: 'Annuler', style: 'cancel' },
          { text: 'Déconnexion', style: 'destructive', onPress: () => signOut() },
        ]
      );
    }
  };

  return (
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{user?.name?.charAt(0).toUpperCase()}</Text>
        </View>
        <Text style={styles.name}>{user?.name}</Text>
        <Text style={styles.email}>{user?.email}</Text>
      </View>

      <View style={styles.section}>
        <View style={styles.row}>
          <View>
            <Text style={styles.rowTitle}>Disponibilité</Text>
            <Text style={styles.rowSubtitle}>
              {isAvailable ? 'Vous êtes disponible' : 'Vous êtes indisponible'}
            </Text>
          </View>
          <Switch
            value={isAvailable}
            onValueChange={toggleAvailability}
            disabled={loading}
          />
        </View>
      </View>

      <View style={styles.section}>
        <TouchableOpacity style={styles.row}>
          <Text style={styles.rowTitle}>📞 {user?.phone}</Text>
        </TouchableOpacity>
      </View>

      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Text style={styles.logoutText}>Déconnexion</Text>
      </TouchableOpacity>

      <Text style={styles.version}>Version 1.0.0</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5' },
  header: { backgroundColor: '#fff', padding: 24, alignItems: 'center', borderBottomWidth: 1, borderBottomColor: '#eee' },
  avatar: { width: 80, height: 80, borderRadius: 40, backgroundColor: '#4169E1', justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
  avatarText: { fontSize: 32, fontWeight: 'bold', color: '#fff' },
  name: { fontSize: 20, fontWeight: 'bold', color: '#333' },
  email: { fontSize: 14, color: '#666', marginTop: 4 },
  section: { backgroundColor: '#fff', marginTop: 12, borderTopWidth: 1, borderBottomWidth: 1, borderColor: '#eee' },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 16 },
  rowTitle: { fontSize: 16, fontWeight: '600', color: '#333' },
  rowSubtitle: { fontSize: 14, color: '#666', marginTop: 2 },
  logoutButton: { backgroundColor: '#DC143C', margin: 16, padding: 16, borderRadius: 8, marginTop: 24 },
  logoutText: { color: '#fff', textAlign: 'center', fontSize: 16, fontWeight: '600' },
  version: { textAlign: 'center', color: '#999', fontSize: 12, marginTop: 20, marginBottom: 30 },
});
