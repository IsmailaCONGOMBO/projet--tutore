import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface Notification {
  id: number;
  message: string;
  lu: boolean;
  created_at: string;
}

@Injectable({ providedIn: 'root' })
export class NotificationService {
  private http = inject(HttpClient);
  private api = 'http://localhost:8000/api';

  getMes()           { return this.http.get<Notification[]>(`${this.api}/notifications`); }
  marquerLu(id: number) {
    return this.http.patch(`${this.api}/notifications/${id}/lire`, {});
  }
  marquerTousLus()   { return this.http.patch(`${this.api}/notifications/lire-tout`, {}); }
}
