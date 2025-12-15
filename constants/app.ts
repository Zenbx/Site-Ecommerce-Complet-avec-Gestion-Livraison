// Constantes de l'application

// Statuts des livraisons
export const DELIVERY_STATUS = {
  ASSIGNED: 'assigned',
  ACCEPTED: 'accepted',
  PICKED_UP: 'picked_up',
  IN_TRANSIT: 'in_transit',
  DELIVERED: 'delivered',
  FAILED: 'failed',
  PENDING_VERIFICATION: 'pending_verification',
} as const;

// Labels des statuts en français
export const DELIVERY_STATUS_LABELS = {
  [DELIVERY_STATUS.ASSIGNED]: 'Assignée',
  [DELIVERY_STATUS.ACCEPTED]: 'Acceptée',
  [DELIVERY_STATUS.PICKED_UP]: 'Récupérée',
  [DELIVERY_STATUS.IN_TRANSIT]: 'En transit',
  [DELIVERY_STATUS.DELIVERED]: 'Livrée',
  [DELIVERY_STATUS.FAILED]: 'Échouée',
  [DELIVERY_STATUS.PENDING_VERIFICATION]: 'En vérification',
};

// Couleurs des statuts
export const DELIVERY_STATUS_COLORS = {
  [DELIVERY_STATUS.ASSIGNED]: '#FFA500',
  [DELIVERY_STATUS.ACCEPTED]: '#4169E1',
  [DELIVERY_STATUS.PICKED_UP]: '#9370DB',
  [DELIVERY_STATUS.IN_TRANSIT]: '#1E90FF',
  [DELIVERY_STATUS.DELIVERED]: '#32CD32',
  [DELIVERY_STATUS.FAILED]: '#DC143C',
  [DELIVERY_STATUS.PENDING_VERIFICATION]: '#FFD700',
};

// Types de preuves de livraison
export const PROOF_TYPES = {
  PHOTO: 'photo',
  SIGNATURE: 'signature',
  QR_CODE: 'qr_code',
} as const;

// Types de problèmes
export const ISSUE_TYPES = {
  CLIENT_ABSENT: 'client_absent',
  ADDRESS_NOT_FOUND: 'address_not_found',
  CLIENT_REFUSED: 'client_refused',
  PACKAGE_DAMAGED: 'package_damaged',
  OTHER: 'other',
} as const;

// Labels des types de problèmes
export const ISSUE_TYPE_LABELS = {
  [ISSUE_TYPES.CLIENT_ABSENT]: 'Client absent/injoignable',
  [ISSUE_TYPES.ADDRESS_NOT_FOUND]: 'Adresse introuvable',
  [ISSUE_TYPES.CLIENT_REFUSED]: 'Client refuse le colis',
  [ISSUE_TYPES.PACKAGE_DAMAGED]: 'Colis endommagé',
  [ISSUE_TYPES.OTHER]: 'Autre problème',
};

// Périodes pour les statistiques
export const STATS_PERIODS = {
  TODAY: 'today',
  WEEK: 'week',
  MONTH: 'month',
  YEAR: 'year',
} as const;

// Clés AsyncStorage
export const STORAGE_KEYS = {
  AUTH_TOKEN: '@auth_token',
  USER_DATA: '@user_data',
  PENDING_PROOFS: '@pending_proofs',
  OFFLINE_DELIVERIES: '@offline_deliveries',
  LAST_LOCATION: '@last_location',
} as const;

// Messages d'erreur
export const ERROR_MESSAGES = {
  NETWORK_ERROR: 'Erreur de connexion. Vérifiez votre connexion internet.',
  UNAUTHORIZED: 'Session expirée. Veuillez vous reconnecter.',
  SERVER_ERROR: 'Erreur serveur. Veuillez réessayer plus tard.',
  LOCATION_PERMISSION: 'Permission de localisation refusée.',
  CAMERA_PERMISSION: 'Permission caméra refusée.',
  INVALID_QR: 'QR code invalide ou ne correspond pas à cette livraison.',
};

// Messages de succès
export const SUCCESS_MESSAGES = {
  DELIVERY_ACCEPTED: 'Livraison acceptée avec succès',
  DELIVERY_PICKED_UP: 'Colis récupéré avec succès',
  DELIVERY_STARTED: 'Livraison démarrée',
  DELIVERY_COMPLETED: 'Livraison complétée avec succès',
  PROOF_UPLOADED: 'Preuve de livraison envoyée',
  ISSUE_REPORTED: 'Problème signalé avec succès',
};

// Regex de validation
export const VALIDATION_REGEX = {
  EMAIL: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
  PHONE: /^(\+237|237)?[6][0-9]{8}$/,
  ORDER_NUMBER: /^ORD-\d{6,}$/,
};
