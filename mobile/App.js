import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider } from './src/context/AuthContext';
import { BrandProvider } from './src/context/BrandContext';
import { ChatInboxProvider } from './src/context/ChatInboxContext';
import AppNavigator from './src/navigation/AppNavigator';
import AppDialogHost from './src/components/AppDialog';
import { installCustomAlert } from './src/utils/alert';

installCustomAlert();

export default function App() {
  return (
    <SafeAreaProvider>
      <BrandProvider>
        <AuthProvider>
          <ChatInboxProvider>
            <AppNavigator />
            <AppDialogHost />
          </ChatInboxProvider>
        </AuthProvider>
      </BrandProvider>
    </SafeAreaProvider>
  );
}
