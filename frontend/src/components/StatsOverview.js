import React from 'react';
import { formatAmount } from '../utils/formatters';

const StatsOverview = ({ stats }) => {
  return (
    <div className="admin-stats">
      <div className="stat-card">
        <h3>{stats.campaignsLabel || 'Total Campaigns'}</h3>
        <div className="stat-number">{stats.totalCampaigns}</div>
      </div>
      <div className="stat-card">
        <h3>Active Campaigns</h3>
        <div className="stat-number">{stats.activeCampaigns}</div>
      </div>
      <div className="stat-card">
        <h3>Total Donations</h3>
        <div className="stat-number">{stats.totalDonations}</div>
      </div>
      <div className="stat-card">
        <h3>Total Amount Raised</h3>
        <div className="stat-number">{formatAmount(stats.totalAmount)}</div>
      </div>
    </div>
  );
};

export default StatsOverview;
