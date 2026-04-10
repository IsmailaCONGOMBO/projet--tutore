import { Component, inject, signal, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { SlicePipe } from '@angular/common';
import { UserService, User } from '../../core/services/user.service';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-utilisateurs',
  imports: [FormsModule, RouterLink, SlicePipe],
  templateUrl: './utilisateurs.html',
  styleUrl: './utilisateurs.css'
})
export class Utilisateurs implements OnInit {
  private userSvc = inject(UserService);
  private auth    = inject(AuthService);

  users    = signal<User[]>([]);
  loading  = signal(false);
  error    = signal('');
  success  = signal('');

  showModal  = signal(false);
  isEditing  = signal(false);
  submitting = signal(false);
  deleteConfirmId = signal<number | null>(null);

  currentUser = this.isBrowser ? JSON.parse(localStorage.getItem('user') || '{}') : {};
  private get isBrowser() { return typeof window !== 'undefined'; }

  form: User = this.emptyForm();

  readonly roles = [
    { value: 'admin',            label: 'Administrateur' },
    { value: 'enseignant',       label: 'Enseignant' },
    { value: 'chef_departement', label: 'Chef de département' },
    { value: 'etudiant',         label: 'Étudiant' },
  ];

  readonly roleLabels: Record<string, string> = {
    admin:            'Administrateur',
    enseignant:       'Enseignant',
    chef_departement: 'Chef de département',
    etudiant:         'Étudiant',
  };

  ngOnInit() { this.load(); }

  load() {
    this.loading.set(true);
    this.userSvc.getAll().subscribe({
      next: (data) => { this.users.set(data); this.loading.set(false); },
      error: ()     => { this.error.set('Erreur lors du chargement.'); this.loading.set(false); }
    });
  }

  openCreate() {
    this.form = this.emptyForm();
    this.isEditing.set(false);
    this.showModal.set(true);
    this.error.set('');
  }

  openEdit(u: User) {
    this.form = { ...u, password: '' };
    this.isEditing.set(true);
    this.showModal.set(true);
    this.error.set('');
  }

  closeModal() { this.showModal.set(false); }

  submit() {
    if (!this.form.name || !this.form.email || !this.form.role) {
      this.error.set('Veuillez remplir tous les champs obligatoires.');
      return;
    }
    if (!this.isEditing() && !this.form.password) {
      this.error.set('Le mot de passe est obligatoire.');
      return;
    }
    this.submitting.set(true);
    this.error.set('');

    const payload: Partial<User> = {
      name: this.form.name,
      email: this.form.email,
      role: this.form.role,
    };
    if (this.form.password) payload['password'] = this.form.password;

    const req = this.isEditing()
      ? this.userSvc.update(this.form.id!, payload)
      : this.userSvc.create(payload as User);

    req.subscribe({
      next: (saved) => {
        this.submitting.set(false);
        this.showModal.set(false);
        this.notify(this.isEditing() ? 'Utilisateur modifié.' : 'Utilisateur créé.');
        if (this.isEditing()) {
          this.users.update(list => list.map(u => u.id === saved.id ? saved : u));
        } else {
          this.users.update(list => [saved, ...list]);
        }
      },
      error: (err) => {
        this.submitting.set(false);
        this.error.set(err.error?.message || 'Une erreur est survenue.');
      }
    });
  }

  confirmDelete(id: number) { this.deleteConfirmId.set(id); }
  cancelDelete()             { this.deleteConfirmId.set(null); }

  doDelete(id: number) {
    this.userSvc.delete(id).subscribe({
      next: () => {
        this.users.update(list => list.filter(u => u.id !== id));
        this.deleteConfirmId.set(null);
        this.notify('Utilisateur supprimé.');
      },
      error: (err) => {
        this.deleteConfirmId.set(null);
        this.error.set(err.error?.message || 'Erreur lors de la suppression.');
      }
    });
  }

  logout() { this.auth.logout(); }

  private emptyForm(): User {
    return { name: '', email: '', role: 'etudiant', password: '' };
  }

  private notify(msg: string) {
    this.success.set(msg);
    setTimeout(() => this.success.set(''), 3500);
  }
}
