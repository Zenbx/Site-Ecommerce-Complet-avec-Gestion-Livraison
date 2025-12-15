// src/app/core/services/websocket.service.ts

import { Injectable } from '@angular/core';
import { Observable, Subject, timer } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AuthService } from './auth.service';

export interface WebSocketMessage {
  type: string;
  data: any;
  timestamp: string;
}

@Injectable({
  providedIn: 'root'
})
export class WebSocketService {
  private socket: WebSocket | null = null;
  private messageSubject = new Subject<WebSocketMessage>();
  public messages$ = this.messageSubject.asObservable();
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectInterval = 3000;

  constructor(private authService: AuthService) {}

  connect(): void {
    if (this.socket?.readyState === WebSocket.OPEN) {
      return;
    }

    const token = this.authService.token;
    const wsUrl = `${environment.wsUrl}?token=${token}`;

    this.socket = new WebSocket(wsUrl);

    this.socket.onopen = () => {
      console.log('WebSocket connected');
      this.reconnectAttempts = 0;
    };

    this.socket.onmessage = (event) => {
      try {
        const message: WebSocketMessage = JSON.parse(event.data);
        this.messageSubject.next(message);
      } catch (error) {
        console.error('Error parsing WebSocket message:', error);
      }
    };

    this.socket.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    this.socket.onclose = () => {
      console.log('WebSocket disconnected');
      this.attemptReconnect();
    };
  }

  private attemptReconnect(): void {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);
      
      timer(this.reconnectInterval).subscribe(() => {
        this.connect();
      });
    } else {
      console.error('Max reconnection attempts reached');
    }
  }

  disconnect(): void {
    if (this.socket) {
      this.socket.close();
      this.socket = null;
    }
  }

  send(message: any): void {
    if (this.socket?.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(message));
    } else {
      console.error('WebSocket is not connected');
    }
  }

  // Méthodes spécifiques pour les types de messages
  subscribeToDeliveryUpdates(deliveryId: number): void {
    this.send({
      type: 'subscribe',
      channel: `delivery.${deliveryId}`
    });
  }

  subscribeToDriverLocation(driverId: number): void {
    this.send({
      type: 'subscribe',
      channel: `driver.${driverId}.location`
    });
  }

  subscribeToAllDeliveries(): void {
    this.send({
      type: 'subscribe',
      channel: 'deliveries.all'
    });
  }

  getMessagesByType(type: string): Observable<WebSocketMessage> {
    return new Observable(observer => {
      const subscription = this.messages$.subscribe(message => {
        if (message.type === type) {
          observer.next(message);
        }
      });
      return () => subscription.unsubscribe();
    });
  }
}