import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface Theme {
  id?: number;
  titre: string;
  description: string;
  statut?: 'EN_ATTENTE' | 'VALIDE' | 'REJETE';
  created_at?: string;
}

@Injectable({ providedIn: 'root' })
export class ThemeService {
  private http = inject(HttpClient);
  private api = 'http://localhost:8000/api';

  getMes()           { return this.http.get<Theme[]>(`${this.api}/themes/mes`); }
  soumettre(t: Pick<Theme, 'titre' | 'description'>) {
    return this.http.post<Theme>(`${this.api}/themes`, t);
  }
}
