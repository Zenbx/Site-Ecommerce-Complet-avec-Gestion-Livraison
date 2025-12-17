import React from 'react';
import { View, StyleSheet, Platform, Linking, TouchableOpacity, Text } from 'react-native';

interface MapViewProps {
  latitude: number;
  longitude: number;
  deliveryAddress?: string;
}

export default function MapView({ latitude, longitude, deliveryAddress }: MapViewProps) {
  // Pour le Web, on utilise un iframe
  if (Platform.OS === 'web') {
    return (
      <View style={styles.container}>
        <iframe
          width="100%"
          height="100%"
          style={{ border: 0 }}
          src={`https://www.openstreetmap.org/export/embed.html?bbox=${longitude - 0.01},${latitude - 0.01},${longitude + 0.01},${latitude + 0.01}&layer=mapnik&marker=${latitude},${longitude}`}
          title="Carte de livraison"
        />
        <View style={styles.webOverlay}>
          <Text style={styles.overlayTitle}>📍 {deliveryAddress || 'Point de livraison'}</Text>
          <TouchableOpacity
            style={styles.overlayButton}
            onPress={() => {
              const url = `https://www.openstreetmap.org/?mlat=${latitude}&mlon=${longitude}#map=16/${latitude}/${longitude}`;
              if (Platform.OS === 'web') {
                window.open(url, '_blank');
              } else {
                Linking.openURL(url);
              }
            }}
          >
            <Text style={styles.overlayButtonText}>🧭 Ouvrir dans OpenStreetMap</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  // Pour mobile, on utilise WebView
  const WebView = require('react-native-webview').WebView;

  const htmlContent = `
    <!DOCTYPE html>
    <html>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
          body { margin: 0; padding: 0; }
          #map { width: 100%; height: 100vh; }
          .custom-popup {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          }
          .popup-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
            color: #333;
          }
          .popup-address {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
          }
          .popup-button {
            background-color: #4169E1;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: block;
          }
        </style>
      </head>
      <body>
        <div id="map"></div>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
          const map = L.map('map').setView([${latitude}, ${longitude}], 15);

          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
          }).addTo(map);

          const customIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSI0MiIgdmlld0JveD0iMCAwIDMyIDQyIj48cGF0aCBmaWxsPSIjREMxNDNDIiBkPSJNMTYgMEMxMC40ODUgMCA2IDQuNDg1IDYgMTBjMCAxMSAxMCAyNCAxMCAyNHMxMC0xMyAxMC0yNGMwLTUuNTE1LTQuNDg1LTEwLTEwLTEweiIvPjxjaXJjbGUgY3g9IjE2IiBjeT0iMTAiIHI9IjQiIGZpbGw9IiNmZmYiLz48L3N2Zz4=',
            iconSize: [32, 42],
            iconAnchor: [16, 42],
            popupAnchor: [0, -42]
          });

          const marker = L.marker([${latitude}, ${longitude}], { icon: customIcon }).addTo(map);

          const popupContent = \`
            <div class="custom-popup">
              <div class="popup-title">📍 Point de livraison</div>
              <div class="popup-address">${deliveryAddress || 'Adresse de livraison'}</div>
              <a href="https://www.openstreetmap.org/directions?from=&to=${latitude},${longitude}" 
                 target="_blank" 
                 class="popup-button">
                🧭 Obtenir l'itinéraire
              </a>
            </div>
          \`;

          marker.bindPopup(popupContent, {
            maxWidth: 300,
            className: 'custom-leaflet-popup'
          }).openPopup();

          map.scrollWheelZoom.disable();
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
        javaScriptEnabled={true}
        domStorageEnabled={true}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    position: 'relative',
  },
  webview: {
    flex: 1,
  },
  webOverlay: {
    position: 'absolute',
    bottom: 20,
    left: 20,
    right: 20,
    backgroundColor: 'white',
    padding: 16,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  overlayTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 12,
  },
  overlayButton: {
    backgroundColor: '#4169E1',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  overlayButtonText: {
    color: 'white',
    fontWeight: '600',
    fontSize: 14,
  },
});