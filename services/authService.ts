import api from './api';

// Interface pour les données du livreur
interface DeliveryPerson {
  id: number;
  name: string;
  email: string;
  id_card_number: string;
  address: string;
  photo_url: string;
  is_available: boolean;
  created_at: string;
  updated_at: string;
}

// Interface pour la réponse de login
interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    delivery_person: DeliveryPerson;
    token: string;
    token_type: string;
  };
}

// Interface pour la réponse de l'utilisateur connecté (profil)
interface MeResponse {
  success: boolean;
  message: string;
  data: DeliveryPerson;
}

export const login = async (email: string, password: string) => {
  const response = await api.post<LoginResponse>('/auth/delivery-person/login', { 
    email, 
    password 
  });
  
  return {
    token: response.data.data.token,
    user: response.data.data.delivery_person,
    token_type: response.data.data.token_type
  };
};

export const logout = async () => {
  return await api.post('/auth/logout');
};

export const refreshToken = async () => {
  const response = await api.post<{ data: { token: string } }>('/auth/refresh');
  return response.data.data.token;
};

export const getMe = async () => {
  const response = await api.get<MeResponse>('/auth/me');
  return response.data.data;
};