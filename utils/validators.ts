import { VALIDATION_REGEX } from '../constants/app';

export const validateEmail = (email: string): boolean => {
  return VALIDATION_REGEX.EMAIL.test(email.trim());
};

export const validatePhone = (phone: string): boolean => {
  return VALIDATION_REGEX.PHONE.test(phone.trim());
};

export const validateOrderNumber = (orderNumber: string): boolean => {
  return VALIDATION_REGEX.ORDER_NUMBER.test(orderNumber.trim());
};

export const validatePassword = (password: string): boolean => {
  return password.length >= 6;
};

export const validateCoordinates = (lat: number, lng: number): boolean => {
  return lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
};

export const validateRequired = (value: any): boolean => {
  if (typeof value === 'string') {
    return value.trim().length > 0;
  }
  return value !== null && value !== undefined;
};
