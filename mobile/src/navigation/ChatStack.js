import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import AuthGateScreen from '../screens/AuthGateScreen';
import ChatListScreen from '../screens/ChatListScreen';
import ChatRoomScreen from '../screens/ChatRoomScreen';

const Stack = createNativeStackNavigator();

const stackScreenOptions = {
  headerShown: false,
  animation: 'slide_from_right',
  gestureEnabled: true,
  fullScreenGestureEnabled: true,
  gestureDirection: 'horizontal',
};

function ChatGate(props) {
  const { isAuthenticated } = useAuth();
  if (!isAuthenticated) {
    return (
      <AuthGateScreen
        {...props}
        title="Chat"
        message="Masuk untuk membaca dan mengirim pesan terkait booking Anda."
      />
    );
  }
  return <ChatListScreen {...props} />;
}

export default function ChatStack() {
  return (
    <Stack.Navigator screenOptions={stackScreenOptions}>
      <Stack.Screen name="ChatList" component={ChatGate} />
      <Stack.Screen name="ChatRoom" component={ChatRoomScreen} />
    </Stack.Navigator>
  );
}
