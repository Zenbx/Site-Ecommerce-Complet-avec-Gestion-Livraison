export enum ProofType {
  SIGNATURE = 'signature',
  QR_CODE = 'qr_code',
  PHOTO = 'photo'
}

export enum ProofStatus {
  PENDING = 'PENDING',
  VALIDATED = 'VALIDATED',
  REJECTED = 'REJECTED'
}

export interface DeliveryDriver {
  id: number;
  name?: string;
  phone?: string;
  // Champs nouvelle API
  email?: string;
  is_available?: boolean;
  // Champs communs
  isAvailable: boolean;
  statistics?: any;
  rating: number;
  totalDeliveries: number;
  currentLocation?: {
    latitude: number;
    longitude: number;
    updatedAt: string;
  };
}

export interface DeliveryAddress {
  street: string;
  city: string;
  postalCode: string;
  country: string;
  latitude: number;
  longitude: number;
  instructions?: string;
}

export interface DeliveryProof {
  id: number;
  type: ProofType;
  url: string;

  recipientName?: string;
  createdAt: string;

  status: ProofStatus;

  validatedAt?: string;
  validatedBy?: string;
  rejectionReason?: string;

  driver?: {
    id: number;
    firstName: string;
    lastName: string;
  };
}

export interface Delivery {
  id: number;
  tracking_code: string;
  status: string;
  order: {
    id: number;
    order_number: string;
    total_amount: string;
    items_count: number;
  };
  client: {
    name: string;
    phone: string;
  };
  delivery_person: {
    id: number;
    name: string;
    is_available: boolean;
  } | null;
  delivery_address: string;
  created_at: string;
  delivered_at: string | null;

}

export interface DeliveryAssignmentRequest {
  deliveryId: number;
  driverId: number;
  estimatedTime?: string;
}

export interface DeliveryTrackingUpdate {
  deliveryId: number;
  status: string;
  location: {
    latitude: number;
    longitude: number;
  };
  timestamp: string;
}