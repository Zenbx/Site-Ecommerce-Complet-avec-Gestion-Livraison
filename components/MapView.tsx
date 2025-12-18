import React from 'react';
import { View, StyleSheet, Platform, Linking, TouchableOpacity, Text } from 'react-native';
import { WebView } from 'react-native-webview';

interface MapViewProps {
  latitude: number;
  longitude: number;
  deliveryAddress?: string;
}

export default function MapView({ latitude, longitude, deliveryAddress }: MapViewProps) {
  if (Platform.OS === 'web') {
    return (
      <View style={styles.container}>
        <iframe
          width="100%"
          height="100%"
          style={{ border: 0, borderRadius: 24 }}
          src={`https://www.openstreetmap.org/export/embed.html?bbox=${longitude - 0.005},${latitude - 0.005},${longitude + 0.005},${latitude + 0.005}&layer=mapnik&marker=${latitude},${longitude}`}
          title="Carte de livraison"
        />
        <View style={styles.webOverlay}>
          <View style={styles.addressContainer}>
            <Text style={styles.pin}>📍</Text>
            <Text style={styles.overlayTitle} numberOfLines={1}>
              {deliveryAddress || 'Destination de livraison'}
            </Text>
          </View>
          <TouchableOpacity
            style={styles.overlayButton}
            onPress={() => {
              const url = `https://www.google.com/maps/search/?api=1&query=${latitude},${longitude}`;
              window.open(url, '_blank');
            }}
          >
            <Text style={styles.overlayButtonText}>Ouvrir dans Google Maps</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const htmlContent = `
    <!DOCTYPE html>
    <html>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <style>
          body { margin: 0; padding: 0; }
          #map { height: 100vh; width: 100vw; }
          .leaflet-control-attribution { display: none; }
          .custom-popup .leaflet-popup-content-wrapper {
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
          }
          .popup-title { font-weight: 800; font-family: system-ui; color: #1e293b; margin-bottom: 4px; }
          .popup-address { font-size: 13px; color: #64748b; font-family: system-ui; line-height: 1.4; }
          .marker-pin {
            background: #0077ff;
            width: 30px;
            height: 30px;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            margin: -15px 0 0 -15px;
            border: 3px solid #fff;
          }
        </style>
      </head>
      <body>
        <div id="map"></div>
        <script>
          const map = L.map('map', { zoomControl: false }).setView([${latitude}, ${longitude}], 16);
          L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
          
          const icon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div class='marker-pin'></div>",
            iconSize: [30, 42],
            iconAnchor: [15, 42]
          });

          const marker = L.marker([${latitude}, ${longitude}], { icon }).addTo(map);
          
          marker.bindPopup(\`
            <div class="custom-popup">
              <div class="popup-title">Destination</div>
              <div class="popup-address">${deliveryAddress || 'Adresse de livraison'}</div>
            </div>
          \`).openPopup();
        </script>
      </body>
    </html>
  `;

  return (
    <View style={styles.container}>
      <WebView
        originWhitelist={['*']}
        source={{ html: htmlContent }}
        style={styles.webview}
        scrollEnabled={false}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
    overflow: 'hidden',
  },
  webview: {
    flex: 1,
  },
  webOverlay: {
    position: 'absolute',
    bottom: 24,
    left: 20,
    right: 20,
    backgroundColor: '#ffffff',
    padding: 16,
    borderRadius: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 10,
    borderWidth: 1,
    borderColor: '#f1f5f9',
  },
  addressContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  pin: {
    fontSize: 18,
    marginRight: 8,
  },
  overlayTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#1e293b',
    flex: 1,
  },
  overlayButton: {
    backgroundColor: '#0077ff',
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: 'center',
  },
  overlayButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '800',
  },
});
