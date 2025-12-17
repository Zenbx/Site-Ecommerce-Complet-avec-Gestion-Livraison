// services/authService.ts
import api from './api';

interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    token: string;
    delivery_person: {
      id: number;
      name: string;
      email: string;
      phone: string;
      avatar?: string;
      is_available: boolean;
    };
  };
}

export const login = async (email: string, password: string) => {
  // L'intercepteur dans api.ts retourne déjà response.data
  // Donc response contient directement { success, message, data }
  const response: LoginResponse = await api.post('/auth/delivery-person/login', { email, password });
  
  return {
    token: response.data.token,  // Pas response.data.data.token
    user: response.data.delivery_person,  // Pas response.data.data.delivery_person
  };
};

export const logout = async () => {
  return await api.post('/auth/delivery-person/logout');
};

export const refreshToken = async () => {
  const response: any = await api.post('/auth/refresh');
  return response.data.token;
};

export const getMe = async () => {
  const response: any = await api.get('/auth/delivery-person/me');
  return response.data;
};
