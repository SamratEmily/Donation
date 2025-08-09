import React, { useState, useEffect } from "react";
import { campaignAPI, donationAPI } from "../services/api";
import { useAuth } from "../contexts/AuthContext";

const UserDashboard = () => {
  const [campaigns, setCampaigns] = useState([]);
  const [donations, setDonations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [activeTab, setActiveTab] = useState("campaigns");
  const [stats, setStats] = useState({
    totalCampaigns: 0,
    activeCampaigns: 0,
    totalDonations: 0,
    totalAmount: 0,
  });

  const { user } = useAuth();

  const fetchAllCampaigns = async () => {
    try {
      const response = await fetch("http://localhost:8000/api/campaigns/all", {
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setCampaigns(data.data);
        }
      }
    } catch (err) {
      console.error("Failed to load campaigns:", err);
    }
  };

  const fetchAllDonations = async () => {
    try {
      const response = await donationAPI.getAll();
      if (response.data.success) {
        // Filter donations for user's campaigns only
        const userDonations = response.data.data.filter(donation => 
          campaigns.some(campaign => campaign.id === donation.campaign_id)
        );
        setDonations(userDonations);
      }
    } catch (err) {
      console.error("Failed to load donations:", err);
    }
  };

  const fetchStats = async () => {
    try {
      const userCampaigns = campaigns;
      const userDonations = donations;

      setStats({
        totalCampaigns: userCampaigns.length,
        activeCampaigns: userCampaigns.filter((c) => c.is_active).length,
        totalDonations: userDonations.length,
        totalAmount: userCampaigns.reduce(
          (sum, c) => sum + parseFloat(c.current_amount || 0),
          0
        ),
      });
    } catch (err) {
      console.error("Failed to load stats:", err);
    }
  };

  useEffect(() => {
    const fetchAllData = async () => {
      try {
        await fetchAllCampaigns();
      } catch (err) {
        setError("Failed to load data");
      } finally {
        setLoading(false);
      }
    };

    fetchAllData();
  }, []);

  useEffect(() => {
    if (campaigns.length > 0) {
      fetchAllDonations();
    }
  }, [campaigns]);

  useEffect(() => {
    if (campaigns.length > 0) {
      fetchStats();
    }
  }, [campaigns, donations]);

  const formatAmount = (amount) => {
    return new Intl.NumberFormat("en-BD", {
      style: "currency",
      currency: "BDT",
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert("Link copied to clipboard!");
  };

  if (loading) return <div className="loading">Loading your dashboard...</div>;
  if (error) return <div className="error-message">{error}</div>;

  return (
    <div className="admin-panel">
      <h2>My Campaign Dashboard</h2>
      <p className="dashboard-welcome">Welcome back, {user?.name}!</p>

      {/* Stats Overview */}
      <div className="admin-stats">
        <div className="stat-card">
          <h3>My Campaigns</h3>
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

      {/* Tab Navigation */}
      <div className="admin-tabs">
        <button
          className={`tab-btn ${activeTab === "campaigns" ? "active" : ""}`}
          onClick={() => setActiveTab("campaigns")}
        >
          My Campaigns
        </button>
        <button
          className={`tab-btn ${activeTab === "donations" ? "active" : ""}`}
          onClick={() => setActiveTab("donations")}
        >
          Received Donations
        </button>
      </div>

      {/* Campaigns Tab */}
      {activeTab === "campaigns" && (
        <div className="campaigns-table">
          <h3>My Campaigns</h3>
          {campaigns.length === 0 ? (
            <div className="empty-state">
              <p>You haven't created any campaigns yet.</p>
              <button 
                className="submit-btn"
                onClick={() => window.location.href = '/create'}
              >
                Create Your First Campaign
              </button>
            </div>
          ) : (
            <table>
              <thead>
                <tr>
                  <th>Campaign</th>
                  <th>Progress</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {campaigns.map((campaign) => {
                  const progressPercentage =
                    (campaign.current_amount / campaign.target_amount) * 100;
                  const isCompleted =
                    campaign.current_amount >= campaign.target_amount;

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
                        <div className="progress-info">
                          <div className="progress-bar small">
                            <div
                              className="progress-fill"
                              style={{
                                width: `${Math.min(100, progressPercentage)}%`,
                                backgroundColor: isCompleted ? "#28a745" : "",
                              }}
                            ></div>
                          </div>
                          <div className="amounts-small">
                            {formatAmount(campaign.current_amount)} /{" "}
                            {formatAmount(campaign.target_amount)}
                          </div>
                          <div className="percentage">
                            {progressPercentage.toFixed(1)}%
                            {isCompleted && (
                              <span className="completed-badge">✓ Completed</span>
                            )}
                          </div>
                        </div>
                      </td>
                      <td>
                        <span
                          className={`status-badge ${
                            campaign.is_active ? "active" : "inactive"
                          }`}
                        >
                          {campaign.is_active ? "Active" : "Inactive"}
                        </span>
                      </td>
                      <td>
                        <small>{formatDate(campaign.created_at)}</small>
                      </td>
                      <td>
                        <div className="action-buttons">
                          <button
                            className="copy-btn"
                            onClick={() =>
                              copyToClipboard(
                                `${window.location.origin}/campaign/${campaign.slug}`
                              )
                            }
                            title="Copy campaign link"
                          >
                            📋 Copy Link
                          </button>
                          <button
                            className="view-btn"
                            onClick={() =>
                              window.open(`/campaign/${campaign.slug}`, "_blank")
                            }
                            title="View campaign"
                          >
                            👁️ View
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>
      )}

      {/* Donations Tab */}
      {activeTab === "donations" && (
        <div className="donations-table">
          <h3>Donations to My Campaigns</h3>
          {donations.length === 0 ? (
            <div className="empty-state">
              <p>No donations received yet.</p>
            </div>
          ) : (
            <table>
              <thead>
                <tr>
                  <th>Donor</th>
                  <th>Campaign</th>
                  <th>Amount</th>
                  <th>Payment Method</th>
                  <th>Date</th>
                  <th>Message</th>
                </tr>
              </thead>
              <tbody>
                {donations.slice(0, 50).map((donation) => (
                  <tr key={donation.id}>
                    <td>
                      <div>
                        <strong>{donation.donor_name}</strong>
                        {donation.donor_email && (
                          <>
                            <br />
                            <small>{donation.donor_email}</small>
                          </>
                        )}
                      </div>
                    </td>
                    <td>
                      <div>
                        <strong>{donation.campaign?.title || "N/A"}</strong>
                      </div>
                    </td>
                    <td>
                      <strong className="donation-amount">
                        {formatAmount(donation.amount)}
                      </strong>
                    </td>
                    <td>
                      <span className="payment-method">
                        {donation.payment_method.toUpperCase()}
                      </span>
                    </td>
                    <td>
                      <small>{formatDate(donation.created_at)}</small>
                    </td>
                    <td>
                      {donation.message ? (
                        <div className="donation-message">
                          "{donation.message}"
                        </div>
                      ) : (
                        <small className="no-message">No message</small>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}
    </div>
  );
};

export default UserDashboard;