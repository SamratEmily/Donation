import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { campaignAPI } from '../services/api';
import { useAuth } from '../contexts/AuthContext';

const CreateCampaign = ({ onCampaignCreated }) => {
  const navigate = useNavigate();
  const { user } = useAuth();
  
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    creator_phone: '',
    target_amount: '',
    payment_type: 'bkash'
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      // Backend automatically uses authenticated user's name/email
      const response = await campaignAPI.create(formData);
      if (response.data.success) {
        alert('Campaign created successfully and sent for approval!');
        setFormData({
          title: '',
          description: '',
          creator_phone: '',
          target_amount: '',
          payment_type: 'bkash'
        });
        if (onCampaignCreated) {
          onCampaignCreated(response.data.data);
        }
        // Redirect to home page
        navigate('/');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create campaign');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="create-campaign">
      <h2>Create New Donation Campaign</h2>
      {error && <div className="error-message">{error}</div>}
      
      <div className="user-info-banner" style={{ marginBottom: '20px', padding: '15px', backgroundColor: '#e3f2fd', borderRadius: '8px' }}>
        <p style={{ margin: 0 }}><strong>Creating as:</strong> {user?.name}</p>
        <p style={{ margin: '5px 0 0' }}><strong>Email:</strong> {user?.email}</p>
      </div>
      
      <form onSubmit={handleSubmit} className="campaign-form">
        <div className="form-group">
          <label htmlFor="title">
            Campaign Title
            <span className="required-indicator">*</span>
          </label>
          <input
            type="text"
            id="title"
            name="title"
            value={formData.title}
            onChange={handleChange}
            required
            placeholder="e.g., Help For Hamid"
          />
        </div>

        <div className="form-group">
          <label htmlFor="description">
            Description
            <span className="required-indicator">*</span>
          </label>
          <textarea
            id="description"
            name="description"
            value={formData.description}
            onChange={handleChange}
            required
            rows="4"
            placeholder="Describe your cause and why people should donate..."
          />
        </div>

        <div className="form-group">
          <label htmlFor="creator_phone">
            Phone Number
            <span className="required-indicator">*</span>
          </label>
          <input
            type="tel"
            id="creator_phone"
            name="creator_phone"
            value={formData.creator_phone}
            onChange={handleChange}
            required
            placeholder="+8801XXXXXXXXX"
          />
        </div>

        <div className="form-group">
          <label htmlFor="target_amount">
            Target Amount (BDT)
            <span className="required-indicator">*</span>
          </label>
          <input
            type="number"
            id="target_amount"
            name="target_amount"
            value={formData.target_amount}
            onChange={handleChange}
            required
            min="1"
            placeholder="50000"
          />
        </div>

        <div className="form-group">
          <label htmlFor="payment_type">
            Preferred Payment Method
            <span className="required-indicator">*</span>
          </label>
          <select
            id="payment_type"
            name="payment_type"
            value={formData.payment_type}
            onChange={handleChange}
            required
          >
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="rocket">Rocket</option>
            <option value="bank">Bank Transfer</option>
          </select>
        </div>

        <button type="submit" disabled={loading} className="submit-btn">
          {loading ? 'Creating...' : 'Create Campaign'}
        </button>
      </form>
    </div>
  );
};

export default CreateCampaign;