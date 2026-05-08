import axios from 'axios'

const API_BASE_URL = '/api'

export interface User {
  id: number
  name: string
  email: string
}

export interface AuthResponse {
  user: User
  token: string
  message: string
}

export const authService = {
  async register(name: string, email: string, password: string, password_confirmation: string): Promise<AuthResponse> {
    const response = await axios.post(`${API_BASE_URL}/register`, {
      name,
      email,
      password,
      password_confirmation
    })
    
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
    }
    
    return response.data
  },

  async login(email: string, password: string): Promise<AuthResponse> {
    const response = await axios.post(`${API_BASE_URL}/login`, {
      email,
      password
    })
    
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
    }
    
    return response.data
  },

  async logout(): Promise<void> {
    try {
      await axios.post(`${API_BASE_URL}/logout`)
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      delete axios.defaults.headers.common['Authorization']
    }
  },

  async getUser(): Promise<User | null> {
    try {
      const response = await axios.get(`${API_BASE_URL}/user`)
      return response.data
    } catch (error) {
      return null
    }
  },

  getToken(): string | null {
    return localStorage.getItem('auth_token')
  },

  getCurrentUser(): User | null {
    const userStr = localStorage.getItem('user')
    return userStr ? JSON.parse(userStr) : null
  },

  isAuthenticated(): boolean {
    return !!this.getToken()
  },

  initAxios() {
    const token = this.getToken()
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    }
  }
}

// Инициализация при загрузке
authService.initAxios()