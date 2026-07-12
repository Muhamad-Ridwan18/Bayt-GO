import AppShell from './src/ui/AppShell';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider } from './src/context/AuthContext';
import { BrandProvider } from './src/context/BrandContext';
import { ChatInboxProvider } from './src/context/ChatInboxContext';
import AppNavigator from './src/navigation/AppNavigator';
import AppDialogHost from './src/components/AppDialog';
import ToastHost from './src/ui/ToastHost';
import { installCustomAlert } from './src/utils/alert';

installCustomAlert();

export default function App() {
  return (
    <AppShell>
      <SafeAreaProvider>
        <BrandProvider>
          <AuthProvider>
            <ChatInboxProvider>
              <AppNavigator />
              <AppDialogHost />
              <ToastHost />
            </ChatInboxProvider>
          </AuthProvider>
        </BrandProvider>
      </SafeAreaProvider>
    </AppShell>
  );
}
