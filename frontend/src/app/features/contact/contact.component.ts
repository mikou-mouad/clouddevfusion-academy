import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/services/api.service';

@Component({
  selector: 'app-contact',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './contact.component.html',
  styleUrls: ['./contact.component.scss']
})
export class ContactComponent {
  activeTab: 'contact' | 'rdv' = 'contact';
  
  // Informations de contact
  contactEmail = 'contact@clouddevfusion.com';
  contactPhone = '+33 7 58 59 75 95';
  contactAddress = {
    company: 'CloudDevFusion',
    street: '78, Avenue des Champs-Élysées, Bureau 326',
    city: '75008 Paris'
  };
  
  socialLinks = {
    linkedin: 'https://www.linkedin.com/company/clouddevfusion',
    twitter: 'https://twitter.com/clouddevfusion',
    facebook: 'https://www.facebook.com/clouddevfusion'
  };
  
  formData = {
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
    consent: false
  };

  rdvData = {
    name: '',
    email: '',
    phone: '',
    preferredDate: '',
    preferredTime: '',
    message: '',
    consent: false
  };

  loading = false;
  successMessage: string | null = null;
  errorMessage: string | null = null;

  constructor(private apiService: ApiService) {}

  onSubmit() {
    if (!this.formData.consent) {
      this.errorMessage = 'Vous devez accepter la politique de confidentialité';
      return;
    }

    this.loading = true;
    this.errorMessage = null;
    this.successMessage = null;

    const contactData = {
      name: this.formData.name.trim(),
      email: this.formData.email.trim(),
      phone: this.formData.phone.trim() || undefined,
      subject: this.formData.subject,
      message: this.formData.message.trim()
    };

    this.apiService.createContact(contactData).subscribe({
      next: () => {
        this.loading = false;
        this.successMessage = 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.';
        // Réinitialiser le formulaire
        this.formData = {
          name: '',
          email: '',
          phone: '',
          subject: '',
          message: '',
          consent: false
        };
        // Masquer le message après 5 secondes
        setTimeout(() => {
          this.successMessage = null;
        }, 5000);
      },
      error: (err) => {
        console.error('Error submitting contact:', err);
        this.loading = false;
        this.errorMessage = 'Erreur lors de l\'envoi du message. Veuillez réessayer.';
        setTimeout(() => {
          this.errorMessage = null;
        }, 5000);
      }
    });
  }

  onSubmitRdv() {
    if (!this.rdvData.consent) {
      this.errorMessage = 'Vous devez accepter la politique de confidentialité';
      return;
    }

    this.loading = true;
    this.errorMessage = null;
    this.successMessage = null;

    const dateTime = this.rdvData.preferredDate && this.rdvData.preferredTime
      ? `${this.rdvData.preferredDate} à ${this.rdvData.preferredTime}`
      : this.rdvData.preferredDate || 'Non spécifiée';

    const contactData = {
      name: this.rdvData.name.trim(),
      email: this.rdvData.email.trim(),
      phone: this.rdvData.phone.trim() || undefined,
      subject: 'rdv',
      message: `Demande de rendez-vous pour le ${dateTime}${this.rdvData.message ? '\n\n' + this.rdvData.message.trim() : ''}`
    };

    this.apiService.createContact(contactData).subscribe({
      next: () => {
        this.loading = false;
        this.successMessage = 'Votre demande de rendez-vous a été envoyée avec succès ! Nous vous contacterons rapidement pour confirmer.';
        // Réinitialiser le formulaire
        this.rdvData = {
          name: '',
          email: '',
          phone: '',
          preferredDate: '',
          preferredTime: '',
          message: '',
          consent: false
        };
        // Masquer le message après 5 secondes
        setTimeout(() => {
          this.successMessage = null;
        }, 5000);
      },
      error: (err) => {
        console.error('Error submitting RDV:', err);
        this.loading = false;
        this.errorMessage = 'Erreur lors de l\'envoi de la demande. Veuillez réessayer.';
        setTimeout(() => {
          this.errorMessage = null;
        }, 5000);
      }
    });
  }
}

