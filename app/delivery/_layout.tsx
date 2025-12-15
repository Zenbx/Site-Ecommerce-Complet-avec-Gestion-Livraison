import { Stack } from 'expo-router';

export default function DeliveryLayout() {
  return (
    <Stack screenOptions={{ headerShown: true }}>
      <Stack.Screen name="[id]" options={{ title: 'Détails' }} />
      <Stack.Screen name="proof" options={{ title: 'Preuve' }} />
      <Stack.Screen name="qr-scanner" options={{ title: 'Scanner' }} />
      <Stack.Screen name="report-issue" options={{ title: 'Problème' }} />
    </Stack>
  );
}
