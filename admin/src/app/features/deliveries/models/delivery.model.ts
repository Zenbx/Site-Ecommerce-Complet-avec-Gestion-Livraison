// src/app/features/deliveries/models/delivery.model.ts

export enum DeliveryStatus {
  PENDING = 'pending',
  ASSIGNED = 'assigned',
  IN_PROGRESS = 'in_progress',
  DELIVERED = 'delivered',
  FAILED = 'failed',
  CANCELLED = 'cancelled'
}

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
  // Champs legacy
  firstName?: string;
  lastName?: string;
  phone?: string;
  vehicleType?: string;
  vehiclePlate?: string;
  // Champs nouvelle API
  name?: string;
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
  orderNumber: string;
  customer: {
    name: string;
    phone: string;
    email?: string;
  };
  address: DeliveryAddress;
  status: DeliveryStatus;
  driver?: DeliveryDriver;
  assignedAt?: string;
  pickedUpAt?: string;
  deliveredAt?: string;
  estimatedDeliveryTime?: string;
  proof?: DeliveryProof;
  notes?: string;
  priority: 'low' | 'medium' | 'high';
  createdAt: string;
  updatedAt: string;
}

export interface DeliveryAssignmentRequest {
  deliveryId: number;
  driverId: number;
  estimatedTime?: string;
}

export interface DeliveryTrackingUpdate {
  deliveryId: number;
  status: DeliveryStatus;
  location: {
    latitude: number;
    longitude: number;
  };
  timestamp: string;
}