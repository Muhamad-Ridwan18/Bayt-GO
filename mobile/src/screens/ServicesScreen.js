import React, { useState, useEffect } from 'react';
import { 
  StyleSheet, 
  Text, 
  View, 
  TouchableOpacity, 
  ScrollView, 
  ActivityIndicator,
  RefreshControl,
  Dimensions
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { apiClient } from '../api/client';
import SwipeableScreen from '../components/SwipeableScreen';

const { width } = Dimensions.get('window');

export default function ServicesScreen({ user, navigation }) {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [services, setServices] = useState([]);

  const fetchServices = async () => {
    try {
      const data = await apiClient.getMuthowifServices(user.token);
      setServices(data.services || []);
    } catch (error) {
      console.error('Fetch Services Error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchServices();
  }, []);

  const formatCurrency = (amount) => {
    return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  };

  return (
    <SwipeableScreen onSwipeBack={() => navigation.goBack()}>
    <SafeAreaView style={styles.container}>
      <StatusBar style="dark" />
      
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.navigate('Dashboard')}>
          <Text style={styles.backBtn}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Layanan Saya</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={fetchServices} color="#0984e3" />}
      >
        <Text style={styles.subTitle}>Kelola paket bimbingan yang Anda tawarkan kepada jamaah.</Text>

        {loading ? (
          <ActivityIndicator size="large" color="#0984e3" style={{marginTop: 50}} />
        ) : services.length > 0 ? (
          services.map((item) => (
            <TouchableOpacity 
              key={item.id} 
              style={styles.serviceCard}
              onPress={() => navigation.navigate('EditService', { service: item })}
            >
              <View style={styles.cardHeader}>
                <View style={[styles.typeBadge, { backgroundColor: item.type === 'private' ? '#F0FDF4' : '#E0F2FE' }]}>
                  <Text style={[styles.typeText, { color: item.type === 'private' ? '#166534' : '#0369A1' }]}>
                    {item.type === 'private' ? '👤 PRIVATE' : '👥 GROUP'}
                  </Text>
                </View>
                <View style={styles.statusRow}>
                  <View style={styles.statusDot} />
                  <Text style={styles.statusText}>{item.status}</Text>
                </View>
              </View>

              <Text style={styles.serviceName}>{item.name}</Text>
              <Text style={styles.serviceDesc}>{item.description}</Text>

              <View style={styles.divider} />

              <View style={styles.cardFooter}>
                <View>
                  <Text style={styles.footerLabel}>HARGA HARIAN</Text>
                  <Text style={styles.priceText}>{item.daily_price}</Text>
                </View>
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={styles.footerLabel}>KAPASITAS</Text>
                  <Text style={styles.pilgrimText}>{item.min_pilgrims} - {item.max_pilgrims} Jamaah</Text>
                </View>
              </View>
            </TouchableOpacity>
          ))
        ) : (
          <View style={styles.emptyBox}>
            <Text style={{fontSize: 50}}>📦</Text>
            <Text style={styles.emptyText}>Layanan belum dikonfigurasi.</Text>
          </View>
        )}

      </ScrollView>
    </SafeAreaView>
    </SwipeableScreen>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FAFC' },
  header: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    paddingHorizontal: 20, 
    paddingVertical: 15,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9'
  },
  backBtn: { fontSize: 24, fontWeight: 'bold', color: '#1E293B', width: 40 },
  headerTitle: { fontSize: 18, fontWeight: '800', color: '#1E293B' },
  
  scrollContent: { padding: 20, paddingBottom: 50 },
  subTitle: { fontSize: 14, color: '#64748B', marginBottom: 25, lineHeight: 20 },
  
  serviceCard: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 20,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 15 },
  typeBadge: { backgroundColor: '#E0F2FE', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 8 },
  typeText: { color: '#0369A1', fontSize: 10, fontWeight: '800' },
  statusRow: { flexDirection: 'row', alignItems: 'center' },
  statusDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#10B981', marginRight: 6 },
  statusText: { color: '#10B981', fontSize: 12, fontWeight: '700' },
  
  serviceName: { fontSize: 18, fontWeight: '800', color: '#1E293B', marginBottom: 8 },
  serviceDesc: { fontSize: 13, color: '#64748B', lineHeight: 18, marginBottom: 15 },
  
  divider: { height: 1, backgroundColor: '#F1F5F9', marginBottom: 15 },
  
  cardFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  footerLabel: { fontSize: 10, fontWeight: '800', color: '#94A3B8', marginBottom: 4 },
  priceText: { fontSize: 16, fontWeight: '800', color: '#0984e3' },
  pilgrimText: { fontSize: 14, fontWeight: '700', color: '#1E293B' },

  emptyBox: { alignItems: 'center', marginTop: 50 },
  emptyText: { color: '#94A3B8', marginTop: 15, fontWeight: '600' },
  
  addBtn: {
    backgroundColor: '#F0F9FF',
    borderWidth: 1,
    borderColor: '#BAE6FD',
    borderStyle: 'dashed',
    padding: 18,
    borderRadius: 20,
    alignItems: 'center',
    marginTop: 10
  },
  addBtnText: { color: '#0369A1', fontWeight: '800', fontSize: 14 }
});
