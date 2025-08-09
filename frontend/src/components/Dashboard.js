import React from 'react';
import { useAuth } from '../contexts/AuthContext';
import AdminPanel from './AdminPanel';
import UserDashboard from './UserDashboard';

const Dashboard = () => {
  const { user, isAdmin } = useAuth();

  if (!user) {
    return <div className="loading">Loading...</div>;
  }

  return (
    <div className="dashboard">
      {isAdmin() ? <AdminPanel /> : <UserDashboard />}
    </div>
  );
};

export default Dashboard;