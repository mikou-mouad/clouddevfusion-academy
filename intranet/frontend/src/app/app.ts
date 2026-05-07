import { HttpClient, HttpHeaders } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { Component, OnDestroy, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { environment } from '../environments/environment';

@Component({
  selector: 'app-root',
  imports: [FormsModule, CommonModule],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class App implements OnDestroy {
  private readonly http = inject(HttpClient);
  private readonly apiBaseUrl = environment.apiBaseUrl;

  email = signal('');
  password = signal('');
  token = signal(localStorage.getItem('intranet_token') ?? '');
  currentRole = signal<UserRole | null>(null);
  loginError = signal('');
  loading = signal(false);
  dashboardLoading = signal(false);
  student = signal<{ firstName: string; lastName: string; email: string } | null>(null);
  trainer = signal<{ firstName: string; lastName: string; email: string } | null>(null);
  admin = signal<{ firstName: string; lastName: string; email: string } | null>(null);
  formations = signal<FormationDashboard[]>([]);
  attendanceSessions = signal<AttendanceSession[]>([]);
  adminClasses = signal<AdminClass[]>([]);
  adminStudents = signal<AdminPerson[]>([]);
  adminTrainers = signal<AdminPerson[]>([]);
  adminCreateNotice = signal('');
  adminCreateError = signal('');
  showAdminFormationCreateForm = signal(false);
  creatingFormation = signal(false);
  editingFormation = signal(false);
  deletingFormationId = signal('');
  formationActionNotice = signal('');
  formationActionError = signal('');
  editFormationId = signal('');
  editFormationTitle = signal('');
  editFormationMode = signal('En ligne');
  editFormationTrainerId = signal<number | null>(null);
  editFormationStartDate = signal('');
  editFormationEndDate = signal('');
  editFormationTeamsLink = signal('');
  editPlanningRows = signal<PlanningInput[]>([{ date: '', slot: '09:00 - 12:30', topic: '' }]);
  archivingFormationId = signal('');
  archiveFormationNotice = signal('');
  archiveFormationError = signal('');
  apprenticeCreateNotice = signal('');
  apprenticeCreateError = signal('');
  creatingApprentices = signal(false);
  resendingStudentPasswordId = signal<number | null>(null);
  studentPasswordNotice = signal('');
  studentPasswordError = signal('');
  trainerCreateNotice = signal('');
  trainerCreateError = signal('');
  creatingTrainer = signal(false);
  newFormationTitle = signal('');
  newFormationCatalogCourseId = signal('');
  newFormationMode = signal('En ligne');
  newFormationTrainerId = signal<number | null>(null);
  newFormationStartDate = signal('');
  newFormationEndDate = signal('');
  newFormationTeamsLink = signal('');
  newFormationClassLabel = signal('');
  newFormationCapacity = signal(20);
  selectedStudentIds = signal<number[]>([]);
  selectedStudentToAdd = signal<number | null>(null);
  studentFilterForSelector = signal('');
  selectedFormationIdsForTrainer = signal<string[]>([]);
  selectedFormationToAddForTrainer = signal('');
  catalogFormations = signal<CatalogFormationOption[]>([]);
  catalogFormationsLoading = signal(false);
  catalogFormationsError = signal('');
  certificationCatalog = signal<CertificationCatalogOption[]>([]);
  certificationCatalogLoading = signal(false);
  certificationCatalogError = signal('');
  formationFilterForTrainer = signal('');
  newTrainerFirstName = signal('');
  newTrainerLastName = signal('');
  newTrainerEmail = signal('');
  newTrainerPhone = signal('');
  newTrainerStatus = signal('salarie');
  newTrainerCompanyName = signal('');
  newTrainerMicrosoftTranscriptUrl = signal('');
  newTrainerCvFile = signal<File | null>(null);
  trainerViewId = signal<number | null>(null);
  trainerEditId = signal<number | null>(null);
  deletingTrainerId = signal<number | null>(null);
  trainerEditFirstName = signal('');
  trainerEditLastName = signal('');
  trainerEditEmail = signal('');
  trainerEditPhone = signal('');
  trainerEditStatus = signal('salarie');
  trainerEditCompanyName = signal('');
  trainerEditMicrosoftTranscriptUrl = signal('');
  trainerEditCvFile = signal<File | null>(null);
  trainerEditCvUrl = signal('');
  trainerCertifications = signal<TrainerCertificationInput[]>([{ name: '', issuer: '', expiresAt: '', proof: '' }]);
  trainerCompletedTrainings = signal<TrainerCompletedTrainingInput[]>([
    {
      domain: '',
      description: '',
      objective: '',
      trainingOrganization: '',
      trainingDate: '',
      durationHours: '',
      attestationUrl: ''
    }
  ]);
  planningRows = signal<PlanningInput[]>([{ date: '', slot: '09:00 - 12:30', topic: '' }]);
  planningDateFilter = signal('');
  apprenticeRows = signal<ApprenticeInput[]>([{ firstName: '', lastName: '', email: '', birthDate: '' }]);
  selectedAttendanceFormation = signal('');
  selectedAdminFormationId = signal('');
  adminSessionSearch = signal('');
  adminSessionFormationFilter = signal('all');
  selectedArchivedFormationId = signal('');
  selectedStudentSessionFormationId = signal('');
  selectedPlanningFormationId = signal('');
  adminPlanningWeekStartIso = signal(this.toIsoDate(this.startOfWeek(new Date())));
  selectedTrainerApprenticeFormationId = signal('all');
  selectedTrainerSessionFormationId = signal('');
  trainerSessionApprenticeSearch = signal('');
  trainerSessionApprenticePage = signal(1);
  readonly trainerSessionApprenticePageSize = 10;
  trainerSessionPlanningPage = signal(1);
  readonly trainerSessionPlanningPageSize = 5;
  trainerSessionResourcePage = signal(1);
  readonly trainerSessionResourcePageSize = 5;
  selectedTrainerResourceFormationId = signal('');
  selectedTrainerResourceSessionId = signal('');
  trainerResourceTitle = signal('');
  trainerResourceType = signal('PDF');
  trainerResourceUrl = signal('');
  trainerResourceFile = signal<File | null>(null);
  trainerResourceNotice = signal('');
  trainerResourceError = signal('');
  creatingTrainerResource = signal(false);
  adminGlobalResourceTitle = signal('');
  adminGlobalResourceType = signal('PDF');
  adminGlobalResourceUrl = signal('');
  adminGlobalResourceFile = signal<File | null>(null);
  adminGlobalResourceNotice = signal('');
  adminGlobalResourceError = signal('');
  creatingAdminGlobalResource = signal(false);
  providers = signal<ProviderRecord[]>([]);
  creatingProvider = signal(false);
  providerNotice = signal('');
  providerError = signal('');
  providerCompanyName = signal('');
  providerSiret = signal('');
  providerAddress = signal('');
  providerPhone = signal('');
  providerActivityDeclarationNumber = signal('');
  providerKbisFile = signal<File | null>(null);
  providerRibFile = signal<File | null>(null);
  providerVigilanceCertificateFile = signal<File | null>(null);
  providerLiabilityInsuranceFile = signal<File | null>(null);
  providerSearch = signal('');
  providerTablePreset = signal<'compact' | 'full' | 'documents'>('compact');
  providerPage = signal(1);
  readonly providerPageSize = 8;
  providerColumnVisibility = signal({
    companyName: true,
    siret: true,
    address: false,
    phone: true,
    activityDeclarationNumber: false,
    documents: false,
    createdAt: true
  });
  trainerApprenticeListSearch = signal('');
  trainerApprenticeListPage = signal(1);
  readonly trainerApprenticeListPageSize = 10;
  attendanceNotice = signal('');
  attendanceError = signal('');
  documents = signal<ResourceDocument[]>([]);
  nowMs = signal(Date.now());
  private clockInterval: ReturnType<typeof setInterval> | null = null;
  private readonly localAttendanceDeadlines = new Map<string, number>();

  isAuthenticated = computed(() => !!this.token());
  isTrainer = computed(() => this.currentRole() === 'trainer');
  isStudent = computed(() => this.currentRole() === 'student');
  isAdmin = computed(() => this.currentRole() === 'admin');
  totalSessions = computed(() =>
    this.formations().reduce((count, formation) => count + formation.planning.length, 0)
  );
  nextSession = computed(() => {
    const firstFormation = this.formations()[0];
    return firstFormation?.planning?.[0] ?? null;
  });
  nextFormation = computed(() => this.formations()[0] ?? null);
  adminPlanningRows = computed(() =>
    this.formations().filter((formation) => !formation.archived).flatMap((formation) =>
      formation.planning.map((slot) => ({
        formationTitle: formation.title,
        trainer: formation.trainer,
        teamsLink: formation.teamsLink,
        date: slot.date,
        slot: slot.slot,
        topic: slot.topic
      }))
    )
  );
  adminPlanningCalendar = computed(() => {
    const grouped = new Map<string, {
      date: string;
      dayLabel: string;
      items: {
        formationTitle: string;
        trainer: string;
        teamsLink: string;
        date: string;
        slot: string;
        topic: string;
      }[];
    }>();

    for (const row of this.adminPlanningRows()) {
      if (!grouped.has(row.date)) {
        grouped.set(row.date, {
          date: row.date,
          dayLabel: this.dayLabelFromIsoDate(row.date),
          items: []
        });
      }
      grouped.get(row.date)!.items.push(row);
    }

    const byDate = Array.from(grouped.values()).sort((a, b) => a.date.localeCompare(b.date));
    for (const day of byDate) {
      day.items.sort((a, b) => a.slot.localeCompare(b.slot));
    }
    return byDate;
  });
  adminPlanningWeekDays = computed(() => {
    const start = this.parseIsoDate(this.adminPlanningWeekStartIso()) ?? this.startOfWeek(new Date());
    const days: { date: string; dayLabel: string; shortLabel: string }[] = [];
    for (let i = 0; i < 5; i += 1) {
      const date = new Date(start);
      date.setDate(start.getDate() + i);
      days.push({
        date: this.toIsoDate(date),
        dayLabel: date.toLocaleDateString('fr-FR', { weekday: 'long' }),
        shortLabel: date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
      });
    }
    return days;
  });
  adminPlanningWeekTitle = computed(() => {
    const start = this.parseIsoDate(this.adminPlanningWeekStartIso()) ?? this.startOfWeek(new Date());
    return start.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
  });
  adminPlanningTimeSlots = computed(() => {
    const slots: { label: string; minutes: number; topPercent: number }[] = [];
    const startMinutes = 8 * 60;
    const endMinutes = 23 * 60;
    const totalRange = endMinutes - startMinutes;
    for (let minute = startMinutes; minute <= endMinutes; minute += 30) {
      const hh = String(Math.floor(minute / 60)).padStart(2, '0');
      const mm = String(minute % 60).padStart(2, '0');
      slots.push({
        label: `${hh}:${mm}`,
        minutes: minute,
        topPercent: ((minute - startMinutes) / totalRange) * 100
      });
    }
    return slots;
  });
  activeAdminFormations = computed(() => this.formations().filter((formation) => !formation.archived));
  archivedAdminFormations = computed(() => this.formations().filter((formation) => formation.archived));
  selectedArchivedFormation = computed(() => {
    const formations = this.archivedAdminFormations();
    if (!formations.length) return null;
    const selectedId = this.selectedArchivedFormationId();
    return formations.find((formation) => formation.id === selectedId) ?? formations[0];
  });
  selectedArchivedFormationApprentices = computed(() => {
    const formation = this.selectedArchivedFormation();
    if (!formation) return [];
    const seen = new Set<number>();
    const rows: { id: number; name: string; email: string; classLabel: string }[] = [];
    for (const classItem of this.adminClasses()) {
      if (classItem.formationId !== formation.id) continue;
      for (const student of classItem.students) {
        if (seen.has(student.id)) continue;
        seen.add(student.id);
        rows.push({
          id: student.id,
          name: `${student.firstName} ${student.lastName}`,
          email: student.email,
          classLabel: classItem.label
        });
      }
    }
    return rows;
  });
  selectedAdminFormation = computed(() => {
    const formations = this.activeAdminFormations();
    if (!formations.length) return null;
    const selectedId = this.selectedAdminFormationId();
    return formations.find((formation) => formation.id === selectedId) ?? formations[0];
  });
  selectedAdminFormationApprentices = computed(() => {
    const formation = this.selectedAdminFormation();
    if (!formation) return [];
    const seen = new Set<number>();
    const rows: { id: number; name: string; email: string; classLabel: string; attendanceMarked: number; attendanceTotal: number }[] = [];
    const sessions = this.selectedAdminFormationSessions();
    const totalSessions = sessions.length;
    for (const classItem of this.adminClasses()) {
      if (classItem.formationId !== formation.id) continue;
      for (const student of classItem.students) {
        if (seen.has(student.id)) continue;
        seen.add(student.id);
        const marked = sessions.reduce((count, session) => {
          const record = session.records.find((item) => item.studentId === student.id);
          if (!record) return count;
          return record.status !== 'pending' ? count + 1 : count;
        }, 0);
        rows.push({
          id: student.id,
          name: `${student.firstName} ${student.lastName}`,
          email: student.email,
          classLabel: classItem.label,
          attendanceMarked: marked,
          attendanceTotal: totalSessions
        });
      }
    }
    return rows;
  });
  selectedAdminFormationSessions = computed(() => {
    const formation = this.selectedAdminFormation();
    if (!formation) return [];
    return this.attendanceSessions()
      .filter((session) => session.formationTitle === formation.title)
      .sort((a, b) => {
        const byDate = a.date.localeCompare(b.date);
        if (byDate !== 0) return byDate;
        const aStart = this.parseSlotRange(a.slot)?.startMinutes ?? Number.MAX_SAFE_INTEGER;
        const bStart = this.parseSlotRange(b.slot)?.startMinutes ?? Number.MAX_SAFE_INTEGER;
        if (aStart !== bStart) return aStart - bStart;
        return a.slot.localeCompare(b.slot);
      });
  });
  selectedAdminFormationAttendanceSummary = computed(() => {
    const records = this.selectedAdminFormationSessions().flatMap((session) => session.records);
    return {
      apprentices: this.selectedAdminFormationApprentices().length,
      sessions: this.selectedAdminFormationSessions().length,
      emargements: records.filter((record) => record.status !== 'pending').length
    };
  });
  adminSessionFormationFilterOptions = computed(() => {
    const counts = new Map<string, number>();
    for (const formation of this.activeAdminFormations()) {
      const catalogName = this.sessionCatalogFormationNameStrict(formation);
      if (!catalogName) continue;
      counts.set(catalogName, (counts.get(catalogName) ?? 0) + 1);
    }
    return Array.from(counts.entries())
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => a.name.localeCompare(b.name));
  });
  filteredAdminSessionCards = computed(() => {
    const search = this.adminSessionSearch().trim().toLowerCase();
    const selectedCatalogName = this.adminSessionFormationFilter();
    return this.activeAdminFormations().filter((formation) => {
      const catalogName = this.sessionCatalogFormationNameStrict(formation);
      const matchFilter = selectedCatalogName === 'all' || catalogName === selectedCatalogName;
      if (!matchFilter) return false;
      if (!search) return true;
      return `${formation.title} ${catalogName} ${formation.trainer} ${formation.mode} ${formation.startDate} ${formation.endDate}`
        .toLowerCase()
        .includes(search);
    });
  });
  selectedStudentSessionFormation = computed(() => {
    const formations = this.formations();
    if (!formations.length) return null;
    const selectedId = this.selectedStudentSessionFormationId();
    return formations.find((formation) => formation.id === selectedId) ?? formations[0];
  });
  studentSessionsForSelectedFormation = computed(() => {
    const formation = this.selectedStudentSessionFormation();
    if (!formation) return [];
    return this.attendanceSessions().filter((session) => session.formationTitle === formation.title);
  });
  adminAttendanceFormationOptions = computed(() => {
    const uniqueTitles = Array.from(new Set(this.attendanceSessions().map((session) => session.formationTitle)));
    return uniqueTitles.sort((a, b) => a.localeCompare(b));
  });
  filteredAttendanceSessions = computed(() => {
    if (!this.isAdmin()) {
      return this.attendanceSessions();
    }
    const selected = this.selectedAttendanceFormation();
    if (!selected) {
      return this.attendanceSessions();
    }
    return this.attendanceSessions().filter((session) => session.formationTitle === selected);
  });
  adminAttendanceSummary = computed(() => {
    const sessions = this.filteredAttendanceSessions();
    const records = sessions.flatMap((session) => session.records);
    const present = records.filter((record) => record.status === 'present').length;
    const absent = records.filter((record) => record.status === 'absent').length;
    const late = records.filter((record) => record.status === 'late').length;
    const pending = records.filter((record) => record.status === 'pending').length;
    return {
      total: records.length,
      present,
      absent,
      late,
      pending,
      allMarked: records.length > 0 && pending === 0
    };
  });
  activeSection = signal<SectionKey>('dashboard');

  readonly quickActions = [
    { label: 'Rejoindre la classe Teams', type: 'teams' },
    { label: 'Signer ma presence', type: 'attendance' },
    { label: 'Contacter le support', type: 'support' }
  ] as const;
  readonly studentMenuItems: { key: SectionKey; label: string }[] = [
    { key: 'dashboard', label: 'Dashboard' },
    { key: 'sessions-student', label: 'Sessions' },
    { key: 'ressources', label: 'Docs' },
    { key: 'evaluations', label: 'Tests' },
    { key: 'archives', label: 'Archives' },
    { key: 'support', label: 'Aide' }
  ];
  readonly trainerMenuItems: { key: SectionKey; label: string }[] = [
    { key: 'dashboard', label: 'Dashboard' },
    { key: 'sessions', label: 'Sessions' },
    { key: 'archives', label: 'Archives' },
    { key: 'support', label: 'Aide' }
  ];
  readonly adminMenuItems: { key: SectionKey; label: string }[] = [
    { key: 'dashboard', label: 'Dashboard' },
    { key: 'formations', label: 'Sessions' },
    { key: 'formateurs', label: 'Formateurs' },
    { key: 'prestataires', label: 'Prestataires' },
    { key: 'apprentis', label: 'Apprentis' },
    { key: 'ressources', label: 'Ressources' },
    { key: 'archives', label: 'Archives' },
    { key: 'support', label: 'Support' }
  ];
  readonly announcements: { title: string; text: string; date: string }[] = [];
  readonly todoItems: string[] = [];
  readonly activityFeed: { label: string; when: string }[] = [];
  readonly planningTimeOptions = this.buildPlanningTimeOptions();

  constructor() {
    this.clockInterval = setInterval(() => {
      this.nowMs.set(Date.now());
    }, 1000);

    if (this.token()) {
      this.loadDashboard();
    }
  }

  ngOnDestroy(): void {
    if (this.clockInterval) {
      clearInterval(this.clockInterval);
      this.clockInterval = null;
    }
  }

  menuItems = computed(() => {
    if (this.isAdmin()) return this.adminMenuItems;
    if (this.isTrainer()) return this.trainerMenuItems;
    return this.studentMenuItems;
  });

  onSubmitLogin(event: Event): void {
    event.preventDefault();
    this.login();
  }

  login(): void {
    this.loading.set(true);
    this.loginError.set('');

    this.http
      .post<LoginResponse>(`${this.apiBaseUrl}/auth/login`, {
        email: this.email(),
        password: this.password()
      })
      .subscribe({
        next: (response) => {
          this.token.set(response.token);
          this.currentRole.set(response.role);
          localStorage.setItem('intranet_token', response.token);
          this.student.set(response.role === 'student' ? response.profile : null);
          this.trainer.set(response.role === 'trainer' ? response.profile : null);
          this.admin.set(response.role === 'admin' ? response.profile : null);
          this.activeSection.set('dashboard');
          this.loadDashboard();
          this.loading.set(false);
        },
        error: (err) => {
          this.loading.set(false);
          this.loginError.set(err?.error?.message ?? 'Connexion impossible');
        }
      });
  }

  logout(): void {
    localStorage.removeItem('intranet_token');
    this.token.set('');
    this.currentRole.set(null);
    this.student.set(null);
    this.trainer.set(null);
    this.admin.set(null);
    this.formations.set([]);
    this.attendanceSessions.set([]);
    this.adminClasses.set([]);
    this.adminStudents.set([]);
    this.adminTrainers.set([]);
    this.providers.set([]);
    this.documents.set([]);
    this.password.set('');
    this.editingFormation.set(false);
    this.deletingFormationId.set('');
    this.formationActionNotice.set('');
    this.formationActionError.set('');
    this.editFormationId.set('');
    this.editFormationTitle.set('');
    this.editFormationMode.set('En ligne');
    this.editFormationTrainerId.set(null);
    this.editFormationStartDate.set('');
    this.editFormationEndDate.set('');
    this.editFormationTeamsLink.set('');
    this.editPlanningRows.set([{ date: '', slot: '09:00 - 12:30', topic: '' }]);
    this.selectedAdminFormationId.set('');
    this.selectedArchivedFormationId.set('');
    this.selectedStudentSessionFormationId.set('');
    this.selectedPlanningFormationId.set('');
    this.selectedTrainerApprenticeFormationId.set('all');
    this.selectedTrainerSessionFormationId.set('');
    this.selectedTrainerResourceFormationId.set('');
    this.selectedTrainerResourceSessionId.set('');
    this.trainerResourceTitle.set('');
    this.trainerResourceType.set('PDF');
    this.trainerResourceUrl.set('');
    this.trainerResourceFile.set(null);
    this.trainerResourceNotice.set('');
    this.trainerResourceError.set('');
    this.archivingFormationId.set('');
    this.archiveFormationNotice.set('');
    this.archiveFormationError.set('');
    this.adminGlobalResourceTitle.set('');
    this.adminGlobalResourceType.set('PDF');
    this.adminGlobalResourceUrl.set('');
    this.adminGlobalResourceFile.set(null);
    this.adminGlobalResourceNotice.set('');
    this.adminGlobalResourceError.set('');
    this.providerNotice.set('');
    this.providerError.set('');
    this.providerCompanyName.set('');
    this.providerSiret.set('');
    this.providerAddress.set('');
    this.providerPhone.set('');
    this.providerActivityDeclarationNumber.set('');
    this.providerKbisFile.set(null);
    this.providerRibFile.set(null);
    this.providerVigilanceCertificateFile.set(null);
    this.providerLiabilityInsuranceFile.set(null);
    this.localAttendanceDeadlines.clear();
  }

  setSection(section: SectionKey): void {
    this.activeSection.set(section);
  }

  toggleAdminFormationCreateForm(): void {
    this.showAdminFormationCreateForm.set(!this.showAdminFormationCreateForm());
  }

  selectAdminFormation(formationId: string): void {
    this.editingFormation.set(false);
    this.formationActionNotice.set('');
    this.formationActionError.set('');
    this.selectedAdminFormationId.set(formationId);
    this.activeSection.set('formation-detail-admin');
  }

  startEditSelectedFormation(): void {
    const formation = this.selectedAdminFormation();
    if (!formation) return;
    this.formationActionNotice.set('');
    this.formationActionError.set('');
    this.editFormationId.set(formation.id);
    this.editFormationTitle.set(formation.title);
    this.editFormationMode.set(formation.mode || 'En ligne');
    this.editFormationTrainerId.set(formation.trainerId ?? this.adminTrainers()[0]?.id ?? null);
    this.editFormationStartDate.set(formation.startDate);
    this.editFormationEndDate.set(formation.endDate);
    this.editFormationTeamsLink.set(formation.teamsLink ?? '');
    this.editPlanningRows.set(
      (formation.planning ?? []).map((slot) => ({
        date: slot.date,
        slot: slot.slot,
        topic: slot.topic
      }))
    );
    if (!this.editPlanningRows().length) {
      this.editPlanningRows.set([{ date: '', slot: '09:00 - 12:30', topic: '' }]);
    }
    this.editingFormation.set(true);
  }

  cancelEditFormation(): void {
    this.editingFormation.set(false);
  }

  saveEditedFormation(event: Event): void {
    event.preventDefault();
    const formationId = this.editFormationId().trim();
    const trainerId = this.editFormationTrainerId();
    if (!formationId || !trainerId) {
      this.formationActionError.set('Formation ou formateur invalide.');
      return;
    }

    this.creatingFormation.set(true);
    this.formationActionNotice.set('');
    this.formationActionError.set('');

    this.http
      .put<{ message: string }>(
        `${this.apiBaseUrl}/admin/formations/${encodeURIComponent(formationId)}`,
        {
          title: this.editFormationTitle().trim(),
          mode: this.editFormationMode().trim() || 'En ligne',
          trainerId,
          startDate: this.editFormationStartDate().trim(),
          endDate: this.editFormationEndDate().trim(),
          teamsLink: this.editFormationTeamsLink().trim(),
          planning: this.normalizePlanningPayload(this.editPlanningRows())
        },
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingFormation.set(false);
          this.editingFormation.set(false);
          this.formationActionNotice.set(response.message);
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingFormation.set(false);
          this.formationActionError.set(err?.error?.message ?? 'Modification impossible.');
        }
      });
  }

  deleteSelectedFormation(): void {
    const formation = this.selectedAdminFormation();
    if (!formation) return;
    const formationId = formation.id;
    this.deletingFormationId.set(formationId);
    this.formationActionNotice.set('');
    this.formationActionError.set('');

    this.http
      .delete<{ message: string }>(
        `${this.apiBaseUrl}/admin/formations/${encodeURIComponent(formationId)}`,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.deletingFormationId.set('');
          this.editingFormation.set(false);
          this.formationActionNotice.set(response.message);
          this.loadDashboard();
        },
        error: (err) => {
          this.deletingFormationId.set('');
          this.formationActionError.set(err?.error?.message ?? 'Suppression impossible.');
        }
      });
  }

  archiveFormation(formationId: string, event?: Event): void {
    event?.stopPropagation();
    if (!formationId || this.archivingFormationId()) return;

    this.archiveFormationNotice.set('');
    this.archiveFormationError.set('');
    this.archivingFormationId.set(formationId);

    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/admin/formations/${encodeURIComponent(formationId)}/archive`,
        {},
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.archivingFormationId.set('');
          this.archiveFormationNotice.set(response.message);
          this.loadDashboard();
        },
        error: (err) => {
          this.archivingFormationId.set('');
          this.archiveFormationError.set(err?.error?.message ?? 'Archivage impossible.');
        }
      });
  }

  selectArchivedFormation(formationId: string): void {
    this.selectedArchivedFormationId.set(formationId);
  }

  selectStudentSessionFormation(formationId: string): void {
    this.selectedStudentSessionFormationId.set(formationId);
    this.activeSection.set('sessions-student-detail');
  }

  backToStudentSessions(): void {
    this.activeSection.set('sessions-student');
  }

  previousAdminPlanningWeek(): void {
    const start = this.parseIsoDate(this.adminPlanningWeekStartIso()) ?? this.startOfWeek(new Date());
    start.setDate(start.getDate() - 7);
    this.adminPlanningWeekStartIso.set(this.toIsoDate(start));
  }

  nextAdminPlanningWeek(): void {
    const start = this.parseIsoDate(this.adminPlanningWeekStartIso()) ?? this.startOfWeek(new Date());
    start.setDate(start.getDate() + 7);
    this.adminPlanningWeekStartIso.set(this.toIsoDate(start));
  }

  goToCurrentAdminPlanningWeek(): void {
    this.adminPlanningWeekStartIso.set(this.toIsoDate(this.startOfWeek(new Date())));
  }

  adminPlanningEventsForDate(isoDate: string): AdminPlanningWeekEvent[] {
    const items = this.adminPlanningRows()
      .filter((row) => row.date === isoDate)
      .map((row) => {
        const range = this.parseSlotRange(row.slot);
        if (!range) return null;
        const startMinutes = 8 * 60;
        const endMinutes = 23 * 60;
        const totalRange = endMinutes - startMinutes;
        const clampedStart = Math.max(startMinutes, Math.min(endMinutes, range.startMinutes));
        const clampedEnd = Math.max(startMinutes, Math.min(endMinutes, range.endMinutes));
        const safeEnd = clampedEnd <= clampedStart ? clampedStart + 30 : clampedEnd;
        const topPercent = ((clampedStart - startMinutes) / totalRange) * 100;
        const heightPercent = ((safeEnd - clampedStart) / totalRange) * 100;
        return {
          ...row,
          topPercent,
          heightPercent
        } satisfies AdminPlanningWeekEvent;
      })
      .filter((item): item is AdminPlanningWeekEvent => item !== null)
      .sort((a, b) => a.topPercent - b.topPercent);

    return items;
  }

  backToAdminFormations(): void {
    this.activeSection.set('formations');
  }

  toggleStudentSelection(studentId: number, checked: boolean): void {
    const current = this.selectedStudentIds();
    if (checked && !current.includes(studentId)) {
      this.selectedStudentIds.set([...current, studentId]);
      return;
    }
    if (!checked) {
      this.selectedStudentIds.set(current.filter((id) => id !== studentId));
    }
  }

  addSelectedStudent(): void {
    const studentId = this.selectedStudentToAdd();
    if (!studentId) return;
    if (this.selectedStudentIds().includes(studentId)) return;
    this.selectedStudentIds.set([...this.selectedStudentIds(), studentId]);
  }

  removeSelectedStudent(studentId: number): void {
    this.selectedStudentIds.set(this.selectedStudentIds().filter((id) => id !== studentId));
  }

  selectedStudents = computed(() =>
    this.adminStudents().filter((student) => this.selectedStudentIds().includes(student.id))
  );
  apprenticeSearch = signal('');
  assignedApprentices = computed(() => {
    const seen = new Set<string>();
    const rows: { id: number; name: string; email: string; classLabel: string; formationTitle: string }[] = [];
    for (const classItem of this.adminClasses()) {
      for (const student of classItem.students) {
        const key = `${classItem.id}#${student.id}`;
        if (seen.has(key)) continue;
        seen.add(key);
        rows.push({
          id: student.id,
          name: `${student.firstName} ${student.lastName}`,
          email: student.email,
          classLabel: classItem.label,
          formationTitle: classItem.formationTitle
        });
      }
    }
    return rows;
  });
  trainerAssignedApprentices = computed(() => {
    const rows: { id: number; name: string; email: string; formationTitle: string }[] = [];
    for (const formation of this.formations()) {
      for (const apprentice of formation.apprentices ?? []) {
        rows.push({
          id: apprentice.id,
          name: apprentice.name,
          email: apprentice.email,
          formationTitle: formation.title
        });
      }
    }
    return rows;
  });
  trainerApprenticeSearch = signal('');
  trainerApprenticePage = signal(1);
  readonly trainerApprenticePageSize = 10;
  filteredTrainerAssignedApprentices = computed(() => {
    const q = this.trainerApprenticeSearch().trim().toLowerCase();
    if (!q) return this.trainerAssignedApprentices();
    return this.trainerAssignedApprentices().filter((item) =>
      `${item.name} ${item.email} ${item.formationTitle}`.toLowerCase().includes(q)
    );
  });
  trainerApprenticeTotalPages = computed(() => {
    const total = this.filteredTrainerAssignedApprentices().length;
    return Math.max(1, Math.ceil(total / this.trainerApprenticePageSize));
  });
  paginatedTrainerAssignedApprentices = computed(() => {
    const page = Math.min(this.trainerApprenticePage(), this.trainerApprenticeTotalPages());
    const start = (page - 1) * this.trainerApprenticePageSize;
    return this.filteredTrainerAssignedApprentices().slice(start, start + this.trainerApprenticePageSize);
  });
  trainerApprenticeKpis = computed(() => ({
    total: this.trainerAssignedApprentices().length,
    formations: this.formations().length
  }));
  selectedTrainerSessionFormation = computed(() => {
    const formations = this.formations();
    if (!formations.length) return null;
    const selectedId = this.selectedTrainerSessionFormationId();
    return formations.find((formation) => formation.id === selectedId) ?? formations[0];
  });
  selectedTrainerSessionApprentices = computed(() => this.selectedTrainerSessionFormation()?.apprentices ?? []);
  selectedTrainerSessionAttendanceSessions = computed(() => {
    const formation = this.selectedTrainerSessionFormation();
    if (!formation) return [];
    return this.attendanceSessions().filter((session) => session.formationTitle === formation.title);
  });
  trainerSessionResourceOptions = computed(() =>
    this.selectedTrainerSessionAttendanceSessions().map((session) => ({
      id: session.id,
      label: `${session.date} · ${session.slot}`
    }))
  );
  selectedTrainerSessionDocuments = computed(() => {
    const selectedSessionId = this.selectedTrainerResourceSessionId().trim();
    if (!selectedSessionId) return [];
    return this.documents().filter((doc) => (doc.sessionId ?? '') === selectedSessionId);
  });
  trainerSessionResourceTotalPages = computed(() => {
    const total = this.selectedTrainerSessionDocuments().length;
    return Math.max(1, Math.ceil(total / this.trainerSessionResourcePageSize));
  });
  paginatedTrainerSessionDocuments = computed(() => {
    const currentPage = Math.min(this.trainerSessionResourcePage(), this.trainerSessionResourceTotalPages());
    const start = (currentPage - 1) * this.trainerSessionResourcePageSize;
    return this.selectedTrainerSessionDocuments().slice(start, start + this.trainerSessionResourcePageSize);
  });
  trainerSessionKpis = computed(() => ({
    apprentices: this.selectedTrainerSessionApprentices().length,
    sessions: this.selectedTrainerSessionAttendanceSessions().length,
    participants: this.selectedTrainerSessionAttendanceSessions().reduce((sum, session) => sum + session.records.length, 0)
  }));
  filteredTrainerSessionApprentices = computed(() => {
    const q = this.trainerSessionApprenticeSearch().trim().toLowerCase();
    if (!q) return this.selectedTrainerSessionApprentices();
    return this.selectedTrainerSessionApprentices().filter((item) =>
      `${item.name} ${item.email}`.toLowerCase().includes(q)
    );
  });
  trainerSessionApprenticeTotalPages = computed(() => {
    const total = this.filteredTrainerSessionApprentices().length;
    return Math.max(1, Math.ceil(total / this.trainerSessionApprenticePageSize));
  });
  paginatedTrainerSessionApprentices = computed(() => {
    const currentPage = Math.min(this.trainerSessionApprenticePage(), this.trainerSessionApprenticeTotalPages());
    const start = (currentPage - 1) * this.trainerSessionApprenticePageSize;
    return this.filteredTrainerSessionApprentices().slice(start, start + this.trainerSessionApprenticePageSize);
  });
  trainerSessionPlanningTotalPages = computed(() => {
    const total = this.selectedTrainerSessionAttendanceSessions().length;
    return Math.max(1, Math.ceil(total / this.trainerSessionPlanningPageSize));
  });
  paginatedTrainerSessionAttendanceSessions = computed(() => {
    const currentPage = Math.min(this.trainerSessionPlanningPage(), this.trainerSessionPlanningTotalPages());
    const start = (currentPage - 1) * this.trainerSessionPlanningPageSize;
    return this.selectedTrainerSessionAttendanceSessions().slice(start, start + this.trainerSessionPlanningPageSize);
  });
  trainerApprenticeFormationOptions = computed(() => [
    { id: 'all', title: 'Toutes les formations' },
    ...this.formations().map((formation) => ({
      id: formation.id,
      title: formation.title
    }))
  ]);
  trainerApprenticesForSelectedFormation = computed(() => {
    const selectedFormationId = this.selectedTrainerApprenticeFormationId();
    const rows: { id: number; name: string; email: string; formationTitle: string }[] = [];

    for (const formation of this.formations()) {
      if (selectedFormationId !== 'all' && formation.id !== selectedFormationId) {
        continue;
      }
      for (const apprentice of formation.apprentices ?? []) {
        rows.push({
          id: apprentice.id,
          name: apprentice.name,
          email: apprentice.email,
          formationTitle: formation.title
        });
      }
    }

    return rows;
  });
  filteredTrainerApprenticesForSelectedFormation = computed(() => {
    const q = this.trainerApprenticeListSearch().trim().toLowerCase();
    if (!q) return this.trainerApprenticesForSelectedFormation();
    return this.trainerApprenticesForSelectedFormation().filter((item) =>
      `${item.name} ${item.email} ${item.formationTitle}`.toLowerCase().includes(q)
    );
  });
  trainerApprenticeListTotalPages = computed(() => {
    const total = this.filteredTrainerApprenticesForSelectedFormation().length;
    return Math.max(1, Math.ceil(total / this.trainerApprenticeListPageSize));
  });
  paginatedTrainerApprenticesForSelectedFormation = computed(() => {
    const currentPage = Math.min(this.trainerApprenticeListPage(), this.trainerApprenticeListTotalPages());
    const start = (currentPage - 1) * this.trainerApprenticeListPageSize;
    return this.filteredTrainerApprenticesForSelectedFormation().slice(start, start + this.trainerApprenticeListPageSize);
  });

  updateTrainerApprenticeSearch(value: string): void {
    this.trainerApprenticeSearch.set(value);
    this.trainerApprenticePage.set(1);
  }

  previousTrainerApprenticePage(): void {
    this.trainerApprenticePage.set(Math.max(1, this.trainerApprenticePage() - 1));
  }

  nextTrainerApprenticePage(): void {
    this.trainerApprenticePage.set(
      Math.min(this.trainerApprenticeTotalPages(), this.trainerApprenticePage() + 1)
    );
  }

  updateTrainerApprenticeFormationFilter(value: string): void {
    this.selectedTrainerApprenticeFormationId.set(value);
    this.trainerApprenticeListPage.set(1);
  }

  updateTrainerApprenticeListSearch(value: string): void {
    this.trainerApprenticeListSearch.set(value);
    this.trainerApprenticeListPage.set(1);
  }

  previousTrainerApprenticeListPage(): void {
    this.trainerApprenticeListPage.set(Math.max(1, this.trainerApprenticeListPage() - 1));
  }

  nextTrainerApprenticeListPage(): void {
    this.trainerApprenticeListPage.set(
      Math.min(this.trainerApprenticeListTotalPages(), this.trainerApprenticeListPage() + 1)
    );
  }

  selectTrainerSessionFormation(formationId: string): void {
    this.selectedTrainerSessionFormationId.set(formationId);
    this.selectedTrainerResourceFormationId.set(formationId);
    this.trainerSessionApprenticeSearch.set('');
    this.trainerSessionApprenticePage.set(1);
    this.trainerSessionPlanningPage.set(1);
    this.trainerSessionResourcePage.set(1);
    this.selectedTrainerResourceSessionId.set(this.selectedTrainerSessionAttendanceSessions()[0]?.id ?? '');
    this.activeSection.set('sessions-detail');
  }

  backToTrainerSessions(): void {
    this.activeSection.set('sessions');
  }

  updateTrainerSessionApprenticeSearch(value: string): void {
    this.trainerSessionApprenticeSearch.set(value);
    this.trainerSessionApprenticePage.set(1);
  }

  previousTrainerSessionApprenticePage(): void {
    this.trainerSessionApprenticePage.set(Math.max(1, this.trainerSessionApprenticePage() - 1));
  }

  nextTrainerSessionApprenticePage(): void {
    this.trainerSessionApprenticePage.set(
      Math.min(this.trainerSessionApprenticeTotalPages(), this.trainerSessionApprenticePage() + 1)
    );
  }

  previousTrainerSessionPlanningPage(): void {
    this.trainerSessionPlanningPage.set(Math.max(1, this.trainerSessionPlanningPage() - 1));
  }

  nextTrainerSessionPlanningPage(): void {
    this.trainerSessionPlanningPage.set(
      Math.min(this.trainerSessionPlanningTotalPages(), this.trainerSessionPlanningPage() + 1)
    );
  }

  setSelectedTrainerResourceSessionId(sessionId: string): void {
    this.selectedTrainerResourceSessionId.set(sessionId);
    this.trainerSessionResourcePage.set(1);
  }

  previousTrainerSessionResourcePage(): void {
    this.trainerSessionResourcePage.set(Math.max(1, this.trainerSessionResourcePage() - 1));
  }

  nextTrainerSessionResourcePage(): void {
    this.trainerSessionResourcePage.set(
      Math.min(this.trainerSessionResourceTotalPages(), this.trainerSessionResourcePage() + 1)
    );
  }

  markedAttendanceCount(session: AttendanceSession): number {
    return session.records.filter((record) => record.status !== 'pending').length;
  }

  private dayLabelFromIsoDate(isoDate: string): string {
    const parsed = new Date(`${isoDate}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) return isoDate;
    return parsed.toLocaleDateString('fr-FR', { weekday: 'long' });
  }

  private parseSlotRange(slot: string): { startMinutes: number; endMinutes: number } | null {
    const match = slot.match(/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/);
    if (!match) return null;
    const startMinutes = Number(match[1]) * 60 + Number(match[2]);
    const endMinutes = Number(match[3]) * 60 + Number(match[4]);
    if (Number.isNaN(startMinutes) || Number.isNaN(endMinutes)) return null;
    return { startMinutes, endMinutes: endMinutes > startMinutes ? endMinutes : startMinutes + 30 };
  }

  private startOfWeek(date: Date): Date {
    const copy = new Date(date);
    const day = copy.getDay();
    const diff = day === 0 ? -6 : 1 - day;
    copy.setDate(copy.getDate() + diff);
    copy.setHours(0, 0, 0, 0);
    return copy;
  }

  private toIsoDate(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  private parseIsoDate(value: string): Date | null {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null;
    const parsed = new Date(`${value}T00:00:00`);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  private buildPlanningTimeOptions(): string[] {
    const options: string[] = [];
    for (let minute = 7 * 60; minute <= 23 * 60; minute += 30) {
      const hh = String(Math.floor(minute / 60)).padStart(2, '0');
      const mm = String(minute % 60).padStart(2, '0');
      options.push(`${hh}:${mm}`);
    }
    return options;
  }

  private toMinutes(value: string): number {
    const [hh, mm] = value.split(':').map((part) => Number(part));
    if (!Number.isFinite(hh) || !Number.isFinite(mm)) return 0;
    return hh * 60 + mm;
  }

  private normalizePlanningPayload(rows: PlanningInput[]): PlanningInput[] {
    return rows
      .map((row) => ({
        date: row.date.trim(),
        slot: row.slot.trim(),
        topic: row.topic.trim()
      }))
      .sort((a, b) => {
        const byDate = a.date.localeCompare(b.date);
        if (byDate !== 0) return byDate;
        const aStart = this.parseSlotRange(a.slot)?.startMinutes ?? Number.MAX_SAFE_INTEGER;
        const bStart = this.parseSlotRange(b.slot)?.startMinutes ?? Number.MAX_SAFE_INTEGER;
        if (aStart !== bStart) return aStart - bStart;
        return a.slot.localeCompare(b.slot);
      });
  }

  private nextPlanningTime(value: string): string {
    const current = this.toMinutes(value);
    const candidate = this.planningTimeOptions.find((option) => this.toMinutes(option) > current);
    return candidate ?? value;
  }

  private previousPlanningTime(value: string): string {
    const current = this.toMinutes(value);
    const candidates = this.planningTimeOptions.filter((option) => this.toMinutes(option) < current);
    return candidates[candidates.length - 1] ?? value;
  }

  createTrainerResource(event: Event): void {
    event.preventDefault();
    this.trainerResourceNotice.set('');
    this.trainerResourceError.set('');

    const formationId = this.selectedTrainerResourceFormationId().trim();
    const sessionId = this.selectedTrainerResourceSessionId().trim();
    const title = this.trainerResourceTitle().trim();
    const type = this.trainerResourceType().trim() || 'DOC';
    const url = this.trainerResourceUrl().trim();
    const file = this.trainerResourceFile();

    if (!formationId || !sessionId || !title) {
      this.trainerResourceError.set('Formation, session et titre sont requis.');
      return;
    }

    if (!url && !file) {
      this.trainerResourceError.set('Ajoutez un lien ou selectionnez un fichier.');
      return;
    }

    this.creatingTrainerResource.set(true);
    const formData = new FormData();
    formData.append('formationId', formationId);
    formData.append('sessionId', sessionId);
    formData.append('title', title);
    formData.append('type', type);
    if (url) {
      formData.append('url', url);
    }
    if (file) {
      formData.append('file', file);
    }

    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/trainer/resources`,
        formData,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingTrainerResource.set(false);
          this.trainerResourceNotice.set(response.message);
          this.trainerResourceTitle.set('');
          this.trainerResourceType.set('PDF');
          this.trainerResourceUrl.set('');
          this.trainerResourceFile.set(null);
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingTrainerResource.set(false);
          this.trainerResourceError.set(err?.error?.message ?? 'Envoi de ressource impossible.');
        }
      });
  }

  onTrainerResourceFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;
    this.trainerResourceFile.set(file);
  }

  createAdminGlobalResource(event: Event): void {
    event.preventDefault();
    this.adminGlobalResourceNotice.set('');
    this.adminGlobalResourceError.set('');

    const title = this.adminGlobalResourceTitle().trim();
    const type = this.adminGlobalResourceType().trim() || 'DOC';
    const url = this.adminGlobalResourceUrl().trim();
    const file = this.adminGlobalResourceFile();

    if (!title) {
      this.adminGlobalResourceError.set('Le titre est requis.');
      return;
    }

    if (!url && !file) {
      this.adminGlobalResourceError.set('Ajoutez un lien ou selectionnez un fichier.');
      return;
    }

    this.creatingAdminGlobalResource.set(true);
    const formData = new FormData();
    formData.append('title', title);
    formData.append('type', type);
    if (url) {
      formData.append('url', url);
    }
    if (file) {
      formData.append('file', file);
    }

    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/admin/resources/global`,
        formData,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingAdminGlobalResource.set(false);
          this.adminGlobalResourceNotice.set(response.message);
          this.adminGlobalResourceTitle.set('');
          this.adminGlobalResourceType.set('PDF');
          this.adminGlobalResourceUrl.set('');
          this.adminGlobalResourceFile.set(null);
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingAdminGlobalResource.set(false);
          this.adminGlobalResourceError.set(err?.error?.message ?? 'Envoi global impossible.');
        }
      });
  }

  onAdminGlobalResourceFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;
    this.adminGlobalResourceFile.set(file);
  }

  onProviderFileSelected(
    event: Event,
    type: 'kbis' | 'rib' | 'vigilanceCertificate' | 'liabilityInsurance'
  ): void {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;
    if (type === 'kbis') this.providerKbisFile.set(file);
    if (type === 'rib') this.providerRibFile.set(file);
    if (type === 'vigilanceCertificate') this.providerVigilanceCertificateFile.set(file);
    if (type === 'liabilityInsurance') this.providerLiabilityInsuranceFile.set(file);
  }

  createProvider(event: Event): void {
    event.preventDefault();
    this.providerNotice.set('');
    this.providerError.set('');

    const companyName = this.providerCompanyName().trim();
    const siret = this.providerSiret().trim();
    const address = this.providerAddress().trim();
    const phone = this.providerPhone().trim();
    const activityDeclarationNumber = this.providerActivityDeclarationNumber().trim();
    const kbis = this.providerKbisFile();
    const rib = this.providerRibFile();
    const vigilanceCertificate = this.providerVigilanceCertificateFile();
    const liabilityInsurance = this.providerLiabilityInsuranceFile();

    if (!companyName || !siret || !address || !phone || !activityDeclarationNumber) {
      this.providerError.set('Tous les champs prestataire sont obligatoires.');
      return;
    }

    if (!kbis || !rib || !vigilanceCertificate || !liabilityInsurance) {
      this.providerError.set('Tous les documents obligatoires doivent etre fournis.');
      return;
    }

    this.creatingProvider.set(true);
    const formData = new FormData();
    formData.append('companyName', companyName);
    formData.append('siret', siret);
    formData.append('address', address);
    formData.append('phone', phone);
    formData.append('activityDeclarationNumber', activityDeclarationNumber);
    formData.append('kbis', kbis);
    formData.append('rib', rib);
    formData.append('vigilanceCertificate', vigilanceCertificate);
    formData.append('liabilityInsurance', liabilityInsurance);

    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/admin/providers`,
        formData,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingProvider.set(false);
          this.providerNotice.set(response.message);
          this.providerCompanyName.set('');
          this.providerSiret.set('');
          this.providerAddress.set('');
          this.providerPhone.set('');
          this.providerActivityDeclarationNumber.set('');
          this.providerKbisFile.set(null);
          this.providerRibFile.set(null);
          this.providerVigilanceCertificateFile.set(null);
          this.providerLiabilityInsuranceFile.set(null);
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingProvider.set(false);
          this.providerError.set(err?.error?.message ?? 'Creation prestataire impossible.');
        }
      });
  }

  toggleProviderColumn(
    column: 'companyName' | 'siret' | 'address' | 'phone' | 'activityDeclarationNumber' | 'documents' | 'createdAt',
    checked: boolean
  ): void {
    this.providerColumnVisibility.set({
      ...this.providerColumnVisibility(),
      [column]: checked
    });
  }

  isProviderColumnVisible(
    column: 'companyName' | 'siret' | 'address' | 'phone' | 'activityDeclarationNumber' | 'documents' | 'createdAt'
  ): boolean {
    return this.providerColumnVisibility()[column];
  }

  applyProviderTablePreset(preset: 'compact' | 'full' | 'documents'): void {
    this.providerTablePreset.set(preset);
    if (preset === 'compact') {
      this.providerColumnVisibility.set({
        companyName: true,
        siret: true,
        address: false,
        phone: true,
        activityDeclarationNumber: false,
        documents: false,
        createdAt: true
      });
      return;
    }
    if (preset === 'documents') {
      this.providerColumnVisibility.set({
        companyName: true,
        siret: true,
        address: false,
        phone: false,
        activityDeclarationNumber: false,
        documents: true,
        createdAt: true
      });
      return;
    }

    this.providerColumnVisibility.set({
      companyName: true,
      siret: true,
      address: true,
      phone: true,
      activityDeclarationNumber: true,
      documents: true,
      createdAt: true
    });
  }

  filteredProviders = computed(() => {
    const query = this.providerSearch().trim().toLowerCase();
    if (!query) return this.providers();
    return this.providers().filter((provider) =>
      `${provider.companyName} ${provider.siret} ${provider.address} ${provider.phone} ${provider.activityDeclarationNumber}`
        .toLowerCase()
        .includes(query)
    );
  });
  providerTotalPages = computed(() => {
    const total = this.filteredProviders().length;
    return Math.max(1, Math.ceil(total / this.providerPageSize));
  });
  paginatedProviders = computed(() => {
    const page = Math.min(this.providerPage(), this.providerTotalPages());
    const start = (page - 1) * this.providerPageSize;
    return this.filteredProviders().slice(start, start + this.providerPageSize);
  });

  updateProviderSearch(value: string): void {
    this.providerSearch.set(value);
    this.providerPage.set(1);
  }

  previousProviderPage(): void {
    this.providerPage.set(Math.max(1, this.providerPage() - 1));
  }

  nextProviderPage(): void {
    this.providerPage.set(Math.min(this.providerTotalPages(), this.providerPage() + 1));
  }

  resolveDocumentUrl(url?: string): string {
    const raw = (url ?? '').trim();
    if (!raw) return '#';
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
      return raw;
    }
    if (raw.startsWith('/')) {
      try {
        const apiOrigin = new URL(this.apiBaseUrl).origin;
        return `${apiOrigin}${raw}`;
      } catch {
        return raw;
      }
    }
    return raw;
  }

  availableStudentsForSelector = computed(() =>
    this.adminStudents()
      .filter((student) => !this.selectedStudentIds().includes(student.id))
      .filter((student) => {
        const q = this.studentFilterForSelector().trim().toLowerCase();
        if (!q) return true;
        return `${student.firstName} ${student.lastName} ${student.email}`.toLowerCase().includes(q);
      })
  );
  filteredPlanningRowsForCreate = computed(() => {
    const selectedDate = this.planningDateFilter().trim();
    const rows = this.planningRows();
    if (!selectedDate) {
      return rows.map((row, index) => ({ row, index }));
    }
    return rows
      .map((row, index) => ({ row, index }))
      .filter((item) => item.row.date === selectedDate);
  });

  getStudentNameById(studentId: number): string {
    const student = this.adminStudents().find((item) => item.id === studentId);
    return student ? `${student.firstName} ${student.lastName}` : `#${studentId}`;
  }

  updatePlanningRow(index: number, field: keyof PlanningInput, value: string): void {
    const rows = [...this.planningRows()];
    if (!rows[index]) return;
    rows[index] = { ...rows[index], [field]: value };
    this.planningRows.set(rows);
  }

  updateEditPlanningRow(index: number, field: keyof PlanningInput, value: string): void {
    const rows = [...this.editPlanningRows()];
    if (!rows[index]) return;
    rows[index] = { ...rows[index], [field]: value };
    this.editPlanningRows.set(rows);
  }

  planningRowStartTime(row: PlanningInput): string {
    const [startRaw] = row.slot.split('-').map((part) => part.trim());
    return /^\d{2}:\d{2}$/.test(startRaw) ? startRaw : '09:00';
  }

  planningRowEndTime(row: PlanningInput): string {
    const [, endRaw] = row.slot.split('-').map((part) => part.trim());
    return /^\d{2}:\d{2}$/.test(endRaw) ? endRaw : '12:30';
  }

  updatePlanningRowTime(index: number, boundary: 'start' | 'end', value: string): void {
    const rows = [...this.planningRows()];
    const row = rows[index];
    if (!row) return;

    let start = this.planningRowStartTime(row);
    let end = this.planningRowEndTime(row);

    if (boundary === 'start') {
      start = value;
    } else {
      end = value;
    }

    if (this.toMinutes(end) <= this.toMinutes(start)) {
      if (boundary === 'start') {
        end = this.nextPlanningTime(start);
      } else {
        start = this.previousPlanningTime(end);
      }
    }

    rows[index] = { ...row, slot: `${start} - ${end}` };
    this.planningRows.set(rows);
  }

  updateEditPlanningRowTime(index: number, boundary: 'start' | 'end', value: string): void {
    const rows = [...this.editPlanningRows()];
    const row = rows[index];
    if (!row) return;

    let start = this.planningRowStartTime(row);
    let end = this.planningRowEndTime(row);

    if (boundary === 'start') {
      start = value;
    } else {
      end = value;
    }

    if (this.toMinutes(end) <= this.toMinutes(start)) {
      if (boundary === 'start') {
        end = this.nextPlanningTime(start);
      } else {
        start = this.previousPlanningTime(end);
      }
    }

    rows[index] = { ...row, slot: `${start} - ${end}` };
    this.editPlanningRows.set(rows);
  }

  addPlanningRow(): void {
    this.planningRows.set([...this.planningRows(), { date: '', slot: '09:00 - 12:30', topic: '' }]);
  }

  addEditPlanningRow(): void {
    this.editPlanningRows.set([...this.editPlanningRows(), { date: '', slot: '09:00 - 12:30', topic: '' }]);
  }

  removePlanningRow(index: number): void {
    const rows = this.planningRows();
    if (rows.length <= 1) return;
    this.planningRows.set(rows.filter((_, i) => i !== index));
  }

  removeEditPlanningRow(index: number): void {
    const rows = this.editPlanningRows();
    if (rows.length <= 1) return;
    this.editPlanningRows.set(rows.filter((_, i) => i !== index));
  }

  createFormation(event: Event): void {
    event.preventDefault();
    this.adminCreateNotice.set('');
    this.adminCreateError.set('');

    if (!this.newFormationCatalogCourseId()) {
      this.adminCreateError.set('Selectionnez une formation du catalogue.');
      return;
    }

    if (!this.newFormationTrainerId()) {
      this.adminCreateError.set('Selectionnez un formateur.');
      return;
    }

    const selectedCatalogFormation = this.catalogFormations().find(
      (item) => item.id === this.newFormationCatalogCourseId()
    );
    if (!selectedCatalogFormation) {
      this.adminCreateError.set('Formation catalogue invalide.');
      return;
    }

    this.creatingFormation.set(true);
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/admin/formations`,
        {
          catalogCourseId: selectedCatalogFormation.id,
          catalogCourseTitle: selectedCatalogFormation.title,
          title: selectedCatalogFormation.title,
          mode: this.newFormationMode().trim() || 'En ligne',
          trainerId: this.newFormationTrainerId(),
          startDate: this.newFormationStartDate().trim(),
          endDate: this.newFormationEndDate().trim(),
          teamsLink: this.newFormationTeamsLink().trim(),
          classLabel: this.newFormationClassLabel().trim() || `Classe ${selectedCatalogFormation.title}`,
          capacity: this.newFormationCapacity(),
          studentIds: this.selectedStudentIds(),
          planning: this.normalizePlanningPayload(this.planningRows())
        },
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingFormation.set(false);
          this.adminCreateNotice.set(response.message);
          this.resetAdminCreationForm();
          this.showAdminFormationCreateForm.set(false);
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingFormation.set(false);
          this.adminCreateError.set(err?.error?.message ?? 'Creation de formation impossible.');
        }
      });
  }

  private loadCatalogFormations(): void {
    if (!this.isAdmin()) return;
    this.catalogFormationsLoading.set(true);
    this.catalogFormationsError.set('');
    this.http
      .get<{ formations: CatalogFormationOption[] }>(
        `${this.apiBaseUrl}/admin/catalog-formations`,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          const rows = Array.isArray(response.formations) ? response.formations : [];
          this.catalogFormations.set(rows);
          if (!rows.some((item) => item.id === this.newFormationCatalogCourseId())) {
            this.newFormationCatalogCourseId.set(rows[0]?.id ?? '');
          }
          this.catalogFormationsLoading.set(false);
        },
        error: () => {
          this.catalogFormations.set([]);
          this.catalogFormationsError.set('Impossible de charger le catalogue des formations.');
          this.catalogFormationsLoading.set(false);
        }
      });
  }

  private loadCertificationCatalog(): void {
    if (!this.isAdmin()) return;
    this.certificationCatalogLoading.set(true);
    this.certificationCatalogError.set('');
    this.http
      .get<{ certifications: CertificationCatalogOption[] }>(
        `${this.apiBaseUrl}/admin/catalog-certifications`,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.certificationCatalog.set(Array.isArray(response.certifications) ? response.certifications : []);
          this.certificationCatalogLoading.set(false);
        },
        error: () => {
          this.certificationCatalog.set([]);
          this.certificationCatalogError.set('Impossible de charger le catalogue des certifications.');
          this.certificationCatalogLoading.set(false);
        }
      });
  }

  updateApprenticeRow(index: number, field: keyof ApprenticeInput, value: string): void {
    const rows = [...this.apprenticeRows()];
    if (!rows[index]) return;
    rows[index] = { ...rows[index], [field]: value };
    this.apprenticeRows.set(rows);
  }

  addApprenticeRow(): void {
    this.apprenticeRows.set([...this.apprenticeRows(), { firstName: '', lastName: '', email: '', birthDate: '' }]);
  }

  removeApprenticeRow(index: number): void {
    const rows = this.apprenticeRows();
    if (rows.length <= 1) return;
    this.apprenticeRows.set(rows.filter((_, i) => i !== index));
  }

  createApprentices(event: Event): void {
    event.preventDefault();
    this.apprenticeCreateNotice.set('');
    this.apprenticeCreateError.set('');
    this.studentPasswordNotice.set('');
    this.studentPasswordError.set('');
    this.creatingApprentices.set(true);
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/admin/students`,
        {
          students: this.apprenticeRows().map((row) => ({
            firstName: row.firstName.trim(),
            lastName: row.lastName.trim(),
            email: row.email.trim(),
            birthDate: row.birthDate.trim()
          }))
        },
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingApprentices.set(false);
          this.apprenticeCreateNotice.set(response.message);
          this.apprenticeRows.set([{ firstName: '', lastName: '', email: '', birthDate: '' }]);
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingApprentices.set(false);
          this.apprenticeCreateError.set(err?.error?.message ?? 'Creation apprentis impossible.');
        }
      });
  }

  resendStudentPassword(studentId: number): void {
    this.studentPasswordNotice.set('');
    this.studentPasswordError.set('');
    this.apprenticeCreateNotice.set('');
    this.apprenticeCreateError.set('');
    this.resendingStudentPasswordId.set(studentId);
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/admin/students/${studentId}/resend-password`,
        {},
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.resendingStudentPasswordId.set(null);
          this.studentPasswordNotice.set(response.message);
        },
        error: (err) => {
          this.resendingStudentPasswordId.set(null);
          this.studentPasswordError.set(err?.error?.message ?? 'Renvoi du mot de passe impossible.');
        }
      });
  }

  setSelectedFormationsForTrainer(selectedOptions: HTMLOptionsCollection): void {
    const ids: string[] = [];
    for (let i = 0; i < selectedOptions.length; i += 1) {
      const value = String(selectedOptions.item(i)?.value ?? '').trim();
      if (value !== '') {
        ids.push(value);
      }
    }
    this.selectedFormationIdsForTrainer.set(ids);
  }

  addSelectedFormationForTrainer(): void {
    const formationId = this.selectedFormationToAddForTrainer().trim();
    if (!formationId) return;
    if (this.selectedFormationIdsForTrainer().includes(formationId)) return;
    this.selectedFormationIdsForTrainer.set([...this.selectedFormationIdsForTrainer(), formationId]);
    this.selectedFormationToAddForTrainer.set('');
    this.newTrainerPhone.set('');
    this.newTrainerStatus.set('salarie');
    this.newTrainerCompanyName.set('');
    this.newTrainerMicrosoftTranscriptUrl.set('');
    this.newTrainerCvFile.set(null);
  }

  removeSelectedFormationForTrainer(formationId: string): void {
    this.selectedFormationIdsForTrainer.set(
      this.selectedFormationIdsForTrainer().filter((id) => id !== formationId)
    );
  }

  selectedFormationsForTrainer = computed(() =>
    this.formations().filter((formation) => !formation.archived && this.selectedFormationIdsForTrainer().includes(formation.id))
  );

  availableFormationsForTrainerSelector = computed(() =>
    this.formations()
      .filter((formation) => !formation.archived)
      .filter((formation) => !this.selectedFormationIdsForTrainer().includes(formation.id))
      .filter((formation) => {
        const q = this.formationFilterForTrainer().trim().toLowerCase();
        if (!q) return true;
        return `${formation.title} ${formation.trainer}`.toLowerCase().includes(q);
      })
  );
  apprenticeAssignedIds = computed(() => {
    const ids = new Set<number>();
    for (const classItem of this.adminClasses()) {
      for (const student of classItem.students) {
        ids.add(student.id);
      }
    }
    return ids;
  });
  apprenticeKpis = computed(() => {
    const total = this.adminStudents().length;
    const assigned = this.apprenticeAssignedIds().size;
    const unassigned = Math.max(0, total - assigned);
    return { total, assigned, unassigned };
  });
  filteredAdminStudents = computed(() => {
    const q = this.apprenticeSearch().trim().toLowerCase();
    if (!q) return this.adminStudents();
    return this.adminStudents().filter((student) =>
      `${student.firstName} ${student.lastName} ${student.email} ${student.birthDate ?? ''}`.toLowerCase().includes(q)
    );
  });

  planningFormationOptions = computed(() =>
    this.formations().map((formation) => ({
      id: formation.id,
      title: formation.title
    }))
  );

  selectedPlanningFormation = computed(() => {
    const formations = this.formations();
    if (!formations.length) return null;
    const selectedId = this.selectedPlanningFormationId();
    return formations.find((formation) => formation.id === selectedId) ?? formations[0];
  });

  createTrainer(event: Event): void {
    event.preventDefault();
    this.trainerCreateNotice.set('');
    this.trainerCreateError.set('');
    const newStatus = this.newTrainerStatus().trim();
    const needsTrainerCompany = newStatus === 'freelance' || newStatus === 'partenaire';
    if (
      !this.newTrainerFirstName().trim()
      || !this.newTrainerLastName().trim()
      || !this.newTrainerEmail().trim()
      || !this.newTrainerPhone().trim()
      || (needsTrainerCompany && !this.newTrainerCompanyName().trim())
      || !this.newTrainerMicrosoftTranscriptUrl().trim()
      || !this.newTrainerCvFile()
    ) {
      this.trainerCreateError.set('Tous les champs obligatoires du formateur doivent etre renseignes.');
      return;
    }
    this.creatingTrainer.set(true);
    const formData = new FormData();
    formData.append('firstName', this.newTrainerFirstName().trim());
    formData.append('lastName', this.newTrainerLastName().trim());
    formData.append('email', this.newTrainerEmail().trim());
    formData.append('phone', this.newTrainerPhone().trim());
    formData.append('status', this.newTrainerStatus().trim());
    formData.append('companyName', needsTrainerCompany ? this.newTrainerCompanyName().trim() : '');
    formData.append('microsoftTranscriptUrl', this.newTrainerMicrosoftTranscriptUrl().trim());
    formData.append('cvFile', this.newTrainerCvFile()!);
    for (const formationId of this.selectedFormationIdsForTrainer()) {
      formData.append('formationIds[]', formationId);
    }
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/admin/trainers`,
        formData,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingTrainer.set(false);
          this.trainerCreateNotice.set(response.message);
          this.newTrainerFirstName.set('');
          this.newTrainerLastName.set('');
          this.newTrainerEmail.set('');
          this.newTrainerPhone.set('');
          this.newTrainerStatus.set('salarie');
          this.newTrainerCompanyName.set('');
          this.newTrainerMicrosoftTranscriptUrl.set('');
          this.newTrainerCvFile.set(null);
          this.selectedFormationIdsForTrainer.set([]);
          this.selectedFormationToAddForTrainer.set('');
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingTrainer.set(false);
          this.trainerCreateError.set(err?.error?.message ?? 'Creation formateur impossible.');
        }
      });
  }

  onTrainerStatusChange(value: string): void {
    this.newTrainerStatus.set(value);
    if (value === 'salarie') {
      this.newTrainerCompanyName.set('');
      return;
    }
    const provider = this.providers()[0];
    if (!this.newTrainerCompanyName().trim() && provider) {
      this.newTrainerCompanyName.set(provider.companyName);
    }
  }

  onTrainerEditStatusChange(value: string): void {
    this.trainerEditStatus.set(value);
    if (value === 'salarie') {
      this.trainerEditCompanyName.set('');
      return;
    }
    const provider = this.providers()[0];
    if (!this.trainerEditCompanyName().trim() && provider) {
      this.trainerEditCompanyName.set(provider.companyName);
    }
  }

  providerCompanyOptions = computed(() =>
    this.providers()
      .map((provider) => provider.companyName.trim())
      .filter((name, index, arr) => !!name && arr.indexOf(name) === index)
      .sort((a, b) => a.localeCompare(b))
  );
  certificationNameOptions = computed(() => {
    const namesFromCertCatalog = this.certificationCatalog()
      .map((item) => (item.name ?? '').trim())
      .filter((name) => name !== '');

    const namesFromFormationCatalog = this.catalogFormations()
      .map((item) => (item.title ?? '').trim())
      .filter((name) => name !== '');

    const merged = [...namesFromCertCatalog, ...namesFromFormationCatalog];
    const unique = Array.from(new Set(merged.map((name) => name.toLowerCase())));

    return unique
      .map((lowerName) => merged.find((name) => name.toLowerCase() === lowerName) ?? lowerName)
      .sort((a, b) => a.localeCompare(b));
  });

  selectedTrainer = computed(() => {
    const id = this.trainerViewId();
    if (id === null) return null;
    return this.adminTrainers().find((trainer) => trainer.id === id) ?? null;
  });

  selectTrainerForView(trainerId: number): void {
    this.trainerViewId.set(trainerId);
    this.trainerEditId.set(null);
  }

  startEditTrainer(trainerId: number): void {
    const trainer = this.adminTrainers().find((item) => item.id === trainerId);
    if (!trainer) return;
    this.trainerCreateError.set('');
    this.trainerEditId.set(trainerId);
    this.trainerViewId.set(null);
    this.trainerEditFirstName.set(trainer.firstName || '');
    this.trainerEditLastName.set(trainer.lastName || '');
    this.trainerEditEmail.set(trainer.email || '');
    this.trainerEditPhone.set(trainer.phone || '');
    const editStatus = trainer.status || 'salarie';
    this.trainerEditStatus.set(editStatus);
    this.trainerEditCompanyName.set(editStatus === 'salarie' ? '' : trainer.companyName || '');
    this.trainerEditMicrosoftTranscriptUrl.set(trainer.microsoftTranscriptUrl || '');
    this.trainerEditCvUrl.set(trainer.cvUrl || '');
    const certs = (trainer.certifications ?? []).map((cert) => ({
      name: cert.name || '',
      issuer: cert.issuer || '',
      expiresAt: cert.expiresAt || '',
      proof: cert.proof || '',
      existingProof: cert.proof || ''
    }));
    this.trainerCertifications.set(certs.length ? certs : [{ name: '', issuer: '', expiresAt: '', proof: '' }]);
    const completedTrainings = (trainer.completedTrainings ?? []).map((training) => ({
      domain: training.domain || '',
      description: training.description || '',
      objective: training.objective || '',
      trainingOrganization: training.trainingOrganization || '',
      trainingDate: training.trainingDate || '',
      durationHours: training.durationHours || '',
      attestationUrl: training.attestationUrl || '',
      existingAttestationUrl: training.attestationUrl || ''
    }));
    this.trainerCompletedTrainings.set(
      completedTrainings.length
        ? completedTrainings
        : [{ domain: '', description: '', objective: '', trainingOrganization: '', trainingDate: '', durationHours: '', attestationUrl: '' }]
    );
  }

  cancelEditTrainer(): void {
    this.trainerCreateError.set('');
    this.trainerEditId.set(null);
  }

  closeTrainerView(): void {
    this.trainerViewId.set(null);
  }

  updateTrainerCertification(index: number, field: keyof TrainerCertificationInput, value: string): void {
    const rows = [...this.trainerCertifications()];
    if (!rows[index]) return;
    rows[index] = { ...rows[index], [field]: value };
    this.trainerCertifications.set(rows);
  }

  addTrainerCertificationRow(): void {
    this.trainerCertifications.set([...this.trainerCertifications(), { name: '', issuer: '', expiresAt: '', proof: '' }]);
  }

  removeTrainerCertificationRow(index: number): void {
    const rows = this.trainerCertifications();
    if (rows.length <= 1) return;
    this.trainerCertifications.set(rows.filter((_, i) => i !== index));
  }

  onTrainerEditCvFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;
    this.trainerEditCvFile.set(file);
  }

  onTrainerCertificationProofSelected(index: number, event: Event): void {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;
    const rows = [...this.trainerCertifications()];
    if (!rows[index]) return;
    rows[index] = { ...rows[index], proofFile: file };
    this.trainerCertifications.set(rows);
  }

  updateTrainerCompletedTraining(index: number, field: keyof TrainerCompletedTrainingInput, value: string): void {
    const rows = [...this.trainerCompletedTrainings()];
    if (!rows[index]) return;
    rows[index] = { ...rows[index], [field]: value };
    this.trainerCompletedTrainings.set(rows);
  }

  addTrainerCompletedTrainingRow(): void {
    this.trainerCompletedTrainings.set([
      ...this.trainerCompletedTrainings(),
      { domain: '', description: '', objective: '', trainingOrganization: '', trainingDate: '', durationHours: '', attestationUrl: '' }
    ]);
  }

  removeTrainerCompletedTrainingRow(index: number): void {
    const rows = this.trainerCompletedTrainings();
    if (rows.length <= 1) return;
    this.trainerCompletedTrainings.set(rows.filter((_, i) => i !== index));
  }

  onTrainerCompletedTrainingAttestationSelected(index: number, event: Event): void {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;
    const rows = [...this.trainerCompletedTrainings()];
    if (!rows[index]) return;
    rows[index] = { ...rows[index], attestationFile: file };
    this.trainerCompletedTrainings.set(rows);
  }

  trainerHasExpiringCertification(trainer: AdminPerson): boolean {
    return this.trainerExpiringCertificationNames(trainer).length > 0;
  }

  trainerExpiringCertificationNames(trainer: AdminPerson): string[] {
    const certifications = trainer.certifications ?? [];
    const now = new Date();
    const threshold = new Date();
    threshold.setDate(now.getDate() + 30);

    return certifications
      .filter((cert) => {
        const expires = this.parseLooseDate(cert.expiresAt);
        if (!expires) return false;
        return expires >= now && expires <= threshold;
      })
      .map((cert) => cert.name || 'Certification')
      .filter((name, index, arr) => arr.indexOf(name) === index);
  }

  certificationExpiresSoon(expiresAt?: string): boolean {
    const expires = this.parseLooseDate(expiresAt || '');
    if (!expires) return false;
    const now = new Date();
    const threshold = new Date();
    threshold.setDate(now.getDate() + 30);
    return expires >= now && expires <= threshold;
  }

  private parseLooseDate(value: string): Date | null {
    const raw = (value ?? '').trim();
    if (!raw) return null;

    const direct = new Date(raw);
    if (!Number.isNaN(direct.getTime())) return direct;

    const match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (match) {
      const normalized = `${match[3]}-${match[2]}-${match[1]}`;
      const parsed = new Date(normalized);
      if (!Number.isNaN(parsed.getTime())) return parsed;
    }

    return null;
  }

  saveTrainerEdit(event: Event): void {
    event.preventDefault();
    const trainerId = this.trainerEditId();
    if (!trainerId) return;

    this.trainerCreateNotice.set('');
    this.trainerCreateError.set('');
    const editStatus = this.trainerEditStatus().trim();
    const editNeedsCompany = editStatus === 'freelance' || editStatus === 'partenaire';
    if (editNeedsCompany && !this.trainerEditCompanyName().trim()) {
      this.trainerCreateError.set('Choisissez une societe dans la liste Prestataires.');
      return;
    }

    this.creatingTrainer.set(true);

    const formData = new FormData();
    formData.append('firstName', this.trainerEditFirstName().trim());
    formData.append('lastName', this.trainerEditLastName().trim());
    formData.append('email', this.trainerEditEmail().trim());
    formData.append('phone', this.trainerEditPhone().trim());
    formData.append('status', editStatus);
    formData.append('companyName', editNeedsCompany ? this.trainerEditCompanyName().trim() : '');
    formData.append('microsoftTranscriptUrl', this.trainerEditMicrosoftTranscriptUrl().trim());
    if (this.trainerEditCvFile()) {
      formData.append('cvFile', this.trainerEditCvFile()!);
    }
    formData.append(
      'certifications',
      JSON.stringify(
        this.trainerCertifications()
          .map((cert) => ({
            name: cert.name.trim(),
            issuer: cert.issuer.trim(),
            expiresAt: cert.expiresAt.trim(),
            proof: cert.proof.trim(),
            existingProof: cert.existingProof?.trim() || ''
          }))
          .filter((cert) => cert.name || cert.issuer || cert.expiresAt || cert.proof)
      )
    );
    this.trainerCertifications().forEach((cert, index) => {
      if (cert.proofFile) {
        formData.append(`certificationProofFile_${index}`, cert.proofFile);
      }
    });
    formData.append(
      'completedTrainings',
      JSON.stringify(
        this.trainerCompletedTrainings()
          .map((training) => ({
            domain: training.domain.trim(),
            description: training.description.trim(),
            objective: training.objective.trim(),
            trainingOrganization: training.trainingOrganization.trim(),
            trainingDate: training.trainingDate.trim(),
            durationHours: String(training.durationHours ?? '').trim(),
            attestationUrl: training.attestationUrl?.trim() || '',
            existingAttestationUrl: training.existingAttestationUrl?.trim() || ''
          }))
          .filter(
            (training) =>
              training.domain
              || training.description
              || training.objective
              || training.trainingOrganization
              || training.trainingDate
              || training.durationHours
              || training.attestationUrl
          )
      )
    );
    this.trainerCompletedTrainings().forEach((training, index) => {
      if (training.attestationFile) {
        formData.append(`completedTrainingAttestationFile_${index}`, training.attestationFile);
      }
    });

    this.http
      .put<{ message: string }>(
        `${this.apiBaseUrl}/admin/trainers/${trainerId}`,
        formData,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.creatingTrainer.set(false);
          this.trainerCreateError.set('');
          this.trainerCreateNotice.set(response.message);
          this.trainerEditId.set(null);
          this.loadDashboard();
        },
        error: (err) => {
          this.creatingTrainer.set(false);
          this.trainerCreateError.set(err?.error?.message ?? 'Modification formateur impossible.');
        }
      });
  }

  deleteTrainer(trainerId: number): void {
    this.trainerCreateNotice.set('');
    this.trainerCreateError.set('');
    this.deletingTrainerId.set(trainerId);

    this.http
      .delete<{ message: string }>(
        `${this.apiBaseUrl}/admin/trainers/${trainerId}`,
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.deletingTrainerId.set(null);
          this.trainerCreateNotice.set(response.message);
          if (this.trainerViewId() === trainerId) this.trainerViewId.set(null);
          if (this.trainerEditId() === trainerId) this.trainerEditId.set(null);
          this.loadDashboard();
        },
        error: (err) => {
          this.deletingTrainerId.set(null);
          this.trainerCreateError.set(err?.error?.message ?? 'Suppression formateur impossible.');
        }
      });
  }

  onTrainerCvFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0] ?? null;
    this.newTrainerCvFile.set(file);
  }

  onLogoError(event: Event): void {
    const img = event.target as HTMLImageElement | null;
    if (!img) return;
    if (!img.src.endsWith('/assets/logo-clouddev.svg')) {
      img.src = '/assets/logo-clouddev.svg';
    }
  }

  quickAction(type: (typeof this.quickActions)[number]['type']): void {
    if (type === 'teams') {
      const teamsLink = this.nextFormation()?.teamsLink;
      if (teamsLink) {
        window.open(teamsLink, '_blank', 'noopener,noreferrer');
      }
      return;
    }

    if (type === 'attendance') {
      this.activeSection.set('sessions-student');
      return;
    }

    window.alert('Support: support@clouddev.local');
  }

  markSelfAttendance(sessionId: string): void {
    this.attendanceNotice.set('');
    this.attendanceError.set('');
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/attendance/self`,
        { sessionId },
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.attendanceNotice.set(response.message);
          this.loadDashboard();
        },
        error: (err) => {
          this.attendanceError.set(err?.error?.message ?? 'Impossible de signer la presence.');
        }
      });
  }

  markAttendanceForStudent(sessionId: string, studentId: number, status: AttendanceStatus): void {
    this.attendanceNotice.set('');
    this.attendanceError.set('');
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/attendance/mark`,
        { sessionId, studentId, status },
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.attendanceNotice.set(response.message);
          this.loadDashboard();
        },
      error: (err) => {
          this.attendanceError.set(err?.error?.message ?? 'Mise a jour emargement impossible.');
        }
      });
  }

  openAttendanceWindow(sessionId: string): void {
    this.attendanceNotice.set('');
    this.attendanceError.set('');
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/attendance/window/open`,
        { sessionId },
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          // Start a local 10-minute countdown immediately for responsive UI.
          this.localAttendanceDeadlines.set(sessionId, Date.now() + 10 * 60 * 1000);
          this.attendanceNotice.set(response.message);
          this.loadDashboard();
        },
        error: (err) => {
          this.attendanceError.set(err?.error?.message ?? "Impossible d'ouvrir l'emargement.");
        }
      });
  }

  closeAttendanceWindow(sessionId: string): void {
    this.attendanceNotice.set('');
    this.attendanceError.set('');
    this.http
      .post<{ message: string }>(
        `${this.apiBaseUrl}/attendance/window/close`,
        { sessionId },
        { headers: this.authHeaders() }
      )
      .subscribe({
        next: (response) => {
          this.localAttendanceDeadlines.delete(sessionId);
          this.attendanceNotice.set(response.message);
          this.loadDashboard();
        },
        error: (err) => {
          this.attendanceError.set(err?.error?.message ?? "Impossible de fermer l'emargement.");
        }
      });
  }

  attendanceRemainingSeconds(session: AttendanceSession): number {
    if (!session.attendanceWindow?.isOpen) return 0;

    const serverExpiresAt = session.attendanceWindow.expiresAt;
    const serverExpiresMs = serverExpiresAt ? Date.parse(serverExpiresAt) : Number.NaN;
    const localExpiresMs = this.localAttendanceDeadlines.get(session.id);
    const now = this.nowMs();

    // Prefer the latest valid deadline between API and local timer.
    const candidates = [serverExpiresMs, localExpiresMs]
      .filter((value): value is number => typeof value === 'number' && Number.isFinite(value) && value > now);

    if (!candidates.length) {
      return 0;
    }

    const effectiveExpiresMs = Math.max(...candidates);
    return Math.max(0, Math.floor((effectiveExpiresMs - now) / 1000));
  }

  attendanceCountdownLabel(session: AttendanceSession): string {
    if (!session.attendanceWindow?.isOpen) {
      return 'Emargement ferme';
    }
    const remaining = this.attendanceRemainingSeconds(session);
    if (remaining <= 0) {
      return 'Fermeture imminente';
    }
    return `Fermeture dans ${this.formatCountdown(remaining)}`;
  }

  attendanceCountdownValue(session: AttendanceSession): string {
    if (!session.attendanceWindow?.isOpen) {
      return '--:--';
    }
    const remaining = this.attendanceRemainingSeconds(session);
    return this.formatCountdown(Math.max(0, remaining));
  }

  attendanceWindowLevel(session: AttendanceSession): 'closed' | 'open' | 'warning' | 'danger' {
    if (!session.attendanceWindow?.isOpen) {
      return 'closed';
    }
    const remaining = this.attendanceRemainingSeconds(session);
    if (remaining <= 60) return 'danger';
    if (remaining <= 180) return 'warning';
    return 'open';
  }

  attendanceWindowProgress(session: AttendanceSession): number {
    const windowState = session.attendanceWindow;
    if (!windowState?.isOpen || !windowState.openedAt || !windowState.expiresAt) return 0;

    const openedMs = Date.parse(windowState.openedAt);
    const expiresMs = Date.parse(windowState.expiresAt);
    if (Number.isNaN(openedMs) || Number.isNaN(expiresMs) || expiresMs <= openedMs) return 0;

    const elapsed = this.nowMs() - openedMs;
    const total = expiresMs - openedMs;
    const percent = (elapsed / total) * 100;
    return Math.max(0, Math.min(100, percent));
  }

  studentAttendanceStatusLabel(session: AttendanceSession): string {
    const record = session.records.find((item) => item.status === 'present' || item.status === 'late');
    if (record) return 'Presence signee';
    if (session.canSelfSign) return 'Signer ma presence';
    return 'Emargement ferme';
  }

  private formatCountdown(totalSeconds: number): string {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  }

  private loadDashboard(): void {
    if (!this.token()) return;

    this.dashboardLoading.set(true);
    this.http.get<DashboardResponse>(`${this.apiBaseUrl}/me/dashboard`, { headers: this.authHeaders() }).subscribe({
      next: (response) => {
        this.currentRole.set(response.role);
        this.student.set(response.role === 'student' ? response.profile : null);
        this.trainer.set(response.role === 'trainer' ? response.profile : null);
        this.admin.set(response.role === 'admin' ? response.profile : null);
        const sortedFormations = [...response.formations].sort((a, b) => {
          const aRank = this.formationNextSessionRank(a);
          const bRank = this.formationNextSessionRank(b);
          if (aRank !== bRank) return aRank - bRank;
          return a.title.localeCompare(b.title);
        });
        this.formations.set(sortedFormations);
        this.attendanceSessions.set(response.attendanceSessions ?? []);
        this.adminClasses.set(response.classes ?? []);
        this.adminStudents.set(response.students ?? []);
        this.adminTrainers.set(response.trainers ?? []);
        this.providers.set(response.providers ?? []);
        this.documents.set(response.documents ?? []);
        if (response.role === 'admin') {
          this.loadCatalogFormations();
          this.loadCertificationCatalog();
        } else {
          this.catalogFormations.set([]);
          this.catalogFormationsError.set('');
          this.catalogFormationsLoading.set(false);
          this.certificationCatalog.set([]);
          this.certificationCatalogError.set('');
          this.certificationCatalogLoading.set(false);
        }
        this.syncLocalAttendanceDeadlines(response.attendanceSessions ?? []);
        const formationIds = new Set(sortedFormations.map((formation) => formation.id));
        const activeAdminFormations = sortedFormations.filter((formation) => !formation.archived);
        const archivedAdminFormations = sortedFormations.filter((formation) => formation.archived);
        const activeAdminFormationIds = new Set(activeAdminFormations.map((formation) => formation.id));
        const archivedAdminFormationIds = new Set(archivedAdminFormations.map((formation) => formation.id));
        if (!activeAdminFormationIds.has(this.selectedAdminFormationId())) {
          this.selectedAdminFormationId.set(activeAdminFormations[0]?.id ?? '');
        }
        if (!archivedAdminFormationIds.has(this.selectedArchivedFormationId())) {
          this.selectedArchivedFormationId.set(archivedAdminFormations[0]?.id ?? '');
        }
        if (!formationIds.has(this.selectedStudentSessionFormationId())) {
          this.selectedStudentSessionFormationId.set(sortedFormations[0]?.id ?? '');
        }
        if (!formationIds.has(this.selectedPlanningFormationId())) {
          this.selectedPlanningFormationId.set(sortedFormations[0]?.id ?? '');
        }
        if (!formationIds.has(this.selectedTrainerSessionFormationId())) {
          this.selectedTrainerSessionFormationId.set(sortedFormations[0]?.id ?? '');
        }
        if (!formationIds.has(this.selectedTrainerResourceFormationId())) {
          this.selectedTrainerResourceFormationId.set(sortedFormations[0]?.id ?? '');
        }
        const sessionIds = new Set((response.attendanceSessions ?? []).map((session) => session.id));
        if (!sessionIds.has(this.selectedTrainerResourceSessionId())) {
          this.selectedTrainerResourceSessionId.set((response.attendanceSessions ?? [])[0]?.id ?? '');
        }
        const selectedApprenticeFormationId = this.selectedTrainerApprenticeFormationId();
        if (
          selectedApprenticeFormationId !== 'all'
          && !formationIds.has(selectedApprenticeFormationId)
        ) {
          this.selectedTrainerApprenticeFormationId.set('all');
        }
        if (response.role === 'admin' && !this.selectedAttendanceFormation() && this.adminAttendanceFormationOptions().length) {
          this.selectedAttendanceFormation.set(this.adminAttendanceFormationOptions()[0]);
        }
        if (response.role === 'admin' && !this.newFormationTrainerId() && (response.trainers?.length ?? 0) > 0) {
          this.newFormationTrainerId.set(response.trainers![0].id);
        }
        if (!response.attendanceSessions?.length) {
          this.attendanceNotice.set('');
          this.attendanceError.set('');
        }
        this.dashboardLoading.set(false);
      },
      error: () => {
        this.dashboardLoading.set(false);
        this.localAttendanceDeadlines.clear();
        this.logout();
      }
    });
  }

  private syncLocalAttendanceDeadlines(sessions: AttendanceSession[]): void {
    const now = Date.now();
    const activeSessionIds = new Set(sessions.map((session) => session.id));

    for (const [sessionId] of this.localAttendanceDeadlines) {
      if (!activeSessionIds.has(sessionId)) {
        this.localAttendanceDeadlines.delete(sessionId);
      }
    }

    for (const session of sessions) {
      if (!session.attendanceWindow?.isOpen) {
        this.localAttendanceDeadlines.delete(session.id);
        continue;
      }

      const serverExpiresMs = session.attendanceWindow.expiresAt
        ? Date.parse(session.attendanceWindow.expiresAt)
        : Number.NaN;
      const localExpiresMs = this.localAttendanceDeadlines.get(session.id);

      // If API does not provide a valid future expiration, keep/start a 10-min local fallback.
      if (!Number.isFinite(serverExpiresMs) || serverExpiresMs <= now) {
        if (!localExpiresMs || localExpiresMs <= now) {
          this.localAttendanceDeadlines.set(session.id, now + 10 * 60 * 1000);
        }
      }
    }
  }

  private formationNextSessionRank(formation: FormationDashboard): number {
    const now = Date.now();
    let nearestFuture = Number.POSITIVE_INFINITY;
    let nearestPast = Number.NEGATIVE_INFINITY;

    for (const slot of formation.planning ?? []) {
      const startTs = this.slotStartTimestamp(slot.date, slot.slot);
      if (!Number.isFinite(startTs)) continue;
      if (startTs >= now && startTs < nearestFuture) {
        nearestFuture = startTs;
      }
      if (startTs < now && startTs > nearestPast) {
        nearestPast = startTs;
      }
    }

    if (Number.isFinite(nearestFuture)) return nearestFuture;
    if (Number.isFinite(nearestPast)) return now + 10_000_000_000 + (now - nearestPast);
    return Number.POSITIVE_INFINITY;
  }

  private slotStartTimestamp(dateIso: string, slotRange: string): number {
    const date = (dateIso ?? '').trim();
    const slot = (slotRange ?? '').trim();
    const match = slot.match(/(\d{1,2}:\d{2})\s*-/);
    if (!date || !match) return Number.NaN;
    return new Date(`${date}T${match[1]}:00`).getTime();
  }

  private sessionCatalogFormationNameStrict(formation: FormationDashboard): string {
    const direct = (formation.catalogCourseTitle ?? '').trim();
    if (direct) return direct;
    const catalogId = (formation.catalogCourseId ?? '').trim();
    if (!catalogId) return '';
    const fromCatalog = this.catalogFormations().find((item) => item.id === catalogId)?.title?.trim();
    return fromCatalog || '';
  }

  private authHeaders(): HttpHeaders {
    return new HttpHeaders({
      Authorization: `Bearer ${this.token()}`
    });
  }

  private resetAdminCreationForm(): void {
    this.newFormationTitle.set('');
    this.newFormationCatalogCourseId.set(this.catalogFormations()[0]?.id ?? '');
    this.newFormationMode.set('En ligne');
    this.newFormationTrainerId.set(this.adminTrainers()[0]?.id ?? null);
    this.newFormationStartDate.set('');
    this.newFormationEndDate.set('');
    this.newFormationTeamsLink.set('');
    this.newFormationClassLabel.set('');
    this.newFormationCapacity.set(20);
    this.planningDateFilter.set('');
    this.selectedStudentIds.set([]);
    this.selectedStudentToAdd.set(null);
    this.planningRows.set([{ date: '', slot: '09:00 - 12:30', topic: '' }]);
  }
}

interface LoginResponse {
  token: string;
  role: UserRole;
  profile: {
    id: number;
    firstName: string;
    lastName: string;
    email: string;
  };
}

interface DashboardResponse {
  role: UserRole;
  profile: {
    id: number;
    firstName: string;
    lastName: string;
    email: string;
  };
  formations: FormationDashboard[];
  attendanceSessions: AttendanceSession[];
  documents?: ResourceDocument[];
  classes?: AdminClass[];
  students?: AdminPerson[];
  trainers?: AdminPerson[];
  providers?: ProviderRecord[];
}

interface ResourceDocument {
  title: string;
  type: string;
  updatedAt: string;
  formationTitle?: string;
  sessionId?: string;
  sessionLabel?: string;
  url?: string;
  senderName?: string;
  sentAt?: string;
}

interface FormationDashboard {
  id: string;
  title: string;
  catalogCourseId?: string;
  catalogCourseTitle?: string;
  mode: string;
  trainerId?: number;
  trainer: string;
  status: string;
  archived?: boolean;
  teamsLink: string;
  startDate: string;
  endDate: string;
  apprentices?: { id: number; name: string; email: string }[];
  planning: {
    day: string;
    date: string;
    slot: string;
    topic: string;
  }[];
}

interface AttendanceSession {
  id: string;
  formationTitle: string;
  date: string;
  slot: string;
  topic: string;
  canSelfSign: boolean;
  attendanceWindow?: {
    isOpen: boolean;
    openedAt: string | null;
    expiresAt: string | null;
  };
  records: AttendanceRecord[];
}

interface AttendanceRecord {
  studentId: number;
  studentName: string;
  status: AttendanceStatus;
  updatedAt: string | null;
}

type AttendanceStatus = 'present' | 'absent' | 'late' | 'pending';
type UserRole = 'student' | 'trainer' | 'admin';
type SectionKey = 'dashboard' | 'parcours' | 'planning' | 'mes-apprentis' | 'emargement' | 'ressources' | 'evaluations' | 'sessions' | 'sessions-detail' | 'sessions-student' | 'sessions-student-detail' | 'support' | 'formations' | 'formation-detail-admin' | 'formateurs' | 'prestataires' | 'apprentis' | 'planning-admin' | 'archives';

interface AdminPerson {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
  birthDate?: string | null;
  phone?: string;
  status?: string;
  companyName?: string;
  microsoftTranscriptUrl?: string;
  cvUrl?: string;
  certifications?: TrainerCertificationInput[];
  completedTrainings?: TrainerCompletedTrainingInput[];
}

interface AdminClass {
  id: string;
  label: string;
  formationId: string;
  formationTitle: string;
  trainerId: number;
  trainer: string;
  capacity: number;
  teamsLink?: string | null;
  students: AdminPerson[];
}

interface PlanningInput {
  date: string;
  slot: string;
  topic: string;
}

interface ApprenticeInput {
  firstName: string;
  lastName: string;
  email: string;
  birthDate: string;
}

interface CatalogFormationOption {
  id: string;
  title: string;
  code?: string;
  label?: string;
}

interface CertificationCatalogOption {
  name: string;
}

interface AdminPlanningWeekEvent {
  formationTitle: string;
  trainer: string;
  teamsLink: string;
  date: string;
  slot: string;
  topic: string;
  topPercent: number;
  heightPercent: number;
}

interface TrainerCertificationInput {
  name: string;
  issuer: string;
  expiresAt: string;
  proof: string;
  existingProof?: string;
  proofFile?: File | null;
}

interface TrainerCompletedTrainingInput {
  domain: string;
  description: string;
  objective: string;
  trainingOrganization: string;
  trainingDate: string;
  durationHours: string;
  attestationUrl: string;
  existingAttestationUrl?: string;
  attestationFile?: File | null;
}

interface ProviderRecord {
  id: string;
  companyName: string;
  siret: string;
  address: string;
  phone: string;
  activityDeclarationNumber: string;
  createdAt: string;
  documents?: {
    kbis?: { url?: string };
    rib?: { url?: string };
    vigilanceCertificate?: { url?: string };
    liabilityInsurance?: { url?: string };
  };
}
