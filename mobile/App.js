import React, { useState, useEffect } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import AsyncStorage from '@react-native-async-storage/async-storage';

import OpeningScreen from './src/screens/OpeningScreen';
import LoginScreen from './src/screens/LoginScreen';
import RegisterScreen from './src/screens/RegisterScreen';
import ForgotPasswordScreen from './src/screens/ForgotPasswordScreen';
import DashboardScreen from './src/screens/DashboardScreen';
import MuthowifDashboardScreen from './src/screens/MuthowifDashboardScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import ServicesScreen from './src/screens/ServicesScreen';
import EditServiceScreen from './src/screens/EditServiceScreen';
import TimeOffScreen from './src/screens/TimeOffScreen';
import MuthowifBookingsScreen from './src/screens/MuthowifBookingsScreen';
import BookingDetailScreen from './src/screens/BookingDetailScreen';
import WalletScreen from './src/screens/WalletScreen';
import ChatScreen from './src/screens/ChatScreen';
import ChatListScreen from './src/screens/ChatListScreen';

export default function App() {
  const [currentScreen, setCurrentScreen] = useState('Opening');
  const [screenParams, setScreenParams] = useState({});
  const [user, setUser] = useState(null);

  const navigate = (screen, params = {}) => {
    setScreenParams(params);
    setCurrentScreen(screen);
  };
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkLoginStatus();
  }, []);

  const checkLoginStatus = async () => {
    try {
      const userData = await AsyncStorage.getItem('user');
      if (userData) {
        setUser(JSON.parse(userData));
        setCurrentScreen('Dashboard');
      }
    } catch (e) {
      console.error('Failed to load login status');
    } finally {
      setLoading(false);
    }
  };

  const login = async (userData) => {
    await AsyncStorage.setItem('user', JSON.stringify(userData));
    setUser(userData);
    setCurrentScreen('Dashboard');
  };

  const logout = async () => {
    await AsyncStorage.removeItem('user');
    setUser(null);
    setCurrentScreen('Login');
  };

  if (loading) return null;

  return (
    <SafeAreaProvider>
      {currentScreen === 'Opening' && (
        <OpeningScreen navigation={{ navigate: setCurrentScreen }} />
      )}
      
      {currentScreen === 'Login' && (
        <LoginScreen navigation={{ navigate: setCurrentScreen }} onLoginSuccess={login} />
      )}

      {currentScreen === 'Register' && (
        <RegisterScreen navigation={{ navigate: setCurrentScreen }} />
      )}

      {currentScreen === 'ForgotPassword' && (
        <ForgotPasswordScreen navigation={{ navigate: setCurrentScreen }} />
      )}

      {currentScreen === 'Dashboard' && (
        user?.user?.role === 'muthowif' ? (
          <MuthowifDashboardScreen user={user} onLogout={logout} navigation={{ navigate }} />
        ) : (
          <DashboardScreen user={user} onLogout={logout} navigation={{ navigate }} />
        )
      )}

      {currentScreen === 'Profile' && (
        <ProfileScreen user={user} onLogout={logout} navigation={{ navigate }} />
      )}

      {currentScreen === 'Services' && (
        <ServicesScreen user={user} navigation={{ navigate }} />
      )}

      {currentScreen === 'EditService' && (
        <EditServiceScreen route={{ params: screenParams }} user={user} navigation={{ navigate }} />
      )}

      {currentScreen === 'TimeOff' && (
        <TimeOffScreen user={user} navigation={{ navigate }} />
      )}

      {currentScreen === 'MuthowifBookings' && (
        <MuthowifBookingsScreen user={user} navigation={{ navigate }} />
      )}

      {currentScreen === 'BookingDetail' && (
        <BookingDetailScreen route={{ params: screenParams }} user={user} navigation={{ navigate, goBack: () => navigate('MuthowifBookings') }} />
      )}

      {currentScreen === 'Wallet' && (
        <WalletScreen user={user} navigation={{ navigate, goBack: () => navigate('Dashboard') }} />
      )}

      {currentScreen === 'Chat' && (
        <ChatScreen 
          user={user} 
          route={{ params: screenParams }} 
          navigation={{ goBack: () => navigate(screenParams?.from ?? 'ChatList') }} 
        />
      )}

      {currentScreen === 'ChatList' && (
        <ChatListScreen 
          user={user} 
          navigation={{ navigate, goBack: () => navigate('Dashboard') }} 
        />
      )}
      
      <StatusBar style="dark" />
    </SafeAreaProvider>
  );
}
