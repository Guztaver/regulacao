import { computed } from 'vue';

export interface Translations {
  // Navigation
  dashboard: string;
  patients: string;
  completedEntries: string;
  platform: string;

  // Dashboard
  managePatients: string;
  activeEntries: string;
  addEntry: string;
  addPatient: string;
  filters: string;
  refresh: string;

  // Patient Management
  createNewPatient: string;
  createNewEntry: string;
  name: string;
  email: string;
  phone: string;
  susNumber: string;
  title: string;
  patient: string;
  selectPatient: string;
  noPatients: string;
  createPatientFirst: string;

  // Actions
  create: string;
  cancel: string;
  delete: string;
  save: string;
  edit: string;
  view: string;
  apply: string;
  clear: string;
  clearAll: string;
  applyFilters: string;

  // Status
  pending: string;
  completed: string;
  examScheduled: string;
  examReady: string;
  cancelled: string;
  unknown: string;

  // Table Headers
  id: string;
  dateCreated: string;
  addedBy: string;
  status: string;
  actions: string;

  // Messages
  loading: string;
  creating: string;
  noEntriesFound: string;
  noPatientsList: string;
  createFirstEntry: string;
  adjustFilters: string;
  entryDetails: string;

  // Filters
  dateFrom: string;
  dateTo: string;
  patientName: string;
  entryId: string;
  limitResults: string;
  searchByPatientName: string;
  enterSpecificEntryId: string;
  entries: string;
  activeFilters: string;
  from: string;
  to: string;
  limit: string;

  // Placeholders
  enterPatientName: string;
  enterEmailAddress: string;
  enterPhoneNumber: string;
  enterSusNumber: string;
  enterEntryTitle: string;
  susNumberDigits: string;

  // Validation & Errors
  required: string;
  invalidEmail: string;
  susNumberFormat: string;
  createError: string;
  loadError: string;
  deleteError: string;

  // Success Messages
  patientCreated: string;
  entryCreated: string;
  entryDeleted: string;
  filtersApplied: string;

  // Common UI
  clickToView: string;
  unknownPatient: string;
  scheduled: string;
  documentsCount: string;
  entriesCount: string;

  // Search
  search: string;
  searchPatients: string;
}

const ptBRTranslations: Translations = {
  // Navigation
  dashboard: 'Painel de Controle',
  patients: 'Pacientes',
  completedEntries: 'Entradas Concluídas',
  platform: 'Plataforma',

  // Dashboard
  managePatients: 'Gerencie pacientes e entradas de forma eficiente',
  activeEntries: 'Entradas Ativas',
  addEntry: 'Adicionar Entrada',
  addPatient: 'Adicionar Paciente',
  filters: 'Filtros',
  refresh: 'Atualizar',

  // Patient Management
  createNewPatient: 'Criar Novo Paciente',
  createNewEntry: 'Criar Nova Entrada',
  name: 'Nome',
  email: 'E-mail',
  phone: 'Telefone',
  susNumber: 'Número do SUS',
  title: 'Título',
  patient: 'Paciente',
  selectPatient: 'Selecione um paciente',
  noPatients: 'Nenhum paciente disponível',
  createPatientFirst: 'Crie um paciente primeiro',

  // Actions
  create: 'Criar',
  cancel: 'Cancelar',
  delete: 'Excluir',
  save: 'Salvar',
  edit: 'Editar',
  view: 'Visualizar',
  apply: 'Aplicar',
  clear: 'Limpar',
  clearAll: 'Limpar Tudo',
  applyFilters: 'Aplicar Filtros',

  // Status
  pending: 'Pendente',
  completed: 'Concluído',
  examScheduled: 'Exame Agendado',
  examReady: 'Exame Pronto',
  cancelled: 'Cancelado',
  unknown: 'Desconhecido',

  // Table Headers
  id: 'ID',
  dateCreated: 'Data de Criação',
  addedBy: 'Adicionado por',
  status: 'Status',
  actions: 'Ações',

  // Messages
  loading: 'Carregando...',
  creating: 'Criando...',
  noEntriesFound: 'Nenhuma entrada encontrada',
  noPatientsList: 'Nenhum paciente encontrado',
  createFirstEntry: 'Crie sua primeira entrada ou ajuste seus filtros.',
  adjustFilters: 'Ajuste seus filtros ou crie um novo paciente.',
  entryDetails: 'Clique para visualizar detalhes da entrada',

  // Filters
  dateFrom: 'Data Inicial',
  dateTo: 'Data Final',
  patientName: 'Nome do Paciente',
  entryId: 'ID da Entrada',
  limitResults: 'Limitar Resultados',
  searchByPatientName: 'Buscar por nome do paciente...',
  enterSpecificEntryId: 'Digite um ID específico da entrada...',
  entries: 'entradas',
  activeFilters: 'Filtros ativos:',
  from: 'De:',
  to: 'Até:',
  limit: 'Limite:',

  // Placeholders
  enterPatientName: 'Digite o nome do paciente',
  enterEmailAddress: 'Digite o endereço de e-mail',
  enterPhoneNumber: 'Digite o número de telefone',
  enterSusNumber: 'Digite o número do SUS (15 dígitos)',
  enterEntryTitle: 'Digite o título da entrada',
  susNumberDigits: '15 dígitos',

  // Validation & Errors
  required: 'Campo obrigatório',
  invalidEmail: 'E-mail inválido',
  susNumberFormat: 'Número do SUS deve ter 15 dígitos',
  createError: 'Erro ao criar',
  loadError: 'Erro ao carregar',
  deleteError: 'Erro ao excluir',

  // Success Messages
  patientCreated: 'Paciente criado com sucesso!',
  entryCreated: 'Entrada criada com sucesso!',
  entryDeleted: 'Entrada excluída com sucesso!',
  filtersApplied: 'Filtros aplicados com sucesso!',

  // Common UI
  clickToView: 'Clique para visualizar',
  unknownPatient: 'Paciente Desconhecido',
  scheduled: 'Agendado',
  documentsCount: 'Documentos',
  entriesCount: 'Entradas',

  // Search
  search: 'Buscar',
  searchPatients: 'Buscar pacientes...',
};

