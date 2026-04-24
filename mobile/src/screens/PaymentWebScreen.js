import React, { useState } from 'react';
import { 
  StyleSheet, 
  View, 
  ActivityIndicator, 
  TouchableOpacity, 
  Text,
  Alert
} from 'react-native';
import { WebView } from 'react-native-webview';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';

export default function PaymentWebScreen({ route, navigation }) {
  const { url, booking_id } = route.params || {};
  const [loading, setLoading] = useState(true);

  const onNavigationStateChange = (navState) => {
    // Detect Midtrans success/finish/error redirects
    // Usually Midtrans redirects back to our site or finishes with specific strings in URL
    if (navState.url.includes('finish') || navState.url.includes('success') || navState.url.includes('error')) {
      Alert.alert(
        'Pembayaran',
        'Proses pembayaran telah selesai. Silakan cek status pesanan Anda.',
        [{ text: 'OK', onPress: () => navigation.navigate('Dashboard') }]
      );
    }
  };

  return (
    <View style={styles.container}>
      <LinearGradient 
        colors={['#0F172A', '#1E3A8A']} 
        style={styles.headerGradient}
      >
        <SafeAreaView edges={['top']}>
          <View style={styles.headerRow}>
            <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtnWrapper}>
              <Ionicons name="close" size={24} color="#FFF" />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>Pembayaran Midtrans</Text>
          </View>
        </SafeAreaView>
      </LinearGradient>
      
      <View style={styles.webviewContainer}>
        <WebView 
          source={{ uri: url }}
          onLoadStart={() => setLoading(true)}
          onLoadEnd={() => setLoading(false)}
          onNavigationStateChange={onNavigationStateChange}
          style={styles.webview}
        />
        {loading && (
          <View style={styles.loader}>
            <ActivityIndicator size="large" color="#0984e3" />
          </View>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFF' },
  headerGradient: {
    paddingBottom: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerRow: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    paddingHorizontal: 20, 
    paddingTop: 10,
    gap: 16
  },
  backBtnWrapper: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: 'rgba(255, 255, 255, 0.15)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#FFF', letterSpacing: -0.5 },
  webviewContainer: { flex: 1 },
  webview: { flex: 1 },
  loader: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(255, 255, 255, 0.8)',
    justifyContent: 'center',
    alignItems: 'center'
  }
});
