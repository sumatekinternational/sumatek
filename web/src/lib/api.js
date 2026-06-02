import axios from 'axios'
import { i18n } from '../i18n'

// Thin axios wrapper: attaches the Sanctum bearer token + the active locale,
// and bounces to /login on 401.
export const api = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  config.headers['Accept-Language'] = i18n.global.locale.value
  return config
})

api.interceptors.response.use(
  (r) => r,
  (error) => {
    if (error.response?.status === 401 && location.hash !== '#/login') {
      localStorage.removeItem('token')
      location.hash = '#/login'
    }
    return Promise.reject(error)
  },
)
