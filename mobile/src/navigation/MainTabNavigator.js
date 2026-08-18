import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import DashboardStack from './DashboardStack';
import BookingsStack from './BookingsStack';
import MuthowifBookingsStack from './MuthowifBookingsStack';
import WalletStack from './WalletStack';
import ChatStack from './ChatStack';
import SupportStack from './SupportStack';
import ProfileStack from './ProfileStack';
import CustomTabBar from './CustomTabBar';
import { useChatInbox } from '../context/ChatInboxContext';
import { useAuth } from '../context/AuthContext';
import { useLocale } from '../utils/locale';

const Tab = createBottomTabNavigator();

export default function MainTabNavigator() {
  const { unreadTotal } = useChatInbox();
  const { isAuthenticated, isVerifiedMuthowif, isPendingMuthowif, isMuthowif } = useAuth();
  const locale = useLocale();
  const isEn = locale === 'en';
  const chatBadge = unreadTotal > 0 ? (unreadTotal > 99 ? '99+' : unreadTotal) : undefined;
  const showMuthowifTabs = isAuthenticated && isVerifiedMuthowif;

  return (
    <Tab.Navigator
      tabBar={(props) => <CustomTabBar {...props} />}
      screenOptions={{
        headerShown: false,
        tabBarHideOnKeyboard: true,
        sceneStyle: {
          backgroundColor: '#F8FAFC',
        },
      }}
    >
      <Tab.Screen name="HomeTab" component={DashboardStack} options={{ tabBarLabel: isEn ? 'Explore' : 'Telusuri' }} />

      {showMuthowifTabs ? (
        <>
          <Tab.Screen
            name="MuthowifBookingsTab"
            component={MuthowifBookingsStack}
            options={{ tabBarLabel: isEn ? 'Requests' : 'Permintaan' }}
          />
          <Tab.Screen name="WalletTab" component={WalletStack} options={{ tabBarLabel: isEn ? 'Wallet' : 'Dompet' }} />
        </>
      ) : (
        <Tab.Screen name="BookingsTab" component={BookingsStack} options={{ tabBarLabel: isEn ? 'Orders' : 'Pesanan' }} />
      )}

      {!isPendingMuthowif ? (
        <Tab.Screen
          name="ChatTab"
          component={ChatStack}
          options={{ tabBarLabel: 'Chat', tabBarBadge: chatBadge }}
        />
      ) : null}

      {isAuthenticated && !isMuthowif ? (
        <Tab.Screen name="SupportTab" component={SupportStack} options={{ tabBarLabel: isEn ? 'Support' : 'Bantuan' }} />
      ) : null}

      <Tab.Screen name="ProfileTab" component={ProfileStack} options={{ tabBarLabel: isEn ? 'Profile' : 'Profil' }} />
    </Tab.Navigator>
  );
}
