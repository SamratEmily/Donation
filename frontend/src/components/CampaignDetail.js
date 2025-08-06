import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { campaignAPI, donationAPI } from '../services/api';
import DonationForm from './DonationForm';

const CampaignDetail = () => {
  const { slug } = useParams();
  const [campaign, setCampaign] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showDonationForm, setShowDonationForm] = useState(false);

  useEffect(() => {
    fetchCampaign();
  }, [slug]);

  const fetchCampaign = async () => {
    try {
      const response = await campaignAPI.getBySlug(slug);
      if (response.data.success) {
        setCampaign(response.data.data);
      }
    } catch (err) {
      setError('Campaign not found');
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

  const handleDonationSuccess = () => {
    setShowDonationForm(false);
    fetchCampaign(); // Refresh campaign data
  };

  if (loading) return <div className="loading">Loading campaign...</div>;
  if (error) return <div className="error-message">{error}</div>;
  if (!campaign) return <div className="error-message">Campaign not found</div>;

  const progressPercentage = (campaign.current_amount / campaign.target_amount) * 100;
  const isCompleted = campaign.current_amount >= campaign.target_amount;

  return (
    <div className="campaign-detail">
      <div className="campaign-header">
        <h1>{campaign.title}</h1>
        <p className="campaign-subtitle">For Mankind</p>
        {!campaign.is_active && (
          <div className="campaign-status closed">
            <span>⚠️ This campaign is closed</span>
          </div>
        )}
        {isCompleted && campaign.is_active && (
          <div className="campaign-status completed">
            <span>🎉 Goal Achieved!</span>
          </div>
        )}
      </div>

      <div className="campaign-content">
        <div className="campaign-info">
          <div className="campaign-stats">
            <div className="total-amount">
              <h2>Total Amount: {formatAmount(campaign.current_amount)} BDT</h2>
              <p>Goal: {formatAmount(campaign.target_amount)} BDT</p>
            </div>
            
            <div className="progress-section">
              <div className="progress-bar large">
                <div 
                  className="progress-fill" 
                  style={{ width: `${progressPercentage}%` }}
                ></div>
              </div>
              <p className="progress-text">
                {progressPercentage.toFixed(1)}% of goal reached
                {isCompleted && <span className="goal-achieved"> - Goal Achieved! 🎉</span>}
              </p>
            </div>
          </div>

          <div className="campaign-description">
            <h3>About this campaign</h3>
            <p>{campaign.description}</p>
          </div>

          <div className="campaign-organizer">
            <h3>Organizer</h3>
            <p><strong>Name:</strong> {campaign.creator_name}</p>
            <p><strong>Email:</strong> {campaign.creator_email}</p>
            <p><strong>Preferred Payment:</strong> {campaign.payment_type.toUpperCase()}</p>
          </div>

          <div className="donation-actions">
            {campaign.is_active ? (
              <button 
                className="donate-btn large"
                onClick={() => setShowDonationForm(true)}
              >
                Donate Now
              </button>
            ) : (
              <div className="campaign-closed-message">
                <p>This campaign is no longer accepting donations.</p>
                <p>Thank you to everyone who contributed!</p>
              </div>
            )}
          </div>
        </div>

        {campaign.donations && campaign.donations.length > 0 && (
          <div className="recent-donations">
            <h3>Recent Donations</h3>
            <div className="donations-list">
              {campaign.donations.slice(0, 10).map((donation) => (
                <div key={donation.id} className="donation-item">
                  <div className="donor-info">
                    <strong>{donation.donor_name}</strong>
                    {donation.message && <p className="donation-message">"{donation.message}"</p>}
                  </div>
                  <div className="donation-amount">
                    {formatAmount(donation.amount)}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {showDonationForm && (
        <div className="modal-overlay">
          <div className="modal-content">
            <button 
              className="close-btn"
              onClick={() => setShowDonationForm(false)}
            >
              ×
            </button>
            <DonationForm 
              campaign={campaign}
              onSuccess={handleDonationSuccess}
              onCancel={() => setShowDonationForm(false)}
            />
          </div>
        </div>
      )}
    </div>
  );
};

export default CampaignDetail;