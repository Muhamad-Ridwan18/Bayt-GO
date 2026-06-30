import React, { useEffect, useState } from 'react';
import { ActivityIndicator, View, StyleSheet } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import AsyncStorage from '@react-native-async-storage/async-storage';
import OnboardingScreen from '../screens/OnboardingScreen';
import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';
import MainTabNavigator from './MainTabNavigator';
import { ONBOARDING_KEY } from '../constants/onboarding';
import { useAuth } from '../context/AuthContext';
import { navigationRef } from './rootNavigation';
import { useNotificationNavigation } from '../notifications/useNotificationNavigation';
import { colors } from '../theme/colors';

const Stack = createNativeStackNavigator();

const stackScreenOptions = {
  headerShown: false,
  animation: 'slide_from_right',
  gestureEnabled: true,
  fullScreenGestureEnabled: true,
  gestureDirection: 'horizontal',
};

function RootNavigator() {
  const { booting } = useAuth();
  const [onboardingDone, setOnboardingDone] = useState(false);
  const [onboardingReady, setOnboardingReady] = useState(false);

  useEffect(() => {
    AsyncStorage.getItem(ONBOARDING_KEY)
      .then((value) => setOnboardingDone(value === '1'))
      .finally(() => setOnboardingReady(true));
  }, []);

  if (booting || !onboardingReady) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator size="large" color={colors.baytgo} />
      </View>
    );
  }

  const initialRoute = onboardingDone ? 'Main' : 'Onboarding';

  return (
    <Stack.Navigator initialRouteName={initialRoute} screenOptions={stackScreenOptions}>
      <Stack.Screen
        name="Onboarding"
        component={OnboardingScreen}
        options={{ animation: 'fade', gestureEnabled: false }}
      />
      <Stack.Screen name="Main" component={MainTabNavigator} options={{ animation: 'fade' }} />
      <Stack.Screen
        name="Login"
        component={LoginScreen}
        options={{ animation: 'slide_from_bottom', presentation: 'modal' }}
      />
      <Stack.Screen
        name="Register"
        component={RegisterScreen}
        options={{ animation: 'slide_from_bottom', presentation: 'modal' }}
      />
    </Stack.Navigator>
  );
}

export default function AppNavigator() {
  return (
    <NavigationContainer ref={navigationRef}>
      <NavigationRoot />
    </NavigationContainer>
  );
}

function NavigationRoot() {
  useNotificationNavigation();
  return <RootNavigator />;
}

const styles = StyleSheet.create({
  loading: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.canvas,
  },
});
