import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import AuthGateScreen from '../screens/AuthGateScreen';
import SupportListScreen from '../screens/SupportListScreen';
import SupportCreateScreen from '../screens/SupportCreateScreen';
import SupportDetailScreen from '../screens/SupportDetailScreen';

const Stack = createNativeStackNavigator();

const stackScreenOptions = {
  headerShown: false,
  animation: 'slide_from_right',
  gestureEnabled: true,
  fullScreenGestureEnabled: true,
  gestureDirection: 'horizontal',
};

function withSupportAuth(Screen) {
  return function SupportAuthGate(props) {
    const { isAuthenticated } = useAuth();
    if (!isAuthenticated) {
      return (
        <AuthGateScreen
          {...props}
          title="Bantuan"
          message="Masuk untuk melihat dan mengelola tiket bantuan Anda."
        />
      );
    }
    return <Screen {...props} />;
  };
}

export const SupportListGate = withSupportAuth(SupportListScreen);
export const SupportCreateGate = withSupportAuth(SupportCreateScreen);
export const SupportDetailGate = withSupportAuth(SupportDetailScreen);

export default function SupportStack() {
  return (
    <Stack.Navigator screenOptions={stackScreenOptions}>
      <Stack.Screen name="SupportList" component={SupportListGate} />
      <Stack.Screen name="SupportCreate" component={SupportCreateGate} />
      <Stack.Screen name="SupportDetail" component={SupportDetailGate} />
    </Stack.Navigator>
  );
}
