import React from 'react';
import { Platform } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import HomeStack from './HomeStack';
import BookingsStack from './BookingsStack';
import ChatStack from './ChatStack';
import ProfileStack from './ProfileStack';
import { useChatInbox } from '../context/ChatInboxContext';
import { colors } from '../theme/colors';

const Tab = createBottomTabNavigator();

export default function MainTabNavigator() {
  const { unreadTotal } = useChatInbox();
  const chatBadge = unreadTotal > 0 ? (unreadTotal > 99 ? '99+' : unreadTotal) : undefined;

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
        tabBarIcon: ({ color, size, focused }) => {
          const icons = {
            HomeTab: focused ? 'home' : 'home-outline',
            BookingsTab: focused ? 'receipt' : 'receipt-outline',
            ChatTab: focused ? 'chatbubbles' : 'chatbubbles-outline',
            ProfileTab: focused ? 'person' : 'person-outline',
          };
          return <Ionicons name={icons[route.name]} size={size} color={color} />;
        },
      })}
    >
      <Tab.Screen name="HomeTab" component={HomeStack} options={{ tabBarLabel: 'Beranda' }} />
      <Tab.Screen name="BookingsTab" component={BookingsStack} options={{ tabBarLabel: 'Pesanan' }} />
      <Tab.Screen
        name="ChatTab"
        component={ChatStack}
        options={{ tabBarLabel: 'Chat', tabBarBadge: chatBadge }}
      />
      <Tab.Screen name="ProfileTab" component={ProfileStack} options={{ tabBarLabel: 'Profil' }} />
    </Tab.Navigator>
  );
}
