import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { NotificationService, NotificationRequest } from '../../../core/services/notification.service';

@Component({
  selector: 'app-admin-notifications',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './notifications.html',
  styleUrl: './notifications.css'
})
export class AdminNotifications implements OnInit {
  private notificationService = inject(NotificationService);
  
  loading = signal(false);
  success = signal<string | null>(null);
  error = signal<string | null>(null);
  
  // Formulaire de notification
  notificationForm = signal<NotificationRequest>({
    titre: '',
    message: '',
    type: 'personnalise',
    cible: 'tous',
    filiere_id: undefined
  });
  
  // Types de notifications prédéfinies
  typesNotifications = [
    { value: 'themes', label: 'Période de soumission des thèmes', icon: 'theme' },
    { value: 'rapports', label: 'Période de dépôt des rapports', icon: 'report' },
    { value: 'personnalise', label: 'Message personnalisé', icon: 'message' }
  ];
  
  // Cibles possibles
  cibles = [
    { value: 'tous', label: 'Tous les utilisateurs', icon: 'all' },
    { value: 'etudiants', label: 'Étudiants uniquement', icon: 'student' },
    { value: 'enseignants', label: 'Enseignants uniquement', icon: 'teacher' },
    { value: 'filiere', label: 'Filière spécifique', icon: 'department' }
  ];

  ngOnInit() {}

  onTypeChange(type: string) {
    const currentForm = this.notificationForm();
    
    // Messages prédéfinis selon le type
    if (type === 'themes') {
      this.notificationForm.set({
        ...currentForm,
        titre: 'Période de soumission des thèmes',
        message: 'Chers étudiants, la période de soumission des thèmes de rapport est maintenant ouverte. Merci de soumettre vos propositions avant la date limite.'
      });
    } else if (type === 'rapports') {
      this.notificationForm.set({
        ...currentForm,
        titre: 'Période de dépôt des rapports',
        message: 'Chers étudiants, la période de dépôt des rapports de fin de cycle est maintenant ouverte. Veillez à respecter les délais et les formats requis.'
      });
    } else {
      this.notificationForm.set({
        ...currentForm,
        titre: '',
        message: ''
      });
    }
  }

  envoyerNotification() {
    const form = this.notificationForm();
    
    // Validation
    if (!form.titre.trim() || !form.message.trim()) {
      this.error.set('Veuillez remplir tous les champs obligatoires');
      return;
    }
    
    if (form.cible === 'filiere' && !form.filiere_id) {
      this.error.set('Veuillez sélectionner une filière');
      return;
    }
    
    this.loading.set(true);
    this.error.set(null);
    this.success.set(null);
    
    this.notificationService.envoyerNotification(form).subscribe({
      next: () => {
        this.success.set('Notification envoyée avec succès !');
        this.loading.set(false);
        
        // Réinitialiser le formulaire
        setTimeout(() => {
          this.notificationForm.set({
            titre: '',
            message: '',
            type: 'personnalise',
            cible: 'tous',
            filiere_id: undefined
          });
          this.success.set(null);
        }, 3000);
      },
      error: (err) => {
        console.error('Erreur lors de l\'envoi:', err);
        const errorMsg = err.error?.message || 'Erreur lors de l\'envoi de la notification';
        this.error.set(errorMsg);
        this.loading.set(false);
      }
    });
  }

  // Simuler des filières (à remplacer par un vrai service)
  filieres = [
    { id: 1, nom: 'Génie Informatique' },
    { id: 2, nom: 'Génie Civil' },
    { id: 3, nom: 'Génie Électrique' },
    { id: 4, nom: 'Management' }
  ];

  // Getters pour simplifier le template
  getTypeLabel(): string {
    const form = this.notificationForm();
    const type = this.typesNotifications.find(t => t.value === form.type);
    return type?.label || 'Personnalisée';
  }

  getCibleLabel(): string {
    const form = this.notificationForm();
    const cible = this.cibles.find(c => c.value === form.cible);
    return cible?.label || 'Tous';
  }
}
