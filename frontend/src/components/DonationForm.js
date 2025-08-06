import React, { useState } from 'react';
import { donationAPI } from '../services/api';

const DonationForm = ({ campaign, onSuccess, onCancel }) => {
  const [formData, setFormData] = useState({
    donor_name: '',
    donor_email: '',
    amount: '',
    payment_method: campaign.payment_type,
    message: ''
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
      const donationData = {
        ...formData,
        campaign_id: campaign.id
      };

      const response = await donationAPI.create(donationData);
      if (response.data.success) {
        alert('Thank you for your donation!');
        onSuccess();
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to process donation');
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

  return (
    <div className="donation-form">
      <h2>Donate to: {campaign.title}</h2>
      <p className="campaign-summary">
        Current: {formatAmount(campaign.current_amount)} / Goal: {formatAmount(campaign.target_amount)}
      </p>

      {error && <div className="error-message">{error}</div>}

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="donor_name">Your Name:</label>
          <input
            type="text"
            id="donor_name"
            name="donor_name"
            value={formData.donor_name}
            onChange={handleChange}
            required
            placeholder="Enter your full name"
          />
        </div>

        <div className="form-group">
          <label htmlFor="donor_email">Your Email (optional):</label>
          <input
            type="email"
            id="donor_email"
            name="donor_email"
            value={formData.donor_email}
            onChange={handleChange}
            placeholder="your.email@example.com"
          />
        </div>

        <div className="form-group">
          <label htmlFor="amount">Donation Amount (BDT):</label>
          <input
            type="number"
            id="amount"
            name="amount"
            value={formData.amount}
            onChange={handleChange}
            required
            min="1"
            placeholder="Enter amount"
          />
        </div>

        <div className="form-group">
          <label htmlFor="payment_method">Payment Method:</label>
          <select
            id="payment_method"
            name="payment_method"
            value={formData.payment_method}
            onChange={handleChange}
            required
          >
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="rocket">Rocket</option>
            <option value="bank">Bank Transfer</option>
          </select>
        </div>

        <div className="form-group">
          <label htmlFor="message">Message (optional):</label>
          <textarea
            id="message"
            name="message"
            value={formData.message}
            onChange={handleChange}
            rows="3"
            placeholder="Leave a message of support..."
          />
        </div>

        <div className="payment-info">
          <h4>Payment Instructions:</h4>
          <p>
            <strong>Preferred Method:</strong> {campaign.payment_type.toUpperCase()}
          </p>
          <p>
            <strong>Organizer:</strong> {campaign.creator_name}
          </p>
          <p>
            <strong>Contact:</strong> {campaign.creator_email}
          </p>
          <small>
            Note: After clicking "Donate", please contact the organizer to complete the payment process.
          </small>
        </div>

        <div className="form-actions">
          <button type="button" onClick={onCancel} className="cancel-btn">
            Cancel
          </button>
          <button type="submit" disabled={loading} className="submit-btn">
            {loading ? 'Processing...' : 'Donate Now'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default DonationForm;