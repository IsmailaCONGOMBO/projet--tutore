import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface User {
  id?: number;
  name: string;
  email: string;
  role: 'admin' | 'etudiant' | 'enseignant' | 'chef_departement';
  password?: string;
  created_at?: string;
}

@Injectable({ providedIn: 'root' })
export class UserService {
  private http = inject(HttpClient);
  private api = 'http://localhost:8000/api/users';

  getAll()               { return this.http.get<User[]>(this.api); }
  create(u: User)        { return this.http.post<User>(this.api, u); }
  update(id: number, u: Partial<User>) { return this.http.put<User>(`${this.api}/${id}`, u); }
  delete(id: number)     { return this.http.delete<{ message: string }>(`${this.api}/${id}`); }
}
