// src/app/features/products/products.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  quantity: number;
  category_id: number;
  image_url?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: number;
  name: string;
  description?: string;
  image_url?: string;
  created_at: string;
  updated_at: string;
}

@Injectable({ providedIn: 'root' })
export class ProductsService {
  private api = `${environment.apiUrl}`;

  constructor(private http: HttpClient) {}

  // --- Products ---
  getProducts(): Observable<{ success: boolean; data: Product[] }> {
    return this.http.get<{ success: boolean; data: Product[] }>(`${this.api}/products`);
  }

  getProduct(id: number): Observable<{ success: boolean; data: Product }> {
    return this.http.get<{ success: boolean; data: Product }>(`${this.api}/products/${id}`);
  }

  createProduct(payload: Partial<Product>): Observable<any> {
    return this.http.post(`${this.api}/products`, payload);
  }

  updateProduct(id: number, payload: Partial<Product>): Observable<any> {
    return this.http.put(`${this.api}/products/${id}`, payload);
  }

  deleteProduct(id: number): Observable<any> {
    return this.http.delete(`${this.api}/products/${id}`);
  }

  updateStock(id: number, quantity: number): Observable<any> {
    return this.http.patch(`${this.api}/products/${id}/stock`, { quantity });
  }

  // --- Categories ---
  getCategories(): Observable<{ success: boolean; data: Category[] }> {
    return this.http.get<{ success: boolean; data: Category[] }>(`${this.api}/categories`);
  }

  getCategory(id: number): Observable<{ success: boolean; data: Category }> {
    return this.http.get<{ success: boolean; data: Category }>(`${this.api}/categories/${id}`);
  }

  createCategory(payload: Partial<Category>): Observable<any> {
    return this.http.post(`${this.api}/categories`, payload);
  }

  updateCategory(id: number, payload: Partial<Category>): Observable<any> {
    return this.http.put(`${this.api}/categories/${id}`, payload);
  }

  deleteCategory(id: number): Observable<any> {
    return this.http.delete(`${this.api}/categories/${id}`);
  }
}
