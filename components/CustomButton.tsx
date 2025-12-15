import React from 'react';
import { TouchableOpacity, Text, StyleSheet, ActivityIndicator } from 'react-native';

interface Props {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'secondary' | 'danger';
  loading?: boolean;
  disabled?: boolean;
}

export default function CustomButton({ title, onPress, variant = 'primary', loading, disabled }: Props) {
  const getBackgroundColor = () => {
    if (disabled || loading) return '#a0a0a0';
    switch (variant) {
      case 'secondary': return '#666';
      case 'danger': return '#DC143C';
      default: return '#4169E1';
    }
  };

  return (
    <TouchableOpacity
      style={[styles.button, { backgroundColor: getBackgroundColor() }]}
      onPress={onPress}
      disabled={disabled || loading}
    >
      {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.text}>{title}</Text>}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  button: { padding: 16, borderRadius: 8, alignItems: 'center' },
  text: { color: '#fff', fontSize: 16, fontWeight: '600' },
});
