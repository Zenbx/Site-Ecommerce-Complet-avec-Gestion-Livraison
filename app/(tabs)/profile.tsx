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
  Image
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

  // Fonction helper pour formater les dates de manière lisible
  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
  };

  return (
    <ScrollView style={styles.container}>
      {/* En-tête du profil avec avatar ou photo */}
      <View style={styles.header}>
        {user?.photo_url ? (
          // Si l'utilisateur a une photo de profil, on l'affiche
          <Image 
            source={{ uri: user.photo_url }} 
            style={styles.avatarImage}
            // Fallback en cas d'erreur de chargement de l'image
            // defaultSource={require('../../assets/default-avatar.png')}
          />
        ) : (
          // Sinon, on affiche les initiales dans un cercle coloré
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>
              {user?.name?.charAt(0).toUpperCase()}
            </Text>
          </View>
        )}
        <Text style={styles.name}>{user?.name}</Text>
        <Text style={styles.email}>{user?.email}</Text>
        
        {/* Badge de statut membre */}
        <View style={styles.memberBadge}>
          <Text style={styles.memberBadgeText}>
            Membre depuis {user?.created_at ? formatDate(user.created_at) : 'N/A'}
          </Text>
        </View>
      </View>

      {/* Section Disponibilité - la plus importante pour un livreur */}
      <View style={styles.section}>
        <View style={styles.row}>
          <View>
            <Text style={styles.rowTitle}>Disponibilité</Text>
            <Text style={styles.rowSubtitle}>
              {isAvailable ? 'Vous êtes disponible pour des livraisons' : 'Vous êtes actuellement indisponible'}
            </Text>
          </View>
          <Switch
            value={isAvailable}
            onValueChange={toggleAvailability}
            disabled={loading}
            trackColor={{ false: '#767577', true: '#81b0ff' }}
            thumbColor={isAvailable ? '#4169E1' : '#f4f3f4'}
          />
        </View>
      </View>

      {/* Section Informations personnelles */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Informations personnelles</Text>
      </View>

      <View style={styles.section}>
        {/* Carte d'identité */}
        <View style={styles.row}>
          <View style={styles.rowIcon}>
            <Text style={styles.icon}>🪪</Text>
          </View>
          <View style={styles.rowContent}>
            <Text style={styles.rowTitle}>Carte d'identité</Text>
            <Text style={styles.rowSubtitle}>{user?.id_card_number}</Text>
          </View>
        </View>

        {/* Séparateur visuel entre les éléments */}
        <View style={styles.separator} />

        {/* Adresse */}
        <View style={styles.row}>
          <View style={styles.rowIcon}>
            <Text style={styles.icon}>📍</Text>
          </View>
          <View style={styles.rowContent}>
            <Text style={styles.rowTitle}>Adresse</Text>
            <Text style={styles.rowSubtitle}>{user?.address}</Text>
          </View>
        </View>

        <View style={styles.separator} />

        {/* Email */}
        <View style={styles.row}>
          <View style={styles.rowIcon}>
            <Text style={styles.icon}>📧</Text>
          </View>
          <View style={styles.rowContent}>
            <Text style={styles.rowTitle}>Email</Text>
            <Text style={styles.rowSubtitle}>{user?.email}</Text>
          </View>
        </View>
      </View>

      {/* Section Statistiques du compte */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Compte</Text>
      </View>

      <View style={styles.section}>
        <View style={styles.row}>
          <View style={styles.rowIcon}>
            <Text style={styles.icon}>🆔</Text>
          </View>
          <View style={styles.rowContent}>
            <Text style={styles.rowTitle}>ID Livreur</Text>
            <Text style={styles.rowSubtitle}>#{user?.id}</Text>
          </View>
        </View>

        <View style={styles.separator} />

        <View style={styles.row}>
          <View style={styles.rowIcon}>
            <Text style={styles.icon}>📅</Text>
          </View>
          <View style={styles.rowContent}>
            <Text style={styles.rowTitle}>Dernière mise à jour</Text>
            <Text style={styles.rowSubtitle}>
              {user?.updated_at ? formatDate(user.updated_at) : 'N/A'}
            </Text>
          </View>
        </View>
      </View>

      {/* Bouton de déconnexion */}
      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
        <Text style={styles.logoutText}>Déconnexion</Text>
      </TouchableOpacity>

      {/* Version de l'application */}
      <Text style={styles.version}>Version 1.0.0</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { 
    flex: 1, 
    backgroundColor: '#f5f5f5' 
  },
  header: { 
    backgroundColor: '#fff', 
    padding: 24, 
    alignItems: 'center', 
    borderBottomWidth: 1, 
    borderBottomColor: '#eee',
    paddingBottom: 32
  },
  // Avatar avec image réelle
  avatarImage: {
    width: 100,
    height: 100,
    borderRadius: 50,
    marginBottom: 12,
    borderWidth: 3,
    borderColor: '#4169E1'
  },
  // Avatar avec initiales (fallback)
  avatar: { 
    width: 100, 
    height: 100, 
    borderRadius: 50, 
    backgroundColor: '#4169E1', 
    justifyContent: 'center', 
    alignItems: 'center', 
    marginBottom: 12,
    borderWidth: 3,
    borderColor: '#3157CC'
  },
  avatarText: { 
    fontSize: 40, 
    fontWeight: 'bold', 
    color: '#fff' 
  },
  name: { 
    fontSize: 22, 
    fontWeight: 'bold', 
    color: '#333',
    marginBottom: 4
  },
  email: { 
    fontSize: 15, 
    color: '#666', 
    marginTop: 4 
  },
  memberBadge: {
    backgroundColor: '#f0f7ff',
    paddingHorizontal: 16,
    paddingVertical: 6,
    borderRadius: 20,
    marginTop: 12
  },
  memberBadgeText: {
    fontSize: 12,
    color: '#4169E1',
    fontWeight: '600'
  },
  sectionHeader: {
    paddingHorizontal: 16,
    paddingTop: 24,
    paddingBottom: 8
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: '#666',
    textTransform: 'uppercase',
    letterSpacing: 0.5
  },
  section: { 
    backgroundColor: '#fff', 
    marginTop: 4,
    borderTopWidth: 1, 
    borderBottomWidth: 1, 
    borderColor: '#eee' 
  },
  row: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    padding: 16,
    minHeight: 60
  },
  rowIcon: {
    width: 40,
    alignItems: 'center',
    justifyContent: 'center'
  },
  icon: {
    fontSize: 24
  },
  rowContent: {
    flex: 1,
    marginLeft: 8
  },
  rowTitle: { 
    fontSize: 16, 
    fontWeight: '600', 
    color: '#333',
    marginBottom: 2
  },
  rowSubtitle: { 
    fontSize: 14, 
    color: '#666', 
    marginTop: 2,
    lineHeight: 20
  },
  separator: {
    height: 1,
    backgroundColor: '#f0f0f0',
    marginLeft: 56 // Aligné avec le texte (40px icon + 16px padding)
  },
  logoutButton: { 
    backgroundColor: '#DC143C', 
    margin: 16, 
    padding: 16, 
    borderRadius: 12, 
    marginTop: 32,
    shadowColor: '#DC143C',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
    elevation: 3
  },
  logoutText: { 
    color: '#fff', 
    textAlign: 'center', 
    fontSize: 16, 
    fontWeight: '700' 
  },
  version: { 
    textAlign: 'center', 
    color: '#999', 
    fontSize: 12, 
    marginTop: 20, 
    marginBottom: 40 
  }
});
