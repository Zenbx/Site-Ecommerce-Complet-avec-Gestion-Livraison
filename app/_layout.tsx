import { useEffect } from 'react';
import { Stack, useRouter, useSegments } from 'expo-router';
import { AuthProvider, useAuth } from '../context/AuthContext';
import { DeliveryProvider } from '../context/DeliveryContext';

function RootLayoutNav() {
  const { token, loading } = useAuth();
  const segments = useSegments();
  const router = useRouter();

  useEffect(() => {
    if (loading) return;

    const inAuthGroup = segments[0] === '(auth)';

    if (!token && !inAuthGroup) {
      // Rediriger vers login si non authentifié
      router.replace('/(auth)/login');
    } else if (token && inAuthGroup) {
      // Rediriger vers tabs si déjà authentifié
      router.replace('/(tabs)');
    }
  }, [token, segments, loading]);

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(auth)" />
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="delivery" />
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <AuthProvider>
      <DeliveryProvider>
        <RootLayoutNav />
      </DeliveryProvider>
    </AuthProvider>
  );
}
