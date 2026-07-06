import React from 'react';
import { Platform } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import DashboardStack from './DashboardStack';
import BookingsStack from './BookingsStack';
import MuthowifBookingsStack from './MuthowifBookingsStack';
import WalletStack from './WalletStack';
import ChatStack from './ChatStack';
import SupportStack from './SupportStack';
import ProfileStack from './ProfileStack';
import { useChatInbox } from '../context/ChatInboxContext';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

const Tab = createBottomTabNavigator();

function tabIcon(routeName, focused) {
  const icons = {
    HomeTab: focused ? 'search' : 'search-outline',
    BookingsTab: focused ? 'receipt' : 'receipt-outline',
    MuthowifBookingsTab: focused ? 'clipboard' : 'clipboard-outline',
    WalletTab: focused ? 'wallet' : 'wallet-outline',
    ChatTab: focused ? 'chatbubbles' : 'chatbubbles-outline',
    SupportTab: focused ? 'help-buoy' : 'help-buoy-outline',
    ProfileTab: focused ? 'person' : 'person-outline',
  };
  return icons[routeName] || 'ellipse-outline';
}

export default function MainTabNavigator() {
  const { unreadTotal } = useChatInbox();
  const { isAuthenticated, isVerifiedMuthowif, isPendingMuthowif, isMuthowif } = useAuth();
  const chatBadge = unreadTotal > 0 ? (unreadTotal > 99 ? '99+' : unreadTotal) : undefined;
  const showMuthowifTabs = isAuthenticated && isVerifiedMuthowif;

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor: colors.baytgo,
        tabBarInactiveTintColor: colors.slate400,
        tabBarStyle: {
          backgroundColor: colors.white,
          borderTopColor: colors.slate100,
          height: Platform.OS === 'ios' ? 88 : 64,
          paddingBottom: Platform.OS === 'ios' ? 28 : 10,
          paddingTop: 8,
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '700',
        },
        tabBarIcon: ({ color, size, focused }) => (
          <Ionicons name={tabIcon(route.name, focused)} size={size} color={color} />
        ),
      })}
    >
      <Tab.Screen name="HomeTab" component={DashboardStack} options={{ tabBarLabel: 'Telusuri' }} />

      {showMuthowifTabs ? (
        <>
          <Tab.Screen
            name="MuthowifBookingsTab"
            component={MuthowifBookingsStack}
            options={{ tabBarLabel: 'Permintaan' }}
          />
          <Tab.Screen name="WalletTab" component={WalletStack} options={{ tabBarLabel: 'Dompet' }} />
        </>
      ) : (
        <Tab.Screen name="BookingsTab" component={BookingsStack} options={{ tabBarLabel: 'Pesanan' }} />
      )}

      {!isPendingMuthowif ? (
        <Tab.Screen
          name="ChatTab"
          component={ChatStack}
          options={{ tabBarLabel: 'Chat', tabBarBadge: chatBadge }}
        />
      ) : null}

      {isAuthenticated && !isMuthowif ? (
        <Tab.Screen name="SupportTab" component={SupportStack} options={{ tabBarLabel: 'Bantuan' }} />
      ) : null}

      <Tab.Screen name="ProfileTab" component={ProfileStack} options={{ tabBarLabel: 'Profil' }} />
    </Tab.Navigator>
  );
}
