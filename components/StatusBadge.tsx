import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { DELIVERY_STATUS_COLORS, DELIVERY_STATUS_LABELS } from '../constants/app';

interface Props {
  status: string;
}

export default function StatusBadge({ status }: Props) {
  return (
    <View style={[styles.badge, { backgroundColor: DELIVERY_STATUS_COLORS[status] }]}>
      <Text style={styles.text}>{DELIVERY_STATUS_LABELS[status]}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 6 },
  text: { color: '#fff', fontSize: 13, fontWeight: '600' },
});
