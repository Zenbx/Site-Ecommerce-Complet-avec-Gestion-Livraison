import api from './api';

export const getDeliveries = async (status?: string, date?: string) => {
  const params: any = {};
  if (status) params.status = status;
  if (date) params.date = date;
  
  const response: any = await api.get('/delivery-person/deliveries', { params });
  return response.data;
};

export const getDeliveryDetails = async (id: number) => {
  const response: any = await api.get(`/delivery-person/deliveries/${id}`);
  return response.data;
};

export const acceptDelivery = async (id: number) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/accept`);
  return response.data;
};

export const declineDelivery = async (id: number, reason: string) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/decline`, { reason });
  return response.data;
};

export const pickupDelivery = async (id: number, latitude: number, longitude: number) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/pickup`, {
    latitude,
    longitude,
    timestamp: new Date().toISOString(),
  });
  return response.data;
};

export const startDelivery = async (id: number, latitude: number, longitude: number) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/start`, {
    latitude,
    longitude,
    timestamp: new Date().toISOString(),
  });
  return response.data;
};

export const updateLocation = async (
  id: number,
  latitude: number,
  longitude: number,
  speed: number | null,
  heading: number | null
) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/location`, {
    latitude,
    longitude,
    speed,
    heading,
    timestamp: new Date().toISOString(),
  });
  return response.data;
};

export const scanQRCode = async (id: number, qrCodeData: string) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/scan-qr`, {
    qr_code_data: qrCodeData,
  });
  return response.data;
};

export const submitProof = async (id: number, formData: FormData) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/proof`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data;
};

export const completeDelivery = async (
  id: number,
  proofId: number,
  latitude: number,
  longitude: number,
  notes?: string
) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/complete`, {
    proof_id: proofId,
    latitude,
    longitude,
    delivered_at: new Date().toISOString(),
    notes,
  });
  return response.data;
};

export const reportIssue = async (id: number, formData: FormData) => {
  const response: any = await api.post(`/delivery-person/deliveries/${id}/report-issue`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data;
};

export const getHistory = async (page: number = 1, perPage: number = 20) => {
  const response: any = await api.get('/delivery-person/deliveries/history', {
    params: { page, per_page: perPage },
  });
  return response.data;
};

export const getStatistics = async (period: string = 'today') => {
  const response: any = await api.get('/delivery-person/profile/statistics', {
    params: { period },
  });
  return response.data;
};

export const updateAvailability = async (isAvailable: boolean, reason?: string) => {
  const response: any = await api.patch('/delivery-person/profile/availability', {
    is_available: isAvailable,
    reason,
  });
  return response.data;
};
