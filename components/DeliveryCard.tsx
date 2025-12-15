import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import StatusBadge from './StatusBadge';
import { formatCurrency } from '../utils/helpers';

interface Props {
  delivery: any;
  distance?: string;
}

export default function DeliveryCard({ delivery, distance }: Props) {
  const router = useRouter();

  return (
    <TouchableOpacity 
      style={styles.card}
      onPress={() => router.push(`/delivery/${delivery.id}`)}
    >
      <View style={styles.header}>
        <Text style={styles.orderNumber}>{delivery.order_number}</Text>
        <StatusBadge status={delivery.status} />
      </View>
      <Text style={styles.customer}>{delivery.customer_name}</Text>
      <Text style={styles.address} numberOfLines={2}>{delivery.delivery_address}</Text>
      <View style={styles.footer}>
        <Text style={styles.amount}>{formatCurrency(delivery.total_amount)}</Text>
        {distance && <Text style={styles.distance}>{distance}</Text>}
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: { backgroundColor: '#fff', padding: 16, marginBottom: 12, borderRadius: 8, borderWidth: 1, borderColor: '#eee' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  orderNumber: { fontSize: 16, fontWeight: 'bold', color: '#333' },
  customer: { fontSize: 15, fontWeight: '600', color: '#333', marginBottom: 4 },
  address: { fontSize: 14, color: '#666', marginBottom: 8 },
  footer: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  amount: { fontSize: 16, fontWeight: 'bold', color: '#32CD32' },
  distance: { fontSize: 14, color: '#4169E1' },
});
