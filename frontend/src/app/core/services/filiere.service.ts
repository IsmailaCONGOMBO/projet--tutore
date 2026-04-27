import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Filiere {
  id: number;
  nom: string;
  code?: string;
  description?: string;
  active?: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class FiliereService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8000/api/filieres';

  getFilieres(): Observable<Filiere[]> {
    return this.http.get<Filiere[]>(this.apiUrl);
  }

  createFiliere(filiere: Partial<Filiere>): Observable<Filiere> {
    return this.http.post<Filiere>(this.apiUrl, filiere);
  }

  updateFiliere(id: number, filiere: Partial<Filiere>): Observable<Filiere> {
    return this.http.put<Filiere>(`${this.apiUrl}/${id}`, filiere);
  }

  deleteFiliere(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }
}
