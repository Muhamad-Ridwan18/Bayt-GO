import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import MuthowifBookingsListScreen from '../screens/MuthowifBookingsListScreen';
import MuthowifBookingDetailScreen from '../screens/MuthowifBookingDetailScreen';

const Stack = createNativeStackNavigator();

const stackScreenOptions = {
  headerShown: false,
  animation: 'slide_from_right',
  gestureEnabled: true,
  fullScreenGestureEnabled: true,
  gestureDirection: 'horizontal',
};

export default function MuthowifBookingsStack() {
  return (
    <Stack.Navigator screenOptions={stackScreenOptions}>
      <Stack.Screen name="MuthowifBookingsList" component={MuthowifBookingsListScreen} />
      <Stack.Screen name="MuthowifBookingDetail" component={MuthowifBookingDetailScreen} />
    </Stack.Navigator>
  );
}