const enTranslations: Translations = {
  // Navigation
  dashboard: 'Dashboard',
  patients: 'Patients',
  completedEntries: 'Completed Entries',
  platform: 'Platform',

  // Dashboard
  managePatients: 'Manage patients and entries efficiently',
  activeEntries: 'Active Entries',
  addEntry: 'Add Entry',
  addPatient: 'Add Patient',
  filters: 'Filters',
  refresh: 'Refresh',

  // Patient Management
  createNewPatient: 'Create New Patient',
  createNewEntry: 'Create New Entry',
  name: 'Name',
  email: 'Email',
  phone: 'Phone',
  susNumber: 'SUS Number',
  title: 'Title',
  patient: 'Patient',
  selectPatient: 'Select a patient',
  noPatients: 'No patients available',
  createPatientFirst: 'Please create a patient first',

  // Actions
  create: 'Create',
  cancel: 'Cancel',
  delete: 'Delete',
  save: 'Save',
  edit: 'Edit',
  view: 'View',
  apply: 'Apply',
  clear: 'Clear',
  clearAll: 'Clear All',
  applyFilters: 'Apply Filters',

  // Status
  pending: 'Pending',
  completed: 'Completed',
  examScheduled: 'Exam Scheduled',
  examReady: 'Exam Ready',
  cancelled: 'Cancelled',
  unknown: 'Unknown',

  // Table Headers
  id: 'ID',
  dateCreated: 'Date Created',
  addedBy: 'Added By',
  status: 'Status',
  actions: 'Actions',

  // Messages
  loading: 'Loading...',
  creating: 'Creating...',
  noEntriesFound: 'No entries found',
  noPatientsList: 'No patients found',
  createFirstEntry: 'Create your first entry or adjust your filters.',
  adjustFilters: 'Adjust your filters or create a new patient.',
  entryDetails: 'Click to view entry details',

  // Filters
  dateFrom: 'Date From',
  dateTo: 'Date To',
  patientName: 'Patient Name',
  entryId: 'Entry ID',
  limitResults: 'Limit Results',
  searchByPatientName: 'Search by patient name...',
  enterSpecificEntryId: 'Enter specific entry ID...',
  entries: 'entries',
  activeFilters: 'Active filters:',
  from: 'From:',
  to: 'To:',
  limit: 'Limit:',

  // Placeholders
  enterPatientName: 'Enter patient name',
  enterEmailAddress: 'Enter email address',
  enterPhoneNumber: 'Enter phone number',
  enterSusNumber: 'Enter SUS number (15 digits)',
  enterEntryTitle: 'Enter entry title',
  susNumberDigits: '15 digits',

  // Validation & Errors
  required: 'Required field',
  invalidEmail: 'Invalid email',
  susNumberFormat: 'SUS number must have 15 digits',
  createError: 'Failed to create',
  loadError: 'Failed to load',
  deleteError: 'Failed to delete',

  // Success Messages
  patientCreated: 'Patient created successfully!',
  entryCreated: 'Entry created successfully!',
  entryDeleted: 'Entry deleted successfully!',
  filtersApplied: 'Filters applied successfully!',

  // Common UI
  clickToView: 'Click to view',
  unknownPatient: 'Unknown Patient',
  scheduled: 'Scheduled',
  documentsCount: 'Documents',
  entriesCount: 'Entries',

  // Search
  search: 'Search',
  searchPatients: 'Search patients...',
};

// For now, we'll default to PT-BR, but this could be made dynamic
const currentLocale = 'pt-BR';

export function useTranslations() {
  const t = computed(() => {
    return currentLocale === 'pt-BR' ? ptBRTranslations : enTranslations;
  });

  return {
    t: t.value,
  };
}
