import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface Notification {
  id: number;
  message: string;
  lu: boolean;
  created_at: string;
}

export interface NotificationRequest {
  titre: string;
  message: string;
  type: 'themes' | 'rapports' | 'personnalise';
  cible?: 'tous' | 'etudiants' | 'enseignants' | 'filiere';
  filiere_id?: number;
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
  
  // Pour le Directeur Adjoint
  envoyerNotification(data: NotificationRequest) {
    return this.http.post(`${this.api}/notifications`, data);
  }
}
