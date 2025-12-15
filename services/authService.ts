import api from './api';

interface LoginResponse {
  success: boolean;
  data: {
    token: string;
    user: {
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
  const response: LoginResponse = await api.post('/auth/login', { email, password });
  return {
    token: response.data.token,
    user: response.data.user,
  };
};

export const logout = async () => {
  return await api.post('/auth/logout');
};

export const refreshToken = async () => {
  const response: any = await api.post('/auth/refresh');
  return response.data.token;
};

export const getMe = async () => {
  const response: any = await api.get('/auth/me');
  return response.data;
};
