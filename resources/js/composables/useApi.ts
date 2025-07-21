import axios, { type AxiosResponse } from 'axios';

// Track if CSRF has been initialized to avoid multiple calls
let csrfInitialized = false;

/**
 * Initialize CSRF token for SPA authentication
 */
async function initializeCsrf(): Promise<void> {
    if (csrfInitialized) return;

    try {
        await axios.get('/sanctum/csrf-cookie');
        csrfInitialized = true;
    } catch (error) {
        console.error('Failed to initialize CSRF token:', error);
        throw error;
    }
}

/**
 * Composable for authenticated API calls
 */
export function useApi() {
    /**
     * Make an authenticated GET request
     */
    async function get<T = any>(url: string, params?: Record<string, any>): Promise<T> {
        await initializeCsrf();

        const queryString = params ? '?' + new URLSearchParams(params).toString() : '';
        const fullUrl = `${url}${queryString}`;

        const response: AxiosResponse<T> = await axios.get(fullUrl);

        return response.data;
    }

    /**
     * Make an authenticated POST request
     */
    async function post<T = any>(url: string, data?: any): Promise<T> {
        await initializeCsrf();

        const response: AxiosResponse<T> = await axios.post(url, data);
        return response.data;
    }

    /**
     * Make an authenticated PUT request
     */
    async function put<T = any>(url: string, data?: any): Promise<T> {
        await initializeCsrf();

        const response: AxiosResponse<T> = await axios.put(url, data);

        return response.data;
    }

    /**
     * Make an authenticated DELETE request
     */
    async function del<T = any>(url: string): Promise<T> {
        await initializeCsrf();

        const response: AxiosResponse<T> = await axios.delete(url);
        return response.data;
    }

    /**
     * Make an authenticated PATCH request
     */
    async function patch<T = any>(url: string, data?: any): Promise<T> {
        await initializeCsrf();

        const response: AxiosResponse<T> = await axios.patch(url, data);
        return response.data;
    }

    return {
        get,
        post,
        put,
        delete: del,
        patch,
    };
}

/**
 * API endpoints for patients
 */
export function usePatientApi() {
    const api = useApi();

    return {
        /**
         * Get all patients with optional search and limit
         */
        getPatients: (params?: { search?: string; limit?: number }) => api.get('/api/patients', params),

        /**
         * Get a specific patient by ID
         */
        getPatient: (id: string) => api.get(`/api/patients/${id}`),

        /**
         * Create a new patient
         */
        createPatient: (data: { name: string; email: string; phone?: string; sus_number?: string }) => api.post('/api/patients', data),

        /**
         * Update an existing patient
         */
        updatePatient: (id: string, data: { name: string; email: string; phone?: string; sus_number?: string }) =>
            api.put(`/api/patients/${id}`, data),

        /**
         * Delete a patient
         */
        deletePatient: (id: string) => api.delete(`/api/patients/${id}`),
    };
}

/**
 * API endpoints for entries
 */
export function useEntryApi() {
    const api = useApi();

    return {
        /**
         * Get entries with optional filters
         */
        getEntries: (params?: { date_from?: string; date_to?: string; patient_name?: string; entry_id?: string; limit?: number }) =>
            api.get('/api/entries', params),

        /**
         * Get active entries with optional filters
         */
        getActiveEntries: (params?: { date_from?: string; date_to?: string; patient_name?: string; entry_id?: string; limit?: number }) =>
            api.get('/api/entries/active', params),

        /**
         * Get scheduled entries with optional filters
         */
        getScheduledEntries: (params?: { date_from?: string; date_to?: string; patient_name?: string; entry_id?: string; limit?: number }) =>
            api.get('/api/entries/scheduled', params),

        /**
         * Get exam ready entries with optional filters
         */
        getExamReadyEntries: (params?: { date_from?: string; date_to?: string; patient_name?: string; entry_id?: string; limit?: number }) =>
            api.get('/api/entries/exam-ready', params),

        /**
         * Get completed entries with optional filters
         */
        getCompletedEntries: (params?: { date_from?: string; date_to?: string; patient_name?: string; entry_id?: string; limit?: number }) =>
            api.get('/api/entries/completed', params),

        /**
         * Get cancelled entries with optional filters
         */
        getCancelledEntries: (params?: { date_from?: string; date_to?: string; patient_name?: string; entry_id?: string; limit?: number }) =>
            api.get('/api/entries/cancelled', params),

        /**
         * Get a specific entry by ID
         */
        getEntry: (id: string) => api.get(`/api/entries/${id}`),

        /**
         * Create a new entry
         */
        createEntry: (data: { patient_id: string; title: string; brought_by?: string }) => api.post('/api/entries', data),

        /**
         * Delete an entry
         */
        deleteEntry: (id: string) => api.delete(`/api/entries/${id}`),

        /**
         * Get all available entry statuses
         */
        getStatuses: () => api.get('/api/entry-statuses'),

        /**
         * Get next possible statuses for an entry
         */
        getNextStatuses: (id: string) => api.get(`/api/entries/${id}/next-statuses`),

        /**
         * Get status history for an entry
         */
        getStatusHistory: (id: string) => api.get(`/api/entries/${id}/status-history`),

        /**
         * Transition an entry to a new status
         */
        transitionStatus: (id: string, statusId: number, reason?: string, metadata?: Record<string, any>) =>
            api.put(`/api/entries/${id}/transition-status`, {
                status_id: statusId,
                reason,
                metadata,
            }),

        /**
         * Cancel an entry
         */
        cancelEntry: (id: string, reason?: string) => api.put(`/api/entries/${id}/cancel`, { reason }),

        // Legacy methods for backward compatibility
        /**
         * Complete/toggle an entry (legacy)
         */
        completeEntry: (id: string) => api.put(`/api/entries/${id}/complete`),

        /**
         * Schedule an exam for an entry (legacy)
         */
        scheduleExam: (id: string, examScheduledDate: string, reason?: string) =>
            api.put(`/api/entries/${id}/schedule-exam`, {
                exam_scheduled_date: examScheduledDate,
                reason,
            }),

        /**
         * Mark an exam as ready (legacy)
         */
        markExamReady: (id: string) => api.put(`/api/entries/${id}/mark-exam-ready`),
    };
}

