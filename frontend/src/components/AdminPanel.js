import React, { useState, useEffect } from "react";
import { campaignAPI, donationAPI } from "../services/api";
import StatsOverview from "./StatsOverview";
import CampaignTable from "./CampaignTable";
import DonationTable from "./DonationTable";

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
      <StatsOverview stats={stats} />

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
        <CampaignTable 
          campaigns={campaigns} 
          title="All Campaigns" 
          onToggleStatus={toggleCampaignStatus}
        />
      )}

      {/* Donations Tab */}
      {activeTab === "donations" && (
        <DonationTable 
          donations={donations} 
          title="Recent Donations" 
        />
      )}
    </div>
  );
};

export default AdminPanel;
