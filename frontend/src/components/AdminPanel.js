import React, { useState, useEffect } from "react";
import { campaignAPI, donationAPI } from "../services/api";

const AdminPanel = () => {
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

  const fetchAllCampaigns = async () => {
    try {
      const response = await campaignAPI.getAllWithAuth();
      if (response.data.success) {
        setCampaigns(response.data.data);
      }
    } catch (err) {
      console.error("Failed to load campaigns:", err);
    }
  };

  const fetchAllDonations = async () => {
    try {
      const response = await donationAPI.getAll();
      if (response.data.success) {
        setDonations(response.data.data);
      }
    } catch (err) {
      console.error("Failed to load donations:", err);
    }
  };

  const fetchStats = async () => {
    try {
      const [campaignsRes, donationsRes] = await Promise.all([
        campaignAPI.getAllWithAuth(),
        donationAPI.getAll(),
      ]);

      if (campaignsRes.data.success && donationsRes.data.success) {
        const allCampaigns = campaignsRes.data.data;
        const allDonations = donationsRes.data.data;

        setStats({
          totalCampaigns: allCampaigns.length,
          activeCampaigns: allCampaigns.filter((c) => c.is_active).length,
          totalDonations: allDonations.length,
          totalAmount: allDonations.reduce(
            (sum, d) => sum + parseFloat(d.amount),
            0
          ),
        });
      }
    } catch (err) {
      console.error("Failed to load stats:", err);
    }
  };

  useEffect(() => {
    const fetchAllData = async () => {
      try {
        await Promise.all([
          fetchAllCampaigns(),
          fetchAllDonations(),
          fetchStats(),
        ]);
      } catch (err) {
        setError("Failed to load data");
      } finally {
        setLoading(false);
      }
    };

    fetchAllData();
  }, []);

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

  const promptForTargetAmount = (currentAmount) => {
    const response = window.prompt(
      "Enter a new target amount for this campaign (leave blank to keep current amount):",
      currentAmount ? String(currentAmount) : ""
    );

    if (response === null) {
      return null;
    }

    const trimmed = response.trim();

    if (trimmed === "") {
      return {};
    }

    const parsed = parseFloat(trimmed);

    if (Number.isNaN(parsed) || parsed < 0) {
      alert("Please provide a valid number for the target amount.");
      return false;
    }

    return { target_amount: parsed };
  };

  const toggleCampaignStatus = async (campaign) => {
    let payload = {};

    if (!campaign.is_active) {
      const result = promptForTargetAmount(campaign.target_amount);

      if (result === null) {
        return;
      }

      if (result === false) {
        return;
      }

      payload = result;
    }

    try {
      await campaignAPI.toggleStatus(campaign.id, payload);
      alert('Campaign status updated successfully');
      fetchAllCampaigns();
    } catch (err) {
      alert('Failed to update campaign status');
    }
  };

  if (loading) return <div className="loading">Loading admin data...</div>;
  if (error) return <div className="error-message">{error}</div>;

  return (
    <div className="admin-panel">
      <h2>Admin Dashboard</h2>

      {/* Stats Overview */}
      <div className="admin-stats">
        <div className="stat-card">
          <h3>Total Campaigns</h3>
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
          Campaigns
        </button>
        <button
          className={`tab-btn ${activeTab === "donations" ? "active" : ""}`}
          onClick={() => setActiveTab("donations")}
        >
          Recent Donations
        </button>
      </div>

      {/* Campaigns Tab */}
      {activeTab === "campaigns" && (
        <div className="campaigns-table">
          <h3>All Campaigns</h3>
          <table>
            <thead>
              <tr>
                <th>Campaign</th>
                <th>Creator</th>
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
                      <div>
                        <strong>{campaign.creator_name}</strong>
                        <br />
                        <small>{campaign.creator_email}</small>
                        {campaign.creator_phone && (
                          <>
                            <br />
                            <small>{campaign.creator_phone}</small>
                          </>
                        )}
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
                          className={`toggle-btn ${campaign.is_active ? 'deactivate' : 'activate'}`}
                          onClick={() => toggleCampaignStatus(campaign)}
                          title={campaign.is_active ? 'Deactivate campaign' : 'Activate campaign'}
                        >
                          {campaign.is_active ? '🔒 Deactivate' : '🔓 Activate'}
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
        </div>
      )}

      {/* Donations Tab */}
      {activeTab === "donations" && (
        <div className="donations-table">
          <h3>Recent Donations</h3>
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
        </div>
      )}
    </div>
  );
};

export default AdminPanel;