/**
 * API endpoints for patient documents
 */
export function useDocumentApi() {
    return {
        /**
         * DISABLED: Patient document functions are no longer supported
         * Use entry document functions instead
         */
        getPatientDocuments: () => {
            console.warn('Patient document API is disabled. Use entry documents instead.');
            return Promise.reject(new Error('Patient documents are no longer supported'));
        },

        getDocument: () => {
            console.warn('Patient document API is disabled. Use entry documents instead.');
            return Promise.reject(new Error('Patient documents are no longer supported'));
        },

        uploadDocument: async () => {
            console.warn('Patient document API is disabled. Use entry documents instead.');
            throw new Error('Patient documents are no longer supported');
        },

        deleteDocument: () => {
            console.warn('Patient document API is disabled. Use entry documents instead.');
            return Promise.reject(new Error('Patient documents are no longer supported'));
        },

        getDocumentTypes: () => {
            console.warn('Patient document types API is disabled. Use entry document types instead.');
            return Promise.reject(new Error('Patient documents are no longer supported'));
        },

        downloadDocument: async () => {
            console.warn('Patient document API is disabled. Use entry documents instead.');
            throw new Error('Patient documents are no longer supported');
        },
    };
}

/**
 * API endpoints for entry documents
 */
export function useEntryDocumentApi() {
    const api = useApi();

    return {
        /**
         * Get all documents for an entry
         */
        getEntryDocuments: (entryId: string) => api.get(`/api/entries/${entryId}/documents`),

        /**
         * Get a specific entry document
         */
        getEntryDocument: (entryId: string, documentId: string) => api.get(`/api/entries/${entryId}/documents/${documentId}`),

        /**
         * Upload a new document for an entry
         */
        uploadEntryDocument: async (entryId: string, file: File, documentType: string, description?: string) => {
            await initializeCsrf();

            const formData = new FormData();
            formData.append('file', file);
            formData.append('document_type', documentType);
            if (description) {
                formData.append('description', description);
            }

            const response = await axios.post(`/api/entries/${entryId}/documents`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return response.data;
        },

        /**
         * Delete an entry document
         */
        deleteEntryDocument: (entryId: string, documentId: string) => api.delete(`/api/entries/${entryId}/documents/${documentId}`),

        /**
         * Get entry document types
         */
        getEntryDocumentTypes: () => api.get('/api/entry-documents/types'),

        /**
         * Download an entry document
         */
        downloadEntryDocument: async (entryId: string, documentId: string) => {
            await initializeCsrf();

            const response = await axios.get(`/api/entries/${entryId}/documents/${documentId}/download`, {
                responseType: 'blob',
            });
            return response;
        },
    };
}

/**
 * Error handling utility
 */
export function handleApiError(error: any): string {
    if (error.response) {
        // Server responded with error status
        const status = error.response.status;
        const data = error.response.data;

        switch (status) {
            case 401:
                return 'Não autorizado. Faça login novamente.';
            case 403:
                return 'Acesso negado. Você não tem permissão para esta ação.';
            case 404:
                return 'Recurso não encontrado.';
            case 422:
                // Validation errors
                if (data.errors) {
                    const firstError = Object.values(data.errors)[0];
                    return Array.isArray(firstError) ? firstError[0] : String(firstError);
                }
                return data.message || 'Dados inválidos.';
            case 500:
                return 'Erro interno do servidor. Tente novamente mais tarde.';
            default:
                return data.message || `Erro ${status}: ${error.message}`;
        }
    } else if (error.request) {
        // Network error
        return 'Erro de conexão. Verifique sua internet e tente novamente.';
    } else {
        // Other error
        return error.message || 'Erro desconhecido.';
    }
}
