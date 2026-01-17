import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService, Contact } from '../../../core/services/api.service';

@Component({
    selector: 'app-admin-contacts',
    imports: [CommonModule, FormsModule],
    templateUrl: './contacts.component.html',
    styleUrls: ['./contacts.component.scss']
})
export class ContactsComponent implements OnInit {
  contacts: Contact[] = [];
  selectedContact: Contact | null = null;
  showModal = false;
  loading = false;
  error: string | null = null;
  filterRead: 'all' | 'read' | 'unread' = 'all';

  get contactsList(): Contact[] {
    return Array.isArray(this.contacts) ? this.contacts : [];
  }

  get filteredContacts(): Contact[] {
    let filtered = this.contactsList;
    
    if (this.filterRead === 'read') {
      filtered = filtered.filter(c => c.read === true);
    } else if (this.filterRead === 'unread') {
      filtered = filtered.filter(c => c.read !== true);
    }
    
    // Trier par date (plus récent en premier)
    return filtered.sort((a, b) => {
      const dateA = a.createdAt ? new Date(a.createdAt).getTime() : 0;
      const dateB = b.createdAt ? new Date(b.createdAt).getTime() : 0;
      return dateB - dateA;
    });
  }

  get unreadCount(): number {
    return this.contactsList.filter(c => c.read !== true).length;
  }

  constructor(private apiService: ApiService) {}

  ngOnInit() {
    this.loadContacts();
  }

  loadContacts() {
    this.loading = true;
    this.error = null;
    this.apiService.getContacts().subscribe({
      next: (data) => {
        this.contacts = Array.isArray(data) ? data : [];
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading contacts:', err);
        this.error = 'Erreur lors du chargement des contacts';
        this.loading = false;
        this.contacts = [];
      }
    });
  }

  viewContact(contact: Contact) {
    this.selectedContact = contact;
    this.showModal = true;
    
    // Marquer comme lu si ce n'est pas déjà fait
    if (!contact.read && contact.id) {
      this.markAsRead(contact.id);
    }
  }

  markAsRead(id: number) {
    const contact = this.contacts.find(c => c.id === id);
    if (!contact) return;

    const updatedContact = { ...contact, read: true };
    this.apiService.updateContact(id, updatedContact).subscribe({
      next: (saved) => {
        const index = this.contacts.findIndex(c => c.id === id);
        if (index !== -1) {
          this.contacts[index] = saved;
        }
      },
      error: (err) => {
        console.error('Error updating contact:', err);
      }
    });
  }

  markAsUnread(id: number) {
    const contact = this.contacts.find(c => c.id === id);
    if (!contact) return;

    const updatedContact = { ...contact, read: false };
    this.apiService.updateContact(id, updatedContact).subscribe({
      next: (saved) => {
        const index = this.contacts.findIndex(c => c.id === id);
        if (index !== -1) {
          this.contacts[index] = saved;
        }
      },
      error: (err) => {
        console.error('Error updating contact:', err);
      }
    });
  }

  deleteContact(id: number) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
      return;
    }

    this.loading = true;
    this.apiService.deleteContact(id).subscribe({
      next: () => {
        this.contacts = this.contacts.filter(c => c.id !== id);
        if (this.selectedContact?.id === id) {
          this.closeModal();
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Error deleting contact:', err);
        alert('Erreur lors de la suppression');
        this.loading = false;
      }
    });
  }

  closeModal() {
    this.showModal = false;
    this.selectedContact = null;
  }

  formatDate(dateString?: string): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  getSubjectLabel(subject: string): string {
    const labels: { [key: string]: string } = {
      'info': 'Demande d\'information',
      'sales': 'Demande commerciale',
      'support': 'Support technique',
      'partnership': 'Partenariat'
    };
    return labels[subject] || subject;
  }
}
