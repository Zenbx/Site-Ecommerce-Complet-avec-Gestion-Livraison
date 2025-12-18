import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useRouter, useSegments } from 'expo-router';
import { STORAGE_KEYS } from '../constants/app';
import * as authService from '../services/authService';

// Interface mise à jour pour correspondre exactement à la structure delivery_person de l'API
interface User {
  id: number;
  name: string;
  email: string;
  id_card_number: string;  // Nouveau : numéro de carte d'identité
  address: string;         // Nouveau : adresse complète
  photo_url: string;       // Nouveau : URL de la photo de profil
  is_available: boolean;
  created_at: string;      // Nouveau : date de création
  updated_at: string;      // Nouveau : date de mise à jour
}

interface AuthContextData {
  user: User | null;
  token: string | null;
  loading: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  updateUser: (userData: Partial<User>) => void;
}

const AuthContext = createContext<AuthContextData>({} as AuthContextData);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadStorageData();
  }, []);

  const loadStorageData = async () => {
    try {
      const storedToken = await AsyncStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
      const storedUser = await AsyncStorage.getItem(STORAGE_KEYS.USER_DATA);

      if (storedToken && storedUser) {
        setToken(storedToken);
        setUser(JSON.parse(storedUser));
      }
    } catch (error) {
      console.error('Erreur chargement données:', error);
    } finally {
      setLoading(false);
    }
  };

  const signIn = async (email: string, password: string) => {
    try {
      const response = await authService.login(email, password);
      
      // Extraction du token et des données utilisateur selon le nouveau schéma API
      // La réponse contient { success, message, data: { delivery_person, token, token_type } }
      const authToken = response.data.token;
      const userData = response.data.delivery_person;

      setToken(authToken);
      setUser(userData);

      // Sauvegarde dans le stockage local pour persistance entre les sessions
      await AsyncStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, authToken);
      await AsyncStorage.setItem(STORAGE_KEYS.USER_DATA, JSON.stringify(userData));
    } catch (error) {
      throw error;
    }
  };

  const signOut = async () => {
    try {
      await authService.logout();
    } catch (error) {
      console.error('Erreur logout API:', error);
    } finally {
      // Effacer les données localement même si l'API échoue
      setToken(null);
      setUser(null);
      await AsyncStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
      await AsyncStorage.removeItem(STORAGE_KEYS.USER_DATA);
      
      // La redirection sera gérée par useProtectedRoute dans _layout.tsx
    }
  };

  const updateUser = (userData: Partial<User>) => {
    if (user) {
      const updatedUser = { ...user, ...userData };
      setUser(updatedUser);
      AsyncStorage.setItem(STORAGE_KEYS.USER_DATA, JSON.stringify(updatedUser));
    }
  };

  return (
    <AuthContext.Provider value={{ user, token, loading, signIn, signOut, updateUser }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth doit être utilisé dans AuthProvider');
  }
  return context;
};
