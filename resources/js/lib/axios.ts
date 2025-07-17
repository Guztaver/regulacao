import axios from 'axios';

// Configuração global para que o axios envie cookies em requisições cross-origin
axios.defaults.withCredentials = true;

// Adiciona o token CSRF para todas as requisições
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

// Configuração para que o axios aceite respostas JSON por padrão
