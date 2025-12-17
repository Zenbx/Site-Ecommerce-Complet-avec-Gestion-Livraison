// services/geocodingService.ts
// Service de géocodage gratuit avec Nominatim (OpenStreetMap)

interface GeocodingResult {
  latitude: number;
  longitude: number;
  display_name: string;
}

/**
 * Convertir une adresse en coordonnées GPS
 * Utilise l'API Nominatim (OpenStreetMap) - GRATUIT
 */
export const geocodeAddress = async (address: string): Promise<GeocodingResult | null> => {
  try {
    // Nettoyer l'adresse
    const cleanAddress = address.trim();
    
    if (!cleanAddress) {
      console.warn('⚠️ Adresse vide');
      return null;
    }

    console.log('🔍 Géocodage de:', cleanAddress);

    // API Nominatim (OpenStreetMap) - Gratuit
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(cleanAddress)}&limit=1`;

    const response = await fetch(url, {
      headers: {
        'User-Agent': 'DeliveryApp/1.0', // Requis par Nominatim
      },
    });

    if (!response.ok) {
      throw new Error(`Erreur HTTP: ${response.status}`);
    }

    const data = await response.json();

    if (data && data.length > 0) {
      const result = {
        latitude: parseFloat(data[0].lat),
        longitude: parseFloat(data[0].lon),
        display_name: data[0].display_name,
      };

      console.log('✅ Coordonnées trouvées:', result);
      return result;
    }

    console.warn('⚠️ Aucune coordonnée trouvée pour:', cleanAddress);
    return null;

  } catch (error) {
    console.error('❌ Erreur de géocodage:', error);
    return null;
  }
};

/**
 * Géocoder une adresse avec fallback sur coordonnées par défaut
 */
export const geocodeAddressWithFallback = async (
  address: string,
  fallbackLat: number = 4.0511, // Centre de Douala par défaut
  fallbackLng: number = 9.7679
): Promise<GeocodingResult> => {
  const result = await geocodeAddress(address);
  
  if (result) {
    return result;
  }

  // Fallback sur les coordonnées par défaut
  console.log('🔄 Utilisation des coordonnées par défaut');
  return {
    latitude: fallbackLat,
    longitude: fallbackLng,
    display_name: address,
  };
};

/**
 * Géocoder plusieurs adresses en batch
 * Ajoute un délai entre chaque requête pour respecter les limites de Nominatim (1 req/sec)
 */
export const geocodeMultipleAddresses = async (
  addresses: string[]
): Promise<(GeocodingResult | null)[]> => {
  const results: (GeocodingResult | null)[] = [];

  for (const address of addresses) {
    const result = await geocodeAddress(address);
    results.push(result);

    // Délai de 1 seconde entre chaque requête (limite Nominatim)
    await new Promise(resolve => setTimeout(resolve, 1000));
  }

  return results;
};

/**
 * Vérifier si des coordonnées sont valides
 */
export const isValidCoordinates = (lat: number, lng: number): boolean => {
  return (
    lat !== 0 &&
    lng !== 0 &&
    lat >= -90 &&
    lat <= 90 &&
    lng >= -180 &&
    lng <= 180
  );
};

/**
 * Coordonnées de référence pour Douala
 */
export const DOUALA_LOCATIONS = {
  centre: { latitude: 4.0511, longitude: 9.7679, name: "Douala Centre" },
  bonanjo: { latitude: 4.0511, longitude: 9.7679, name: "Bonanjo" },
  akwa: { latitude: 4.0467, longitude: 9.7043, name: "Akwa" },
  bonapriso: { latitude: 4.0615, longitude: 9.7217, name: "Bonapriso" },
  newBell: { latitude: 4.0600, longitude: 9.7500, name: "New Bell" },
  bali: { latitude: 4.0333, longitude: 9.7167, name: "Bali" },
  deido: { latitude: 4.0656, longitude: 9.7406, name: "Deido" },
  makepe: { latitude: 4.0833, longitude: 9.7333, name: "Makepé" },
};

/**
 * Extraire les coordonnées d'un quartier/zone connu de Douala
 */
export const getCoordinatesFromDistrict = (address: string): GeocodingResult | null => {
  const addressLower = address.toLowerCase();

  for (const [key, value] of Object.entries(DOUALA_LOCATIONS)) {
    if (addressLower.includes(key) || addressLower.includes(value.name.toLowerCase())) {
      console.log(`📍 Quartier détecté: ${value.name}`);
      return {
        latitude: value.latitude,
        longitude: value.longitude,
        display_name: value.name,
      };
    }
  }

  return null;
};