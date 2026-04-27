import React, { useState, useEffect, useCallback } from 'react';
import { 
  StyleSheet, 
  View, 
  ActivityIndicator, 
  TouchableOpacity, 
  Text,
  Alert,
  StatusBar
} from 'react-native';
import { WebView } from 'react-native-webview';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { apiClient } from '../api/client';

export default function PaymentWebScreen({ route, navigation, user }) {
  const { payment_url, booking_id } = route.params || {};
  const [url, setUrl] = useState(payment_url);
  const [loading, setLoading] = useState(!payment_url);
  const [webLoading, setWebLoading] = useState(true);

  const fetchUrl = useCallback(async () => {
    try {
      const data = await apiClient.getPaymentUrl(user.token, booking_id);
      const targetUrl = data.redirect_url || data.paymentUrl;
      if (targetUrl) {
        setUrl(targetUrl);
      } else {
        throw new Error('Gagal mendapatkan URL pembayaran');
      }
    } catch (error) {
      Alert.alert('Error', error.message);
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  }, [user.token, booking_id, navigation]);

  useEffect(() => {
    if (!url && booking_id) {
      fetchUrl();
    }
  }, [url, booking_id, fetchUrl]);

  const handleNavigationStateChange = (navState) => {
    // Deteksi redirect setelah pembayaran (DOKU / host checkout: finish/callback)
    // Biasanya URL mengandung "finish", "unfinish", atau "error"
    if (navState.url.includes('finish') || navState.url.includes('callback')) {
      Alert.alert('Status Pembayaran', 'Terima kasih! Kami akan memverifikasi pembayaran Anda.', [
        { text: 'Selesai', onPress: () => navigation.navigate('BookingList') }
      ]);
    }
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <SafeAreaView edges={['top']}>
          <View style={styles.headerContent}>
            <TouchableOpacity onPress={() => navigation.goBack()} style={styles.closeBtn}>
              <Ionicons name="close" size={24} color="#0F172A" />
            </TouchableOpacity>
            <View style={styles.titleWrapper}>
              <Text style={styles.headerTitle}>Pembayaran Aman</Text>
              <View style={styles.secureRow}>
                <Ionicons name="lock-closed" size={10} color="#64748B" />
                <Text style={styles.headerSub}>MIDTRANS SECURE GATEWAY</Text>
              </View>
            </View>
            <TouchableOpacity style={styles.closeBtn} onPress={fetchUrl}>
              <Ionicons name="refresh" size={20} color="#0F172A" />
            </TouchableOpacity>
          </View>
        </SafeAreaView>
      </View>

      <View style={{ flex: 1 }}>
        {loading ? (
          <View style={styles.center}>
            <ActivityIndicator size="large" color="#3B82F6" />
            <Text style={styles.loadingText}>Menyiapkan gerbang pembayaran...</Text>
          </View>
        ) : (
          <>
            <WebView 
              source={{ uri: url }} 
              onNavigationStateChange={handleNavigationStateChange}
              onLoadStart={() => setWebLoading(true)}
              onLoadEnd={() => setWebLoading(false)}
              style={{ flex: 1 }}
            />
            {webLoading && (
              <View style={styles.webLoader}>
                <ActivityIndicator size="small" color="#3B82F6" />
              </View>
            )}
          </>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#FFF' },
  header: { 
    backgroundColor: '#FFFFFF',
    paddingBottom: 15, 
    borderBottomLeftRadius: 24, 
    borderBottomRightRadius: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.03,
    shadowRadius: 10,
    elevation: 5
  },
  headerContent: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingTop: 10 },
  closeBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#F8FAFC', justifyContent: 'center', alignItems: 'center' },
  titleWrapper: { alignItems: 'center' },
  headerTitle: { color: '#0F172A', fontSize: 16, fontWeight: '900' },
  secureRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 2 },
  headerSub: { color: '#94A3B8', fontSize: 9, fontWeight: '800', letterSpacing: 0.5 },
  
  center: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 40 },
  loadingText: { marginTop: 16, fontSize: 14, color: '#64748B', fontWeight: '600', textAlign: 'center' },
  
  webLoader: { position: 'absolute', top: 0, left: 0, right: 0, height: 3, backgroundColor: '#F1F5F9', justifyContent: 'center' }
});
