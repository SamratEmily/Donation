import React, { useState, useEffect } from 'react';
import { campaignAPI } from '../services/api';

const AdminPanel = () => {
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchAllCampaigns();
  }, []);

  const fetchAllCampaigns = async () => {
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

  const closeCampaign = async (campaignId) => {
    try {
      const response = await fetch(`http://localhost:8000/api/campaigns/${campaignId}/close`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        alert('Campaign closed successfully');
        fetchAllCampaigns();
      }
    } catch (err) {
      alert('Failed to close campaign');
    }
  };

  const reopenCampaign = async (campaignId) => {
    try {
      const response = await fetch(`http://localhost:8000/api/campaigns/${campaignId}/reopen`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });
      
      if (response.ok) {
        alert('Campaign reopened successfully');
        fetchAllCampaigns();
      }
    } catch (err) {
      alert('Failed to reopen campaign');
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
    <div className="admin-panel">
      <h2>Campaign Management</h2>
      
      <div className="campaigns-table">
        <table>
          <thead>
            <tr>
              <th>Campaign</th>
              <th>Creator</th>
              <th>Progress</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {campaigns.map((campaign) => {
              const progressPercentage = (campaign.current_amount / campaign.target_amount) * 100;
              const isCompleted = campaign.current_amount >= campaign.target_amount;
              
              return (
                <tr key={campaign.id}>
                  <td>
                    <div className="campaign-info">
                      <strong>{campaign.title}</strong>
                      <br />
                      <small>{campaign.slug}</small>
                    </div>
                  </td>
                  <td>
                    <div>
                      <strong>{campaign.creator_name}</strong>
                      <br />
                      <small>{campaign.creator_email}</small>
                    </div>
                  </td>
                  <td>
                    <div className="progress-info">
                      <div className="progress-bar small">
                        <div 
                          className="progress-fill" 
                          style={{ 
                            width: `${Math.min(100, progressPercentage)}%`,
                            backgroundColor: isCompleted ? '#28a745' : ''
                          }}
                        ></div>
                      </div>
                      <div className="amounts-small">
                        {formatAmount(campaign.current_amount)} / {formatAmount(campaign.target_amount)}
                      </div>
                      <div className="percentage">
                        {progressPercentage.toFixed(1)}%
                        {isCompleted && <span className="completed-badge">✓ Completed</span>}
                      </div>
                    </div>
                  </td>
                  <td>
                    <span className={`status-badge ${campaign.is_active ? 'active' : 'inactive'}`}>
                      {campaign.is_active ? 'Active' : 'Closed'}
                    </span>
                  </td>
                  <td>
                    <div className="action-buttons">
                      {campaign.is_active ? (
                        <button 
                          className="close-btn"
                          onClick={() => closeCampaign(campaign.id)}
                        >
                          Close Campaign
                        </button>
                      ) : (
                        <button 
                          className="reopen-btn"
                          onClick={() => reopenCampaign(campaign.id)}
                        >
                          Reopen Campaign
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default AdminPanel;