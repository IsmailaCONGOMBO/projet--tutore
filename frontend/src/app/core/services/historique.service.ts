import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Historique {
  id: number;
  user_id: number;
  action: string;
  cible_type: string | null;
  cible_id: number | null;
  details: string | null;
  ip_address: string | null;
  created_at: string;
  user?: {
    id: number;
    name: string;
  };
}

@Injectable({
  providedIn: 'root'
})
export class HistoriqueService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8000/api/historique';

  getRecentActions(): Observable<any> {
    return this.http.get<any>(this.apiUrl);
  }
}
