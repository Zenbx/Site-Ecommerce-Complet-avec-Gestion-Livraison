import { Link, Stack } from 'expo-router';
import { StyleSheet, View, Text } from 'react-native';

export default function NotFoundScreen() {
  return (
    <>
      <Stack.Screen options={{ title: 'Page introuvable' }} />
      <View style={styles.container}>
        <Text style={styles.title}>404</Text>
        <Text style={styles.message}>Cette page n'existe pas.</Text>
        <Link href="/(tabs)" style={styles.link}>
          <Text style={styles.linkText}>Retour à l'accueil</Text>
        </Link>
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 20,
    backgroundColor: '#f5f5f5',
  },
  title: {
    fontSize: 64,
    fontWeight: 'bold',
    color: '#333',
  },
  message: {
    fontSize: 18,
    color: '#666',
    marginTop: 10,
    marginBottom: 30,
  },
  link: {
    paddingVertical: 12,
    paddingHorizontal: 24,
    backgroundColor: '#4169E1',
    borderRadius: 8,
  },
  linkText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});
