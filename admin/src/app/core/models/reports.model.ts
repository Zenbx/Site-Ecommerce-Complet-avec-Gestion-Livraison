export type ReportPeriod = 'today' | 'week' | 'month' | 'year' | 'custom';

export interface ReportParams {
  period: ReportPeriod;
  start_date?: string;
  end_date?: string;
}

// Stats globales de livraison
export interface DeliveriesReportData {
  period: {
    start: string;
    end: string;
  };
  totals: {
    total: number;
    delivered: number;
    failed: number;
    pending: number;
    canceled: number;
  };
  rates: {
    successRate: number;
    failureRate: number;
  };
  timing: {
    averageTime: string; // ex: "45m" ou "1h 20m"
    averageDistance?: string;
  };
  by_day: Array<{
    day: string; // Label (ex: "Lun", "12/05")
    count: number;
  }>;
}

// Stats par livreur
export interface DeliveryPersonStat {
  id: number;
  name: string;
  totalDeliveries: number;
  completedDeliveries: number;
  failedDeliveries: number;
  successRate: number;
  rating: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
}