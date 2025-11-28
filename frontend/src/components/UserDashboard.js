import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { campaignAPI, donationAPI } from "../services/api";
import { useAuth } from "../contexts/AuthContext";
import StatsOverview from "./StatsOverview";
import CampaignTable from "./CampaignTable";
import DonationTable from "./DonationTable";

const UserDashboard = () => {
  const navigate = useNavigate();
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
        activeCampaigns: userCampaigns.filter((c) => c.status === 'approved').length,
        totalDonations: userDonations.length,
        totalAmount: userCampaigns.reduce(
          (sum, c) => sum + parseFloat(c.current_amount || 0),
          0
        ),
        campaignsLabel: "My Campaigns"
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

  const deleteCampaign = async (campaign) => {
    try {
      await campaignAPI.delete(campaign.id);
      alert('Campaign deleted successfully');
      fetchAllCampaigns();
    } catch (err) {
      alert('Failed to delete campaign');
      console.error(err);
    }
  };

  if (loading) return <div className="loading">Loading your dashboard...</div>;
  if (error) return <div className="error-message">{error}</div>;

  return (
    <div className="admin-panel">
      <h2>My Campaign Dashboard</h2>
      <p className="dashboard-welcome">Welcome back, {user?.name}!</p>

      {/* Stats Overview */}
      <StatsOverview stats={stats} />

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
        <CampaignTable 
          campaigns={campaigns} 
          title="My Campaigns" 
          onDelete={deleteCampaign}
          isAdmin={false}
          emptyStateAction={
            <button 
              className="submit-btn"
              onClick={() => navigate('/create')}
            >
              Create Your First Campaign
            </button>
          }
        />
      )}

      {/* Donations Tab */}
      {activeTab === "donations" && (
        <DonationTable 
          donations={donations} 
          title="Donations to My Campaigns" 
        />
      )}
    </div>
  );
};

export default UserDashboard;