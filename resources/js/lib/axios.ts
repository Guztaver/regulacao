import axios from 'axios';

// Create axios instance with default configuration for Laravel Sanctum
const axiosInstance = axios.create({
    baseURL: '/',
    timeout: 10000,
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Request interceptor to add CSRF token
axiosInstance.interceptors.request.use(
    (config) => {
        // Get CSRF token from meta tag
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    },
);

// Response interceptor for error handling
axiosInstance.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        // Log errors for debugging but don't auto-redirect
        if (error.response?.status === 401) {
            console.warn('Authentication required for API request');
        }

        if (error.response?.status === 419) {
            console.warn('CSRF token mismatch');
        }

        return Promise.reject(error);
    },
);

// Configure global axios defaults for backward compatibility
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add CSRF token to global axios as well
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

// Export the configured instance as default
export default axiosInstance;

// Also export the original axios for compatibility
export { axios };
