import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Switch,
  Alert,
  Platform,
  Image,
  SafeAreaView,
  StatusBar
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

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
  };

  return (
    <SafeAreaView style={styles.mainContainer}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />
      <ScrollView 
        style={styles.container} 
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
      >
        <View style={styles.header}>
          <View style={styles.avatarContainer}>
            {user?.photo_url ? (
              <Image 
                source={{ uri: user.photo_url }} 
                style={styles.avatarImage}
              />
            ) : (
              <View style={styles.avatarFallback}>
                <Text style={styles.avatarText}>
                  {user?.name?.charAt(0).toUpperCase()}
                </Text>
              </View>
            )}
            <View style={[styles.statusDot, { backgroundColor: isAvailable ? '#4CAF50' : '#FF5252' }]} />
          </View>
          
          <Text style={styles.name}>{user?.name}</Text>
          <Text style={styles.email}>{user?.email}</Text>
          
          <View style={styles.memberBadge}>
            <Text style={styles.memberBadgeText}>
              Livreur partenaire depuis {user?.created_at ? formatDate(user.created_at) : 'N/A'}
            </Text>
          </View>
        </View>

        <View style={styles.availabilityCard}>
          <View style={styles.availabilityInfo}>
            <Text style={styles.availabilityTitle}>Disponibilité</Text>
            <Text style={styles.availabilityStatus}>
              {isAvailable ? 'En ligne' : 'Hors ligne'}
            </Text>
          </View>
          <Switch
            value={isAvailable}
            onValueChange={toggleAvailability}
            disabled={loading}
            trackColor={{ false: '#E0E0E0', true: '#0077ff4D' }}
            thumbColor={isAvailable ? '#0077ff' : '#F5F5F5'}
            ios_backgroundColor="#E0E0E0"
          />
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Informations personnelles</Text>
          
          <View style={styles.infoCard}>
            <View style={styles.infoRow}>
              <View style={styles.iconBox}>
                <Text style={styles.iconText}>🪪</Text>
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Carte d'identité</Text>
                <Text style={styles.infoValue}>{user?.id_card_number}</Text>
              </View>
            </View>

            <View style={styles.divider} />

            <View style={styles.infoRow}>
              <View style={styles.iconBox}>
                <Text style={styles.iconText}>📍</Text>
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Adresse de résidence</Text>
                <Text style={styles.infoValue}>{user?.address}</Text>
              </View>
            </View>

            <View style={styles.divider} />

            <View style={styles.infoRow}>
              <View style={styles.iconBox}>
                <Text style={styles.iconText}>📧</Text>
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Email de contact</Text>
                <Text style={styles.infoValue}>{user?.email}</Text>
              </View>
            </View>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Paramètres du compte</Text>
          <View style={styles.infoCard}>
            <View style={styles.infoRow}>
              <View style={styles.iconBox}>
                <Text style={styles.iconText}>🆔</Text>
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Identifiant Livreur</Text>
                <Text style={styles.infoValue}>#{user?.id}</Text>
              </View>
            </View>

            <View style={styles.divider} />

            <View style={styles.infoRow}>
              <View style={styles.iconBox}>
                <Text style={styles.iconText}>📅</Text>
              </View>
              <View style={styles.infoContent}>
                <Text style={styles.infoLabel}>Dernière synchronisation</Text>
                <Text style={styles.infoValue}>
                  {user?.updated_at ? formatDate(user.updated_at) : 'N/A'}
                </Text>
              </View>
            </View>
          </View>
        </View>

        <TouchableOpacity 
          style={styles.logoutButton} 
          onPress={handleLogout}
          activeOpacity={0.8}
        >
          <Text style={styles.logoutText}>Se déconnecter</Text>
        </TouchableOpacity>

        <Text style={styles.versionText}>Application Version 1.0.0</Text>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  mainContainer: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  container: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 40,
  },
  header: {
    alignItems: 'center',
    paddingVertical: 32,
    backgroundColor: '#ffffff',
  },
  avatarContainer: {
    position: 'relative',
    marginBottom: 16,
  },
  avatarImage: {
    width: 110,
    height: 110,
    borderRadius: 55,
    borderWidth: 4,
    borderColor: '#F8FAFC',
  },
  avatarFallback: {
    width: 110,
    height: 110,
    borderRadius: 55,
    backgroundColor: '#0077ff',
    justifyContent: 'center',
    alignItems: 'center',
    elevation: 4,
    shadowColor: '#0077ff',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
  },
  avatarText: {
    fontSize: 44,
    fontWeight: '800',
    color: '#ffffff',
  },
  statusDot: {
    position: 'absolute',
    bottom: 5,
    right: 5,
    width: 24,
    height: 24,
    borderRadius: 12,
    borderWidth: 4,
    borderColor: '#ffffff',
  },
  name: {
    fontSize: 24,
    fontWeight: '800',
    color: '#1a1a1a',
    letterSpacing: -0.5,
  },
  email: {
    fontSize: 14,
    color: '#64748B',
    marginTop: 4,
    fontWeight: '500',
  },
  memberBadge: {
    marginTop: 16,
    paddingHorizontal: 16,
    paddingVertical: 8,
    backgroundColor: '#F1F5F9',
    borderRadius: 20,
  },
  memberBadgeText: {
    fontSize: 12,
    color: '#475569',
    fontWeight: '600',
  },
  availabilityCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#F8FAFC',
    marginHorizontal: 20,
    padding: 20,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  availabilityInfo: {
    flex: 1,
  },
  availabilityTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#1a1a1a',
  },
  availabilityStatus: {
    fontSize: 14,
    color: '#64748B',
    marginTop: 2,
    fontWeight: '500',
  },
  section: {
    marginTop: 32,
    paddingHorizontal: 20,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#94A3B8',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: 16,
    marginLeft: 4,
  },
  infoCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    paddingHorizontal: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 2,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 16,
  },
  iconBox: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: '#F8FAFC',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  iconText: {
    fontSize: 20,
  },
  infoContent: {
    flex: 1,
  },
  infoLabel: {
    fontSize: 12,
    color: '#94A3B8',
    fontWeight: '600',
    marginBottom: 2,
  },
  infoValue: {
    fontSize: 15,
    color: '#1a1a1a',
    fontWeight: '600',
  },
  divider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginLeft: 60,
  },
  logoutButton: {
    marginHorizontal: 20,
    marginTop: 40,
    backgroundColor: '#FFF1F2',
    paddingVertical: 18,
    borderRadius: 20,
    alignItems: 'center',
  },
  logoutText: {
    color: '#E11D48',
    fontSize: 16,
    fontWeight: '700',
  },
  versionText: {
    textAlign: 'center',
    color: '#94A3B8',
    fontSize: 12,
    marginTop: 24,
    fontWeight: '500',
  },
});
