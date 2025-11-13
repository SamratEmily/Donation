import React, { useState, useEffect } from 'react';
import { campaignAPI } from '../services/api';

const CampaignList = ({ onCampaignSelect }) => {
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchCampaigns();
  }, []);

  const fetchCampaigns = async () => {
    try {
      const response = await campaignAPI.getAll();
      if (response.data.success) {
        setCampaigns(response.data.data);
      }
    } catch (err) {
      setError('Failed to load campaigns');
    } finally {
      setLoading(false);
    }
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-BD', {
      style: 'currency',
      currency: 'BDT',
      minimumFractionDigits: 0
    }).format(amount);
  };

  if (loading) return <div className="loading">Loading campaigns...</div>;
  if (error) return <div className="error-message">{error}</div>;

  return (
    <div className="campaign-list">
      <h2>Active Donation Campaigns</h2>
      {campaigns.length === 0 ? (
        <p>No campaigns available at the moment.</p>
      ) : (
        <div className="campaigns-grid">
          {campaigns.map((campaign) => (
            <div key={campaign.id} className="campaign-card">
              <h3>{campaign.title}</h3>
              <p className="campaign-description">{campaign.description}</p>
              
              <div className="campaign-stats">
                <div className="progress-bar">
                  <div 
                    className="progress-fill" 
                    style={{ 
                      width: `${Math.min(100, (campaign.current_amount / campaign.target_amount) * 100)}%`,
                      backgroundColor: campaign.current_amount >= campaign.target_amount ? '#28a745' : ''
                    }}
                  ></div>
                </div>
                
                <div className="amounts">
                  <span className="current-amount">
                    Raised: {formatAmount(campaign.current_amount)}
                  </span>
                  <span className="target-amount">
                    Goal: {formatAmount(campaign.target_amount)}
                  </span>
                </div>
                
                <div className="campaign-meta">
                  <p><strong>Organizer:</strong> {campaign.creator_name}</p>
                  <p><strong>Payment Method:</strong> {campaign.payment_type.toUpperCase()}</p>
                  {campaign.creator_phone && (
                    <p><strong>Phone:</strong> {campaign.creator_phone}</p>
                  )}
                </div>
              </div>
              
              {campaign.is_active ? (
                <button 
                  className="donate-btn"
                  onClick={() => onCampaignSelect(campaign)}
                >
                  {campaign.current_amount >= campaign.target_amount ? 'Goal Achieved - Donate More' : 'Donate Now'}
                </button>
              ) : (
                <button className="donate-btn disabled" disabled>
                  Campaign Closed
                </button>
              )}
              
              <div className="campaign-link">
                <small>Share: /campaign/{campaign.slug}</small>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default CampaignList;