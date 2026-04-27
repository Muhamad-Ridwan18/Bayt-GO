import React, { useState, useEffect, useRef } from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { Animated, Dimensions, Easing, StyleSheet, View } from 'react-native';
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
import BookingListScreen from './src/screens/BookingListScreen';
import WalletScreen from './src/screens/WalletScreen';
import ChatScreen from './src/screens/ChatScreen';
import ChatListScreen from './src/screens/ChatListScreen';
import SearchMuthowifScreen from './src/screens/SearchMuthowifScreen';
import MuthowifDetailScreen from './src/screens/MuthowifDetailScreen';
import CheckoutScreen from './src/screens/CheckoutScreen';
import PaymentScreen from './src/screens/PaymentScreen';

const { width } = Dimensions.get('window');

/**
 * StackScreen: Wrapper animasi untuk tiap screen di dalam stack.
 * - Saat mount (navigate forward): slide dari kanan.
 * - Saat isExiting = true (tombol back ditekan): slide keluar ke kanan.
 */
const StackScreen = React.memo(({ screen, isRoot, isExiting, onRemove }) => {
  const slideAnim = useRef(new Animated.Value(isRoot ? 0 : width)).current;

  // Animasi masuk
  useEffect(() => {
    if (!isRoot) {
      Animated.timing(slideAnim, {
        toValue: 0,
        duration: 350,
        easing: Easing.out(Easing.poly(4)),
        useNativeDriver: true,
      }).start();
    }
  }, [isRoot]);

  // Animasi keluar (jika tombol back ditekan)
  useEffect(() => {
    if (isExiting) {
      Animated.timing(slideAnim, {
        toValue: width,
        duration: 300,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: true,
      }).start(() => {
        onRemove(screen.key);
      });
    }
  }, [isExiting]);

  return (
    <Animated.View
      style={[
        StyleSheet.absoluteFill,
        { transform: [{ translateX: slideAnim }], backgroundColor: 'transparent', elevation: isRoot ? 0 : 5, zIndex: isRoot ? 0 : 5 },
      ]}
    >
      {screen.render()}
    </Animated.View>
  );
});

export default function App() {
  // Simpan stack sebagai array of objects
  const [screenStack, setScreenStack] = useState([{ key: 'init', name: 'Opening', params: {} }]);
  const [exitingKey, setExitingKey] = useState(null);
  
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkLoginStatus();
  }, []);

  const checkLoginStatus = async () => {
    try {
      const userData = await AsyncStorage.getItem('user');
      if (userData) {
        const parsedUser = JSON.parse(userData);
        setUser(parsedUser);
        setScreenStack([{ key: 'dash', name: 'Dashboard', params: {} }]);
      }
    } catch (e) {
      console.error('Failed to load login status');
    } finally {
      setLoading(false);
    }
  };

  const navigate = (name, params = {}) => {
    setScreenStack(prev => [...prev, { key: Date.now().toString(), name, params }]);
  };

  const goBack = (options = {}) => {
    setScreenStack(prev => {
      if (prev.length <= 1) return prev;
      const topScreen = prev[prev.length - 1];
      
      if (options.isSwipe) {
        // SwipeableScreen sudah handle animasi keluarnya, langsung hapus
        return prev.slice(0, -1);
      } else {
        // Tombol back ditekan, trigger animasi keluar
        setExitingKey(topScreen.key);
        return prev;
      }
    });
  };

  const handleRemove = (key) => {
    setScreenStack(prev => prev.filter(s => s.key !== key));
    setExitingKey(null);
  };

  const login = async (userData) => {
    await AsyncStorage.setItem('user', JSON.stringify(userData));
    setUser(userData);
    setScreenStack([{ key: 'dash', name: 'Dashboard', params: {} }]);
  };

  const logout = async () => {
    await AsyncStorage.removeItem('user');
    setUser(null);
    setScreenStack([{ key: 'login', name: 'Login', params: {} }]);
  };

  if (loading) return null;

  const renderScreenContent = (name, route, nav) => {
    switch (name) {
      case 'Opening': return <OpeningScreen navigation={nav} />;
      case 'Login': return <LoginScreen navigation={nav} onLoginSuccess={login} />;
      case 'Register': return <RegisterScreen navigation={nav} />;
      case 'ForgotPassword': return <ForgotPasswordScreen navigation={nav} />;
      case 'Dashboard': 
        return user?.user?.role === 'muthowif' 
          ? <MuthowifDashboardScreen user={user} onLogout={logout} navigation={nav} />
          : <DashboardScreen user={user} onLogout={logout} navigation={nav} />;
      case 'Profile': return <ProfileScreen user={user} onLogout={logout} navigation={nav} />;
      case 'Services': return <ServicesScreen user={user} navigation={nav} />;
      case 'EditService': return <EditServiceScreen route={route} user={user} navigation={nav} />;
      case 'TimeOff': return <TimeOffScreen user={user} navigation={nav} />;
      case 'MuthowifBookings': return <MuthowifBookingsScreen user={user} navigation={nav} />;
      case 'BookingDetail': return <BookingDetailScreen route={route} user={user} navigation={nav} />;
      case 'Wallet': return <WalletScreen user={user} navigation={nav} />;
      case 'Chat': return <ChatScreen user={user} route={route} navigation={nav} />;
      case 'ChatList': return <ChatListScreen user={user} navigation={nav} />;
      case 'SearchMuthowif': return <SearchMuthowifScreen user={user} navigation={nav} />;
      case 'MuthowifDetail': return <MuthowifDetailScreen route={route} user={user} navigation={nav} />;
      case 'Checkout': return <CheckoutScreen route={route} user={user} navigation={nav} />;
      case 'Payment': return <PaymentScreen route={route} user={user} navigation={nav} />;
      case 'BookingList': return <BookingListScreen user={user} navigation={nav} />;
      default: return null;
    }
  };

  return (
    <SafeAreaProvider style={styles.root}>
      {screenStack.map((screen, index) => {
        const isRoot = index === 0;
        const isExiting = screen.key === exitingKey;
        const nav = { navigate, goBack };
        const route = { params: screen.params };
        
        return (
          <StackScreen 
            key={screen.key}
            screen={{ ...screen, render: () => renderScreenContent(screen.name, route, nav) }}
            isRoot={isRoot}
            isExiting={isExiting}
            onRemove={handleRemove}
          />
        );
      })}
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
});
