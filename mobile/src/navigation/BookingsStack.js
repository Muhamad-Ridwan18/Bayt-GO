import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import AuthGateScreen from '../screens/AuthGateScreen';
import BookingsListScreen from '../screens/BookingsListScreen';
import BookingDetailScreen from '../screens/BookingDetailScreen';
import BookingPaymentScreen from '../screens/BookingPaymentScreen';
import BookingRatingScreen from '../screens/BookingRatingScreen';
import BookingRefundScreen from '../screens/BookingRefundScreen';
import BookingRescheduleScreen from '../screens/BookingRescheduleScreen';
import BookingInvoiceScreen from '../screens/BookingInvoiceScreen';
import BookingEmergencyReportScreen from '../screens/BookingEmergencyReportScreen';

const Stack = createNativeStackNavigator();

const stackScreenOptions = {
  headerShown: false,
  animation: 'slide_from_right',
  gestureEnabled: true,
  fullScreenGestureEnabled: true,
  gestureDirection: 'horizontal',
};

function BookingsGate(props) {
  const { isAuthenticated } = useAuth();
  if (!isAuthenticated) {
    return (
      <AuthGateScreen
        {...props}
        title="Pesanan"
        message="Masuk untuk melihat dan mengelola pesanan muthowif Anda."
      />
    );
  }
  return <BookingsListScreen {...props} />;
}

export default function BookingsStack() {
  return (
    <Stack.Navigator screenOptions={stackScreenOptions}>
      <Stack.Screen name="BookingsList" component={BookingsGate} />
      <Stack.Screen name="BookingDetail" component={BookingDetailScreen} />
      <Stack.Screen name="BookingPayment" component={BookingPaymentScreen} />
      <Stack.Screen name="BookingRating" component={BookingRatingScreen} />
      <Stack.Screen name="BookingRefund" component={BookingRefundScreen} />
      <Stack.Screen name="BookingReschedule" component={BookingRescheduleScreen} />
      <Stack.Screen name="BookingInvoice" component={BookingInvoiceScreen} />
      <Stack.Screen name="BookingEmergencyReport" component={BookingEmergencyReportScreen} />
    </Stack.Navigator>
  );
}
