import axios from 'axios';

const API_BASE_URL = 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

export const campaignAPI = {
  // Get all campaigns
  getAll: () => api.get('/campaigns'),
  
  // Get campaign by slug
  getBySlug: (slug) => api.get(`/campaigns/slug/${slug}`),
  
  // Create new campaign
  create: (data) => api.post('/campaigns', data),
  
  // Update campaign
  update: (id, data) => api.put(`/campaigns/${id}`, data),
  
  // Delete campaign
  delete: (id) => api.delete(`/campaigns/${id}`),
};

export const donationAPI = {
  // Get all donations
  getAll: (campaignId = null) => {
    const params = campaignId ? { campaign_id: campaignId } : {};
    return api.get('/donations', { params });
  },
  
  // Create new donation
  create: (data) => api.post('/donations', data),
  
  // Get donation by id
  getById: (id) => api.get(`/donations/${id}`),
  
  // Update donation
  update: (id, data) => api.put(`/donations/${id}`, data),
  
  // Delete donation
  delete: (id) => api.delete(`/donations/${id}`),
};

export default api;