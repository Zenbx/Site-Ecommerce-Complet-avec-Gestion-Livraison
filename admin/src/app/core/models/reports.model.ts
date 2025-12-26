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
    deliveries: number;
    failed: number;
    in_progress: number;
    pending: number;
    successful: number;
  };

  rates: {
    failure_rate: number;
    success_rate: number;
  };

  timing: {
    average_delivery_time: string;
    fastest_delivery: {
      tracking_code: string;
      time: string;
    };
    slowest_delivery: {
      tracking_code: string;
      time: string;
    };
  };

  by_day: Array<{
    date: string;
    failed: number;
    successful: number;
    total: number;
  }>;
}

// Stats par livreur
export interface DeliveryPersonStat {
  id: number;
  email: string;
  name: string;
  statistics: {
    failed: number;
    success_rate: number;
    successful: number;
    total_deliveries: number;
  };
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
}