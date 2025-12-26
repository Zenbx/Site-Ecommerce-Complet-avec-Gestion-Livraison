// src/app/core/models/order.model.ts

export interface Client {
  id: number;
  name: string;
  email: string;
  address: string;
  orders_count: number;
  total_spent: number;
  has_active_cart: boolean;
  created_at: string;
  updated_at: string;
  member_since: string;
}

export interface Product {
  id: number;
  name: string;
  image_url: string;
}

export interface OrderItem {
  id: number;
  product: Product;
  quantity: number;
  unit_price: string;
  subtotal: string;
}

export interface DeliveryPerson {
  id: number;
  name: string;
  phone: string;
  photo_url: string | null;
  is_available: boolean;
}

export interface QRCode {
  token: string;
  status: string;
  expires_at: string;
  scanned_at: string | null;
  url: string;
}

export interface ProofOfDelivery {
  image_url: string | null;
  submitted_at: string | null;
}

export interface DeliveryTimeline {
  created_at: string;
  delivered_at: string | null;
  updated_at: string;
  elapsed_time: string;
  total_duration: string | null;
}

export interface Delivery {
  id: number;
  tracking_code: string;
  status: string;
  status_label: string;
  order: {
    id: number;
  };
  delivery_person: DeliveryPerson | null;
  delivery_address: string;
  qr_code: QRCode;
  proof_of_delivery: ProofOfDelivery;
  timeline: DeliveryTimeline;
  estimated_delivery: string;
}

export interface Order {
  id: number;
  order_number: string;
  client: Client;
  subtotal: string;
  delivery_fee: string;
  total: string;
  status: OrderStatus;
  payment_status: PaymentStatus;
  status_label: string;
  payment_status_label: string;
  items: OrderItem[];
  items_count: number;
  delivery: Delivery | null;
  created_at: string;
  updated_at: string;
}

export enum OrderStatus {
  PENDING = 'PENDING',
  CONFIRMED = 'CONFIRMED',
  PROCESSING = 'PROCESSING',
  SHIPPED = 'SHIPPED',
  DELIVERED = 'DELIVERED',
  CANCELLED = 'CANCELLED'
}

export enum PaymentStatus {
  PENDING = 'PENDING',
  PAID = 'PAID',
  FAILED = 'FAILED',
  REFUNDED = 'REFUNDED'
}

export interface OrdersListResponse {
  data: Order[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface AssignDeliveryRequest {
  delivery_person_id: number;
  delivery_address: string;
}
