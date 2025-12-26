/**
 * Modèle de données pour le Dashboard Logistique
 */

export interface RevenueStats {
  total: number;
  today: number;
  this_week: number;
  this_month: number;
  average_order_value: number;
}

export interface OrderStats {
  total: number;
  pending: number;
  confirmed: number;
  processing: number;
  shipped: number;
  delivered: number;
  cancelled: number;
}

export interface ProductStats {
  total: number;
  active: number;
  out_of_stock: number;
  low_stock: number;
}

export interface CustomerStats {
  total: number;
  new: number;
  active: number;
  retention_rate: number;
}

export interface DeliveryStats {
  total: number;
  pending: number;
  assigned: number;
  in_transit: number;
  delivered: number;
  failed: number;
  success_rate: number;
}

export interface GrowthStats {
  revenue_growth: number;
  current_revenue: number;
  previous_revenue: number;
}

export interface DashboardStats {
  revenue: RevenueStats;
  orders: OrderStats;
  products: ProductStats;
  customers: CustomerStats;
  deliveries: DeliveryStats;
  growth: GrowthStats;
}

export interface DashboardOverviewResponse {
  success: boolean;
  data: {
    period: string;
    statistics: DashboardStats;
    generated_at: string;
  };
}

export interface SalesChartData {
  labels: string[];
  orders: number[];
  revenue: number[];
}

export interface SalesChartResponse {
  success: boolean;
  data: SalesChartData;
}

export interface TopProduct {
  id: number;
  name: string;
  image_url: string;
  total_sold: number;
  total_revenue: string;
  total_revenue_raw: number;
}

export interface TopProductsResponse {
  success: boolean;
  data: TopProduct[];
}

export interface DriverDeliveryStats {
  total: number;
  delivered: number;
  failed: number;
  in_progress: number;
  success_rate: number;
}

export interface DriverPerformanceMetrics {
  average_delivery_time: string; // Format: "247 minutes"
  on_time_rate: number;
}

export interface DriverStatistics {
  period: string;
  deliveries: DriverDeliveryStats;
  performance: DriverPerformanceMetrics;
}

export interface DriverPerformance {
  id: number;
  driverName: string;
  statistics: DriverStatistics;
  deliveriesCompleted: number;
  successRate: number;
  averageTime: number;
}

export interface DeliveryPerformanceResponse {
  success: boolean;
  data: Array<{
    id: number;
    name: string;
    statistics: DriverStatistics;
  }>;
}

export interface RecentActivity {
  type: string;
  title: string;
  link: string;
  message: string;
  timestamp: string;
  time_ago: string;
}

export interface RecentActivityResponse {
  success: boolean;
  data: RecentActivity[];
}

export type DeliveryStatus = 
  | 'ready_to_ship'
  | 'in_transit'
  | 'delivered'
  | 'failed'
  | 'returned';

export interface DashboardAlert {
  type: 'info' | 'warning' | 'error';
  message: string;
  timestamp: Date;
}

export type DashboardPeriod = 'today' | 'week' | 'month' | 'year' | 'all';
export type ChartPeriod = 'week' | 'month' | 'year';
export type ChartGroupBy = 'day' | 'week' | 'month';